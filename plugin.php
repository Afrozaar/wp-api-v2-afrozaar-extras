<?php

  /*
  * Plugin Name: WP REST Api Extras
  * Description:  Adds extra WP REST Api calls
  * Version:  0.32
  * Author: Jan-Louis Crafford, Afrozaar Consulting
  * Plugin URI: https://github.com/Afrozaar/wp-api-v2-afrozaar-extras
  */

include_once ABSPATH.'wp-admin/includes/plugin.php';

require_once dirname(__FILE__).'/lib/endpoints/class-wp-rest-users-extras-controller.php';
require_once dirname(__FILE__).'/lib/endpoints/class-wp-rest-media-extras-controller.php';
require_once dirname(__FILE__).'/lib/endpoints/class-wp-rest-posts-extras-controller.php';

add_action('rest_api_init', function () {
  // Users extras.
  $controller = new WP_REST_Users_Extras_Controller();
  $controller->register_routes();

  // Media extras
  $controller = new WP_REST_Media_Extras_Controller();
  $controller->register_routes();

  // Posts extras
  $controller = new WP_REST_Posts_Extras_Controller();
  $controller->register_routes();
});

// Add settings link on plugin page
function afrozaar_add_plugin_link($links)
{
    $links[] = '<a href="options-general.php?page=plugin.php">Settings</a>';
  //array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'afrozaar_add_plugin_link');

// Draws notice in case parent plugin not available
function pim_draw_notice_rest_api()
{
    echo "<div id='message' class='error fade'><p style='line-height: 150%'>";
    _e('<strong>WP REST API - Afrozaar Extras</strong></a> requires the WP REST API plugin to be activated. Please <a href="http://wordpress.org/plugins/rest-api/">install / activate WP REST API</a> first.', 'rest-api');
    echo '</p></div>';
}

function modify_afrozaar_menu()
{
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
function set_afrozaar_options()
{
    $allow_kses_script = get_option('allow_kses_script');
}

// disables options
function unset_afrozaar_options()
{
    delete_option('allow_kses_script');
}

function admin_afrozaar_options()
{
    if ($_REQUEST['submit']) {
        update_afrozaar_options();
    }
    print_afrozaar_form();
}

function update_afrozaar_options()
{
    foreach (array('allow_kses_script') as $option) {
        if (isset($_REQUEST[$option])) {
            update_option($option, $_REQUEST[$option]);
        } else {
            delete_option($option);
        }
    }

    echo "
        <div id='message' class='updated fade'>
            <p>Options saved.</p>
        </div>
  ";
}

function print_afrozaar_form()
{
    $allow_kses_script_selected = '';

    if (get_option('allow_kses_script') == 'yes') {
        $allow_kses_script_selected = 'checked';
    } else {
        $allow_kses_script_selected = '';
    }

    if (CUSTOM_TAGS) {
        $style = 'red';
        $status = "The 'CUSTOM_TAGS' variable is set. Any tags enabled here won't work!";
    } else {
        $style = 'green';
        $status = 'We are good to go!';
    }

    echo "
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
    if (get_option('allow_kses_script') == 'yes') {
        $allowedposttags['script'] = array(
          'type' => array(),
        );

        $allowedposttags['a'] = array(
          'href' => array(),
          'rel' => array(),
          'rev' => array(),
          'name' => array(),
          'target' => array(),
          'download' => array(),
          'class' => array(),
	       );
    }
}

register_activation_hook(__FILE__, 'set_afrozaar_options');
register_deactivation_hook(__FILE__, 'unset_afrozaar_options');

// Adds JWPlayer to header to allow post videos
// TODO make this customizable in Settings
/*function jwplayer_header() {
  ?>
  <script type="text/javascript" src="http://content.jwplatform.com/libraries/JPCZ6UQS.js"></script>
  <?php
}*/

//add_action('wp_head', 'jwplayer_header');

function get_data($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}


// AWS STUFFS

add_action( 'init', 'afrozaar_aws_init' );

function afrozaar_aws_init() {
  afrozaar_aws_required_files();
  global $afrozaar_aws;
  $afrozaar_aws = new Afrozaar_Aws_Extras( __FILE__ );
}

function afrozaar_aws_required_files() {
  $abspath = dirname( __FILE__ );
  require_once $abspath . '/classes/afro-plugin-base.php';
  require_once $abspath . '/classes/afrozaar-extras.php';
  require_once $abspath . '/aws/aws-autoloader.php';
}

 ?>
