<template>
    <div id="trp-save-container">
        <span id="trp-translation-saved" style="display:none">{{ editorStrings.saved }}</span>
        <button id="trp-save" :disabled="disabledSaveButton" type="submit" class="button-primary trp-save-string" @click="save" :title="editorStrings.save_title_attr">{{ saveButtonText }}</button>
    </div>
</template>
<script>
    import axios from 'axios'

    export default{
        props: [
            'selectedIndexesArray',
            'selectedString',
            'dictionary',
            'settings',
            'nonces',
            'ajax_url',
            'currentLanguage',
            'onScreenLanguage',
            'iframe',
            'currentURL',
            'mergingString',
            'mergeData',
            'editorStrings'
        ],
        data(){
            return {
                'saveButtonText'            : this.editorStrings.save_translation,
                'saveStringsRequestsLeft'   : 0,
                'disabledSaveButton'        : false,
            }
        },
        mounted(){
            this.addKeyboardShortcutsListener()
        },
        watch:{
            saveStringsRequestsLeft : function( newValue, oldValue ){
                if ( newValue > 0 ) {
                    this.disabledSaveButton = true
                    this.saveButtonText = this.editorStrings.saving_translation
                }else{
                    this.disabledSaveButton = false
                    this.saveButtonText = this.editorStrings.save_translation

                    this.showTranslationsSaved()
                }
            }
        },
        methods:{
            save(){
                if ( this.mergingString )
                    this.createTranslationBlock()
                else {
                    this.saveStringType( 'gettext' )
                    this.saveStringType( 'regular' )
                    this.saveStringType( 'postslug' )
                }
                if ( this.saveStringsRequestsLeft === 0 ) {
                    // no saving action was triggered
                    this.showTranslationsSaved()
                }
            },
            saveStringType( typeSlug ){
                this.saveStringsRequestsLeft++
                let self = this
                let saveData = {}
                let updateIframeData  = {}
                let foundStringsToSave = false

                // construct an array of the necessary information
                this.selectedIndexesArray.forEach( function( selectedIndex ){
                    if ( typeSlug === self.dictionary[selectedIndex].type ) {
                        self.settings['translation-languages'].forEach( function( languageCode  ){

                            saveData[languageCode] = ( saveData[languageCode] ) ? saveData[languageCode] : []
                            updateIframeData[languageCode] = ( updateIframeData[languageCode] ) ? updateIframeData[languageCode] : []

                            if ( self.dictionary[selectedIndex].translationsArray[languageCode] && (self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation != self.dictionary[selectedIndex].translationsArray[languageCode].translated ) ) {
                                self.dictionary[selectedIndex].translationsArray[languageCode].status = ( self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation === '' ) ? 0 : 2
                                self.dictionary[selectedIndex].translationsArray[languageCode].translated = self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation

                                saveData[languageCode].push( self.dictionary[selectedIndex].translationsArray[languageCode] )
                                updateIframeData[languageCode].push( self.dictionary[selectedIndex] )

                                foundStringsToSave = true
                            }
                        })
                    }
                })

                // send request to save strings in database
                if ( foundStringsToSave ) {
                    let data = new FormData()
                        data.append('action', 'trp_save_translations_' + typeSlug)
                        data.append('security', this.nonces['savetranslationsnonce' + typeSlug])
                        data.append('strings', JSON.stringify(saveData))

                    axios.post(this.ajax_url, data)
                        .then(function (response) {
                            if ( typeSlug === 'gettext' ) {
                                axios.get(self.currentURL).then( function( reloadedIframeResponse) {
                                    self.updateIframe(updateIframeData, reloadedIframeResponse.data)
                                    self.saveStringsRequestsLeft--
                                })
                            }else {
                                self.updateIframe(updateIframeData)
                                self.saveStringsRequestsLeft--
                            }
                            self.$emit('translations-saved')
                        })
                        .catch(function (error) {
                            console.log(error)
                        });
                }else{
                    self.saveStringsRequestsLeft--
                }
            },
            updateIframe( updateIframeData, reloadedIframeResponse = null ){
                let self = this
                this.settings['translation-languages'].forEach( function( languageCode  ){
                    if ( updateIframeData[languageCode].length > 0 ){
                        updateIframeData[languageCode].forEach(function( string ){
                            if ( self.currentLanguage === languageCode ) {
                                self.setTextInIframe( string, languageCode, reloadedIframeResponse )
                            }
                        })
                    }
                })
            },
            setTextInIframe( string, languageCode, reloadedIframeResponse ){
                let nodes = this.iframe.querySelectorAll( "[" + string.selector + "='" + string.dbID + "']" )
                let textToSet = null
                if ( reloadedIframeResponse ){
                    let translatedNode = document.createRange().createContextualFragment(reloadedIframeResponse).querySelector( "[" + string.selector + "='" + string.dbID + "']" )
                    if ( translatedNode ) {
                        textToSet = (typeof string.attribute === 'undefined' || string.attribute === "") ? translatedNode.textContent : translatedNode.getAttribute(string.attribute)
                    }
                }
                if ( textToSet === null ) {
                    textToSet = ( string.translationsArray[languageCode].translated === '' ) ? string.original : string.translationsArray[languageCode].translated
                }

                nodes.forEach(function(node){
                    if (typeof string.attribute === 'undefined' || string.attribute === "" || string.attribute === 'innertext') {
                        let initialValue = node.textContent;
                        textToSet = initialValue.replace(initialValue.trim(), textToSet);
                        node.innerHTML = textToSet
                    } else {
                        let initialValue = node.getAttribute(string.attribute)
                        textToSet = initialValue.replace(initialValue.trim(), textToSet)
                        node.setAttribute(string.attribute, textToSet)
                        if( string.attribute === 'src' ){
                            node.setAttribute('srcset', '')
                        }
                    }
                })
            },
            createTranslationBlock() {
                this.saveStringsRequestsLeft++
                let self = this
                let saveData = {}, translation = {}, original
                let foundStringsToSave = false

                this.selectedIndexesArray.forEach( function( selectedIndex ){
                    self.settings['translation-languages'].forEach( function( languageCode  ){
                        saveData[languageCode] = ( saveData[languageCode] ) ? saveData[languageCode] : []

                        if( self.dictionary[selectedIndex] && self.dictionary[selectedIndex].translationsArray[languageCode] ) {

                            translation = self.dictionary[selectedIndex].translationsArray[languageCode]

                            translation.block_type = self.dictionary[selectedIndex].block_type
                            translation.id         = self.dictionary[selectedIndex].dbID
                            translation.original   = self.dictionary[selectedIndex].original

                            if( self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation != self.dictionary[selectedIndex].translationsArray[languageCode].translated ) {
                                self.dictionary[selectedIndex].translationsArray[languageCode].translated = self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation

                                if( self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation !== '' )
                                    self.dictionary[selectedIndex].translationsArray[languageCode].status = 2
                            }

                            saveData[languageCode].push( translation )


                            foundStringsToSave = true
                        }
                    })

                    original = self.dictionary[selectedIndex].original
                })

                if( foundStringsToSave ) {
                    let data = new FormData()
                        data.append( 'action'       , 'trp_create_translation_block' )
                        data.append( 'security'     , this.nonces['mergetbnonce'] )
                        data.append( 'language'     , this.currentLanguage )
                        data.append( 'strings'      , JSON.stringify( saveData ) )
                        data.append( 'original'     , original )
                        data.append( 'all_languages', 'true' )

                    axios.post(this.ajax_url, data)
                        .then(function (response) {
                            self.saveStringsRequestsLeft--
                            self.$parent.mergingString = false
                            let item = self.dictionary[self.selectedIndexesArray[0]]

                            //update dictionary string ids
                            Object.keys( item.translationsArray ).forEach( function(key) {
                                Object.keys( response.data[key] ).forEach( function(index) {
                                    if ( key === self.onScreenLanguage ){
                                        self.dictionary[self.selectedIndexesArray[0]].dbID = response.data[key][index].id
                                    }
                                    item.translationsArray[key].id = response.data[key][index].id
                                })
                            })

                            self.$parent.mergeData = []

                            //get merged string
                            let mergedString

                            if( typeof item.translationsArray[self.currentLanguage] !== 'undefined' && item.translationsArray[self.currentLanguage].translated )
                                mergedString = item.translationsArray[self.onScreenLanguage].translated
                            else
                                mergedString = item.original

                            //replace HTML in iFrame
                            let translationBlock = self.iframe.querySelector( '.trp-create-translation-block' )
                                translationBlock.innerHTML = mergedString
                                translationBlock.setAttribute( 'data-trp-translate-id', item.dbID )
                                translationBlock.classList.remove( 'trp-create-translation-block' )

                            //setup event listener for new block
                            self.$parent.setupEventListener( translationBlock )
                        })
                        .catch(function (error) {
                            self.$parent.mergingString = false
                            console.log(error)
                        });
                }else{
                    this.saveStringsRequestsLeft--
                }
            },
            showTranslationsSaved : function(){
                let translationSaved = jQuery('#trp-translation-saved')
                translationSaved.css("display", "inline")
                translationSaved.delay(3000).fadeOut(400)
            },
            addKeyboardShortcutsListener(){
                document.addEventListener("keydown", function(e) {

                    // CTRL + S
                    if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)  && e.keyCode === 83) {
                        e.preventDefault();

                        window.dispatchEvent( new Event( 'trp_trigger_save_translations_event' ) );
                    }
                }, false);

                window.addEventListener( 'trp_trigger_save_translations_event', this.save )
            }

        }
    }
</script>
