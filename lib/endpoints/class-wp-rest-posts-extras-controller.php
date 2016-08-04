<?php

  class WP_REST_Posts_Extras_Controller extends WP_REST_Posts_Controller {

    function __construct() {
       parent::__construct('post');
     }

    /**
    * Register the routes for the objects of the controller.
    */
    public function register_routes() {

        register_rest_route( $this->namespace, '/posts/stream', array(
          'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_items_after_date' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));

        register_rest_route( $this->namespace, '/posts/stream/(?P<date>[\S-]+)', array(
          'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_items_after_date' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));
    }

    /**
    * Get a collection of posts, optionally filtered by date.
    * Uses the 'after' query param to get posts made after a certain time.
    * Param value uses the same date formatting as normal Post date fields, eg:
    * '2016-07-30T10:00:00'
  	*
  	* @param WP_REST_Request $request Full details about the request.
  	* @return WP_Error|WP_REST_Response
  	*/
    public function get_items_after_date( $request ) {
      $date_requested = $request['date'];

      if ( ! empty( $date_requested ) ) {
        $args['date_query'][0]['after'] = $date_requested;
      }

      $args = apply_filters( "rest_post_query", $args, $request );

      $posts_query = new WP_Query();
  		$query_result = $posts_query->query( $args );

      $posts = array();
  		foreach ( $query_result as $post ) {

        $data = $this->prepare_custom_item_for_response( $post, $request );
        $posts[] = $this->prepare_response_for_collection( $data );
      }

      return $posts;
      //return $query_result;
    }

    /**
  	 * Prepares the custom post response
  	 *
  	 * @param object $post Post counts object.
  	 * @return WP_REST_Response Response data.
  	 */
  	public function prepare_custom_item_for_response( $post, $request ) {

      $schema = $this->get_item_schema();

  		// Base fields for every post.
  		$data = array();

  		if ( ! empty( $schema['properties']['id'] ) ) {
  			$data['id'] = $post->ID;
  		}

  		if ( ! empty( $schema['properties']['date'] ) ) {
  			$data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );
  		}

      if ( ! empty( $schema['properties']['title'] ) ) {
  			$data['title'] = $post->post_title;
  		}

      if ( ! empty( $schema['properties']['author'] ) ) {
  			$data['author'] = (int) $post->post_author;
  		}

      if ( ! empty( $schema['properties']['featured_media'] ) ) {
        $data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
      }

      // Wrap the data in a response object.
  		$response = rest_ensure_response( $data );

  		/**
  		 * Filter the post data for a response.
  		 *
  		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
  		 * prepared for the response.
  		 *
  		 * @param WP_REST_Response   $response   The response object.
  		 * @param WP_Post            $post       Post object.
  		 * @param WP_REST_Request    $request    Request object.
  		 */
  		return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );
    }
  }
