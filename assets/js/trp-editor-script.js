
function TRP_Editor(){
    var _this = this;
    this.preview_iframe = null;
    var strings = [];
    var dictionaries = [];
    var default_language;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    this.original_textarea = jQuery( '#trp-original' );
    var translated_textareas = [];

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
                _this.strings_saved( response );
            },
            error: function(errorThrown){
                console.log( 'Translate Press AJAX Request Error' );
            }
        });
    };

    this.strings_saved = function ( response ){
        //TODO nice popup.
        console.log('Saved!');
    };

    this.save_string = function(){
        var strings_to_save = [];
        var original = _this.original_textarea.val();
        for( var key in translated_textareas ){
            var translated = translated_textareas[key].val();
            if ( dictionaries[key].get_string_by_original( original ).translated != translated ){
                var id = translated_textareas[key].attr( TRP_TRANSLATION_ID );
                strings_to_save.push( { language: key, id: id, translated: translated, status: 2 } );
            }
        }
        if ( strings_to_save.length > 0 ){
            _this.ajax_save_strings( strings_to_save );
        }
    };


    this.initialize = function(){

        this.preview_iframe = jQuery( '#trp-preview-iframe').contents();

        var all_strings = this.preview_iframe.find( 'body *' ).contents().filter(function(){
            if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
                return this
            }
        }).wrap("<translate-press></translate-press>")/*.*/;

        //all_strings.parent().attr('trp-translate', 'trp-translate');


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
/*

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
*/
    function add_event_handlers(){
        jQuery( '#trp-save' ).on( 'click', _this.save_string );
        /*preview_iframe.on( 'mouseenter', 'translate', _this.highlight );
        preview_iframe.on( 'mouseleave', 'translate', _this.unhighlight );
        preview_iframe.on( 'click', 'translate', _this.select_string );*/
    }


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
                translated_textareas[key] = jQuery( '#trp-translated-' + key );

                }
            }

        //dictionaries[TRP_LANGUAGE].prepare_edit_buttons();
    };
}


function TRP_Dictionary( language_code ){

    var _this = this;
    this.strings = []; // TRP_String
    this.language = language_code;

    this.set_strings = function( strings_object ){
        if ( TRP_LANGUAGE == _this.language ){
            for ( var s in this.strings ){
                for ( var i in strings_object ){
                    /*if ( )*/

                    if ( this.strings[s].id == strings_object[i].id  || ( ( this.strings[s].original ) && this.strings[s].original.trim() == strings_object[i].original.trim() ) ){
                        this.strings[s].set_string( strings_object[i] );
                        break;
                    }
                }
            }
        }else{
            for ( var i in strings_object ) {
                var string = new TRP_String( _this.language );
                string.set_string(strings_object[i]);
                this.strings.push(string);
            }
        }
    };

    this.set_on_screen_strings = function( new_strings ){
        this.strings = this.strings.concat( new_strings );
    };

    this.get_string_by_original = function ( original ){
        for ( var i in _this.strings ){
            if ( _this.strings[i].original.trim() == original.trim() ){
                return _this.strings[i];
            }
        }
        return {};
    };

 /*   this.prepare_edit_buttons = function(){
        for ( var i in this.strings ){
            strings[i].
        }
    };*/



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
        details[ 'original' ] = this.original;
        return details;
    };

    this.select_string = function(){
        jQuery( this ).css('border', '1px solid green');
    };

    this.set_string = function ( object ){
        _this.id = ( object.id ) ? object.id : _this.id;
        _this.original = ( object.original ) ? object.original : _this.original;
        _this.translated = ( object.translated ) ? object.translated : _this.translated;
        _this.status = ( object.status ) ? object.status : _this.status;

        if ( jquery_object ){
            if( object.translated ) {
                if (this.translated) {
                    jquery_object.text(this.translated);
                } else {
                    jquery_object.text(this.original);
                }
            }
            jquery_object.on( 'mouseenter', '', _this.highlight );
            jquery_object.on( 'mouseleave', '', _this.unhighlight );

            //if ( this.id ){
            /*if ( this.original == 'Dolly') */
                //jquery_object.attr( 'data-trp-translate-id', 'dol1ly' );
            //}
        }
    };

    this.highlight = function (){
        //jquery_object.css('border', '1px solid green');
        jQuery( '#trp-preview-iframe').contents().find( '.trp-edit-translation' ).remove();
        jquery_object.prepend( "<span class='trp-edit-translation'></span>" );
        trp_jquery_edit_translation = jquery_object.children('.trp-edit-translation');
        trp_jquery_edit_translation.on( 'click', _this.edit_string );


        //jquery_object.prepend( '<span class="trp-edit-translation"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z"/></svg></span>')
        //jQuery( this ).
        jQuery(this).css('box-shadow', 'rgb(119, 172, 255) 0px 0px 5px 0px inset');
    };

    this.unhighlight = function (){
        jquery_object.css('box-shadow', 'none');
    };

    this.edit_string = function(){
        //trpEditor.original_textarea.val( _this.original);//.trigger('change');
        trpEditor.edit_strings( _this );
        return false; // cancel navigating to another link
    };

    this.set_raw_string = function( raw_string ){
        jquery_object = jQuery( raw_string ).parent();
        var translation_id_attribute = jquery_object.parent().attr( TRP_TRANSLATION_ID );
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


