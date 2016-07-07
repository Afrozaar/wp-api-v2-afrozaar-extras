<?php

class Afrozaar_Aws_Extras extends Afro_Plugin_Base {

	/**
	 * @var string
	 */
	private $plugin_title;

	/**
	 * @var string
	 */
	private $plugin_menu_title;

	/**
	 * @var string
	 */
	private $plugin_permission;

	/**
	 * @var
	 */
	private $client;

	const SETTINGS_KEY  = 'afro_settings';
	const SETTINGS_CONSTANT = 'AFRO_SETTINGS';

	/**
	* @param string $plugin_file_path
	*/
	function __construct( $plugin_file_path ) {
		$this->plugin_slug = 'afrozaar-extras';

		parent::__construct( $plugin_file_path );

		//do_action( 'aws_init', $this );

		//if ( is_admin() ) {
		//	do_action( 'aws_admin_init', $this );
		//}

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_network_options';
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_options';
		}

		$this->plugin_title	= __( 'Afrozaar Extras', 'afrozaar-extras' );
		$this->plugin_menu_title = __( ' Afrozaar Extras', 'afrozaar-extras' );

		add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2);

		//load_plugin_textdomain( 'amazon-web-services', false, dirname( plugin_basename( $plugin_file_path ) ) . '/languages/' );
	}

	function admin_menu() {
		if ( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			//$icon_url = plugins_url( 'assets/img/icon16.png', $this->plugin_file_path );
			$icon_url = false;
		} else {
			$icon_url = false;
		}

		$hook_suffixes = array();
		$hook_suffixes[] = add_menu_page( $this->plugin_title, $this->plugin_menu_title, $this->plugin_permission, $this->plugin_slug, array( $this, 'render_page' ), $icon_url );

		do_action ( 'afro_admin_menu', $this );

		foreach ( $hook_suffixes as $hook_suffix ) {
			add_action( 'load-' . $hook_suffix, array( $this, 'plugin_load' ) );
		}
	}

	/**
	 * Plugin loading enqueue scripts and styles
	 */
	function plugin_load() {
		$version = $this->get_asset_version();
		$suffix  = $this->get_asset_suffix();

		$src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
		wp_enqueue_style( 'aws-styles', $src, array(), $version );

		$src = plugins_url( 'assets/js/script' . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( 'aws-script', $src, array( 'jquery' ), $version, true );

		if ( isset( $_GET['page'] ) && 'aws-addons' == sanitize_key( $_GET['page'] ) ) { // input var okay
			add_filter( 'admin_body_class', array( $this, 'admin_plugin_body_class' ) );
			wp_enqueue_script( 'plugin-install' );
			add_thickbox();
		}

		$this->handle_post_request();

		do_action( 'aws_plugin_load', $this );
	}

	/**
	 * Process the saving of the settings form
	 */
	function handle_post_request() {
		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'afro-save-settings' ) ) { // input var okay
			die( __( "Cheatin' eh?", 'afrozaar-extras' ) );
		}

		// Make sure $this->settings has been loaded
		$this->get_settings();

		$post_vars = array( 'access_key_id', 'secret_access_key', 'aws_region' );
		foreach ( $post_vars as $var ) {
			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			if ( 'secret_access_key' == $var && '-- not shown --' == $value ) {
				continue;
			}

			$this->set_setting( $var, $value );
		}

		$this->save_settings();
	}

	/**
	 * Render the output of a page
	 */
	function render_page() {
		$view       = 'settings';
		$page_title = __( 'Afrozaar Extras', 'afrozaar-extras' );

		if ( empty( $_GET['page'] ) ) { // input var okay
			// Not sure why we'd ever end up here, but just in case
			wp_die( 'What the heck are we doin here?' );
		}

		$this->render_view( 'header', array( 'page' => $view, 'page_title' => $page_title ) );
		$this->render_view( $view );
		$this->render_view( 'footer' );
	}

	/**
	 * Check if we are using constants for the AWS access credentials
	 *
	 * @return bool
	 */
	function are_key_constants_set() {
		return defined( 'AWS_ACCESS_KEY_ID' ) && defined( 'AWS_SECRET_ACCESS_KEY' );
	}

	/**
	 * Check if we are using the prefixed constants for the AWS access credentials
	 *
	 * @return bool
	 */
	function are_prefixed_key_constants_set() {
		return defined( 'DBI_AWS_ACCESS_KEY_ID' ) && defined( 'DBI_AWS_SECRET_ACCESS_KEY' );
	}

	/**
	 * Check if access keys are defined either by constants or database
	 *
	 * @return bool
	 */
	function are_access_keys_set() {
		return $this->get_access_key_id() && $this->get_secret_access_key();
	}

	/**
	 * Get the AWS key from a constant or the settings
	 *
	 * @return string
	 */
	function get_access_key_id() {
		if ( defined( 'DBI_AWS_ACCESS_KEY_ID' ) ) {
			return DBI_AWS_ACCESS_KEY_ID;
		} elseif ( defined( 'AWS_ACCESS_KEY_ID' ) ) {
			return AWS_ACCESS_KEY_ID; // Deprecated
		}

		return $this->get_setting( 'access_key_id' );
	}

	/**
	 * Get the AWS secret from a constant or the settings
	 *
	 * @return string
	 */
	function get_secret_access_key() {
		if ( defined( 'DBI_AWS_SECRET_ACCESS_KEY' ) ) {
			return DBI_AWS_SECRET_ACCESS_KEY;
		} elseif ( defined( 'AWS_SECRET_ACCESS_KEY' ) ) {
			return AWS_SECRET_ACCESS_KEY; // Deprecated
		}

		return $this->get_setting( 'secret_access_key' );
	}

	/**
	* Get the AWS Region
	*
	* @return string
	*/
	function get_aws_region() {
		return $this->get_setting( 'aws_region' );
	}

	/**
	 * Instantiate a new AWS service client for the AWS SDK
	 * using the defined AWS key and secret
	 *
	 * @return SnsClient|WP_Error
	 */
	function get_client() {
		if ( ! $this->get_access_key_id() || ! $this->get_secret_access_key() ) {
			return new WP_Error( 'access_keys_missing', sprintf( __( 'You must first <a href="%s">set your AWS access keys</a> to use this addon.', 'amazon-web-services' ), 'admin.php?page=' . $this->plugin_slug ) ); // xss ok
		}

		if ( is_null( $this->client ) ) {
			$args = array();

			$args = array(
				'key'    => $this->get_access_key_id(),
				'secret' => $this->get_secret_access_key(),
				'region' => $this->get_aws_region(),
			);

			$args         = apply_filters( 'aws_get_client_args', $args );

			$this->$client = SnsClient::factory( $args );
		}

		return $this->client;
	}

	/**
	 * Customize the link text on the plugins page
	 *
	 * @return string
	 */
	function get_plugin_action_settings_text() {
		return __( 'Afrozaar Setup', 'afrozaar-extras' );
	}



































}
