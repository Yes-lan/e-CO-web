# e-CO Web - Quick Setup Script (PowerShell)
# Run this after cloning the repository

Write-Host "🚀 Starting e-CO Web setup..." -ForegroundColor Cyan
Write-Host ""

# Check if Docker is running
try {
    docker info | Out-Null
    Write-Host "✅ Docker is running" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 1: Install npm dependencies
Write-Host "📦 Step 1/7: Installing JavaScript dependencies..." -ForegroundColor Yellow
if (npm install) {
    Write-Host "✅ npm install completed" -ForegroundColor Green
} else {
    Write-Host "❌ npm install failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 2: Build assets
Write-Host "🔨 Step 2/7: Building JavaScript assets..." -ForegroundColor Yellow
if (npm run build) {
    Write-Host "✅ Assets built successfully" -ForegroundColor Green
} else {
    Write-Host "❌ Asset build failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 3: Start Docker containers
Write-Host "🐳 Step 3/7: Starting Docker containers..." -ForegroundColor Yellow
if (docker compose up -d --wait) {
    Write-Host "✅ Docker containers started" -ForegroundColor Green
} else {
    Write-Host "❌ Docker containers failed to start" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Install PHP dependencies
Write-Host "📦 Step 4/7: Installing PHP dependencies..." -ForegroundColor Yellow
if (docker compose exec php composer install) {
    Write-Host "✅ Composer install completed" -ForegroundColor Green
} else {
    Write-Host "❌ Composer install failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 5: Generate JWT keys
Write-Host "🔐 Step 5/7: Generating JWT authentication keys..." -ForegroundColor Yellow
if (docker compose exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists) {
    Write-Host "✅ JWT keys generated" -ForegroundColor Green
} else {
    Write-Host "❌ JWT key generation failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 6: Run database migrations
Write-Host "🗃️  Step 6/7: Running database migrations..." -ForegroundColor Yellow
if (docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction) {
    Write-Host "✅ Database migrations completed" -ForegroundColor Green
} else {
    Write-Host "❌ Database migrations failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 7: Clear cache
Write-Host "🧹 Step 7/7: Clearing Symfony cache..." -ForegroundColor Yellow
if (docker compose exec php php bin/console cache:clear) {
    Write-Host "✅ Cache cleared" -ForegroundColor Green
} else {
    Write-Host "❌ Cache clear failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "🎉 Setup completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Next steps:" -ForegroundColor Cyan
Write-Host "  1. Open http://localhost/ in your browser"
Write-Host "  2. Login with: test@test.com / password"
Write-Host "  3. Check http://localhost:8080 for database (Adminer)"
Write-Host "  4. Check http://localhost:8025 for emails (Mailpit)"
Write-Host ""
Write-Host "💡 To stop the project: docker compose down" -ForegroundColor Yellow
Write-Host "💡 To restart: docker compose up -d" -ForegroundColor Yellow
Write-Host ""
