<?php

function trp_the_language_switcher(){
    $trp = TRP_Translate_Press::get_trp_instance();
    $language_switcher = $trp->get_component( 'language_switcher' );
    echo $language_switcher->language_switcher();
}