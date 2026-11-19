<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ví tiền, tổng nạp, cấp bậc, API token của thành viên.
 * Số dư lưu ở usermeta "ml_balance" và luôn được cộng/trừ bằng 1 câu SQL nguyên tử để tránh trừ tiền 2 lần.
 */
class ML_Users
{
    /** ID giao dịch vừa ghi gần nhất (để gắn mã đơn sau khi tạo). */
    public static $last_tx = 0;

    public static function init()
    {
        add_action('after_setup_theme', function () {
            if (!current_user_can('edit_posts')) {
                show_admin_bar(false);
            }
        });
        add_action('admin_init', function () {
            if (wp_doing_ajax() || current_user_can('edit_posts')) {
                return;
            }
            wp_safe_redirect(home_url('/auth/profile/'));
            exit;
        });
        add_filter('login_url', function ($url, $redirect) {
            if (strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-admin') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-login') !== false) {
                return $url;
            }
            return home_url('/auth/login/');
        }, 10, 2);
        add_action('user_register', function ($uid) {
            foreach (['ml_balance' => 0, 'ml_total_deposit' => 0] as $k => $v) {
                if (get_user_meta($uid, $k, true) === '') {
                    update_user_meta($uid, $k, $v);
                }
            }
        });
    }

    public static function balance($uid)
    {
        return (float) get_user_meta($uid, 'ml_balance', true);
    }

    public static function total_deposit($uid)
    {
        return (float) get_user_meta($uid, 'ml_total_deposit', true);
    }

    /** Cấp bậc: 1 = Thành viên, 2 = CTV, 3 = Đại lý (theo tổng nạp) hoặc cấp do admin gán cố định. */
    public static function level($uid)
    {
        if (!$uid) {
            return 1;
        }
        $fixed = (int) get_user_meta($uid, 'ml_level_fixed', true);
        if ($fixed > 0) {
            return $fixed;
        }
        $total = self::total_deposit($uid);
        $level = 1;
        foreach (ML_Settings::levels() as $i => $l) {
            if ($total >= (float) $l['min']) {
                $level = $i + 1;
            }
        }
        return $level;
    }

    public static function level_name($uid)
    {
        $levels = ML_Settings::levels();
        $l = self::level($uid);
        return isset($levels[$l - 1]) ? $levels[$l - 1]['name'] : '';
    }

    private static function ensure_meta($uid)
    {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'ml_balance'", $uid));
        if (!$exists) {
            add_user_meta($uid, 'ml_balance', 0, true);
        }
    }

    /**
     * Cộng (amount > 0) hoặc trừ (amount < 0) số dư. Khi trừ, chỉ thành công nếu đủ tiền.
     * Trả về [true, balance_after] hoặc [false, message].
     */
    public static function change_balance($uid, $amount, $type, $note = '', $order_id = 0)
    {
        global $wpdb;
        $uid = (int) $uid;
        $amount = round((float) $amount, 2);
        if (!$uid || $amount == 0) {
            return [false, 'Số tiền không hợp lệ'];
        }
        self::ensure_meta($uid);
        if ($amount < 0) {
            $ok = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS DECIMAL(16,2)) + %f WHERE user_id = %d AND meta_key = 'ml_balance' AND CAST(meta_value AS DECIMAL(16,2)) >= %f",
                $amount, $uid, abs($amount)
            ));
        } else {
            $ok = $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS DECIMAL(16,2)) + %f WHERE user_id = %d AND meta_key = 'ml_balance'",
                $amount, $uid
            ));
        }
        wp_cache_delete($uid, 'user_meta');
        if (!$ok) {
            return [false, 'Số dư không đủ, vui lòng nạp thêm tiền!'];
        }
        $after = self::balance($uid);
        $wpdb->insert(ML_DB::t('transactions'), [
            'user_id' => $uid, 'type' => $type, 'amount' => $amount,
            'balance_before' => $after - $amount, 'balance_after' => $after,
            'order_id' => (int) $order_id, 'note' => mb_substr((string) $note, 0, 1000), 'created_at' => ml_now(),
        ]);
        self::$last_tx = (int) $wpdb->insert_id;
        return [true, $after];
    }

    public static function add_deposit_total($uid, $amount)
    {
        global $wpdb;
        $uid = (int) $uid;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'ml_total_deposit'", $uid));
        if (!$exists) {
            add_user_meta($uid, 'ml_total_deposit', 0, true);
        }
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS DECIMAL(16,2)) + %f WHERE user_id = %d AND meta_key = 'ml_total_deposit'",
            (float) $amount, $uid
        ));
        wp_cache_delete($uid, 'user_meta');
    }

    public static function api_token($uid, $regenerate = false)
    {
        $t = get_user_meta($uid, 'ml_api_token', true);
        if (!$t || $regenerate) {
            $t = wp_generate_password(40, false, false);
            update_user_meta($uid, 'ml_api_token', $t);
        }
        return $t;
    }

    public static function by_token($token)
    {
        if (!$token || strlen($token) < 20) {
            return 0;
        }
        $users = get_users(['meta_key' => 'ml_api_token', 'meta_value' => $token, 'number' => 1, 'fields' => 'ID']);
        return $users ? (int) $users[0] : 0;
    }

    /** Mã nội dung chuyển khoản của thành viên, ví dụ "mualikes 15". */
    public static function transfer_code($uid)
    {
        return ML_Settings::get('transfer_prefix', 'mualikes') . ' ' . (int) $uid;
    }
}
