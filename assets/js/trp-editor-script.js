
function TRP_Editor(){
    var _this = this;
    var preview_iframe;
    var strings = [];


    this.ajax_request = function( strings_to_query ){
        jQuery.ajax({
            url: trp_ajax_url,
            type: 'post',
            dataType: 'jsonp',
            data: {
                action: 'trp_get_translations',
                strings: JSON.stringify( strings_to_query )
            },
            success: function (response) {

            }
        });
    };


    this.initialize = function(){
        preview_iframe = jQuery( '#trp-preview-iframe').contents();
        var all_strings = preview_iframe.find( 'body *' ).contents().filter(function(){
            if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
                return this
            }
        });

        var strings_to_query = [];

        for ( var i = 0; i < all_strings.length; i++) {
            var string = new TRP_String( all_strings[i] );
            strings.push( string );
            strings_to_query.push( string.get_details());
        }

        _this.ajax_request( strings_to_query );

    };

}

function TRP_String( raw_string ){
    var _this = this;
    var TRP_TRANSLATION_ID = 'data-trp-translate-id';
    var id = null;
    var original = null;
    var translated = [];
    var jquery_object = jQuery( raw_string ).parent();

    this.get_details = function(){
        var details = {};
        details['id'] = id;
        details[ 'original' ] = original;
        return details;
    };

    this.initialize = function(){
        var translation_id_attribute = jquery_object.attr( TRP_TRANSLATION_ID );
        if ( translation_id_attribute ){
            id = translation_id_attribute;
            translated[TRP_LANGUAGE] = jquery_object.text();
        }else{
            original = jquery_object.text();
        }
    };

    this.initialize();
}




var trpEditor;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {
    trpEditor = new TRP_Editor();
});


