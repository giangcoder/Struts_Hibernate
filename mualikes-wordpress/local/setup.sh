#!/bin/sh
# Chạy bên trong container wp-cli: cài WordPress, bật theme/plugin, nhập dữ liệu MuaLikes.
set -e
cd /var/www/html
SITE_URL="${SITE_URL:-http://localhost:8080}"

echo ">> Chờ WordPress sẵn sàng..."
i=0
until [ -f wp-config.php ] && wp db check >/dev/null 2>&1; do
  i=$((i+1)); [ $i -gt 60 ] && { echo "Hết thời gian chờ WordPress/DB"; exit 1; }
  sleep 3
done

if ! wp core is-installed >/dev/null 2>&1; then
  echo ">> Cài WordPress..."
  wp core install --url="$SITE_URL" --title="MuaLikes.Net" \
    --admin_user=admin --admin_password=admin123 --admin_email=admin@example.com --skip-email
  wp language core install vi --activate || true
fi

wp theme activate mualikes
wp plugin activate mualikes-core

echo ">> Nhập dữ liệu (dịch vụ, server, VIP, blog...)"
if [ "${WITH_IMAGES:-0}" = "1" ]; then
  wp mualikes import --images
else
  wp mualikes import
fi

wp rewrite structure '/blog/%postname%/'
wp rewrite flush
if ! grep -q "BEGIN WordPress" .htaccess 2>/dev/null; then
  cat >> .htaccess <<'HT'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HT
fi
wp plugin delete hello akismet >/dev/null 2>&1 || true

# Tài khoản khách mẫu có sẵn 1.000.000đ để thử đặt đơn
if ! wp user get khachtest >/dev/null 2>&1; then
  wp user create khachtest khachtest@example.com --role=subscriber --user_pass=khach123
  wp mualikes credit khachtest 1000000 --deposit --note="Tiền thử nghiệm"
fi

echo ""
echo "=============================================="
echo " XONG! Mở: $SITE_URL"
echo " Quản trị: $SITE_URL/wp-admin  (admin / admin123)"
echo " Khách thử: khachtest / khach123 (số dư 1.000.000đ)"
echo "=============================================="
