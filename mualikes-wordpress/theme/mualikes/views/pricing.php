<?php
get_header();
mlt_content_header('Bảng Giá Dịch Vụ', false);
$levels = mlt_ready() ? ML_Settings::levels() : [];
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Cấp bậc tài khoản</h3>
                    </div>
                    <div class="card-body">
                        <div class="card-deck text-center">
                            <?php foreach ($levels as $l) : ?>
                                <div class="card box-shadow card-info">
                                    <div class="card-header" style="background-color: <?php echo esc_attr($l['color']); ?> !important;">
                                        <h4 class="my-0 font-weight-normal"><?php echo esc_html($l['name']); ?></h4>
                                    </div>
                                    <div class="card-body">
                                        <small class="text-muted">Tổng nạp từ</small>
                                        <h5 class="pricing-card-title h1"><?php echo esc_html(ml_number($l['min'])); ?> <small class="text-muted">đ</small></h5>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ((array) $l['perks'] as $p) : ?>
                                                <li class="list-group-item"><?php echo esc_html($p); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <a href="<?php echo esc_url(home_url('/auth/payment/')); ?>" class="btn btn-primary">Nâng cấp</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Bảng giá dịch vụ (đơn vị: đ)</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table cell-border table-striped table-bordered table-hover text-center" id="priceTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th style="width: 10px;">#</th>
                                        <th class="text-left" width="25%">Dịch vụ</th>
                                        <?php foreach ($levels as $l) : ?><th width="12%"><?php echo esc_html($l['name']); ?></th><?php endforeach; ?>
                                        <th>Mua ngay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (mlt_ready() ? ML_Services::menu() : [] as $g) :
                                        if ($g['vip']) {
                                            continue;
                                        }
                                        $n = 0; ?>
                                        <tr>
                                            <td colspan="<?php echo 3 + count($levels); ?>" class="table-primary"><strong><?php echo esc_html($g['name']); ?></strong></td>
                                        </tr>
                                        <?php foreach ($g['items'] as $it) :
                                            $servers = ML_Services::servers($it['id']); ?>
                                            <tr>
                                                <td><?php echo ++$n; ?></td>
                                                <td class="text-left"><?php echo esc_html($it['title']); ?></td>
                                                <?php foreach ($levels as $i => $l) : ?>
                                                    <td>
                                                        <?php foreach ($servers as $s) : ?>
                                                            <?php echo esc_html($s->label); ?>: <span class="font-weight-bold cl-red"><?php echo esc_html(ml_price(ML_Services::server_price($s, $i + 1))); ?> đ</span><br>
                                                        <?php endforeach; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td><a class="btn btn-sm btn-primary" href="<?php echo esc_url($it['url']); ?>"><i class="fas fa-shopping-cart"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card mb-4" id="card-level">
                    <div class="card-body cl-red">
                        <h5 class="cl-red"><i class="icon fas fa-ban"></i> Chú ý:</h5>
                        <div class="card-text">
                            <?php foreach (array_filter(array_map('trim', preg_split('/\r\n|\n/', (string) mlt_setting('level_notes', '')))) as $line) : ?>
                                <p class="mb-2"><?php echo esc_html($line); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
