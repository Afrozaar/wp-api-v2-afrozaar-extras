<?php

  class WP_REST_Posts_Extras_Controller extends WP_REST_Controller {

    public function register_routes() {

      $version = '2';
      $namespace = 'wp/v' . $version;

        register_rest_route( $namespace, '/posts/pages', array(
    			'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_item' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));
    }

    public function get_item( $request ) {

      $count_pages = wp_count_posts('page');

      $response = $this->prepare_item_for_response($count_pages);

      return $response;
    }

    /**
  	 * Prepares post pages counts for response
  	 *
  	 * @param object $pages Pages count object.
  	 * @return WP_REST_Response Response data.
  	 */
  	public function prepare_item_for_response( $pages ) {
      $data = array(
            'publish'     => $pages->publish,
            'draft'       => $pages->draft,
            'privatePub'  => $pages->private,
          );

      $response = rest_ensure_response( $data );

      return $response;
    }
  }
