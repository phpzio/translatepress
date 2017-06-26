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