<?php
$uid = get_current_user_id();
$code = ML_Users::transfer_code($uid);
$promo = ML_Settings::active_promo();
get_header();
mlt_content_header('Nạp tiền', []);
?>
<div class="content">
    <div class="container-fluid">
        <?php if ($promo > 0) : ?>
            <div class="alert alert-success"><i class="fas fa-gift"></i> Khuyến mãi <strong>+<?php echo esc_html($promo); ?>%</strong> giá trị nạp từ <?php echo esc_html(date_i18n('d/m', strtotime(mlt_setting('promo_from')))); ?> đến <?php echo esc_html(date_i18n('d/m/Y', strtotime(mlt_setting('promo_to')))); ?> (tối thiểu <?php echo esc_html(ml_number(mlt_setting('promo_min'))); ?>đ).</div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary card-outline card-tabs">
                    <div class="card-header p-0 pt-1 border-bottom-0">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-bank" role="tab">Chuyển khoản ngân hàng</a></li>
                            <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-card" role="tab">Thẻ cào</a></li>
                            <?php if (mlt_setting('usdt_address')) : ?><li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-usdt" role="tab">USDT</a></li><?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade active show" id="tab-bank" role="tabpanel">
                                <?php if (!mlt_setting('bank_account')) : ?>
                                    <div class="alert alert-warning">Quản trị viên chưa cấu hình tài khoản ngân hàng (MuaLikes → Cài đặt → Nạp tiền).</div>
                                <?php else : ?>
                                <div class="row">
                                    <div class="col-md-7">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between">Ngân hàng: <strong><?php echo esc_html(mlt_setting('bank_name')); ?></strong></li>
                                            <li class="list-group-item d-flex justify-content-between">Số tài khoản: <strong class="copy text-primary" role="button" data-clipboard-text="<?php echo esc_attr(mlt_setting('bank_account')); ?>"><?php echo esc_html(mlt_setting('bank_account')); ?> <i class="far fa-copy"></i></strong></li>
                                            <li class="list-group-item d-flex justify-content-between">Chủ tài khoản: <strong><?php echo esc_html(mlt_setting('bank_owner')); ?></strong></li>
                                            <li class="list-group-item d-flex justify-content-between">Nội dung chuyển khoản: <strong class="copy text-danger" role="button" data-clipboard-text="<?php echo esc_attr($code); ?>"><?php echo esc_html($code); ?> <i class="far fa-copy"></i></strong></li>
                                            <li class="list-group-item d-flex justify-content-between">Nạp tối thiểu: <strong><?php echo esc_html(ml_number(mlt_setting('min_deposit'))); ?> đ</strong></li>
                                        </ul>
                                        <div class="alert bg-red-alert mt-3 cl-red">
                                            - Vui lòng nhập <strong>đúng nội dung chuyển khoản</strong> để hệ thống tự động cộng tiền sau 1 - 5 phút.<br>
                                            - Chuyển sai nội dung vui lòng chụp ảnh giao dịch và gửi Support kèm tên đăng nhập.
                                        </div>
                                    </div>
                                    <div class="col-md-5 text-center">
                                        <img src="<?php echo esc_url(ML_Payments::qr_url($uid)); ?>" alt="QR nạp tiền" style="max-width:100%;border-radius:8px">
                                        <p class="text-muted mt-2">Quét mã QR bằng ứng dụng ngân hàng</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="tab-pane fade" id="tab-card" role="tabpanel">
                                <form server-send action="payment-card" method="POST">
                                    <div class="form-group row"><label class="col-sm-3 col-form-label">Loại thẻ</label><div class="col-sm-9"><select class="form-control" name="telco"><option>VIETTEL</option><option>VINAPHONE</option><option>MOBIFONE</option><option>VIETNAMOBILE</option><option>ZING</option><option>GATE</option></select></div></div>
                                    <div class="form-group row"><label class="col-sm-3 col-form-label">Mệnh giá</label><div class="col-sm-9"><select class="form-control" name="amount"><?php foreach ([10000, 20000, 30000, 50000, 100000, 200000, 300000, 500000, 1000000] as $a) : ?><option value="<?php echo (int) $a; ?>"><?php echo esc_html(ml_number($a)); ?> đ</option><?php endforeach; ?></select></div></div>
                                    <div class="form-group row"><label class="col-sm-3 col-form-label">Số serial</label><div class="col-sm-9"><input class="form-control" name="serial" required></div></div>
                                    <div class="form-group row"><label class="col-sm-3 col-form-label">Mã thẻ</label><div class="col-sm-9"><input class="form-control" name="code" required></div></div>
                                    <p class="cl-red">Thực nhận <?php echo esc_html(mlt_setting('card_rate', 80)); ?>% mệnh giá. Chọn sai mệnh giá sẽ bị mất thẻ.</p>
                                    <button type="submit" class="btn btn-primary btn-block">Nạp thẻ</button>
                                </form>
                            </div>
                            <?php if (mlt_setting('usdt_address')) : ?>
                            <div class="tab-pane fade" id="tab-usdt" role="tabpanel">
                                <p>Ví USDT (TRC20): <strong class="copy text-primary" role="button" data-clipboard-text="<?php echo esc_attr(mlt_setting('usdt_address')); ?>"><?php echo esc_html(mlt_setting('usdt_address')); ?> <i class="far fa-copy"></i></strong></p>
                                <p>Tỷ giá: 1 USDT = <?php echo esc_html(ml_number(mlt_setting('usdt_rate'))); ?> đ. Sau khi chuyển, gửi mã giao dịch (TxID) + tên đăng nhập cho Support để được cộng tiền.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <?php get_template_part('template-parts/levels-card'); ?>
            </div>
        </div>
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Lịch sử nạp tiền</h3></div>
            <div class="card-body"><?php mlt_history_table(); ?></div>
        </div>
    </div>
</div>
<script>
var columns = [
    {data:'id',name:'id',title:'#'},{data:'gateway',name:'gateway',title:'Hình thức'},{data:'amount',name:'amount',title:'Số tiền'},
    {data:'bonus',name:'bonus',title:'Khuyến mãi'},{data:'content',name:'content',title:'Nội dung'},{data:'status',name:'status',title:'Trạng thái'},{data:'created',name:'created',title:'Thời gian'}
];
document.addEventListener('DOMContentLoaded', function(){ jQuery(function(){ initDataTable(jQuery('#dataTable'), columns, [[0,'desc']], function(d){}, 'payment-history'); }); });
</script>
<?php get_footer();
