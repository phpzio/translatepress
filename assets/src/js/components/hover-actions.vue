<template>
    <div id="trp-span trp-actions"></div>
</template>

<script>
    import utils from '../utils'
    import axios from 'axios'

    export default{
        props:[
            'dictionary',
            'settings',
            'iframe',
            'dataAttributes',
            'mergeRules',
            'ajax_url',
            'nonces',
            'mergeData',
            'editorStrings',
            'currentLanguage'
        ],
        data(){
            return{
                hoveredStringId       : '',
                hoveredStringSelector : '',
                hoveredTarget         : '',
                counter               : 0
            }
        },
        methods:{
            showPencilIcon( element ){
                if( !this.dictionary || this.dictionary.length < 1 )
                    return

                let self = this
                let target = element.target
                let relatedNode, relatedNodeAttr, position, stringSelector, stringId, mergeOrSplit

                //for these tag names we need to insert our HTML before the element and not inside of it
                //@TODO: add/research more
                let beforePosition = [ 'IMG', 'INPUT', 'TEXTAREA' ]

                if( self.hoveredTarget != '' && target.isSameNode( self.hoveredTarget ) )
                    return

                //if other icons are showing, remove them
                self.removePencilIcon()

                //remove highlight class
                self.removeHighlight( false )

                //figure out where to insert extra HTML
                if( beforePosition.includes( target.tagName ) )
                    position = 'beforebegin'
                else
                    position = 'afterbegin'

                //insert button HTML
                target.insertAdjacentHTML( position, this.getTrpSpan() )

                //inserted node
                let trpSpan = self.iframe.getElementsByTagName( 'trp-span' )[0]

                if( !trpSpan )
                    return

                //get node info based on where we inserted our button
                if( position == 'afterbegin' )
                    relatedNode = trpSpan.parentNode
                else
                    relatedNode = trpSpan.nextElementSibling

                //edit string button
                let editButton = this.iframe.querySelector( 'trp-edit' )
                let foundNonGettext = false

                self.dataAttributes.forEach( function( baseSelector ) {

                    self.$parent.prepareSelectorStrings( baseSelector ).forEach( function( selector ) {

                        relatedNodeAttr = relatedNode.getAttribute( selector )

                        if ( relatedNodeAttr ) {
                            stringId = relatedNodeAttr
                            stringSelector = selector
                            if ( ! stringSelector.includes( 'data-trpgettextoriginal' ) ){
                                // includes at least one data-base-selector that is not gettext. Useful for determining edit pencil color
                                foundNonGettext = true
                            }
                        }
                    })
                })

                self.hoveredStringSelector = stringSelector
                self.hoveredStringId       = stringId
                self.hoveredTarget         = target

                // show green edit pencil
                if ( foundNonGettext ){
                    editButton.classList.remove( 'trp-gettext-pencil' )
                }else{
                    editButton.classList.add( 'trp-gettext-pencil' )
                }

                //figure out if split or merge is available
                mergeOrSplit = self.checkMergeOrSplit( target )

                //fit inside view
                self.fitPencilIconInsideView( trpSpan, target, mergeOrSplit )

                if( !self.mergeData.includes( stringId ) ) {
                    editButton.style.display = 'inline-block'

                    //add class to highlight text
                    if( !target.classList.contains( 'trp-highlight' ) )
                        target.className += ' trp-highlight'
                }

                //merge or split event listeners
                if( mergeOrSplit != 'none' && !self.mergeData.includes( stringId ) ) {
                    let button = this.iframe.querySelector( 'trp-' + mergeOrSplit )

                    button.style.display = 'inline-block'

                    //setup event listeners for merge and split
                    if( mergeOrSplit == 'split' )
                        button.addEventListener( 'click', self.splitHandler )
                    else if( mergeOrSplit == 'merge' )
                        button.addEventListener( 'click', self.mergeHandler )
                }

                editButton.addEventListener( 'click', self.editHandler )
            },
            editHandler( event ){
                event.preventDefault()
                event.stopPropagation()

                if( this.$parent.mergingString )
                    this.removeHighlight( true )

                this.$parent.mergeData      = []

                this.$parent.selectedString = this.$parent.getStringIndex( this.hoveredStringSelector, this.hoveredStringId )

                this.$parent.translationNotLoadedYet  = ( this.$parent.selectedString === null )

                jQuery( '#trp-string-categories' ).select2( 'close' )
            },
            splitHandler( event ) {
                event.preventDefault()
                event.stopPropagation()
                this.$parent.mergingString = false

                let split = confirm( this.editorStrings.split_confirmation )

                if( split === false )
                    return

                let strings = []
                let hoveredStringIndex = this.$parent.getStringIndex( this.hoveredStringSelector, this.hoveredStringId )
                strings.push( this.dictionary[ hoveredStringIndex ].original )

                let data = new FormData()
                    data.append( 'action', 'trp_split_translation_block' )
                    data.append( 'security', this.nonces['splittbnonce'] )
                    data.append( 'strings', JSON.stringify( strings ) )

                let self = this

                axios.post(this.ajax_url, data)
                    .then(function (response) {
                        window.location.reload()
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            },
            mergeHandler( event ) {
                event.preventDefault()
                event.stopPropagation()

                let self = this
                let parent, isDeprecated = null, deprecatedString = null, stringId

                self.$parent.mergingString = true

                //remove classes
                let previouslyHighlighted = this.iframe.getElementsByClassName( 'trp-create-translation-block' )

                if( previouslyHighlighted.length > 0 ) {
                    let i

                    for ( i = 0; i < previouslyHighlighted.length; i++ ) {
                        previouslyHighlighted[i].classList.remove( 'trp-highlight' )
                        previouslyHighlighted[i].classList.remove( 'trp-create-translation-block' )
                    }
                }

                parent = self.hoveredTarget.closest( self.mergeRules.top_parents )

                //remove highlight classes from children
                parent.querySelectorAll( '.trp-highlight' ).forEach( function(node) {
                    node.classList.remove( 'trp-highlight' )
                })

                //determine the strings that are being prepared for merging (no gettext)
                self.$parent.mergeData = []

                parent.querySelectorAll( '[data-trp-translate-id]' ).forEach( function( node ) {
                    stringId = node.getAttribute( 'data-trp-translate-id' )

                    if ( stringId )
                        self.$parent.mergeData.push( stringId )
                })

                //check if we have existing translations for this block
                isDeprecated = parent.getAttribute( 'data-trp-translate-id-deprecated' )

                if( isDeprecated )
                    deprecatedString = self.$parent.getStringIndex( 'data-trp-translate-id', isDeprecated )

                parent.setAttribute( 'data-trp-translate-id', 'trp_creating_translation_block' )

                parent.className += ' trp-highlight trp-create-translation-block'

                //create a placeholder string for the dictionary
                let dummyString = {
                    type              : 'regular',
                    attribute         : '',
                    block_type        : '1',
                    dbID              : 'create_translation_block' + this.counter,
                    original          : self.stripEditorData( parent ),
                    selector          : 'data-trp-translate-id',
                    translationsArray : {}
                }
                this.counter++

                let dummyTranslations = {}

                let defaultLanguage = this.settings['default-language']

                //populate translationsArray
                self.settings['translation-languages'].forEach( function( languageCode  ){
                    if( languageCode != defaultLanguage ) {
                        dummyTranslations = {
                            block_type : '1',
                            id         : languageCode,
                            status     : '0',
                            translated : '',
                            editedTranslation: ''
                        }

                        //populate existing translations
                        if( deprecatedString ) {
                            dummyTranslations.translated        = self.dictionary[deprecatedString].translationsArray[languageCode].translated
                            dummyTranslations.editedTranslation = self.dictionary[deprecatedString].translationsArray[languageCode].translated
                        }

                        dummyString.translationsArray[languageCode] = dummyTranslations
                    }
                })

                //add item to dictionary and set selectedString as the index
                self.$parent.selectedString = self.dictionary.push( dummyString ) - 1

            },
            removePencilIcon(){
                let icons = this.iframe.querySelectorAll( 'trp-span' )

                if ( icons.length > 0 ) {
                    icons.forEach( function( icon ) {
                        icon.remove()
                    })
                }
            },
            checkMergeOrSplit( target ){
                if( !this.mergeRules || !this.mergeRules.self_object_type || !this.mergeRules.top_parents )
                    return 'none'

                let hoveredStringIndex = this.$parent.getStringIndex( this.hoveredStringSelector, this.hoveredStringId )
                if( !hoveredStringIndex )
                    hoveredStringIndex = this.$parent.selectedString

                if( typeof this.dictionary[hoveredStringIndex] != 'undefined' && this.dictionary[hoveredStringIndex].block_type == 1 )
                    return 'split'

                let self = this
                let parentNode, childNodes, incompatibleSiblings

                let action = 'none'

                //check if target is the correct object type
                this.mergeRules.self_object_type.forEach( function( thisObjectType ) {

                    if( target.tagName.toLowerCase() == thisObjectType ) {
                        //get parent based on merge rules
                        parentNode = target.closest( self.mergeRules.top_parents )

                        if( parentNode != null ) {
                            //get childrens that are of the correct type based on parent,
                            self.mergeRules.self_object_type.forEach( function( selfObjectType ) {
                                childNodes = parentNode.querySelectorAll( selfObjectType )

                                if( childNodes.length > 1 ) {
                                    //check if between the children we have incompatible siblings (gettext or dynamic strings)
                                    incompatibleSiblings = parentNode.querySelectorAll( self.mergeRules.incompatible_siblings )

                                    if ( incompatibleSiblings.length == 0 )
                                        action = 'merge'
                                }
                            })
                        }
                    }
                })

                return action
            },
            stripEditorData( target ){
                let copy = target.cloneNode( true )
                let self = this

                let buttons = copy.querySelector( 'trp-span' )

                if( buttons )
                    buttons.remove()

                /** In case we are in secondary language and the strings that will be merged are already translated,
                 *  we must use the originals of these strings instead of what is in the preview iframe HTML page at this point
                 */
                if ( this.settings['default-language'] != this.currentLanguage ){
                    copy.querySelectorAll( '[data-trp-translate-id]' ).forEach( function( node ) {
                        let stringId = node.getAttribute( 'data-trp-translate-id' )
                        let index = self.$parent.getStringIndex( 'data-trp-translate-id', stringId )
                        if ( self.dictionary[index].translationsArray[self.currentLanguage] && self.dictionary[index].translationsArray[self.currentLanguage].status != 0 ) {
                            node.innerHTML = node.innerText.replace( self.dictionary[index].translationsArray[self.currentLanguage].translated, self.dictionary[index].original )
                        }
                    })
                }

                copy.querySelectorAll( 'translate-press, trp-wrap, trp-highlight' ).forEach( function( node ) {
                    utils.unwrap( node )
                })

                let attributesToReplace = [ 'href', 'target' ]

                attributesToReplace.forEach( function( attribute ) {
                    copy.querySelectorAll( '[data-trp-original-' + attribute + ']' ).forEach( function( node ) {
                        let dataTrpOriginalAttribute = 'data-trp-original-' + attribute;
                        node.setAttribute( attribute, node.getAttribute( dataTrpOriginalAttribute ) )
                        node.removeAttribute(dataTrpOriginalAttribute)
                    })
                })

                let node
                let otherAttributes = [ 'data-trp-placeholder', 'data-trp-unpreviewable' ]
                let attributesToRemove = otherAttributes.concat( self.$parent.prepareSelectorStrings( 'data-trp-translate-id' ), self.$parent.prepareSelectorStrings( 'data-trp-node-group' ), self.$parent.prepareSelectorStrings( 'data-trp-node-description' ) )

                attributesToRemove.forEach( function( attribute ) {
                    copy.querySelectorAll( '[' + attribute + ']' ).forEach( function( node ) {
                        node.removeAttribute( attribute )
                    })
                })

                return copy.innerHTML

            },
            removeHighlight( removeFromBlocks = true ){
                let previouslyHighlighted = this.iframe.getElementsByClassName( 'trp-highlight' )

                if( previouslyHighlighted.length > 0 ) {
                    let i

                    for ( i = 0; i < previouslyHighlighted.length; i++ ) {

                        if ( removeFromBlocks )
                            previouslyHighlighted[i].classList.remove( 'trp-highlight' )
                        else if ( !removeFromBlocks && !previouslyHighlighted[i].classList.contains( 'trp-create-translation-block' ) )
                            previouslyHighlighted[i].classList.remove( 'trp-highlight' )
                    }
                }

                return true
            },
            fitPencilIconInsideView( pencil, target, mergeOrSplit ){
                // 'slick-slide-image' is a fix for elementor image slider to display pencil icon
                // 'attachment-woocommerce_thumbnail' - is a fix for WooCommerce product images on shop page (Hestia theme and others)
                // 'woocommerce-placeholder' - is a fix for WooCommerce product placeholder image on shop page (Hestia theme and others)
                let forcePencilDisplayClasses = ['slick-slide-image', 'attachment-woocommerce_thumbnail', 'woocommerce-placeholder']
                let forcePencilDisplay = false
                if ( target.tagName === 'IMG' ){
                    let i
                    for ( i = 0; i < forcePencilDisplayClasses.length; i++ ){
                        if ( target.classList.contains( forcePencilDisplayClasses[i] ) ) {
                            forcePencilDisplay = true
                            break;
                        }
                    }
                }

                let rect = target.getBoundingClientRect()
                if( forcePencilDisplay || rect.left < 35 ) {
                    let margin

                    if( mergeOrSplit != 'none' )
                        margin = 60
                    else
                        margin = 30

                    pencil.setAttribute( 'style', 'margin-left: ' + margin + 'px !important' )
                }
            },
            getTrpSpan() {
                return '<trp-span><trp-merge title="'+ this.editorStrings.merge +'" class="trp-icon trp-merge dashicons dashicons-arrow-up-alt"></trp-merge><trp-split title="'+ this.editorStrings.split +'" class="trp-icon trp-split dashicons dashicons-arrow-down-alt"></trp-split><trp-edit title="'+ this.editorStrings.edit +'" class="trp-icon trp-edit-translation dashicons dashicons-edit"></trp-edit></trp-span>'
            }
        }
    }
</script>
