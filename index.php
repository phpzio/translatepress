<?php
/*
Plugin Name: TranslatePress - Multilingual
Plugin URI: https://translatepress.com/
Description: Experience a better way of translating your WordPress site, with full support for WooCommerce and site builders.
Version: 1.3.0
Author: Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban
Author URI: https://cozmoslabs.com/
Text Domain: translatepress-multilingual
Domain Path: /languages
License: GPL2
WC requires at least: 2.5.0
WC tested up to: 3.3

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



require_once plugin_dir_path(__FILE__) . 'class-translate-press.php';

function trp_run_translatepress_hooks(){
    $trp = TRP_Translate_Press::get_trp_instance();
    $trp->run();
}
/* make sure we execute our plugin before other plugins so the changes we make apply across the board */
add_action( 'plugins_loaded', 'trp_run_translatepress_hooks', 1 );