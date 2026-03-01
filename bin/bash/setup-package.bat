@echo off
REM Social Sync Package Setup Script for Windows
REM This script creates the complete directory structure for the package

echo.
echo ========================================
echo  Social Sync - Package Setup Script
echo ========================================
echo.

REM Check if we're in a Laravel project
if not exist "artisan" (
    echo ERROR: This script must be run from a Laravel project root directory
    echo        Make sure you're in the directory containing 'artisan' file
    pause
    exit /b 1
)

echo [OK] Laravel project detected
echo.

REM Define base paths
set PACKAGE_PATH=packages\prateekbhujel\laravel-social-sync
set SRC_PATH=%PACKAGE_PATH%\src

echo Creating package directory structure...
echo.

REM Create main directories
mkdir "%PACKAGE_PATH%" 2>nul
mkdir "%SRC_PATH%" 2>nul
mkdir "%SRC_PATH%\Facades" 2>nul
mkdir "%SRC_PATH%\Contracts" 2>nul
mkdir "%SRC_PATH%\Drivers" 2>nul
mkdir "%SRC_PATH%\Models" 2>nul
mkdir "%SRC_PATH%\Jobs" 2>nul
mkdir "%SRC_PATH%\Events" 2>nul
mkdir "%SRC_PATH%\Console\Commands" 2>nul
mkdir "%SRC_PATH%\Http\Controllers" 2>nul
mkdir "%SRC_PATH%\Http\Middleware" 2>nul
mkdir "%PACKAGE_PATH%\database\migrations" 2>nul
mkdir "%PACKAGE_PATH%\config" 2>nul
mkdir "%PACKAGE_PATH%\resources\views" 2>nul
mkdir "%PACKAGE_PATH%\routes" 2>nul
mkdir "%PACKAGE_PATH%\tests\Unit" 2>nul
mkdir "%PACKAGE_PATH%\tests\Feature" 2>nul

echo [OK] Package directories created
echo.

echo Creating placeholder files...
echo.

REM Root files
type nul > "%PACKAGE_PATH%\README.md"
type nul > "%PACKAGE_PATH%\LICENSE"
type nul > "%PACKAGE_PATH%\CHANGELOG.md"

REM Source files
type nul > "%SRC_PATH%\SocialSyncServiceProvider.php"
type nul > "%SRC_PATH%\SocialMediaManager.php"
type nul > "%SRC_PATH%\PostBuilder.php"
type nul > "%SRC_PATH%\helpers.php"

REM Facades
type nul > "%SRC_PATH%\Facades\SocialMedia.php"

REM Contracts
type nul > "%SRC_PATH%\Contracts\SocialDriverInterface.php"

REM Drivers
type nul > "%SRC_PATH%\Drivers\FacebookDriver.php"
type nul > "%SRC_PATH%\Drivers\InstagramDriver.php"
type nul > "%SRC_PATH%\Drivers\TwitterDriver.php"
type nul > "%SRC_PATH%\Drivers\LinkedInDriver.php"

REM Models
type nul > "%SRC_PATH%\Models\SocialAccount.php"
type nul > "%SRC_PATH%\Models\ScheduledPost.php"

REM Jobs
type nul > "%SRC_PATH%\Jobs\PublishPostJob.php"

REM Events
type nul > "%SRC_PATH%\Events\PostPublished.php"
type nul > "%SRC_PATH%\Events\PostFailed.php"

REM Commands
type nul > "%SRC_PATH%\Console\Commands\InstallCommand.php"
type nul > "%SRC_PATH%\Console\Commands\AddAccountCommand.php"
type nul > "%SRC_PATH%\Console\Commands\ListAccountsCommand.php"
type nul > "%SRC_PATH%\Console\Commands\TestPostCommand.php"

REM Controllers
type nul > "%SRC_PATH%\Http\Controllers\OAuthController.php"

REM Middleware
type nul > "%SRC_PATH%\Http\Middleware\RateLimitPosts.php"

REM Config
type nul > "%PACKAGE_PATH%\config\social-sync.php"

REM Routes
type nul > "%PACKAGE_PATH%\routes\web.php"

REM Views
type nul > "%PACKAGE_PATH%\resources\views\oauth-success.blade.php"
type nul > "%PACKAGE_PATH%\resources\views\oauth-error.blade.php"

REM Migrations
type nul > "%PACKAGE_PATH%\database\migrations\2024_01_01_000000_create_social_sync_tables.php"

REM Tests
type nul > "%PACKAGE_PATH%\tests\TestCase.php"

echo [OK] Placeholder files created
echo.

REM Create composer.json
echo Creating composer.json...
(
echo {
echo     "name": "prateekbhujel/laravel-social-sync",
echo     "description": "Unified social media posting SDK for Laravel",
echo     "type": "library",
echo     "license": "MIT",
echo     "require": {
echo         "php": "^^8.1",
echo         "illuminate/support": "^^10.0^|^^11.0",
echo         "guzzlehttp/guzzle": "^^7.0"
echo     },
echo     "autoload": {
echo         "psr-4": {
echo             "SocialSync\\": "src/"
echo         },
echo         "files": [
echo             "src/helpers.php"
echo         ]
echo     },
echo     "extra": {
echo         "laravel": {
echo             "providers": [
echo                 "SocialSync\\SocialSyncServiceProvider"
echo             ],
echo             "aliases": {
echo                 "SocialMedia": "SocialSync\\Facades\\SocialMedia"
echo             }
echo         }
echo     }
echo }
) > "%PACKAGE_PATH%\composer.json"

echo [OK] composer.json created
echo.

REM Create LICENSE file
echo Creating MIT LICENSE...
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set YEAR=%datetime:~0,4%

(
echo MIT License
echo.
echo Copyright ^(c^) %YEAR% Social Sync
echo.
echo Permission is hereby granted, free of charge, to any person obtaining a copy
echo of this software and associated documentation files ^(the "Software"^), to deal
echo in the Software without restriction, including without limitation the rights
echo to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
echo copies of the Software, and to permit persons to whom the Software is
echo furnished to do so, subject to the following conditions:
echo.
echo The above copyright notice and this permission notice shall be included in all
echo copies or substantial portions of the Software.
echo.
echo THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
echo IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
echo FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
echo AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
echo LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
echo OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
echo SOFTWARE.
) > "%PACKAGE_PATH%\LICENSE"

echo [OK] LICENSE created
echo.

REM Create app directories for test UI
echo Creating application directories for test UI...
mkdir "app\Http\Controllers" 2>nul
mkdir "resources\views" 2>nul

type nul > "app\Http\Controllers\SocialSyncTestController.php"
type nul > "resources\views\social-sync-test.blade.php"

echo [OK] Application directories created
echo.

REM Create documentation directory
echo Creating documentation directory...
mkdir "docs" 2>nul

echo [OK] Documentation directory created
echo.

REM Summary
echo.
echo ==================================
echo  Package structure created!
echo ==================================
echo.
echo Package location: %PACKAGE_PATH%
echo.
echo Next steps:
echo.
echo 1. Copy the content of each artifact to its corresponding file
echo    ^(Refer to COMPLETE_FILE_LIST.md for artifact-to-file mapping^)
echo.
echo 2. Update main composer.json require section:
echo    "require": {
echo        "prateekbhujel/laravel-social-sync": "@dev"
echo    }
echo.
echo 3. Add repository path in composer.json:
echo    "repositories": [
echo        {
echo            "type": "path",
echo            "url": "./packages/prateekbhujel/laravel-social-sync"
echo        }
echo    ]
echo.
echo 4. Install the package:
echo    composer update prateekbhujel/laravel-social-sync
echo.
echo 5. Run installation:
echo    php artisan social-sync:install
echo.
echo 6. Follow the QUICK_START_GUIDE.md to complete setup
echo.
echo Directory structure:
dir /s /b "%PACKAGE_PATH%" | findstr /v "\.git node_modules vendor"
echo.
echo Happy coding!
echo.
pause
