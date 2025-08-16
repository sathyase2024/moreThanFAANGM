<?php
namespace AIVID;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webhook_Controller {
	public function init() : void {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'ai-video/v1', '/webhook/(?P<provider>[a-z0-9_-]+)', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => '__return_true',
			] );
		} );
	}

	public function handle_webhook( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
		$provider = sanitize_key( (string) $request['provider'] );
		$body     = $request->get_json_params();

		// In production, validate signatures and payloads per provider.
		// For mock, simply acknowledge.
		return new WP_REST_Response( [ 'received' => true, 'provider' => $provider, 'body' => $body ], 200 );
	}
}