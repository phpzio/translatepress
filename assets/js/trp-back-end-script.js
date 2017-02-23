

function TRP_Settings() {

    var _this = this;
    /**
     * Function that initializes select2 on fields
     */
    this.initialize_select2 = function () {
        jQuery('.trp-select2').each(function () {
            var selectElement = jQuery(this);
            //var arguments = selectElement.attr('data-trp-select2-arguments');
            //arguments = JSON.parse(arguments);
            selectElement.select2(/*arguments*/);
        });
    };

    this.update_translation_language = function(){
        var selected_language = jQuery( '#trp-translation-language' ).val();
        var checkbox = jQuery( 'input.trp-translation-published' );
        checkbox.val( selected_language );
    };

    this.initialize = function(){
        _this.initialize_select2();
        jQuery( '#trp-translation-language' ).on( 'change', _this.update_translation_language );
    };

    this.initialize();

}

var trpSettings;

// Initialize the Translate Press Settings after jQuery is ready
jQuery( function() {
    trpSettings = new TRP_Settings();
});
