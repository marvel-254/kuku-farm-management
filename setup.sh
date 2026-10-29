#!/bin/bash

# KUKU Farm Management - Desktop App Setup Script
# This script helps set up the desktop application

echo "Setting up KUKU Farm Management Desktop Application..."
echo "=================================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    echo "   Download from: https://nodejs.org/"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ Node.js and npm are installed"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully"
else
    echo "❌ Failed to install dependencies"
    exit 1
fi

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p assets/icons
mkdir -p dist

# Check if database file exists, if not create it
if [ ! -f "database/kuku_farm.db" ]; then
    echo "🗄️  Setting up database..."
    php database/create_db.php
    if [ $? -eq 0 ]; then
        echo "✅ Database created successfully"
    else
        echo "❌ Failed to create database"
        exit 1
    fi
else
    echo "✅ Database already exists"
fi

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "To run the application:"
echo "  npm start              # Development mode"
echo "  npm run build          # Build for current platform"
echo "  npm run build-win      # Build for Windows"
echo "  npm run build-mac      # Build for macOS"
echo "  npm run build-linux    # Build for Linux"
echo ""
echo "📁 Application files are located in the 'public' directory"
echo "🗄️  Database file: database/kuku_farm.db"
echo "📦 Built applications will be in the 'dist' directory"