<?php
namespace AIVID;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Job_Manager {
	public static function create_job( array $args ) : int {
		$title = isset( $args['title'] ) ? (string) $args['title'] : __( 'AI Video Job', 'ai-video-generator' );
		$post_id = wp_insert_post( [
			'post_type'   => 'aivid_job',
			'post_title'  => $title,
			'post_status' => 'publish',
		] );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		$meta = [
			'provider'        => $args['provider'] ?? 'mock',
			'provider_job_id' => $args['provider_job_id'] ?? '',
			'params'          => $args['params'] ?? [],
			'client_ip'       => $args['client_ip'] ?? '',
			'status'          => 'queued',
			'progress'        => 0,
		];
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, 'aivid_' . $key, $value );
		}
		return (int) $post_id;
	}

	public static function update_job( int $post_id, array $fields ) : void {
		foreach ( $fields as $key => $value ) {
			update_post_meta( $post_id, 'aivid_' . $key, $value );
		}
	}

	public static function get_job_meta( int $post_id ) : array {
		$keys = [ 'provider', 'provider_job_id', 'params', 'client_ip', 'status', 'progress', 'media_id', 'result_url' ];
		$data = [];
		foreach ( $keys as $key ) {
			$data[ $key ] = get_post_meta( $post_id, 'aivid_' . $key, true );
		}
		return $data;
	}

	public static function attach_remote_video_to_media_library( int $job_post_id, string $remote_url ) : int {
		if ( ! $remote_url ) {
			return 0;
		}

		// Required includes for media functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download to temp file.
		$tmp = download_url( $remote_url );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$filename = wp_basename( parse_url( $remote_url, PHP_URL_PATH ) );
		$file_array = [
			'name'     => $filename ?: 'aivid-video.mp4',
			'tmp_name' => $tmp,
		];

		// Handle upload to Media Library.
		$overrides = [ 'test_form' => false ];
		$results   = wp_handle_sideload( $file_array, $overrides );
		if ( isset( $results['error'] ) ) {
			@unlink( $tmp );
			return 0;
		}

		$url  = $results['url'];
		$type = $results['type'] ?? 'video/mp4';
		$file = $results['file'];

		$attachment = [
			'post_mime_type' => $type,
			'post_title'     => get_the_title( $job_post_id ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attach_id = wp_insert_attachment( $attachment, $file );
		if ( ! is_wp_error( $attach_id ) && $attach_id ) {
			wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file ) );
			update_post_meta( $job_post_id, 'aivid_media_id', (int) $attach_id );
			return (int) $attach_id;
		}

		return 0;
	}
}