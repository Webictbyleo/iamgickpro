# IAMGickPro Production Installer

This comprehensive installer sets up a complete production environment for the IAMGickPro design platform.

## Features

- **Automated System Setup**: Installs nginx, MySQL, PHP 8.4, Redis, and all required dependencies
- **Application Deployment**: Clones repository, builds frontend, configures backend
- **Database Management**: Creates database, runs migrations, imports content
- **Media Processing**: Compiles ImageMagick and FFmpeg from source for optimal performance
- **Security Configuration**: Sets up proper file permissions, firewall, SSL certificates
- **Background Services**: Configures async job processing and monitoring
- **Backup & Maintenance**: Automated backups, log rotation, update tools

## Installation Phases

The installer is organized into 10 phases for reliability and recovery:

1. **User Input** - Collects configuration and validates database connection
2. **System Setup** - Installs system dependencies (nginx, MySQL, PHP 8.4, Redis)
3. **Repository Clone** - Clones main repo and shapes repo from GitHub
4. **Environment Configuration** - Generates .env files for backend and frontend
5. **Backend Setup** - Installs PHP dependencies, generates JWT keys, sets permissions
6. **Frontend Setup** - Builds Vue.js application and deploys to webroot
7. **Database Setup** - Creates database, runs migrations, optimizes for production
8. **Content Import** - Imports design templates and vector shapes
9. **Media Dependencies** - Compiles ImageMagick and FFmpeg from source
10. **Final Configuration** - Creates admin user, sets up monitoring, generates summary

## Requirements

### System Requirements
- Ubuntu 20.04+ or Debian 11+ (CentOS/RHEL experimental)
- Minimum 2GB RAM (4GB+ recommended)
- 20GB+ available disk space
- Root access (sudo)

### Network Requirements
- Internet connection for downloading dependencies
- Domain name (recommended for production)
- Port 80 and 443 accessible

## Usage

### Quick Start
```bash
# Download or clone the project
git clone https://github.com/Webictbyleo/iamgickpro.git
cd iamgickpro

# Run the installer
sudo ./install.sh
```

### What You'll Need
During installation, you'll be prompted for:

- **Domain name** (e.g., mydesignstudio.com)
- **Database configuration** (name, user, password)
- **MySQL root password** (for database creation)
- **Admin account** (email and password)
- **External API keys** (Unsplash, Pexels - optional)

## Installation Process

### Step 1: Run Installer
```bash
sudo ./install.sh
```

### Step 2: Provide Configuration
The installer will prompt you for:
- Domain name
- Database credentials
- Admin account details
- Application settings
- External service API keys (optional)

### Step 3: Wait for Completion
The installer will:
- Install system dependencies (~5-10 minutes)
- Clone and configure the application (~2-3 minutes)
- Build frontend and setup backend (~3-5 minutes)
- Compile media dependencies (~15-30 minutes)
- Import content and finalize setup (~5-10 minutes)

**Total installation time: 30-60 minutes** (depending on system performance)

## Post-Installation

### Access Your Application
- **Frontend URL**: https://yourdomain.com
- **Admin Login**: Use the email/password you provided during installation

### Important Files
- **Installation Summary**: `/root/iamgickpro-installation-summary.txt`
- **Backend Config**: `/var/www/html/iamgickpro/backend/.env.local`
- **Nginx Config**: `/etc/nginx/sites-available/iamgickpro`
- **Log Files**: `/var/www/html/iamgickpro/backend/var/log/`

### Management Commands
```bash
# Check system status
iamgickpro-status

# Update application
iamgickpro-update

# Update templates and shapes
iamgickpro-update-content

# Create database backup
iamgickpro-backup
```

## Security Setup

### SSL Certificate (Recommended)
```bash
# Install Let's Encrypt certificate
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Firewall Configuration
The installer automatically configures UFW firewall:
- SSH (port 22)
- HTTP (port 80)
- HTTPS (port 443)

### File Permissions
All file permissions are automatically configured for security:
- Application files: 755 (www-data:www-data)
- Sensitive configs: 600 (www-data:www-data)
- Upload directories: 775 (www-data:www-data)

## Troubleshooting

### Common Issues

#### Installation Fails
- Check `/var/log/iamgickpro-install.log` for detailed error messages
- Ensure you have sufficient disk space and memory
- Verify internet connection for downloading dependencies

#### Database Connection Error
- Verify MySQL is running: `systemctl status mysql`
- Check root password is correct
- Ensure MySQL allows root login

#### Frontend Not Loading
- Check nginx status: `systemctl status nginx`
- Verify nginx configuration: `nginx -t`
- Check application logs: `tail -f /var/www/html/iamgickpro/backend/var/log/prod.log`

#### Permission Errors
- Reset permissions: `chown -R www-data:www-data /var/www/html/iamgickpro`
- Check upload directory permissions: `ls -la /var/www/html/iamgickpro/backend/public/uploads`

### Recovery and Cleanup

#### Resume Installation
If installation fails, you can resume from any phase by running the installer again. Completed phases are automatically skipped.

#### Complete Cleanup
```bash
# Stop services
systemctl stop nginx php8.4-fpm mysql redis-server iamgickpro-worker

# Remove application
rm -rf /var/www/html/iamgickpro

# Remove database
mysql -e "DROP DATABASE IF EXISTS iamgickpro; DROP USER IF EXISTS 'iamgickpro'@'%';"

# Remove nginx config
rm -f /etc/nginx/sites-enabled/iamgickpro /etc/nginx/sites-available/iamgickpro

# Remove maintenance scripts
rm -f /usr/local/bin/iamgickpro-*
```

## Configuration Customization

### Environment Variables
Edit `/var/www/html/iamgickpro/backend/.env.local` to customize:
- External API keys
- Email configuration
- File upload limits
- Security settings

### Nginx Configuration
Edit `/etc/nginx/sites-available/iamgickpro` to customize:
- SSL settings
- Performance optimizations
- Custom headers
- Rate limiting

### PHP Configuration
Edit `/etc/php/8.4/fpm/pool.d/iamgickpro.conf` to customize:
- Memory limits
- Upload sizes
- Performance settings

## Monitoring and Maintenance

### Automated Backups
- **Database**: Daily at 2:00 AM (kept for 7 days)
- **Content**: Weekly (kept for 30 days)
- **Configuration**: With each backup

### Log Rotation
- **Application logs**: Daily rotation, 14 days retention
- **PHP error logs**: Daily rotation, 7 days retention
- **Nginx logs**: System default

### Health Monitoring
- Background worker service automatically restarts
- System monitoring service checks application health
- Email notifications for critical errors (if configured)

## Updates

### Application Updates
```bash
# Update to latest version
iamgickpro-update
```

### System Updates
```bash
# Update system packages
apt update && apt upgrade -y

# Restart services if needed
systemctl restart nginx php8.4-fpm mysql redis-server
```

## Support

### Getting Help
- **Documentation**: Check `/root/iamgickpro-installation-summary.txt`
- **Logs**: Check `/var/log/iamgickpro-install.log`
- **GitHub Issues**: https://github.com/Webictbyleo/iamgickpro/issues

### Reporting Issues
When reporting issues, please include:
- Operating system and version
- Installation log excerpts
- Error messages
- System status output (`iamgickpro-status`)

## Advanced Configuration

### Custom Domain Setup
1. Point your domain's A record to the server IP
2. Update nginx configuration with your domain
3. Install SSL certificate with Let's Encrypt
4. Update backend `.env.local` with new domain

### Performance Tuning
- **Database**: Optimize MySQL configuration for your workload
- **PHP**: Adjust memory limits and process counts
- **Nginx**: Enable gzip compression and browser caching
- **Redis**: Configure memory limits and persistence

### Scaling Considerations
- **Horizontal**: Use load balancer with multiple application servers
- **Database**: Consider read replicas for high-traffic sites
- **Storage**: Use CDN for static assets and uploads
- **Caching**: Implement application-level caching strategies

---

**IAMGickPro Production Installer v1.0.0**  
For questions and support, visit: https://github.com/Webictbyleo/iamgickpro
