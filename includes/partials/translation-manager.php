
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE;
    $trp = TRP_Translate_Press::get_trp_instance();
    $trp_languages = $trp->get_component( 'languages' );
    $settings_component = $trp->get_component( 'settings' );
    $url_converter = $trp->get_component('url_converter');
    $trp_settings = $settings_component->get_settings();

    $available_languages = $trp_languages->get_language_names( $trp_settings['translation-languages'] );

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

    $current_language_published = ( in_array( $TRP_LANGUAGE, $trp_settings[ 'publish-languages' ] ) );
    $current_url = $url_converter->cur_page_url();

    do_action( 'trp_head' );
    ?>

    <title>TranslatePress</title>
    <script type="application/javascript">
        var trp_language = '<?php echo $TRP_LANGUAGE; ?>';
        var trp_on_screen_language = '<?php echo ( isset( $translation_languages[0] ) ) ? $translation_languages[0] : 'null' ; ?>';
        var trp_ajax_url = '<?php echo apply_filters( 'trp_ajax_url', admin_url( 'admin-ajax.php' ) ); ?>';
    </script>
</head>
<body>
    <div id="trp-editor">
        <div id="trp-controls">
            <div class="trp-controls-container">
                <div id="trp-close-save">
                    <a id="trp-controls-close" href="#">&times;</a>
                    <div id="trp-save-container">
                        <span id="trp-translation-saved" style="display: none"><?php _e( 'Saved!', TRP_PLUGIN_SLUG ); ?></span>
                        <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <button id="trp-save" type="submit" class="button-primary trp-save-string"><?php _e( 'Save translation', TRP_PLUGIN_SLUG ); ?></button>
                    </div>
                </div>
                <div class="trp-controls-section">
                    <div class="trp-controls-section-content">
                        <form id="trp-language-switch-form" action="" method="GET">
                            <select id="trp-language-select" name="lang" onchange='trpEditor.change_language( this )'>
                                <?php foreach ( $available_languages as $code => $language ) { ?>
                                    <option value="<?php echo $code; ?>" <?php echo ( $TRP_LANGUAGE == $code ) ? 'selected' : ''; ?>> <?php echo $language; ?> </option>
                                <?php } ?>
                            </select>
                            <input type="hidden" name="trp-edit-translation" value="true">
                        </form>
                        <div id="trp-string-list">
                            <select id="trp-string-categories" data-trp-placeholder="<?php _e( 'Select string to translate...', TRP_PLUGIN_SLUG ); ?>">
                                <?php //add here an optiongroup so we know to add all the gettext strings below this and all the other strings above this ?>
                                <optgroup id="trp-gettext-strings-optgroup" label="<?php _e( 'Gettext strings', TRP_PLUGIN_SLUG ); ?>"></optgroup>
                            </select>
                        </div>
                        <?php if( count( $trp_settings['translation-languages'] ) > 1 ) { ?>
                            <div id="trp-next-previous">
                                <button type="button" id="trp-previous" class="trp-next-previous-buttons"><span>&laquo;</span> <?php _e( 'Previous', TRP_PLUGIN_SLUG ); ?></button>
                                <button type="button" id="trp-next" class="trp-next-previous-buttons"><?php _e( 'Next', TRP_PLUGIN_SLUG ); ?> <span>&raquo;</span></button>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if( count( $trp_settings['translation-languages'] ) > 1 ) { ?>
                        <br class="clear">
                    <?php } ?>
                </div>
                <div class="trp-controls-section">
                    <div class="trp-controls-section-content">
                        <?php if ( count( $trp_settings['translation-languages'] ) > 1 ){ ?>

                            <?php //original strings for gettext textarea ?>
                            <div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">
                                <div class="trp-language-name"><?php _e( 'Original String', TRP_PLUGIN_SLUG );?></div>
                                <textarea id="trp-gettext-original-textarea" disabled></textarea>
                            </div>

                            <div id="trp-language-<?php echo $trp_settings['default-language'];?>" class="trp-language-text trp-default-language">
                                <?php $default_language_name =  $available_languages[ $trp_settings['default-language'] ];?>
                                <div class="trp-language-name" data-trp-gettext-language-name="<?php echo sprintf( __( 'To %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>" data-trp-default-language-name="<?php echo sprintf( __( 'From %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>">
                                    <?php echo sprintf( __( 'From %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>
                                </div>
                                <textarea id="trp-original" data-trp-language-code="<?php echo esc_attr( $trp_settings['default-language'] ); ?>" disabled></textarea>
                                <div class="trp-discard-changes trp-discard-on-default-language" style="display:none;"><?php _e( 'Discard changes', TRP_PLUGIN_SLUG );?></div>
                            </div>
                            <?php
                            foreach( $translation_languages as $language ){?>
                                <div id="trp-language-<?php echo $language;?>" class="trp-language-text <?php echo ( $TRP_LANGUAGE == $trp_settings['default-language'] || $language == $TRP_LANGUAGE ) ? 'trp-current-language' : 'trp-other-language' ?>">
                                    <div class="trp-language-name"><?php echo sprintf( __( 'To %s', TRP_PLUGIN_SLUG ), $available_languages[ $language ] ); ?></div>
                                    <textarea id="trp-translated-<?php echo $language; ?>" data-trp-translate-id="" data-trp-language-code="<?php echo esc_attr( $language ); ?>"></textarea>
                                    <div class="trp-discard-changes"><?php _e( 'Discard changes', TRP_PLUGIN_SLUG );?></div>
                                </div>
                                <?php if ( $language == $TRP_LANGUAGE && count( $translation_languages ) > 1 ){
                                    $other_languages = __( 'Other languages', TRP_PLUGIN_SLUG );
                                    ?>
                                    <div id="trp-show-all-languages" class="trp-toggle-languages"><span>&#11208; <?php echo $other_languages ?></span></div>
                                    <div id="trp-hide-all-languages" class="trp-toggle-languages trp-toggle-languages-active"><span>&#11206; <?php echo $other_languages ?></span></div>
                                <?php } ?>
                            <?php }?>
                        <?php } else{ ?>
                            <div> <?php printf( __( 'No languages set for translation. Please select a translation language from <a href="%s">Settings->TranslatePress</a>', TRP_PLUGIN_SLUG ), admin_url( 'options-general.php?page=translate-press' ) );?></div>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div id="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', $current_url );?>" width="100%" height="100%"></iframe>
        </div>
    </div>
</body>
</html>



<?php

