<?php
function codex_nas_lighting_lights_job() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	// Check if the Bridge Username and Bridge IP have been provided
	if( $username == false || $ip_address == false ) {
		$error_message = 'lights_job (warning): No username or IP address has been set from Settings > Philips Hue screen. URL: ' . $url;
		error_log( '[wordhue] ' . $error_message );
		set_transient( 'codex_nas_lighting_lights', false, HOUR_IN_SECONDS );
		return;
	}

	$lights = array();
	$url = 'http://' . $ip_address . '/api/' . $username . '/lights';
	$response = wp_remote_get( $url );
	if( is_array( $response ) ) {
		if( is_wp_error( $response ) == false ) {
			if( $response['response']['code'] == 200 ) {
				$response = json_decode( $response['body'] );
				if( !empty( $response ) ) {
					set_transient( 'codex_nas_lighting_lights_raw', $response, HOUR_IN_SECONDS );
					foreach( $response as $light_id => $light ) {
						$args = array(
							'id' => $light_id,
							'on' => ( isset( $light->state->on ) ? absint( $light->state->on ) : '' ),
							'brightness' => ( isset( $light->state->bri ) ? absint( $light->state->bri ) : '' ),
							'alert' => $light->state->alert,
							'reachable' => absint( $light->state->reachable ),
							'type' => $light->type,
							'name' => $light->name,
							'modelid' => $light->modelid,
							'manufacturername' => $light->manufacturername,
							'uniqueid' => $light->uniqueid,
							'swversion' => $light->swversion
						);
						$lights[] = $args;
					}
				}
			} else {
				error_log( 'lights_job: ' . print_r( $response, true ) );
			}
		} else {
			$error_message = $response->get_error_message();
			error_log( 'lights_job (error): ' . $error_message );
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
	} else {
		$error_message = 'lights_job (error): wp_remote_get() returned false for the URL: ' . $url;
		error_log( '[wordhue] ' . $error_message );
	}

}
add_action( 'codex_nas_lighting_lights_job', 'codex_nas_lighting_lights_job' );

function codex_nas_lighting_delete_lights() {

	delete_transient( 'codex_nas_lighting_lights' );

}

function codex_nas_lighting_turn_lights_on() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light['id'] . '/state';
			$args = array(
				'method' => 'PUT',
				'body' => '{"on":true}'
			);
			$response = wp_remote_post( $url, $args );
			if( is_wp_error( $response ) ) {
				error_log( 'lights_on: ' . print_r( $response, true ) );
				return false;
			} else {
				$lights[$key]['on'] = true;
			}
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
		return true;
	}

}

function codex_nas_lighting_turn_lights_off() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light['id'] . '/state';
			$args = array(
				'method' => 'PUT',
				'body' => '{"on":false}'
			);
			$response = wp_remote_post( $url, $args );
			if( is_wp_error( $response ) ) {
				error_log( 'lights_off: ' . print_r( $response, true ) );
				return false;
			} else {
				$lights[$key]['on'] = false;
			}
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
		return true;
	}

}

function codex_nas_lighting_turn_light_on( $light_id = 0 ) {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			if( $light['id'] == $light_id ) {
				$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light_id . '/state';
				$args = array(
					'method' => 'PUT',
					'body' => '{"on":true}'
				);
				$response = wp_remote_post( $url, $args );
				if( is_wp_error( $response ) ) {
					error_log( 'light_on: ' . print_r( $response, true ) );
					return false;
				} else {
					$lights[$key]['on'] = true;
				}
				break;
			}
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
		return true;
	}

}

function codex_nas_lighting_turn_light_off( $light_id = 0 ) {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( !empty( $lights ) ) {
		foreach( $lights as $key => $light ) {
			if( $light['id'] == $light_id ) {
				$url = 'http://' . $ip_address . '/api/' . $username . '/lights/' . $light_id . '/state';
				$args = array(
					'method' => 'PUT',
					'body' => '{"on":false}'
				);
				$response = wp_remote_post( $url, $args );
				if( is_wp_error( $response ) ) {
					error_log( 'light_off: ' . print_r( $response, true ) );
					return false;
				} else {
					$lights[$key]['on'] = false;
				}
				break;
			}
		}
		set_transient( 'codex_nas_lighting_lights', $lights, HOUR_IN_SECONDS );
		return true;
	}

}
?>