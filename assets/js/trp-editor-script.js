
function TRP_Editor(){
    var _this = this;
    var preview_iframe;
    var strings = [];
    var dictionaries = [];
    var default_language;


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
        })/*.*/;

        //all_strings.parent().attr('trp-translate', 'trp-translate');


        var strings_to_query = [];

        for ( var i = 0; i < all_strings.length; i++ ) {
            var string = new TRP_String();
            string.set_raw_string( all_strings[i] );
            strings.push( string );
            strings_to_query.push( string.get_details());
        }

        dictionaries[TRP_LANGUAGE] = new TRP_Dictionary( TRP_LANGUAGE );
        dictionaries[TRP_LANGUAGE].set_on_screen_strings( strings );

        _this.ajax_request( strings_to_query );

        add_event_handlers();
    };

    this.highlight = function (){
        jQuery( this ).css('border', '1px solid green');
        //jQuery( this ).
        //jQuery(this).css('box-shadow', 'rgb(119, 172, 255) 0px 0px 5px 0px inset');
    };

    this.unhighlight = function (){
        jQuery( this ).css('border', 'none');
    };

    this.select_string = function(){
        alert( jQuery(this).text() );
    };

    function add_event_handlers(){
        preview_iframe.on( 'mouseenter', '[data-trp-translate-id]', _this.highlight );
        preview_iframe.on( 'mouseleave', '[data-trp-translate-id]', _this.unhighlight );
        preview_iframe.on( 'click', '[data-trp-translate-id]', _this.select_string );
    }


    this.populate_strings = function( response ){
        for ( var key in response ) {
            if ( response.hasOwnProperty( key ) ) {
                if ( dictionaries[key] == undefined ){
                    dictionaries[key] = new TRP_Dictionary( key );
                }
                dictionaries[key].set_strings( response[key] );
            }
        }
        //dictionaries[TRP_LANGUAGE].prepare_edit_buttons();
        console.log(dictionaries);
    }
}


function TRP_Dictionary( language_code ){

    this.strings = []; // TRP_String
    this.language = language_code;

    this.set_strings = function( strings_object ){
        if ( TRP_LANGUAGE == this.language ){
            for ( var i in strings_object ){
                for ( var s in this.strings ){
                    if ( this.strings[s].id == strings_object[i].id  || this.strings[s].original == strings_object[i].original ){
                        this.strings[s].set_string( strings_object[i] )
                        break;
                    }
                }
            }
        }else{
            for ( var i in strings_object ) {
                var string = new TRP_String();
                string.set_string(strings_object[i]);
                this.strings.push(string);
            }
        }
    };

    this.set_on_screen_strings = function( new_strings ){
        this.strings = this.strings.concat( new_strings );
    };

 /*   this.prepare_edit_buttons = function(){
        for ( var i in this.strings ){
            strings[i].
        }
    };*/



}

function TRP_String(){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    this.id = null;
    this.original = null;
    this.translated = null;
    this.status = null;
    var jquery_object = null;

    this.get_details = function(){
        var details = {};
        details['id'] = this.id;
        details[ 'original' ] = this.original;
        return details;
    };

    this.set_string = function ( object ){
        this.id = ( object.id ) ? object.id : this.id;
        this.original = ( object.original ) ? object.original : this.original;
        this.translated = ( object.translated ) ? object.translated : this.translated;
        this.status = ( object.status ) ? object.status : this.status;
        if ( jquery_object ){
            if( object.translated ) {
                if (this.translated) {
                    jquery_object.text(this.translated);
                } else {
                    jquery_object.text(this.original);
                }
            }
            //if ( this.id ){
                jquery_object.attr( 'data-trp-translate-id', this.id );
            //}
        }
    };

    this.set_raw_string = function( raw_string ){
        jquery_object = jQuery( raw_string ).parent();
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            this.id = translation_id_attribute;
            this.translated = jquery_object.text();
        }else{
            this.original = jquery_object.text();
        }
    };

    //_this.initialize();
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


