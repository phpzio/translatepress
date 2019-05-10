
/**
 * Script to replace dynamically added strings with their translation.
 */
function TRP_Translator(){

    var _this = this;
    var observer = null;
    var active = true;
    var ajax_url = trp_data.trp_ajax_url;
    var wp_ajax_url = trp_data.trp_wp_ajax_url;
    var language_to_query;
    var except_characters = " \t\n\r  �.,/`~!@#$€£%^&*():;-_=+[]{}\\|?/<>1234567890'";
    var trim_characters = " \t\n\r  �\x0A\x0B" + "\302" + "\240";

    /**
     * Ajax request to get translations for strings
     */
    this.ajax_get_translation = function( strings_to_query, url ) {
        var all_languages_true_false = 'false';
        if ( typeof parent.trpEditor !== 'undefined' ) {
            all_languages_true_false = 'true';
        }
        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_get_translations',
                all_languages: all_languages_true_false,
                security: trp_localized_text['gettranslationsnonce'],
                language: language_to_query,
                original_language: original_language,
                strings: JSON.stringify( strings_to_query )
            },
            success: function( response ) {
                if ( response === 'error' ) {
                    _this.ajax_get_translation( strings_to_query, wp_ajax_url );
                    console.log( 'Notice: TranslatePress trp-ajax request uses fall back to admin ajax.' );
                }else{
                    _this.update_strings( response, strings_to_query );
                    //window.parent.jQuery('#trp-preview-iframe').trigger('load');
                }
            },
            error: function( errorThrown ){
                if ( url == ajax_url ){
                    _this.ajax_get_translation( strings_to_query, wp_ajax_url );
                    console.log( 'Notice: TranslatePress trp-ajax request uses fall back to admin ajax.' );
                }else{
                    _this.update_strings( null, strings_to_query );
                    console.log( 'TranslatePress AJAX Request Error' );
                }
            }
        });
    };

    /**
     * Return given text converted to html.
     *
     * Useful for decoding special characters into displayable form.
     *
     * @param html
     * @returns {*}
     */
    this.decode_html = function( html ) {
        var txt = document.createElement( "textarea" );
        txt.innerHTML = html;
        return txt.value;
    };

    /**
     * Replace original strings with translations if found.
     */
    this.update_strings = function( response, strings_to_query ) {
        if ( response != null && response[language_to_query] != null ){
            var dictionary = {};
            for (var j = 0 ; j < strings_to_query.length; j++){
                var queried_string = strings_to_query[j];
                var translation_found = false;
                var initial_value = queried_string.original;
                for( var i in response[language_to_query] ) {
                    var response_string = response[language_to_query][i];
                    if (response_string.original.trim() == queried_string.original.trim()) {
                        // We use j instead of i index because the strings_to_query can contain duplicates and response cannot. We need duplicates to refer to different jQuery objects where the same string appears in different places on the page.
                        dictionary[j] = {};
                        dictionary[j].id = response[language_to_query][i].id;
                        dictionary[j].original = response[language_to_query][i].original;
                        dictionary[j].translated = response[language_to_query][i].translated;
                        dictionary[j].status = response[language_to_query][i].status;
                        dictionary[j].jquery_object = jQuery( queried_string.node ).parent( 'translate-press' );
                        if ( typeof parent.trpEditor !== 'undefined' ) {
                            dictionary[j].jquery_object.attr('data-trp-translate-id', response[language_to_query][i].id);
                            dictionary[j].jquery_object.attr('data-trp-node-type', 'Dynamic Added Strings');
                        }

                        if (response_string.translated != '' && language_to_query == current_language ) {
                            var text_to_set = initial_value.replace(initial_value.trim(), response_string.translated);
                            _this.pause_observer();
                            queried_string.node.textContent = _this.decode_html(text_to_set);
                            _this.unpause_observer();
                            translation_found = true;
                            break;
                        }
                    }
                }

                if ( ! translation_found ){
                    _this.pause_observer();
                    queried_string.node.textContent = initial_value;
                    _this.unpause_observer();
                }
            }
            // this should always be outside the for loop
            if ( typeof parent.trpEditor !== 'undefined' ) {
                response[language_to_query] = dictionary;
                parent.trpEditor.populate_strings( response );
                if ( parent.trpEditor.trp_lister != null ) {
                    parent.trpEditor.trp_lister.reload_list();
                }
            }
        }else{
            for (var j = 0 ; j < strings_to_query.length; j++){
                strings_to_query[j].node.textContent = strings_to_query[j].original;
            }
        }
    };

    /**
     * Detect and remember added strings.
     */
    this.detect_new_strings = function( mutations ){
        if ( active ) {
            var strings = [];
            mutations.forEach( function (mutation) {
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    if ( mutation.addedNodes[i].textContent && _this.trim( mutation.addedNodes[i].textContent.trim(), except_characters ) != '' ) {
                        var node = jQuery( mutation.addedNodes[i] );

                        /* if it is an anchor add the trp-edit-translation=preview parameter to it */
                        if ( typeof parent.trpEditor !== 'undefined' ) {
                            jQuery(mutation.addedNodes[i]).find('a').context.href = _this.update_query_string('trp-edit-translation', 'preview', jQuery(mutation.addedNodes[i]).find('a').context.href);
                        }

                        if ( skip_string(node) ){
                            continue;
                        }

                        var direct_string = get_string_from_node( mutation.addedNodes[i] );
                        if ( direct_string ) {
                            if ( _this.trim( direct_string.textContent, except_characters ) != '' ) {
                                strings.push({
                                    node: mutation.addedNodes[i],
                                    original: _this.trim(direct_string.textContent, trim_characters)
                                });

                                direct_string.textContent = '';
                                if (typeof parent.trpEditor !== 'undefined') {
                                    jQuery(mutation.addedNodes[i]).wrap('<translate-press></translate-press>');
                                }
                            }
                        }else{
                            var all_nodes = jQuery( mutation.addedNodes[i]).find( '*').addBack();
                            var all_strings = all_nodes.contents().filter(function(){
                                if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
                                        if ( ! skip_string(this) ){
                                                return this;
                                            }
                                    }});
                            if ( typeof parent.trpEditor !== 'undefined' ) {
                                all_strings.wrap('<translate-press></translate-press>');
                            }
                            var all_strings_length = all_strings.length;
                            for (var j = 0; j < all_strings_length; j++ ) {
                                if ( _this.trim( all_strings[j].textContent, except_characters ) != '' ) {
                                    strings.push({node: all_strings[j], original: all_strings[j].textContent });
                                    if ( trp_localized_text ['showdynamiccontentbeforetranslation'] == false ) {
                                        all_strings[j].textContent = '';
                                    }
                                }
                            }
                        }
                    }
                }
            });
            if ( strings.length > 0 ) {
                _this.ajax_get_translation( strings, ajax_url );
            }
        }
    };

    function skip_string(node){
        // skip nodes containing these attributes
        var selectors = trp_data.trp_skip_selectors;
        for (var i = 0; i < selectors.length ; i++ ){
            if ( jQuery(node).closest( selectors[ i ] ).length > 0 ){
                return true;
            }
        }
        return false;
    }

    function get_string_from_node( node ){
        if( node.nodeType === 3 && /\S/.test(node.nodeValue) ){
            if ( ! skip_string(node) ){
                return node;
            }
        }
    }

    //function that cleans the gettext wrappers
    this.cleanup_gettext_wrapper = function(){
        jQuery('trp-gettext').contents().unwrap();
    };

    /**
     * Update url with query string.
     *
     */
    this.update_query_string = function(key, value, url) {
        if (!url) return url;
        if ( url.startsWith('#') ){
            return url;
        }
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
     * Initialize and configure observer.
     */
    this.initialize = function() {

        current_language = trp_data.trp_current_language;
        original_language = trp_data.trp_original_language;
        language_to_query = trp_data.trp_language_to_query;

        // create an observer instance
        observer = new MutationObserver( _this.detect_new_strings );
        // configuration of the observer:
        var config = {
            attributes: true,
            childList: true,
            characterData: false,//this could be CDATA so I set it to false in v 1.4.5
            subtree: true
        };


        observer.observe( document.documentElement , config );

        jQuery( document ).ajaxComplete(function( event, request, settings ) {
            if( typeof window.parent.jQuery !== "undefined" && window.parent.jQuery('#trp-preview-iframe').length != 0 ) {
                var settingsdata = "" + settings.data;
                if( typeof settings.data == 'undefined' || jQuery.isEmptyObject( settings.data ) || settingsdata.indexOf('action=trp_') === -1 ) {
                    window.parent.jQuery('#trp-preview-iframe').trigger('trp_page_loaded');
                    jQuery( window ).trigger('trp_page_loaded');
                }
            }
        });

        //try a final attempt at cleaning the gettext wrappers
        _this.cleanup_gettext_wrapper();
    };

    /**
     * Stop observing new strings.
     */
    this.disconnect = function(){
        observer.disconnect();
    };

    /**
     * Resume observing new strings added.
     */
    this.unpause_observer = function(){
        active = true;
    };

    /**
     * Pause observing new string added.
     */
    this.pause_observer = function(){
        active = false;
    };

    this.trim = function (str, charlist) {
        //  discuss at: http://locutus.io/php/trim/
        // original by: Kevin van Zonneveld (http://kvz.io)
        // improved by: mdsjack (http://www.mdsjack.bo.it)
        // improved by: Alexander Ermolaev (http://snippets.dzone.com/user/AlexanderErmolaev)
        // improved by: Kevin van Zonneveld (http://kvz.io)
        // improved by: Steven Levithan (http://blog.stevenlevithan.com)
        // improved by: Jack
        //    input by: Erkekjetter
        //    input by: DxGx
        // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
        //   example 1: trim('    Kevin van Zonneveld    ')
        //   returns 1: 'Kevin van Zonneveld'
        //   example 2: trim('Hello World', 'Hdle')
        //   returns 2: 'o Wor'
        //   example 3: trim(16, 1)
        //   returns 3: '6'
        var whitespace = [
            ' ',
            '\n',
            '\r',
            '\t',
            '\f',
            '\x0b',
            '\xa0',
            '\u2000',
            '\u2001',
            '\u2002',
            '\u2003',
            '\u2004',
            '\u2005',
            '\u2006',
            '\u2007',
            '\u2008',
            '\u2009',
            '\u200a',
            '\u200b',
            '\u2028',
            '\u2029',
            '\u3000'
        ].join('');
        var l = 0;
        var i = 0;
        str += '';
        if (charlist) {
            whitespace += (charlist + '').replace(/([[\]().?/*{}+$^:])/g, '$1');
        }
        l = str.length;
        for (i = 0; i < l; i++) {
            if (whitespace.indexOf(str.charAt(i)) === -1) {
                str = str.substring(i);
                break;
            }
        }
        l = str.length;
        for (i = l - 1; i >= 0; i--) {
            if (whitespace.indexOf(str.charAt(i)) === -1) {
                str = str.substring(0, i + 1);
                break;
            }
        }
        return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
    };

    _this.initialize();
}

var trpTranslator;
var current_language;
var original_language;

function trp_get_IE_version() {
    var sAgent = window.navigator.userAgent;
    var Idx = sAgent.indexOf("MSIE");

    // If IE, return version number.
    if (Idx > 0)
        return parseInt(sAgent.substring(Idx+ 5, sAgent.indexOf(".", Idx)));

    // If IE 11 then look for Updated user agent string.
    else if (!!navigator.userAgent.match(/Trident\/7\./))
        return 11;
    else
        return 0; //It is not IE
}

function trp_allow_detect_dom_changes_to_run(){
    var IE_version = trp_get_IE_version();
    if ( IE_version != 0 && IE_version <= 11 ){
        return false;
    }
    return true;
}


// Initialize the Translate Press Editor when the script loads
if ( trp_allow_detect_dom_changes_to_run() ) {
    trpTranslator = new TRP_Translator();
}


