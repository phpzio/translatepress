<?php

class TRP_Language_Switcher{

    protected $settings;
    protected $url_converter;
    protected $trp_settings_object;

    public function __construct( $settings, $url_converter, $trp_settings_object ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
        $this->trp_settings_object = $trp_settings_object;
        $language = $this->get_current_language();
        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = $language;
    }

    public function language_switcher(){
        ob_start();
        global $TRP_LANGUAGE;
        $current_language = $TRP_LANGUAGE;
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );
        require TRP_PLUGIN_DIR . 'includes/partials/language-switcher-1.php';
        return ob_get_clean();
    }

    public function get_current_language(){
        //todo add all possible ways of determining language: cookies, global define etc.
        if ( isset( $_REQUEST['lang'] ) ){
            $language_code = esc_attr( $_REQUEST['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }else{
            return $this->url_converter->get_lang_from_url_string( );
        }

        return $this->settings['default-language'];
    }

    protected function str_lreplace( $search, $replace, $subject ) {
        $pos = strrpos($subject, $search);
        if ( $pos !== false ) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    protected function ends_with($haystack, $needle){
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function enqueue_language_switcher_scripts( ){
        wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-language-switcher.js', array('jquery'));

        wp_enqueue_script( 'trp-floater-language-switcher-script', TRP_PLUGIN_URL . 'assets/js/trp-floater-language-switcher.js', array( 'jquery' ) );
        wp_enqueue_style( 'trp-floater-language-switcher-style', TRP_PLUGIN_URL . 'assets/css/trp-floater-language-switcher.css' );
    }

    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
        if( is_customize_preview() || is_admin() )
            return $url;

        global $TRP_LANGUAGE;
        $abs_home = $this->url_converter->get_abs_home();
        $new_url = $abs_home . '/' . $TRP_LANGUAGE;
        if ( ! empty( $path ) ){
            $new_url .= '/' . ltrim( $path, '/' );
        }

        return apply_filters( 'trp_home_url', $new_url, $abs_home, $TRP_LANGUAGE, $path );
    }

    public function add_floater_language_switcher() {

        // Current language
        global $TRP_LANGUAGE;

        // All the published languages
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );

        // Check if we display language code or name
        $trp_floater_ls_names = ( isset( $this->settings['trp-ls-floater-names'] ) && $this->settings['trp-ls-floater-names'] == 'yes' ? 'name' : 'code' );

        // Add a specific class for when we display language code and another for language name
        $trp_floater_ls_class = ( $trp_floater_ls_names == 'name' ? 'trp-floater-ls-name' : 'trp-floater-ls-code' );

        $current_language = array();
        $other_languages = array();
        foreach( $published_languages as $code => $name ) {
            if( $code == $TRP_LANGUAGE ) {
                // $current_language = ( $trp_floater_ls_names == 'name' ? ucfirst( $name ) : strtoupper( $code ) );
                $current_language['code'] = strtoupper( $code );
                $current_language['name'] = ucfirst( $name );
            } else {
                $other_languages[$code] = $name;
            }
        }

        ?>
        <div id="trp-floater-ls" class="<?php echo $trp_floater_ls_class ?>">
            <!-- class="trp-with-flags" ----- should be added only when we display flags -->
            <div id="trp-floater-ls-current-language" class="trp-with-flags">
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language" onclick="void(0)"><?php echo $this->add_flag( $current_language['code'], $current_language['name'] ); echo ( $trp_floater_ls_names == 'name' ? $current_language['name'] : $current_language['code'] ); ?></a>
            </div>
            <!-- class="trp-with-flags" ----- should be added only when we display flags -->
            <div id="trp-floater-ls-language-list" class="trp-with-flags">
                <?php
                foreach( $other_languages as $code => $name ) {
                    $language_label = ( $trp_floater_ls_names == 'name' ? $name : strtoupper( $code ) )
                    ?>
                    <a href="javascript:void(0)" title="<?php echo $name; ?>" onclick="trp_floater_change_language( '<?php echo $code; ?>' )"><?php echo $this->add_flag( $code, $name ); echo $language_label; ?></a>
                <?php
                }
                ?>
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language"><?php echo $this->add_flag( $current_language['code'], $current_language['name'] ); echo ( $trp_floater_ls_names == 'name' ? $current_language['name'] : $current_language['code'] ); ?></a>
            </div>
        </div>

    <?php
    }

    public function add_flag( $language_code, $language_name ) {

        // Path to folder with flags images
        $flags_path = TRP_PLUGIN_URL .'assets/images/flags/';
        $flags_path = apply_filters( 'trp_flags_path', $flags_path, $language_code );

        // File name for specific flag
        $flag_file_name = $language_code .'.png';
        $flag_file_name = apply_filters( 'trp_flag_file_name', $flag_file_name, $language_code );

        // HTML code to display flag image
        $flag_html = '<img class="trp-flag-image" src="'. $flags_path . $flag_file_name .'" width="18" height="12" alt="' . $language_code . '" title="' . $language_name . '">';

        return $flag_html;

    }

    public function add_ls_to_menu( ){
        $args = array(
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_nav_menus'     => true,
            'show_in_menu'          => false,
            'show_in_admin_bar'     => false,
            'can_export'            => false,
            'public'                => false,
            'label'                 => 'Language Switcher'
        );
        register_post_type( 'language-switcher', $args );
    }

    public function ls_menu_permalinks( $items, $menu, $args ){
        foreach ( $items as $key => $item ){
            if ( $item->object == 'language-switcher' ){
                $ls_id = get_post_meta( $item->ID, '_menu_item_object_id', true );
                $ls_post = get_post( $ls_id );
                if ( $ls_post == null ) {
                    continue;
                }
                $ls_options = $this->trp_settings_object->get_language_switcher_options();
                $menu_settings = $ls_options[$this->settings['menu-options']];
                $language_code = $ls_post->post_content;
                $language_name = $ls_post->post_title;

                $items[$key]->url = $this->url_converter->get_url_for_language( $language_code );
                $items[$key]->title = '<span>';
                if ( $menu_settings['flags'] ) {
                    //todo get flag for country
                    $items[$key]->title .= '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAD1BMVEUAI5X////tKTk/Wq/2lJzlzbSZAAAAGElEQVQYlWNgAANmRjBgYQIDhlHBwSQIAJUFAfVnNwpwAAAAAElFTkSuQmCC">';
                }
                if ( $menu_settings['short_names'] ) {
                    $items[$key]->title .= $language_code;
                }
                if ( $menu_settings['full_names'] ) {
                    $items[$key]->title .= $language_name;
                }
                $items[$key]->title .= '</span>';

                $items[$key]->title = apply_filters( 'trp_menu_language_switcher', $items[$key]->title, $language_name, $language_code, $menu_settings );
            }
        }

        return $items;
    }

}