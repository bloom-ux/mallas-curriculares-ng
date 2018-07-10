<?php

namespace Curricula;

class Group implements \JsonSerializable {
	protected $id    = 0;
	protected $name  = '';
	protected $color = '';
	private $group   = null;
	public function __construct( $group = null ) {
		if ( isset( $group->id ) ) {
			$this->set_id( $group->id );
		}
		if ( isset( $group->name) ) {
			$this->set_name( $group->name );
		}
		if ( isset( $group->color ) ) {
			$this->set_color( $group->color );
		}
	}
	public function set_id( $id ) {
		$this->id = (int) $id;
	}
	public function set_name( $name ) {
		$this->name = sanitize_text_field( $name );
	}
	public function set_color( $color ) {
		$this->color = $color;
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
			'id'    => $this->id,
			'name'  => $this->name,
			'color' => $this->color
		);
	}
}
