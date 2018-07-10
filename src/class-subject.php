<?php

namespace Curricula;

class Subject implements \JsonSerializable {
	protected $id          = 0;
	protected $title       = '';
	protected $description = '';
	protected $credits     = 0;
	protected $group       =  null;
	public function __construct( $subject = null ) {
		if ( isset($subject->id) ) {
			$this->set_id( $subject->id );
		}
		if ( isset( $subject->title ) ) {
			$this->set_title( $subject->title );
		}
		if ( isset( $subject->description ) ) {
			$this->set_description( $subject->description );
		}
		if ( isset( $subject->group ) ) {
			$this->set_group( $subject->group );
		}
		if ( isset( $subject->credits ) ) {
			$this->set_credits( $subject->credits );
		}
	}
	public function set_id( $id ) {
		$this->id = (int) $id;
	}
	public function set_group( $group_id ) {
		$this->group = (int) $group_id;
	}
	public function set_title( $title ) {
		$this->title = sanitize_text_field( $title );
	}
	public function set_credits( $credits ) {
		$this->credits = absint( $credits );
	}
	public function set_description( $description ) {
		static $sanitize_textarea_exists;
		$sanitize_textarea_exists = function_exists('sanitize_textarea_field');
		$this->description        = $sanitize_textarea_exists ? sanitize_textarea_field( $description ) : wp_strip_all_tags( $description );
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

	public function jsonSerialize() {
		return (object) array(
			'id'          => $this->id,
			'title'       => $this->title,
			'description' => $this->description,
			'credits'     => $this->credits,
			'group'       => $this->group
		);
	}
}
