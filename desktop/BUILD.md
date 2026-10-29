# Build Scripts for KUKU Farm Manager Pro

## Development Setup
```bash
# Install dependencies
npm install

# Start development server with hot reload
npm run dev

# Run tests
npm test

# Lint code
npm run lint
```

## Production Build
```bash
# Build for all platforms
npm run build

# Windows 64-bit
npm run build-win

# macOS 64-bit
npm run build-mac

# Linux 64-bit
npm run build-linux

# Create distributable packages
npm run package
```

## Post-build Scripts
```bash
# Install dependencies for production
npm run postinstall

# Create installer packages
npm run dist
```

## Development Workflow
```bash
# Start with file watching
npm run watch

# Run development server
npm run start

# Build and run in development mode
npm run dev-start
```

## Testing
```bash
# Run unit tests
npm run test

# Run integration tests
npm run test:integration

# Run end-to-end tests
npm run test:e2e

# Generate coverage report
npm run test:coverage
```

## Code Quality
```bash
# Format code
npm run format

# Check code style
npm run lint

# Fix linting issues
npm run lint:fix

# Type checking
npm run type-check
```

## Release Management
```bash
# Bump version
npm run version:patch
npm run version:minor
npm run version:major

# Create release
npm run release

# Publish to npm (if applicable)
npm run publish
```

## Environment Variables
```bash
# Development
NODE_ENV=development
DEBUG=kuku:*

# Production
NODE_ENV=production
KUKU_ENV=production

# Testing
NODE_ENV=test
KUKU_TEST=true
```

## Platform-Specific Builds

### Windows
```bash
# Create NSIS installer
npm run build:win-installer

# Create portable version
npm run build:win-portable

# Create Microsoft Store package
npm run build:win-store
```

### macOS
```bash
# Create DMG
npm run build:mac-dmg

# Create PKG for Mac App Store
npm run build:mac-pkg

# Create ZIP for distribution
npm run build:mac-zip
```

### Linux
```bash
# Create AppImage
npm run build:linux-appimage

# Create DEB package
npm run build:linux-deb

# Create RPM package
npm run build:linux-rpm
```

## Asset Optimization
```bash
# Optimize images
npm run optimize:images

# Minify CSS/JS
npm run optimize:assets

# Bundle analysis
npm run analyze:bundle

# Tree shaking
npm run optimize:deps
```

## Deployment
```bash
# Deploy to staging
npm run deploy:staging

# Deploy to production
npm run deploy:production

# Rollback deployment
npm run deploy:rollback

# Health check
npm run health:check
```

## Database Management
```bash
# Create fresh database
npm run db:create

# Run migrations
npm run db:migrate

# Seed database with sample data
npm run db:seed

# Backup database
npm run db:backup

# Restore database
npm run db:restore
```

## Monitoring & Analytics
```bash
# Start monitoring
npm run monitor:start

# Generate analytics report
npm run analytics:report

# Performance profiling
npm run profile:performance

# Memory usage analysis
npm run profile:memory
```

## Security
```bash
# Security audit
npm run security:audit

# Dependency vulnerability check
npm run security:check

# Code security scan
npm run security:scan

# Generate security report
npm run security:report
```

## Documentation
```bash
# Generate API documentation
npm run docs:api

# Generate user documentation
npm run docs:user

# Generate developer guide
npm run docs:dev

# Serve documentation locally
npm run docs:serve
```

## Clean Up
```bash
# Clean build artifacts
npm run clean

# Clean dependencies
npm run clean:deps

# Clean all generated files
npm run clean:all

# Reset to clean state
npm run reset
```

## Troubleshooting
```bash
# Check build environment
npm run check:env

# Verify dependencies
npm run check:deps

# Test database connection
npm run test:db

# Validate configuration
npm run validate:config

# Generate diagnostic report
npm run diagnose
```