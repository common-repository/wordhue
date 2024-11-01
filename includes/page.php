<?php
function codex_nas_lighting_shortcode( $atts ) {

	$lights = get_transient( 'codex_nas_lighting_lights' );
	if( $lights == false ) {
		codex_nas_lighting_lights_job();
		$lights = get_transient( 'codex_nas_lighting_lights' );
		$timestamp = wp_next_scheduled( 'codex_nas_lighting_lights_job' );
		if( $timestamp == false )
			wp_schedule_event( time(), 'hourly', 'codex_nas_lighting_lights_job' );
	}

	$sensors = get_transient( 'codex_nas_lighting_sensors' );
	if( $sensors == false ) {
		codex_nas_lighting_sensors_job();
		$sensors = get_transient( 'codex_nas_lighting_sensors' );
		$timestamp = wp_next_scheduled( 'codex_nas_lighting_sensors_job' );
		if( $timestamp == false )
			wp_schedule_event( time(), 'hourly', 'codex_nas_lighting_sensors_job' );
	}

	// Check the User Capability
	if( current_user_can( 'manage_options' ) == false ) {
		wp_login_form();
		return;
	}

	ob_start();

	echo '<div id="lighting">';

	// Lights
	if( !empty( $lights ) ) {
		echo '<div id="lighting-lights">';

		echo '<p>';
		echo '<a href="' . add_query_arg( array( 'action' => 'refresh_lights', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Refresh lights', 'philips_hue_lighting' ) . '</a>' . ' | ';
		echo '<a href="' . add_query_arg( array( 'action' => 'lights_on', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Turn lights on', 'philips_hue_lighting' ) . '</a>' . ' | ';
		echo '<a href="' . add_query_arg( array( 'action' => 'lights_off', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Turn lights off', 'philips_hue_lighting' ) . '</a>' . ' | ';
		echo '<a href="' . add_query_arg( array( 'action' => 'flash_lights', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Flash lights', 'philips_hue_lighting' ) . '</a>';
		// echo '<a href="' . add_query_arg( array( 'action' => 'set_location', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Set Location', 'philips_hue_lighting' ) . '</a>';
		echo '</p>';

		echo '<ul id="lights">';
		foreach( $lights as $light ) {
			echo '<li id="light-' . $light['uniqueid'] . '" class="light-id-' . $light['id'] . ( empty( $light['reachable'] ) ? ' light-disabled' : '' ) . '">';
			echo '<strong>' . $light['name'] . '</strong>';
			echo ' (<abbr title="' . $light['manufacturername'] . ' ' . $light['modelid'] . ' - ' . $light['swversion'] . '">' . $light['type'] . '</abbr>)';
			echo '<br />';
			echo '<div id="slider" class="light-slider light-slider-id-' . $light['id'] . '" data-light-id="' . $light['id'] . '" data-brightness="' . $light['brightness'] . '" data-uniqueid="' . $light['uniqueid'] . '"></div>';
			echo '<a href="' . add_query_arg( array( 'action' => ( !empty( $light['on'] ) ? 'light_off' : 'light_on' ), 'light_id' => $light['id'], '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" title="' . ( !empty( $light['on'] ) ? __( 'Turn the light off', 'philips_hue_lighting' ) : __( 'Turn the light on', 'philips_hue_lighting' ) ) . '">' . ( empty( $light['on'] ) ? __( 'Off', 'philips_hue_lighting' ) : __( 'On', 'philips_hue_lighting' ) ) . '</a>';
			echo ' - ';
			echo __( 'Brightness', 'philips_hue_lighting' ) . ': <span class="brightness light-brightness-id-' . $light['id'] . '">' . $light['brightness'] . '</span>';
			if( empty( $light['reachable'] ) ) {
				echo '<p>' . sprintf( __( '%s cannot be reached by the Hue Bridge. Is it turned on at the wall?', 'philips_hue_lighting' ), $light['name'] ) . '</p>';
			}
			if( CODEX_NAS_HUE_DEBUG && isset( $_GET['iddqd'] ) ) {
				echo '<hr />';
				print_r( $light );
			}
			echo '<hr />';
			echo '</li>';
		}
		echo '</ul>
<!-- #lights -->';
		echo '<p>' . __( 'Total lights', 'philips_hue_lighting' ) . ': ' . count( $lights ) . '</p>';
		echo '</div>';
		echo '<!-- #lighting-lights -->';
	}
	
	// Sensors
	if( !empty( $sensors ) ) {
		echo '<div id="lighting-sensors">';
		echo '<h3>' . __( 'Sensors', 'philips_hue_lighting' ) . '</h3>';

		echo '<p>';
		echo '<a href="' . add_query_arg( array( 'action' => 'refresh_sensors', '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" class="button">' . __( 'Refresh sensors', 'philips_hue_lighting' ) . '</a>';
		echo '</p>';

		echo '<div class="ajax">';

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

		echo '</div>';
		echo '</div>';
		echo '<!-- #lighting-sensors -->';
	}

	echo '</div>';
	echo '<!-- #lighting -->';

	$output = ob_get_clean();
	return $output;

}
add_shortcode( 'philips_hue_lighting', 'codex_nas_lighting_shortcode' );
?>