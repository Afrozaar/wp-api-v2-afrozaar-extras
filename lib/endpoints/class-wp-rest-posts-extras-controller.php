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
          'permission_callback' => array( $this, 'get_item_permissions_check' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));

        register_rest_route( $this->namespace, '/posts/stream/(?P<date>[\S-]+)', array(
          'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_items_after_date' ),
          'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
      $args                         = array();
      $args['paged']                = $request['page'];
      $args['posts_per_page']       = $request['per_page'];

      $args['date_query'] = array();
      // Set after into date query. Date query must be specified as an array of an array.
  		if ( isset( $request['after'] ) ) {
  			$args['date_query'][0]['after'] = $request['after'];
  		}

      //$date_requested = $request['date'];

      //if ( ! empty( $date_requested ) ) {
      //  $args['date_query'][0]['after'] = $date_requested;
      //}

      $args = apply_filters( "rest_post_query", $args, $request );
      $query_args = $this->prepare_items_query( $args, $request );

      $posts_query = new WP_Query();
  		$query_result = $posts_query->query( $query_args );

      $posts = array();
  		foreach ( $query_result as $post ) {
        if ( ! $this->check_read_permission( $post ) ) {
  				continue;
  			}

        $data = $this->prepare_custom_item_for_response( $post, $request );
        $posts[] = $this->prepare_response_for_collection( $data );
      }

      $page = (int) $query_args['paged'];
      $total_posts = $posts_query->found_posts;

      if ( $total_posts < 1 ) {
        // Out-of-bounds, run the query again without LIMIT for total count
        unset( $query_args['paged'] );
        $count_query = new WP_Query();
        $count_query->query( $query_args );
        $total_posts = $count_query->found_posts;
      }

      $max_pages = ceil( $total_posts / (int) $query_args['posts_per_page'] );

      $response = rest_ensure_response( $posts );
      $response->header( 'X-WP-Total', (int) $total_posts );
      $response->header( 'X-WP-TotalPages', (int) $max_pages );

      $request_params = $request->get_query_params();
  		if ( ! empty( $request_params['filter'] ) ) {
  			// Normalize the pagination params.
  			unset( $request_params['filter']['posts_per_page'] );
  			unset( $request_params['filter']['paged'] );
  		}

      return $response;
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

      if ( ! empty( $schema['properties']['modified'] ) ) {
  			$data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );
  		}

      if ( ! empty( $schema['properties']['title'] ) ) {
  			$data['title'] = $post->post_title;
  		}

      if ( ! empty( $schema['properties']['author'] ) ) {
  			$data['author'] = (int) $post->post_author;
  		}

      $user = get_userdata( $data['author'] );
      $data['author_name'] = $user->display_name;

      if ( ! empty( $schema['properties']['status'] ) ) {
  			$data['status'] = $post->post_status;
  		}

      if ( ! empty( $schema['properties']['featured_media'] ) ) {
        $data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
      }

      if ( ! empty( $data['featured_media'] ) && $data['featured_media'] != 0 ) {
        $data['media_url'] = wp_get_attachment_url($data['featured_media']);
      }

      // get the META for the posts

  		global $wpdb;
  		$table = _get_meta_table( 'post' );
      $parent_column = 'post_id';
      $id_column = 'meta_id';
      $parent_id = (int) $data['id'];

      // @codingStandardsIgnoreStart
      $results = $wpdb->get_results( $wpdb->prepare( "SELECT $id_column, $parent_column, meta_key, meta_value FROM $table WHERE $parent_column = %d", $parent_id ) );
      // @codingStandardsIgnoreEnd

  		$meta = array();

  		foreach ( $results as $row ) {
        $key = $row->meta_key;

        if ( hash_equals($key, 'az_images') ) {
          $data['image_count'] = $row->meta_value;
        } else if ( hash_equals($key, 'az_videos') ) {
          $data['video_count'] = $row->meta_value;
        } else if ( hash_equals($key, 'az_audio') ) {
          $data['audio_count'] = $row->meta_value;
        } else if ( hash_equals($key, 'az_address') ) {
          $data['location_count'] = 1;
        }
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

    /**
  	 * Check if a given request has access to read /posts.
  	 *
  	 * @param  WP_REST_Request $request Full details about the request.
  	 * @return WP_Error|boolean
  	 */
  	public function get_items_permissions_check( $request ) {

  		$post_type = get_post_type_object( $this->post_type );

  		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
  			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit these posts in this post type' ), array( 'status' => rest_authorization_required_code() ) );
  		}

  		return true;
  	}
  }
