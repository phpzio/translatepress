<?php
/*
Plugin Name: Translate Press
Plugin URI: https://www.cozmoslabs.com/
Description: A simple WordPress translation plugin
Version: 0.0.1
Author: Cozmoslabs, Razvan Mocanu
Author URI: https://www.cozmoslabs.com/
License: GPL2

== Copyright ==
Copyright 2017 Cozmoslabs (www.cozmoslabs.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define('TRP_VERSION', '1.0.0');
define('TRP_PLUGIN_DIR', dirname(__FILE__));
define('TRP_PLUGIN_URL', plugin_dir_url(__FILE__));


/* include simle html dom */
require TRP_PLUGIN_DIR.'/assets/lib/simplehtmldom/simple_html_dom.php';

function trp_full_trim($word) {
    return trim($word," \t\n\r\0\x0B\xA0�");
}

add_action( 'init', 'trp_start_object_cache' );
function trp_start_object_cache( ){
    if( is_admin() )
        return;

    ob_start('trp_translate_page');
}

add_action( 'wp_enqueue_scripts', 'trp_print_scripts' );
function trp_print_scripts(){
    wp_enqueue_script( 'trp-translation', TRP_PLUGIN_URL . 'assets/js/frontend_translate.js', array(), TRP_VERSION, true );
}


function trp_translate_page($string){

    $tstrings = array();

    $html = TRP_Simplehtmldom\str_get_html($string, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

    foreach ($html->find('text') as $k => $row){
        if( trp_full_trim($row->outertext)!="" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric(trp_full_trim($row->outertext)) && !preg_match('/^\d+%$/',trp_full_trim($row->outertext))
            )
            $tstrings[] =  $row;
    }



    global $wpdb;

    $string = microtime(true).$string;
    if( isset( $_GET['lang'] ) && $_GET['lang'] == 'it' ) {
        /*foreach ($tstrings as $tstring) {
            $translation = $wpdb->get_row("SELECT * FROM wp_en_it WHERE en = '$tstring'");
            $string = str_replace($tstring, $translation->it, $string);
        }*/

        $translation = $wpdb->get_results("SELECT * FROM wp_en_it WHERE en IN ('".implode( "','", $tstrings )."')");
        foreach( $translation as $translati ){
            $string = str_replace($translati->en, $translati->it, $string);
        }

    }
    //var_dump($translation);
    $string .= microtime(true);

    return $string;
}



/*$tstrings = array(
    'An event that happens suddenly or by chance without an apparent cause.',
    'A compound capable of transferring a hydrogen ion in solution.',
    'A language of Nigeria.',
    'An austro-asiatic language spoken by the Western Lawa people principally in the Yongle and Zhenkang counties in Yunnan Province in China, and also in the Chiang Mai and Maehongson provinces of northern Thailand.',
    'What you want to achieve or do.',
    'A species of tree that is endemic to parts of Morocco and Algeria and cultivated for its oil-rich seeds.',
    'A form of energy that is transferred by a difference in temperature: it is equal to the total kinetic energy of the atoms or molecules of a system.',
    'An element member of the actinide group of 15 radioactive elements.',
    'An Indo-European language spoken by 3.5 million people mostly in western Iran and eastern Iraq.',
    'A person who dedicates his or her life to a pursuit of contemplative ideals and practices extreme self-denial or self-mortification for religious reasons.',
    'Agriculture that uses a large area of land for production of a single crop year after year.',
    'A glass stemware that is used to drink wine.',
    'A substance mixed in small quantities with another product to modify its chemical or physical state, for example to make food look visually more attractive.',
    'An island nation in the northern Atlantic Ocean. Its capital is Reykjavik.',
    'A variety of the Cree language spoken by the Atikamekw people of southwestern Quebec.',
    'A machine used for the application of capital punishment by decapitation.',
    'An establishment, either physical or virtual, that sells goods or services to the public.',
    'A device that modulates an analog carrier signal, to encode digital information, and that also demodulates such a carrier signal to decode the transmitted information.',
    'An Indo-Aryan language spoken in the Republic of Maldives and also in the island of Maliku (Minicoy) in Union territory of Lakshadweep, India.',
    'A person who hunts game.',
    'A person other than a family member, spouse or lover whose company one enjoys and towards whom one feels affection.',
    'A language of Kenya.',
    'The capital of the Indian state of West Bengal, which is located in eastern India on the east bank of the Hooghly River.',
    'Coordinating link with negative value, usually preceded by another negation.',
    'A unit of mass equal to one hundred grams.',
    'An unpleasant complex combination of emotions that includes fear, apprehension and worry, and is often accompanied by physical sensations such as palpitations, nausea, chest pain and/or shortness of breath.',
    'A well developed set of abdominal muscles.',
    'The prevention, treatment, and management of illness and the preservation of mental and physical well being through the services offered by the medical, nursing, and allied health professions.',
    'A system of related measures that facilitates the quantification of some particular characteristic.',
    'The neutrally determined gender of a kind of word as used by some languages.',
    'A physical theory of relativity based on the assumption that the speed of light in a vacuum is a constant and the assumption that the laws of physics are invariant in all inertial systems.',
    'A strait that runs between Norway and the southwest coast of Sweden and the Jutland peninsula of Denmark, connecting the North Sea and the Kattegat strait, which leads to the Baltic Sea.',
    'A constellation listed by Ptolemy, which lies roughly at the celestial equator.',
    'A costume worn on formal occasions by the faculty or students of a university or college.',
    'Mathematics: Point where a graph of a function changes either from a right hand bend to a left hand bend or vice versa.',
    'A river that flows through the Moscow and Smolensk Oblasts in Russia, and is a tributary of the Oka River.',
    'Uneducated in general; lacking knowledge or sophistication.',
    'One of the major divisions of the Chinese language which is spoken in most of Zhejiang province, the municipality of Shanghai, southern Jiangsu province, as well as smaller parts of Anhui, Jiangxi, and Fujian provinces.',
    'A unit of mass equal to one thousandth of a picogram.',
    'A geographical dictionary, an important reference for information about places and place-names, used in conjunction with an atlas.',
    'Any of various large, venomous rays, of the orders Rajiformes and Myliobatiformes, having a barbed, whiplike tail.',
    'Exclusive, transferable and usually inheritable power and liberty, recognised to the creator(s) of a literary or artistic work and to his successors in title, to publish, translate, copy, perform etc. it, for free or for a fee.',
    'To remove from a package or container, particularly with respect to items that had previously been arranged closely and securely in a pack.',
    'Continent that is entirely located on the southern half of the globe, surrounded by the Indian, and Pacific Oceans, and Southern Ocean.',
    'A psychological phenomenon in which repetition causes a word or phrase to temporarily lose meaning for the listener.',
    );*/
