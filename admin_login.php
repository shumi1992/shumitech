<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'], $_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['admin_login_csrf'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (
        hash_equals('admin', $username)
        && password_verify($password, '$2y$10$fcxkAB2oi82TBYAz6o.iKOYpBUmaeQIeU8B0q8dqFO9nhWRDWeVha')
    ) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['full_name'] = 'Administrator';
        unset($_SESSION['admin_login_csrf']);

        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'The username or password is incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Source Sans 3", "Segoe UI", Arial, sans-serif;
            color: #343a40;
            background: #e9ecef;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
        }
        .login-shell { width: min(100%, 400px); }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 0 0 22px;
            color: #343a40;
            font-size: 30px;
            font-weight: 300;
            text-decoration: none;
        }
        .brand img { width: 48px; height: 48px; object-fit: contain; border-radius: 50%; }
        .brand strong { font-weight: 700; }
        .card {
            overflow: hidden;
            border: 0;
            border-radius: 5px;
            background: #fff;
            box-shadow: 0 8px 26px rgb(0 0 0 / 12%);
        }
        .card-header { padding: 24px 24px 8px; text-align: center; }
        h1 { margin: 0; font-size: 23px; font-weight: 400; }
        .subtitle { margin: 8px 0 0; color: #6c757d; font-size: 14px; }
        form { padding: 20px 24px 24px; }
        .field { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        input {
            display: block;
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            color: #495057;
            background: #fff;
            font: inherit;
        }
        input:focus { outline: 3px solid rgb(0 123 255 / 18%); border-color: #80bdff; }
        .error {
            margin: 0 0 16px;
            padding: 11px 12px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            background: #f8d7da;
            font-size: 14px;
        }
        button {
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 4px;
            color: #fff;
            background: #007bff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #0069d9; }
        .security-note {
            margin: 16px 0 0;
            color: #6c757d;
            text-align: center;
            font-size: 12px;
            line-height: 1.5;
        }
        @media (prefers-reduced-motion: no-preference) {
            .card { animation: appear .2s ease-out; }
            @keyframes appear { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <a class="brand" href="blog.php" aria-label="Return to website">
            <img src="logo.jpg" alt="">
            <span><strong>Admin</strong> Portal</span>
        </a>
        <section class="card" aria-labelledby="login-title">
            <header class="card-header">
                <h1 id="login-title">Sign in to your account</h1>
                <p class="subtitle">Enter your administrator credentials to continue</p>
            </header>
            <form method="post" action="admin_login.php">
                <?php if ($error !== ''): ?>
                    <p class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button type="submit">Sign in</button>
                <p class="security-note">The initial admin password is a default credential. Replace it before exposing this site publicly.</p>
            </form>
        </section>
    </main>
</body>
</html>
