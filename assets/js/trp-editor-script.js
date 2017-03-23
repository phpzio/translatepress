
function TRP_Editor(){
    var _this = this;
    var preview_iframe;
    var strings = [];
    var dictionaries = [];


    this.ajax_request = function( strings_to_query ){

        jQuery.ajax({
            url: "http://local.profile-builder.dev/wp-content/plugins/translate-press/includes/trp-ajax1.php", //trp_ajax_url
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_get_translations',
                language: TRP_LANGUAGE,
                strings: JSON.stringify( strings_to_query )
            },
            success: function (response) {
                _this.populate_strings( response );
            },
            error: function(errorThrown){
                console.log( 'Translate Press AJAX Request Error' );
            }

        });
    };


    this.initialize = function(){

        preview_iframe = jQuery( '#trp-preview-iframe').contents();

        var all_strings = preview_iframe.find( 'body *' ).contents().filter(function(){
            if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
                return this
            }
        });

        var strings_to_query = [];

        for ( var i = 0; i < all_strings.length; i++) {
            var string = new TRP_String( all_strings[i] );
            strings.push( string );
            strings_to_query.push( string.get_details());
        }

        dictionaries[TRP_LANGUAGE] = new TRP_Dictionary( TRP_LANGUAGE );
        dictionaries[TRP_LANGUAGE].set_on_screen_strings( strings );

        _this.ajax_request( strings_to_query );
    };

    this.populate_strings = function( response ){
        for ( var key in response ) {
            if ( response.hasOwnProperty( key ) ) {
                if ( dictionaries[key] == undefined ){
                    dictionaries[key] = new TRP_Dictionary( key );
                }

                dictionaries[key].set_strings( response[key] );
                //TRP_LANGUAGE

                //console.log(key + " -> " + dictionaries[key]);
            }
        }



        //for ( var i = 0; i < strings.length; i++) {
            //strings_to_query.push( string.get_details());
        //}
    }
}


function TRP_Dictionary( language_code, strings ){

    var strings = []; // TRP_String
    this.language = language_code;
    var default_language = false;

    this.set_strings = function( strings_object ){
        if ( TRP_LANGUAGE == this.language ){
            // make sure to map existing
        }else{
            // store them properly without jquery object to point at.
        }
    };

    this.set_on_screen_strings = function( new_strings ){
        strings = strings.concat( new_strings );
    };


}

function TRP_String( raw_string ){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    var id = null;
    var original = null;
    var translated = [];
    var jquery_object = jQuery( raw_string ).parent();

    this.get_details = function(){
        var details = {};
        details['id'] = id;
        details[ 'original' ] = original;
        return details;
    };

    this.initialize = function(){
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            id = translation_id_attribute;
            translated[TRP_LANGUAGE] = jquery_object.text();
        }else{
            original = jquery_object.text();
        }
    };

    _this.initialize();
}


function TRP_Tabs(){
    var _this = this;

    this.change_tab = function(){
        var tab_id = jQuery(this).attr('data-tab');

        jQuery( '.trp-section' ).removeClass( 'trp-current' );

        jQuery("#"+tab_id).addClass('trp-current');
    };


    this.add_event_handlers = function(){
        jQuery( '#trp-tabs li' ).click( _this.change_tab );
    };

    _this.add_event_handlers();
}



var trpEditor;
// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {

    trpEditor = new TRP_Editor();
    //todo move this in trp_editor constructor
    var trpTabs = new TRP_Tabs();

});


