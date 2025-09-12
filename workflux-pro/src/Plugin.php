<?php

namespace WFP;

use WFP\Admin\Menu;
use WFP\Api\Routes;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    public function run(): void
    {
        add_action('rest_api_init', [Routes::class, 'register']);
        add_action('admin_menu', [Menu::class, 'register']);
        add_action('admin_enqueue_scripts', [Menu::class, 'enqueueAdminAssets']);
        add_filter('login_redirect', [$this, 'redirectAfterLogin'], 10, 3);
        add_filter('authenticate', [$this, 'blockNonActiveUsers'], 30, 3);
    }

    public function redirectAfterLogin($redirect_to, $request, $user)
    {
        if (is_wp_error($user) || !$user) {
            return $redirect_to;
        }
        // Admin and managers
        if (user_can($user, 'wfp_manage_settings') || user_can($user, 'wfp_manage_projects') || user_can($user, 'wfp_manage_employees') || user_can($user, 'manage_options')) {
            return admin_url('admin.php?page=workflux-pro');
        }
        // Employee
        if (user_can($user, 'wfp_clock_attendance')) {
            return admin_url('admin.php?page=wfp-my-dashboard');
        }
        return $redirect_to;
    }

    public function blockNonActiveUsers($user, $username, $password)
    {
        if ($user instanceof \WP_User) {
            $status = get_user_meta($user->ID, 'wfp_status', true);
            if ($status && $status !== 'active') {
                return new \WP_Error('wfp_not_active', __('Your account is not active. Please contact admin.', 'workflux-pro'));
            }
        }
        return $user;
    }
}

