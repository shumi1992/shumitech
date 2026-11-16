<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_project_payment_storage.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('admin_login.php');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
$submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
$sessionToken = is_string($_SESSION['blog_dashboard2_csrf'] ?? null) ? $_SESSION['blog_dashboard2_csrf'] : '';
if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['portfolio_project_payment_flash'] = ['error' => 'Your session expired. Refresh the dashboard and try again.'];
    redirect('admin_dashboard.php#portfolio_projects');
}

try {
    portfolioProjectPaymentEnsureTables($conn);
    $catalog = portfolioProjectPaymentCatalog($conn);
    $action = is_string($_POST['payment_action'] ?? null) ? $_POST['payment_action'] : '';
    if ($action === 'upload_archive') {
        $projectKey = is_string($_POST['project_key'] ?? null) ? $_POST['project_key'] : '';
        $upload = $_FILES['project_zip'] ?? null;
        if (!isset($catalog[$projectKey]) || preg_match('/\A[a-f0-9]{64}\z/', $projectKey) !== 1) {
            throw new RuntimeException('Choose a project from the list.');
        }
        if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('Choose a ZIP archive to upload.');
        }
        if (($upload['size'] ?? 0) <= 0 || $upload['size'] > 100 * 1024 * 1024) {
            throw new RuntimeException('The project ZIP must be smaller than 100 MB.');
        }
        $originalName = basename((string) ($upload['name'] ?? 'project.zip'));
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($upload['tmp_name']);
        $prefix = file_get_contents($upload['tmp_name'], false, null, 0, 4);
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip' ||
            !in_array($mime, ['application/zip', 'application/x-zip', 'application/octet-stream'], true) ||
            !is_string($prefix) || substr($prefix, 0, 2) !== 'PK') {
            throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
        }
        $directory = portfolioProjectPrivateArchiveDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create private project ZIP storage.');
        }
        $storedName = bin2hex(random_bytes(16)) . '.zip';
        $newPath = portfolioProjectArchivePath($storedName);
        if (!move_uploaded_file($upload['tmp_name'], $newPath)) {
            throw new RuntimeException('Could not save the project ZIP in private storage.');
        }
        $previousName = '';
        $lookup = $conn->prepare('SELECT stored_name FROM portfolio_project_archives WHERE project_key = ? LIMIT 1');
        $lookup->bind_param('s', $projectKey);
        $lookup->execute();
        $existing = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        $previousName = is_array($existing) ? (string) $existing['stored_name'] : '';
        $originalName = substr($originalName, 0, 255);
        $save = $conn->prepare(
            'INSERT INTO portfolio_project_archives (project_key, stored_name, original_name) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stored_name = VALUES(stored_name), original_name = VALUES(original_name)'
        );
        if (!$save) {
            unlink($newPath);
            throw new RuntimeException('Could not prepare the project ZIP record: ' . $conn->error);
        }
        $save->bind_param('sss', $projectKey, $storedName, $originalName);
        if (!$save->execute()) {
            $save->close();
            unlink($newPath);
            throw new RuntimeException('Could not save the project ZIP record.');
        }
        $save->close();
        if ($previousName !== '' && $previousName !== $storedName) {
            $oldPath = portfolioProjectArchivePath($previousName);
            if (is_file($oldPath) && !unlink($oldPath)) {
                error_log('Could not remove replaced project archive ' . $previousName);
            }
        }
        $_SESSION['portfolio_project_payment_flash'] = ['message' => 'Private project ZIP uploaded successfully.'];
    } elseif ($action === 'review_request') {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = is_string($_POST['decision'] ?? null) ? $_POST['decision'] : '';
        if ($requestId === false || $requestId < 1 || !in_array($decision, ['approved', 'rejected'], true)) {
            throw new RuntimeException('The payment review action is invalid.');
        }
        if ($decision === 'approved') {
            $check = $conn->prepare(
                'SELECT p.project_key FROM portfolio_project_payment_requests p
                 INNER JOIN portfolio_project_archives a ON a.project_key = p.project_key
                 WHERE p.request_id = ? AND p.status = "pending" LIMIT 1'
            );
            $check->bind_param('i', $requestId);
            $check->execute();
            $ready = $check->get_result()->num_rows === 1;
            $check->close();
            if (!$ready) {
                throw new RuntimeException('A ZIP must be uploaded before this payment can be approved.');
            }
        }
        $reviewerId = (int) $_SESSION['user_id'];
        $review = $conn->prepare(
            'UPDATE portfolio_project_payment_requests
             SET status = ?, reviewed_at = NOW(), reviewed_by = ?
             WHERE request_id = ? AND status = "pending"'
        );
        $review->bind_param('sii', $decision, $reviewerId, $requestId);
        if (!$review->execute() || $review->affected_rows !== 1) {
            $review->close();
            throw new RuntimeException('The request could not be updated; it may already have been reviewed.');
        }
        $review->close();
        $_SESSION['portfolio_project_payment_flash'] = ['message' => 'Payment request ' . $decision . '.'];
    } else {
        throw new RuntimeException('Unknown project payment action.');
    }
} catch (Throwable $exception) {
    error_log('Admin project payment action failed: ' . $exception->getMessage());
    $_SESSION['portfolio_project_payment_flash'] = ['error' => $exception->getMessage()];
}
redirect('admin_dashboard.php#portfolio_projects');
