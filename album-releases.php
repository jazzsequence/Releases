<?php
/*
Plugin Name: Plague Releases
Plugin URI: http://museumthemes.com
Description: An album release plugin for WordPress, brought to you by <a href="http://plaguemusic.com" target="_blank">Plague Music</a>. Part of the Plague Netlabel-in-a-Box.
Version: 2.0.0
Author: Chris Reynolds
Author URI: http://chrisreynolds.io/
License: GPLv3
License URI: http://gnu.org/licenses/gnu.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-album-releases.php' );

Album_Releases::get_instance();