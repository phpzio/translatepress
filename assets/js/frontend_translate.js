jQuery( function(){
    jQuery("body *").contents().filter(function(){
        if( this.nodeType === 3 && /\S/.test(this.nodeValue) ){
            return this
        }
    }).wrap("<wpt-translate></wpt-translate>");

    jQuery( 'body' ).on( 'mouseenter', 'wpt-translate', function(){
        jQuery( this ).css('border', '1px solid green');
    });

    jQuery( 'body' ).on( 'mouseleave', 'wpt-translate', function(){
        jQuery( this ).css('border', 'none');
    });

    jQuery( 'body' ).on( 'click', 'wpt-translate', function(){
        alert( jQuery(this).text() );
    });
});