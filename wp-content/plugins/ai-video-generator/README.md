# AI Video Generator (Sora-like) for WordPress

Front-end AI video generation via pluggable providers (Sora-like). Ships with a mock provider for demos and a shortcode your site visitors can use.

## Features
- Shortcode `[ai_video_generator]` renders a user-facing form to request a video by prompt
- REST API endpoints to create and poll jobs
- Pluggable provider interface (mock included; Sora stub included to wire in once available)
- Stores finished videos in the WordPress Media Library
- Simple per-IP daily quota (default: 5 requests/day)

## Installation
1. Copy the `ai-video-generator` folder to `wp-content/plugins/`.
2. Activate the plugin in WordPress Admin → Plugins.
3. Configure provider settings in Settings → AI Video.
4. Add the shortcode `[ai_video_generator]` to a page.

## Usage
- Visitors submit a prompt and options.
- The plugin creates a job and polls for completion.
- When done, the resulting video is shown and saved to the Media Library.

## Providers
- Mock provider: instant demo with a public sample video URL.
- Sora (stub): set API Base and Key in Settings → AI Video, switch provider to Sora. The stub uses typical REST patterns: `POST /v1/videos/generations` to submit, `GET /v1/videos/generations/{id}` to poll, `POST .../{id}/cancel` to cancel. Replace endpoints/fields to match the official API when available.

## Security
- Public endpoints for demo. Before production, add: rate limiting, CAPTCHA, authentication, provider webhook signature verification.

## Shortcode
- `[ai_video_generator]` — renders the default form.

## REST API
- `POST /wp-json/ai-video/v1/jobs` — Create a job
- `GET /wp-json/ai-video/v1/jobs/{id}` — Poll job status
- `POST /wp-json/ai-video/v1/jobs/{id}/cancel` — Cancel a job (if provider supports)
- `POST /wp-json/ai-video/v1/webhook/{provider}` — Provider webhook receiver

## Development
- Main file: `ai-video-generator.php`
- Includes: `includes/`
- Providers: `includes/providers/`
- Assets: `assets/`

This plugin follows WordPress coding standards and uses tabs for indentation.