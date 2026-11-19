<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Kết nối nhà cung cấp (API chuẩn SMM Panel v2: key, action=add|status|services|balance|refill|cancel).
 * Mỗi server có thể gắn 1 nhà cung cấp + mã dịch vụ bên đó. Server không gắn sẽ xử lý thủ công trong admin.
 */
class ML_Providers
{
    public static function all($only_active = false)
    {
        global $wpdb;
        $sql = "SELECT * FROM " . ML_DB::t('providers') . ($only_active ? " WHERE active = 1" : "") . " ORDER BY id ASC";
        return $wpdb->get_results($sql);
    }

    public static function get($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ML_DB::t('providers') . " WHERE id = %d", $id));
    }

    public static function save(array $d)
    {
        global $wpdb;
        $row = [
            'name' => sanitize_text_field($d['name'] ?? ''),
            'api_url' => esc_url_raw($d['api_url'] ?? ''),
            'api_key' => sanitize_text_field($d['api_key'] ?? ''),
            'active' => empty($d['active']) ? 0 : 1,
        ];
        if (!empty($d['id'])) {
            $wpdb->update(ML_DB::t('providers'), $row, ['id' => (int) $d['id']]);
            return (int) $d['id'];
        }
        $row['created_at'] = ml_now();
        $wpdb->insert(ML_DB::t('providers'), $row);
        return (int) $wpdb->insert_id;
    }

    public static function call($provider, array $params)
    {
        if (is_numeric($provider)) {
            $provider = self::get($provider);
        }
        if (!$provider || !$provider->active || !$provider->api_url) {
            return [false, 'Nhà cung cấp không khả dụng'];
        }
        $params['key'] = $provider->api_key;
        $res = wp_remote_post($provider->api_url, ['timeout' => 30, 'body' => $params]);
        if (is_wp_error($res)) {
            return [false, $res->get_error_message()];
        }
        $body = json_decode(wp_remote_retrieve_body($res), true);
        if (!is_array($body)) {
            return [false, 'Phản hồi không hợp lệ từ nhà cung cấp'];
        }
        if (isset($body['error'])) {
            return [false, is_string($body['error']) ? $body['error'] : wp_json_encode($body['error'])];
        }
        return [true, $body];
    }

    /** Gửi đơn sang nhà cung cấp. */
    public static function place_order($order, $server)
    {
        $params = ['action' => 'add', 'service' => $server->provider_service, 'link' => $order->original_link ?: $order->uid, 'quantity' => (int) $order->quantity];
        $p = $order->params ? json_decode($order->params, true) : [];
        if (!empty($order->content)) {
            $params['comments'] = $order->content;
        }
        if (!empty($p['reaction'])) {
            $params['reaction'] = $p['reaction'];
        }
        if (!empty($p['time_to_live'])) {
            $params['runs'] = 1;
            $params['interval'] = (int) $p['time_to_live'];
        }
        if ($order->kind === 'vip') {
            $params['username'] = $order->uid;
            $params['min'] = (int) $order->vip_package;
            $params['max'] = (int) $order->vip_package;
            $params['posts'] = max(1, (int) ($p['max_post'] ?? 0));
            $params['delay'] = 0;
            $params['expiry'] = $order->time_end ? date('d/m/Y', strtotime($order->time_end)) : '';
            unset($params['quantity'], $params['link']);
        }
        return self::call($server->provider_id, $params);
    }

    public static function status($provider_id, $provider_order)
    {
        return self::call($provider_id, ['action' => 'status', 'order' => $provider_order]);
    }

    public static function refill($provider_id, $provider_order)
    {
        return self::call($provider_id, ['action' => 'refill', 'order' => $provider_order]);
    }

    public static function cancel($provider_id, $provider_order)
    {
        return self::call($provider_id, ['action' => 'cancel', 'orders' => $provider_order]);
    }

    public static function balance($provider_id)
    {
        $r = self::call($provider_id, ['action' => 'balance']);
        if ($r[0]) {
            global $wpdb;
            $txt = ($r[1]['balance'] ?? '') . ' ' . ($r[1]['currency'] ?? '');
            $wpdb->update(ML_DB::t('providers'), ['balance' => trim($txt)], ['id' => (int) $provider_id]);
        }
        return $r;
    }

    public static function services($provider_id)
    {
        return self::call($provider_id, ['action' => 'services']);
    }

    /** Chuẩn hoá trạng thái nhà cung cấp về trạng thái nội bộ. */
    public static function map_status($s)
    {
        $s = strtolower(trim((string) $s));
        $map = [
            'pending' => 'pending', 'processing' => 'processing', 'in progress' => 'running', 'inprogress' => 'running',
            'completed' => 'completed', 'partial' => 'partial', 'canceled' => 'canceled', 'cancelled' => 'canceled',
            'refunded' => 'canceled', 'paused' => 'paused', 'error' => 'paused', 'fail' => 'canceled',
        ];
        return $map[$s] ?? 'running';
    }
}
