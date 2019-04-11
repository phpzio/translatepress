<template>
    <div class="translation-input" :class="{'trp-highlight-unsaved-changes':highlightUnsavedChanges}">
        <div class="trp-translation-input-header" v-show="attributeName != ''">
            <div class="trp-translation-input-header-item" :class="{'trp-no-media': inputType != 'addmedia'}">
                <div class="trp-attribute-name"  v-show="attributeName != 'Content'">
                    {{attributeName}}
                </div>
            </div>
            <div class="trp-translation-input-header-item">
                <input v-show="inputType == 'addmedia'" type="button" class="trp-add-media" value="Add Media">
            </div>
        </div>
        <div v-if="inputType == 'textarea'" class="trp-translation-input-parent">
            <textarea class="trp-translation-input trp-textarea" :readonly="readonly" ref="textarea" :value="value" @input="updateValue()"></textarea>
        </div>
        <div v-if="inputType == 'input' || inputType == 'addmedia' " class="trp-translation-input-parent">
            <input class="trp-translation-input trp-input" :class="{'trp-media' : inputType == 'addmedia' }" :readonly="readonly" ref="input" :value="value" @input="updateValue()">
        </div>
    </div>
</template>
<script>
export default{
    props:[
        'value',
        'string',
        'translated',
        'disabled',
        'readonly',
        'editedValue',
        'highlightUnsavedChanges'
    ],
    data(){
        return{
            inputType      : 'textarea',
            attributeName  : this.string.attribute.charAt(0).toUpperCase() + this.string.attribute.slice(1),
        }
    },
    mounted(){
        let inputTypeArray = {
            ''            : 'textarea',
            'content'     : 'textarea',
            'alt'         : 'textarea',
            'title'       : 'textarea',
            'placeholder' : 'textarea',
            'outertext'   : 'input',
            'value'       : 'input',
            'src'         : 'addmedia',
            'href'        : 'addmedia'
        };
        this.inputType = ( inputTypeArray[this.string.attribute] ) ? inputTypeArray[this.string.attribute] : 'textarea'
    },
    methods:{
        updateValue(){
            this.$emit( 'input', this.$refs[this.inputType].value )
        }
    }
}
</script>
