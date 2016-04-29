<?php

  class WP_REST_Posts_Extras_Controller extends WP_REST_Controller {

    public function register_routes() {

      $version = '2';
      $namespace = 'wp/v' . $version;

        register_rest_route( $namespace, '/posts/counts', array(
    			'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_item' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));
    }

    public function get_item( $request ) {
      $count_posts = wp_count_posts();

      $response = $this->prepare_item_for_response($count_posts);

      return $response;
    }

    /**
  	 * Prepares post counts for response
  	 *
  	 * @param object $posts Post counts object.
  	 * @return WP_REST_Response Response data.
  	 */
  	public function prepare_item_for_response( $posts ) {
      $data = array(
            'publish'     => $posts->publish,
            'draft'       => $posts->draft,
            'privatePub'  => $posts->private,
          );

      $response = rest_ensure_response( $data );

      return $response;
    }
  }
