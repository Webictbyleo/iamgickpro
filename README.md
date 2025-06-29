# IAMGickPro

A modern web-based design platform similar to Canva, built with Symfony 7 (PHP 8.4) backend and Vue 3 + TypeScript frontend.

## Features

- **Design Editor**: Canvas-based design interface with drag-and-drop functionality
- **Template Library**: 50+ professionally designed templates
- **Vector Shapes**: Comprehensive shape library with customization options
- **Text Editing**: Advanced typography controls and text effects
- **Image Processing**: Upload, resize, and apply filters to images
- **Export Options**: Multiple format exports (PNG, JPEG, SVG, PDF)
- **AI Integration**: AI-powered image generation via Replicate API
- **User Management**: Role-based access control and user accounts
- **Admin Panel**: Complete administration interface
- **Plugin System**: Extensible architecture for custom functionality

## Technology Stack

### Backend
- **Framework**: Symfony 7
- **Language**: PHP 8.4
- **Database**: MySQL 8.0+
- **Authentication**: JWT tokens
- **API**: RESTful API with comprehensive documentation

### Frontend
- **Framework**: Vue 3 with Composition API
- **Language**: TypeScript
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **Canvas**: Konva.js for 2D graphics
- **State Management**: Pinia

### Infrastructure
- **Web Server**: Nginx
- **Media Processing**: ImageMagick, FFmpeg
- **Caching**: Redis (optional)
- **SSL**: Let's Encrypt automatic certificates

## Quick Installation

For a complete automated installation, use the dedicated installer repository:

```bash
# Download and run the installer
curl -fsSL https://raw.githubusercontent.com/Webictbyleo/iamgickpro-installer/main/install.sh | bash
```

The installer will automatically:
- Install all system dependencies
- Configure web server and database
- Deploy the application
- Import sample content
- Set up SSL certificates

**Repository**: [iamgickpro-installer](https://github.com/Webictbyleo/iamgickpro-installer)

## Manual Development Setup

For development or manual installation:

### Prerequisites
- PHP 8.4+ with extensions: curl, dom, fileinfo, mbstring, openssl, pdo_mysql, tokenizer, xml
- Node.js 18+ and npm
- MySQL 8.0+
- Composer

### Backend Setup
```bash
cd backend
composer install
cp .env.local.example .env
# Configure your database and JWT settings in .env
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair
```

### Frontend Setup
```bash
cd frontend
npm install
npm run build
```

### Development Servers
```bash
# Backend (Symfony)
cd backend && php -S localhost:8000 -t public/

# Frontend (Vite dev server)
cd frontend && npm run dev
```

## Configuration

### Environment Variables
Key configuration options in `backend/.env`:

```bash
# Database
DATABASE_URL="mysql://username:password@localhost:3306/iamgickpro"

# JWT Authentication
JWT_SECRET_KEY="%kernel.project_dir%/config/jwt/private.pem"
JWT_PUBLIC_KEY="%kernel.project_dir%/config/jwt/public.pem"
JWT_PASSPHRASE="your-secure-passphrase"

# External APIs
REPLICATE_API_TOKEN="your-replicate-api-token"

# Email
MAILER_DSN="smtp://user:pass@host:port"
```

### Frontend Configuration
Configuration in `frontend/.env`:

```bash
VITE_API_BASE_URL="http://localhost:8000"
VITE_APP_NAME="IAMGickPro"
```

## Usage

### Creating Designs
1. **Start with a Template**: Choose from 50+ professional templates
2. **Add Elements**: Drag and drop shapes, text, and images
3. **Customize**: Modify colors, fonts, sizes, and effects
4. **Export**: Download in various formats (PNG, JPEG, SVG, PDF)

### Admin Features
- **User Management**: Create and manage user accounts
- **Template Management**: Upload and organize design templates
- **Shape Library**: Manage vector shape collections
- **Media Library**: Organize uploaded images and assets
- **System Settings**: Configure application preferences

## API Documentation

The API provides full programmatic access to all platform features:

- **Authentication**: JWT-based user authentication
- **Designs**: Create, read, update, delete designs
- **Templates**: Access template library
- **Shapes**: Vector shape management
- **Media**: File upload and management
- **Export**: Generate design exports

Access the interactive API documentation at `/api/doc` when running the application.

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes and commit: `git commit -m "Add new feature"`
4. Push to the branch: `git push origin feature/new-feature`
5. Submit a pull request

### Development Guidelines
- Follow PSR-12 coding standards for PHP
- Use TypeScript for all frontend code
- Write tests for new features
- Update documentation as needed

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For installation issues, use the [installer repository](https://github.com/YOUR_USERNAME/iamgickpro-installer).

For application bugs or feature requests, create an issue in this repository.

## Acknowledgments

- Built with [Symfony](https://symfony.com/) and [Vue.js](https://vuejs.org/)
- Canvas functionality powered by [Konva.js](https://konvajs.org/)
- UI components styled with [Tailwind CSS](https://tailwindcss.com/)
- AI image generation via [Replicate](https://replicate.com/)
