#!/bin/bash
# e-CO Web - Quick Setup Script
# Run this after cloning the repository

echo "🚀 Starting e-CO Web setup..."
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

echo "✅ Docker is running"
echo ""

# Step 1: Install npm dependencies
echo "📦 Step 1/7: Installing JavaScript dependencies..."
if npm install; then
    echo "✅ npm install completed"
else
    echo "❌ npm install failed"
    exit 1
fi
echo ""

# Step 2: Build assets
echo "🔨 Step 2/7: Building JavaScript assets..."
if npm run build; then
    echo "✅ Assets built successfully"
else
    echo "❌ Asset build failed"
    exit 1
fi
echo ""

# Step 3: Start Docker containers
echo "🐳 Step 3/7: Starting Docker containers..."
if docker compose up -d --wait; then
    echo "✅ Docker containers started"
else
    echo "❌ Docker containers failed to start"
    exit 1
fi
echo ""

# Step 4: Install PHP dependencies
echo "📦 Step 4/7: Installing PHP dependencies..."
if docker compose exec php composer install; then
    echo "✅ Composer install completed"
else
    echo "❌ Composer install failed"
    exit 1
fi
echo ""

# Step 5: Generate JWT keys
echo "🔐 Step 5/7: Generating JWT authentication keys..."
if docker compose exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists; then
    echo "✅ JWT keys generated"
else
    echo "❌ JWT key generation failed"
    exit 1
fi
echo ""

# Step 6: Run database migrations
echo "🗃️  Step 6/7: Running database migrations..."
if docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction; then
    echo "✅ Database migrations completed"
else
    echo "❌ Database migrations failed"
    exit 1
fi
echo ""

# Step 7: Clear cache
echo "🧹 Step 7/7: Clearing Symfony cache..."
if docker compose exec php php bin/console cache:clear; then
    echo "✅ Cache cleared"
else
    echo "❌ Cache clear failed"
    exit 1
fi
echo ""

echo "🎉 Setup completed successfully!"
echo ""
echo "📝 Next steps:"
echo "  1. Open http://localhost/ in your browser"
echo "  2. Login with: test@test.com / password"
echo "  3. Check http://localhost:8080 for database (Adminer)"
echo "  4. Check http://localhost:8025 for emails (Mailpit)"
echo ""
echo "💡 To stop the project: docker compose down"
echo "💡 To restart: docker compose up -d"
echo ""
