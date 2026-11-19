<?php
if (!mlt_ready()) {
    get_template_part('index');
    return;
}
the_post();
$sid = get_the_ID();
$vip = ML_Services::is_vip($sid);
$group = ML_Services::group_of($sid);
$level = ML_Users::level(get_current_user_id());
$logged = is_user_logged_in();

// Form tạo đơn: HTML gốc + danh sách server lấy từ CSDL
$form_html = ML_Services::meta($sid, 'form_html');
$form_html = str_replace(['<!--ML_SERVERS-->', '{{ML_SERVICE_ID}}'], [ML_Services::render_servers($sid, $level), (string) $sid], $form_html);
$form_html = ml_fix_urls($form_html);
$attrs = json_decode((string) ML_Services::meta($sid, 'form_attrs', '{}'), true) ?: ($vip ? ['creater-orders' => '', 'class' => 'form-horizontal', 'method' => 'POST'] : ['class' => 'form-horizontal', 'id' => 'form-create']);
$attr_html = '';
foreach ($attrs as $k => $v) {
    $attr_html .= ' ' . esc_attr($k) . ($v === '' ? '' : '="' . esc_attr($v) . '"');
}

// JS riêng của trang (giống inline script của site gốc)
$inline = '';
if (!$vip) {
    $def = ML_DIR . 'data/service-default.js';
    $inline .= ML_Services::meta($sid, 'inline_js');
    $inline .= "\n" . (file_exists($def) ? file_get_contents($def) : '');
} else {
    $inline = ML_Services::meta($sid, 'inline_js');
}
add_action('wp_enqueue_scripts', function () use ($inline, $vip) {
    wp_add_inline_script($vip ? 'mualikes-custom-service' : 'mualikes-order', $inline, 'before');
}, 30);

get_header();
mlt_content_header(get_the_title(), [$vip ? 'Dịch vụ VIP' : ($group ? $group->name : '')]);
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9">
                <div class="card card-primary card-outline card-tabs">
                    <div class="card-header p-0 pt-1 border-bottom-0">
                        <ul class="nav nav-tabs" id="service-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="service-tabs-create" data-toggle="pill" href="#service-tabs-create-content" role="tab" aria-controls="service-tabs-create-content" aria-selected="true">Tạo tiến trình</a>
                            </li>
                            <?php if ($logged) : ?>
                                <li class="nav-item">
                                    <a class="nav-link" id="service-tabs-list" data-loaded="0" data-toggle="pill" href="#service-tabs-list-content" role="tab" aria-controls="service-tabs-list-content" aria-selected="false">Lịch sử đơn</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if (!$logged) : ?>
                            <div role="alert" class="alert bg-red-alert py-2">
                                <div class="cl-red mb-0">
                                    Vui lòng <a href="<?php echo esc_url(home_url('/auth/login/')); ?>"><strong>đăng nhập</strong></a> hoặc <a href="<?php echo esc_url(home_url('/auth/register/')); ?>"><strong>đăng ký</strong></a> để sử dụng dịch vụ!
                                </div>
                            </div>
                        <?php else : ?>
                            <div role="alert" class="alert alert-default-info py-2">
                                Số dư: <strong class="text-danger"><?php echo esc_html(ml_number(ML_Users::balance(get_current_user_id()))); ?> đ</strong> – Cấp bậc: <strong><?php echo esc_html(ML_Users::level_name(get_current_user_id())); ?></strong> – <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>">Nạp tiền</a>
                            </div>
                        <?php endif; ?>
                        <div class="tab-content" id="service-tabsContent">
                            <div class="tab-pane fade active show" id="service-tabs-create-content" role="tabpanel" aria-labelledby="service-tabs-create">
                                <form<?php echo $attr_html; ?>>
                                    <?php echo $form_html; // HTML form do admin quản lý (đã nhập từ site gốc) ?>
                                </form>
                            </div>
                            <?php if ($logged) : ?>
                                <div class="tab-pane fade" id="service-tabs-list-content" role="tabpanel" aria-labelledby="service-tabs-list">
                                    <?php mlt_history_table(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="modalServer" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div id="modal-server-content">
                                <div class="text-center p-3">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <?php if ($video = ML_Services::meta($sid, 'guide_video')) : ?>
                    <div class="card card-primary card-outline">
                        <div class="card-header"><i class="fa-solid fa-circle-info"></i> Hướng dẫn sử dụng</div>
                        <div class="card-body p-2">
                            <div class="embed-responsive embed-responsive-16by9">
                                <iframe class="embed-responsive-item" src="<?php echo esc_url($video); ?>" allowfullscreen="allowfullscreen"></iframe>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($notes = ML_Services::meta($sid, 'notes_html')) : ?>
                    <div class="card card-primary card-outline">
                        <div class="card-header"><i class="fa-solid fa-book"></i> Lưu ý </div>
                        <div class="card-body p-2">
                            <?php echo ml_fix_urls($notes); // đã lọc khi lưu ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php mlt_support_cards(); ?>
            </div>
        </div>
        <?php
        $content = apply_filters('the_content', get_the_content());
        if (trim(wp_strip_all_tags($content)) !== '') :
            [$toc, $content] = mlt_toc($content); ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="content">
                                <?php echo $toc; ?>
                                <?php echo $content; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
