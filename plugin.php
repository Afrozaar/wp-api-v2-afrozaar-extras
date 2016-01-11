<?php

/*
Plugin Name: WP REST Api Extras

Description:  Adds extra WP REST Api calls

Version:  0.1

Author: Jan-Louis Crafford, Afrozaar Consulting

*/

  require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-users-extras-controller.php';

  require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-media-extras-controller.php';

  add_action( 'rest_api_init', function() {
    // Users extras.
  	$controller = new WP_REST_Users_Extras_Controller;
  	$controller->register_routes();

    // Media extras
    $controller = new WP_REST_Media_Extras_Controller;
    $controller->register_routes();
  });

  /*add_filter( 'pre_kses', function($data) {
    global $allowedtags;
    $allowedtags['script'] = array();
    return $data;
  });*/

  $allowedposttags['script'] = array(
			'type' => array (),
		);

 ?>
