<?php

/*
 * Plugin Name: WP REST Api Extras
 * Description:  Adds extra WP REST Api calls
 * Version:  0.3
 * Author: Jan-Louis Crafford, Afrozaar Consulting
 * Plugin URI: https://github.com/Afrozaar/wp-api-v2-afrozaar-extra
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

  /*$allowedposttags['script'] = array(
			'type' => array (),
		);
*/

    // Add settings link on plugin page
    function afrozaar_add_plugin_link($links) {
      $links[] = '<a href="options-general.php?page=plugin.php">Settings</a>';
      //array_unshift($links, $settings_link);
      return $links;
    }

    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", 'afrozaar_add_plugin_link');

    // Draws notice in case parent plugin not available
    function pim_draw_notice_rest_api() {
      echo '<div id="message" class="error fade"><p style="line-height: 150%">';

    _e('<strong>WP REST API - Afrozaar Extras</strong></a> requires the WP REST API plugin to be activated. Please <a href="http://wordpress.org/plugins/rest-api/">install / activate WP REST API</a> first.', 'rest-api');

    echo '</p></div>';

    }


    function modify_afrozaar_menu() {
      add_options_page(
            'Afrozaar Options', // page title
            'Afrozaar Options', // sub-menu title
            'manage_options', // access/capabilities
            'plugin.php', // file
            'admin_afrozaar_options' // function
      );
    }

    add_action('admin_menu', 'modify_afrozaar_menu');

    // enables options
    function set_afrozaar_options() {
      $allow_kses_script = get_option('allow_kses_script');
    }

    // disables options
    function unset_afrozaar_options() {
      delete_option('allow_kses_script');
    }

    function admin_afrozaar_options() {
      if ($_REQUEST['submit'])
          update_afrozaar_options();

          print_afrozaar_form();
    }

    function update_afrozaar_options() {
      foreach (array (
        "allow_kses_script"
      ) as $option)

      {
        if (isset($_REQUEST[$option])) {
          update_option($option, $_REQUEST[$option]);
        } else {
          delete_option($option);
        }
      }

      echo '<div id="message" class="updated fade">
              <p>Options saved.</p>
            </div>
      ';
    }

    function print_afrozaar_form() {

      $allow_kses_script_selected = '';

      if (get_option('allow_kses_script') == "yes")
          $allow_kses_script_selected = 'checked';
      else
          $allow_kses_script_selected = '';


      if ( CUSTOM_TAGS ) {
            $style = "red";
            $status = "The 'CUSTOM_TAGS' variable is set. Any tags enabled here won't work!";
      }   else {
          $style = "green";
          $status = "We are good to go!";
      }

      print "

      <div class='wrap'>
          <h2>Afrozaar WordPress custom tags</h2>

          <p><strong>Plugin check:</strong> <span style='color: $style'>$status</span></p>

          <form method='post'>
              <ul>
                  <li><input type='checkbox' name='allow_kses_script' value='yes' $allow_kses_script_selected />Allow &lt;script&gt; tags in Post body</li>
              </ul>

              <br />
              <input type='submit' name='submit' class='button-primary' value='Save options' />

          </form>
      </div>
      ";
    }

    if (!CUSTOM_TAGS) {

      if (get_option('allow_kses_script') == "yes") {
        $allowedposttags['script'] = array(
            'type' => array (),
          );
      }
    }

register_activation_hook(__FILE__,'set_kses_options');
register_deactivation_hook(__FILE__,'unset_kses_options');

 ?>
