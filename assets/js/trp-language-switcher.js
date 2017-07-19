function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor == 'undefined' ) {
        select.form.submit();
    }
}

jQuery( document ).ready( function( ) {

    jQuery.widget( 'trp.iconselectmenu', jQuery.ui.selectmenu, {
        _renderItem: function( ul, item ) {
            var li = jQuery( '<li class="trp-ls-li" data-no-translation>' );
            var wrapper = jQuery( '<div style="display: inline-block;">' );

            if( item.disabled ) {
                li.addClass( 'ui-state-disabled' );
            }

            jQuery( '<span>', {
                text: jQuery.trim( item.label )
            } ).appendTo( wrapper );

            jQuery( '<span>', {
                style: item.element.attr( 'data-style' ),
                'class': 'ui-icon ' + item.element.attr( 'data-class' )
            } ).prependTo( wrapper );

            return li.append( wrapper ).appendTo( ul );
        }
    } );


    jQuery( '.trp-language-switcher-select' ).each( function() {
        jQuery( this )
            .iconselectmenu( {
                change: function( event, ui ) {
                    if( typeof parent.trpEditor == 'undefined' ) {
                        window.location.replace( document.querySelector( 'link[hreflang="' + ui.item.value + '"]' ).href );
                    }
                },
                select: function( event, ui ) {
                    jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( '.trp-current-language-icon' )
                        .css( 'background-image', 'url(' + jQuery( this ).find( 'option[value="' + ui.item.value + '"]' ).data( 'flag-url' ) + ')' );
                },
                icons: {
                    button: 'trp-current-language-icon'
                }
            } ).iconselectmenu( 'menuWidget' ).addClass( 'ui-menu-icons trp-flag-icon' );
    } );

    jQuery( '<span>', {
        'class': 'dashicons dashicons-arrow-down'
    } ).appendTo( jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ) );

    jQuery( '.ui-state-default .ui-icon.trp-current-language-icon' ).each( function() {
        jQuery( this ).css( 'background-image', 'url(' + jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( 'select.trp-language-switcher-select' ).find( ':selected' ).data( 'flag-url' ) + ')' );
    } );

    jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ).click( function() {
        var id = jQuery( this ).attr( 'id' );
        var width = jQuery( this ).width();

        jQuery( '.ui-menu.trp-flag-icon' ).each( function() {
            if( jQuery( this ).attr( 'aria-labelledby' ) == id ) {
                jQuery( this ).width( width );
            }
        } );
    } );

} );