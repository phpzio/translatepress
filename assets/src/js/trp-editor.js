//import 'babel-polyfill'
import Vue from 'vue'
import Editor from './editor.vue'

if ( document.getElementById( 'trp-editor-container' ) ) {

    let app = new Vue({
        components: {
            'trp-editor' : Editor,
        },
        el: '#trp-editor-container',
        data: {
        }
    })

}
