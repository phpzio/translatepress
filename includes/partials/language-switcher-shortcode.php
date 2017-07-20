<div class="trp-language-switcher " data-no-translation>
    <form class="trp-language-switcher-form" action="" method="POST">
        <select data-no-translation class="trp-language-switcher-select" <?php echo ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) ? 'data-trp-unpreviewable="trp-unpreviewable"' : '' ?> name="lang" onchange='trp_change_language( this )'>
            <?php foreach ( $published_languages as $code => $name ){ ?>
            <option data-class="trp-flag-icon" data-style="background-image: url( <?php echo $this->add_flag( $code, $name, 'ls_shortcode' ) ?> );" data-flag-url="<?php echo $this->add_flag( $code, $name, 'ls_shortcode' ) ?>" value="<?php echo $code ?>" <?php echo ( $current_language == $code ) ? 'selected' : '' ?> >
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
        <noscript><input type="submit" value="<?php _e( 'Change', TRP_PLUGIN_SLUG );?>"></noscript>
    </form>
</div>
