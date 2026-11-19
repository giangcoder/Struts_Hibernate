<?php
get_header();
mlt_content_header('API Document', []);
$api = home_url('/api/v2');
$blocks = [
    ['Danh sách dịch vụ', 'services', ['key' => 'API token của bạn', 'action' => 'services'],
        '[{"service":755,"name":"Tăng Like Bài Viết - Server 541: Like Global, tốc độ chậm","type":"Default","category":"Dịch vụ Facebook","rate":29900,"min":100,"max":100000,"refill":false,"cancel":false}]'],
    ['Tạo đơn hàng', 'add', ['key' => 'API token', 'action' => 'add', 'service' => 'ID server (cột "service" ở danh sách dịch vụ)', 'link' => 'Link / UID cần tăng', 'quantity' => 'Số lượng', 'comments' => '(Tuỳ chọn) Nội dung bình luận, mỗi dòng 1 bình luận', 'reaction' => '(Tuỳ chọn) like, love, care, haha, wow, sad, angry'],
        '{"order":23501}'],
    ['Trạng thái đơn hàng', 'status', ['key' => 'API token', 'action' => 'status', 'order' => 'ID đơn hàng'],
        '{"charge":"2990.00","start_count":"120","status":"In progress","remains":"40","currency":"VND"}'],
    ['Trạng thái nhiều đơn', 'status', ['key' => 'API token', 'action' => 'status', 'orders' => 'Danh sách ID, phân tách dấu phẩy (tối đa 100)'],
        '{"1":{"charge":"2990.00","start_count":"120","status":"Completed","remains":"0","currency":"VND"},"10":{"error":"Incorrect order ID"}}'],
    ['Bảo hành đơn', 'refill', ['key' => 'API token', 'action' => 'refill', 'order' => 'ID đơn hàng'], '{"refill":23501}'],
    ['Số dư tài khoản', 'balance', ['key' => 'API token', 'action' => 'balance'], '{"balance":"1500000.00","currency":"VND"}'],
];
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-3">
                <div class="card card-primary card-outline">
                    <div class="card-header">Mục lục</div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($blocks as $i => $b) : ?>
                            <a href="#api-<?php echo (int) $i; ?>" class="list-group-item list-group-item-action scroll-docs"><?php echo esc_html($b[0]); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><th style="width:200px">API URL</th><td><code><?php echo esc_html($api); ?></code></td></tr>
                            <tr><th>HTTP Method</th><td>POST</td></tr>
                            <tr><th>Định dạng phản hồi</th><td>JSON</td></tr>
                            <tr><th>API Key</th><td><?php if (is_user_logged_in()) : ?>Lấy tại <a href="<?php echo esc_url(home_url('/auth/api/')); ?>">Tích hợp API</a><?php else : ?><a href="<?php echo esc_url(home_url('/auth/login/')); ?>">Đăng nhập</a> để lấy API token<?php endif; ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php foreach ($blocks as $i => $b) : ?>
                    <div class="card card-primary card-outline" id="api-<?php echo (int) $i; ?>">
                        <div class="card-header"><h3 class="card-title"><?php echo esc_html($b[0]); ?></h3></div>
                        <div class="card-body">
                            <table class="table table-bordered table-sm">
                                <thead><tr><th style="width:200px">Tham số</th><th>Mô tả</th></tr></thead>
                                <tbody>
                                    <?php foreach ($b[2] as $k => $v) : ?><tr><td><code><?php echo esc_html($k); ?></code></td><td><?php echo esc_html($v); ?></td></tr><?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="mb-1"><strong>Ví dụ phản hồi</strong></p>
                            <pre class="bg-light p-2 rounded" style="white-space:pre-wrap"><?php echo esc_html(wp_json_encode(json_decode($b[3]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Ví dụ PHP</h3></div>
                    <div class="card-body"><pre class="bg-light p-2 rounded"><?php echo esc_html("<?php\n\$ch = curl_init('" . $api . "');\ncurl_setopt_array(\$ch, [\n    CURLOPT_POST => true,\n    CURLOPT_RETURNTRANSFER => true,\n    CURLOPT_POSTFIELDS => http_build_query([\n        'key' => 'API_TOKEN',\n        'action' => 'add',\n        'service' => 755,\n        'link' => 'https://www.facebook.com/...',\n        'quantity' => 100,\n    ]),\n]);\necho curl_exec(\$ch);"); ?></pre></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
