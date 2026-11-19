<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * wp mualikes import [--only=groups,services,vip,notices,pages,blog,settings] [--images]
 * wp mualikes credit <user> <amount> [--note=<note>]
 * wp mualikes cron
 */
class ML_CLI
{
    /**
     * Nhập dữ liệu mẫu từ site gốc.
     *
     * [--only=<steps>]
     * : Danh sách bước, phân tách dấu phẩy.
     *
     * [--images]
     * : Tải ảnh /upload/ từ site gốc về thư mục uploads.
     */
    public function import($args, $assoc)
    {
        ML_Importer::$mirror_images = !empty($assoc['images']);
        $steps = !empty($assoc['only']) ? array_map('trim', explode(',', $assoc['only'])) : ['groups', 'services', 'vip', 'notices', 'pages', 'blog', 'settings'];
        ML_Importer::run($steps);
        flush_rewrite_rules(false);
        WP_CLI::success('Import xong.');
    }

    /**
     * Cộng/trừ tiền cho thành viên.
     *
     * <user>
     * : ID, email hoặc tên đăng nhập.
     *
     * <amount>
     * : Số tiền (âm để trừ).
     *
     * [--deposit]
     * : Tính vào tổng nạp (dùng để lên cấp bậc).
     */
    public function credit($args, $assoc)
    {
        $u = is_numeric($args[0]) ? get_userdata((int) $args[0]) : (get_user_by('login', $args[0]) ?: get_user_by('email', $args[0]));
        if (!$u) {
            WP_CLI::error('Không tìm thấy thành viên');
        }
        [$ok, $r] = ML_Users::change_balance($u->ID, (float) $args[1], 'admin', $assoc['note'] ?? 'Admin điều chỉnh (CLI)');
        if (!$ok) {
            WP_CLI::error($r);
        }
        if (!empty($assoc['deposit']) && $args[1] > 0) {
            ML_Users::add_deposit_total($u->ID, (float) $args[1]);
            global $wpdb;
            $wpdb->insert(ML_DB::t('payments'), ['user_id' => $u->ID, 'gateway' => 'admin', 'ref' => 'cli-' . uniqid(), 'amount' => (float) $args[1], 'bonus' => 0,
                'content' => $assoc['note'] ?? 'Admin cộng tiền (CLI)', 'status' => 'completed', 'created_at' => ml_now()]);
        }
        WP_CLI::success('Số dư mới: ' . ml_number($r));
    }

    /** Chạy cron đồng bộ đơn hàng ngay. */
    public function cron()
    {
        ML_Cron::run();
        WP_CLI::success('Đã chạy cron');
    }
}
WP_CLI::add_command('mualikes', 'ML_CLI');
