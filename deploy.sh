#!/bin/bash

# ImagePro Design Platform - Deployment Update Script
# This script handles updates to the production environment
# Run with: ./deploy.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables
PROJECT_DIR="/var/www/html/iamgickpro"
WEB_USER="www-data"
PHP_VERSION="8.4"
BACKUP_DIR="/opt/backups/imagepro"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root or with sudo
check_permissions() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run this script as root or with sudo"
        exit 1
    fi
}

# Function to create backup before deployment
create_backup() {
    print_status "Creating backup before deployment..."
    mkdir -p "$BACKUP_DIR"
    
    DATE=$(date +%Y%m%d_%H%M%S)
    
    # Backup database
    mysqldump imagepro_production | gzip > "$BACKUP_DIR/pre_deploy_database_$DATE.sql.gz"
    
    # Backup current application
    tar -czf "$BACKUP_DIR/pre_deploy_app_$DATE.tar.gz" \
        -C "$PROJECT_DIR" \
        --exclude='backend/var/cache' \
        --exclude='backend/var/log' \
        --exclude='node_modules' \
        --exclude='.git' \
        backend frontend
    
    print_success "Backup created: $BACKUP_DIR/pre_deploy_*_$DATE.*"
}

# Function to pull latest changes
pull_changes() {
    print_status "Pulling latest changes from repository..."
    cd "$PROJECT_DIR"
    
    # Stash any local changes
    sudo -u $WEB_USER git stash
    
    # Pull latest changes
    sudo -u $WEB_USER git pull origin production
    
    print_success "Latest changes pulled successfully"
}

# Function to update backend dependencies and run migrations
update_backend() {
    print_status "Updating backend dependencies..."
    cd "$PROJECT_DIR/backend"
    
    # Update composer dependencies
    sudo -u $WEB_USER composer install --no-dev --optimize-autoloader
    
    # Run database migrations
    print_status "Running database migrations..."
    sudo -u $WEB_USER php bin/console doctrine:migrations:migrate --no-interaction
    
    # Clear cache
    print_status "Clearing backend cache..."
    sudo -u $WEB_USER php bin/console cache:clear --env=prod
    sudo -u $WEB_USER php bin/console cache:warmup --env=prod
    
    print_success "Backend updated successfully"
}

# Function to update frontend and build
update_frontend() {
    print_status "Updating frontend dependencies and building..."
    cd "$PROJECT_DIR/frontend"
    
    # Update npm dependencies
    sudo -u $WEB_USER npm ci --only=production
    
    # Build for production
    sudo -u $WEB_USER npm run build
    
    print_success "Frontend updated and built successfully"
}

# Function to restart services
restart_services() {
    print_status "Restarting services..."
    
    # Restart PHP-FPM
    systemctl restart php${PHP_VERSION}-fpm
    
    # Restart Nginx
    systemctl restart nginx
    
    # Restart Redis
    systemctl restart redis-server
    
    # Restart Supervisor
    supervisorctl restart imagepro-messenger:*
    
    print_success "Services restarted successfully"
}

# Function to run health checks
run_health_checks() {
    print_status "Running health checks..."
    
    # Check if services are running
    services=("nginx" "php${PHP_VERSION}-fpm" "mysql" "redis-server")
    for service in "${services[@]}"; do
        if systemctl is-active --quiet "$service"; then
            print_success "$service is running"
        else
            print_error "$service is not running"
            return 1
        fi
    done
    
    # Check if application responds
    sleep 5
    if curl -f -s http://localhost/health > /dev/null; then
        print_success "Application health check passed"
    else
        print_warning "Application health check failed - check logs"
    fi
    
    # Check database connection
    cd "$PROJECT_DIR/backend"
    if sudo -u $WEB_USER php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
        print_success "Database connection test passed"
    else
        print_error "Database connection test failed"
        return 1
    fi
}

# Function to set proper permissions
set_permissions() {
    print_status "Setting proper file permissions..."
    
    chown -R $WEB_USER:$WEB_USER "$PROJECT_DIR"
    chmod -R 755 "$PROJECT_DIR"
    chmod -R 775 "$PROJECT_DIR/backend/var"
    chmod -R 775 "$PROJECT_DIR/backend/public/uploads"
    chmod -R 775 "$PROJECT_DIR/backend/storage"
    
    print_success "Permissions set successfully"
}

# Function to show deployment status
show_status() {
    print_success "=========================================="
    print_success "Deployment Status"
    print_success "=========================================="
    echo ""
    
    # Show git status
    cd "$PROJECT_DIR"
    echo "Current Git Commit:"
    git log --oneline -1
    echo ""
    
    # Show service status
    echo "Service Status:"
    systemctl is-active nginx && echo "  • Nginx: Running" || echo "  • Nginx: Stopped"
    systemctl is-active php${PHP_VERSION}-fpm && echo "  • PHP-FPM: Running" || echo "  • PHP-FPM: Stopped"
    systemctl is-active mysql && echo "  • MySQL: Running" || echo "  • MySQL: Stopped"
    systemctl is-active redis-server && echo "  • Redis: Running" || echo "  • Redis: Stopped"
    echo ""
    
    # Show disk usage
    echo "Disk Usage:"
    df -h "$PROJECT_DIR" | tail -1
    echo ""
    
    # Show recent logs
    echo "Recent Error Logs (last 5 lines):"
    tail -5 /var/log/nginx/error.log 2>/dev/null || echo "  No nginx error logs"
    tail -5 "$PROJECT_DIR/backend/var/log/prod.log" 2>/dev/null || echo "  No application logs"
}

# Main deployment function
main() {
    print_status "Starting ImagePro deployment..."
    
    check_permissions
    
    # Enable maintenance mode (optional)
    if [ -f "$PROJECT_DIR/frontend/dist/index.html" ]; then
        cp "$PROJECT_DIR/frontend/dist/index.html" "$PROJECT_DIR/frontend/dist/index.html.backup"
    fi
    
    # Run deployment steps
    create_backup
    pull_changes
    update_backend
    update_frontend
    set_permissions
    restart_services
    
    # Disable maintenance mode
    if [ -f "$PROJECT_DIR/frontend/dist/index.html.backup" ]; then
        rm -f "$PROJECT_DIR/frontend/dist/index.html.backup"
    fi
    
    # Run health checks
    if run_health_checks; then
        print_success "Deployment completed successfully!"
        show_status
    else
        print_error "Deployment completed with warnings - check logs"
        show_status
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    "backend")
        print_status "Updating backend only..."
        check_permissions
        create_backup
        update_backend
        restart_services
        run_health_checks
        ;;
    "frontend")
        print_status "Updating frontend only..."
        check_permissions
        update_frontend
        systemctl restart nginx
        ;;
    "status")
        show_status
        ;;
    "health")
        run_health_checks
        ;;
    "backup")
        check_permissions
        create_backup
        ;;
    *)
        main
        ;;
esac
