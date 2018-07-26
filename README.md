# Instalación:

Con [https://getcomposer.org/](Composer).

Añadir el repositorio a composer.json, p.ej:

```
"repositories" : [
	{
		"type": "vcs",
		"url": "https://github.com/bloom-ux/mallas-curriculares-ng.git"
	},
	{
		"type":"composer",
		"url":"https://wpackagist.org"
	}
]
```

En el ejemplo, el primer repositorio corresponde al repositorio del plugin en GitHub. El segundo corresponde a el repositorio de [WordPress Packagist](https://wpackagist.org/), que es necesario para instalar plugins mediante Composer.

Luego de descargar el plugin, ingresar a su carpeta de instalación (p.ej: wp-content/plugins/mallas-curriculares-ng) y ejecutar `yarn install` para instalación de dependencias de javascript (o `npm install`)

# Visualización de la malla:

El plugin se integra con [Shortcode UI](https://wordpress.org/plugins/shortcode-ui/) para permitir insertar una malla en un contenido.

El plugin genera una visualización default básica, pero ésta se puede personalizar completamente según se necesite.

Para esto, se debe aplicar un filtro a través de una función del tema o plugin en el hook `curricula_shortcode_template`, p.ej:

```
add_filter('curricula_shortcode_template', function( $template, $atts ){
	return 'template-parts/curriculum.php';
}, 10, 2);
```

La función que se engancha al filtro debe retornar el nombre de un archivo con ruta absoluta, o relativa respecto del tema o plantilla (busca primero en el tema hijo y luego en el tema padre).

La plantilla recibirá las siguientes variables:

* `$atts : array` Atributos que recibe el shortcode
* `$curriculum : Curricula\Curriculum` Objeto de Malla Curricular
* `$download: WP_Post` Objeto de post de la descarga asociada a la malla