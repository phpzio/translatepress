/**
 * Script used in Settings Page.
 */
function TRP_Settings() {

    var _this = this;

    /**
     * Initialize select to become select2
     */
    this.initialize_select2 = function () {
        jQuery('.trp-select2').each(function () {
            var select_element = jQuery(this);
            select_element.select2(/*arguments*/);
        });
    };

    /**
     * Update translation language checkbox to active.
     */
    this.update_translation_language = function(){
        var selected_language = jQuery( '#trp-translation-language' ).val();
        var checkbox = jQuery( 'input.trp-translation-published' );
        checkbox.val( selected_language );
        var url_slug = jQuery ( '#trp-url-slug' );
        url_slug.attr( 'name', 'trp_settings[url-slugs][' + selected_language + ']').val( '' ).val( trp_iso_codes[selected_language] );
    };

    /**
     * Add event handler for translation language.
     */
    this.initialize = function(){
        _this.initialize_select2();
        jQuery( '#trp-translation-language' ).on( 'change', _this.update_translation_language );
    };

    this.initialize();

}

var trpSettings;

// Initialize the TranslatePress Settings after jQuery is ready
jQuery( function() {
    trpSettings = new TRP_Settings();
});
