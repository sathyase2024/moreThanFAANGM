<?php
namespace AIVID;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Provider_Interface {
	/** Provider machine name, e.g., 'mock' */
	public function get_name() : string;

	/** Human label, e.g., 'Mock Provider' */
	public function get_label() : string;

	/**
	 * Submit a generation job.
	 *
	 * @param array $params { prompt:string, duration_sec:int, aspect_ratio:string, ... }
	 * @return array|WP_Error { provider_job_id:string }
	 */
	public function submit_job( array $params );

	/**
	 * Fetch job status.
	 *
	 * @param string $provider_job_id
	 * @return array|WP_Error { status:'queued|running|succeeded|failed|canceled', progress:int(0-100), result_url?:string }
	 */
	public function fetch_job( string $provider_job_id );

	/**
	 * Cancel a job if supported.
	 *
	 * @param string $provider_job_id
	 * @return true|WP_Error
	 */
	public function cancel_job( string $provider_job_id );
}