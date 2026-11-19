<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nguồn dữ liệu server-side cho DataTables (initDataTable trong common.js gọi /server/{action}).
 */
class ML_DataTables
{
    public static function handle($action)
    {
        $uid = get_current_user_id();
        if (!$uid) {
            wp_send_json(['draw' => (int) ml_request('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Vui lòng đăng nhập!']);
        }
        switch ($action) {
            case 'order-list':
                self::orders($uid, 'normal');
                break;
            case 'order-list-vip':
                self::orders($uid, 'vip');
                break;
            case 'payment-history':
                self::payments($uid);
                break;
            case 'transaction-history':
                self::transactions($uid);
                break;
            default:
                wp_send_json(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        }
    }

    private static function paging()
    {
        $start = max(0, (int) ml_request('start', 0));
        $len = (int) ml_request('length', 20);
        $len = $len <= 0 ? 20 : min(500, $len);
        $search = '';
        if (isset($_GET['search']['value'])) {
            $search = sanitize_text_field(wp_unslash($_GET['search']['value']));
        }
        $dir = 'DESC';
        if (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') {
            $dir = 'ASC';
        }
        return [$start, $len, $search, $dir];
    }

    private static function out($total, $filtered, $data)
    {
        wp_send_json([
            'draw' => (int) ml_request('draw', 0),
            'recordsTotal' => (int) $total,
            'recordsFiltered' => (int) $filtered,
            'data' => $data,
        ]);
    }

    private static function orders($uid, $kind)
    {
        global $wpdb;
        [$start, $len, $search, $dir] = self::paging();
        $t = ML_DB::t('orders');
        $where = $wpdb->prepare("o.user_id = %d AND o.kind = %s", $uid, $kind);
        $service = (int) ml_request('order_id', 0);
        if ($service) {
            $where .= $wpdb->prepare(" AND o.service_id = %d", $service);
        }
        $vip_type = sanitize_text_field(ml_request('vip_type', ''));
        if ($vip_type) {
            $sid = (int) $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ml_vip_type' AND meta_value = %s LIMIT 1", $vip_type));
            $where .= $wpdb->prepare(" AND o.service_id = %d", $sid);
        }
        $service_type = sanitize_text_field(ml_request('serviceType', ''));
        if ($service_type) {
            $sid = (int) $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ml_service_type' AND meta_value = %s LIMIT 1", $service_type));
            if ($sid) {
                $where .= $wpdb->prepare(" AND o.service_id = %d", $sid);
            }
        }
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t o WHERE $where");
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (o.id = %d OR o.uid LIKE %s OR o.note LIKE %s OR o.original_link LIKE %s)", (int) $search, $like, $like, $like);
        }
        $filtered = $wpdb->get_var("SELECT COUNT(*) FROM $t o WHERE $where");
        $rows = $wpdb->get_results("SELECT o.*, s.label AS server_label, s.refill AS server_refill FROM $t o LEFT JOIN " . ML_DB::t('servers') . " s ON s.id = o.server_id WHERE $where ORDER BY o.id $dir LIMIT $start, $len");
        $max_rej = (int) ML_Settings::get('max_guarantee_canceled', 3);
        $data = [];
        foreach ($rows as $o) {
            $title = get_the_title($o->service_id);
            $uid_html = '<span class="copy text-primary" role="button" data-clipboard-text="' . esc_attr($o->uid) . '" title="Sao chép">' . esc_html(mb_strimwidth($o->uid, 0, 40, '...')) . '</span>';
            if ($o->original_link && $o->original_link !== $o->uid) {
                $uid_html .= ' <a href="' . esc_url($o->original_link) . '" target="_blank" rel="nofollow"><i class="fas fa-external-link-alt"></i></a>';
            }
            $server = '<span class="badge bg-info fw-bold text-white">' . esc_html($o->server_label) . '</span><br><small>' . esc_html($title) . '</small>';
            $p = $o->params ? json_decode($o->params, true) : [];
            if (!empty($p['reaction'])) {
                $server .= '<br><small>Cảm xúc: ' . esc_html($p['reaction']) . '</small>';
            }
            $action = '';
            if ($kind === 'vip') {
                if ($o->status === 'active') {
                    $action .= '<button class="btn btn-xs btn-success btn-renew mb-1" data-orders="' . (int) $o->id . '" data-server="' . (int) $o->server_id . '">Gia hạn</button> ';
                    $action .= '<button class="btn btn-xs btn-danger btn-cancel mb-1" data-orders="' . (int) $o->id . '">Hủy</button>';
                } elseif ($o->status === 'expired') {
                    $action .= '<button class="btn btn-xs btn-success btn-renew" data-orders="' . (int) $o->id . '" data-server="' . (int) $o->server_id . '">Gia hạn</button>';
                }
            } elseif ($o->status === 'completed' && $o->server_refill && $o->guarantee_rejected < $max_rej) {
                $action = '<button class="btn btn-xs btn-warning btn-guarantee" data-order="' . (int) $o->id . '">Bảo hành</button>';
            }
            $row = [
                'id' => (int) $o->id,
                'uid' => $uid_html,
                'server' => $server,
                'content' => $o->content ? '<span class="see-uid text-primary" role="button" data-id="' . esc_attr($o->content) . '">Xem (' . count(preg_split('/\n/', $o->content)) . ' dòng)</span>' : '',
                'note' => esc_html($o->note),
                'admin_note' => esc_html($o->admin_note),
                'price' => ml_price($o->price) . 'đ',
                'amount' => ml_number($o->amount) . 'đ',
                'count' => ml_number($o->quantity),
                'original' => ml_number($o->start_count),
                'present' => ml_number($o->present),
                'created' => date_i18n('H:i:s d/m/Y', strtotime($o->created_at)),
                'status' => ml_status_badge($o->status),
                'action' => $action,
                'detail' => '<button class="btn btn-xs btn-info btn-modal-server" data-toggle="modal" data-target="#modalServer" data-url="' . esc_url(home_url('/server/server-detail/' . (int) $o->server_id . '/')) . '">Xem</button>',
                'vip_package' => ml_number($o->vip_package),
                'days' => (int) $o->days . ' ngày',
                'time_end' => $o->time_end ? date_i18n('H:i d/m/Y', strtotime($o->time_end)) : '',
            ];
            $data[] = $row;
        }
        self::out($total, $filtered, $data);
    }

    private static function payments($uid)
    {
        global $wpdb;
        [$start, $len, , $dir] = self::paging();
        $t = ML_DB::t('payments');
        $where = $wpdb->prepare("user_id = %d", $uid);
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE $where");
        $rows = $wpdb->get_results("SELECT * FROM $t WHERE $where ORDER BY id $dir LIMIT $start, $len");
        $map = ['completed' => 'Thành công', 'pending' => 'Đang xử lý', 'rejected' => 'Thất bại', 'unmatched' => 'Chưa khớp'];
        $data = [];
        foreach ($rows as $p) {
            $data[] = [
                'id' => (int) $p->id,
                'gateway' => esc_html(strtoupper($p->gateway)),
                'amount' => ml_number($p->amount) . 'đ',
                'bonus' => ml_number($p->bonus) . 'đ',
                'content' => esc_html($p->gateway === 'card' ? preg_replace('/Mã: .*/', 'Mã: ******', $p->content) : $p->content),
                'status' => esc_html($map[$p->status] ?? $p->status),
                'created' => date_i18n('H:i:s d/m/Y', strtotime($p->created_at)),
            ];
        }
        self::out($total, $total, $data);
    }

    private static function transactions($uid)
    {
        global $wpdb;
        [$start, $len, $search, $dir] = self::paging();
        $t = ML_DB::t('transactions');
        $where = $wpdb->prepare("user_id = %d", $uid);
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE $where");
        if ($search !== '') {
            $where .= $wpdb->prepare(" AND note LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }
        $filtered = $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE $where");
        $rows = $wpdb->get_results("SELECT * FROM $t WHERE $where ORDER BY id $dir LIMIT $start, $len");
        $types = ['deposit' => 'Nạp tiền', 'bonus' => 'Khuyến mãi', 'order' => 'Mua dịch vụ', 'refund' => 'Hoàn tiền', 'admin' => 'Admin điều chỉnh'];
        $data = [];
        foreach ($rows as $r) {
            $cls = $r->amount >= 0 ? 'text-success' : 'text-danger';
            $data[] = [
                'id' => (int) $r->id,
                'type' => esc_html($types[$r->type] ?? $r->type),
                'before' => ml_number($r->balance_before) . 'đ',
                'amount' => '<span class="font-weight-bold ' . $cls . '">' . ($r->amount >= 0 ? '+' : '') . ml_number($r->amount) . 'đ</span>',
                'after' => ml_number($r->balance_after) . 'đ',
                'note' => esc_html($r->note),
                'created' => date_i18n('H:i:s d/m/Y', strtotime($r->created_at)),
            ];
        }
        self::out($total, $filtered, $data);
    }
}
