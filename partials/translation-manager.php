
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
        <?php do_action( 'trp_translation_manager_before_controls' ); ?>
        <div id="trp-controls">
            <div class="trp-controls-container">
                <div id="trp-close-save">
                    <a id="trp-controls-close" href="#"></a>
                    <div id="trp-save-container">
                        <span id="trp-translation-saved" style="display: none"><?php _e( 'Saved!', 'translatepress-multilingual' ); ?></span>
                        <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <button id="trp-save" type="submit" class="button-primary trp-save-string"><?php _e( 'Save translation', 'translatepress-multilingual' ); ?></button>
                    </div>
                </div>
                <?php do_action( 'trp_translation_manager_before_strings' ); ?>
                <div class="trp-controls-section">
                    <div class="trp-controls-section-content">
                        <form id="trp-language-switch-form" action="" method="GET">
                            <select id="trp-language-select" name='lang' onchange='trpEditor.change_language( this )'>
                                <?php foreach ( $available_languages as $code => $language ) { ?>
                                    <option value="<?php echo $code; ?>" <?php echo ( $TRP_LANGUAGE == $code ) ? 'selected' : ''; ?>> <?php echo $language; ?> </option>
                                <?php } ?>
                            </select>
                            <input type="hidden" name="trp-edit-translation" value="true">
                        </form>
                        <div id="trp-string-list">
                            <select id="trp-string-categories" data-trp-placeholder="<?php _e( 'Select string to translate...', 'translatepress-multilingual' ); ?>">
                                <option value="" class="default-option"></option>
                                <?php //add here an optiongroup so we know to add all the gettext strings below this and all the other strings above this ?>
                                <optgroup id="trp-gettext-strings-optgroup" label="<?php _e( 'Gettext strings', 'translatepress-multilingual' ); ?>"></optgroup>
                            </select>
                        </div>

                        <div id="trp-next-previous">
                            <button type="button" id="trp-previous" class="trp-next-previous-buttons"><span>&laquo;</span> <?php _e( 'Previous', 'translatepress-multilingual' ); ?></button>
                            <button type="button" id="trp-next" class="trp-next-previous-buttons"><?php _e( 'Next', 'translatepress-multilingual' ); ?> <span>&raquo;</span></button>
                        </div>

                        <div id="trp-view-as">
                            <div id="trp-view-as-description"><?php _e( 'View as', 'translatepress-multilingual' ); ?></div>
                            <select id="trp-view-as-select" onchange='trpEditor.change_view_as( this )'>
                                <?php 
                                $trp_view_as_values = array( __('Current User', 'translatepress-multilingual') => 'current_user', __('Logged Out', 'translatepress-multilingual') => 'logged_out' );
                                $trp_all_roles = wp_roles()->roles;
                                if( !empty( $trp_all_roles ) ){
                                    foreach( $trp_all_roles as $trp_all_role ){
                                        $trp_view_as_values[$trp_all_role['name']] = '';
                                    }
                                }
                                $trp_view_as_values = apply_filters( 'trp_view_as_values', $trp_view_as_values );
                                
                                ?>
                                <?php foreach ( $trp_view_as_values as $trp_view_as_label => $trp_view_as_value ) { ?>
                                    <option value="<?php echo $trp_view_as_value; ?>" <?php if( empty( $trp_view_as_value ) ){ echo 'disabled="disabled" title="'. __( 'Available in our Pro Versions', 'translatepress-multilingual' ) .'"'; }?> data-view-as-nonce="<?php echo wp_create_nonce( 'trp_view_as'.$trp_view_as_value.get_current_user_id() ); ?>" <?php if( isset($_REQUEST['trp-view-as']) ){ echo ( sanitize_text_field( $_REQUEST['trp-view-as'] ) == $trp_view_as_value ) ? 'selected' : ''; } ?>> <?php echo $trp_view_as_label; ?> </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <br class="clear">

                </div>

                <?php do_action( 'trp_translation_manager_before_translations' ); ?>

                <div class="trp-controls-section">
                    <div id="trp-translation-section" class="trp-controls-section-content">
                        <div id="trp-unsaved-changes-warning-message" style="display:none"><?php _e( 'You have unsaved changes!', 'translatepress-multilingual' );?></div>


                            <?php //original strings for gettext textarea ?>
                            <div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">
                                <div class="trp-language-name"><?php _e( 'Original String', 'translatepress-multilingual' );?></div>
                                <textarea id="trp-gettext-original-textarea" readonly="readonly"></textarea>
                            </div>

                            <div id="trp-language-<?php echo $trp_settings['default-language'];?>" class="trp-language-text trp-default-language">
                                <?php $default_language_name =  $available_languages[ $trp_settings['default-language'] ];?>
                                <div class="trp-language-name" data-trp-gettext-language-name="<?php echo sprintf( __( 'To %s', 'translatepress-multilingual' ), $default_language_name ); ?>" data-trp-default-language-name="<?php echo sprintf( __( 'From %s', 'translatepress-multilingual' ), $default_language_name ); ?>">
                                    <?php echo sprintf( __( 'From %s', 'translatepress-multilingual' ), $default_language_name ); ?>
                                </div>
                                <textarea id="trp-original" data-trp-language-code="<?php echo esc_attr( $trp_settings['default-language'] ); ?>" readonly="readonly"></textarea>
                                <div class="trp-discard-changes trp-discard-on-default-language" style="display:none;"><?php _e( 'Discard changes', 'translatepress-multilingual' );?></div>
                            </div>
                            <?php
                            foreach( $translation_languages as $language ){?>
                                <div id="trp-language-<?php echo $language;?>" class="trp-language-text <?php echo ( $TRP_LANGUAGE == $trp_settings['default-language'] || $language == $TRP_LANGUAGE ) ? 'trp-current-language' : 'trp-other-language' ?>">
                                    <div class="trp-language-name"><?php echo sprintf( __( 'To %s', 'translatepress-multilingual' ), $available_languages[ $language ] ); ?></div>
                                    <textarea id="trp-translated-<?php echo $language; ?>" data-trp-translate-id="" data-trp-language-code="<?php echo esc_attr( $language ); ?>"></textarea>
                                    <div class="trp-discard-changes"><?php _e( 'Discard changes', 'translatepress-multilingual' );?></div>
                                </div>
                                <?php if ( $language == $TRP_LANGUAGE && count( $translation_languages ) > 1 ){
                                    $other_languages = __( 'Other languages', 'translatepress-multilingual' );
                                    ?>
                                    <div id="trp-show-all-languages" class="trp-toggle-languages"><span>&#11208; <?php echo $other_languages ?></span></div>
                                    <div id="trp-hide-all-languages" class="trp-toggle-languages trp-toggle-languages-active"><span>&#11206; <?php echo $other_languages ?></span></div>
                                <?php } ?>
                            <?php }?>
                    </div>
                </div>

                <?php do_action( 'trp_translation_manager_after_translations' ); ?>

                <?php if( count( $trp_settings['translation-languages'] ) == 1 ) { ?>
                <div class="trp-controls-section">
                    <div id="trp-translation-section" class="trp-controls-section-content">
                        <p><?php printf( __( 'You can add a new language from <a href="%s">Settings->TranslatePress</a>', 'translatepress-multilingual' ), admin_url( 'options-general.php?page=translate-press' ) );?></p>
                        <p><?php _e( 'However, you can still use TranslatePress to <strong style="background: #f5fb9d;">modify gettext strings</strong> available in your page.', 'translatepress-multilingual' );?></p>
                        <p><?php _e( 'Strings that are user created can\'t be modified, only those from themes and plugins.', 'translatepress-multilingual' );?></p>
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
                        <h3><?php _e('Extra Translation Features', 'translatepress-multilingual' ); ?></h3>
                        <ul>
                            <li><?php _e('Support for 221 Extra Languages', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Yoast SEO support', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Translate SEO Title, Description, Slug', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Create Translator Accounts', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Publish only when translation is done', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Translate by Browsing as User Role', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Different Menus Items per Language', 'translatepress-multilingual' ); ?></li>
                            <li><?php _e('Automatic User Language Detection', 'translatepress-multilingual' ); ?></li>
                        </ul>
                        <p><span style="background: #f5fb9d;"><?php _e('Supported By Real People', 'translatepress-multilingual' ); ?></span></p>
                        <p><a class="button-primary" target="_blank" href=" <?php echo trp_add_affiliate_id_to_link('https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=tpeditor&utm_campaign=tpfree')?> "><?php _e('Find Out More', 'translatepress-multilingual' ); ?></a></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div id="trp-preview">
                <iframe id="trp-preview-iframe" onload="trpEditor.initialize();" src="<?php echo add_query_arg( 'trp-edit-translation', 'preview', $current_url );?>" width="100%" height="100%"></iframe>
        </div>
    </div>
    <?php do_action( 'trp_translation_manager_footer' ); ?>
</body>
</html>



<?php

