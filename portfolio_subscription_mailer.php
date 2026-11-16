<?php
declare(strict_types=1);

function portfolioEnsureSubscriberTable(mysqli $connection): bool
{
    try {
        if (!$connection->query("CREATE TABLE IF NOT EXISTS system_settings (
            setting_id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )")) {
            throw new RuntimeException('Could not initialize site settings storage: ' . $connection->error);
        }

        $storageSetting = $connection->query(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_subscriber_storage' LIMIT 1"
        );
        $useSettingsStorage = false;
        if ($storageSetting) {
            $storageRow = $storageSetting->fetch_assoc();
            $storageSetting->free();
            $useSettingsStorage = ($storageRow['setting_value'] ?? '') === 'settings';
        }

        $existingTable = $connection->query("SHOW TABLES LIKE 'portfolio_email_subscribers'");
        $tableExists = $existingTable && $existingTable->num_rows > 0;
        if ($existingTable) {
            $existingTable->free();
        }
        if ($useSettingsStorage && !$tableExists) {
            return false;
        }
        if (!$tableExists) {
            $createTable = "CREATE TABLE IF NOT EXISTS portfolio_email_subscribers (
                subscriber_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(254) NOT NULL,
                email_hash CHAR(64) NOT NULL,
                unsubscribe_token CHAR(64) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NULL,
                isp_name VARCHAR(160) NULL,
                device_type VARCHAR(20) NULL,
                city VARCHAR(120) NULL,
                region VARCHAR(120) NULL,
                country VARCHAR(120) NULL,
                metadata_consent_at DATETIME NULL,
                UNIQUE KEY uq_portfolio_subscriber_email_hash (email_hash),
                UNIQUE KEY uq_portfolio_subscriber_token (unsubscribe_token),
                KEY idx_portfolio_subscriber_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            if (!$connection->query($createTable)) {
                error_log('Could not create portfolio subscriber table; using system settings storage: ' . $connection->error);
                return false;
            }
        }

        $metadataColumns = [
            'ip_address' => 'VARCHAR(45) NULL',
            'isp_name' => 'VARCHAR(160) NULL',
            'device_type' => 'VARCHAR(20) NULL',
            'city' => 'VARCHAR(120) NULL',
            'region' => 'VARCHAR(120) NULL',
            'country' => 'VARCHAR(120) NULL',
            'metadata_consent_at' => 'DATETIME NULL',
        ];
        foreach ($metadataColumns as $column => $definition) {
            $columnResult = $connection->query("SHOW COLUMNS FROM portfolio_email_subscribers LIKE '" . $connection->real_escape_string($column) . "'");
            if (!$columnResult) {
                throw new RuntimeException('Could not inspect subscriber metadata columns: ' . $connection->error);
            }
            $columnExists = $columnResult->num_rows > 0;
            $columnResult->free();
            if (!$columnExists && !$connection->query("ALTER TABLE portfolio_email_subscribers ADD COLUMN {$column} {$definition}")) {
                throw new RuntimeException('Could not add subscriber metadata column ' . $column . ': ' . $connection->error);
            }
        }
        return !$useSettingsStorage;
    } catch (Throwable $exception) {
        error_log('Could not initialize portfolio subscriber table; using system settings storage: ' . $exception->getMessage());
        return false;
    }
}

function portfolioUseSettingsSubscriberStorage(mysqli $connection): void
{
    $statement = $connection->prepare(
        "INSERT INTO system_settings (setting_key, setting_value, setting_type)
         VALUES ('portfolio_subscriber_storage', 'settings', 'text')
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)"
    );
    if (!$statement || !$statement->execute()) {
        throw new RuntimeException('Could not select subscription storage: ' . $connection->error);
    }
    $statement->close();
}

function portfolioLoadSettingsSubscribers(mysqli $connection): array
{
    $result = $connection->query("SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_email_subscribers' LIMIT 1");
    if (!$result) {
        throw new RuntimeException('Could not load subscription records: ' . $connection->error);
    }
    $row = $result->fetch_assoc();
    $result->free();
    if (!$row) {
        return [];
    }
    $subscribers = json_decode((string) $row['setting_value'], true);
    if (!is_array($subscribers)) {
        throw new RuntimeException('Stored subscription records are not valid JSON.');
    }
    return array_values(array_filter($subscribers, static function ($subscriber): bool {
        return is_array($subscriber) && isset($subscriber['email']) && is_string($subscriber['email']);
    }));
}

function portfolioSaveSettingsSubscribers(mysqli $connection, array $subscribers): void
{
    $encoded = json_encode(array_values($subscribers), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($encoded)) {
        throw new RuntimeException('Could not encode subscription records.');
    }
    $statement = $connection->prepare(
        "INSERT INTO system_settings (setting_key, setting_value, setting_type)
         VALUES ('portfolio_email_subscribers', ?, 'json')
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)"
    );
    if (!$statement || !$statement->bind_param('s', $encoded) || !$statement->execute()) {
        throw new RuntimeException('Could not save subscription records: ' . $connection->error);
    }
    $statement->close();
}

function portfolioLoadSubscribers(mysqli $connection): array
{
    $tableAvailable = portfolioEnsureSubscriberTable($connection);
    $subscribersByEmail = [];
    if (!$tableAvailable) {
        foreach (portfolioLoadSettingsSubscribers($connection) as $subscriber) {
            $subscribersByEmail[strtolower($subscriber['email'])] = $subscriber;
        }
    }

    try {
        $tableCheck = $connection->query("SHOW TABLES LIKE 'portfolio_email_subscribers'");
        $hasTable = $tableCheck && $tableCheck->num_rows > 0;
        if ($tableCheck) {
            $tableCheck->free();
        }
        if ($hasTable) {
            $result = $connection->query('SELECT email, unsubscribe_token, is_active, subscribed_at, ip_address, isp_name, device_type, city, region, country, metadata_consent_at FROM portfolio_email_subscribers ORDER BY subscribed_at DESC');
            if (!$result) {
                throw new RuntimeException('Could not load portfolio subscribers: ' . $connection->error);
            }
            while ($subscriber = $result->fetch_assoc()) {
                $emailKey = strtolower($subscriber['email']);
                if (!isset($subscribersByEmail[$emailKey])) {
                    $subscribersByEmail[$emailKey] = $subscriber;
                }
            }
            $result->free();
        }
    } catch (Throwable $exception) {
        if ($tableAvailable) {
            throw $exception;
        }
        error_log('Could not merge subscriber table records with settings storage: ' . $exception->getMessage());
    }

    return array_values($subscribersByEmail);
}

function portfolioSubscriberMetadata(string $remoteAddress, string $userAgent): array
{
    $ipAddress = filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false ? $remoteAddress : '';
    $normalizedAgent = substr($userAgent, 0, 1000);
    $androidTablet = preg_match('/android/i', $normalizedAgent) === 1 &&
        preg_match('/mobile/i', $normalizedAgent) !== 1;
    if ($androidTablet || preg_match('/ipad|tablet|kindle|silk/i', $normalizedAgent) === 1) {
        $deviceType = 'Tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android|iemobile|windows phone/i', $normalizedAgent) === 1) {
        $deviceType = 'Mobile';
    } elseif ($normalizedAgent !== '') {
        $deviceType = 'Desktop';
    } else {
        $deviceType = 'Unknown';
    }

    $metadata = [
        'ip_address' => $ipAddress,
        'isp_name' => '',
        'device_type' => $deviceType,
        'city' => '',
        'region' => '',
        'country' => '',
        'metadata_consent_at' => date('Y-m-d H:i:s'),
    ];
    if ($ipAddress === '' || filter_var(
        $ipAddress,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false) {
        return $metadata;
    }
    if (!function_exists('curl_init')) {
        error_log('Could not look up subscriber location because the PHP cURL extension is unavailable.');
        return $metadata;
    }

    $handle = curl_init('https://ipwho.is/' . rawurlencode($ipAddress));
    if ($handle === false) {
        error_log('Could not initialize subscriber IP geolocation lookup.');
        return $metadata;
    }
    $configured = curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'PortfolioSubscription/1.0',
    ]);
    if (!$configured) {
        $error = curl_error($handle);
        curl_close($handle);
        error_log('Could not configure subscriber IP geolocation lookup: ' . $error);
        return $metadata;
    }
    $response = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $requestError = curl_error($handle);
    curl_close($handle);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        error_log('Subscriber IP geolocation lookup failed with HTTP status ' . $status . ($requestError !== '' ? ': ' . $requestError : '.'));
        return $metadata;
    }
    $location = json_decode($response, true);
    if (!is_array($location) || ($location['success'] ?? false) !== true) {
        error_log('Subscriber IP geolocation provider returned an invalid or unsuccessful response.');
        return $metadata;
    }
    $connection = is_array($location['connection'] ?? null) ? $location['connection'] : [];
    if (isset($connection['isp']) && is_string($connection['isp'])) {
        $metadata['isp_name'] = substr(trim($connection['isp']), 0, 160);
    }
    foreach (['city' => 'city', 'region' => 'region', 'country' => 'country'] as $field => $providerField) {
        if (isset($location[$providerField]) && is_string($location[$providerField])) {
            $metadata[$field] = substr(trim($location[$providerField]), 0, 120);
        }
    }
    return $metadata;
}

function portfolioSubscriberHasEmailHash(mysqli $connection): bool
{
    $result = $connection->query("SHOW COLUMNS FROM portfolio_email_subscribers LIKE 'email_hash'");
    if (!$result) {
        throw new RuntimeException('Could not inspect portfolio subscriber storage: ' . $connection->error);
    }
    $hasEmailHash = $result->num_rows > 0;
    $result->free();
    return $hasEmailHash;
}

function portfolioMailIdentity(mysqli $connection): array
{
    $identity = ['name' => 'Portfolio', 'email' => 'info@aashish-jha.com.np'];
    try {
        $result = $connection->query("SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_header' LIMIT 1");
    } catch (mysqli_sql_exception $exception) {
        error_log('Could not load portfolio mail identity: ' . $exception->getMessage());
        return $identity;
    }

    if (!$result) {
        error_log('Could not load portfolio mail identity: ' . $connection->error);
        return $identity;
    }
    $row = $result->fetch_assoc();
    $result->free();
    $settings = is_array($row) ? json_decode((string) $row['setting_value'], true) : null;
    if (is_array($settings)) {
        if (isset($settings['name']) && is_string($settings['name']) && trim($settings['name']) !== '') {
            $identity['name'] = trim($settings['name']);
        }
        if (isset($settings['email']) && is_string($settings['email']) && filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
            $identity['email'] = $settings['email'];
        }
    }

    return $identity;
}

function portfolioOAuthProviders(): array
{
    $providerSettings = [
        'google' => [
            'label' => 'Google',
            'client_id_env' => 'PORTFOLIO_GOOGLE_CLIENT_ID',
            'client_secret_env' => 'PORTFOLIO_GOOGLE_CLIENT_SECRET',
            'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'profile_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope' => 'openid email profile',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'client_id_env' => 'PORTFOLIO_FACEBOOK_CLIENT_ID',
            'client_secret_env' => 'PORTFOLIO_FACEBOOK_CLIENT_SECRET',
            'authorization_url' => 'https://www.facebook.com/v22.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v22.0/oauth/access_token',
            'profile_url' => 'https://graph.facebook.com/me?fields=id,name,email',
            'scope' => 'email,public_profile',
        ],
        'github' => [
            'label' => 'GitHub',
            'client_id_env' => 'PORTFOLIO_GITHUB_CLIENT_ID',
            'client_secret_env' => 'PORTFOLIO_GITHUB_CLIENT_SECRET',
            'authorization_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'profile_url' => 'https://api.github.com/user/emails',
            'scope' => 'read:user user:email',
        ],
    ];

    foreach ($providerSettings as &$settings) {
        $settings['client_id'] = trim((string) getenv($settings['client_id_env']));
        $settings['client_secret'] = trim((string) getenv($settings['client_secret_env']));
        $settings['configured'] = $settings['client_id'] !== '' && $settings['client_secret'] !== '';
    }
    unset($settings);

    return $providerSettings;
}

function portfolioOAuthRequest(string $url, string $method, array $parameters = [], array $headers = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Social sign-in requires the PHP cURL extension.');
    }

    if ($method === 'GET' && $parameters !== []) {
        $url .= (strpos($url, '?') === false ? '?' : '&') .
            http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Could not initialize the social sign-in request.');
    }
    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_USERAGENT => 'PortfolioSubscription/1.0',
    ];
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
    if (!curl_setopt_array($handle, $options)) {
        $requestError = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException('Could not configure the social sign-in request.' . ($requestError !== '' ? ' ' . $requestError : ''));
    }
    $response = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $requestError = curl_error($handle);
    curl_close($handle);

    if (!is_string($response) || $response === '') {
        throw new RuntimeException('Social sign-in could not reach the identity provider.' . ($requestError !== '' ? ' ' . $requestError : ''));
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('The identity provider returned an unreadable response.');
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('The identity provider rejected the social sign-in request.');
    }

    return $decoded;
}

function portfolioOAuthVerifiedEmail(string $provider, string $code, string $redirectUri): string
{
    $providers = portfolioOAuthProviders();
    if (!isset($providers[$provider]) || !$providers[$provider]['configured']) {
        throw new RuntimeException('This social sign-in provider is not configured.');
    }
    $settings = $providers[$provider];
    $tokenParameters = [
        'client_id' => $settings['client_id'],
        'client_secret' => $settings['client_secret'],
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ];
    $tokenResponse = portfolioOAuthRequest(
        $settings['token_url'],
        $provider === 'facebook' ? 'GET' : 'POST',
        $tokenParameters
    );
    $accessToken = $tokenResponse['access_token'] ?? '';
    if (!is_string($accessToken) || $accessToken === '') {
        throw new RuntimeException('The identity provider did not return an access token.');
    }

    $profileHeaders = ['Authorization: Bearer ' . $accessToken];
    if ($provider === 'github') {
        $emailRecords = portfolioOAuthRequest($settings['profile_url'], 'GET', [], $profileHeaders);
        foreach ($emailRecords as $emailRecord) {
            if (is_array($emailRecord) && !empty($emailRecord['primary']) && !empty($emailRecord['verified']) &&
                isset($emailRecord['email']) && is_string($emailRecord['email'])) {
                $email = strtolower(trim($emailRecord['email']));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }
        throw new RuntimeException('GitHub did not provide a verified primary email address.');
    }

    $profile = portfolioOAuthRequest($settings['profile_url'], 'GET', [], $profileHeaders);
    $email = $profile['email'] ?? '';
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('The identity provider did not provide a valid email address.');
    }
    if ($provider === 'google' && empty($profile['email_verified'])) {
        throw new RuntimeException('Google did not verify this email address.');
    }

    return strtolower(trim($email));
}

function portfolioPublicBaseUrl(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (preg_match('/\A[a-z0-9.-]+(?::[0-9]{1,5})?\z/i', $host) !== 1) {
        return 'https://aashish-jha.com.np';
    }

    $hostname = preg_replace('/:[0-9]{1,5}\z/', '', $host);
    if (!is_string($hostname) || filter_var($hostname, FILTER_VALIDATE_IP) === false &&
        filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
        return 'https://aashish-jha.com.np';
    }

    $isLocalHost = in_array(strtolower($hostname), ['localhost', '127.0.0.1', '::1'], true);
    $isHttps = !$isLocalHost || (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off');
    return ($isHttps ? 'https://' : 'http://') . $host;
}

function portfolioSendEmail(string $recipient, string $subject, string $body, string $sender): bool
{
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $safeSubject = preg_replace('/[\r\n]+/', ' ', trim($subject));
    if (!is_string($safeSubject) || $safeSubject === '') {
        return false;
    }

    $smtpResult = portfolioSendGmailSmtpEmail($recipient, $safeSubject, $body, $sender);
    if ($smtpResult !== null) {
        return $smtpResult;
    }

    if (!function_exists('mail')) {
        return false;
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($safeSubject) . '?=';
    $headers = [
        'From: ' . $sender,
        'Reply-To: ' . $sender,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    set_error_handler(
        static function (int $severity, string $message): bool {
            if (($severity & E_WARNING) === 0) {
                return false;
            }
            error_log('Portfolio email delivery warning: ' . $message);
            return true;
        },
        E_WARNING
    );
    try {
        return mail($recipient, $encodedSubject, $body, implode("\r\n", $headers));
    } finally {
        restore_error_handler();
    }
}

function portfolioSendGmailSmtpEmail(string $recipient, string $subject, string $body, string $replyTo): ?bool
{
    $username = trim((string) getenv('PORTFOLIO_GMAIL_USERNAME'));
    $appPassword = str_replace(' ', '', (string) getenv('PORTFOLIO_GMAIL_APP_PASSWORD'));
    if ($username === '' && $appPassword === '') {
        return null;
    }
    if (!filter_var($username, FILTER_VALIDATE_EMAIL) || $appPassword === '' || !extension_loaded('openssl')) {
        error_log('Gmail SMTP is configured incompletely or PHP OpenSSL is unavailable.');
        return false;
    }

    $socket = @stream_socket_client(
        'ssl://smtp.gmail.com:465',
        $errorCode,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT
    );
    if (!is_resource($socket)) {
        error_log('Could not connect to Gmail SMTP: ' . (string) $errorMessage . ' (' . (string) $errorCode . ').');
        return false;
    }
    stream_set_timeout($socket, 15);

    $readResponse = static function () use ($socket): array {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new RuntimeException('Gmail SMTP closed the connection unexpectedly.');
            }
            $response .= $line;
            if (!preg_match('/\A([0-9]{3})([ -])/', $line, $matches)) {
                throw new RuntimeException('Gmail SMTP returned an invalid response.');
            }
            $status = (int) $matches[1];
        } while ($matches[2] === '-');

        return [$status, trim($response)];
    };
    $expectResponse = static function (array $acceptedCodes, string $operation) use ($readResponse): void {
        [$status, $response] = $readResponse();
        if (!in_array($status, $acceptedCodes, true)) {
            throw new RuntimeException($operation . ' failed with SMTP status ' . $status . ': ' . $response);
        }
    };
    $sendCommand = static function (string $command, array $acceptedCodes, string $operation) use ($socket, $expectResponse): void {
        $data = $command . "\r\n";
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException($operation . ' could not be written to Gmail SMTP.');
            }
            $offset += $written;
        }
        $expectResponse($acceptedCodes, $operation);
    };

    try {
        $expectResponse([220], 'Gmail SMTP greeting');
        $sendCommand('EHLO localhost', [250], 'Gmail SMTP greeting');
        $sendCommand('AUTH LOGIN', [334], 'Gmail SMTP authentication');
        $sendCommand(base64_encode($username), [334], 'Gmail SMTP username');
        $sendCommand(base64_encode($appPassword), [235], 'Gmail SMTP authentication');
        $sendCommand('MAIL FROM:<' . $username . '>', [250], 'Gmail SMTP sender');
        $sendCommand('RCPT TO:<' . $recipient . '>', [250, 251], 'Gmail SMTP recipient');
        $sendCommand('DATA', [354], 'Gmail SMTP message start');

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = [
            'From: Portfolio <' . $username . '>',
            'Reply-To: ' . $replyTo,
            'To: ' . $recipient,
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode($body), 76, "\r\n")),
        ];
        $data = implode("\r\n", $message);
        $data = preg_replace('/(?m)^\./', '..', $data) . "\r\n.\r\n";
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('The email message could not be written to Gmail SMTP.');
            }
            $offset += $written;
        }
        $expectResponse([250], 'Gmail SMTP message delivery');
        return true;
    } catch (Throwable $exception) {
        error_log('Gmail SMTP email delivery failed: ' . $exception->getMessage());
        return false;
    } finally {
        fclose($socket);
    }
}

function portfolioFindNewBlogPosts(string $settingKey, $previous, $updated): array
{
    if (!in_array($settingKey, ['blog_feature_update', 'blog_insurance_news'], true) || !is_array($updated)) {
        return [];
    }

    if ($settingKey === 'blog_feature_update') {
        $oldTitle = is_array($previous) && isset($previous['title']) && is_string($previous['title'])
            ? trim($previous['title'])
            : '';
        $newTitle = isset($updated['title']) && is_string($updated['title']) ? trim($updated['title']) : '';
        if ($newTitle === '' || strcasecmp($oldTitle, $newTitle) === 0) {
            return [];
        }

        return [[
            'title' => $newTitle,
            'description' => isset($updated['description']) && is_string($updated['description']) ? trim($updated['description']) : '',
            'link_url' => isset($updated['link_url']) && is_string($updated['link_url']) ? trim($updated['link_url']) : '',
        ]];
    }

    $knownTitles = [];
    if (is_array($previous)) {
        foreach ($previous as $item) {
            if (is_array($item) && isset($item['title']) && is_string($item['title'])) {
                $knownTitles[strtolower(trim($item['title']))] = true;
            }
        }
    }

    $newPosts = [];
    foreach ($updated as $item) {
        if (!is_array($item) || !isset($item['title']) || !is_string($item['title'])) {
            continue;
        }
        $title = trim($item['title']);
        $titleKey = strtolower($title);
        if ($title === '' || isset($knownTitles[$titleKey])) {
            continue;
        }
        $knownTitles[$titleKey] = true;
        $newPosts[] = [
            'title' => $title,
            'description' => isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '',
            'link_url' => isset($item['link_url']) && is_string($item['link_url']) ? trim($item['link_url']) : '',
        ];
    }

    return $newPosts;
}

function portfolioNotifySubscribers(mysqli $connection, string $sender, string $senderName, array $posts): array
{
    $subscribers = array_values(array_filter(
        portfolioLoadSubscribers($connection),
        static fn(array $subscriber): bool => !empty($subscriber['is_active'])
    ));
    $summary = ['sent' => 0, 'failed' => 0, 'subscribers' => count($subscribers)];
    $baseUrl = portfolioPublicBaseUrl();

    foreach ($posts as $post) {
        if (!isset($post['title']) || !is_string($post['title'])) {
            continue;
        }

        $title = trim($post['title']);
        if ($title === '') {
            continue;
        }
        $description = isset($post['description']) && is_string($post['description']) ? trim($post['description']) : '';
        $linkUrl = isset($post['link_url']) && is_string($post['link_url']) ? trim($post['link_url']) : '';

        foreach ($subscribers as $subscriber) {
            $unsubscribeUrl = $baseUrl . '/portfolio_subscription_api.php?unsubscribe=' . rawurlencode($subscriber['unsubscribe_token']);
            $body = "Hello,\n\n" . $senderName . " published a new blog post: " . $title . "\n\n";
            if ($description !== '') {
                $body .= $description . "\n\n";
            }
            if ($linkUrl !== '' && $linkUrl !== '#') {
                $body .= "Read more: " . $linkUrl . "\n\n";
            }
            $body .= "To stop receiving updates, visit: " . $unsubscribeUrl . "\n";

            if (portfolioSendEmail($subscriber['email'], 'New post: ' . $title, $body, $sender)) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
                error_log('Could not send a portfolio post notification to subscriber ID ' . (string) ($subscriber['subscriber_id'] ?? 'unknown'));
            }
        }
    }

    return $summary;
}
