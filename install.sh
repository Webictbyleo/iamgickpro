#!/bin/bash

# ImagePro Design Platform - Production Installation Script
# This script sets up the complete production environment
# Run with: sudo ./install.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables
PROJECT_DIR="/var/www/html/iamgickpro"
NGINX_SITE_NAME="imagepro"
PHP_VERSION="8.4"
NODE_VERSION="21"
DB_NAME="imagepro_production"
DB_USER="imagepro_user"
WEB_USER="www-data"

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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to generate random password
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root (use sudo)"
    exit 1
fi

print_status "Starting ImagePro Design Platform installation..."
print_status "Project directory: $PROJECT_DIR"

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    print_error "Project directory $PROJECT_DIR does not exist!"
    exit 1
fi

# Update system packages
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required system packages
print_status "Installing system dependencies..."
apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    nginx \
    mysql-server \
    redis-server \
    supervisor \
    certbot \
    python3-certbot-nginx \
    imagemagick \
    ghostscript \
    inkscape \
    ffmpeg \
    webp \
    optipng \
    jpegoptim \
    pngquant

# Install PHP 8.4 and extensions
print_status "Installing PHP $PHP_VERSION and extensions..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xmlrpc \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-imagick \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-dev \
    php${PHP_VERSION}-imap \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-soap \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-memcached \
    php${PHP_VERSION}-sqlite3

# Install Composer
print_status "Installing Composer..."
if ! command_exists composer; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
fi

# Install Node.js and npm
print_status "Installing Node.js $NODE_VERSION..."
if ! command_exists node || [ "$(node -v | cut -d'v' -f2 | cut -d'.' -f1)" != "$NODE_VERSION" ]; then
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y nodejs
fi

# Configure MySQL
print_status "Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Generate database password
DB_PASSWORD=$(generate_password)
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Configure PHP-FPM
print_status "Configuring PHP-FPM..."
PHP_FPM_CONF="/etc/php/$PHP_VERSION/fpm/pool.d/www.conf"
PHP_INI="/etc/php/$PHP_VERSION/fpm/php.ini"

# Backup original configs
cp "$PHP_FPM_CONF" "$PHP_FPM_CONF.backup"
cp "$PHP_INI" "$PHP_INI.backup"

# Configure PHP-FPM pool
cat > "$PHP_FPM_CONF" << EOF
[www]
user = $WEB_USER
group = $WEB_USER
listen = /var/run/php/php${PHP_VERSION}-fpm.sock
listen.owner = $WEB_USER
listen.group = $WEB_USER
listen.mode = 0660
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
php_admin_value[error_log] = /var/log/php_errors.log
php_admin_flag[log_errors] = on
catch_workers_output = yes
security.limit_extensions = .php
env[HOSTNAME] = \$HOSTNAME
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp
EOF

# Configure PHP settings
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' "$PHP_INI"
sed -i 's/^post_max_size = .*/post_max_size = 100M/' "$PHP_INI"
sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_INI"
sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
sed -i 's/^max_input_time = .*/max_input_time = 300/' "$PHP_INI"
sed -i 's/^;date.timezone =.*/date.timezone = UTC/' "$PHP_INI"
sed -i 's/^;opcache.enable=.*/opcache.enable=1/' "$PHP_INI"
sed -i 's/^;opcache.memory_consumption=.*/opcache.memory_consumption=256/' "$PHP_INI"
sed -i 's/^;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' "$PHP_INI"
sed -i 's/^;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' "$PHP_INI"

# Restart PHP-FPM
systemctl restart php${PHP_VERSION}-fpm
systemctl enable php${PHP_VERSION}-fpm

# Set up project directory permissions
print_status "Setting up project permissions..."
chown -R $WEB_USER:$WEB_USER "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"

# Create necessary directories
mkdir -p "$PROJECT_DIR/backend/var/cache"
mkdir -p "$PROJECT_DIR/backend/var/log"
mkdir -p "$PROJECT_DIR/backend/public/uploads"
mkdir -p "$PROJECT_DIR/backend/public/uploads/media"
mkdir -p "$PROJECT_DIR/backend/public/uploads/thumbnails"
mkdir -p "$PROJECT_DIR/backend/storage"
mkdir -p "$PROJECT_DIR/frontend/dist"

# Set special permissions for writable directories
chmod -R 775 "$PROJECT_DIR/backend/var"
chmod -R 775 "$PROJECT_DIR/backend/public/uploads"
chmod -R 775 "$PROJECT_DIR/backend/storage"

# Install backend dependencies
print_status "Installing backend dependencies..."
cd "$PROJECT_DIR/backend"
sudo -u $WEB_USER composer install --no-dev --optimize-autoloader

# Create backend environment file
print_status "Creating backend environment configuration..."
JWT_SECRET=$(generate_password)
APP_SECRET=$(generate_password)

cat > "$PROJECT_DIR/backend/.env.local" << EOF
# Production environment configuration
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=$APP_SECRET

# Database configuration
DATABASE_URL="mysql://$DB_USER:$DB_PASSWORD@127.0.0.1:3306/$DB_NAME?serverVersion=8.0&charset=utf8mb4"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=$JWT_SECRET

# Redis configuration
REDIS_URL=redis://localhost:6379

# Mailer configuration (update with your SMTP settings)
MAILER_DSN=smtp://localhost:1025

# External services (update with your API keys)
REPLICATE_API_TOKEN=your_replicate_token_here
YOUTUBE_API_KEY=your_youtube_api_key_here

# File upload settings
MAX_UPLOAD_SIZE=104857600
ALLOWED_MIME_TYPES=image/jpeg,image/png,image/gif,image/webp,image/svg+xml,video/mp4,video/webm

# Application settings
FRONTEND_URL=https://your-domain.com
CORS_ALLOW_ORIGIN=https://your-domain.com
EOF

# Generate JWT keys
print_status "Generating JWT keys..."
mkdir -p "$PROJECT_DIR/backend/config/jwt"
openssl genpkey -out "$PROJECT_DIR/backend/config/jwt/private.pem" -aes256 -algorithm rsa -pkcs8 -pass pass:$JWT_SECRET
openssl pkey -in "$PROJECT_DIR/backend/config/jwt/private.pem" -passin pass:$JWT_SECRET -out "$PROJECT_DIR/backend/config/jwt/public.pem" -pubout

chown -R $WEB_USER:$WEB_USER "$PROJECT_DIR/backend/config/jwt"
chmod 600 "$PROJECT_DIR/backend/config/jwt/private.pem"
chmod 644 "$PROJECT_DIR/backend/config/jwt/public.pem"

# Run database migrations
print_status "Running database migrations..."
sudo -u $WEB_USER php bin/console doctrine:migrations:migrate --no-interaction

# Clear and warm up cache
print_status "Clearing and warming up cache..."
sudo -u $WEB_USER php bin/console cache:clear --env=prod
sudo -u $WEB_USER php bin/console cache:warmup --env=prod

# Install frontend dependencies and build
print_status "Installing frontend dependencies and building..."
cd "$PROJECT_DIR/frontend"
sudo -u $WEB_USER npm ci --only=production
sudo -u $WEB_USER npm run build

# Configure Nginx
print_status "Configuring Nginx..."
cp "$PROJECT_DIR/nginx-production.conf" "/etc/nginx/sites-available/$NGINX_SITE_NAME"

# Update domain in nginx config (prompt user for domain)
read -p "Enter your domain name (e.g., your-domain.com): " DOMAIN_NAME
if [ -z "$DOMAIN_NAME" ]; then
    print_warning "No domain provided, using localhost"
    DOMAIN_NAME="localhost"
fi

sed -i "s/your-domain.com/$DOMAIN_NAME/g" "/etc/nginx/sites-available/$NGINX_SITE_NAME"

# Enable site
ln -sf "/etc/nginx/sites-available/$NGINX_SITE_NAME" "/etc/nginx/sites-enabled/"
rm -f /etc/nginx/sites-enabled/default

# Test nginx configuration
if nginx -t; then
    print_success "Nginx configuration is valid"
    systemctl restart nginx
    systemctl enable nginx
else
    print_error "Nginx configuration error"
    exit 1
fi

# Configure Redis
print_status "Configuring Redis..."
systemctl restart redis-server
systemctl enable redis-server

# Configure Supervisor for background jobs
print_status "Configuring Supervisor for background jobs..."
cat > "/etc/supervisor/conf.d/imagepro-messenger.conf" << EOF
[program:imagepro-messenger]
command=php $PROJECT_DIR/backend/bin/console messenger:consume async --time-limit=3600
user=$WEB_USER
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=$PROJECT_DIR/backend/var/log/messenger.out.log
stderr_logfile=$PROJECT_DIR/backend/var/log/messenger.err.log
directory=$PROJECT_DIR/backend
EOF

supervisorctl reread
supervisorctl update
supervisorctl start imagepro-messenger:*

# Setup log rotation
print_status "Setting up log rotation..."
cat > "/etc/logrotate.d/imagepro" << EOF
$PROJECT_DIR/backend/var/log/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 $WEB_USER $WEB_USER
    postrotate
        systemctl reload php${PHP_VERSION}-fpm
    endscript
}
EOF

# Setup firewall (UFW)
print_status "Configuring firewall..."
if command_exists ufw; then
    ufw allow OpenSSH
    ufw allow 'Nginx Full'
    ufw --force enable
fi

# Create backup script
print_status "Creating backup script..."
cat > "/usr/local/bin/imagepro-backup" << 'EOF'
#!/bin/bash
# ImagePro backup script

BACKUP_DIR="/opt/backups/imagepro"
PROJECT_DIR="/var/www/html/iamgickpro"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="imagepro_production"

mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump "$DB_NAME" | gzip > "$BACKUP_DIR/database_$DATE.sql.gz"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C "$PROJECT_DIR/backend/public" uploads/

# Backup configuration
tar -czf "$BACKUP_DIR/config_$DATE.tar.gz" \
    "$PROJECT_DIR/backend/.env.local" \
    "$PROJECT_DIR/backend/config/jwt/" \
    "/etc/nginx/sites-available/imagepro"

# Remove backups older than 30 days
find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
EOF

chmod +x /usr/local/bin/imagepro-backup

# Add backup to crontab
print_status "Setting up automated backups..."
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/imagepro-backup") | crontab -

# Setup SSL certificate with Let's Encrypt (if not localhost)
if [ "$DOMAIN_NAME" != "localhost" ]; then
    print_status "Setting up SSL certificate with Let's Encrypt..."
    read -p "Do you want to setup SSL certificate with Let's Encrypt? (y/n): " SETUP_SSL
    if [ "$SETUP_SSL" = "y" ] || [ "$SETUP_SSL" = "Y" ]; then
        certbot --nginx -d "$DOMAIN_NAME" -d "www.$DOMAIN_NAME" --non-interactive --agree-tos --email admin@$DOMAIN_NAME
        
        # Setup auto-renewal
        (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
    fi
fi

# Create system service for monitoring
print_status "Creating monitoring service..."
cat > "/etc/systemd/system/imagepro-monitor.service" << EOF
[Unit]
Description=ImagePro Health Monitor
After=network.target

[Service]
Type=simple
User=$WEB_USER
WorkingDirectory=$PROJECT_DIR/backend
ExecStart=/usr/bin/php bin/console app:health-check
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable imagepro-monitor
systemctl start imagepro-monitor

# Final setup and permissions check
print_status "Final setup and permissions check..."
chown -R $WEB_USER:$WEB_USER "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/backend/var"
chmod -R 775 "$PROJECT_DIR/backend/public/uploads"
chmod -R 775 "$PROJECT_DIR/backend/storage"

# Print installation summary
print_success "=========================================="
print_success "ImagePro Design Platform Installation Complete!"
print_success "=========================================="
echo ""
print_status "Installation Details:"
echo "  • Project Directory: $PROJECT_DIR"
echo "  • Domain: $DOMAIN_NAME"
echo "  • Database: $DB_NAME"
echo "  • Database User: $DB_USER"
echo "  • Database Password: $DB_PASSWORD"
echo "  • JWT Secret: $JWT_SECRET"
echo "  • Web User: $WEB_USER"
echo ""
print_status "Service Status:"
systemctl is-active nginx && echo "  • Nginx: Running" || echo "  • Nginx: Stopped"
systemctl is-active php${PHP_VERSION}-fpm && echo "  • PHP-FPM: Running" || echo "  • PHP-FPM: Stopped"
systemctl is-active mysql && echo "  • MySQL: Running" || echo "  • MySQL: Stopped"
systemctl is-active redis-server && echo "  • Redis: Running" || echo "  • Redis: Stopped"
echo ""
print_status "Next Steps:"
echo "  1. Update the domain name in your DNS settings"
echo "  2. Configure your external API keys in $PROJECT_DIR/backend/.env.local"
echo "  3. Set up your email SMTP settings"
echo "  4. Test the application at https://$DOMAIN_NAME"
echo "  5. Review the nginx logs: tail -f /var/log/nginx/error.log"
echo "  6. Review the application logs: tail -f $PROJECT_DIR/backend/var/log/prod.log"
echo ""
print_warning "IMPORTANT: Save the database password and JWT secret in a secure location!"
echo "Database Password: $DB_PASSWORD"
echo "JWT Secret: $JWT_SECRET"
echo ""
print_success "Installation completed successfully!"
