#!/bin/bash
# Bấm đúp để chạy Hồ sơ Web, trình duyệt tự mở http://localhost:8080. Đóng cửa sổ này để tắt.
APP_DIR="$(cd "$(dirname "$(readlink "$0" || echo "$0")")/.." && pwd)"
cd "$APP_DIR" || exit 1
KEY_FILE="$HOME/.hosoweb/sinbyte_key"
if [ -s "$KEY_FILE" ]; then export SINBYTE_API_KEY="$(cat "$KEY_FILE")"; fi
if lsof -iTCP:8080 -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Ứng dụng đã chạy. Mở trình duyệt…"; open "http://localhost:8080"; exit 0
fi
( for i in $(seq 1 120); do sleep 2; if curl -s -o /dev/null http://localhost:8080/login; then open "http://localhost:8080"; break; fi; done ) &
echo "Đang khởi động Hồ sơ Web… (đóng cửa sổ này hoặc bấm Ctrl+C để tắt)"
exec ./mvnw -q jetty:run
