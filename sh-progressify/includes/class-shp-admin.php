<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin UI controller for SH Progressify mock plugin.
 */
class SHP_Admin {

    /**
     * Admin page hook suffix for enqueuing assets.
     *
     * @var string
     */
    private $page_hook_suffix = '';

    /**
     * Menu slug.
     *
     * @var string
     */
    private $menu_slug = 'sh-progressify';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register the top-level admin menu and page.
     */
    public function register_menu() {
        $capability = 'manage_options';
        $page_title = __( 'Progressify Mock', 'sh-progressify' );
        $menu_title = __( 'Progressify', 'sh-progressify' );
        $icon       = 'dashicons-smartphone';

        $this->page_hook_suffix = add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $this->menu_slug,
            array( $this, 'render_page' ),
            $icon,
            58
        );
    }

    /**
     * Enqueue styles and scripts on our plugin page.
     */
    public function enqueue_assets( $hook_suffix ) {
        if ( $hook_suffix !== $this->page_hook_suffix ) {
            return;
        }

        // Styles
        wp_enqueue_style( 'shp-admin', SHP_PLUGIN_URL . 'admin/css/admin.css', array(), '0.1.0' );
        // JS
        wp_enqueue_script( 'shp-admin', SHP_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), '0.1.0', true );

        // Data for JS (in case you wire it later)
        wp_localize_script( 'shp-admin', 'SHP_DATA', array(
            'pluginUrl' => SHP_PLUGIN_URL,
            'nonce'     => wp_create_nonce( 'shp_admin' ),
        ) );
    }

    /**
     * Render the admin application mockup.
     */
    public function render_page() {
        ?>
        <div class="shp-wrap">
            <aside class="shp-sidebar">
                <div class="shp-brand">
                    <img src="<?php echo esc_url( SHP_PLUGIN_URL . 'assets/logo.svg' ); ?>" alt="Logo" />
                    <span>Progressify</span>
                </div>
                <nav class="shp-nav">
                    <ul>
                        <li class="is-active" data-section="dashboard"><span class="dashicons dashicons-dashboard"></span><b><?php esc_html_e( 'Dashboard', 'sh-progressify' ); ?></b></li>
                        <li data-section="manifest"><span class="dashicons dashicons-media-document"></span><b><?php esc_html_e( 'Web App Manifest', 'sh-progressify' ); ?></b></li>
                        <li data-section="installation"><span class="dashicons dashicons-download"></span><b><?php esc_html_e( 'Installation', 'sh-progressify' ); ?></b></li>
                        <li data-section="offline"><span class="dashicons dashicons-cloud"></span><b><?php esc_html_e( 'Offline Usage', 'sh-progressify' ); ?></b></li>
                        <li data-section="ui"><span class="dashicons dashicons-screenoptions"></span><b><?php esc_html_e( 'UI Components', 'sh-progressify' ); ?></b></li>
                        <li data-section="capabilities"><span class="dashicons dashicons-admin-generic"></span><b><?php esc_html_e( 'App Capabilities', 'sh-progressify' ); ?></b></li>
                        <li data-section="push"><span class="dashicons dashicons-megaphone"></span><b><?php esc_html_e( 'Push Notifications', 'sh-progressify' ); ?></b></li>
                        <li data-section="publish"><span class="dashicons dashicons-cloud-upload"></span><b><?php esc_html_e( 'Publish to App Stores', 'sh-progressify' ); ?></b></li>
                        <li data-section="help"><span class="dashicons dashicons-editor-help"></span><b><?php esc_html_e( 'Help Centre', 'sh-progressify' ); ?></b></li>
                        <li data-section="whatsnew"><span class="dashicons dashicons-update"></span><b><?php esc_html_e( "What's New", 'sh-progressify' ); ?></b></li>
                    </ul>
                </nav>
            </aside>
            <main class="shp-main">
                <header class="shp-header">
                    <h1 id="shp-section-title">Dashboard</h1>
                    <button class="button button-primary" id="shp-publish-cta"><?php esc_html_e( 'Publish to App Stores', 'sh-progressify' ); ?></button>
                </header>

                <section class="shp-section" id="section-dashboard">
                    <div class="shp-grid">
                        <div class="shp-card">
                            <h3><?php esc_html_e( 'Active PWA Users', 'sh-progressify' ); ?></h3>
                            <div class="shp-metric">7</div>
                            <p><?php esc_html_e( 'Chrome 100%', 'sh-progressify' ); ?></p>
                        </div>
                        <div class="shp-card">
                            <h3><?php esc_html_e( 'PWA Scorecard', 'sh-progressify' ); ?></h3>
                            <div class="shp-scorebar"><span style="width: 82%"></span></div>
                            <p><?php esc_html_e( 'Good', 'sh-progressify' ); ?></p>
                        </div>
                        <div class="shp-card">
                            <h3><?php esc_html_e( 'PWA Installations', 'sh-progressify' ); ?></h3>
                            <div class="shp-chart-placeholder">—</div>
                        </div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-manifest">
                    <div class="shp-form">
                        <label><?php esc_html_e( 'App Name', 'sh-progressify' ); ?> <input type="text" placeholder="SH Progressify – Sri Hayavadhana" /></label>
                        <label><?php esc_html_e( 'Short Name', 'sh-progressify' ); ?> <input type="text" placeholder="Sri Hayavadhana" /></label>
                        <label><?php esc_html_e( 'Theme Color', 'sh-progressify' ); ?> <input type="color" value="#0B6E4F" /></label>
                        <label><?php esc_html_e( 'Background Color', 'sh-progressify' ); ?> <input type="color" value="#ffffff" /></label>
                        <div class="shp-actions"><button class="button button-primary"><?php esc_html_e( 'Save Changes', 'sh-progressify' ); ?></button></div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-installation">
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'Installation Prompts', 'sh-progressify' ); ?></h3>
                        <label class="shp-switch"><input type="checkbox" checked><span></span></label>
                        <p><?php esc_html_e( 'Show header banner or snackbar to encourage install.', 'sh-progressify' ); ?></p>
                        <div class="shp-actions"><button class="button"><?php esc_html_e( 'Preview', 'sh-progressify' ); ?></button></div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-offline">
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'Offline Cache', 'sh-progressify' ); ?></h3>
                        <label class="shp-switch"><input type="checkbox" checked><span></span></label>
                        <div class="shp-form">
                            <label><?php esc_html_e( 'Caching Strategy', 'sh-progressify' ); ?>
                                <select>
                                    <option>Network-First</option>
                                    <option>Stale-While-Revalidate</option>
                                    <option>Cache-First</option>
                                </select>
                            </label>
                            <label><?php esc_html_e( 'Cache Expiration (days)', 'sh-progressify' ); ?> <input type="number" value="10" min="1" /></label>
                        </div>
                        <div class="shp-actions"><button class="button button-primary"><?php esc_html_e( 'Save Changes', 'sh-progressify' ); ?></button></div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-ui">
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'Navigation Tab Bar', 'sh-progressify' ); ?></h3>
                        <label class="shp-switch"><input type="checkbox" checked><span></span></label>
                        <div class="shp-form shp-inline">
                            <label><input type="text" placeholder="Home" /></label>
                            <label><input type="text" placeholder="Services" /></label>
                            <label><input type="text" placeholder="Events" /></label>
                            <label><input type="text" placeholder="Contact" /></label>
                        </div>
                        <div class="shp-actions"><button class="button button-secondary"><?php esc_html_e( 'Add Item', 'sh-progressify' ); ?></button></div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-capabilities">
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'Smooth Transitions', 'sh-progressify' ); ?></h3>
                        <label class="shp-switch"><input type="checkbox"><span></span></label>
                    </div>
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'URL Protocol Handler', 'sh-progressify' ); ?></h3>
                        <label class="shp-switch"><input type="checkbox"><span></span></label>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-push">
                    <div class="shp-card">
                        <h3><?php esc_html_e( 'Push Notifications', 'sh-progressify' ); ?></h3>
                        <p><?php esc_html_e( 'Mock subscriber list and send test notifications.', 'sh-progressify' ); ?></p>
                        <div class="shp-actions"><button class="button button-primary"><?php esc_html_e( 'Send Push Notification', 'sh-progressify' ); ?></button></div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-publish">
                    <div class="shp-grid two">
                        <div class="shp-plan">
                            <h3>Android</h3>
                            <div class="shp-price">$29</div>
                            <ul>
                                <li><?php esc_html_e( 'Generate Android package', 'sh-progressify' ); ?></li>
                                <li><?php esc_html_e( 'Publish to Google Play', 'sh-progressify' ); ?></li>
                            </ul>
                            <button class="button">PayPal</button>
                        </div>
                        <div class="shp-plan featured">
                            <h3>Android and iOS</h3>
                            <div class="shp-price">$48</div>
                            <ul>
                                <li><?php esc_html_e( 'Includes Android & iOS packages', 'sh-progressify' ); ?></li>
                                <li><?php esc_html_e( 'Publish to both app stores', 'sh-progressify' ); ?></li>
                            </ul>
                            <button class="button button-primary">PayPal</button>
                        </div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-help">
                    <div class="shp-grid two">
                        <div class="shp-card">
                            <h3><?php esc_html_e( 'Frequently Asked Questions', 'sh-progressify' ); ?></h3>
                            <details><summary><?php esc_html_e( 'What are Progressive Web Apps?', 'sh-progressify' ); ?></summary><p><?php esc_html_e( 'They are installable, reliable, and fast web experiences.', 'sh-progressify' ); ?></p></details>
                            <details><summary><?php esc_html_e( 'Why prompts may not appear?', 'sh-progressify' ); ?></summary><p><?php esc_html_e( 'Depends on browser criteria and engagement heuristics.', 'sh-progressify' ); ?></p></details>
                        </div>
                        <div class="shp-card">
                            <h3><?php esc_html_e( 'Support Request', 'sh-progressify' ); ?></h3>
                            <div class="shp-form">
                                <label><input type="text" placeholder="Your Name" /></label>
                                <label><input type="email" placeholder="Your Email" /></label>
                                <label><textarea placeholder="Describe your problem"></textarea></label>
                                <button class="button button-primary"><?php esc_html_e( 'Submit Request', 'sh-progressify' ); ?></button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="shp-section is-hidden" id="section-whatsnew">
                    <div class="shp-timeline">
                        <div class="shp-release">
                            <div class="shp-version">v1.0.5</div>
                            <div class="shp-date">April 3, 2025</div>
                            <ul>
                                <li><?php esc_html_e( 'Added Korean language support.', 'sh-progressify' ); ?></li>
                                <li><?php esc_html_e( 'Fixed dark mode activation issue.', 'sh-progressify' ); ?></li>
                            </ul>
                        </div>
                        <div class="shp-release">
                            <div class="shp-version">v1.0.3</div>
                            <div class="shp-date">March 31, 2025</div>
                            <ul>
                                <li><?php esc_html_e( 'Added PWA as file handler.', 'sh-progressify' ); ?></li>
                                <li><?php esc_html_e( 'Improved compatibility with page builders.', 'sh-progressify' ); ?></li>
                            </ul>
                        </div>
                    </div>
                </section>

            </main>
        </div>
        <?php
    }
}

