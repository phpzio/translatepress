/**
 * Handle Editor interface
 */
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
    var close_button = jQuery( '#trp-controls-close' );
    var translated_textareas = [];
    this.edit_translation_button = null;
    var categories;
    var trp_lister = null;
    var jquery_string_selector = jQuery( '#trp-string-categories' );
    var change_tracker;

    /**
     * Change the language in the Editor from the dropdown.
     *
     * @param select           HTML Element Select with languages
     */
    this.change_language = function( select ){
        var language = select.value;
        var link = jQuery( '#trp-preview-iframe' ).contents().find('link[hreflang=' + language + ']').first().attr('href');
        if ( link != undefined ){
            select.form.action = link;
            select.form.submit();
        }
    };

    /**
     * Initialize string finding, string select dropdown and change tracker.
     */
    this.initialize = function(){
        _this.saving_translation_ui();

        _this.edit_translation_button = null;

        _this.update_parent_url();

        _this.iframe_strings_lookup();

        if ( trp_lister != null ) {
            jquery_string_selector.off( 'change', trp_lister.select_string );
        }
        trp_lister = new TRP_Lister( dictionaries[trp_on_screen_language] );

        if ( change_tracker != null ) {
            change_tracker.destroy();
        }
    };

    /**
     * Mimic the navigation in iframe to the parent window.
     */
    this.update_parent_url = function(){

        var location = document.getElementById("trp-preview-iframe").contentWindow.location.href;
        var close_url = location.replace( '&trp-edit-translation=preview', '' );
        close_url = close_url.replace( '?trp-edit-translation=preview', '?' );
        if ( close_url[close_url.length -1] == '?' ){
            close_url = close_url.slice(0, -1);
        }

        /* remove the lang atribute from url. TODO maybe use this same method for trp-edit-translation ? */
        close_url = _this.remove_url_parameter( close_url, 'lang' );

        close_button.attr( 'href', close_url );
        location = location.replace( 'trp-edit-translation=preview', 'trp-edit-translation=true' );
        window.history.replaceState( null, null, location );
    };

    /**
     * Search for strings in preview window.
     *
     * Collects all strings prepared in the back-end and triggers ajax request.
     */
    this.iframe_strings_lookup = function(){
        _this.preview_iframe = jQuery( '#trp-preview-iframe').contents();
        strings = [];
        dictionaries = [];
        var all_strings = _this.preview_iframe.contents().find( '[data-trp-translate-id]' );
        if( all_strings.length != 0 ){
            var title = document.getElementById("trp-preview-iframe").contentDocument.title;
            if ( /<[a-z][\s\S]*>/i.test( title ) ) {
                // add iframe title
                all_strings.push ( jQuery( document.getElementById("trp-preview-iframe").contentDocument.title )[0] );
            }
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
        }
        else{
            loading_animation.toggle();
        }
    };

    /**
     * Query for strings to get original translation.
     *
     * @param strings_to_query        Strings to find a translation for.
     */
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
                change_tracker = new TRP_Change_Tracker( _this.original_textarea, translated_textareas );
            },
            error: function(errorThrown){
                console.log( 'TranslatePress AJAX Request Error' );
            }

        });
    };

    /**
     * Store response in dictionaries variables.
     *
     * @param response       The Ajax response message.
     */
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
        _this.saved_translation_ui();
    };

    /**
     * Put the selected string into the translate textareas.
     *
     * The string can be selected from the preview window or from the select dropdown.
     * @param trp_string
     * @param index
     */
    this.edit_strings = function ( trp_string, index ){
        if ( change_tracker.check_unsaved_changes() ) {
            return;
        }
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

    /**
     * Prepare modified translation and send it via Ajax for saving in db.
     *
     * Triggered by Save translation button.
     * Calls function to send ajax request.
     */
    this.save_string = function(){
        var strings_to_save = {};
        var modified = false;
        var original = _this.original_textarea.val();
        var action = 'trp_save_translations';
        if ( original != "" ) {
            for ( var key in translated_textareas ) {
                var translated = translated_textareas[key].val();
                var string = dictionaries[key].get_string_by_original(original);
                if ( string.slug == true ){
                    action = 'trp_save_slug_translation';
                }
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
                }
            }
        }

        if ( modified ){
            _this.saving_translation_ui();
            _this.ajax_save_strings( strings_to_save, action );
        }else{
            _this.saved_translation_ui();
        }
    };

    /**
     * Getter function for dictionaries.
     *
     * @returns {*}         Array of dictionaries object.
     */
    this.get_dictionaries = function(){
        return dictionaries;
    };

    /**
     * Ajax request with translation to be stored.
     *
     * @param strings_to_save           Strings to save in database.
     * @param action                    'trp_save_translations' | 'trp_save_slug_translation'.
     */
    this.ajax_save_strings = function ( strings_to_save, action ){
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
                console.log( 'TranslatePress AJAX Request Error' );
            }
        });
    };

    /**
     * Show UI for translation being saved.
     */
    this.saving_translation_ui = function(){
        loading_animation.toggle();
        save_button.attr( 'disabled', 'disabled' );
    };

    /**
     * Show UI for translation done saving.
     */
    this.saved_translation_ui = function(){
        save_button.removeAttr( 'disabled' );
        loading_animation.css('display', 'none');
        if ( change_tracker ) {
            change_tracker.mark_changes_saved();
            translation_saved.css("display","inline");
            translation_saved.delay(3000).fadeOut(400);
        }
    };

    /**
     * Toggle extra textareas for other languages
     */
    this.toggle_languages = function (){
        jQuery( '.trp-other-language' ).toggle();
        jQuery( '.trp-toggle-languages' ).toggle();
    };

    /**
     * Get into focus previous string from String select dropdown.
     *
     * Triggerd by Previous Button.
     */
    this.previous_string = function(){
        dictionaries[trp_on_screen_language].set_previous_string();
    };

    /**
     * Get into focus next string from String select dropdown.
     *
     * Triggerd by Next Button.
     */
    this.next_string = function(){
        dictionaries[trp_on_screen_language].set_next_string();
    };

    /**
     * Return the given url without the given parameter and its value
     *
     * @param url
     * @param parameter
     * @returns {*}
     */
    this.remove_url_parameter = function(url, parameter) {
        //prefer to use l.search if you have a location/link object
        var urlparts= url.split('?');
        if (urlparts.length>=2) {

            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }

            url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
            return url;
        } else {
            return url;
        }
    };

    /**
     * Resizing preview window.
     *
     * @param event
     * @param ui
     */
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

    /**
     * Add event handlers for buttons and dropdowns.
     */
    function add_event_handlers(){
        save_button.on( 'click', _this.save_string );
        jQuery( '.trp-toggle-languages span' ).on( 'click', _this.toggle_languages );
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

    /**
     * Make string selection dropdown to have options with descriptions below.
     *
     * @param option
     * @returns {*}
     */
    function format_option(option){
        option = jQuery(
                '<div>' + option.text + '</div><div class="string-selector-description">' + option.title + '</div>'
            );
        return option;
    }

    add_event_handlers();
}

/**
 * Collection of TRP_String for a particular language.
 */
function TRP_Dictionary( language_code ){

    var _this = this;
    this.strings = []; // TRP_String
    this.language = language_code;
    var current_index = 0;


    /**
     * The currently translated index of the Dictionary.
     *
     * Refers to a TRP_String object of this dictionary at the specified index.
     *
     * @param index
     */
    this.set_current_index = function ( index ){
        current_index = index;
    };

    /**
     * Foreach TRP_String of this dictionary, update the values received as parameter.
     *
     * For new strings, create new entries.
     *
     * @param strings_object
     */
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

    /**
     * Concatenate given strings with the existing list.
     *
     * @param new_strings
     */
    this.set_on_screen_strings = function( new_strings ){
        _this.strings = _this.strings.concat( new_strings );
    };

    /**
     * Return a TRP_String entry for the given original.
     *
     * @param original
     * @returns {*}
     */
    this.get_string_by_original = function ( original ){
        for ( var i in _this.strings ){
            if ( _this.strings[i].original.trim() == original.trim() ){
                return _this.strings[i];
            }
        }
        return {};
    };

    /**
     * Place in translating textareas the previous string in the list.
     */
    this.set_previous_string = function(){
        var index = ( current_index - 1 < 0 ) ? _this.strings.length - 1 : current_index - 1;
        _this.edit_string_index( index );
    };

    /**
     * Place in translating textareas the next string in the list.
     */
    this.set_next_string = function(){
        var index = ( current_index + 1 > _this.strings.length - 1 ) ? 0 : current_index + 1;
        _this.edit_string_index( index );
    };

    /**
     * Place in translating textareas the string at given index in dictionary.
     *
     * @param index
     */
    this.edit_string_index = function( index ){
        _this.strings[index].edit_string();
    };

    /**
     * Return strings organized in categories.
     *
     * Used in String selection dropdown.
     *
     * @returns {Array}
     */
    this.get_categories = function (){
        var categorized = [];
        for ( var i in _this.strings ){
            if ( categorized[ _this.strings[i].node_type ] == undefined ) {
                categorized[ _this.strings[i].node_type ] = [];
            }
            if ( _this.strings[i].original != '' ){
                categorized[ _this.strings[i].node_type ].push( _this.strings[i] );
            }
        }

        for ( var i in categorized ){
            if ( categorized[i].length == 0 ){
                delete categorized[i];
            }
        }

        return categorized;
    };

}

/**
 * String original, translation and jquery object.
 */
function TRP_String( language, array_index ){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    var TRP_NODE_TYPE = 'data-trp-node-type';
    var TRP_NODE_DESCRIPTION = 'data-trp-node-description';
    this.id = null;
    this.original = null;
    this.translated = null;
    this.status = null;
    this.node_type = 'Dynamic Added Strings';
    this.node_description = '';
    var jquery_object = null;
    this.language = language;
    this.index = array_index;
    this.slug = false;
    this.slug_post_id = false;

    /**
     * Return string id, original and slug details
     *
     * Used in get translation request.
     *
     * @returns {{}}
     */
    this.get_details = function(){
        var details = {};
        if ( _this.slug ){
            details['slug'] = _this.slug;
            details['slug_post_id'] = _this.slug_post_id;
        }
        details['id'] = _this.id;
        details['original'] = _this.original;
        return details;
    };

    /**
     * Return given text converted to html.
     *
     * Useful for decoding special characters into displayable form.
     *
     * @param html
     * @returns {*}
     */
    function decode_html( html ) {
        var txt = document.createElement( "textarea" );
        txt.innerHTML = html;
        return txt.value;
    }

    /**
     * Update string information. Also updates in page if available.
     *
     * @param new_settings
     */
    this.set_string = function ( new_settings ){
        _this.id = ( new_settings.hasOwnProperty ( 'id' ) ) ? new_settings.id : _this.id;
        _this.original = ( new_settings.hasOwnProperty ( 'original' ) ) ? new_settings.original : _this.original;
        _this.original = decode_html( _this.original );
        jquery_object = ( new_settings.hasOwnProperty ( 'jquery_object' ) ) ? new_settings.jquery_object : jquery_object;

        if ( jquery_object ){
            if ( trp_language == trp_on_screen_language ) {
                var text_to_set = null;
                if (new_settings.hasOwnProperty('translated') && new_settings.translated != _this.translated) {
                    text_to_set = decode_html ( new_settings.translated );
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
        _this.translated = ( new_settings.hasOwnProperty( 'translated' ) ) ? decode_html ( new_settings.translated ) : _this.translated;
    };

    /**
     * Show the pencil and border the viewable string in Preview window.
     */
    this.highlight = function (){
        if ( ! trpEditor.edit_translation_button ){
            jquery_object.prepend( '<span class="trp-edit-translation"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z"></path></svg></span>' );
            trpEditor.edit_translation_button = jquery_object.children('.trp-edit-translation');
        }else{
            jquery_object.prepend( trpEditor.edit_translation_button );
        }
        trpEditor.edit_translation_button.off( 'click' );
        trpEditor.edit_translation_button.on( 'click', _this.edit_string );

        jQuery( this ).addClass( 'trp-highlight' );
    };

    /**
     * Remove border for viewable string
     */
    this.unhighlight = function (){
        jQuery( this ).removeClass( 'trp-highlight' );
    };

    /**
     * Show string in translatable textareas.
     *
     * @returns {boolean}
     */
    this.edit_string = function(){
        trpEditor.edit_strings( _this, _this.index );
        return false; // cancel navigating to another link
    };

    /**
     * Extract fom raw html code the information for a TRP_String.
     *
     * @param raw_string
     */
    this.set_raw_string = function( raw_string ){
        jquery_object = jQuery( raw_string );
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            _this.id = translation_id_attribute;
            _this.node_type = jquery_object.attr( TRP_NODE_TYPE );
            _this.node_description = jquery_object.attr( TRP_NODE_DESCRIPTION );
            if ( jquery_object.attr( 'name' ) == 'trp-slug' ){
                _this.slug = true;
                _this.slug_post_id = jquery_object.attr( 'post-id' );
                _this.original = jquery_object.attr( 'content' );
            }
        }else{
            _this.original = jquery_object.text();
        }
    };

}

/**
 * String list dropdown handler
 */
function TRP_Lister( current_dictionary ) {

    var _this = this;
    var jquery_string_selector = jQuery( '#trp-string-categories' );
    var dictionary = current_dictionary;
    var category_array;


    /**
     * A string has been selected from the list.
     */
    this.select_string = function(){
        dictionary.edit_string_index( jquery_string_selector.val() );
    };

    /**
     * Refresh list with new strings.
     */
    this.reload_list = function (){
        category_array = dictionary.get_categories();
        jquery_string_selector.find( 'option').remove();
        jquery_string_selector.find( 'optgroup').remove();
        jquery_string_selector.append(jQuery('<option></option>'));
        for ( var category in category_array ){
            jquery_string_selector.append( jQuery( '<optgroup></optgroup>' ).attr( 'label', _this.format_category_name( category ) ) );
            for ( var i in category_array[category] ) {
                var original = category_array[category][i].original;
                var description = '';
                if ( category_array[category][i].node_description != undefined && category_array[category][i].node_description != '' ){
                    description = '(' + category_array[category][i].node_description + ')';
                }
                if ( original ) {
                    jquery_string_selector.append(jQuery('<option></option>').attr( 'value', category_array[category][i].index).text( _this.format_text( original, category_array[category][i] )).attr( 'title', description ) );//category_array[category][i].index).text(original.substring(0, 90) + suspension_dots));
                }
            }
        }

        jquery_string_selector.on( 'change', _this.select_string );
    };

    /**
     * Cut the length of text displayed in string dropdown list.
     */
    this.format_text = function ( original, string ){
        var suspension_dots = '...';
        if ( original.length <= 90){
            suspension_dots = '';
        }

        return original.substring(0, 90) + suspension_dots ;
    };

    /**
     * Format the name for the option group in string dropdown list.
     */
    this.format_category_name = function( name ){
        name = name.replace(/_/g, ' ');
        name = name.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});

        return name;
    };

    /**
     * Change the selected string in string dropdown list based on the id.
     *
     * @param id
     */
    this.update_selected_string = function( id ){
        jquery_string_selector.off( 'change', _this.select_string );
        jquery_string_selector.val( id ).change();
        jquery_string_selector.on( 'change', _this.select_string );
    };

}

/**
 * Track changes of the translation textareas.
 */
function TRP_Change_Tracker( _original_textarea, _translated_textareas ){

    var _this = this;
    var changes_saved = true;
    var original_textarea = _original_textarea;
    var translated_textareas = _translated_textareas;


    /**
     * Check if there are unsaved translations in textareas.
     *
     * Show animation in case it does.
     *
     * @returns {boolean}
     */
    this.check_unsaved_changes = function(){

        if ( !changes_saved ){
            //open other languages if unsaved changes below
            if ( jQuery ( '.trp-unsaved-changes.trp-other-language').last().css( 'display' ) == 'none'  ){
                trpEditor.toggle_languages();
            }

            jQuery ( '.trp-unsaved-changes textarea').css ( 'backgroundColor', 'red' ).animate({
                backgroundColor: "#eee"
            }, 1000 );
        }
        return !changes_saved;
    };

    /**
     * Disable restrictions for saving.
     */
    this.mark_changes_saved = function(){
        changes_saved = true;
        _this.initialize();
    };

    /**
     * Enable restrictions for saving.
     * @param key
     */
    this.show_unsaved_changes = function( key ){
        translated_textareas[key].parent().addClass('trp-unsaved-changes');
    };

    /**
     * Stop listening for changes.
     */
    this.destroy = function(){
        jQuery('.trp-language-text:not(.trp-default-text)').off();
    };

    /**
     * Change was detected in textareas.
     */
    this.change_detected = function(){
        if ( original_textarea.val() == '' ){
            return;
        }
        var id = this.id.replace( 'trp-translated-', '' );
        jQuery( this ).off();
        _this.show_unsaved_changes( id );
        changes_saved = false;
    };

    /**
     * Set event listeners on translation textareas.
     */
    this.initialize = function(){

        for ( var key in translated_textareas ) {
            translated_textareas[key].parent().removeClass('trp-unsaved-changes');
        }
        jQuery('.trp-language-text:not(.trp-default-text) textarea').off().on('input propertychange paste', _this.change_detected );
    };

    /**
     * Restore initial translation.
     */
    this.discard_changes = function( ){
        var dictionaries = trpEditor.get_dictionaries();
        var original = original_textarea.val();
        var language = jQuery(this).parent().attr( 'id' ).replace( 'trp-language-', '' );
        var string = dictionaries[language].get_string_by_original(original);
        translated_textareas[language].val( string.translated ).change();
        translated_textareas[language].on('input propertychange paste', _this.change_detected );
        translated_textareas[language].parent().removeClass('trp-unsaved-changes');
        changes_saved = true;
        for ( var key in translated_textareas ){
            if ( translated_textareas[key].parent().hasClass( 'trp-unsaved-changes' ) ){
                changes_saved = false;
            }
        }
    };

    /**
     * Set event listeners for discard changes button.
     */
    this.add_event_handlers = function(){
        _this.initialize();
        jQuery( '.trp-discard-changes' ).on('click', _this.discard_changes );

    };

    _this.add_event_handlers();
}



var trpEditor;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {

    trpEditor = new TRP_Editor();

});


