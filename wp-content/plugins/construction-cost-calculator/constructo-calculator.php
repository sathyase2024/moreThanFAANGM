<?php
/**
 * Plugin Name: Construction Cost Calculator
 * Description: Construction cost estimation calculator with shortcode [construction_calculator].
 * Version: 1.1.0
 * Author: Your Team
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Construction_Cost_Calculator {
    const VERSION = '1.1.0';
    const SLUG = 'construction-calculator';
    const OPTION = 'construction_calculator_settings';

    public function __construct() {
        add_action('init', [$this, 'register_assets']);
        // Primary shortcode per spec
        add_shortcode('construction_calculator', [$this, 'render_shortcode']);
        // Backward-compatible alias
        add_shortcode('constructo_calculator', [$this, 'render_shortcode']);
        add_action('wp_ajax_construction_calc_submit', [$this, 'handle_form_submit']);
        add_action('wp_ajax_nopriv_construction_calc_submit', [$this, 'handle_form_submit']);
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_settings_page']);
            add_action('admin_init', [$this, 'register_settings']);
        }
    }

    public function register_assets() {
        $asset_url = plugin_dir_url(__FILE__) . 'assets/';
        wp_register_style(
            self::SLUG,
            $asset_url . 'style.css',
            [],
            self::VERSION
        );
        wp_register_script(
            self::SLUG,
            $asset_url . 'app.js',
            [],
            self::VERSION,
            true
        );
    }

    /**
     * Shortcode renderer
     */
    public function render_shortcode($atts = []) {
        $atts = shortcode_atts([
            'title' => __('Architectural Construction Cost Calculator 2025 (Tamilnadu)', 'construction-calculator'),
            'city' => 'Tamilnadu',
            'year' => '2025',
        ], $atts, 'construction_calculator');

        // Default packages are data-driven and filterable
        $default_packages = [
            'standard' => 2099,
            'premium' => 2399,
            'luxury' => 2699,
        ];
        $default_rates = [
            'sump_rate' => 24,
            'septic_rate' => 24,
            'wall_rate' => 425,
        ];

        $default_works = [
            [
                'key' => 'builtup',
                'label' => __('Enter required Built up Area for Ground Floor', 'construction-calculator'),
                'unit' => 'sqft',
                'rate_key' => 'package',
                'inputs' => [ ['placeholder' => 'Area in sqft'] ],
            ],
            [
                'key' => 'sump',
                'label' => __('Size of RCC Water Sump (A 4 member family will require 9000 liter capacity)', 'construction-calculator'),
                'unit' => 'ltr',
                'rate_key' => 'sump_rate',
                'inputs' => [ ['placeholder' => 'No. of Liters'] ],
            ],
            [
                'key' => 'septic',
                'label' => __('Size of Septic Tank', 'construction-calculator'),
                'unit' => 'ltr',
                'rate_key' => 'septic_rate',
                'inputs' => [ ['placeholder' => 'No. of Liters'] ],
            ],
            [
                'key' => 'wall',
                'label' => __('Plain Compound Wall', 'construction-calculator'),
                'unit' => 'sqft',
                'rate_key' => 'wall_rate',
                'math' => 'product',
                'inputs' => [ ['placeholder' => 'Length'], ['placeholder' => 'Height'] ],
            ],
        ];

        // Merge settings from admin page
        $settings = $this->get_settings();
        $packages = !empty($settings['packages']) && is_array($settings['packages']) ? $settings['packages'] : $default_packages;
        $rates = !empty($settings['rates']) && is_array($settings['rates']) ? array_merge($default_rates, $settings['rates']) : $default_rates;
        $works = !empty($settings['works']) && is_array($settings['works']) ? $settings['works'] : $default_works;

        $packages = apply_filters('construction_calculator_packages', $packages, $atts);

        $rates = apply_filters('construction_calculator_rates', $rates, $atts);

        $works = apply_filters('construction_calculator_works', $works, $atts);

        wp_enqueue_style(self::SLUG);
        wp_enqueue_script(self::SLUG);
        wp_localize_script(self::SLUG, 'CONSTRUCTION_CALC', [
            'packages' => $packages,
            'rates' => $rates,
            'currency' => 'Rs.',
            'ajax' => [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('construction_calc'),
            ],
        ]);

        ob_start();
        ?>
        <div class="constructo-wrapper">
            <div class="constructo-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p><?php echo esc_html__('You can arrive your Construction estimate here'); ?></p>
            </div>
            <div class="constructo-controls">
                <label>
                    <?php echo esc_html__('No. of Floors'); ?>
                    <select class="constructo-floor">
                        <option value="0"><?php echo esc_html__('Ground'); ?></option>
                        <option value="1"><?php echo esc_html__('1 Floor'); ?></option>
                        <option value="2"><?php echo esc_html__('2 Floors'); ?></option>
                        <option value="3"><?php echo esc_html__('3 Floors'); ?></option>
                        <option value="4"><?php echo esc_html__('4 Floors'); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo esc_html__('Package'); ?>
                    <select class="constructo-package">
                        <?php foreach ($packages as $key => $rate): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html(ucfirst($key) . ' Package @ ' . number_format_i18n($rate) . '/sqft'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="constructo-table">
                <div class="constructo-row constructo-head">
                    <div class="c-col work"><?php echo esc_html__('Work'); ?></div>
                    <div class="c-col area"><?php echo esc_html__('Area'); ?></div>
                    <div class="c-col unit"><?php echo esc_html__('Unit'); ?></div>
                    <div class="c-col rate"><?php echo esc_html__('Rate'); ?></div>
                    <div class="c-col cost"><?php echo esc_html__('Cost'); ?></div>
                </div>

                <?php foreach ($works as $work): ?>
                    <div class="constructo-row" data-key="<?php echo esc_attr($work['key']); ?>" data-rate-key="<?php echo esc_attr($work['rate_key']); ?>"<?php echo isset($work['math']) ? ' data-math="' . esc_attr($work['math']) . '"' : ''; ?>>
                        <div class="c-col work"><?php echo esc_html($work['label']); ?></div>
                        <div class="c-col area">
                            <?php if (count($work['inputs']) > 1): ?>
                                <div class="grid-2">
                                    <?php foreach ($work['inputs'] as $input): ?>
                                        <input type="number" min="0" step="1" placeholder="<?php echo esc_attr($input['placeholder']); ?>" />
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <input type="number" min="0" step="1" placeholder="<?php echo esc_attr($work['inputs'][0]['placeholder']); ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="c-col unit"><?php echo esc_html($work['unit']); ?></div>
                        <div class="c-col rate" data-rate>—</div>
                        <div class="c-col cost" data-cost>Rs. 0</div>
                    </div>
                <?php endforeach; ?>

                <div class="constructo-row constructo-total">
                    <div class="c-col work">&nbsp;</div>
                    <div class="c-col area">&nbsp;</div>
                    <div class="c-col unit">&nbsp;</div>
                    <div class="c-col rate"><?php echo esc_html__('Total Construction Cost'); ?></div>
                    <div class="c-col cost" data-total>Rs. 0</div>
                </div>
            </div>

            <div class="constructo-cta">
                <a href="#" class="constructo-button"><?php echo esc_html__('GET FREE ESTIMATE NOW'); ?></a>
            </div>
            <div class="constructo-modal" aria-hidden="true" role="dialog">
                <div class="constructo-modal__dialog">
                    <button class="constructo-modal__close" aria-label="Close">×</button>
                    <h3><?php echo esc_html__('Request Your Free Estimate'); ?></h3>
                    <form class="constructo-form" novalidate>
                        <div class="form-grid">
                            <label>
                                <span><?php echo esc_html__('Name'); ?></span>
                                <input type="text" name="name" required />
                            </label>
                            <label>
                                <span><?php echo esc_html__('Phone'); ?></span>
                                <input type="tel" name="phone" required />
                            </label>
                            <label>
                                <span><?php echo esc_html__('Email'); ?></span>
                                <input type="email" name="email" />
                            </label>
                            <label class="full">
                                <span><?php echo esc_html__('Message'); ?></span>
                                <textarea name="message" rows="3" placeholder="Project details"></textarea>
                            </label>
                            <label class="full readonly">
                                <span><?php echo esc_html__('Estimated Total'); ?></span>
                                <input type="text" name="estimated_total_display" readonly />
                            </label>
                        </div>
                        <input type="hidden" name="estimated_total" value="0" />
                        <button type="submit" class="constructo-submit"><?php echo esc_html__('Submit'); ?></button>
                        <p class="constructo-form__status" aria-live="polite"></p>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_settings() {
        $raw = get_option(self::OPTION, []);
        if (!is_array($raw)) {
            $raw = [];
        }
        $decode = function($val) {
            if (is_array($val)) return $val;
            if (!is_string($val) || trim($val) === '') return [];
            $data = json_decode($val, true);
            return is_array($data) ? $data : [];
        };
        return [
            'packages' => isset($raw['packages']) ? $decode($raw['packages']) : [],
            'rates' => isset($raw['rates']) ? $decode($raw['rates']) : [],
            'works' => isset($raw['works']) ? $decode($raw['works']) : [],
        ];
    }

    public function add_settings_page() {
        add_options_page(
            __('Construction Calculator', 'construction-calculator'),
            __('Construction Calculator', 'construction-calculator'),
            'manage_options',
            'construction-calculator-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting(self::OPTION, self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => [],
        ]);

        add_settings_section('construction_calculator_main', __('Configuration', 'construction-calculator'), function(){
            echo '<p>' . esc_html__('Provide JSON for packages, rates, and works. Invalid JSON will be ignored.') . '</p>';
        }, 'construction-calculator-settings');

        add_settings_field('packages', __('Packages (JSON)', 'construction-calculator'), function(){
            $opt = get_option(self::OPTION, []);
            $val = isset($opt['packages']) ? $opt['packages'] : json_encode(['standard'=>2099,'premium'=>2399,'luxury'=>2699], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            echo '<textarea name="' . esc_attr(self::OPTION) . '[packages]" rows="6" style="width:100%;">' . esc_textarea($val) . '</textarea>';
        }, 'construction-calculator-settings', 'construction_calculator_main');

        add_settings_field('rates', __('Rates (JSON)', 'construction-calculator'), function(){
            $opt = get_option(self::OPTION, []);
            $val = isset($opt['rates']) ? $opt['rates'] : json_encode(['sump_rate'=>24,'septic_rate'=>24,'wall_rate'=>425], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            echo '<textarea name="' . esc_attr(self::OPTION) . '[rates]" rows="6" style="width:100%;">' . esc_textarea($val) . '</textarea>';
        }, 'construction-calculator-settings', 'construction_calculator_main');

        add_settings_field('works', __('Works/Rows (JSON)', 'construction-calculator'), function(){
            $opt = get_option(self::OPTION, []);
            $sample = [
                ['key'=>'builtup','label'=>'Enter required Built up Area for Ground Floor','unit'=>'sqft','rate_key'=>'package','inputs'=>[['placeholder'=>'Area in sqft']]],
                ['key'=>'sump','label'=>'RCC Water Sump','unit'=>'ltr','rate_key'=>'sump_rate','inputs'=>[['placeholder'=>'No. of Liters']]],
                ['key'=>'septic','label'=>'Septic Tank','unit'=>'ltr','rate_key'=>'septic_rate','inputs'=>[['placeholder'=>'No. of Liters']]],
                ['key'=>'wall','label'=>'Plain Compound Wall','unit'=>'sqft','rate_key'=>'wall_rate','math'=>'product','inputs'=>[['placeholder'=>'Length'],['placeholder'=>'Height']]],
            ];
            $val = isset($opt['works']) ? $opt['works'] : json_encode($sample, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            echo '<textarea name="' . esc_attr(self::OPTION) . '[works]" rows="12" style="width:100%;">' . esc_textarea($val) . '</textarea>';
        }, 'construction-calculator-settings', 'construction_calculator_main');
    }

    public function sanitize_settings($input) {
        $output = [];
        foreach (['packages','rates','works'] as $key) {
            if (!isset($input[$key])) { continue; }
            $raw = wp_unslash($input[$key]);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                // Basic deep sanitization
                $output[$key] = $this->deep_sanitize($decoded);
            } else {
                // Keep original text so user can fix later
                $output[$key] = $raw;
            }
        }
        return $output;
    }

    private function deep_sanitize($value) {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $clean[sanitize_text_field((string)$k)] = $this->deep_sanitize($v);
            }
            return $clean;
        }
        if (is_scalar($value)) {
            return is_numeric($value) ? (float)$value : sanitize_text_field((string)$value);
        }
        return '';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Construction Calculator Settings', 'construction-calculator') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION);
        do_settings_sections('construction-calculator-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function handle_form_submit() {
        check_ajax_referer('construction_calc', 'nonce');

        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $total = isset($_POST['estimated_total']) ? floatval($_POST['estimated_total']) : 0;

        if (empty($name) || empty($phone)) {
            wp_send_json_error(['message' => __('Please provide name and phone.')]);
        }

        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New Construction Estimate Request - %s'), $name);
        $body_lines = [
            'Name: ' . $name,
            'Phone: ' . $phone,
            'Email: ' . $email,
            'Estimated Total: Rs. ' . number_format_i18n($total),
            'Message:',
            $message,
        ];
        $body = implode("\n", $body_lines);
        $headers = [];
        if (!empty($email)) {
            $headers[] = 'Reply-To: ' . $email;
        }

        $sent = wp_mail($admin_email, $subject, $body, $headers);
        if ($sent) {
            wp_send_json_success(['message' => __('Thanks! We will contact you shortly.')]);
        }
        wp_send_json_error(['message' => __('Unable to send at the moment. Please try again later.')]);
    }
}

new Construction_Cost_Calculator();

