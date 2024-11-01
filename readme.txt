=== WordHue ===
Contributors: visser, visser.labs
Donate link: https://www.visser.com.au/donate/
Tags: philips, philips hue, hue bridge, hue lights, hue switch, hue sensor
Requires at least: 3.0
Tested up to: 5.0
Stable tag: 1.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your Philips hue Bridge, Lights, Switches and Sensors to WordPress.

== Description ==

The WordHue Plugin allows you to do the following:

- Turn on/off all connected lights
- Turn on/off individual connected lights
- Set the dimming level of individual connected lights
- Flash notification alerts on all connected lights
- Turn on/off individual connected lights via the Lights Widgets

** Note: This Plugin is intended to be run on a WordPress install within your home network, not by exposing your Hue Bridge to the internet **

== Installation ==

The quickest method for installing this Plugin is:

1. Visit the Plugins > Add New screen from the WordPress Administration
1. Enter 'WordHue' into the Search Plugins field
1. Click "Install Now"
1. Finally click "Activate Plugin"
1. Create a new Page and paste this Shortcode into the Post Content section: [philips_hue_lighting]
1. Publish the Page
1. Go to the Settings > Philips hue screen
1. Enter the required Philips hue Bridge details and Post ID of the Page above
1. View the Page you created above

If you would prefer to do things manually then follow these instructions:

1. Upload the `wordhue` folder to the `/wp-content/plugins/` directory
1. Activate the Plugin through the 'Plugins' menu in WordPress
1. Create a new Page and paste this Shortcode into the Post Content section: [philips_hue_lighting]
1. Publish the Page
1. Go to the Settings > Philips hue screen
1. Enter the required Philips hue Bridge details and Post ID of the Page above
1. View the Page you created above

== FAQ ==

= How do I find my Hue Bridge IP address? =

WordHue needs to know the IP address of the Hue Bridge on your local network. In the near future the Philips Hue API will support remote access removing this local network restriction.

Have a look at the [Getting started document on the Hue Developer Program](http://www.developers.meethue.com/documentation/getting-started) website for steps to find your Hue Bridge IP address and connect your Hue Kit.

= How do I generate my Hue Bridge username? =

WordHue needs to know a username that has been authenticated to access your Hue Bridge.

Have a look at the [Getting started document on the Hue Developer Program](http://www.developers.meethue.com/documentation/getting-started) website for steps to generate a new Hue Bridge username or retreive existing ones.

= Can I change the state of a light by calling a PHP function within your Plugin? =

You sure can! Call codex_nas_lighting_turn_light_on( $light_id ) or codex_nas_lighting_turn_light_off( $light_id ) within your Theme or Plugin. Make sure you replace $light_id with the Light ID of your connected light and it's a good idea to check that our Plugin is activated and the required PHP function is available.

To turn all lights on or off use the following PHP functions:

- codex_nas_lighting_turn_lights_on()
- codex_nas_lighting_turn_lights_off()

As above, it's a good idea to check that our Plugin is activated and the required PHP function is available.

= Can you add XYZ feature? =

Let's do it! Create a new Support topic by switching to the Support section of this page. Please be creative and descriptive! :)

= Can WordHue make me a morning coffee? =

WordHue only supports Hue products, see our cafePress Plugin for this functionality. (jk) :P

== Screenshots ==

1. Switch on and adjust the brightness of Philips hue Lights connected to your Philips hue Bridge.
2. Monitor sensors connected to the Philips hue Bridge.
3. Use the Lights Widget to quickly switch on/off Lights.

== Changelog ==

= 1.5.1 =
* Added: Check for false response on wp_remote_get()

= 1.5 =
* Changed: Hide the Lights widget if no lights are detected
* Added: Added refresh lights to Lights widget

= 1.4 =
* Added: AJAX Lights Widget

= 1.3 =
* Added: Detection of different sensor types

= 1.2 =
* Fixed: Lights Widget missing token check

= 1.1 =
* Changed: Renamed to WordHue

= 1.0 =
* Initial release