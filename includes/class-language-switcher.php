<?php

class TRP_Language_Switcher{

    protected $settings;
    protected $url_converter;

    public function __construct( $settings, $url_converter ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
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



        //todo  move this to settings, add check if exists before insert post
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );

        foreach ( $published_languages as $language_code => $language_name ) {

            $ls = array(
                'post_title'    => $language_name,
                'post_content'  => $language_code,
                'post_status'   => 'publish',
                'post_type'     => 'language-switcher'
            );
            //error_log(json_encode(wp_insert_post( $ls )));
        }
    }

    public function ls_menu_permalinks( $items, $menu, $args ){

        //error_log( serialize( $items ) );
        foreach ( $items as $key => $item ){
            if ( $item->object == 'language-switcher' ){
                $ls_id = get_post_meta( $item->ID, '_menu_item_object_id', true );
                $ls_post = get_post( $ls_id );
                $items[$key]->url = $this->url_converter->get_url_for_language( $ls_post->post_content );
            }
        }

        return $items;



       /* $args = array(
            'menu-item-url' => 'URRRRLLL',
             'menu-item-title' => 'DIFFFERENT',
        );
        //wp_update_nav_menu_item(7,480,$args);
        /*ls_menu_permalinks
        if ( $post->post_type !== 'language-switcher' ){
            /*
            //error_log('it\'s LS');
            //return 'http://www.googgle.com/';
            return $permalink;
        }

        error_log('ls');
        //error_log( $post->ID );
        return $permalink . "/something-new/";*/

    }


}