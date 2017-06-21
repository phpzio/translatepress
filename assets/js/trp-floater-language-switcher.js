function trp_floater_change_language( languageCode ) {
    window.location.replace( document.querySelector( 'link[hreflang="' + languageCode + '"]' ).href );
}