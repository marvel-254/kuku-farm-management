<?php
// Simple database creation script
$db_path = __DIR__ . '/kuku_farm.sqlite';

// Remove existing database
if (file_exists($db_path)) {
    unlink($db_path);
    echo "Removed existing database\n";
}

try {
    // Create new database
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute main schema
    $schema = file_get_contents(__DIR__ . '/migrations/001_create_tables.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    // Read and execute invoices/debts migration
    if (file_exists(__DIR__ . '/migrations/002_invoices_debts.sql')) {
        $invoicesSchema = file_get_contents(__DIR__ . '/migrations/002_invoices_debts.sql');
        $invoicesStatements = array_filter(array_map('trim', explode(';', $invoicesSchema)));

        foreach ($invoicesStatements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "Invoices and debts tables created.\n";
    }
    
    echo "Database created successfully at: $db_path\n";
    echo "Tables created and default data inserted.\n";
    
} catch (Exception $e) {
    echo "Error creating database: " . $e->getMessage() . "\n";
    exit(1);
}
?>