<?php

class WP_REST_Users_Extras_Controller extends WP_REST_Controller {

  public function register_routes() {

    $version = '2';
    $namespace = 'wp/v' . $version;

      register_rest_route( $namespace, '/users/login/(?P<username>[\w-]+)', array(
  			'methods'         => WP_REST_Server::READABLE,
  			'callback'        => array( $this, 'get_item_username' ),
  			'args'            => array(
  				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
  			),
  			'schema' => array( $this, 'get_public_item_schema' ),
  		));

      register_rest_route( $namespace, '/users/email/(?P<email>[\S-]+)', array(
        'methods'       => WP_REST_Server::READABLE,
        'callback'      => array( $this, 'get_item_username' ),
        'args'          => array(
          'context'       => $this->get_context_param( array( 'default' => 'embed' ) ),
        ),
        'schema'        => array ( $this, 'get_public_item_schema' ),
      ));
  }

  public function get_item_username( $request ) {
		$username = $request['username'];
    $email = urldecode($request['email']);

    if ( ! empty( $username ) ) {
      $user = get_user_by( 'login', $username );
    } elseif (! empty( $email ) ) {
      $user = get_user_by('email', $email);
    }

    if ((empty($username) && (empty($email)) || empty($user->user_login))) {
      return new WP_Error( 'rest_user_invalid_username', __( 'Invalid user name.' ), array( 'status' => 404 ) );
    }

    $user_id = $user->ID;
    $user_blogs = get_blogs_of_user($user_id);

    $is_valid_for_site = false;
    foreach ($user_blogs AS $user_blog) {
      $blog_name = $user_blog->siteurl;

      //error_log("=================== siteurl ".$blog_name);
      if (strpos($blog_name, $_SERVER['SERVER_NAME']) !== false) {
        //error_log("=================== siteurl is valid!!!!!!");
        $is_valid_for_site = true;
        break;
      }
    }

    if ($is_valid_for_site == false) {
        return new WP_Error( 'rest_user_invalid_site', __( 'User not allowed for site.'), array( 'status' => 404 ) );

        //$blog_details = get_blog_details($_SERVER['SERVER_NAME']);

        //$blogid = $blog_details->blog_id;
        //$role = 'author';
        //if (! add_user_to_blog( $blogid, $user_id, $role ) ) {
        //  return new WP_Error( 'rest_user_invalid_site', __( 'User not allowed for site.'), array( 'status' => 404 ) );
        //}
    }

		$user = $this->prepare_item_for_response( $user, $request );

		$response = rest_ensure_response( $user );

		return $response;
	}

  /**
	 * Prepare a single user output for response
	 *
	 * @param object $user User object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response data.
	 */
	public function prepare_item_for_response( $user, $request ) {

    $roles = $user->roles;
    if ( empty($roles)) {
      $isadmin = false;
    } else {
      $isadmin = hash_equals($roles[0], 'administrator');
    }

    //$user_id = $user->ID;
    //$user_blogs = get_blogs_of_user($user_id);

    //$site = urldecode($request['site']);

    //error_log('Current site is ' .  $_SERVER['SERVER_NAME']);
    //error_log('Blog '.$blog_details->blog_id.' is called '.$blog_details->blogname.'.');

		$data = array(
			'id'                 => $user->ID,
			'username'           => $user->user_login,
			'name'               => $user->display_name,
			//'first_name'         => $user->first_name,
			//'last_name'          => $user->last_name,
			'email'              => $user->user_email,
			//'url'                => $user->user_url,
			//'description'        => $user->description,
			//'link'               => get_author_posts_url( $user->ID ),
			//'avatar_urls'        => rest_get_avatar_urls( $user->user_email ),
			//'nickname'           => $user->nickname,
			//'slug'               => $user->user_nicename,
			//'registered_date'    => date( 'c', strtotime( $user->user_registered ) ),
			//'roles'              => $user->roles,
      'admin'              => $isadmin,
      'role'               => $roles[0],
			//'capabilities'       => $user->allcaps,
			//'extra_capabilities' => $user->caps,
      //'site'                => $_SERVER['SERVER_NAME'],
      //'host'                => $_SERVER['HTTP_HOST'],
      //'blogs'               => $user_blogs,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';
		$data = $this->filter_response_by_context( $data, $context );

		$data = $this->add_additional_fields_to_object( $data, $request );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		/**
		 * Filter user data returned from the REST API.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param object           $user      User object used to create response.
		 * @param WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'rest_prepare_user', $response, $user, $request );
	}
}


 ?>
