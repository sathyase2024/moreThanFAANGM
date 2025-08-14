<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSA_Admin {
	/**
	 * @var WPSA_Plugin
	 */
	protected $plugin;

	/**
	 * @param WPSA_Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page under Settings menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			__( 'SEO Audit', 'wp-seo-audit' ),
			__( 'SEO Audit', 'wp-seo-audit' ),
			'manage_options',
			'wpsa-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wpsa_settings_group', 'wpsa_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'wpsa_main_section',
			__( 'Google PageSpeed Insights', 'wp-seo-audit' ),
			function() {
				echo '<p>' . esc_html__( 'Enter your Google PageSpeed Insights API key. Leaving it blank will use the public quota, which is limited.', 'wp-seo-audit' ) . '</p>';
			},
			'wpsa-settings'
		);

		add_settings_field(
			'wpsa_api_key',
			__( 'API Key', 'wp-seo-audit' ),
			array( $this, 'render_api_key_field' ),
			'wpsa-settings',
			'wpsa_main_section'
		);

		add_settings_field(
			'wpsa_cache_ttl',
			__( 'Cache TTL (seconds)', 'wp-seo-audit' ),
			array( $this, 'render_cache_ttl_field' ),
			'wpsa-settings',
			'wpsa_main_section'
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		$sanitized['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$sanitized['cache_ttl'] = isset( $input['cache_ttl'] ) ? absint( $input['cache_ttl'] ) : 1800;
		if ( $sanitized['cache_ttl'] < 60 ) {
			$sanitized['cache_ttl'] = 60;
		}
		return $sanitized;
	}

	/**
	 * Render API key input.
	 *
	 * @return void
	 */
	public function render_api_key_field() {
		$options = $this->plugin->get_settings();
		echo '<input type="text" class="regular-text" name="wpsa_settings[api_key]" value="' . esc_attr( $options['api_key'] ) . '" placeholder="AIza..." />';
	}

	/**
	 * Render cache TTL input.
	 *
	 * @return void
	 */
	public function render_cache_ttl_field() {
		$options = $this->plugin->get_settings();
		echo '<input type="number" class="small-text" min="60" step="60" name="wpsa_settings[cache_ttl]" value="' . esc_attr( (string) $options['cache_ttl'] ) . '" />';
	}

	/**
	 * Render the settings page contents.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpsa_settings_group' );
				do_settings_sections( 'wpsa-settings' );
				submit_button();
				?>
			</form>
			<hr />
			<p><?php echo wp_kses_post( __( 'Use the shortcode <code>[seo_audit]</code> on any page to display the public audit form.', 'wp-seo-audit' ) ); ?></p>
		</div>
		<?php
	}
}