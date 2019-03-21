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
            'ajax_url'
//            'currentLanguage',
//            'onScreenLanguage',
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
                let foundStringsToSave = false
                this.selectedIndexesArray.forEach( function( selectedIndex ){
                    if ( typeSlug === self.dictionary[selectedIndex].type ) {
                        self.settings['translation-languages'].forEach( function( languageCode  ){
                            saveData[languageCode] = []
                            if ( self.dictionary[selectedIndex].translationsArray[languageCode] && (self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation != self.dictionary[selectedIndex].translationsArray[languageCode].translated ) ) {
                                if ( self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation === '' ) {
                                    //set as not translated
                                    self.dictionary[selectedIndex].translationsArray[languageCode].status = 0
                                }else{
                                    //set as human reviewed
                                    self.dictionary[selectedIndex].translationsArray[languageCode].status = 2
                                }
                                self.dictionary[selectedIndex].translationsArray[languageCode].translated = self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation
                                saveData[languageCode].push( self.dictionary[selectedIndex].translationsArray[languageCode] )

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
                            //self.updateIframe(response.data, nodeData)
                        })
                        .catch(function (error) {
                            console.log(error);
                        });
                }
            }
        }
    }
</script>