#!/bin/sh
# Xoá toàn bộ site local (kể cả database)
cd "$(dirname "$0")" && docker compose down -v
