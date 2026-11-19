<?php require __DIR__ . '/_guest-head.php'; ?>
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="<?php echo esc_url(home_url()); ?>" class="h1"><img style="max-width: 88%" src="<?php echo esc_url(mlt_img(mlt_setting('logo_green', '/upload/2024/10/logogreenfile.png'))); ?>"></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Đăng ký sử dụng dịch vụ</p>
                <form id="form-register" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="name" placeholder="Tên đăng nhập hoặc số điện thoại" name="username" value="" required autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" minlength="4" maxlength="50" />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email" id="email" name="email" value="" required />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
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
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="Nhập lại mật khẩu" id="password_confirmation" name="password_confirmation" required minlength="6" />
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="icheck-primary">
                                <input type="checkbox" id="agreeTerms" name="terms" value="agree" checked required>
                                <label for="agreeTerms" class="form-check-label">
                                    Tôi đồng ý với các <a href="<?php echo esc_url(home_url('/dieu-khoan-dich-vu/')); ?>" target="_blank" title="điều khoản dịch vụ">điều khoản dịch vụ</a>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="social-auth-links text-center">
                        <button type="submit" class="btn btn-block btn-primary" id="btn-register">
                            Đăng ký </button>
                    </div>
                </form>
                <div class="row">
                    <div class="col-12 text-center">
                        Bạn đã có tài khoản, hãy <a href="<?php echo esc_url(home_url('/auth/login/')); ?>" class="text-center">Đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require __DIR__ . '/_guest-foot.php';
