@echo off
cd /d "%~dp0"

echo.
echo ========================================
echo FormForge - Setup and Start
echo ========================================
echo.

REM Check and install dependencies
if not exist vendor (
    echo [1/6] Installing Composer dependencies...
    call C:\php82\composer.bat install --no-interaction
)

if not exist node_modules (
    echo [2/6] Installing NPM dependencies...
    call npm install
)

REM Check and setup .env
if not exist .env (
    echo [3/6] Setting up .env file...
    copy .env.example .env
    php artisan key:generate
) else (
    echo [3/6] .env file already exists
)

REM Check and setup database
if not exist database\database.sqlite (
    echo [4/6] Running migrations...
    php artisan migrate
    echo [5/6] Seeding demo data...
    php artisan db:seed
)

echo [6/6] Starting Laravel server...
echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Application URL: http://localhost:8000
echo.
echo Login Credentials:
echo    Email: demo@example.com
echo    Password: password
echo.
echo To stop: Press Ctrl+C
echo.

REM Start Laravel server
php artisan serve
