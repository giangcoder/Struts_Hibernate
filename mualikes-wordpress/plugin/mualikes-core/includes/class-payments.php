<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nạp tiền: chuyển khoản ngân hàng tự động (webhook SePay / Casso), thẻ cào (duyệt tay), cộng tay bởi admin.
 * Khuyến mãi % nạp tiền theo khoảng ngày cấu hình trong Cài đặt.
 */
class ML_Payments
{
    public static function init()
    {
    }

    public static function qr_url($uid, $amount = 0)
    {
        $bin = ML_Settings::get('bank_bin');
        $acc = ML_Settings::get('bank_account');
        if (!$bin || !$acc) {
            return '';
        }
        $q = ['addInfo' => ML_Users::transfer_code($uid), 'accountName' => ML_Settings::get('bank_owner')];
        if ($amount > 0) {
            $q['amount'] = (int) $amount;
        }
        return 'https://img.vietqr.io/image/' . rawurlencode($bin) . '-' . rawurlencode($acc) . '-compact2.png?' . http_build_query($q);
    }

    /** Tìm user từ nội dung chuyển khoản "mualikes 15" (không phân biệt hoa thường/khoảng trắng). */
    public static function parse_user($content)
    {
        $prefix = preg_quote(strtolower(ML_Settings::get('transfer_prefix', 'mualikes')), '/');
        $c = strtolower(preg_replace('/\s+/', '', (string) $content));
        if (preg_match('/' . str_replace('\ ', '', $prefix) . '(\d+)/', $c, $m)) {
            $uid = (int) $m[1];
            return get_userdata($uid) ? $uid : 0;
        }
        return 0;
    }

    /**
     * Ghi nhận khoản nạp và cộng tiền (idempotent theo gateway+ref).
     */
    public static function credit($uid, $amount, $gateway, $ref, $content = '', $raw = null, $apply_promo = true)
    {
        global $wpdb;
        $amount = round((float) $amount);
        if ($amount <= 0) {
            return [false, 'Số tiền không hợp lệ'];
        }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . ML_DB::t('payments') . " WHERE gateway = %s AND ref = %s", $gateway, $ref));
        if ($exists) {
            return [false, 'Giao dịch đã được xử lý'];
        }
        $bonus = 0;
        $promo = $apply_promo ? ML_Settings::active_promo() : 0;
        if ($promo > 0 && $amount >= (float) ML_Settings::get('promo_min', 0)) {
            $bonus = round($amount * $promo / 100);
        }
        $ok = $wpdb->insert(ML_DB::t('payments'), [
            'user_id' => (int) $uid, 'gateway' => $gateway, 'ref' => mb_substr((string) $ref, 0, 191), 'amount' => $amount, 'bonus' => $bonus,
            'content' => mb_substr((string) $content, 0, 1000), 'status' => $uid ? 'completed' : 'unmatched',
            'raw' => $raw ? wp_json_encode($raw) : null, 'created_at' => ml_now(),
        ]);
        if (!$ok) {
            return [false, 'Giao dịch đã được xử lý'];
        }
        if (!$uid) {
            return [false, 'Không xác định được tài khoản từ nội dung chuyển khoản'];
        }
        ML_Users::change_balance($uid, $amount, 'deposit', 'Nạp tiền qua ' . strtoupper($gateway) . ' (' . $ref . ')');
        if ($bonus > 0) {
            ML_Users::change_balance($uid, $bonus, 'bonus', 'Khuyến mãi ' . $promo . '% nạp tiền');
        }
        ML_Users::add_deposit_total($uid, $amount);
        do_action('ml_deposit_completed', $uid, $amount, $bonus, $gateway);
        return [true, $amount + $bonus];
    }

    /** Webhook SePay: header "Authorization: Apikey <API_KEY>". */
    public static function webhook_sepay()
    {
        $key = ML_Settings::get('sepay_api_key');
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!$key || !hash_equals('Apikey ' . $key, trim($auth))) {
            ml_json(false, 'Unauthorized', [], 401);
        }
        $d = json_decode(file_get_contents('php://input'), true);
        if (!is_array($d) || ($d['transferType'] ?? '') !== 'in') {
            ml_json(true, 'ignored');
        }
        $uid = self::parse_user($d['content'] ?? '');
        $r = self::credit($uid, $d['transferAmount'] ?? 0, 'sepay', (string) ($d['id'] ?? $d['referenceCode'] ?? ''), $d['content'] ?? '', $d);
        wp_send_json(['success' => true, 'message' => $r[1]], 200);
    }

    /** Webhook Casso (v2): header "Secure-Token". */
    public static function webhook_casso()
    {
        $token = ML_Settings::get('casso_secure_token');
        $hdr = $_SERVER['HTTP_SECURE_TOKEN'] ?? '';
        if (!$token || !hash_equals($token, $hdr)) {
            ml_json(false, 'Unauthorized', [], 401);
        }
        $d = json_decode(file_get_contents('php://input'), true);
        $items = isset($d['data']) ? (isset($d['data'][0]) ? $d['data'] : [$d['data']]) : [];
        foreach ($items as $t) {
            if ((float) ($t['amount'] ?? 0) <= 0) {
                continue;
            }
            $uid = self::parse_user($t['description'] ?? '');
            self::credit($uid, $t['amount'], 'casso', (string) ($t['id'] ?? $t['tid'] ?? ''), $t['description'] ?? '', $t);
        }
        wp_send_json(['error' => 0, 'message' => 'ok']);
    }

    /** Gửi thẻ cào – lưu chờ admin duyệt. */
    public static function submit_card($uid, array $in)
    {
        global $wpdb;
        $telco = sanitize_text_field($in['telco'] ?? '');
        $amount = (int) ($in['amount'] ?? 0);
        $serial = sanitize_text_field($in['serial'] ?? '');
        $code = sanitize_text_field($in['code'] ?? '');
        if (!$telco || !$amount || !$serial || !$code) {
            return [false, 'Vui lòng nhập đầy đủ thông tin thẻ!'];
        }
        $ok = $wpdb->insert(ML_DB::t('payments'), [
            'user_id' => $uid, 'gateway' => 'card', 'ref' => 'card-' . md5($telco . $serial . $code), 'amount' => $amount, 'bonus' => 0,
            'content' => $telco . ' | Serial: ' . $serial . ' | Mã: ' . $code, 'status' => 'pending', 'created_at' => ml_now(),
        ]);
        if (!$ok) {
            return [false, 'Thẻ này đã được gửi trước đó!'];
        }
        return [true, 'Gửi thẻ thành công, vui lòng chờ hệ thống xử lý trong 1-5 phút.'];
    }

    /** Admin duyệt thẻ / giao dịch chưa khớp. */
    public static function approve($payment_id, $real_amount = null, $uid = null)
    {
        global $wpdb;
        $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ML_DB::t('payments') . " WHERE id = %d", $payment_id));
        if (!$p || $p->status === 'completed') {
            return [false, 'Giao dịch không hợp lệ'];
        }
        $uid = $uid ?: (int) $p->user_id;
        if (!$uid) {
            return [false, 'Chưa chọn tài khoản'];
        }
        $amount = $real_amount !== null ? (float) $real_amount : (float) $p->amount;
        if ($p->gateway === 'card' && $real_amount === null) {
            $amount = round($amount * (float) ML_Settings::get('card_rate', 80) / 100);
        }
        $wpdb->update(ML_DB::t('payments'), ['status' => 'completed', 'user_id' => $uid, 'amount' => $amount], ['id' => $p->id]);
        ML_Users::change_balance($uid, $amount, 'deposit', 'Nạp tiền qua ' . strtoupper($p->gateway) . ' (#' . $p->id . ')');
        ML_Users::add_deposit_total($uid, $amount);
        return [true, 'Đã cộng ' . ml_number($amount) . 'đ'];
    }
}
