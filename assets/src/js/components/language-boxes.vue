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
                            <translation-input :string="dictionary[selectedIndex]" v-model="dictionary[selectedIndex].translationsArray[languageCode].translated"></translation-input>
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
                    <!--<div id="trp-hide-all-languages" class="trp-toggle-languages trp-toggle-languages-active"><span> Other languages</span></div>-->
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
    import translationInput from './translation-input.vue'
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
                completeLanguageNames : Object.assign( { 'original': 'Original String' }, this.languageNames ),
                othersButtonPositionOffset: 1,
                showOtherLanguages : false
            }
        },
        components:{
            translationInput
        },
        watch: {
            selectedIndexesArray: function () {
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
                this.languages.push(defaultLanguage)
                this.languages = this.languages.concat(this.orderedSecondaryLanguages)

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
    }
</script>

<style>
    .trp-language-name{
        padding-bottom: 10px;
        color: black;
    }
</style>
