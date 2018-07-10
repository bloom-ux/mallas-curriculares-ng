<?php

namespace Curricula;

class Admin {
	private static $instance;

	/**
	 * Inicializar acciones administrativas
	 */
	public function init() {
		add_action('edit_form_after_editor', array($this, 'configure_courses_ui'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('save_post_curriculum', array($this, 'save_curriculum'));
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'generate_export'));
		add_action('admin_init', array($this, 'do_migration'));
		add_filter('manage_curriculum_posts_columns', array($this, 'filter_admin_columns'));
		add_action('manage_curriculum_posts_custom_column', array($this, 'do_admin_columns'), 10, 2);
	}

	/**
	 * Agregar columnas de administración a listado de mallas
	 * @param  array $cols  Columnas del listado
	 * @return array        Columnas filtradas
	 */
	public function filter_admin_columns( $cols ) {
		$last = array_slice( $cols, -1, 1 );
		array_pop( $cols );
		$cols['modules'] = 'Módulos (semestres)';
		$cols['groups'] = 'Grupos de ramos';
		$cols[ key($last) ] = current( $last );
		return $cols;
	}

	/**
	 * Output de columnas administrativas
	 * @param  string $column  Identificador de la columna
	 * @param  int $post_id ID del post
	 */
	public function do_admin_columns( $column, $post_id ) {
		$curriculum = new Curriculum( $post_id );
		switch ( $column ) {
			case 'modules':
				echo count( $curriculum->get_modules() );
				break;
			case 'groups':
				$names = (array) array_map( function( $group ){
					return $group->name;
				}, $curriculum->get_groups() );
				echo implode('; ', $names);
				break;
		}
	}

	/**
	 * Ejecutar procesos de migración de mallas desde formatos antiguos a nuevo
	 */
	public function do_migration() {
		if ( ! isset($_POST['action']) || $_POST['action'] !== 'curriculum__migrate' ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['_curriculum__migrate-nonce'], 'curriculum__migrate') ) {
			wp_die( 'No puedes realizar esta acción', 'Acceso prohibido', array(
				'response' => 403
			));
		}
		if ( empty( $_POST['migrate_curriculum']) ) {
			wp_die( 'Debes seleccionar al menos una malla curricular para migrar', 'Petición errónea', array(
				'response'  => 400,
				'back_link' => true
			));
		}
		$selected_indexes    = array_map( 'absint', $_POST['migrate_curriculum'] );
		$available_curricula = Migrate::get_curricula();
		foreach ( $selected_indexes as $index ) {
			if ( ! isset( $available_curricula[ $index] ) ) {
				continue;
			}
			$available_curricula[ $index ]->insert();
		}
		wp_safe_redirect( add_query_arg( array(
			'page'   => 'curriculum__migrate',
			'status' => 'ok'
		) , admin_url('edit.php?post_type=curriculum') ), 303 );
		exit;
	}

	public function generate_export() {
		if ( ! isset($_POST['action']) || $_POST['action'] !== 'curriculum__export' ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['_curriculum__export-nonce'], 'curriculum__export' ) ) {
			wp_die('No estás autorizado a hacer esto', 'No autorizado', array('status' => 403 ));
		}
		if ( filter_input( INPUT_POST, 'curriculum_export_all') ) {
			$args = array(
				'posts_per_page' => -1,
				'post_type'      => 'curriculum'
			);
		} else {
			$args = array(
				'posts_per_page' => -1,
				'post_type' => 'curriculum',
				'post__in' => array_map('absint', $_POST['curriculum_export_ids'])
			);
		}
		$curricula = Curriculum::get( $args );
		$response  = array();
		if ( $curricula->have_posts() ) {
			foreach ( $curricula->posts as $post ) {
				$curriculum = new Curriculum( $post );
				$response[] = $curriculum;
			}
		}
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		@header( 'Content-Disposition: attachment; filename=curricula.json' );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Definir menús de administración
	 * @todo Importación de mallas
	 */
	public function add_admin_menu() {
		// add_submenu_page( 'edit.php?post_type=curriculum', 'Exportar Mallas Curriculares', 'Exportar', 'edit_pages', 'curriculum__export', array( $this, 'export_ui') );
		add_submenu_page( 'edit.php?post_type=curriculum', 'Migrar Mallas Curriculares', 'Migrar', 'edit_pages', 'curriculum__migrate', array( $this, 'migrate_ui') );
		// add_submenu_page( 'edit.php?post_type=curriculum', 'Importar Mallas Curriculares', 'Importar', 'edit_pages', 'curriculum__import', array ( $this, 'import_ui') );
	}

	/**
	 * Definir interfaz para ejecutar migraciones de mallas
	 */
	public function migrate_ui() {
		$curricula = Migrate::get_curricula();
		$notice    = filter_input( INPUT_GET, 'status' );
		require_once __DIR__ .'/../admin/migrate.php';
	}

	public function import_ui() {
		require_once __DIR__ .'/../admin/import.php';
	}
	public function export_ui() {
		$curricula = new \WP_Query( array(
			'post_type' => 'curriculum',
			'posts_per_page' => -1
		) );
		require_once __DIR__ .'/../admin/export.php';
	}

	/**
	 * Guardar los datos de una malla curricular a partir de los datos enviados por $_POST
	 * @param  int $post_id ID de la malla que se está guardando
	 */
	public function save_curriculum( $post_id ) {
		if ( ! isset($_POST['groups'], $_POST['modules']) ) {
			return;
		}
		$groups  = json_decode( filter_input( INPUT_POST, 'groups' ) );
		$modules = json_decode( filter_input( INPUT_POST, 'modules' ) );
		$this->save( $post_id, $groups, $modules );
	}

	/**
	 * Guardar los datos de una malla curricular
	 * @param  int $post_id ID del post
	 * @param  array $groups  Grupos de ramos
	 * @param  array $modules Módulos (semestres) con ramos
	 */
	private function save( $post_id, $groups, $modules ) {
		$sanitized_groups = array();
		foreach ( $groups as $group ) {
			$g = new Group( $group );
			$sanitized_groups[] = $g;
		}
		foreach ( $modules as $mod ) {
			$module = new Module( $mod );
			$sanitized_modules[] = $module;
		}
		update_post_meta( $post_id, '_groups', $sanitized_groups );
		update_post_meta( $post_id, '_modules', $sanitized_modules );
	}

	/**
	 * Obtener instancia de la administración de mallas
	 * @return Curricula\Admin Instancia de la administración de mallas
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	public function admin_enqueue_scripts() {
		global $post;
		$screen = get_current_screen();
		if ( $screen->id != 'curriculum' )
			return;

		wp_enqueue_style( 'curriculum', plugins_url('css/admin.css', dirname( __FILE__ ) ), array(), CURRICULUM_VERSION );
		wp_enqueue_script( 'vue.js', plugins_url( 'node_modules/vue/dist/vue.js', dirname( __FILE__ ) ), array(), '2.5.16', true );
		wp_enqueue_script( 'sortable.js', plugins_url( 'node_modules/sortablejs/Sortable.min.js', dirname( __FILE__ ) ), array(), '1.7.0', true );
		wp_enqueue_script( 'vue.draggable', plugins_url( 'node_modules/vuedraggable/dist/vuedraggable.js', dirname( __FILE__ ) ), array('vue.js'), '2.16.0', true );
		wp_enqueue_script( 'vue.color', plugins_url( 'node_modules/vue-color/dist/vue-color.min.js', dirname( __FILE__ ) ), array('vue.js'), '2.4.6', true );
		wp_enqueue_script( 'curriculum', plugins_url( 'js/admin.js', dirname( __FILE__ ) ), array('sortable.js', 'vue.draggable', 'vue.color', 'underscore'), CURRICULUM_VERSION, true );

		$faculty_default_color = get_curriculum_base_color();

		$curriculum = new Curriculum( $post->ID );

		$groups  = $curriculum->get_groups();
		$modules = $curriculum->get_modules();

		$curriculum_data = array(
			'default_color' => array(
				'hex' => $faculty_default_color
			),
			'new_module' => array(
				'is_editing' => false,
				'id'         => null,
				'title'      => '',
				'subjects'   => array()
			),
			'new_group' => array(
				'is_editing'  => false,
				'is_creating' => false,
				'name'        => '',
				'color'       => $faculty_default_color
			),
			'new_subject' => array(
				'module'      => 0,
				'is_editing'  => false,
				'is_creating' => false,
				'title'       => '',
				'group'       => 1,
				'description' => ''
			),
			'undo'    => array(
				'available' => false,
				'msg'       => ''
			),
			'groups'  => $groups,
			'modules' => $modules
		);

		wp_localize_script( 'curriculum',  'Curriculum_Data', $curriculum_data );
	}

	/**
	 * Definir la interfaz de administración de una malla curricular.
	 * Incluye una vista de plantilla para vue.js
	 * @param  WP_Post $post Objeto de post de la malla
	 */
	public function configure_courses_ui( $post ) {
		if ( isset($post->post_type) && $post->post_type == 'curriculum' ) {
			require_once __DIR__ .'/../admin/configure-courses.php';
		}
	}
}
