<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_contact_storage.php';

function portfolioContactJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    portfolioContactJson(405, ['error' => 'Only POST requests are supported.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrfToken) || !isset($_SESSION['social_feed_csrf']) ||
    !is_string($_SESSION['social_feed_csrf']) ||
    !hash_equals($_SESSION['social_feed_csrf'], $csrfToken)) {
    portfolioContactJson(403, ['error' => 'Your page session expired. Refresh the page and try again.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    portfolioContactJson(400, ['error' => 'The request body must be valid JSON.']);
}

$honeypot = $input['company'] ?? '';
if (!is_string($honeypot) || $honeypot !== '') {
    portfolioContactJson(400, ['error' => 'The message could not be accepted.']);
}

$name = isset($input['name']) && is_string($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) && is_string($input['email']) ? strtolower(trim($input['email'])) : '';
$subject = isset($input['subject']) && is_string($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) && is_string($input['message']) ? trim($input['message']) : '';

if ($name === '' || strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
    portfolioContactJson(400, ['error' => 'Enter a valid name up to 120 characters.']);
}
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    portfolioContactJson(400, ['error' => 'Enter a valid email address.']);
}
if ($subject === '' || strlen($subject) > 160 || preg_match('/[\r\n]/', $subject)) {
    portfolioContactJson(400, ['error' => 'Enter a subject under 160 characters without line breaks.']);
}
if ($message === '' || strlen($message) > 5000) {
    portfolioContactJson(400, ['error' => 'Enter a message under 5,000 characters.']);
}

$lastAttemptAt = (int) ($_SESSION['portfolio_contact_last_attempt'] ?? 0);
if (time() - $lastAttemptAt < 15) {
    portfolioContactJson(429, ['error' => 'Please wait a few seconds before sending another message.']);
}

try {
    portfolioEnsureContactInboxTable($conn);
    $statement = $conn->prepare(
        'INSERT INTO portfolio_contact_messages (sender_name, sender_email, subject, message) VALUES (?, ?, ?, ?)'
    );
    if (!$statement) {
        throw new RuntimeException('Could not prepare portfolio contact message storage: ' . $conn->error);
    }
    if (!$statement->bind_param('ssss', $name, $email, $subject, $message) || !$statement->execute()) {
        $statementError = $statement->error;
        $statement->close();
        throw new RuntimeException('Could not save portfolio contact message: ' . $statementError);
    }
    $statement->close();
} catch (Throwable $exception) {
    error_log('Portfolio contact inbox write failed: ' . $exception->getMessage());
    portfolioContactJson(500, ['error' => 'Your message could not be saved. Please try again later.']);
}
$_SESSION['portfolio_contact_last_attempt'] = time();

portfolioContactJson(200, ['sent' => true]);
