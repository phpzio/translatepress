<div id="trp-language-switcher">
    <form id="trp-language-switcher-form" action="" method="GET">
        <select id="trp-language-switcher-select" name="lang" onchange='this.form.submit()'>
            <?php foreach ( $published_languages as $code => $name ){ ?>
            <option value="<?php echo $code ?>" <?php echo ( $current_language == $code ) ? 'selected' : '' ?> >
                <?php echo $name ?>
            </option>
            <?php } ?>
        </select>
        <noscript><input type="submit" value="<?php _e( 'Change', TRP_PLUGIN_SLUG );?>"></noscript>
    </form>
</div>
