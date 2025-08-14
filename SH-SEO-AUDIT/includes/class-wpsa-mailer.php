<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSA_Mailer {
	/**
	 * Build an HTML summary of audit results.
	 *
	 * @param string $url
	 * @param array  $results
	 * @param array  $errors
	 * @return string
	 */
	public static function build_results_html( $url, array $results, array $errors = array() ) {
		$styles_table = 'width:100%;border-collapse:collapse;margin:12px 0;';
		$styles_th = 'text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;background:#f8fafc;';
		$styles_td = 'padding:8px;border-bottom:1px solid #f1f5f9;';

		$html = '';
		$html .= '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;line-height:1.5;color:#0f172a">';
		$html .= '<h2 style="margin:0 0 8px">SEO Audit Results</h2>';
		$html .= '<p style="margin:0 0 12px">URL: <a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a></p>';

		foreach ( array( 'mobile', 'desktop' ) as $strategy ) {
			if ( empty( $results[ $strategy ] ) ) {
				continue;
			}
			$r = $results[ $strategy ];
			$html .= '<h3 style="margin:16px 0 8px">' . ucfirst( $strategy ) . '</h3>';
			$html .= '<table style="' . $styles_table . '">';
			$html .= '<thead><tr>';
			$html .= '<th style="' . $styles_th . '">Category</th>';
			$html .= '<th style="' . $styles_th . '">Score</th>';
			$html .= '<th style="' . $styles_th . '">Metric</th>';
			$html .= '<th style="' . $styles_th . '">Value</th>';
			$html .= '</tr></thead><tbody>';
			$html .= '<tr><td style="' . $styles_td . '">Performance</td><td style="' . $styles_td . '">' . ( isset( $r['scores']['performance'] ) ? (int) $r['scores']['performance'] : '—' ) . '</td><td style="' . $styles_td . '">LCP</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['lcp_ms'] ?? null ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '">SEO</td><td style="' . $styles_td . '">' . ( isset( $r['scores']['seo'] ) ? (int) $r['scores']['seo'] : '—' ) . '</td><td style="' . $styles_td . '">FCP</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['fcp_ms'] ?? null ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '">Accessibility</td><td style="' . $styles_td . '">' . ( isset( $r['scores']['accessibility'] ) ? (int) $r['scores']['accessibility'] : '—' ) . '</td><td style="' . $styles_td . '">INP</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['inp_ms'] ?? null ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '">Best Practices</td><td style="' . $styles_td . '">' . ( isset( $r['scores']['best_practices'] ) ? (int) $r['scores']['best_practices'] : '—' ) . '</td><td style="' . $styles_td . '">CLS</td><td style="' . $styles_td . '">' . ( isset( $r['web_vitals']['cls'] ) && is_numeric( $r['web_vitals']['cls'] ) ? number_format( (float) $r['web_vitals']['cls'], 3 ) : '—' ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '"></td><td style="' . $styles_td . '"></td><td style="' . $styles_td . '">TBT</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['tbt_ms'] ?? null ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '"></td><td style="' . $styles_td . '"></td><td style="' . $styles_td . '">Speed Index</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['si_ms'] ?? null ) . '</td></tr>';
			$html .= '<tr><td style="' . $styles_td . '"></td><td style="' . $styles_td . '"></td><td style="' . $styles_td . '">TTI</td><td style="' . $styles_td . '">' . self::format_ms( $r['web_vitals']['tti_ms'] ?? null ) . '</td></tr>';
			$html .= '</tbody></table>';

			if ( ! empty( $r['opportunities'] ) && is_array( $r['opportunities'] ) ) {
				$html .= '<p style="margin:12px 0 4px;font-weight:600">Top Opportunities</p><ul style="margin:0;padding-left:18px">';
				foreach ( array_slice( $r['opportunities'], 0, 6 ) as $opp ) {
					$title = isset( $opp['title'] ) ? $opp['title'] : ( $opp['id'] ?? '' );
					$ms = isset( $opp['estimated_ms'] ) ? ' (' . self::format_ms( $opp['estimated_ms'] ) . ')' : '';
					$html .= '<li>' . esc_html( $title ) . $ms . '</li>';
				}
				$html .= '</ul>';
			}
		}

		if ( ! empty( $errors ) ) {
			$html .= '<p style="color:#b91c1c">Errors: ' . esc_html( wp_json_encode( $errors ) ) . '</p>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Send owner/internal email with lead details and audit report.
	 *
	 * @param WPSA_Plugin $plugin
	 * @param array       $lead [company,email,phone,url]
	 * @param array       $results
	 * @param array       $errors
	 * @return bool
	 */
	public static function send_owner_email( $plugin, array $lead, array $results, array $errors ) {
		$settings = $plugin->get_settings();
		$to = ! empty( $settings['recipient_email'] ) ? $settings['recipient_email'] : get_option( 'admin_email' );
		$from = ! empty( $settings['from_email'] ) ? $settings['from_email'] : get_option( 'admin_email' );

		$subject = 'New SEO Audit - ' . ( $lead['company'] ?? '' ) . ' (' . ( $lead['url'] ?? '' ) . ')';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $from ) ) {
			$headers[] = 'From: ' . $from;
		}
		if ( ! empty( $lead['email'] ) && is_email( $lead['email'] ) ) {
			$headers[] = 'Reply-To: ' . $lead['email'];
		}

		$body  = '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">';
		$body .= '<p><strong>Company:</strong> ' . esc_html( $lead['company'] ?? '' ) . '</p>';
		$body .= '<p><strong>Website:</strong> <a href="' . esc_url( $lead['url'] ?? '' ) . '">' . esc_html( $lead['url'] ?? '' ) . '</a></p>';
		$body .= '<p><strong>Contact:</strong> ' . esc_html( $lead['phone'] ?? '' ) . '</p>';
		$body .= '<p><strong>Email:</strong> ' . esc_html( $lead['email'] ?? '' ) . '</p>';
		$body .= self::build_results_html( $lead['url'] ?? '', $results, $errors );
		$body .= '</div>';

		return (bool) wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Send customer autoresponder email with their audit report.
	 *
	 * @param WPSA_Plugin $plugin
	 * @param array       $lead [company,email,phone,url]
	 * @param array       $results
	 * @param array       $errors
	 * @return bool
	 */
	public static function send_customer_email( $plugin, array $lead, array $results, array $errors ) {
		if ( empty( $lead['email'] ) || ! is_email( $lead['email'] ) ) {
			return false;
		}
		$settings = $plugin->get_settings();
		$to = $lead['email'];
		$from = ! empty( $settings['from_email'] ) ? $settings['from_email'] : get_option( 'admin_email' );

		$subject = 'Your SEO Audit Report for ' . ( $lead['url'] ?? '' );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $from ) ) {
			$headers[] = 'From: ' . $from;
		}

		$body  = '<div style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">';
		$body .= '<p>Hi ' . esc_html( $lead['company'] ?? '' ) . ',</p>';
		$body .= '<p>Thanks for requesting a free SEO audit. Here is a snapshot of your results for <a href="' . esc_url( $lead['url'] ?? '' ) . '">' . esc_html( $lead['url'] ?? '' ) . '</a>:</p>';
		$body .= self::build_results_html( $lead['url'] ?? '', $results, $errors );
		$body .= '<p>If you would like a deeper review and recommendations, just reply to this email.</p>';
		$body .= '<p>— Digital Cruz</p>';
		$body .= '</div>';

		return (bool) wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Format milliseconds for email.
	 *
	 * @param int|null $ms
	 * @return string
	 */
	protected static function format_ms( $ms ) {
		if ( ! is_numeric( $ms ) ) {
			return '—';
		}
		return number_format_i18n( (int) $ms ) . ' ms';
	}
}