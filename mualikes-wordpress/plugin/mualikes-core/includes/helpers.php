<?php
if (!defined('ABSPATH')) {
    exit;
}

/** Trả JSON đúng định dạng mà JS gốc mong đợi: {status, message}. */
function ml_json($status, $message = '', $extra = [], $code = 200)
{
    if (!headers_sent()) {
        status_header($code);
        header('Content-Type: application/json; charset=utf-8');
        nocache_headers();
    }
    echo wp_json_encode(array_merge(['status' => (bool) $status, 'success' => (bool) $status, 'message' => $message], $extra));
    exit;
}

/** Định dạng số giống number_format của site gốc (1,000,000). */
function ml_number($n, $dec = 0)
{
    return number_format((float) $n, $dec, '.', ',');
}

/** Định dạng tiền: 1.000.000 ₫ (giống Intl vi-VN). */
function ml_vnd($n)
{
    return number_format((float) round($n), 0, ',', '.') . ' ₫';
}

/** Hiển thị đơn giá dạng 21.9 (bỏ số 0 thừa). */
function ml_price($p)
{
    $s = rtrim(rtrim(number_format((float) $p, 2, '.', ''), '0'), '.');
    return $s === '' ? '0' : $s;
}

function ml_now()
{
    return current_time('mysql');
}

function ml_time_ago($datetime)
{
    $ts = strtotime($datetime);
    $diff = current_time('timestamp') - $ts;
    if ($diff < 60) {
        return 'vừa xong';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    }
    if ($diff < 86400 * 30) {
        return floor($diff / 86400) . ' ngày trước';
    }
    return date_i18n('d/m/Y', $ts);
}

/** URL tài nguyên của theme (ảnh, icon) – dùng cho dữ liệu nhập có đường dẫn gốc /images/... /upload/... */
function ml_asset($path)
{
    if (!$path) {
        return '';
    }
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    $path = '/' . ltrim($path, '/');
    $theme_file = get_template_directory() . '/assets/img' . $path;
    if (file_exists($theme_file)) {
        return get_template_directory_uri() . '/assets/img' . $path;
    }
    $up = wp_get_upload_dir();
    if (strpos($path, '/upload/') === 0 && file_exists($up['basedir'] . '/mualikes' . $path)) {
        return $up['baseurl'] . '/mualikes' . $path;
    }
    return ML_Settings::get('remote_asset_base', 'https://mualikes.net') . $path;
}

/** Thay các đường dẫn tương đối /upload/... /images/... trong nội dung HTML bằng URL thật. */
function ml_fix_urls($html)
{
    return preg_replace_callback('#(src|href)=(["\'])(/(?:upload|images)/[^"\']+)\2#', function ($m) {
        return $m[1] . '=' . $m[2] . esc_url(ml_asset($m[3])) . $m[2];
    }, (string) $html);
}

function ml_request($key, $default = '')
{
    if (isset($_POST[$key])) {
        return is_array($_POST[$key]) ? array_map('sanitize_text_field', wp_unslash($_POST[$key])) : wp_unslash($_POST[$key]);
    }
    if (isset($_GET[$key])) {
        return is_array($_GET[$key]) ? array_map('sanitize_text_field', wp_unslash($_GET[$key])) : wp_unslash($_GET[$key]);
    }
    return $default;
}

function ml_mask_user($login)
{
    $login = (string) $login;
    $len = function_exists('mb_strlen') ? mb_strlen($login) : strlen($login);
    $keep = max(3, min(8, (int) floor($len * 0.6)));
    return (function_exists('mb_substr') ? mb_substr($login, 0, $keep) : substr($login, 0, $keep)) . '....';
}

function ml_status_label($status)
{
    $map = ML_Orders::statuses();
    return isset($map[$status]) ? $map[$status] : $status;
}

function ml_status_badge($status)
{
    $colors = [
        'pending' => 'warning', 'processing' => 'warning', 'running' => 'info', 'completed' => 'success',
        'partial' => 'secondary', 'paused' => 'dark', 'canceled' => 'danger', 'refunded' => 'danger',
        'active' => 'success', 'expired' => 'secondary', 'scheduled' => 'primary',
    ];
    $c = isset($colors[$status]) ? $colors[$status] : 'secondary';
    return '<span class="badge bg-' . $c . ' fw-bold text-white">' . esc_html(ml_status_label($status)) . '</span>';
}

function ml_log($msg, $ctx = [])
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[mualikes] ' . $msg . ($ctx ? ' ' . wp_json_encode($ctx) : ''));
    }
}

/** Ngày giờ trả về cho JS gốc (JS gọi numberFormat lên chuỗi này → chèn ký tự rỗng để năm không bị thành "2,026"). */
function ml_js_date($datetime)
{
    $s = date_i18n('H:i d/m/Y', strtotime($datetime));
    return preg_replace('/(\d{2})(\d{2})$/', "$1\u{200B}$2", $s);
}
