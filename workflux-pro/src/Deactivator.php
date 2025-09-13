<?php

namespace WFP;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivator
{
    public static function deactivate(): void
    {
        // Intentionally minimal: keep data and roles on deactivate.
        // Cleanup or role removal could be added here if desired.
    }
}

