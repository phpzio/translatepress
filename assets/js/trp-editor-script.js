
function TRP_Editor(){
    var _this = this;
    this.preview_iframe = null;
    var strings;
    var dictionaries;
    var default_language;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    this.original_textarea = jQuery( '#trp-original' );
    var loading_animation = jQuery( '#trp-string-saved-ajax-loader' );
    var translation_saved = jQuery( '#trp-translation-saved' );
    var preview_container = jQuery( '#trp-preview' );
    var controls = jQuery( '#trp-controls' );
    var save_button = jQuery( '#trp-save' );
    var translated_textareas = [];
    this.edit_translation_button = null;
    var categories;
    var trp_lister = null;
    var jquery_string_selector = jQuery( '#trp-string-categories' );

    this.initialize = function(){
        _this.edit_translation_button = null;

        _this.update_parent_url();

        _this.iframe_strings_lookup();

        if ( trp_lister != null ) {
            jquery_string_selector.off( 'change', trp_lister.select_string );
        }
        trp_lister = new TRP_Lister( dictionaries[trp_on_screen_language] );

    };

    this.update_parent_url = function(){
        var location = document.getElementById("trp-preview-iframe").contentWindow.location.href;
        location = location.replace( 'trp-edit-translation=preview', 'trp-edit-translation=true' );
        window.history.pushState( null, null, location );
    };

    this.iframe_strings_lookup = function(){
        _this.preview_iframe = jQuery( '#trp-preview-iframe').contents();
        strings = [];
        dictionaries = [];

        /*
         var all_strings = this.preview_iframe.find( 'body *' ).contents().filter(function(){
         if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
         return this
         }
         }).wrap("<translate-press></translate-press>");*/

        var all_strings = _this.preview_iframe.contents().find( '[data-trp-translate-id]' );
        // add iframe title
        all_strings.push ( jQuery( document.getElementById("trp-preview-iframe").contentDocument.title )[0] );
        var strings_to_query = [];
        for ( var i = 0; i < all_strings.length; i++ ) {
            var string = new TRP_String( trp_on_screen_language, i );
            string.set_raw_string( all_strings[i] );
            strings.push( string );
            strings_to_query.push( string.get_details());
        }

        dictionaries[trp_on_screen_language] = new TRP_Dictionary( trp_on_screen_language );
        dictionaries[trp_on_screen_language].set_on_screen_strings( strings );

        _this.ajax_get_strings( strings_to_query );
    };

    this.ajax_get_strings = function( strings_to_query ){

        jQuery.ajax({
            url: trp_ajax_url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_get_translations',
                language: trp_on_screen_language,
                strings: JSON.stringify( strings_to_query ),
                all_languages: 'true'
            },
            success: function (response) {
                _this.populate_strings( response );
                trp_lister.reload_list();
            },
            error: function(errorThrown){
                console.log( 'Translate Press AJAX Request Error' );
            }

        });
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
    };

    this.edit_strings = function ( trp_string, index ){
        _this.original_textarea.val( trp_string.original );
        for ( var key in translated_textareas ){
            var translated = '';
            var id = '';
            if ( key == trp_on_screen_language ) {
                translated = trp_string.translated;
                id = trp_string.id;
                dictionaries[key].set_current_index( index );
            }else {
                var string = dictionaries[key].get_string_by_original( trp_string.original );
                if ( string ) {
                    translated = string.translated;
                    id = string.id;
                }
            }
            translated_textareas[key].val( translated );
            translated_textareas[key].attr( TRP_TRANSLATION_ID, id );
            trp_lister.update_selected_string( index );
        }
    };

    this.save_string = function(){
        var strings_to_save = {};
        var modified = false;
        var original = _this.original_textarea.val();
        var action = 'trp_save_translations';
        if ( original != "" ) {
            for (var key in translated_textareas) {
                var translated = translated_textareas[key].val();
                var string = dictionaries[key].get_string_by_original(original);
                if ( string.translated != translated ) {
                    modified = true;
                    if (strings_to_save[key] == undefined) {
                        strings_to_save[key] = [];
                    }
                    var id = translated_textareas[key].attr( TRP_TRANSLATION_ID );
                    var status = 2;
                    if (translated.trim() == '') {
                        status = 0;
                    }
                    strings_to_save[key].push({id: id, original: original, translated: translated, status: status});
                    if ( string.slug == true ){
                        action = 'trp_save_slug_translations';
                    }
                }
            }
        }

        if ( modified ){
            _this.saving_translation_ui();
            _this.ajax_save_strings( strings_to_save, action );
        }
    };

    this.ajax_save_strings = function ( strings_to_save, action ){
        console.log(action ) ;
        jQuery.ajax({
            url: trp_ajax_url,
            type: 'post',
            dataType: 'json',
            data: {
                action: action,
                strings: JSON.stringify( strings_to_save )
            },
            success: function (response) {
                _this.populate_strings( strings_to_save );
                _this.saved_translation_ui();

            },
            error: function(errorThrown){
                loading_animation.toggle();
                console.log( 'Translate Press AJAX Request Error' );
            }
            //returns 0 ajax-ul daca e eroare
        });
    };

    this.saving_translation_ui = function(){
        loading_animation.toggle();
        save_button.attr( 'disabled', 'disabled' );
    };

    this.saved_translation_ui = function(){
        save_button.removeAttr( 'disabled' );
        loading_animation.toggle();
        translation_saved.css("display","inline");
        translation_saved.delay(3000).fadeOut(400);
    };

    this.toggle_languages = function (){
        jQuery( '.trp-other-language' ).toggle();
        jQuery( '.trp-toggle-languages' ).toggle();
    };

    this.previous_string = function(){
        dictionaries[trp_on_screen_language].set_previous_string();
    };

    this.next_string = function(){
        dictionaries[trp_on_screen_language].set_next_string();
    };

    function resize_iframe (event, ui) {
        var total_width = jQuery(window).width();
        var width = controls.width();

        if(width > total_width) {
            width = total_width;
            controls.css('width', width);
        }

        preview_container.css('right', width );
        preview_container.css('left', ( width - 298 ) );
        preview_container.css('width', (total_width - width));
    }

    function add_event_handlers(){
        save_button.on( 'click', _this.save_string );
        jQuery( '.trp-toggle-languages' ).on( 'click', _this.toggle_languages );
        jQuery( '#trp-previous' ).on( 'click', _this.previous_string );
        jQuery( '#trp-next' ).on( 'click', _this.next_string );

        controls.resizable({
            start: function( ) { preview_container.toggle(); },
            stop: function( ) { preview_container.toggle(); },
            handles: 'e',
            minWidth: 190,
            alsoResize: '.trp-language-text textarea, span.select2-container, #trp-string-categories',

        }).bind( "resize", resize_iframe );

        jQuery( window ).resize(function () {
            resize_iframe();
        });

        var placeholder_text = jquery_string_selector.attr('data-trp-placeholder');
        if (placeholder_text != '') {
            placeholder_text = 'Select string to translate...';
        }
        jquery_string_selector.select2({ placeholder: placeholder_text, templateResult: format_option });
        jQuery( '#trp-language-select' ).select2();
    }

    function format_option(option){
        //todo options that don't have title get (undefined). fix this.
      //  if ( option.title ) {
            option = jQuery(
                '<div>' + option.text + '</div><div class="string-selector-description">' + option.title + '</div>'
            );
       // }
        return option;
    }

    add_event_handlers();
}


function TRP_Dictionary( language_code ){

    var _this = this;
    this.strings = []; // TRP_String
    this.language = language_code;
    var current_index = 0;

    this.set_current_index = function ( index ){
        current_index = index;
    };

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
            if ( strings_object[i].hasOwnProperty( 'set' ) && strings_object[i].set == true ){
                continue;
            }
            var string = new TRP_String( _this.language, _this.strings.length );
            string.set_string( strings_object[i] );
            _this.strings.push(string);
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

    this.set_previous_string = function(){
        var index = ( current_index - 1 < 0 ) ? _this.strings.length - 1 : current_index - 1;
        _this.strings[index].edit_string();
    };

    this.set_next_string = function(){
        var index = ( current_index + 1 > _this.strings.length - 1 ) ? 0 : current_index + 1;
        _this.edit_string_index( index );
    };

    this.edit_string_index = function( index ){
        _this.strings[index].edit_string();
    };

    this.get_categories = function (){
        var categorized = [];
        for ( var i in _this.strings ){
            if ( categorized[ _this.strings[i].node_type ] == undefined ) {
                categorized[ _this.strings[i].node_type ] = [];
            }
            categorized[ _this.strings[i].node_type ].push( _this.strings[i] );
        }

        return categorized;
    };

}

function TRP_String( language, array_index ){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    var TRP_NODE_TYPE = 'data-trp-node-type';
    var TRP_NODE_DESCRIPTION = 'data-trp-node-description';
    this.id = null;
    this.original = null;
    this.translated = null;
    this.status = null;
    //todo translate this
    this.node_type = 'Dynamic Added Strings';
    this.node_description = '';
    var jquery_object = null;
    this.language = language;
    this.index = array_index;
    this.slug = false;

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
        jquery_object = ( new_settings.hasOwnProperty ( 'jquery_object' ) ) ? new_settings.jquery_object : jquery_object;

        if ( jquery_object ){
            if ( trp_on_screen_language == trp_language ) {
                var text_to_set = null;
                if (new_settings.hasOwnProperty('translated') && new_settings.translated != _this.translated) {
                    text_to_set = new_settings.translated;
                }
                if (new_settings.hasOwnProperty('status') && new_settings.status == 0) {
                    text_to_set = _this.original;
                }

                if (text_to_set) {
                    var initial_value = jquery_object.text();
                    text_to_set = initial_value.replace(initial_value.trim(), text_to_set);
                    jquery_object.text(text_to_set);
                }
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
        trpEditor.edit_strings( _this, _this.index );
        return false; // cancel navigating to another link
    };

    this.set_raw_string = function( raw_string ){
        //jquery_object = jQuery( raw_string ).parent();
        jquery_object = jQuery( raw_string );
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            _this.id = translation_id_attribute;
            _this.node_type = jquery_object.attr( TRP_NODE_TYPE );
            _this.node_description = jquery_object.attr( TRP_NODE_DESCRIPTION );
            if ( jquery_object.attr( 'name' ) == 'trp-slug' ){
                _this.slug = true;
            }
        }else{
            _this.original = jquery_object.text();
        }
    };
}

function TRP_Lister( current_dictionary ) {
    var _this = this;
    var jquery_string_selector = jQuery( '#trp-string-categories' );
    var dictionary = current_dictionary;
    var category_array;

    this.select_string = function(){
        dictionary.edit_string_index( jquery_string_selector.val() );
    };

    this.reload_list = function (){
        category_array = dictionary.get_categories();
        jquery_string_selector.find( 'option').remove();
        jquery_string_selector.find( 'optgroup').remove();
        jquery_string_selector.append(jQuery('<option></option>'));
        for ( var category in category_array ){
            // todo add number of strings in category Text(6) Meta (3)
            jquery_string_selector.append( jQuery( '<optgroup></optgroup>' ).attr( 'label', _this.format_category_name( category ) ) );
            for ( var i in category_array[category] ) {
                //todo limit the original string length
                var original = category_array[category][i].original;
                var description = '';
                if ( category_array[category][i].node_description != "" ){
                    description = '(' + category_array[category][i].node_description + ') ';
                }
                if ( original ) {
                    jquery_string_selector.append(jQuery('<option></option>').attr( 'value', category_array[category][i].index).text( _this.format_text( original, category_array[category][i] )).attr( 'title', description ) );//category_array[category][i].index).text(original.substring(0, 90) + suspension_dots));
                }
            }
        }

        jquery_string_selector.on( 'change', _this.select_string );
    };

    this.format_text = function ( original, string ){
        var suspension_dots = '...';
        if ( original.length <= 90){
            suspension_dots = '';
        }

        return original.substring(0, 90) + suspension_dots ;
    };

    this.format_category_name = function( name ){
        name = name.replace(/_/g, ' ');
        name = name.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});

        return name;
    };

    this.update_selected_string = function( id ){
        jquery_string_selector.off( 'change', _this.select_string );
        jquery_string_selector.val( id ).change();
        jquery_string_selector.on( 'change', _this.select_string );
/*        for( var category in category_array ){
            for( var s in category_array[category] ){
                if ( category_array[category][s].id == id ){
                    jquery_string_selector.val( s).change();
                    break;
                }
            }
        }*/

    };

}

var trpEditor;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {

    trpEditor = new TRP_Editor();

});


