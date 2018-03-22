<?php
$only_flags_class = '';
if( $shortcode_settings['flags'] && ! $shortcode_settings['full_names'] && ! $shortcode_settings['short_names'] ) {
    $only_flags_class = 'trp-ls-shortcode-only-flags';
}

?>
<div class="trp-language-switcher " data-no-translation>
    <form class="trp-language-switcher-form <?php echo $only_flags_class ?>" action="" method="POST">
        <select data-no-translation class="trp-language-switcher-select" <?php echo ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) ? 'data-trp-unpreviewable="trp-unpreviewable"' : '' ?> name="lang" onchange='trp_change_language( this )'>
            <?php foreach ( $published_languages as $code => $name ){ ?>
            <option title="<?php echo ucfirst( $name ) ?>" data-class="trp-flag-icon" data-style="background-image: url( <?php echo $this->add_flag( $code, $name, 'ls_shortcode' ) ?> );" data-flag-url="<?php echo $this->add_flag( $code, $name, 'ls_shortcode' ) ?>" value="<?php echo str_replace("_", "-", $code );?>" <?php echo ( $current_language == $code ) ? 'selected' : '' ?> >
                <?php

                if( $shortcode_settings['full_names'] ) {
                    echo ucfirst( $name );
                }

                if( $shortcode_settings['short_names'] ) {
                    echo strtoupper( $this->url_converter->get_url_slug( $code, false ) );
                }

                ?>
            </option>
            <?php } ?>
        </select>
        <noscript><input type="submit" value="<?php _e( 'Change', 'translatepress-multilingual' );?>"></noscript>
    </form>
</div>
