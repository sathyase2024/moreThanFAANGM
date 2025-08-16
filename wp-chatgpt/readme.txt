=== WP ChatGPT AI Chatbot ===
Contributors: cursor-ai
Tags: chatbot, openai, ai, chatgpt, assistant
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a ChatGPT-like AI chatbot to your WordPress site via a floating widget and a shortcode.

== Description ==
A lightweight, no-dependency plugin to embed a ChatGPT-like assistant on your site. Configure your API key and model, and use the floating widget or the shortcode.

== Installation ==
1. Upload the `wp-chatgpt` folder to `/wp-content/plugins/` or install via the WordPress admin Plugins page.
2. Activate the plugin.
3. Go to Settings → WP ChatGPT and set your API key.

== Usage ==
- Floating widget: enable in settings.
- Shortcode: `[wp_chatgpt height="600px"]`

== Frequently Asked Questions ==
= Which models are supported? =
Any Chat Completions-compatible model. Defaults to `gpt-4o-mini`.

= Where is chat history stored? =
Optionally in the browser's localStorage only.

== Changelog ==
= 1.0.0 =
- Initial release