
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE, $trp_settings;
    $available_languages = TRP_Utils::get_language_names( $trp_settings['translation-languages'] );

    // move the current language to the beginning of the array
    $translation_languages = $trp_settings['translation-languages'];
    if ( $TRP_LANGUAGE != $trp_settings['default-language'] ) {
        $current_language_key = array_search( $TRP_LANGUAGE, $trp_settings['translation-languages'] );
        unset( $translation_languages[$current_language_key] );
        $translation_languages = array_merge( array( $TRP_LANGUAGE ), array_values( $translation_languages ) );
    }
    $default_language_key = array_search( $trp_settings['default-language'], $translation_languages );
    unset( $translation_languages[$default_language_key] );
    $translation_languages = array_values( $translation_languages );

    do_action( 'trp_head' );
    ?>

    <script type="application/javascript">
        var trp_language = '<?php echo $TRP_LANGUAGE; ?>';
        var trp_on_screen_language = '<?php echo $translation_languages[0]; ?>';
        var trp_ajax_url = '<?php echo apply_filters( 'trp_ajax_url', admin_url( 'admin-ajax.php' ) ); ?>';
    </script>
</head>
<body>
    <div class="trp-editor">
        <div class="trp-controls">
            <div class="trp-controls-container">
                <?php
                if ( count( $trp_settings['translation-languages'] ) > 1 ){ ?>
                <select class="trp-select2">
                    <?php foreach ( $available_languages as $code => $language ) { ?>
                        <option value="<?php echo $language; ?>" <?php echo ( $TRP_LANGUAGE == $code ) ? 'selected' : ''; ?>> <?php echo $language; ?> </option>
                    <?php } ?>
                </select>
                <?php }else{ ?>
                    <span > <?php echo $trp_settings['translation-languages'][0]; ?></span>
                <?php }?>

                <button type="submit"><?php _e( 'Publish', TRP_PLUGIN_SLUG ); ?></button>
                <h1>Translate Press</h1>
                <div id="trp-string-list">
                    <select id="trp-string-categories">
                        <option><?php _e( 'Choose text category', TRP_PLUGIN_SLUG ); ?></option>
                    </select>
                    <div id="trp-lists"> </div>
                </div>

                <div id="trp-next-previous">
                    <span id="trp-previous" class="trp-next-previous-buttons">&#171; <?php _e( 'Previous', TRP_PLUGIN_SLUG ); ?></span>
                    <span id="trp-next" class="trp-next-previous-buttons"><?php _e( 'Next', TRP_PLUGIN_SLUG ); ?> &#187;</span>
                </div>
                <div id="<?php echo $trp_settings['default-language'];?>" class="trp-default-language">
                    <p><?php _e( 'From ', TRP_PLUGIN_SLUG ); echo $available_languages[ $trp_settings['default-language'] ];  ?></p>
                    <textarea id="trp-original" disabled></textarea>
                </div>
                <?php
                foreach( $translation_languages as $language ){?>
                    <div id="trp-language-<?php echo $language;//todo display status as human readable?>" class="<?php echo ( $TRP_LANGUAGE == $trp_settings['default-language'] || $language == $TRP_LANGUAGE ) ? 'trp-current-language' : 'trp-other-language' ?>">
                        <p><?php _e( 'To ', TRP_PLUGIN_SLUG ); echo $available_languages[ $language ]; ?></p>
                        <textarea id="trp-translated-<?php echo $language; ?>" data-trp-translate-id=""></textarea>
                    </div>
                    <?php if ( $language == $TRP_LANGUAGE && count( $translation_languages ) > 1 ){
                        $other_languages = __( 'Other languages', TRP_PLUGIN_SLUG );
                        ?>
                        <div id="trp-show-all-languages" class="trp-toggle-languages">&#11208; <span><?php echo $other_languages ?></span></div>
                        <div id="trp-hide-all-languages" class="trp-toggle-languages">&#11206; <span><?php echo $other_languages ?></span></div>
                    <?php } ?>
                <?php }?>
                <div>
                    <button id="trp-save" type="submit" class="button-primary"><?php _e( 'Save', TRP_PLUGIN_SLUG ); ?></button>
                </div>
            </div>
        </div>
        <div class="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', TRP_Utils::get_current_page_url() );?>" width="100%" height="100%"></iframe>
        </div>
    </div>
</body>
</html>



<?php //get_footer();

