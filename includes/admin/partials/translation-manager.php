
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE, $trp_settings;
    $available_languages = TRP_Utils::get_language_names( $trp_settings['translation-languages'] );

    do_action( 'trp_head' );
    // TODO add attribute the ID from DB to translated strings when in another language to know if they have been translated. query inversely. translated->original ?>

    <script type="application/javascript">
        var TRP_LANGUAGE = '<?php echo $TRP_LANGUAGE; ?>';
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

                <button type="submit"> <?php _e( 'Publish', TRP_PLUGIN_SLUG ); ?></button>
                <h1>Translate Press</h1>
                <ul id="trp-controls-accordion">
                    <li id="trp-main-section" class="trp-section trp-current">
                        <ul id="trp-tabs">
                            <li data-tab="trp-meta-information">Meta information</li>
                            <li data-tab="trp-string-list">String List</li>
                        </ul>
                        <p><?php _e( 'From ', TRP_PLUGIN_SLUG ); echo $available_languages[ $trp_settings['default-language'] ];  ?></p>
                        <textarea id="trp-original" disabled></textarea>
                        <?php
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

                        foreach( $translation_languages as $language ){ ?>
                            <div id="<?php echo $language; //todo display status as human readable?> ">
                                <p><?php _e( 'To ', TRP_PLUGIN_SLUG ); echo $available_languages[ $language ]; ?></p>
                                <textarea id="trp-translated-<?php echo $language; ?>" data-trp-translate-id=""></textarea>
                            </div>
                        <?php }?>
                        <div>
                            <button id="trp-save" type="submit" class="button-primary"><?php _e( 'Save', TRP_PLUGIN_SLUG ); ?></button>
                        </div>
                    </li>

                    <li id="trp-meta-information" class="trp-section">
                        <h3>Meta information</h3>
                        <p><?php _e( 'From ', TRP_PLUGIN_SLUG ); echo $available_languages[ $trp_settings['default-language'] ];  ?></p>
                        <textarea id="trp-original-meta"></textarea>
                        <p><?php _e( 'To ', TRP_PLUGIN_SLUG ); echo $available_languages[ $TRP_LANGUAGE ];  ?></p>
                        <textarea id="trp-translated-meta"></textarea>
                        <div>
                            <button id="trp-save-meta" type="submit" class="button-primary"><?php _e( 'Save', TRP_PLUGIN_SLUG ); ?></button>
                        </div>
                    </li>

                    <li id="trp-string-list" class="trp-section">
                        <h3>String List</h3>
                        <p><?php _e( 'From ', TRP_PLUGIN_SLUG ); echo $available_languages[ $trp_settings['default-language'] ];  ?></p>
                        <textarea id="trp-original-list"></textarea>
                        <p><?php _e( 'To ', TRP_PLUGIN_SLUG ); echo $available_languages[ $TRP_LANGUAGE ];  ?></p>
                        <textarea id="trp-translated-list"></textarea>
                        <div>
                            <button id="trp-save-list" type="submit" class="button-primary"><?php _e( 'Save', TRP_PLUGIN_SLUG ); ?></button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', TRP_Utils::get_current_page_url() );?>" width="100%" height="100%"></iframe>
        </div>
    </div>
</body>
</html>



<?php //get_footer();

