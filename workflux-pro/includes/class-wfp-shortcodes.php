<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Shortcodes {
    public function register() {
        add_shortcode( 'wfp_clock', [ $this, 'shortcode_clock' ] );
        add_shortcode( 'wfp_my_timesheet', [ $this, 'shortcode_timesheet' ] );
    }

    public function shortcode_clock( $atts ) {
        if ( ! is_user_logged_in() ) {
            return esc_html__( 'Please log in to access the clock.', 'workflux-pro' );
        }
        wp_enqueue_style( 'wfp-frontend' );
        wp_enqueue_script( 'wfp-frontend' );
        ob_start();
        ?>
        <div class="wfp-clock">
            <button class="button button-primary" id="wfp-clock-toggle">Clock In / Out</button>
            <div class="wfp-clock-status" aria-live="polite"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function shortcode_timesheet( $atts ) {
        if ( ! is_user_logged_in() ) {
            return esc_html__( 'Please log in to view your timesheet.', 'workflux-pro' );
        }
        wp_enqueue_style( 'wfp-frontend' );
        ob_start();
        ?>
        <div class="wfp-timesheet">
            <h3><?php echo esc_html__( 'My Timesheet (Recent)', 'workflux-pro' ); ?></h3>
            <div id="wfp-timesheet-list"></div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

