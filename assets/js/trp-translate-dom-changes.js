
// observe, get, replace.
function TRP_Translator(){

    var _this = this;
    var observer = null;
    var active = true;
    var ajax_url = trp_data.trp_ajax_url;
    var wp_ajax_url = trp_data.trp_wp_ajax_url;


    this.ajax_get_translation = function( strings_to_query, url ) {
        jQuery.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'trp_get_translations',
                async: false,
                //todo output trp_language
                language: language,
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
                    console.log( 'Translate Press AJAX Request Error' );
                }
            }
        });
    };

    this.update_strings = function( response, strings_to_query ) {
        console.log( response );
        if ( response != null && response[language] != null ){
            for ( var j in strings_to_query ) {
                var queried_string = strings_to_query[j];
                var translation_found = false;
                var initial_value = queried_string.original;
                for( var i in response[language] ) {
                    var response_string = response[language][i];
                    if (response_string.original.trim() == queried_string.original.trim()) {
                        response[language][i].jquery_object = queried_string.node;
                        if (response_string.translated != '') {
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
                    queried_string.node.innerText = initial_value;
                }

                if ( typeof trpEditor !== 'undefined' ) {
                    trpEditor.populate_strings( response );
                    console.log("SUccess");
                }else{
                    console.log('TRP EDITOR IS NOT DEFINED')
                }

            }
        }
    };

    this.detect_new_strings = function( mutations ){
        if ( active ) {
            var strings = [];
            mutations.forEach( function (mutation) {
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    if ( mutation.addedNodes[i].innerText && mutation.addedNodes[i].innerText.trim() != '' ) {
                        console.log(mutation.addedNodes[i].innerText);
                        strings.push({node: mutation.addedNodes[i], original: mutation.addedNodes[i].innerText});
                    }
                    mutation.addedNodes[i].innerText = '';
                }
            });
            if ( strings.length > 0 ) {
                _this.ajax_get_translation( strings, ajax_url );
            }
        }
    };

    this.initialize = function() {

        language = trp_data.trp_language;
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
var language;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {
    // todo script should execute before any others
    trpTranslator = new TRP_Translator();
    jQuery( ".site-branding" ).append("<h1>new word</h1>");
    jQuery( ".site-branding" ).append("<h1>other new word</h1>");
    jQuery( ".site-branding" ).append("<h1>no translation</h1>");
});

