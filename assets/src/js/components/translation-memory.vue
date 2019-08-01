<template>
    <div>
        <p class="trp-no-available-suggestions" v-show="!available_suggestions">No available suggestions</p>
        <transition name="fade">
            <details open="open" v-show="available_suggestions">
                <summary>Suggestions From Translation Memory</summary>
                <div class="trp-translation-memory-suggestions">
                    <ul>
                        <li v-for="(suggestion, index) in suggestions" @click="copy(suggestion.translated)" :key="index" title="Click to Copy">
                            <span class="percentage"><span>{{suggestion.similarity}}%</span></span>
                            <span class="translated">{{suggestion.translated}}</span>
                            <span class="original" v-html="suggestion.original"></span>
                        </li>
                    </ul>
                </div>
            </details>
        </transition>
    </div>
</template>
<script>
    import axios from 'axios'
    import autosize from 'autosize'
    import simplediff from 'simplediff'
    import similarity from 'string-similarity'


    export default{
        props:[
            'value',
            'string',
            'editorStrings',
            'ajax_url',
            'nonces',
            'languageCode',
            'inputValue'
        ],
        data(){
            return{
                suggestions : [],
                available_suggestions : false,
                similarity : 0,
                currentstring : this.string,
            }
        },
        mounted(){
            this.init()
        },
        methods:{
            init(){
                let data = new FormData()
                data.append('action'            , 'trp_get_similar_string_translation')
                data.append('security'          , this.nonces['getsimilarstring'])
                data.append('original_string'   , this.string.original)
                data.append('language'          , this.languageCode)
                data.append('selector'          , this.string.selector)
                data.append('number'            , 3)

                let self = this
                axios.post(this.ajax_url, data)
                    .then(function (response) {
                        let suggestions = response.data
                        let i

                        for (i = suggestions.length - 1; i >= 0; --i) {
                            suggestions[i]['similarity'] = Math.round(similarity.compareTwoStrings(self.string.original,suggestions[i]['original'])*100)
                            suggestions[i]['original'] = simplediff.htmlDiff(self.string.original, suggestions[i]['original'])

                            if (suggestions[i]['similarity'] < 10 ) {
                                suggestions.splice(i, 1); // drop suggestions less then 70%
                            }
                        }

                        self.suggestions = suggestions
                        if (suggestions.length > 0){
                            self.available_suggestions = true
                        }
                    })
                    .catch(function (error) {
                        console.log(error)
                    });
            },
            copy(translated){
                this.currentstring.translationsArray[this.languageCode].editedTranslation = translated
                //autosize.update(document.querySelectorAll('.trp-textarea'))
                //console.log(document.querySelectorAll('.trp-textarea'))
            }
        }
    }
</script>
