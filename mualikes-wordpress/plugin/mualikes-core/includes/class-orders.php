<?php
if (!defined('ABSPATH')) {
    exit;
}

class ML_Orders
{
    public static function statuses()
    {
        return [
            'pending' => 'Đang xử lý',
            'processing' => 'Đang xử lý',
            'running' => 'Đang chạy',
            'completed' => 'Đã xong',
            'partial' => 'Hoàn một phần',
            'paused' => 'Tạm dừng',
            'canceled' => 'Đã hủy',
            'refunded' => 'Đã hoàn tiền',
            'scheduled' => 'Chờ hẹn giờ',
            'active' => 'Đang chạy',
            'expired' => 'Hết hạn',
        ];
    }

    public static function get($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ML_DB::t('orders') . " WHERE id = %d", $id));
    }

    public static function update($id, array $data)
    {
        global $wpdb;
        $data['updated_at'] = ml_now();
        return $wpdb->update(ML_DB::t('orders'), $data, ['id' => (int) $id]);
    }

    private static function insert(array $row)
    {
        global $wpdb;
        $row['created_at'] = $row['updated_at'] = ml_now();
        $wpdb->insert(ML_DB::t('orders'), $row);
        return (int) $wpdb->insert_id;
    }

    private static function lines($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text);
        return array_values(array_filter(array_map('trim', $lines), 'strlen'));
    }

    /** Hệ số giá theo "Loại video" (select service_id có data-multiple) lấy từ form của dịch vụ. */
    private static function video_multiple($service_id, $value)
    {
        $html = ML_Services::meta($service_id, 'form_html');
        if ($value !== '' && preg_match('/<option[^>]*value="' . preg_quote((string) $value, '/') . '"[^>]*data-multiple="(\d+)"/', $html, $m)) {
            return max(1, (int) $m[1]);
        }
        return 1;
    }

    private static function allowed_values($service_id, $name)
    {
        $html = ML_Services::meta($service_id, 'form_html');
        if (!preg_match('/<select[^>]*name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/select>/s', $html, $m)) {
            return [];
        }
        preg_match_all('/<option[^>]*value="([^"]*)"/', $m[1], $o);
        return $o[1];
    }

    /**
     * Tạo đơn thường từ form #form-create (action=order-service).
     * @return array [bool, message|array]
     */
    public static function create_from_request($uid, array $in)
    {
        $server = ML_Services::server((int) ($in['serverId'] ?? 0));
        if (!$server) {
            return [false, 'Vui lòng chọn server!'];
        }
        $service_id = (int) $server->service_id;
        if (get_post_status($service_id) !== 'publish') {
            return [false, 'Dịch vụ không tồn tại'];
        }
        if (!$server->active) {
            return [false, 'Server đang bảo trì, vui lòng chọn server khác!'];
        }
        $attrs = is_array($server->attrs) ? $server->attrs : [];
        $level = ML_Users::level($uid);
        $price = ML_Services::server_price($server, $level);

        // ----- Danh sách link -----
        $targets = [];
        $is_multi = !empty($in['is_multiple_orders']);
        $custom_uid = $is_multi && !empty($in['custom_uid']);
        $qty = (int) ($in['number_seeding'] ?? ($in['count'] ?? 0));
        if (isset($in['count']) && $in['count'] !== '' && !isset($in['number_seeding'])) {
            $qty = (int) $in['count'];
        }
        if ($is_multi) {
            $rows = self::lines($in['post_link_multiple'] ?? '');
            $max_links = (int) ML_Settings::get('max_multi_links', 50);
            if (!$rows) {
                return [false, 'Vui lòng nhập link/UID!'];
            }
            if (count($rows) > $max_links) {
                return [false, 'Vui lòng nhập tối đa ' . $max_links . ' link!'];
            }
            foreach ($rows as $r) {
                if ($custom_uid) {
                    $parts = array_map('trim', explode('|', $r));
                    if (count($parts) < 2 || !ctype_digit($parts[1])) {
                        return [false, 'Vui lòng nhập đúng fomat bên dưới!'];
                    }
                    $targets[] = [$parts[0], (int) $parts[1]];
                } else {
                    $targets[] = [$r, $qty];
                }
            }
        } else {
            $link = trim((string) ($in['post_link'] ?? ''));
            if ($link === '') {
                return [false, 'Vui lòng nhập trường này!'];
            }
            $targets[] = [$link, $qty];
        }

        // ----- Nội dung bình luận / đánh giá -----
        $content = '';
        $comment_lines = self::lines($in['comments'] ?? ($in['content'] ?? ''));
        $uses_comment = (!empty($attrs['comment']) && $attrs['comment'] !== '0') || strpos(ML_Services::meta($service_id, 'form_html'), 'name="comments"') !== false;
        if ($comment_lines && $uses_comment) {
            $content = implode("\n", $comment_lines);
            foreach ($targets as &$t) {
                $t[1] = count($comment_lines);
            }
            unset($t);
        } elseif ($comment_lines) {
            $content = implode("\n", $comment_lines);
        }

        // ----- Cảm xúc -----
        $params = [];
        $allowed_reacts = array_filter(explode(',', (string) ($attrs['reactions'] ?? '')));
        if (!empty($in['reactions']) && is_array($in['reactions'])) {
            $r = array_values(array_intersect($in['reactions'], $allowed_reacts ?: $in['reactions']));
            if (!$r) {
                return [false, 'Vui lòng chọn cảm xúc!'];
            }
            $params['reaction'] = implode(',', $r);
        } elseif (!empty($in['reaction'])) {
            $r = sanitize_key($in['reaction']);
            if ($allowed_reacts && !in_array($r, $allowed_reacts, true)) {
                return [false, 'Cảm xúc không hợp lệ với server này!'];
            }
            $params['reaction'] = $r;
        }
        foreach (['speed', 'order-type', 'description', 'owner_link'] as $k) {
            if (isset($in[$k]) && $in[$k] !== '') {
                $params[$k] = sanitize_text_field($in[$k]);
            }
        }

        // ----- Hệ số giá -----
        $multiple = 1;
        if (isset($in['service_id']) && $in['service_id'] !== '' && !empty($attrs['video-by-time']) && $attrs['video-by-time'] !== '0') {
            $multiple = self::video_multiple($service_id, $in['service_id']);
            $params['video_type'] = sanitize_text_field($in['service_id']);
        }
        $ttl = 0;
        if (!empty($in['time_to_live'])) {
            $ttl = (int) $in['time_to_live'];
            $allowed = self::allowed_values($service_id, 'time_to_live');
            if ($allowed && !in_array((string) $ttl, $allowed, true)) {
                return [false, 'Thời gian duy trì không hợp lệ'];
            }
            $params['time_to_live'] = $ttl;
        }

        // ----- Hẹn giờ mua nhiều lần -----
        $runs = 1;
        $delay = 0;
        if (!empty($in['is_drip_feed']) && !empty($attrs['dripfeed']) && $attrs['dripfeed'] !== '0') {
            $runs = max(1, min(100, (int) ($in['total_order'] ?? 1)));
            $delay = max(5, (int) ($in['order_delay'] ?? 5));
        }

        // ----- Kiểm tra số lượng & tính tiền -----
        $total = 0;
        foreach ($targets as $t) {
            $q = (int) $t[1];
            if ($q <= 0) {
                return [false, 'Vui lòng nhập số lượng cần tăng!'];
            }
            if ($server->min_qty > 0 && $q < $server->min_qty) {
                return [false, 'Vui lòng nhập số lượng lớn hơn hoặc bằng ' . ml_number($server->min_qty) . '.'];
            }
            if ($server->max_qty > 0 && $q > $server->max_qty) {
                return [false, 'Vui lòng nhập số lượng nhỏ hơn hoặc bằng ' . ml_number($server->max_qty) . '.'];
            }
            $total += self::calc_amount($q, $price, $multiple, $ttl) * $runs;
        }
        $total = round($total);
        if ($total <= 0) {
            return [false, 'Số tiền không hợp lệ'];
        }

        // ----- Trừ tiền (nguyên tử) -----
        $title = get_the_title($service_id);
        [$ok, $msg] = ML_Users::change_balance($uid, -$total, 'order', 'Mua ' . $title . ' - ' . $server->label);
        if (!$ok) {
            return [false, $msg];
        }
        $tx = ML_Users::$last_tx;

        $note = mb_substr(sanitize_text_field($in['note'] ?? ''), 0, 255);
        $original = esc_url_raw($in['originalLink'] ?? '') ?: '';
        $first_id = 0;
        $created = [];
        foreach ($targets as $t) {
            $parent = 0;
            for ($i = 0; $i < $runs; $i++) {
                $amount = round(self::calc_amount($t[1], $price, $multiple, $ttl));
                $id = self::insert([
                    'user_id' => $uid, 'service_id' => $service_id, 'server_id' => $server->id, 'parent_id' => $parent,
                    'kind' => 'normal', 'uid' => mb_substr(sanitize_text_field($t[0]), 0, 500),
                    'original_link' => mb_substr($is_multi ? sanitize_text_field($t[0]) : ($original ?: sanitize_text_field($t[0])), 0, 1000),
                    'content' => $content, 'params' => wp_json_encode($params), 'quantity' => (int) $t[1],
                    'price' => $price, 'amount' => $amount, 'status' => $i === 0 ? 'pending' : 'scheduled',
                    'note' => $note, 'provider_id' => (int) $server->provider_id,
                    'run_at' => $i === 0 ? null : date('Y-m-d H:i:s', current_time('timestamp') + $i * $delay * 60),
                ]);
                if ($i === 0) {
                    $parent = $id;
                    $created[] = $id;
                }
                if (!$first_id) {
                    $first_id = $id;
                }
            }
        }
        self::link_tx($tx, $first_id);

        foreach ($created as $id) {
            self::dispatch($id);
        }
        $o = self::get($first_id);
        return [true, [
            'orderId' => count($created) > 1 ? implode(', ', $created) : $first_id,
            'price' => ml_price($price) . 'đ',
            'amount' => (int) $total,
            'status' => ml_status_label($o->status),
            'created' => ml_js_date($o->created_at),
        ]];
    }

    private static function link_tx($tx_id, $order_id)
    {
        global $wpdb;
        if ($tx_id) {
            $wpdb->query($wpdb->prepare("UPDATE " . ML_DB::t('transactions') . " SET order_id = %d, note = CONCAT(note, %s) WHERE id = %d", $order_id, ' #' . $order_id, $tx_id));
        }
    }

    public static function calc_amount($qty, $price, $multiple = 1, $ttl = 0)
    {
        $a = $qty * $price * max(1, $multiple);
        if ($ttl > 0) {
            $a *= $ttl;
        }
        return $a;
    }

    /** Đẩy đơn sang nhà cung cấp (nếu server có cấu hình). */
    public static function dispatch($order_id)
    {
        $o = self::get($order_id);
        if (!$o || !in_array($o->status, ['pending', 'active'], true) || $o->provider_order) {
            return;
        }
        $server = ML_Services::server($o->server_id);
        if (!$server || !$server->provider_id || !$server->provider_service) {
            return; // xử lý thủ công
        }
        [$ok, $res] = ML_Providers::place_order($o, $server);
        if ($ok && !empty($res['order'])) {
            self::update($o->id, ['provider_id' => $server->provider_id, 'provider_order' => (string) $res['order'], 'status' => $o->kind === 'vip' ? 'active' : 'processing']);
        } else {
            self::update($o->id, ['admin_note' => mb_substr('Lỗi gửi đơn: ' . (is_string($res) ? $res : wp_json_encode($res)), 0, 1000)]);
            do_action('ml_order_dispatch_failed', $o, $res);
        }
    }

    /* ------------------------------ VIP ------------------------------ */

    public static function create_vip($uid, array $in)
    {
        $server = ML_Services::server((int) ($in['serverId'] ?? 0));
        if (!$server || !ML_Services::is_vip($server->service_id)) {
            return [false, 'Vui lòng chọn server!'];
        }
        if (!$server->active) {
            return [false, 'Server đang bảo trì, vui lòng chọn server khác!'];
        }
        $service_id = (int) $server->service_id;
        $link = trim(sanitize_text_field($in['post_link'] ?? ''));
        if ($link === '') {
            return [false, 'Vui lòng nhập ID hoặc link cần chạy!'];
        }
        $pkg = (int) ($in['vip_package'] ?? 0);
        $days = (int) ($in['days'] ?? 30);
        $pk_allowed = self::allowed_values($service_id, 'vip_package');
        $day_allowed = self::allowed_values($service_id, 'days');
        if ($pk_allowed && !in_array((string) $pkg, $pk_allowed, true)) {
            return [false, 'Gói VIP không hợp lệ'];
        }
        if ($day_allowed && !in_array((string) $days, $day_allowed, true)) {
            return [false, 'Thời gian không hợp lệ'];
        }
        if ($pkg <= 0 || $days <= 0) {
            return [false, 'Vui lòng chọn gói VIP!'];
        }
        $attrs = is_array($server->attrs) ? $server->attrs : [];
        $max_post_mult = 1;
        $params = [];
        if (!empty($attrs['max-post']) && $attrs['max-post'] === '1') {
            $mp = (int) ($in['max_post'] ?? 5);
            $max_post_mult = max(1, (int) floor($mp / 5));
            $params['max_post'] = $mp;
        }
        $price = (int) ML_Services::server_price($server, ML_Users::level($uid)); // JS gốc dùng parseInt
        $amount = round($pkg * ($days / 30) * $price) * $max_post_mult;
        $comments = self::lines($in['comments'] ?? '');
        [$ok, $msg] = ML_Users::change_balance($uid, -$amount, 'order', 'Mua ' . get_the_title($service_id) . ' - ' . $server->label);
        if (!$ok) {
            return [false, $msg];
        }
        $tx = ML_Users::$last_tx;
        $id = self::insert([
            'user_id' => $uid, 'service_id' => $service_id, 'server_id' => $server->id, 'kind' => 'vip',
            'uid' => mb_substr($link, 0, 500), 'original_link' => mb_substr($link, 0, 1000),
            'content' => implode("\n", $comments), 'params' => wp_json_encode($params), 'quantity' => $pkg,
            'price' => $price, 'amount' => $amount, 'status' => 'active', 'note' => mb_substr(sanitize_text_field($in['note'] ?? ''), 0, 255),
            'vip_package' => $pkg, 'days' => $days, 'provider_id' => (int) $server->provider_id,
            'time_end' => date('Y-m-d H:i:s', current_time('timestamp') + $days * DAY_IN_SECONDS),
        ]);
        self::link_tx($tx, $id);
        $o = self::get($id);
        if ($server->provider_id && $server->provider_service) {
            $o->status = 'active';
            [$pok, $res] = ML_Providers::place_order($o, $server);
            if ($pok && !empty($res['order'])) {
                self::update($id, ['provider_order' => (string) $res['order']]);
            } else {
                self::update($id, ['admin_note' => 'Lỗi gửi đơn: ' . (is_string($res) ? $res : wp_json_encode($res))]);
            }
        }
        return [true, [
            'orderId' => $id, 'price' => ml_number($price) . 'đ', 'amount' => (int) $amount,
            'status' => ml_status_label('active'), 'created' => ml_js_date($o->created_at),
        ]];
    }

    /** Hủy VIP: hoàn tiền số ngày còn lại trừ 7 ngày phí. */
    public static function refund_vip($uid, $order_id)
    {
        $o = self::get($order_id);
        if (!$o || (int) $o->user_id !== (int) $uid || $o->kind !== 'vip') {
            return [false, 'Không tìm thấy đơn hàng'];
        }
        if ($o->status !== 'active') {
            return [false, 'Đơn hàng không thể hủy'];
        }
        $remain_days = max(0, floor((strtotime($o->time_end) - current_time('timestamp')) / DAY_IN_SECONDS) - 7);
        $refund = $o->days > 0 ? round($o->amount * $remain_days / $o->days) : 0;
        if ($o->provider_order) {
            ML_Providers::cancel($o->provider_id, $o->provider_order);
        }
        self::update($o->id, ['status' => 'canceled', 'admin_note' => 'Khách hủy, hoàn ' . ml_number($refund) . 'đ']);
        if ($refund > 0) {
            ML_Users::change_balance($uid, $refund, 'refund', 'Hoàn tiền hủy đơn #' . $o->id, $o->id);
        }
        return [true, 'Đã hủy đơn #' . $o->id . ($refund > 0 ? ', hoàn ' . ml_number($refund) . 'đ vào tài khoản.' : '. Đơn hàng không đủ điều kiện hoàn tiền.')];
    }

    public static function renew_vip($uid, $order_id, $days)
    {
        $o = self::get($order_id);
        $days = (int) $days;
        if (!$o || (int) $o->user_id !== (int) $uid || $o->kind !== 'vip') {
            return [false, 'Không tìm thấy đơn hàng'];
        }
        if ($days < 30) {
            return [false, 'Số ngày gia hạn tối thiểu là 30'];
        }
        $server = ML_Services::server($o->server_id);
        if (!$server || !$server->active) {
            return [false, 'Server đang bảo trì, không thể gia hạn'];
        }
        $p = $o->params ? json_decode($o->params, true) : [];
        $mult = !empty($p['max_post']) ? max(1, floor($p['max_post'] / 5)) : 1;
        $price = (int) ML_Services::server_price($server, ML_Users::level($uid));
        $amount = round($o->vip_package * ($days / 30) * $price) * $mult;
        [$ok, $msg] = ML_Users::change_balance($uid, -$amount, 'order', 'Gia hạn đơn #' . $o->id . ' thêm ' . $days . ' ngày', $o->id);
        if (!$ok) {
            return [false, $msg];
        }
        $base = max(current_time('timestamp'), strtotime($o->time_end));
        self::update($o->id, [
            'days' => $o->days + $days, 'amount' => $o->amount + $amount, 'status' => 'active',
            'time_end' => date('Y-m-d H:i:s', $base + $days * DAY_IN_SECONDS),
        ]);
        return [true, 'Gia hạn thành công đơn #' . $o->id . ' thêm ' . $days . ' ngày (' . ml_number($amount) . 'đ).'];
    }

    /* ------------------------------ Bảo hành / hoàn tiền ------------------------------ */

    public static function guarantee($uid, $order_id)
    {
        $o = self::get($order_id);
        if (!$o || (int) $o->user_id !== (int) $uid) {
            return [false, 'Không tìm thấy đơn hàng'];
        }
        $max = (int) ML_Settings::get('max_guarantee_canceled', 3);
        if ($o->guarantee_rejected >= $max) {
            return [false, 'Đơn hàng đã bị từ chối bảo hành quá ' . $max . ' lần.'];
        }
        if ($o->status !== 'completed') {
            return [false, 'Chỉ đơn hàng đã hoàn thành mới được bảo hành.'];
        }
        $server = ML_Services::server($o->server_id);
        if (!$server || !$server->refill) {
            return [false, 'Server này không hỗ trợ bảo hành.'];
        }
        if ($o->provider_order && $server->provider_id) {
            [$ok, $res] = ML_Providers::refill($server->provider_id, $o->provider_order);
            if (!$ok) {
                self::update($o->id, ['guarantee_rejected' => $o->guarantee_rejected + 1, 'admin_note' => 'Bảo hành bị từ chối: ' . $res]);
                return [false, 'Bảo hành bị từ chối: ' . $res];
            }
        }
        self::update($o->id, ['guarantee_count' => $o->guarantee_count + 1, 'status' => 'running', 'admin_note' => 'Đã tạo bảo hành lúc ' . date_i18n('H:i d/m/Y')]);
        return [true, 'Tạo bảo hành thành công cho đơn #' . $o->id];
    }

    /**
     * Đổi trạng thái đơn (admin hoặc đồng bộ nhà cung cấp) và tự hoàn tiền khi Hủy/Hoàn một phần.
     */
    public static function set_status($order_id, $status, $remains = null, $note = '')
    {
        $o = self::get($order_id);
        if (!$o || $o->status === $status) {
            return;
        }
        $data = ['status' => $status];
        if ($note) {
            $data['admin_note'] = $note;
        }
        $refund = 0;
        if (in_array($status, ['canceled', 'refunded'], true) && !in_array($o->status, ['canceled', 'refunded', 'partial'], true)) {
            $refund = (float) $o->amount;
        } elseif ($status === 'partial' && $remains !== null && $o->quantity > 0 && !in_array($o->status, ['canceled', 'refunded', 'partial'], true)) {
            $refund = round($o->amount * min($o->quantity, max(0, (int) $remains)) / $o->quantity);
            $data['remains'] = (int) $remains;
        }
        self::update($o->id, $data);
        if ($refund > 0) {
            ML_Users::change_balance($o->user_id, $refund, 'refund', 'Hoàn tiền đơn #' . $o->id, $o->id);
            if ($status === 'canceled') {
                // Hủy luôn các lần hẹn giờ còn lại
                global $wpdb;
                $children = $wpdb->get_col($wpdb->prepare("SELECT id FROM " . ML_DB::t('orders') . " WHERE parent_id = %d AND status = 'scheduled'", $o->id));
                foreach ($children as $cid) {
                    self::set_status($cid, 'canceled', null, 'Hủy theo đơn gốc #' . $o->id);
                }
            }
        }
        do_action('ml_order_status_changed', $o, $status);
    }

    /* ------------------------------ Cron ------------------------------ */

    public static function release_scheduled()
    {
        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM " . ML_DB::t('orders') . " WHERE status = 'scheduled' AND run_at <= %s LIMIT 50", ml_now()));
        foreach ($ids as $id) {
            self::update($id, ['status' => 'pending']);
            self::dispatch($id);
        }
    }

    public static function sync_provider_status()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . ML_DB::t('orders') . " WHERE provider_order <> '' AND kind = 'normal' AND status IN ('pending','processing','running') ORDER BY updated_at ASC LIMIT 100");
        foreach ($rows as $o) {
            [$ok, $res] = ML_Providers::status($o->provider_id, $o->provider_order);
            if (!$ok) {
                self::update($o->id, []);
                continue;
            }
            $st = ML_Providers::map_status($res['status'] ?? '');
            $start = (int) ($res['start_count'] ?? $o->start_count);
            $remains = isset($res['remains']) ? (int) $res['remains'] : null;
            $present = $remains !== null ? max(0, $o->quantity - $remains) : $o->present;
            self::update($o->id, ['start_count' => $start, 'present' => $present]);
            if ($st !== $o->status) {
                self::set_status($o->id, $st, $remains);
            }
        }
    }

    public static function expire_vip()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE " . ML_DB::t('orders') . " SET status = 'expired', updated_at = %s WHERE kind = 'vip' AND status = 'active' AND time_end < %s", ml_now(), ml_now()));
    }

    /** Thống kê đơn hàng mới hiển thị ở trang chủ. */
    public static function recent($limit = 50)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.id, o.quantity, o.created_at, s.label, u.user_login FROM " . ML_DB::t('orders') . " o
             LEFT JOIN " . ML_DB::t('servers') . " s ON s.id = o.server_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.user_id
             WHERE o.kind = 'normal' AND o.parent_id = 0 ORDER BY o.id DESC LIMIT %d",
            $limit
        ));
    }
}
