function trp_floater_change_language( languageCode ) {
    if ( typeof parent.trpEditor == 'undefined' ) {
        window.location.replace(document.querySelector('link[hreflang="' + languageCode + '"]').href);
    }
}