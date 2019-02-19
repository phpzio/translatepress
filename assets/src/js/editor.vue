<template>
    <div id="trp-editor">

        <div id="trp-controls">
            <div class="trp-controls-container">

                <div id="trp-close-save">
                    <a id="trp-controls-close" :href="closeURL"></a>
                    <div id="trp-save-container">
                        <span id="trp-translation-saved" style="display: none">Saved</span>
                        <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <button id="trp-save" type="submit" class="button-primary trp-save-string">Save translation</button>
                    </div>
                </div>

                <div class="trp-controls-section">

                    <div class="trp-controls-section-content">
                        <div id="trp-language-switch">
                            <select id="trp-language-select" name="lang" v-model="currentLanguage" v-select2>
                                <option v-for="(lang, langIndex) in languages" :value="langIndex">{{lang}}</option>
                            </select>
                        </div>

                        <div id="trp-string-list">
                            <select id="trp-string-categories" v-model="selectedString" v-select2>
                                <optgroup v-for="(type) in stringTypes" :label="type">
                                    <option v-for="(string, index) in dictionary" :value="index" v-if="string.type == type">{{string.original}}</option>
                                </optgroup>
                            </select>
                        </div>

                        <div id="trp-next-previous">
                            <button type="button" id="trp-previous" class="trp-next-previous-buttons"><span>&laquo;</span> Previous</button>
                            <button type="button" id="trp-next" class="trp-next-previous-buttons">Next <span>&raquo;</span></button>
                        </div>

                        <div id="trp-view-as">
                            <div id="trp-view-as-description">View as</div>
                            <select id="trp-view-as-select">
                                <option v-for="(role, roleIndex) in roles" :value="role" :disabled="!role" :title="!role ? 'Available in our Pro Versions' : ''">{{roleIndex}}</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="trp-controls-section">

                    <div id="trp-translation-section" class="trp-controls-section-content">

                        <div id="trp-unsaved-changes-warning-message" style="display:none">You have unsaved changes!</div>

                        <div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">
                            <div class="trp-language-name">Original String</div>
                            <textarea id="trp-gettext-original-textarea" readonly="readonly"></textarea>
                        </div>

                        <div :id="'trp-language-' + settings['default-language']" class="trp-language-text trp-default-language">
                            <div class="trp-language-name" :data-trp-gettext-language-name="'To ' + settings['default-language-name']" :data-trp-default-language-name="'From ' + settings['default-language-name']">
                                {{ 'From ' + settings['default-language-name'] }}
                            </div>

                            <textarea id="trp-original" :data-trp-language-code="settings['default-language']" readonly="readonly"></textarea>

                            <div class="trp-discard-changes trp-discard-on-default-language" style="display:none;">Discard changes</div>
                        </div>
                    </div>
                </div>

            </div>

            <div id="trp_select2_overlay"></div>
        </div>

        <div id="trp-preview">
            <iframe id="trp-preview-iframe" :src="urlToLoad" v-on:load="iFrameLoaded"></iframe>
        </div>
    </div>
</template>

<script>
    import 'select2/dist/js/select2.min.js'
    import utils from './utils'
    import axios from 'axios'

    export default {
        props: [
            'trp_settings',
            'available_languages',
            'current_language',
            'on_screen_language',
            'view_as_roles',
            'url_to_load',
            'string_selectors',
            'ajax_url',
            'editor_nonces',
            'string_type_order'
        ],
        data(){
            return {
                settings        : JSON.parse( this.trp_settings ),
                languages       : JSON.parse( this.available_languages ),
                roles           : JSON.parse( this.view_as_roles ),
                selectors       : JSON.parse( this.string_selectors ),
                nonces          : JSON.parse( this.editor_nonces),
                stringTypeOrder : JSON.parse( this.string_type_order),
                currentLanguage : this.current_language,
                currentURL      : this.url_to_load,
                urlToLoad       : this.url_to_load,
                iframe          : '',
                dictionary      : [],
                stringTypes     : [],
                selectedString : '',
                nodes          : {},
            }
        },
        created(){
            this.settings['default-language-name'] = this.languages[ this.settings['default-language'] ]
            this.selectors = this.prepareSelectorStrings()
        },
        mounted(){
            // initialize select2
            jQuery( '#trp-language-select, #trp-view-as-select' ).select2( { width : '100%' })
            //@todo add template
            jQuery( '#trp-string-categories' ).select2({ placeholder : 'Select string to translate...', width : '100%' })

            // show overlay when select is opened
            jQuery( '#trp-language-select, #trp-string-categories' ).on( 'select2:open', function() {
                jQuery( '#trp_select2_overlay' ).fadeIn( '100' )
            }).on( 'select2:close', function() {
                jQuery( '#trp_select2_overlay' ).hide()
            })
        },
        watch: {
            currentLanguage: function( currentLanguage ) {

                //grab the correct URL from the iFrame
                let newURL = this.iframe.querySelector( 'link[hreflang="' + currentLanguage.replace( '_', '-' ) +'"]' ).getAttribute('href')

                this.currentURL = newURL
            },
            currentURL: function ( newUrl, oldUrl ) {
                window.history.replaceState( null, null, this.parentURL( newUrl ) )
            },
            selectedString: function ( newString, oldString ){
                console.log( newString )
            },
            dictionary: function (){
//                let self = this
//                this.dictionary.forEach( function ( row ) {
//                    if ( self.stringTypes.indexOf( row.type ) === -1 ){
//                        self.stringTypes.push( row.type )
//                    }
//                })

                //merge the data type info
            }
        },
        computed: {
            closeURL: function() {
                return this.cleanURL( this.currentURL )
            }
        },
        methods: {
            iFrameLoaded(){
                let iframeElement = document.querySelector('#trp-preview-iframe')

                this.iframe = iframeElement.contentDocument || iframeElement.contentWindow.document

                //sync iFrame URL with parent
                if ( this.currentURL != this.iframe.URL )
                    this.currentURL = this.iframe.URL

                this.init()
            },
            init(){
                this.setupNodes()
                this.setupDictionaries()
                this.setupEventListeners()
            },
            setupNodes() {
                this.nodes = this.iframe.querySelectorAll( '[' + this.selectors.join('],[') + ']' )
            },
            setupDictionaries(){
                //setup strings array based on iFrame
                //save nodes somewhere else so we can hook the event listeners ?
                let self                  = this
                let regularStringIdsArray = []
                let gettextStringIdsArray = []

                this.nodes.forEach( function ( node ){
                    self.selectors.some( function ( selector ){
                        let stringId = node.getAttribute( selector )

                        // add these details now and merge them later
                        //this.nodeInfo.push( { type, stringId, nodeType, relatedId } );

                        if ( stringId ){
                            if ( selector.includes('data-trpgettextoriginal') ){
                                gettextStringIdsArray.push( stringId )
                            }else{
                                regularStringIdsArray.push( { 'id': stringId } )
                            }

                            return true
                        }
                        return false
                    })
                })

                //unique ids only
                gettextStringIdsArray = [...new Set(gettextStringIdsArray)]

                //grab Regular strings
                let data = new FormData()
                    data.append('action'       , 'trp_get_translations')
                    data.append('all_languages', 'true')
                    data.append('security'     , this.nonces['gettranslationsnonce'])
                    data.append('language'     , this.on_screen_language)
                    data.append('strings'      , JSON.stringify( regularStringIdsArray ) )

                axios.post( this.ajax_url, data )
                    .then(function (response) {
                        self.addToDictionary( response.data )
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

                //grab Gettext strings
                data = new FormData()
                    data.append('action'            , 'trp_gettext_get_translations')
                    data.append('security'          , this.nonces['gettextgettranslationsnonce'])
                    data.append('language'          , this.currentLanguage)
                    data.append('gettext_string_ids', JSON.stringify( gettextStringIdsArray ) )


                axios.post( this.ajax_url, data )
                    .then(function (response){
                        self.addToDictionary( response.data )
                    })
                    .catch(function (error){
                        console.log(error)
                    });
            },
            addToDictionary( data ){
                if ( data != null ) {
                    this.dictionary = this.dictionary.concat( data );

                    let self = this
                    let foundStringTypes = this.stringTypes;

                    data.forEach( function ( row ) {
                        if ( foundStringTypes.indexOf( row.type ) === -1 ){
                            foundStringTypes.push( row.type )
                        }
                    })

                    let orderedStringTypes = [];
                    this.stringTypeOrder.forEach( function( type ){
                        if ( foundStringTypes.indexOf( type ) !== -1 ){
                            orderedStringTypes.push( type )
                        }
                    });

                    this.stringTypes = orderedStringTypes;
                }
            },
            setupEventListeners(){
                this.nodes.forEach( function ( node ){

                    node.addEventListener( 'mouseenter', function( element ) {
                        element.target.className += 'trp-highlight'
                    })

                    node.addEventListener( 'mouseleave', function( element ) {
                        element.target.classList.remove( 'trp-highlight' )
                    })

                })
            },
            prepareSelectorStrings(){
                let parsed_selectors = []

                this.selectors.forEach( function ( selector, index ){
                    parsed_selectors.push( 'data-trp-translate-id' + selector  )
                    parsed_selectors.push( 'data-trpgettextoriginal' + selector  )
                })

                return parsed_selectors
            },
            parentURL( url ) {
                return url.replace( 'trp-edit-translation=preview', 'trp-edit-translation=true' )
            },
            cleanURL( url ) {
                //make removeUrlParameter recursive and only call it once with all the parameters that
                //need to stripped ?
                url = utils.removeUrlParameter( url, 'lang' )
                url = utils.removeUrlParameter( url, 'trp-view-as' )
                url = utils.removeUrlParameter( url, 'trp-view-as-nonce' )
                url = utils.removeUrlParameter( url, 'trp-edit-translation' )

                return url
            }
        },
        //add support for v-model in select2
        directives: {
            select2: {
                inserted(el) {
                    jQuery(el).on('select2:select', () => {
                        const event = new Event('change', { bubbles: true, cancelable: true })
                        el.dispatchEvent(event)
                    })

                    jQuery(el).on('select2:unselect', () => {
                        const event = new Event('change', { bubbles: true, cancelable: true })
                        el.dispatchEvent(event)
                    })
                },
            }
        }
    }
</script>
