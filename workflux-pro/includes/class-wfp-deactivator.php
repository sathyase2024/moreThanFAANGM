<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WFP_Deactivator {
    public static function deactivate() {
        // No data removal on deactivate; only clear rewrites
        flush_rewrite_rules();
    }
}

