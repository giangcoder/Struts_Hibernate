<?php
$uid = get_current_user_id();
$u = wp_get_current_user();
get_header();
mlt_content_header('Thông tin tài khoản', []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center"><i class="fas fa-user-circle fa-5x text-primary"></i></div>
                        <h3 class="profile-username text-center"><?php echo esc_html($u->user_login); ?></h3>
                        <p class="text-muted text-center"><?php echo esc_html(ML_Users::level_name($uid)); ?></p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item"><b>Số dư</b> <a class="float-right text-danger font-weight-bold"><?php echo esc_html(ml_number(ML_Users::balance($uid))); ?> đ</a></li>
                            <li class="list-group-item"><b>Tổng nạp</b> <a class="float-right"><?php echo esc_html(ml_number(ML_Users::total_deposit($uid))); ?> đ</a></li>
                            <li class="list-group-item"><b>Ngày tham gia</b> <a class="float-right"><?php echo esc_html(date_i18n('d/m/Y', strtotime($u->user_registered))); ?></a></li>
                        </ul>
                        <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>" class="btn btn-primary btn-block"><b>Nạp tiền</b></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Thông tin cá nhân</h3></div>
                    <div class="card-body">
                        <form server-send action="update-profile" method="POST">
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Tên đăng nhập</label><div class="col-sm-9"><input class="form-control" value="<?php echo esc_attr($u->user_login); ?>" disabled></div></div>
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Họ tên</label><div class="col-sm-9"><input class="form-control" name="name" value="<?php echo esc_attr($u->display_name); ?>"></div></div>
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Email</label><div class="col-sm-9"><input type="email" class="form-control" name="email" value="<?php echo esc_attr($u->user_email); ?>"></div></div>
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Số điện thoại</label><div class="col-sm-9"><input class="form-control" name="phone" value="<?php echo esc_attr(get_user_meta($uid, 'ml_phone', true)); ?>"></div></div>
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </form>
                    </div>
                </div>
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Đổi mật khẩu</h3></div>
                    <div class="card-body">
                        <form id="form-change-password" method="POST">
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Mật khẩu hiện tại</label><div class="col-sm-9"><input type="password" class="form-control" name="old_password" required></div></div>
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Mật khẩu mới</label><div class="col-sm-9"><input type="password" class="form-control" name="password" minlength="6" required></div></div>
                            <div class="form-group row"><label class="col-sm-3 col-form-label">Nhập lại mật khẩu</label><div class="col-sm-9"><input type="password" class="form-control" name="password_confirmation" minlength="6" required></div></div>
                            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
