<?php
namespace AIVID\Providers;

use AIVID\Provider_Interface;
use AIVID\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sora_Provider implements Provider_Interface {
	public function get_name() : string { return 'sora'; }
	public function get_label() : string { return 'Sora (Stub)'; }

	private function get_api_base() : string {
		$options = get_option( Settings::OPTION_KEY, [] );
		$base    = isset( $options['sora_api_base'] ) ? (string) $options['sora_api_base'] : '';
		return rtrim( $base, '/' );
	}
	private function get_api_key() : string {
		$options = get_option( Settings::OPTION_KEY, [] );
		return isset( $options['sora_api_key'] ) ? (string) $options['sora_api_key'] : '';
	}

	public function submit_job( array $params ) {
		$base = $this->get_api_base();
		$key  = $this->get_api_key();
		if ( ! $base || ! $key ) {
			return new WP_Error( 'aivid_sora_not_configured', __( 'Sora provider not configured. Set API base and key.', 'ai-video-generator' ) );
		}

		$payload = [
			'prompt'       => (string) ( $params['prompt'] ?? '' ),
			'duration_sec' => (int) ( $params['duration_sec'] ?? 5 ),
			'aspect_ratio' => (string) ( $params['aspect_ratio'] ?? '16:9' ),
		];
		$args = [
			'headers' => [ 'Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		];
		$response = wp_remote_post( $base . '/v1/videos/generations', $args );
		if ( is_wp_error( $response ) ) { return $response; }
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'aivid_sora_submit_failed', __( 'Sora submit failed.', 'ai-video-generator' ), [ 'code' => $code, 'body' => $body ] );
		}
		$provider_job_id = (string) ( $body['id'] ?? '' );
		if ( ! $provider_job_id ) {
			return new WP_Error( 'aivid_sora_bad_response', __( 'Invalid Sora response.', 'ai-video-generator' ) );
		}
		return [ 'provider_job_id' => $provider_job_id ];
	}

	public function fetch_job( string $provider_job_id ) {
		$base = $this->get_api_base();
		$key  = $this->get_api_key();
		if ( ! $base || ! $key ) {
			return new WP_Error( 'aivid_sora_not_configured', __( 'Sora provider not configured. Set API base and key.', 'ai-video-generator' ) );
		}
		$args = [ 'headers' => [ 'Authorization' => 'Bearer ' . $key ], 'timeout' => 30 ];
		$response = wp_remote_get( $base . '/v1/videos/generations/' . rawurlencode( $provider_job_id ), $args );
		if ( is_wp_error( $response ) ) { return $response; }
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'aivid_sora_fetch_failed', __( 'Sora fetch failed.', 'ai-video-generator' ), [ 'code' => $code, 'body' => $body ] );
		}

		$status_map = [
			'queued'    => 'queued',
			'processing'=> 'running',
			'succeeded' => 'succeeded',
			'failed'    => 'failed',
			'canceled'  => 'canceled',
		];
		$raw_status = (string) ( $body['status'] ?? 'queued' );
		$status     = isset( $status_map[ $raw_status ] ) ? $status_map[ $raw_status ] : 'running';
		$progress   = (int) ( $body['progress'] ?? 0 );
		$result_url = (string) ( $body['result_url'] ?? '' );

		$out = [ 'status' => $status, 'progress' => $progress ];
		if ( $result_url ) { $out['result_url'] = $result_url; }
		return $out;
	}

	public function cancel_job( string $provider_job_id ) {
		$base = $this->get_api_base();
		$key  = $this->get_api_key();
		if ( ! $base || ! $key ) {
			return new WP_Error( 'aivid_sora_not_configured', __( 'Sora provider not configured. Set API base and key.', 'ai-video-generator' ) );
		}
		$args = [ 'headers' => [ 'Authorization' => 'Bearer ' . $key ], 'timeout' => 30, 'method' => 'POST' ];
		$response = wp_remote_request( $base . '/v1/videos/generations/' . rawurlencode( $provider_job_id ) . '/cancel', $args );
		if ( is_wp_error( $response ) ) { return $response; }
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'aivid_sora_cancel_failed', __( 'Sora cancel failed.', 'ai-video-generator' ), [ 'code' => $code ] );
		}
		return true;
	}
}