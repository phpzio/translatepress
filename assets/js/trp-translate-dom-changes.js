

function TRP_Translator(){

    var _this = this;

    this.initialize = function() {
        // create an observer instance
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                for (var i = 0; i < mutation.addedNodes.length; i++) {
                    //console.log('  "' + mutation.addedNodes[i].textContent + '" added');
                    //console.log( mutation.addedNodes[i] );
                    mutation.addedNodes[i].textContent = 'altfel';
                    /*updateTextContent('altceva');*/
                }
                /*console.log(mutation);*/
            });
        });

        // configuration of the observer:
        var config = {
            attributes: true,
            childList: true,
            characterData: true,
            subtree: true
        };


        observer.observe( document.body , config );
    };

    _this.initialize();
}

var trpTranslator;

// Initialize the Translate Press Editor after jQuery is ready
jQuery( function() {
    trpTranslator = new TRP_Translator();
    jQuery( ".site-branding" ).append("<h1>new word</h1>");
});

