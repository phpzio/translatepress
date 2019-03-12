<template>
    <div class="translation-input">
        <div class="trp-attribute-name">
            {{attributeName}}
        </div>
        <div v-if="inputType == 'textarea'" class="trp-translation-input-parent">
            <textarea class="trp-translation-input trp-textarea" :readonly="readonly" ref="textarea" :value="value" @input="updateValue()"></textarea>
            <div v-if="!readonly" class="trp-discard-changes">Discard changes</div>
        </div>
        <div v-if="inputType == 'input'" class="trp-translation-input-parent">
            <input class="trp-translation-input trp-input" :readonly="readonly" ref="input" :value="value" @input="updateValue()">
            <div v-if="!readonly" class="trp-discard-changes">Discard changes</div>
        </div>

    </div>
</template>
<script>
export default{
    props:[
        'value',
        'string',
        'disabled',
        'readonly'
    ],
    data(){
        return{
            inputType     : 'textarea',
            attributeName : this.string.attribute.charAt(0).toUpperCase() + this.string.attribute.slice(1)
        }
    },
    mounted(){
        let inputTypeArray = {
                ''        : 'textarea',
                'content' : 'textarea',
                'alt'     : 'textarea',
                'title'   : 'textarea',
                'href'    : 'input',
                'src'     : 'input',
        };
        this.inputType = inputTypeArray[this.string.attribute]
    },
    methods:{
        updateValue(){
            this.$emit( 'input', this.$refs[inputType].value )
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

    .trp-discard-changes{
        color: darkgrey;
        font-size: 11px;
        float: right;
        user-select: none;
    }

    .trp-unsaved-changes .trp-discard-changes{
        color: #a00;
        cursor: pointer;
        text-decoration: underline;
    }
    .trp-unsaved-changes .trp-discard-changes:hover{
        color: #dc3232;
        cursor: pointer;
    }

</style>