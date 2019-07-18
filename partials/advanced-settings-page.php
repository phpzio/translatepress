
<div id="trp-advanced-settings" class="wrap">
    <form method="post" action="admin.php">
        <?php settings_fields( 'trp_advanced_settings' ); ?>
        <h1> <?php esc_html_e( 'TranslatePress Advanced Settings', 'translatepress-multilingual' );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

        <table id="trp-options" class="form-table">
            <?php do_action('trp_output_advanced_settings_options' ); ?>
        </table>
        <input type="hidden" name="page" value="trp_advanced_page">
        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
