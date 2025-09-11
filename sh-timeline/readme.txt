=== SH Timeline (WPBakery + Shortcode) ===
Contributors: your-name
Tags: timeline, wpbakery, visual composer, shortcode
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later

Alternating vertical timeline (Deejos-like). Provides a [sh_timeline] shortcode and a WPBakery element with param group for items.

== Features ==
- Alternating vertical layout with center line and markers
- Step numbers and title header per item
- Single image or multi-image gallery per item
- Auto-rotating gallery (hover to pause), configurable interval

== Usage ==

1) Install
- Zip this folder and upload via Plugins → Add New → Upload Plugin, or copy it to wp-content/plugins/sh-timeline.
- Activate "SH Timeline (WPBakery + Shortcode)".

2) WPBakery element
- Edit a page with WPBakery → Add Element → SH Timeline.
- Add items (Date, Title, Text, Image). Optional Accent Color.
 - For multiple images: use the Gallery field (attach_images). Autoplay Interval (ms) controls slide speed.

3) Shortcode (without WPBakery)
- Example with JSON items (minimal):
[sh_timeline accent_color="#0ea5a8" items='[{"date":"2020","title":"Started","text":"Kicked off the project."},{"date":"2021","title":"Growth","text":"Scaled the team."}]']

- Example with gallery (URLs) and custom interval:
[sh_timeline accent_color="#0ea5a8" autoplay_ms="4000" items='[
 {"date":"Step 1","title":"Design","text":"Concept & planning.","gallery_urls":["https://example.com/a.jpg","https://example.com/b.jpg"]},
 {"date":"Step 2","title":"Build","text":"Construction phase.","gallery_urls":"https://example.com/c.jpg, https://example.com/d.jpg"}
]']

== Notes ==
- Styles are enqueued automatically when the shortcode renders.
- The accent color can be overriden in the element settings or by CSS.

