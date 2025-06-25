# ImagePro Design Platform - Production Deployment Guide

## Overview

This production branch contains a clean, optimized version of the ImagePro Design Platform ready for deployment. All development files, documentation, and test artifacts have been removed.

## Quick Deployment

### Prerequisites
- Ubuntu 20.04+ or CentOS 8+ server
- Root or sudo access
- Domain name (optional, can use IP address)

### One-Command Installation
```bash
# Clone the production branch
git clone -b production https://github.com/your-repo/iamgickpro.git /var/www/html/iamgickpro

# Run the installation script
cd /var/www/html/iamgickpro
sudo ./install.sh
```

## Manual Installation

### 1. System Requirements
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y \
    nginx mysql-server redis-server \
    php8.4-fpm php8.4-cli php8.4-mysql php8.4-gd php8.4-xml \
    php8.4-curl php8.4-mbstring php8.4-intl php8.4-zip \
    nodejs npm curl wget git unzip
```

### 2. Configure Services

#### MySQL
```bash
sudo mysql -e "CREATE DATABASE imagepro_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'imagepro_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON imagepro_production.* TO 'imagepro_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

#### Nginx
```bash
# Copy configuration
sudo cp nginx-production.conf /etc/nginx/sites-available/imagepro

# Update domain name
sudo sed -i 's/your-domain.com/your-actual-domain.com/g' /etc/nginx/sites-available/imagepro

# Enable site
sudo ln -s /etc/nginx/sites-available/imagepro /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test and restart
sudo nginx -t && sudo systemctl restart nginx
```

### 3. Application Setup

#### Backend
```bash
cd backend

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Create environment file
cp .env.production.template .env.local

# Edit .env.local with your configuration:
# - Database credentials
# - JWT secrets
# - API keys
# - Domain settings

# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkcs8
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

#### Frontend
```bash
cd frontend

# Install dependencies and build
npm ci --only=production
npm run build
```

### 4. Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/iamgickpro
sudo chmod -R 755 /var/www/html/iamgickpro
sudo chmod -R 775 /var/www/html/iamgickpro/backend/var
sudo chmod -R 775 /var/www/html/iamgickpro/backend/public/uploads
```

### 5. SSL Certificate (Optional but Recommended)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## Configuration Files

### Environment Variables (.env.local)
```env
APP_ENV=prod
APP_DEBUG=false
APP_SECRET=your_app_secret_here
DATABASE_URL="mysql://imagepro_user:password@127.0.0.1:3306/imagepro_production"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase
REDIS_URL=redis://localhost:6379
MAILER_DSN=smtp://your-smtp-server:587
FRONTEND_URL=https://your-domain.com
CORS_ALLOW_ORIGIN=https://your-domain.com
```

### Nginx Configuration
The `nginx-production.conf` file includes:
- SSL/TLS configuration
- Security headers
- Rate limiting
- Static file serving
- PHP-FPM configuration
- Frontend serving from `/dist`

## Deployment Updates

Use the deployment script for updates:

```bash
# Full deployment update
sudo ./deploy.sh

# Backend only
sudo ./deploy.sh backend

# Frontend only  
sudo ./deploy.sh frontend

# Create admin user
sudo ./deploy.sh create-admin

# Check status
./deploy.sh status

# Health check
./deploy.sh health

# Create backup
sudo ./deploy.sh backup
```

### Admin User Management

During installation, an admin user is automatically created. You can also create additional admin users:

```bash
# Interactive admin creation
cd backend
sudo -u www-data php bin/console app:create-admin

# Non-interactive admin creation
sudo -u www-data php bin/console app:create-admin \
    --email="admin@example.com" \
    --password="SecurePassword123" \
    --first-name="John" \
    --last-name="Admin" \
    --username="johnadmin"
```

## Monitoring

### Health Check
Visit: `https://your-domain.com/health`

### Log Files
- Application: `backend/var/log/prod.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php8.4-fpm.log`

### System Status
```bash
# Check services
sudo systemctl status nginx php8.4-fpm mysql redis-server

# Check processes
ps aux | grep -E "(nginx|php-fpm|mysql|redis)"

# Check disk space
df -h

# Check memory usage
free -h
```

## Security Features

- **SSL/TLS**: Full HTTPS encryption
- **Rate Limiting**: API endpoint protection
- **Security Headers**: XSS, CSRF, content sniffing protection
- **File Upload**: Restricted file types and sizes
- **Database**: Secure credentials and connections
- **JWT**: Token-based authentication

## Performance Optimizations

- **PHP OPcache**: Enabled for production
- **Redis**: Session and cache storage
- **Gzip**: Compression for static assets
- **Static Files**: Direct nginx serving
- **Database**: Optimized queries and indexing

## Backup and Recovery

### Automated Backups
Backups run daily at 2 AM:
- Database dumps
- Upload files
- Configuration files

Location: `/opt/backups/imagepro/`

### Manual Backup
```bash
sudo /usr/local/bin/imagepro-backup
```

### Restore from Backup
```bash
# Database
gunzip < /opt/backups/imagepro/database_YYYYMMDD_HHMMSS.sql.gz | mysql imagepro_production

# Files
tar -xzf /opt/backups/imagepro/uploads_YYYYMMDD_HHMMSS.tar.gz -C backend/public/
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check nginx error log: `tail -f /var/log/nginx/error.log`
   - Check PHP-FPM log: `tail -f /var/log/php8.4-fpm.log`
   - Check application log: `tail -f backend/var/log/prod.log`

2. **Database Connection Issues**
   - Verify credentials in `.env.local`
   - Check MySQL service: `sudo systemctl status mysql`
   - Test connection: `php bin/console doctrine:query:sql "SELECT 1"`

3. **File Upload Issues**
   - Check directory permissions: `ls -la backend/public/uploads/`
   - Verify nginx upload limits in configuration
   - Check PHP upload settings: `php -i | grep upload`

4. **Frontend Not Loading**
   - Verify build completed: `ls -la frontend/dist/`
   - Check nginx configuration for static file serving
   - Verify domain configuration

### Performance Issues
```bash
# Check CPU/Memory usage
top
htop

# Check disk I/O
iotop

# Check nginx connections
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443

# Check PHP-FPM status
sudo systemctl status php8.4-fpm
```

## Support

For issues:
1. Check the logs first
2. Verify all services are running
3. Check file permissions
4. Review configuration files
5. Test database connectivity

## License

MIT License - see LICENSE file for details.
