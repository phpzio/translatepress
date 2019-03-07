<template>
    <div class="translation-input">
        <div v-if="inputType == 'textarea'">
            <textarea :disabled="disabled" ref="textarea" :value="value" @input="updateValue()"></textarea>
        </div>
        <div v-if="inputType == 'input'">
            <input :disabled="disabled" ref="input" :value="value" @input="updateValue()">
        </div>
    </div>
</template>
<script>
export default{
    props:[
        'value',
        'string',
        'disabled'
    ],
    data(){
        return{
            inputType : 'textarea'
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
        console.log(this.value);
        console.log(this.inputType);
        this.inputType = inputTypeArray[this.string.attribute]
        console.log(this.string.attribute)
        console.log(inputTypeArray[this.string.attribute]);
    },
    methods:{
        updateValue(){
            this.$emit( 'input', this.$refs[inputType].value )
        }
    }
}
</script>