#!/bin/sh
# Cài MuaLikes WordPress local bằng Docker (macOS / Linux).
# Dùng: ./install.sh           (ảnh trong bài viết lấy từ mualikes.net)
#       ./install.sh --images  (tải toàn bộ ảnh về máy, lâu hơn)
set -e
cd "$(dirname "$0")"
command -v docker >/dev/null 2>&1 || { echo "Chưa cài Docker Desktop: https://www.docker.com/products/docker-desktop/"; exit 1; }
WITH_IMAGES=0; [ "$1" = "--images" ] && WITH_IMAGES=1
docker compose up -d db wordpress
docker compose run --rm -e WITH_IMAGES=$WITH_IMAGES wpcli
