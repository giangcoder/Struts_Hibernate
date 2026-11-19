<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<?php
$ml_body_attrs = '';
if (is_singular('ml_service')) {
    $ml_body_attrs = ' data-orders="' . (int) get_the_ID() . '"';
}
?>
<body <?php body_class(); ?><?php echo $ml_body_attrs; ?>>
    <?php wp_body_open(); ?>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav text-nowrap">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i><span class="d-md-none"> Menu</span></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <?php if (mlt_setting('google_translate', 1)) : ?>
                <li class="nav-item mr-2">
                    <a class="nav-link" data-toggle="modal" data-target="#modalLanguage" id="btn-language" role="button"><i class="fas fa-language fa-lg"></i> <span class="d-none d-md-inline">Chọn ngôn ngữ</span></a>
                </li>
                <?php endif; ?>
                <?php if (!is_user_logged_in()) : ?>
                    <li class="nav-item align-self-center mr-2">
                        <a href="<?php echo esc_url(home_url('/auth/login/')); ?>" title="Đăng nhập" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                    </li>
                    <li class="nav-item align-self-center">
                        <a href="<?php echo esc_url(home_url('/auth/register/')); ?>" title="Đăng ký" class="btn btn-secondary btn-sm"><i class="fas fa-user-plus"></i> Đăng ký</a>
                    </li>
                <?php else :
                    $ml_uid = get_current_user_id();
                    $ml_u = wp_get_current_user(); ?>
                    <li class="nav-item align-self-center mr-2">
                        <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>" class="btn btn-primary btn-sm" title="Nạp tiền"><i class="fas fa-wallet"></i> <span class="d-none d-sm-inline"><?php echo mlt_ready() ? esc_html(ml_number(ML_Users::balance($ml_uid))) : 0; ?> đ</span></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-toggle="dropdown" href="#" role="button"><i class="fas fa-user-circle fa-lg"></i> <span class="d-none d-md-inline"><?php echo esc_html($ml_u->user_login); ?></span></a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <span class="dropdown-item-text text-muted small"><?php echo mlt_ready() ? esc_html(ML_Users::level_name($ml_uid)) : ''; ?></span>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo esc_url(home_url('/auth/profile/')); ?>" class="dropdown-item"><i class="fas fa-user mr-2"></i> Thông tin tài khoản</a>
                            <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>" class="dropdown-item"><i class="fas fa-wallet mr-2"></i> Nạp tiền</a>
                            <a href="<?php echo esc_url(home_url('/auth/order-list/')); ?>" class="dropdown-item"><i class="fas fa-history mr-2"></i> Lịch sử đơn hàng</a>
                            <a href="<?php echo esc_url(home_url('/auth/transactions/')); ?>" class="dropdown-item"><i class="fas fa-exchange-alt mr-2"></i> Lịch sử giao dịch</a>
                            <a href="<?php echo esc_url(home_url('/auth/api/')); ?>" class="dropdown-item"><i class="fas fa-code mr-2"></i> Tích hợp API</a>
                            <?php if (current_user_can('manage_options')) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=mualikes')); ?>" class="dropdown-item"><i class="fas fa-cogs mr-2"></i> Quản trị</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo esc_url(home_url('/auth/logout/')); ?>" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php get_template_part('template-parts/sidebar'); ?>
        <div class="content-wrapper">
