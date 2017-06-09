
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
            // todo check if form works
            jQuery( this ).append( jQuery('<input></input>').attr({ type: 'hidden', value: 'preview', name: 'trp-edit-translation' }) );
            //this.action = update_query_string('trp-edit-translation', 'preview', this.action);
        });

        if ( QueryString.hasOwnProperty( 'lang' )/* && QueryString.lang != trp_language */){
            console.log( "refresh " + QueryString.lang );
        }else{
            console.log( "no refresh " + QueryString.lang );
        }

    };

    var QueryString = function () {
        // This function is anonymous, is executed immediately and
        // the return value is assigned to QueryString!
        var query_string = {};

        var query = window.location.search.substring(1);

        /*console.log(window.location);
        console.log(document.getElementById("trp-preview-iframe").contentWindow.location.href );*/

        var vars = query.split("&");
        for (var i=0;i<vars.length;i++) {
            var pair = vars[i].split("=");
            // If first entry with this name
            if (typeof query_string[pair[0]] === "undefined") {
                query_string[pair[0]] = decodeURIComponent(pair[1]);
                // If second entry with this name
            } else if (typeof query_string[pair[0]] === "string") {
                var arr = [ query_string[pair[0]],decodeURIComponent(pair[1]) ];
                query_string[pair[0]] = arr;
                // If third or later entry with this name
            } else {
                query_string[pair[0]].push(decodeURIComponent(pair[1]));
            }
        }
        return query_string;
    }();

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
