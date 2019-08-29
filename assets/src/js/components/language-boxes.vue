<template>
    <div id="trp-translation-section" class="trp-controls-section-content" v-if="selectedIndexesArray">
        <div v-show="showChangesUnsavedMessage" class="trp-changes-unsaved-message">{{ editorStrings.unsaved_changes }} <span class="trp-unsaved-changes trp-discard-changes "@click="discardAll" :title="editorStrings.discard_all_title_attr">{{ editorStrings.discard_all }}</span>?</div>
        <div v-for="(languageCode, key) in languages" :id="'trp-language-' + languageCode">
            <div v-show="( (key <= othersButtonPosition) || showOtherLanguages ) && ( selectedIndexesArray && selectedIndexesArray.length > 0 )"  class="trp-language-container">
                <div class="trp-language-name">
                    <span v-if="key == 0 ">{{ editorStrings.from }} </span>
                    <span v-else>{{ editorStrings.to }} </span>
                    {{ completeLanguageNames[languageCode] }}
                    <img v-if="languageCode != 'original'" class="trp-language-box-flag-image" :src="flagsPath[languageCode] + '/' + languageCode + '.png'" width="18" height="12" :alt="languageCode" :title="completeLanguageNames[languageCode]">
                </div>
                <table class="trp-translations-for-language">
                    <td class="trp-translation-icon-container" v-if="showImageIcon">
                        <span class="trp-translation-icon dashicons dashicons-format-image"></span>
                    </td>
                    <td class="trp-translations-container">
                        <div class="trp-string-container" v-for="selectedIndex in selectedIndexesArray">
                            <div v-if="dictionary[selectedIndex] && dictionary[selectedIndex].translationsArray[languageCode]" :key="selectedIndex">
                                <translation-input :string="dictionary[selectedIndex]" v-model="dictionary[selectedIndex].translationsArray[languageCode].editedTranslation" :highlightUnsavedChanges="showChangesUnsavedMessage && hasUnsavedChanges( selectedIndex, languageCode )" :editorStrings="editorStrings"></translation-input>
                            </div>
                            <div v-else-if="dictionary[selectedIndex]" :key="selectedIndex">
                                <translation-input :readonly="true" :string="dictionary[selectedIndex]" :value="dictionary[selectedIndex].original" :editorStrings="editorStrings"></translation-input>
                            </div>
                            <div class="trp-translation-input-footer" :data-dictionary-entry="JSON.stringify(dictionary[selectedIndex])">
                                <div class="trp-attribute-name"  v-show="dictionary[selectedIndex].attribute != 'content' || dictionary[selectedIndex].attribute != ''">{{ ( editorStrings[ dictionary[selectedIndex].attribute ] ) ? editorStrings[ dictionary[selectedIndex].attribute ] : editorStrings.text }}</div>
                                <div v-if="dictionary[selectedIndex] && dictionary[selectedIndex].translationsArray[languageCode]" class="trp-discard-changes trp-discard-individual-changes" @click="discardChanges(selectedIndex,languageCode)" :class="{'trp-unsaved-changes': hasUnsavedChanges( selectedIndex, languageCode ) }" :title="editorStrings.discard_individual_changes_title_attribute">{{ editorStrings.discard }}</div>
                            </div>
                            <div class="trp-translation-memory-wrap" v-if="dictionary[selectedIndex] && dictionary[selectedIndex].translationsArray[languageCode]" :key="'trp_tmw_' + selectedIndex">
                                <translation-memory :string="dictionary[selectedIndex]" :editorStrings="editorStrings" :ajax_url="ajax_url" :nonces="nonces" :languageCode="languageCode"></translation-memory>
                            </div>
                        </div>
                    </td>
                </table>
                <div v-show="key == othersButtonPosition">
                    <div class="trp-toggle-languages" @click="showOtherLanguages = !showOtherLanguages" :class="{ 'trp-show-other-languages': showOtherLanguages, 'trp-hide-other-languages': !showOtherLanguages }">
                        <span>{{ (showOtherLanguages)? '&#11206;' : '&#11208;' }} {{ editorStrings.other_lang }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import translationInput from './translation-input.vue'
    import translationMemory from './translation-memory.vue'
    export default{
        props:[
            'selectedIndexesArray',
            'dictionary',
            'currentLanguage',
            'onScreenLanguage',
            'languageNames',
            'settings',
            'showChangesUnsavedMessage',
            'editorStrings',
            'flagsPath',
            'iframe',
            'nonces',
            'ajax_url'
        ],
        data(){
            return{
                languages                  : [],
                completeLanguageNames      : Object.assign( { 'original': 'Original String' }, this.languageNames ),
                othersButtonPositionOffset : 1,
                showOtherLanguages         : false,
                orderedLanguages           : [],
                showImageIcon              : true
            }
        },
        components:{
            translationInput,
            translationMemory
        },
        mounted(){
            this.determineLanguageOrder()
            this.addKeyboardShortcutsListener()
        },
        updated(){
            // if already active do nothing
            if ( document.activeElement.classList.contains( 'trp-translation-input' )){
                return
            }
            // place the cursor in the first textarea or input for translation
            let translationSection = document.getElementById( 'trp-translation-section' )
            if ( translationSection )  {
                let focusableSelectors = ['textarea:not([readonly])', 'input[type="text"]:not([readonly])']
                for ( var i = 0; i<focusableSelectors.length; i++ ){
                    let focusable = document.getElementById( 'trp-translation-section' ).querySelector(focusableSelectors[i])
                    if ( focusable ) {
                        focusable.focus()
                        break;
                    }
                }
            }
        },
        watch: {
            selectedIndexesArray: function () {
                this.updateLanguages()
            },
            onScreenLanguage: function(){
                this.determineLanguageOrder()
                this.updateLanguages()
            }
        },
        computed:{
            othersButtonPosition: function (){
                if (this.currentLanguage === this.settings['default-language'] || this.settings['translation-languages'].length <= 2 ) {
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
                if ( this.onScreenLanguage !== '' )
                    this.orderedLanguages.push( this.onScreenLanguage )
                this.orderedLanguages = this.orderedLanguages.concat( filteredLanguages )
            },
            updateLanguages: function () {
                this.languages                  = []
                let self                        = this
                let defaultLanguage             = this.settings['default-language']
                let translateToDefault          = false
                this.showImageIcon              = false
                this.othersButtonPositionOffset = 1

                this.selectedIndexesArray.forEach(function (selectedIndex) {
                    if( self.dictionary[selectedIndex] && self.dictionary[selectedIndex].translationsArray[defaultLanguage] )
                        translateToDefault = true
                    if( self.dictionary[selectedIndex] && self.dictionary[selectedIndex].attribute === 'src' )
                        self.showImageIcon = true
                })

                if (translateToDefault) {
                    this.languages.push('original')
                    this.othersButtonPositionOffset++
                }

                this.languages = this.languages.concat(this.orderedLanguages)
            },
            discardChanges: function(selectedIndex,languageCode){
                this.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation = this.dictionary[selectedIndex].translationsArray[languageCode].translated
                this.$emit('discarded-changes')
            },
            hasUnsavedChanges: function(selectedIndex, languageCode){
                return (this.dictionary[selectedIndex].translationsArray[languageCode].translated !== this.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation)
            },
            discardAll: function(){
                let self = this
                this.selectedIndexesArray.forEach(function(selectedIndex){
                    self.settings['translation-languages'].forEach( function( languageCode  ) {
                        if ( self.dictionary[selectedIndex].translationsArray[languageCode] &&
                            (self.dictionary[selectedIndex].translationsArray[languageCode].translated !== self.dictionary[selectedIndex].translationsArray[languageCode].editedTranslation) ) {
                            self.discardChanges(selectedIndex,languageCode)
                        }
                    })
                })

                if ( this.$parent.mergingString === true ){
                    this.$parent.selectedString = null
                    let previouslyHighlighted = this.iframe.getElementsByClassName( 'trp-create-translation-block' )
                    if( previouslyHighlighted.length > 0 ) {
                        let i
                        for ( i = 0; i < previouslyHighlighted.length; i++ ) {
                            previouslyHighlighted[i].classList.remove('trp-highlight')
                            previouslyHighlighted[i].classList.remove('trp-create-translation-block')
                        }
                    }
                    this.$parent.mergingString = false
                    this.$parent.mergeData = []
                }
            },
            addKeyboardShortcutsListener(){
                document.addEventListener("keydown", function(e) {
                    // CTRL + ALT + Z
                    if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey) && e.altKey && e.keyCode === 90 ) {
                        e.preventDefault();
                        window.dispatchEvent(new Event('trp_trigger_discard_all_changes_event'));
                    }
                }, false);

                window.addEventListener( 'trp_trigger_discard_all_changes_event', this.discardAll )
            }
        }
    }
</script>
