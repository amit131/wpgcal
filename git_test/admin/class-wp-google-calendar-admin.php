<?php

class WordPress_Google_Calendar_Admin {

	private $version;
	private $plugin_slug;
	private $client;
	//private $cal_obj;
	
	/**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

	public function __construct( $version, $plugin_slug) {
		$this->version = $version;
		$this->plugin_slug = $plugin_slug;
		$this->options = get_option( 'gc_options' );
		require_once WP_PLUGIN_DIR.'/wpgooglecalendar/google/src/Google_Client.php';
		require_once WP_PLUGIN_DIR.'/wpgooglecalendar/google/src/contrib/Google_CalendarService.php';
		session_start();
	}

	public function enqueue_styles() {

		wp_enqueue_style(
			$this->plugin_slug.'-admin',
			plugin_dir_url( __FILE__ ) . 'css/'.$this->plugin_slug.'-admin.css',
			array(),
			$this->version,
			FALSE
		);

	}
	
	private function getGoogleClient(){
		$this->client = new Google_Client();
		
		$this->client->setClientId( $this->options['client_id'] );
		$this->client->setClientSecret( $this->options['client_secret'] );
		$this->client->setRedirectUri( $this->options['redirect_uri'] );
		$this->client->setDeveloperKey( $this->options['dev_key'] );
	}
	
	private function getCalendarObject(){
		$this->getGoogleClient();
		$calObj = new Google_CalendarService($this->client);
		return $calObj; 
	}
	
	private function getAuthUrl(){
		$this->getCalendarObject();
		$authUrl = $this->client->createAuthUrl();
		return $authUrl;
	}
	
	public function add_plugin_main_menu() {
		add_menu_page('Google Calendar with Events', 'Google Calendar with Events', 'manage_options', 'google-calendar-events', null);
	}

	public function create_plugin_menu_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		} 
		$nonce_event = wp_create_nonce("add_event_nonce");
		//$nonce_connect = wp_create_nonce("connect_google_api_nonce");
		//$action_connect = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&action=connect_google_api&wp_nonce='.$nonce_connect;
		//echo '<h3>Please add below URL to the Redirect URIs field under Credentials Tab in Google Developer Console Project Dashboard</h3><b>'.$action_connect.'</b><br>';
		$action_event = admin_url('admin.php?action=add_new_event&wp_nonce='.$nonce_event,'http');
		echo '<br><br><a id="api_connect" data-nonce="'.$nonce_connect.'" href="'.$this->getAuthUrl().'">Connect to Google API</a><br><br>';
		$form_output = '<form id="add_event" method="post" action="'.$action_event.'">Summary: <input type="text" name="summary"><br>Location: <input type="text" name="location"><br>Start: <input type="text" name="start"><br>End: <input type="text" name="end"><br>Attendee: <input type="text" name="attendee"><br><input type="hidden" id="data-nonce" value="'.$nonce_event.'"><input type="hidden" id="data-token" value="'.$_SESSION['token'].'"><input type="button" id="btnAddEvent" name="add_event" value="Add Event"></form>';
		if(isset($_GET['code'])){
			$this->connect_google_api();
			if(isset($_SESSION['token'])){
				echo '<div class="wrap">';
				echo '<div id="response"></div>';
				echo '<h3>Add New Event to Google Calendar</h3>';
				echo $form_output;
				echo '</div>';
			}
		}
	}
	
	public function add_first_submenu_page() {
		add_submenu_page( 'google-calendar-events','Calendar Dashboard', 'Calendar Dashboard', 'manage_options', 'google-calendar-events',  array($this,'create_plugin_menu_page')); 
		//page callback for page rendering
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}
	
	public function add_second_submenu_page() {
		add_submenu_page( 'google-calendar-events', 'Calendar Settings', 'Calendar Settings', 'manage_options', 'google-calendar-settings', array($this,'create_plugin_submenu_page' )); 
		//page callback for page rendering
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

    /**
     * Options page callback
     */
    public function create_plugin_submenu_page()
    {
        // Set class property
        //$this->options = get_option( 'gc_options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h1>Google Calendar Settings</h1>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'gc_group' );   
                do_settings_sections( 'my-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'gc_group', // Option group
            'gc_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_api_id', // ID
            'Google API Information', // Title
            array( $this, 'print_api_info' ), // Callback
            'my-setting-admin' // Page
        );  

        add_settings_field(
            'app_name', // ID
            'Application Name', // Title 
            array( $this, 'app_name_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_api_id' // Section           
        );      

        add_settings_field(
            'dev_key', // ID
            'Developer Key', // Title 
            array( $this, 'dev_key_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_api_id' // Section           
        );      
		
        add_settings_field(
            'client_id', // ID
            'Client ID', // Title 
            array( $this, 'client_id_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_api_id' // Section           
        );      

        add_settings_field(
            'client_secret', // ID
            'Client Secret', // Title 
            array( $this, 'client_secret_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_api_id' // Section           
        );      

		add_settings_section(
            'redirect_setting_api_id', // ID
            'Google API Information - Redirect URI', // Title
            array( $this, 'print_redirect_info' ), // Callback
            'my-setting-admin' // Page
        );  
		
        add_settings_field(
            'redirect_uri', // ID
            'Redirect URI', // Title 
            array( $this, 'redirect_uri_callback' ), // Callback
            'my-setting-admin', // Page
            'redirect_setting_api_id' // Section           
        );      

	    add_settings_section(
            'setting_calendar_id', // ID
            'Google Calendar\'s User Information', // Title
            array( $this, 'print_calendar_info' ), // Callback
            'my-setting-admin' // Page
        );  

        add_settings_field(
            'calendar_id', // ID
            'Calendar ID', // Title 
            array( $this, 'calendar_id_callback' ), // Callback
            'my-setting-admin', // Page
            'setting_calendar_id' // Section           
        );      

        /*add_settings_field(
            'title', 
            'Title', 
            array( $this, 'title_callback' ), 
            'my-setting-admin', 
            'setting_section_id'
        );*/      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
       
        if( isset( $input['app_name'] ) )
            $new_input['app_name'] = sanitize_text_field( $input['app_name'] );
			
		if( isset( $input['dev_key'] ) )
            $new_input['dev_key'] = sanitize_text_field( $input['dev_key'] ); 

        if( isset( $input['client_id'] ) )
            $new_input['client_id'] = sanitize_text_field( $input['client_id'] );
			
		if( isset( $input['client_secret'] ) )
            $new_input['client_secret'] = sanitize_text_field( $input['client_secret'] );

		if( isset( $input['redirect_uri'] ) )
            $new_input['redirect_uri'] = sanitize_text_field( $input['redirect_uri'] );

		if( isset( $input['calendar_id'] ) )
            $new_input['calendar_id'] = sanitize_text_field( $input['calendar_id'] ); //absint() for absolute integer value
			
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_api_info()
    {
        print 'Enter your API information below:';
    }

    public function print_redirect_info()
	{
		//$nonce_connect = wp_create_nonce("connect_google_api_nonce");
		//'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&action=connect_google_api&wp_nonce='.$nonce_connect;
		$action_connect = admin_url('admin.php?page=google-calendar-events', 'http');
        print '<h4 style="color:red">Please add below URL to the Redirect URIs field under Credentials Tab in Google Developer Console Project Dashboard</h4><b>'.$action_connect.'</b><br>';
    }
	
	public function print_calendar_info()
    {
        print 'Enter your Calendar information below:';
    }
	
	/** 
     * Get the settings option array and print one of its values
     */
    public function app_name_callback()
    {
        printf(
            '<input type="text" id="app_name" name="gc_options[app_name]" value="%s" size="50" />',
            isset( $this->options['app_name'] ) ? esc_attr( $this->options['app_name']) : ''
        );
    }

	public function dev_key_callback()
    {
        printf(
            '<input type="text" id="dev_key" name="gc_options[dev_key]" value="%s" size="50" />',
            isset( $this->options['dev_key'] ) ? esc_attr( $this->options['dev_key']) : ''
        );
    }
	
	public function client_id_callback()
    {
        printf(
            '<input type="text" id="client_id" name="gc_options[client_id]" value="%s" size="50" />',
            isset( $this->options['client_id'] ) ? esc_attr( $this->options['client_id']) : ''
        );
    }
	
	public function client_secret_callback()
    {
        printf(
            '<input type="text" id="client_secret" name="gc_options[client_secret]" value="%s" size="50" />',
            isset( $this->options['client_secret'] ) ? esc_attr( $this->options['client_secret']) : ''
        );
    }
	
	public function redirect_uri_callback()
    {
        printf(
            '<input type="text" id="redirect_uri" name="gc_options[redirect_uri]" value="%s" size="50" />',
            isset( $this->options['redirect_uri'] ) ? esc_attr( $this->options['redirect_uri']) : ''
        );
    }
	
	public function calendar_id_callback()
    {
        printf(
            '<input type="text" id="calendar_id" name="gc_options[calendar_id]" value="%s" size="50" />',
            isset( $this->options['calendar_id'] ) ? esc_attr( $this->options['calendar_id']) : ''
        );
    }
	
		
	
	//add_action( 'admin_init', array( $this,'my_script_enqueuer' )); will not work here

	public function my_script_enqueuer() {
   		wp_register_script( 'add_event_script', WP_PLUGIN_URL.'/wpgooglecalendar/js/add_event_script.js', array('jquery') );
   		wp_localize_script( 'add_event_script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        

	   	wp_enqueue_script( 'jquery' );
   		wp_enqueue_script( 'add_event_script' );
	}


	private function connect_google_api(){
	
		/*if ( !wp_verify_nonce( $_GET['wp_nonce'], 'connect_google_api_nonce')) {
    	  exit('No naughty business please');
   		}*/ 
   		//print_r($_GET);
		//session_start();

		if ( ! is_admin() ) {
			unset($_SESSION['token']);
		}
		//var_dump($this->client);
	
		if (isset($_GET['code'])) { 
			$this->client->authenticate($_GET['code']);
  			$_SESSION['token'] = $this->client->getAccessToken();
			if (isset($_SESSION['token'])) {
  				$this->client->setAccessToken($_SESSION['token']);
				//echo $this->client->getAccessToken();
				//echo '>>>>> '.$_SESSION['token'];
				//wp_redirect(admin_url('admin.php?page=google-calendar-events','http'));
			}
		}
			if($this->client->getAccessToken()){
				echo 'Google Calendar API successfully connected...<br><h4>List of available calendars in the account:</h4>';
				echo '<pre>'.print_r($this->client,true).'</pre>';
				//$calendars = $this->getCalendarObject()->calendarList->listCalendarList();
				//print_r($calendars, true);
			}
  			//header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		else{
			echo 'Google Calendar API cannot be connected';
			unset($_SESSION['token']);
			exit;
		}
	}

	
	//add_action('wp_ajax_add_new_event', array($this, 'add_new_event')); will not work here
	//add_action('wp_ajax_nopriv_add_new_event', array($this, 'add_new_event')); will not work here

	private function add_new_event() {

	   if ( !wp_verify_nonce( $_POST['wp_nonce'], 'add_event_nonce')) {
    	  exit('No naughty business please');
   		} 
   		//$this->connect_google_api();
	
   	if (isset($_POST['token'])) {
   	//print_r($_POST);exit;
	$event = new Google_Event();
	$event->setSummary($_POST['summary']);
	$event->setLocation($_POST['location']);
	$start = new Google_EventDateTime();
	$start->setDateTime($_POST['start']);
	$event->setStart($start);
	$end = new Google_EventDateTime();
	$end->setDateTime($_POST['end']);
	$event->setEnd($end);
	$attendee1 = new Google_EventAttendee();
	$attendee1->setEmail($_POST['attendee']);
  // ...
	$attendees = array($attendee1,
                   // ...
                  );
	$event->attendees = $attendees;
	$createdEvent = $this->getCalendarObject()->events->insert($this->options['calendar_id'], $event);
	if($createdEvent->getId()){
		echo 'Event with ID '.$createdEvent->getId().' successfully created';
		exit;
   	}else{
		echo 'Event cannot be created';exit;
	}
   }else{
   		echo 'Access Token is empty';exit;
   }
 }
	
/*
	public function add_meta_box() {

		add_meta_box(
			'single-post-meta-manager-admin',
			'Single Post Meta Manager',
			array( $this, 'render_meta_box' ),
			'post',
			'normal',
			'core'
		);

	}

	public function render_meta_box() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/single-post-meta-manager.php';
	}
*/


}
