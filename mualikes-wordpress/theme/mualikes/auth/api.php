<?php
$uid = get_current_user_id();
get_header();
mlt_content_header('Tích hợp API', []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">API Token</h3></div>
            <div class="card-body">
                <form id="form-token" method="POST">
                    <div class="input-group">
                        <input type="text" class="form-control" id="api-token" value="<?php echo esc_attr(ML_Users::api_token($uid)); ?>" readonly>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-info copy" data-clipboard-target="#api-token" onclick="this.setAttribute('data-clipboard-text', document.getElementById('api-token').value)"><i class="far fa-copy"></i> Sao chép</button>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-sync"></i> Tạo mới</button>
                        </div>
                    </div>
                </form>
                <p class="mt-3">API URL: <code><?php echo esc_html(home_url('/api/v2')); ?></code> – Phương thức <code>POST</code>. Xem chi tiết tại <a href="<?php echo esc_url(home_url('/docs/document/')); ?>">API Document</a>.</p>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
