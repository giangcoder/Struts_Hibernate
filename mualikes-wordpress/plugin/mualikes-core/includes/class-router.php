<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Giữ nguyên toàn bộ URL của site gốc:
 *  /{slug-dich-vu}/, /other-service/{nen-tang}/{loai}/, /auth/{trang}/, /server/graph-api, /server/{datatable},
 *  /bang-gia-dich-vu/, /docs/document/, /blog/{bai-viet}/, /category/{x}/, /tag/{x}/, /api/v2
 */
class ML_Router
{
    public static $auth_pages = [
        'login' => 'Đăng nhập', 'register' => 'Đăng ký', 'forgot-password' => 'Quên mật khẩu', 'logout' => 'Đăng xuất',
        'profile' => 'Thông tin tài khoản', 'payment' => 'Nạp tiền', 'order-list' => 'Lịch sử đơn hàng',
        'transactions' => 'Lịch sử giao dịch', 'api' => 'Tích hợp API', 'affiliate' => 'Tiếp thị liên kết',
    ];
    public static $guest_pages = ['login', 'register', 'forgot-password'];

    public static function init()
    {
        add_action('init', [__CLASS__, 'add_rules'], 20);
        add_action('init', [__CLASS__, 'maybe_flush'], 99);
        add_filter('query_vars', function ($v) {
            return array_merge($v, ['ml_endpoint', 'ml_arg', 'ml_auth', 'ml_view']);
        });
        add_action('parse_request', [__CLASS__, 'endpoints'], 1);
        add_action('template_redirect', [__CLASS__, 'template_redirect'], 1);
        add_filter('register_taxonomy_args', function ($args, $tax) {
            if (in_array($tax, ['category', 'post_tag'], true)) {
                $args['rewrite'] = ['slug' => $tax === 'category' ? 'category' : 'tag', 'with_front' => false];
            }
            return $args;
        }, 10, 2);
        add_filter('pre_get_document_title', [__CLASS__, 'title'], 20);
        add_filter('redirect_canonical', function ($redirect) {
            if (get_query_var('ml_auth') || get_query_var('ml_view') || get_query_var('ml_endpoint')) {
                return false;
            }
            return $redirect;
        });
    }

    public static function add_rules()
    {
        add_rewrite_rule('^server/graph-api/?$', 'index.php?ml_endpoint=graph-api', 'top');
        add_rewrite_rule('^server/orders/?$', 'index.php?ml_endpoint=orders', 'top');
        add_rewrite_rule('^server/webhook/([a-z0-9-]+)/?$', 'index.php?ml_endpoint=webhook&ml_arg=$matches[1]', 'top');
        add_rewrite_rule('^server/server-detail/([0-9]+)/?$', 'index.php?ml_endpoint=server-detail&ml_arg=$matches[1]', 'top');
        add_rewrite_rule('^server/([a-z0-9-]+)/?$', 'index.php?ml_endpoint=datatable&ml_arg=$matches[1]', 'top');
        add_rewrite_rule('^api/v2/?$', 'index.php?ml_endpoint=api', 'top');
        add_rewrite_rule('^auth/([a-z-]+)/?$', 'index.php?ml_auth=$matches[1]', 'top');
        add_rewrite_rule('^bang-gia-dich-vu/?$', 'index.php?ml_view=pricing', 'top');
        add_rewrite_rule('^docs/document/?$', 'index.php?ml_view=docs', 'top');
        add_rewrite_rule('^ref/([0-9]+)/?$', 'index.php?ml_endpoint=ref&ml_arg=$matches[1]', 'top');
        foreach (ML_Services::paths() as $path => $id) {
            add_rewrite_rule('^' . preg_quote($path, '#') . '/?$', 'index.php?post_type=' . ML_Services::CPT . '&p=' . (int) $id, 'top');
        }
    }

    public static function maybe_flush()
    {
        if (get_option('ml_flush_rules')) {
            delete_option('ml_flush_rules');
            flush_rewrite_rules(false);
        }
    }

    public static function endpoints($wp)
    {
        $ep = $wp->query_vars['ml_endpoint'] ?? '';
        if (!$ep) {
            return;
        }
        $arg = $wp->query_vars['ml_arg'] ?? '';
        switch ($ep) {
            case 'graph-api':
                ML_Graph_API::handle();
                break;
            case 'orders':
                ML_Graph_API::handle_orders();
                break;
            case 'datatable':
                ML_DataTables::handle($arg);
                break;
            case 'webhook':
                if ($arg === 'sepay') {
                    ML_Payments::webhook_sepay();
                } elseif ($arg === 'casso') {
                    ML_Payments::webhook_casso();
                }
                ml_json(false, 'Not found', [], 404);
                break;
            case 'server-detail':
                self::server_detail((int) $arg);
                break;
            case 'api':
                ML_Public_API::handle();
                break;
            case 'ref':
                setcookie('ml_ref', (int) $arg, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                wp_safe_redirect(home_url('/auth/register/'));
                exit;
        }
    }

    private static function server_detail($id)
    {
        $s = ML_Services::server($id);
        header('Content-Type: text/html; charset=utf-8');
        if (!$s) {
            echo '<div class="p-3">Không tìm thấy server</div>';
            exit;
        }
        $level = ML_Users::level(get_current_user_id());
        echo '<div class="modal-header"><h5 class="modal-title">' . esc_html(get_the_title($s->service_id) . ' - ' . $s->label) . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'
            . '<div class="modal-body"><p><strong>' . $s->name . '</strong></p>'
            . '<p>Đơn giá: <span class="badge badge-danger">' . ml_price(ML_Services::server_price($s, $level)) . ' đ</span> '
            . ($s->active ? '<span class="badge bg-success text-white">Hoạt động</span>' : '<span class="badge bg-danger text-white">Bảo trì</span>') . '</p>'
            . '<div class="alert alert-default-info">' . ml_fix_urls($s->note) . '</div></div>';
        exit;
    }

    public static function template_redirect()
    {
        $auth = get_query_var('ml_auth');
        if (!$auth) {
            return;
        }
        if (!isset(self::$auth_pages[$auth])) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        if ($auth === 'logout') {
            wp_logout();
            wp_safe_redirect(home_url('/'));
            exit;
        }
        $guest = in_array($auth, self::$guest_pages, true);
        if ($guest && is_user_logged_in()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
        if (!$guest && !is_user_logged_in()) {
            wp_safe_redirect(home_url('/auth/login/'));
            exit;
        }
    }

    public static function title($title)
    {
        $site = ML_Settings::get('site_name');
        if ($a = get_query_var('ml_auth')) {
            return (self::$auth_pages[$a] ?? $title) . ' - ' . $site;
        }
        if (get_query_var('ml_view') === 'pricing') {
            return 'Bảng Giá Dịch Vụ - ' . $site;
        }
        if (get_query_var('ml_view') === 'docs') {
            return 'API Document - ' . $site;
        }
        if (is_singular(ML_Services::CPT)) {
            $t = ML_Services::meta(get_the_ID(), 'meta_title');
            return $t ?: get_the_title() . ' - ' . $site;
        }
        if (is_singular('post')) {
            $t = get_post_meta(get_the_ID(), '_ml_meta_title', true);
            return $t ?: $title;
        }
        return $title;
    }
}
