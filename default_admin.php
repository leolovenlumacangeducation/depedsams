<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * SAMS - Default Admin Creation Script
 *
 * This script checks for and creates a default administrator user if one does not already exist.
 * It can be run from a web browser for initial setup.
 *
 * To run: visit /default_admin.php in your browser.
 */

require_once 'db.php';

// --- Admin User Details ---

$fullName = 'Default Admin';
$username = 'admin';
$password = 'admin';
$preferredRoleId = 1; // preferred role id (commonly 1)

// Determine force flag (web: ?force=1 and optional token, CLI: --force)
$force = false;
$forceTokenRequired = false; // set to true if you want an extra safety token for web
$webForceToken = 'RECREATE_ADMIN'; // change if you want a secret token
if (php_sapi_name() === 'cli') {
    // Check CLI args
    global $argv;
    if (!empty($argv)) {
        foreach ($argv as $arg) {
            if ($arg === '--force' || $arg === '-f') $force = true;
        }
    }
} else {
    // Web: check query param
    if (isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true')) {
        if ($forceTokenRequired) {
            if (isset($_GET['token']) && $_GET['token'] === $webForceToken) $force = true;
        } else {
            $force = true;
        }
    }
}

$message = '';
$message_type = 'info'; // Can be 'success', 'danger', 'info'

try {
    // Start a transaction for safety
    $pdo->beginTransaction();

    // Ensure the role exists. Try preferred id first, then by name 'Admin', otherwise create it.
    $roleId = null;
    $stmt = $pdo->prepare("SELECT role_id FROM tbl_role WHERE role_id = ?");
    $stmt->execute([$preferredRoleId]);
    $r = $stmt->fetch();
    if ($r) {
        $roleId = $r['role_id'];
    } else {
        // Look up by name
        $stmt = $pdo->prepare("SELECT role_id FROM tbl_role WHERE role_name = ?");
        $stmt->execute(['Admin']);
        $r = $stmt->fetch();
        if ($r) {
            $roleId = $r['role_id'];
        } else {
            // Create the Admin role
            $stmt = $pdo->prepare("INSERT INTO tbl_role (role_name) VALUES (?)");
            $stmt->execute(['Admin']);
            $roleId = $pdo->lastInsertId();
        }
    }

    // 1. Check if the 'admin' user already exists
    $stmt = $pdo->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch() && !$force) {
        $message = "Admin user '{$username}' already exists. No action taken. Use ?force=1 or --force to recreate.";
        $message_type = 'info';
        $pdo->rollBack();
    } else {
        if ($force) {
            // Delete any existing admin user(s) with this username and ensure ID 1 is free
            $del = $pdo->prepare("DELETE FROM tbl_user WHERE username = ? OR user_id = 1");
            $del->execute([$username]);
        }
        // 2. Hash the password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert the new admin user with ID 1 and ensured role id
        $sql = "INSERT INTO tbl_user (user_id, full_name, username, hashed_password, role_id, is_active) VALUES (1, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullName, $username, $hashedPassword, $roleId, 1]);

        $message = "SUCCESS: Default admin user '{$username}' created. You can now log in with password 'admin'.";
        $message_type = 'success';
        $pdo->commit();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "ERROR: Database operation failed: " . $e->getMessage();
    $message_type = 'danger';
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "ERROR: An unexpected error occurred: " . $e->getMessage();
    $message_type = 'danger';
}
 
// Store the message in the session and redirect to the login page
$_SESSION['setup_message'] = $message;
$_SESSION['setup_message_type'] = $message_type;
header("Location: login.php");
exit;
?>