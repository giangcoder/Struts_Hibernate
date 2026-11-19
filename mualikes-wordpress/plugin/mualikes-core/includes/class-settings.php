<?php
if (!defined('ABSPATH')) {
    exit;
}

class ML_Settings
{
    const OPT = 'ml_settings';
    private static $cache = null;

    public static function defaults()
    {
        return [
            'site_name' => 'MuaLikes.Net',
            'logo_dark' => '/upload/2024/09/logodarkfile.png',
            'logo_green' => '/upload/2024/10/logogreenfile.png',
            'favicon' => '/upload/2024/09/logomain.png',
            'color_primary' => '#1e8e41',
            'color_secondary' => '#ec224b',
            'violation_texts' => "Hệ thống AI tự động giám sát đơn hàng vi phạm 24/7. Tài khoản mua đơn hàng vi phạm pháp luật sẽ bị khóa tài khoản ngay lập tức mà không báo trước",
            'support_links' => [
                ['label' => 'Messenger', 'url' => 'https://m.me/mualikes.net', 'icon' => '/upload/2024/09/messengericon.png'],
                ['label' => 'Fanpage', 'url' => 'https://www.facebook.com/mualikes.net', 'icon' => '/upload/2024/10/fanpage-icon.png'],
                ['label' => 'Chat Telegram', 'url' => 'https://t.me/cskh_mualikes', 'icon' => '/upload/2026/03/telegramchat.png'],
                ['label' => 'Youtube HDSD', 'url' => 'https://www.youtube.com/@MuaLikesNet', 'icon' => '/upload/2024/09/youtubeicon.png'],
            ],
            'levels' => [
                ['name' => 'Thành viên', 'color' => '#007dc4', 'min' => 0, 'perks' => ['-', 'Bình thường']],
                ['name' => 'Cộng tác viên', 'color' => '#fbb20c', 'min' => 1000000, 'perks' => ['Giảm giá 5% dịch vụ', 'Ưu tiên hỗ trợ']],
                ['name' => 'Đại lý', 'color' => '#fb0c0c', 'min' => 10000000, 'perks' => ['Giảm giá 10% dịch vụ', 'Ưu tiên hỗ trợ riêng']],
            ],
            'level_notes' => "- Hệ thống sẽ tự động nâng cấp bậc sau 1 - 5 phút kể từ khi tài khoản đủ điều kiện.\n- Mỗi cấp bậc sẽ có bảng giá riêng cho từng dịch vụ và nhiều ưu đãi đặc biệt khác.",
            'faq' => [],
            'recent_orders_count' => 50,
            // Nạp tiền
            'bank_name' => 'MB Bank',
            'bank_bin' => 'MB',
            'bank_account' => '',
            'bank_owner' => '',
            'transfer_prefix' => 'mualikes',
            'min_deposit' => 10000,
            'promo_percent' => 10,
            'promo_from' => '',
            'promo_to' => '',
            'promo_min' => 10000,
            'sepay_api_key' => '',
            'casso_secure_token' => '',
            'card_rate' => 80,
            'usdt_address' => '',
            'usdt_rate' => 25000,
            // Đơn hàng
            'cancellation_fee' => 5000,
            'max_guarantee_canceled' => 3,
            'max_multi_links' => 50,
            // Khác
            'google_analytics' => 'G-SSVXV0KW6G',
            'google_translate' => 1,
            'footer_copyright' => 'MuaLikes.Net',
            'footer_design' => 'Design by MuaLikes.Net',
            'custom_css' => '',
            'head_code' => '',
            'remote_asset_base' => 'https://mualikes.net',
            'api_enabled' => 1,
        ];
    }

    public static function all()
    {
        if (self::$cache === null) {
            $saved = get_option(self::OPT, []);
            self::$cache = array_merge(self::defaults(), is_array($saved) ? $saved : []);
        }
        return self::$cache;
    }

    public static function get($key, $default = null)
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function update(array $values)
    {
        $all = array_merge(self::all(), $values);
        update_option(self::OPT, $all);
        self::$cache = $all;
    }

    public static function install_defaults()
    {
        if (!get_option(self::OPT)) {
            $d = self::defaults();
            $faq = ML_DIR . 'data/faq.json';
            if (file_exists($faq)) {
                $d['faq'] = json_decode(file_get_contents($faq), true);
            }
            add_option(self::OPT, $d);
        }
    }

    public static function levels()
    {
        $levels = self::get('levels');
        usort($levels, function ($a, $b) {
            return $a['min'] <=> $b['min'];
        });
        return $levels;
    }

    /** Khuyến mãi nạp tiền đang áp dụng (%), 0 nếu không. */
    public static function active_promo()
    {
        $p = (float) self::get('promo_percent');
        $from = self::get('promo_from');
        $to = self::get('promo_to');
        if ($p <= 0 || !$from || !$to) {
            return 0;
        }
        $now = current_time('timestamp');
        if ($now >= strtotime($from . ' 00:00:00') && $now <= strtotime($to . ' 23:59:59')) {
            return $p;
        }
        return 0;
    }
}
