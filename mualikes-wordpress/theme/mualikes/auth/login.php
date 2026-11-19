<?php require __DIR__ . '/_guest-head.php'; ?>
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?php echo esc_url(home_url()); ?>" class="h1"><img style="max-width: 88%" src="<?php echo esc_url(mlt_img(mlt_setting('logo_green', '/upload/2024/10/logogreenfile.png'))); ?>"></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Vui lòng đăng nhập để sử dụng dịch vụ của chúng tôi</p>
                <form id="form-login-account" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="name" placeholder="Tên đăng nhập hoặc số điện thoại" name="username" value="" required autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Mật khẩu" id="password" name="password" required autocomplete="new-password" minlength="6" />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="social-auth-links text-center">
                        <button type="submit" class="btn btn-block btn-primary">Đăng nhập </button>
                    </div>
                </form>
                <div class="row">
                    <div class="col-6">
                        <a href="<?php echo esc_url(home_url('/auth/forgot-password/')); ?>">Quên mật khẩu ?</a>
                    </div>
                    <div class="col-6 text-right">
                        <a href="<?php echo esc_url(home_url('/auth/register/')); ?>" class="text-center">Đăng ký</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/_guest-foot.php';
