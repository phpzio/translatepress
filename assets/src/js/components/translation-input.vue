<template>
    <div class="translation-input" :class="{'trp-highlight-unsaved-changes':highlightUnsavedChanges}">
        <div v-if="inputType == 'textarea'" class="trp-translation-input-parent">
            <textarea class="trp-translation-input trp-textarea" :readonly="readonly" ref="textarea" :value="getValue()" @input="updateValue()"></textarea>
        </div>
        <div v-if="inputType == 'input'" class="trp-translation-input-parent">
            <input class="trp-translation-input trp-input" readonly :value="getValue()">
        </div>
        <div v-if="inputType == 'inputmedia'" class="trp-translation-input-parent trp-input-media-parent">
            <input v-show="inputType == 'inputmedia'" type="button" class="trp-add-media" :value="editorStrings.add_media" @click="openMediaUpload($event)">
            <div class="trp-input-media-container">
                <input class="trp-translation-input trp-input trp-input-media" :readonly="readonly" ref="inputmedia" :value="getValue()" @input="updateValue( null )">
            </div>
        </div>
    </div>
</template>
<script>
import he from 'he'
import autosize from 'autosize'

export default{
    props:[
        'value',
        'string',
        'readonly',
        'highlightUnsavedChanges',
        'editorStrings'
    ],
    data(){
        return{
            inputType      : 'textarea',
        }
    },
    mounted(){
        let inputTypeArray = {
            ''            : 'textarea',
            'content'     : 'textarea',
            'alt'         : 'textarea',
            'title'       : 'textarea',
            'placeholder' : 'textarea',
            'outertext'   : 'textarea',
            'value'       : 'textarea',
            'src'         : 'inputmedia',
            'href'        : 'inputmedia'
        };
        this.inputType = ( inputTypeArray[this.string.attribute] ) ? inputTypeArray[this.string.attribute] : 'textarea'
        this.inputType = (this.readonly && this.inputType === 'inputmedia' ) ? 'input' : this.inputType;

        autosize(document.querySelectorAll('.trp-textarea'))

    },
    methods:{
        getValue(){
            if( this.value )
                return he.decode( this.value )

            return this.value
        },
        updateValue( value ){
            value = ( value ) ? value : this.$refs[this.inputType].value
            this.$emit( 'input', value )
        },
        openMediaUpload(event){
            event.preventDefault()
            let self = this
            if (wp.media && wp.media.editor) {
                wp.media.editor.send.attachment = function (props, attachment) {
                    self.updateValue(attachment.url)
                }
                wp.media.editor.open()
            }else{
                console.log( 'TranslatePress Error: WP Media not loaded')
            }
            return false
        }
    }
}
</script>
