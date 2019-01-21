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
                            <select id="trp-language-select" name="lang" v-model="currentLanguage">
                                <option v-for="(lang, langIndex) in languages" :value="langIndex">{{lang}}</option>
                            </select>
                        </div>


                        <div id="trp-string-list">
                            <select id="trp-string-categories">
                                <option></option>

                                <optgroup id="trp-gettext-strings-optgroup" label="Gettext Strings"></optgroup>
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
            <trp-iframe id="trp-preview-iframe" :src="current_url"></trp-iframe>
        </div>
    </div>
</template>

<script>
    import 'select2/dist/js/select2.full.js'
    import iframe from './iframe.vue';

    export default {
        components: {
            'trp-iframe'            : iframe
        },
        props: [
            'trp_settings',
            'available_languages',
            'current_language',
            'view_as_roles',
            'current_url'
        ],
        data() {
            return {
                settings        : JSON.parse( this.trp_settings ),
                domain          : 'translatepress-multilingual',
                translation     : wp.i18n,
                languages       : JSON.parse( this.available_languages ),
                roles           : JSON.parse( this.view_as_roles ),
                currentLanguage : this.current_language,
                iframe          : ''
            }
        },
        created() {
            this.settings['default-language-name'] = this.languages[ this.settings['default-language'] ]
            //get iframe elements
           /* let iframe = document.querySelector('#trp-preview-iframe')
            if ( iframe != null  ) {
                this.iframe = iframe.contentDocument || iframe.contentWindow.document
                this.iframe.onload = function () {
                    alert('da')
                    console.log(this.iframe.querySelector('.site-description'))
                };
            }*/
        },
        mounted() {
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
            translations() {
                const { __, _x, _n, _nx } = wp.i18n

                let str = __( 'Saved!', this.domain )

                console.log( str )
            },
            onLoadIframe(event) {
                const iframe = findIframeByName(event.currentTarget.name);
                console.log('loaded!');
                console.log(iframe);

            }
        }
    }
    function findIframeByName(name) {
        return _.find(window.frames, frame => frame.name === name);
    }
</script>
