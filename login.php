<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// --- CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Fetch school logo ---
try {
    $school_stmt = $pdo->query("SELECT logo FROM tbl_school LIMIT 1");
    $school_settings = $school_stmt->fetch();
    $logo_path = 'assets/uploads/school/' . ($school_settings['logo'] ?? 'logo.png');
} catch (PDOException $e) {
    $logo_path = 'assets/logo.png'; // Fallback
}

$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- CSRF Token Validation ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid form submission. Please try again.";
    } 
    // --- Rate Limiting Check ---
    elseif (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_login_attempt']) < 300) { // 5 attempts in 5 minutes
        $error_message = "Too many failed login attempts. Please wait 5 minutes before trying again.";
    } else {
        // Reset attempts if the last one was more than 5 minutes ago
        if (isset($_SESSION['last_login_attempt']) && (time() - $_SESSION['last_login_attempt']) >= 300) {
            unset($_SESSION['login_attempts']);
        }

        // Proceed with login
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = "Please enter both username and password.";
        } else {
            try {
                // Prepare and execute the query to find the user
                $sql = "SELECT u.user_id, u.username, u.full_name, u.hashed_password, u.role_id, r.role_name
                        FROM tbl_user u
                        JOIN tbl_role r ON u.role_id = r.role_id
                        WHERE u.username = ? AND u.is_active = 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // Verify the user and password
                if ($user && password_verify($password, $user['hashed_password'])) {
                    // --- Login Success ---
                    unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']); // Clear rate limit on success
                    session_regenerate_id(true); // Prevent session fixation

                    // Store data in session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];

                    // Redirect all logged-in users to the main dashboard (index.php)
                    if (isset($_SESSION['role_id'])) {
                        header("Location: index.php");
                    } else {
                        header("Location: logout.php"); // Fallback if no role_id
                    }
                    exit;
                } else {
                    // --- Login Failure ---
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['last_login_attempt'] = time();
                    $error_message = "Invalid username or password.";
                }
            } catch (PDOException $e) {
                error_log("Login Error: " . $e->getMessage());
                $error_message = "An error occurred. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMS - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <img class="mb-4" src="<?= htmlspecialchars($logo_path) ?>" alt="School Logo" width="72">
            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <?php if (isset($_SESSION['setup_message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['setup_message_type'] ?? 'info') ?>" role="alert">
                    <?= htmlspecialchars($_SESSION['setup_message']) ?>
                </div>
                <?php unset($_SESSION['setup_message'], $_SESSION['setup_message_type']); // Clear the message after displaying ?>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                <label for="username">Username</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary mt-3" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-muted">&copy; SAMS v1.0 - <?= date('Y') ?></p>
        </form>
    </main>
</body>
</html>