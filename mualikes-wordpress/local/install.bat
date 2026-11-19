@echo off
REM Cai MuaLikes WordPress local bang Docker Desktop (Windows)
REM Dung: install.bat           hoac   install.bat --images
cd /d "%~dp0"
where docker >nul 2>nul || (echo Chua cai Docker Desktop: https://www.docker.com/products/docker-desktop/ & pause & exit /b 1)
set WITH_IMAGES=0
if "%1"=="--images" set WITH_IMAGES=1
docker compose up -d db wordpress || (pause & exit /b 1)
docker compose run --rm -e WITH_IMAGES=%WITH_IMAGES% wpcli
start http://localhost:8080
pause
