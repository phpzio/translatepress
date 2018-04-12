/**
 * Change language function for shortcode language switcher.
 */

function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor == 'undefined' ) {
        select.form.submit();
    }
}

jQuery( document ).ready( function( ) {

    sessionStorage.removeItem('wc_fragments');

    // Run this code only if flags are enabled in shortcode language switcher
    if( trp_language_switcher_data.shortcode_ls_flags ) {
        jQuery.widget( 'trp.iconselectmenu', jQuery.ui.selectmenu, {
            _renderItem : function( ul, item ) {
                // Check if Translation Editor and add data-trp-unpreviewable
                var data_trp_unpreviewable = '';
                if( typeof parent.trpEditor != 'undefined' ) {
                    data_trp_unpreviewable = 'data-trp-unpreviewable="trp-unpreviewable"';
                }

                // Get language title attr
                var title = jQuery( jQuery( item )[0]['element'][0] ).attr( 'title' );

                var li = jQuery( '<li class="trp-ls-li" data-no-translation ' + data_trp_unpreviewable + '>' );
                var wrapper = jQuery( '<div class="trp-ls-div" style="display: inline-block;" title="' + jQuery.trim( title ) + '">' );

                if( item.disabled ) {
                    li.addClass( 'ui-state-disabled' );
                }

                if( jQuery.trim( item.label ) ) {
                    jQuery( '<span>', {
                        text : jQuery.trim( item.label )
                    } ).appendTo( wrapper );
                }

                jQuery( '<span>', {
                    style : item.element.attr( 'data-style' ),
                    'class' : 'ui-icon ' + item.element.attr( 'data-class' )
                } ).prependTo( wrapper );

                return li.append( wrapper ).appendTo( ul );
            }
        } );


        jQuery( '.trp-language-switcher-select' ).each( function() {
            jQuery( this )
                .iconselectmenu( {
                    create: function ( event, ui ) {
                        // Remove span for language name when empty
                        if( ! jQuery.trim( jQuery( jQuery( event )[0]['target'] ).text() ) ) {
                            jQuery( 'form.trp-language-switcher-form .ui-selectmenu-text' ).remove();
                        }

                        // Add title attr
                        jQuery( '.trp-current-language-icon' ).closest( 'span.ui-selectmenu-button' ).attr(
                            'title', jQuery.trim( jQuery( '.trp-language-switcher-select' ).find( ':selected' ).attr( 'title' ) )
                        );
                    },
                    change: function( event, ui ) {
                        // Change language
                        if( typeof parent.trpEditor == 'undefined' ) {
                            window.location.replace( document.querySelector( 'link[hreflang="' + ui.item.value + '"]' ).href );
                        }
                    },
                    select: function( event, ui ) {
                        // Add the right flag to the selected option
                        jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( '.trp-current-language-icon' )
                            .css( 'background-image', 'url(' + jQuery( this ).find( 'option[value="' + ui.item.value + '"]' ).data( 'flag-url' ) + ')' );
                    },
                    icons: {
                        button : 'trp-current-language-icon'
                    }
                } ).iconselectmenu( 'menuWidget' ).addClass( 'ui-menu-icons trp-ls-options-with-flag-icons' );
        } );

        // Add arrow-down icon to the jQuery UI select
        jQuery( '<span>', {
            'class' : 'dashicons dashicons-arrow-down'
        } ).appendTo( jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ) );

        // Add the right flag to the selected option
        jQuery( '.ui-state-default .ui-icon.trp-current-language-icon' ).each( function() {
            jQuery( this ).css( 'background-image', 'url(' + jQuery( this ).closest( 'form.trp-language-switcher-form' ).find( 'select.trp-language-switcher-select' ).find( ':selected' ).data( 'flag-url' ) + ')' );
        } );

        // Adjust the font size of select options based on the select font size
        jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ).each( function() {
            var id = jQuery( this ).attr( 'id' );
            var font_size = jQuery( this ).css( 'font-size' );

            jQuery( '.ui-menu.trp-ls-options-with-flag-icons' ).each( function() {
                if( jQuery( this ).attr( 'aria-labelledby' ) == id ) {
                    jQuery( this ).css( 'font-size', font_size );
                }
            } );
        } );

        // Check if Translation Editor and add data-trp-unpreviewable
        if( typeof parent.trpEditor != 'undefined' ) {
            jQuery( 'form.trp-language-switcher-form .ui-selectmenu-button' ).each( function() {
                jQuery( this ).attr( 'data-trp-unpreviewable', 'trp-unpreviewable' );
                jQuery( this ).find( 'span' ).attr( 'data-trp-unpreviewable', 'trp-unpreviewable' );
            } );
        }
    }

} );