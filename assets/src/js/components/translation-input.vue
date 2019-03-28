<template>
    <div class="translation-input" :class="{'trp-highlight-unsaved-changes':highlightUnsavedChanges}">
        <div class="trp-attribute-name" v-show="attributeName != 'Content'">
            {{attributeName}}
        </div>
        <div v-if="inputType == 'textarea'" class="trp-translation-input-parent">
            <textarea class="trp-translation-input trp-textarea" :readonly="readonly" ref="textarea" :value="value" @input="updateValue()"></textarea>
        </div>
        <div v-if="inputType == 'input'" class="trp-translation-input-parent">
            <input class="trp-translation-input trp-input" :readonly="readonly" ref="input" :value="value" @input="updateValue()">
        </div>
        <div v-if="inputType == 'addmedia'" class="trp-translation-input-parent">
            <input type="button" class="trp-add-media" value="Add Media">
            <input class="trp-translation-input trp-input trp-media" :readonly="readonly" ref="addmedia" :value="value" @input="updateValue()">
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
            'href'        : 'input',
            'outertext'   : 'input',
            'value'       : 'input',
            'src'         : 'addmedia'
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
<style>
    .trp-attribute-name{
        padding-bottom: 3px;
    }
    .trp-translation-input-parent {
        padding-right: 9px;
        padding-bottom: 15px;
    }
    .trp-translation-input{
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
        font-size: 14px;
        max-width: 100%;
        min-width: 100%;
        width: 100%;
        padding: 3px;
        border: 1px solid  #aaa;
        border-radius:3px;
    }
    .trp-translation-input[readonly="readonly"]{
       background: #EBEBE4;
       border: 1px solid  #aaa;
       outline-width: 0;
   }

    .trp-textarea{
        height: 80px;
    }
    .trp-highlight-unsaved-changes .trp-translation-input{
        border:solid 2px red
    }

</style>