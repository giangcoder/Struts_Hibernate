#!/bin/bash
# Cài Hồ sơ Web trên macOS (Apple Silicon M1/M2/M3/M4 hoặc Intel).
# Chạy:  curl -fsSL https://raw.githubusercontent.com/giangcoder/Struts_Hibernate/claude/elegant-newton-f3vd74/scripts/install-mac.sh | bash
set -euo pipefail

REPO="giangcoder/Struts_Hibernate"
BRANCH="claude/elegant-newton-f3vd74"
APP_DIR="$HOME/HoSoWeb"
CONF_DIR="$HOME/.hosoweb"

say() { printf '\n\033[1;34m==>\033[0m %s\n' "$1"; }

# 1. Java 17+
java_ok() { /usr/libexec/java_home -v 17+ >/dev/null 2>&1; }
if java_ok; then
  say "Đã có Java: $(/usr/libexec/java_home -v 17+)"
else
  case "$(uname -m)" in arm64) ARCH=aarch64 ;; *) ARCH=x64 ;; esac
  say "Cài Java 21 (Temurin, $ARCH) — máy sẽ hỏi mật khẩu đăng nhập Mac"
  PKG="$(mktemp -d)/temurin21.pkg"
  curl -fL --progress-bar -o "$PKG" "https://api.adoptium.net/v3/installer/latest/21/ga/mac/$ARCH/jdk/hotspot/normal/eclipse"
  sudo installer -pkg "$PKG" -target /
  java_ok || { echo "Cài Java chưa thành công."; exit 1; }
fi

# 2. Mã nguồn
if [ -d "$APP_DIR/.git" ]; then
  say "Cập nhật ứng dụng trong $APP_DIR"
  git -C "$APP_DIR" pull --ff-only
elif [ -f "$APP_DIR/pom.xml" ]; then
  say "Cập nhật ứng dụng trong $APP_DIR (tải lại, giữ dữ liệu)"
  TMP="$(mktemp -d)"
  curl -fL --progress-bar -o "$TMP/app.zip" "https://github.com/$REPO/archive/refs/heads/$BRANCH.zip"
  unzip -q "$TMP/app.zip" -d "$TMP"
  SRC="$(find "$TMP" -mindepth 1 -maxdepth 1 -type d | head -1)"
  rsync -a --exclude data "$SRC/" "$APP_DIR/"
elif git --version >/dev/null 2>&1; then
  say "Tải ứng dụng vào $APP_DIR"
  git clone -b "$BRANCH" "https://github.com/$REPO.git" "$APP_DIR"
else
  say "Tải ứng dụng vào $APP_DIR"
  TMP="$(mktemp -d)"
  curl -fL --progress-bar -o "$TMP/app.zip" "https://github.com/$REPO/archive/refs/heads/$BRANCH.zip"
  unzip -q "$TMP/app.zip" -d "$TMP"
  SRC="$(find "$TMP" -mindepth 1 -maxdepth 1 -type d | head -1)"
  mv "$SRC" "$APP_DIR"
fi
chmod +x "$APP_DIR/mvnw" "$APP_DIR/scripts/"*.command 2>/dev/null || true

# 3. Khóa Sinbyte (lưu riêng trên máy, quyền 600, không nằm trong mã nguồn)
mkdir -p "$CONF_DIR"; chmod 700 "$CONF_DIR"
if [ ! -s "$CONF_DIR/sinbyte_key" ]; then
  say "Nhập khóa API Sinbyte (Enter để bỏ qua, nhập sau trong $CONF_DIR/sinbyte_key):"
  read -r -s KEY < /dev/tty || KEY=""
  echo
  if [ -n "$KEY" ]; then (umask 077; printf '%s' "$KEY" > "$CONF_DIR/sinbyte_key"); fi
fi

# 4. Lối tắt trên Desktop
ln -sf "$APP_DIR/scripts/HoSoWeb.command" "$HOME/Desktop/Hồ sơ Web.command"

# 5. Tải sẵn thư viện (lần đầu mất vài phút)
say "Tải thư viện lần đầu (vài phút)…"
( cd "$APP_DIR" && ./mvnw -q -DskipTests package ) || echo "Chưa tải xong thư viện — sẽ tải tiếp khi mở ứng dụng."

say "Xong! Bấm đúp \"Hồ sơ Web\" trên Desktop để mở ứng dụng."
