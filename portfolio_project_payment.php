<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_project_payment_storage.php';
require_once __DIR__ . '/portfolio_subscription_mailer.php';

header('Cache-Control: no-store, private');
portfolioProjectPaymentEnsureTables($conn);
$catalog = portfolioProjectPaymentCatalog($conn);
$projectKey = is_string($_REQUEST['project_key'] ?? null) ? $_REQUEST['project_key'] : '';
if (preg_match('/\A[a-f0-9]{64}\z/', $projectKey) !== 1 ||
    !isset($catalog[$projectKey]) || !$catalog[$projectKey]['visible']) {
    http_response_code(404);
    exit('Project not found.');
}
$project = $catalog[$projectKey];
$csrfToken = portfolioProjectPaymentCsrfToken();
$message = '';
$error = '';
if (isset($_GET['signout'])) {
    unset(
        $_SESSION['portfolio_project_buyer_email'],
        $_SESSION['portfolio_project_buyer_auth_at'],
        $_SESSION['portfolio_project_email_code'],
        $_SESSION['portfolio_project_email_code_sent_at']
    );
    header('Location: portfolio_project_payment.php?project_key=' . rawurlencode($projectKey), true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (isset($_POST['send_email_code'])) {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $lastSent = (int) ($_SESSION['portfolio_project_email_code_sent_at'] ?? 0);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (time() - $lastSent < 60) {
            $error = 'Please wait one minute before requesting another sign-in code.';
        } else {
            $code = (string) random_int(100000, 999999);
            $subject = 'Your project purchase sign-in code';
            $body = "Your project purchase sign-in code is {$code}. It expires in 10 minutes.";
            $mailIdentity = portfolioMailIdentity($conn);
            $sender = $mailIdentity['email'];
            if (!portfolioSendEmail($email, $subject, $body, $sender)) {
                $error = 'The sign-in email could not be delivered. Configure Gmail SMTP with a Google App Password on the server, or use Google/GitHub sign-in below.';
            } else {
                $_SESSION['portfolio_project_email_code'] = [
                    'email' => $email,
                    'hash' => password_hash($code, PASSWORD_DEFAULT),
                    'expires_at' => time() + 600,
                    'attempts' => 0,
                ];
                $_SESSION['portfolio_project_email_code_sent_at'] = time();
                $message = 'A sign-in code was sent to your email. Check your inbox and spam folder.';
            }
        }
    } elseif (isset($_POST['verify_email_code'])) {
        $challenge = $_SESSION['portfolio_project_email_code'] ?? null;
        $code = trim((string) ($_POST['email_code'] ?? ''));
        if (!is_array($challenge) || !isset($challenge['email'], $challenge['hash'], $challenge['expires_at'], $challenge['attempts']) ||
            !is_string($challenge['email']) || !is_string($challenge['hash']) ||
            time() > (int) $challenge['expires_at'] || (int) $challenge['attempts'] >= 5) {
            unset($_SESSION['portfolio_project_email_code']);
            $error = 'The sign-in code expired or has too many attempts. Request a new code.';
        } elseif (preg_match('/\A\d{6}\z/', $code) !== 1 || !password_verify($code, $challenge['hash'])) {
            $_SESSION['portfolio_project_email_code']['attempts'] = (int) $challenge['attempts'] + 1;
            $error = 'That sign-in code is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['portfolio_project_buyer_email'] = strtolower($challenge['email']);
            $_SESSION['portfolio_project_buyer_auth_at'] = time();
            unset($_SESSION['portfolio_project_email_code']);
            header('Location: portfolio_project_payment.php?project_key=' . rawurlencode($projectKey), true, 303);
            exit;
        }
    } elseif (isset($_POST['submit_payment'])) {
        $email = portfolioProjectBuyerEmail();
        $amount = filter_var($_POST['amount_birr'] ?? null, FILTER_VALIDATE_INT);
        $method = is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : '';
        $reference = trim((string) ($_POST['payment_reference'] ?? ''));
        $allowedMethods = ['telebirr', 'awash_pro', 'cbe_mobile'];
        if ($email === '') {
            $error = 'Sign in with a verified email before submitting a payment.';
        } elseif ($amount === false || $amount !== $project['price_birr']) {
            $error = 'Enter the exact project price shown above as the amount you paid.';
        } elseif (!in_array($method, $allowedMethods, true)) {
            $error = 'Choose Telebirr, Awash Pro, or CBE Mobile Bank.';
        } elseif (preg_match('/\A[A-Za-z0-9._\/-]{4,120}\z/', $reference) !== 1) {
            $error = 'Enter a valid payment reference (4–120 letters, numbers, dots, underscores, slashes, or hyphens).';
        } else {
            $archiveStatement = $conn->prepare('SELECT stored_name FROM portfolio_project_archives WHERE project_key = ? LIMIT 1');
            $archiveStatement->bind_param('s', $projectKey);
            $archiveStatement->execute();
            $archiveResult = $archiveStatement->get_result();
            $archiveReady = $archiveResult && $archiveResult->num_rows === 1;
            if ($archiveResult) {
                $archiveResult->free();
            }
            $archiveStatement->close();
            if (!$archiveReady) {
                $error = 'The ZIP for this project is not available yet. Please check back later.';
            } else {
                $insert = $conn->prepare(
                    "INSERT INTO portfolio_project_payment_requests
                    (project_key, project_title, buyer_email, amount_birr, payment_method, payment_reference)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );
                if (!$insert) {
                    throw new RuntimeException('Could not prepare the payment request: ' . $conn->error);
                }
                $insert->bind_param('sssiss', $projectKey, $project['title'], $email, $amount, $method, $reference);
                if ($insert->execute()) {
                    $message = 'Payment reference submitted. Your request is pending administrator verification.';
                } elseif ($insert->errno === 1062) {
                    $error = 'That payment reference has already been submitted. Check the reference or contact the administrator.';
                } else {
                    throw new RuntimeException('Could not save your payment request: ' . $insert->error);
                }
                $insert->close();
            }
        }
    }
}

$buyerEmail = portfolioProjectBuyerEmail();
$buyerRequests = [];
if ($buyerEmail !== '') {
    $requests = $conn->prepare(
        'SELECT amount_birr, payment_method, payment_reference, status, submitted_at
         FROM portfolio_project_payment_requests WHERE buyer_email = ? AND project_key = ?
         ORDER BY submitted_at DESC LIMIT 10'
    );
    $requests->bind_param('ss', $buyerEmail, $projectKey);
    $requests->execute();
    $result = $requests->get_result();
    while ($row = $result->fetch_assoc()) {
        $buyerRequests[] = $row;
    }
    $result->free();
    $requests->close();
}
$providers = portfolioOAuthProviders();
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Project payment · <?= $escape($project['title']) ?></title>
<style>
body{margin:0;padding:24px;background:#f3f5f9;color:#172033;font:16px/1.55 system-ui,sans-serif}.shell{max-width:760px;margin:5vh auto;padding:28px;background:#fff;border-radius:16px;box-shadow:0 12px 40px #17203318}h1{margin-top:0}.notice{padding:12px 14px;border-radius:8px;background:#eef8f1;color:#17632d}.error{background:#fff0f0;color:#9d2020}label{display:block;margin:14px 0 6px;font-weight:650}input,select{width:100%;box-sizing:border-box;min-height:42px;padding:8px 10px;border:1px solid #cbd2df;border-radius:7px;font:inherit}button,.button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:8px 14px;border:0;border-radius:7px;background:#1468d4;color:#fff;text-decoration:none;font:inherit;font-weight:650;cursor:pointer}.methods{padding:14px;border:1px solid #e1e5ed;border-radius:10px;background:#f8faff}.methods p{margin:5px 0}.providers{display:flex;flex-wrap:wrap;gap:10px;margin:12px 0}.history{width:100%;border-collapse:collapse}.history th,.history td{padding:9px;border-bottom:1px solid #e2e6ee;text-align:left;overflow-wrap:anywhere}.history th{background:#f5f7fa}@media(max-width:600px){body{padding:12px}.shell{padding:18px}.history{font-size:13px}}
</style>
</head>
<body><main class="shell">
<p><a href="portfolio.php#work">← Back to projects</a></p>
<h1>Request source ZIP: <?= $escape($project['title']) ?></h1>
<p>Price: <strong><?= $escape($project['price_birr']) ?> Birr</strong>. Transfer manually, submit the paid amount and reference, and wait for administrator approval. No payment is processed automatically.</p>
<?php if ($message !== ''): ?><p class="notice" role="status"><?= $escape($message) ?></p><?php endif; ?>
<?php if ($error !== ''): ?><p class="notice error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<?php if ($buyerEmail === ''): ?>
<h2>Sign in with verified email</h2>
<p>Use an email code or a verified Google/GitHub account. We use this email to associate your request and approved download.</p>
<form method="post">
<input type="hidden" name="project_key" value="<?= $escape($projectKey) ?>"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
<label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" required>
<button type="submit" name="send_email_code">Email me a sign-in code</button>
</form>
<?php if (isset($_SESSION['portfolio_project_email_code'])): ?>
<form method="post"><input type="hidden" name="project_key" value="<?= $escape($projectKey) ?>"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
<p>Code sent to <strong><?= $escape($_SESSION['portfolio_project_email_code']['email'] ?? '') ?></strong>.</p>
<label for="email_code">6-digit email code</label><input id="email_code" name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required><button type="submit" name="verify_email_code">Verify code</button>
</form>
<?php endif; ?>
<div class="providers"><a class="button" href="portfolio_subscription_api.php?oauth=google&amp;purchase_project_key=<?= rawurlencode($projectKey) ?>">Continue with Google</a><a class="button" href="portfolio_subscription_api.php?oauth=github&amp;purchase_project_key=<?= rawurlencode($projectKey) ?>">Continue with GitHub</a></div>
<?php else: ?>
<p>Signed in as <strong><?= $escape($buyerEmail) ?></strong>
<?php if (!empty($_SESSION['portfolio_project_buyer_email'])): ?> · <a href="portfolio_project_payment.php?project_key=<?= rawurlencode($projectKey) ?>&amp;signout=1">Sign out from purchase session</a><?php endif; ?>
</p>
<section class="methods"><h2>Transfer destination</h2><p><strong>Telebirr:</strong> 0933 973472</p><p><strong>Awash Pro:</strong> 013201377513400</p><p><strong>CBE Mobile Bank:</strong> 1000586813181</p><p>After transferring the project price, submit your payment reference below.</p></section>
<form method="post"><input type="hidden" name="project_key" value="<?= $escape($projectKey) ?>"><input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
<label for="amount_birr">Amount paid (Birr)</label><input id="amount_birr" name="amount_birr" type="number" min="0" max="1000000" step="1" value="<?= $escape($project['price_birr']) ?>" required>
<label for="payment_method">Payment method</label><select id="payment_method" name="payment_method" required><option value="">Choose a method</option><option value="telebirr">Telebirr</option><option value="awash_pro">Awash Pro</option><option value="cbe_mobile">CBE Mobile Bank</option></select>
<label for="payment_reference">Payment reference number</label><input id="payment_reference" name="payment_reference" type="text" minlength="4" maxlength="120" pattern="[A-Za-z0-9._/-]+" required>
<p><button type="submit" name="submit_payment">Submit for review</button></p></form>
<?php if ($buyerRequests !== []): ?><h2>Your payment requests</h2><table class="history"><thead><tr><th>Reference</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach ($buyerRequests as $request): ?><tr><td><?= $escape($request['payment_reference']) ?></td><td><?= $escape($request['amount_birr']) ?> Birr</td><td><?= $escape(ucfirst($request['status'])) ?></td></tr><?php endforeach; ?></tbody></table>
<?php foreach ($buyerRequests as $request): if ($request['status'] === 'approved'): ?><p><a class="button" href="portfolio_project_download.php?project_key=<?= rawurlencode($projectKey) ?>">Download approved ZIP</a></p><?php break; endif; endforeach; ?>
<?php endif; ?>
<?php endif; ?>
</main></body></html>
