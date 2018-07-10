<?php
/*
Plugin Name: Mallas Curriculares (Bloom UX)
Plugin URI: https://bloom-ux.com
Description: Administración avanzada de Mallas Curriculares
Version: 0.1.0
Author: Felipe Lavín - Bloom User Experience
Author URI: https://bloom-ux.com
License: GPL-3.0-or-later
*/
use Curricula;

define('CURRICULUM_VERSION', '0.1.0');

// autoloader de dependencias via composer, si no se ha cargado ya
if ( ! class_exists('Curricula\Admin') && is_readable( __DIR__ .'/vendor/autoload.php' ) ) {
	require_once __DIR__ .'/vendor/autoload.php';
}

add_action('plugins_loaded', function(){
	if ( is_admin() ){
		$admin = new Curricula\Admin;
		$admin->init();
	}
});
add_action('init', function(){
	Curricula\Curriculum::register_post_type();
});

/**
 * Definir un color base para la creación de nuevos grupos
 * @return string Color hexadecimal
 */
function get_curriculum_base_color() {
	return apply_filters('curriculum_base_color', '#014C8F' );
}
