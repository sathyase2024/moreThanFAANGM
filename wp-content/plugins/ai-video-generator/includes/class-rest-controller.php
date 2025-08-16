<?php
namespace AIVID;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest_Controller {
	public function init() : void {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'ai-video/v1', '/jobs', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_job' ],
				'permission_callback' => '__return_true',
			] );

			register_rest_route( 'ai-video/v1', '/jobs/(?P<id>\d+)', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_job' ],
				'permission_callback' => '__return_true',
			] );

			register_rest_route( 'ai-video/v1', '/jobs/(?P<id>\\d+)/cancel', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'cancel_job' ],
				'permission_callback' => '__return_true',
			] );
		} );
	}

	public function create_job( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
		$client_ip = $this->get_client_ip();
		if ( ! $this->check_quota( $client_ip ) ) {
			return new WP_Error( 'aivid_quota', __( 'Daily request limit reached. Please try again tomorrow.', 'ai-video-generator' ), [ 'status' => 429 ] );
		}

		$prompt       = trim( (string) $request->get_param( 'prompt' ) );
		$duration_sec = (int) $request->get_param( 'duration' );
		$aspect_ratio = (string) $request->get_param( 'aspect_ratio' );

		if ( $prompt === '' ) {
			return new WP_Error( 'aivid_prompt_required', __( 'Prompt is required.', 'ai-video-generator' ), [ 'status' => 400 ] );
		}
		if ( $duration_sec <= 0 || $duration_sec > 60 ) {
			$duration_sec = 5;
		}
		if ( ! in_array( $aspect_ratio, [ '16:9', '9:16', '1:1' ], true ) ) {
			$aspect_ratio = '16:9';
		}

		$params = [
			'prompt'       => $prompt,
			'duration_sec' => $duration_sec,
			'aspect_ratio' => $aspect_ratio,
			'client_ip'    => $client_ip,
		];

		$provider = Plugin::instance()->get_provider();
		$submit   = $provider->submit_job( $params );
		if ( is_wp_error( $submit ) ) {
			return $submit;
		}

		$job_id = Job_Manager::create_job( [
			'title'            => wp_trim_words( $prompt, 6, '…' ),
			'provider'         => $provider->get_name(),
			'provider_job_id'  => $submit['provider_job_id'] ?? '',
			'params'           => $params,
			'client_ip'        => $client_ip,
		] );

		$this->increment_quota( $client_ip );

		return new WP_REST_Response( [ 'job_id' => $job_id ], 201 );
	}

	public function get_job( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
		$job_id = (int) $request['id'];
		$post   = get_post( $job_id );
		if ( ! $post || $post->post_type !== 'aivid_job' ) {
			return new WP_Error( 'aivid_not_found', __( 'Job not found.', 'ai-video-generator' ), [ 'status' => 404 ] );
		}

		$meta              = Job_Manager::get_job_meta( $job_id );
		$provider_name     = $meta['provider'] ?? 'mock';
		$provider_job_id   = $meta['provider_job_id'] ?? '';
		$existing_status   = $meta['status'] ?? 'queued';
		$existing_media_id = isset( $meta['media_id'] ) ? (int) $meta['media_id'] : 0;

		$provider = Plugin::instance()->get_provider();
		if ( $provider->get_name() !== $provider_name ) {
			// In case settings changed after job creation, attempt to instantiate by stored name in future.
		}

		$remote = $provider->fetch_job( $provider_job_id );
		if ( is_wp_error( $remote ) ) {
			return $remote;
		}

		$status    = $remote['status'] ?? $existing_status;
		$progress  = isset( $remote['progress'] ) ? (int) $remote['progress'] : 0;
		$resultUrl = isset( $remote['result_url'] ) ? (string) $remote['result_url'] : '';

		if ( $status === 'succeeded' && $resultUrl && ! $existing_media_id ) {
			$attachment_id = Job_Manager::attach_remote_video_to_media_library( $job_id, $resultUrl );
			if ( $attachment_id ) {
				Job_Manager::update_job( $job_id, [ 'status' => 'succeeded', 'media_id' => $attachment_id, 'result_url' => $resultUrl ] );
			}
		}
		else {
			Job_Manager::update_job( $job_id, [ 'status' => $status, 'progress' => $progress, 'result_url' => $resultUrl ] );
		}

		$response = [
			'id'         => $job_id,
			'status'     => $status,
			'progress'   => $progress,
			'provider'   => $provider_name,
			'created_at' => get_post_time( 'c', true, $post ),
		];
		if ( $existing_media_id ) {
			$response['media_id']  = $existing_media_id;
			$response['media_url'] = wp_get_attachment_url( $existing_media_id );
		}
		if ( $resultUrl ) {
			$response['result_url'] = $resultUrl;
		}

		return new WP_REST_Response( $response, 200 );
	}

	public function cancel_job( WP_REST_Request $request ) : WP_REST_Response|WP_Error {
		$job_id = (int) $request['id'];
		$post   = get_post( $job_id );
		if ( ! $post || $post->post_type !== 'aivid_job' ) {
			return new WP_Error( 'aivid_not_found', __( 'Job not found.', 'ai-video-generator' ), [ 'status' => 404 ] );
		}
		$meta = Job_Manager::get_job_meta( $job_id );
		$provider_job_id = $meta['provider_job_id'] ?? '';

		$provider = Plugin::instance()->get_provider();
		$cancel   = $provider->cancel_job( $provider_job_id );
		if ( is_wp_error( $cancel ) ) {
			return $cancel;
		}
		Job_Manager::update_job( $job_id, [ 'status' => 'canceled' ] );
		return new WP_REST_Response( [ 'id' => $job_id, 'status' => 'canceled' ], 200 );
	}

	private function get_client_ip() : string {
		// Best-effort; do not trust for strong security decisions without a proxy-aware solution.
		$keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$raw = (string) $_SERVER[ $key ];
				$parts = explode( ',', $raw );
				return trim( $parts[0] );
			}
		}
		return '0.0.0.0';
	}

	private function check_quota( string $ip ) : bool {
		$limit   = (int) apply_filters( 'aivid_daily_quota', 5 );
		$key     = 'aivid_quota_' . md5( $ip . gmdate( 'Y-m-d' ) );
		$current = (int) get_transient( $key );
		return $current < $limit;
	}

	private function increment_quota( string $ip ) : void {
		$limit = (int) apply_filters( 'aivid_daily_quota', 5 );
		$key   = 'aivid_quota_' . md5( $ip . gmdate( 'Y-m-d' ) );
		$val   = (int) get_transient( $key );
		$val++;
		// Expire at midnight UTC.
		$seconds_until_midnight = strtotime( 'tomorrow 00:00:00 UTC' ) - time();
		if ( $seconds_until_midnight < 60 ) {
			$seconds_until_midnight = HOUR_IN_SECONDS;
		}
		set_transient( $key, min( $val, $limit ), $seconds_until_midnight );
	}
}