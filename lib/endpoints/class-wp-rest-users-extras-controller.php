<?php

class WP_REST_Users_Extras_Controller extends WP_REST_Controller {

  public function register_routes() {

    $version = '2';
    $namespace = 'wp/v' . $version;

      register_rest_route( $namespace, '/users/login/(?P<username>[\w-]+)', array(
  			'methods'         => WP_REST_Server::READABLE,
  			'callback'        => array( $this, 'get_item_username' ),
  			//'permission_callback' => array( $this, 'get_item_permissions_check' ),
  			'args'            => array(
  				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
  			),
  			'schema' => array( $this, 'get_public_item_schema' ),
  		));
  }

  public function get_item_username( $request ) {
		$username = $request['username'];
		$user = get_user_by( 'login', $username );

		if ( empty( $username ) || empty( $user->user_login ) ) {
			return new WP_Error( 'rest_user_invalid_username', __( 'Invalid user name.' ), array( 'status' => 404 ) );
		}

		$user = $this->prepare_item_for_response( $user, $request );
		$response = rest_ensure_response( $user );

		return $response;
	}

}


 ?>
