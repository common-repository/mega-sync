<?php
/*
Plugin Name: Mega Sync
Description: This plugin enables HubSpot and Salesforce forms integration with Contact Form 7 forms and allow user to specify SMTP settings that can be used to email form.
Author: ARCS
Version: 1.0
Author URI: http://arcscorp.com/

PREFIX: cfhsfi (Contact Form 7 HubSpot Forms Integration) and cfsffi (Contact Form 7 Salesforce Forms Integration)

*/

// check to make sure contact form 7 is installed and active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {

	function cfhsfi_root_url( $append = false ) {

		$base_url = plugin_dir_url( __FILE__ );

		return ($append ? $base_url . $append : $base_url);

	}
	
	function cfhsfi_root_dir( $append = false ) {

		$base_dir = plugin_dir_path( __FILE__ );

		return ($append ? $base_dir . $append : $base_dir);

	}

	include_once( cfhsfi_root_dir('inc/constants.php') );

	function cfhsfi_enqueue( $hook ) {

    if ( !strpos( $hook, 'wpcf7' ) )
    	return;

    wp_enqueue_style( 'cf7hsfi-styles',
    	cfhsfi_root_url('assets/css/styles.css'),
    	false,
    	CF7HSFI_VERSION );

		wp_enqueue_script( 'cf7hsfi-scripts',
    	cfhsfi_root_url('assets/js/scripts.js'),
			array('jquery'),
			CF7HSFI_VERSION );

	}
	add_action( 'admin_enqueue_scripts', 'cfhsfi_enqueue' );

	function cfhsfi_admin_panel ( $panels ) {

		$new_page = array(
			'hubspot-forms-integration-addon' => array(
				'title' => __( 'HubSpot Form Integration', 'contact-form-7' ),
				'callback' => 'cfhsfi_admin_panel_content'
			)
		);
		
		$panels = array_merge($panels, $new_page);
		
		return $panels;
		
	}
	add_filter( 'wpcf7_editor_panels', 'cfhsfi_admin_panel' );

	function cfhsfi_admin_panel_content( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$enabled = get_post_meta($post_id, "_cf7hsfi_enabled", true);
		$portal_id = get_post_meta($post_id, "_cf7hsfi_portal_id", true);
		$form_id = get_post_meta($post_id, "_cf7hsfi_form_id", true);
		$form_page_url = get_post_meta($post_id, "_cf7hsfi_form_page_url", true);
		$form_page_name = get_post_meta($post_id, "_cf7hsfi_form_page_name", true);
		$form_fields_str = get_post_meta($post_id, "_cf7hsfi_form_fields", true);
		$form_fields = $form_fields_str ? $form_fields_str : false;
		$debug_log = get_post_meta($post_id, "_cf7hsfi_debug_log", true);

		$template = cfhsfi_get_view_template('form-fields.tpl.php');

		if($form_fields) {

			$form_fields_html = '';
			$count = 1;

			foreach ($form_fields as $key => $value) {

				$search_replace = array(
					'{first_field}' => ' first_field',
					'{field_name}' => $key,
					'{field_value}' => $value,
					'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
					'{remove_button}' => '<a href="#" class="button remove_field">Remove Field</a>',
				);

				$search = array_keys($search_replace);
				$replace = array_values($search_replace);

				if($count >  1) $replace[0] = $replace[3] = '';				
				if($count == 1) $replace[4] = '';

				$form_fields_html .= str_replace($search, $replace, $template);

				$count++;

			}

		} else {

			$search_replace = array(
				'{first_field}' => ' first_field',
				'{field_name}' => '',
				'{field_value}' => '',
				'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
				'{remove_button}' => '',
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$form_fields_html = str_replace($search, $replace, $template);

		}

		$debug_log_str = is_array($debug_log) ? print_r($debug_log, true) : $debug_log;

		$search_replace = array(
			'{enabled}' => ($enabled == 1 ? ' checked' : ''),
			'{portal_id}' => $portal_id,
			'{form_id}' => $form_id,
			'{form_page_url}' => $form_page_url,
			'{form_page_name}' => $form_page_name,
			'{form_fields_html}' => $form_fields_html,
			'{debug_log}' => $debug_log_str,
		);

		$search = array_keys($search_replace);
		$replace = array_values($search_replace);

		$template = cfhsfi_get_view_template('ui-tabs-panel.tpl.php');

		$admin_table_output = str_replace($search, $replace, $template);

		echo $admin_table_output;

	}

	function cfhsfi_get_view_template( $template_name ) {

		$template_content = false;
		$template_path = CF7HSFI_VIEWS_DIR . $template_name;

		if( file_exists($template_path) ) {

			$search_replace = array(
				"<?php if(!defined( 'ABSPATH')) exit; ?>" => '',
				"{plugin_url}" => cfhsfi_root_url(),
				"{site_url}" => get_site_url(),
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$template_content = str_replace($search, $replace, file_get_contents( $template_path ));

		}

		return $template_content;

	}

	function cfhsfi_admin_save_form( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$form_fields = array();

		foreach ($_POST['cf7hsfi_hs_field'] as $key => $value) {

			if($_POST['cf7hsfi_cf7_field'][$key] == '' && $value == '') continue;

			$form_fields[$value] = sanitize_text_field($_POST['cf7hsfi_cf7_field'][$key]);

		}
		
		$cf7hsfi_enabled = intval( $_POST['cf7hsfi_enabled'] );
		if ( ! $cf7hsfi_enabled )
			$cf7hsfi_enabled = '0';

		update_post_meta($post_id, '_cf7hsfi_enabled', $cf7hsfi_enabled);
		update_post_meta($post_id, '_cf7hsfi_portal_id', sanitize_text_field($_POST['cf7hsfi_portal_id']));
		update_post_meta($post_id, '_cf7hsfi_form_id', sanitize_text_field($_POST['cf7hsfi_form_id']));
		update_post_meta($post_id, '_cf7hsfi_form_page_url', esc_url($_POST['cf7hsfi_form_page_url']));
		update_post_meta($post_id, '_cf7hsfi_form_page_name', sanitize_text_field($_POST['cf7hsfi_form_page_name']));
		update_post_meta($post_id, '_cf7hsfi_form_fields', $form_fields);

	}
	add_action('wpcf7_save_contact_form', 'cfhsfi_admin_save_form');

	function cfhsfi_frontend_submit_form( $wpcf7_data ) {

		$post_id = $wpcf7_data->id;
		$enabled = get_post_meta($post_id, "_cf7hsfi_enabled", true);
		$portal_id = get_post_meta($post_id, "_cf7hsfi_portal_id", true);
		$form_id = get_post_meta($post_id, "_cf7hsfi_form_id", true);
		$form_page_url = get_post_meta($post_id, "_cf7hsfi_form_page_url", true);
		$form_page_name = get_post_meta($post_id, "_cf7hsfi_form_page_name", true);
		$form_fields_str = get_post_meta($post_id, "_cf7hsfi_form_fields", true);
		$form_fields = $form_fields_str ? $form_fields_str : false;

    if( $enabled == 1 && $form_fields ) {

			$hs_cookie = $_COOKIE["hubspotutk"];
			$user_ip = $_SERVER["REMOTE_ADDR"];
			$hs_context = array(
				"hutk" => $hs_cookie,
				"ipAddress" => $user_ip
			);

			if( !empty($form_page_url) ) $hs_context["pageUrl"] = $form_page_url;
			if( !empty($form_page_name) ) $hs_context["pageName"] = $form_page_name;

			$hs_context_json = json_encode($hs_context);

			$post_array = array();

			foreach ($form_fields as $key => $value) {

				$search = array("[", "]");
				$post_key = str_replace($search, "", $value);

				$post_array[$key] = sanitize_text_field($_POST[$post_key]);

			}

			$post_array["hs_context"] = $hs_context_json;
			$post_string = http_build_query($post_array);

			$post_url = "https://forms.hubspot.com/uploads/form/v2/{$portal_id}/{$form_id}";


			$body = $post_string;
			 
			$args = array(
				'body' => $body,
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type: application/x-www-form-urlencoded'),
				'cookies' => array()
			);
			 
			$response = wp_remote_post( $post_url, $args );
			
			$response = wp_remote_retrieve_body($response);
			$status_code = wp_remote_retrieve_response_code($response);
			
			$debug_log = array(
				'STATUS_CODE' => $status_code,
				'HUBSPOT_RESPONSE' => $response
			);

			update_post_meta($post_id, '_cf7hsfi_debug_log', $debug_log);

    }

	}
	add_action("wpcf7_before_send_mail", "cfhsfi_frontend_submit_form");
	
}

if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {

	function cfsffi_root_url( $append = false ) {

		$base_url = plugin_dir_url( __FILE__ );

		return ($append ? $base_url . $append : $base_url);

	}
	
	function cfsffi_root_dir( $append = false ) {

		$base_dir = plugin_dir_path( __FILE__ );

		return ($append ? $base_dir . $append : $base_dir);

	}

	include_once( cfsffi_root_dir('inc/constants.php') );

	function cfsffi_enqueue( $hook ) {

    if ( !strpos( $hook, 'wpcf7' ) )
    	return;

    wp_enqueue_style( 'cf7sffi-styles',
    	cfsffi_root_url('assets/css/styles1.css'),
    	false,
    	CF7HSFI_VERSION );

		wp_enqueue_script( 'cf7sffi-scripts',
    	cfsffi_root_url('assets/js/scripts1.js'),
			array('jquery'),
			CF7HSFI_VERSION );

	}
	add_action( 'admin_enqueue_scripts', 'cfsffi_enqueue' );
	
	
	function cfsffi_admin_panel ( $panels ) {

		$new_page = array(
			'salesforce-forms-integration-addon' => array(
				'title' => __( 'Salesforce Form Integration', 'contact-form-7' ),
				'callback' => 'cfsffi_admin_panel_content'
			)
		);
		
		$panels = array_merge($panels, $new_page);
		
		return $panels;
		
	}
	add_filter( 'wpcf7_editor_panels', 'cfsffi_admin_panel' );

	function cfsffi_admin_panel_content( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$enabled = get_post_meta($post_id, "_cf7sffi_enabled", true);
		$sf_url = get_post_meta($post_id, "_cf7sffi_url", true);
		$form_oid = get_post_meta($post_id, "_cf7sffi_oid", true);
		$form_fields_str = get_post_meta($post_id, "_cf7sffi_form_fields", true);
		$form_fields = $form_fields_str ? $form_fields_str : false;
		$debug_log = get_post_meta($post_id, "_cf7hsfi_debug_log", true);

		$template = cfsffi_get_view_template('form-fields-sf.tpl.php');

		if($form_fields) {

			$form_fields_html = '';
			$count = 1;

			foreach ($form_fields as $key => $value) {

				$search_replace = array(
					'{first_field}' => ' first_field',
					'{field_name}' => $key,
					'{field_value}' => $value,
					'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
					'{remove_button}' => '<a href="#" class="button remove_field">Remove Field</a>',
				);

				$search = array_keys($search_replace);
				$replace = array_values($search_replace);

				if($count >  1) $replace[0] = $replace[3] = '';				
				if($count == 1) $replace[4] = '';

				$form_fields_html .= str_replace($search, $replace, $template);

				$count++;

			}

		} else {

			$search_replace = array(
				'{first_field}' => ' first_field',
				'{field_name}' => '',
				'{field_value}' => '',
				'{add_button}' => '<a href="#" class="button add_field">Add Another Field</a>',
				'{remove_button}' => '',
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$form_fields_html = str_replace($search, $replace, $template);

		}

		$debug_log_str = is_array($debug_log) ? print_r($debug_log, true) : $debug_log;

		$search_replace = array(
			'{enabled}' => ($enabled == 1 ? ' checked' : ''),
			'{sf_url}' => $sf_url,
			'{form_oid}' => $form_oid,
			'{form_fields_html}' => $form_fields_html,
			'{debug_log}' => $debug_log_str,
		);

		$search = array_keys($search_replace);
		$replace = array_values($search_replace);

		$template = cfsffi_get_view_template('ui-tabs-panel-sf.tpl.php');

		$admin_table_output = str_replace($search, $replace, $template);

		echo $admin_table_output;

	}

	function cfsffi_get_view_template( $template_name ) {

		$template_content = false;
		$template_path = CF7HSFI_VIEWS_DIR . $template_name;

		if( file_exists($template_path) ) {

			$search_replace = array(
				"<?php if(!defined( 'ABSPATH')) exit; ?>" => '',
				"{plugin_url}" => cfsffi_root_url(),
				"{site_url}" => get_site_url(),
			);

			$search = array_keys($search_replace);
			$replace = array_values($search_replace);

			$template_content = str_replace($search, $replace, file_get_contents( $template_path ));

		}

		return $template_content;

	}

	function cfsffi_admin_save_form( $cf7 ) {
		
		$post_id = sanitize_text_field($_GET['post']);

		$form_fields = array();

		foreach ($_POST['cf7sffi_sf_field'] as $key => $value) {

			if($_POST['cf7sffi_cf7_field'][$key] == '' && $value == '') continue;

			$form_fields[$value] = sanitize_text_field($_POST['cf7sffi_cf7_field'][$key]);

		}
		
		$cf7sffi_enabled = intval( $_POST['cf7sffi_enabled'] );
		if ( ! $cf7sffi_enabled )
			$cf7sffi_enabled = '0';

		update_post_meta($post_id, '_cf7sffi_enabled', $cf7sffi_enabled);
		update_post_meta($post_id, '_cf7sffi_url', esc_url($_POST['cf7sffi_url']));
		update_post_meta($post_id, '_cf7sffi_oid', sanitize_text_field($_POST['cf7sffi_oid']));
		update_post_meta($post_id, '_cf7sffi_form_fields', $form_fields);

	}
	add_action('wpcf7_save_contact_form', 'cfsffi_admin_save_form');

	function cfsffi_frontend_submit_form( $wpcf7_data ) {

		$post_id = $wpcf7_data->id;
		$enabled = get_post_meta($post_id, "_cf7sffi_enabled", true);
		$sf_url = get_post_meta($post_id, "_cf7sffi_url", true);
		$form_oid = get_post_meta($post_id, "_cf7sffi_oid", true);
		$form_fields_str = get_post_meta($post_id, "_cf7sffi_form_fields", true);
		$form_fields = $form_fields_str ? $form_fields_str : false;

    if( $enabled == 1 && $form_fields ) {

			$post_array = array();

			foreach ($form_fields as $key => $value) {

				$search = array("[", "]");
				$post_key = str_replace($search, "", $value);

				$post_array[$key] = sanitize_text_field($_POST[$post_key]);

			}

			$post_array["oid"] = $form_oid;
			$post_string = http_build_query($post_array);

			$post_url = "{$sf_url}";


			$body = $post_string;
			 
			$args = array(
				'body' => $body,
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array('Content-Type: application/x-www-form-urlencoded'),
				'cookies' => array()
			);
			 
			$response = wp_remote_post( $post_url, $args );
			
			$response = wp_remote_retrieve_body($response);
			$status_code = wp_remote_retrieve_response_code($response);
			
			$debug_log = array(
				'STATUS_CODE' => $status_code,
				'HUBSPOT_RESPONSE' => $response
			);

			update_post_meta($post_id, '_cf7hsfi_debug_log', $debug_log);

    }

	}
	
	add_action("wpcf7_before_send_mail", "cfsffi_frontend_submit_form");

}



global $wpms_options;
$wpms_options = array(
	'mail_from'            => '',
	'mail_from_name'       => '',
	'mailer'               => 'smtp',
	'mail_set_return_path' => 'false',
	'smtp_host'            => 'localhost',
	'smtp_port'            => '25',
	'smtp_ssl'             => 'none',
	'smtp_auth'            => false,
	'smtp_user'            => '',
	'smtp_pass'            => '',
	'pepipost_user'        => '',
	'pepipost_pass'        => '',
	'pepipost_port'        => '2525',
	'pepipost_ssl'         => 'none',
);


	function wp_mail_smtp_activate() {
		global $wpms_options;
		foreach ( $wpms_options as $name => $val ) {
			add_option( $name, $val );
		}
	}


	function wp_mail_smtp_whitelist_options( $whitelist_options ) {
		global $wpms_options;
		$whitelist_options['email'] = array_keys( $wpms_options );
		return $whitelist_options;
	}


if ( ! function_exists( 'phpmailer_init_smtp' ) ) :
	function phpmailer_init_smtp( $phpmailer ) {
		if (
			defined( 'WPMS_ON' ) && WPMS_ON &&
			defined( 'WPMS_MAILER' )
		) {
			$phpmailer->Mailer = WPMS_MAILER;

			if ( defined( 'WPMS_SET_RETURN_PATH' ) && WPMS_SET_RETURN_PATH ) {
				$phpmailer->Sender = $phpmailer->From;
			}

			if (
				WPMS_MAILER === 'smtp' &&
				defined( 'WPMS_SSL' ) &&
				defined( 'WPMS_SMTP_HOST' ) &&
				defined( 'WPMS_SMTP_PORT' )
			) {
				$phpmailer->SMTPSecure = WPMS_SSL;
				$phpmailer->Host       = WPMS_SMTP_HOST;
				$phpmailer->Port       = WPMS_SMTP_PORT;

				if (
					defined( 'WPMS_SMTP_AUTH' ) && WPMS_SMTP_AUTH &&
					defined( 'WPMS_SMTP_USER' ) &&
					defined( 'WPMS_SMTP_PASS' )
				) {
					$phpmailer->SMTPAuth = true;
					$phpmailer->Username = WPMS_SMTP_USER;
					$phpmailer->Password = WPMS_SMTP_PASS;
				}
			}
		} else {
			$option_mailer    = get_option( 'mailer' );
			$option_smtp_host = get_option( 'smtp_host' );
			$option_smtp_ssl  = get_option( 'smtp_ssl' );

			if (
				! $option_mailer ||
				( 'smtp' === $option_mailer && ! $option_smtp_host )
			) {
				return;
			}

			if ( 'pepipost' === $option_mailer && ( ! get_option( 'pepipost_user' ) && ! get_option( 'pepipost_pass' ) ) ) {
				return;
			}

			$phpmailer->Mailer = $option_mailer;

			if ( get_option( 'mail_set_return_path' ) ) {
				$phpmailer->Sender = $phpmailer->From;
			}

			$phpmailer->SMTPSecure = $option_smtp_ssl;
			if ( 'none' === $option_smtp_ssl ) {
				$phpmailer->SMTPSecure  = '';
				$phpmailer->SMTPAutoTLS = false;
			}

			if ( 'smtp' === $option_mailer ) {
				$phpmailer->Host = $option_smtp_host;
				$phpmailer->Port = get_option( 'smtp_port' );

				if ( get_option( 'smtp_auth' ) === 'true' ) {
					$phpmailer->SMTPAuth = true;
					$phpmailer->Username = get_option( 'smtp_user' );
					$phpmailer->Password = get_option( 'smtp_pass' );
				}
			} elseif ( 'pepipost' === $option_mailer ) {
				$phpmailer->Mailer     = 'smtp';
				$phpmailer->Host       = 'smtp.pepipost.com';
				$phpmailer->Port       = get_option( 'pepipost_port' );
				$phpmailer->SMTPSecure = get_option( 'pepipost_ssl' ) === 'none' ? '' : get_option( 'pepipost_ssl' );
				$phpmailer->SMTPAuth   = true;
				$phpmailer->Username   = get_option( 'pepipost_user' );
				$phpmailer->Password   = get_option( 'pepipost_pass' );
			}
		}

		$phpmailer = apply_filters( 'wp_mail_smtp_custom_options', $phpmailer );
	}
endif;


	function wp_mail_smtp_options_page() {

		global $phpmailer;

		if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$phpmailer = new PHPMailer( true );
		}

		if (
			isset( $_POST['wpms_action'] ) &&
			__( 'Send Test', 'wp-mail-smtp' ) === sanitize_text_field( $_POST['wpms_action'] ) &&
			is_email( $_POST['to'] )
		) {

			check_admin_referer( 'test-email' );

			$to      = sanitize_email( $_POST['to'] );
			$subject = 'WP Mail SMTP: ' . sprintf( __( 'Test mail to %s', 'wp-mail-smtp' ), $to );
			$message = __( 'This is a test email generated by the WP Mail SMTP WordPress plugin.', 'wp-mail-smtp' );

			$phpmailer->SMTPDebug = apply_filters( 'wp_mail_smtp_admin_test_email_smtp_debug', 0 );

			ob_start();

			$result = wp_mail( $to, $subject, $message );

			$smtp_debug = ob_get_clean();

			?>
			<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Test Message Sent', 'wp-mail-smtp' ); ?></strong></p>
				<p><?php _e( 'The result was:', 'wp-mail-smtp' ); ?></p>
				<pre><?php var_dump( $result ); ?></pre>

				<p><?php _e( 'The full debugging output is shown below:', 'wp-mail-smtp' ); ?></p>
				<pre><?php print_r( $phpmailer ); ?></pre>

				<p><?php _e( 'The SMTP debugging output is shown below:', 'wp-mail-smtp' ); ?></p>
				<pre><?php echo $smtp_debug; ?></pre>
			</div>
			<?php

			unset( $phpmailer );
		}

		?>
		<div class="wrap">
			<h2>
				<?php _e( 'Mail SMTP Settings', 'wp-mail-smtp' ); ?>
			</h2>

			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php wp_nonce_field( 'email-options' ); ?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="mail_from"><?php _e( 'From Email', 'wp-mail-smtp' ); ?></label>
						</th>
						<td>
							<input name="mail_from" type="email" id="mail_from" value="<?php print( get_option( 'mail_from' ) ); ?>" size="40" class="regular-text"/>

							<p class="description">
								<?php
								_e( 'You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', 'wp-mail-smtp' );
								if ( get_option( 'db_version' ) < 6124 ) {
									print( '<br /><span style="color: red;">' );
									_e( '<strong>Please Note:</strong> You appear to be using a version of WordPress prior to 2.3. Please ignore the From Name field and instead enter Name&lt;email@domain.com&gt; in this field.', 'wp-mail-smtp' );
									print( '</span>' );
								}
								?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="mail_from_name"><?php _e( 'From Name', 'wp-mail-smtp' ); ?></label>
						</th>
						<td>
							<input name="mail_from_name" type="text" id="mail_from_name" value="<?php print( get_option( 'mail_from_name' ) ); ?>" size="40" class="regular-text"/>

							<p class="description">
								<?php _e( 'You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', 'wp-mail-smtp' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Mailer', 'wp-mail-smtp' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php _e( 'Mailer', 'wp-mail-smtp' ); ?></span>
								</legend>

								<p>
									<input id="mailer_smtp" class="wpms_mailer" type="radio" name="mailer" value="smtp" <?php checked( 'smtp', get_option( 'mailer' ) ); ?> />
									<label for="mailer_smtp"><?php _e( 'Send all WordPress emails via SMTP.', 'wp-mail-smtp' ); ?></label>
								</p>
								<p>
									<input id="mailer_mail" class="wpms_mailer" type="radio" name="mailer" value="mail" <?php checked( 'mail', get_option( 'mailer' ) ); ?> />
									<label for="mailer_mail"><?php _e( 'Use the PHP mail() function to send emails.', 'wp-mail-smtp' ); ?></label>
								</p>

								<?php if ( wp_mail_smtp_is_pepipost_active() ) : ?>
									<p>
										<input id="mailer_pepipost" class="wpms_mailer" type="radio" name="mailer" value="pepipost" <?php checked( 'pepipost', get_option( 'mailer' ) ); ?> />
										<label for="mailer_pepipost"><?php _e( 'Use Pepipost SMTP to send emails.', 'wp-mail-smtp' ); ?></label>
									</p>
									<p class="description">
										<?php
										printf(
											__( 'Looking for high inbox delivery? Try Pepipost with easy setup and free emails. Learn more %1$shere%2$s.', 'wp-mail-smtp' ),
											'<a href="https://app1.pepipost.com/index.php/login/wp_mail_smtp?page=signup&utm_source=WordPress&utm_campaign=Plugins&utm_medium=wp_mail_smtp&utm_term=organic&code=WP-MAIL-SMTP" target="_blank">',
											'</a>'
										);
										?>
									</p>
								<?php endif; ?>
							</fieldset>
						</td>
					</tr>
				</table>

				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e( 'Return Path', 'wp-mail-smtp' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php _e( 'Return Path', 'wp-mail-smtp' ); ?></span>
								</legend>

								<label for="mail_set_return_path">
									<input name="mail_set_return_path" type="checkbox" id="mail_set_return_path" value="true" <?php checked( 'true', get_option( 'mail_set_return_path' ) ); ?> />
									<?php _e( 'Set the return-path to match the From Email', 'wp-mail-smtp' ); ?>
								</label>

								<p class="description">
									<?php _e( 'Return Path indicates where non-delivery receipts - or bounce messages - are to be sent.', 'wp-mail-smtp' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-mail-smtp' ); ?>"/>
				</p>

				<div id="wpms_section_smtp" class="wpms_section">
					<h3>
						<?php _e( 'SMTP Options', 'wp-mail-smtp' ); ?>
					</h3>
					<p><?php _e( 'These options only apply if you have chosen to send mail by SMTP above.', 'wp-mail-smtp' ); ?></p>

					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<label for="smtp_host"><?php _e( 'SMTP Host', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="smtp_host" type="text" id="smtp_host" value="<?php print( get_option( 'smtp_host' ) ); ?>" size="40" class="regular-text"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="smtp_port"><?php _e( 'SMTP Port', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="smtp_port" type="text" id="smtp_port" value="<?php print( get_option( 'smtp_port' ) ); ?>" size="6" class="regular-text"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Encryption', 'wp-mail-smtp' ); ?> </th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php _e( 'Encryption', 'wp-mail-smtp' ); ?></span>
									</legend>

									<input id="smtp_ssl_none" type="radio" name="smtp_ssl" value="none" <?php checked( 'none', get_option( 'smtp_ssl' ) ); ?> />
									<label for="smtp_ssl_none">
										<span><?php _e( 'No encryption.', 'wp-mail-smtp' ); ?></span>
									</label><br/>

									<input id="smtp_ssl_ssl" type="radio" name="smtp_ssl" value="ssl" <?php checked( 'ssl', get_option( 'smtp_ssl' ) ); ?> />
									<label for="smtp_ssl_ssl">
										<span><?php _e( 'Use SSL encryption.', 'wp-mail-smtp' ); ?></span>
									</label><br/>

									<input id="smtp_ssl_tls" type="radio" name="smtp_ssl" value="tls" <?php checked( 'tls', get_option( 'smtp_ssl' ) ); ?> />
									<label for="smtp_ssl_tls">
										<span><?php _e( 'Use TLS encryption.', 'wp-mail-smtp' ); ?></span>
									</label>

									<p class="description"><?php esc_html_e( 'TLS is not the same as STARTTLS. For most servers SSL is the recommended option.', 'wp-mail-smtp' ); ?></p>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Authentication', 'wp-mail-smtp' ); ?> </th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php _e( 'Authentication', 'wp-mail-smtp' ); ?></span>
									</legend>

									<input id="smtp_auth_false" type="radio" name="smtp_auth" value="false" <?php checked( 'false', get_option( 'smtp_auth' ) ); ?> />
									<label for="smtp_auth_false">
										<span><?php _e( 'No: Do not use SMTP authentication.', 'wp-mail-smtp' ); ?></span>
									</label><br/>

									<input id="smtp_auth_true" type="radio" name="smtp_auth" value="true" <?php checked( 'true', get_option( 'smtp_auth' ) ); ?> />
									<label for="smtp_auth_true">
										<span><?php _e( 'Yes: Use SMTP authentication.', 'wp-mail-smtp' ); ?></span>
									</label><br/>

									<p class="description">
										<?php _e( 'If this is set to no, the values below are ignored.', 'wp-mail-smtp' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="smtp_user"><?php _e( 'Username', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="smtp_user" type="text" id="smtp_user" value="<?php print( get_option( 'smtp_user' ) ); ?>" size="40" class="code"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="smtp_pass"><?php _e( 'Password', 'wp-mail-smtp' ); ?></label>
							</th>
							<td>
								<input name="smtp_pass" type="text" id="smtp_pass" value="<?php print( get_option( 'smtp_pass' ) ); ?>" size="40" class="code"/>

								<p class="description">
									<?php esc_html_e( 'This is in plain text because it must not be stored encrypted.', 'wp-mail-smtp' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-mail-smtp' ); ?>"/>
					</p>
				</div><!-- #wpms_section_smtp -->

				<?php if ( wp_mail_smtp_is_pepipost_active() ) : ?>
					<div id="wpms_section_pepipost" class="wpms_section">
						<h3>
							<?php _e( 'Pepipost SMTP Options', 'wp-mail-smtp' ); ?>
						</h3>
						<p>
							<?php
							printf(
								__( 'You need to signup on %s to get the SMTP username/password.', 'wp-mail-smtp' ),
								'<a href="https://app1.pepipost.com/index.php/login/wp_mail_smtp?page=signup&utm_source=WordPress&utm_campaign=Plugins&utm_medium=wp_mail_smtp&utm_term=organic&code=WP-MAIL-SMTP" target="_blank">Pepipost</a>',
								''
							);
							?>
						</p>
						<table class="form-table">
							<tr valign="top">
								<th scope="row">
									<label for="pepipost_user"><?php _e( 'Username', 'wp-mail-smtp' ); ?></label>
								</th>
								<td>
									<input name="pepipost_user" type="text" id="pepipost_user" value="<?php print( get_option( 'pepipost_user' ) ); ?>" size="40" class="code"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="pepipost_pass"><?php _e( 'Password', 'wp-mail-smtp' ); ?></label>
								</th>
								<td>
									<input name="pepipost_pass" type="text" id="pepipost_pass" value="<?php print( get_option( 'pepipost_pass' ) ); ?>" size="40" class="code"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="pepipost_port"><?php _e( 'SMTP Port', 'wp-mail-smtp' ); ?></label>
								</th>
								<td>
									<input name="pepipost_port" type="text" id="pepipost_port" value="<?php print( get_option( 'pepipost_port' ) ); ?>" size="6" class="regular-text"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<?php _e( 'Encryption', 'wp-mail-smtp' ); ?>
								</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text">
											<span>
												<?php _e( 'Encryption', 'wp-mail-smtp' ); ?>
											</span>
										</legend>

										<input id="pepipost_ssl_none" type="radio" name="pepipost_ssl" value="none" <?php checked( 'none', get_option( 'pepipost_ssl' ) ); ?> />
										<label for="pepipost_ssl_none">
											<span><?php _e( 'No encryption.', 'wp-mail-smtp' ); ?></span>
										</label><br/>

										<input id="pepipost_ssl_ssl" type="radio" name="pepipost_ssl" value="ssl" <?php checked( 'ssl', get_option( 'pepipost_ssl' ) ); ?> />
										<label for="pepipost_ssl_ssl">
											<span><?php _e( 'Use SSL encryption.', 'wp-mail-smtp' ); ?></span>
										</label><br/>

										<input id="pepipost_ssl_tls" type="radio" name="pepipost_ssl" value="tls" <?php checked( 'tls', get_option( 'pepipost_ssl' ) ); ?> />
										<label for="pepipost_ssl_tls">
											<span><?php _e( 'Use TLS encryption.', 'wp-mail-smtp' ); ?></span>
										</label>
									</fieldset>
								</td>
							</tr>
						</table>

						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-mail-smtp' ); ?>"/>
						</p>
					</div><!-- #wpms_section_pepipost -->
				<?php endif; ?>

				<input type="hidden" name="action" value="update"/>
				<input type="hidden" name="option_page" value="email">
			</form>

			<script type="text/javascript">
				/* globals jQuery */
				var wpmsOnMailerChange = function ( mailer ) {
					// Hide all the mailer forms.
					jQuery( '.wpms_section' ).hide();
					// Show the target mailer form.
					jQuery( '#wpms_section_' + mailer ).show();
				};
				jQuery( document ).ready( function () {
					// Call wpmsOnMailerChange() on startup with the current mailer.
					wpmsOnMailerChange( jQuery( 'input.wpms_mailer:checked' ).val() );

					// Watch the mailer for any changes
					jQuery( 'input.wpms_mailer' ).on( 'change', function ( e ) {
						// Call the wpmsOnMailerChange() handler, passing the value of the newly selected mailer.
						wpmsOnMailerChange( jQuery( e.target ).val() );
					} );
				} );
			</script>

		</div>
		<?php
	} // End of wp_mail_smtp_options_page() function definition.



	function wp_mail_smtp_menus() {

		if ( function_exists( 'add_submenu_page' ) ) {
			add_options_page( __( 'Mail SMTP Settings', 'wp-mail-smtp' ), __( 'Mail SMTP', 'wp-mail-smtp' ), 'manage_options', __FILE__, 'wp_mail_smtp_options_page' );
		}
	} // End of wp_mail_smtp_menus() function definition.



	function wp_mail_smtp_mail_from( $orig ) {
		$server_name = ! empty( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : wp_parse_url( get_home_url( get_current_blog_id() ), PHP_URL_HOST );

		$sitename = strtolower( $server_name );
		if ( substr( $sitename, 0, 4 ) === 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$default_from = 'wordpress@' . $sitename;

		if ( $orig !== $default_from ) {
			return $orig;
		}

		if (
			defined( 'WPMS_ON' ) && WPMS_ON &&
			defined( 'WPMS_MAIL_FROM' )
		) {
			$mail_from_email = WPMS_MAIL_FROM;

			if ( ! empty( $mail_from_email ) ) {
				return $mail_from_email;
			}
		}

		if ( is_email( get_option( 'mail_from' ), false ) ) {
			return get_option( 'mail_from' );
		}

		return $orig;
	} // End of wp_mail_smtp_mail_from() function definition.



	function wp_mail_smtp_mail_from_name( $orig ) {

		if ( 'WordPress' === $orig ) {
			if (
				defined( 'WPMS_ON' ) && WPMS_ON &&
				defined( 'WPMS_MAIL_FROM_NAME' )
			) {
				$mail_from_name = WPMS_MAIL_FROM_NAME;

				if ( ! empty( $mail_from_name ) ) {
					return $mail_from_name;
				}
			}

			$from_name = get_option( 'mail_from_name' );
			if ( ! empty( $from_name ) && is_string( $from_name ) ) {
				return $from_name;
			}
		}

		return $orig;
	}


function wp_mail_plugin_action_links( $links, $file ) {

	if ( plugin_basename( __FILE__ ) !== $file ) {
		return $links;
	}

	$settings_link = '<a href="options-general.php?page=' . plugin_basename( __FILE__ ) . '">' . __( 'Settings', 'wp-mail-smtp' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

function wp_mail_smtp_is_pepipost_active() {
	return apply_filters( 'wp_mail_smtp_options_is_pepipost_active', 'pepipost' === get_option( 'mailer' ) );
}

function wp_mail_smtp_check_php_version() {

	if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
		return;
	}

	if ( ! is_super_admin() ) {
		return;
	}

	if ( isset( $GLOBALS['pagenow'] ) && 'index.php' !== $GLOBALS['pagenow'] ) {
		return;
	}

	echo '<div class="notice notice-error">' .
		'<p>' .
		sprintf(
			__(
				'Your site is running an outdated version of PHP that is no longer supported and may cause issues with %1$s. %2$sRead more%3$s for additional information.',
				'wpforms'
			),
			'<strong>WP Mail SMTP</strong>',
			'<a href="https://wpforms.com/docs/supported-php-version/" target="_blank">',
			'</a>'
		) .
		'</p>' .
	'</div>';
}

add_action( 'admin_notices', 'wp_mail_smtp_check_php_version' );

add_action( 'phpmailer_init', 'phpmailer_init_smtp' );

if ( ! defined( 'WPMS_ON' ) || ! WPMS_ON ) {
	add_filter( 'whitelist_options', 'wp_mail_smtp_whitelist_options' );
	add_action( 'admin_menu', 'wp_mail_smtp_menus' );
	register_activation_hook( __FILE__, 'wp_mail_smtp_activate' );
	add_filter( 'plugin_action_links', 'wp_mail_plugin_action_links', 10, 2 );
}

add_filter( 'wp_mail_from', 'wp_mail_smtp_mail_from' );
add_filter( 'wp_mail_from_name', 'wp_mail_smtp_mail_from_name' );

load_plugin_textdomain( 'wp-mail-smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

