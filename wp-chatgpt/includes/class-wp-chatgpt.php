<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_ChatGPT_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function get_default_settings() {
		return array(
			'provider' => 'openai',
			'api_base' => '',
			'api_key' => '',
			'model' => 'gpt-4o-mini',
			'system_prompt' => 'You are a helpful assistant for this website.',
			'widget_title' => 'Ask AI',
			'position' => 'bottom-right',
			'accent_color' => '#4f46e5',
			'enable_floating_widget' => 1,
			'enable_chat_history' => 1,
			'max_tokens' => 500,
			'temperature' => 0.7,
		);
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'wp_chatgpt', array( $this, 'render_shortcode' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function register_admin_menu() {
		add_options_page(
			__( 'WP ChatGPT', 'wp-chatgpt' ),
			__( 'WP ChatGPT', 'wp-chatgpt' ),
			'manage_options',
			'wp-chatgpt',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'wp_chatgpt_settings_group', 'wp_chatgpt_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'wp_chatgpt_main',
			__( 'Chatbot Settings', 'wp-chatgpt' ),
			'__return_false',
			'wp-chatgpt'
		);

		$this->add_text_field( 'api_key', __( 'API Key', 'wp-chatgpt' ), true );
		$this->add_text_field( 'api_base', __( 'API Base URL (optional)', 'wp-chatgpt' ), false );
		$this->add_text_field( 'model', __( 'Model', 'wp-chatgpt' ), false );
		$this->add_textarea_field( 'system_prompt', __( 'System Prompt', 'wp-chatgpt' ) );
		$this->add_text_field( 'widget_title', __( 'Widget Title', 'wp-chatgpt' ), false );
		$this->add_select_field( 'position', __( 'Widget Position', 'wp-chatgpt' ), array(
			'bottom-right' => __( 'Bottom Right', 'wp-chatgpt' ),
			'bottom-left' => __( 'Bottom Left', 'wp-chatgpt' ),
		) );
		$this->add_text_field( 'accent_color', __( 'Accent Color', 'wp-chatgpt' ), false );
		$this->add_checkbox_field( 'enable_floating_widget', __( 'Enable Floating Widget', 'wp-chatgpt' ) );
		$this->add_checkbox_field( 'enable_chat_history', __( 'Enable Chat History (localStorage)', 'wp-chatgpt' ) );
		$this->add_number_field( 'max_tokens', __( 'Max Tokens', 'wp-chatgpt' ), 100, 4096 );
		$this->add_number_field( 'temperature', __( 'Temperature', 'wp-chatgpt' ), 0, 2, '0.1' );
	}

	private function get_option( $key ) {
		$settings = get_option( 'wp_chatgpt_settings', array() );
		$defaults = self::get_default_settings();
		return isset( $settings[ $key ] ) && $settings[ $key ] !== '' ? $settings[ $key ] : ( $defaults[ $key ] ?? '' );
	}

	public function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();
		$output = array();
		$output['provider'] = 'openai';
		$output['api_base'] = sanitize_text_field( $input['api_base'] ?? '' );
		$output['api_key'] = sanitize_text_field( $input['api_key'] ?? '' );
		$output['model'] = sanitize_text_field( $input['model'] ?? $defaults['model'] );
		$output['system_prompt'] = wp_kses_post( $input['system_prompt'] ?? $defaults['system_prompt'] );
		$output['widget_title'] = sanitize_text_field( $input['widget_title'] ?? $defaults['widget_title'] );
		$output['position'] = in_array( $input['position'] ?? '', array( 'bottom-right', 'bottom-left' ), true ) ? $input['position'] : 'bottom-right';
		$output['accent_color'] = sanitize_hex_color( $input['accent_color'] ?? $defaults['accent_color'] ) ?: $defaults['accent_color'];
		$output['enable_floating_widget'] = ! empty( $input['enable_floating_widget'] ) ? 1 : 0;
		$output['enable_chat_history'] = ! empty( $input['enable_chat_history'] ) ? 1 : 0;
		$output['max_tokens'] = max( 1, min( 4096, intval( $input['max_tokens'] ?? $defaults['max_tokens'] ) ) );
		$output['temperature'] = max( 0, min( 2, floatval( $input['temperature'] ?? $defaults['temperature'] ) ) );
		return $output;
	}

	private function add_text_field( $key, $label, $mask = false ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $mask ) {
				$value = esc_attr( $this->get_option( $key ) );
				$type = $mask ? 'password' : 'text';
				echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="wp_chatgpt_settings[' . esc_attr( $key ) . ']" value="' . $value . '" class="regular-text" />';
			},
			'wp-chatgpt',
			'wp_chatgpt_main'
		);
	}

	private function add_textarea_field( $key, $label ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key ) {
				$value = esc_textarea( $this->get_option( $key ) );
				echo '<textarea id="' . esc_attr( $key ) . '" name="wp_chatgpt_settings[' . esc_attr( $key ) . ']" class="large-text" rows="4">' . $value . '</textarea>';
			},
			'wp-chatgpt',
			'wp_chatgpt_main'
		);
	}

	private function add_select_field( $key, $label, $choices ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $choices ) {
				$current = $this->get_option( $key );
				echo '<select id="' . esc_attr( $key ) . '" name="wp_chatgpt_settings[' . esc_attr( $key ) . ']">';
				foreach ( $choices as $value => $text ) {
					$selected = selected( $current, $value, false );
					echo '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $text ) . '</option>';
				}
				echo '</select>';
			},
			'wp-chatgpt',
			'wp_chatgpt_main'
		);
	}

	private function add_checkbox_field( $key, $label ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key ) {
				$checked = checked( 1, intval( $this->get_option( $key ) ), false );
				echo '<label><input type="checkbox" id="' . esc_attr( $key ) . '" name="wp_chatgpt_settings[' . esc_attr( $key ) . ']" value="1" ' . $checked . ' /> ' . esc_html__( 'Enabled', 'wp-chatgpt' ) . '</label>';
			},
			'wp-chatgpt',
			'wp_chatgpt_main'
		);
	}

	private function add_number_field( $key, $label, $min, $max, $step = '1' ) {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $min, $max, $step ) {
				$value = esc_attr( $this->get_option( $key ) );
				echo '<input type="number" id="' . esc_attr( $key ) . '" name="wp_chatgpt_settings[' . esc_attr( $key ) . ']" value="' . $value . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" class="small-text" />';
			},
			'wp-chatgpt',
			'wp_chatgpt_main'
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wp_chatgpt_settings_group' );
				do_settings_sections( 'wp-chatgpt' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function enqueue_assets() {
		$settings = get_option( 'wp_chatgpt_settings', array() );
		if ( empty( $settings['enable_floating_widget'] ) ) {
			return;
		}

		wp_enqueue_style( 'wp-chatgpt', WP_CHATGPT_PLUGIN_URL . 'assets/css/chatbot.css', array(), WP_CHATGPT_VERSION );
		wp_enqueue_script( 'wp-chatgpt', WP_CHATGPT_PLUGIN_URL . 'assets/js/chatbot.js', array( 'wp-api-fetch' ), WP_CHATGPT_VERSION, true );

		$config = array(
			'restUrl' => esc_url_raw( rest_url( 'wp-chatgpt/v1/chat' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'widgetTitle' => $this->get_option( 'widget_title' ),
			'position' => $this->get_option( 'position' ),
			'accentColor' => $this->get_option( 'accent_color' ),
			'enableChatHistory' => (bool) intval( $this->get_option( 'enable_chat_history' ) ),
		);
		wp_localize_script( 'wp-chatgpt', 'WPChatGPTConfig', $config );
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'height' => '480px',
		), $atts, 'wp_chatgpt' );

		wp_enqueue_style( 'wp-chatgpt', WP_CHATGPT_PLUGIN_URL . 'assets/css/chatbot.css', array(), WP_CHATGPT_VERSION );
		wp_enqueue_script( 'wp-chatgpt', WP_CHATGPT_PLUGIN_URL . 'assets/js/chatbot.js', array( 'wp-api-fetch' ), WP_CHATGPT_VERSION, true );

		ob_start();
		?>
		<div class="wp-chatgpt-embed" style="height: <?php echo esc_attr( $atts['height'] ); ?>"></div>
		<?php
		return ob_get_clean();
	}

	public function register_rest_routes() {
		register_rest_route( 'wp-chatgpt/v1', '/chat', array(
			'methods' => 'POST',
			'callback' => array( $this, 'handle_chat' ),
			'permission_callback' => function () {
				$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : '' );
				return wp_verify_nonce( $nonce, 'wp_rest' );
			},
		) );
	}

	public function handle_chat( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$userMessage = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';
		$history = isset( $params['history'] ) && is_array( $params['history'] ) ? $params['history'] : array();

		if ( empty( $userMessage ) ) {
			return new WP_Error( 'bad_request', __( 'Message is required.', 'wp-chatgpt' ), array( 'status' => 400 ) );
		}

		$apiKey = $this->get_option( 'api_key' );
		if ( empty( $apiKey ) ) {
			return new WP_Error( 'config_error', __( 'API key is not configured.', 'wp-chatgpt' ), array( 'status' => 500 ) );
		}

		$response = $this->call_openai_chat_completions( $userMessage, $history );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( $response );
	}

	private function call_openai_chat_completions( $userMessage, $history ) {
		$apiKey = $this->get_option( 'api_key' );
		$apiBase = trim( $this->get_option( 'api_base' ) );
		$model = $this->get_option( 'model' );
		$systemPrompt = $this->get_option( 'system_prompt' );
		$maxTokens = intval( $this->get_option( 'max_tokens' ) );
		$temperature = floatval( $this->get_option( 'temperature' ) );

		$messages = array();
		if ( ! empty( $systemPrompt ) ) {
			$messages[] = array( 'role' => 'system', 'content' => $systemPrompt );
		}
		foreach ( $history as $turn ) {
			if ( isset( $turn['role'], $turn['content'] ) ) {
				$messages[] = array(
					'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
					'content' => wp_strip_all_tags( $turn['content'] ),
				);
			}
		}
		$messages[] = array( 'role' => 'user', 'content' => wp_strip_all_tags( $userMessage ) );

		$endpoint = rtrim( $apiBase ?: 'https://api.openai.com', '/' ) . '/v1/chat/completions';

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $apiKey,
			),
			'timeout' => 30,
			'body' => wp_json_encode( array(
				'model' => $model,
				'messages' => $messages,
				'max_tokens' => $maxTokens,
				'temperature' => $temperature,
			) ),
		);

		$wp_response = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $wp_response ) ) {
			return new WP_Error( 'api_error', $wp_response->get_error_message(), array( 'status' => 500 ) );
		}

		$code = wp_remote_retrieve_response_code( $wp_response );
		$body = json_decode( wp_remote_retrieve_body( $wp_response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown API error', 'wp-chatgpt' );
			return new WP_Error( 'api_http_error', $message, array( 'status' => $code ) );
		}

		$text = $body['choices'][0]['message']['content'] ?? '';
		return array(
			'message' => $text,
		);
	}
}