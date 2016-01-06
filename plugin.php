<?php

/*
Plugin Name: WP REST Api Extras

Description:  Adds extra WP REST Api calls

Version:  0.1

Author: Jan-Louis Crafford, Afrozaar Consulting

*/

/*  include_once( ABSPATH . 'wp-admin/includes/plugin.php');

  if (!is_plugin_active('rest-api/plugin.php')) {
    add_action('admin_notices', 'pim_draw_notice_json_api');
    return;
  }

*/
  require_once dirname( __FILE__ ) . '/lib/endpoints/class-wp-rest-users-extras-controller.php';

  add_action( 'rest_api_init', function() {
    // Users extras.
  	$controller = new WP_REST_Users_Extras_Controller;
  	$controller->register_routes();
  });

/*  function pim_draw_notice_json_api() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';

    _e('<strong>JSON API Android</strong></a> requires the JSON API plugin to be activated. Please <a href="wordpress.org/plugins/json-api/‎">install / activate JSON API</a> first.', 'json-android-api');

    echo '</p></div>';
  }
*/

    /**
     * Prepare the item for the REST response
     *
     * @param mixed $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return mixed
     */
    public function prepare_item_for_response( $item, $request ) {
        return array();
    }

 ?>
