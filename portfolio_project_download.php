<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_project_payment_storage.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$email = portfolioProjectBuyerEmail();
$projectKey = is_string($_GET['project_key'] ?? null) ? $_GET['project_key'] : '';
if ($email === '' || preg_match('/\A[a-f0-9]{64}\z/', $projectKey) !== 1) {
    http_response_code(403);
    exit('A verified sign-in and approved project payment are required.');
}
portfolioProjectPaymentEnsureTables($conn);
$statement = $conn->prepare(
    "SELECT a.stored_name, a.original_name
     FROM portfolio_project_archives a
     INNER JOIN portfolio_project_payment_requests p
       ON p.project_key = a.project_key AND p.buyer_email = ? AND p.status = 'approved'
     WHERE a.project_key = ? LIMIT 1"
);
if (!$statement) {
    throw new RuntimeException('Could not prepare the approved download check: ' . $conn->error);
}
$statement->bind_param('ss', $email, $projectKey);
$statement->execute();
$result = $statement->get_result();
$archive = $result->fetch_assoc();
$result->free();
$statement->close();
if (!$archive) {
    http_response_code(403);
    exit('No approved payment is available for this project and account.');
}
$archivePath = portfolioProjectArchivePath((string) $archive['stored_name']);
$privateRoot = realpath(portfolioProjectPrivateArchiveDirectory());
$resolvedPath = realpath($archivePath);
if ($privateRoot === false || $resolvedPath === false ||
    strpos($resolvedPath, $privateRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
    error_log('Approved project ZIP is missing or outside private storage for project ' . $projectKey);
    http_response_code(404);
    exit('The approved project ZIP is currently unavailable. Please contact the administrator.');
}
$downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo((string) $archive['original_name'], PATHINFO_FILENAME));
$downloadName = trim((string) $downloadName, '.-_');
if ($downloadName === '') {
    $downloadName = 'project-source';
}
header('Content-Type: application/zip');
header('Content-Length: ' . (string) filesize($resolvedPath));
header('Content-Disposition: attachment; filename="' . $downloadName . '.zip"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');
readfile($resolvedPath);
exit;
