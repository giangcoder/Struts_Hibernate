<?php
get_header();
mlt_content_header('Lịch sử đơn hàng', []);
$vip = !empty($_GET['vip']);
?>
<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link <?php echo $vip ? '' : 'active'; ?>" href="<?php echo esc_url(home_url('/auth/order-list/')); ?>">Đơn dịch vụ</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo $vip ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/auth/order-list/?vip=1')); ?>">Đơn VIP</a></li>
                </ul>
            </div>
            <div class="card-body">
                <?php mlt_history_table(); ?>
            </div>
        </div>
    </div>
</div>
<script>
var cancellationFee = <?php echo (int) mlt_setting('cancellation_fee', 5000); ?>, maxGuaranteeCanceled = <?php echo (int) mlt_setting('max_guarantee_canceled', 3); ?>;
<?php if ($vip) : ?>
var columns = [{data:'id',name:'id',title:'#'},{data:'uid',name:'uid',title:'Uid'},{data:'server',name:'server',title:'Server',class:'text-left'},{data:'note',name:'note',title:'Ghi chú'},{data:'admin_note',name:'admin_note',title:'Admin note'},{data:'price',name:'price',title:'Đơn giá'},{data:'amount',name:'amount',title:'Tổng số tiền'},{data:'vip_package',name:'vip_package',title:'Gói'},{data:'days',name:'days',title:'Thời hạn'},{data:'time_end',name:'time_end',title:'Ngày hết hạn'},{data:'created',name:'created',title:'Thời gian'},{data:'status',name:'status',title:'Trạng thái'},{data:'action',name:'action',searchable:false,orderable:false,title:'Hành động'}];
var table_action = 'order-list-vip';
<?php else : ?>
var columns = [{data:'id',name:'id',title:'#'},{data:'uid',name:'uid',title:'Uid'},{data:'server',name:'server',title:'Server',class:'text-left'},{data:'content',name:'content',title:'Nội dung'},{data:'note',name:'note',title:'Ghi chú'},{data:'admin_note',name:'admin_note',title:'Admin note'},{data:'amount',name:'amount',title:'Số tiền'},{data:'count',name:'count',title:'Số lượng'},{data:'original',name:'original',title:'Bắt đầu'},{data:'present',name:'present',title:'Đã chạy'},{data:'created',name:'created',title:'Thời gian'},{data:'status',name:'status',title:'Trạng thái'},{data:'action',name:'action',searchable:false,orderable:false,title:'Hành động'},{data:'detail',name:'detail',title:'Xem chi tiết'}];
var table_action = 'order-list';
<?php endif; ?>
document.addEventListener('DOMContentLoaded', function(){ jQuery(function(){ initDataTable(jQuery('#dataTable'), columns, [[0,'desc']], function(d){}, table_action); }); });
</script>
<div class="modal fade" id="modalServer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div id="modal-server-content"><div class="text-center p-3"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div></div></div></div>
</div>
<?php get_footer();
