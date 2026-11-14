#!/bin/bash
# Bấm đúp để chạy Hồ sơ Web, trình duyệt tự mở http://localhost:8080. Đóng cửa sổ này để tắt.
APP_DIR="$(cd "$(dirname "$(readlink "$0" || echo "$0")")/.." && pwd)"
cd "$APP_DIR" || exit 1
KEY_FILE="$HOME/.hosoweb/sinbyte_key"
if [ -s "$KEY_FILE" ]; then export SINBYTE_API_KEY="$(cat "$KEY_FILE")"; fi
if lsof -iTCP:8080 -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Ứng dụng đã chạy. Mở trình duyệt…"; open "http://localhost:8080"; exit 0
fi
# Sao lưu dữ liệu mỗi lần mở (giữ 10 bản gần nhất) — data/backup/
if [ -f data/hosoweb.mv.db ]; then
  mkdir -p data/backup
  cp data/hosoweb.mv.db "data/backup/hosoweb-$(date +%Y%m%d-%H%M%S).mv.db"
  ls -1t data/backup/hosoweb-*.mv.db 2>/dev/null | tail -n +11 | while read -r f; do rm -f "$f"; done
fi
( for i in $(seq 1 450); do sleep 2; if curl -s -o /dev/null http://localhost:8080/login; then open "http://localhost:8080"; break; fi; done ) &
echo "Đang khởi động Hồ sơ Web… (đóng cửa sổ này hoặc bấm Ctrl+C để tắt)"
exec ./mvnw -q jetty:run
