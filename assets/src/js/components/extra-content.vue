<template>
    <div>
        <div class="trp-controls-section" v-if="showLanguagesMessage()">
            <div id="trp-translation-section" class="trp-controls-section-content">
                <p v-html="editorStrings.extra_lang_row1"></p>
                <p v-html="editorStrings.extra_lang_row2"></p>
                <p v-html="editorStrings.extra_lang_row3"></p>
            </div>
        </div>

        <div class="trp-controls-section wp-core-ui" v-if="showUpsellMessage()">
            <div id="trp-upsell-section" class="trp-controls-section-content">
                <h3>{{ editorStrings.extra_upsell_title }}</h3>
                <ul>
                    <li>{{ editorStrings.extra_upsell_row1 }}</li>
                    <li>{{ editorStrings.extra_upsell_row2 }}</li>
                    <li>{{ editorStrings.extra_upsell_row3 }}</li>
                    <li>{{ editorStrings.extra_upsell_row4 }}</li>
                    <li>{{ editorStrings.extra_upsell_row5 }}</li>
                    <li>{{ editorStrings.extra_upsell_row6 }}</li>
                    <li>{{ editorStrings.extra_upsell_row7 }}</li>
                </ul>
                <p><span style="background: #f5fb9d;">{{ editorStrings.extra_upsell_row8 }}</span></p>
                <p v-html="editorStrings.extra_upsell_button"></p>
            </div>
        </div>
    </div>
</template>
<script>
export default{
    props:[
        'languageNames',
        'editorStrings',
        'paidVersion'
    ],
    methods:{
        showLanguagesMessage(){
            if( Object.keys( this.languageNames ).length == 1 )
                return true

            return false
        },
        showUpsellMessage(){
            if( this.paidVersion != 'true' )
                return true

            return false
        }
    }
}
</script>
