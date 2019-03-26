<template>
    <div id="trp-save-container">
        <span id="trp-translation-saved" style="display: none">Saved</span>
        <button id="trp-save" type="submit" class="button-primary trp-save-string" @click="save">Save translation</button>
    </div>
</template>
<script>
    import axios from 'axios'
    export default{
        props: [
            'selectedIndexesArray',
            'dictionary',
            'settings',
            'nonces',
            'ajax_url',
            'currentLanguage',
            'iframe',
            'currentURL'
        ],
        data(){
            return {

            }
        },
        methods:{
            save(){
                this.saveStringType( 'gettext' )
                this.saveStringType( 'regular' )
            },
            saveStringType( typeSlug ){
                let self = this
                let saveData = {}
                let updateIframeData  = {}
                let foundStringsToSave = false
                this.selectedIndexesArray.forEach( function( selectedIndex ){
                    if ( typeSlug === self.dictionary[selectedIndex].type ) {
                        self.settings['translation-languages'].forEach( function( languageCode  ){

                            saveData[languageCode] = ( saveData[languageCode] ) ? saveData[languageCode] : []
                            updateIframeData[languageCode] = ( updateIframeData[languageCode] ) ? updateIframeData[languageCode] : []

                            if ( self.dictionary[selectedIndex].translationsArray[languageCode] && (self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation != self.dictionary[selectedIndex].translationsArray[languageCode].translated ) ) {
                                if ( self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation === '' ) {
                                    self.dictionary[selectedIndex].translationsArray[languageCode].status = 0  //set as not translated
                                }else{
                                    self.dictionary[selectedIndex].translationsArray[languageCode].status = 2  //set as human reviewed
                                }
                                self.dictionary[selectedIndex].translationsArray[languageCode].translated = self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation

                                saveData[languageCode].push( self.dictionary[selectedIndex].translationsArray[languageCode] )
                                updateIframeData[languageCode].push( self.dictionary[selectedIndex] )

                                foundStringsToSave = true
                            }
                        })
                    }
                })

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
                                })
                            }else {
                                self.updateIframe(updateIframeData)
                            }
                        })
                        .catch(function (error) {
                            console.log(error)
                        });
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
                    if (typeof string.attribute === 'undefined' || string.attribute === "") {
                        let initialValue = node.textContent;
                        textToSet = initialValue.replace(initialValue.trim(), textToSet);
                        node.innerHTML = textToSet
                    } else {
                        let initialValue = node.getAttribute(string.attribute)
                        textToSet = initialValue.replace(initialValue.trim(), textToSet)
                        node.setAttribute(string.attribute, textToSet)
                    }
                })
            }
        }
    }
</script>