/*
 * Script used in Settings Page
 */

jQuery( function() {

    /**
     * Change the language selector and slugs
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
            var selected_language = jQuery( '#trp-select-language' );
            var new_language = selected_language.val();
            if ( new_language == "" ){
                return;
            }

            if (jQuery( "#trp-languages-table .trp-language" ).length >= 2 ){
                jQuery(".trp-upsell-multiple-languages").show('fast');
                return;
            }

            selected_language.val( '' ).trigger( 'change' );

            var new_option = jQuery( '.trp-language' ).first().clone();
            new_option = jQuery( new_option );

            new_option.find( '.trp-hidden-default-language' ).remove();
            new_option.find( '.select2-container' ).remove();
            var select = new_option.find( 'select.trp-translation-language' );
            select.removeAttr( 'disabled' );
            select.find( 'option' ).each(function(index, el){
                el.text = el.text.replace('Default: ', '');
            })

            select.val( new_language );
            select.select2();

            var checkbox = new_option.find( 'input.trp-translation-published' );
            checkbox.removeAttr( 'disabled' );
            checkbox.val( new_language );

            var url_slug = new_option.find( 'input.trp-language-slug' );
            url_slug.val( _this.get_default_url_slug( new_language ) );
            url_slug.attr('name', 'trp_settings[url-slugs][' + new_language + ']' );

            var language_code = new_option.find( 'input.trp-language-code' );
            language_code.val( new_language);

            var remove = new_option.find( '.trp-remove-language' ).toggle();

            new_option = jQuery( '#trp-sortable-languages' ).append( new_option );
            new_option.find( '.trp-remove-language' ).last().click( _this.remove_language );
        };

        this.remove_language = function( element ){
            var message = jQuery( element.target ).attr( 'data-confirm-message' );
            var confirmed = confirm( message );
            if ( confirmed ) {
                jQuery ( element.target ).parent().parent().remove();
            }
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
            row.find( '.trp-language-code' ).val( '' ).val( new_language );
            row.find( '.trp-translation-published' ).val( new_language );
        };

        this.initialize = function () {
            this.initialize_select2();

            if ( !jQuery( '.trp-language-selector-limited' ).length ){
                return;
            }

            duplicate_url_error_message = trp_url_slugs_info['error_message_duplicate_slugs'];
            iso_codes = trp_url_slugs_info['iso_codes'];

            jQuery( '#trp-sortable-languages' ).sortable({ handle: '.trp-sortable-handle' });
            jQuery( '#trp-add-language' ).click( _this.add_language );
            jQuery( '.trp-remove-language' ).click( _this.remove_language );
            jQuery( '#trp-default-language' ).on( 'change', _this.update_default_language );
            jQuery( "form[action='options.php']").on ( 'submit', _this.check_unique_url_slugs );
            jQuery( '#trp-languages-table' ).on( 'change', '.trp-translation-language', _this.update_url_slug_and_status );
        };

        this.initialize();
    }

    /*
     * Show Google Translate API Key only when Google Translate is active
     */
    function TRP_Field_Toggler (){
        var _$setting_toggled;
        var _$trigger_field;
        var _trigger_field_value_for_show;

        function show_hide_based_on_value( value ) {
            if ( value === _trigger_field_value_for_show ) {
                _$setting_toggled.show();
            } else {
                _$setting_toggled.hide();
            }
        }

        function add_event_on_change() {
            _$trigger_field.on('change', function () {
                show_hide_based_on_value( this.value );
            });
        }

        function init( trigger_select_id, setting_id, value_for_show ){
            _trigger_field_value_for_show = value_for_show;
            _$trigger_field = jQuery( trigger_select_id );
            _$setting_toggled = jQuery( setting_id ).parents('tr');
            show_hide_based_on_value( _$trigger_field.val() );
            add_event_on_change();
        }

        return {
            init: init
        };
    };

    var trpSettingsLanguages = new TRP_Settings_Language_Selector();

    jQuery('#trp-default-language').on("select2:selecting", function(e) {
        jQuery("#trp-options .warning").show('fast');
    });

    var trpGoogleTranslate = TRP_Field_Toggler();
    trpGoogleTranslate.init('#trp-g-translate', '#trp-g-translate-key', 'yes' );

});

