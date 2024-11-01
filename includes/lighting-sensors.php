<?php
function codex_nas_lighting_sensors_job() {

	$ip_address = codex_nas_lighting_get_option( 'bridge_ip', false );
	$username = codex_nas_lighting_get_option( 'bridge_username', false );

	// Check if the Bridge Username and Bridge IP have been provided
	if( $username == false || $ip_address == false ) {
		$error_message = 'sensors_job (warning): No username or IP address has been set from Settings > Philips Hue screen. URL: ' . $url;
		error_log( '[wordhue] ' . $error_message );
		set_transient( 'codex_nas_lighting_sensors', false, HOUR_IN_SECONDS );
		return;
	}

	$url = 'http://' . $ip_address . '/api/' . $username . '/sensors';
	$response = wp_remote_get( $url );
	if( is_array( $response ) ) {
		if( $response['response']['code'] == 200 ) {
			$response = json_decode( $response['body'] );
			if( !empty( $response ) ) {
				set_transient( 'codex_nas_lighting_sensors_raw', $response, HOUR_IN_SECONDS );
				foreach( $response as $sensor_id => $sensor ) {
					$args = array(
						'id' => $sensor_id,
						'on' => absint( $sensor->config->on ),
						'reachable' => ( isset( $sensor->state->reachable ) ? absint( $sensor->state->reachable ) : '' ),
						'type' => $sensor->type,
						'name' => $sensor->name,
						'modelid' => $sensor->modelid,
						'manufacturername' => $sensor->manufacturername,
						'uniqueid' => ( isset( $sensor->uniqueid ) ? $sensor->uniqueid : '' ),
						'swversion' => $sensor->swversion,
						'lastupdated' => ( isset( $sensor->state->lastupdated ) ? $sensor->state->lastupdated : '' ),
						'buttonevent' => ( isset( $sensor->state->buttonevent ) ? $sensor->state->buttonevent : '' ),
						'battery' => ( isset( $sensor->config->battery ) ? $sensor->config->battery : '' )
					);
					// Check for other reachable states
					if( ( $args['reachable'] == '' ) && isset( $sensor->config->reachable ) )
						$args['reachable'] = absint( $sensor->config->reachable );
					// Check for different sensor Types
					if( !empty( $args['type'] ) ) {
						switch( $args['type'] ) {

							case 'Daylight':
								$args['daylight'] = ( isset( $sensor->state->daylight ) ? absint( $sensor->state->daylight ) : '' );
								break;

						}
					}
					$sensors[] = $args;
				}
			}
		} else {
			error_log( 'sensors_job: ' . print_r( $response, true ) );
		}
		set_transient( 'codex_nas_lighting_sensors', $sensors, HOUR_IN_SECONDS );
	} else {
		$error_message = 'sensors_job (error): wp_remote_get() returned false for the URL: ' . $url;
		error_log( '[wordhue] ' . $error_message );
	}

}
add_action( 'codex_nas_lighting_sensors_job', 'codex_nas_lighting_sensors_job' );

function codex_nas_ajax_refresh_lighting_sensors() {

	codex_nas_lighting_sensors_job();

	$sensors = get_transient( 'codex_nas_lighting_sensors' );
	if( !empty( $sensors ) ) {

		echo '<ul id="sensors">';
		$i = 0;
		foreach( $sensors as $sensor_id => $sensor ) {
			echo '<li id="sensor-' . $sensor_id . ' class="sensor-id-' . $sensor_id . ( isset( $sensor['reachable'] ) && empty( $sensor['reachable'] ) ? ' sensor-disabled' : '' ) . '">';
			echo ( isset( $sensor['reachable'] ) && $sensor['reachable'] <> '' ? '<span style="float:right;">' . ( empty( $sensor['reachable'] ) ? '0' : '1' ) . '</span>' : '' );
			echo '<strong>' . $sensor['name'] . '</strong>';
			echo ' (<abbr title="' . __( 'Manufacturer', 'philips_hue_lighting' ) . ': ' . $sensor['manufacturername'] . ' - ' . __( 'Model ID', 'philips_hue_lighting' ) . ': ' . $sensor['modelid'] . ' - ' . __( 'Version', 'philips_hue_lighting' ) . ': ' . $sensor['swversion'] . ( !empty( $sensor['battery'] ) ? ' - ' . __( 'Battery', 'philips_hue_lighting' ) . ': ' . $sensor['battery'] . '%' : '' ) . '">' . $sensor['type'] . '</abbr>)' . '<br />';
			echo ( !empty( $sensor['on'] ) ? __( 'On', 'philips_hue_lighting' ) : __( 'Off', 'philips_hue_lighting' ) );
			echo ' - ';
			if( $sensor['type'] == 'Daylight' ) {
				echo ( isset( $sensor['daylight'] ) && empty( $sensor['daylight'] ) ? 'Is no longer daylight' : 'Is daylight' );
				echo ' - ';
			}
			if( $sensor['type'] == 'ZLLSwitch' ) {
				echo ( !empty( $sensor['buttonevent'] ) ? __( 'Button event', 'philips_hue_lighting' ) . ': ' . $sensor['buttonevent'] : '-' );
				echo ' - ';
			}
			// echo ( $sensor['lastupdated'] <> 'none' ? __( 'Last updated', 'philips_hue_lighting' ) . ': ' . human_time_diff( strtotime( $sensor['lastupdated'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'philips_hue_lighting' ) : '' );
			echo ( $sensor['lastupdated'] <> 'none' ? __( 'Last updated', 'philips_hue_lighting' ) . ': ' . human_time_diff( strtotime( $sensor['lastupdated'] ), time() ) . ' ' . __( 'ago', 'philips_hue_lighting' ) : '' );
			// echo ( isset( $sensor['uniqueid'] ) && !empty( $sensor['uniqueid'] ) ? __( 'Unique ID', 'philips_hue_lighting' ) . ': ' . $sensor['uniqueid'] . '<br />' : '' );
			// echo '<br />';
			// echo 'state: ' . print_r( $sensor->state, true ) . '<br />';
			// echo 'config: ' . print_r( $sensor->config, true ) . '<br />';
			// echo print_r( $sensor, true );
			if( CODEX_NAS_HUE_DEBUG && isset( $_GET['iddqd'] ) ) {
				echo '<hr />';
				print_r( $sensor );
			}
			echo '<hr />';
			echo '</li>';
			$i++;
		}
		echo '</ul>
<!-- #sensors -->';
		echo '<p>' . __( 'Total sensors', 'philips_hue_lighting' ) . ': ' . $i . '</p>';

	}
	die();

}
add_action( 'wp_ajax_refresh_lighting_sensors', 'codex_nas_ajax_refresh_lighting_sensors' );
// add_action( 'wp_ajax_nopriv_refresh_lighting_sensors', 'codex_nas_ajax_refresh_lighting_sensors' );

function codex_nas_lighting_delete_sensors() {

	delete_transient( 'codex_nas_lighting_sensors' );

}
?>