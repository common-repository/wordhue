<?php
// Creating the widget
class codex_nas_lighting_lights_widget extends WP_Widget {

	function __construct() {

		parent::__construct(
			// Base ID of your widget
			'codex_nas_lighting_lights_widget',
			// Widget name will appear in UI
			__( 'Lights', 'philips_hue_lighting' ),
			// Widget description
			array( 'description' => __( 'Control the State of your Philip Hue Lights from a Widget.', 'philips_hue_lighting' ) )
		);

	}

	// Creating widget front-end
	// This is where the action happens
	public function widget( $args, $instance ) {

		// Check the User Capability
		if( current_user_can( 'manage_options' ) == false )
			return;

		$post_id = codex_nas_lighting_get_option( 'post_id', false );
		if( $post_id !== false ) {
			// Hide the Lights widget if we are on the Lighting Page
			if( get_the_ID() == $post_id )
				return;
		}

		$lights = get_transient( 'codex_nas_lighting_lights' );
		if( $lights == false ) {
			codex_nas_lighting_lights_job();
			$lights = get_transient( 'codex_nas_lighting_lights' );
			$timestamp = wp_next_scheduled( 'codex_nas_lighting_lights_job' );
			if( $timestamp == false )
				wp_schedule_event( time(), 'hourly', 'codex_nas_lighting_lights_job' );
		}

		if( !empty( $lights ) ) {

			// This is where you run the code and display the output

			$title = apply_filters( 'widget_title', $instance['title'] );
			// before and after widget arguments are defined by themes
			echo $args['before_widget'];
			if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

			echo '<div class="lights">';
			foreach( $lights as $light ) {
				echo '<p id="light-' . $light['uniqueid'] . '" class="light-id-' . $light['id'] . ( empty( $light['reachable'] ) ? ' light-disabled' : '' ) . '">';
				echo '<strong>' . $light['name'] . '</strong>';
				echo ' (<abbr title="' . $light['manufacturername'] . ' ' . $light['modelid'] . ' - ' . $light['swversion'] . '">' . $light['type'] . '</abbr>)';
				echo '<br />';
				echo '<a href="' . add_query_arg( array( 'action' => ( !empty( $light['on'] ) ? 'light_off' : 'light_on' ), 'light_id' => $light['id'], '_wpnonce' => wp_create_nonce( 'codex_nas_lighting' ) ) ) . '" title="' . ( !empty( $light['on'] ) ? __( 'Turn the light off', 'codex_nas' ) : __( 'Turn the light on', 'codex_nas' ) ) . '" class="button" data-light-id="' . $light['id'] . '" data-uniqueid="' . $light['uniqueid'] . '" data-brightness="' . $light['brightness'] . '" data-state="' . ( !empty( $light['on'] ) ? 'light_off' : 'light_on' ) . '">' . ( empty( $light['on'] ) ? __( 'Off', 'philips_hue_lighting' ) : __( 'On', 'philips_hue_lighting' ) ) . ' - ' . __( 'Brightness', 'philips_hue_lighting' ) . ': ' . $light['brightness'] . '</a>';
				echo '</p>';
			}
			echo '</div>';

			echo '<hr />';

			echo $args['after_widget'];

		}

	}

	// Widget Backend
	public function form( $instance ) {

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}	else {
			$title = __( 'Lights', 'philips_hue_lighting' );
		}
		// Widget admin form
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php

	}

	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;

	}

} // Class codex_nas_lighting_lights_widget ends here
?>