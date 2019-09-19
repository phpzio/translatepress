<?php

/**
 * Class TRP_Machine_Translator
 *
 * Facilitates Machine Translation calls
 */
class TRP_Machine_Translator {
    protected $settings;
	protected $referer;
	protected $url_converter;
	protected $machine_translator_logger;
    /**
     * TRP_Machine_Translator constructor.
     *
     * @param array $settings         Settings option.
     */
    public function __construct( $settings ){
        $this->settings = $settings;
    }

    /**
     * Whether automatic translation is available.
     *
     * @return bool
     */
    public function is_available(){
        if( !empty( $this->settings['machine-translation'] ) && $this->settings['machine-translation'] == 'yes' )
            return true;
        else
            return false;
    }

	/**
	 * Return site referer
	 *
	 * @return string
	 */
	public function get_referer(){
		if( ! $this->referer ) {
			if( ! $this->url_converter ) {
				$trp = TRP_Translate_Press::get_trp_instance();
				$this->url_converter = $trp->get_component( 'url_converter' );
			}

			$this->referer = $this->url_converter->get_abs_home();
		}

		return $this->referer;
	}

    public function translate_array( $strings, $language_code ){}
}
