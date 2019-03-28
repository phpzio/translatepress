<template>
    <div id="trp-editor">

        <div id="trp-controls">
            <div class="trp-controls-container">

                <div id="trp-close-save">
                    <a id="trp-controls-close" :href="closeURL"></a>
                    <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                        <div class="trp-spinner"></div>
                    </span>
                    <save-translations
                            :selectedIndexesArray="selectedIndexesArray"
                            :dictionary="dictionary"
                            :settings="settings"
                            :nonces="nonces"
                            :ajax_url="ajax_url"
                            :currentLanguage="currentLanguage"
                            :iframe="iframe"
                            :currentURL="currentURL"
                            @translations-saved="showChangesUnsavedMessage = false"
                    >
                    </save-translations>
                </div>

                <div class="trp-controls-section">

                    <div class="trp-controls-section-content">
                        <div id="trp-language-switch">
                            <select id="trp-language-select" name="lang" v-model="currentLanguage" v-select2>
                                <option v-for="(lang, langIndex) in languageNames" :value="langIndex">{{lang}}</option>
                            </select>
                        </div>

                        <div id="trp-string-list">
                            <!-- @NOTE: Move to a component ?
                            Because this is simply a listing which gets data and then displays it accordingly -->
                            <select id="trp-string-categories" v-model="selectedString" v-select2>
                                <optgroup v-for="(group) in stringGroups" :label="group">
                                    <option v-for="(string, index) in dictionary" :value="index" v-if="showString( string, group )" :title="string.description" :data-database-id="string.dbID" :data-group="string.group">{{ processOptionName( string.original, group ) }}</option>
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
                    <language-boxes
                            :selectedIndexesArray="selectedIndexesArray"
                            :dictionary="dictionary"
                            :currentLanguage="currentLanguage"
                            :onScreenLanguage="onScreenLanguage"
                            :languageNames="languageNames"
                            :settings="settings"
                            :showChangesUnsavedMessage="showChangesUnsavedMessage"
                            @discarded-changes="hasUnsavedChanges()"
                    >
                    </language-boxes>
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
    import utils            from './utils'
    import axios            from 'axios'
    import languageBoxes    from './components/language-boxes.vue'
    import saveTranslations from './components/save-translations.vue'
    import he               from 'he'

    export default {
        props: [
            'trp_settings',
            'language_names',
            'ordered_secondary_languages',
            'current_language',
            'on_screen_language',
            'view_as_roles',
            'url_to_load',
            'string_selectors',
            'data_attributes',
            'ajax_url',
            'editor_nonces',
            'string_group_order'
        ],
        components:{
            languageBoxes,
            saveTranslations
        },
        data(){
            return {
                settings                  : JSON.parse( this.trp_settings ),
                languageNames             : JSON.parse( this.language_names ),
                orderedSecondaryLanguages : JSON.parse( this.ordered_secondary_languages ),
                roles                     : JSON.parse( this.view_as_roles ),
                nonces                    : JSON.parse( this.editor_nonces),
                stringGroupOrder          : JSON.parse( this.string_group_order),
                selectors                 : JSON.parse( this.string_selectors ),
                dataAttributes            : JSON.parse( this.data_attributes ),
                currentLanguage           : this.current_language,
                onScreenLanguage          : this.on_screen_language,
                currentURL                : this.url_to_load,
                urlToLoad                 : this.url_to_load,
                iframe                    : '',
                dictionary                : [],
                detectedSelectorAndId     : [],
                stringGroups              : [],
                selectedString            : '',
                nodes                     : {},
                selectedIndexesArray      : [],
                showChangesUnsavedMessage : false,
                editStringHtml            : '<trp-span><trp-merge  title="Merge" class="trp-icon trp-merge dashicons dashicons-arrow-up-alt"></trp-merge><trp-split title="Split" class="trp-icon trp-split dashicons dashicons-arrow-down-alt"></trp-split><trp-edit title="Edit" class="trp-icon trp-edit-translation dashicons dashicons-edit"></trp-edit></trp-span>',
            }
        },
        created(){
            this.settings['default-language-name'] = this.languageNames[ this.settings['default-language'] ]
        },
        mounted(){
            let self = this
            // initialize select2
            jQuery( '#trp-language-select, #trp-view-as-select' ).select2( { width : '100%' })
            jQuery( '#trp-string-categories' ).select2( { placeholder : 'Loading strings...', width : '100%' } ).prop( 'disabled', true )

            // show overlay when select is opened
            jQuery( '#trp-language-select, #trp-string-categories' ).on( 'select2:open', function() {
                jQuery( '#trp_select2_overlay' ).fadeIn( '100' )
            }).on( 'select2:close', function() {
                jQuery( '#trp_select2_overlay' ).hide()
            }).on( 'select2:opening', function(e) {
                /* when we have unsaved changes prevent the strings dropdown from opening so we do not have a disconnect between the textareas and the dropdown */
                if (self.hasUnsavedChanges()) {
                    e.preventDefault();
                }
            })
        },
        watch: {
            currentLanguage: function( currentLanguage ) {
                let self = this
                //grab the correct URL from the iFrame
                let newURL = this.iframe.querySelector( 'link[hreflang="' + currentLanguage.replace( '_', '-' ) +'"]' ).getAttribute('href')

                this.currentURL = newURL

                this.onScreenLanguage = currentLanguage
                if( this.settings['default-language'] == this.currentLanguage && this.settings['translation-languages'].length > 1 ){
                    this.settings['translation-languages'].some(function(language){
                        if ( language != self.settings['default-language'] ){
                            // return the first language not default
                            self.onScreenLanguage = language
                            return true
                        }
                    })
                }
            },
            currentURL: function ( newUrl, oldUrl ) {
                window.history.replaceState( null, null, this.parentURL( newUrl ) )
            },
            selectedString: function ( selectedStringArrayIndex, oldString ){
                if ( this.hasUnsavedChanges() || typeof selectedStringArrayIndex == 'undefined' || selectedStringArrayIndex == null ) {
                    return
                }

                jQuery('#trp-string-categories').val( selectedStringArrayIndex ).trigger( 'change' )

                let selectedString = this.dictionary[selectedStringArrayIndex]
                let currentNode = this.iframe.querySelector( "[" + selectedString.selector + "='" + selectedString.dbID + "']")
                let nodes = []
                nodes.push( currentNode )

                let self = this
                let selectors = self.getAllSelectors()

                if ( currentNode.tagName == "IMG" ){
                    // include the anchor's translatable attributes
                    let anchorParent  = currentNode.closest('a')
                    if(  anchorParent != null ) {
                        nodes.push(anchorParent)
                    }
                }

                if ( currentNode.tagName == "A" && currentNode.children.length > 0 ){
                    // include all the translatable attributes inside the anchor
                    let childrenArray = [ ...currentNode.children ];
                    childrenArray.forEach( function ( child ) {
                        nodes.push(child)
                    })

                }

                let selectedIndexesArray = []
                nodes.forEach( function( node ) {
                    selectors.forEach(function (selector) {
                        let stringId = node.getAttribute(selector)
                        if (stringId) {
                            selectedIndexesArray.push(self.getStringIndex(selector, stringId))
                        }
                    })
                })

                this.selectedIndexesArray = selectedIndexesArray

            },
            selectedIndexesArray: function( newSelectedIndexesArray, oldSelectedIndexesArray ){
                console.log( newSelectedIndexesArray)
            },
            dictionary: function (){
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
                window.addEventListener( 'trp_iframe_page_updated', this.scanIframeForStrings )
            },
            init(){
                this.dictionary = []
                this.scanIframeForStrings()
            },
            scanIframeForStrings(){
                this.scanForSelector( 'data-trp-translate-id', 'regular', this.onScreenLanguage )
                this.scanForSelector( 'data-trpgettextoriginal', 'gettext', this.currentLanguage )
            },
            scanForSelector( baseSelector, typeSlug, languageOfIds ){
                let self           = this
                let selectors      = this.prepareSelectorStrings( baseSelector )
                let nodes          = this.iframe.querySelectorAll( '[' + selectors.join('],[') + ']' )
                let stringIdsArray = [], nodeData = [], nodeEntries = []

                nodes.forEach( function ( node ){
                    nodeEntries = self.getNodeInfo( node, baseSelector )

                    nodeEntries.forEach( function( entry ) {
                        // this check ensures that we don't create duplicates when rescanning after ajax complete
                        if ( !self.alreadyDetected( entry.selector, entry.dbID ) ) {

                            stringIdsArray.push(entry.dbID)
                            nodeData.push(entry)
                        }
                    })

                    self.setupEventListener( node )
                })

                //unique ids only
                stringIdsArray = [...new Set(stringIdsArray)]
                if ( stringIdsArray.length > 0 ) {
                    let data = new FormData()
                    data.append('action', 'trp_get_translations_' + typeSlug)
                    data.append('all_languages', 'true')
                    data.append('security', this.nonces['gettranslationsnonce' + typeSlug])
                    data.append('language', languageOfIds)
                    data.append('string_ids', JSON.stringify(stringIdsArray))

                    axios.post(this.ajax_url, data)
                        .then(function (response) {
                            self.addToDictionary(response.data, nodeData)
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                }

            },
            alreadyDetected( selector, dbId ){
                let combined = selector + '=' + dbId
                if ( utils.arrayContainsItem( this.detectedSelectorAndId, combined ) ) {
                    return true
                }else {
                    this.detectedSelectorAndId.push(combined)
                    return false
                }
            },
            setupEventListener( node ){
                if ( node.tagName == 'A' )
                    return false

                let self = this

                node.addEventListener( 'mouseenter', self.showPencilIcon )
            },
            addToDictionary( responseData, nodeInfo = null ){
                if ( responseData != null ) {
                    if ( nodeInfo ){
                        nodeInfo.forEach(function ( infoRow, index ){
                            responseData.some( function ( responseDataRow ) {
                                if ( infoRow.dbID == responseDataRow.dbID ) {
                                    nodeInfo[index] = Object.assign( {}, responseDataRow, infoRow )
                                    return true // a sort of break
                                }
                            })
                        })
                    }else{
                        nodeInfo = responseData
                    }

                    this.stringGroups = this.addToStringGroups( nodeInfo )
                    this.dictionary = this.dictionary.concat( nodeInfo )

                    this.initStringsDropdown()
                }
            },
            addToStringGroups( strings ){

                // see what node groups are found
                let foundStringGroups = this.stringGroups;
                strings.forEach( function ( string ) {
                    if ( foundStringGroups.indexOf( string.group ) === -1 ){
                        foundStringGroups.push( string.group )
                    }
                })

                // put the node groups in the order that we want, according to the prop this.stringGroupOrder
                let orderedStringGroups = [];
                this.stringGroupOrder.forEach( function( group ){
                    if ( foundStringGroups.indexOf( group ) !== -1 ){
                        orderedStringGroups.push( group )
                    }
                })

                // if there were any other string groups that were not in the prop, add them at the end.
                foundStringGroups.forEach( function (group) {
                    if ( orderedStringGroups.indexOf( group ) === -1 ){
                        orderedStringGroups.push(group);
                    }
                })

                return orderedStringGroups;
            },
            getStringIndex( selector, dbID ){
                let found = null

                this.dictionary.some(function ( string, index ) {
                    if ( string.dbID == dbID && string.selector == selector ){
                        found = index
                        return true
                    }
                })

                return found
            },
            getNodeInfo( node, baseSelector = '' ){
                let stringId
                let nodeData  = []
                let selectors = this.prepareSelectorStrings( baseSelector )

                selectors.forEach( function ( selector ) {

                    stringId = node.getAttribute( selector )

                    if ( stringId ) {

                        let nodeAttribute   = selector.replace( baseSelector, '' )
                        let nodeGroup       = node.getAttribute( 'data-trp-node-group' + nodeAttribute )
                        let nodeDescription = node.getAttribute( 'data-trp-node-description' + nodeAttribute )

                        let entry = {
                            dbID      : stringId,
                            selector  : selector,
                            attribute : nodeAttribute.substr(1), // substr(1) is used to trim prefixing line - ex. -alt will result in alt (no line)
                        }

                        if ( nodeGroup )
                            entry.group = nodeGroup

                        if ( nodeDescription )
                            entry.description = nodeDescription

                        nodeData.push( entry )
                    }

                })

                return nodeData
            },
            getAllSelectors(){
                let selectors = []
                let self      = this

                this.dataAttributes.forEach( function ( dataAttribute ){
                    selectors = selectors.concat( self.prepareSelectorStrings( dataAttribute ) )
                })

                return selectors
            },
            prepareSelectorStrings( baseNameSelector ){
                let parsed_selectors = []

                this.selectors.forEach( function ( selectorSuffix, index ){
                    parsed_selectors.push( baseNameSelector + selectorSuffix  )
                })

                return parsed_selectors
            },
            parentURL( url ){
                return url.replace( 'trp-edit-translation=preview', 'trp-edit-translation=true' )
            },
            cleanURL( url ){
                //make removeUrlParameter recursive and only call it once with all the parameters that
                //need to stripped ?
                url = utils.removeUrlParameter( url, 'lang' )
                url = utils.removeUrlParameter( url, 'trp-view-as' )
                url = utils.removeUrlParameter( url, 'trp-view-as-nonce' )
                url = utils.removeUrlParameter( url, 'trp-edit-translation' )

                return url
            },
            showPencilIcon( element ){
                let target = element.target

                let self = this
                let relatedNode, relatedNodeAttr, position, stringSelector, stringId

                //for these tag names we need to insert our HTML before the element and not inside of it
                //@TODO: add/research more
                let beforePosition = [ 'IMG', 'INPUT', 'TEXTAREA' ]

                //if other icons are showing, remove them
                self.removePencilIcon()

                //remove highlight class
                let previouslyHighlighted = this.iframe.getElementsByClassName( 'trp-highlight' )

                if ( previouslyHighlighted.length > 0 ) {
                    let i

                    for ( i = 0; i < previouslyHighlighted.length; i++ )
                        previouslyHighlighted[i].classList.remove( 'trp-highlight' )
                }

                //add class to highlight text
                if ( !target.classList.contains( 'trp-highlight' ) )
                    target.className += ' trp-highlight'

                //figure out where to insert extra HTML
                if ( beforePosition.includes( target.tagName ) )
                    position = 'beforebegin'
                else
                    position = 'afterbegin'

                //insert button HTML
                target.insertAdjacentHTML( position, this.editStringHtml )

                let editButton = this.iframe.querySelector( 'trp-span' )

                //onclick event listener
                //@NOTE: we might need to add separate events for different buttons (the block split stuff)
                editButton.addEventListener( 'click', function( event ) {
                    event.preventDefault()

                    //get node info based on where we inserted our button
                    if ( position == 'afterbegin' )
                        relatedNode = self.iframe.getElementsByTagName( 'trp-span' )[0].parentNode
                    else
                        relatedNode = self.iframe.getElementsByTagName( 'trp-span' )[0].nextElementSibling

                    self.dataAttributes.forEach( function( baseSelector ) {

                        self.prepareSelectorStrings( baseSelector ).forEach( function( selector ) {

                            relatedNodeAttr = relatedNode.getAttribute( selector )

                            if ( relatedNodeAttr ) {
                                stringId = relatedNodeAttr
                                stringSelector = selector
                            }

                        })

                    })

                    self.selectedString = self.getStringIndex( stringSelector, stringId )

                    jQuery( '#trp-string-categories' ).select2( 'close' )
                })

            },
            removePencilIcon(){
                let icons = this.iframe.querySelectorAll( 'trp-span' )

                if ( icons.length > 0 ) {
                    icons.forEach( function( icon ) {
                        icon.remove()
                    })
                }
            },
            showString( string, type ){
                if ( type == 'Images' && typeof string.attribute != 'undefined' && string.attribute == 'src' )
                    return true

                if ( typeof string.attribute != 'undefined' && ( string.attribute == 'href' || string.attribute == 'src' ) )
                    return false

                if ( string.group == type )
                    return true

                return false
            },
            initStringsDropdown(){
                if ( !this.isStringsDropdownOpen() ) {
                    jQuery( '#trp-string-categories' ).select2( 'destroy' )

                    jQuery( '#trp-string-categories' ).select2( { placeholder : 'Select string to translate...', templateResult: function(option){
                        let original    = he.decode( option.text.substring(0, 90) ) + ( ( option.text.length <= 90) ? '' : '...' )
                        let description = ( option.title ) ?  '(' + option.title + ')' : ''

                        return jQuery( '<div>' + original + '</div><div class="string-selector-description">' + description + '</div>' );
                    }, width : '100%' } ).prop( 'disabled', false )

                    jQuery( '#trp_select2_overlay' ).hide()
                }
            },
            processOptionName( name, type ){
                if ( type == 'Images' )
                    return utils.getFilename( name )

                return name
            },
            isStringsDropdownOpen() {
                return jQuery( '#trp-string-categories' ).select2( 'isOpen' )
            },
            hasUnsavedChanges(){
                let unsavedChanges = false
                let self = this
                this.selectedIndexesArray.forEach(function(selectedIndex){
                    self.settings['translation-languages'].forEach( function( languageCode  ) {
                        if ( self.dictionary[selectedIndex].translationsArray[languageCode] &&
                            (self.dictionary[selectedIndex].translationsArray[languageCode].translated !== self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation) ) {
                            unsavedChanges = true
                        }
                    })
                })
                this.showChangesUnsavedMessage = unsavedChanges

                return unsavedChanges
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
