
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
                    <a id="trp-controls-close" href="#"></a>
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
                            <select id="trp-language-select" onchange='trpEditor.change_language( this )'>
                                <?php foreach ( $available_languages as $code => $language ) { ?>
                                    <option value="<?php echo $code; ?>" <?php echo ( $TRP_LANGUAGE == $code ) ? 'selected' : ''; ?>> <?php echo $language; ?> </option>
                                <?php } ?>
                            </select>
                            <input type="hidden" name="trp-edit-translation" value="true">
                        </form>
                        <div id="trp-string-list">
                            <select id="trp-string-categories" data-trp-placeholder="<?php _e( 'Select string to translate...', TRP_PLUGIN_SLUG ); ?>">
                                <option value="" class="default-option"></option>
                                <?php //add here an optiongroup so we know to add all the gettext strings below this and all the other strings above this ?>
                                <optgroup id="trp-gettext-strings-optgroup" label="<?php _e( 'Gettext strings', TRP_PLUGIN_SLUG ); ?>"></optgroup>
                            </select>
                        </div>

                        <div id="trp-next-previous">
                            <button type="button" id="trp-previous" class="trp-next-previous-buttons"><span>&laquo;</span> <?php _e( 'Previous', TRP_PLUGIN_SLUG ); ?></button>
                            <button type="button" id="trp-next" class="trp-next-previous-buttons"><?php _e( 'Next', TRP_PLUGIN_SLUG ); ?> <span>&raquo;</span></button>
                        </div>

                    </div>
                    <br class="clear">

                </div>
                <div class="trp-controls-section">
                    <div id="trp-translation-section" class="trp-controls-section-content">
                        <div id="trp-unsaved-changes-warning-message" style="display:none"><?php _e( 'You have unsaved changes!', TRP_PLUGIN_SLUG );?></div>


                            <?php //original strings for gettext textarea ?>
                            <div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">
                                <div class="trp-language-name"><?php _e( 'Original String', TRP_PLUGIN_SLUG );?></div>
                                <textarea id="trp-gettext-original-textarea" readonly="readonly"></textarea>
                            </div>

                            <div id="trp-language-<?php echo $trp_settings['default-language'];?>" class="trp-language-text trp-default-language">
                                <?php $default_language_name =  $available_languages[ $trp_settings['default-language'] ];?>
                                <div class="trp-language-name" data-trp-gettext-language-name="<?php echo sprintf( __( 'To %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>" data-trp-default-language-name="<?php echo sprintf( __( 'From %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>">
                                    <?php echo sprintf( __( 'From %s', TRP_PLUGIN_SLUG ), $default_language_name ); ?>
                                </div>
                                <textarea id="trp-original" data-trp-language-code="<?php echo esc_attr( $trp_settings['default-language'] ); ?>" readonly="readonly"></textarea>
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
                    </div>
                </div>


                <?php if( count( $trp_settings['translation-languages'] ) == 1 ) { ?>
                <div class="trp-controls-section">
                    <div id="trp-translation-section" class="trp-controls-section-content">
                        <p><?php printf( __( 'You can add a new language from <a href="%s">Settings->TranslatePress</a>', TRP_PLUGIN_SLUG ), admin_url( 'options-general.php?page=translate-press' ) );?></p>
                        <p><?php _e( 'However, you can still use TranslatePress to <strong style="background: #f5fb9d;">modify gettext strings</strong> available in your page.', TRP_PLUGIN_SLUG );?></p>
                        <p><?php _e( 'Strings that are user created can\'t be modified, only those from themes and plugins.', TRP_PLUGIN_SLUG );?></p>
                    </div>
                </div>
                <?php } ?>
                <?php
                    // upsell to PRO from Translation Editor.
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    if ( !( is_plugin_active('tp-add-on-extra-languages/tp-extra-languages.php') || is_plugin_active('tp-add-on-seo-pack/tp-seo-pack.php') || is_plugin_active('tp-add-on-translator-accounts/index.php') ) ) :
                ?>
                <div class="trp-controls-section wp-core-ui">
                    <div id="trp-upsell-section" class="trp-controls-section-content">
                        <h3><?php _e('Your Website <br/> Multiple Languages', TRP_PLUGIN_SLUG ); ?></h3>
                        <ul>
                            <li><?php _e('Support for 221 Languages', TRP_PLUGIN_SLUG ); ?></li>
                            <li><?php _e('Translate SEO Title, Description, Slug', TRP_PLUGIN_SLUG ); ?></li>
                            <li><?php _e('Translate Facebook Tags', TRP_PLUGIN_SLUG ); ?></li>
                            <li><?php _e('Create Translator Accounts', TRP_PLUGIN_SLUG ); ?></li>
                            <li><?php _e('Publish when the translation is done', TRP_PLUGIN_SLUG ); ?></li>
                        </ul>
                        <p><span style="background: #f5fb9d;"><?php _e('Supported By Real People', TRP_PLUGIN_SLUG ); ?></span></p>
                        <p><a class="button-primary" target="_blank" href="https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=tpeditor&utm_campaign=tpfree"><?php _e('Learn More', TRP_PLUGIN_SLUG ); ?></a></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div id="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', $current_url );?>" width="100%" height="100%"></iframe>
        </div>
    </div>

</body>
</html>



<?php

