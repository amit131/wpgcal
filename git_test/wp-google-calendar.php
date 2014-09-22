<?php
/*
 * Plugin Name:		WordPress Google Calendar
 * Description:		WordPress Google Calendar displays the Google Calendar with Events. Calendar User can <strong>add/edit/delete</strong> calendar events directly from WordPress 
 					Admin Section.
 * Version:			1.0
 * Author:			Amit Kumar
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-google-calendar.php';

function run_wp_google_calendar() {

	$wgc = new Wordpress_Google_Calendar();
	$wgc->run();

}

run_wp_google_calendar();
