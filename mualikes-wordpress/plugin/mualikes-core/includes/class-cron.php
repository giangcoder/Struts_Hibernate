<?php
if (!defined('ABSPATH')) {
    exit;
}

class ML_Cron
{
    const HOOK = 'ml_cron_tick';

    public static function init()
    {
        add_filter('cron_schedules', function ($s) {
            $s['ml_5min'] = ['interval' => 300, 'display' => 'Mỗi 5 phút'];
            return $s;
        });
        add_action(self::HOOK, [__CLASS__, 'run']);
        if (!wp_next_scheduled(self::HOOK)) {
            self::schedule();
        }
    }

    public static function schedule()
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 60, 'ml_5min', self::HOOK);
        }
    }

    public static function unschedule()
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    public static function run()
    {
        if (get_transient('ml_cron_lock')) {
            return;
        }
        set_transient('ml_cron_lock', 1, 240);
        try {
            ML_Orders::release_scheduled();
            ML_Orders::sync_provider_status();
            ML_Orders::expire_vip();
        } finally {
            delete_transient('ml_cron_lock');
        }
    }
}
