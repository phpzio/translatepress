<li class="menu-item menu-item-language menu-item-language-current trp-language-switcher">
    <form class="trp-language-switcher-form" action="" method="POST">
        <select class="trp-language-switcher-select" name="lang" onchange='trp_change_language( this )'>
            <?php foreach ( $published_languages as $code => $name ){ ?>
            <option value="<?php echo $code ?>" <?php echo ( $current_language == $code ) ? 'selected' : '' ?> >
                <?php echo $name ?>
            </option>
            <?php } ?>
        </select>
        <noscript><input type="submit" value="<?php _e( 'Change', TRP_PLUGIN_SLUG );?>"></noscript>
    </form>
</li>
