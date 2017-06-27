
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

    $current_language_published = ( in_array( $TRP_LANGUAGE, $trp_settings[ 'publish-languages' ] ) );

    // todo maybe move hook (or create another one in footer) to load scripts lower
    do_action( 'trp_head' );
    ?>

    <title>Translate Press</title>
    <script type="application/javascript">
        var trp_language = '<?php echo $TRP_LANGUAGE; ?>';
        var trp_on_screen_language = '<?php echo $translation_languages[0]; ?>';
        var trp_ajax_url = '<?php echo apply_filters( 'trp_ajax_url', admin_url( 'admin-ajax.php' ) ); ?>';
    </script>
</head>
<body>
    <div id="trp-editor">
        <div id="trp-controls">
            <div class="trp-controls-container">
                <div id="trp-close-save">
                    <a id="trp-controls-close" href="<?php  echo TRP_Utils::get_current_page_url(); ?>">&times;</a>
                    <div id="trp-save-container">
                        <span id="trp-translation-saved" style="display: none"><?php _e( 'Saved!', TRP_PLUGIN_SLUG ); ?></span>
                        <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <button id="trp-save" type="submit" class="button-primary"><?php _e( 'Save translation', TRP_PLUGIN_SLUG ); ?></button>
                    </div>
                </div>
                <div class="trp-controls-section">
                    <div class="trp-controls-section-content">
                        <?php
                        if ( count( $trp_settings['translation-languages'] ) > 1 ){ ?>
                            <form id="trp-language-switch-form" action="" method="GET">
                                <select id="trp-language-select" name="lang" onchange='trpEditor.change_language( this )'>
                                    <?php foreach ( $available_languages as $code => $language ) { ?>
                                        <option value="<?php echo $code; ?>" <?php echo ( $TRP_LANGUAGE == $code ) ? 'selected' : ''; ?>> <?php echo $language; ?> </option>
                                    <?php } ?>
                                </select>
                                <input type="hidden" name="trp-edit-translation" value="true">
                            </form>
                        <?php }else{ ?>
                            <span > <?php echo $trp_settings['translation-languages'][0]; ?></span>
                        <?php } ?>

                        <div id="trp-string-list">
                            <select id="trp-string-categories" data-trp-placeholder="<?php _e( 'Select string to translate...', TRP_PLUGIN_SLUG ); ?>"></select>
                        </div>
                    </div>
                </div>
                <div class="trp-controls-section">
                    <div class="trp-controls-section-content">
                        <div id="trp-next-previous">
                            <span id="trp-previous" class="trp-next-previous-buttons">&#171; <?php _e( 'Previous', TRP_PLUGIN_SLUG ); ?></span>
                            <span id="trp-next" class="trp-next-previous-buttons"><?php _e( 'Next', TRP_PLUGIN_SLUG ); ?> &#187;</span>
                        </div>
                        <div id="<?php echo $trp_settings['default-language'];?>" class="trp-language-text trp-default-language">
                            <div class="trp-language-name"><?php _e( 'From ', TRP_PLUGIN_SLUG ); echo $available_languages[ $trp_settings['default-language'] ];  ?></div>
                            <textarea id="trp-original" disabled></textarea>
                        </div>
                        <?php
                        foreach( $translation_languages as $language ){?>
                            <div id="trp-language-<?php echo $language;?>" class="trp-language-text <?php echo ( $TRP_LANGUAGE == $trp_settings['default-language'] || $language == $TRP_LANGUAGE ) ? 'trp-current-language' : 'trp-other-language' ?>">
                                <div class="trp-language-name"><?php _e( 'To ', TRP_PLUGIN_SLUG ); echo $available_languages[ $language ]; ?> </div>
                                <textarea id="trp-translated-<?php echo $language; ?>" data-trp-translate-id=""></textarea>
                                <div class="trp-discard-changes"><?php _e( 'Discard changes', TRP_PLUGIN_SLUG );?></div>
                            </div>
                            <?php if ( $language == $TRP_LANGUAGE && count( $translation_languages ) > 1 ){
                                $other_languages = __( 'Other languages', TRP_PLUGIN_SLUG );
                                ?>
                                <div id="trp-show-all-languages" class="trp-toggle-languages">&#11208; <span><?php echo $other_languages ?></span></div>
                                <div id="trp-hide-all-languages" class="trp-toggle-languages">&#11206; <span><?php echo $other_languages ?></span></div>
                            <?php } ?>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div id="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', TRP_Utils::get_current_page_url() );?>" width="100%" height="100%"></iframe>
        </div>
    </div>
</body>
</html>



<?php //get_footer();

