function TRP_Sortable_Languages() {

    /**
     * Function that initializes select2 on fields
     */

    this.add_language = function(){
        var selected_language = jQuery( '#trp-select-language' );
        var new_language = selected_language.val();
        if ( new_language == "" ){
            return;
        }

        selected_language.val( '' ).trigger( 'change' );

        var new_option = jQuery( '.trp-language' ).first().clone();
        new_option = jQuery( new_option );

        new_option.find( '#trp-hidden-default-language' ).remove();
        new_option.find( '.select2-container' ).remove();
        var select = new_option.find( 'select.trp-translation-language' );
        select.removeAttr( 'disabled' );
        select.val( new_language );
        select.select2();

        var checkbox = new_option.find( 'input.trp-translation-published' );
        checkbox.removeAttr( 'disabled' );
        checkbox.attr( 'checked', false );

        var remove = new_option.find( '.trp-remove-language' ).toggle();

        new_option = jQuery( '#trp-sortable-languages' ).append( new_option );
        console.log(jQuery( new_option ));
        jQuery( new_option ).find( '.trp-remove-language' ).last().click( this.remove_language );

    };

    this.remove_language = function( element ){
        console.log('remove');
        var message = jQuery( element.target ).attr( 'data-confirm-message' );
        var confirmed = confirm( message );
        if ( confirmed ) {
            jQuery ( element.target ).parent().remove();
        }
    };

    this.initialize = function () {
        jQuery( '#trp-sortable-languages' ).sortable({ handle: '.trp-sortable-handle' });
        jQuery( '#trp-add-language' ).click( this.add_language );
        jQuery( '.trp-remove-language' ).click( this.remove_language );
    };

    this.initialize();
}

var trpSortableLanguages;

// Initialize the Translate Press Settings after jQuery is ready
jQuery( function() {
    trpSortableLanguages = new TRP_Sortable_Languages();
});
