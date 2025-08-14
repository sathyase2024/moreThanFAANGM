<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSA_PageSpeed {
	/**
	 * Run a PageSpeed audit for a URL and strategy.
	 *
	 * @param string $url
	 * @param string $strategy 'mobile' or 'desktop'
	 * @param string $api_key
	 * @param int    $cache_ttl
	 * @return array|WP_Error
	 */
	public static function run_audit( $url, $strategy = 'mobile', $api_key = '', $cache_ttl = 1800 ) {
		$url = esc_url_raw( $url );
		$strategy = in_array( $strategy, array( 'mobile', 'desktop' ), true ) ? $strategy : 'mobile';

		$transient_key = 'wpsa_' . md5( implode( '|', array( $url, $strategy, (string) $api_key ) ) );
		$cached = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
		$query_args = array(
			'url' => $url,
			'strategy' => $strategy,
			'locale' => get_locale(),
		);
		if ( ! empty( $api_key ) ) {
			$query_args['key'] = $api_key;
		}

		$request_url = add_query_arg( $query_args, $endpoint );
		// Append categories as repeated query params to ensure PSI returns all desired categories
		$requested_categories = array( 'performance', 'seo', 'accessibility', 'best-practices' );
		foreach ( $requested_categories as $cat ) {
			$request_url .= '&category=' . rawurlencode( $cat );
		}

		$response = wp_remote_get( $request_url, array( 'timeout' => 60 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 ) {
			$error_message = __( 'Unexpected response from PageSpeed API.', 'wp-seo-audit' );
			if ( $body ) {
				$data = json_decode( $body, true );
				if ( isset( $data['error']['message'] ) ) {
					$error_message = $data['error']['message'];
				}
			}
			return new WP_Error( 'wpsa_http_error', $error_message );
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'wpsa_bad_json', __( 'Invalid JSON from PageSpeed API.', 'wp-seo-audit' ) );
		}

		$normalized = self::normalize_result( $data, $strategy );
		set_transient( $transient_key, $normalized, max( 60, (int) $cache_ttl ) );
		return $normalized;
	}

	/**
	 * Normalize PSI response into a compact structure.
	 *
	 * @param array  $data
	 * @param string $strategy
	 * @return array
	 */
	protected static function normalize_result( array $data, $strategy ) {
		$lhr = isset( $data['lighthouseResult'] ) ? $data['lighthouseResult'] : array();
		$audits = isset( $lhr['audits'] ) ? $lhr['audits'] : array();
		$categories = isset( $lhr['categories'] ) ? $lhr['categories'] : array();

		$scores = array(
			'performance' => isset( $categories['performance']['score'] ) ? (int) round( (float) $categories['performance']['score'] * 100 ) : null,
			'seo' => isset( $categories['seo']['score'] ) ? (int) round( (float) $categories['seo']['score'] * 100 ) : null,
			'accessibility' => isset( $categories['accessibility']['score'] ) ? (int) round( (float) $categories['accessibility']['score'] * 100 ) : null,
			'best_practices' => isset( $categories['best-practices']['score'] ) ? (int) round( (float) $categories['best-practices']['score'] * 100 ) : null,
		);

		$web_vitals = array(
			'lcp_ms' => isset( $audits['largest-contentful-paint']['numericValue'] ) ? (int) round( (float) $audits['largest-contentful-paint']['numericValue'] ) : null,
			'cls' => isset( $audits['cumulative-layout-shift']['numericValue'] ) ? (float) $audits['cumulative-layout-shift']['numericValue'] : null,
			'inp_ms' => isset( $audits['interaction-to-next-paint']['numericValue'] ) ? (int) round( (float) $audits['interaction-to-next-paint']['numericValue'] ) : null,
			'fcp_ms' => isset( $audits['first-contentful-paint']['numericValue'] ) ? (int) round( (float) $audits['first-contentful-paint']['numericValue'] ) : null,
			'tbt_ms' => isset( $audits['total-blocking-time']['numericValue'] ) ? (int) round( (float) $audits['total-blocking-time']['numericValue'] ) : null,
			'si_ms' => isset( $audits['speed-index']['numericValue'] ) ? (int) round( (float) $audits['speed-index']['numericValue'] ) : null,
			'tti_ms' => isset( $audits['interactive']['numericValue'] ) ? (int) round( (float) $audits['interactive']['numericValue'] ) : null,
		);

		$opportunities = array();
		foreach ( $audits as $audit_id => $audit ) {
			if ( isset( $audit['details'] ) && is_array( $audit['details'] ) && isset( $audit['details']['type'] ) && 'opportunity' === $audit['details']['type'] ) {
				$opportunities[] = array(
					'id' => $audit_id,
					'title' => isset( $audit['title'] ) ? (string) $audit['title'] : $audit_id,
					'estimated_ms' => isset( $audit['details']['overallSavingsMs'] ) ? (int) round( (float) $audit['details']['overallSavingsMs'] ) : null,
					'score' => isset( $audit['score'] ) ? (float) $audit['score'] : null,
				);
			}
		}

		$final_screenshot = null;
		if ( isset( $audits['final-screenshot']['details']['data'] ) ) {
			$final_screenshot = $audits['final-screenshot']['details']['data'];
		}

		return array(
			'strategy' => $strategy,
			'scores' => $scores,
			'web_vitals' => $web_vitals,
			'opportunities' => $opportunities,
			'final_screenshot' => $final_screenshot,
		);
	}
}