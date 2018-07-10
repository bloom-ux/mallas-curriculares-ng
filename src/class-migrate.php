<?php

namespace Curricula;

class Migrate {
    public static function get_curricula() {
        if ( ! is_callable( array('\Curriculum', 'get_mode') ) ) {
            return array();
        }
        if ( \Curriculum::get_mode() == 'multiple' ) {
            $curricula = static::get_curricula_from_contents();
		} else {
            $curricula = static::get_curricula_from_options();
		}
        return $curricula;
    }

    /**
     * Obtener mallas curriculares desde opciones del sitio. Para T2/T3 o doctorados
     * @return array Mallas curriculares
     */
    private static function get_curricula_from_options() {
        $data = get_option( 'curriculum_data' );
        if ( empty( $data ) ) {
            return array();
        }
        $curricula = array();
        foreach ( $data as $curriculum ) {
            $built = static::build_curriculum( $curriculum->name, $curriculum->groups, $curriculum->matters, $curriculum->tipo_mod, $curriculum->download_id );
            if ( ! $built instanceof Curriculum ) {
                continue;
            }
            $curricula[] = $built;
        }
        return $curricula;
    }

    /**
     * Obtener mallas curriculares asociadas a carreras o postgrados como post_meta
     * @return array Mallas curriculares
     */
    private static function get_curricula_from_contents() {
        $curricula = array();
        $academic_programs = new \WP_Query( array(
            'post_type'      => array('career', 'postgrad'),
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => true
        ) );
        if ( ! $academic_programs->have_posts() ) {
            return $curricula;
        }
        foreach ( $academic_programs->posts as $program ) {
            $program_curricula = static::build_t1_curricula( $program );
            foreach ( $program_curricula as $curriculum ) {
                $curricula[] = $curriculum;
            }
        }
        return $curricula;
    }

    /**
     * Construir mallas curriculares para carreras o postgrados a partir de post_meta
     * @param  int $post_id ID del post
     * @return array        Mallas curriculares del programa
     */
    private static function build_t1_curricula( $program ) {
        $titles    = (array) $program->_av_curricula_titles;
        $count     = max( count( $titles ), 1 );
        $curricula = array();
        for ( $i = 1; $i <= $count; $i++ ) {
            $title = isset( $titles[$i] ) ? $titles[$i] : 'Malla Curricular de '. $program->post_title;
            $curriculum = static::build_post_curriculum( $program, $i, $title );
            if ( $curriculum instanceof \Curricula\Curriculum ) {
                $curricula[] = $curriculum;
            }
        }
        return $curricula;
    }

    /**
     * Construir una malla curricular a partir de los datos del plugin v1.0
     * @param  string $title    Título de la malla
     * @param  array $groups   Grupos de ramos de la malla
     * @param  array $matters  Ramos de la malla
     * @param  string $tipo_mod Tipo de "módulo"
     * @return Curriculum           Malla Curricular
     */
    private static function build_curriculum( $title, $groups, $matters , $tipo_mod = 'semester', $download_id ) {
        if ( empty( $matters ) ) {
            return false;
        }
        /**
         * Pivote para convertir slugs de grupos (key) en IDs (val)
         * @var array
         */
        $groups_pivot = array();

        $curriculum = new Curriculum;
        $curriculum->set_title( $title );
        $i = 1; foreach ( $groups as $group_name ) {
            $group = new Group;
            $group->set_id( $i );
            $group->set_name( $group_name );
            // inventar método para definir colores default
            $group->set_color( static::get_group_color( $i, count( $groups ) ) );
            $curriculum->add_group( $group );
            $slug = sanitize_title_with_dashes( remove_accents( $group_name ) );
            $groups_pivot[ $slug ] = $i;
        ++$i; }

        /**
         * Indexar ramos por semestre
         */
        $semesters = array();
        foreach ( $matters as $matter ) {
            $semester = absint( $matter['semester'] );
            $semesters[ $semester ]['subjects'][] = $matter;
        }
        foreach ( $semesters as $key => $val ) {
            $module = new Module;
            $module->set_id( $key );
            $module->set_title( static::get_module_label( $key, $tipo_mod ) );
            $s_idx = 0; foreach ( $val['subjects'] as $sub ) {
                $subject = new Subject;
                $subject->set_id( $s_idx );
                $subject->set_title( $sub['name'] );
                if ( isset( $sub['description'] ) ) {
                    $subject->set_description( $sub['description']);
                }
                if ( isset( $groups_pivot[ $sub['group'] ] ) ) {
                    $subject->set_group( $groups_pivot[ $sub['group'] ] );
                }
                $module->add_subject( $subject );
            $s_idx++; }
            $curriculum->add_module( $module );
        }

        if ( $download_id ) {
            $curriculum->set_download( $download_id );
        }

        return $curriculum;
    }

    /**
     * Construir un objeto de malla curricular a partir de la información de un post
     * @param  WP_Post $program Post de carrera o postgrado
     * @param  int $index       Índice de la malla (1 o más)
     * @param  string $title    Título de la malla
     * @return Curriculum       Objeto de malla curricular
     */
    private static function build_post_curriculum( $program, $index, $title ) {
        $modules_meta_key = $index === 1 ? '_av_curriculum_matters' : "_av_curriculum_matters_{$index}";
        $groups_meta_key  = $index === 1 ? 'career_groups' : "career_groups_{$i}";
        $matters  = get_post_meta( $program->ID, $modules_meta_key, true );
        $groups   = get_post_meta( $program->ID, $groups_meta_key, false );
        $tipo_mod = get_post_meta( $program->ID, 'career_tipo_mod', true );
        return static::build_curriculum( $title, $groups, $matters, $tipo_mod );
    }

    /**
     * Definir un color predeterminado para un grupo de ramos
     * @param  int $index El índice del grupo actual
     * @param  int $total La cantidad total de grupos disponibles
     * @return string     Código hexadecimal de color
     * @todo
     */
    private static function get_group_color( $index, $total ) {
        $base_color = get_curriculum_base_color();
        $color      = new \Mexitek\PHPColors\Color( $base_color );
        $new_color  = $color->mix( '#ffffff', (100/($total+1)) * $index );
        return strtoupper('#'. $new_color);
    }

    private static function get_module_label( $index, $type ) {
        switch ( $type ) {
            case 'semester':
				$type_label = _x('Semestre', 'textos shortcode', 'av_curriculum');
				break;
			case 'module':
				$type_label = _x('Módulo', 'textos shortcode', 'av_curriculum');
				break;
			case 'trimester':
				$type_label = _x('Trimestre', 'textos shortcode', 'av_curriculum');
				break;
            default:
                $type_label = ucfirst( $type );
                break;
        }
        $index_label = static::get_module_index_label( $index );
        return sprintf( '%1$s %2$s', $index_label, $type_label );
    }

    /**
     * Obtener el etiquetado de módulo según su índice
     * @param  int $n Orden del módulo
     * @return string Etiqueta del orden
     */
    private static function get_module_index_label( $n ){
        $n = (int) $n;
        switch ( $n ) {
            case 1:
                return 'Primer';
                break;
            case 2:
                return 'Segundo';
                break;
            case 3:
                return 'Tercer';
                break;
            case 4:
                return 'Cuarto';
                break;
            case 5:
                return 'Quinto';
                break;
            case 6:
                return 'Sexto';
                break;
            case 7:
                return 'Séptimo';
                break;
            case 8:
                return 'Octavo';
                break;
            case 9:
                return 'Noveno';
                break;
            case 10:
                return 'Décimo';
                break;
            default:
                return (string) $n .'°';
                break;
        }
    }
}
