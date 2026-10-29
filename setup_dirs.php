<?php
// This file ensures the sessions directory exists
$sessionPath = __DIR__ . '/../sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}

// This file ensures the logs directory exists
$logPath = __DIR__ . '/../logs';
if (!is_dir($logPath)) {
    mkdir($logPath, 0755, true);
}

echo "Directories created successfully";
?>