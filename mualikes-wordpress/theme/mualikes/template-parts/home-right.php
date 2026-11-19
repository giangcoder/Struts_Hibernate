<div class="col-lg-3">
    <div class="card" id="card-level">
        <div class="card-header">Cấp bậc tài khoản</div>
        <ul class="list-group list-group-flush border-bottom">
            <?php foreach (mlt_ready() ? ML_Settings::levels() : [] as $i => $l) : ?>
                <li class="list-group-item p-1">
                    <div class="info-box shadow-none mb-0">
                        <span class="info-box-icon bg-primary" style="background-color: <?php echo esc_attr($l['color']); ?> !important;"><?php echo $i + 1; ?>. </span>
                        <div class="info-box-content">
                            <span class="info-box-text font-weight-bold text-uppercase" style="color:<?php echo esc_attr($l['color']); ?> !important;"><?php echo esc_html($l['name']); ?></span>
                            <span class="info-box-text">Tổng nạp từ <span class="font-weight-bold"><?php echo esc_html(ml_number($l['min'])); ?></span> <small>đ</small></span>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="card-body cl-red">
            <h5 class="cl-red"><i class="icon fas fa-ban"></i> Chú ý:</h5>
            <div class="card-text">
                <?php foreach (array_filter(array_map('trim', preg_split('/\r\n|\n/', (string) mlt_setting('level_notes', '')))) as $line) : ?>
                    <p class="mb-2"><?php echo esc_html($line); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="card" style="max-height: 500px;">
        <h5 class="card-header">
            <i class="fas fa-bell mr-1"></i> Thông báo mới
        </h5>
        <ul class="list-group list-group-flush overflow-auto rounded-bottom mb-2">
            <?php
            $ml_notices = get_posts(['post_type' => 'ml_notice', 'numberposts' => 20, 'post_status' => 'publish']);
            foreach ($ml_notices as $n) : ?>
                <li class="list-group-item">
                    <p><?php echo function_exists('ml_fix_urls') ? ml_fix_urls($n->post_content) : $n->post_content; ?></p>
                    <p class="card-text">
                        <small class="text-muted"><i class="fas fa-clock"></i> <?php echo esc_html(get_the_date('H:i d/m/Y', $n)); ?></small>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card" style="max-height: 500px;">
        <h5 class="card-header">
            <i class="fas fa-shopping-cart mr-1"></i> Đơn hàng mới
        </h5>
        <ul class="list-group list-group-flush overflow-auto rounded-bottom mb-2">
            <?php foreach (mlt_ready() ? ML_Orders::recent((int) mlt_setting('recent_orders_count', 50)) : [] as $o) : ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-danger"><strong><?php echo esc_html(ml_mask_user($o->user_login)); ?></strong></span>
                    <span><em><span class="badge bg-info fw-bold text-white"><?php echo esc_html($o->label); ?></span></em></span>
                    <span class="text-primary"><strong>SL: <?php echo esc_html(ml_number($o->quantity)); ?></strong></span>
                    <span class="badge badge-primary badge-pill"><?php echo esc_html(ml_time_ago($o->created_at)); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
