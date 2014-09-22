<?php

class Wordpress_Google_Calendar {

	protected $loader;

	protected $plugin_slug;

	protected $plugin_name;

	protected $version;

	public function __construct() {

		$this->plugin_slug = 'wp-google-calendar-slug';
		$this->plugin_name = 'wp-google-calendar';
		$this->version = '1.0';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-'.$this->plugin_name.'-admin.php';

		require_once plugin_dir_path( __FILE__ ) . 'class-'.$this->plugin_name.'-loader.php';
		$this->loader = new WordPress_Google_Calendar_Loader();

	}

	private function define_admin_hooks() {

		$admin = new WordPress_Google_Calendar_Admin( $this->get_version(), $this->get_plugin_name()  );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		//$this->loader->add_action( 'add_meta_boxes', $admin, 'add_meta_box' );
		$this->loader->add_action('admin_menu', $admin, 'add_plugin_main_menu');
		$this->loader->add_action('admin_menu', $admin, 'add_first_submenu_page');		
		$this->loader->add_action('admin_menu', $admin, 'add_second_submenu_page');
		$this->loader->add_action('admin_init', $admin, 'my_script_enqueuer');
		$this->loader->add_action('wp_ajax_add_new_event', $admin, 'add_new_event');
		$this->loader->add_action('wp_ajax_nopriv_add_new_event', $admin, 'add_new_event');
		$this->loader->add_action('wp_ajax_connect_google_api', $admin, 'connect_google_api');
		$this->loader->add_action('wp_ajax_nopriv_connect_google_api', $admin, 'connect_google_api');
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return $this->version;
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}
}
