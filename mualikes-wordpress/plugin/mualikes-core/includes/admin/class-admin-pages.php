<?php
if (!defined('ABSPATH')) {
    exit;
}

class ML_Admin_Pages
{
    private static function head($title)
    {
        echo '<div class="wrap ml-wrap"><h1>' . esc_html($title) . '</h1>';
        ML_Admin::notice();
    }

    private static function pager($total, $per, $page)
    {
        $pages = max(1, (int) ceil($total / $per));
        if ($pages <= 1) {
            return;
        }
        echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links([
            'base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $page, 'total' => $pages,
        ]) . '</div></div>';
    }

    private static function user_link($uid)
    {
        $u = get_userdata($uid);
        return $u ? '<a href="' . esc_url(admin_url('admin.php?page=ml-users&s=' . rawurlencode($u->user_login))) . '">' . esc_html($u->user_login) . '</a>' : '#' . (int) $uid;
    }

    public static function dashboard()
    {
        global $wpdb;
        $o = ML_DB::t('orders');
        $p = ML_DB::t('payments');
        $today = current_time('Y-m-d');
        $month = current_time('Y-m-01');
        $stats = [
            'Thành viên' => ml_number(count_users()['total_users']),
            'Tổng số dư thành viên' => ml_number($wpdb->get_var("SELECT SUM(CAST(meta_value AS DECIMAL(16,2))) FROM {$wpdb->usermeta} WHERE meta_key = 'ml_balance'")) . 'đ',
            'Nạp hôm nay' => ml_number($wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $p WHERE status = 'completed' AND created_at >= %s", $today))) . 'đ',
            'Nạp tháng này' => ml_number($wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $p WHERE status = 'completed' AND created_at >= %s", $month))) . 'đ',
            'Doanh thu đơn hôm nay' => ml_number($wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM $o WHERE status NOT IN ('canceled','refunded') AND created_at >= %s", $today))) . 'đ',
            'Đơn hôm nay' => ml_number($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $o WHERE created_at >= %s", $today))),
            'Đơn chờ xử lý thủ công' => ml_number($wpdb->get_var("SELECT COUNT(*) FROM $o WHERE status = 'pending' AND provider_order = ''")),
            'Thẻ/giao dịch chờ duyệt' => ml_number($wpdb->get_var("SELECT COUNT(*) FROM $p WHERE status IN ('pending','unmatched')")),
        ];
        self::head('MuaLikes – Tổng quan');
        echo '<div class="ml-stats">';
        foreach ($stats as $k => $v) {
            echo '<div class="card"><span>' . esc_html($k) . '</span><b>' . esc_html($v) . '</b></div>';
        }
        echo '</div>';
        echo '<div class="card"><h2>Webhook nạp tiền tự động</h2><p>SePay: <code>' . esc_html(home_url('/server/webhook/sepay')) . '</code> (Xác thực: API Key)</p><p>Casso: <code>' . esc_html(home_url('/server/webhook/casso')) . '</code> (Secure Token)</p>'
            . '<p>API đại lý: <code>' . esc_html(home_url('/api/v2')) . '</code></p><p>Cron đồng bộ đơn chạy mỗi 5 phút qua WP-Cron. Nên cấu hình cron thật: <code>*/5 * * * * wget -q -O - ' . esc_html(site_url('wp-cron.php?doing_wp_cron')) . ' &gt;/dev/null</code></p></div></div>';
    }

    public static function orders()
    {
        global $wpdb;
        $t = ML_DB::t('orders');
        $per = 30;
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $where = '1=1';
        $st = sanitize_key($_GET['status'] ?? '');
        if ($st) {
            $where .= $wpdb->prepare(' AND o.status = %s', $st);
        }
        if (!empty($_GET['manual'])) {
            $where .= " AND o.provider_order = ''";
        }
        $s = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        if ($s !== '') {
            $like = '%' . $wpdb->esc_like($s) . '%';
            $where .= $wpdb->prepare(' AND (o.id = %d OR o.uid LIKE %s OR u.user_login LIKE %s OR o.provider_order = %s)', (int) $s, $like, $like, $s);
        }
        $from = "FROM $t o LEFT JOIN {$wpdb->users} u ON u.ID = o.user_id LEFT JOIN " . ML_DB::t('servers') . " s ON s.id = o.server_id";
        $total = $wpdb->get_var("SELECT COUNT(*) $from WHERE $where");
        $rows = $wpdb->get_results("SELECT o.*, u.user_login, s.label AS server_label $from WHERE $where ORDER BY o.id DESC LIMIT " . (($page - 1) * $per) . ", $per");
        self::head('Đơn hàng');
        echo '<form method="get"><input type="hidden" name="page" value="ml-orders"><p class="search-box"><select name="status"><option value="">Tất cả trạng thái</option>';
        foreach (ML_Orders::statuses() as $k => $v) {
            echo '<option value="' . esc_attr($k) . '"' . selected($st, $k, false) . '>' . esc_html($v . ' (' . $k . ')') . '</option>';
        }
        echo '</select> <label><input type="checkbox" name="manual" value="1"' . checked(!empty($_GET['manual']), true, false) . '> Chỉ đơn thủ công</label> <input type="search" name="s" value="' . esc_attr($s) . '" placeholder="ID, link, thành viên"> <button class="button">Lọc</button></p></form>';
        echo '<table class="widefat striped ml-table"><thead><tr><th>#</th><th>Thành viên</th><th>Dịch vụ / Server</th><th>Link / Nội dung</th><th>SL</th><th>Tiền</th><th>Bắt đầu / Đã chạy</th><th>Trạng thái</th><th>NCC</th><th>Thời gian</th><th>Cập nhật</th></tr></thead><tbody>';
        foreach ($rows as $o) {
            $p = $o->params ? json_decode($o->params, true) : [];
            echo '<tr><td>' . (int) $o->id . ($o->parent_id ? '<br><small>gốc #' . (int) $o->parent_id . '</small>' : '') . '</td><td>' . self::user_link($o->user_id) . '<br><small>' . esc_html($o->source) . '</small></td>';
            echo '<td>' . esc_html(get_the_title($o->service_id)) . '<br><span class="ml-badge">' . esc_html($o->server_label) . '</span>' . (!empty($p['reaction']) ? ' <small>' . esc_html($p['reaction']) . '</small>' : '') . ($o->kind === 'vip' ? '<br><small>VIP ' . (int) $o->vip_package . ' / ' . (int) $o->days . ' ngày – hết hạn ' . esc_html($o->time_end) . '</small>' : '') . '</td>';
            echo '<td style="max-width:260px;word-break:break-all"><a href="' . esc_url($o->original_link ?: $o->uid) . '" target="_blank">' . esc_html($o->uid) . '</a>' . ($o->content ? '<details><summary>Nội dung</summary><pre style="white-space:pre-wrap">' . esc_html($o->content) . '</pre></details>' : '') . ($o->note ? '<br><small>Ghi chú: ' . esc_html($o->note) . '</small>' : '') . '</td>';
            echo '<td>' . ml_number($o->quantity) . '</td><td>' . ml_number($o->amount) . 'đ<br><small>' . ml_price($o->price) . 'đ</small></td>';
            echo '<td>' . ml_number($o->start_count) . ' / ' . ml_number($o->present) . '</td><td><span class="ml-badge ' . esc_attr($o->status) . '">' . esc_html(ml_status_label($o->status)) . '</span></td>';
            echo '<td>' . ($o->provider_order ? esc_html('#' . $o->provider_order) : '<em>thủ công</em>') . '</td><td>' . esc_html($o->created_at) . '</td><td>';
            ML_Admin::form_open('order_update');
            echo '<input type="hidden" name="order_id" value="' . (int) $o->id . '"><select name="status">';
            foreach (ML_Orders::statuses() as $k => $v) {
                echo '<option value="' . esc_attr($k) . '"' . selected($o->status, $k, false) . '>' . esc_html($v) . '</option>';
            }
            echo '</select><br><input type="number" name="start_count" value="' . (int) $o->start_count . '" style="width:80px" title="Bắt đầu"> <input type="number" name="present" value="' . (int) $o->present . '" style="width:80px" title="Đã chạy"><br>';
            echo '<input type="text" name="admin_note" value="' . esc_attr($o->admin_note) . '" placeholder="Admin note"><br><label><input type="checkbox" name="resend" value="1"> Gửi lại NCC</label> <button class="button button-small">Lưu</button></form>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        self::pager($total, $per, $page);
        echo '<p class="description">Đổi sang "Đã hủy" sẽ hoàn toàn bộ tiền; "Hoàn một phần" hoàn theo số lượng chưa chạy (Số lượng − Đã chạy).</p></div>';
    }

    public static function users()
    {
        $s = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $q = new WP_User_Query(['search' => $s ? '*' . $s . '*' : '', 'number' => 30, 'paged' => $page, 'orderby' => 'registered', 'order' => 'DESC']);
        self::head('Thành viên & số dư');
        echo '<form method="get"><input type="hidden" name="page" value="ml-users"><p class="search-box"><input type="search" name="s" value="' . esc_attr($s) . '" placeholder="Tên đăng nhập, email"> <button class="button">Tìm</button></p></form>';
        echo '<table class="widefat striped ml-table"><thead><tr><th>ID</th><th>Tài khoản</th><th>Số dư</th><th>Tổng nạp</th><th>Cấp bậc</th><th>Cộng / trừ tiền</th><th>Tuỳ chỉnh</th></tr></thead><tbody>';
        $levels = ML_Settings::levels();
        foreach ($q->get_results() as $u) {
            echo '<tr><td>' . (int) $u->ID . '</td><td><strong>' . esc_html($u->user_login) . '</strong><br><small>' . esc_html($u->user_email) . '<br>' . esc_html($u->user_registered) . '</small>' . (get_user_meta($u->ID, 'ml_banned', true) ? '<br><span class="ml-badge canceled">Đã khóa</span>' : '') . '</td>';
            echo '<td><b>' . ml_number(ML_Users::balance($u->ID)) . 'đ</b></td><td>' . ml_number(ML_Users::total_deposit($u->ID)) . 'đ</td><td>' . esc_html(ML_Users::level_name($u->ID)) . '</td><td>';
            ML_Admin::form_open('balance');
            echo '<input type="hidden" name="user_id" value="' . (int) $u->ID . '"><select name="sign"><option value="+">+</option><option value="-">−</option></select> <input type="number" name="amount" style="width:110px" placeholder="Số tiền"> <input type="text" name="note" placeholder="Lý do" style="width:140px"><br><label><input type="checkbox" name="as_deposit" value="1"> Tính là nạp tiền (lên cấp)</label> <button class="button button-small">Thực hiện</button></form></td><td>';
            ML_Admin::form_open('user_flags');
            echo '<input type="hidden" name="user_id" value="' . (int) $u->ID . '"><select name="level_fixed"><option value="0">Cấp theo tổng nạp</option>';
            foreach ($levels as $i => $l) {
                echo '<option value="' . ($i + 1) . '"' . selected((int) get_user_meta($u->ID, 'ml_level_fixed', true), $i + 1, false) . '>Cố định: ' . esc_html($l['name']) . '</option>';
            }
            echo '</select><br><input type="number" name="total_deposit" placeholder="Sửa tổng nạp" style="width:120px"> <label><input type="checkbox" name="banned" value="1"' . checked((int) get_user_meta($u->ID, 'ml_banned', true), 1, false) . '> Khóa</label> <button class="button button-small">Lưu</button></form></td></tr>';
        }
        echo '</tbody></table>';
        self::pager($q->get_total(), 30, $page);
        echo '</div>';
    }

    public static function payments()
    {
        global $wpdb;
        $t = ML_DB::t('payments');
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t");
        $rows = $wpdb->get_results("SELECT * FROM $t ORDER BY FIELD(status,'pending','unmatched') DESC, id DESC LIMIT " . (($page - 1) * 30) . ", 30");
        self::head('Nạp tiền');
        echo '<p>Giao dịch ngân hàng tự cộng qua webhook. Giao dịch "Chưa khớp" (sai nội dung) và thẻ cào cần duyệt tay bên dưới.</p>';
        echo '<table class="widefat striped ml-table"><thead><tr><th>#</th><th>Thành viên</th><th>Cổng</th><th>Số tiền</th><th>KM</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th>Xử lý</th></tr></thead><tbody>';
        foreach ($rows as $p) {
            echo '<tr><td>' . (int) $p->id . '</td><td>' . ($p->user_id ? self::user_link($p->user_id) : '—') . '</td><td>' . esc_html($p->gateway) . '</td><td>' . ml_number($p->amount) . 'đ</td><td>' . ml_number($p->bonus) . 'đ</td><td>' . esc_html($p->content) . '</td><td><span class="ml-badge ' . esc_attr($p->status) . '">' . esc_html($p->status) . '</span></td><td>' . esc_html($p->created_at) . '</td><td>';
            if (in_array($p->status, ['pending', 'unmatched'], true)) {
                ML_Admin::form_open('payment_approve');
                echo '<input type="hidden" name="payment_id" value="' . (int) $p->id . '">' . ($p->user_id ? '' : '<input type="text" name="user" placeholder="ID/tên đăng nhập" style="width:120px"> ') . '<input type="number" name="real_amount" placeholder="Số tiền thực cộng" style="width:130px"> <button class="button button-primary button-small">Duyệt</button></form>';
                ML_Admin::form_open('payment_reject');
                echo '<input type="hidden" name="payment_id" value="' . (int) $p->id . '"><button class="button button-small">Từ chối</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        self::pager($total, 30, $page);
        echo '</div>';
    }

    public static function transactions()
    {
        global $wpdb;
        $t = ML_DB::t('transactions');
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t");
        $rows = $wpdb->get_results("SELECT * FROM $t ORDER BY id DESC LIMIT " . (($page - 1) * 50) . ", 50");
        self::head('Lịch sử giao dịch');
        echo '<table class="widefat striped"><thead><tr><th>#</th><th>Thành viên</th><th>Loại</th><th>Số dư trước</th><th>Thay đổi</th><th>Số dư sau</th><th>Nội dung</th><th>Thời gian</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . (int) $r->id . '</td><td>' . self::user_link($r->user_id) . '</td><td>' . esc_html($r->type) . '</td><td>' . ml_number($r->balance_before) . '</td><td><b>' . ($r->amount > 0 ? '+' : '') . ml_number($r->amount) . '</b></td><td>' . ml_number($r->balance_after) . '</td><td>' . esc_html($r->note) . '</td><td>' . esc_html($r->created_at) . '</td></tr>';
        }
        echo '</tbody></table>';
        self::pager($total, 50, $page);
        echo '</div>';
    }

    public static function providers()
    {
        self::head('Nhà cung cấp API (SMM Panel v2)');
        echo '<p>Nhập API URL (VD: <code>https://domain-ncc.com/api/v2</code>) và API key. Sau đó vào từng Dịch vụ → bảng server để gắn nhà cung cấp + mã dịch vụ bên đó.</p>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Tên</th><th>API URL</th><th>API Key</th><th>Số dư</th><th>Hoạt động</th><th></th></tr></thead><tbody>';
        $list = ML_Providers::all();
        $list[] = (object) ['id' => 0, 'name' => '', 'api_url' => '', 'api_key' => '', 'balance' => '', 'active' => 1];
        foreach ($list as $p) {
            echo '<tr>';
            ML_Admin::form_open('provider');
            echo '<td>' . ($p->id ? (int) $p->id : 'Mới') . '<input type="hidden" name="id" value="' . (int) $p->id . '"></td><td><input type="text" name="name" value="' . esc_attr($p->name) . '"></td><td><input type="url" name="api_url" value="' . esc_attr($p->api_url) . '" style="width:280px"></td><td><input type="text" name="api_key" value="' . esc_attr($p->api_key) . '" style="width:220px"></td><td>' . esc_html($p->balance) . '</td><td><input type="checkbox" name="active" value="1"' . checked($p->active, 1, false) . '></td><td><button class="button button-primary button-small">' . ($p->id ? 'Lưu' : 'Thêm') . '</button> ';
            if ($p->id) {
                echo '<button class="button button-small" name="check" value="1">Kiểm tra số dư</button> <button class="button button-small" name="delete" value="1" onclick="return confirm(\'Xoá?\')">Xoá</button>';
            }
            echo '</td></form></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function settings()
    {
        $s = ML_Settings::all();
        self::head('Cài đặt MuaLikes');
        ML_Admin::form_open('settings');
        $groups = [
            'Chung' => ['site_name' => 'Tên website', 'logo_dark' => 'Logo sidebar (nền xanh)', 'logo_green' => 'Logo trang đăng nhập', 'favicon' => 'Favicon', 'color_primary' => 'Màu chính', 'color_secondary' => 'Màu phụ', 'footer_copyright' => 'Tên ở Copyright', 'footer_design' => 'Dòng "Design by"', 'google_analytics' => 'Google Analytics ID', 'remote_asset_base' => 'Nguồn ảnh gốc (khi chưa tải ảnh về)'],
            'Nạp tiền' => ['bank_name' => 'Tên ngân hàng', 'bank_bin' => 'Mã ngân hàng VietQR (VD: MB, VCB, ACB, 970422)', 'bank_account' => 'Số tài khoản', 'bank_owner' => 'Chủ tài khoản', 'transfer_prefix' => 'Tiền tố nội dung CK', 'min_deposit' => 'Nạp tối thiểu', 'promo_percent' => 'Khuyến mãi nạp (%)', 'promo_from' => 'KM từ ngày (YYYY-MM-DD)', 'promo_to' => 'KM đến ngày (YYYY-MM-DD)', 'promo_min' => 'KM áp dụng từ (đ)', 'sepay_api_key' => 'SePay API Key (webhook)', 'casso_secure_token' => 'Casso Secure Token', 'card_rate' => 'Chiết khấu thẻ cào nhận (%)', 'usdt_address' => 'Ví USDT (TRC20)', 'usdt_rate' => 'Tỷ giá USDT'],
            'Đơn hàng' => ['cancellation_fee' => 'Phí hủy đơn', 'max_guarantee_canceled' => 'Số lần từ chối bảo hành tối đa', 'max_multi_links' => 'Số link tối đa khi mua nhiều đơn', 'recent_orders_count' => 'Số đơn hiển thị ở "Đơn hàng mới"'],
        ];
        foreach ($groups as $title => $fields) {
            echo '<h2>' . esc_html($title) . '</h2><table class="form-table">';
            foreach ($fields as $k => $lb) {
                echo '<tr><th>' . esc_html($lb) . '</th><td><input type="text" class="regular-text" name="' . esc_attr($k) . '" value="' . esc_attr(is_scalar($s[$k]) ? $s[$k] : '') . '"></td></tr>';
            }
            echo '</table>';
        }
        echo '<h2>Nội dung</h2><table class="form-table">';
        echo '<tr><th>Băng chạy "Cảnh báo vi phạm" (mỗi dòng 1 câu)</th><td><textarea name="violation_texts" rows="3" class="large-text">' . esc_textarea($s['violation_texts']) . '</textarea></td></tr>';
        echo '<tr><th>Ghi chú cấp bậc</th><td><textarea name="level_notes" rows="3" class="large-text">' . esc_textarea($s['level_notes']) . '</textarea></td></tr>';
        echo '<tr><th>Cấp bậc (JSON)</th><td><textarea name="levels" rows="8" class="large-text code">' . esc_textarea(wp_json_encode($s['levels'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</textarea><p class="description">name, color, min (tổng nạp), perks. Giá theo cấp nhập ở từng server (cột Giá 1/2/3).</p></td></tr>';
        echo '<tr><th>Liên hệ hỗ trợ (JSON)</th><td><textarea name="support_links" rows="8" class="large-text code">' . esc_textarea(wp_json_encode($s['support_links'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</textarea></td></tr>';
        echo '<tr><th>Câu hỏi thường gặp (JSON: q, a)</th><td><textarea name="faq" rows="10" class="large-text code">' . esc_textarea(wp_json_encode($s['faq'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</textarea></td></tr>';
        echo '<tr><th>CSS tuỳ chỉnh</th><td><textarea name="custom_css" rows="5" class="large-text code">' . esc_textarea($s['custom_css']) . '</textarea></td></tr>';
        echo '<tr><th>Mã chèn &lt;head&gt;</th><td><textarea name="head_code" rows="4" class="large-text code">' . esc_textarea($s['head_code']) . '</textarea></td></tr>';
        echo '<tr><th>Tuỳ chọn</th><td><label><input type="checkbox" name="google_translate" value="1"' . checked($s['google_translate'], 1, false) . '> Nút "Chọn ngôn ngữ" (Google Translate)</label><br><label><input type="checkbox" name="api_enabled" value="1"' . checked($s['api_enabled'], 1, false) . '> Bật API đại lý /api/v2</label></td></tr>';
        echo '</table>';
        submit_button('Lưu cài đặt');
        echo '</form></div>';
    }

    public static function import()
    {
        self::head('Nhập dữ liệu từ MuaLikes.Net');
        echo '<p>Nhập toàn bộ dữ liệu đã trích xuất từ site gốc (thư mục <code>wp-content/plugins/mualikes-core/data</code>). Có thể chạy lại nhiều lần (cập nhật, không nhân bản).</p>';
        echo '<p><strong>Khuyên dùng WP-CLI</strong> cho bước blog (272 bài): <code>wp mualikes import --images</code></p>';
        ML_Admin::form_open('import');
        $steps = ['groups' => 'Nhóm dịch vụ', 'services' => '63 dịch vụ + server + giá 3 cấp', 'vip' => '9 dịch vụ VIP', 'notices' => 'Thông báo', 'pages' => 'Trang tĩnh + Trang chủ/Blog + đường dẫn', 'blog' => '272 bài viết blog', 'settings' => 'FAQ, liên hệ, cấp bậc'];
        foreach ($steps as $k => $lb) {
            echo '<label style="display:block"><input type="checkbox" name="steps[]" value="' . esc_attr($k) . '" checked> ' . esc_html($lb) . '</label>';
        }
        echo '<label style="display:block;margin-top:8px"><input type="checkbox" name="images" value="1"> Tải ảnh từ site gốc về máy chủ (lâu hơn)</label>';
        submit_button('Bắt đầu nhập');
        echo '</form></div>';
    }
}
