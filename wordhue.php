<?php
/*
Plugin Name: WordHue
Plugin URI: https://www.visser.io/tools/philips-hue/
Description: Connect your Philips hue Bridge to WordPress and control your home Lights, Switches and Sensors.
Author: Visser I/O
Author URI: https://www.visser.io
Version: 1.5.1
Text Domain: philips_hue_lighting
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Turn this on to enable additional debugging options at export time
if( !defined( 'CODEX_NAS_HUE_DEBUG' ) )
	define( 'CODEX_NAS_HUE_DEBUG', false );

include_once( 'includes/widget.php' );
include_once( 'includes/page.php' );
include_once( 'includes/lighting-lights.php' );
include_once( 'includes/lighting-sensors.php' );

function codex_nas_lighting_init() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	// Check if the Bridge Username and Bridge IP have been provided
	if( $username == false || $ip_address == false )
		return;

	$action = ( isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : false );
	if( !empty( $action ) ) {
		switch( $action ) {

			case 'refresh_lights':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					codex_nas_lighting_delete_lights();
					codex_nas_lighting_lights_job();
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'refresh_sensors':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					codex_nas_lighting_delete_sensors();
					codex_nas_lighting_sensors_job();
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'lights_on':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					codex_nas_lighting_turn_lights_on();
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'lights_off':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					codex_nas_lighting_turn_lights_off();
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'light_on':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					$light_id = ( isset( $_GET['light_id'] ) ? sanitize_text_field( $_GET['light_id'] ) : false );
					if( $light_id !== false )
						codex_nas_lighting_turn_light_on( $light_id );
					$url = add_query_arg( array( 'action' => null, 'light_id' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'light_off':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					$light_id = ( isset( $_GET['light_id'] ) ? sanitize_text_field( $_GET['light_id'] ) : false );
					if( $light_id !== false )
						codex_nas_lighting_turn_light_off( $light_id );
					$url = add_query_arg( array( 'action' => null, 'light_id' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'flash_lights':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					codex_nas_lighting_flash_lights();
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

			case 'set_location':
				// We need to verify the nonce.
				if( !empty( $_GET ) && check_admin_referer( 'codex_nas_lighting' ) ) {
					$sensor_id = 1;
					$url = 'http://' . $ip_address . '/api/' . $username . '/sensors/' . $sensor_id . '/config';
					$args = array(
						'method' => 'PUT',
						'body' => '{"long": "53.551085W", "lat": "9.993682N", "sunriseoffset": 30, "sunsetoffset": -30}'
					);
					$response = wp_remote_post( $url, $args );
					error_log( 'set_location: ' . print_r( $response, true ) );
					$url = add_query_arg( array( 'action' => null, '_wpnonce' => null ) );
					wp_redirect( $url );
					exit();
				}
				break;

		}
	}

}
add_action( 'init', 'codex_nas_lighting_init' );

function codex_nas_lighting_flash_lights() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( $lights == false ) {
		codex_nas_lighting_lights_job();
		$lights = get_transient( 'codex_nas_lighting_lights' );
	}
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light['id'] . '/state';
			$args = array(
				'method' => 'PUT',
				'body' => '{"alert":"select"}'
			);
			$response = wp_remote_post( $url, $args );
			if( is_wp_error( $response ) ) {
				error_log( 'lights alert: ' . print_r( $response, true ) );
				return false;
			} else {
				$lights[$key]['alert'] = 'select';
			}
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
		return true;
	}

}

function codex_nas_lighting_dim_lights() {

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( $lights == false ) {
		codex_nas_lighting_lights_job();
		$lights = get_transient( 'codex_nas_lighting_lights' );
	}
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			codex_nas_lighting_light_brightness( $light['id'], 139 );
		}
		return true;
	}

}

function codex_nas_lighting_full_brightness() {

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( $lights == false ) {
		codex_nas_lighting_lights_job();
		$lights = get_transient( 'codex_nas_lighting_lights' );
	}
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			codex_nas_lighting_light_brightness( $light['id'], 254 );
		}
		return true;
	}

}

function codex_nas_lighting_light_brightness( $light_id = 0, $brightness = false ) {

	if( empty( $light_id ) && $brightness == false )
		return;

	if( $brightness == 0 )
		$brightness = 1;

	if( $brightness > 254 )
		$brightness = 254;

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light_id . '/state';

	$args = array(
		'method' => 'PUT',
		'body' => '{"bri":' . $brightness . '}'
	);
	$response = wp_remote_post( $url, $args );
	if( is_wp_error( $response ) ) {
		error_log( 'light brightness: ' . print_r( $response, true ) );
	}

	// Update light brightness
	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			if( $light['id'] == $light_id ) {
				$lights[$key]['brightness'] = $brightness;
				set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
				break;
			}
		}
	}
	return true;

}

function codex_nas_lighting_lights_slider_javascript() {

	// Check the User Capability
	if( current_user_can( 'manage_options' ) == false )
		return;

	$post_id = codex_nas_lighting_get_option( 'post_id', false );
	if( $post_id !== false ) {
		// Return the Light Javascript if we are on the Lighting Page
		if( get_the_ID() <> $post_id )
			return;
	}

?>
<script type="text/javascript">
	var $j = jQuery.noConflict();
	$j(function() {

		$j(function() {
			$j( ".light-slider" ).each(function() {
				var value = $j(this).attr('data-brightness');
				$j(this).slider({
					value: value,
					range: "min",
					animate: true,
					max: 255,
					min: 0,
					change: function( event, ui ){
						var light_id = $j(this).attr('data-light-id');
						var uniqueid = $j(this).attr('data-uniqueid');
						var brightness = $j(this).slider("value");
						$j(this).attr('data-brightness', brightness);
						$j( '.light-brightness-id-' + light_id ).text(brightness);
						var data = {
							'action': 'update_light_status',
							'light_id': light_id,
							'uniqueid': uniqueid,
							'brightness': brightness
						};
						var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
						$j.post(ajaxurl, data, function(response) {
							/* alert(response); */
						});
					}
				});
			});
		});

	});

	jQuery(document).ready(function($) {

		var data = {
			'action': 'refresh_lighting_sensors',
		};
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

		setInterval(function() {
			$.get(ajaxurl, data, function (result) {
				$( '#lighting-sensors .ajax' ).html( result );
			});
		},10000);

	});
</script>
<?php

}
add_action( 'wp_footer', 'codex_nas_lighting_lights_slider_javascript' );

function codex_nas_lighting_lights_widget_javascript() {

	// Check the User Capability
	if( current_user_can( 'manage_options' ) == false )
		return;

	$post_id = codex_nas_lighting_get_option( 'post_id', false );
	if( $post_id !== false ) {
		// Do not return the Light Javascript if we are on the Lighting Page
		if( get_the_ID() == $post_id )
			return;
	}

?>
<script type="text/javascript">
	var $j = jQuery.noConflict();
	$j(function() {
		$j(".lights a.button").click(function(e){
			e.preventDefault();
			var light_id = $j(this).attr('data-light-id');
			var uniqueid = $j(this).attr('data-uniqueid');
			var state = $j(this).attr('data-state');
			var brightnesss = $j(this).attr('data-brightness');
			var data = {
				'action': 'update_light_state',
				'light_id': light_id,
				'uniqueid': uniqueid,
				'state': state
			};
			var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
			$j.post(ajaxurl, data, function(response) {
				if( state == 'light_off' ) {
					$j(".light-id-" + light_id + " a.button").text( "Off - Brightness: " + brightnesss );
					$j(".light-id-" + light_id + " a.button").data( "state", "light_on" );
					$j(".light-id-" + light_id + " a.button").attr( "data-state", "light_on" );
				} else {
					$j(".light-id-" + light_id + " a.button").text( "On - Brightness: " + brightnesss );
					$j(".light-id-" + light_id + " a.button").data( "state", "light_off" );
					$j(".light-id-" + light_id + " a.button").attr( "data-state", "light_off" );
				}
				
			});
		});
	});

</script>
<?php

}
add_action( 'wp_footer', 'codex_nas_lighting_lights_widget_javascript' );

function codex_nas_lighting_enqueue_styles() {

	wp_enqueue_style( 'codex_nas_lighting', plugins_url( '/css/style.css', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) ) );

}
add_action( 'wp_enqueue_scripts', 'codex_nas_lighting_enqueue_styles' );

function codex_nas_lighting_light_brightness_ajax() {

	$light_id = ( isset( $_POST['light_id'] ) ? absint( $_POST['light_id'] ) : false );
	$uniqueid = ( isset( $_POST['uniqueid'] ) ? sanitize_text_field( $_POST['uniqueid'] ) : '' );
	$brightness = ( isset( $_POST['brightness'] ) ? absint( $_POST['brightness'] ) : false );

	if( !empty( $light_id ) && $brightness !== false ) {
		codex_nas_lighting_light_brightness( $light_id, $brightness );
	}
	wp_die();

}
add_action( 'wp_ajax_update_light_status', 'codex_nas_lighting_light_brightness_ajax' );
// add_action( 'wp_ajax_nopriv_update_light_status', 'codex_nas_lighting_lights_ajax' );

function codex_nas_lighting_light_state_ajax() {

	$light_id = ( isset( $_POST['light_id'] ) ? absint( $_POST['light_id'] ) : false );
	$uniqueid = ( isset( $_POST['uniqueid'] ) ? sanitize_text_field( $_POST['uniqueid'] ) : '' );
	$state = ( isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : false );

	$response = false;
	if( !empty( $light_id ) && $state !== false ) {
		switch( $state ) {

			case 'light_on':
				$response = codex_nas_lighting_turn_light_on( $light_id );
				break;

			case 'light_off':
				$response = codex_nas_lighting_turn_light_off( $light_id );
				break;

		}
	}
	echo $response;
	wp_die();

}
add_action( 'wp_ajax_update_light_state', 'codex_nas_lighting_light_state_ajax' );

// Register and load the widget
function codex_nas_load_lighting_widgets() {

	register_widget( 'codex_nas_lighting_lights_widget' );

}
add_action( 'widgets_init', 'codex_nas_load_lighting_widgets' );

if( is_admin() ) {

	function codex_nas_lighting_admin_menu() {

		add_options_page( __( 'Philips hue', 'philips_hue_lighting' ), __( 'Philips hue', 'philips_hue_lighting' ), 'manage_options', 'philips_hue', 'codex_nas_lighting_settings' );

	}
	add_action( 'admin_menu', 'codex_nas_lighting_admin_menu' );

	function codex_nas_lighting_settings_init() {

		register_setting( 'philips_hue_settings', 'philips_hue_settings' );

		add_settings_section( 'codex_nas_lighting_settings', 'General Settings', 'codex_nas_lighting_settings_text', 'philips_hue' );

		add_settings_field( 'codex_nas_lighting_bridge_ip', 'Hue Bridge IP Address', 'codex_nas_lighting_settings_bridge_ip', 'philips_hue', 'codex_nas_lighting_settings' );
		add_settings_field( 'codex_nas_lighting_bridge_username', 'Hue Bridge User', 'codex_nas_lighting_settings_bridge_username', 'philips_hue', 'codex_nas_lighting_settings' );
		add_settings_field( 'codex_nas_lighting_bridge_post_id', 'Post ID', 'codex_nas_lighting_settings_post_id', 'philips_hue', 'codex_nas_lighting_settings' );

	}
	add_action( 'admin_init', 'codex_nas_lighting_settings_init' );

	function codex_nas_lighting_settings() {

?>
<div class="wrap">
	<h2><?php _e( 'Philips hue', 'philips_hue_lighting' ); ?></h2>
	<form method="post" action="options.php">
<?php
	// This prints out all hidden setting fields
	settings_fields( 'philips_hue_settings' );
	do_settings_sections( 'philips_hue' );
	submit_button();
?>
	</form>
	</div>
<?php

	}

	function codex_nas_lighting_settings_text() {

		_e( 'Enter your settings below:', 'philips_hue_lighting' );

	}

	function codex_nas_lighting_settings_bridge_ip() {

		$value = codex_nas_lighting_get_option( 'bridge_ip', '' );

		echo '
<input type="text" name="philips_hue_settings[bridge_ip]" id="bridge_ip" value="' . $value . '" class="code" /> 
<span class="description">'. __( 'Enter the local IP address of your Philips hue Bridge.', 'philips_hue_lighting' ) . '</span>';

	}

	function codex_nas_lighting_settings_bridge_username() {

		$value = codex_nas_lighting_get_option( 'bridge_username', '' );

		echo '
<input type="text" name="philips_hue_settings[bridge_username]" id="bridge_username" value="' . $value . '" class="code" /> 
<span class="description">'. __( 'Enter the user authenticated on your Philips hue Bridge.', 'philips_hue_lighting' ) . ' ' . sprintf( '<a href="%s" target="_blank">' . __( 'Learn more', 'philips_hue_lighting' ) . '</a>', 'http://www.developers.meethue.com/documentation/getting-started' ) . '</span>';

	}

	function codex_nas_lighting_settings_post_id() {

		$value = codex_nas_lighting_get_option( 'post_id', '' );

		echo '
<input type="text" name="philips_hue_settings[post_id]" id="post_id" value="' . $value . '" class="code" /> 
<span class="description">'. __( 'Enter the Post ID of the Page where the Shortcode <code>[philips_hue_lighting]</code> has been added to the Post Content.', 'philips_hue_lighting' ) . '</span>';

	}

}

function codex_nas_lighting_get_option( $name = '', $default = false ) {

	if( empty( $name ) )
		return;

	$options = get_option( 'philips_hue_settings' );
	$value = ( isset( $options[$name] ) ? sanitize_text_field( $options[$name] ) : false );
	if( $value == false && $default !== false )
		$value = $default;
	return $value;

}
?>