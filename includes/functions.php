<?php
// functions.php

/**
 * Generates the next sequential number for a given document type.
 *
 * @param PDO $pdo The PDO database connection.
 * @param string $table_name The name of the number sequence table (e.g., 'tbl_po_number').
 * @param string $serial_type The serial type (e.g., 'PO', 'RIS', 'IIRUP').
 * @param ?string $format_column The specific name of the format column (e.g., 'po_number_format'). Optional.
 * @return array An associative array containing 'next_number' and 'current_count'.
 * @throws Exception If the number sequence table or format is not found.
 */
function generateNextNumber(PDO $pdo, string $table_name, string $serial_type, ?string $format_column = null): array
{
    $current_year = date('Y');
    $serial = 'default'; // Assuming 'default' serial for now

    $stmt = $pdo->prepare("SELECT * FROM {$table_name} WHERE serial = ? AND year = ?");
    $stmt->execute([$serial, $current_year]);
    $number_sequence = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$number_sequence) {
        throw new Exception("Number sequence for {$serial_type} in {$current_year} not found.");
    }

    $current_count = $number_sequence['start_count'];
    $format = $number_sequence[$format_column];

    // If format column is not specified, use a default format based on serial type
    if (!$format) {
        $format = $serial_type . '-{YYYY}-{NNNN}';
    }

    // Replace placeholders in the format string
    $next_number = str_replace('{YYYY}', $current_year, $format);
    $next_number = str_replace('{NNNN}', sprintf('%04d', $current_count), $next_number); // Assuming 4-digit padding

    return [
        'next_number' => $next_number,
        'current_count' => $current_count
    ];
}
?>