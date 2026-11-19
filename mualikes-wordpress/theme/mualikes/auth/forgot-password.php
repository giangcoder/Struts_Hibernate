<?php require __DIR__ . '/_guest-head.php'; ?>
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?php echo esc_url(home_url()); ?>" class="h1"><img style="max-width: 88%" src="<?php echo esc_url(mlt_img(mlt_setting('logo_green', '/upload/2024/10/logogreenfile.png'))); ?>"></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Nhập địa chỉ email đã đăng ký của bạn để nhận hướng dẫn đặt lại mật khẩu.</p>
                <form id="form-forgot-password" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="email" placeholder="Địa chỉ email của bạn" name="email" value="" required autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" minlength="4" maxlength="50" />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="social-auth-links text-center">
                        <button type="submit" class="btn btn-block btn-primary">Gửi yêu cầu</button>
                    </div>
                </form>
                <div class="row">
                    <div class="col-6"><a href="<?php echo esc_url(home_url('/auth/login/')); ?>">Đăng nhập</a></div>
                    <div class="col-6 text-right"><a href="<?php echo esc_url(home_url('/auth/register/')); ?>">Đăng ký</a></div>
                </div>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/_guest-foot.php';
