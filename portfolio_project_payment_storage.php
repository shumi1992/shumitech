<?php
declare(strict_types=1);

function portfolioProjectPaymentEnsureTables(mysqli $connection): void
{
    $settingsSql = "CREATE TABLE IF NOT EXISTS system_settings (
        setting_id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $archiveSql = "CREATE TABLE IF NOT EXISTS portfolio_project_archives (
        project_key CHAR(64) NOT NULL PRIMARY KEY,
        stored_name CHAR(36) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $paymentSql = "CREATE TABLE IF NOT EXISTS portfolio_project_payment_requests (
        request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        project_key CHAR(64) NOT NULL,
        project_title VARCHAR(160) NOT NULL,
        buyer_email VARCHAR(254) NOT NULL,
        amount_birr INT UNSIGNED NOT NULL,
        payment_method VARCHAR(24) NOT NULL,
        payment_reference VARCHAR(120) NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL,
        reviewed_by BIGINT UNSIGNED NULL,
        UNIQUE KEY uq_portfolio_payment_reference (payment_method, payment_reference),
        KEY idx_portfolio_payment_review (status, submitted_at),
        KEY idx_portfolio_payment_buyer (buyer_email(100), project_key, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$connection->query($settingsSql) || !$connection->query($archiveSql) || !$connection->query($paymentSql)) {
        throw new RuntimeException('Could not initialize project payment storage: ' . $connection->error);
    }
}

function portfolioProjectPaymentKey(string $category, string $title, ?string $projectId = null): string
{
    return portfolioProjectPaymentKeyForId(
        $projectId !== null && $projectId !== ''
            ? $projectId
            : portfolioProjectPaymentLegacyId($category, $title)
    );
}

function portfolioProjectPaymentLegacyId(string $category, string $title): string
{
    return 'legacy-' . substr(
        hash('sha256', strtolower(trim($category)) . "\0" . strtolower(trim($title))),
        0,
        32
    );
}

function portfolioProjectPaymentKeyForId(string $projectId): string
{
    if (preg_match('/\A[a-z0-9][a-z0-9-]{0,59}\z/', $projectId) !== 1) {
        throw new RuntimeException('The project identifier is invalid.');
    }
    return hash('sha256', 'project-id:' . $projectId);
}

function portfolioProjectPaymentCatalog(mysqli $connection): array
{
    $result = $connection->query(
        "SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_projects' LIMIT 1"
    );
    if (!$result) {
        throw new RuntimeException('Could not load projects for ZIP payments: ' . $connection->error);
    }
    $row = $result->fetch_assoc();
    $result->free();
    $decoded = json_decode((string) ($row['setting_value'] ?? ''), true);
    if (!$row) {
        $decoded = ['projects' => [
            ['category' => 'portfolio', 'title' => 'Personal Portfolio', 'price_birr' => 100],
            ['category' => 'platform', 'title' => 'Web Platform', 'price_birr' => 100],
            ['category' => 'application', 'title' => 'Web Application', 'price_birr' => 100],
        ]];
    } elseif (!is_array($decoded) || !is_array($decoded['projects'] ?? null)) {
        throw new RuntimeException('Stored portfolio projects are not valid JSON.');
    }

    $catalog = [];
    foreach ($decoded['projects'] as $project) {
        if (!is_array($project) || !isset($project['category'], $project['title'], $project['price_birr']) ||
            !is_string($project['category']) || !in_array($project['category'], ['portfolio', 'platform', 'application'], true) ||
            !is_string($project['title']) || trim($project['title']) === '' ||
            filter_var($project['price_birr'], FILTER_VALIDATE_INT) === false ||
            (int) $project['price_birr'] < 0 ||
            (isset($project['visible']) && !is_bool($project['visible']))) {
            continue;
        }
        $projectId = isset($project['id']) && is_string($project['id']) &&
            preg_match('/\A[a-z0-9][a-z0-9-]{0,59}\z/', $project['id']) === 1
            ? $project['id']
            : portfolioProjectPaymentLegacyId($project['category'], $project['title']);
        $key = portfolioProjectPaymentKey($project['category'], $project['title'], $projectId);
        if (isset($catalog[$key])) {
            throw new RuntimeException('Projects need unique IDs before ZIP payments can be managed.');
        }
        $catalog[$key] = [
            'key' => $key,
            'id' => $projectId,
            'title' => trim($project['title']),
            'price_birr' => (int) $project['price_birr'],
            'visible' => !isset($project['visible']) || $project['visible'] === true,
        ];
    }
    return $catalog;
}

function portfolioProjectPrivateArchiveDirectory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'portfolio_private_project_zips';
}

function portfolioProjectArchivePath(string $storedName): string
{
    if (preg_match('/\A[a-f0-9]{32}\.zip\z/', $storedName) !== 1) {
        throw new RuntimeException('The stored project archive name is invalid.');
    }
    return portfolioProjectPrivateArchiveDirectory() . DIRECTORY_SEPARATOR . $storedName;
}

function portfolioProjectBuyerEmail(): string
{
    $buyerEmail = $_SESSION['portfolio_project_buyer_email'] ?? '';
    if (is_string($buyerEmail) && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        return strtolower(trim($buyerEmail));
    }
    if (isset($_SESSION['user_id'], $_SESSION['email']) && filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
        return strtolower(trim((string) $_SESSION['email']));
    }
    return '';
}

function portfolioProjectPaymentCsrfToken(): string
{
    if (empty($_SESSION['portfolio_project_payment_csrf']) ||
        !is_string($_SESSION['portfolio_project_payment_csrf'])) {
        $_SESSION['portfolio_project_payment_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['portfolio_project_payment_csrf'];
}
