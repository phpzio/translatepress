<template>
    <div id="trp-translation-section" class="trp-controls-section-content" v-if="selectedIndexesArray">
        <div v-for="(languageCode, key) in languages" :id="'trp-language-' + languageCode" class="trp-language-container">
            <div class="trp-language-name">
                <span v-if="key == 0 ">From </span>
                <span v-else>To </span>
                {{ completeLanguageNames[languageCode] }}
            </div>
            <div class="trp-translations-container">
                <div class="trp-string-container" v-for="selectedIndex in selectedIndexesArray">
                    <div v-if="dictionary[selectedIndex].translationsArray[languageCode]">
                        <textarea v-model="dictionary[selectedIndex].translationsArray[languageCode].translated"></textarea>
                    </div>
                    <div v-else>
                        <textarea :value="dictionary[selectedIndex].original"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!--<div id="trp-unsaved-changes-warning-message" style="display:none">You have unsaved changes!</div>-->

        <!--<div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">-->
            <!--<div class="trp-language-name">Original String</div>-->
            <!--<textarea id="trp-gettext-original-textarea" readonly="readonly"></textarea>-->
        <!--</div>-->

        <!--<div :id="'trp-language-' + settings['default-language']" class="trp-language-text trp-default-language">-->
            <!--<div class="trp-language-name" :data-trp-gettext-language-name="'To ' + settings['default-language-name']" :data-trp-default-language-name="'From ' + settings['default-language-name']">-->
                <!--{{ 'From ' + settings['default-language-name'] }}-->
            <!--</div>-->

            <!--<textarea id="trp-original" :data-trp-language-code="settings['default-language']" readonly="readonly"></textarea>-->

            <!--<div class="trp-discard-changes trp-discard-on-default-language" style="display:none;">Discard changes</div>-->
        <!--</div>-->
    <!--<div>-->
        <!--<textarea v-model="string">{{string}}</textarea>-->
        <!--<textarea v-model="selectedarray[0]">{{selectedarray[0]}}</textarea>-->
    <!--</div>-->
</template>

<script>
    export default{
        props:[
            'selectedIndexesArray',
            'dictionary',
            'languageNames',
            'currentLanguage',
            'settings',
            'orderedSecondaryLanguages'
        ],
        data(){
            return{
                languages  : [],
                completeLanguageNames : Object.assign( { 'original': 'Original String' }, this.languageNames )
            }
        },
        watch:{
            selectedIndexesArray: function(){
                this.languages = []
                let self = this
                let defaultLanguage = this.settings['default-language']
                let translateToDefault = false

                this.selectedIndexesArray.forEach(function( selectedIndex ){
                    if ( self.dictionary[selectedIndex].translationsArray[defaultLanguage] ){
                        translateToDefault = true
                    }
                })

                if ( translateToDefault ) {
                    this.languages.push('original')
                }
                this.languages.push(defaultLanguage)
                this.languages = this.languages.concat(this.orderedSecondaryLanguages)
            }
        },
    }
</script>

<style>

</style>