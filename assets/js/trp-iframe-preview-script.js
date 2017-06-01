
function TRP_Iframe_Preview(){
    var _this = this;
    this.initialize = function() {
        jQuery('a').each(function () { 
            if (this.action != '') {
                if ( isLinkPreviewable ( this ) ) {
                    this.href = update_query_string('trp-edit-translation', 'preview', this.href);
                }else {
                    jQuery( this ).on( 'click',
                        function(event) {
                            event.preventDefault();
                        }
                    );
                }
            }
        });

        jQuery('form').each(function () {
            // todo form
            jQuery( this ).append( jQuery('<input></input>').attr({ type: 'hidden', value: 'preview', name: 'trp-edit-translation' }) );
            //this.action = update_query_string('trp-edit-translation', 'preview', this.action);
        });
    };

    function update_query_string(key, value, url) {
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
    }

    function isLinkPreviewable( element ) {
        if ( ! jQuery( element ).hasClass( 'trp-unpreviewable' ) ){
            return true;
        }
    }

    _this.initialize();
}

var trp_preview_iframe;
jQuery( function(){
    //TODO remove this when finished
    //jQuery( ".site-branding" ).append("<h1>new word</h1>");

    trp_preview_iframe = new TRP_Iframe_Preview();
});
