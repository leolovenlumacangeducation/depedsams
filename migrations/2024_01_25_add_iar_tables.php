<?php
// Database settings
$host = 'localhost';
$dbname = 'samspikpok_db';
$username = 'root';
$password = 'root';

// Create connection
$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the SQL content
$sql = file_get_contents(__DIR__ . '/2024_01_25_add_iar_tables.sql');

try {
    // Begin transaction
    $mysqli->begin_transaction();
    
    // Execute the SQL statements
    if (!$mysqli->multi_query($sql)) {
        throw new Exception($mysqli->error);
    }

    // Handle all result sets
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    // Record the migration
    $stmt = $mysqli->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->bind_param("s", "2024_01_25_add_iar_tables");
    $stmt->execute();
    
    // Commit transaction
    $mysqli->commit();
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    // Roll back transaction on error
    $mysqli->rollback();
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $mysqli->close();
}