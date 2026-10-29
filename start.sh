#!/bin/bash

# KUKU Farm Management System - Startup Script (Enhanced)

# Default configuration
DEFAULT_PORT=4567
DEFAULT_HOST="localhost"

# Allow override from environment variables
PORT=${PORT:-$DEFAULT_PORT}
HOST=${HOST:-$DEFAULT_HOST}

# Function to validate port number
validate_port() {
    local port=$1
    if ! [[ "$port" =~ ^[0-9]+$ ]] || [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
        echo "ERROR: Invalid port number: $port"
        echo "   Port must be between 1 and 65535"
        exit 1
    fi
}

# Allow override from environment variables
PORT=${PORT:-$DEFAULT_PORT}
HOST=${HOST:-$DEFAULT_HOST}

# Validate port number
validate_port $PORT

echo "Starting KUKU Farm Management System..."
echo "=========================================="
echo "Configuration:"
echo "  Host: $HOST"
echo "  Port: $PORT"
echo "=========================================="

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    echo "   On Ubuntu/Debian: sudo apt install php php-sqlite3 php-pdo"
    echo "   On CentOS/RHEL: sudo yum install php php-sqlite3 php-pdo"
    exit 1
fi

echo "PHP found: $(php --version | head -n1)"

# Check required PHP extensions
echo "Checking PHP extensions..."

extensions=("sqlite3" "pdo" "pdo_sqlite")
missing_extensions=()

for ext in "${extensions[@]}"; do
    if php -m | grep -q "$ext"; then
        echo "$ext extension found"
    else
        echo "$ext extension missing"
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -gt 0 ]; then
    echo ""
    echo "Missing PHP extensions: ${missing_extensions[*]}"
    echo "   Please install missing extensions and try again."
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "app/config/config.php" ]; then
    echo "Not in KUKU system directory. Please navigate to kuku folder first."
    exit 1
fi

echo "KUKU system files found"

# Create necessary directories if they don't exist
echo "Creating necessary directories..."
mkdir -p sessions logs database

# Set up database if it doesn't exist
if [ ! -f "database/kuku_farm.sqlite" ]; then
    echo "Setting up database..."
    php database/create_db.php
    if [ $? -eq 0 ]; then
        echo "Database setup completed"
    else
        echo "Database setup failed"
        exit 1
    fi
else
    echo "Database already exists"
fi

# Set proper permissions
echo "Setting file permissions..."
chmod 755 sessions logs database
find . -name "*.php" -exec chmod 644 {} \;
find . -name "*.sh" -exec chmod 755 {} \;

echo "Permissions set"

# Function to check and kill processes on a given port
check_and_kill_port() {
    local port=$1
    local service_name=$2
    
    echo "Checking for services on port $port..."
    
    # Find processes using the port
    local pids=$(lsof -ti :$port -sTCP:LISTEN 2>/dev/null)
    
    if [ -n "$pids" ]; then
        echo "WARNING: Port $port is in use by the following processes:"
        
        # Show details of processes using the port
        lsof -i :$port -sTCP:LISTEN 2>/dev/null | while read line; do
            echo "   $line"
        done
        
        echo ""
        echo "Attempting to terminate existing processes..."
        
        # Try to kill processes gracefully first
        for pid in $pids; do
            local process_name=$(ps -p $pid -o comm= 2>/dev/null)
            echo "   Stopping process $pid ($process_name)..."
            
            # Try graceful termination first
            kill $pid 2>/dev/null
            
            # Wait a moment
            sleep 1
            
            # Check if process is still running
            if kill -0 $pid 2>/dev/null; then
                echo "   Force killing process $pid..."
                kill -9 $pid 2>/dev/null
            fi
        done
        
        # Wait for processes to fully terminate
        sleep 2
        
        # Check again if port is still in use
        local remaining_pids=$(lsof -ti :$port -sTCP:LISTEN 2>/dev/null)
        if [ -n "$remaining_pids" ]; then
            echo "ERROR: Failed to clear port $port. Processes still running:"
            lsof -i :$port -sTCP:LISTEN 2>/dev/null | while read line; do
                echo "   $line"
            done
            echo ""
            echo "Please manually stop these processes and try again."
            echo "You can use: sudo kill -9 $remaining_pids"
            exit 1
        else
            echo "Successfully cleared port $port"
        fi
    else
        echo "Port $port is available"
    fi
}

# Check and clear the configured port
check_and_kill_port $PORT "KUKU Farm Management System"

echo ""
echo "Starting KUKU Farm Management System..."
echo "=========================================="
echo "Server: http://$HOST:$PORT"
echo "Username: admin"
echo "Password: admin123"
echo "Press Ctrl+C to stop the server"
echo "=========================================="
echo ""

# Start the PHP development server with proper document root
cd public
php -S $HOST:$PORT -t .

echo ""
echo "Server stopped"
echo "You can view logs in the logs/ directory"
echo "To restart, run: ./start.sh"