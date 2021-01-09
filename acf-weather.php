<?php

/*
Plugin Name: Advanced Custom Fields: OpenWeather
Plugin URI:
Description:
Version: 1.0.0
Author:
Author URI:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/





// 1. set text domain
// Reference: https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'acf-weather', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );




// 2. Include field type for ACF5
// $version = 5 and can be ignored until ACF6 exists

function include_field_types_tweet( $version ) {

	include_once('acf-weather-v5.php');

}

add_action('acf/include_field_types', 'include_field_types_tweet');

?>
