=== Theater Room Designer ===
Contributors: srihayavadhana
Tags: theater, home-theater, room-design, 3d-visualization, audio-video
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive 3D theater room design tool that allows users to visualize and plan their perfect home theater setup with room dimensions, seating arrangements, and speaker configurations.

== Description ==

Theater Room Designer is a powerful WordPress plugin that brings professional theater room planning capabilities to your website. Similar to Audio Advice's Home Theater Designer, this plugin provides an interactive 3D environment where users can:

**Key Features:**

* **3D Room Visualization** - Real-time 3D rendering using Three.js
* **Room Dimension Controls** - Customize width, length, and height
* **Screen Configuration** - Support for TVs and projector screens
* **Audio System Planning** - Multiple speaker configurations (2.1, 5.1, 7.1, 9.1)
* **Seating Arrangements** - Various seating types and layouts
* **Smart Recommendations** - Automatic suggestions for optimal placement
* **Save & Load Designs** - Users can save their room designs
* **Export Functionality** - Export designs as JSON files
* **Social Sharing** - Share designs on social media
* **Responsive Design** - Works on desktop, tablet, and mobile devices

**Perfect for:**

* Home theater retailers and installers
* Interior designers specializing in media rooms
* AV consultants and professionals
* Home improvement websites
* DIY home theater enthusiasts

**Admin Features:**

* Comprehensive settings panel
* Design management dashboard
* Usage statistics and analytics
* Export/import functionality
* Customizable default values
* Guest user support options

== Installation ==

1. Upload the `theater-room-designer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Theater Designer' in your WordPress admin menu to configure settings
4. Use the shortcode `[theater_room_designer]` to embed the tool on any page or post

== Frequently Asked Questions ==

= How do I embed the theater room designer on my website? =

Simply use the shortcode `[theater_room_designer]` on any page or post where you want the designer to appear.

= Can I customize the default room dimensions? =

Yes! Go to Theater Designer > Settings in your WordPress admin to set default values for room dimensions, screen sizes, and speaker configurations.

= Can guests save their designs? =

This depends on your settings. You can enable or disable guest saving in the plugin settings. When enabled, guests can save designs locally in their browser.

= What speaker configurations are supported? =

The plugin supports 2.1, 5.1, 7.1, and 9.1 speaker configurations with automatic optimal placement calculations.

= Is the plugin mobile-friendly? =

Yes, the plugin is fully responsive and works on desktop, tablet, and mobile devices.

= Can I export user designs? =

Yes, both users and administrators can export designs as JSON files for backup or sharing purposes.

== Screenshots ==

1. Main theater room designer interface with 3D visualization
2. Room dimension controls and configuration options
3. Speaker placement and audio configuration
4. Seating arrangement options
5. Admin dashboard with usage statistics
6. Plugin settings page
7. Mobile responsive design
8. Save and load design functionality

== Changelog ==

= 1.0.0 =
* Initial release
* 3D room visualization with Three.js
* Complete room design functionality
* Admin dashboard and settings
* Save/load design capabilities
* Social sharing features
* Responsive design implementation
* Comprehensive documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release of Theater Room Designer plugin.

== Shortcode Parameters ==

The `[theater_room_designer]` shortcode accepts the following optional parameters:

* `width` - Set the width of the designer (default: 100%)
* `height` - Set the height of the designer (default: 600px)
* `show_saved` - Show saved designs list (default: true)
* `allow_save` - Allow users to save designs (default: true)

Example: `[theater_room_designer width="800px" height="500px" show_saved="false"]`

== Developer Information ==

**System Requirements:**
* WordPress 5.0 or higher
* PHP 7.4 or higher
* Modern web browser with WebGL support
* Minimum 2GB RAM recommended for optimal performance

**Browser Compatibility:**
* Chrome 60+
* Firefox 55+
* Safari 12+
* Edge 79+

**Third-party Libraries:**
* Three.js for 3D rendering
* OrbitControls for camera manipulation

== Support ==

For support, feature requests, or bug reports, please visit https://www.srihayavadhana.com/support or contact Sri Hayavadhana Info-Tech directly.

== Privacy Policy ==

This plugin stores room design data in your WordPress database. No personal information is collected without explicit user consent. When users save designs, only the design data and basic timestamp information is stored.

== Credits ==

Inspired by professional theater room design tools, this plugin aims to democratize access to high-quality home theater planning capabilities.

== License ==

This plugin is licensed under the GPLv2 or later. You are free to use, modify, and distribute this plugin according to the terms of the GPL license.