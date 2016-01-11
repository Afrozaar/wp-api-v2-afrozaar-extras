<?php

/*
Plugin Name: WP REST Api Extras

Description:  Adds extra WP REST Api calls

Version:  0.2

Author: Jan-Louis Crafford, Afrozaar Consulting

*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php');

if (!is_plugin_active('rest-api/plugin.php')) {
  add_action('admin_notices', 'pim_draw_notice_rest_api');
  return;
}

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


    function pim_draw_notice_rest_api() {
      echo '<div id="message" class="error fade"><p style="line-height: 150%">';

    _e('<strong>WP REST API - Afrozaar Extras</strong></a> requires the WP REST API plugin to be activated. Please <a href="http://wordpress.org/plugins/rest-api/">install / activate WP REST API</a> first.', 'rest-api');

    echo '</p></div>';

    }

 ?>
