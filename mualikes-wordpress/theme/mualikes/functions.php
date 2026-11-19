<?php
if (!defined('ABSPATH')) {
    exit;
}

define('MLT_VER', '1.0.0');
define('MLT_URI', get_template_directory_uri());
define('MLT_DIR', get_template_directory());

/* Theme vẫn hiển thị được (thông báo) nếu chưa bật plugin. */
function mlt_ready()
{
    return class_exists('ML_Services');
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_generator');
});

function mlt_setting($k, $d = '')
{
    return mlt_ready() ? ML_Settings::get($k, $d) : $d;
}

function mlt_img($path)
{
    if (function_exists('ml_asset')) {
        return ml_asset($path);
    }
    return MLT_URI . '/assets/img' . $path;
}

/* ------------------------------------------------------------------ */
/* CSS / JS – đúng thứ tự & phiên bản như site gốc                     */
/* ------------------------------------------------------------------ */
add_action('wp_enqueue_scripts', function () {
    $v = MLT_URI . '/assets/vendor/';
    $css = [
        'google-font' => 'https://fonts.googleapis.com/css?family=Roboto:300,400,400i,700&display=fallback',
        'fontawesome6' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        'adminlte' => $v . 'dist/css/adminlte.min.css',
        'toastr' => $v . 'plugins/toastr/toastr.min.css',
        'icheck' => $v . 'plugins/icheck-bootstrap/icheck-bootstrap.min.css',
        'sweetalert2' => $v . 'plugins/sweetalert2/sweetalert2.min.css',
        'dt-bs4' => $v . 'plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
        'dt-responsive' => $v . 'plugins/datatables-responsive/css/responsive.bootstrap4.min.css',
        'dt-buttons' => $v . 'plugins/datatables-buttons/css/buttons.bootstrap4.min.css',
        'select2' => $v . 'plugins/select2/css/select2.min.css',
        'select2-bs4' => $v . 'plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css',
        'overlayscrollbars' => $v . 'plugins/overlayScrollbars/css/OverlayScrollbars.min.css',
        'mualikes-app' => MLT_URI . '/assets/css/app.css',
        'mualikes-wp' => MLT_URI . '/assets/css/wp.css',
    ];
    $prev = [];
    foreach ($css as $h => $src) {
        wp_enqueue_style($h, $src, $prev, MLT_VER);
        $prev = [$h];
    }
    $primary = mlt_setting('color_primary', '#1e8e41');
    $secondary = mlt_setting('color_secondary', '#ec224b');
    wp_add_inline_style('mualikes-wp', ':root{--primary:' . esc_attr($primary) . ';--secondary:' . esc_attr($secondary) . ';}' . mlt_setting('custom_css', ''));

    // jQuery của AdminLTE (giống site gốc) thay cho jQuery mặc định của WP ở front-end
    wp_deregister_script('jquery');
    wp_register_script('jquery', $v . 'plugins/jquery/jquery.min.js', [], '3.6.0', true);

    $js = [
        'clipboard' => [$v . 'plugins/clipboard.js/dist/clipboard.min.js', []],
        'bootstrap' => [$v . 'plugins/bootstrap/js/bootstrap.bundle.min.js', ['jquery']],
        'fontawesome-js' => [$v . 'plugins/fontawesome-free/js/all.min.js', []],
        'adminlte' => [$v . 'dist/js/adminlte.js', ['jquery', 'bootstrap']],
        'toastr' => [$v . 'plugins/toastr/toastr.min.js', ['jquery']],
        'sweetalert2' => [$v . 'plugins/sweetalert2/sweetalert2.min.js', []],
        'jquery-validate' => [$v . 'plugins/jquery-validation/jquery.validate.min.js', ['jquery']],
        'select2' => [$v . 'plugins/select2/js/select2.full.min.js', ['jquery']],
        'datatables' => [$v . 'plugins/datatables/jquery.dataTables.min.js', ['jquery']],
        'dt-bs4' => [$v . 'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js', ['datatables']],
        'dt-responsive' => [$v . 'plugins/datatables-responsive/js/dataTables.responsive.min.js', ['datatables']],
        'dt-responsive-bs4' => [$v . 'plugins/datatables-responsive/js/responsive.bootstrap4.min.js', ['dt-responsive']],
        'dt-buttons' => [$v . 'plugins/datatables-buttons/js/dataTables.buttons.min.js', ['datatables']],
        'dt-buttons-bs4' => [$v . 'plugins/datatables-buttons/js/buttons.bootstrap4.min.js', ['dt-buttons']],
        'jszip' => [$v . 'plugins/jszip/jszip.min.js', []],
        'pdfmake' => [$v . 'plugins/pdfmake/pdfmake.min.js', []],
        'pdfmake-fonts' => [$v . 'plugins/pdfmake/vfs_fonts.js', ['pdfmake']],
        'dt-html5' => [$v . 'plugins/datatables-buttons/js/buttons.html5.min.js', ['dt-buttons']],
        'dt-print' => [$v . 'plugins/datatables-buttons/js/buttons.print.min.js', ['dt-buttons']],
        'overlayscrollbars' => [$v . 'plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js', ['jquery']],
        'fastclick' => [$v . 'plugins/fastclick/fastclick.js', []],
    ];
    foreach ($js as $h => $d) {
        wp_enqueue_script($h, $d[0], $d[1], MLT_VER, true);
    }
    $deps = array_keys($js);
    if (mlt_setting('google_translate', 1)) {
        wp_enqueue_script('google-translate', 'https://translate.google.com/translate_a/element.js?cb=googleTranslate', ['jquery'], null, true);
        wp_add_inline_script('google-translate', "function googleTranslate(){new google.translate['TranslateElement']({pageLanguage:'vi'},'google_translate_element');}", 'before');
    }
    wp_enqueue_script('mualikes-common', MLT_URI . '/assets/js/common.js', $deps, MLT_VER, true);
    wp_enqueue_script('mualikes-custom', MLT_URI . '/assets/js/custom.js', ['mualikes-common'], MLT_VER, true);

    // Biến toàn cục giống site gốc + nonce chống CSRF cho mọi $.post
    $logged = is_user_logged_in();
    wp_add_inline_script('jquery', 'var $users = ' . wp_json_encode(['isLoggedIn' => $logged]) . ';', 'after');
    if ($logged) {
        wp_add_inline_script('jquery', 'jQuery.ajaxSetup({headers:{"X-ML-Nonce":' . wp_json_encode(wp_create_nonce('ml_action')) . '}});', 'after');
    }

    if (is_singular('ml_service')) {
        $vip = mlt_ready() && ML_Services::is_vip(get_the_ID());
        if ($vip) {
            wp_enqueue_script('mualikes-custom-service', MLT_URI . '/assets/js/custom-service.js', ['mualikes-custom'], MLT_VER, true);
        } else {
            wp_enqueue_script('mualikes-order', MLT_URI . '/assets/js/order.js', ['mualikes-custom'], MLT_VER, true);
            wp_enqueue_script('mualikes-service', MLT_URI . '/assets/js/service.js', ['mualikes-order'], MLT_VER, true);
        }
        if ($vip) {
            wp_enqueue_script('mualikes-order', MLT_URI . '/assets/js/order.js', ['mualikes-custom-service'], MLT_VER, true);
        }
    }
    if (get_query_var('ml_auth') && in_array(get_query_var('ml_auth'), ['order-list', 'transactions', 'payment'], true)) {
        wp_enqueue_script('mualikes-order', MLT_URI . '/assets/js/order.js', ['mualikes-custom'], MLT_VER, true);
    }
}, 20);

/* Băng "CẢNH BÁO VI PHẠM" (giống inline script/style gốc) */
add_action('wp_footer', function () {
    $texts = array_filter(array_map('trim', preg_split('/\r\n|\n/', (string) mlt_setting('violation_texts', ''))));
    if (!$texts || get_query_var('ml_auth') && in_array(get_query_var('ml_auth'), ['login', 'register', 'forgot-password'], true)) {
        return;
    }
    ?>
<style>
    .violation-banner{background:linear-gradient(135deg,#fff0f3,#fce4ec);border:1px solid #f8c4d0;border-radius:8px;margin:10px 15px 0;padding:8px 16px;overflow:hidden;position:relative;top:10px}
    .violation-banner::before{content:'● CẢNH BÁO VI PHẠM';position:absolute;left:0;top:0;bottom:0;display:flex;align-items:center;padding:0 16px;background:linear-gradient(135deg,#fff0f3,#fce4ec);color:#d32f2f;font-weight:800;font-size:12px;letter-spacing:.5px;z-index:2;white-space:nowrap}
    .violation-scroll-wrap{overflow:hidden}
    .violation-scroll{display:inline-block;white-space:nowrap;animation:violation-scroll 20s linear infinite}
    .violation-scroll span{display:inline-block;padding:0 24px;color:#c62828;font-size:13px;font-weight:500;line-height:24px}
    .violation-scroll span::before{content:'—';margin-right:8px;opacity:.4}
    @keyframes violation-scroll{0%{transform:translateX(100%)}100%{transform:translateX(-100%)}}
</style>
<script>
jQuery(function($){
    var violations = <?php echo wp_json_encode(array_values($texts)); ?>;
    var items = violations.map(function(v){ return $('<span>').text(v).prop('outerHTML'); }).join('');
    $('.content-wrapper').prepend('<div class="violation-banner"><div class="violation-scroll-wrap"><div class="violation-scroll">' + items + '</div></div></div>');
});
</script>
    <?php
}, 100);

/* Google Analytics + mã head tuỳ chỉnh + SEO meta */
add_action('wp_head', function () {
    $fav = mlt_img(mlt_setting('favicon', '/upload/2024/09/logomain.png'));
    echo '<link rel="icon" href="' . esc_url($fav) . '" /><link rel="apple-touch-icon" href="' . esc_url($fav) . '" />' . "\n";
    $desc = '';
    if (is_singular('ml_service') && mlt_ready()) {
        $desc = ML_Services::meta(get_the_ID(), 'meta_desc');
    } elseif (is_singular()) {
        $desc = get_post_meta(get_the_ID(), '_ml_meta_desc', true) ?: wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 30);
    } elseif (is_front_page()) {
        $desc = get_bloginfo('description');
    }
    if ($desc) {
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '" />' . "\n";
    }
    echo '<meta property="og:locale" content="vi_VN" /><meta property="og:site_name" content="' . esc_attr(mlt_setting('site_name', 'MuaLikes.Net')) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url(mlt_img('/upload/2024/09/bannerfb.png')) . '" />' . "\n";
    if ($ga = mlt_setting('google_analytics')) {
        echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . esc_attr($ga) . "\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config'," . wp_json_encode($ga) . ");</script>\n";
    }
    echo mlt_setting('head_code', '');
}, 5);

/* Trang chủ trả về title giống site gốc */
add_filter('pre_get_document_title', function ($t) {
    if (is_front_page()) {
        return get_bloginfo('name');
    }
    return $t;
}, 30);

/* Định tuyến template cho /auth/*, /bang-gia-dich-vu/, /docs/document/ */
add_filter('template_include', function ($tpl) {
    if ($a = get_query_var('ml_auth')) {
        $f = locate_template('auth/' . sanitize_file_name($a) . '.php');
        if ($f) {
            return $f;
        }
    }
    if ($v = get_query_var('ml_view')) {
        $f = locate_template('views/' . sanitize_file_name($v) . '.php');
        if ($f) {
            return $f;
        }
    }
    return $tpl;
}, 99);

add_filter('body_class', function ($c) {
    $a = get_query_var('ml_auth');
    if ($a && in_array($a, ['login', 'register', 'forgot-password'], true)) {
        return ['hold-transition', 'login-page'];
    }
    return array_merge($c, ['hold-transition', 'sidebar-mini', 'layout-fixed', 'layout-navbar-fixed']);
});

/* Nội dung: chuyển /upload/... về URL thật */
add_filter('the_content', function ($c) {
    return function_exists('ml_fix_urls') ? ml_fix_urls($c) : $c;
}, 20);

/* Ảnh đại diện bài viết (ảnh gốc lưu ở meta _ml_thumb) */
function mlt_thumb($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail_url($post_id, 'large');
    }
    $t = get_post_meta($post_id, '_ml_thumb', true);
    return $t ? mlt_img($t) : mlt_img('/upload/2024/09/bannerfb.png');
}

/* Breadcrumb + tiêu đề nội dung giống site gốc */
function mlt_content_header($title, $crumbs = [])
{
    echo '<div class="content-header"><div class="container-fluid"><div class="row align-items-center"><div class="col-auto mr-auto"><h1 class="m-0 title">' . esc_html($title) . '</h1></div>';
    if ($crumbs !== false) {
        echo '<div class="col-auto"><nav aria-label="breadcrumb"><ol class="breadcrumb float-sm-right"><li class="breadcrumb-item"><a href="' . esc_url(home_url('/')) . '" title="Trang chủ">Trang chủ</a></li>';
        foreach ($crumbs as $c) {
            if (is_array($c)) {
                echo '<li class="breadcrumb-item"><a href="' . esc_url($c[1]) . '" title="' . esc_attr($c[0]) . '">' . esc_html($c[0]) . '</a></li>';
            } else {
                echo '<li class="breadcrumb-item">' . esc_html($c) . '</li>';
            }
        }
        echo '<li class="breadcrumb-item active" aria-current="page">' . esc_html($title) . '</li></ol></nav></div>';
    }
    echo '</div></div></div>';
}

function mlt_pagination($query = null)
{
    $q = $query ?: $GLOBALS['wp_query'];
    $total = (int) $q->max_num_pages;
    if ($total < 2) {
        return;
    }
    $cur = max(1, get_query_var('paged'));
    $html = '<div><ul class="pagination pagination-circle pagination-outline">';
    $start = max(1, $cur - 2);
    $end = min($total, $start + 4);
    if ($cur > 1) {
        $html .= '<li class="page-item m-1"><a title="Prev" href="' . esc_url(get_pagenum_link($cur - 1)) . '" class="page-link">←</a></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= $i === $cur ? '<li class="page-item m-1 active"><a class="page-link">' . $i . '</a></li>' : '<li class="page-item m-1"><a title="Page ' . $i . '" href="' . esc_url(get_pagenum_link($i)) . '" class="page-link">' . $i . '</a></li>';
    }
    if ($cur < $total) {
        $html .= '<li class="page-item next m-1"><a title="Next" href="' . esc_url(get_pagenum_link($cur + 1)) . '" class="page-link">→</a></li>';
        $html .= '<li class="page-item next m-1"><a title="Next" href="' . esc_url(get_pagenum_link($total)) . '" class="page-link">»</a></li>';
    }
    echo $html . '</ul></div>';
}

/* Hỗ trợ ?page=N như site gốc cho trang blog */
add_action('pre_get_posts', function ($q) {
    if (!is_admin() && $q->is_main_query() && ($q->is_home() || $q->is_archive()) && !empty($_GET['page']) && !$q->get('paged')) {
        $q->set('paged', max(1, (int) $_GET['page']));
        $q->set('page', '');
    }
});

/* Mục lục bài viết (giống site gốc) – trả về [html_toc, html_noi_dung_da_gan_id] */
function mlt_toc($html)
{
    $items = [];
    $n2 = 0;
    $n3 = 0;
    $html = preg_replace_callback('#<h([23])([^>]*)>(.*?)</h\1>#is', function ($m) use (&$items, &$n2, &$n3) {
        $text = trim(wp_strip_all_tags(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8')));
        if ($text === '') {
            return $m[0];
        }
        $attrs = $m[2];
        if (preg_match('/\sid=(["\'])(.*?)\1/', $attrs, $idm)) {
            $id = $idm[2];
        } else {
            $id = preg_replace('/\s+/u', '-', mb_strtolower($text));
            $attrs .= ' id="' . esc_attr($id) . '"';
        }
        if ($m[1] === '2') {
            $n2++;
            $n3 = 0;
            $num = $n2 . '.';
        } else {
            $n3++;
            $num = max(1, $n2) . '.' . $n3;
        }
        $items[] = '<li class="toc-level-' . $m[1] . '"><a href="#' . esc_attr($id) . '"><div>' . esc_html($num . ' ' . $text) . '</div></a></li>';
        return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
    }, $html);
    if (!$items) {
        return ['', $html];
    }
    $toc = '<div class="toc"><ul class="menu"><li class="nav-item"><a class="nav-link" role="button"><i class="fas fa-bars" style="color:#504a4a"></i><p class="title">Mục lục bài viết </p></a>'
        . '<div class="isOpenToc"><i class="fa-solid fa-angle-down is-open"></i><i class="fa-solid fa-angle-right is-close"></i></div></li></ul>'
        . '<ul class="toc_content">' . implode('', $items) . '</ul></div>';
    return [$toc, $html];
}

/* Khung "Bạn cần hỗ trợ ?" + "Câu hỏi thường gặp" dùng ở trang dịch vụ */
function mlt_support_cards()
{
    ?>
    <div class="card card-primary card-outline">
        <div class="card-header"><i class="fa-solid fa-headset"></i> Bạn cần hỗ trợ ?</div>
        <div class="card-body p-2">
            <ul class="list-group list-group-flush">
                <?php foreach ((array) mlt_setting('support_links', []) as $l) : ?>
                    <li class="list-group-item p-2">
                        <a role="button" href="<?php echo esc_url($l['url']); ?>" target="_blank" rel="nofollow">
                            <img src="<?php echo esc_url(mlt_img($l['icon'])); ?>" style="max-width: 24px;" /> <?php echo esc_html($l['label']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php $faq = (array) mlt_setting('faq', []);
    if ($faq) : ?>
    <div class="card card-primary card-outline">
        <div class="card-header"><i class="fa-solid fa-question"></i> Câu hỏi thường gặp</div>
        <div class="card-body p-2">
            <div id="accordion">
                <div class="list-group list-group-flush">
                    <?php foreach ($faq as $i => $f) : ?>
                        <li class="list-group-item p-2">
                            <button class="btn btn-link text-left p-0" type="button" data-toggle="collapse" data-target="#question_<?php echo (int) $i; ?>" aria-expanded="true" aria-controls="question_<?php echo (int) $i; ?>"><?php echo esc_html($f['q']); ?></button>
                            <div id="question_<?php echo (int) $i; ?>" class="collapse" data-parent="#accordion">
                                <?php echo function_exists('ml_fix_urls') ? ml_fix_urls($f['a']) : $f['a']; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <small class="text-muted"><a href="<?php echo esc_url(home_url('/dieu-khoan-dich-vu/')); ?>" target="_blank" title="Điều khoản dịch vụ">Xem thêm &gt;&gt;</a></small>
        </div>
    </div>
    <?php endif;
}

/* Bảng DataTables lịch sử (dùng chung) */
function mlt_history_table()
{
    ?>
    <form class="form-search mb-2"></form>
    <div class="table-responsive">
        <table class="table cell-border table-striped table-bordered table-hover text-center nowrap" id="dataTable" width="100%" cellspacing="0"></table>
    </div>
    <?php
}

/* Nội dung nhập từ site gốc đã là HTML hoàn chỉnh → không chạy wpautop (tránh chèn <br>/<p> thừa) */
add_filter('the_content', function ($c) {
    if (preg_match('/<(p|h2|h3|ul|table|figure)[\s>]/i', $c)) {
        remove_filter('the_content', 'wpautop');
    } elseif (!has_filter('the_content', 'wpautop')) {
        add_filter('the_content', 'wpautop');
    }
    return $c;
}, 1);

/* Giữ link phân trang kiểu cũ /blog?page=N (không để WP chuyển hướng về trang 1) */
add_filter('redirect_canonical', function ($redirect) {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!empty($_GET['page']) && preg_match('#^/(blog|category|tag)(/|$)#', $path)) {
        return false;
    }
    return $redirect;
});

add_filter('request', function ($qv) {
    if (!is_admin() && !empty($_GET['page']) && (isset($qv['pagename']) || isset($qv['category_name']) || isset($qv['tag']))) {
        unset($qv['page']);
        $qv['paged'] = max(1, (int) $_GET['page']);
    }
    return $qv;
});
