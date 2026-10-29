<?php
// Create necessary directories
$dirs = [
    __DIR__ . '/sessions',
    __DIR__ . '/logs'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Create database
echo "Creating database...\n";
require_once __DIR__ . '/database/create_db.php';

echo "Setup complete!\n";
?>