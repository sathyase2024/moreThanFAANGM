=== Deejos Timeline (WPBakery + Shortcode) ===
Contributors: your-name
Tags: timeline, wpbakery, visual composer, shortcode
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later

Deejos-style alternating vertical timeline. Provides a [deejos_timeline] shortcode and a WPBakery element with param group for items.

== Usage ==

1) Install
- Zip this folder and upload via Plugins → Add New → Upload Plugin, or copy it to wp-content/plugins/deejos-timeline.
- Activate "Deejos Timeline (WPBakery + Shortcode)".

2) WPBakery element
- Edit a page with WPBakery → Add Element → Deejos Timeline.
- Add items (Date, Title, Text, Image). Optional Accent Color.

3) Shortcode (without WPBakery)
- Example with JSON items (minimal):
[deejos_timeline accent_color="#0ea5a8" items='[{"date":"2020","title":"Started","text":"Kicked off the project."},{"date":"2021","title":"Growth","text":"Scaled the team."}]']

== Notes ==
- Styles are enqueued automatically when the shortcode renders.
- The accent color can be overriden in the element settings or by CSS.

