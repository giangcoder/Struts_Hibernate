<?php
get_header();
mlt_content_header('Lịch sử giao dịch', []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-body"><?php mlt_history_table(); ?></div>
        </div>
    </div>
</div>
<script>
var columns = [{data:'id',name:'id',title:'#'},{data:'type',name:'type',title:'Loại'},{data:'before',name:'before',title:'Số dư trước'},{data:'amount',name:'amount',title:'Thay đổi'},{data:'after',name:'after',title:'Số dư sau'},{data:'note',name:'note',title:'Nội dung'},{data:'created',name:'created',title:'Thời gian'}];
document.addEventListener('DOMContentLoaded', function(){ jQuery(function(){ initDataTable(jQuery('#dataTable'), columns, [[0,'desc']], function(d){}, 'transaction-history'); }); });
</script>
<?php get_footer();
