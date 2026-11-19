<div class="card" id="card-level">
    <div class="card-header">Cấp bậc tài khoản</div>
    <ul class="list-group list-group-flush border-bottom">
        <?php foreach (ML_Settings::levels() as $i => $l) : ?>
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
