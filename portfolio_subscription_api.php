<?php
declare(strict_types=1);

ob_start();
$portfolioSubscriptionResponseSent = false;

function portfolioSubscriptionJson(int $status, array $payload): void
{
    global $portfolioSubscriptionResponseSent;
    $portfolioSubscriptionResponseSent = true;
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    exit;
}

function portfolioSubscriptionHtml(string $title, string $message, string $token = '', int $status = 200): void
{
    global $portfolioSubscriptionResponseSent;
    $portfolioSubscriptionResponseSent = true;
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeToken = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $safeTitle . '</title><body style="font:16px/1.6 system-ui,sans-serif;max-width:620px;margin:12vh auto;padding:24px;color:#172033">';
    echo '<h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>';
    if ($safeToken !== '') {
        echo '<form method="post"><input type="hidden" name="unsubscribe_token" value="' . $safeToken . '"><button type="submit">Unsubscribe</button></form>';
    }
    echo '</body></html>';
    exit;
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception): void {
    error_log('Unhandled portfolio subscription endpoint error: ' . $exception->getMessage());
    portfolioSubscriptionJson(500, ['error' => 'The subscription service encountered an error. Please try again later.']);
});

register_shutdown_function(static function (): void {
    global $portfolioSubscriptionResponseSent;
    if ($portfolioSubscriptionResponseSent) {
        return;
    }

    $lastError = error_get_last();
    if (ob_get_level() === 0 && $lastError === null) {
        return;
    }
    if ($lastError !== null) {
        error_log('Portfolio subscription endpoint stopped unexpectedly: ' . $lastError['message']);
    } else {
        error_log('Portfolio subscription endpoint stopped before returning a response.');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['error' => 'The subscription service could not complete the request. Please try again later.']);
    }
});

try {
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_subscription_mailer.php';

if (isset($_GET['oauth']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $provider = is_string($_GET['oauth']) ? strtolower($_GET['oauth']) : '';
    $providers = portfolioOAuthProviders();
    if (!isset($providers[$provider])) {
        portfolioSubscriptionHtml('Unsupported sign-in provider', 'Choose Google, Facebook, or GitHub.', '', 400);
    }
    if (!$providers[$provider]['configured']) {
        $callbackUrl = portfolioPublicBaseUrl() . '/portfolio_subscription_api.php?oauth_callback=' . rawurlencode($provider);
        portfolioSubscriptionHtml(
            $providers[$provider]['label'] . ' sign-in is not configured',
            'The site owner must set ' . $providers[$provider]['client_id_env'] . ' and ' .
                $providers[$provider]['client_secret_env'] . ' in the web server environment, then register this exact callback URL with the provider: ' .
                $callbackUrl . '. Email sign-up remains available.',
            '',
            503
        );
    }

    $state = bin2hex(random_bytes(32));
    $purchaseProjectKey = is_string($_GET['purchase_project_key'] ?? null)
        ? $_GET['purchase_project_key']
        : '';
    if (!isset($_SESSION['portfolio_project_oauth_keys']) ||
        !is_array($_SESSION['portfolio_project_oauth_keys'])) {
        $_SESSION['portfolio_project_oauth_keys'] = [];
    }
    if (preg_match('/\A[a-f0-9]{64}\z/', $purchaseProjectKey) === 1) {
        $_SESSION['portfolio_project_oauth_keys'][$provider] = $purchaseProjectKey;
    } else {
        unset($_SESSION['portfolio_project_oauth_keys'][$provider]);
    }
    if (!isset($_SESSION['portfolio_subscription_oauth_state']) ||
        !is_array($_SESSION['portfolio_subscription_oauth_state'])) {
        $_SESSION['portfolio_subscription_oauth_state'] = [];
    }
    $_SESSION['portfolio_subscription_oauth_state'][$provider] = $state;
    $redirectUri = portfolioPublicBaseUrl() . '/portfolio_subscription_api.php?oauth_callback=' . rawurlencode($provider);
    $authorizationParameters = [
        'client_id' => $providers[$provider]['client_id'],
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $providers[$provider]['scope'],
        'state' => $state,
    ];
    if ($provider === 'google') {
        $authorizationParameters['prompt'] = 'select_account';
    }
    header('Location: ' . $providers[$provider]['authorization_url'] . '?' .
        http_build_query($authorizationParameters, '', '&', PHP_QUERY_RFC3986), true, 302);
    exit;
}

if (isset($_GET['oauth_callback']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $provider = is_string($_GET['oauth_callback']) ? strtolower($_GET['oauth_callback']) : '';
    $providers = portfolioOAuthProviders();
    if (!isset($providers[$provider]) || !$providers[$provider]['configured']) {
        portfolioSubscriptionHtml('Social sign-in is unavailable', 'This provider is not configured for this site.', '', 400);
    }
    if (isset($_GET['error'])) {
        portfolioSubscriptionHtml('Sign-in was not completed', 'You can close this window or return to the subscription form and use email sign-up.');
    }

    $postedState = is_string($_GET['state'] ?? null) ? $_GET['state'] : '';
    $savedStates = $_SESSION['portfolio_subscription_oauth_state'] ?? [];
    $savedState = is_array($savedStates) && isset($savedStates[$provider]) && is_string($savedStates[$provider])
        ? $savedStates[$provider]
        : '';
    if (isset($_SESSION['portfolio_subscription_oauth_state']) &&
        is_array($_SESSION['portfolio_subscription_oauth_state'])) {
        unset($_SESSION['portfolio_subscription_oauth_state'][$provider]);
    }
    $code = is_string($_GET['code'] ?? null) ? $_GET['code'] : '';
    if ($postedState === '' || $savedState === '' || !hash_equals($savedState, $postedState) || $code === '') {
        portfolioSubscriptionHtml('Sign-in could not be verified', 'Please return to the portfolio and try again.', '', 400);
    }

    try {
        $redirectUri = portfolioPublicBaseUrl() . '/portfolio_subscription_api.php?oauth_callback=' . rawurlencode($provider);
        $verifiedEmail = portfolioOAuthVerifiedEmail($provider, $code, $redirectUri);
        $purchaseProjectKeys = $_SESSION['portfolio_project_oauth_keys'] ?? [];
        $purchaseProjectKey = is_array($purchaseProjectKeys) &&
            is_string($purchaseProjectKeys[$provider] ?? null)
            ? $purchaseProjectKeys[$provider]
            : '';
        if (is_array($purchaseProjectKeys)) {
            unset($purchaseProjectKeys[$provider]);
            $_SESSION['portfolio_project_oauth_keys'] = $purchaseProjectKeys;
        }
        if (preg_match('/\A[a-f0-9]{64}\z/', $purchaseProjectKey) === 1) {
            session_regenerate_id(true);
            $_SESSION['portfolio_project_buyer_email'] = $verifiedEmail;
            $_SESSION['portfolio_project_buyer_auth_at'] = time();
            header('Location: portfolio_project_payment.php?project_key=' . rawurlencode($purchaseProjectKey), true, 303);
            exit;
        }
        $_SESSION['portfolio_subscription_oauth_email'] = [
            'email' => $verifiedEmail,
            'created_at' => time(),
        ];
        header('Location: portfolio.php?subscription=oauth', true, 303);
        exit;
    } catch (Throwable $oauthException) {
        error_log('Portfolio social sign-in failed for ' . $provider . ': ' . $oauthException->getMessage());
        portfolioSubscriptionHtml('Could not complete sign-in', 'The provider could not verify an email address. Please try another sign-in option or enter your email directly.', '', 502);
    }
}

if (isset($_GET['oauth_result']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $oauthResult = $_SESSION['portfolio_subscription_oauth_email'] ?? null;
    if (!is_array($oauthResult) || !isset($oauthResult['email'], $oauthResult['created_at']) ||
        !is_string($oauthResult['email']) || !is_int($oauthResult['created_at']) ||
        time() - $oauthResult['created_at'] > 300) {
        unset($_SESSION['portfolio_subscription_oauth_email']);
        portfolioSubscriptionJson(404, ['error' => 'The verified social account could not be found. Try signing in again.']);
    }
    portfolioSubscriptionJson(200, ['email' => $oauthResult['email']]);
}

if (isset($_GET['unsubscribe'])) {
    $token = is_string($_GET['unsubscribe']) ? $_GET['unsubscribe'] : '';
    if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
        portfolioSubscriptionHtml('Invalid unsubscribe link', 'This unsubscribe link is invalid or incomplete.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = is_string($_POST['unsubscribe_token'] ?? null) ? $_POST['unsubscribe_token'] : '';
        if (!hash_equals($token, $postedToken)) {
            portfolioSubscriptionHtml('Invalid unsubscribe link', 'This unsubscribe link is invalid or incomplete.');
        }
        try {
            if (portfolioEnsureSubscriberTable($conn)) {
                $statement = $conn->prepare(
                    'UPDATE portfolio_email_subscribers SET is_active = 0, ip_address = NULL, isp_name = NULL, device_type = NULL, city = NULL, region = NULL, country = NULL, metadata_consent_at = NULL WHERE unsubscribe_token = ? AND is_active = 1'
                );
                $statement->bind_param('s', $token);
                $statement->execute();
                $updated = $statement->affected_rows;
                $statement->close();
            } else {
                $conn->begin_transaction();
                portfolioUseSettingsSubscriberStorage($conn);
                $subscribers = portfolioLoadSettingsSubscribers($conn);
                $updated = 0;
                foreach ($subscribers as &$subscriber) {
                    if (($subscriber['unsubscribe_token'] ?? '') === $token && !empty($subscriber['is_active'])) {
                        $subscriber['is_active'] = 0;
                        foreach (['ip_address', 'isp_name', 'device_type', 'city', 'region', 'country', 'metadata_consent_at'] as $metadataField) {
                            unset($subscriber[$metadataField]);
                        }
                        $updated++;
                    }
                }
                unset($subscriber);
                portfolioSaveSettingsSubscribers($conn, $subscribers);
                $conn->commit();
            }
            unset($_SESSION['portfolio_subscription_email']);
            portfolioSubscriptionHtml(
                'Subscription updated',
                $updated > 0 ? 'You have been unsubscribed and will no longer receive new-post emails.' : 'This address is already unsubscribed.'
            );
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            error_log('Could not process portfolio unsubscribe: ' . $exception->getMessage());
            portfolioSubscriptionHtml('Could not update subscription', 'Please try again later or contact the site owner.');
        }
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET, POST');
        portfolioSubscriptionHtml('Unsupported request', 'Please open the unsubscribe link from your email.', '', 405);
    }
    portfolioSubscriptionHtml('Confirm unsubscribe', 'Click below to stop receiving new-post emails.', $token);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    portfolioSubscriptionJson(405, ['error' => 'Unsupported request method.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['social_feed_csrf']) || !is_string($csrfToken) ||
    !hash_equals((string) $_SESSION['social_feed_csrf'], $csrfToken)) {
    portfolioSubscriptionJson(403, ['error' => 'Your page session expired. Refresh the page and try again.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    portfolioSubscriptionJson(400, ['error' => 'The request body must be valid JSON.']);
}

$honeypot = $input['company'] ?? '';
if (!is_string($honeypot) || $honeypot !== '') {
    portfolioSubscriptionJson(400, ['error' => 'The subscription request could not be accepted.']);
}

$email = $input['email'] ?? null;
if (!is_string($email)) {
    portfolioSubscriptionJson(400, ['error' => 'Enter a valid email address.']);
}
$email = strtolower(trim($email));
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    portfolioSubscriptionJson(400, ['error' => 'Enter a valid email address.']);
}
$metadataConsent = $input['metadata_consent'] ?? false;
if ($metadataConsent !== true) {
    portfolioSubscriptionJson(400, ['error' => 'Consent is required to store signup IP, ISP, and device details and request approximate location from ipwho.is.']);
}

$lastAttemptAt = (int) ($_SESSION['portfolio_subscription_last_attempt'] ?? 0);
if (time() - $lastAttemptAt < 10) {
    portfolioSubscriptionJson(429, ['error' => 'Please wait a few seconds before trying again.']);
}
$subscriberMetadata = portfolioSubscriberMetadata(
    (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
);

try {
    $subscriberTableAvailable = portfolioEnsureSubscriberTable($conn);
    $conn->begin_transaction();
    $transactionActive = true;

    $token = bin2hex(random_bytes(32));
    if ($subscriberTableAvailable) {
        try {
        $hasEmailHash = portfolioSubscriberHasEmailHash($conn);
        if ($hasEmailHash) {
            $emailHash = hash('sha256', $email);
            $existingStatement = $conn->prepare('SELECT subscriber_id, is_active FROM portfolio_email_subscribers WHERE email_hash = ? FOR UPDATE');
            $existingStatement->bind_param('s', $emailHash);
        } else {
            $existingStatement = $conn->prepare('SELECT subscriber_id, is_active FROM portfolio_email_subscribers WHERE email = ? FOR UPDATE');
            $existingStatement->bind_param('s', $email);
        }
        $existingStatement->execute();
        $existingStatement->bind_result($existingId, $isActive);
        $hasExisting = $existingStatement->fetch();
        $existingStatement->close();
        if ($hasExisting && (int) $isActive === 1) {
            $metadataStatement = $conn->prepare(
                'UPDATE portfolio_email_subscribers SET ip_address = ?, isp_name = ?, device_type = ?, city = ?, region = ?, country = ?, metadata_consent_at = ? WHERE subscriber_id = ?'
            );
            $metadataStatement->bind_param(
                'sssssssi',
                $subscriberMetadata['ip_address'],
                $subscriberMetadata['isp_name'],
                $subscriberMetadata['device_type'],
                $subscriberMetadata['city'],
                $subscriberMetadata['region'],
                $subscriberMetadata['country'],
                $subscriberMetadata['metadata_consent_at'],
                $existingId
            );
            $metadataStatement->execute();
            $metadataStatement->close();
            $conn->commit();
            $transactionActive = false;
            $_SESSION['portfolio_subscription_last_attempt'] = time();
            $_SESSION['portfolio_subscription_email'] = $email;
            unset($_SESSION['portfolio_subscription_oauth_email']);
            portfolioSubscriptionJson(200, ['subscribed' => true, 'already_subscribed' => true]);
        }

        if ($hasExisting) {
            if ($hasEmailHash) {
                $saveStatement = $conn->prepare(
                    'UPDATE portfolio_email_subscribers SET email = ?, email_hash = ?, unsubscribe_token = ?, is_active = 1, subscribed_at = CURRENT_TIMESTAMP, ip_address = ?, isp_name = ?, device_type = ?, city = ?, region = ?, country = ?, metadata_consent_at = ? WHERE subscriber_id = ?'
                );
                $saveStatement->bind_param(
                    'ssssssssssi',
                    $email,
                    $emailHash,
                    $token,
                    $subscriberMetadata['ip_address'],
                    $subscriberMetadata['isp_name'],
                    $subscriberMetadata['device_type'],
                    $subscriberMetadata['city'],
                    $subscriberMetadata['region'],
                    $subscriberMetadata['country'],
                    $subscriberMetadata['metadata_consent_at'],
                    $existingId
                );
            } else {
                $saveStatement = $conn->prepare(
                    'UPDATE portfolio_email_subscribers SET email = ?, unsubscribe_token = ?, is_active = 1, subscribed_at = CURRENT_TIMESTAMP, ip_address = ?, isp_name = ?, device_type = ?, city = ?, region = ?, country = ?, metadata_consent_at = ? WHERE subscriber_id = ?'
                );
                $saveStatement->bind_param(
                    'sssssssssi',
                    $email,
                    $token,
                    $subscriberMetadata['ip_address'],
                    $subscriberMetadata['isp_name'],
                    $subscriberMetadata['device_type'],
                    $subscriberMetadata['city'],
                    $subscriberMetadata['region'],
                    $subscriberMetadata['country'],
                    $subscriberMetadata['metadata_consent_at'],
                    $existingId
                );
            }
        } else {
            if ($hasEmailHash) {
                $saveStatement = $conn->prepare(
                    'INSERT INTO portfolio_email_subscribers (email, email_hash, unsubscribe_token, is_active, ip_address, isp_name, device_type, city, region, country, metadata_consent_at) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)'
                );
                $saveStatement->bind_param(
                    'ssssssssss',
                    $email,
                    $emailHash,
                    $token,
                    $subscriberMetadata['ip_address'],
                    $subscriberMetadata['isp_name'],
                    $subscriberMetadata['device_type'],
                    $subscriberMetadata['city'],
                    $subscriberMetadata['region'],
                    $subscriberMetadata['country'],
                    $subscriberMetadata['metadata_consent_at']
                );
            } else {
                $saveStatement = $conn->prepare(
                    'INSERT INTO portfolio_email_subscribers (email, unsubscribe_token, is_active, ip_address, isp_name, device_type, city, region, country, metadata_consent_at) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?)'
                );
                $saveStatement->bind_param(
                    'sssssssss',
                    $email,
                    $token,
                    $subscriberMetadata['ip_address'],
                    $subscriberMetadata['isp_name'],
                    $subscriberMetadata['device_type'],
                    $subscriberMetadata['city'],
                    $subscriberMetadata['region'],
                    $subscriberMetadata['country'],
                    $subscriberMetadata['metadata_consent_at']
                );
            }
        }
        $saveStatement->execute();
        $saveStatement->close();

        $countResult = $conn->query('SELECT COUNT(*) AS subscriber_count FROM portfolio_email_subscribers WHERE is_active = 1');
        if (!$countResult) {
            throw new RuntimeException('Could not count active portfolio subscribers: ' . $conn->error);
        }
        $subscriberCount = (int) $countResult->fetch_assoc()['subscriber_count'];
        $countResult->free();
        } catch (Throwable $tableException) {
            $conn->rollback();
            $transactionActive = false;
            error_log('Portfolio subscriber table write failed; retrying with site settings storage: ' . $tableException->getMessage());
            $subscriberTableAvailable = false;
        }
    }

    if (!$subscriberTableAvailable) {
        if (!$transactionActive) {
            $conn->begin_transaction();
            $transactionActive = true;
        }
        portfolioUseSettingsSubscriberStorage($conn);
        if (!$conn->query(
            "INSERT INTO system_settings (setting_key, setting_value, setting_type)
             VALUES ('portfolio_email_subscribers', '[]', 'json')
             ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key)"
        )) {
            throw new RuntimeException('Could not initialize subscription settings storage: ' . $conn->error);
        }
        $settingsStatement = $conn->prepare(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'portfolio_email_subscribers' FOR UPDATE"
        );
        $settingsStatement->execute();
        $settingsStatement->bind_result($subscriberJson);
        if (!$settingsStatement->fetch()) {
            $settingsStatement->close();
            throw new RuntimeException('Could not load subscription records from site settings.');
        }
        $settingsStatement->close();
        $subscribers = json_decode((string) $subscriberJson, true);
        if (!is_array($subscribers)) {
            throw new RuntimeException('Stored subscription records are not valid JSON.');
        }
        $existingIndex = null;
        foreach ($subscribers as $index => $subscriber) {
            if (is_array($subscriber) && isset($subscriber['email']) && strtolower((string) $subscriber['email']) === $email) {
                $existingIndex = $index;
                if (!empty($subscriber['is_active'])) {
                    $subscribers[$existingIndex] = array_merge($subscriber, $subscriberMetadata);
                    portfolioSaveSettingsSubscribers($conn, $subscribers);
                    $conn->commit();
                    $transactionActive = false;
                    $_SESSION['portfolio_subscription_last_attempt'] = time();
                    $_SESSION['portfolio_subscription_email'] = $email;
                    unset($_SESSION['portfolio_subscription_oauth_email']);
                    portfolioSubscriptionJson(200, ['subscribed' => true, 'already_subscribed' => true]);
                }
                break;
            }
        }
        $record = [
            'email' => $email,
            'unsubscribe_token' => $token,
            'is_active' => 1,
            'subscribed_at' => date('Y-m-d H:i:s'),
        ] + $subscriberMetadata;
        if ($existingIndex === null) {
            $subscribers[] = $record;
        } else {
            $subscribers[$existingIndex] = $record;
        }
        portfolioSaveSettingsSubscribers($conn, $subscribers);
        $subscriberCount = count(array_filter(
            $subscribers,
            static fn($subscriber): bool => is_array($subscriber) && !empty($subscriber['is_active'])
        ));
    }
    $conn->commit();
    $transactionActive = false;
    $_SESSION['portfolio_subscription_last_attempt'] = time();
    $_SESSION['portfolio_subscription_email'] = $email;
    unset($_SESSION['portfolio_subscription_oauth_email']);
    $identity = portfolioMailIdentity($conn);
    $unsubscribeUrl = portfolioPublicBaseUrl() . '/portfolio_subscription_api.php?unsubscribe=' . rawurlencode($token);
    $body = "Thank you for subscribing to " . $identity['name'] . " portfolio updates.\n\nYou will receive an email when a new featured update or news post is published.\n\nIf you did not request this, you can unsubscribe here: " . $unsubscribeUrl . "\n";
    try {
        $emailSent = portfolioSendEmail($email, 'Thanks for subscribing to portfolio updates', $body, $identity['email']);
    } catch (Throwable $mailException) {
        error_log('Could not send portfolio subscription confirmation: ' . $mailException->getMessage());
        $emailSent = false;
    }
    if (!$emailSent) {
        error_log('PHP mail() did not accept a portfolio subscription confirmation message.');
    }
    portfolioSubscriptionJson(201, [
        'subscribed' => true,
        'already_subscribed' => false,
        'subscriber_count' => $subscriberCount,
        'email_sent' => $emailSent,
    ]);
} catch (Throwable $exception) {
    if (isset($transactionActive) && $transactionActive) {
        $conn->rollback();
    }
    error_log('Could not complete portfolio subscription: ' . $exception->getMessage());
    portfolioSubscriptionJson(500, ['error' => 'The subscription could not be saved. Please try again later.']);
}
} catch (Throwable $exception) {
    error_log('Could not initialize portfolio subscription endpoint: ' . $exception->getMessage());
    portfolioSubscriptionJson(500, ['error' => 'Subscription setup failed. Please check the site database connection and table permissions.']);
}
