<?php
/*
Plugin Name: TranslatePress
Plugin URI: https://translatepress.com/
Description: Automatic translation plugin with front-end editing.
Version: 1.0.0-beta
Author: Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban
Author URI: https://translatepress.com/
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



require_once plugin_dir_path(__FILE__) . 'class-translate-press.php';

function trp_run_translatepress_hooks(){
    $trp = TRP_Translate_Press::get_trp_instance();
    $trp->run();
}
add_action( 'plugins_loaded', 'trp_run_translatepress_hooks', 15 );

