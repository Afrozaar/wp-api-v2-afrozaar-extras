<?php

  class WP_REST_Media_Extras_Controller extends WP_REST_Controller {

    public function register_routes() {

      $version = '2';
      $namespace = 'wp/v' . $version;

        register_rest_route( $namespace, '/posts/(?P<id>[\d]+)/media/(?P<type>[\w-]+)', array(
    			'methods'         => WP_REST_Server::READABLE,
    			'callback'        => array( $this, 'get_items' ),
    			'args'            => array(
    				'context'          => $this->get_context_param( array( 'default' => 'embed' ) ),
    			),
    			'schema' => array( $this, 'get_public_item_schema' ),
    		));
    }

  public function get_items( $request ) {
    $id = $request['id'];
    $type = $request['type'];

    $query_result = get_attached_media( $type, $id );

    $medias = array();
    foreach ( $query_result as $media ) {

      //$mediaItem = json_decode(get_data(rest_url('wp/v2/media/' . $media->ID)));

			$data = $this->prepare_item_for_response( $media, $request );
			$medias[] = $this->prepare_response_for_collection( $data );

      //$medias[] = $data;
		}

    $response = rest_ensure_response( $medias );
    return $response;
  }

  /**
	 * Prepare a single user output for response
	 *
	 * @param object $user User object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response data.
	 */
	public function prepare_item_for_response( $media, $request ) {

		$data = array(
			'id'                 => $media->ID,
      'caption'            => $media->post_excerpt,
      'media_type'         => $media->post_mime_type,
      'source_url'         => wp_get_attachment_url( $media->ID ),
			//'username'           => $user->user_login,
			//'name'               => $user->display_name,
			//'first_name'         => $user->first_name,
			//'last_name'          => $user->last_name,
			//'email'              => $user->user_email,
			//'url'                => $user->user_url,
			//'description'        => $user->description,
			//'link'               => get_author_posts_url( $user->ID ),
			//'avatar_urls'        => rest_get_avatar_urls( $user->user_email ),
			//'nickname'           => $user->nickname,
			//'slug'               => $user->user_nicename,
			//'registered_date'    => date( 'c', strtotime( $user->user_registered ) ),
			//'roles'              => $user->roles,
			//'capabilities'       => $user->allcaps,
			//'extra_capabilities' => $user->caps,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';
		$data = $this->filter_response_by_context( $data, $context );

		$data = $this->add_additional_fields_to_object( $data, $request );

		// Wrap the data in a response object
		$response = rest_ensure_response( $data );

		//$response->add_links( $this->prepare_links( $user ) );

		/**
		 * Filter user data returned from the REST API.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param object           $user      User object used to create response.
		 * @param WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'rest_prepare_media', $response, $media, $request );
	}
  }
 ?>
