<?php
declare(strict_types=1);

function portfolioEnsureAdvertisingTable(mysqli $connection): void
{
    $createTable = "CREATE TABLE IF NOT EXISTS portfolio_advertisements (
        ad_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        brand_name VARCHAR(120) NOT NULL,
        product_name VARCHAR(160) NOT NULL,
        description TEXT NOT NULL,
        brand_logo_url VARCHAR(500) NULL,
        product_image_url VARCHAR(500) NOT NULL,
        poster_image_url VARCHAR(500) NULL,
        video_file_url VARCHAR(500) NULL,
        pdf_file_url VARCHAR(500) NULL,
        target_url VARCHAR(1000) NULL,
        cta_label VARCHAR(80) NOT NULL DEFAULT 'Learn more',
        is_pinned TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('active','banned') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_portfolio_advertisements_status_pin (status, is_pinned, ad_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$connection->query($createTable)) {
        throw new RuntimeException('Could not initialize portfolio advertisements: ' . $connection->error);
    }
    foreach (['poster_image_url' => 'VARCHAR(500) NULL', 'video_file_url' => 'VARCHAR(500) NULL', 'pdf_file_url' => 'VARCHAR(500) NULL'] as $column => $definition) {
        $columnResult = $connection->query("SHOW COLUMNS FROM portfolio_advertisements LIKE '" . $connection->real_escape_string($column) . "'");
        if (!$columnResult) {
            throw new RuntimeException('Could not inspect portfolio advertisement storage: ' . $connection->error);
        }
        $columnExists = $columnResult->num_rows > 0;
        $columnResult->free();
        if (!$columnExists && !$connection->query("ALTER TABLE portfolio_advertisements ADD COLUMN {$column} {$definition}")) {
            throw new RuntimeException('Could not add portfolio advertisement field ' . $column . ': ' . $connection->error);
        }
    }
}

function portfolioLoadAdvertisements(mysqli $connection, bool $includeBanned = false): array
{
    $where = $includeBanned ? '' : " WHERE status = 'active'";
    $result = $connection->query(
        'SELECT ad_id, brand_name, product_name, description, brand_logo_url, product_image_url, poster_image_url, video_file_url, pdf_file_url, target_url, cta_label, is_pinned, status, created_at, updated_at
         FROM portfolio_advertisements' . $where . ' ORDER BY is_pinned DESC, ad_id DESC'
    );
    if (!$result) {
        throw new RuntimeException('Could not load portfolio advertisements: ' . $connection->error);
    }

    $advertisements = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $advertisements;
}

function portfolioStoreAdvertisementUpload(array $file, string $type): string
{
    $rules = [
        'image' => ['extensions' => ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'], 'max_bytes' => 8 * 1024 * 1024],
        'video' => ['extensions' => ['mp4' => 'video/mp4', 'webm' => 'video/webm'], 'max_bytes' => 50 * 1024 * 1024],
        'pdf' => ['extensions' => ['pdf' => 'application/pdf'], 'max_bytes' => 15 * 1024 * 1024],
    ];
    if (!isset($rules[$type])) {
        throw new InvalidArgumentException('Unsupported advertisement upload type.');
    }
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('The advertisement upload was not received correctly.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['name'], $file['size']) ||
        !is_string($file['tmp_name']) || !is_string($file['name']) ||
        !is_int($file['size']) || $file['size'] < 1 || $file['size'] > $rules[$type]['max_bytes'] ||
        !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException(match ($type) {
            'pdf' => 'The PDF upload is invalid or exceeds the 15 MB limit.',
            'video' => 'The video upload is invalid or exceeds the 50 MB limit.',
            default => 'The image upload is invalid or exceeds the 8 MB limit.',
        });
    }

    $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($rules[$type]['extensions'][$extension])) {
        throw new RuntimeException(match ($type) {
            'pdf' => 'Upload a PDF document.',
            'video' => 'Upload an MP4 or WebM video.',
            default => 'Upload a JPG, PNG, GIF, or WebP image.',
        });
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!is_string($mime) || $mime !== $rules[$type]['extensions'][$extension]) {
        throw new RuntimeException('The uploaded file content does not match its file type.');
    }
    if ($type === 'image' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded image could not be verified.');
    }

    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio' . DIRECTORY_SEPARATOR . 'advertisements';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The advertisement upload directory could not be created.');
    }
    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $fileName)) {
        throw new RuntimeException('The advertisement file could not be stored.');
    }

    return 'uploads/portfolio/advertisements/' . $fileName;
}

function portfolioAdvertisementAssetValid($value): bool
{
    if (!is_string($value)) {
        return false;
    }
    $value = trim($value);
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
    return preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $value) === 1;
}

function portfolioAdvertisementPdfValid($value): bool
{
    if (!portfolioAdvertisementAssetValid($value)) {
        return false;
    }
    return strtolower((string) pathinfo((string) parse_url(trim((string) $value), PHP_URL_PATH), PATHINFO_EXTENSION)) === 'pdf';
}

function portfolioAdvertisementVideoValid($value): bool
{
    if (!portfolioAdvertisementAssetValid($value)) {
        return false;
    }
    return in_array(
        strtolower((string) pathinfo((string) parse_url(trim((string) $value), PHP_URL_PATH), PATHINFO_EXTENSION)),
        ['mp4', 'webm'],
        true
    );
}

function portfolioAdvertisementLinkValid($value): bool
{
    if (!is_string($value)) {
        return false;
    }
    $value = trim($value);
    return $value === ''
        || portfolioAdvertisementAssetValid($value)
        || preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/D', $value) === 1;
}
