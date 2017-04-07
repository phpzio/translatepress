
function TRP_Editor(){
    var _this = this;
    this.preview_iframe = null;
    var strings = [];
    var dictionaries = [];
    var default_language;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    this.original_textarea = jQuery( '#trp-original' );
    var translated_textareas = [];
    this.edit_translation_button = null;

    this.ajax_get_strings = function( strings_to_query ){

        jQuery.ajax({
            url: trp_ajax_url,
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

    this.ajax_save_strings = function ( strings_to_save ){
        jQuery.ajax({
            url: trp_ajax_url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_save_translations',
                strings: JSON.stringify( strings_to_save )
            },
            success: function (response) {
                _this.strings_saved( strings_to_save );
                _this.populate_strings( strings_to_save )
            },
            error: function(errorThrown){
                console.log( 'Translate Press AJAX Request Error' );
            }
        });
    };

    this.strings_saved = function ( strings_saved ){
        //TODO nice popup.
        console.log('Saved!');

    };

    this.save_string = function(){
        var strings_to_save = {};
        var modified = false;
        var original = _this.original_textarea.val();
        for( var key in translated_textareas ){
            var translated = translated_textareas[key].val();
            if ( dictionaries[key].get_string_by_original( original ).translated != translated ){
                modified = true;
                if ( strings_to_save[key] == undefined ){
                    strings_to_save[key] = [];
                }
                var id = translated_textareas[key].attr( TRP_TRANSLATION_ID );
                var status = 2;
                if ( translated.trim() == '' ){
                    status = 0;
                }
                strings_to_save[key].push( { id: id, original: original, translated: translated, status: status } );
            }
        }

        if ( modified ){
            _this.ajax_save_strings( strings_to_save );
        }
    };

    this.initialize = function(){

        this.preview_iframe = jQuery( '#trp-preview-iframe').contents();
/*
        var all_strings = this.preview_iframe.find( 'body *' ).contents().filter(function(){
            if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
                return this
            }
        }).wrap("<translate-press></translate-press>");*/

        //all_strings.parent().attr('trp-translate', 'trp-translate');

        var all_strings = this.preview_iframe.contents().find( 'translate-press' );
        //console.log(all_strings);

        var strings_to_query = [];

        for ( var i = 0; i < all_strings.length; i++ ) {
            var string = new TRP_String( TRP_LANGUAGE );
            string.set_raw_string( all_strings[i] );
            strings.push( string );
            strings_to_query.push( string.get_details());
        }

        dictionaries[TRP_LANGUAGE] = new TRP_Dictionary( TRP_LANGUAGE );
        dictionaries[TRP_LANGUAGE].set_on_screen_strings( strings );

        _this.ajax_get_strings( strings_to_query );

        add_event_handlers();
    };

    function add_event_handlers(){
        jQuery( '#trp-save' ).on( 'click', _this.save_string );
        jQuery( '.trp-toggle-languages' ).on( 'click', _this.toggle_languages );
        jQuery( '#trp-previous' ).on( 'click', _this.previous_string );
        jQuery( '#trp-next' ).on( 'click', _this.next_string );
    }

    this.toggle_languages = function (){
        jQuery( '.trp-other-language' ).toggle();
        jQuery( '.trp-toggle-languages' ).toggle();
    };

    this.previous_string = function(){

    };

    this.next_string = function(){

    };

    this.edit_strings = function ( trp_string ){
        _this.original_textarea.val( trp_string.original );
        for ( var key in translated_textareas ){
            var translated = '';
            var id = '';
            if ( key == TRP_LANGUAGE ) {
                translated = trp_string.translated;
                id = trp_string.id;
            }else {
                var string = dictionaries[key].get_string_by_original( trp_string.original );
                if ( string ) {
                    translated = string.translated;
                    id = string.id;
                }
            }
            translated_textareas[key].val( translated );
            translated_textareas[key].attr( TRP_TRANSLATION_ID, id );
        }
    };

    this.populate_strings = function( response ){
        for ( var key in response ) {
            if ( response.hasOwnProperty( key ) ) {
                if ( response[key]['default-language'] == true ){
                    default_language = key;
                    continue;
                }
                if ( dictionaries[key] == undefined ){
                    dictionaries[key] = new TRP_Dictionary( key );
                }

                dictionaries[key].set_strings( response[key] );
                if ( translated_textareas[key] == undefined ) {
                    translated_textareas[key] = jQuery( '#trp-translated-' + key );
                }

            }
        }
        console.log(dictionaries);
    };
}


function TRP_Dictionary( language_code ){

    var _this = this;
    this.strings = []; // TRP_String
    this.language = language_code;

    this.set_strings = function( strings_object ){
        for ( var s in _this.strings ){
            for ( var i in strings_object ){
                if ( _this.strings[s].id == strings_object[i].id  || ( ( _this.strings[s].original ) && _this.strings[s].original.trim() == strings_object[i].original.trim() ) ){
                    strings_object[i].set = true;
                    _this.strings[s].set_string( strings_object[i] );
                    break;
                }
            }
        }
        for ( var i in strings_object ) {
            if ( strings_object[i].hasOwnProperty( 'set' ) && strings_object[i] == true ){
                continue;
            }
            var string = new TRP_String( _this.language );
            string.set_string(strings_object[i]);
            this.strings.push(string);
        }

    };

    this.set_on_screen_strings = function( new_strings ){
        _this.strings = _this.strings.concat( new_strings );
    };

    this.get_string_by_original = function ( original ){
        for ( var i in _this.strings ){
            if ( _this.strings[i].original.trim() == original.trim() ){
                return _this.strings[i];
            }
        }
        return {};
    };

}

function TRP_String( language ){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    this.id = null;
    this.original = null;
    this.translated = null;
    this.status = null;
    var jquery_object = null;
    this.language = language;

    this.get_details = function(){
        var details = {};
        details['id'] = this.id;
        details['original'] = this.original;
        return details;
    };

    function decode_html( html ) {
        var txt = document.createElement( "textarea" );
        txt.innerHTML = html;
        return txt.value;
    }

    this.set_string = function ( new_settings ){
        _this.id = ( new_settings.hasOwnProperty ( 'id' ) ) ? new_settings.id : _this.id;
        _this.original = ( new_settings.hasOwnProperty ( 'original' ) ) ? new_settings.original : _this.original;
        _this.original = decode_html( _this.original );

        if ( jquery_object ){
            var text_to_set = null;
            if( new_settings.hasOwnProperty( 'translated' ) && new_settings.translated != _this.translated ) {
                text_to_set = new_settings.translated;
            }
            if( new_settings.hasOwnProperty( 'status' ) && new_settings.status == 0 ) {
                text_to_set = _this.original;
            }

            if ( text_to_set ) {
                var initial_value = jquery_object.text();
                text_to_set = initial_value.replace( initial_value.trim(), text_to_set );
                jquery_object.text( text_to_set );
            }

            jquery_object.on( 'mouseenter', '', _this.highlight );
            jquery_object.on( 'mouseleave', '', _this.unhighlight );
        }

        _this.status = ( new_settings.hasOwnProperty( 'status' ) ) ? new_settings.status : _this.status;
        _this.translated = ( new_settings.hasOwnProperty( 'translated' ) ) ? new_settings.translated : _this.translated;

    };

    this.highlight = function (){
        if ( ! trpEditor.edit_translation_button ){
            jquery_object.prepend( "<span class='trp-edit-translation'></span>" );
            trpEditor.edit_translation_button = jquery_object.children('.trp-edit-translation');
        }else{
            jquery_object.prepend( trpEditor.edit_translation_button );
        }
        trpEditor.edit_translation_button.off( 'click' );
        trpEditor.edit_translation_button.on( 'click', _this.edit_string );

        //todo maybe css not js
        jQuery(this).css('box-shadow', 'rgb(119, 172, 255) 0px 0px 5px 0px inset');
    };

    this.unhighlight = function (){
        //todo maybe css not js
        jquery_object.css('box-shadow', 'none');
    };

    this.edit_string = function(){
        trpEditor.edit_strings( _this );
        return false; // cancel navigating to another link
    };

    this.set_raw_string = function( raw_string ){
        //jquery_object = jQuery( raw_string ).parent();
        jquery_object = jQuery( raw_string );
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            this.id = translation_id_attribute;
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
        jQuery( '#trp-tabs li' ).on( 'click', _this.change_tab );
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


