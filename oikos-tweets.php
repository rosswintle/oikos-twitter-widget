<?php
/*
Plugin Name: Oikos Twitter Widget
Plugin URI: http://oikos.org.uk
Description: This is a simple plugin used to get tweets using the Twitter REST API v1.1. and oAuth
Version: 0.1
Author: Ross Wintle
Author Email: ross@oikos.org.uk
License:

  Copyright 2013 Ross Wintle (ross@oikos.org.uk)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

class OikosTwitterWidget {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Oikos Twitter Widget';
	const slug = 'oikos_twitter_widget';
	
	/**
	 * Constructor
	 */
	function __construct() {
		//Hook up to the init action
		add_action( 'plugins_loaded', array( &$this, 'init_oikos_twitter_widget' ) );
	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_oikos_twitter_widget() {
		// do not generate any output here
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_oikos_twitter_widget() {
	
		// Include the widget class
		require('class-oikos-twitter-widget.php');
		
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		//add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
		//add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) );    
	}

	/*function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}
	*/
	
	/**
	 * Callback to print Twitter API code in footer
	 */
	public static function print_twitter_api() {
?>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<?
	}

	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {

		} else {
			$this->load_file('oikos-twitter-widget-css', 'oikos-twitter-widget.css', false);
			add_action('wp_footer', array('OikosTwitterWidget', 'print_twitter_api'));
		} // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
  
} // end class
new OikosTwitterWidget();
