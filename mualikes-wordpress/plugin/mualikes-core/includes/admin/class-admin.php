<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-admin-service.php';
require_once __DIR__ . '/class-admin-pages.php';

class ML_Admin
{
    const CAP = 'manage_options';

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_ml_save', [__CLASS__, 'save']);
        add_action('admin_enqueue_scripts', function ($hook) {
            wp_add_inline_style('common', '.ml-wrap .card{max-width:none;padding:12px 16px}.ml-stats{display:flex;gap:12px;flex-wrap:wrap}.ml-stats .card{flex:1;min-width:180px}.ml-stats b{font-size:22px;display:block}.ml-table td,.ml-table th{vertical-align:top}.ml-servers input[type=text],.ml-servers input[type=number]{width:100%}.ml-servers textarea{width:100%;min-height:54px}.ml-badge{display:inline-block;padding:2px 6px;border-radius:3px;background:#eee;font-size:11px}.ml-badge.completed{background:#c6f6d5}.ml-badge.canceled,.ml-badge.refunded{background:#fed7d7}.ml-badge.running,.ml-badge.processing,.ml-badge.active{background:#bee3f8}.ml-badge.pending{background:#fefcbf}');
        });
        ML_Admin_Service::init();
    }

    public static function menu()
    {
        add_menu_page('MuaLikes', 'MuaLikes', self::CAP, 'mualikes', ['ML_Admin_Pages', 'dashboard'], 'dashicons-chart-area', 24);
        add_submenu_page('mualikes', 'Tổng quan', 'Tổng quan', self::CAP, 'mualikes', ['ML_Admin_Pages', 'dashboard']);
        add_submenu_page('mualikes', 'Đơn hàng', 'Đơn hàng', self::CAP, 'ml-orders', ['ML_Admin_Pages', 'orders']);
        add_submenu_page('mualikes', 'Thành viên & số dư', 'Thành viên', self::CAP, 'ml-users', ['ML_Admin_Pages', 'users']);
        add_submenu_page('mualikes', 'Nạp tiền', 'Nạp tiền', self::CAP, 'ml-payments', ['ML_Admin_Pages', 'payments']);
        add_submenu_page('mualikes', 'Lịch sử giao dịch', 'Giao dịch', self::CAP, 'ml-transactions', ['ML_Admin_Pages', 'transactions']);
        add_submenu_page('mualikes', 'Nhà cung cấp API', 'Nhà cung cấp', self::CAP, 'ml-providers', ['ML_Admin_Pages', 'providers']);
        add_submenu_page('mualikes', 'Cài đặt', 'Cài đặt', self::CAP, 'ml-settings', ['ML_Admin_Pages', 'settings']);
        add_submenu_page('mualikes', 'Nhập dữ liệu', 'Nhập dữ liệu', self::CAP, 'ml-import', ['ML_Admin_Pages', 'import']);
    }

    /** Xử lý mọi form POST của admin (admin-post.php?action=ml_save&do=...). */
    public static function save()
    {
        if (!current_user_can(self::CAP)) {
            wp_die('Không có quyền');
        }
        check_admin_referer('ml_admin');
        $do = sanitize_key($_POST['do'] ?? '');
        $back = wp_get_referer() ?: admin_url('admin.php?page=mualikes');
        $msg = 'Đã lưu';
        $in = wp_unslash($_POST);
        switch ($do) {
            case 'order_update':
                $id = (int) $in['order_id'];
                $o = ML_Orders::get($id);
                if ($o) {
                    ML_Orders::update($id, [
                        'admin_note' => sanitize_text_field($in['admin_note'] ?? ''),
                        'start_count' => (int) ($in['start_count'] ?? $o->start_count),
                        'present' => (int) ($in['present'] ?? $o->present),
                    ]);
                    $st = sanitize_key($in['status'] ?? $o->status);
                    if ($st !== $o->status) {
                        ML_Orders::set_status($id, $st, isset($in['remains']) && $in['remains'] !== '' ? (int) $in['remains'] : max(0, $o->quantity - (int) ($in['present'] ?? $o->present)));
                    }
                    if (!empty($in['resend'])) {
                        ML_Orders::update($id, ['provider_order' => '', 'status' => 'pending']);
                        ML_Orders::dispatch($id);
                    }
                }
                break;
            case 'balance':
                $uid = (int) $in['user_id'];
                $amount = (float) str_replace([',', '.'], '', $in['amount'] ?? 0) * (($in['sign'] ?? '+') === '-' ? -1 : 1);
                [$ok, $r] = ML_Users::change_balance($uid, $amount, 'admin', sanitize_text_field($in['note'] ?? 'Admin điều chỉnh'));
                if ($ok && !empty($in['as_deposit']) && $amount > 0) {
                    ML_Users::add_deposit_total($uid, $amount);
                    global $wpdb;
                    $wpdb->insert(ML_DB::t('payments'), ['user_id' => $uid, 'gateway' => 'admin', 'ref' => 'admin-' . uniqid(), 'amount' => $amount, 'bonus' => 0,
                        'content' => sanitize_text_field($in['note'] ?? ''), 'status' => 'completed', 'created_at' => ml_now()]);
                }
                $msg = $ok ? 'Số dư mới: ' . ml_number($r) . 'đ' : $r;
                break;
            case 'user_flags':
                $uid = (int) $in['user_id'];
                update_user_meta($uid, 'ml_level_fixed', (int) ($in['level_fixed'] ?? 0));
                update_user_meta($uid, 'ml_banned', empty($in['banned']) ? 0 : 1);
                if (isset($in['total_deposit']) && $in['total_deposit'] !== '') {
                    update_user_meta($uid, 'ml_total_deposit', (float) $in['total_deposit']);
                }
                break;
            case 'payment_approve':
                $u = !empty($in['user']) ? (is_numeric($in['user']) ? (int) $in['user'] : (int) (get_user_by('login', $in['user'])->ID ?? 0)) : null;
                [$ok, $msg] = ML_Payments::approve((int) $in['payment_id'], $in['real_amount'] !== '' ? (float) $in['real_amount'] : null, $u);
                break;
            case 'payment_reject':
                global $wpdb;
                $wpdb->update(ML_DB::t('payments'), ['status' => 'rejected'], ['id' => (int) $in['payment_id']]);
                break;
            case 'provider':
                if (!empty($in['delete'])) {
                    global $wpdb;
                    $wpdb->delete(ML_DB::t('providers'), ['id' => (int) $in['id']]);
                } else {
                    $pid = ML_Providers::save($in);
                    if (!empty($in['check'])) {
                        [$ok, $r] = ML_Providers::balance($pid);
                        $msg = $ok ? 'Kết nối OK. Số dư: ' . ($r['balance'] ?? '') . ' ' . ($r['currency'] ?? '') : 'Lỗi: ' . $r;
                    }
                }
                break;
            case 'settings':
                $vals = [];
                foreach (ML_Settings::defaults() as $k => $def) {
                    if (!array_key_exists($k, $in)) {
                        continue;
                    }
                    if (in_array($k, ['support_links', 'levels', 'faq'], true)) {
                        $j = json_decode($in[$k], true);
                        if (is_array($j)) {
                            $vals[$k] = $j;
                        }
                    } elseif (in_array($k, ['custom_css', 'head_code', 'violation_texts', 'level_notes'], true)) {
                        $vals[$k] = current_user_can('unfiltered_html') ? $in[$k] : wp_kses_post($in[$k]);
                    } else {
                        $vals[$k] = sanitize_text_field($in[$k]);
                    }
                }
                foreach (['google_translate', 'api_enabled'] as $cb) {
                    $vals[$cb] = empty($in[$cb]) ? 0 : 1;
                }
                ML_Settings::update($vals);
                break;
            case 'import':
                ML_Importer::$mirror_images = !empty($in['images']);
                $steps = isset($in['steps']) && is_array($in['steps']) ? array_map('sanitize_key', $in['steps']) : [];
                $log = ML_Importer::run($steps);
                $msg = implode(' | ', $log);
                break;
        }
        wp_safe_redirect(add_query_arg('ml_msg', rawurlencode(wp_strip_all_tags($msg)), $back));
        exit;
    }

    public static function notice()
    {
        if (!empty($_GET['ml_msg'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(wp_unslash($_GET['ml_msg'])) . '</p></div>';
        }
    }

    public static function form_open($do)
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('ml_admin');
        echo '<input type="hidden" name="action" value="ml_save"><input type="hidden" name="do" value="' . esc_attr($do) . '">';
    }
}
