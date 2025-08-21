# WP Theater Designer

A lightweight WordPress plugin that adds a theater room designer UI with canvas visualization and simple export options.

## Features

- Canvas-based room layout renderer
- Configure room dimensions, seating, and speaker presence
- Export PNG image and JSON configuration
- Shortcode-based usage: `[theater_designer]`

## Installation

1. Download or copy the `wp-theater-designer` folder into your WordPress site's `wp-content/plugins/` directory.
2. In the WordPress admin, go to Plugins and activate "WP Theater Designer".

## Usage

Add the shortcode to any page or post:

```
[theater_designer]
```

Optional shortcode attributes:

- `unit`: `ft` or `m` (default: `ft`)
- `room_width`: numeric (default: `16`)
- `room_depth`: numeric (default: `20`)
- `ceiling_height`: numeric (default: `9`)
- `screen_width`: numeric (default: `10`)

Example:

```
[theater_designer unit="ft" room_width="18" room_depth="24" ceiling_height="9" screen_width="11"]
```

## Notes

- This plugin is a simplified tool intended to provide fast, client-side visualization. It does not replicate all features of proprietary tools.
- No data is stored on the server. Exports are generated locally in the browser.

## License

GPLv2 or later.