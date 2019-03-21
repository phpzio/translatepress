<template>
    <div id="trp-translation-section" class="trp-controls-section-content" v-if="selectedIndexesArray">
        <div v-for="(languageCode, key) in languages" :id="'trp-language-' + languageCode" class="trp-language-container">
            <div v-show="(key <= othersButtonPosition) || showOtherLanguages">
                <div class="trp-language-name">
                    <span v-if="key == 0 ">From </span>
                    <span v-else>To </span>
                    {{ completeLanguageNames[languageCode] }}
                </div>
                <div class="trp-translations-container">
                    <div class="trp-string-container" v-for="selectedIndex in selectedIndexesArray">
                        <div v-if="dictionary[selectedIndex].translationsArray[languageCode]" :key="selectedIndex">
                            <translation-input :string="dictionary[selectedIndex]" v-model="dictionary[selectedIndex].translationsArray[languageCode].editedTranslation"></translation-input>
                        </div>
                        <div v-else :key="selectedIndex">
                            <translation-input :readonly="true" :string="dictionary[selectedIndex]" :value="dictionary[selectedIndex].original"></translation-input>
                        </div>
                    </div>
                </div>
                <div v-show="key == othersButtonPosition">
                    <div class="trp-toggle-languages" @click="showOtherLanguages = !showOtherLanguages" :class="{ 'trp-show-other-languages': showOtherLanguages, 'trp-hide-other-languages': !showOtherLanguages }">
                        <span>{{ (showOtherLanguages)? '&#11206;' : '&#11208;' }} Other languages</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import translationInput from './translation-input.vue'
    export default{
        props:[
            'selectedIndexesArray',
            'dictionary',
            'currentLanguage',
            'onScreenLanguage',
            'languageNames',
            'settings',
        ],
        data(){
            return{
                languages  : [],
                completeLanguageNames : Object.assign( { 'original': 'Original String' }, this.languageNames ),
                othersButtonPositionOffset: 1,
                showOtherLanguages : false,
                orderedLanguages : []
            }
        },
        components:{
            translationInput
        },
        mounted(){
            this.determineLanguageOrder()
        },
        watch: {
            selectedIndexesArray: function () {
                this.updateLanguages()
                let self = this
                this.selectedIndexesArray.forEach( function( selectedIndex ) {
                    self.settings['translation-languages'].forEach( function( languageCode  ){
                        if ( self.dictionary[selectedIndex].translationsArray[languageCode] ) {
                            self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation = self.dictionary[selectedIndex].translationsArray[languageCode].translated
                        }
                    })
                })
            },
            onScreenLanguage: function(){
                this.determineLanguageOrder()
                this.updateLanguages()
            }
        },
        computed:{
            othersButtonPosition: function (){
                if (this.currentLanguage === this.settings['default-language']) {
                    // don't display it
                    return 999
                }else{
                    return this.othersButtonPositionOffset
                }
            }
        },
        methods:{
            determineLanguageOrder: function () {
                let self = this
                let filteredLanguages = this.settings['translation-languages'].filter(function(language, index, array){
                    // all languages except default and current or on screen language.
                    return ( self.settings['default-language'] !== language ) && ( self.onScreenLanguage !== language )
                });
                this.orderedLanguages = []
                this.orderedLanguages.push( this.settings['default-language'] )
                this.orderedLanguages.push( this.onScreenLanguage )
                this.orderedLanguages = this.orderedLanguages.concat( filteredLanguages )
            },
            updateLanguages: function () {
                this.languages = []
                let self = this
                let defaultLanguage = this.settings['default-language']
                let translateToDefault = false
                this.othersButtonPositionOffset = 1

                this.selectedIndexesArray.forEach(function (selectedIndex) {
                    if (self.dictionary[selectedIndex].translationsArray[defaultLanguage]) {
                        translateToDefault = true
                    }
                })

                if (translateToDefault) {
                    this.languages.push('original')
                    this.othersButtonPositionOffset++
                }
                this.languages = this.languages.concat(this.orderedLanguages)

            }
        }
    }
</script>

<style>
    .trp-language-name{
        padding-bottom: 10px;
        color: black;
    }
</style>
