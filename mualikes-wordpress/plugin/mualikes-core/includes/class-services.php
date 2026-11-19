<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dịch vụ (CPT ml_service), nhóm dịch vụ (taxonomy ml_group), thông báo (CPT ml_notice) và server.
 */
class ML_Services
{
    const CPT = 'ml_service';
    const TAX = 'ml_group';
    const NOTICE = 'ml_notice';

    public static function init()
    {
        add_action('init', [__CLASS__, 'register'], 5);
        add_filter('post_type_link', [__CLASS__, 'permalink'], 10, 2);
        add_action('save_post_' . self::CPT, [__CLASS__, 'on_save'], 20);
        add_action('deleted_post', [__CLASS__, 'on_delete']);
        // Soạn dịch vụ bằng trình soạn thảo cổ điển để giữ nguyên HTML bài viết/form gốc
        add_filter('use_block_editor_for_post_type', function ($use, $type) {
            return in_array($type, [self::CPT, self::NOTICE], true) ? false : $use;
        }, 10, 2);
    }

    public static function register()
    {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'Dịch vụ', 'singular_name' => 'Dịch vụ', 'add_new' => 'Thêm dịch vụ',
                'add_new_item' => 'Thêm dịch vụ', 'edit_item' => 'Sửa dịch vụ', 'all_items' => 'Tất cả dịch vụ',
                'menu_name' => 'Dịch vụ SMM',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'menu_icon' => 'dashicons-thumbs-up',
            'menu_position' => 25,
            'supports' => ['title', 'editor', 'thumbnail', 'page-attributes', 'revisions'],
        ]);
        register_taxonomy(self::TAX, self::CPT, [
            'labels' => ['name' => 'Nhóm dịch vụ', 'singular_name' => 'Nhóm dịch vụ', 'menu_name' => 'Nhóm dịch vụ'],
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => false,
        ]);
        register_post_type(self::NOTICE, [
            'labels' => ['name' => 'Thông báo', 'singular_name' => 'Thông báo', 'add_new_item' => 'Thêm thông báo', 'menu_name' => 'Thông báo mới'],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 26,
            'supports' => ['title', 'editor'],
        ]);
    }

    /** Đường dẫn của dịch vụ, ví dụ "tang-like-bai-viet-facebook" hoặc "other-service/facebook/vip-like". */
    public static function path($post_id)
    {
        $p = get_post_meta($post_id, '_ml_path', true);
        if (!$p) {
            $p = get_post_field('post_name', $post_id);
        }
        return trim($p, '/');
    }

    public static function permalink($link, $post)
    {
        if ($post->post_type === self::CPT) {
            return home_url('/' . self::path($post->ID) . '/');
        }
        return $link;
    }

    public static function paths()
    {
        $paths = get_option('ml_service_paths');
        if (!is_array($paths)) {
            $paths = self::rebuild_paths();
        }
        return $paths;
    }

    public static function rebuild_paths()
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_name, m.meta_value AS path FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_ml_path'
             WHERE p.post_type = %s AND p.post_status = 'publish'",
            self::CPT
        ));
        $paths = [];
        foreach ($rows as $r) {
            $paths[trim($r->path ?: $r->post_name, '/')] = (int) $r->ID;
        }
        update_option('ml_service_paths', $paths, true);
        return $paths;
    }

    public static function on_save($post_id)
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        self::rebuild_paths();
        update_option('ml_flush_rules', 1);
    }

    public static function on_delete($post_id)
    {
        if (get_post_type($post_id) === self::CPT) {
            global $wpdb;
            $wpdb->delete(ML_DB::t('servers'), ['service_id' => $post_id]);
            self::rebuild_paths();
            update_option('ml_flush_rules', 1);
        }
    }

    public static function meta($post_id, $key, $default = '')
    {
        $v = get_post_meta($post_id, '_ml_' . $key, true);
        return ($v === '' || $v === null) ? $default : $v;
    }

    public static function is_vip($post_id)
    {
        return self::meta($post_id, 'kind') === 'vip';
    }

    /** Menu: các nhóm (theo thứ tự) + dịch vụ trong từng nhóm. */
    public static function menu()
    {
        $cached = wp_cache_get('ml_menu', 'mualikes');
        if ($cached !== false) {
            return $cached;
        }
        $terms = get_terms(['taxonomy' => self::TAX, 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            return [];
        }
        usort($terms, function ($a, $b) {
            return (int) get_term_meta($a->term_id, 'order', true) <=> (int) get_term_meta($b->term_id, 'order', true);
        });
        $out = [];
        foreach ($terms as $t) {
            $posts = get_posts([
                'post_type' => self::CPT, 'numberposts' => -1, 'post_status' => 'publish',
                'tax_query' => [['taxonomy' => self::TAX, 'terms' => $t->term_id]],
                'orderby' => 'menu_order', 'order' => 'ASC',
            ]);
            $items = [];
            foreach ($posts as $p) {
                $items[] = [
                    'id' => $p->ID,
                    'title' => self::meta($p->ID, 'menu_title', $p->post_title),
                    'url' => get_permalink($p),
                    'icon' => ml_asset(self::meta($p->ID, 'icon')),
                    'badge' => self::meta($p->ID, 'badge'),
                    'badge_class' => self::meta($p->ID, 'badge_class'),
                ];
            }
            $out[] = [
                'id' => $t->term_id,
                'name' => $t->name,
                'icon' => ml_asset(get_term_meta($t->term_id, 'icon', true)),
                'anchor' => get_term_meta($t->term_id, 'anchor', true),
                'vip' => (bool) get_term_meta($t->term_id, 'vip', true),
                'items' => $items,
            ];
        }
        wp_cache_set('ml_menu', $out, 'mualikes', 300);
        return $out;
    }

    public static function group_of($post_id)
    {
        $terms = wp_get_post_terms($post_id, self::TAX);
        return $terms && !is_wp_error($terms) ? $terms[0] : null;
    }

    /* ------------------------------ Server ------------------------------ */

    public static function servers($service_id, $only_active = false)
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM " . ML_DB::t('servers') . " WHERE service_id = %d", $service_id);
        if ($only_active) {
            $sql .= " AND active = 1";
        }
        $sql .= " ORDER BY sort ASC, id ASC";
        $rows = $wpdb->get_results($sql);
        foreach ($rows as $r) {
            $r->attrs = $r->attrs ? json_decode($r->attrs, true) : [];
        }
        return $rows;
    }

    public static function server($id)
    {
        global $wpdb;
        $r = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . ML_DB::t('servers') . " WHERE id = %d", $id));
        if ($r) {
            $r->attrs = $r->attrs ? json_decode($r->attrs, true) : [];
        }
        return $r;
    }

    /** Giá theo cấp bậc (1..3) – cấp bậc lớn hơn 3 dùng giá cấp cao nhất. */
    public static function server_price($server, $level)
    {
        $level = max(1, min(3, (int) $level));
        $p = (float) $server->{'price_' . $level};
        if ($p <= 0) {
            $p = (float) $server->price_1;
        }
        return $p;
    }

    /** true khi nhập dữ liệu (importer/CLI) – giữ nguyên HTML gốc (style màu chữ...). */
    public static $trusted = false;

    private static function clean_html($html)
    {
        return (self::$trusted || current_user_can('unfiltered_html')) ? (string) $html : wp_kses_post($html);
    }

    public static function save_server(array $data)
    {
        global $wpdb;
        $row = [
            'service_id' => (int) $data['service_id'],
            'label' => sanitize_text_field($data['label'] ?? ''),
            'name' => self::clean_html($data['name'] ?? ''),
            'note' => self::clean_html($data['note'] ?? ''),
            'price_1' => (float) ($data['price_1'] ?? 0),
            'price_2' => (float) ($data['price_2'] ?? 0),
            'price_3' => (float) ($data['price_3'] ?? 0),
            'min_qty' => (int) ($data['min_qty'] ?? 0),
            'max_qty' => (int) ($data['max_qty'] ?? 0),
            'active' => empty($data['active']) ? 0 : 1,
            'status_text' => sanitize_text_field($data['status_text'] ?? ''),
            'attrs' => wp_json_encode(isset($data['attrs']) && is_array($data['attrs']) ? $data['attrs'] : []),
            'sort' => (int) ($data['sort'] ?? 0),
            'provider_id' => (int) ($data['provider_id'] ?? 0),
            'provider_service' => sanitize_text_field($data['provider_service'] ?? ''),
            'refill' => empty($data['refill']) ? 0 : 1,
            'cancel' => empty($data['cancel']) ? 0 : 1,
        ];
        if (!empty($data['id']) && self::server((int) $data['id'])) {
            $wpdb->update(ML_DB::t('servers'), $row, ['id' => (int) $data['id']]);
            return (int) $data['id'];
        }
        if (!empty($data['id'])) {
            $row['id'] = (int) $data['id'];
        }
        $wpdb->insert(ML_DB::t('servers'), $row);
        return (int) $wpdb->insert_id;
    }

    /** HTML danh sách server (radio) theo đúng markup site gốc. */
    public static function render_servers($service_id, $level = 1)
    {
        $vip = self::is_vip($service_id);
        $html = '';
        foreach (self::servers($service_id) as $s) {
            $price = ml_price(self::server_price($s, $level));
            $attrs = is_array($s->attrs) ? $s->attrs : [];
            $data = '';
            if ($vip) {
                $data .= ' data-price="' . esc_attr($price) . '"';
                $data .= ' data-max-post="' . esc_attr($attrs['max-post'] ?? '0') . '"';
                $data .= ' data-min="' . esc_attr($s->min_qty) . '" data-max="' . esc_attr($s->max_qty) . '"';
                $data .= ' data-multi="' . esc_attr($attrs['multi'] ?? '0') . '" data-speed="' . esc_attr($attrs['speed'] ?? '0') . '" data-reactions="' . esc_attr($attrs['reactions'] ?? 'like') . '"';
            } else {
                $data .= ' data-video-by-time="' . esc_attr($attrs['video-by-time'] ?? '0') . '"';
                $data .= ' data-dripFeed="' . esc_attr($attrs['dripfeed'] ?? '0') . '"';
                $data .= ' data-price="' . esc_attr($price) . '"';
                $data .= ' data-min="' . esc_attr($s->min_qty) . '"';
                $data .= ' data-comment="' . esc_attr($attrs['comment'] ?? '0') . '"';
                $data .= ' data-max="' . esc_attr($s->max_qty) . '"';
                $data .= ' data-multi="' . esc_attr($attrs['multi'] ?? '0') . '"';
                $data .= ' data-speed="' . esc_attr($attrs['speed'] ?? '') . '"';
                $data .= ' data-reactions="' . esc_attr($attrs['reactions'] ?? '') . '"';
            }
            $st_text = $s->status_text !== '' ? $s->status_text : ($s->active ? 'Hoạt động' : 'Bảo trì');
            $st_color = (mb_stripos($st_text, 'bảo trì') !== false || mb_stripos($st_text, 'tạm') !== false) ? 'bg-danger' : 'bg-success';
            $status = '<span class="badge ' . $st_color . ' fw-bold text-white">' . esc_html($st_text) . '</span>';
            $html .= '<div class="server-item"><div class="icheck-primary">';
            $html .= '<input type="radio" id="server-' . (int) $s->id . '" name="serverId" value="' . (int) $s->id . '"' . $data . ($s->active ? '' : ' disabled') . '>';
            $html .= '<label class="font-weight-normal" for="server-' . (int) $s->id . '"><span class="text-info font-weight-bold">' . esc_html($s->label) . ($vip ? ' :' : ':') . '</span> '
                . $s->name . ' <span class="badge badge-danger font-weight-normal">' . esc_html($price) . ' đ</span> ' . $status . '</label>';
            $html .= '</div>';
            $html .= '<div class="alert alert-default-info mb-2 server-note" style="display: none;">' . ml_fix_urls($s->note) . '</div>';
            $html .= '</div>';
        }
        return $html;
    }
}
