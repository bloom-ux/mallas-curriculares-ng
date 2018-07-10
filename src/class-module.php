<?php

namespace Curricula;

class Module implements \JsonSerializable {
	protected $id       = 0;
	protected $subjects = array();
	protected $title    =  '';
	public function __construct( $module = null ){
		if ( isset( $module->id ) ) {
			$this->set_id( $module->id );
		}
		if ( isset( $module->title ) ) {
			$this->set_title( $module->title );
		}
		if ( ! empty( $module->subjects ) ) {
			foreach ( $module->subjects as $subject ) {
				$this->subjects[] = new Subject( $subject );
			}
		}
	}
	public function set_id( $id ){
		$this->id = (int) $id;
	}
	public function add_subject( Subject $subject ) {
		$this->subjects[] = $subject;
	}
	public function set_title( $title ){
		$this->title = sanitize_text_field( $title );
	}
	public function jsonSerialize(){
		return (object) array(
			'id'       => $this->id,
			'title'    => $this->title,
			'subjects' => $this->subjects
		);
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
}
