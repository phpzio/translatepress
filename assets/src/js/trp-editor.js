import Vue from 'vue'
import Editor from './editor.vue'

if ( document.getElementById( 'trp-editor-container' ) ) {

    window.tpEditorApp = new Vue({
        components: {
            'trp-editor' : Editor,
        },
        el: '#trp-editor-container',
        data: {
        },
       /* Not needed for now. Maybe activate this in the future
       methods: {
            addToDictionary: function( strings, extraNodeInfo = null ) {
                this.$refs.trp_editor.addToDictionary( strings, extraNodeInfo );
            },
            setupEventListener: function( node ) {
                this.$refs.trp_editor.setupEventListener( node );
            }
        }*/
    })

}
