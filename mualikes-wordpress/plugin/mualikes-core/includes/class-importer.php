<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nhập dữ liệu mẫu đã trích xuất từ site gốc (thư mục data/): nhóm dịch vụ, dịch vụ + server + giá 3 cấp bậc,
 * dịch vụ VIP, thông báo, trang tĩnh, bài viết blog (chuyên mục, thẻ, ảnh đại diện).
 * Chạy: WP-CLI `wp mualikes import [--images]` hoặc Công cụ > MuaLikes Import trong admin.
 */
class ML_Importer
{
    private static $log = [];
    public static $mirror_images = false;

    public static function data($name)
    {
        $f = ML_DIR . 'data/' . $name . '.json';
        return file_exists($f) ? json_decode(file_get_contents($f), true) : [];
    }

    private static function log($m)
    {
        self::$log[] = $m;
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($m);
        }
    }

    public static function get_log()
    {
        return self::$log;
    }

    /** Chuẩn hoá nội dung: ảnh /upload, /images về đường dẫn tương đối; link site gốc về site mới. */
    public static function normalize($html)
    {
        $html = (string) $html;
        $html = preg_replace('#(src|href)=(["\'])https?://(?:www\.)?mualikes\.net(/(?:upload|images)/)#i', '$1=$2$3', $html);
        $html = preg_replace('#(src|href)=(["\'])(?:\.\./)+(upload|images)/#i', '$1=$2/$3/', $html);
        $html = preg_replace('#https?://(?:www\.)?mualikes\.net/#i', trailingslashit(home_url()), $html);
        $html = preg_replace('#href=(["\'])(?:\.\./)+#', 'href=$1' . trailingslashit(home_url()), $html);
        if (self::$mirror_images) {
            preg_match_all('#(?:src|href)=["\'](/upload/[^"\']+\.(?:png|jpe?g|gif|webp|svg))["\']#i', $html, $m);
            foreach (array_unique($m[1]) as $p) {
                self::mirror($p);
            }
        }
        return $html;
    }

    /** Tải ảnh từ site gốc về uploads/mualikes/upload/... (giữ nguyên cấu trúc thư mục). */
    public static function mirror($path)
    {
        $up = wp_get_upload_dir();
        $dest = $up['basedir'] . '/mualikes' . $path;
        if (file_exists($dest) || file_exists(get_template_directory() . '/assets/img' . $path)) {
            return true;
        }
        wp_mkdir_p(dirname($dest));
        $res = wp_remote_get(ML_Settings::get('remote_asset_base', 'https://mualikes.net') . str_replace(' ', '%20', $path), ['timeout' => 30, 'stream' => true, 'filename' => $dest]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            @unlink($dest);
            return false;
        }
        return true;
    }

    public static function run($steps = ['groups', 'services', 'vip', 'notices', 'pages', 'blog', 'settings'])
    {
        @set_time_limit(0);
        wp_cache_delete('ml_menu', 'mualikes');
        kses_remove_filters();
        ML_Services::$trusted = true;
        foreach ($steps as $s) {
            $m = 'import_' . $s;
            if (method_exists(__CLASS__, $m)) {
                self::$m();
            }
        }
        kses_init_filters();
        ML_Services::rebuild_paths();
        update_option('ml_flush_rules', 1);
        wp_cache_delete('ml_menu', 'mualikes');
        return self::$log;
    }

    private static function term($name, $meta)
    {
        $t = term_exists($name, ML_Services::TAX);
        $id = $t ? (int) $t['term_id'] : (int) wp_insert_term($name, ML_Services::TAX)['term_id'];
        foreach ($meta as $k => $v) {
            update_term_meta($id, $k, $v);
        }
        return $id;
    }

    public static function import_groups()
    {
        $menu = self::data('menu');
        foreach ($menu['groups'] as $i => $g) {
            self::term($g['name'], ['icon' => $g['icon'], 'anchor' => $g['anchor'], 'order' => $i + 1, 'vip' => 0]);
        }
        self::term($menu['vip_group']['name'], ['icon' => $menu['vip_group']['icon'], 'anchor' => 'vip', 'order' => 99, 'vip' => 1]);
        self::log('Đã tạo ' . (count($menu['groups']) + 1) . ' nhóm dịch vụ');
    }

    private static function find_service($path)
    {
        $q = get_posts(['post_type' => ML_Services::CPT, 'meta_key' => '_ml_path', 'meta_value' => $path, 'post_status' => 'any', 'numberposts' => 1]);
        return $q ? $q[0]->ID : 0;
    }

    private static function upsert_service($rec, $group_name, $menu_item, $order, $kind)
    {
        $path = trim($rec['slug'], '/');
        $id = self::find_service($path);
        $post = [
            'post_type' => ML_Services::CPT,
            'post_title' => $rec['title'],
            'post_name' => sanitize_title(basename($path)) . ($kind === 'vip' ? '-' . sanitize_title(explode('/', $path)[1] ?? '') : ''),
            'post_content' => self::normalize($rec['article_html'] ?? ''),
            'post_status' => 'publish',
            'menu_order' => $order,
        ];
        if ($id) {
            $post['ID'] = $id;
            wp_update_post(wp_slash($post));
        } else {
            $id = wp_insert_post(wp_slash($post));
        }
        $js = $rec['inline_js'] ?? '';
        $meta = [
            'path' => $path,
            'kind' => $kind,
            'menu_title' => $menu_item['title'] ?? $rec['title'],
            'icon' => $menu_item['icon'] ?? '',
            'badge' => $menu_item['badge'] ?? '',
            'badge_class' => $menu_item['badge_class'] ?? '',
            'orig_service_id' => (int) ($rec['service_id'] ?? 0),
            'form_html' => self::normalize($rec['form_html']),
            'form_attrs' => wp_json_encode($rec['form_attrs']),
            'inline_js' => $js,
            'guide_video' => $rec['guide_video'] ?? '',
            'notes_html' => self::normalize($rec['notes_html'] ?? ''),
            'meta_title' => $rec['meta']['title'] ?? '',
            'meta_desc' => $rec['meta']['description'] ?? '',
            'vip_type' => preg_match("/vip_type = '([A-Z_]+)'/", $js, $m) ? $m[1] : '',
            'service_type' => preg_match("/serviceType = '([A-Z_]+)'/", $js, $m2) ? $m2[1] : '',
        ];
        foreach ($meta as $k => $v) {
            update_post_meta($id, '_ml_' . $k, wp_slash($v));
        }
        $t = term_exists($group_name, ML_Services::TAX);
        if ($t) {
            wp_set_object_terms($id, (int) $t['term_id'], ML_Services::TAX);
        }
        // Server
        global $wpdb;
        $keep = [];
        foreach ($rec['servers'] as $i => $s) {
            $prices = $s['prices'] ?? [$s['price'], round($s['price'] * 0.95, 1), round($s['price'] * 0.9, 1)];
            $data = [
                'service_id' => $id, 'label' => $s['label'], 'name' => $s['name'], 'note' => self::normalize($s['note']),
                'price_1' => $prices[0], 'price_2' => $prices[1], 'price_3' => $prices[2],
                'min_qty' => $s['min'], 'max_qty' => $s['max'], 'active' => $s['active'] ? 1 : 0,
                'status_text' => $s['status_text'], 'attrs' => $s['data'], 'sort' => $i,
            ];
            if ($kind === 'normal') {
                $data['id'] = $s['id'];
            } else {
                $exist = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . ML_DB::t('servers') . " WHERE service_id = %d AND label = %s", $id, $s['label']));
                if ($exist) {
                    $data['id'] = (int) $exist;
                }
            }
            $keep[] = ML_Services::save_server($data);
        }
        if ($keep) {
            $wpdb->query($wpdb->prepare("DELETE FROM " . ML_DB::t('servers') . " WHERE service_id = %d AND id NOT IN (" . implode(',', array_map('intval', $keep)) . ")", $id));
        }
        return $id;
    }

    public static function import_services()
    {
        $menu = self::data('menu');
        $by = [];
        foreach (self::data('services') as $s) {
            $by['/' . $s['slug'] . '/'] = $s;
        }
        $n = 0;
        foreach ($menu['groups'] as $g) {
            foreach ($g['items'] as $i => $it) {
                if (!isset($by[$it['url']])) {
                    continue;
                }
                self::upsert_service($by[$it['url']], $g['name'], $it, $i, 'normal');
                $n++;
            }
        }
        self::log("Đã nhập $n dịch vụ");
    }

    public static function import_vip()
    {
        $menu = self::data('menu');
        $by = [];
        foreach (self::data('vip') as $s) {
            $by['/' . $s['slug'] . '/'] = $s;
        }
        $n = 0;
        foreach ($menu['vip_items'] as $i => $it) {
            if (isset($by[$it['url']])) {
                self::upsert_service($by[$it['url']], $menu['vip_group']['name'], $it, $i, 'vip');
                $n++;
            }
        }
        self::log("Đã nhập $n dịch vụ VIP");
    }

    private static function parse_date($s, $with_time = true)
    {
        $s = trim($s);
        if ($with_time && preg_match('#(\d{1,2}):(\d{2})\s+(\d{1,2})/(\d{1,2})/(\d{4})#', $s, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[5], $m[4], $m[3], $m[1], $m[2]);
        }
        if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', $s, $m)) {
            return sprintf('%04d-%02d-%02d 08:00:00', $m[3], $m[2], $m[1]);
        }
        return current_time('mysql');
    }

    public static function import_notices()
    {
        $n = 0;
        foreach (array_reverse(self::data('notifications')) as $no) {
            $date = self::parse_date($no['time']);
            $title = wp_trim_words(wp_strip_all_tags($no['html']), 12, '...');
            $exists = get_posts(['post_type' => ML_Services::NOTICE, 'title' => $title, 'post_status' => 'any', 'numberposts' => 1, 'date_query' => [['after' => $date, 'before' => $date, 'inclusive' => true]]]);
            if ($exists) {
                continue;
            }
            wp_insert_post(wp_slash(['post_type' => ML_Services::NOTICE, 'post_title' => $title, 'post_content' => self::normalize($no['html']), 'post_status' => 'publish', 'post_date' => $date]));
            $n++;
        }
        self::log("Đã nhập $n thông báo");
    }

    public static function import_pages()
    {
        foreach (self::data('pages') as $p) {
            $ex = get_page_by_path($p['slug']);
            $post = ['post_type' => 'page', 'post_title' => $p['title'], 'post_name' => $p['slug'], 'post_content' => self::normalize($p['html']), 'post_status' => 'publish'];
            if ($ex) {
                $post['ID'] = $ex->ID;
                wp_update_post(wp_slash($post));
                $id = $ex->ID;
            } else {
                $id = wp_insert_post(wp_slash($post));
            }
            update_post_meta($id, '_ml_meta_title', $p['meta']['title']);
            update_post_meta($id, '_ml_meta_desc', $p['meta']['description']);
        }
        // Trang chủ + Blog
        $home = get_page_by_path('trang-chu');
        $home_id = $home ? $home->ID : wp_insert_post(['post_type' => 'page', 'post_title' => 'Trang chủ', 'post_name' => 'trang-chu', 'post_status' => 'publish']);
        $blog = get_page_by_path('blog');
        $blog_id = $blog ? $blog->ID : wp_insert_post(['post_type' => 'page', 'post_title' => 'Blog MuaLikes.Net', 'post_name' => 'blog', 'post_status' => 'publish']);
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
        update_option('page_for_posts', $blog_id);
        update_option('posts_per_page', 24);
        update_option('permalink_structure', '/blog/%postname%/');
        update_option('users_can_register', 1);
        update_option('default_role', 'subscriber');
        update_option('timezone_string', 'Asia/Ho_Chi_Minh');
        update_option('date_format', 'd/m/Y');
        update_option('time_format', 'H:i');
        update_option('blogname', 'MuaLikes.Net - Hệ Thống Tăng Like, Follow, Vip Like Uy Tín #1 Việt Nam');
        update_option('blogdescription', 'MuaLikes.Net - Cung cấp dịch vụ App tăng Like, Follow/Sub, tăng View, Cmt Facebook, Tiktok, Youtube, Telegram, Instagram,...Nhanh - Uy Tín - Giá Rẻ.');
        self::log('Đã nhập trang tĩnh, đặt Trang chủ/Blog và cấu trúc đường dẫn /blog/%postname%/');
    }

    public static function import_blog($offset = 0, $limit = 0)
    {
        kses_remove_filters();
        $cats = self::data('categories');
        foreach ($cats as $c) {
            if (!term_exists($c['slug'], 'category')) {
                wp_insert_term($c['name'], 'category', ['slug' => $c['slug']]);
            }
        }
        $posts = self::data('blog');
        if ($limit) {
            $posts = array_slice($posts, $offset, $limit);
        }
        $admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
        $n = 0;
        foreach ($posts as $p) {
            $ex = get_page_by_path($p['slug'], OBJECT, 'post');
            $cat = !empty($p['category_slug']) ? get_category_by_slug($p['category_slug']) : null;
            $post = [
                'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $p['title'], 'post_name' => $p['slug'],
                'post_content' => self::normalize($p['content']), 'post_date' => self::parse_date($p['date'], false),
                'post_excerpt' => wp_strip_all_tags(html_entity_decode($p['excerpt'] ?? '')),
                'post_author' => $admin ? (int) $admin[0] : 1,
                'post_category' => $cat ? [$cat->term_id] : [],
                'tags_input' => array_map(function ($t) {
                    return $t[0];
                }, $p['tags'] ?? []),
            ];
            if ($ex) {
                $post['ID'] = $ex->ID;
            }
            $id = $ex ? wp_update_post(wp_slash($post)) : wp_insert_post(wp_slash($post));
            if (is_wp_error($id) || !$id) {
                continue;
            }
            update_post_meta($id, '_ml_meta_title', $p['meta']['title'] ?? '');
            update_post_meta($id, '_ml_meta_desc', $p['meta']['description'] ?? '');
            if (!empty($p['thumbnail'])) {
                update_post_meta($id, '_ml_thumb', $p['thumbnail']);
                if (self::$mirror_images) {
                    self::mirror($p['thumbnail']);
                }
            }
            $n++;
        }
        self::log("Đã nhập $n bài viết blog");
        return $n;
    }

    public static function import_settings()
    {
        $faq = self::data('faq');
        $supp = self::data('support');
        $levels = self::data('levels');
        $vals = [];
        if ($faq) {
            foreach ($faq as &$f) {
                $f['a'] = self::normalize($f['a']);
            }
            $vals['faq'] = $faq;
        }
        if ($supp) {
            $vals['support_links'] = $supp;
        }
        if ($levels) {
            $vals['levels'] = $levels;
        }
        ML_Settings::update($vals);
        self::log('Đã cập nhật cài đặt (FAQ, liên hệ hỗ trợ, cấp bậc)');
    }
}
