function trp_change_language( select ){
    select.form.action = document.querySelector('link[hreflang="' + select.value + '"]').href;
    if ( typeof parent.trpEditor == 'undefined' ) {
        select.form.submit();
    }
}

jQuery( document ).ready( function( ) {
    /*try {
        jQuery("body select.trp-language-switcher-select").msDropDown();
    } catch(e) {
        alert(e.message);
    }*/

    //jQuery(".trp-language-switcher-select").selectmenu();

    /*jQuery(function () {
        jQuery.widget("custom.TFOiconSelectImg", jQuery.ui.selectmenu, {
            _renderItem: function (ul, item) {
                var li = jQuery("<li>", { html: item.element.html() });
                var attr = item.element.attr("data-style");
                if (typeof attr !== typeof undefined && attr !== false) {
                    jQuery("<span>", {
                        style: item.element.attr("data-style"),
                        "class": "ui-icon TFOOptlstFiltreImg"
                    }).appendTo(li);
                }
                return li.appendTo(ul);
            }
        });

        //jQuery(".trp-language-switcher-select")
        jQuery("select")
            .TFOiconSelectImg({
                create: function (event, ui) {
                    var widget = jQuery(this).TFOiconSelectImg("widget");
                    $span = jQuery('<span id="' + this.id + 'ImgSelected" class="TFOSizeImgSelected"> ').html("<img src='http://icons.iconarchive.com/icons/custom-icon-design/all-country-flag/256/Hungary-Flag-icon.png'>").appendTo(widget);
                    $span.attr("style", jQuery(this).children(":first").data("style"));
                },
                change: function (event, ui) {
                    jQuery("#" + this.id + 'ImgSelected').attr("style", ui.item.element.data("style"));
                }
            }).TFOiconSelectImg("menuWidget").addClass("ui-menu-icons customicons");
    });*/

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
            })
                .prependTo( wrapper );

            return li.append( wrapper ).appendTo( ul );
        }
    });

    jQuery( ".trp-language-switcher-select" )
        .iconselectmenu( {
            change: function() {
                console.log( jQuery( this ).val() );
            },
            icons: {
                button: "custom-icon"
            }
        } )
        .iconselectmenu( "menuWidget")
        .addClass( "ui-menu-icons avatar" );


} );