<template>
    <div id="trp-editor">

        <div id="trp-controls">
            <div class="trp-controls-container">

                <div id="trp-close-save">
                    <a id="trp-controls-close" href="#"></a>
                    <div id="trp-save-container">
                        <span id="trp-translation-saved" style="display: none">{{ translation.__( 'Saved!', domain ) }}</span>
                        <span class="trp-ajax-loader" style="display: none" id="trp-string-saved-ajax-loader">
                            <div class="trp-spinner"></div>
                        </span>
                        <button id="trp-save" type="submit" class="button-primary trp-save-string">Save translation</button>
                    </div>
                </div>

                <div class="trp-controls-section">

                    <div class="trp-controls-section-content">
                        <div id="trp-language-switch">
                            <select id="trp-language-select" name="lang" v-model="currentLanguage" v-select2>
                                <option v-for="(lang, langIndex) in languages" :value="langIndex">{{lang}}</option>
                            </select>
                        </div>


                        <div id="trp-string-list">
                            <select id="trp-string-categories" v-model="selectedString" v-select2>
                                <option v-for="(option, optionIndex) in selectData" :value="option.id" :key="optionIndex">{{option.original}}</option>

                                <!--<optgroup id="trp-gettext-strings-optgroup" label="Gettext Strings"></optgroup>-->
                            </select>
                        </div>

                        <div id="trp-next-previous">
                            <button type="button" id="trp-previous" class="trp-next-previous-buttons"><span>&laquo;</span> Previous</button>
                            <button type="button" id="trp-next" class="trp-next-previous-buttons">Next <span>&raquo;</span></button>
                        </div>

                        <div id="trp-view-as">
                            <div id="trp-view-as-description">View as</div>
                            <select id="trp-view-as-select">
                                <option v-for="(role, roleIndex) in roles" :value="role" :disabled="!role" :title="!role ? 'Available in our Pro Versions' : ''">{{roleIndex}}</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="trp-controls-section">

                    <div id="trp-translation-section" class="trp-controls-section-content">

                        <div id="trp-unsaved-changes-warning-message" style="display:none">You have unsaved changes!</div>

                        <div id="trp-gettext-original" class="trp-language-text trp-gettext-original-language" style="display:none">
                            <div class="trp-language-name">Original String</div>
                            <textarea id="trp-gettext-original-textarea" readonly="readonly"></textarea>
                        </div>

                        <div :id="'trp-language-' + settings['default-language']" class="trp-language-text trp-default-language">
                            <div class="trp-language-name" :data-trp-gettext-language-name="'To ' + settings['default-language-name']" :data-trp-default-language-name="'From ' + settings['default-language-name']">
                                {{ 'From ' + settings['default-language-name'] }}
                            </div>

                            <textarea id="trp-original" :data-trp-language-code="settings['default-language']" readonly="readonly"></textarea>

                            <div class="trp-discard-changes trp-discard-on-default-language" style="display:none;">Discard changes</div>
                        </div>
                    </div>
                </div>

            </div>

            <div id="trp_select2_overlay"></div>
        </div>

        <div id="trp-preview">
            <iframe id="trp-preview-iframe" :src="current_url" v-on:load="onLoadIframe"></iframe>
        </div>
    </div>
</template>

<script>
    import 'select2/dist/js/select2.min.js'
    import axios from 'axios'

    export default {
        props: [
            'trp_settings',
            'available_languages',
            'current_language',
            'on_screen_language',
            'view_as_roles',
            'current_url',
            'string_selectors',
            'ajax_url',
            'editor_nonces'
        ],
        data(){
            return {
                settings          : JSON.parse( this.trp_settings ),
                domain            : 'translatepress-multilingual',
                translation       : wp.i18n,
                languages         : JSON.parse( this.available_languages ),
                roles             : JSON.parse( this.view_as_roles ),
                currentLanguage   : this.current_language,
                selectors         : JSON.parse( this.string_selectors ),
                nonces            : JSON.parse( this.editor_nonces),
                iframe            : '',
                dictionaryRegular : [],
                dictionaryGettext : [],
                dictionaryDynamic : [],
                selectData        : [],
                selectedString    : '',
            }
        },
        created(){
            this.settings['default-language-name'] = this.languages[ this.settings['default-language'] ]
            this.selectors = this.prepareSelectorStrings()
        },
        mounted(){
            // initialize select2
            jQuery( '#trp-language-select' ).select2( { width : '100%' })
            jQuery( '#trp-string-categories' ).select2({ placeholder : 'Select string to translate...', width : '100%' })
            jQuery( '#trp-view-as-select' ).select2( { width : '100%' })

            // show overlay when select is opened
            jQuery( '#trp-language-select, #trp-string-categories' ).on( 'select2:open', function() {
                jQuery( '#trp_select2_overlay' ).fadeIn( '100' )
            }).on( 'select2:close', function() {
                jQuery( '#trp_select2_overlay' ).hide()
            })
        },
        methods: {
            prepareSelectorStrings(){
                let parsed_selectors = []
                this.selectors.forEach( function ( selector, index ){
                    parsed_selectors.push( 'data-trp-translate-id' + selector  )
                    parsed_selectors.push( 'data-trpgettextoriginal' + selector  )
                })

                return parsed_selectors
            },
            translations(){
                const { __, _x, _n, _nx } = wp.i18n

                let str = __( 'Saved!', this.domain )

                console.log( str )
            },
            onLoadIframe(){
                //setup iframe
                let iframeElement = document.querySelector('#trp-preview-iframe')
                let app = this

                this.iframe = iframeElement.contentDocument || iframeElement.contentWindow.document

                //setup string array
                let regularStringIdsArray = []
                let gettextStringIdsArray = []
                let nodes = this.iframe.querySelectorAll( '[' + this.selectors.join('],[') + ']' )
                nodes.forEach( function ( node ) {
                    app.selectors.some( function ( selector ) {
                        let stringId = node.getAttribute( selector )

                        if ( stringId ){
                            if ( selector.includes('data-trpgettextoriginal') ){
                                gettextStringIdsArray.push( stringId )
                            }else{
                                regularStringIdsArray.push( { 'id': stringId } )
                            }

                            return true
                        }
                        return false
                    })
                })

                // unique array of ids
                gettextStringIdsArray = [...new Set(gettextStringIdsArray)];

                /* REGULAR */
                //setup POST data
                let data = new FormData();
                    data.append('action', 'trp_get_translations');
                    data.append('all_languages', 'true');
                    data.append('security', this.nonces['gettranslationsnonce']);
                    data.append('language', this.on_screen_language);
                    data.append('strings', JSON.stringify( regularStringIdsArray ) );

                //make ajax request
                axios.post( this.ajax_url, data )
                    .then(function (response) {
                        app.dictionaryRegular = response.data
                        //app.selectData = response.data[app.on_screen_language]
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

                /* GETTEXT */
                //setup POST data
                data = new FormData();
                    data.append('action', 'trp_gettext_get_translations');
                    data.append('security', this.nonces['gettextgettranslationsnonce']);
                    data.append('language', this.currentLanguage);
                    data.append('gettext_string_ids', JSON.stringify( gettextStringIdsArray ) );

                //make ajax request
                axios.post( this.ajax_url, data )
                    .then(function (response) {
                  //      console.log(response.data);
                        app.dictionaryGettext = response.data
                        //app.selectData = response.data[app.on_screen_language]
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

            },
        },
        //add support for v-model in select2
        directives: {
            select2: {
                inserted(el) {
                    jQuery(el).on('select2:select', () => {
                        const event = new Event('change', { bubbles: true, cancelable: true })
                        el.dispatchEvent(event)
                    })

                    jQuery(el).on('select2:unselect', () => {
                        const event = new Event('change', {bubbles: true, cancelable: true})
                        el.dispatchEvent(event)
                    })
                },
            }
        }
    }
</script>
