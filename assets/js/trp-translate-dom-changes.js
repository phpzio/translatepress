
// observe, get, replace.
function TRP_Translator(){

    var _this = this;
    var observer = null;
    var active = true;
    var ajax_url = trp_data.trp_ajax_url;
    var wp_ajax_url = trp_data.trp_wp_ajax_url;
    var language_to_query;

    this.ajax_get_translation = function( strings_to_query, url ) {
        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_get_translations',
                async: false,
                language: language_to_query,
                original_language: original_language,
                strings: JSON.stringify( strings_to_query )
            },
            success: function( response ) {
                if ( response === 'error' ) {
                    _this.ajax_get_translation( strings_to_query, wp_ajax_url );
                }else{
                    _this.update_strings( response, strings_to_query );
                }
            },
            error: function( errorThrown ){
                if ( url == ajax_url ){
                    _this.ajax_get_translation( strings_to_query, wp_ajax_url );
                }else{
                    _this.update_strings( null, strings_to_query );
                    console.log( 'TranslatePress AJAX Request Error' );
                }
            }
        });
    };

    this.update_strings = function( response, strings_to_query ) {
        if ( response != null && response[language_to_query] != null ){
            for ( var j in strings_to_query ) {
                var queried_string = strings_to_query[j];
                var translation_found = false;
                var initial_value = queried_string.original;
                for( var i in response[language_to_query] ) {
                    var response_string = response[language_to_query][i];
                    if (response_string.original.trim() == queried_string.original.trim()) {
                        response[language_to_query][i].jquery_object = jQuery( queried_string.node );
                        if (response_string.translated != '' && language_to_query == current_language ) {
                            var text_to_set = initial_value.replace(initial_value.trim(), response_string.translated);
                            _this.pause_observer();
                            queried_string.node.innerText = text_to_set;
                            _this.unpause_observer();
                            translation_found = true;
                            break;
                        }
                    }
                }

                if ( ! translation_found ){
                    //console.log(queried_string.node);
                    //console.log(initial_value);
                    queried_string.node.innerText = initial_value;
                }

                if ( typeof parent.trpEditor !== 'undefined' ) {
                    parent.trpEditor.populate_strings( response );
                }
            }
        }else{
            for ( var j in strings_to_query ) {
                strings_to_query[j].node.innerText = strings_to_query[j].original;
            }
        }
    };

    this.detect_new_strings = function( mutations ){
        if ( active ) {
            var strings = [];
            mutations.forEach( function (mutation) {
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    if ( mutation.addedNodes[i].innerText && mutation.addedNodes[i].innerText.trim() != '' ) {
                        var node = jQuery( mutation.addedNodes[i] );
                        var attribute = node.attr( 'data-no-translation' );
                        if ( (typeof attribute !== typeof undefined && attribute !== false) || node.parents( '[data-no-translation]').length > 0 ){
                            continue;
                        }

                        strings.push({node: mutation.addedNodes[i], original: mutation.addedNodes[i].innerText});
                        //mutation.addedNodes[i] = mutation.addedNodes[i];
                        //var text = jQuery(mutation.addedNodes[i]).text();
                        //console.log( jQuery(mutation.addedNodes[i]));
                       // console.log( strings[(strings.length -1) ].original );
                        //console.log(mutation.addedNodes[i].innerText);
                        mutation.addedNodes[i].innerText = '';
                    }
                }
            });
            if ( strings.length > 0 ) {
                _this.ajax_get_translation( strings, ajax_url );
            }
        }
    };

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
            characterData: true,
            subtree: true
        };

        observer.observe( document.body , config );
    };

    this.disconnect = function(){
        observer.disconnect();
    };

    this.unpause_observer = function(){
        active = true;
    };

    this.pause_observer = function(){
        active = false;
    };

    _this.initialize();
}

var trpTranslator;
var current_language;
var original_language;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {
    trpTranslator = new TRP_Translator();
    //TODO remove this when finished
    jQuery( ".site-branding" ).append("<div data-no-tran2slation><h1>new word</h1></div>");
    jQuery( ".site-branding" ).append("<h1>new word2</h1>");

});

