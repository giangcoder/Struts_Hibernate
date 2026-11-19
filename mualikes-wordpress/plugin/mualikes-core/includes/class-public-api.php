<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API cho đại lý theo chuẩn SMM Panel v2: POST /api/v2  (key, action).
 *  - services : danh sách server (rate = giá cho 1000)
 *  - add      : service, link, quantity, [comments], [reaction]
 *  - status   : order | orders (phân tách dấu phẩy)
 *  - balance
 *  - refill   : order
 */
class ML_Public_API
{
    public static function handle()
    {
        if (!ML_Settings::get('api_enabled', 1)) {
            wp_send_json(['error' => 'API is disabled'], 403);
        }
        $key = sanitize_text_field(ml_request('key', ml_request('api_token')));
        $uid = ML_Users::by_token($key);
        if (!$uid) {
            wp_send_json(['error' => 'Invalid API key'], 401);
        }
        if (get_user_meta($uid, 'ml_banned', true)) {
            wp_send_json(['error' => 'Account is banned'], 403);
        }
        $action = sanitize_key(ml_request('action'));
        switch ($action) {
            case 'services':
                self::services($uid);
                break;
            case 'add':
                self::add($uid);
                break;
            case 'status':
                self::status($uid);
                break;
            case 'balance':
                wp_send_json(['balance' => number_format(ML_Users::balance($uid), 2, '.', ''), 'currency' => 'VND']);
                break;
            case 'refill':
                [$ok, $msg] = ML_Orders::guarantee($uid, (int) ml_request('order'));
                wp_send_json($ok ? ['refill' => (int) ml_request('order')] : ['error' => $msg]);
                break;
            default:
                wp_send_json(['error' => 'Incorrect request']);
        }
    }

    private static function services($uid)
    {
        $level = ML_Users::level($uid);
        $out = [];
        foreach (ML_Services::menu() as $g) {
            if ($g['vip']) {
                continue;
            }
            foreach ($g['items'] as $it) {
                foreach (ML_Services::servers($it['id'], true) as $s) {
                    $out[] = [
                        'service' => (int) $s->id,
                        'name' => $it['title'] . ' - ' . $s->label . ': ' . wp_strip_all_tags($s->name),
                        'type' => (!empty($s->attrs['comment']) && $s->attrs['comment'] !== '0') ? 'Custom Comments' : 'Default',
                        'category' => $g['name'],
                        'rate' => round(ML_Services::server_price($s, $level) * 1000, 2),
                        'min' => (int) $s->min_qty,
                        'max' => (int) $s->max_qty,
                        'refill' => (bool) $s->refill,
                        'cancel' => (bool) $s->cancel,
                    ];
                }
            }
        }
        wp_send_json($out);
    }

    private static function add($uid)
    {
        $in = [
            'serverId' => (int) ml_request('service'),
            'post_link' => (string) ml_request('link'),
            'number_seeding' => (int) ml_request('quantity'),
            'comments' => (string) ml_request('comments'),
            'reaction' => (string) ml_request('reaction'),
            'note' => 'API',
        ];
        $server = ML_Services::server($in['serverId']);
        if (!$server || ML_Services::is_vip($server->service_id)) {
            wp_send_json(['error' => 'Incorrect service ID']);
        }
        if (!$in['reaction'] && !empty($server->attrs['reactions'])) {
            $in['reaction'] = explode(',', $server->attrs['reactions'])[0];
        }
        [$ok, $msg] = ML_Orders::create_from_request($uid, $in);
        if (!$ok) {
            wp_send_json(['error' => wp_strip_all_tags($msg)]);
        }
        global $wpdb;
        $wpdb->update(ML_DB::t('orders'), ['source' => 'api'], ['id' => (int) $msg['orderId']]);
        wp_send_json(['order' => (int) $msg['orderId']]);
    }

    private static function fmt($o)
    {
        $map = ['pending' => 'Pending', 'processing' => 'Processing', 'running' => 'In progress', 'completed' => 'Completed', 'partial' => 'Partial',
            'paused' => 'In progress', 'canceled' => 'Canceled', 'refunded' => 'Canceled', 'scheduled' => 'Pending'];
        return [
            'charge' => number_format((float) $o->amount, 2, '.', ''),
            'start_count' => (string) $o->start_count,
            'status' => $map[$o->status] ?? 'Pending',
            'remains' => (string) max(0, $o->quantity - $o->present),
            'currency' => 'VND',
        ];
    }

    private static function status($uid)
    {
        global $wpdb;
        $t = ML_DB::t('orders');
        if ($ids = ml_request('orders')) {
            $ids = array_slice(array_filter(array_map('intval', explode(',', $ids))), 0, 100);
            $out = [];
            foreach ($ids as $id) {
                $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND user_id = %d", $id, $uid));
                $out[$id] = $o ? self::fmt($o) : ['error' => 'Incorrect order ID'];
            }
            wp_send_json($out);
        }
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id = %d AND user_id = %d", (int) ml_request('order'), $uid));
        wp_send_json($o ? self::fmt($o) : ['error' => 'Incorrect order ID']);
    }
}
