<?php
class TRP_Machine_Translator_Logger
{
    protected $settings;
    protected $query;
    protected $url_converter;
    protected $counter;
    protected $counter_date;
    protected $limit;

    /**
     * TRP_Machine_Translator_Logger constructor.
     *
     * @param array $settings       Settings option.
     */
    public function __construct( $settings ){
        $this->settings = $settings;
        $this->counter = intval($this->get_advanced_sub_option('machine_translation_counter', 0));
        $this->counter_date = $this->get_advanced_sub_option('machine_translation_counter_date', date ("Y-m-d" ));
        $this->limit = intval($this->get_advanced_sub_option('machine_translation_limit', 1000000));
        // if a new day has passed, update the counter and date
        $this->maybe_reset_counter_date();
    }

    public function log($args = array()){

        if ( ! $this->query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->query = $trp->get_component('query');
        }

        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }

        if(empty($args)){
            return false;
        }

        if( $this->get_advanced_sub_option('machine_translation_log', false) !== 'yes' ){
            return false;
        }

        if( !$this->query->check_machine_translation_log_table() ){
            return false;
        }

        // expected structure.
        $log = array(
            'url' => $this->url_converter->cur_page_url(),
            'strings' => $args['strings'],
            'characters' => $this->count(unserialize($args['strings'])),
            'response' => $args['response'],
            'lang_source' => $args['lang_source'],
            'lang_target' => $args['lang_target'],
            'timestamp' => date ("Y-m-d H:i:s" )
        );

        $table_name = $this->query->db->prefix . 'trp_machine_translation_log';

        $query = "INSERT INTO `$table_name` ( `url`, `strings`, `characters`, `response`, `lang_source`, `lang_target`, `timestamp` ) VALUES (%s, %s, %s, %s, %s, %s, %s)";

        $prepared_query = $this->query->db->prepare( $query, $log );
        $this->query->db->get_results( $prepared_query, OBJECT_K  );

        if ($this->query->db->last_error !== '') {
            return false;
        }
        return true;
    }

    private function count($strings){
        if(!is_array($strings)){
            return 0;
        }

        $char_number = 0;
        foreach($strings as $string){
            $char_number += strlen($string);
        }

        return $char_number;
    }

    public function count_towards_quota($strings){
        $this->counter += $this->count($strings);
        $this->update_advanced_sub_option('machine_translation_counter', $this->counter);

        return $this->counter;
    }

    public function quota_exceeded(){
        if ( $this->limit  >=  $this->counter )
        {
            // quota NOT exceeded
            // for some reason this condition is hard to comprehend by my brain
            // thus the unneeded comment.
            return false;
        }

        // we've exceeded our daily quota
        $this->update_advanced_sub_option('machine_translation_trigger_quota_notification' , true );
        return true;
    }

    public function maybe_reset_counter_date(){
        $some = 'else';

        if ($this->counter_date === date ("Y-m-d" ))
        {
            // the day has not passed
            return false;
        } else {
            // there is a new day
            $this->update_advanced_sub_option('machine_translation_counter_date' , date ("Y-m-d" ) );
            // clear the counter
            $this->update_advanced_sub_option('machine_translation_counter' , 0 );
            // clear the notification
            $this->update_advanced_sub_option('machine_translation_trigger_quota_notification' , false );

            return true;
        }
    }

    private function get_advanced_sub_option($option_name, $default){

        if( isset($this->settings['advanced_settings'][$option_name]) )
        {
            return $this->settings['advanced_settings'][$option_name];
        } else {
            return $default;
        }
    }

    private function update_advanced_sub_option($option_name, $value){
        // update TP settings instance
        $this->settings['advanced_settings'][$option_name] = $value;
        // update in the database as well
        update_option('trp_advanced_settings', $this->settings['advanced_settings']);
    }
}