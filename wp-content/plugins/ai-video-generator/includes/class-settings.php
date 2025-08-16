<?php
namespace AIVID;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	public const OPTION_KEY = 'aivid_settings';

	public function init() : void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_menu() : void {
		add_options_page(
			__( 'AI Video', 'ai-video-generator' ),
			__( 'AI Video', 'ai-video-generator' ),
			'manage_options',
			'ai-video-generator',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings() : void {
		register_setting( 'aivid_settings_group', self::OPTION_KEY );

		add_settings_section( 'aivid_provider_section', __( 'Provider', 'ai-video-generator' ), function () {
			echo '<p>' . esc_html__( 'Choose a provider and set credentials. Mock provider is for demo only.', 'ai-video-generator' ) . '</p>';
		}, 'ai-video-generator' );

		add_settings_field( 'aivid_provider', __( 'Provider', 'ai-video-generator' ), function () {
			$options  = get_option( self::OPTION_KEY, [] );
			$current  = isset( $options['provider'] ) ? (string) $options['provider'] : 'mock';
			$choices  = [ 'mock' => __( 'Mock (demo)', 'ai-video-generator' ), 'sora' => __( 'Sora (stub)', 'ai-video-generator' ) ];
			echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[provider]">';
			foreach ( $choices as $val => $label ) {
				echo '<option value="' . esc_attr( $val ) . '"' . selected( $current, $val, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		}, 'ai-video-generator', 'aivid_provider_section' );

		add_settings_field( 'aivid_sora_api_base', __( 'Sora API Base URL', 'ai-video-generator' ), function () {
			$options = get_option( self::OPTION_KEY, [] );
			$val     = isset( $options['sora_api_base'] ) ? (string) $options['sora_api_base'] : '';
			echo '<input type="url" size="40" placeholder="https://api.sora.example" name="' . esc_attr( self::OPTION_KEY ) . '[sora_api_base]" value="' . esc_attr( $val ) . '" />';
			echo '<p class="description">' . esc_html__( 'Hypothetical Sora API base. Replace when official API is available.', 'ai-video-generator' ) . '</p>';
		}, 'ai-video-generator', 'aivid_provider_section' );

		add_settings_field( 'aivid_sora_api_key', __( 'Sora API Key', 'ai-video-generator' ), function () {
			$options = get_option( self::OPTION_KEY, [] );
			$val     = isset( $options['sora_api_key'] ) ? (string) $options['sora_api_key'] : '';
			echo '<input type="password" size="40" name="' . esc_attr( self::OPTION_KEY ) . '[sora_api_key]" value="' . esc_attr( $val ) . '" autocomplete="off" />';
		}, 'ai-video-generator', 'aivid_provider_section' );

		add_settings_section( 'aivid_quota_section', __( 'Quota', 'ai-video-generator' ), function () {
			echo '<p>' . esc_html__( 'Basic per-IP daily request limit for public endpoints.', 'ai-video-generator' ) . '</p>';
		}, 'ai-video-generator' );

		add_settings_field( 'aivid_quota', __( 'Daily limit per IP', 'ai-video-generator' ), function () {
			$options = get_option( self::OPTION_KEY, [] );
			$limit   = isset( $options['daily_quota'] ) ? (int) $options['daily_quota'] : 5;
			echo '<input type="number" min="1" name="' . esc_attr( self::OPTION_KEY ) . '[daily_quota]" value="' . esc_attr( (string) $limit ) . '" />';
		}, 'ai-video-generator', 'aivid_quota_section' );
	}

	public function render_page() : void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'aivid_settings_group' ); ?>
				<?php do_settings_sections( 'ai-video-generator' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}