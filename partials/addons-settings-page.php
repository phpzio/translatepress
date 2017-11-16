<div id="trp-addons-page" class="wrap">

    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>
    <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2>Advanced Addons</h2>
            <p>These addons extend your translation plugin and are available in the Developer, Business and Personal plans.</p>
        </div>
    </div>

    <div class="grid">
        <div class="grid-cell" style="overflow:hidden;">
            <a href="https://translatepress.com/pricing/" target="_blank"><img src="<?php echo plugins_url('../assets/images/seo_icon_translatepress.png', __FILE__) ?>" alt="SEO" style="float: left; margin: 0 1.5rem 1.5rem 0;"></a>
            <h3><a href=" <?php echo trp_add_affiliate_id_to_link('https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=addons_tab&utm_campaign=tpfree') ?> " target="_blank"> SEO Pack</a></h3>
            <p>SEO support for page slug, page title, description and facebook and twitter social graph information. The HTML lang attribute is properly set.</p>
        </div>
    </div>

    <div class="grid">
        <div class="grid-cell" style="overflow:hidden;">
            <a href="https://translatepress.com/pricing/" target="_blank"><img src="<?php echo plugins_url('../assets/images/multiple_lang_icon.png', __FILE__) ?>" alt="Multiple Languages" style="float: left; margin: 0 1.5rem 1.5rem 0;"></a>
            <h3><a href=" <?php echo trp_add_affiliate_id_to_link('https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=addons_tab&utm_campaign=tpfree') ?> " target="_blank"> Multiple Languages</a></h3>
            <p>Add as many languages as you need for your project to go global.<br>
                Publish your language only when all your translations are done. </p>
        </div>
    </div>

    <div class="grid">
        <div class="grid-cell" style="overflow:hidden;">
            <a href="https://translatepress.com/pricing/" target="_blank"><img src="<?php echo plugins_url('../assets/images/translator-accounts-addon.png', __FILE__) ?>" alt="Translator Account" style="float: left; margin: 0 1.5rem 1.5rem 0;"></a>
            <h3><a href=" <?php echo trp_add_affiliate_id_to_link('https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=addons_tab&utm_campaign=tpfree') ?> " target="_blank"> Translator Accounts</a></h3>
            <p>Create translator accounts for new users or allow existing users <br/>that are not administrators to translate your website.</p>
        </div>
    </div>
</div>