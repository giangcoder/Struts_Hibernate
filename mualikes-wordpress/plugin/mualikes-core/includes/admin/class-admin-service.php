<?php
if (!defined('ABSPATH')) {
    exit;
}

/** Meta box cho dịch vụ (cấu hình form + danh sách server) và trường meta cho nhóm dịch vụ. */
class ML_Admin_Service
{
    private static $fields = [
        'path' => ['Đường dẫn (URL)', 'text', 'VD: tang-like-bai-viet-facebook hoặc other-service/facebook/vip-like'],
        'kind' => ['Loại', 'select', ''],
        'menu_title' => ['Tên trên menu', 'text', ''],
        'icon' => ['Icon (URL hoặc /images/icon/...)', 'text', ''],
        'badge' => ['Badge nền tảng (VIP)', 'text', 'VD: Facebook'],
        'badge_class' => ['Màu badge (bg-1..bg-4)', 'text', ''],
        'vip_type' => ['Mã loại VIP', 'text', 'VD: FB_VIP_LIKE'],
        'guide_video' => ['Video hướng dẫn (URL embed YouTube)', 'text', ''],
        'meta_title' => ['SEO title', 'text', ''],
        'meta_desc' => ['SEO description', 'textarea', ''],
        'notes_html' => ['Khung "Lưu ý" (HTML)', 'textarea', ''],
        'form_html' => ['HTML form tạo đơn', 'code', 'Giữ nguyên <!--ML_SERVERS--> (vị trí danh sách server) và {{ML_SERVICE_ID}}.'],
        'inline_js' => ['JS riêng của dịch vụ (tuỳ chọn)', 'code', ''],
    ];

    public static function init()
    {
        add_action('add_meta_boxes_' . ML_Services::CPT, function () {
            add_meta_box('ml_servers', 'Danh sách server & giá theo cấp bậc', [__CLASS__, 'box_servers'], ML_Services::CPT, 'normal', 'high');
            add_meta_box('ml_service', 'Cấu hình dịch vụ', [__CLASS__, 'box_service'], ML_Services::CPT, 'normal', 'default');
        });
        add_action('save_post_' . ML_Services::CPT, [__CLASS__, 'save'], 10, 2);
        add_action(ML_Services::TAX . '_edit_form_fields', [__CLASS__, 'term_fields']);
        add_action(ML_Services::TAX . '_add_form_fields', [__CLASS__, 'term_fields']);
        add_action('edited_' . ML_Services::TAX, [__CLASS__, 'term_save']);
        add_action('created_' . ML_Services::TAX, [__CLASS__, 'term_save']);
    }

    public static function box_service($post)
    {
        wp_nonce_field('ml_service_save', 'ml_service_nonce');
        echo '<table class="form-table">';
        foreach (self::$fields as $k => $f) {
            $v = get_post_meta($post->ID, '_ml_' . $k, true);
            echo '<tr><th><label>' . esc_html($f[0]) . '</label></th><td>';
            if ($f[1] === 'select') {
                echo '<select name="ml[' . $k . ']"><option value="normal"' . selected($v, 'normal', false) . '>Dịch vụ thường</option><option value="vip"' . selected($v, 'vip', false) . '>Dịch vụ VIP (theo tháng)</option></select>';
            } elseif ($f[1] === 'textarea') {
                echo '<textarea name="ml[' . $k . ']" rows="4" class="large-text">' . esc_textarea($v) . '</textarea>';
            } elseif ($f[1] === 'code') {
                echo '<textarea name="ml[' . $k . ']" rows="12" class="large-text code" style="font-family:monospace">' . esc_textarea($v) . '</textarea>';
            } else {
                echo '<input type="text" name="ml[' . $k . ']" value="' . esc_attr($v) . '" class="regular-text" style="width:100%">';
            }
            if ($f[2]) {
                echo '<p class="description">' . esc_html($f[2]) . '</p>';
            }
            echo '</td></tr>';
        }
        echo '</table><p class="description">Nội dung bài viết SEO bên dưới form được soạn trong trình soạn thảo chính.</p>';
    }

    public static function box_servers($post)
    {
        $servers = ML_Services::servers($post->ID);
        $providers = ML_Providers::all();
        $levels = ML_Settings::levels();
        echo '<p class="description">Đơn giá tính theo 1 tương tác (VNĐ). Giá cột 2, 3 áp dụng cho cấp bậc ' . esc_html(($levels[1]['name'] ?? '') . ' / ' . ($levels[2]['name'] ?? '')) . '. Server không gắn nhà cung cấp sẽ chờ admin xử lý thủ công trong MuaLikes → Đơn hàng.</p>';
        echo '<table class="widefat striped ml-servers"><thead><tr><th style="width:60px">ID</th><th style="width:90px">Nhãn</th><th>Tên / Ghi chú</th><th style="width:220px">Giá ' . esc_html(implode(' / ', array_column($levels, 'name'))) . '</th><th style="width:120px">Min / Max</th><th style="width:170px">Nhà cung cấp</th><th style="width:170px">Tuỳ chọn</th><th style="width:40px">Xoá</th></tr></thead><tbody id="ml-server-rows">';
        $rows = $servers ?: [];
        $i = 0;
        foreach ($rows as $s) {
            self::server_row($i++, $s, $providers);
        }
        echo '</tbody></table>';
        echo '<script type="text/template" id="ml-server-tpl">';
        self::server_row('__i__', null, $providers);
        echo '</script>';
        echo '<p><button type="button" class="button" id="ml-add-server">+ Thêm server</button></p>';
        echo '<script>jQuery(function($){var n=' . (int) $i . ';$("#ml-add-server").on("click",function(){$("#ml-server-rows").append($("#ml-server-tpl").html().replace(/__i__/g,n++));});});</script>';
    }

    private static function server_row($i, $s, $providers)
    {
        $a = $s && is_array($s->attrs) ? $s->attrs : [];
        $n = 'ml_servers[' . $i . ']';
        echo '<tr>';
        echo '<td><input type="number" name="' . $n . '[id]" value="' . esc_attr($s->id ?? '') . '" placeholder="tự động"></td>';
        echo '<td><input type="text" name="' . $n . '[label]" value="' . esc_attr($s->label ?? 'Server ') . '"><label><input type="checkbox" name="' . $n . '[active]" value="1"' . checked($s ? $s->active : 1, 1, false) . '> Hoạt động</label></td>';
        echo '<td><input type="text" name="' . $n . '[name]" value="' . esc_attr($s->name ?? '') . '" placeholder="Mô tả ngắn"><textarea name="' . $n . '[note]" placeholder="Ghi chú hiển thị khi chọn server (HTML)">' . esc_textarea($s->note ?? '') . '</textarea></td>';
        echo '<td><input type="text" name="' . $n . '[price_1]" value="' . esc_attr($s ? ml_price($s->price_1) : '') . '" placeholder="Giá 1"><input type="text" name="' . $n . '[price_2]" value="' . esc_attr($s ? ml_price($s->price_2) : '') . '" placeholder="Giá 2"><input type="text" name="' . $n . '[price_3]" value="' . esc_attr($s ? ml_price($s->price_3) : '') . '" placeholder="Giá 3"></td>';
        echo '<td><input type="number" name="' . $n . '[min_qty]" value="' . esc_attr($s->min_qty ?? 0) . '"><input type="number" name="' . $n . '[max_qty]" value="' . esc_attr($s->max_qty ?? 0) . '"><input type="number" name="' . $n . '[sort]" value="' . esc_attr($s->sort ?? 0) . '" title="Thứ tự"></td>';
        echo '<td><select name="' . $n . '[provider_id]" style="width:100%"><option value="0">— Thủ công —</option>';
        foreach ($providers as $p) {
            echo '<option value="' . (int) $p->id . '"' . selected($s->provider_id ?? 0, $p->id, false) . '>' . esc_html($p->name) . '</option>';
        }
        echo '</select><input type="text" name="' . $n . '[provider_service]" value="' . esc_attr($s->provider_service ?? '') . '" placeholder="Mã dịch vụ bên NCC">';
        echo '<label><input type="checkbox" name="' . $n . '[refill]" value="1"' . checked($s->refill ?? 0, 1, false) . '> Bảo hành</label></td>';
        echo '<td><small>Cảm xúc (like,love,...)</small><input type="text" name="' . $n . '[attrs][reactions]" value="' . esc_attr($a['reactions'] ?? '') . '">';
        foreach (['multi' => 'Nhiều cảm xúc', 'comment' => 'Nhập nội dung', 'dripfeed' => 'Hẹn giờ', 'video-by-time' => 'Theo thời lượng video', 'max-post' => 'Chọn số bài (VIP)'] as $k => $lb) {
            echo '<label style="display:block"><input type="checkbox" name="' . $n . '[attrs][' . $k . ']" value="1"' . checked(!empty($a[$k]) && $a[$k] !== '0', true, false) . '> ' . esc_html($lb) . '</label>';
        }
        echo '<input type="hidden" name="' . $n . '[attrs][speed]" value="' . esc_attr($a['speed'] ?? '') . '"></td>';
        echo '<td><input type="checkbox" name="' . $n . '[_delete]" value="1"></td>';
        echo '</tr>';
    }

    public static function save($post_id, $post)
    {
        if (!isset($_POST['ml_service_nonce']) || !wp_verify_nonce($_POST['ml_service_nonce'], 'ml_service_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
            return;
        }
        $in = wp_unslash($_POST['ml'] ?? []);
        foreach (self::$fields as $k => $f) {
            if (!isset($in[$k])) {
                continue;
            }
            $v = $in[$k];
            if (in_array($k, ['form_html', 'inline_js', 'notes_html'], true)) {
                $v = current_user_can('unfiltered_html') ? $v : wp_kses_post($v);
            } elseif ($k === 'path') {
                $v = trim(preg_replace('#[^a-z0-9/_-]#', '', strtolower($v)), '/');
                if ($v === '') {
                    $v = $post->post_name;
                }
            } else {
                $v = sanitize_text_field($v);
            }
            update_post_meta($post_id, '_ml_' . $k, wp_slash($v));
        }
        global $wpdb;
        $rows = wp_unslash($_POST['ml_servers'] ?? []);
        foreach ($rows as $r) {
            if (!empty($r['_delete'])) {
                if (!empty($r['id'])) {
                    $wpdb->delete(ML_DB::t('servers'), ['id' => (int) $r['id'], 'service_id' => $post_id]);
                }
                continue;
            }
            if (empty($r['label']) && empty($r['name'])) {
                continue;
            }
            if (!empty($r['id'])) {
                $owner = $wpdb->get_var($wpdb->prepare("SELECT service_id FROM " . ML_DB::t('servers') . " WHERE id = %d", (int) $r['id']));
                if ($owner && (int) $owner !== $post_id) {
                    unset($r['id']); // ID đã thuộc dịch vụ khác → tạo mới
                }
            }
            $attrs = [];
            foreach (($r['attrs'] ?? []) as $k => $v) {
                $attrs[sanitize_key($k)] = sanitize_text_field($v);
            }
            $r['attrs'] = $attrs;
            $r['service_id'] = $post_id;
            ML_Services::save_server($r);
        }
        wp_cache_delete('ml_menu', 'mualikes');
    }

    public static function term_fields($term)
    {
        $id = is_object($term) ? $term->term_id : 0;
        $f = ['icon' => 'Icon (URL)', 'anchor' => 'Anchor trang chủ (VD: facebook)', 'order' => 'Thứ tự'];
        foreach ($f as $k => $lb) {
            $v = $id ? get_term_meta($id, $k, true) : '';
            echo '<tr class="form-field"><th><label>' . esc_html($lb) . '</label></th><td><input type="text" name="ml_term[' . $k . ']" value="' . esc_attr($v) . '"></td></tr>';
        }
        $vip = $id ? get_term_meta($id, 'vip', true) : 0;
        echo '<tr class="form-field"><th>Nhóm VIP</th><td><label><input type="checkbox" name="ml_term[vip]" value="1"' . checked($vip, 1, false) . '> Đây là nhóm "Dịch vụ vip"</label></td></tr>';
    }

    public static function term_save($term_id)
    {
        if (!isset($_POST['ml_term']) || !current_user_can('manage_categories')) {
            return;
        }
        $in = wp_unslash($_POST['ml_term']);
        foreach (['icon', 'anchor', 'order'] as $k) {
            update_term_meta($term_id, $k, sanitize_text_field($in[$k] ?? ''));
        }
        update_term_meta($term_id, 'vip', empty($in['vip']) ? 0 : 1);
        wp_cache_delete('ml_menu', 'mualikes');
    }
}
