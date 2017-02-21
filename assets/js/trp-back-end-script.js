

function TRP_Settings() {

    /**
     * Function that initializes select2 on fields
     */
    this.initializeSelect2 = function () {
        jQuery('.trp-select2').each(function () {
            var selectElement = jQuery(this);
            //var arguments = selectElement.attr('data-trp-select2-arguments');
            //arguments = JSON.parse(arguments);
            selectElement.select2(/*arguments*/);
        });
    };

    this.initializeSelect2();
}

var trpSettings;

// Initialize the Translate Press Settings after jQuery is ready
jQuery( function() {
    trpSettings = new TRP_Settings();
});
