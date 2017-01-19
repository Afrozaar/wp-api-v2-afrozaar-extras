<?php
use Aws\Sns\SnsClient;
use Aws\DynamoDb\Exception\DynamoDbException;

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

	private $dynamoDb;

	const SETTINGS_KEY  = 'afro_settings';
	const SETTINGS_CONSTANT = 'AFRO_SETTINGS';

	/**
	* Constants for the Push Message types
	*/
	const PUSH_TYPE_POST_NEW = 'POST_NEW';
	const PUSH_TYPE_POST_UPDATED = 'POST_UPDATED';
	const PUSH_TYPE_CHAT_MSG = 'CHAT_MSG';
	const PUSH_TYPE_COMMENT = 'COMMENT';

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

		//if ( is_multisite() ) {
		//	add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
		//	$this->plugin_permission = 'manage_network_options';
		//} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			$this->plugin_permission = 'manage_options';
		//}

		$this->plugin_title	= __( 'Afrozaar Extras', 'afrozaar-extras' );
		$this->plugin_menu_title = __( ' Afrozaar Extras', 'afrozaar-extras' );

		add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2);

		//load_plugin_textdomain( 'amazon-web-services', false, dirname( plugin_basename( $plugin_file_path ) ) . '/languages/' );

		add_action('publish_post', array( $this, 'hook_publish_post' ), 10, 2);

		add_action('draft_post', array( $this, 'hook_draft_post' ), 10, 2);

		add_action('comment_post', array( $this, 'hook_comment_post' ), 10, 2);

		add_action( 'wp_trash_post', array( $this, 'hook_trash_post' ), 10, 2);

		add_action('deleted_post_meta', array( $this, 'hook_delete_meta_data' ), 10, 4);

		add_action('publish_post', array( $this, 'hook_delete_meta_value' ), 10, 2);
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

		$post_vars = array( 'access_key_id', 'secret_access_key', 'aws_region', 'new_post_topic', 'updated_post_topic', 'comment_post_topic', 'mojo_site', 'baobab_meta_key' );
		foreach ( $post_vars as $var ) {
			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			if ( 'secret_access_key' == $var && '-- HIDDEN --' == $value ) {
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
		$page_title = __( 'Afrozaar AWS Extras', 'afrozaar-extras' );

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

	function get_new_post_topic() {
		return $this->get_setting( 'new_post_topic' );
	}

	function get_updated_post_topic() {
		return $this->get_setting( 'updated_post_topic' );
	}

	function get_comment_post_topic() {
		return $this->get_setting( 'comment_post_topic' );
	}

	function get_mojo_site() {
		return $this->get_setting( 'mojo_site' );
	}

	function get_baobab_meta_key() {
		return $this->get_setting( 'baobab_meta_key' );
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

	/**
	* Code to run when a Post gets published
	*/
	function hook_publish_post($post_id, $post) {

	  // Checks whether is post updated or published at first time.
	  if ($post->post_date != $post->post_modified) {
	    $topic_arn = $this->get_updated_post_topic();
			$is_new_post = false;
			$push_type = self::PUSH_TYPE_POST_UPDATED;
	  } else {
	    $topic_arn = $this->get_new_post_topic();
			$is_new_post = true;
			$push_type = self::PUSH_TYPE_POST_NEW;
	  }

		if ( ! $this->get_access_key_id() || ! $this->get_secret_access_key() ) {
			return new WP_Error( 'access_keys_missing', sprintf( __( 'You must first <a href="%s">set your AWS access keys</a> to use this addon.', 'amazon-web-services' ), 'admin.php?page=' . $this->plugin_slug ) ); // xss ok
		}

		$user = get_userdata( $post->post_author );

		if ($is_new_post) {
			$message_type = '(N)';
			$bool_val = 'true';
		} else {
			$message_type = '(U)';
			$bool_val = 'false';
		}

		$alert = $message_type . ' ' . $user->display_name . ' : ' . $post->post_title;

		$msg_encoded = '\"msgType\":\"' . $push_type . '\",\"postId\":' . $post_id . ',\"title\":\"' . $post->post_title . '\",\"author\":\"' . $user->display_name . '\",\"authorId\":' . $user->ID . ',\"newPost\":' . $bool_val . '';

		$this->amazonSnsPush($alert, $msg_encoded, $topic_arn);

		$this->amazon_add_map_marker( $post_id, $post->post_date, $post->post_title, $user->display_name );
	}

	function hook_delete_meta_value( $post_id, $post ) {

		// Checks whether is post updated or published at first time.
	  if ($post->post_date != $post->post_modified) {
			$is_new_post = false;
	  } else {
			$is_new_post = true;
	  }

		if (!$is_new_post) {

			$config_key = $this->get_baobab_meta_key();

			if ( !empty($config_key) ) {

					//error_log('==================== key not empty deleting');

					$return = delete_post_meta($post_id, $config_key, '');
			} else {
				//error_log('==================== key EMPTY');
			}
	  }
	}

	/**
	* Code to run when a Post status is marked as Draft
	*/
	function hook_draft_post( $post_id, $post ) {

		//error_log('============================== this is a error log on the DRAFT POST HOOK');

		$location_meta = get_post_meta($post_id, 'az_address', true);

		if (!empty($location_meta)) {
			$this->amazon_remove_map_marker($post_id);
		}
	}

	/**
	* Code to run when a Post gets tras
	*/
	function hook_trash_post( $post_id ) {

		//error_log('============================== this is a error log on the TRASH POST HOOK');

		$location_meta = get_post_meta($post_id, 'az_address', true);

		if (!empty($location_meta)) {
			$this->amazon_remove_map_marker($post_id);
		}
	}

/**
* Runs when meta objects get deleted. If meta object is location, remove post marker from AW
*/
	function hook_delete_meta_data( $deleted_meta_ids, $post_id, $meta_key, $only_delete_these_meta_values ) {

			if ( $meta_key == 'az_address' ) {
				//error_log('==========-------------------------- DELETE ADDRESS META');

				$this->amazon_remove_map_marker($post_id);
			}
	}

	/**
	* Creates a new DB entry on DynamoDB for a post marker
	*/
	function amazon_add_map_marker( $post_id, $post_date, $post_title, $author_name) {
		$location_meta = get_post_meta($post_id, 'az_address', true);

		try {
			if ( !empty($location_meta) ) {
					$location_lat = get_post_meta($post_id, 'az_latitude', true);
					$location_long = get_post_meta($post_id, 'az_longitude', true);

					if ( is_null($this->dynamoDb) ) {
						$sdk = new Aws\Sdk([
							'region' => $this->get_aws_region(),
							'version' => 'latest',
							'credentials' => [
								'key'    => $this->get_access_key_id(),
								'secret' => $this->get_secret_access_key(),
							],
						]);

							$dynamoDb = $sdk->createDynamoDb();
					}

					$tableName = 'mojo-map-posts';
					$timestamp = strtotime($post_date);

					$response = $dynamoDb->putItem([
						'TableName' => $tableName,
						'Item' => [
							'mojoSite' => ['S' => $this->get_mojo_site() ],
							'postId' => ['N' => $post_id . '' ],
							'authorName' => ['S' => $author_name ],
							'postTitle' => ['S' => $post_title ],
							'latitude' => ['N' => $location_lat . '' ],
							'longitude' => ['N' => $location_long . '' ],
							'dateCreated' => ['N' => $timestamp . '' ],
						],
					]);
			}
		} catch (Exception $e) {
			error_log("Unable to upload map post marker to DynamoDB " . $e->getMessage());
		}
	}

	/**
	* Removes a DB entry on DynamoDB for a post marker
	*/
	function amazon_remove_map_marker( $post_id ) {

		//error_log('============================== this is a error log on the REMOVE MAP MARKER');

		try {
			if ( is_null($this->dynamoDb) ) {
				$sdk = new Aws\Sdk([
					'region' => $this->get_aws_region(),
					'version' => 'latest',
					'credentials' => [
						'key'    => $this->get_access_key_id(),
						'secret' => $this->get_secret_access_key(),
					],
				]);

					$dynamoDb = $sdk->createDynamoDb();
			}

			$tableName = 'mojo-map-posts';

			$response = $dynamoDb->deleteItem ( [
				'TableName' => $tableName,
	    	'Key' => [
					'mojoSite' => ['S' => $this->get_mojo_site() ],
					'postId' => ['N' => $post_id . '' ],
	    	],
	    	'ReturnValues' => 'ALL_OLD'
			]);

		} catch(Exception $e) {
			error_log("Unable to remove map post marker from DynamoDB " . $e->getMessage());
		}
	}

	/**
	* Sends an SNS push when making a new comment
	*/
	function hook_comment_post($comment_id, $comment_approved) {
			//error_log('this is a error log on the comment hook');

			if( 1 === $comment_approved ) {
				$comment = get_comment( $comment_id );

				if ( empty( $comment ) ) {
					//return new WP_Error( 'rest_comment_invalid_id', __( 'Invalid comment id.' ), array( 'status' => 404 ) );
					//error_log('============================= empty comment for id ' . $comment_id);
					return;
				}

				$topic_arn_comment = $this->get_comment_post_topic();

				$post = get_post( $comment->comment_post_ID );
				$user = get_userdata( $post->post_author );

				$msg_encoded = '\"msgType\":\"' . self::PUSH_TYPE_COMMENT . '\",\"postId\":' . $comment->comment_post_ID . ',\"author\":\"' . $user->display_name . '\",\"authorId\":' . $user->ID . ',\"msgText\":\"' . $comment->comment_content . '\"';
				$alert = "bla";

				$this->amazonSnsPush($alert, $msg_encoded, $topic_arn_comment);
    	}
	}

	/**
	* Creates and sends SNS push
	*/
	function amazonSnsPush($alert, $msg_encoded, $topic_arn) {
		$message = '{
			"default": "{' . $msg_encoded . '}",
			"APNS": "{\"aps\":{\"alert\": \"' . $alert . '\"}, \"custom\":{' . $msg_encoded . '}}",
			"APNS_SANDBOX":"{\"aps\":{\"alert\": \"' . $alert . '\"}, \"custom\":{' . $msg_encoded . '}}"
		}';

		if ( is_null( $this->client ) ) {
			$args = array();

			$args = array(
				//'key'    => $this->get_access_key_id(),
				//'secret' => $this->get_secret_access_key(),
				'region' => $this->get_aws_region(),
				//'version' => 'latest',
				'version' => '2010-03-31',
				'credentials' => [
        	'key'    => $this->get_access_key_id(),
        	'secret' => $this->get_secret_access_key(),
    		]
			);

			$args         = apply_filters( 'aws_get_client_args', $args );

			$this->client = SnsClient::factory( $args );
		}

		try {
			$result = $this->client->publish(array(
				'TopicArn' => $topic_arn,
				'Message' => $message,
				'MessageStructure' => 'json',
			));
		} catch (Exception $e) {
			error_log("===== got the exception from publish call : " . $e->getMessage());
		}
	}

	/**
	* Gets Topic name from ARN
	*/
	function getTopicNameFromArn($topic_arn) {

		// Topics format as follow:
		// arn:aws:sns:eu-west-1:840066320465:wp-kmtv-mojo-test-comment
		// 0  : 1 : 2 : 3       : 4          : 5

		$topic_parts = explode(":", $topic_arn);

		return $topic_parts[5];
	}
}
