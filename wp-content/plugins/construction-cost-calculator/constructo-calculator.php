<?php
/**
 * Plugin Name: Constructo Cost Calculator
 * Description: Simple construction cost estimation calculator with shortcode [constructo_calculator].
 * Version: 1.0.0
 * Author: Your Team
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

class Constructo_Cost_Calculator {
    const VERSION = '1.0.0';
    const SLUG = 'constructo-calculator';

    public function __construct() {
        add_action('init', [$this, 'register_assets']);
        add_shortcode('constructo_calculator', [$this, 'render_shortcode']);
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
            ['wp-i18n'],
            self::VERSION,
            true
        );
    }

    /**
     * Shortcode renderer
     *
     * Attributes:
     * - base_rate: cost per sqft for Standard package
     * - sump_rate: cost per liter for sump
     * - septic_rate: cost per liter for septic tank
     * - wall_rate: cost per sqft for compound wall
     */
    public function render_shortcode($atts = []) {
        $atts = shortcode_atts([
            'title' => __('Home Construction Cost Calculator', 'constructo'),
            'city' => 'Coimbatore',
            'year' => date('Y'),
            'base_rate' => 2099,
            'sump_rate' => 24,
            'septic_rate' => 24,
            'wall_rate' => 425,
        ], $atts, 'constructo_calculator');

        $rates = [
            'base_rate' => (float) $atts['base_rate'],
            'sump_rate' => (float) $atts['sump_rate'],
            'septic_rate' => (float) $atts['septic_rate'],
            'wall_rate' => (float) $atts['wall_rate'],
        ];

        /**
         * Filter: constructo_calculator_rates
         * Allows altering default rates.
         */
        $rates = apply_filters('constructo_calculator_rates', $rates, $atts);

        wp_enqueue_style(self::SLUG);
        wp_enqueue_script(self::SLUG);
        wp_localize_script(self::SLUG, 'CONSTRUCTO_CALC', [
            'rates' => $rates,
            'currency' => 'Rs.',
            'i18n' => [
                'floors' => __('No. of Floors', 'constructo'),
                'package' => __('Package', 'constructo'),
                'ground' => __('Ground', 'constructo'),
                'g1' => __('G + 1', 'constructo'),
                'g2' => __('G + 2', 'constructo'),
                'standard' => sprintf(__('Standard Package @ %s/sqft', 'constructo'), number_format_i18n($rates['base_rate'])),
                'work' => __('Work', 'constructo'),
                'area' => __('Area', 'constructo'),
                'unit' => __('Unit', 'constructo'),
                'rate' => __('Rate', 'constructo'),
                'cost' => __('Cost', 'constructo'),
                'total' => __('Total Construction Cost', 'constructo'),
                'estimateCta' => __('Get Free Estimate Now', 'constructo'),
            ],
        ]);

        ob_start();
        ?>
        <div class="constructo-wrapper">
            <div class="constructo-header">
                <h2><?php echo esc_html($atts['title']); ?> (<?php echo esc_html($atts['year']); ?>) <?php echo esc_html($atts['city']); ?></h2>
                <p><?php echo esc_html__('You can arrive your Construction estimate here', 'constructo'); ?></p>
            </div>
            <div class="constructo-controls">
                <label>
                    <?php echo esc_html__('No. of Floors', 'constructo'); ?>
                    <select class="constructo-floor">
                        <option value="0"><?php echo esc_html__('Ground', 'constructo'); ?></option>
                        <option value="1"><?php echo esc_html__('G + 1', 'constructo'); ?></option>
                        <option value="2"><?php echo esc_html__('G + 2', 'constructo'); ?></option>
                    </select>
                </label>
                <label>
                    <?php echo esc_html__('Package', 'constructo'); ?>
                    <select class="constructo-package">
                        <option value="standard"><?php echo esc_html(sprintf(__('Standard Package @ %s/sqft', 'constructo'), number_format_i18n($rates['base_rate']))); ?></option>
                    </select>
                </label>
            </div>

            <div class="constructo-table">
                <div class="constructo-row constructo-head">
                    <div class="c-col work"><?php echo esc_html__('Work', 'constructo'); ?></div>
                    <div class="c-col area"><?php echo esc_html__('Area', 'constructo'); ?></div>
                    <div class="c-col unit"><?php echo esc_html__('Unit', 'constructo'); ?></div>
                    <div class="c-col rate"><?php echo esc_html__('Rate', 'constructo'); ?></div>
                    <div class="c-col cost"><?php echo esc_html__('Cost', 'constructo'); ?></div>
                </div>

                <div class="constructo-row" data-line="builtup">
                    <div class="c-col work"><?php echo esc_html__('Enter required Built up Area for Ground Floor', 'constructo'); ?></div>
                    <div class="c-col area"><input type="number" min="0" step="1" placeholder="Area in sqft" class="input-area" /></div>
                    <div class="c-col unit">sqft</div>
                    <div class="c-col rate"><?php echo esc_html('Rs.' . number_format_i18n($rates['base_rate'])); ?></div>
                    <div class="c-col cost" data-cost>Rs. 0</div>
                </div>

                <div class="constructo-row" data-line="sump">
                    <div class="c-col work"><?php echo esc_html__('Size of RCC Water Sump (A 4 member family will require 9000 liter capacity)', 'constructo'); ?></div>
                    <div class="c-col area"><input type="number" min="0" step="1" placeholder="No. of Liters" class="input-sump" /></div>
                    <div class="c-col unit">ltr</div>
                    <div class="c-col rate"><?php echo esc_html('Rs.' . number_format_i18n($rates['sump_rate'])); ?></div>
                    <div class="c-col cost" data-cost>Rs. 0</div>
                </div>

                <div class="constructo-row" data-line="septic">
                    <div class="c-col work"><?php echo esc_html__('Size of Septic Tank', 'constructo'); ?></div>
                    <div class="c-col area"><input type="number" min="0" step="1" placeholder="No. of Liters" class="input-septic" /></div>
                    <div class="c-col unit">ltr</div>
                    <div class="c-col rate"><?php echo esc_html('Rs.' . number_format_i18n($rates['septic_rate'])); ?></div>
                    <div class="c-col cost" data-cost>Rs. 0</div>
                </div>

                <div class="constructo-row" data-line="wall">
                    <div class="c-col work"><?php echo esc_html__('Plain Compound Wall', 'constructo'); ?></div>
                    <div class="c-col area">
                        <div class="grid-2">
                            <input type="number" min="0" step="1" placeholder="Length" class="input-wall-length" />
                            <input type="number" min="0" step="1" placeholder="Height" class="input-wall-height" />
                        </div>
                    </div>
                    <div class="c-col unit">sqft</div>
                    <div class="c-col rate"><?php echo esc_html('Rs.' . number_format_i18n($rates['wall_rate'])); ?></div>
                    <div class="c-col cost" data-cost>Rs. 0</div>
                </div>

                <div class="constructo-row constructo-total">
                    <div class="c-col work">&nbsp;</div>
                    <div class="c-col area">&nbsp;</div>
                    <div class="c-col unit">&nbsp;</div>
                    <div class="c-col rate"><?php echo esc_html__('Total Construction Cost', 'constructo'); ?></div>
                    <div class="c-col cost" data-total>Rs. 0</div>
                </div>
            </div>

            <div class="constructo-cta">
                <a href="#" class="constructo-button"><?php echo esc_html__('Get Free Estimate Now', 'constructo'); ?></a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Constructo_Cost_Calculator();

