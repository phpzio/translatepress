<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php do_action( 'trp_head' );
    // TODO add attribute the ID from DB to translated strings when in another language to know if they have been translated. query inversely. translated->original ?>
</head>

<body>
    <div class="trp-editor">
        <div class="trp-controls">
            <?php global $TRP_LANGUAGE;
            global $trp_settings;
            if ( count( $trp_settings['translation-languages'] ) > 1 ){ ?>
            <select class="trp-select2">
                <?php foreach ( TRP_Utils::get_language_names( $trp_settings['translation-languages'] ) as $language ) { ?>
                    <option value="<?php echo $language; ?>"> <?php echo $language; ?> </option>
                <?php } ?>
            </select>
            <?php }else{ ?>
                <span > <?php echo $trp_settings['translation-languages'][0]; ?></span>
            <?php }?>
            <h1>Translate Press</h1>
            <textarea>

            </textarea>
            <textarea>

            </textarea>
            <button type="submit" class="button-primary"><?php _e( 'Save', TRP_PLUGIN_SLUG ); ?></button>
        </div>
        <div class="trp-preview">
                <iframe class="trp-preview-iframe" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', TRP_Utils::get_current_page_url() );?>" width="100%" height="100%">
                </iframe>
        </div>
    </div>
</body>
</html>



<?php //get_footer();

