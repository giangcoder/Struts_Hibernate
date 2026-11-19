<?php
if (!defined('ABSPATH')) {
    exit;
}

class ML_DB
{
    public static function t($name)
    {
        global $wpdb;
        return $wpdb->prefix . 'ml_' . $name;
    }

    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();

        // Server của từng dịch vụ. id = ID server (giữ nguyên ID của site gốc khi import).
        dbDelta("CREATE TABLE " . self::t('servers') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service_id bigint(20) unsigned NOT NULL DEFAULT 0,
            label varchar(100) NOT NULL DEFAULT '',
            name varchar(500) NOT NULL DEFAULT '',
            note longtext NULL,
            price_1 decimal(16,4) NOT NULL DEFAULT 0,
            price_2 decimal(16,4) NOT NULL DEFAULT 0,
            price_3 decimal(16,4) NOT NULL DEFAULT 0,
            min_qty bigint(20) NOT NULL DEFAULT 0,
            max_qty bigint(20) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            status_text varchar(50) NOT NULL DEFAULT '',
            attrs longtext NULL,
            sort int(11) NOT NULL DEFAULT 0,
            provider_id bigint(20) unsigned NOT NULL DEFAULT 0,
            provider_service varchar(100) NOT NULL DEFAULT '',
            refill tinyint(1) NOT NULL DEFAULT 0,
            cancel tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY service_id (service_id)
        ) $c;");

        dbDelta("CREATE TABLE " . self::t('orders') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            service_id bigint(20) unsigned NOT NULL DEFAULT 0,
            server_id bigint(20) unsigned NOT NULL DEFAULT 0,
            parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
            kind varchar(20) NOT NULL DEFAULT 'normal',
            uid varchar(500) NOT NULL DEFAULT '',
            original_link varchar(1000) NOT NULL DEFAULT '',
            content longtext NULL,
            params longtext NULL,
            quantity bigint(20) NOT NULL DEFAULT 0,
            price decimal(16,4) NOT NULL DEFAULT 0,
            amount decimal(16,2) NOT NULL DEFAULT 0,
            start_count bigint(20) NOT NULL DEFAULT 0,
            present bigint(20) NOT NULL DEFAULT 0,
            remains bigint(20) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            provider_id bigint(20) unsigned NOT NULL DEFAULT 0,
            provider_order varchar(100) NOT NULL DEFAULT '',
            note varchar(500) NOT NULL DEFAULT '',
            admin_note varchar(1000) NOT NULL DEFAULT '',
            guarantee_count int(11) NOT NULL DEFAULT 0,
            guarantee_rejected int(11) NOT NULL DEFAULT 0,
            vip_package int(11) NOT NULL DEFAULT 0,
            days int(11) NOT NULL DEFAULT 0,
            time_end datetime NULL,
            run_at datetime NULL,
            source varchar(20) NOT NULL DEFAULT 'web',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY service_id (service_id),
            KEY status (status),
            KEY parent_id (parent_id)
        ) $c;");

        dbDelta("CREATE TABLE " . self::t('transactions') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            type varchar(20) NOT NULL DEFAULT '',
            amount decimal(16,2) NOT NULL DEFAULT 0,
            balance_before decimal(16,2) NOT NULL DEFAULT 0,
            balance_after decimal(16,2) NOT NULL DEFAULT 0,
            order_id bigint(20) unsigned NOT NULL DEFAULT 0,
            note varchar(1000) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY type (type)
        ) $c;");

        // Giao dịch nạp tiền nhận từ ngân hàng / cổng thanh toán (chống cộng trùng bằng gateway+ref).
        dbDelta("CREATE TABLE " . self::t('payments') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            gateway varchar(30) NOT NULL DEFAULT '',
            ref varchar(191) NOT NULL DEFAULT '',
            amount decimal(16,2) NOT NULL DEFAULT 0,
            bonus decimal(16,2) NOT NULL DEFAULT 0,
            content varchar(1000) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            raw longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY gateway_ref (gateway, ref),
            KEY user_id (user_id)
        ) $c;");

        dbDelta("CREATE TABLE " . self::t('providers') . " (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL DEFAULT '',
            api_url varchar(500) NOT NULL DEFAULT '',
            api_key varchar(500) NOT NULL DEFAULT '',
            active tinyint(1) NOT NULL DEFAULT 1,
            balance varchar(50) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $c;");

        update_option('ml_db_version', ML_DB_VERSION);
    }
}
