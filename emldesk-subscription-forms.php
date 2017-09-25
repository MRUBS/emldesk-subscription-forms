<?php
/*
Plugin Name: EmlDesk Subscription Forms
Plugin URI: https://go.emldesk.com/Integration/WordPress-SubscriptionForms/
Description: Add EmlDesk subscription forms to your WordPress site.
Version: 1.1
Author: EmlDesk Inc.
Author URI: http://www.emldesk.com
License:  GNU General Public License v2
*/

/*
Copyright (c) 2017 EmlDesk Subscription Forms

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/********** CONSTANTS **********/

//The bare minimum version of WordPress required to run without generating a fatal error.
//EmlDesk Subscription Forms will refuse to run if activated on a lower version of WP.
define('EMLDESK_MINIMUM_WP_VER', '4.5');

//Reading plugin info from constants is faster than trying to parse it from the header above.
define('EMLDESK_PLUGIN_NAME', 'EmlDesk Subscription Forms');
define('EMLDESK_PLUGIN_URI', 'https://go.emldesk.com/Integration/WordPress-SubscriptionForms/');
define('EMLDESK_VERSION', '1.1');
define('EMLDESK_AUTHOR', 'EmlDesk Inc.');
define('EMLDESK_AUTHOR_URI', 'http://www.emldesk.com/');
define('EMLDESK_USER_AGENT', 'EmlDeskSubscriptionForms/1.0.5');

if ( ! defined( 'ABSPATH' ) ) exit;   // Exit if accessed directly

/********** VERSION CHECK & INITIALIZATION **********/

global $wp_version;
if (version_compare($wp_version, EMLDESK_MINIMUM_WP_VER, '>=')) {
	global $emldesk_subscription_forms;
	//$emldesk_subscription_forms = new EmlDesk_Subscription_Forms(__FILE__);
} else {
	add_action('admin_notices', 'emldesk_wp_incompat_notice');
}

function emldesk_wp_incompat_notice() {
	echo '<div class="error"><p>';
	printf(__('EmlDesk Subcription Forms requires WordPress %s or above. Please upgrade to the latest version of WordPress to enable EmlDesk Subcription Forms on your blog, or deactivate EmlDesk Subcription Forms to remove this notice.', 'emldesk_subscription_forms'), EMLDESK_MINIMUM_WP_VER);
	echo "</p></div>\n";
}

class EmlDesk_Subscription_Form_Widget extends	WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            "emlDesk_subscription_form_widget",
            "EmlDesk Subscription Form",
            array("description" => __("Add EmlDesk subscription forms to your WordPress site", "text_domain"),)
        );
    }

    /**
    * Front-end display of widget.
    *
    * @see WP_Widget::widget()
    *
    * @param array $args     Widget arguments.
    * @param array $instance Saved values from database.
    */
    public function widget( $args, $instance )
    {
    ?>
		<p class="emldeskSubscriptionForm-title"><?php echo $instance["form_title"]; ?></p>
		<?php echo $instance["form_content"]; ?>
    <?php
    }

    /**
    * Back-end widget form.
    *
    * @see WP_Widget::form()
    *
    * @param array $instance Previously saved values from database.
    */
    public function form($instance)
    {
        $api_token = (isset($instance["api_token"])) ? $instance["api_token"] : "";
        $form_id = (isset($instance["form_id"])) ? $instance["form_id"] : "";
		$form_title = (isset($instance["form_title"])) ? $instance["form_title"] : "";
        ?>
		<form method="post">   
            <p>
                API Token:
                <input type="text" name="<?php echo $this->get_field_name('api_token');?>" id="emldesk_api_token" value="<?php echo esc_attr($api_token); ?>" style="width: 100%;" />
				<?php wp_nonce_field( 'api_token_' . $api_token, 'nonce_token' ); ?>
            </p>

            <p>
				Form Id:
				<input type="text" name="<?php echo $this->get_field_name('form_id');?>" id="emldesk_form_id" value="<?php echo esc_attr($form_id); ?>" style="width: 100%;" />
				<?php wp_nonce_field( 'form_id_' . $form_id, 'nonce_token' ); ?>
            </p>

            <p>
				Title:
				<input type="text" name="<?php echo $this->get_field_name('form_title');?>" id="emldesk_form_title" value="<?php echo esc_attr($form_title); ?>" style="width: 100%;" />
				<?php wp_nonce_field( 'form_title_' . $form_title, 'nonce_token' ); ?>
            </p>
		</form>
        <?php
		$url = wp_nonce_url( admin_url(), 'api_token_' . $api_token, 'nonce_token' );
		$url = add_query_arg( 'api_token', $api_token, $url ); // Add the id of the user we send to
		$url = wp_nonce_url( admin_url(), 'form_id_' . $form_id, 'nonce_token' );
		$url = add_query_arg( 'form_id', $form_id, $url ); // Add the id of the user we send to
		$url = wp_nonce_url( admin_url(), 'form_title_' . $form_title, 'nonce_token' );
		$url = add_query_arg( 'form_title', $form_title, $url ); // Add the id of the user we send to
    }
	
	function emldesk_form_nonce_token($post) {
		$name='api_token'; // Make sure this is unique, prefix it with your plug-in/theme name
		$name='form_id'; // Make sure this is unique, prefix it with your plug-in/theme name
		$name='form_title'; // Make sure this is unique, prefix it with your plug-in/theme name
		$action='api_token_'.$post->ID; // This is the nonce action
		$action='form_id_'.$post->ID; // This is the nonce action
		$action='form_title_'.$post->ID; // This is the nonce action
	 
		wp_nonce_field($action,$name);
	 
		// Your meta box...
	}

	function emldesk_wp_nonce( $post_id ) {
		// Check its not an auto save
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return;
	 
		// Check your data has been sent - this helps verify that we intend to process our metabox
		if ( !isset($_POST['api_token']) )
			return;
		
		if ( !isset($_POST['form_id']) )
			return;
		
		if ( !isset($_POST['form_title']) )
			return;
	 
		// Check permissions
		if ( !current_user_can('edit_post', $post_id) )
			return;
	 
		// Finally check the nonce
		check_admin_referer('api_token_'.$post_id, 'api_token');
		check_admin_referer('form_id_'.$post_id, 'form_id');
		check_admin_referer('form_title_'.$post_id, 'form_title');
	}

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['api_token'] = (!empty($new_instance['api_token'])) ? strip_tags($new_instance['api_token']) : '';
        $instance['form_id'] = (!empty($new_instance['form_id'])) ? strip_tags($new_instance['form_id']) : '';
		$instance['form_title'] = (!empty($new_instance['form_title'])) ? strip_tags($new_instance['form_title']) : '';

        $subscription_form = get_emldesk_subscription_form($instance['api_token'], $instance['form_id']);

        $instance['form_content'] = str_replace('REPLACE_POST_URL', plugins_url('form_post.php', __FILE__), $subscription_form["Content"]);

        return $instance;
    }
}

function get_emldesk_subscription_form($api_token, $form_id)
{
    $args = array(
				'headers' => array(
								'Authorization' => 'token ' . $api_token,
								'Accept' => 'application/json'
							)
			);

	$url = 'https://services.emldesk.com/lists/subscriptionform/' . $form_id;

	$response = wp_remote_get($url, $args);

	try
	{
		$json = json_decode(wp_remote_retrieve_body($response), true);
	}
	catch(Exception $ex)
	{
		$json = null;
	}

	return $json;
}

function emldesk_shortcodes()
{
	$widget = get_option("widget_emldesk_subscriptionform_widget");

	foreach ($widget as $k => $v)
	{
		if (isset($v["api_token"]) && isset($v["form_id"]) && isset($v["form_content"]))
		{
			$widget_display = $v["form_content"];
			return $widget_display;
		}
	}

	return "";
}

function emldesk_register_widget()
{
    register_widget("EmlDesk_Subscription_Form_Widget");
}

function emldesk_register_shortcodes()
{
    add_shortcode("emldesk_subscriptionform", "emldesk_shortcodes");
}

add_action('save_post','emldesk_form_nonce_token');
add_action("widgets_init", "emldesk_register_widget");
add_action("init", "emldesk_register_shortcodes");

?>
