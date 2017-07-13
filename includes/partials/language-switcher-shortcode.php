<div class="trp-language-switcher ">
    <form class="trp-language-switcher-form" action="" method="POST">
        <select data-no-translation class="trp-language-switcher-select <?php echo ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) ? 'trp-unpreviewable' : '' ?>" name="lang" onchange='trp_change_language( this )'>
            <?php foreach ( $published_languages as $code => $name ){ ?>
            <option data-class="trp-flag-icon" data-style="background-image: url(&apos;http://www.gravatar.com/avatar/b3e04a46e85ad3e165d66f5d927eb609?d=monsterid&amp;r=g&amp;s=16&apos;);" value="<?php echo $code ?>" <?php echo ( $current_language == $code ) ? 'selected' : '' ?> >
                <?php

                /*if( $shortcode_settings['flags'] ) {
                    echo $this->add_flag( $code, $name );
                }*/

                if( $shortcode_settings['full_names'] ) {
                    echo ucfirst( $name );
                }

                if( $shortcode_settings['short_names'] ) {
                    echo strtoupper( $this->url_converter->get_url_slug( $code ) );
                }

                ?>
            </option>
            <?php } ?>
        </select>
        <noscript><input type="submit" value="<?php _e( 'Change', TRP_PLUGIN_SLUG );?>"></noscript>
    </form>
</div>
