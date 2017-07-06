

function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor == 'undefined' ) {
        select.form.submit();
    }
}
