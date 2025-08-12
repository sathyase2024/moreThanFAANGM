# AA 3D Viewer (WordPress Plugin)

Lightweight 3D model viewer for WordPress using Three.js. Embed interactive GLTF/GLB models via a shortcode.

## Install

1. Copy the `aa-3d-viewer` folder to `wp-content/plugins/`.
2. Activate "AA 3D Viewer" in WordPress Admin > Plugins.

## Usage

Insert the shortcode where you want the 3D viewer:

```
[aa3d src="https://example.com/path/to/model.glb" poster="https://example.com/path/to/poster.jpg" background="#111111" exposure="1.1" autoRotate="true" cameraFov="45"]
```

- `src` (required): URL to `.glb` or `.gltf` model
- `poster` (optional): image shown until the model loads
- `background` (optional): scene background color (hex)
- `exposure` (optional): tone mapping exposure (default `1.0`)
- `autoRotate` (optional): `true|false` (default `true`)
- `cameraFov` (optional): camera field of view in degrees (default `45`)
- `maxAzimuthAngle`, `minAzimuthAngle`, `maxPolarAngle`, `minPolarAngle` (optional): constrain OrbitControls (radians)

## Embed an external 3D Designer (iframe)

Use this to embed a hosted designer (e.g., a custom app similar to Audio Advice’s Home Theater Designer):

```
[aa3d_designer src="https://your-designer.app/embed" height="800" title="Home Theater Designer"]
```

- `src` (required): URL to the external app/experience
- `height` (optional): fixed height in px (default 720)
- `title` (optional): iframe title for accessibility
- `allow` (optional): feature policy string
- `loading` (optional): `lazy|eager`

### Iframe restrictions
Some sites set `X-Frame-Options` or `Content-Security-Policy: frame-ancestors` to block embedding. If your `src` is blocked, the plugin shows an overlay with an “Open Designer” button to launch the tool in a new window.

To avoid blocking, use a proper embed URL from the provider (if available) or host your own experience. The `[aa3d]` shortcode loads models you host yourself and is not affected by third-party iframe policies.

## Notes

- Three.js and loaders are loaded from a CDN for simplicity.
- AR button is shown only if WebXR AR is supported in the browser; AR session is not implemented in this version.
- Optimize your models for the web (draco/meshopt compression recommended).

## Roadmap

- Optional environment maps (HDRI)
- Basic AR session with WebXR hit-test
- Gutenberg block
- WooCommerce product gallery integration