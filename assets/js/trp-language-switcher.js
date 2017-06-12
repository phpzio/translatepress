

function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor !== 'undefined' ) {
        parent.trpEditor.change_language( select );
    }else{
        select.form.submit();
    }
}
