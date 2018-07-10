<div class="wrap">
    <h1>Migrar Mallas Curriculares</h1>
    <?php if ( $notice && $notice == 'ok') : ?>
        <div class="notice notice-success">
            <p>Las mallas seleccionadas fueron importadas correctamente. <a href="<?php echo admin_url('edit.php?post_type=curriculum') ?>">Editar</a></p>
        </div>
    <?php endif; ?>
    <?php if ( $curricula ) : ?>
        <p class="description">Con esta funcionalidad puedes convertir las mallas creadas con el antiguo editor al nuevo formato</p>
        <form method="post">
            <ul>
                <?php $i=0; foreach ( $curricula as $curriculum ) : ?>
                <li><label><input type="checkbox" name="migrate_curriculum[]" value="<?php echo $i ?>"> <?php echo $curriculum->title ?></label></li>
                <?php ++$i; endforeach; ?>
            </ul>
            <input type="hidden" name="action" value="curriculum__migrate">
            <?php wp_nonce_field( 'curriculum__migrate', '_curriculum__migrate-nonce') ?>
            <?php submit_button('Migrar mallas seleccionadas') ?>
        </form>
    <?php else : ?>
        <div class="notice notice-warning"><p>No existen mallas con el formato antiguo :-)</p></div>
    <?php endif; ?>
</div>
