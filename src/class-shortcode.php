<?php

namespace Curricula;

class Shortcode {
	public function init() {
		add_shortcode( $this->get_tag(), array( $this, 'do_shortcode') );
		add_action('register_shortcode_ui', array( $this, 'register_admin_ui') );
	}
	public function get_tag() {
		return 'curriculum';
	}
	public function get_label() {
		return _x('Malla Curricular', 'shortcode label', 'bloom_curricula');
	}
	public function do_shortcode( $atts = array(), $content = '' ) {
		$defaults = apply_filters( 'curricula_shortcode_defaults', array(
			'curriculum_id' => 0,
			'download_id' => 0,
			'disclaimer' => ''
		), $atts, $content );
		$atts = apply_filters( 'curricula_shortcode_atts', shortcode_atts( $defaults, $atts ), $atts, $defaults, $content );

		$curriculum = new Curriculum( $atts['curriculum_id'] );
		$download   = get_post( $atts['download_id'] );

		// permitir que un tema o plugin defina su propia plantilla
		$template_name = apply_filters( 'curricula_shortcode_template', '', $atts );

		ob_start();
		if ( ! empty( $template_name ) ) {
			if ( stripos( $template_name, ABSPATH ) !== false ) {
				// si es una ruta absoluta, usemos eso directamente
				$template = $template_name;
			} else {
				// si es una ruta relativa, tratemos de ubicarla respecto al tema
				$template = locate_template( $template_name, false, false );
			}
			include $template;
		} else {
			include __DIR__ .'/../partials/curriculum-shortcode.php';
		}
		return ob_get_clean();
	}
	public function register_admin_ui() {
		$ui_params = apply_filters( 'curricula_shortcode_ui_params', array(
			'label'         => $this->get_label(),
			'listItemImage' => 'dashicons-schedule',
			'post_type'     => array( 'post', 'page' ),
			'attrs'         => array(
				array(
					'label' => esc_html_x('Selecciona la malla', 'shortcode ui', 'bloom_curricula'),
					'attr'  => 'curriculum_id',
					'type'  => 'post_select',
					'query' => array(
						'post_type' => 'curriculum'
					)
				),
				array(
					'label'       => esc_html_x('Selecciona el archivo descargable', 'shortcode ui', 'bloom_curricula'),
					'attr'        => 'download_id',
					'type'        => 'attachment',
					'libraryType' => array( 'application' ),
					'multiple'    => false,
					'addButton'   => esc_html_x('Seleccionar', 'shortcode ui', 'bloom_curricula'),
					'frameTitle'  => esc_html_x('Selecciona el archivo descargable', 'shortcode ui', 'bloom_curricula')
				),
				array(
					'label'  => esc_html_x('Texto advertencia', 'shortcode ui', 'bloom_curricula'),
					'attr'   => 'disclaimer',
					'type'   => 'textarea',
					'encode' => true
				)
			)
		) );
		shortcode_ui_register_for_shortcode( $this->get_tag(), $ui_params );
	}
}