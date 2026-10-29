#!/bin/bash

# KUKU Farm Manager Pro - Build Script

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="KUKU Farm Manager Pro"
VERSION=$(node -p "require('./package.json').version")
BUILD_DIR="dist"
PLATFORMS="win mac linux"

echo -e "${BLUE}Building ${APP_NAME} v${VERSION}...${NC}"

# Clean previous build
echo -e "${YELLOW}Cleaning previous build...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Install dependencies
echo -e "${YELLOW}Installing dependencies...${NC}"
npm ci --silent

# Run tests
echo -e "${YELLOW}Running tests...${NC}"
npm test --silent

# Build for each platform
for platform in $PLATFORMS; do
    echo -e "${BLUE}Building for ${platform}...${NC}"
    
    case $platform in
        "win")
            npm run build-win --silent
            ;;
        "mac")
            npm run build-mac --silent
            ;;
        "linux")
            npm run build-linux --silent
            ;;
    esac
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Build successful for ${platform}${NC}"
    else
        echo -e "${RED}✗ Build failed for ${platform}${NC}"
        exit 1
    fi
done

# Create installer packages
echo -e "${YELLOW}Creating installer packages...${NC}"

# Windows installer
if [ -d "$BUILD_DIR/win" ]; then
    echo -e "${BLUE}Creating Windows installer...${NC}"
    cd "$BUILD_DIR/win"
    electron-builder --win --x64 --config.nsis.license=LICENSE.txt --config.nsis.allowToChangeInstallationDirectory=true
fi

# macOS installer
if [ -d "$BUILD_DIR/mac" ]; then
    echo -e "${BLUE}Creating macOS installer...${NC}"
    cd "$BUILD_DIR/mac"
    electron-builder --mac --x64 --config.mac.identity="com.kuku.farmmanager.pro"
fi

# Linux packages
if [ -d "$BUILD_DIR/linux" ]; then
    echo -e "${BLUE}Creating Linux packages...${NC}"
    cd "$BUILD_DIR/linux"
    electron-builder --linux --x64 AppImage deb rpm
fi

# Generate checksums
echo -e "${YELLOW}Generating checksums...${NC}"
cd "$BUILD_DIR"
find . -name "*.exe" -o -name "*.dmg" -o -name "*.AppImage" -o -name "*.deb" -o -name "*.rpm" | while read file; do
    if [ -f "$file" ]; then
        sha256sum "$file" > "$file.sha256"
    fi
done

# Create release notes
echo -e "${YELLOW}Creating release notes...${NC}"
cat > "$BUILD_DIR/RELEASE_NOTES.md" << EOF
# KUKU Farm Manager Pro v${VERSION}

## 🚀 New Features
- Enhanced desktop application with modern UI
- Advanced farm management capabilities
- Real-time analytics and reporting
- Cross-platform support (Windows, macOS, Linux)

## 🔧 Improvements
- Performance optimizations
- Enhanced security features
- Improved user experience
- Bug fixes and stability improvements

## 📦 System Requirements
- Windows 10+ / macOS 10.14+ / Ubuntu 18.04+
- 4GB RAM minimum (8GB recommended)
- 500MB free disk space

## 🚀 Installation
- Windows: Run the .exe installer
- macOS: Open the .dmg file and drag to Applications
- Linux: Run the AppImage or install the .deb package

## 📞 Support
- Email: support@kukufarm.com
- Phone: +1-800-KUKU-FARM
- Website: https://kukufarm.com

---

## 📋 Build Information
- Build Date: $(date)
- Node.js: $(node --version)
- Electron: $(npm list electron --depth=0 | grep electron | cut -d' ' -f2)
- Platform: $(uname -s)
EOF

# Build summary
echo -e "${GREEN}✓ Build completed successfully!${NC}"
echo -e "${BLUE}Build artifacts are in: $BUILD_DIR${NC}"
echo -e "${YELLOW}Release notes: $BUILD_DIR/RELEASE_NOTES.md${NC}"

# Show build statistics
echo -e "${BLUE}Build Statistics:${NC}"
echo -e "Total files: $(find $BUILD_DIR -type f | wc -l)"
echo -e "Total size: $(du -sh $BUILD_DIR | cut -f1)"

# Success message
echo -e "${GREEN}🎉 ${APP_NAME} v${VERSION} is ready for distribution!${NC}"