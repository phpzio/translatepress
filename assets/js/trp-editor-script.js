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
    var save_button = jQuery( '.trp-save-string' );
    var close_button = jQuery( '#trp-controls-close' );
    var translated_textareas = [];
    this.edit_translation_button = null;
    var categories;
    this.trp_lister = null;
    this.jquery_string_selector = jQuery( '#trp-string-categories' );
    this.change_tracker  = null;
    this.maybe_overflow_fix  = null;

    /**
     * Change the language in the Editor from the dropdown.
     *
     * @param select           HTML Element Select with languages
     */
    this.change_language = function( select ){
        var language = select.value;
        var link = jQuery( '#trp-preview-iframe' ).contents().find('link[hreflang=' + language + ']').first().attr('href');
        if ( link != undefined ){

            /* pass on trp-view-as parameters to all links that also have preview parameter */
            if( typeof URL == 'function' && window.location.href.search("trp-view-as=") >= 0 && window.location.href.search("trp-view-as-nonce=") >= 0 ){
                var currentUrl = new URL(window.location.href);
                var trp_view_as = currentUrl.searchParams.get("trp-view-as");
                link = _this.update_query_string('trp-view-as', trp_view_as, link );

                var trp_view_as_nonce = currentUrl.searchParams.get("trp-view-as-nonce");
                link = _this.update_query_string('trp-view-as-nonce', trp_view_as_nonce, link );
            }
            link = _this.update_query_string('trp-edit-translation', 'true', link );
            window.location.href = link;
        }
    };

    /**
     * Change view as iframe source
     *
     * @param select           HTML Element Select with languages
     */
    this.change_view_as = function( select ){
        var view_as = select.value;
        var view_nonce = jQuery('option:selected', select).attr('data-view-as-nonce');
        var current_link = document.getElementById("trp-preview-iframe").contentWindow.location.href;
        current_link = current_link.replace( 'trp-edit-translation=true', 'trp-edit-translation=preview' );

        /* remove maybe previously selected values */
        current_link = _this.remove_url_parameter( current_link, 'trp-view-as' );
        current_link = _this.remove_url_parameter( current_link, 'trp-view-as-nonce' );

        if ( current_link != undefined ){
            if( view_as == 'current_user' )
                jQuery( '#trp-preview-iframe' ).attr('src', current_link );
            else
                jQuery( '#trp-preview-iframe' ).attr('src', current_link + '&trp-view-as=' + view_as + '&trp-view-as-nonce=' + view_nonce );
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

        if ( _this.trp_lister != null ) {
            _this.jquery_string_selector.off( 'change', _this.trp_lister.select_string );
        }
        _this.trp_lister = new TRP_Lister( dictionaries[trp_on_screen_language] );

        if (  _this.change_tracker != null ) {
            _this.change_tracker.destroy();
        }
    };

    /**
     * Mimic the navigation in iframe to the parent window.
     */
    this.update_parent_url = function(){

        var location = document.getElementById("trp-preview-iframe").contentWindow.location.href;
        var close_url = location.replace( '&trp-edit-translation=preview', '' );
        close_url = close_url.replace( '?trp-edit-translation=preview', '?' );

        /* remove the lang atribute from url. TODO maybe use this same method for trp-edit-translation ? */
        close_url = _this.remove_url_parameter( close_url, 'lang' );
        close_url = _this.remove_url_parameter( close_url, 'trp-view-as' );
        close_url = _this.remove_url_parameter( close_url, 'trp-view-as-nonce' );

        if ( close_url[close_url.length -1] == '?' ){
            close_url = close_url.slice(0, -1);
        }

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

        var all_strings = _this.preview_iframe.find( '[data-trp-translate-id]' );
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
                _this.trp_lister.reload_list();
                _this.change_tracker = new TRP_Change_Tracker( _this.original_textarea, translated_textareas );
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
        _this.saved_translation_ui(true);
    };

    /**
     * Put the selected string into the translate textareas.
     *
     * The string can be selected from the preview window or from the select dropdown.
     * @param trp_string
     * @param index
     */
    this.edit_strings = function ( trp_string, index ){
        if (  _this.change_tracker.check_unsaved_changes() ) {
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
    this.saved_translation_ui = function( dontShowMessage ){
        dontShowMessage = dontShowMessage || 0;
        save_button.removeAttr( 'disabled' );
        if( gettext_dictionaries != null )
            loading_animation.css('display', 'none');//don't hide the animation if the gettexts arent loaded
        if (  _this.change_tracker ) {
            _this.change_tracker.mark_changes_saved();
            if( !dontShowMessage ) {
                translation_saved.css("display", "inline");
                translation_saved.delay(3000).fadeOut(400);
            }
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
     * Update url with query string.
     *
     */
    this.update_query_string = function(key, value, url) {
        if (!url) url = window.location.href;
        var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
            hash;

        if (re.test(url)) {
            if (typeof value !== 'undefined' && value !== null)
                return url.replace(re, '$1' + key + "=" + value + '$2$3');
            else {
                hash = url.split('#');
                url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
                if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                    url += '#' + hash[1];
                return url;
            }
        }
        else {
            if (typeof value !== 'undefined' && value !== null ) {
                var separator = url.indexOf('?') !== -1 ? '&' : '?';
                hash = url.split('#');
                url = hash[0] + separator + key + '=' + value;
                if (typeof hash[1] !== 'undefined' && hash[1] !== null)
                    url += '#' + hash[1];
                return url;
            }
            else
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
        preview_container.css('left', ( width - 348 ) );
        preview_container.css('width', (total_width - width));
    }

    /**
     * Add event handlers for buttons and dropdowns.
     */
    function add_event_handlers(){
        save_button.on( 'click', function(e){
            e.preventDefault();
            /* trigger a save for a normal string here and not a gettext string */
            if(jQuery(this).attr('id') == 'trp-save'){
                _this.save_string();
            }
        });
        jQuery( '.trp-toggle-languages span' ).on( 'click', _this.toggle_languages );
        jQuery( '#trp-previous' ).on( 'click', function(e){
            e.preventDefault();
            if (  _this.change_tracker.check_unsaved_changes() ) {
                return;
            }
            prev_option_value = jQuery( 'option:selected', _this.jquery_string_selector ).prevAll('option').first().attr('value');
            if( typeof prev_option_value != "undefined" && prev_option_value != '' ) {
                _this.jquery_string_selector.val(prev_option_value).trigger('change');
            }
        });
        jQuery( '#trp-next' ).on( 'click', function(e){
            e.preventDefault();
            if (  _this.change_tracker.check_unsaved_changes() ) {
                return;
            }
            next_option_value = jQuery('option:selected', _this.jquery_string_selector).nextAll('option').first().attr('value');
            if( typeof next_option_value != "undefined" && next_option_value != '' ) {
                _this.jquery_string_selector.val(next_option_value).trigger('change');
            }
        });

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

        var placeholder_text = _this.jquery_string_selector.attr('data-trp-placeholder');
        if (placeholder_text != '') {
            placeholder_text = 'Select string to translate...';
        }
        _this.jquery_string_selector.select2({ placeholder: placeholder_text, templateResult: format_option, width: '100%' });
        jQuery( '#trp-language-select' ).select2({ width: '100%' });
        jQuery( '#trp-view-as-select' ).select2({ width: '100%' });

        /* when we have unsaved changes prevent the strings dropdown from opening so we do not have a disconnect between the textareas and the dropdown */
        _this.jquery_string_selector.on('select2:opening', function (e) {
            if ( trpEditor.change_tracker.check_unsaved_changes() ) {
                e.preventDefault();
            }
        });
    }

    /**
     * Remove pencil icon from preview window.
     */
    this.remove_pencil_icon = function(){
        jQuery( '#trp-preview-iframe').contents().find( '.trp-edit-translation' ).remove();
    };

    /**
     * if the edit icon button has a parent with overflow hidden and position relative it won't show so we want to change it's margin to 0 so it will appear inside the element
     */
    this.maybe_overflow_fix = function( icon ){
        /*if (navigator.userAgent.search("Chrome") >= 0 || navigator.userAgent.search("Firefox") >= 0 || navigator.userAgent.search("Edge") >= 0 ) {
            icon.parents().filter( function(){ var overflow = jQuery(this).css('overflow');
                return overflow == 'hidden' && !jQuery(this).is('body'); } ).each( function(){
                jQuery(this).parent().addClass('trp-overflow-transform-fixer');
                return false;
            });
        }
        else{*/
            icon.parents().filter( function(index){ if( index > 6 ) return false;var overflow = jQuery(this).css('overflow');
                return overflow == 'hidden' && !jQuery(this).is('body'); } ).each( function(){
                jQuery(this).addClass('trp-overflow-inside-fixer');
                return false;
            });
        //}
    };

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

    this.make_sure_pencil_icon_is_inside_view = function( jquery_object_highlighted ){
        var rect = jquery_object_highlighted.getBoundingClientRect();
        if (rect.left < 30 ){
            var margin = - rect.left;
            trpEditor.edit_translation_button[0].setAttribute( 'style', 'margin-left: ' + margin + 'px !important' );
        }else{
            trpEditor.edit_translation_button[0].removeAttribute( 'style' );
        }
    };

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
     * Place in translating textareas the string at given index in dictionary.
     *
     * @param index
     */
    this.edit_string_index = function( index ){
        if( index ) {
            /* start modifications of the editor screen */
            jQuery('.trp-save-string').attr('id', 'trp-save');
            jQuery('.trp-language-name[data-trp-gettext-language-name]').text(jQuery('.trp-language-name[data-trp-default-language-name]').attr('data-trp-default-language-name'));
            jQuery('#trp-gettext-original').hide();
            jQuery('.trp-discard-on-default-language').hide();
            jQuery('.trp-default-language textarea').attr('readonly', 'readonly');
            /* end modifications of the editor screen */
            _this.strings[index].edit_string();
        }
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
        categorized[ 'Meta Information' ] = [];
        categorized[ 'String List' ] = [];
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
                _this.wrap_special_html_elements();
                if (text_to_set) {
                    var initial_value = jquery_object.text();
                    text_to_set = initial_value.replace(initial_value.trim(), text_to_set);
                    if ( jquery_object.attr( 'data-trp-attr' ) ){
                        jquery_object.children().attr( jquery_object.attr('data-trp-attr'), text_to_set );
                    }else if( jquery_object.attr( 'data-trp-button' ) ){
                        jquery_object.children('button').text(text_to_set);
                    }else {
                        jquery_object.html( text_to_set );
                    }
                }
            }

            jquery_object.on( 'mouseenter', '', _this.highlight );
            jquery_object.on( 'mouseleave', '', _this.unhighlight );
        }


        _this.status = ( new_settings.hasOwnProperty( 'status' ) ) ? new_settings.status : _this.status;
        _this.translated = ( new_settings.hasOwnProperty( 'translated' ) ) ? decode_html ( new_settings.translated ) : _this.translated;
    };

    /**
     * Wrap buttons and placeholders so that we can display the pencil button and also replace with translation.
     */
    this.wrap_special_html_elements = function(){
        if( jquery_object.is('button') ){
            jquery_object.unwrap('trp-highlight');
            jquery_object.wrap('<trp-highlight data-trp-button="true"></trp-highlight>');
            jquery_object = jquery_object.parent();
        }
        else if ( jquery_object.attr( 'type' ) == 'submit' || jquery_object.attr( 'type' ) == 'button'  ) {
            jquery_object.unwrap('trp-highlight');
            jquery_object.wrap('<trp-highlight data-trp-attr="value"></trp-highlight>');
            jquery_object = jquery_object.parent();
        }
        else if ( ( jquery_object.attr( 'type' ) == 'text' || jquery_object.attr( 'type' ) == 'search' ) && ( typeof jquery_object.attr( 'placeholder' ) != 'undefined' ) ) {
            jquery_object.unwrap('trp-highlight');
            jquery_object.wrap('<trp-highlight data-trp-attr="placeholder"></trp-highlight>');
            jquery_object = jquery_object.parent();
        }
    };

    /**
     * Show the pencil and border the viewable string in Preview window.
     */
    this.highlight = function (){
        if ( ! trpEditor.edit_translation_button ){
            jquery_object.prepend( '<span class="trp-edit-translation"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z"></path></svg></span>' );
            trpEditor.edit_translation_button = jquery_object.children('.trp-edit-translation');
        }else{
            _this.wrap_special_html_elements();
            trpEditor.maybe_overflow_fix(trpEditor.edit_translation_button);
            jquery_object.prepend(trpEditor.edit_translation_button);
        }

        trpEditor.make_sure_pencil_icon_is_inside_view( jquery_object[0] );

        trpEditor.edit_translation_button.off( 'click' );
        trpEditor.edit_translation_button.on( 'click',  function(e){
            e.preventDefault();
            if ( trpEditor.change_tracker.check_unsaved_changes() ) {
                return;
            }

            notDoingAjax = true;
            if( typeof jQuery.active != 'undefined' ){
                if( jQuery.active !== 0 )
                    notDoingAjax = false;
            }

            if( jQuery( 'option[value="'+_this.index+'"]', trpEditor.jquery_string_selector ).length != 0 && !jQuery('.trp-ajax-loader', trpEditor).is(":visible")  && notDoingAjax ) {
                trpEditor.jquery_string_selector.val( _this.index ).trigger( 'change', true )
            }else{
                trpEditor.jquery_string_selector.trigger('trpSelectorNotChanged');
            }
        });

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
    var jquery_string_selector = trpEditor.jquery_string_selector;
    var dictionary = current_dictionary;
    var category_array;

    /*
     * Save current selected option in the dropdown. Should be called before we make changes in the dropdown.
     */
    this.cache_selected_option = function(){
        var selected_option = jQuery( 'option:selected', jquery_string_selector ).val();
        if ( typeof selected_option != "undefined" && selected_option != "" ) {
            cached_selected_option = selected_option;
        }
    };

    /*
     * Restore saved cached option. Should be called after we make changes in the dropdown. Otherwise the selected option will be changed to default.
     */
    this.set_cached_option = function(){
        if ( typeof cached_selected_option != "undefined" && cached_selected_option != "" ){
            jquery_string_selector.val( cached_selected_option );
        }
    };

    /**
     * A string has been selected from the list.
     */
    this.select_string = function( event, keep_pencil_icon ){
        /* this is how we differentiate gettext strings from normal strings */
        trp_gettext_id = jQuery(this).find(':selected').attr('data-trp-gettext-id');
        if( trp_gettext_id ) {
            _this.set_textareas_with_gettext(trp_gettext_id);
        }
        else {
            dictionary.edit_string_index(jquery_string_selector.val());
        }
        if ( keep_pencil_icon === undefined ){
            trpEditor.remove_pencil_icon();
        }
    };

    /**
     * Refresh list with new strings.
     */
    this.reload_list = function (){
        _this.cache_selected_option();

        category_array = dictionary.get_categories();
        jQuery( "#trp-gettext-strings-optgroup", jquery_string_selector ).prevAll(":not(.default-option)").remove();
        /* add the normal strings before the trp-gettext-strings-optgroup optiongroup so it doesn't matter which ajax finishes first */
        for ( var category in category_array ){
            jQuery( "#trp-gettext-strings-optgroup", jquery_string_selector ).before( jQuery( '<optgroup></optgroup>' ).attr( 'label', _this.format_category_name( category ) ) );
            for ( var i in category_array[category] ) {
                var original = category_array[category][i].original;
                var description = '';
                if ( category_array[category][i].node_description != undefined && category_array[category][i].node_description != '' ){
                    description = '(' + category_array[category][i].node_description + ')';
                }
                if ( original ) {
                    jQuery( "#trp-gettext-strings-optgroup", jquery_string_selector ).before(jQuery('<option></option>').attr( 'value', category_array[category][i].index).text( _this.format_text( original )).attr( 'title', description ) );
                }
            }
        }
        jquery_string_selector.on( 'change', _this.select_string );

        _this.set_cached_option();
    };


    this.add_gettext_strings = function (){
        _this.cache_selected_option();

        gettext_category = dictionary;
        jQuery( "#trp-gettext-strings-optgroup", jquery_string_selector ).nextAll().remove();
        for ( var i in gettext_category){
            var original = gettext_category[i].original;
            jQuery( "#trp-gettext-strings-optgroup", jquery_string_selector ).after(jQuery('<option></option>').attr( 'value', 'gettext-'+gettext_category[i].id ).text( _this.format_text( original )).attr( 'title', gettext_category[i].domain ).attr( 'data-trp-gettext-id', gettext_category[i].id ) );
        }

        _this.set_cached_option();
    };

    /**
     * Cut the length of text displayed in string dropdown list.
     */
    this.format_text = function ( original ){
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

    this.set_textareas_with_gettext =  function( string_id ){

        /* don't do anything if there is an unsaved change */
        if ( trpEditor.change_tracker.check_unsaved_changes() ) {
            return;
        }

        /* start modifications of the editor screen */
        jQuery( '#trp-gettext-original' ).show();
        jQuery('.trp-save-string').attr('id', 'trp-save-gettext');
        jQuery( '.trp-language-name[data-trp-gettext-language-name]').text(jQuery( '.trp-language-name[data-trp-gettext-language-name]').attr('data-trp-gettext-language-name'));
        jQuery( '.trp-discard-on-default-language' ).show();
        /* end modifications of the editor screen */

        jQuery( gettext_dictionaries[trp_language] ).each(function(){
            if( this.id == string_id ){
                original = this.original;
                domain = this.domain;
            }
        });

        jQuery( '#trp-controls .trp-language-text textarea' ).each(function(){
            textarea = this;

            if( jQuery(textarea).parent().hasClass('trp-default-language') ) {
                jQuery(textarea).removeAttr('readonly');
            }

            if( jQuery(textarea).parent().hasClass('trp-gettext-original-language') ){
                jQuery( textarea ).val( original );
            }
            else{
                textarea_language = jQuery( textarea ).attr("data-trp-language-code");
                jQuery( gettext_dictionaries[textarea_language] ).each(function(){
                    if( this.original == original && this.domain == domain ){
                        jQuery( textarea ).val( this.translated );
                        jQuery( textarea ).attr( 'data-trp-translate-id', this.id );
                    }
                });
            }
        });
    };

    this.set_texts_select_to_gettext = function( jquery_object ){
        if ( trpEditor.change_tracker.check_unsaved_changes() ) {
            return;
        }
        string_id = jquery_object.attr('data-trpgettextoriginal');

        notDoingAjax = true;
        if( typeof jQuery.active != 'undefined' ){
            if( jQuery.active !== 0 )
                notDoingAjax = false;
        }
        if( jQuery( 'option[value="gettext-'+string_id+'"]', trpEditor.jquery_string_selector ).length != 0 && !jQuery('.trp-ajax-loader', trpEditor).is(":visible") && notDoingAjax ) {
            trpEditor.jquery_string_selector.val('gettext-' + string_id).trigger('change', true);
        }else{
            trpEditor.jquery_string_selector.trigger('trpSelectorNotChanged');
        }
    }

}

/**
 * Track changes of the translation textareas.
 */
function TRP_Change_Tracker( _original_textarea, _translated_textareas ){

    var _this = this;
    var changes_saved = true;
    var original_textarea = _original_textarea;
    /* clone the textareas here so we don;t actually modify the translated_textareas object in the TRP_Editor */
    var check_translated_textareas = jQuery.extend({}, _translated_textareas);
    /* make the change tracker aware of the default language textarea. we need this when editing gettext strings */
    check_translated_textareas[original_textarea.attr('data-trp-language-code')] = jQuery( '#trp-original' );

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
            jQuery ( '#trp-unsaved-changes-warning-message').css ( 'backgroundColor', 'red' ).animate({
                backgroundColor: "#fff"
            }, 1000 );

            var unsaved_changes_warning_message = jQuery ( '#trp-unsaved-changes-warning-message')
            unsaved_changes_warning_message.css("display","inline");
            unsaved_changes_warning_message.delay(3000).fadeOut(400);

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
        check_translated_textareas[key].parent().addClass('trp-unsaved-changes');
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
        if( jQuery("#trp-gettext-original-textarea").is(":visible") ){
            //gettext case here
            if ( jQuery("#trp-gettext-original-textarea").val() == '' ){
                return;
            }
        }
        else{
            //normal string case here
            if ( original_textarea.val() == '' ){
                return;
            }
        }
        var id = this.id.replace( 'trp-translated-', '' );
        if( id == trpEditor.original_textarea.attr('id') )
            id = trpEditor.original_textarea.attr('data-trp-language-code');
        jQuery( this ).off();
        _this.show_unsaved_changes( id );
        changes_saved = false;
    };

    /**
     * Set event listeners on translation textareas.
     */
    this.initialize = function(){

        for ( var key in check_translated_textareas ) {
            check_translated_textareas[key].parent().removeClass('trp-unsaved-changes');
        }
        jQuery('.trp-language-text:not(.trp-default-text) textarea').off().on('input propertychange paste', _this.change_detected );
    };

    /**
     * Restore initial translation.
     */
    this.discard_changes = function( ){
        var language = jQuery(this).parent().attr( 'id' ).replace( 'trp-language-', '' );

        if( jQuery("#trp-gettext-original-textarea").is(":visible") ){
            //gettext case here
            var original = jQuery("#trp-gettext-original-textarea").val();
            for ( var i in gettext_dictionaries[language] ){
                if ( gettext_dictionaries[language][i].original.trim() == jQuery("#trp-gettext-original-textarea").val().trim() ){
                    string = gettext_dictionaries[language][i];
                }
            }
        }
        else{
            //normal string case here
            var dictionaries = trpEditor.get_dictionaries();
            var original = original_textarea.val();
            var string = dictionaries[language].get_string_by_original(original);
        }
        check_translated_textareas[language].val( string.translated ).change();
        check_translated_textareas[language].on('input propertychange paste', _this.change_detected );
        check_translated_textareas[language].parent().removeClass('trp-unsaved-changes');
        changes_saved = true;
        for ( var key in check_translated_textareas ){
            if ( check_translated_textareas[key].parent().hasClass( 'trp-unsaved-changes' ) ){
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



/* handle gettext texts */
var gettext_dictionaries = null;
jQuery(function(){
    /* initial load and populate the dropdown with gettext strings */
    jQuery( "#trp-preview-iframe" ).load(function(){
        /* get the gettext texts ids from the page and pass them to a ajax call to construct the dictonaries */
        var gettext_strings = jQuery( '#trp-preview-iframe').contents().find( '[data-trpgettextoriginal]' );
        gettext_string_ids = [];
        gettext_strings.each( function(){
            gettext_string_ids.push( jQuery(this).attr('data-trpgettextoriginal'));
        });

        jQuery.ajax({
            url: trp_ajax_url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_gettext_get_translations',
                language: trp_language,
                gettext_string_ids: JSON.stringify( gettext_string_ids )
            },
            success: function (response) {
                gettext_dictionaries = response;
                trp_lister = new TRP_Lister( gettext_dictionaries[trp_language] );
                trp_lister.add_gettext_strings();
                jQuery( '#trp-string-saved-ajax-loader' ).css('display', 'none');
            },
            error: function(errorThrown){
                console.log( 'TranslatePress AJAX Request Error' );
                jQuery( '#trp-string-saved-ajax-loader' ).css('display', 'none');
            }

        });

        /* handle clicking the edit icon on gettext strings */
        trp_lister = new TRP_Lister( [] );
        jQuery(jQuery( '#trp-preview-iframe').contents()).on( 'mouseenter', '[data-trpgettextoriginal]', function(){
            if( gettext_dictionaries == null )
                return;//the strings haven't been loaded so don't do nothing yet

            gettext_string = this;

            if ( ! trpEditor.edit_translation_button ){
                trpEditor.edit_translation_button = jQuery( '<span class="trp-edit-translation"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M13.89 3.39l2.71 2.72c.46.46.42 1.24.03 1.64l-8.01 8.02-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.03c.39-.39 1.22-.39 1.68.07zm-2.73 2.79l-5.59 5.61 1.11 1.11 5.54-5.65zm-2.97 8.23l5.58-5.6-1.07-1.08-5.59 5.6z"></path></svg></span>' );
            }

            trpEditor.maybe_overflow_fix(trpEditor.edit_translation_button);

            if ( jQuery(this).attr( 'type' ) == 'submit' || jQuery(this).attr( 'type' ) == 'button' || jQuery(this).attr('type') == 'search' ) {
                if( jQuery(this).parent('trp-wrap').length == 0 )
                    jQuery(this).wrap('<trp-wrap class="trpgettext-wrap"></trp-wrap>');
                jQuery(this).parent().prepend(trpEditor.edit_translation_button);
            }
            else {
                jQuery(this).prepend(trpEditor.edit_translation_button);
            }
            trpEditor.make_sure_pencil_icon_is_inside_view ( jQuery(this)[0] );
            trpEditor.edit_translation_button.off( 'click' );
            trpEditor.edit_translation_button.on( 'click', function(e){
                e.preventDefault();
                e.stopPropagation();
                trp_lister.set_texts_select_to_gettext( jQuery(gettext_string) );
            });
            jQuery( this ).addClass( 'trp-highlight' );

        });

        jQuery(jQuery( '#trp-preview-iframe').contents()).on( 'mouseleave', '[data-trpgettextoriginal]', function(){
            jQuery( this ).removeClass( 'trp-highlight' );
        });



    });

    /* handle saving gettext strings */
    jQuery( '#trp-editor' ).on( 'click', '#trp-save-gettext', function(){
        strings_to_save = {};
        modified = false;
        original = jQuery('#trp-gettext-original-textarea').val();
        if ( original != "" ) {

            jQuery( '#trp-editor textarea[data-trp-translate-id]' ).each( function(){
                id = jQuery(this).attr('data-trp-translate-id');
                language = jQuery(this).attr('data-trp-language-code');
                if( trp_language == language ){
                    gettext_id_in_dom = id;
                }
                translated = jQuery(this).val();
                jQuery.each(gettext_dictionaries[language], function(i, string){
                    if( string.id == id ){
                        if ( string.translated != translated ) {
                            modified = true;
                            gettext_dictionaries[language][i].translated = translated;
                            var status = 2;
                            if (translated.trim() == '') {
                                status = 0;
                            }
                            if (typeof strings_to_save[language] == 'undefined') {
                                strings_to_save[language] = [];
                            }

                            if( !domain )
                                domain = 'default';

                            strings_to_save[language].push({id: id, original: original, translated: translated, domain: domain, status: status});
                        }
                    }
                });
            });
        }

        if ( modified ){
            trpEditor.saving_translation_ui();
            jQuery.ajax({
                url: trp_ajax_url,
                type: 'post',
                dataType: 'json',
                data: {
                    action: 'trp_gettext_save_translations',
                    gettext_strings: JSON.stringify( strings_to_save )
                },
                success: function (response) {
                    if(gettext_id_in_dom) {
                        jQuery.get(document.getElementById("trp-preview-iframe").contentWindow.location.href, function (response) {
                            replacement = jQuery(response).find('[data-trpgettextoriginal="' + gettext_id_in_dom + '"]').first();
                            if( replacement.length != 0 )
                                jQuery('#trp-preview-iframe').contents().find('[data-trpgettextoriginal="' + gettext_id_in_dom + '"]').replaceWith(replacement);
                            trpEditor.saved_translation_ui();
                        });
                    }
                    else
                        trpEditor.saved_translation_ui();
                },
                error: function(errorThrown){
                    console.log( 'TranslatePress AJAX Request Error' );
                }
            });
        }else{
            trpEditor.saved_translation_ui();
        }
    });

});

jQuery( function(){
    trpEditor.jquery_string_selector.on('trpSelectorNotChanged', function(){
        if( !jQuery('#trp-preview').hasClass('trp-still-loading-strings') ) {
            jQuery('#trp-preview').addClass('trp-still-loading-strings');
            setTimeout(function () {
                jQuery('#trp-preview').removeClass('trp-still-loading-strings')
            }, 2000 );
        }
    });

});


