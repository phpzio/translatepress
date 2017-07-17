function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor == 'undefined' ) {
        select.form.submit();
    }
}

jQuery( document ).ready( function( ) {
    jQuery.widget( "custom.iconselectmenu", jQuery.ui.selectmenu, {
        _renderItem: function( ul, item ) {
            var li = jQuery( "<li class='trp-ls-li'>" ),
                wrapper = jQuery( "<div style='display: inline-block;'>" );

            if( item.disabled ) {
                li.addClass( "ui-state-disabled" );
            }

            jQuery( "<span>", { text: jQuery.trim( item.label ) } ).appendTo( wrapper );

            jQuery( "<span>", {
                style: item.element.attr( "data-style" ),
                "class": "ui-icon " + item.element.attr( "data-class" )
            } ).prependTo( wrapper );

            return li.append( wrapper ).appendTo( ul );
        }
    });

    jQuery( '.trp-language-switcher-select' ).each( function() {
        jQuery( this )
            .iconselectmenu( {
                change: function() {
                    /*jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( '.trp-current-language-icon' )
                        .css( 'background-image', 'url(' + jQuery( this ).find( 'option[value="' + ui.item.value + '"]' ).data( 'flag-url' ) + ')' );*/
                },
                select: function( event, ui ) {
                    jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( '.trp-current-language-icon' )
                        .css( 'background-image', 'url(' + jQuery( this ).find( 'option[value="' + ui.item.value + '"]' ).data( 'flag-url' ) + ')' );
                    if( typeof parent.trpEditor == 'undefined' ) {
                        window.location.replace( document.querySelector( 'link[hreflang="' + ui.item.value + '"]' ).href );
                    }
                },
                icons: {
                    button: "trp-current-language-icon"
                }
            } ).iconselectmenu( "menuWidget" ).addClass( "ui-menu-icons trp-avatar" );


    } );

    /*jQuery( ".trp-language-switcher-select" )
        .iconselectmenu( {
            change: function() {
                console.log( jQuery( this ).val() );
            },
            icons: {
                button: "custom-icon"
            }
        } )
        .iconselectmenu( "menuWidget")
        .addClass( "ui-menu-icons trp-avatar" );*/

    jQuery( '<span>', {
        "class": "dashicons dashicons-arrow-down"
    } ).appendTo( jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ) );

    jQuery( '.ui-state-default .ui-icon.trp-current-language-icon' ).each( function() {
        jQuery( this ).css( 'background-image', 'url( http://local.wordpress.dev/wp-content/plugins/translate-press/assets/images/flags/en_US.png )' );
    } );

} );