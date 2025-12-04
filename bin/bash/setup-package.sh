#!/bin/bash

# Social Sync Package Setup Script
# This script creates the complete directory structure for the package

echo "🚀 Social Sync - Package Setup Script"
echo "======================================"
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from a Laravel project root directory"
    echo "   Make sure you're in the directory containing 'artisan' file"
    exit 1
fi

echo "✓ Laravel project detected"
echo ""

# Define base paths
PACKAGE_PATH="packages/socialsync/laravel-social-sync"
SRC_PATH="$PACKAGE_PATH/src"

echo "📁 Creating package directory structure..."
echo ""

# Create main directories
mkdir -p "$PACKAGE_PATH"
mkdir -p "$SRC_PATH"
mkdir -p "$SRC_PATH/Facades"
mkdir -p "$SRC_PATH/Contracts"
mkdir -p "$SRC_PATH/Drivers"
mkdir -p "$SRC_PATH/Models"
mkdir -p "$SRC_PATH/Jobs"
mkdir -p "$SRC_PATH/Events"
mkdir -p "$SRC_PATH/Console/Commands"
mkdir -p "$SRC_PATH/Http/Controllers"
mkdir -p "$SRC_PATH/Http/Middleware"
mkdir -p "$PACKAGE_PATH/database/migrations"
mkdir -p "$PACKAGE_PATH/config"
mkdir -p "$PACKAGE_PATH/resources/views"
mkdir -p "$PACKAGE_PATH/routes"
mkdir -p "$PACKAGE_PATH/tests/Unit"
mkdir -p "$PACKAGE_PATH/tests/Feature"

echo "✓ Package directories created"
echo ""

# Create empty placeholder files
echo "📝 Creating placeholder files..."
echo ""

# Root files
touch "$PACKAGE_PATH/README.md"
touch "$PACKAGE_PATH/LICENSE"
touch "$PACKAGE_PATH/CHANGELOG.md"

# Source files
touch "$SRC_PATH/SocialSyncServiceProvider.php"
touch "$SRC_PATH/SocialMediaManager.php"
touch "$SRC_PATH/PostBuilder.php"
touch "$SRC_PATH/helpers.php"

# Facades
touch "$SRC_PATH/Facades/SocialMedia.php"

# Contracts
touch "$SRC_PATH/Contracts/SocialDriverInterface.php"

# Drivers
touch "$SRC_PATH/Drivers/FacebookDriver.php"
touch "$SRC_PATH/Drivers/InstagramDriver.php"
touch "$SRC_PATH/Drivers/TwitterDriver.php"
touch "$SRC_PATH/Drivers/LinkedInDriver.php"

# Models
touch "$SRC_PATH/Models/SocialAccount.php"
touch "$SRC_PATH/Models/ScheduledPost.php"

# Jobs
touch "$SRC_PATH/Jobs/PublishPostJob.php"

# Events
touch "$SRC_PATH/Events/PostPublished.php"
touch "$SRC_PATH/Events/PostFailed.php"

# Commands
touch "$SRC_PATH/Console/Commands/InstallCommand.php"
touch "$SRC_PATH/Console/Commands/AddAccountCommand.php"
touch "$SRC_PATH/Console/Commands/ListAccountsCommand.php"
touch "$SRC_PATH/Console/Commands/TestPostCommand.php"

# Controllers
touch "$SRC_PATH/Http/Controllers/OAuthController.php"

# Middleware
touch "$SRC_PATH/Http/Middleware/RateLimitPosts.php"

# Config
touch "$PACKAGE_PATH/config/social-sync.php"

# Routes
touch "$PACKAGE_PATH/routes/web.php"

# Views
touch "$PACKAGE_PATH/resources/views/oauth-success.blade.php"
touch "$PACKAGE_PATH/resources/views/oauth-error.blade.php"

# Migrations
touch "$PACKAGE_PATH/database/migrations/2024_01_01_000000_create_social_sync_tables.php"

# Tests
touch "$PACKAGE_PATH/tests/TestCase.php"

echo "✓ Placeholder files created"
echo ""

# Create composer.json
echo "📦 Creating composer.json..."
cat > "$PACKAGE_PATH/composer.json" << 'EOF'
{
    "name": "socialsync/laravel-social-sync",
    "description": "Unified social media posting SDK for Laravel",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.1",
        "illuminate/support": "^10.0|^11.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "autoload": {
        "psr-4": {
            "SocialSync\\": "src/"
        },
        "files": [
            "src/helpers.php"
        ]
    },
    "extra": {
        "laravel": {
            "providers": [
                "SocialSync\\SocialSyncServiceProvider"
            ],
            "aliases": {
                "SocialMedia": "SocialSync\\Facades\\SocialMedia"
            }
        }
    }
}
EOF

echo "✓ composer.json created"
echo ""

# Create LICENSE file
echo "📄 Creating MIT LICENSE..."
YEAR=$(date +%Y)
cat > "$PACKAGE_PATH/LICENSE" << EOF
MIT License

Copyright (c) $YEAR Social Sync

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF

echo "✓ LICENSE created"
echo ""

# Update main composer.json
echo "🔧 Updating main composer.json..."

# Check if repositories section exists
if ! grep -q '"repositories"' composer.json; then
    # Add repositories section before require
    sed -i.bak '/"require"/i\
    "repositories": [\
        {\
            "type": "path",\
            "url": "./packages/socialsync/laravel-social-sync"\
        }\
    ],' composer.json
    echo "✓ Added repositories section to composer.json"
else
    echo "⚠ Repositories section already exists in composer.json"
    echo "  Please manually add the package path if needed"
fi

echo ""

# Create app directories for test UI
echo "🎨 Creating application directories for test UI..."
mkdir -p "app/Http/Controllers"
mkdir -p "resources/views"

touch "app/Http/Controllers/SocialSyncTestController.php"
touch "resources/views/social-sync-test.blade.php"

echo "✓ Application directories created"
echo ""

# Create documentation directory
echo "📚 Creating documentation directory..."
mkdir -p "docs"

echo "✓ Documentation directory created"
echo ""

# Summary
echo ""
echo "=================================="
echo "✅ Package structure created successfully!"
echo "=================================="
echo ""
echo "📂 Package location: $PACKAGE_PATH"
echo ""
echo "Next steps:"
echo ""
echo "1. Copy the content of each artifact to its corresponding file"
echo "   (Refer to COMPLETE_FILE_LIST.md for artifact-to-file mapping)"
echo ""
echo "2. Update main composer.json require section:"
echo "   \"require\": {"
echo "       \"socialsync/laravel-social-sync\": \"@dev\""
echo "   }"
echo ""
echo "3. Install the package:"
echo "   composer update socialsync/laravel-social-sync"
echo ""
echo "4. Run installation:"
echo "   php artisan social-sync:install"
echo ""
echo "5. Follow the QUICK_START_GUIDE.md to complete setup"
echo ""
echo "📁 Directory tree created:"
echo ""

# Show tree if available
if command -v tree &> /dev/null; then
    tree -L 3 -I 'vendor|node_modules' "$PACKAGE_PATH"
else
    echo "   Install 'tree' command to view directory structure"
    echo "   Or run: find $PACKAGE_PATH -type d | sort"
fi

echo ""
echo "Happy coding! 🚀"
echo ""
