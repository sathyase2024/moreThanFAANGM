<?php
namespace AIVID;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AIVID_PLUGIN_DIR . 'includes/class-rest-controller.php';
require_once AIVID_PLUGIN_DIR . 'includes/class-webhook-controller.php';
require_once AIVID_PLUGIN_DIR . 'includes/class-job-manager.php';
require_once AIVID_PLUGIN_DIR . 'includes/class-provider-interface.php';
require_once AIVID_PLUGIN_DIR . 'includes/class-settings.php';
require_once AIVID_PLUGIN_DIR . 'includes/providers/class-mock-provider.php';
require_once AIVID_PLUGIN_DIR . 'includes/providers/class-sora-provider.php';

class Plugin {
	/** @var Plugin */
	private static $instance;

	/** @var Provider_Interface */
	private $provider_instance;

	public static function instance() : Plugin {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Plugin();
		}
		return self::$instance;
	}

	public function init() : void {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

		// Admin settings.
		( new Settings() )->init();

		// Dynamic quota from saved option.
		add_filter( 'aivid_daily_quota', function ( $default ) {
			$options = get_option( Settings::OPTION_KEY, [] );
			if ( isset( $options['daily_quota'] ) && (int) $options['daily_quota'] > 0 ) {
				return (int) $options['daily_quota'];
			}
			return (int) $default;
		} );

		// REST.
		( new Rest_Controller() )->init();
		( new Webhook_Controller() )->init();
	}

	public function register_post_types() : void {
		register_post_type( 'aivid_job', [
			'labels' => [
				'name'          => __( 'AI Video Jobs', 'ai-video-generator' ),
				'singular_name' => __( 'AI Video Job', 'ai-video-generator' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'upload.php',
			'supports'            => [ 'title', 'custom-fields', 'author' ],
			'capability_type'     => 'post',
			'has_archive'         => false,
			'show_in_rest'        => false,
		] );
	}

	public function register_shortcode() : void {
		add_shortcode( 'ai_video_generator', [ $this, 'render_shortcode' ] );
	}

	public function render_shortcode( $atts ) : string {
		$container_id = 'aivid-container-' . wp_generate_uuid4();

		ob_start();
		?>
		<div class="aivid-container" id="<?php echo esc_attr( $container_id ); ?>">
			<form class="aivid-form" data-container-id="<?php echo esc_attr( $container_id ); ?>">
				<label>
					<?php esc_html_e( 'Prompt', 'ai-video-generator' ); ?>
					<textarea name="prompt" required rows="3" placeholder="A serene beach at sunset with waves gently crashing..."></textarea>
				</label>
				<div class="aivid-row">
					<label>
						<?php esc_html_e( 'Duration (seconds)', 'ai-video-generator' ); ?>
						<select name="duration">
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="15">15</option>
						</select>
					</label>
					<label>
						<?php esc_html_e( 'Aspect Ratio', 'ai-video-generator' ); ?>
						<select name="aspect_ratio">
							<option value="16:9">16:9</option>
							<option value="9:16">9:16</option>
							<option value="1:1">1:1</option>
						</select>
					</label>
				</div>
				<button type="submit" class="aivid-submit"><?php esc_html_e( 'Generate Video', 'ai-video-generator' ); ?></button>
			</form>
			<div class="aivid-status" hidden></div>
			<div class="aivid-progress" hidden>
				<div class="aivid-progress-bar" style="width:0%"></div>
			</div>
			<div class="aivid-result" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function enqueue_frontend_assets() : void {
		// Only enqueue when shortcode exists on page. Simple heuristic: always enqueue on front-end.
		wp_register_style( 'aivid-frontend', AIVID_PLUGIN_URL . 'assets/css/frontend.css', [], AIVID_VERSION );
		wp_enqueue_style( 'aivid-frontend' );

		wp_register_script( 'aivid-frontend', AIVID_PLUGIN_URL . 'assets/js/frontend.js', [], AIVID_VERSION, true );
		$settings = [
			'restBase'   => esc_url_raw( rest_url( 'ai-video/v1' ) ),
			'ajaxNonce'  => wp_create_nonce( 'wp_rest' ),
			'quotaLimit' => (int) apply_filters( 'aivid_daily_quota', 5 ),
		];
		wp_localize_script( 'aivid-frontend', 'AIVID', $settings );
		wp_enqueue_script( 'aivid-frontend' );
	}

	public function get_provider() : Provider_Interface {
		if ( $this->provider_instance ) {
			return $this->provider_instance;
		}

		$options         = get_option( Settings::OPTION_KEY, [] );
		$provider_choice = isset( $options['provider'] ) ? (string) $options['provider'] : 'mock';

		switch ( $provider_choice ) {
			case 'sora':
				$this->provider_instance = new Providers\Sora_Provider();
				break;
			case 'mock':
			default:
				$this->provider_instance = new Providers\Mock_Provider();
				break;
		}

		return $this->provider_instance;
	}
}