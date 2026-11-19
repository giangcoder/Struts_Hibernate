<?php
$ml_menu = mlt_ready() ? ML_Services::menu() : [];
$ml_current = is_singular('ml_service') ? get_the_ID() : 0;
?>
<aside class="main-sidebar  sidebar-dark-primary elevation-4">
    <a href="<?php echo esc_url(home_url()); ?>" class="brand-link p-2">
        <div class="text-logo show-on-collapse">M</div>
        <img src="<?php echo esc_url(mlt_img(mlt_setting('logo_dark', '/upload/2024/09/logodarkfile.png'))); ?>" alt="<?php echo esc_attr(mlt_setting('site_name', 'MuaLikes.Net')); ?>" class="d-logo hide-on-collapse">
    </a>
    <div id="menu-sidebar" class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar nav-child-indent flex-column nav-default" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Trang chủ</p>
                    </a>
                </li>
                <?php if (is_user_logged_in()) : ?>
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>" class="nav-link ">
                        <i class="nav-icon fas fa-wallet"></i>
                        <p>Nạp tiền</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/auth/order-list/')); ?>" class="nav-link ">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Lịch sử đơn hàng</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/bang-gia-dich-vu/')); ?>" class="nav-link ">
                        <i class="nav-icon fas fa-table"></i>
                        <p>Bảng giá & cấp bậc</p>
                    </a>
                </li>
                <?php foreach ($ml_menu as $g) :
                    $open = in_array($ml_current, array_column($g['items'], 'id'), true); ?>
                    <li class="nav-item menu-item<?php echo $open ? ' menu-is-opening menu-open' : ''; ?>">
                        <a href="#" class="nav-link ">
                            <img src="<?php echo esc_url($g['icon']); ?>" style="max-width: 25px;">
                            <p> <?php echo esc_html($g['name']); ?> <i class="fas fa-angle-left right fa-rotate-270"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($g['items'] as $it) : ?>
                                <li class="nav-item text-nowrap">
                                    <a href="<?php echo esc_url($it['url']); ?>" class="nav-link <?php echo $it['id'] === $ml_current ? 'active' : ''; ?>">
                                        <img src="<?php echo esc_url($it['icon']); ?>" style="max-width: 25px;">
                                        <p><?php if ($it['badge']) : ?><span class="badge <?php echo esc_attr($it['badge_class']); ?> fw-bold text-white"><?php echo esc_html($it['badge']); ?></span> <?php endif; ?><?php echo esc_html($it['title']); ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="nav-link">
                        <i class="nav-icon fas fa-thumbtack"></i>
                        <p>Blog bài viết</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
