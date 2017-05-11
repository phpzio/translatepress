
// observe, get, replace.
function TRP_Translator(){

    var _this = this;
    var observer = null;
    var active = true;
    var ajax_url = trp_data.trp_ajax_url;
    var wp_ajax_url = trp_data.trp_wp_ajax_url;


    this.ajax_get_translation = function( strings_to_query, url ){
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
            success: function (response) {
                if ( response === 'error' ) {
                    _this.ajax_get_translation( strings_to_query, wp_ajax_url );
                }else{
                    _this.update_strings(response, strings_to_query);
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
            for( var i in response[language] ){
                var response_string = response[language][i];
                for ( var j in strings_to_query ) {
                    var queried_string = strings_to_query[j];
                    if ( response_string.original.trim() == queried_string.original.trim() && response_string.translated != '' ) {
                        _this.pause_observer();
                        //todo str replace
                        var initial_value = queried_string.node.innerText;
                        var text_to_set = initial_value.replace(initial_value.trim(), response_string.translated);
                        queried_string.node.innerText = text_to_set;
                        _this.unpause_observer();
                    }
                }
            }
        }
    };

    this.initialize = function() {

        language = trp_data.trp_language;
        // create an observer instance
        observer = new MutationObserver(function (mutations) {
            if ( active ) {
                var strings = [];
                mutations.forEach( function (mutation) {
                    for (var i = 0; i < mutation.addedNodes.length; i++) {
                        //console.log('  "' + mutation.addedNodes[i].textContent + '" added');
                        //console.log( mutation.addedNodes[i] );
                        strings.push( { node: mutation.addedNodes[i], original: mutation.addedNodes[i].textContent } );
                        //mutation.addedNodes[i].textContent = 'altfel';

                        /*updateTextContent('altceva');*/
                    }


                    /*console.log(mutation);*/
                });
                if ( strings.length > 0 ) {
                    _this.ajax_get_translation( strings, ajax_url );
                }

                // store i, textContent
                // send array
                // on success change.

            }
        });

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
    trpTranslator = new TRP_Translator();
    jQuery( ".site-branding" ).append("<h1>new word</h1>");
    jQuery( ".site-branding" ).append("<h1>other new word</h1>");
});

