/**
 * Change language using language select floater.
 *
 * @param string language_code   language code
 */
function trp_floater_change_language( language_code ) {
    if( typeof parent.trpEditor == 'undefined' ) {
        window.location.replace(document.querySelector('link[hreflang="' + language_code + '"]').href);
    }
}