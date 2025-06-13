# Media File Serving Setup Documentation

## Overview

This document explains the complete media file serving setup for the ImagePro platform, covering both development and production environments.

## Current Architecture

### Development Environment (PHP Dev Server)
- **Server**: PHP built-in development server (`php -S localhost:8000 -t public/`)
- **Media Serving**: Handled by Symfony's `MediaFileController`
- **Routes**:
  - `/media/{filename}` - Direct media file access
  - `/thumbnails/{filename}` - Thumbnail access
  - `/secure-media/{uuid}` - Authenticated media access

### Production Environment (Nginx + PHP-FPM)
- **Server**: Nginx with PHP-FPM
- **Media Serving**: Hybrid approach - direct Nginx serving with Symfony fallback
- **Performance**: Optimized with caching, compression, and direct file serving

## File Structure

```
backend/public/uploads/
├── media/           # Original uploaded media files
├── thumbnails/      # Generated thumbnails
├── avatars/         # User avatars
└── plugins/         # Plugin files
```

## URL Patterns

### Development URLs
- `http://localhost:8000/media/filename.jpg`
- `http://localhost:8000/thumbnails/thumb_filename.jpg`
- `http://localhost:8000/secure-media/uuid-here`

### Production URLs
- `https://your-domain.com/media/filename.jpg`
- `https://your-domain.com/thumbnails/thumb_filename.jpg`
- `https://your-domain.com/secure-media/uuid-here`

## Security Features

### Access Control
- **Public Media**: Accessible to all users
- **Premium Media**: Requires premium subscription
- **Private Media**: Only accessible by owner
- **Admin Override**: Admins can access all media

### File Security
- Script execution prevention in upload directories
- MIME type validation
- Content-Type-Options: nosniff header
- Malicious file detection and forced download

### Headers Applied
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Cache-Control: public, max-age=2592000
Content-Type: [appropriate MIME type]
```

## Performance Optimizations

### Caching Strategy
- **Media Files**: 7 days cache
- **Thumbnails**: 30 days cache
- **Conditional Requests**: ETag and Last-Modified support
- **Browser Caching**: Proper Cache-Control headers

### Nginx Optimizations (Production)
- Direct file serving (bypasses PHP for static files)
- Gzip compression for text content
- HTTP/2 support
- Rate limiting for uploads

## Development Setup

### Starting the Development Server
```bash
# Option 1: Use VS Code task
# Press Ctrl+Shift+P → "Tasks: Run Task" → "Start Backend Dev Server"

# Option 2: Manual command
cd backend
php -S localhost:8000 -t public/
```

### Testing Media Serving
```bash
# Run comprehensive tests
./test_media_serving_comprehensive.sh

# Manual testing
curl -I http://localhost:8000/media/test.jpg
curl -I http://localhost:8000/thumbnails/thumb_test.jpg
```

## Production Deployment

### 1. Nginx Configuration
```bash
# Copy nginx configuration
sudo cp nginx-production.conf /etc/nginx/sites-available/imagepro
sudo ln -s /etc/nginx/sites-available/imagepro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 2. PHP-FPM Configuration
```bash
# Ensure PHP-FPM is running
sudo systemctl status php8.4-fpm
sudo systemctl enable php8.4-fpm
```

### 3. Directory Permissions
```bash
# Set proper permissions for upload directories
sudo chown -R www-data:www-data /var/www/html/iamgickpro/backend/public/uploads/
sudo chmod -R 755 /var/www/html/iamgickpro/backend/public/uploads/
```

### 4. SSL Certificate (Production)
```bash
# Using Let's Encrypt (recommended)
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

## Configuration Files

### MediaFileController Parameters
Located in `config/services.yaml`:
```yaml
parameters:
    app.upload_directory: '%kernel.project_dir%/public/uploads'
    app.media_directory: '%app.upload_directory%/media'
    app.thumbnail_directory: '%app.upload_directory%/thumbnails'
```

### Nginx Rate Limiting
Add to main nginx.conf in `http` block:
```nginx
limit_req_zone $binary_remote_addr zone=upload:10m rate=10r/m;
```

## API Integration

### Frontend Integration
```typescript
// Media URL generation
const getMediaUrl = (filename: string): string => {
  return `${API_BASE_URL}/media/${filename}`;
};

const getThumbnailUrl = (filename: string): string => {
  return `${API_BASE_URL}/thumbnails/${filename}`;
};

// Secure media with authentication
const getSecureMediaUrl = (uuid: string): string => {
  return `${API_BASE_URL}/secure-media/${uuid}`;
};
```

### Upload Handling
The media files are uploaded through the MediaController and automatically processed:
1. File validation and storage
2. Thumbnail generation
3. Metadata extraction
4. Database record creation

## Monitoring and Logging

### Nginx Access Logs
```bash
# View media access logs
sudo tail -f /var/log/nginx/access.log | grep -E "(media|thumbnails)"
```

### Symfony Logs
```bash
# View Symfony logs for media operations
tail -f backend/var/log/prod.log | grep -i media
```

### Performance Monitoring
- Monitor media file serving response times
- Track cache hit rates
- Monitor disk usage in uploads directory

## Troubleshooting

### Common Issues

1. **404 Errors for Media Files**
   - Check file permissions
   - Verify file exists in correct directory
   - Check Nginx configuration

2. **Slow Media Loading**
   - Verify cache headers are set
   - Check if files are being served directly by Nginx
   - Consider CDN integration

3. **Security Errors**
   - Check file MIME types
   - Verify upload directory permissions
   - Review security headers

### Debug Commands
```bash
# Check if media files exist
ls -la backend/public/uploads/media/

# Test direct file access
curl -v http://localhost:8000/media/test.jpg

# Check Nginx configuration
sudo nginx -t

# View PHP-FPM status
sudo systemctl status php8.4-fpm
```

## Future Enhancements

### CDN Integration
Consider implementing CDN for production:
- CloudFlare for global distribution
- AWS CloudFront for scalability
- Automatic image optimization

### Advanced Caching
- Redis cache for metadata
- Image processing cache
- Progressive image loading

### Monitoring
- File access analytics
- Performance metrics
- Error tracking

## Testing Checklist

- [ ] Media files serve correctly in development
- [ ] Thumbnails are accessible
- [ ] Security headers are present
- [ ] Cache headers are set properly
- [ ] Non-existent files return 404
- [ ] Authentication works for secure media
- [ ] File permissions are correct
- [ ] Nginx configuration is valid
- [ ] SSL certificate is installed
- [ ] Performance is acceptable

## Conclusion

This setup provides a robust, secure, and performant media serving solution that scales from development to production. The hybrid approach ensures both security and performance while maintaining flexibility for future enhancements.
