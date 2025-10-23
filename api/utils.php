<?php
/**
 * utils.php
 *
 * Contains shared utility functions used across multiple API endpoints.
 */

/**
 * Generates the next unique number from a sequence table.
 * This function should be called within a database transaction to ensure atomicity.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $table The name of the sequence table (e.g., 'tbl_po_number').
 * @param string $year The current year.
 * @return array An associative array containing the formatted number and the sequence ID.
 * @throws Exception if the sequence is not found or columns are misnamed.
 */
function getNextNumber(PDO $pdo, string $table, string $year): array {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE year = ? AND serial = 'default' LIMIT 1 FOR UPDATE");
    $stmt->execute([$year]);
    $sequence = $stmt->fetch();
    if (!$sequence) throw new Exception("Number sequence for year {$year} not found in {$table}. Please seed the database.");

    $base_name = rtrim(str_replace('tbl_', '', $table), 's');
    $id_column = $base_name . '_id';
    $format_column = $base_name . '_format';
    if (!isset($sequence[$id_column])) throw new Exception("Could not find ID column '{$id_column}' in '{$table}'.");

    $formatted_number = str_replace(['{YYYY}', '{NNNN}'], [$year, str_pad($sequence['start_count'], 4, '0', STR_PAD_LEFT)], $sequence[$format_column] ?? '{YYYY}-{NNNN}');
    $pdo->prepare("UPDATE {$table} SET start_count = start_count + 1 WHERE {$id_column} = ?")->execute([$sequence[$id_column]]);

    return ['number' => $formatted_number, 'id' => $sequence[$id_column]];
}

/**
 * Resizes and saves an uploaded image, converting it to WEBP format.
 *
 * @param array $file The uploaded file array from $_FILES.
 * @param string $uploadDir The directory to save the file in.
 * @param string $filePrefix A prefix for the new filename (e.g., 'user_', 'consumable_').
 * @param int $maxWidth The maximum width for the resized image.
 * @param int $maxHeight The maximum height for the resized image.
 * @return string The new filename.
 * @throws Exception If the file is invalid or cannot be processed.
 */
function processAndSaveImage(array $file, string $uploadDir, string $filePrefix, int $maxWidth = 512, int $maxHeight = 512): string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('No valid uploaded file found');
    }

    // 1. Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.');
    }

    // Enable error reporting for image processing
    $oldErrorReporting = error_reporting(E_ALL);
    $oldDisplayErrors = ini_get('display_errors');
    ini_set('display_errors', '0');

    try {
        // 2. Load the image into memory
        switch ($mime_type) {
            case 'image/jpeg': 
                $source_image = @imagecreatefromjpeg($file['tmp_name']); 
                break;
            case 'image/png': 
                $source_image = @imagecreatefrompng($file['tmp_name']); 
                break;
            case 'image/webp': 
                $source_image = @imagecreatefromwebp($file['tmp_name']); 
                break;
            case 'image/gif': 
                $source_image = @imagecreatefromgif($file['tmp_name']); 
                break;
            default: 
                throw new Exception('Unsupported file type for processing.');
        }

        if (!$source_image) {
            throw new Exception('Failed to read uploaded image: ' . error_get_last()['message']);
        }

        // 3. Calculate new dimensions while maintaining aspect ratio
        $orig_w = imagesx($source_image);
        $orig_h = imagesy($source_image);

        if ($orig_w === false || $orig_h === false) {
            throw new Exception('Failed to get image dimensions');
        }

        $ratio = $orig_w / $orig_h;
        $new_w = $orig_w;
        $new_h = $orig_h;

        if ($orig_w > $maxWidth || $orig_h > $maxHeight) {
            if (($maxWidth / $maxHeight) > $ratio) {
                $new_w = $maxHeight * $ratio;
                $new_h = $maxHeight;
            } else {
                $new_h = $maxWidth / $ratio;
                $new_w = $maxWidth;
            }
        }

        // 4. Create a new image canvas and save the resized image as WEBP
        $new_image = imagecreatetruecolor($new_w, $new_h);
        if (!$new_image) {
            throw new Exception('Failed to create new image canvas');
        }

        // Enable alpha channel support
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_w, $new_h, $transparent);

        // Copy and resize the image
        if (!imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h)) {
            throw new Exception('Failed to resize image');
        }

        // Generate unique filename and save
        $new_filename = $filePrefix . time() . '_' . uniqid() . '.webp';
        if (!imagewebp($new_image, $uploadDir . $new_filename, 80)) {
            throw new Exception('Failed to save resized image as WebP');
        }

        // Clean up
        imagedestroy($source_image);
        imagedestroy($new_image);

        // Reset error reporting
        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);

        return $new_filename;
    } catch (Exception $e) {
        // Clean up on error
        if (isset($source_image) && is_resource($source_image)) {
            imagedestroy($source_image);
        }
        if (isset($new_image) && is_resource($new_image)) {
            imagedestroy($new_image);
        }

        // Reset error reporting
        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);

        throw $e;
    }
}

/**
 * Fetches common lookup data used in various UI pages.
 * This centralizes data fetching and improves code organization.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An associative array containing 'users', 'categories', 'units', and 'inventory_types'.
 */
function getUiLookupData(PDO $pdo): array {
    $data = [];
    try {
        $data['users'] = $pdo->query("SELECT user_id, full_name FROM tbl_user WHERE is_active = 1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
        $data['categories'] = $pdo->query("SELECT category_id, category_name, inventory_type_id FROM tbl_category ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
        $data['units'] = $pdo->query("SELECT unit_id, unit_name FROM tbl_unit ORDER BY unit_name")->fetchAll(PDO::FETCH_ASSOC);
        $data['inventory_types'] = $pdo->query("SELECT inventory_type_id, inventory_type_name FROM tbl_inventory_type")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // In a real application, you might want to handle this more gracefully
        error_log("Failed to get UI lookup data: " . $e->getMessage());
        return ['users' => [], 'categories' => [], 'units' => [], 'inventory_types' => []];
    }
    return $data;
}