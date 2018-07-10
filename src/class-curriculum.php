<?php

namespace Curricula;
use WP_Error;

class Curriculum implements \JsonSerializable {
	protected $post     = null;
	protected $title    = '';
	protected $groups   = array();
	protected $modules  = array();
	protected $post_id  = 0;
	protected $download = 0;
	private static $query_args = array(
		'post_type' => 'curriculum'
	);

	/**
	 * Construir un objeto de malla a partir de un Post o ID de post
	 * @param  mixed $post_or_id ID u objeto de post de la malla
	 */
	public function __construct( $post_or_id = null ) {
		if ( ! is_null( $post_or_id ) ) {
			$post = get_post( $post_or_id );
			if ( $post->post_type == 'curriculum' ) {
				$this->post = $post;
				$this->set_post_id( $this->post->ID );
				$this->set_title( $this->post->post_title );
				if ( $this->post->curriculum_download ) {
					$this->set_download( $this->post->curriculum_download );
				}
				$this->load_groups();
				$this->load_modules();
			}
		}
	}

	/**
	 * Cargar datos de "grupos" desde BBDD
	 */
	private function load_groups() {
		$groups = get_post_meta( $this->post->ID, '_groups', true );
		if ( empty( $groups ) ) {
			return array();
		}
		foreach ( $groups as $group ) {
			$this->groups[] = new Group( $group );
		}
	}

	/**
	 * Cargar datos de "módulos" desde BBDD
	 */
	private function load_modules() {
		$modules = get_post_meta( $this->post->ID, '_modules', true );
		if ( empty( $modules ) ) {
			return array();
		}
		foreach ( $modules as $module ) {
			$this->modules[] = new Module( $module );
		}
	}

	/**
	 * Indicar el título de la malla
	 * @param string $title Título para la malla
	 */
	public function set_title( $title ) {
		$this->title = sanitize_text_field( $title );
	}

	/**
	 * Indicar el Post ID de la Malla. Sólo aplica si es una malla que ya
	 * existe en base de datos
	 * @param int $post_id ID de la malla como post en bbdd
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = (int) $post_id;
	}

	/**
	 * Indicar el ID de la descarga con registro asociada a la malla
	 * @param int $id ID de la descarga con registro
	 */
	public function set_download( $id ) {
		$this->download = (int) $id;
	}

	/**
	 * Obtener mallas desde base de datos
	 * @param  array  $args Parámetros de búsqueda, compatible con WP_Query
	 * @return array        Objetos de post de mallas
	 */
	public static function get( $args = array() ) {
		$args = wp_parse_args( $args, static::$query_args );
		return new \WP_Query( $args );
	}

	/**
	 * Insertar la malla en base de datos
	 * @return int|WP_Error ID del post si todo OK, WP_Error si hay problemas
	 */
	public function insert() {
		$postdata = array(
			'post_title'   => $this->title,
			'post_type'    => 'curriculum',
			'post_content' => '',
			'meta_input'   => array(
				'_groups'             => $this->get_groups(),
				'_modules'            => $this->get_modules(),
				'curriculum_download' => $this->get_download_id()
			)
		);
		return wp_insert_post( $postdata, true );
	}

	/**
	 * Actualizar la malla curricular
	 * @return int|WP_Error ID del post si todo OK, WP_Error si hay problemas
	 */
	public function update() {
		if ( ! $this->id ) {
			return new \WP_Error('curriculum_not_exists', 'La malla debe existir en base de datos y tener un ID definido');
		}
		return wp_update_post( array(
			'ID'           => $this->id,
			'post_title'   => $this->title,
			'post_type'    => 'curriculum',
			'post_content' => '',
			'meta_input'   => array(
				'_groups'     => $this->get_groups(),
				'_modules'    => $this->get_modules()
			)
		), true );
	}

	/**
	 * Indica si el objeto tiene la propiedad indicada
	 * @param  string  $key Nombre de la propiedad
	 * @return boolean      Verdadero si el objeto tiene la propiedad
	 */
	public function __isset( $key ) {
		return isset( $this->$key );
	}

	/**
	 * Habilita acceso lectura a propiedades protegidas/privadas
	 * @param  string $key Nombre de la propiedad a obtener
	 * @return mixed      Valor de la propiedad, si existe, o nulo
	 */
	public function __get( $key ) {
		return isset( $this->$key ) ? $this->$key : null;
	}

	/**
	 * Obtener grupos de ramos de la malla curricular
	 * @return array Arreglo de objetos Group
	 */
	public function get_groups() {
		return $this->groups;
	}

	public function get_group( $id ) {
		foreach ( $this->get_groups() as $group ) {
			if ( $id === $group->id ) {
				return $group;
			}
		}
		return null;
	}

	/**
	 * Añadir un grupo de ramos a la malla
	 * @param  Group $group Grupo de ramos
	 * @author Felipe Lavín <felipe@yukei.net>
	 * @return Curriculum Instancia de la Malla
	 */
	public function add_group( Group $group ) {
		$this->groups[] = $group;
		return $this;
	}

	/**
	 * Añadir un módulo (semestre) a la malla
	 * @param Module $module Objeto de módulo, con sus respectivos ramos
	 */
	public function add_module( Module $module ) {
		$this->modules[] = $module;
		return $this;
	}

	/**
	 * Obtener módulos de la malla curricular
	 * @return array Array con objetos de módulos
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Obtener el ID de la descarga asociada
	 * @return int ID de la descarga
	 */
	public function get_download_id() {
		return (int) $this->download;
	}

	/**
	 * Obtener el objeto de post de la descarga asociada
	 * @return WP_Post Descarga asociada
	 */
	public function get_download() {
		return get_post( $this->get_download_id() );
	}

	/**
	 * Definir representación en json de la malla
	 * @return object Representación simplificada de la malla para json
	 */
	public function jsonSerialize() {
		return (object) array(
			'id'          => $this->post->ID,
			'title'       => $this->title,
			'groups'      => $this->get_groups(),
			'modules'     => $this->get_modules(),
			'download_id' => $this->get_download_id()
		);
	}

	/**
	 * Registrar el tipo de post de mallas curriculares
	 * @internal
	 */
	public static function register_post_type() {
		register_post_type( 'curriculum', array(
			'label'  => 'Mallas Curriculares',
			'labels' => array(
				'name'          => 'Mallas Curriculares',
				'singular_name' => 'Malla Curricular',
			),
			'public'               => false,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'menu_icon'            => 'dashicons-schedule',
			'capability_type'      => 'page',
			'map_meta_cap'         => true,
			'hierarchical'         => false,
			'supports'        => array(
				'title',
				'thumbnail'
			),
			'has_archive' => false,
			'can_export'  => false,
		) );
	}
}
