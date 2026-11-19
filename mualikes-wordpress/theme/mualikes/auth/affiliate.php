<?php
$uid = get_current_user_id();
$link = home_url('/ref/' . $uid . '/');
$refs = get_users(['meta_key' => 'ml_referrer', 'meta_value' => $uid, 'fields' => ['ID', 'user_login', 'user_registered']]);
get_header();
mlt_content_header('Tiếp thị liên kết', []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-body">
                <p>Link giới thiệu của bạn:</p>
                <div class="input-group mb-3"><input class="form-control" value="<?php echo esc_attr($link); ?>" readonly><div class="input-group-append"><button class="btn btn-info copy" data-clipboard-text="<?php echo esc_attr($link); ?>"><i class="far fa-copy"></i> Sao chép</button></div></div>
                <p>Số người đã giới thiệu: <strong><?php echo count($refs); ?></strong></p>
                <table class="table table-bordered table-striped"><thead><tr><th>Tài khoản</th><th>Ngày đăng ký</th></tr></thead><tbody>
                    <?php foreach ($refs as $r) : ?><tr><td><?php echo esc_html(ml_mask_user($r->user_login)); ?></td><td><?php echo esc_html($r->user_registered); ?></td></tr><?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
