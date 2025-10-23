<?php
require_once __DIR__ . '/db.php';

try {
    // Create migrations table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `migration` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Get all migration files
    $migration_files = glob(__DIR__ . '/migrations/*.sql');

    // Get applied migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $applied_migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($migration_files as $file) {
        $migration = basename($file);

        if (!in_array($migration, $applied_migrations)) {
            // Apply migration
            $sql = file_get_contents($file);
            $pdo->exec($sql);

            // Log migration
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration]);

            echo "Applied migration: $migration\n";
        } else {
            echo "Skipped migration: $migration (already applied)\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
