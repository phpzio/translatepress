/**
 * Script used in Settings Page to change the language selector and slugs.
 */

function TRP_Settings_Language_Selector() {
    var _this = this;
    var duplicate_url_error_message;
    var iso_codes;

    /**
     * Initialize select to become select2
     */
    this.initialize_select2 = function () {
        jQuery('.trp-select2').each(function () {
            var select_element = jQuery(this);
            select_element.select2(/*arguments*/);
        });
    };

    this.get_default_url_slug = function( new_language ){
        var return_slug = iso_codes[new_language];
        var url_slugs = _this.get_existing_url_slugs();
        url_slugs.push( return_slug );
        if ( has_duplicates ( url_slugs ) ){
            return_slug = new_language;
        }
        return return_slug.toLowerCase();
    };

    this.add_language = function(){
        jQuery(".trp-upsell-multiple-languages").show('fast');
    };

    this.update_default_language = function(){
        var selected_language = jQuery( '#trp-default-language').val();
        jQuery( '.trp-hidden-default-language' ).val( selected_language );
        jQuery( '.trp-translation-published[disabled]' ).val( selected_language );
        jQuery( '.trp-translation-language[disabled]').val( selected_language ).trigger( 'change' );
    };

    function has_duplicates(array) {
        var valuesSoFar = Object.create(null);
        for (var i = 0; i < array.length; ++i) {
            var value = array[i];
            if (value in valuesSoFar) {
                return true;
            }
            valuesSoFar[value] = true;
        }
        return false;
    }

    this.get_existing_url_slugs = function(){
        var url_slugs = [];
        jQuery( '.trp-language-slug' ).each( function (){
            url_slugs.push( jQuery( this ).val().toLowerCase() );
        } );
        return url_slugs;
    };

    this.check_unique_url_slugs = function (event){
        var url_slugs = _this.get_existing_url_slugs();
        if ( has_duplicates(url_slugs)){
            alert( duplicate_url_error_message );
            event.preventDefault();
        }
    };

    this.update_url_slug_and_status = function ( event ) {
        var select = jQuery( event.target );
        var new_language = select.val();
        var row = jQuery( select ).parents( '.trp-language' ) ;
        row.find( '.trp-language-slug' ).attr( 'name', 'trp_settings[url-slugs][' + new_language + ']').val( '' ).val( _this.get_default_url_slug( new_language ) );
        row.find( '.trp-translation-published' ).val( new_language );
    };

    this.initialize = function () {
        this.initialize_select2();
        duplicate_url_error_message = trp_url_slugs_info['error_message_duplicate_slugs'];
        iso_codes = trp_url_slugs_info['iso_codes'];

        jQuery( '#trp-sortable-languages' ).sortable({ handle: '.trp-sortable-handle' });
        jQuery( '#trp-add-language' ).click( _this.add_language );
        jQuery( '#trp-default-language' ).on( 'change', _this.update_default_language );
        jQuery( "form[action='options.php']").on ( 'submit', _this.check_unique_url_slugs );
        jQuery( '#trp-languages-table' ).on( 'change', '.trp-translation-language', _this.update_url_slug_and_status );
    };

    this.initialize();
}

var trpSettingsLanguages;

// Initialize the Translate Press Settings after jQuery is ready
jQuery( function() {
    trpSettingsLanguages = new TRP_Settings_Language_Selector();
});

