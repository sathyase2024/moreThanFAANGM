<?php
namespace AIVID\Providers;

use AIVID\Provider_Interface;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mock_Provider implements Provider_Interface {
	public function get_name() : string {
		return 'mock';
	}

	public function get_label() : string {
		return 'Mock Provider (Demo)';
	}

	public function submit_job( array $params ) {
		$provider_job_id = 'mock_' . wp_generate_uuid4();
		$duration        = isset( $params['duration_sec'] ) ? (int) $params['duration_sec'] : 5;
		$now             = time();
		$target_secs     = min( 10, max( 3, (int) ceil( $duration / 2 ) ) );

		$state = [
			'created_at' => $now,
			'target_at'  => $now + $target_secs,
			'status'     => 'running',
			'progress'   => 0,
			'params'     => $params,
		];
		set_transient( $this->tkey( $provider_job_id ), $state, HOUR_IN_SECONDS );
		return [ 'provider_job_id' => $provider_job_id ];
	}

	public function fetch_job( string $provider_job_id ) {
		$state = get_transient( $this->tkey( $provider_job_id ) );
		if ( ! is_array( $state ) ) {
			return new WP_Error( 'aivid_mock_not_found', 'Mock job not found' );
		}
		if ( isset( $state['status'] ) && in_array( $state['status'], [ 'succeeded', 'failed', 'canceled' ], true ) ) {
			return $state + [ 'result_url' => $state['result_url'] ?? '' ];
		}

		$now      = time();
		$target   = (int) $state['target_at'];
		$created  = (int) $state['created_at'];
		$elapsed  = max( 0, $now - $created );
		$total    = max( 1, $target - $created );
		$progress = (int) min( 100, floor( ( $elapsed / $total ) * 100 ) );
		$status   = $progress >= 100 ? 'succeeded' : 'running';

		if ( $status === 'succeeded' ) {
			$state['status']     = 'succeeded';
			$state['progress']   = 100;
			$state['result_url'] = 'https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
			set_transient( $this->tkey( $provider_job_id ), $state, HOUR_IN_SECONDS );
			return [ 'status' => 'succeeded', 'progress' => 100, 'result_url' => $state['result_url'] ];
		}

		$state['status']   = 'running';
		$state['progress'] = $progress;
		set_transient( $this->tkey( $provider_job_id ), $state, HOUR_IN_SECONDS );
		return [ 'status' => 'running', 'progress' => $progress ];
	}

	public function cancel_job( string $provider_job_id ) {
		$state = get_transient( $this->tkey( $provider_job_id ) );
		if ( ! is_array( $state ) ) {
			return new WP_Error( 'aivid_mock_not_found', 'Mock job not found' );
		}
		$state['status']   = 'canceled';
		$state['progress'] = 0;
		set_transient( $this->tkey( $provider_job_id ), $state, HOUR_IN_SECONDS );
		return true;
	}

	private function tkey( string $id ) : string {
		return 'aivid_mock_job_' . sanitize_key( $id );
	}
}