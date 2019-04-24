<template>
    <div class="trp-controls-section">

        <div id="trp-translation-section" class="trp-controls-section-content" v-if="showLanguagesMessage()">
            <p v-html="editorStrings.extra_lang_row1"></p>
            <p v-html="editorStrings.extra_lang_row2"></p>
            <p v-html="editorStrings.extra_lang_row3"></p>
        </div>

    </div>
</template>
<script>
export default{
    props:[
        'languageNames',
        'editorStrings'
    ],
    methods:{
        showLanguagesMessage(){
            if( Object.keys( this.languageNames ).length == 1 )
                return true

            return false
        }
    }
}
</script>
