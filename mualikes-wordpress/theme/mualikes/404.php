<?php get_header();
mlt_content_header('Không tìm thấy trang', []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="error-page">
            <h2 class="headline text-warning">404</h2>
            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Không tìm thấy trang.</h3>
                <p>Trang bạn tìm không tồn tại. Quay lại <a href="<?php echo esc_url(home_url('/')); ?>">trang chủ</a>.</p>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
