<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Endpoint /server/graph-api?action=... và /server/orders?action=... – tương thích 100% với JS gốc
 * (common.js, custom.js, service.js, order.js, custom-service.js). Mọi phản hồi dạng {status, message}.
 */
class ML_Graph_API
{
    /** Các action cần đăng nhập + kiểm tra nonce chống CSRF (header X-ML-Nonce do theme gắn qua $.ajaxSetup). */
    private static $auth_actions = [
        'order-service', 'change-password', 'change-api-token', 'guarantee-order', 'refund', 'renew',
        'orders-multiple', 'active-affiliate', 'payment-card', 'form-add-domain', 'update-profile',
    ];

    public static function handle()
    {
        $action = sanitize_key(ml_request('action', ''));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !in_array($action, ['bang-gia'], true)) {
            ml_json(false, 'Method not allowed', [], 405);
        }
        if (in_array($action, self::$auth_actions, true)) {
            if (!is_user_logged_in()) {
                ml_json(false, 'Vui lòng <a href="/auth/login">đăng nhập</a> hoặc <a href="/auth/register">đăng ký</a> để sử dụng dịch vụ!');
            }
            self::verify_nonce();
        }
        $method = 'a_' . str_replace('-', '_', $action);
        if (!method_exists(__CLASS__, $method)) {
            ml_json(false, 'Hành động không hợp lệ');
        }
        self::$method();
    }

    /** /server/orders?action=order-vip-* (form[creater-orders]) */
    public static function handle_orders()
    {
        if (!is_user_logged_in()) {
            ml_json(false, 'Vui lòng đăng nhập để sử dụng dịch vụ!');
        }
        self::verify_nonce();
        $action = sanitize_key(ml_request('action', ''));
        if (strpos($action, 'order-vip') !== 0) {
            ml_json(false, 'Hành động không hợp lệ');
        }
        [$ok, $msg] = ML_Orders::create_vip(get_current_user_id(), wp_unslash($_POST));
        ml_json($ok, $msg);
    }

    private static function verify_nonce()
    {
        $n = $_SERVER['HTTP_X_ML_NONCE'] ?? ($_POST['_ml_nonce'] ?? '');
        if (!wp_verify_nonce($n, 'ml_action')) {
            ml_json(false, 'Phiên làm việc đã hết hạn, vui lòng tải lại trang!');
        }
    }

    private static function throttle($key, $max = 10, $window = 600)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
        $k = 'ml_rl_' . md5($key . $ip);
        $n = (int) get_transient($k);
        if ($n >= $max) {
            ml_json(false, 'Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút.');
        }
        set_transient($k, $n + 1, $window);
    }

    /* ------------------------------ Tài khoản ------------------------------ */

    private static function a_form_login_account()
    {
        self::throttle('login', 15);
        $login = sanitize_text_field(ml_request('username'));
        $pass = (string) ml_request('password');
        if ($login === '' || $pass === '') {
            ml_json(false, 'Vui lòng nhập tên đăng nhập và mật khẩu!');
        }
        if (is_email($login)) {
            $u = get_user_by('email', $login);
            if ($u) {
                $login = $u->user_login;
            }
        }
        $user = wp_signon(['user_login' => $login, 'user_password' => $pass, 'remember' => true], is_ssl());
        if (is_wp_error($user)) {
            ml_json(false, 'Tên đăng nhập hoặc mật khẩu không chính xác!');
        }
        if (get_user_meta($user->ID, 'ml_banned', true)) {
            wp_logout();
            ml_json(false, 'Tài khoản của bạn đã bị khóa!');
        }
        ml_json(true, 'Đăng nhập thành công!');
    }

    private static function a_register_account()
    {
        self::throttle('register', 5, 3600);
        if (!get_option('users_can_register') && !apply_filters('ml_allow_register', true)) {
            ml_json(false, 'Hệ thống tạm dừng đăng ký!');
        }
        $username = sanitize_user(ml_request('username'), true);
        $email = sanitize_email(ml_request('email'));
        $pass = (string) ml_request('password');
        $pass2 = (string) ml_request('password_confirmation');
        if (strlen($username) < 4 || strlen($username) > 50) {
            ml_json(false, 'Tên đăng nhập phải từ 4 - 50 ký tự!');
        }
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
            ml_json(false, 'Tên đăng nhập chỉ gồm chữ cái không dấu, số, dấu chấm hoặc gạch dưới!');
        }
        if (!is_email($email)) {
            ml_json(false, 'Email không hợp lệ!');
        }
        if (strlen($pass) < 6) {
            ml_json(false, 'Mật khẩu tối thiểu 6 ký tự!');
        }
        if ($pass !== $pass2) {
            ml_json(false, 'Mật khẩu nhập lại không khớp!');
        }
        if (empty($_POST['terms'])) {
            ml_json(false, 'Bạn cần đồng ý với điều khoản dịch vụ!');
        }
        if (username_exists($username)) {
            ml_json(false, 'Tên đăng nhập đã tồn tại!');
        }
        if (email_exists($email)) {
            ml_json(false, 'Email đã được sử dụng!');
        }
        $uid = wp_insert_user(['user_login' => $username, 'user_email' => $email, 'user_pass' => $pass, 'role' => 'subscriber', 'display_name' => $username]);
        if (is_wp_error($uid)) {
            ml_json(false, $uid->get_error_message());
        }
        $ref = (int) ($_COOKIE['ml_ref'] ?? 0);
        if ($ref && get_userdata($ref)) {
            update_user_meta($uid, 'ml_referrer', $ref);
        }
        wp_set_current_user($uid);
        wp_set_auth_cookie($uid, true, is_ssl());
        ml_json(true, 'Đăng ký tài khoản thành công!');
    }

    private static function a_forgot_password()
    {
        self::throttle('forgot', 5, 3600);
        $email = sanitize_email(ml_request('email'));
        $u = get_user_by('email', $email);
        if (!$u) {
            ml_json(false, 'Email không tồn tại trong hệ thống!');
        }
        $r = retrieve_password($u->user_login);
        if (is_wp_error($r)) {
            ml_json(false, 'Không gửi được email, vui lòng liên hệ hỗ trợ!');
        }
        ml_json(true, 'Vui lòng kiểm tra email để đặt lại mật khẩu!');
    }

    private static function a_change_password()
    {
        $u = wp_get_current_user();
        $old = (string) ml_request('old_password', ml_request('current_password'));
        $new = (string) ml_request('password', ml_request('new_password'));
        $new2 = (string) ml_request('password_confirmation', ml_request('confirm_password'));
        if (!wp_check_password($old, $u->user_pass, $u->ID)) {
            ml_json(false, 'Mật khẩu hiện tại không đúng!');
        }
        if (strlen($new) < 6) {
            ml_json(false, 'Mật khẩu mới tối thiểu 6 ký tự!');
        }
        if ($new !== $new2) {
            ml_json(false, 'Mật khẩu nhập lại không khớp!');
        }
        wp_set_password($new, $u->ID);
        wp_set_auth_cookie($u->ID, true, is_ssl());
        ml_json(true, 'Đổi mật khẩu thành công!');
    }

    private static function a_update_profile()
    {
        $uid = get_current_user_id();
        $email = sanitize_email(ml_request('email'));
        $data = ['ID' => $uid];
        if ($email && is_email($email)) {
            $o = email_exists($email);
            if ($o && (int) $o !== $uid) {
                ml_json(false, 'Email đã được sử dụng!');
            }
            $data['user_email'] = $email;
        }
        $name = sanitize_text_field(ml_request('name'));
        if ($name) {
            $data['display_name'] = $name;
        }
        wp_update_user($data);
        update_user_meta($uid, 'ml_phone', sanitize_text_field(ml_request('phone')));
        ml_json(true, 'Cập nhật thông tin thành công!');
    }

    private static function a_change_api_token()
    {
        ml_json(true, ML_Users::api_token(get_current_user_id(), true));
    }

    /* ------------------------------ Đơn hàng ------------------------------ */

    private static function a_order_service()
    {
        [$ok, $msg] = ML_Orders::create_from_request(get_current_user_id(), wp_unslash($_POST));
        ml_json($ok, $msg);
    }

    private static function a_guarantee_order()
    {
        [$ok, $msg] = ML_Orders::guarantee(get_current_user_id(), (int) ml_request('orderId'));
        ml_json($ok, $msg);
    }

    private static function a_refund()
    {
        [$ok, $msg] = ML_Orders::refund_vip(get_current_user_id(), (int) ml_request('orderId'));
        ml_json($ok, $msg);
    }

    private static function a_renew()
    {
        [$ok, $msg] = ML_Orders::renew_vip(get_current_user_id(), (int) ml_request('orderId'), (int) ml_request('days', 30));
        ml_json($ok, $msg);
    }

    private static function a_orders_multiple()
    {
        global $wpdb;
        $id = (int) ml_request('orderId');
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . ML_DB::t('orders') . " WHERE (id = %d OR parent_id = %d) AND user_id = %d ORDER BY id ASC", $id, $id, get_current_user_id()));
        $h = '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Uid</th><th>Số lượng</th><th>Thời gian chạy</th><th>Trạng thái</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $h .= '<tr><td>' . (int) $r->id . '</td><td>' . esc_html($r->uid) . '</td><td>' . ml_number($r->quantity) . '</td><td>'
                . esc_html($r->run_at ? date_i18n('H:i d/m/Y', strtotime($r->run_at)) : date_i18n('H:i d/m/Y', strtotime($r->created_at))) . '</td><td>' . ml_status_badge($r->status) . '</td></tr>';
        }
        $h .= '</tbody></table>';
        ml_json(true, $h);
    }

    /* ------------------------------ Tiện ích link ------------------------------ */

    private static function a_get_link_facebook()
    {
        $link = esc_url_raw(trim((string) ml_request('post_link')));
        if (!$link) {
            ml_json(false, 'Link không hợp lệ');
        }
        $final = $link;
        if (preg_match('#(fb\.watch|facebook\.com/share/)#', $link)) {
            $url = $link;
            for ($i = 0; $i < 5; $i++) {
                $res = wp_remote_get($url, ['timeout' => 12, 'redirection' => 0, 'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36']);
                if (is_wp_error($res)) {
                    break;
                }
                $loc = wp_remote_retrieve_header($res, 'location');
                if ($loc) {
                    $url = is_array($loc) ? end($loc) : $loc;
                    if (self::fb_uid($url)) {
                        break;
                    }
                    continue;
                }
                $body = wp_remote_retrieve_body($res);
                if (preg_match('#<link rel="canonical" href="([^"]+)"#', $body, $m)) {
                    $url = html_entity_decode($m[1]);
                }
                break;
            }
            $final = $url;
        }
        $uid = self::fb_uid($final);
        ml_json(true, $uid ?: $link);
    }

    private static function a_get_username()
    {
        $link = trim((string) ml_request('post_link'));
        if (preg_match('/id=(\d+)/', $link, $m)) {
            ml_json(true, ['id' => $m[1], 'name' => 'Tên khách hàng']);
        }
        if (preg_match('#(?:facebook|fb)\.com/([^/?&]+)#', $link, $m)) {
            ml_json(true, ['id' => $m[1], 'name' => 'Tên khách hàng']);
        }
        ml_json(true, ['id' => sanitize_text_field($link), 'name' => 'Tên khách hàng']);
    }

    private static function fb_uid($u)
    {
        $pat = [
            '#/posts/(?:[^/]+/)?(pfbid\w+|\d+)#', '#[?&]story_fbid=(pfbid\w+|\d+)#', '#[?&]fbid=(\d+)#', '#/videos/(?:[^/]+/)?(\d+)#',
            '#/reel/(\d+)#', '#/permalink/(\d+)#', '#[?&]v=(\d+)#', '#/photos/[^/]+/(\d+)#', '#multi_permalinks=(\d+)#',
        ];
        foreach ($pat as $p) {
            if (preg_match($p, $u, $m)) {
                return $m[1];
            }
        }
        return '';
    }

    /* ------------------------------ Bảng giá chèn trong bài viết ------------------------------ */

    private static function a_bang_gia()
    {
        $cls = sanitize_text_field(ml_request('service'));
        $map = [
            'bang-gia-facebook' => 'facebook', 'bang-gia-tiktok' => 'tiktok', 'bang-gia-youtube' => 'youtube', 'bang-gia-twitter' => 'twitter',
            'bang-gia-instagram' => 'instagram', 'bang-gia-telegram' => 'telegram', 'bang-gia-threads' => 'threads', 'bang-gia-shopee' => 'shopee', 'bang-gia-khac' => 'google',
        ];
        $anchor = $map[trim($cls)] ?? '';
        $group = null;
        foreach (ML_Services::menu() as $g) {
            if ($g['anchor'] === $anchor) {
                $group = $g;
            }
        }
        if (!$group) {
            ml_json(false, '');
        }
        $levels = ML_Settings::levels();
        $h = '<div class="table-responsive"><table class="table table-bordered table-striped text-center"><thead><tr><th class="text-left">Dịch vụ</th>';
        foreach ($levels as $l) {
            $h .= '<th>' . esc_html($l['name']) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($group['items'] as $it) {
            $servers = ML_Services::servers($it['id']);
            $h .= '<tr><td class="text-left"><a href="' . esc_url($it['url']) . '">' . esc_html($it['title']) . '</a></td>';
            foreach ($levels as $i => $l) {
                $h .= '<td>';
                foreach ($servers as $s) {
                    $h .= esc_html($s->label) . ': <span class="font-weight-bold cl-red">' . ml_price(ML_Services::server_price($s, $i + 1)) . ' đ</span><br>';
                }
                $h .= '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</tbody></table></div>';
        ml_json(true, $h);
    }

    /* ------------------------------ Nạp tiền ------------------------------ */

    private static function a_payment_card()
    {
        [$ok, $msg] = ML_Payments::submit_card(get_current_user_id(), wp_unslash($_POST));
        ml_json($ok, $msg);
    }

    /* ------------------------------ Khác ------------------------------ */

    private static function a_active_affiliate()
    {
        update_user_meta(get_current_user_id(), 'ml_affiliate', 1);
        ml_json(true, 'Kích hoạt tiếp thị liên kết thành công!');
    }

    private static function a_form_add_domain()
    {
        ml_json(false, 'Tính năng tạo website đại lý chưa được hỗ trợ. Vui lòng liên hệ hỗ trợ!');
    }
}
