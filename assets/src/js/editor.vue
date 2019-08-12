<template>
    <div id="trp-editor">

        <div id="trp-controls">
            <div class="trp-controls-container">

                <div id="trp-close-save">
                    <a id="trp-controls-close" :href="closeURL" :title="editorStrings.close"></a>
                    <div id="trp-save-and-loader-spinner">
                        <span class="trp-ajax-loader" v-show="loading_strings > 0" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <save-translations
                                :selectedIndexesArray="selectedIndexesArray"
                                :dictionary="dictionary"
                                :settings="settings"
                                :nonces="nonces"
                                :ajax_url="ajax_url"
                                :currentLanguage="currentLanguage"
                                :onScreenLanguage="onScreenLanguage"
                                :iframe="iframe"
                                :currentURL="currentURL"
                                :mergingString="mergingString"
                                :mergeData="mergeData"
                                @translations-saved="showChangesUnsavedMessage = false"
                                :editorStrings="editorStrings"
                        >
                        </save-translations>
                    </div>
                </div>

                <div class="trp-controls-section">

                    <div class="trp-controls-section-content">
                        <div id="trp-language-switch">
                            <select id="trp-language-select" name="lang" v-model="currentLanguage" v-select2>
                                <option v-for="(lang, langIndex) in languageNames" :value="langIndex">{{lang}}</option>
                            </select>
                        </div>

                        <div id="trp-string-list">
                            <select id="trp-string-categories" v-model="selectedString" v-select2>
                                <optgroup v-for="(group) in stringGroups" :label="group">
                                    <option v-for="(string, index) in dictionary" :value="index" v-if="showString( string, group )" :title="string.description" :data-database-id="string.dbID" :data-group="string.group">{{ processOptionName( string.original, group ) }}</option>
                                </optgroup>
                            </select>
                        </div>

                        <div id="trp-next-previous">
                            <button type="button" id="trp-previous" class="trp-next-previous-buttons" v-on:click="previousString()" :title="editorStrings.previous_title_attr"><span>&laquo;</span> {{ editorStrings.previous }}</button>
                            <button type="button" id="trp-next" class="trp-next-previous-buttons" v-on:click="nextString()" :title="editorStrings.next_title_attr">{{ editorStrings.next }} <span>&raquo;</span></button>
                        </div>

                        <div id="trp-view-as">
                            <div id="trp-view-as-description">{{ editorStrings.view_as }}</div>
                            <select id="trp-view-as-select" v-model="viewAs" v-select2>
                                <option v-for="(role, roleIndex) in roles" :value="role" :disabled="!role" :title="!role ? editorStrings.view_as_pro : ''">{{roleIndex}}</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="trp-controls-section" v-show="selectedString !== null">
                    <language-boxes
                            :selectedIndexesArray="selectedIndexesArray"
                            :dictionary="dictionary"
                            :currentLanguage="currentLanguage"
                            :onScreenLanguage="onScreenLanguage"
                            :languageNames="languageNames"
                            :settings="settings"
                            :showChangesUnsavedMessage="showChangesUnsavedMessage"
                            @discarded-changes="hasUnsavedChanges()"
                            :editorStrings="editorStrings"
                            :flagsPath="flagsPath"
                            :iframe="iframe"
                            :nonces="nonces"
                            :ajax_url="ajax_url"
                    >
                    </language-boxes>
                </div>

                <extra-content :languageNames="languageNames" :editorStrings="editorStrings" :paidVersion="paid_version"></extra-content>

                <div class="trp-controls-section" v-show="translationNotLoadedYet">
                    <div id="trp-translation-not-ready-section" class="trp-controls-section-content">
                        <p v-html="editorStrings.translation_not_loaded_yet"></p>
                    </div>
                </div>
            </div>

            <div id="trp_select2_overlay"></div>

            <hover-actions
                ref="hoverActions"
                :dictionary="dictionary"
                :settings="settings"
                :iframe="iframe"
                :dataAttributes="dataAttributes"
                :mergeRules="mergeRules"
                :nonces="nonces"
                :ajax_url="ajax_url"
                :mergeData="mergeData"
                :editorStrings="editorStrings"
                :currentLanguage="currentLanguage"
            >
            </hover-actions>
        </div>

        <div id="trp-preview">
            <iframe id="trp-preview-iframe" :src="urlToLoad" v-on:load="iFrameLoaded"></iframe>

            <div id="trp-preview-loader">
                <svg class="trp-loader" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg">
                    <circle class="trp-circle" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle>
                </svg>
            </div>
        </div>
    </div>
</template>

<script>
    import 'select2/dist/js/select2.min.js'
    import utils            from './utils'
    import axios            from 'axios'
    import languageBoxes    from './components/language-boxes.vue'
    import saveTranslations from './components/save-translations.vue'
    import hoverActions     from './components/hover-actions.vue'
    import extraContent     from './components/extra-content.vue'
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
            'string_group_order',
            'merge_rules',
            'localized_text',
            'paid_version',
            'flags_path'
        ],
        components:{
            languageBoxes,
            saveTranslations,
            hoverActions,
            extraContent
        },
        data(){
            return {
                //props
                settings                  : JSON.parse( this.trp_settings ),
                languageNames             : JSON.parse( this.language_names ),
                orderedSecondaryLanguages : JSON.parse( this.ordered_secondary_languages ),
                roles                     : JSON.parse( this.view_as_roles ),
                nonces                    : JSON.parse( this.editor_nonces),
                stringGroupOrder          : JSON.parse( this.string_group_order),
                selectors                 : JSON.parse( this.string_selectors ),
                dataAttributes            : JSON.parse( this.data_attributes ),
                mergeRules                : JSON.parse( this.merge_rules ),
                editorStrings             : trp_localized_strings,
                flagsPath                 : JSON.parse( this.flags_path ),
                //data
                currentLanguage           : this.current_language,
                onScreenLanguage          : this.on_screen_language,
                currentURL                : this.url_to_load,
                urlToLoad                 : this.url_to_load,
                iframe                    : '',
                dictionary                : [],
                selectedString            : null,
                selectedIndexesArray      : [],
                detectedSelectorAndId     : [],
                stringGroups              : [],
                mergingString             : false,
                mergeData                 : [],
                showChangesUnsavedMessage : false,
                viewAs                    : '',
                loading_strings           : 0,
                translationNotLoadedYet   : false,
            }
        },
        created(){
            this.settings['default-language-name'] = this.languageNames[ this.settings['default-language'] ]

            //set default value for the View As select
            let params = utils.getUrlParameters( this.currentURL )

            if( Object.keys(params).length > 1 && params['trp-view-as'] )
                this.viewAs = params['trp-view-as']
            else
                this.viewAs = 'current_user'
        },
        mounted(){
            this.addKeyboardShortcutsListener()
            let self = this
            // initialize select2
            jQuery( '#trp-language-select, #trp-view-as-select' ).select2( { width : '100%' })

            //init strings dropdown
            this.stringsDropdownLoading()

            // show overlay when select is opened
            jQuery( '#trp-language-select, #trp-string-categories' ).on( 'select2:open', function() {
                jQuery( '#trp_select2_overlay' ).fadeIn( '100' )
            }).on( 'select2:close', function() {
                jQuery( '#trp_select2_overlay' ).hide()
            }).on( 'select2:opening', function(e) {
                /* when we have unsaved changes prevent the strings dropdown from opening so we do not have a disconnect between the textareas and the dropdown */
                if (self.hasUnsavedChanges()) {
                    e.preventDefault()
                }
            })

            // resize sidebar and consequently the iframe
            let previewContainer = jQuery( '#trp-preview' );
            let total_width = jQuery(window).width();
            jQuery( '#trp-controls' ).resizable({
                start: function( ) { previewContainer.toggle(); },
                stop: function( ) { previewContainer.toggle(); },
                handles: 'e',
                minWidth: 285,
                maxWidth: total_width - 20
            }).bind( "resize", this.resizeIframe );

            // resize iframe when resizing window
            jQuery( window ).resize(function () {
                self.resizeIframe();
            });
        },
        watch: {
            currentLanguage: function( currentLanguage ) {
                let self = this
                //grab the correct URL from the iFrame
                let newURL = this.iframe.querySelector( 'link[hreflang="' + currentLanguage.replace( '_', '-' ) +'"]' ).getAttribute('href')

                this.currentURL           = newURL
                this.iframe.location      = newURL

                //reset vue props
                this.selectedString       = ''
                this.selectedIndexesArray = []

                //set strings dropdown to loading state
                jQuery('#trp-string-categories').val('').trigger('change')
                this.stringsDropdownLoading()

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
            viewAs: function( role ) {
                if( !this.currentURL || !this.iframe )
                    return

                let url = this.cleanURL( this.currentURL )

                url = utils.updateUrlParameter( url, 'trp-edit-translation', 'preview' )

                if( role == 'current_user' ) {
                    this.iframe.location = url
                    return
                }

                //if nonce not available, an update to the Browse as Other Roles add-on is required
                if( !this.nonces[role] ) {
                    alert( this.editorStrings.bor_update_notice )
                    return
                }

                url = utils.updateUrlParameter( url, 'trp-view-as', role )
                url = utils.updateUrlParameter( url, 'trp-view-as-nonce', this.nonces[role] )

                this.iframe.location = url
            },
            selectedString: function ( selectedStringArrayIndex, oldString ){

                if( this.hasUnsavedChanges() || ( !selectedStringArrayIndex && selectedStringArrayIndex !== 0 ) )
                    return

                jQuery( '#trp-string-categories' ).val( selectedStringArrayIndex !== null ? selectedStringArrayIndex : '' ).trigger( 'change' )

                let selectedString       = this.dictionary[selectedStringArrayIndex]

                if( !selectedString )
                    return

                let currentNode          = this.iframe.querySelector( "[" + selectedString.selector + "='" + selectedString.dbID + "']")
                let selectedIndexesArray = []

                //when merging we do not have a valid current node, so we just add the fake id
                if( currentNode ) {
                    let self = this
                    let selectors = self.getAllSelectors()
                    let nodes = []

                    nodes.push( currentNode )

                    if ( currentNode.tagName != "A" ){
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

                    nodes.forEach( function( node ) {
                        selectors.forEach(function (selector) {
                            let stringId = node.getAttribute(selector)
                            if (stringId) {
                                let found = false
                                let i
                                for( i = 0; i < selectedIndexesArray.length; i++ ){
                                    if ( typeof self.dictionary[selectedIndexesArray[i]] !== 'undefined' && self.dictionary[selectedIndexesArray[i]].dbID !== 'undefined' && self.dictionary[selectedIndexesArray[i]].dbID === stringId ){
                                        found = true
                                        break;
                                    }
                                }
                                if ( ! found ) {
                                    selectedIndexesArray.push(self.getStringIndex(selector, stringId))
                                }
                            }
                        })
                    })
                } else
                    selectedIndexesArray.push( selectedStringArrayIndex )

                this.selectedIndexesArray = selectedIndexesArray
            },
        },
        computed: {
            closeURL: function() {
                return this.cleanURL( this.currentURL )
            }
        },
        methods: {
            iFrameLoaded(){
                let self = this
                let iframeElement = document.querySelector('#trp-preview-iframe')

                this.iframe = iframeElement.contentDocument || iframeElement.contentWindow.document

                //sync iFrame URL with parent
                if ( this.currentURL != this.iframe.URL )
                    this.currentURL = this.iframe.URL

                //hide iFrame loader
                this.iframeLoader( 'hide' )

                self.detectedSelectorAndId = []
                self.dictionary            = []
                this.scanIframeForStrings()

                window.addEventListener( 'trp_iframe_page_updated', this.scanIframeForStrings )

                //event that is fired when the iFrame is navigated
                iframeElement.contentWindow.onbeforeunload = function() {
                    self.iframeLoader( 'show' )

                    self.selectedString = null
                    self.selectedIndexesArray = []
                    self.translationNotLoadedYet = false

                    self.stringsDropdownLoading()
                }

            },
            scanIframeForStrings(){
                this.scanForSelector( 'data-trp-translate-id', 'regular', this.onScreenLanguage )
                this.scanForSelector( 'data-trpgettextoriginal', 'gettext', this.currentLanguage )
                this.scanForSelector( 'data-trp-post-slug', 'postslug', this.currentLanguage )
            },
            scanForSelector( baseSelector, typeSlug, languageOfIds ){
                this.loading_strings++
                let self           = this
                let selectors      = this.prepareSelectorStrings( baseSelector )
                let nodes          = [...this.iframe.querySelectorAll( '[' + selectors.join('],[') + ']' )]
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
                    data.append('action'       , 'trp_get_translations_' + typeSlug)
                    data.append('all_languages', 'true')
                    data.append('security'     , this.nonces['gettranslationsnonce' + typeSlug])
                    data.append('language'     , languageOfIds)
                    data.append('string_ids'   , JSON.stringify(stringIdsArray))

                    axios.post(this.ajax_url, data)
                        .then(function (response) {
                            self.loading_strings--
                            self.addToDictionary(response.data, nodeData)
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                }else{
                    self.loading_strings--
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
                if ( node.tagName == 'A' && !node.hasAttribute( 'data-trpgettextoriginal' ) )
                    return false

                let self = this

                node.addEventListener( 'mouseenter', self.$refs.hoverActions.showPencilIcon )
            },
            addToDictionary( responseData, nodeInfo = null ){
                let self = this

                if ( responseData != null ) {
                    if ( nodeInfo ){
                        nodeInfo.forEach(function ( infoRow, index ){
                            responseData.some( function ( responseDataRow ) {

                                if ( infoRow.dbID == responseDataRow.dbID ) {
                                    //bring block_type to the top level object
                                    if ( responseDataRow.type != 'gettext' && typeof responseDataRow.block_type == 'undefined' ) {
                                        let firstLanguage = self.orderedSecondaryLanguages[0]

                                        if ( typeof responseDataRow.translationsArray[firstLanguage].block_type != 'undefined' )
                                            responseDataRow.block_type = responseDataRow.translationsArray[firstLanguage].block_type
                                    }

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
                    if ( foundStringGroups.indexOf( string.group ) === -1 && ( ( typeof string.blockType === 'undefined' ) || string.blockType !== '2' ) ){
                        foundStringGroups.push( string.group )
                    }
                })

                // put the node groups in the order that we want, according to the prop this.stringGroupOrder
                let orderedStringGroups = [];

                if ( this.editorStrings.seo_update_notice != 'seo_pack_update_not_needed' ){
                    orderedStringGroups.push( this.editorStrings.seo_update_notice );
                }

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
            showString( string, type ){
                if ( typeof string.blockType !== 'undefined' && string.blockType === '2' ){
                    // don't show deprecated translation blocks in the dropdown
                    return false
                }
                if ( type === this.editorStrings.images && typeof string.attribute != 'undefined' && string.attribute == 'src' )
                    return true

                if ( typeof string.attribute !== 'undefined' && ( string.attribute == 'href' || string.attribute == 'src' ) )
                    return false

                if ( string.group === type )
                    return true

                return false
            },
            initStringsDropdown(){
                let self = this

                if ( !this.isStringsDropdownOpen() ) {
                    jQuery( '#trp-string-categories' ).select2( 'destroy' )

                    jQuery( '#trp-string-categories' ).select2( { placeholder : self.editorStrings.select_string, templateResult: function(option){
                        let original    = he.decode( option.text.substring(0, 90) ) + ( ( option.text.length <= 90) ? '' : '...' )
                        let description = ( option.title ) ?  '(' + option.title + ')' : ''

                        return jQuery( '<div>' + original + '</div><div class="string-selector-description">' + description + '</div>' );
                    }, width : '100%' } ).prop( 'disabled', false )

                    jQuery( '#trp_select2_overlay' ).hide()
                }
            },
            stringsDropdownLoading(){
                jQuery( '#trp-string-categories' ).select2( { placeholder : this.editorStrings.strings_loading, width : '100%' } ).prop( 'disabled', true )
            },
            processOptionName( name, type ){
                if ( type == 'Images' )
                    return utils.getFilename( name )

                return utils.escapeHtml( name )
            },
            isStringsDropdownOpen(){
                return jQuery( '#trp-string-categories' ).select2( 'isOpen' )
            },
            hasUnsavedChanges(){
                let unsavedChanges = false
                let self = this
                if ( this.selectedIndexesArray.length > 0 ) {
                    this.selectedIndexesArray.forEach(function (selectedIndex) {
                        self.settings['translation-languages'].forEach(function (languageCode) {
                            if (self.dictionary[selectedIndex] &&
                                self.dictionary[selectedIndex].translationsArray[languageCode] &&
                                (self.dictionary[selectedIndex].translationsArray[languageCode].translated !== self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation)) {
                                unsavedChanges = true
                            }
                        })
                    })
                }
                this.showChangesUnsavedMessage = unsavedChanges

                return unsavedChanges
            },
            iframeLoader( status ) {
                let loader = document.getElementById( 'trp-preview-loader' )

                if( status == 'show' )
                    loader.style.display = 'flex'
                else if( status == 'hide' )
                    loader.style.display = 'none'
            },
            previousString(){
                let currentValue = document.getElementById('trp-string-categories').value

                let newValue = +currentValue - 1

                while( newValue >= 0 && document.querySelectorAll('#trp-string-categories option[value="' + newValue + '"]').length === 0 ){
                    newValue--;
                }

                if( newValue < 0 )
                    return

                this.selectedString = newValue.toString()
            },
            nextString(){
                let currentValue = document.getElementById('trp-string-categories').value, newValue = 0

                if( currentValue != '' )
                    newValue = +currentValue + 1

                while( newValue < this.dictionary.length && document.querySelectorAll('#trp-string-categories option[value="' + newValue + '"]').length === 0 ){
                    newValue++;
                }

                if ( newValue >= this.dictionary.length ){
                    return
                }

                this.selectedString = newValue.toString()
            },
            addKeyboardShortcutsListener(){
                document.addEventListener("keydown", function(e) {
                    if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey) && e.altKey ) {
                        // CTRL + ALT + right arrow
                        if( e.keyCode === 39 ){
                            e.preventDefault();
                            window.dispatchEvent( new Event( 'trp_trigger_next_string_event' ) );
                        }else{
                            // CTRL + ALT + left arrow
                            if( e.keyCode === 37 ) {
                                e.preventDefault();
                                window.dispatchEvent( new Event( 'trp_trigger_previous_string_event' ) );
                            }
                        }
                    }
                }, false);

                window.addEventListener( 'trp_trigger_next_string_event', this.nextString )
                window.addEventListener( 'trp_trigger_previous_string_event', this.previousString )
            },
            resizeIframe (event, ui) {
                let total_width = jQuery(window).width();
                let width = jQuery( '#trp-controls' ).width();

                if(width > total_width) {
                    width = total_width;
                    controls.css('width', width);
                }
                let previewContainer = jQuery( '#trp-preview' );
                previewContainer.css('right', width );
                previewContainer.css('left', ( width - 348 ) );
                previewContainer.css('width', (total_width - width));
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
