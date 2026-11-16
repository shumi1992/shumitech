<?php
declare(strict_types=1);
require_once __DIR__ . '/portfolio_project_payment_storage.php';

$isGalleryPage = isset($isGalleryPage) && $isGalleryPage === true;

$portfolio = [
    'name' => 'Aashish Jha',
    'brand_name' => 'Aashish Jha',
    'initials' => 'AJ',
    'role' => 'Web Developer',
    'location' => 'Kathmandu, Nepal',
    'email' => 'info@aashish-jha.com.np',
    'logo_image' => '',
    'availability' => 'Kathmandu, Nepal',
    'intro' => 'I love to learn and build solutions that make a difference.',
    'about' => [
        'I’m Aashish Jha, a web developer based in Kathmandu, Nepal. I build modern web applications with PHP and MySQL.',
        'My toolkit includes PHP, MySQL, JavaScript, HTML, CSS, Bootstrap, and SEO.',
        'Explore my work, read my notes, or get in touch at info@aashish-jha.com.np.',
    ],
];
$portfolioHome = [
    'headline' => "Hi, I'm Shumi.",
    'roles' => ['Web Developer', 'Student', 'Developer', 'Tech Explorer'],
    'intro' => $portfolio['intro'],
    'portrait_url' => 'https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg',
    'background_url' => 'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg',
    'portrait_bio_name' => 'Shumi',
    'portrait_bio_kicker' => 'A little about me',
    'portrait_bio' => $portfolio['intro'],
    'portrait_skills' => 'PHP · MySQL · JavaScript · HTML · CSS',
    'hire_label' => 'Hire Me',
    'cv_url' => 'cv.pdf',
    'cv_label' => 'Download CV',
    'hire_url' => 'mailto:' . $portfolio['email'] . '?subject=Hiring%20inquiry',
    'certificates_label' => 'Certificates',
    'certificates_url' => 'portfolio-gallery.php#galleryWall',
    'subscription_label' => 'Subscribe',
    'subscription_url' => '#contact',
    'visibility' => [
        'headline' => true, 'roles' => true, 'intro' => true, 'portrait' => true,
        'background' => true, 'hire_button' => true, 'cv_button' => true, 'certificates_button' => true,
        'subscription_button' => true,
    ],
    'stats' => [
        ['label' => 'Years Experience', 'value' => 1, 'suffix' => '+', 'visible' => true],
        ['label' => 'Completed Projects', 'value' => 2, 'suffix' => '+', 'visible' => true],
        ['label' => 'Subscriber Number', 'value' => 5, 'suffix' => '+', 'visible' => true],
        ['label' => 'Happy Users', 'value' => 15, 'suffix' => '+', 'visible' => true],
    ],
    'menu_links' => [
        ['label' => 'Home', 'url' => '#home', 'children' => []],
        ['label' => 'About', 'url' => '#about', 'children' => [
            ['label' => 'About me', 'url' => '#about'],
            ['label' => 'Journey', 'url' => '#journey'],
            ['label' => 'Skills', 'url' => '#skills'],
            ['label' => 'FAQs', 'url' => '#faq']
        ]],
        ['label' => 'Projects', 'url' => '#work', 'children' => [
            ['label' => 'All Projects', 'url' => '#work', 'filter' => 'all'],
            ['label' => 'Strategy', 'url' => '#workGoals', 'children' => [
                ['label' => 'Mission', 'url' => '#goal-mission'],
                ['label' => 'Vision', 'url' => '#goal-vision'],
                ['label' => 'Core Value', 'url' => '#goal-core-value']
            ]]
        ]],
        ['label' => 'Gallery', 'url' => '#gallery', 'children' => []],
        ['label' => 'Blog', 'url' => '#blog', 'children' => []],
        ['label' => 'Contact', 'url' => '#contact', 'children' => []]
    ],
];
$portfolioAbout = [
    'header_label' => 'About Me',
    'header_title' => 'Who I Am',
    'image' => 'https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg',
    'profile_kicker' => 'The person behind the work',
    'statement_title' => 'More than what I do.',
    'statement_accent' => 'Why I do it.',
    'descriptions' => [
        $portfolio['about'][0],
        $portfolio['about'][1],
        $portfolio['intro']
    ],
    'objectives_kicker' => 'What guides me',
    'objectives_title' => 'My objectives & values.',
    'values' => [
        'curiosity' => ['label' => 'Curiosity', 'description' => 'I ask questions, stay open to new perspectives, and look for the human need behind every brief.'],
        'craft' => ['label' => 'Craft', 'description' => 'I care about the details: clear words, thoughtful interactions, and work that feels as good as it functions.'],
        'care' => ['label' => 'Care', 'description' => 'I make room for collaboration and accessibility so more people can feel considered in the outcome.'],
        'learning' => ['label' => 'Always learning', 'description' => 'I treat each project as a chance to experiment, reflect, and make the next iteration better.']
    ],
    'footer_text' => $portfolio['about'][2]
];
$portfolioSettings = [
    'portfolio_header' => array_intersect_key($portfolio, array_flip(['name', 'brand_name', 'initials', 'role', 'location', 'email', 'logo_image'])),
    'portfolio_home' => $portfolioHome,
    'portfolio_about' => $portfolioAbout,
    'portfolio_skills' => [],
    'portfolio_journey' => [],
    'portfolio_faqs' => [],
    'portfolio_projects' => null,
    'portfolio_social_posts' => null,
    'portfolio_sections' => [],
];

$subscriberCount = 0;
$subscriptionOAuthProviders = [];
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/portfolio_subscription_mailer.php';
    $subscriptionOAuthProviders = portfolioOAuthProviders();
    $portfolioSettingsResult = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('portfolio_header','portfolio_home','portfolio_about','portfolio_skills','portfolio_journey','portfolio_faqs','portfolio_projects','portfolio_social_posts','portfolio_sections')");
    if ($portfolioSettingsResult) {
        while ($row = $portfolioSettingsResult->fetch_assoc()) {
            $decoded = json_decode($row['setting_value'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && array_key_exists($row['setting_key'], $portfolioSettings)) {
                if ($row['setting_key'] === 'portfolio_header' && !isset($decoded['brand_name']) && isset($decoded['name']) && is_string($decoded['name'])) {
                    $decoded['brand_name'] = $decoded['name'];
                }
                $portfolioSettings[$row['setting_key']] = $row['setting_key'] === 'portfolio_social_posts'
                    ? array_values($decoded)
                    : array_replace($portfolioSettings[$row['setting_key']] ?? [], $decoded);
            }
        }
    } else {
        error_log('Could not load portfolio website settings: ' . $conn->error);
    }
} catch (mysqli_sql_exception $exception) {
    error_log('Could not load portfolio website settings: ' . $exception->getMessage());
}

$portfolioAdvertisements = [];
try {
    require_once __DIR__ . '/portfolio_advertising_storage.php';
    portfolioEnsureAdvertisingTable($conn);
    $portfolioAdvertisements = array_values(array_filter(
        portfolioLoadAdvertisements($conn),
        static function (array $advertisement): bool {
            $logo = trim((string) ($advertisement['brand_logo_url'] ?? ''));
            $poster = trim((string) ($advertisement['poster_image_url'] ?? ''));
            $video = trim((string) ($advertisement['video_file_url'] ?? ''));
            $pdf = trim((string) ($advertisement['pdf_file_url'] ?? ''));
            return portfolioAdvertisementAssetValid($advertisement['product_image_url'] ?? null)
                && ($logo === '' || portfolioAdvertisementAssetValid($logo))
                && ($poster === '' || portfolioAdvertisementAssetValid($poster))
                && ($video === '' || portfolioAdvertisementVideoValid($video))
                && ($pdf === '' || portfolioAdvertisementPdfValid($pdf))
                && portfolioAdvertisementLinkValid($advertisement['target_url'] ?? '');
        }
    ));
} catch (Throwable $exception) {
    error_log('Could not load portfolio advertisements: ' . $exception->getMessage());
}

try {
    require_once __DIR__ . '/portfolio_subscription_mailer.php';
    foreach (portfolioLoadSubscribers($conn) as $subscriber) {
        if (!empty($subscriber['is_active'])) {
            $subscriberCount++;
        }
    }
} catch (mysqli_sql_exception | RuntimeException $exception) {
    error_log('Could not load portfolio subscriber count: ' . $exception->getMessage());
}

if (!isset($_SESSION['social_feed_csrf']) || !is_string($_SESSION['social_feed_csrf'])) {
    $_SESSION['social_feed_csrf'] = bin2hex(random_bytes(32));
}
$socialFeedCsrfToken = $_SESSION['social_feed_csrf'];
$isPortfolioSubscribed = isset($_SESSION['portfolio_subscription_email']) &&
    is_string($_SESSION['portfolio_subscription_email']) &&
    filter_var($_SESSION['portfolio_subscription_email'], FILTER_VALIDATE_EMAIL) !== false;

foreach (['name', 'brand_name', 'initials', 'role', 'location', 'email', 'logo_image'] as $headerField) {
    if (isset($portfolioSettings['portfolio_header'][$headerField]) && is_string($portfolioSettings['portfolio_header'][$headerField])) {
        $portfolio[$headerField] = trim($portfolioSettings['portfolio_header'][$headerField]);
    }
}
if ($portfolio['name'] === '') {
    $portfolio['name'] = 'Aashish Jha';
}
if ($portfolio['brand_name'] === '') {
    $portfolio['brand_name'] = $portfolio['name'];
}
if ($portfolio['initials'] === '') {
    $portfolio['initials'] = 'AJ';
}
if ($portfolio['role'] === '') {
    $portfolio['role'] = 'Web Developer';
}
if ($portfolio['location'] === '') {
    $portfolio['location'] = 'Kathmandu, Nepal';
}
if (!filter_var($portfolio['email'], FILTER_VALIDATE_EMAIL)) {
    $portfolio['email'] = 'info@aashish-jha.com.np';
}
$portfolio['availability'] = $portfolio['role'] . ' · ' . $portfolio['location'];

$homeSettings = $portfolioSettings['portfolio_home'];
$homeHeadline = isset($homeSettings['headline']) && is_string($homeSettings['headline']) && trim($homeSettings['headline']) !== ''
    ? trim($homeSettings['headline'])
    : $portfolioHome['headline'];
if ($homeHeadline === "Hi, I'm") {
    $homeHeadline = "Hi, I'm Shumi.";
}
$homeRoles = isset($homeSettings['roles']) && is_array($homeSettings['roles'])
    ? array_values(array_filter($homeSettings['roles'], static fn($role): bool => is_string($role) && trim($role) !== ''))
    : ['Web Developer', 'Student', 'Developer', 'Tech Explorer'];
if ($homeRoles === []) {
    $homeRoles = ['Web Developer', 'Student', 'Developer', 'Tech Explorer'];
}
$portfolio['intro'] = isset($homeSettings['intro']) && is_string($homeSettings['intro'])
    ? $homeSettings['intro']
    : 'I love to learn and build solutions that make a difference.';
$homeVisibilityDefaults = $portfolioHome['visibility'];
$savedHomeVisibility = isset($homeSettings['visibility']) && is_array($homeSettings['visibility']) ? $homeSettings['visibility'] : [];
$homeVisibility = $homeVisibilityDefaults;
foreach ($homeVisibilityDefaults as $visibilityKey => $defaultVisible) {
    if (isset($savedHomeVisibility[$visibilityKey]) && is_bool($savedHomeVisibility[$visibilityKey])) {
        $homeVisibility[$visibilityKey] = $savedHomeVisibility[$visibilityKey];
    }
}
$validPortfolioAsset = static function ($value, string $fallback): string {
    if (!is_string($value)) {
        return $fallback;
    }
    $value = trim($value);
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $value : $fallback;
    }
    return preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $value) === 1
        ? $value
        : $fallback;
};
$headerFeatureGrids = [];
$seenHeaderFeatureGridIds = [];
$savedHeaderFeatureGrids = $portfolioSettings['portfolio_header']['feature_grids'] ?? [];
if (is_array($savedHeaderFeatureGrids)) {
    foreach (array_slice($savedHeaderFeatureGrids, 0, 20) as $grid) {
        if (!is_array($grid) ||
            !isset($grid['id'], $grid['title'], $grid['description'], $grid['media_type'], $grid['media_url'], $grid['nav_label']) ||
            !is_string($grid['id']) || preg_match('/^[a-z0-9][a-z0-9-]{0,59}$/', $grid['id']) !== 1 || isset($seenHeaderFeatureGridIds[$grid['id']]) ||
            !is_string($grid['title']) || trim($grid['title']) === '' ||
            !is_string($grid['description']) || trim($grid['description']) === '' ||
            !is_string($grid['media_type']) || !in_array($grid['media_type'], ['image', 'video', 'pdf'], true) ||
            !is_string($grid['media_url']) || !is_string($grid['nav_label']) || trim($grid['nav_label']) === '' ||
            (isset($grid['visible']) && $grid['visible'] !== true)) {
            continue;
        }
        $mediaUrl = $validPortfolioAsset($grid['media_url'], '');
        $posterUrl = $validPortfolioAsset($grid['poster_url'] ?? '', '');
        if ($mediaUrl === '') {
            continue;
        }
        $images = [];
        if (is_array($grid['images'] ?? null)) {
            foreach (array_slice($grid['images'], 0, 8) as $image) {
                if (!is_array($image) || !isset($image['src'], $image['caption']) || !is_string($image['src']) || !is_string($image['caption'])) {
                    continue;
                }
                $imageUrl = $validPortfolioAsset($image['src'], '');
                if ($imageUrl !== '' && trim($image['caption']) !== '') {
                    $images[] = ['src' => $imageUrl, 'caption' => trim($image['caption'])];
                }
            }
        }
        $seenHeaderFeatureGridIds[$grid['id']] = true;
        $headerFeatureGrids[] = [
            'id' => $grid['id'],
            'title' => trim($grid['title']),
            'description' => trim($grid['description']),
            'media_type' => $grid['media_type'],
            'media_url' => $mediaUrl,
            'poster_url' => $posterUrl,
            'nav_label' => trim($grid['nav_label']),
            'parent' => isset($grid['parent']) && is_string($grid['parent']) ? trim($grid['parent']) : '',
            'images' => $images
        ];
    }
}
$portraitUrl = $validPortfolioAsset($homeSettings['portrait_url'] ?? null, 'https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg');
$backgroundUrl = isset($homeSettings['background_url']) && is_string($homeSettings['background_url'])
    ? $validPortfolioAsset($homeSettings['background_url'], '')
    : 'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg';
$cvUrl = $validPortfolioAsset($homeSettings['cv_url'] ?? null, 'cv.pdf');
$validPortfolioActionUrl = static function ($value, string $fallback) use ($validPortfolioAsset): string {
    if ($validPortfolioAsset($value, '') !== '') {
        return trim((string) $value);
    }
    if (is_string($value) && (
        preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*(?:#[A-Za-z][A-Za-z0-9_-]*)?$~D', trim($value)) === 1 ||
        preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/D', trim($value)) === 1
    )) {
        return trim($value);
    }
    return is_string($value) && preg_match('/^mailto:[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?:\?[A-Za-z0-9._~%&=+-]*)?$/iD', trim($value)) === 1
        ? trim($value)
        : $fallback;
};
$hireUrl = $validPortfolioActionUrl($homeSettings['hire_url'] ?? null, 'mailto:' . $portfolio['email'] . '?subject=Hiring%20inquiry');
$certificatesUrl = $validPortfolioActionUrl($homeSettings['certificates_url'] ?? null, 'portfolio-gallery.php#galleryWall');
$homePortraitBioName = isset($homeSettings['portrait_bio_name']) && is_string($homeSettings['portrait_bio_name']) && trim($homeSettings['portrait_bio_name']) !== '' ? trim($homeSettings['portrait_bio_name']) : 'Shumi';
$homePortraitBioKicker = isset($homeSettings['portrait_bio_kicker']) && is_string($homeSettings['portrait_bio_kicker']) && trim($homeSettings['portrait_bio_kicker']) !== '' ? trim($homeSettings['portrait_bio_kicker']) : 'A little about me';
$homePortraitBio = isset($homeSettings['portrait_bio']) && is_string($homeSettings['portrait_bio']) && trim($homeSettings['portrait_bio']) !== '' ? trim($homeSettings['portrait_bio']) : $portfolio['intro'];
$homePortraitSkills = isset($homeSettings['portrait_skills']) && is_string($homeSettings['portrait_skills']) && trim($homeSettings['portrait_skills']) !== '' ? trim($homeSettings['portrait_skills']) : 'PHP · MySQL · JavaScript · HTML · CSS';
$homeActionLabels = [
    'hire' => isset($homeSettings['hire_label']) && is_string($homeSettings['hire_label']) && trim($homeSettings['hire_label']) !== '' ? trim($homeSettings['hire_label']) : 'Hire Me',
    'cv' => isset($homeSettings['cv_label']) && is_string($homeSettings['cv_label']) && trim($homeSettings['cv_label']) !== '' ? trim($homeSettings['cv_label']) : 'Download CV',
    'certificates' => isset($homeSettings['certificates_label']) && is_string($homeSettings['certificates_label']) && trim($homeSettings['certificates_label']) !== '' ? trim($homeSettings['certificates_label']) : 'Certificates',
    'subscription' => isset($homeSettings['subscription_label']) && is_string($homeSettings['subscription_label']) && trim($homeSettings['subscription_label']) !== '' ? trim($homeSettings['subscription_label']) : 'Subscribe',
];
$defaultHomeStats = $portfolioHome['stats'];
$homeStats = isset($homeSettings['stats']) && is_array($homeSettings['stats']) ? [] : $defaultHomeStats;
if (isset($homeSettings['stats']) && is_array($homeSettings['stats'])) {
    foreach (array_slice($homeSettings['stats'], 0, 8) as $stat) {
        if (!is_array($stat) || !isset($stat['label'], $stat['value'], $stat['suffix']) ||
            !is_string($stat['label']) || trim($stat['label']) === '' ||
            filter_var($stat['value'], FILTER_VALIDATE_INT) === false || (int) $stat['value'] < 0 || (int) $stat['value'] > 999999 ||
            !is_string($stat['suffix']) || strlen($stat['suffix']) > 12) {
            $homeStats = $defaultHomeStats;
            break;
        }
        $statValue = (int) $stat['value'];
        if (in_array(strtolower(trim($stat['label'])), ['subscriber number', 'subscribers'], true)) {
            $statValue = $subscriberCount;
        }
        $homeStats[] = [
            'label' => trim($stat['label']),
            'value' => $statValue,
            'suffix' => $stat['suffix'],
            'visible' => !empty($stat['visible']),
        ];
    }
}
foreach ($homeStats as &$stat) {
    if (in_array(strtolower(trim($stat['label'])), ['subscriber number', 'subscribers'], true)) {
        $stat['value'] = $subscriberCount;
    }
}
unset($stat);
$logoImage = $validPortfolioAsset($portfolio['logo_image'], '');
$isNavigationUrlValid = static function ($value) use ($validPortfolioAsset): bool {
    if (!is_string($value)) {
        return false;
    }
    $value = trim($value);
    if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $value) === 1 || preg_match('/^(mailto|tel):[^\s]+$/i', $value) === 1) {
        return true;
    }
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
    return $validPortfolioAsset($value, '') !== '';
};
$homeMenuLinks = [];
if (isset($homeSettings['menu_links']) && is_array($homeSettings['menu_links'])) {
    foreach (array_slice($homeSettings['menu_links'], 0, 12) as $menuItem) {
        if (!is_array($menuItem) || !isset($menuItem['label'], $menuItem['url']) || !is_string($menuItem['label']) || trim($menuItem['label']) === '' || !is_string($menuItem['url'])) {
            continue;
        }
        $menuUrl = trim($menuItem['url']);
        $menuChildren = [];
        if (isset($menuItem['children']) && is_array($menuItem['children'])) {
            foreach (array_slice($menuItem['children'], 0, 10) as $childItem) {
                if (!is_array($childItem) || !isset($childItem['label'], $childItem['url']) || !is_string($childItem['label']) || trim($childItem['label']) === '' || !is_string($childItem['url']) || !$isNavigationUrlValid($childItem['url'])) {
                    continue;
                }
                $grandchildren = [];
                if (isset($childItem['children']) && is_array($childItem['children'])) {
                    foreach (array_slice($childItem['children'], 0, 6) as $grandchildItem) {
                        if (!is_array($grandchildItem) || !isset($grandchildItem['label'], $grandchildItem['url']) || !is_string($grandchildItem['label']) || trim($grandchildItem['label']) === '' || !is_string($grandchildItem['url']) || !$isNavigationUrlValid($grandchildItem['url'])) {
                            continue;
                        }
                        $grandchildren[] = [
                            'label' => trim($grandchildItem['label']),
                            'url' => trim($grandchildItem['url'])
                        ];
                    }
                }
                $menuChildren[] = [
                    'label' => trim($childItem['label']),
                    'url' => trim($childItem['url']),
                    'filter' => isset($childItem['filter']) && in_array($childItem['filter'], ['all', 'portfolio', 'platform', 'application', 'design', 'development', 'strategy'], true) ? $childItem['filter'] : '',
                    'children' => $grandchildren
                ];
            }
        }
        if (($menuUrl !== '' && !$isNavigationUrlValid($menuUrl)) || ($menuUrl === '' && $menuChildren === [])) {
            continue;
        }
        $homeMenuLinks[] = [
            'label' => trim($menuItem['label']),
            'url' => $menuUrl,
            'filter' => isset($menuItem['filter']) && in_array($menuItem['filter'], ['all', 'portfolio', 'platform', 'application', 'design', 'development', 'strategy'], true) ? $menuItem['filter'] : '',
            'children' => $menuChildren
        ];
    }
}
if ($homeMenuLinks === []) {
    $homeMenuLinks = $portfolioHome['menu_links'];
}
foreach ($homeMenuLinks as &$menuItem) {
    if (strtolower(trim($menuItem['label'])) === 'about') {
        $menuItem['children'] = [
            ['label' => 'About me', 'url' => '#about'],
            ['label' => 'Journey', 'url' => '#journey'],
            ['label' => 'FAQs', 'url' => '#faq']
        ];
        continue;
    }
    if ($menuItem['label'] !== 'Projects') {
        continue;
    }
    if (!isset($menuItem['children']) || !is_array($menuItem['children'])) {
        $menuItem['children'] = [];
    }
    $menuItem['children'] = array_values(array_filter(
        $menuItem['children'],
        static fn($childItem): bool => is_array($childItem) && !in_array(
            strtolower(trim(is_string($childItem['label'] ?? null) ? $childItem['label'] : '')),
            ['product design', 'development'],
            true
        )
    ));
    $hasStrategyMenu = false;
    foreach ($menuItem['children'] as &$childItem) {
        if (($childItem['label'] ?? '') === 'Strategy') {
            $hasStrategyMenu = true;
            if (empty($childItem['children'])) {
                $childItem['children'] = [
                    ['label' => 'Mission', 'url' => '#goal-mission'],
                    ['label' => 'Vision', 'url' => '#goal-vision'],
                    ['label' => 'Core Value', 'url' => '#goal-core-value']
                ];
            }
            break;
        }
    }
    unset($childItem);
    if (!$hasStrategyMenu) {
        $menuItem['children'][] = [
            'label' => 'Strategy',
            'url' => '#workGoals',
            'filter' => '',
            'children' => [
                ['label' => 'Mission', 'url' => '#goal-mission'],
                ['label' => 'Vision', 'url' => '#goal-vision'],
                ['label' => 'Core Value', 'url' => '#goal-core-value']
            ]
        ];
    }
}
unset($menuItem);

$aboutContent = $portfolioAbout;
$aboutSettings = $portfolioSettings['portfolio_about'];
foreach (['header_label', 'header_title', 'profile_kicker', 'statement_title', 'statement_accent', 'objectives_kicker', 'objectives_title', 'footer_text'] as $aboutField) {
    if (isset($aboutSettings[$aboutField]) && is_string($aboutSettings[$aboutField]) && trim($aboutSettings[$aboutField]) !== '') {
        $aboutContent[$aboutField] = trim($aboutSettings[$aboutField]);
    }
}
$aboutContent['image'] = $validPortfolioAsset($aboutSettings['image'] ?? null, $portfolioAbout['image']);
$aboutImages = [];
if (isset($aboutSettings['images']) && is_array($aboutSettings['images'])) {
    foreach (array_slice($aboutSettings['images'], 0, 8) as $image) {
        $validatedImage = $validPortfolioAsset($image, '');
        if ($validatedImage !== '' && !in_array($validatedImage, $aboutImages, true)) {
            $aboutImages[] = $validatedImage;
        }
    }
}
if ($aboutImages === []) {
    $aboutImages = array_values(array_unique([
        $aboutContent['image'],
        'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg',
        'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
        'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
    ]));
}
if (isset($aboutSettings['descriptions']) && is_array($aboutSettings['descriptions'])) {
    foreach ($aboutContent['descriptions'] as $index => $defaultDescription) {
        if (isset($aboutSettings['descriptions'][$index]) && is_string($aboutSettings['descriptions'][$index]) && trim($aboutSettings['descriptions'][$index]) !== '') {
            $aboutContent['descriptions'][$index] = trim($aboutSettings['descriptions'][$index]);
        }
    }
}
if (isset($aboutSettings['values']) && is_array($aboutSettings['values'])) {
    foreach ($aboutContent['values'] as $valueKey => $defaultValue) {
        $savedValue = $aboutSettings['values'][$valueKey] ?? null;
        if (!is_array($savedValue)) {
            continue;
        }
        foreach (['label', 'description'] as $valueField) {
            if (isset($savedValue[$valueField]) && is_string($savedValue[$valueField]) && trim($savedValue[$valueField]) !== '') {
                $aboutContent['values'][$valueKey][$valueField] = trim($savedValue[$valueField]);
            }
        }
    }
}
$aboutValues = $aboutContent['values'];

$projects = [
    [
        'category' => 'portfolio',
        'category_label' => 'Personal Portfolio',
        'title' => 'Personal Portfolio',
        'summary' => 'A personal website to introduce my work, share selected projects, and make it easy to get in touch.',
        'image' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
        'image_alt' => 'Personal portfolio project preview',
        'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
        'contribution' => 'A portfolio project bringing profile, project highlights, and contact information together.',
        'outcome' => 'A single place for visitors to learn about my work and explore my projects.',
        'price_birr' => 100,
    ],
    [
        'category' => 'platform',
        'category_label' => 'Web Platform',
        'title' => 'Web Platform',
        'summary' => 'A responsive platform that organizes useful content and helps visitors find the information they need.',
        'image' => 'uploads/portfolio/f8395ea72410ef0f652972f4bb04bb4d.jpg',
        'image_alt' => 'Web platform project preview',
        'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
        'contribution' => 'A web platform project focused on clear navigation and an adaptable interface.',
        'outcome' => 'A structured online experience designed to work across desktop and mobile screens.',
        'price_birr' => 100,
    ],
    [
        'category' => 'application',
        'category_label' => 'Web Application',
        'title' => 'Web Application',
        'summary' => 'A web application concept combining interactive features with a clear, straightforward user interface.',
        'image' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
        'image_alt' => 'Web application project preview',
        'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
        'contribution' => 'An application project bringing interface elements and server-side functionality together.',
        'outcome' => 'A foundation for delivering practical features through the web.',
        'price_birr' => 100,
    ],
];

$defaultProjectGoals = [
    [
        'id' => 'mission',
        'title' => 'Mission',
        'icon' => 'fa-bullseye',
        'description' => 'Build useful, accessible web experiences that solve real problems.',
        'detail' => 'I turn ideas into dependable websites and applications through thoughtful design, clear communication, and practical PHP, MySQL, JavaScript, HTML, and CSS development.',
        'visible' => true,
    ],
    [
        'id' => 'vision',
        'title' => 'Vision',
        'icon' => 'fa-eye',
        'description' => 'Create digital experiences people can trust and enjoy using.',
        'detail' => 'My vision is to keep growing as a developer and contribute to an inclusive web where reliable technology, useful content, and intuitive interactions work well for everyone.',
        'visible' => true,
    ],
    [
        'id' => 'core-value',
        'title' => 'Core Value',
        'icon' => 'fa-gem',
        'description' => 'Lead with curiosity, care, integrity, and a commitment to learning.',
        'detail' => 'I value honest collaboration, attention to detail, accessible choices, and continuous improvement. These principles guide decisions from the first conversation through delivery.',
        'visible' => true,
    ],
];
$projectGoals = $defaultProjectGoals;
$projectSettings = $portfolioSettings['portfolio_projects'];
$projectCategories = [
    'portfolio' => 'Personal Portfolio',
    'platform' => 'Web Platform',
    'application' => 'Web Application',
];
if (is_array($projectSettings)) {
    if (isset($projectSettings['projects']) && is_array($projectSettings['projects'])) {
        $savedProjects = [];
        foreach (array_slice($projectSettings['projects'], 0, 24) as $project) {
            if (!is_array($project) ||
                !isset($project['category'], $project['title'], $project['summary'], $project['image'], $project['image_alt'], $project['tags'], $project['contribution'], $project['outcome'], $project['price_birr']) ||
                !is_string($project['category']) || !isset($projectCategories[$project['category']]) ||
                !is_string($project['title']) || trim($project['title']) === '' ||
                !is_string($project['summary']) ||
                !is_string($project['image']) || ($image = $validPortfolioAsset($project['image'], '')) === '' ||
                !is_string($project['image_alt']) || !is_array($project['tags']) ||
                !is_string($project['contribution']) || !is_string($project['outcome']) ||
                filter_var($project['price_birr'], FILTER_VALIDATE_INT) === false ||
                (int) $project['price_birr'] < 0 || (int) $project['price_birr'] > 1000000) {
                continue;
            }
            $tags = array_values(array_filter(
                $project['tags'],
                static fn($tag): bool => is_string($tag) && trim($tag) !== '' && strlen($tag) <= 40
            ));
            $savedProjects[] = [
                'id' => isset($project['id']) && is_string($project['id']) &&
                    preg_match('/\A[a-z0-9][a-z0-9-]{0,59}\z/', $project['id']) === 1
                    ? $project['id']
                    : portfolioProjectPaymentLegacyId($project['category'], $project['title']),
                'category' => $project['category'],
                'category_label' => $projectCategories[$project['category']],
                'title' => trim($project['title']),
                'summary' => trim($project['summary']),
                'image' => $image,
                'image_alt' => trim($project['image_alt']),
                'tags' => array_slice($tags, 0, 12),
                'contribution' => trim($project['contribution']),
                'outcome' => trim($project['outcome']),
                'price_birr' => (int) $project['price_birr'],
                'visible' => !isset($project['visible']) || $project['visible'] === true,
            ];
        }
        $projects = $savedProjects;
    }
    if (isset($projectSettings['goals']) && is_array($projectSettings['goals'])) {
        $goalDefaultsById = [];
        foreach ($defaultProjectGoals as $goalDefault) {
            $goalDefaultsById[$goalDefault['id']] = $goalDefault;
        }
        $savedGoals = [];
        foreach (array_slice($projectSettings['goals'], 0, 3) as $goal) {
            if (!is_array($goal) ||
                !isset($goal['id'], $goal['title'], $goal['icon'], $goal['description'], $goal['detail']) ||
                !is_string($goal['id']) || !isset($goalDefaultsById[$goal['id']]) ||
                !is_string($goal['title']) || trim($goal['title']) === '' ||
                !is_string($goal['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $goal['icon']) !== 1 ||
                !is_string($goal['description']) || !is_string($goal['detail'])) {
                continue;
            }
            $savedGoals[$goal['id']] = [
                'id' => $goal['id'],
                'title' => trim($goal['title']),
                'icon' => $goal['icon'],
                'description' => trim($goal['description']),
                'detail' => trim($goal['detail']),
                'visible' => !isset($goal['visible']) || $goal['visible'] === true,
            ];
        }
        $projectGoals = array_values(array_filter(
            $savedGoals,
            static fn(array $goal): bool => $goal['visible']
        ));
    }
}
$projects = array_values(array_filter(
    $projects,
    static fn(array $project): bool => !isset($project['visible']) || $project['visible'] === true
));
foreach ($projects as &$project) {
    if (!isset($project['id'])) {
        $project['id'] = portfolioProjectPaymentLegacyId($project['category'], $project['title']);
    }
    $project['project_key'] = portfolioProjectPaymentKey($project['category'], $project['title'], $project['id']);
}
unset($project);
foreach ($homeMenuLinks as &$menuItem) {
    if (strtolower(trim($menuItem['label'])) !== 'about') {
        continue;
    }
    $menuItem['url'] = '#about';
    $menuItem['children'] = [
        ['label' => 'About me', 'url' => '#about'],
        ['label' => 'Journey', 'url' => '#journey'],
        ['label' => 'Skills', 'url' => '#skills'],
        ['label' => 'FAQs', 'url' => '#faq']
    ];
}
unset($menuItem);
foreach ($homeMenuLinks as &$menuItem) {
    if (strtolower(trim($menuItem['label'])) !== 'projects') {
        continue;
    }
    $strategyLinks = array_map(
        static fn(array $goal): array => ['label' => $goal['title'], 'url' => '#goal-' . $goal['id']],
        $projectGoals
    );
    $menuItem['url'] = '#work';
    $menuItem['children'] = [
        ['label' => 'All Projects', 'url' => '#work', 'filter' => 'all'],
        ['label' => 'Strategy', 'url' => '#workGoals', 'children' => $strategyLinks],
    ];
}
unset($menuItem);

$services = [
    [
        'icon' => 'fa-code',
        'title' => 'Website Development',
        'description' => 'Custom, responsive websites built to present your business clearly and help visitors take action.',
        'details' => 'From page structure and visual layout to interactive features, I build websites around your goals and audience.',
    ],
    [
        'icon' => 'fa-screwdriver-wrench',
        'title' => 'Website Maintenance',
        'description' => 'Ongoing updates and improvements to keep your website reliable, current, and running smoothly.',
        'details' => 'Maintenance can include content updates, routine checks, small enhancements, and help keeping your site in good shape.',
    ],
    [
        'icon' => 'fa-bug',
        'title' => 'Bug Fixing',
        'description' => 'Careful troubleshooting to find and fix website issues that interrupt the experience.',
        'details' => 'I investigate reported behavior, identify the likely cause, and apply a focused fix while checking related functionality.',
    ],
    [
        'icon' => 'fa-mobile-screen-button',
        'title' => 'Responsive Design',
        'description' => 'Layouts that adapt cleanly to phones, tablets, laptops, and desktop displays.',
        'details' => 'I refine page structure, sizing, spacing, and interactions so content remains usable across common screen sizes.',
    ],
    [
        'icon' => 'fa-database',
        'title' => 'Database Design',
        'description' => 'Organized database structures to support your application’s data and everyday workflows.',
        'details' => 'Work may include planning tables and relationships, choosing appropriate fields, and connecting the database to your application.',
    ],
];
$skillsSettings = is_array($portfolioSettings['portfolio_skills'] ?? null) ? $portfolioSettings['portfolio_skills'] : [];
$skillsVisible = !isset($skillsSettings['visible']) || $skillsSettings['visible'] === true;
$skillsTitle = isset($skillsSettings['title']) && is_string($skillsSettings['title']) && trim($skillsSettings['title']) !== ''
    ? trim($skillsSettings['title'])
    : 'What I Do';
$skillsDescription = isset($skillsSettings['description']) && is_string($skillsSettings['description']) && trim($skillsSettings['description']) !== ''
    ? trim($skillsSettings['description'])
    : 'End-to-end web development services for businesses and individuals.';
if (isset($skillsSettings['items']) && is_array($skillsSettings['items'])) {
    $savedServices = [];
    foreach (array_slice($skillsSettings['items'], 0, 30) as $service) {
        if (!is_array($service) ||
            !isset($service['icon'], $service['title'], $service['description'], $service['details']) ||
            !is_string($service['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $service['icon']) !== 1 ||
            !is_string($service['title']) || trim($service['title']) === '' ||
            !is_string($service['description']) || trim($service['description']) === '' ||
            !is_string($service['details']) || trim($service['details']) === '' ||
            (isset($service['visible']) && $service['visible'] !== true)) {
            continue;
        }
        $savedServices[] = [
            'icon' => $service['icon'],
            'title' => trim($service['title']),
            'description' => trim($service['description']),
            'details' => trim($service['details'])
        ];
    }
    $services = $savedServices;
}

$experience = [
    ['2023 — Present', 'Your Current Role · Company', 'Describe your focus, the people you work with, and the impact you make.'],
    ['2020 — 2023', 'Previous Role · Organization', 'Highlight an accomplishment, a skill you developed, or a challenge you helped solve.'],
];

$education = [
    ['Current status: Add your status', 'Qualification · Institution', 'Add your area of study, dates, and a learning experience that shaped your work.'],
];

$faqCategories = [
    'about' => 'About me',
    'experience' => 'Experience',
    'skills' => 'Skills and tools',
    'working' => 'Working together',
];

$faqs = [
    ['category' => 'about', 'question' => 'What do you do?', 'answer' => 'I’m Shumi, a web developer who enjoys learning and building practical web experiences with PHP and MySQL.'],
    ['category' => 'experience', 'question' => 'What kind of experience do you have?', 'answer' => 'I build personal web projects and keep growing my skills through hands-on learning. My current focus is responsive websites and applications using PHP, MySQL, JavaScript, HTML, and CSS.'],
    ['category' => 'skills', 'question' => 'What technologies do you work with?', 'answer' => 'My areas of focus include PHP, MySQL, JavaScript, HTML, CSS, Bootstrap, SEO, and web development.'],
    ['category' => 'working', 'question' => 'How can I get in touch?', 'answer' => 'For project or collaboration inquiries, contact Shumi using the contact form on this portfolio.'],
];

$gallery = [
    [
        'category' => 'workspace',
        'title' => 'Workspace',
        'type' => 'Workspace & process',
        'summary' => 'A glimpse into the ideas, tools, and quiet moments that make space for focused work.',
        'detail' => 'The workspace is where sketches, experiments, and plans begin to take shape.',
        'outcome' => 'A practical, thoughtful environment helps turn early ideas into useful digital experiences.',
        'images' => [
            ['src' => 'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg', 'alt' => 'A workspace and creative process moment'],
            ['src' => 'uploads/portfolio/f010771f6a2a4d7b3af4b589b6c7ba2e.jpg', 'alt' => 'A focused work-in-progress moment'],
            ['src' => 'uploads/portfolio/f19238011240a10906f2f7d53e223e46.jpg', 'alt' => 'A detail from the workspace'],
        ],
    ],
    [
        'category' => 'awards',
        'title' => 'Awards',
        'type' => 'Recognition & milestones',
        'summary' => 'A rotating set of highlights that celebrate progress, learning, and meaningful milestones.',
        'detail' => 'Milestones mark the effort, practice, and collaboration behind each step forward.',
        'outcome' => 'Every achievement is a reminder to keep learning and creating with care.',
        'images' => [
            ['src' => 'uploads/portfolio/f8395ea72410ef0f652972f4bb04bb4d.jpg', 'alt' => 'A project achievement highlight'],
            ['src' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg', 'alt' => 'A web project milestone'],
            ['src' => 'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg', 'alt' => 'A portfolio milestone moment'],
        ],
    ],
    [
        'category' => 'creative',
        'title' => 'Creative & Innovation',
        'type' => 'Ideas & experiments',
        'summary' => 'A collection of visual ideas and experiments exploring how thoughtful design can make the web more useful.',
        'detail' => 'Creativity is a process of asking questions, exploring possibilities, and making ideas tangible.',
        'outcome' => 'Small experiments open new directions for clear, engaging, and practical web experiences.',
        'images' => [
            ['src' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg', 'alt' => 'A creative web application idea'],
            ['src' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg', 'alt' => 'A web platform design experiment'],
            ['src' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg', 'alt' => 'A web development innovation highlight'],
        ],
    ],
];

$journeyItems = [
    [
        'category' => 'educational',
        'category_label' => 'Educational',
        'title' => 'Education & Learning',
        'icon' => 'fa-graduation-cap',
        'date' => $education[0][0],
        'summary' => $education[0][1],
        'detail' => $education[0][2],
        'features' => ['Education history', 'Qualifications and study', 'Ongoing learning'],
        'outcome' => 'Learning and practice continue to shape the way I approach new challenges.',
        'attachments' => array_map(
            static fn(array $image): array => ['type' => 'image', 'src' => $image['src'], 'alt' => $image['alt'], 'label' => $image['alt']],
            $gallery[1]['images']
        ),
    ],
    [
        'category' => 'experience',
        'category_label' => 'Experience',
        'title' => 'Professional Experience',
        'icon' => 'fa-briefcase',
        'date' => $experience[0][0],
        'summary' => $experience[0][1],
        'detail' => implode(' ', array_map(static fn(array $entry): string => implode(' — ', $entry), $experience)),
        'features' => ['Roles and responsibilities', 'Professional milestones', 'Skills developed through work'],
        'outcome' => 'Explore the roles, responsibilities, and milestones that have shaped my work.',
        'attachments' => array_map(
            static fn(array $image): array => ['type' => 'image', 'src' => $image['src'], 'alt' => $image['alt'], 'label' => $image['alt']],
            $gallery[0]['images']
        ),
    ],
    [
        'category' => 'talent',
        'category_label' => 'Special Talent',
        'title' => 'Special Talent & Creativity',
        'icon' => 'fa-wand-magic-sparkles',
        'date' => 'Skills & interests',
        'summary' => 'A place to highlight creative strengths, personal interests, and special achievements.',
        'detail' => 'Add a description of the talents, creative work, or special accomplishments you would like to share.',
        'features' => ['Creative strengths', 'Personal projects and interests', 'Special achievements'],
        'outcome' => 'Personal strengths and creative practice bring a unique perspective to every project.',
        'attachments' => array_map(
            static fn(array $image): array => ['type' => 'image', 'src' => $image['src'], 'alt' => $image['alt'], 'label' => $image['alt']],
            $gallery[2]['images']
        ),
    ],
];

$journeySettings = $portfolioSettings['portfolio_journey'];
$journeyEyebrow = isset($journeySettings['eyebrow']) && is_string($journeySettings['eyebrow']) && trim($journeySettings['eyebrow']) !== ''
    ? trim($journeySettings['eyebrow'])
    : 'The journey so far';
$journeyTitle = isset($journeySettings['title']) && is_string($journeySettings['title']) && trim($journeySettings['title']) !== ''
    ? trim($journeySettings['title'])
    : 'Experience & Education';
$journeyDescription = isset($journeySettings['description']) && is_string($journeySettings['description']) && trim($journeySettings['description']) !== ''
    ? trim($journeySettings['description'])
    : 'Explore the education, experience, and special talents behind my work.';
$journeyCategoryLabels = ['educational' => 'Educational', 'experience' => 'Experience', 'talent' => 'Special Talent'];
if (array_key_exists('items', $journeySettings) && is_array($journeySettings['items'])) {
    $savedJourneyItems = [];
    foreach (array_slice($journeySettings['items'], 0, 20) as $item) {
        if (!is_array($item) || !isset($item['category'], $item['title'], $item['icon'], $item['date'], $item['summary'], $item['detail'], $item['features'], $item['outcome'], $item['attachments']) ||
            !is_string($item['category']) || !isset($journeyCategoryLabels[$item['category']]) ||
            !is_string($item['title']) || trim($item['title']) === '' ||
            !is_string($item['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $item['icon']) !== 1 ||
            !is_string($item['date']) || !is_string($item['summary']) ||
            !is_string($item['detail']) || !is_array($item['features']) ||
            !is_string($item['outcome']) || !is_array($item['attachments'])) {
            continue;
        }
        $features = array_values(array_filter($item['features'], static fn($feature): bool => is_string($feature) && trim($feature) !== ''));
        $attachments = [];
        foreach (array_slice($item['attachments'], 0, 8) as $attachment) {
            if (!is_string($attachment) || ($src = $validPortfolioAsset($attachment, '')) === '') {
                continue;
            }
            $extension = strtolower((string) pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'], true);
            $label = basename((string) parse_url($src, PHP_URL_PATH));
            $attachments[] = [
                'type' => $isImage ? 'image' : 'file',
                'src' => $src,
                'alt' => $label !== '' ? $label : $item['title'],
                'label' => $label !== '' ? $label : $item['title']
            ];
        }
        $savedJourneyItems[] = [
            'category' => $item['category'],
            'category_label' => $journeyCategoryLabels[$item['category']],
            'title' => trim($item['title']),
            'icon' => $item['icon'],
            'date' => trim($item['date']),
            'summary' => trim($item['summary']),
            'detail' => trim($item['detail']),
            'features' => array_slice($features, 0, 12),
            'outcome' => trim($item['outcome']),
            'attachments' => $attachments,
            'visible' => !isset($item['visible']) || $item['visible'] === true
        ];
    }
    $journeyItems = $savedJourneyItems;
}
$journeyItems = array_values(array_filter($journeyItems, static fn(array $item): bool => !isset($item['visible']) || $item['visible'] === true));

$faqSettings = $portfolioSettings['portfolio_faqs'];
if (isset($faqSettings['title']) && is_string($faqSettings['title']) && trim($faqSettings['title']) !== '') {
    $faqTitle = trim($faqSettings['title']);
} else {
    $faqTitle = 'FAQs.';
}
$savedFaqCategories = is_array($faqSettings['categories'] ?? null) ? $faqSettings['categories'] : [];
foreach ($faqCategories as $categoryKey => $defaultLabel) {
    $savedCategoryLabel = $savedFaqCategories[$categoryKey] ?? null;
    if (is_string($savedCategoryLabel) && trim($savedCategoryLabel) !== '') {
        $faqCategories[$categoryKey] = trim($savedCategoryLabel);
    }
}
if (array_key_exists('items', $faqSettings) && is_array($faqSettings['items'])) {
    $savedFaqItems = [];
    foreach (array_slice($faqSettings['items'], 0, 40) as $faq) {
        if (!is_array($faq) || !isset($faq['category'], $faq['question'], $faq['answer']) ||
            !is_string($faq['category']) || !isset($faqCategories[$faq['category']]) ||
            !is_string($faq['question']) || trim($faq['question']) === '' ||
            !is_string($faq['answer']) || trim($faq['answer']) === '') {
            continue;
        }
        if (isset($faq['visible']) && $faq['visible'] !== true) {
            continue;
        }
        $savedFaqItems[] = [
            'category' => $faq['category'],
            'question' => trim($faq['question']),
            'answer' => trim($faq['answer'])
        ];
    }
    $faqs = $savedFaqItems;
}

$socialPosts = [
    [
        'id' => 'portfolio-update',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg',
        'alt' => 'A recent portfolio project',
        'title' => 'A portfolio built around the work',
        'description' => 'A personal portfolio is more than a collection of pages. It is a place to share what I make, what I am learning, and how to get in touch. I have been refining this site to make those things easier to discover on every screen.',
        'publisher_name' => 'Shumi',
        'publisher_role' => 'Web Developer',
        'published_at' => date(DATE_ATOM),
        'featured' => false,
    ],
    [
        'id' => 'web-platform',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
        'alt' => 'A web platform project',
        'title' => 'Making web platforms easier to use',
        'description' => 'Clear navigation and a thoughtful content structure help people find what they need. I enjoy turning complex information into a responsive experience that feels straightforward to use.',
        'publisher_name' => 'Shumi',
        'publisher_role' => 'Web Developer',
        'published_at' => date(DATE_ATOM),
        'featured' => false,
    ],
    [
        'id' => 'web-application',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
        'alt' => 'A web application project',
        'title' => 'Building useful web applications',
        'description' => 'A good application connects a useful idea with an interface people can understand. This is where design, frontend behavior, and server-side development work together to solve practical problems.',
        'publisher_name' => 'Shumi',
        'publisher_role' => 'Web Developer',
        'published_at' => date(DATE_ATOM),
        'featured' => false,
    ],
];
if (is_array($portfolioSettings['portfolio_social_posts'])) {
    $savedSocialPosts = [];
    foreach ($portfolioSettings['portfolio_social_posts'] as $post) {
        if (!is_array($post) || !isset($post['id'], $post['media_type'], $post['image'], $post['alt'], $post['title'], $post['description']) ||
            !is_string($post['id']) || preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $post['id']) !== 1 ||
            !is_string($post['media_type']) || !in_array($post['media_type'], ['image', 'video'], true) ||
            !is_string($post['image']) || trim($post['image']) === '' ||
            !is_string($post['alt']) || trim($post['alt']) === '' ||
            !is_string($post['title']) || trim($post['title']) === '' ||
            !is_string($post['description']) || trim($post['description']) === '') {
            continue;
        }
        $imageUrl = trim($post['image']);
        $imageScheme = parse_url($imageUrl, PHP_URL_SCHEME);
        $isHttpUrl = filter_var($imageUrl, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) $imageScheme), ['http', 'https'], true);
        $isSafeRelativePath = preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $imageUrl) === 1;
        if (!$isHttpUrl && !$isSafeRelativePath) {
            continue;
        }
        $savedSocialPosts[] = [
            'id' => $post['id'],
            'media_type' => $post['media_type'],
            'image' => $imageUrl,
            'alt' => trim($post['alt']),
            'title' => trim($post['title']),
            'description' => trim($post['description']),
            'publisher_name' => isset($post['publisher_name']) && is_string($post['publisher_name']) && trim($post['publisher_name']) !== '' ? trim($post['publisher_name']) : 'Shumi',
            'publisher_role' => isset($post['publisher_role']) && is_string($post['publisher_role']) && trim($post['publisher_role']) !== '' ? trim($post['publisher_role']) : 'Web Developer',
            'published_at' => isset($post['published_at']) && is_string($post['published_at']) && strtotime($post['published_at']) !== false
                ? date(DATE_ATOM, strtotime($post['published_at']))
                : date(DATE_ATOM),
            'featured' => isset($post['featured']) && $post['featured'] === true,
            'visible' => !isset($post['visible']) || $post['visible'] === true
        ];
    }
    $socialPosts = $savedSocialPosts;
}

$articles = [
    [
        'category' => 'Field Notes',
        'date' => 'June 12, 2025',
        'date_iso' => '2025-06-12',
        'title' => 'Designing for the questions people actually ask',
        'summary' => 'A practical reminder to start with real user needs, not assumptions.',
        'body' => 'Good product work begins by listening. Ask people what they are trying to do, what gets in their way, and what a successful outcome looks like. Those answers create a stronger foundation than adding features because they seem familiar. Start with what people are really trying to accomplish, then make the next step clear, useful, and easy to find.',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg',
        'image_alt' => 'Portfolio project exploring user-focused design',
    ],
    [
        'category' => 'Process',
        'date' => 'June 5, 2025',
        'date_iso' => '2025-06-05',
        'title' => 'A small design system can make a big difference',
        'summary' => 'How a few clear patterns can improve consistency and team momentum.',
        'body' => 'A useful design system does not need to be large. Start with shared typography, spacing, colors, and a handful of repeated components. Document why each pattern exists and make it easy for teammates to suggest improvements. Small, well-used patterns help a product feel cohesive and make everyday design decisions easier.',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
        'image_alt' => 'Web platform project demonstrating consistent design patterns',
    ],
    [
        'category' => 'Learning',
        'date' => 'May 28, 2025',
        'date_iso' => '2025-05-28',
        'title' => 'What I learned from building in the open',
        'summary' => 'Sharing work early can make collaboration clearer and more generous.',
        'body' => 'Showing unfinished work invites better questions sooner. Explain what you are testing, make the current limitations visible, and treat feedback as material for the next iteration rather than a verdict on the first one. Sharing the process creates room to learn together and improve the result while changes are still easy to make.',
        'media_type' => 'image',
        'image' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
        'image_alt' => 'Web application concept shared while in progress',
    ],
];

$portfolioSections = is_array($portfolioSettings['portfolio_sections'] ?? null)
    ? $portfolioSettings['portfolio_sections']
    : [];
$gallerySettings = is_array($portfolioSections['gallery'] ?? null) ? $portfolioSections['gallery'] : [];
$galleryTitle = isset($gallerySettings['title']) && is_string($gallerySettings['title']) && trim($gallerySettings['title']) !== ''
    ? trim($gallerySettings['title'])
    : 'Moments & Milestones';
$galleryDescription = isset($gallerySettings['description']) && is_string($gallerySettings['description']) && trim($gallerySettings['description']) !== ''
    ? trim($gallerySettings['description'])
    : 'Explore the spaces, achievements, and creative ideas behind my work.';
$galleryVisible = !isset($gallerySettings['visible']) || $gallerySettings['visible'] === true;
$galleryCategoryDefaults = ['workspace' => 'Workspace', 'awards' => 'Awards', 'creative' => 'Creative & Innovation'];
if (isset($gallerySettings['items']) && is_array($gallerySettings['items'])) {
    $savedGallery = [];
    foreach (array_slice($gallerySettings['items'], 0, 30) as $item) {
        if (!is_array($item) || !isset($item['category'], $item['title'], $item['type'], $item['summary'], $item['detail'], $item['outcome'], $item['images']) ||
            !is_string($item['category']) || preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $item['category']) !== 1 ||
            !is_string($item['title']) || trim($item['title']) === '' ||
            !is_string($item['type']) || trim($item['type']) === '' ||
            !is_string($item['summary']) || trim($item['summary']) === '' ||
            !is_string($item['detail']) || trim($item['detail']) === '' ||
            !is_string($item['outcome']) || trim($item['outcome']) === '' || !is_array($item['images'])) {
            continue;
        }
        $images = [];
        foreach (array_slice($item['images'], 0, 12) as $image) {
            if (!is_array($image) || !isset($image['src'], $image['alt']) || !is_string($image['src']) || !is_string($image['alt'])) {
                continue;
            }
            $src = $validPortfolioAsset($image['src'], '');
            if ($src === '' || trim($image['alt']) === '') {
                continue;
            }
            $images[] = ['src' => $src, 'alt' => trim($image['alt'])];
        }
        if ($images === []) {
            continue;
        }
        $savedGallery[] = [
            'category' => $item['category'],
            'category_label' => isset($item['category_label']) && is_string($item['category_label']) && trim($item['category_label']) !== ''
                ? trim($item['category_label'])
                : ($galleryCategoryDefaults[$item['category']] ?? ucfirst(str_replace('-', ' ', $item['category']))),
            'title' => trim($item['title']),
            'type' => trim($item['type']),
            'summary' => trim($item['summary']),
            'detail' => trim($item['detail']),
            'outcome' => trim($item['outcome']),
            'images' => $images,
            'visible' => !isset($item['visible']) || $item['visible'] === true
        ];
    }
    $gallery = $savedGallery;
}
$gallery = array_values(array_filter($gallery, static fn(array $item): bool => !isset($item['visible']) || $item['visible'] === true));
$galleryCategories = [];
foreach ($gallery as $item) {
    $galleryCategories[$item['category']] = $item['category_label'] ?? (
        $galleryCategoryDefaults[$item['category']] ?? ucfirst(str_replace('-', ' ', $item['category']))
    );
}

$blogSettings = is_array($portfolioSections['blog'] ?? null) ? $portfolioSections['blog'] : [];
$blogTitle = isset($blogSettings['title']) && is_string($blogSettings['title']) && trim($blogSettings['title']) !== ''
    ? trim($blogSettings['title'])
    : 'Articles & Notes';
$blogDescription = isset($blogSettings['description']) && is_string($blogSettings['description']) && trim($blogSettings['description']) !== ''
    ? trim($blogSettings['description'])
    : 'Ideas, lessons, and reflections from designing and building for the web.';
$blogVisible = !isset($blogSettings['visible']) || $blogSettings['visible'] === true;
if (isset($blogSettings['items']) && is_array($blogSettings['items'])) {
    $savedArticles = [];
    foreach (array_slice($blogSettings['items'], 0, 30) as $article) {
        if (!is_array($article) || !isset($article['category'], $article['date'], $article['date_iso'], $article['title'], $article['summary'], $article['body'], $article['media_type'], $article['image'], $article['image_alt']) ||
            !is_string($article['category']) || trim($article['category']) === '' ||
            !is_string($article['date']) || trim($article['date']) === '' ||
            !is_string($article['date_iso']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $article['date_iso']) !== 1 || strtotime($article['date_iso']) === false ||
            !is_string($article['title']) || trim($article['title']) === '' ||
            !is_string($article['summary']) || trim($article['summary']) === '' ||
            !is_string($article['body']) || trim($article['body']) === '' ||
            !is_string($article['media_type']) || !in_array($article['media_type'], ['image', 'video'], true) ||
            !is_string($article['image']) || !is_string($article['image_alt'])) {
            continue;
        }
        $image = $validPortfolioAsset($article['image'], '');
        if ($image === '' || trim($article['image_alt']) === '' || (isset($article['visible']) && $article['visible'] !== true)) {
            continue;
        }
        $savedArticles[] = [
            'category' => trim($article['category']),
            'date' => trim($article['date']),
            'date_iso' => $article['date_iso'],
            'title' => trim($article['title']),
            'summary' => trim($article['summary']),
            'body' => trim($article['body']),
            'media_type' => $article['media_type'],
            'image' => $image,
            'image_alt' => trim($article['image_alt'])
        ];
    }
    $articles = $savedArticles;
}

$contactSettings = is_array($portfolioSections['contact'] ?? null) ? $portfolioSections['contact'] : [];
$contactSettings = array_replace([
    'visible' => true, 'form_visible' => true, 'social_visible' => true,
    'title' => 'Let’s Work Together',
    'description' => 'Have an idea, a question, or a project in mind? I’d love to hear from you.',
    'phone' => 'Available upon request',
    'info_note' => 'Email is the best way to get in touch.',
    'form_title' => 'Send a Message',
    'social_title' => 'Find me',
    'social_note' => 'These open the platform homepages; profile links can be added when available.',
    'social_links' => [
        ['label' => 'Facebook', 'url' => 'https://www.facebook.com/', 'icon' => 'fa-facebook-f', 'visible' => true],
        ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/', 'icon' => 'fa-tiktok', 'visible' => true],
        ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/', 'icon' => 'fa-linkedin-in', 'visible' => true],
        ['label' => 'Telegram', 'url' => 'https://t.me/', 'icon' => 'fa-telegram', 'visible' => true]
    ]
], $contactSettings);
$contactDefaults = [
    'visible' => true, 'form_visible' => true, 'social_visible' => true,
    'title' => 'Let’s Work Together',
    'description' => 'Have an idea, a question, or a project in mind? I’d love to hear from you.',
    'phone' => 'Available upon request',
    'info_note' => 'Email is the best way to get in touch.',
    'form_title' => 'Send a Message',
    'social_title' => 'Find me',
    'social_note' => 'These open the platform homepages; profile links can be added when available.'
];
foreach ($contactDefaults as $key => $fallback) {
    if ($key === 'visible' || $key === 'form_visible' || $key === 'social_visible') {
        if (!is_bool($contactSettings[$key])) {
            $contactSettings[$key] = $fallback;
        }
    } elseif (!is_string($contactSettings[$key]) || trim($contactSettings[$key]) === '') {
        $contactSettings[$key] = $fallback;
    }
}
$contactSocialLinks = [];
if (is_array($contactSettings['social_links'])) {
    foreach (array_slice($contactSettings['social_links'], 0, 12) as $link) {
        if (!is_array($link) || !isset($link['label'], $link['url'], $link['icon']) ||
            !is_string($link['label']) || trim($link['label']) === '' ||
            !is_string($link['url']) || !filter_var($link['url'], FILTER_VALIDATE_URL) ||
            strtolower((string) parse_url($link['url'], PHP_URL_SCHEME)) !== 'https' ||
            !is_string($link['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $link['icon']) !== 1 ||
            (isset($link['visible']) && $link['visible'] !== true)) {
            continue;
        }
        $contactSocialLinks[] = $link;
    }
}
$searchSettings = is_array($portfolioSections['search'] ?? null) ? $portfolioSections['search'] : [];
$searchVisible = !isset($searchSettings['visible']) || $searchSettings['visible'] === true;
$searchPlaceholder = isset($searchSettings['placeholder']) && is_string($searchSettings['placeholder']) && trim($searchSettings['placeholder']) !== ''
    ? trim($searchSettings['placeholder'])
    : 'Search...';
$themeSettings = is_array($portfolioSections['theme'] ?? null) ? $portfolioSections['theme'] : [];
$themeVisible = !isset($themeSettings['visible']) || $themeSettings['visible'] === true;
$defaultTheme = isset($themeSettings['default']) && in_array($themeSettings['default'], ['dark', 'light', 'black', 'light-black', 'natural', 'light-pure'], true)
    ? $themeSettings['default']
    : 'dark';
$themeColors = ['dark' => '#11131b', 'light' => '#f0fff0', 'black' => '#000000', 'light-black' => '#18181b', 'natural' => '#f4efe6', 'light-pure' => '#ffffff'];
$defaultThemeColor = $themeColors[$defaultTheme];
$nextThemeLabels = ['dark' => 'Light', 'light' => 'Black', 'black' => 'Light Black', 'light-black' => 'Natural', 'natural' => 'Light Pure', 'light-pure' => 'Dark'];
$initialThemeIcon = in_array($defaultTheme, ['dark', 'black'], true) ? '☼' : '☾';
$managedSectionVisibility = [
    'gallery' => $galleryVisible,
    'blog' => $blogVisible,
    'contact' => !empty($contactSettings['visible'])
];
$homeMenuLinks = array_values(array_filter($homeMenuLinks, static function (array $link) use ($managedSectionVisibility): bool {
    $sectionKey = strtolower(trim($link['label'] ?? ''));
    return !array_key_exists($sectionKey, $managedSectionVisibility) || $managedSectionVisibility[$sectionKey];
}));
foreach ($headerFeatureGrids as $grid) {
    $gridUrl = '#feature-' . $grid['id'];
    if ($grid['parent'] === '') {
        $homeMenuLinks[] = [
            'label' => $grid['nav_label'],
            'url' => $gridUrl,
            'filter' => '',
            'children' => []
        ];
        continue;
    }
    $parentPath = array_values(array_filter(
        array_map('trim', explode('>', $grid['parent'])),
        static fn(string $part): bool => $part !== ''
    ));
    if ($parentPath === [] || count($parentPath) > 2) {
        continue;
    }
    $parentIndex = null;
    foreach ($homeMenuLinks as $index => $menuItem) {
        if (strcasecmp(trim($menuItem['label']), $parentPath[0]) === 0) {
            $parentIndex = $index;
            break;
        }
    }
    if ($parentIndex === null) {
        $newParent = [
            'label' => $parentPath[0],
            'url' => $gridUrl,
            'filter' => '',
            'children' => []
        ];
        if (isset($parentPath[1])) {
            $newParent['children'][] = [
                'label' => $parentPath[1],
                'url' => $gridUrl,
                'filter' => '',
                'children' => [[
                    'label' => $grid['nav_label'],
                    'url' => $gridUrl,
                    'filter' => '',
                    'children' => []
                ]]
            ];
        } else {
            $newParent['children'][] = [
                'label' => $grid['nav_label'],
                'url' => $gridUrl,
                'filter' => '',
                'children' => []
            ];
        }
        $homeMenuLinks[] = [
            'label' => $newParent['label'],
            'url' => $newParent['url'],
            'filter' => $newParent['filter'],
            'children' => $newParent['children']
        ];
        continue;
    }
    if (isset($parentPath[1])) {
        $childIndex = null;
        foreach ($homeMenuLinks[$parentIndex]['children'] as $index => $childItem) {
            if (strcasecmp(trim($childItem['label']), $parentPath[1]) === 0) {
                $childIndex = $index;
                break;
            }
        }
        if ($childIndex === null) {
            $homeMenuLinks[$parentIndex]['children'][] = [
                'label' => $parentPath[1],
                'url' => $gridUrl,
                'filter' => '',
                'children' => [[
                    'label' => $grid['nav_label'],
                    'url' => $gridUrl,
                    'filter' => '',
                    'children' => []
                ]]
            ];
        } else {
            if (!isset($homeMenuLinks[$parentIndex]['children'][$childIndex]['children']) ||
                !is_array($homeMenuLinks[$parentIndex]['children'][$childIndex]['children'])) {
                $homeMenuLinks[$parentIndex]['children'][$childIndex]['children'] = [];
            }
            $homeMenuLinks[$parentIndex]['children'][$childIndex]['children'][] = [
                'label' => $grid['nav_label'],
                'url' => $gridUrl,
                'filter' => '',
                'children' => []
            ];
        }
        continue;
    }
    $homeMenuLinks[$parentIndex]['children'][] = [
        'label' => $grid['nav_label'],
        'url' => $gridUrl,
        'filter' => '',
        'children' => []
    ];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$socialShareOrigin = 'http://10.1.12.39';
$socialRequestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/portfolio.php'), PHP_URL_PATH);
if (!is_string($socialRequestPath) || $socialRequestPath === '' || $socialRequestPath[0] !== '/') {
    $socialRequestPath = '/portfolio.php';
}
$socialShareBaseUrl = $socialShareOrigin . $socialRequestPath;
$socialSharePost = null;
$requestedSocialPostId = $_GET['social_post'] ?? '';
if (is_string($requestedSocialPostId)) {
    foreach ($socialPosts as $candidatePost) {
        if ($candidatePost['id'] === $requestedSocialPostId && (!isset($candidatePost['visible']) || $candidatePost['visible'] === true)) {
            $socialSharePost = $candidatePost;
            break;
        }
    }
}
$socialShareTitle = $socialSharePost['title'] ?? ($portfolio['name'] . ' — Portfolio');
$socialShareDescription = $socialSharePost['description'] ?? ('Portfolio of ' . $portfolio['name'] . ', ' . $portfolio['role'] . ' based in ' . $portfolio['location'] . '.');
$socialShareImage = $socialSharePost !== null && $socialSharePost['media_type'] !== 'video'
    ? $socialSharePost['image']
    : $portraitUrl;
if (filter_var($socialShareImage, FILTER_VALIDATE_URL) === false) {
    $socialShareImage = $socialShareOrigin . '/' . ltrim($socialShareImage, '/');
}
$socialShareUrl = $socialShareBaseUrl;
if ($socialSharePost !== null) {
    $socialShareUrl .= '?' . http_build_query(['social_post' => $socialSharePost['id']]);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($defaultTheme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" id="themeColorMeta" content="<?= e($defaultThemeColor) ?>">
  <meta name="description" content="<?= e('Portfolio of ' . $portfolio['name'] . ', ' . $portfolio['role'] . ' based in ' . $portfolio['location'] . '.') ?>">
  <title><?= e($portfolio['name']) ?> — <?= $isGalleryPage ? 'Gallery' : 'Portfolio' ?></title>
  <meta property="og:type" content="<?= $socialSharePost === null ? 'website' : 'article' ?>">
  <meta property="og:title" content="<?= e($socialShareTitle) ?>">
  <meta property="og:description" content="<?= e($socialShareDescription) ?>">
  <meta property="og:url" content="<?= e($socialShareUrl) ?>">
  <meta property="og:image" content="<?= e($socialShareImage) ?>">
  <meta property="og:image:alt" content="<?= e($socialSharePost['alt'] ?? $portfolio['name'] . ' portfolio') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($socialShareTitle) ?>">
  <meta name="twitter:description" content="<?= e($socialShareDescription) ?>">
  <meta name="twitter:image" content="<?= e($socialShareImage) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;family=JetBrains+Mono:wght@400;600&amp;display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://aashish-jha.com.np/assets/css/style.css">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Person",
        "@id": "https://aashish-jha.com.np/#person",
        "name": <?= json_encode($portfolio['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        "url": "https://aashish-jha.com.np",
        "image": <?= json_encode($portraitUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>,
        "jobTitle": <?= json_encode($portfolio['role'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode('Portfolio of ' . $portfolio['name'] . ', a ' . $portfolio['role'] . ' based in ' . $portfolio['location'] . '.', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        "email": <?= json_encode('mailto:' . $portfolio['email'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        "address": {
          "@type": "PostalAddress",
          "addressLocality": <?= json_encode($portfolio['location'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
          "addressCountry": "NP"
        },
        "sameAs": [
          "https://github.com/",
          "https://linkedin.com/",
          "https://facebook.com/",
          "https://instagram.com/aashish_zha0",
          "https://youtube.com/",
          "https://x.com/Aashish_Zha0"
        ],
        "knowsAbout": ["PHP", "MySQL", "JavaScript", "HTML5", "CSS3", "Bootstrap", "SEO", "Web Development"]
      },
      {
        "@type": "WebSite",
        "@id": "https://aashish-jha.com.np/#website",
        "url": "https://aashish-jha.com.np",
        "name": <?= json_encode($portfolio['name'] . ' — Portfolio', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
        "publisher": {
          "@id": "https://aashish-jha.com.np/#person"
        }
      }
    ]
  }
  </script>
  <script>
    (() => {
      const updateAboutPageView = () => {
        const isAboutView = window.location.hash === '#about' || window.location.hash === '#whoIAm';
        const isProjectsView = window.location.hash === '#work';
        document.documentElement.classList.toggle('about-page-view', isAboutView);
        document.documentElement.classList.toggle('projects-page-view', isProjectsView);
      };
      const scrollToAboutTarget = () => {
        const target = document.getElementById(window.location.hash.slice(1));
        if (!target || !['about', 'whoIAm', 'work'].includes(target.id)) return;
        requestAnimationFrame(() => requestAnimationFrame(() => {
          target.scrollIntoView({
            behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block:'start',
          });
        }));
      };
      updateAboutPageView();
      window.addEventListener('hashchange', () => {
        updateAboutPageView();
        scrollToAboutTarget();
      });
      window.addEventListener('DOMContentLoaded', scrollToAboutTarget, {once:true});
    })();
  </script>
  <style>
    :root {
      color-scheme: dark;
      --page:#0f172a; --surface:#1e293b; --raised:#273449; --text:#fff;
      --muted:#94a3b8; --line:rgba(255,255,255,.08); --accent:#22c55e; --gold:#22c55e;
      --accent-ink:#04170b; --purple:#86efac; --shadow:0 24px 80px rgba(2,6,17,.6);
      --header:rgba(15,23,42,.84); font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;
      scroll-behavior:smooth; scroll-padding-top:82px;
    }
    :root[data-theme="light"] {
      color-scheme:light; --page:#f0fff0; --surface:#fffafa; --raised:#e8f7ef;
      --text:#102a43; --muted:#334e68; --line:rgba(16,42,67,.13);
      --accent:#087e9d; --gold:#9a6700; --accent-ink:#fff; --purple:#0e7490;
      --shadow:0 24px 70px rgba(16,42,67,.12); --header:rgba(255,250,250,.9);
      font-family:Poppins,Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;
    }
    :root[data-theme="black"] {
      color-scheme:dark; --page:#000; --surface:#101010; --raised:#1b1b1b;
      --text:#f5f5f5; --muted:#b3b3b3; --line:rgba(255,255,255,.12);
      --accent:#22d3ee; --gold:#fbbf24; --accent-ink:#07131a; --purple:#a5b4fc;
      --shadow:0 24px 80px rgba(0,0,0,.72); --header:rgba(0,0,0,.9);
    }
    :root[data-theme="light-black"] {
      color-scheme:dark; --page:#18181b; --surface:#242428; --raised:#303036;
      --text:#f4f4f5; --muted:#c4c4cc; --line:rgba(255,255,255,.13);
      --accent:#67e8f9; --gold:#fbbf24; --accent-ink:#111827; --purple:#c4b5fd;
      --shadow:0 24px 80px rgba(0,0,0,.45); --header:rgba(24,24,27,.9);
    }
    :root[data-theme="natural"] {
      color-scheme:light; --page:#f4efe6; --surface:#fffaf1; --raised:#e9dfcf;
      --text:#352f27; --muted:#665b4e; --line:rgba(69,55,36,.16);
      --accent:#267765; --gold:#a96d18; --accent-ink:#fff; --purple:#725d8c;
      --shadow:0 24px 70px rgba(69,55,36,.13); --header:rgba(255,250,241,.92);
      font-family:Poppins,Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;
    }
    :root[data-theme="light-pure"] {
      color-scheme:light; --page:#fff; --surface:#fff; --raised:#f3f5f7;
      --text:#111827; --muted:#4b5563; --line:rgba(17,24,39,.14);
      --accent:#007f9e; --gold:#a96d18; --accent-ink:#fff; --purple:#6d5bd0;
      --shadow:0 24px 70px rgba(17,24,39,.1); --header:rgba(255,255,255,.94);
      font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;
    }
    :root[data-theme="light"] body,:root[data-theme="natural"] body,:root[data-theme="light-pure"] body {
      background-image:radial-gradient(ellipse at 12% 8%,rgba(34,211,238,.14),transparent 34%),
        radial-gradient(ellipse at 88% 28%,rgba(251,191,36,.11),transparent 30%),
        linear-gradient(135deg,var(--page) 0%,var(--surface) 48%,var(--raised) 100%);
      background-size:150% 150%;
      background-attachment:fixed;
      animation:light-canvas-flow 24s ease-in-out infinite alternate;
    }
    :root[data-theme="black"] body,:root[data-theme="light-black"] body {
      background-image:radial-gradient(ellipse at 12% 8%,rgba(34,211,238,.09),transparent 36%),
        radial-gradient(ellipse at 88% 28%,rgba(251,191,36,.07),transparent 32%);
      background-size:150% 150%;
      background-attachment:fixed;
      animation:light-canvas-flow 28s ease-in-out infinite alternate;
    }
    @keyframes light-canvas-flow {
      from { background-position:0% 40%; }
      to { background-position:100% 60%; }
    }
    :root[data-theme="light"] main :is(h1,h2,h3,h4),:root[data-theme="natural"] main :is(h1,h2,h3,h4),:root[data-theme="light-pure"] main :is(h1,h2,h3,h4) {
      color:var(--text);
      font-family:Poppins,Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;
      font-weight:750;
    }
    :root[data-theme="light"] main #home .hero-title,:root[data-theme="natural"] main #home .hero-title,:root[data-theme="light-pure"] main #home .hero-title {
      background-image:linear-gradient(105deg,#0891b2 0%,#b77905 48%,#4d7c0f 76%,#0891b2 100%);
      background-clip:text;
      -webkit-background-clip:text;
      color:transparent;
      -webkit-text-fill-color:transparent;
    }
    :root[data-theme="light"] main #home .hero-role #heroRoleTyping,:root[data-theme="natural"] main #home .hero-role #heroRoleTyping,:root[data-theme="light-pure"] main #home .hero-role #heroRoleTyping {
      background-image:linear-gradient(100deg,#0891b2 0%,#b77905 48%,#4d7c0f 76%,#0891b2 100%);
    }
    :root[data-theme="light"] main p,:root[data-theme="natural"] main p,:root[data-theme="light-pure"] main p { color:var(--muted); }
    :root[data-theme="light"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card),:root[data-theme="natural"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card),:root[data-theme="light-pure"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card) {
      border-color:var(--line);
      background-color:var(--surface);
      box-shadow:0 12px 34px color-mix(in srgb,var(--text) 8%,transparent);
    }
    :root[data-theme="light"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card):hover,:root[data-theme="natural"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card):hover,:root[data-theme="light-pure"] main :is(.project,.service-card,.faq-card,.about-value,.article-card,.contact-card,.goal-card,.gallery-card):hover {
      border-color:color-mix(in srgb,var(--accent) 55%,var(--line));
      box-shadow:0 18px 42px color-mix(in srgb,var(--accent) 18%,transparent);
    }
    :root[data-theme="light"] main :is(.eyebrow,.project-category,.service-icon,.faq-category,.article-meta,.contact-icon),:root[data-theme="natural"] main :is(.eyebrow,.project-category,.service-icon,.faq-category,.article-meta,.contact-icon),:root[data-theme="light-pure"] main :is(.eyebrow,.project-category,.service-icon,.faq-category,.article-meta,.contact-icon) {
      color:var(--accent);
    }
    @media(prefers-reduced-motion:reduce) {
      :root[data-theme="light"] body,:root[data-theme="black"] body,:root[data-theme="light-black"] body { animation:none; }
    }
    *,*::before,*::after{box-sizing:border-box}
    body{min-height:100vh;margin:0;display:flex;flex-direction:column;background:var(--page);color:var(--text);line-height:1.6;transition:background .25s,color .25s}
    main{flex:1}
    main>section[hidden]{display:none!important}
    main #home [hidden],main .stats-section[hidden]{display:none!important}
    a{color:inherit} button{font:inherit}
    button:focus-visible,a:focus-visible{outline:3px solid var(--accent);outline-offset:4px}
    .skip{position:fixed;z-index:100;top:10px;left:10px;padding:10px 14px;transform:translateY(-160%);border-radius:8px;color:var(--accent-ink);background:var(--accent)}
    .skip:focus{transform:translateY(0)}
    .progress{position:fixed;z-index:70;inset:0 0 auto;height:3px;transform:scaleX(0);transform-origin:left;background:var(--accent)}
    .header{position:sticky;z-index:60;top:0;border-bottom:1px solid var(--line);background:var(--header);backdrop-filter:blur(18px)}
    .nav{width:min(1160px,calc(100% - 40px));min-height:74px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px}
    .brand{display:flex;align-items:center;gap:11px;text-decoration:none;font-weight:750;letter-spacing:-.03em}
    .brand-mark{width:40px;height:40px;display:grid;place-items:center;border:1px solid rgba(45,212,191,.45);border-radius:12px;color:#fff;background:linear-gradient(135deg,#0891b2,#059669);box-shadow:0 6px 20px rgba(13,148,136,.22);font-size:13px;font-weight:850;letter-spacing:-.04em;transition:transform .2s,box-shadow .2s}
    .brand-logo-image{width:40px;height:40px;display:block;object-fit:contain;border:1px solid rgba(45,212,191,.45);border-radius:12px;background:var(--surface);box-shadow:0 6px 20px rgba(13,148,136,.22);transition:transform .2s,box-shadow .2s}
    .brand:hover .brand-mark,.brand:hover .brand-logo-image{transform:translateY(-2px);box-shadow:0 9px 24px rgba(13,148,136,.32)}
    .nav-right,.nav-links{display:flex;align-items:center}
    .nav-right{gap:16px}.nav-links{gap:clamp(12px,1.5vw,22px)}
    .nav-links>a,.nav-links>.nav-dropdown>summary{position:relative;display:inline-flex;align-items:center;padding:8px 10px;border:1px solid transparent;border-radius:10px;color:var(--muted);font-size:14px;font-weight:700;text-decoration:none;transition:color .25s,background-color .25s,border-color .25s,transform .25s}
    .nav-links>a:after,.nav-links>.nav-dropdown>summary:after{position:absolute;right:50%;bottom:3px;left:50%;height:2px;content:"";border-radius:99px;background:linear-gradient(90deg,#fcd34d,#f59e0b,#fcd34d);background-size:200% 100%;box-shadow:0 0 10px rgba(245,158,11,.5);transition:left .28s cubic-bezier(.2,.8,.2,1),right .28s cubic-bezier(.2,.8,.2,1)}
    .nav-links>a:hover,.nav-links>a:focus-visible,.nav-links>.nav-dropdown>summary:hover,.nav-links>.nav-dropdown[open]>summary{transform:translateY(-1px);border-color:#2dd4bf;background:rgba(20,184,166,.18);color:#fff}
    .nav-links>a[aria-current="location"]{border-color:#2dd4bf;background:linear-gradient(135deg,rgba(6,182,212,.32),rgba(16,185,129,.3));color:#fff;box-shadow:0 0 16px rgba(45,212,191,.2)}
    .nav-links>a:hover:after,.nav-links>a:focus-visible:after,.nav-links>a[aria-current="location"]:after,.nav-links>.nav-dropdown>summary:hover:after,.nav-links>.nav-dropdown>summary:focus-visible:after,.nav-links>.nav-dropdown[open]>summary:after{right:10px;left:10px;animation:nav-line-flow 1.4s linear infinite}
    :root[data-theme="light"] .nav-links>a[aria-current="location"]{border-color:#0f766e;background:linear-gradient(135deg,#0891b2,#059669);color:#fff;box-shadow:0 0 16px rgba(13,148,136,.24)}
    :root[data-theme="light"] .nav-links>a:hover,:root[data-theme="light"] .nav-links>a:focus-visible,:root[data-theme="light"] .nav-links>.nav-dropdown>summary:hover,:root[data-theme="light"] .nav-links>.nav-dropdown[open]>summary{border-color:#0f766e;background:#0f766e;color:#fff}
    @keyframes nav-line-flow{to{background-position:200% 0}}
    .nav-dropdown{position:relative}
    .nav-dropdown>summary{display:flex;align-items:center;gap:7px;cursor:pointer;list-style:none}
    .nav-dropdown>summary::-webkit-details-marker{display:none}
    .nav-dropdown-chevron{font-size:10px;transition:transform .2s}
    .nav-dropdown[open] .nav-dropdown-chevron{transform:rotate(180deg)}
    .nav-dropdown-menu{position:absolute;z-index:90;top:calc(100% + 10px);left:-14px;width:max-content;min-width:190px;max-width:min(320px,calc(100vw - 30px));display:grid;gap:3px;padding:8px;border:1px solid var(--line);border-radius:14px;background:var(--surface);box-shadow:var(--shadow);animation:dropdown-in .16s ease-out}
    .nav-dropdown.is-closing>.nav-dropdown-menu,.nav-subdropdown.is-closing>.nav-subdropdown-menu{animation:dropdown-out .18s ease-in both;pointer-events:none}
    .nav-dropdown-menu a{display:block;padding:9px 11px;border-radius:8px;color:var(--muted);font-size:13px;text-decoration:none;white-space:normal}
    .nav-dropdown-menu a:after{display:none}
    .nav-dropdown-menu a:hover,.nav-dropdown-menu a:focus-visible{transform:none;color:var(--text);background:var(--raised)}
    .nav-subdropdown{position:relative}
    .nav-subdropdown>summary{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:9px 11px;border-radius:8px;color:var(--muted);font-size:13px;cursor:pointer;list-style:none}
    .nav-subdropdown>summary::-webkit-details-marker{display:none}
    .nav-subdropdown>summary:hover,.nav-subdropdown[open]>summary{color:var(--text);background:var(--raised)}
    .nav-subdropdown-chevron{font-size:10px;transition:transform .2s}
    .nav-subdropdown[open] .nav-subdropdown-chevron{transform:rotate(90deg)}
    .nav-subdropdown-menu{position:absolute;z-index:91;top:-8px;left:calc(100% + 8px);width:max-content;min-width:170px;display:grid;gap:3px;padding:8px;border:1px solid var(--line);border-radius:14px;background:var(--surface);box-shadow:var(--shadow);animation:dropdown-in .16s ease-out}
    .nav-subdropdown-menu a{display:block;padding:9px 11px;border-radius:8px;color:var(--muted);font-size:13px;text-decoration:none;white-space:normal}
    .nav-subdropdown-menu a:hover,.nav-subdropdown-menu a:focus-visible{color:var(--text);background:var(--raised)}
    @keyframes dropdown-in{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
    @keyframes dropdown-out{to{opacity:0;transform:translateY(-5px)}}
    .search-wrap{position:relative}
    .search-form{height:40px;display:flex;align-items:center;gap:7px;padding:0 10px;border:1px solid var(--line);border-radius:999px;background:var(--surface)}
    .search-form:focus-within{outline:2px solid var(--accent);outline-offset:3px}
    .search-icon{color:var(--muted);font-size:14px}
    .search-input{width:125px;min-width:0;border:0;outline:0;color:var(--text);background:transparent;font:inherit;font-size:12px}
    .search-input::placeholder{color:var(--muted)}
    .search-results{position:absolute;z-index:80;top:calc(100% + 10px);right:0;width:min(330px,calc(100vw - 30px));max-height: min(60vh,420px);overflow:auto;padding:7px;border:1px solid var(--line);border-radius:15px;background:var(--surface);box-shadow:var(--shadow)}
    .search-results[hidden]{display:none}
    .search-result{width:100%;display:block;padding:10px 12px;border:0;border-radius:9px;color:var(--text);background:transparent;text-align:left;cursor:pointer}
    .search-result:hover,.search-result:focus-visible{background:var(--raised)}
    .search-result small{display:block;color:var(--muted);font-size:11px}
    .search-empty{padding:10px 12px;color:var(--muted);font-size:13px}
    .search-submit{padding:0;border:0;color:var(--muted);background:transparent;font-size:13px;cursor:pointer}
    .search-submit:hover{color:var(--text)}
    .icon-button,.menu-button{width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--line);border-radius:50%;color:var(--text);background:var(--surface);cursor:pointer;transition:color .25s,border-color .25s,transform .25s,box-shadow .25s}
    .icon-button:hover,.menu-button:hover{transform:translateY(-2px);border-color:var(--gold);color:var(--gold);box-shadow:0 5px 18px color-mix(in srgb,var(--gold) 16%,transparent)}
    .menu-button{display:none;border-radius:12px}
    .container{width:min(1080px,calc(100% - 40px));margin-inline:auto}
    .hero{position:relative;isolation:isolate;height:calc(100vh - 74px);height:calc(100svh - 74px);min-height:clamp(520px,calc(100svh - 74px),820px);display:grid;align-items:stretch;overflow:hidden}
    .hero-background-image[hidden]{display:none!important}
    .hero:before,.hero:after{position:absolute;z-index:0;content:"";pointer-events:none}
    .hero:before{inset:0;background-image:linear-gradient(90deg,rgba(15,23,42,.82) 0%,rgba(15,23,42,.58) 38%,rgba(15,23,42,.18) 100%),linear-gradient(0deg,rgba(15,23,42,.28),transparent 58%);background-position:center;background-size:cover}
    :root[data-theme="light"] .hero:before{background-image:linear-gradient(90deg,rgba(240,255,240,.76) 0%,rgba(240,255,240,.48) 38%,rgba(240,255,240,.12) 100%),linear-gradient(0deg,rgba(255,250,250,.18),transparent 58%)}
    .hero-background-image{position:absolute;z-index:-1;inset:-5%;width:110%;height:110%;object-fit:cover;object-position:center;filter:saturate(1.2) brightness(1.12) contrast(1.04);animation:hero-event-zoom 32s ease-in-out infinite alternate}
    :root[data-theme="light"] .hero-background-image{filter:saturate(1.22) brightness(1.12) contrast(1.05)}
    @media(prefers-reduced-motion:reduce){.hero-background-image{animation:none}}
    .hero:after{width:520px;height:520px;right:-180px;bottom:-290px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.18),rgba(163,230,53,.08) 34%,transparent 70%);animation:hero-glow-drift 12s ease-in-out infinite alternate}
    .hero-grid{position:relative;z-index:1;box-sizing:border-box;width:min(100%,1160px);height:100%;margin-inline:auto;padding-inline:20px;display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);align-items:stretch;gap:clamp(32px,5vw,70px)}
    .hero-grid>.reveal:first-child{align-self:center}
    @keyframes hero-event-zoom{from{transform:scale(1.015);background-position:48% 50%}to{transform:scale(1.11);background-position:56% 44%}}
    @keyframes hero-glow-drift{to{transform:translate(-28px,-24px) scale(1.12)}}
    .eyebrow{display:inline-flex;align-items:center;gap:9px;color:var(--accent);font-size:12px;font-weight:750;letter-spacing:.14em;text-transform:uppercase}
    .status{width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 5px color-mix(in srgb,var(--accent) 15%,transparent);animation:pulse 2s infinite}
    h1{max-width:760px;margin:22px 0 18px;font-size:clamp(46px,7vw,82px);line-height:.99;letter-spacing:-.065em}
    .hero-title{background:linear-gradient(105deg,#22d3ee 0%,#fbbf24 48%,#a3e635 76%,#22d3ee 100%);background-size:240% 100%;background-clip:text;-webkit-background-clip:text;color:transparent;-webkit-text-fill-color:transparent;animation:hero-title-fade .9s .12s both,hero-gradient-flow 8s linear .9s infinite}
    @keyframes hero-title-fade{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    @keyframes hero-gradient-flow{to{background-position:240% center}}
    .accent{color:var(--accent)}.typed{display:inline-block;min-height:1.12em;color:var(--purple)}
    .caret{display:inline-block;width:2px;height:.85em;margin-left:4px;vertical-align:-.04em;background:var(--purple);animation:blink 1s steps(2,start) infinite}
    .hero-copy{max-width:590px;color:var(--muted);font-size:clamp(16px,2vw,19px)}
    .hero-copy{min-height:3.2em}
    .hero-role{min-height:1.5em;margin:0;color:var(--accent);font-size:clamp(16px,2vw,21px);font-weight:800;line-height:1.5}
    .hero-role #heroRoleTyping{display:inline-block;background:linear-gradient(100deg,#22d3ee 0%,#fbbf24 48%,#a3e635 76%,#22d3ee 100%);background-size:220% auto;background-clip:text;-webkit-background-clip:text;color:transparent;-webkit-text-fill-color:transparent;animation:hero-gradient-flow 6s linear infinite}
    .visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .hero-actions{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:30px}
    @media(max-width:900px){.hero-actions{grid-template-columns:repeat(2,minmax(0,1fr))}}
    .button{min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 20px;border:1px solid transparent;border-radius:999px;color:var(--accent-ink);background:var(--accent);font-size:14px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .2s,filter .2s}
    .button:hover{transform:translateY(-2px);filter:brightness(1.05)}
    .button.ghost{border-color:var(--line);color:var(--text);background:var(--surface)}
    .hero-actions .button{min-height:46px;padding-inline:12px;font-size:13px;white-space:nowrap}
    .hero-note{display:flex;align-items:center;gap:11px;margin-top:36px;color:var(--muted);font-size:13px}
    .avatar-stack{display:flex;padding-left:8px}.avatar-stack span{width:26px;height:26px;display:grid;place-items:center;margin-left:-8px;border:2px solid var(--page);border-radius:50%;color:#18210a;background:var(--purple);font-size:8px;font-weight:800}
    .avatar-stack span:nth-child(2){background:#f6b982}.avatar-stack span:nth-child(3){background:#8ed3c2}
    .portrait{position:relative;box-sizing:border-box;width:calc(100% - 10px);min-height:0;justify-self:end;align-self:stretch;aspect-ratio:auto;padding:10px;margin-right:10px;border:1px solid color-mix(in srgb,#22d3ee 45%,var(--line));border-radius:12px;background:linear-gradient(145deg,rgba(34,211,238,.18),rgba(251,191,36,.12) 52%,rgba(163,230,53,.16));box-shadow:0 24px 70px rgba(2,6,17,.24);perspective:1400px}
    .portrait.reveal.visible{animation:hero-portrait-slide-in .9s cubic-bezier(.2,.8,.2,1) both}
    @keyframes hero-portrait-slide-in{from{opacity:0;transform:translateX(64px) scale(.96)}to{opacity:1;transform:translateX(0) scale(1)}}
    .portrait-flip{position:absolute;inset:10px;width:calc(100% - 20px);height:calc(100% - 20px);padding:0;overflow:visible;border:0;border-radius:8px;color:var(--text);background:transparent;text-align:left;cursor:pointer;-webkit-tap-highlight-color:transparent}
    .portrait-flip:focus-visible{outline:3px solid #22d3ee;outline-offset:6px}
    .portrait-flip-inner{position:absolute;inset:0;display:block;border:1px solid color-mix(in srgb,var(--accent) 32%,var(--line));border-radius:8px;transform-style:preserve-3d;transition:transform .85s cubic-bezier(.2,.8,.2,1);box-shadow:0 24px 70px rgba(2,6,17,.32)}
    .portrait.is-flipped .portrait-flip-inner{transform:rotateY(180deg)}
    .portrait-face{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;border-radius:7px;backface-visibility:hidden;-webkit-backface-visibility:hidden}
    .portrait-front{background:var(--raised)}
    .portrait-front:after{position:absolute;inset:38% 0 0;content:"";background:linear-gradient(180deg,transparent 0%,rgba(5,15,28,.16) 35%,rgba(5,15,28,.88) 100%);pointer-events:none}
    .portrait-photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 25%;animation:portrait-photo-zoom 22s ease-in-out infinite alternate}
    @keyframes portrait-photo-zoom{from{transform:scale(1)}to{transform:scale(1.075)}}
    .portrait-label{position:relative;z-index:1;padding:24px 25px 22px;color:#fff;font-size:13px;text-shadow:0 2px 12px rgba(0,0,0,.4)}
    .portrait-label strong{display:block;font-size:15px}
    .portrait-flip-hint{position:absolute;z-index:1;top:17px;right:17px;display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid rgba(255,255,255,.4);border-radius:999px;color:#fff;background:rgba(15,23,42,.58);backdrop-filter:blur(10px);font-size:11px;font-weight:750}
    .portrait-back{align-items:flex-start;justify-content:center;gap:15px;padding:clamp(22px,4vw,36px);transform:rotateY(180deg);border:1px solid rgba(34,211,238,.5);color:#eafaff;background:linear-gradient(145deg,rgba(7,25,40,.98),rgba(8,60,78,.97) 55%,rgba(53,76,27,.95));text-align:left}
    .portrait-back-kicker{color:#67e8f9;font-size:10px;font-weight:850;letter-spacing:.16em;text-transform:uppercase}
    .portrait-back-name{margin:0;color:#fff;font-family:Poppins,Inter,sans-serif;font-size:clamp(28px,4vw,42px);font-weight:850;line-height:1.1}
    .portrait-back-role{display:flex;align-items:center;gap:8px;color:#a3e635;font-size:13px;font-weight:750}
    .portrait-back-copy{margin:0;color:rgba(234,250,255,.88);font-size:14px;line-height:1.8}
    .portrait-back-skills{padding-top:12px;border-top:1px solid rgba(103,232,249,.25);color:#67e8f9;font-size:11px;font-weight:700;line-height:1.7}
    .portrait-back-hint{display:inline-flex;align-items:center;gap:8px;margin-top:3px;color:#fbbf24;font-size:11px;font-weight:750}
    .product-ad-grid-panel{position:fixed;z-index:39;top:9vh;right:12px;width:min(380px,calc(100vw - 24px));max-height:82vh;padding:18px;overflow:auto;border:1px solid var(--line);border-radius:20px;color:var(--text);background:var(--page);box-shadow:0 18px 52px rgba(2,6,17,.32);animation:product-ad-panel-in .65s cubic-bezier(.2,.8,.2,1);transition:opacity .35s ease,transform .4s cubic-bezier(.2,.8,.2,1),visibility .35s}
    .product-ad-grid-panel.is-dismissed{visibility:hidden;opacity:0;pointer-events:none;transform:translateX(115%) rotateY(-9deg)}
    .product-ad-grid-panel.is-expanded{max-height:88vh}
    @keyframes product-ad-panel-in{from{opacity:0;transform:translateX(42px) rotateY(-8deg)}to{opacity:1;transform:translateX(0) rotateY(0)}}
    .product-ad-grid-header{position:sticky;z-index:7;top:-18px;display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin:-18px -18px 16px;padding:18px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--page) 94%,transparent);backdrop-filter:blur(14px)}
    .product-ad-grid-header h2{margin:0;font-size:clamp(22px,4vw,30px)}
    .product-ad-grid-header p{margin:7px 0 0;color:var(--muted);font-size:13px}
    .product-ad-popup-actions{display:flex;flex:0 0 auto;gap:7px}
    .product-ad-popup-actions button{min-height:34px;padding:0 11px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font:inherit;font-size:11px;font-weight:750;cursor:pointer;transition:transform .2s,border-color .2s,background .2s}
    .product-ad-popup-actions button:hover,.product-ad-popup-actions button:focus-visible{transform:translateY(-2px);border-color:var(--accent);outline:none}
    .product-ad-popup-actions [data-ad-see-more]{border-color:transparent;color:var(--accent-ink);background:linear-gradient(100deg,#22d3ee,#fbbf24)}
    .product-ad-grid-panel:not(.is-expanded) .product-ad-card:not(:first-child){display:none}
    .product-ad-grid-panel.is-expanded .product-ad-card{animation:product-ad-card-in .55s cubic-bezier(.2,.8,.2,1) backwards}
    .product-ad-grid-panel.is-expanded .product-ad-card:nth-child(2){animation-delay:.06s}
    .product-ad-grid-panel.is-expanded .product-ad-card:nth-child(3){animation-delay:.12s}
    .product-ad-grid-panel.is-expanded .product-ad-card:nth-child(4){animation-delay:.18s}
    .product-ad-section{padding:0}
    .product-ad-intro{display:none}
    .product-ad-grid{display:grid;grid-template-columns:minmax(0,1fr);align-items:stretch;gap:0;perspective:1200px}
    .product-ad-card{position:relative;min-width:0;display:flex;flex-direction:column;overflow:hidden;margin-bottom:-24px;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 14px 40px rgba(2,6,17,.14);transform-origin:top center;transform-style:preserve-3d;transition:transform .38s cubic-bezier(.2,.8,.2,1),border-color .25s,box-shadow .25s}
    .product-ad-card:last-child{margin-bottom:0}
    .product-ad-card:nth-child(2){transform:translateY(-7px) rotateZ(.5deg)}
    .product-ad-card:nth-child(3){transform:translateY(-14px) rotateZ(-.5deg)}
    .product-ad-card:nth-child(4){transform:translateY(-21px) rotateZ(.4deg)}
    @keyframes product-ad-card-in{from{opacity:0;transform:translateX(32px) rotateY(-12deg)}to{opacity:1;transform:translateX(0) rotateY(0)}}
    .product-ad-card:hover,.product-ad-card:focus-within{z-index:8;transform:translateY(-10px) rotateY(-4deg) rotateZ(0) scale(1.015);border-color:color-mix(in srgb,var(--accent) 65%,var(--line));box-shadow:0 22px 48px rgba(2,6,17,.3),var(--shadow)}
    .product-ad-image-wrap{display:grid;place-items:center;width:100%;aspect-ratio:8.5/11;min-height:250px;padding:12px;overflow:hidden;background:color-mix(in srgb,var(--raised) 78%,var(--surface))}
    .product-ad-art{width:100%;height:100%;object-fit:contain;transition:transform .45s cubic-bezier(.2,.8,.2,1)}
    .product-ad-card:hover .product-ad-art,.product-ad-card:focus-within .product-ad-art{transform:scale(1.055)}
    .product-ad-brand{display:flex;align-items:center;gap:10px;min-width:0;padding:16px 18px 0}
    .product-ad-brand-mark{width:36px;height:36px;flex:0 0 auto;display:grid;place-items:center;overflow:hidden;border:1px solid var(--line);border-radius:10px;color:var(--accent);background:var(--raised);font-size:15px}
    .product-ad-brand-mark img{width:100%;height:100%;object-fit:contain;padding:3px}
    .product-ad-brand-name{min-width:0;overflow:hidden;color:var(--muted);font-size:12px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
    .product-ad-label{display:inline-flex;align-items:center;gap:6px;color:var(--accent);font-size:10px;font-weight:850;letter-spacing:.12em;text-transform:uppercase}
    .product-ad-copy{padding:10px 18px 0}
    .product-ad-copy h3{margin:7px 0 8px;color:var(--text);font-size:clamp(18px,2vw,22px);line-height:1.3}
    .product-ad-copy p{margin:0;color:var(--muted);font-size:14px;line-height:1.7;overflow-wrap:anywhere;white-space:pre-wrap}
    .product-ad-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:auto;padding:16px 18px 18px}
    .product-ad-footer>span{color:var(--muted);font-size:11px}
    .product-ad-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
    .product-ad-details,.product-ad-footer a{min-height:38px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 13px;border:1px solid color-mix(in srgb,var(--accent) 38%,var(--line));border-radius:999px;color:var(--accent);background:color-mix(in srgb,var(--accent) 8%,var(--surface));font-size:12px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .product-ad-footer a{border-color:transparent;color:var(--accent-ink);background:linear-gradient(100deg,#22d3ee,#fbbf24)}
    .product-ad-details:hover,.product-ad-footer a:hover{transform:translateY(-2px);border-color:var(--accent);box-shadow:0 6px 16px color-mix(in srgb,var(--accent) 22%,transparent)}
    .product-ad-details:focus-visible,.product-ad-footer a:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
    .product-ad-dialog{position:fixed;inset:0;width:100vw;height:100dvh;max-width:none;max-height:none;margin:0;padding:0;overflow:auto;transform:scale(.985);opacity:0;border:0;border-radius:0;color:var(--text);background:var(--page);box-shadow:none;transition:transform .25s ease,opacity .25s ease}
    .product-ad-dialog.is-open{transform:scale(1);opacity:1}
    .product-ad-dialog::backdrop{background:rgba(5,7,13,.88);backdrop-filter:blur(8px);opacity:0;transition:opacity .25s}
    .product-ad-dialog[open].is-open::backdrop{opacity:1}
    .product-ad-dialog-content{min-height:100%;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr);align-items:center;gap:clamp(22px,5vw,64px);padding:clamp(22px,6vw,72px)}
    .product-ad-dialog-media{display:grid;align-content:center;gap:14px;min-width:0}
    .product-ad-dialog-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
    .product-ad-dialog-top h2{margin:8px 0 0;font-size:clamp(24px,4vw,34px);line-height:1.2}
    .product-ad-slideshow{position:relative;width:100%;height:min(70vh,760px);min-height:280px;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:var(--raised);isolation:isolate}
    .product-ad-slides{position:absolute;inset:0}
    .product-ad-media-slide{position:absolute;inset:0;display:grid;place-items:center;opacity:0;visibility:hidden;transform:translateX(7%) scale(.985);transition:opacity .42s ease,transform .52s cubic-bezier(.2,.8,.2,1),visibility .42s}
    .product-ad-media-slide.is-active{opacity:1;visibility:visible;transform:translateX(0) scale(1)}
    .product-ad-media-slide img,.product-ad-media-slide video{width:100%;height:100%;object-fit:contain;background:var(--raised)}
    .product-ad-slide-controls{position:absolute;inset:auto 12px 48px;z-index:2;display:flex;align-items:center;justify-content:space-between;pointer-events:none}
    .product-ad-slide-step{width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.35);border-radius:50%;color:#fff;background:rgba(15,23,42,.72);backdrop-filter:blur(8px);cursor:pointer;pointer-events:auto}
    .product-ad-slide-step:hover{border-color:var(--accent);color:var(--accent)}
    .product-ad-slide-status{padding:7px 11px;border:1px solid rgba(255,255,255,.25);border-radius:999px;color:#fff;background:rgba(15,23,42,.68);font-size:11px;font-weight:750}
    .product-ad-slide-dots{position:absolute;z-index:2;right:12px;bottom:12px;left:12px;display:flex;justify-content:center;gap:7px}
    .product-ad-slide-dot{width:9px;height:9px;padding:0;border:0;border-radius:999px;background:rgba(255,255,255,.58);cursor:pointer;transition:width .2s,background .2s}
    .product-ad-slide-dot[aria-current="true"]{width:24px;background:#fff}
    .product-ad-dialog-copy{min-width:0}
    .product-ad-dialog-copy p{color:var(--muted);line-height:1.8;white-space:pre-wrap;overflow-wrap:anywhere}
    .product-ad-dialog-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:20px}
    .product-ad-dialog-actions a{min-height:40px;display:inline-flex;align-items:center;justify-content:center;padding:0 15px;border-radius:999px;color:var(--accent-ink);background:linear-gradient(100deg,#22d3ee,#fbbf24);font-size:12px;font-weight:750;text-decoration:none}
    .product-ad-dialog-actions a[hidden]{display:none}
    .product-ad-dialog-close{min-height:40px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font:inherit;font-size:12px;font-weight:750;cursor:pointer}
    @media(max-width:900px){.product-ad-dialog-content{grid-template-columns:1fr;align-content:start}.product-ad-slideshow{height:min(52vh,560px)}}
    @media(max-width:600px){.product-ad-grid-panel{top:auto;right:8px;bottom:8px;width:calc(100vw - 16px);max-height:70dvh;border-radius:18px}.product-ad-grid-panel.is-expanded{max-height:88dvh}.product-ad-grid-header{align-items:flex-start;flex-direction:column}.product-ad-popup-actions{width:100%;justify-content:flex-end}.product-ad-image-wrap{min-height:210px}.product-ad-footer{align-items:flex-start;flex-direction:column}.product-ad-dialog-content{padding:20px}.product-ad-dialog-top h2{font-size:25px}.product-ad-slideshow{height:42vh;min-height:220px}.product-ad-slide-controls{inset:auto 8px 43px}.product-ad-slide-step{width:36px;height:36px}}
    @media(prefers-reduced-motion:reduce){.product-ad-grid-panel,.product-ad-card{animation:none}.product-ad-grid-panel,.product-ad-dialog,.product-ad-card,.product-ad-art,.product-ad-details,.product-ad-footer a,.product-ad-popup-actions button{transition:none}.product-ad-card,.product-ad-card:hover,.product-ad-card:focus-within,.product-ad-art,.product-ad-details:hover,.product-ad-footer a:hover,.product-ad-popup-actions button:hover{transform:none}}
    .stats-section{padding:0 0 36px}
    .stats-row{width:min(920px,100%);display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-inline:auto;padding:21px 12px;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:var(--shadow)}
    .stat-item{position:relative;display:grid;justify-items:center;gap:3px;padding:8px 12px;border-radius:14px;text-align:center;transition:transform .25s,border-color .25s,background .25s,box-shadow .25s}
    .stat-item+.stat-item{border-left:1px solid var(--line)}
    .stats-section.visible .stat-item{animation:stat-item-in .55s cubic-bezier(.2,.8,.2,1) backwards}
    .stats-section.visible .stat-item:nth-child(2){animation-delay:.08s}
    .stats-section.visible .stat-item:nth-child(3){animation-delay:.16s}
    .stats-section.visible .stat-item:nth-child(4){animation-delay:.24s}
    .stat-item:hover{transform:translateY(-5px);background:color-mix(in srgb,var(--accent) 8%,var(--surface));box-shadow:0 10px 24px color-mix(in srgb,var(--accent) 12%,transparent)}
    .stat-item strong{color:var(--accent);font-family:Poppins,Inter,sans-serif;font-size:clamp(23px,3vw,32px);font-weight:800;line-height:1.2;transition:transform .25s}
    .stat-item:hover strong{transform:scale(1.08)}
    .stat-item span{color:var(--muted);font-size:12px;font-weight:650}
    @keyframes stat-item-in{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
    .subscription-button{position:relative;isolation:isolate;overflow:hidden;border-color:rgba(34,211,238,.65);background:linear-gradient(105deg,rgba(34,211,238,.16),rgba(251,191,36,.16));animation:subscription-pulse 2.2s ease-in-out infinite}
    .subscription-button:before{position:absolute;z-index:-1;inset:0;content:"";transform:translateX(-120%);background:linear-gradient(100deg,transparent,rgba(255,255,255,.25),transparent);animation:subscription-shimmer 3s ease-in-out infinite}
    .subscription-button:hover{border-color:#fbbf24;box-shadow:0 0 24px rgba(34,211,238,.25),0 0 30px rgba(251,191,36,.16)}
    .subscription-button[disabled]{animation:none;cursor:default}
    .subscription-field{display:grid;gap:8px;margin:20px 0}
    .subscription-field label{font-size:13px;font-weight:700}
    .subscription-field input{width:100%;min-height:48px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;color:var(--text);background:var(--page);font:inherit}
    .subscription-field input:focus{outline:2px solid var(--accent);outline-offset:2px}
    .subscription-form-status{min-height:24px;margin:8px 0 0;color:var(--muted);font-size:13px}
    .subscription-form-status[data-state="error"]{color:#fb7185}
    .subscription-form-status[data-state="success"]{color:#22c55e}
    .subscription-submit{min-height:44px;padding:0 18px;border:0;border-radius:999px;color:var(--accent-ink);background:linear-gradient(100deg,#22d3ee,#fbbf24);font:inherit;font-weight:800;cursor:pointer}
    .subscription-submit:disabled{opacity:.65;cursor:wait}
    @keyframes subscription-pulse{50%{box-shadow:0 0 0 5px rgba(34,211,238,.08),0 0 24px rgba(251,191,36,.2)}}
    @keyframes subscription-shimmer{55%,100%{transform:translateX(120%)}}
    .section{padding-block:82px;scroll-margin-top:82px}
    .section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:30px}
    .services-scroll-button{width:max-content;min-height:42px;display:flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 24px;padding:0 20px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:999px;color:var(--accent);background:color-mix(in srgb,var(--accent) 9%,var(--surface));font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .services-scroll-button:hover{transform:translateY(-3px);border-color:var(--accent);background:color-mix(in srgb,var(--accent) 15%,var(--surface));box-shadow:0 8px 24px color-mix(in srgb,var(--accent) 16%,transparent)}
    .services-scroll-button i{animation:services-arrow-bob 1.5s ease-in-out infinite}
    @keyframes services-arrow-bob{50%{transform:translateY(3px)}}
    .services-intro{display:grid;justify-items:center;margin-bottom:32px;text-align:center}
    .services-intro h2{margin:0}
    .services-intro>p{max-width:620px;margin:14px 0 0;color:var(--muted)}
    .services-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
    .service-card{min-height:255px;display:flex;flex-direction:column;padding:25px;border:1px solid var(--line);border-radius:20px;background:var(--surface);transition:transform .25s,border-color .25s,box-shadow .25s}
    .service-card:hover{transform:translateY(-6px);border-color:color-mix(in srgb,var(--accent) 55%,var(--line));box-shadow:var(--shadow)}
    #services.visible .service-card{animation:service-card-in .55s cubic-bezier(.2,.8,.2,1) backwards}
    #services.visible .service-card:nth-child(2){animation-delay:.08s}
    #services.visible .service-card:nth-child(3){animation-delay:.16s}
    #services.visible .service-card:nth-child(4){animation-delay:.24s}
    #services.visible .service-card:nth-child(5){animation-delay:.32s}
    @keyframes service-card-in{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .service-icon{width:48px;height:48px;display:grid;place-items:center;margin-bottom:19px;border:1px solid color-mix(in srgb,var(--accent) 26%,var(--line));border-radius:15px;color:var(--accent);background:color-mix(in srgb,var(--accent) 10%,var(--surface));font-size:20px;transition:transform .25s,background .25s}
    .service-card:hover .service-icon{transform:rotate(-5deg) scale(1.08);background:color-mix(in srgb,var(--accent) 17%,var(--surface))}
    .service-card h3{margin:0 0 9px;font-size:19px;letter-spacing:-.025em}
    .service-card>p{margin:0;color:var(--muted);font-size:14px}
    .service-details{display:flex;align-items:center;gap:8px;align-self:flex-start;margin-top:auto;padding:16px 0 0;border:0;color:var(--accent);background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    .service-details i{font-size:11px;transition:transform .2s}
    .service-details[aria-expanded="true"] i{transform:rotate(180deg)}
    .service-detail-copy{margin:12px 0 0!important;padding-top:12px;border-top:1px solid var(--line);color:var(--muted);font-size:13px}
    .service-detail-copy[hidden]{display:none}
    .work-section-head{align-items:center;flex-direction:column;text-align:center}
    .project-empty-state{grid-column:1/-1;margin:0;padding:24px;color:var(--muted);text-align:center}
    .work-section-head>p{max-width:620px}
    .kicker{color:var(--accent);font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
    h2{margin:8px 0 0;font-size:clamp(30px,4.6vw,48px);line-height:1.08;letter-spacing:-.045em}
    .section-head>p{max-width:450px;margin:0;color:var(--muted)}
    .about-section{padding-top:0}
    html.about-page-view body.portfolio-home main>section:not(#about){display:none!important}
    html.about-page-view body.portfolio-home #about{display:block!important}
    html.projects-page-view body.portfolio-home main>section:not(#work){display:none!important}
    html.projects-page-view body.portfolio-home #work{display:block!important}
    #home:target ~ #about{display:none}
    .about-intro{position:relative;display:grid;place-items:center;overflow:hidden;padding:62px 20px 24px;background:radial-gradient(ellipse at 50% 42%,rgba(34,197,94,.12),transparent 42%),linear-gradient(180deg,var(--page),var(--surface));text-align:center}
    .about-intro:before,.about-intro:after{position:absolute;content:"";border:1px solid var(--line);border-radius:50%;pointer-events:none}
    .about-intro:before{width:min(72vw,650px);aspect-ratio:1;opacity:.38}
    .about-intro:after{width:min(54vw,480px);aspect-ratio:1;opacity:.25}
    .about-intro-inner{position:relative;z-index:1;display:grid;justify-items:center;max-width:760px}
    .about-label-button{position:relative;isolation:isolate;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 22px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:999px;color:var(--accent);background:color-mix(in srgb,var(--accent) 10%,var(--surface));font-size:12px;font-weight:750;letter-spacing:.04em;text-decoration:none;box-shadow:0 0 0 0 color-mix(in srgb,var(--accent) 0%,transparent);transition:transform .25s,border-color .25s,background .25s,box-shadow .25s}
    .about-label-button:before{position:absolute;z-index:-1;top:0;bottom:0;left:-80%;width:55%;content:"";transform:skewX(-20deg);background:linear-gradient(105deg,transparent,color-mix(in srgb,#fff 38%,transparent),transparent);transition:left .55s cubic-bezier(.22,1,.36,1)}
    .about-label-button:hover,.about-label-button:focus-visible{transform:translateY(-3px);border-color:var(--accent);background:color-mix(in srgb,var(--accent) 16%,var(--surface));box-shadow:0 10px 32px color-mix(in srgb,var(--accent) 20%,transparent)}
    .about-label-button:hover:before,.about-label-button:focus-visible:before{left:130%}
    .about-label-button:active{transform:translateY(-1px) scale(.98)}
    .about-intro-inner.is-visible .about-label-button{animation:about-button-in .65s cubic-bezier(.22,1,.36,1) backwards,about-button-glow 3.2s ease-in-out 1s infinite}
    .about-down{width:44px;height:44px;display:grid;place-items:center;margin:14px auto 9px;border:1px solid var(--line);border-radius:50%;color:var(--text);background:var(--surface);font-size:16px;text-decoration:none;animation:about-bob 1.8s ease-in-out infinite;transition:color .2s,border-color .2s,transform .2s,box-shadow .2s}
    .about-down:hover{transform:translateY(4px);border-color:var(--accent);color:var(--accent)}
    .about-down:focus-visible{box-shadow:0 0 0 5px color-mix(in srgb,var(--accent) 22%,transparent)}
    .about-intro h2{margin:0;font-size:clamp(38px,7vw,68px);letter-spacing:-.065em}
    .about-intro-inner.is-visible h2{animation:about-heading-in .7s .2s cubic-bezier(.22,1,.36,1) backwards}
    .about-body{padding-top:24px;scroll-margin-top:82px}
    .about-profile-layout{display:grid;grid-template-columns:minmax(260px,.78fr) minmax(0,1.22fr);align-items:center;gap:clamp(28px,6vw,76px)}
    .about-photo-wrap{min-width:0}
    .about-section.visible .about-photo-wrap{animation:about-from-left .75s .08s cubic-bezier(.22,1,.36,1) backwards}
    .about-section.visible .about-statement{animation:about-from-right .75s .18s cubic-bezier(.22,1,.36,1) backwards}
    .about-photo-carousel{position:relative;overflow:hidden;aspect-ratio:.86;border:1px solid var(--line);border-radius:20px;background:var(--raised);box-shadow:var(--shadow);isolation:isolate}
    .about-photo-slide{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 35%;opacity:0;transform:translateX(7%) scale(1.025);transition:opacity .65s ease,transform .85s cubic-bezier(.22,1,.36,1);pointer-events:none}
    .about-photo-slide.is-active{opacity:1;transform:translateX(0) scale(1);pointer-events:auto}
    .about-photo-carousel:hover .about-photo-slide.is-active{transform:translateX(0) scale(1.025)}
    .about-photo-controls{position:absolute;inset:auto 12px 12px;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .about-photo-arrow{width:38px;height:38px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.35);border-radius:50%;color:#fff;background:rgba(15,23,42,.65);backdrop-filter:blur(8px);cursor:pointer;transition:transform .2s,background .2s,border-color .2s}
    .about-photo-arrow:hover{transform:scale(1.08);border-color:var(--accent);background:rgba(15,23,42,.88)}
    .about-photo-dots{display:flex;align-items:center;justify-content:center;gap:7px;padding:7px 10px;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(15,23,42,.58);backdrop-filter:blur(8px)}
    .about-photo-dot{width:8px;height:8px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.55);cursor:pointer;transition:width .2s,background .2s}
    .about-photo-dot[aria-current="true"]{width:21px;border-radius:999px;background:#fff}
    .about-photo-status{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    .about-photo-wrap:focus-within .about-photo-arrow:focus-visible{outline:3px solid var(--accent);outline-offset:3px}
    .about-photo-caption{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:15px}
    .about-photo-caption strong{display:block;color:var(--text);font-size:17px}
    .about-photo-caption span{display:block;color:var(--muted);font-size:13px}
    .about-photo-caption .about-location{color:var(--accent);font-size:12px;font-weight:700;text-align:right}
    .about-statement .kicker{display:inline-flex}
    .about-statement h3{margin:14px 0 16px;font-size:clamp(30px,4.4vw,48px);line-height:1.12;letter-spacing:-.05em}
    .about-description{color:var(--muted);font-size:15px;line-height:1.8}
    .about-description p{margin:0}
    .about-description p+p{margin-top:14px}
    .about-availability{display:inline-flex;align-items:center;gap:9px;margin-top:22px;padding:9px 13px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);font-size:12px}
    .about-availability .status{width:7px;height:7px}
    .about-objectives{margin-top:64px}
    .about-objectives-head{max-width:620px;margin-bottom:20px}
    .about-objectives-head h3{margin:8px 0 0;font-size:clamp(24px,3vw,34px)}
    .about-values{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
    .about-section.visible .about-value{animation:about-card-in .55s cubic-bezier(.22,1,.36,1) backwards}
    .about-section.visible .about-value:nth-child(2){animation-delay:.08s}
    .about-section.visible .about-value:nth-child(3){animation-delay:.16s}
    .about-section.visible .about-value:nth-child(4){animation-delay:.24s}
    .about-value{position:relative;min-height:132px;padding:20px;text-align:left;border:1px solid var(--line);border-radius:18px;color:var(--text);background:var(--surface);cursor:pointer;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .about-value:hover{transform:translateY(-4px);border-color:var(--purple);box-shadow:0 12px 30px color-mix(in srgb,var(--purple) 12%,transparent)}
    .about-value:active{transform:translateY(-1px) scale(.99)}
    .about-value[aria-pressed="true"]{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 8%,var(--surface));box-shadow:0 8px 28px color-mix(in srgb,var(--accent) 9%,transparent)}
    .about-value-check{position:absolute;top:14px;right:14px;width:23px;height:23px;display:grid;place-items:center;transform:scale(.6);border-radius:50%;opacity:0;color:var(--accent-ink);background:var(--accent);font-size:11px;transition:opacity .2s,transform .2s}
    .about-value[aria-pressed="true"] .about-value-check{transform:scale(1);opacity:1}
    .about-value strong{display:block;margin-bottom:8px;font-size:15px}
    .about-value p{margin:0;color:var(--muted);font-size:13px;line-height:1.65}
    .about-value-status{min-height:22px;margin:12px 2px 0;color:var(--muted);font-size:12px}
    @keyframes about-button-in{from{opacity:0;transform:translateY(12px) scale(.94)}to{opacity:1;transform:translateY(0) scale(1)}}
    @keyframes about-button-glow{50%{box-shadow:0 0 0 6px color-mix(in srgb,var(--accent) 8%,transparent)}}
    @keyframes about-heading-in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    @keyframes about-from-left{from{opacity:0;transform:translateX(-22px)}to{opacity:1;transform:translateX(0)}}
    @keyframes about-from-right{from{opacity:0;transform:translateX(22px)}to{opacity:1;transform:translateX(0)}}
    @keyframes about-card-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    .about-footer{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:22px;padding:18px 22px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(110deg,color-mix(in srgb,var(--accent) 10%,var(--surface)),var(--surface) 60%)}
    .about-footer p{margin:0;color:var(--muted);font-size:13px}
    .about-links{display:flex;flex-wrap:wrap;gap:10px}
    .about-link{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--surface);font-size:12px;font-weight:700;text-decoration:none}
    .about-link:hover{border-color:var(--accent);color:var(--accent)}
    @keyframes about-bob{50%{transform:translateY(5px)}}
    .filters{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:20px}
    .filter{min-height:38px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:transparent;cursor:pointer;font-size:13px}
    .filter:hover,.filter.active{border-color:var(--accent);color:var(--accent-ink);background:var(--accent)}
    #work{scroll-margin-top:82px}
    .work-intro{display:grid;justify-items:center;gap:13px;margin:0 auto 30px;text-align:center}
    .work-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;border:1px solid rgba(34,211,238,.48);border-radius:999px;color:#22d3ee;background:rgba(34,211,238,.09);font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .work-scroll-button:hover{transform:translateY(-3px);border-color:#22d3ee;background:rgba(34,211,238,.15);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    .work-scroll-button i{animation:work-arrow-bob 1.5s ease-in-out infinite}
    @keyframes work-arrow-bob{50%{transform:translateY(3px)}}
    .work-intro h2{margin:0;background:linear-gradient(100deg,#22d3ee 8%,#fbbf24 52%,#22d3ee 92%);background-size:220% auto;background-clip:text;color:transparent;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em;animation:work-title-flow 7s linear infinite}
    @keyframes work-title-flow{to{background-position:220% center}}
    .work-intro>p{max-width:660px;margin:0;color:var(--muted);font-size:15px}
    .work-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:3px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.12),rgba(251,191,36,.12))}
    .work-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:work-flow 3.2s ease-in-out infinite}
    @keyframes work-flow{to{transform:translateX(100%)}}
    #work .filters{justify-content:center;margin-bottom:24px}
    #work .filter{transition:transform .2s,border-color .2s,color .2s,background .2s,box-shadow .2s}
    #work .filter:hover{transform:translateY(-2px);border-color:#22d3ee;color:#22d3ee;background:transparent}
    #work .filter[aria-pressed="true"],#work .filter.active{border-color:#22d3ee;color:#06131a;background:linear-gradient(100deg,#22d3ee,#fbbf24);box-shadow:0 7px 22px rgba(34,211,238,.18)}
    .projects{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
    .projects[hidden]{display:none}
    .project{overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--surface);transition:transform .25s,border-color .25s,box-shadow .25s}
    .project:hover{transform:translateY(-5px);border-color:color-mix(in srgb,var(--accent) 55%,var(--line));box-shadow:var(--shadow)}
    .feature-grid-projects{width:min(1120px,100%);grid-template-columns:repeat(auto-fit,minmax(min(100%,310px),1fr));margin-inline:auto}
    .feature-grid-card{display:flex;flex-direction:column;min-width:0;scroll-margin-top:96px;animation:feature-grid-arrive .65s cubic-bezier(.2,.8,.2,1) both}
    .feature-grid-card:nth-child(2){animation-delay:.08s}.feature-grid-card:nth-child(3){animation-delay:.16s}.feature-grid-card:nth-child(4){animation-delay:.24s}
    @keyframes feature-grid-arrive{from{opacity:0;transform:translateY(22px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
    .feature-grid-media{min-height:240px;aspect-ratio:1.45}
    .feature-grid-media video.project-preview{z-index:1}
    .feature-grid-copy{flex:1}
    .feature-grid-images{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin-top:16px}
    .feature-grid-images figure{min-width:0;margin:0;overflow:hidden;border:1px solid var(--line);border-radius:10px;background:var(--raised)}
    .feature-grid-images img{width:100%;height:100px;display:block;object-fit:cover}
    .feature-grid-images figcaption{padding:7px 9px;color:var(--muted);font-size:11px}
    .feature-grid-pdf-link,.feature-grid-details{position:relative;z-index:2;min-height:38px;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin:12px 18px;padding:0 14px;border:1px solid color-mix(in srgb,var(--accent) 40%,var(--line));border-radius:999px;color:var(--accent);background:var(--surface);font-size:12px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .2s,border-color .2s,box-shadow .2s}
    .feature-grid-details{margin:16px 21px 21px;color:var(--accent-ink);border-color:transparent;background:linear-gradient(100deg,#22d3ee,#fbbf24)}
    .feature-grid-details:hover,.feature-grid-pdf-link:hover{transform:translateY(-2px);border-color:var(--accent);box-shadow:0 7px 18px color-mix(in srgb,var(--accent) 20%,transparent)}
    .feature-grid-details:focus-visible,.feature-grid-pdf-link:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
    .header-feature-dialog{position:fixed;inset:0;width:min(1100px,calc(100% - 32px));max-width:none;max-height:min(88dvh,900px);margin:auto;padding:clamp(18px,3vw,34px);overflow:auto;border:1px solid var(--line);border-radius:24px;color:var(--text);background:var(--page);box-shadow:var(--shadow)}
    .header-feature-dialog[open]{animation:feature-dialog-in .32s cubic-bezier(.2,.8,.2,1) both}
    .header-feature-dialog::backdrop{background:rgba(2,6,17,.76);backdrop-filter:blur(8px)}
    @keyframes feature-dialog-in{from{opacity:0;transform:translateY(16px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
    .header-feature-dialog-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:20px}
    .header-feature-dialog-head p{margin:0;color:var(--accent);font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
    .header-feature-dialog-close{min-height:40px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font:inherit;font-weight:700;cursor:pointer}
    .header-feature-dialog-body{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);align-items:start;gap:clamp(20px,4vw,48px)}
    .header-feature-dialog-media{position:relative;min-width:0;min-height:300px;aspect-ratio:1.18;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:var(--raised)}
    .header-feature-dialog-media:after{display:none}
    .header-feature-dialog-media .project-preview{position:static;width:100%;height:100%;min-height:300px;object-fit:contain;transform:none!important;filter:none!important}
    .header-feature-dialog-media .feature-grid-pdf-link{margin:14px}
    .header-feature-dialog-copy{min-width:0}
    .header-feature-dialog-copy .project-body{min-height:0;padding:0}
    .header-feature-dialog-copy h2{margin:10px 0 14px;font-size:clamp(26px,4vw,38px);line-height:1.15}
    .header-feature-dialog-copy>div>p,.header-feature-dialog-copy .feature-grid-copy>p{margin:0;color:var(--muted);font-size:15px;line-height:1.8;white-space:pre-wrap}
    .header-feature-dialog-copy .feature-grid-images{grid-template-columns:repeat(auto-fit,minmax(120px,1fr))}
    .header-feature-dialog-copy .feature-grid-images img{height:130px}
    @media(max-width:760px){.header-feature-dialog-body{grid-template-columns:1fr}.header-feature-dialog-media{min-height:220px;aspect-ratio:1.4}.header-feature-dialog-media .project-preview{min-height:220px}}
    @media(prefers-reduced-motion:reduce){.feature-grid-card,.header-feature-dialog[open]{animation:none}.feature-grid-card,.feature-grid-details,.feature-grid-pdf-link{transition:none}.feature-grid-card:hover,.feature-grid-details:hover,.feature-grid-pdf-link:hover{transform:none}}
    #work.visible .project{animation:project-card-in .55s cubic-bezier(.2,.8,.2,1) backwards}
    #work.visible .project:nth-child(2){animation-delay:.1s}
    #work.visible .project:nth-child(3){animation-delay:.2s}
    @keyframes project-card-in{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .project[hidden]{display:none}
    .project-art{position:relative;min-height:210px;aspect-ratio:1.55;overflow:hidden;background:var(--raised);isolation:isolate}
    .project-art:after{position:absolute;inset:45% 0 0;content:"";background:linear-gradient(transparent,rgba(2,6,17,.42));pointer-events:none}
    .project-preview{width:100%;height:100%;position:absolute;inset:0;display:block;object-fit:cover;transition:transform .7s cubic-bezier(.2,.8,.2,1),filter .4s}
    .project:hover .project-preview{transform:scale(1.06);filter:saturate(1.12)}
    .project-body{min-height:290px;display:flex;flex-direction:column;padding:19px 21px 21px}
    .project-meta{display:flex;justify-content:space-between;gap:12px;color:var(--accent);font-size:11px;font-weight:750;letter-spacing:.12em;text-transform:uppercase}
    .project h3{margin:9px 0 7px;font-size:20px;letter-spacing:-.025em}
    .project p{min-height:48px;margin:0;color:var(--muted);font-size:14px}
    .tags,.dialog-tags{display:flex;flex-wrap:wrap;gap:7px;margin-top:auto;padding-top:15px}
    .dialog-tags{margin-top:15px;padding-top:0}
    .tags span,.dialog-tags span{padding:5px 9px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:11px}
    .project-rating{display:flex;align-items:center;gap:3px;margin-top:14px}
    .project-rating button{padding:3px;border:0;color:var(--muted);background:transparent;font-size:19px;line-height:1;cursor:pointer;transition:color .15s,transform .15s}
    .project-rating button:hover,.project-rating button[data-filled="true"]{color:#fbbf24}
    .project-rating button:hover{transform:scale(1.18)}
    .project-rating-status{margin:4px 0 0;color:var(--muted);font-size:11px}
    .project-actions{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:auto;padding-top:14px}
    .project-open{padding:0;border:0;color:var(--accent);background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    .project-open:hover{text-decoration:underline;text-underline-offset:4px}
    .project-download-link{min-height:36px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 12px;border:1px solid rgba(34,211,238,.4);border-radius:999px;color:#22d3ee;background:rgba(34,211,238,.08);font-size:11px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s}
    .project-download-link:hover{transform:translateY(-2px);border-color:#22d3ee;background:rgba(34,211,238,.15)}
    .project-full{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(0,.92fr);align-items:center;gap:clamp(22px,4vw,48px);margin-top:28px;padding:clamp(18px,3vw,30px);border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:var(--shadow);scroll-margin-top:96px;animation:project-card-in .4s ease both}
    .project-full[hidden]{display:none}
    .project-full-media{position:relative;min-width:0;min-height:300px;aspect-ratio:1.2;overflow:hidden;border-radius:17px;background:#080b12}
    .project-full-media img{width:100%;height:100%;position:absolute;inset:0;display:block;object-fit:cover}
    .project-full-copy{align-self:center}
    .project-full-copy h3{margin:8px 0 14px;font-size:clamp(27px,4vw,40px);line-height:1.12;letter-spacing:-.045em}
    .project-full-copy p{color:var(--muted);font-size:14px;line-height:1.8}
    .project-full-copy p+p{margin-top:12px}
    .project-full-price{display:block;margin-top:16px;padding:13px 15px;border:1px solid rgba(34,211,238,.22);border-radius:12px;color:var(--text);background:linear-gradient(110deg,rgba(34,211,238,.08),rgba(251,191,36,.08));font-size:12px;line-height:1.65}
    .project-full-price strong{color:#fbbf24}
    .project-full-actions{display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-top:18px}
    .project-back{min-height:40px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font-size:12px;font-weight:750;cursor:pointer}
    .project-back:hover{border-color:#22d3ee;color:#22d3ee}
    @media(max-width:760px){.project-full{grid-template-columns:1fr}.project-full-media{min-height:220px;aspect-ratio:1.45}}
    @media(max-width:480px){.project-full{padding:16px}.project-full-media{min-height:190px;aspect-ratio:1.1}.project-full-actions{align-items:stretch;flex-direction:column}.project-full-actions>*{justify-content:center}}
    #work .goals-section{margin-top:76px;scroll-margin-top:96px}
    #work .goals-intro{display:grid;justify-items:center;gap:12px;margin:0 auto 27px;text-align:center}
    #work .goals-intro h3{margin:0;background:linear-gradient(100deg,#67e8f9 8%,#fbbf24 52%,#67e8f9 92%);background-size:220% auto;background-clip:text;-webkit-background-clip:text;color:transparent;font-size:clamp(30px,4.5vw,46px);letter-spacing:-.045em}
    #work.visible .goals-intro h3{animation:work-title-flow 7s linear infinite,project-card-in .6s ease both}
    #work .goals-flow-line{position:relative;width:min(500px,82%);height:3px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.16),rgba(251,191,36,.18))}
    #work .goals-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:work-flow 3.2s ease-in-out infinite}
    #work .goals-intro>p{max-width:660px;margin:0;color:var(--muted);font-size:14px}
    #work .goals-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
    #work .goal-card{position:relative;min-width:0;min-height:240px;padding:23px;border:1px solid var(--line);border-radius:20px;background:linear-gradient(145deg,rgba(34,211,238,.055),var(--surface) 48%,rgba(251,191,36,.045));box-shadow:0 14px 40px rgba(2,6,17,.14);scroll-margin-top:100px;transition:transform .3s,border-color .3s,box-shadow .3s}
    #work.visible .goal-card{animation:project-card-in .55s cubic-bezier(.2,.8,.2,1) var(--goal-delay,0ms) both}
    #work .goal-card:nth-child(2){--goal-delay:90ms}
    #work .goal-card:nth-child(3){--goal-delay:180ms}
    #work .goal-card:hover,#work .goal-card:focus-within{transform:translateY(-6px);border-color:rgba(34,211,238,.62);box-shadow:0 16px 38px rgba(34,211,238,.1),var(--shadow)}
    #work .goal-card:target{border-color:#fbbf24;box-shadow:0 0 0 3px rgba(251,191,36,.13),var(--shadow)}
    #work .goal-card-top{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    #work .goal-card-icon{width:44px;height:44px;flex:0 0 auto;display:grid;place-items:center;border:1px solid rgba(34,211,238,.26);border-radius:14px;color:#67e8f9;background:rgba(34,211,238,.09);font-size:17px;transition:transform .25s,color .25s,border-color .25s}
    #work .goal-card:hover .goal-card-icon,#work .goal-card:focus-within .goal-card-icon{transform:rotate(-6deg) scale(1.08);border-color:#fbbf24;color:#fbbf24}
    #work .goal-card-top h4{margin:0;font-size:19px;letter-spacing:-.025em}
    #work .goal-card-description{margin:0;color:var(--text);font-size:14px;font-weight:650;line-height:1.7}
    #work .goal-card-more{margin:12px 0 0;color:var(--muted);font-size:13px;line-height:1.8}
    #work .goal-card-link{display:inline-flex;align-items:center;gap:8px;margin-top:18px;color:#67e8f9;font-size:12px;font-weight:750;text-decoration:none}
    #work .goal-card-link i{transition:transform .2s}
    #work .goal-card-link:hover{color:#fbbf24}
    #work .goal-card-link:hover i{transform:translateX(4px)}
    @media(max-width:850px){#work .goals-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){#work .goals-section{margin-top:56px}#work .goals-grid{grid-template-columns:1fr}#work .goal-card{min-height:0;padding:20px}}
    @media(prefers-reduced-motion:reduce){#work .goals-flow-line:after{animation:none;transform:none}#work .goal-card,#work .goal-card-icon,#work .goal-card-link i{transition:none}#work .goal-card:hover,#work .goal-card:focus-within{transform:none}}
    .social-intro{display:grid;justify-items:center;padding:66px 20px 34px;text-align:center;background:radial-gradient(ellipse at 50% 0,color-mix(in srgb,var(--accent) 12%,transparent),transparent 65%)}
    .social-scroll-button{width:max-content;min-height:42px;display:flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 23px;padding:0 20px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:999px;color:var(--accent);background:color-mix(in srgb,var(--accent) 9%,var(--surface));font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .social-scroll-button:hover{transform:translateY(-3px);border-color:var(--accent);background:color-mix(in srgb,var(--accent) 15%,var(--surface));box-shadow:0 8px 24px color-mix(in srgb,var(--accent) 16%,transparent)}
    .social-scroll-button i{animation:social-arrow-bob 1.5s ease-in-out infinite}
    @keyframes social-arrow-bob{50%{transform:translateY(3px)}}
    .social-intro h2{margin:0;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em}
    .social-intro>p{max-width:660px;margin:13px 0 0;color:var(--muted);font-size:15px}
    .social-feed{width:min(780px,calc(100% - 40px));display:grid;gap:22px;margin:0 auto}
    .social-post{overflow:hidden;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 14px 40px rgba(2,6,17,.14);transition:transform .25s,border-color .25s,box-shadow .25s}
    .social-post:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--accent) 45%,var(--line));box-shadow:var(--shadow)}
    .social-post.social-entering{animation:social-post-in .55s cubic-bezier(.2,.8,.2,1) var(--social-delay,0ms) both}
    @keyframes social-post-in{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .social-post-header{display:flex;align-items:center;gap:12px;padding:17px 20px}
    .social-avatar{width:42px;height:42px;display:grid;place-items:center;flex:0 0 auto;border-radius:50%;color:var(--accent-ink);background:linear-gradient(135deg,var(--accent),var(--purple));font-size:13px;font-weight:800}
    .social-author{min-width:0}
    .social-author strong{display:block;font-size:14px}
    .social-author span,.social-author time{display:block;color:var(--muted);font-size:11px}
    .social-author .social-featured-badge{display:inline-flex;width:max-content;align-items:center;gap:5px;margin-top:5px;padding:3px 8px;border:1px solid rgba(251,191,36,.4);border-radius:999px;color:#fbbf24;font-size:10px;font-weight:800;letter-spacing:.04em}
    .social-author time{margin-top:2px;font-variant-numeric:tabular-nums}
    .social-post-image{width:100%;max-height:520px;display:block;object-fit:cover;background:var(--raised);cursor:zoom-in;transition:filter .25s,transform .5s}
    img.social-post-image:hover{transform:scale(1.015);filter:saturate(1.08)}
    img.social-post-image:focus-visible{outline:3px solid var(--accent);outline-offset:-3px}
    video.social-post-image{cursor:default}
    .social-post-copy{padding:18px 20px 10px}
    .social-post-copy h3{margin:0 0 8px;font-size:19px;letter-spacing:-.025em}
    .social-post-copy p{display:-webkit-box;margin:0;overflow:hidden;color:var(--muted);font-size:14px;line-height:1.75;-webkit-box-orient:vertical;-webkit-line-clamp:3}
    .social-post-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:9px 20px 5px}
    .social-share-menu{position:relative;margin-left:auto}
    .social-share-menu>summary{min-height:38px;display:inline-flex;align-items:center;gap:8px;padding:0 12px;border:1px solid var(--line);border-radius:9px;color:var(--muted);background:var(--surface);font-size:13px;font-weight:650;cursor:pointer;list-style:none;transition:color .18s,border-color .18s,background .18s,transform .18s}
    .social-share-menu>summary::-webkit-details-marker{display:none}
    .social-share-menu>summary:hover,.social-share-menu[open]>summary{transform:translateY(-1px);border-color:var(--accent);color:var(--accent);background:color-mix(in srgb,var(--accent) 9%,var(--surface))}
    .social-share-menu>summary:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
    .social-share-dropdown{position:absolute;z-index:20;right:0;bottom:calc(100% + 6px);min-width:210px;display:grid;gap:3px;padding:7px;border:1px solid var(--line);border-radius:12px;background:var(--surface);box-shadow:var(--shadow);animation:dropdown-in .16s ease-out}
    .social-share-dropdown .social-action{width:100%;justify-content:flex-start}
    .social-share-dropdown .social-action:hover{transform:none}
    .social-post-more-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:5px 20px 9px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--raised) 34%,var(--surface))}
    .social-action{position:relative;min-height:38px;display:inline-flex;align-items:center;gap:8px;padding:0 11px;border:0;border-radius:9px;color:var(--muted);background:transparent;font-size:13px;font-weight:650;text-decoration:none;cursor:pointer;transition:color .18s,background .18s,transform .18s}
    .social-action:hover{transform:translateY(-1px);color:var(--accent);background:color-mix(in srgb,var(--accent) 9%,var(--surface))}
    .social-action:focus-visible,.social-read-full:focus-visible,.social-full-post-close:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
    .social-action[aria-pressed="true"]{color:#fb7185}
    .social-see-more{min-height:30px;padding:0 8px;border:0;color:var(--accent);background:transparent;font-size:11px;font-weight:750;cursor:pointer}
    .social-see-more:hover{color:var(--gold);text-decoration:underline}
    .social-see-more:focus-visible,.social-comment-actions button:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
    .social-activity-details{padding:10px 20px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--raised) 45%,var(--surface))}
    .social-activity-details[hidden]{display:none}
    .social-activity-details h4{margin:0 0 7px;font-size:12px}
    .social-activity-details ul{display:flex;flex-wrap:wrap;gap:6px;margin:0;padding:0;list-style:none}
    .social-activity-details li{padding:4px 9px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:11px}
    .social-action .fa-heart{transition:transform .18s}
    .social-action[aria-pressed="true"] .fa-heart{transform:scale(1.15)}
    .social-action.is-liking .fa-heart{animation:social-heart-pop .45s cubic-bezier(.2,.8,.2,1)}
    .social-like-burst{position:absolute;top:50%;left:18px;color:#fb7185;font-size:18px;pointer-events:none;animation:social-heart-float .65s ease-out forwards}
    @keyframes social-heart-pop{50%{transform:scale(1.65) rotate(-12deg)}}
    @keyframes social-heart-float{to{transform:translate(5px,-28px) scale(1.4);opacity:0}}
    .social-comments{padding:14px 20px 4px}
    .social-comments[hidden],.social-comment-form[hidden]{display:none!important}
    .social-comment-status{margin:0 0 10px;color:var(--muted);font-size:12px}
    .social-comment-list{display:grid;gap:10px}
    .social-comment{padding:10px 12px;border-radius:12px;background:var(--raised);animation:social-comment-in .3s ease both}
    .social-comment[hidden]{display:none}
    @keyframes social-comment-in{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:translateY(0)}}
    .social-comment strong{display:block;margin-bottom:3px;font-size:12px}
    .social-comment p{margin:0;color:var(--muted);font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}
    .social-comment time{display:block;margin-top:5px;color:var(--muted);font-size:10px}
    .social-comment-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:9px}
    .social-comment-actions>button,.social-comment-reactions button{min-height:28px;padding:3px 8px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);font:inherit;font-size:11px;cursor:pointer}
    .social-comment-actions>button:hover,.social-comment-reactions button:hover,.social-comment-reactions button[aria-pressed="true"]{border-color:var(--accent);color:var(--accent)}
    .social-comment-reactions{display:flex;flex-wrap:wrap;gap:4px}
    .social-comment-reactions button{min-width:30px;padding-inline:5px;font-size:14px}
    .social-comment-reaction-summary{min-height:17px;margin-top:6px!important;font-size:11px!important}
    .social-comment-replies{display:grid;gap:6px;margin:9px 0 0 12px;padding-left:10px;border-left:2px solid var(--line)}
    .social-comment-reply{font-size:12px!important}
    .social-comment-reply strong{display:inline;margin:0}
    .social-comment-reply-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:7px;margin-top:8px}
    .social-comment-reply-form[hidden]{display:none}
    .social-comment-reply-form input{min-width:0;padding:7px 9px;border:1px solid var(--line);border-radius:8px;color:var(--text);background:var(--page);font:inherit;font-size:12px}
    .social-comment-reply-form button{padding:6px 10px;border:0;border-radius:8px;color:var(--accent-ink);background:var(--accent);font-size:11px;font-weight:700;cursor:pointer}
    .social-comment-reply-form button:disabled,.social-comment-actions button:disabled{opacity:.6;cursor:wait}
    .social-inline-edit-form{display:grid;grid-template-columns:1fr auto auto;gap:7px;margin-top:8px}
    .social-inline-edit-form[hidden]{display:none}
    .social-inline-edit-form textarea{grid-column:1/-1;width:100%;min-height:64px;padding:8px 10px;border:1px solid var(--line);border-radius:8px;color:var(--text);background:var(--page);font:inherit;font-size:12px;resize:vertical}
    .social-inline-edit-form button{min-height:30px;padding:5px 10px;border:1px solid var(--line);border-radius:8px;color:var(--text);background:var(--surface);font-size:11px;font-weight:700;cursor:pointer}
    .social-inline-edit-form button[type="submit"]{border-color:var(--accent);color:var(--accent-ink);background:var(--accent)}
    .social-inline-edit-form button:disabled{opacity:.6;cursor:wait}
    .social-comment-form{display:grid;grid-template-columns: minmax(0,1fr) auto;gap:9px;padding:14px 20px 18px}
    .social-comment-fields{display:grid;gap:8px}
    .social-comment-form input,.social-comment-form textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;color:var(--text);background:var(--page);font:inherit;font-size:13px}
    .social-comment-form textarea{min-height:70px;resize:vertical}
    .social-comment-form button{align-self:end;min-height:40px;padding:0 15px;border:0;border-radius:10px;color:var(--accent-ink);background:var(--accent);font-size:12px;font-weight:750;cursor:pointer}
    .social-comment-form button:disabled{opacity:.6;cursor:wait}
    .social-feed-error{margin:0;color:#fca5a5;font-size:13px}
    .social-full-post{width:min(1100px,calc(100% - 40px));margin:28px auto 0;padding:clamp(20px,4vw,32px);border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:var(--shadow);scroll-margin-top:96px;animation:social-post-in .35s ease both}
    .social-full-post[hidden]{display:none}
    .social-full-post-top{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}
    .social-full-post-top h3{margin:7px 0 16px;font-size:clamp(22px,4vw,30px);letter-spacing:-.035em}
    .social-full-post-close{flex:0 0 auto;padding:8px 12px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font:inherit;font-size:12px;font-weight:700;cursor:pointer}
    .social-full-post-close:hover{border-color:var(--accent);color:var(--accent)}
    .social-full-post-media{width:min(100%,960px);height:clamp(220px,42vw,560px);max-height:65vh;display:grid;place-items:center;margin:0 auto 22px;overflow:hidden;border-radius:14px;background:#080b12}
    .social-full-post-media img,.social-full-post-media video{width:100%;height:100%;display:block;object-fit:contain;object-position:center}
    .social-full-post-copy{max-width:960px;margin:0 auto;color:var(--muted);font-size:16px;line-height:1.9;white-space:pre-wrap}
    @media(max-width:560px){.social-full-post{width:calc(100% - 28px);padding:18px}.social-full-post-top{flex-wrap:wrap}.social-full-post-close{margin-left:auto}.social-full-post-media{height:clamp(210px,64vw,340px);margin-bottom:17px}}
    .gallery-section{padding-top:55px}
    body.portfolio-home #gallery{display:none!important}
    body.portfolio-gallery-page main>section:not(#gallery){display:none!important}
    .gallery-intro{display:grid;justify-items:center;margin-bottom:34px;text-align:center}
    .gallery-intro h2{margin:0;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em}
    .gallery-intro>p{max-width:650px;margin:13px 0 0;color:var(--muted);font-size:15px}
    .gallery-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 10px;padding:0 20px;border:1px solid rgba(34,211,238,.48);border-radius:999px;color:#22d3ee;background:rgba(34,211,238,.09);font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,box-shadow .2s}
    .gallery-scroll-button:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    .gallery-scroll-button i{animation:gallery-arrow-bob 1.5s ease-in-out infinite}
    .gallery-intro h2{background:linear-gradient(100deg,#67e8f9 12%,#fbbf24 52%,#67e8f9 88%);background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent}
    .gallery-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:8px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.12),rgba(251,191,36,.12))}
    .gallery-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:gallery-flow 3.2s ease-in-out infinite}
    #gallery.visible .gallery-intro h2{animation:gallery-title-flow 7s linear infinite, gallery-in .7s .12s both}
    #gallery.visible .gallery-intro>p{animation:gallery-in .6s .2s both}
    #gallery.visible .gallery-flow-line{animation:gallery-in .6s .3s both}
    .gallery-category-filters{display:flex;justify-content:center;flex-wrap:wrap;gap:9px;margin:0 auto 26px}
    .gallery-category-filter{min-height:40px;padding:0 17px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);font-size:12px;font-weight:750;cursor:pointer;transition:transform .2s,border-color .2s,color .2s,background .2s,box-shadow .2s}
    #gallery.visible .gallery-category-filter{animation:gallery-in .45s cubic-bezier(.2,.8,.2,1) var(--gallery-delay,0ms) both}
    .gallery-category-filter:hover{transform:translateY(-2px);border-color:#22d3ee;color:#22d3ee}
    .gallery-category-filter[aria-pressed="true"]{border-color:#22d3ee;color:#06131a;background:linear-gradient(100deg,#22d3ee,#fbbf24);box-shadow:0 7px 22px rgba(34,211,238,.18)}
    .gallery-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
    .gallery-grid[hidden]{display:none}
    .gallery-card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 14px 40px rgba(2,6,17,.14);transition:transform .3s,border-color .3s,box-shadow .3s}
    .gallery-card.is-entering{animation:gallery-in .55s cubic-bezier(.2,.8,.2,1) var(--gallery-delay,0ms) both}
    .gallery-card[hidden]{display:none}
    .gallery-card:hover{transform:translateY(-7px) scale(1.012);border-color:rgba(34,211,238,.65);box-shadow:0 20px 55px rgba(34,211,238,.12),var(--shadow)}
    .gallery-slideshow{position:relative;aspect-ratio:1.25;overflow:hidden;background:var(--raised);isolation:isolate}
    .gallery-slide{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transform:translateX(6%) scale(1.035);transition:opacity .7s ease,transform .9s cubic-bezier(.22,1,.36,1);pointer-events:none}
    .gallery-slide.is-active{opacity:1;transform:translateX(0) scale(1);pointer-events:auto}
    .gallery-slideshow:hover .gallery-slide.is-active{transform:scale(1.045)}
    .gallery-slideshow:after{position:absolute;inset:45% 0 0;z-index:1;content:"";background:linear-gradient(transparent,rgba(0,0,0,.45));pointer-events:none}
    .gallery-image-caption{position:absolute;z-index:2;top:12px;left:12px;max-width:calc(100% - 24px);overflow:hidden;padding:6px 10px;border:1px solid rgba(255,255,255,.25);border-radius:999px;color:#fff;background:rgba(15,23,42,.58);backdrop-filter:blur(8px);font-size:10px;font-weight:650;text-overflow:ellipsis;white-space:nowrap;pointer-events:none}
    .gallery-slideshow-controls{position:absolute;z-index:2;inset:auto 11px 11px;display:flex;align-items:center;justify-content:space-between;gap:10px}
    .gallery-arrow-button{width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.38);border-radius:50%;color:#fff;background:rgba(15,23,42,.62);backdrop-filter:blur(8px);cursor:pointer;transition:transform .2s,background .2s}
    .gallery-arrow-button:hover{transform:scale(1.08);background:rgba(15,23,42,.9)}
    .gallery-dots{display:flex;align-items:center;gap:6px;padding:6px 9px;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(15,23,42,.55);backdrop-filter:blur(8px)}
    .gallery-dot{width:7px;height:7px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.58);cursor:pointer;transition:width .2s,background .2s}
    .gallery-dot[aria-current="true"]{width:18px;border-radius:99px;background:#fff}
    .gallery-card-copy{padding:18px 20px 20px}
    .gallery-card-copy small{color:var(--accent);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .gallery-card-copy h3{margin:5px 0 8px;font-size:20px;letter-spacing:-.03em}
    .gallery-card-copy p{margin:0;color:var(--muted);font-size:13px;line-height:1.7}
    .gallery-open{display:inline-flex;align-items:center;gap:8px;margin-top:16px;padding:0;border:0;color:var(--accent);background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    .gallery-open i{transition:transform .2s}
    .gallery-open:hover i{transform:translateX(4px)}
    .gallery-full{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:clamp(24px,5vw,56px);align-items:center;margin-top:30px;padding:clamp(20px,4vw,36px);border:1px solid rgba(34,211,238,.36);border-radius:24px;background:linear-gradient(135deg,rgba(34,211,238,.07),rgba(251,191,36,.045) 42%,var(--surface) 70%);box-shadow:0 20px 70px rgba(34,211,238,.08),var(--shadow);scroll-margin-top:100px;animation:gallery-in .4s ease both}
    .gallery-full[hidden]{display:none}
    .gallery-full-media{position:relative;min-width:0;aspect-ratio:1.12;overflow:hidden;border-radius:17px;background:#080b12;box-shadow:0 18px 50px rgba(0,0,0,.25)}
    .gallery-full-media .gallery-slide{object-fit:contain;background:#080b12}
    .gallery-full-copy{align-self:center}
    .gallery-full-copy h3{margin:8px 0 14px;font-size:clamp(28px,4vw,42px);line-height:1.12;letter-spacing:-.045em}
    .gallery-full-copy p{color:var(--muted);font-size:15px;line-height:1.8}
    .gallery-full-copy p+p{margin-top:13px}
    .gallery-back{min-height:42px;display:inline-flex;align-items:center;gap:9px;margin-top:12px;padding:0 16px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font-size:12px;font-weight:750;cursor:pointer;transition:border-color .2s,transform .2s}
    .gallery-back:hover{transform:translateY(-2px);border-color:#22d3ee}
    .gallery-status{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @keyframes gallery-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @keyframes gallery-arrow-bob{50%{transform:translateY(3px)}}
    @keyframes gallery-flow{50%{transform:translateX(0)}to{transform:translateX(100%)}}
    @keyframes gallery-title-flow{to{background-position:200% center}}
    @media(prefers-reduced-motion:reduce){.gallery-scroll-button i,.gallery-flow-line:after{animation:none}.gallery-flow-line:after{transform:none}.gallery-card,.gallery-slide,.gallery-category-filter,.gallery-back{transition:none}.gallery-card:hover{transform:none}}
    .article-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
    .article-card{padding:22px;border:1px solid var(--line);border-radius:18px;background:var(--surface);transition:transform .2s,border-color .2s}
    .article-card:hover{transform:translateY(-4px);border-color:var(--purple)}
    .article-date{color:var(--accent);font-size:10px;font-weight:800;letter-spacing:.11em}
    .article-card h3{margin:12px 0 8px;font-size:18px;line-height:1.3}
    .article-card>p{margin:0;color:var(--muted);font-size:13px}
    .article-card details{margin-top:15px}
    .article-card summary{color:var(--purple);font-size:13px;font-weight:750;cursor:pointer}
    .article-card details p{margin:10px 0 0;color:var(--muted);font-size:13px}
    #blog .blog-intro{display:grid;justify-items:center;gap:12px;margin-bottom:30px;text-align:center}
    #blog .blog-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;border:1px solid rgba(34,211,238,.48);border-radius:999px;color:#67e8f9;background:rgba(34,211,238,.09);font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    #blog .blog-scroll-button:hover{transform:translateY(-3px);border-color:#fbbf24;background:rgba(34,211,238,.15);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    #blog .blog-scroll-button i{animation:blog-arrow-bob 1.5s ease-in-out infinite}
    #blog .blog-intro h2{margin:0;background:linear-gradient(100deg,#67e8f9 8%,#fbbf24 52%,#67e8f9 92%);background-size:220% auto;background-clip:text;-webkit-background-clip:text;color:transparent;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em}
    #blog.visible .blog-intro h2{animation:blog-title-flow 7s linear infinite,blog-reveal .7s .08s both}
    #blog .blog-intro>p{max-width:660px;margin:0;color:var(--muted);font-size:15px}
    #blog .blog-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:3px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.16),rgba(251,191,36,.18))}
    #blog .blog-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:blog-flow 3.2s ease-in-out infinite}
    #blog .blog-article-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:20px}
    #blog .blog-article-grid[hidden]{display:none}
    #blog .blog-article{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 14px 40px rgba(2,6,17,.14);transition:transform .3s,border-color .3s,box-shadow .3s}
    #blog.visible .blog-article{animation:blog-reveal .55s cubic-bezier(.2,.8,.2,1) var(--blog-delay,0ms) both}
    #blog .blog-article:hover{transform:translateY(-6px);border-color:rgba(34,211,238,.6);box-shadow:var(--shadow)}
    #blog .blog-article-media{position:relative;aspect-ratio:2.45;max-height:140px;overflow:hidden;background:var(--raised)}
    #blog .blog-article-media img,#blog .blog-article-media video{width:100%;height:100%;position:absolute;inset:0;display:block;object-fit:cover;transition:transform .65s cubic-bezier(.2,.8,.2,1),filter .4s}
    #blog .blog-article:hover .blog-article-media img{transform:scale(1.055);filter:saturate(1.12)}
    #blog .blog-article-copy{min-height:250px;display:flex;flex-direction:column;padding:17px 19px 19px}
    #blog .blog-article-byline{display:flex;align-items:center;justify-content:space-between;gap:12px;color:var(--muted);font-size:11px}
    #blog .blog-article-byline strong{color:var(--text);font-size:12px}
    #blog .blog-article-category{display:inline-block;margin-top:12px;color:#22d3ee;font-size:10px;font-weight:800;letter-spacing:.13em;text-transform:uppercase}
    #blog .blog-article-copy h3{margin:6px 0 8px;font-size:19px;line-height:1.4;letter-spacing:-.025em}
    #blog .blog-article-copy>p{margin:0;color:var(--muted);font-size:13px;line-height:1.75}
    #blog .blog-read-more{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;margin-top:auto;padding:16px 0 0;border:0;color:#22d3ee;background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    #blog .blog-read-more i{transition:transform .2s}
    #blog .blog-read-more:hover i{transform:translateX(4px)}
    #blog .blog-full{display:grid;grid-template-columns:minmax(0,.55fr) minmax(0,1.45fr);align-items:start;gap:clamp(22px,4vw,48px);margin-top:28px;padding:clamp(18px,3vw,30px);border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:var(--shadow);scroll-margin-top:96px;animation:blog-reveal .4s ease both}
    #blog .blog-full[hidden]{display:none}
    #blog .blog-full-media{position:relative;width:min(100%,280px);min-height:165px;aspect-ratio:1.65;overflow:hidden;border-radius:17px;background:#080b12}
    #blog .blog-full-media img,#blog .blog-full-media video{width:100%;height:100%;position:absolute;inset:0;display:block;object-fit:cover}
    #blog .blog-full-copy{align-self:start;min-width:0}
    #blog .blog-full-copy h3{margin:7px 0 10px;font-size:clamp(28px,4vw,42px);line-height:1.12;letter-spacing:-.045em}
    #blog .blog-full-meta{display:flex;align-items:center;flex-wrap:wrap;gap:14px;color:var(--muted);font-size:12px}
    #blog .blog-full-meta span{display:inline-flex;align-items:center;gap:7px}
    #blog .blog-full-meta i{color:#22d3ee}
    #blog .blog-full-body{margin-top:18px;color:var(--muted);font-size:15px;line-height:1.9}
    #blog .blog-full-share-row{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:24px;padding-top:16px;border-top:1px solid var(--line)}
    #blog .blog-share-button,#blog .blog-social-link{width:38px;height:38px;display:inline-grid;place-items:center;border:1px solid var(--line);border-radius:50%;color:var(--muted);background:var(--raised);font-size:14px;text-decoration:none;cursor:pointer;transition:transform .2s,border-color .2s,color .2s,background .2s}
    #blog .blog-share-button:hover,#blog .blog-social-link:hover{transform:translateY(-2px);border-color:#22d3ee;color:#22d3ee}
    #blog .blog-social-links{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
    #blog .blog-back{min-height:38px;margin-top:20px;padding:0 14px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font-size:12px;font-weight:750;cursor:pointer;transition:border-color .2s,color .2s}
    #blog .blog-back:hover{border-color:#22d3ee;color:#22d3ee}
    @keyframes blog-arrow-bob{50%{transform:translateY(3px)}}
    @keyframes blog-flow{50%{transform:translateX(0)}to{transform:translateX(100%)}}
    @keyframes blog-title-flow{to{background-position:220% center}}
    @keyframes blog-reveal{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @media(max-width:800px){#blog .blog-article-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:700px){#blog .blog-full{grid-template-columns:1fr;gap:20px}#blog .blog-full-media{width:min(100%,340px);min-height:180px}}
    @media(max-width:560px){#blog .blog-article-grid{grid-template-columns:1fr}#blog .blog-full{padding:16px}#blog .blog-full-media{max-height:200px;aspect-ratio:1.8}#blog .blog-full-share-row{align-items:center;flex-direction:row;gap:8px}#blog .blog-social-links{gap:5px}#blog .blog-share-button,#blog .blog-social-link{width:34px;height:34px}}
    @media(prefers-reduced-motion:reduce){#blog .blog-scroll-button i,#blog .blog-flow-line:after{animation:none}#blog .blog-flow-line:after{transform:none}#blog .blog-article,#blog .blog-article-media img,#blog .blog-read-more,#blog .blog-share-button,#blog .blog-social-link,#blog .blog-back{transition:none}#blog .blog-article:hover,#blog .blog-share-button:hover,#blog .blog-social-link:hover{transform:none}}
    .timeline{display:grid;gap:12px}
    .timeline-item{display:grid;grid-template-columns:140px 1fr;gap:24px;padding:22px 24px;border:1px solid var(--line);border-radius:17px;background:var(--surface)}
    .timeline-date{color:var(--accent);font-size:12px;font-weight:750}
    .timeline-item h3{margin:0 0 5px;font-size:17px}.timeline-item p{margin:0;color:var(--muted);font-size:14px}
    .journey-group+.journey-group{margin-top:42px}
    .journey-subhead{margin-bottom:16px}
    .journey-subhead h3{margin:7px 0 0;font-size:clamp(22px,3vw,30px)}
    .education-status{display:inline-flex;margin-bottom:8px;padding:5px 10px;border:1px solid var(--line);border-radius:999px;color:var(--accent);background:var(--raised);font-size:11px;font-weight:750}
    .journey-section{scroll-margin-top:82px}
    .journey-intro{display:grid;justify-items:center;gap:12px;margin-bottom:30px;text-align:center}
    .journey-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 10px;padding:0 20px;border:1px solid color-mix(in srgb,#22d3ee 48%,var(--line));border-radius:999px;color:#22d3ee;background:color-mix(in srgb,#22d3ee 9%,var(--surface));font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,box-shadow .2s}
    .journey-scroll-button:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    .journey-scroll-button i{animation:journey-arrow-bob 1.5s ease-in-out infinite}
    .journey-intro h2{margin:0;font-size:clamp(32px,5vw,50px);letter-spacing:-.05em}
    .journey-intro>p{max-width:660px;margin:0;color:var(--muted)}
    .journey-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:8px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.12),rgba(251,191,36,.12))}
    .journey-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:journey-flow 3.2s ease-in-out infinite}
    .journey-category-filters{display:flex;justify-content:center;flex-wrap:wrap;gap:9px;margin:0 auto 26px}
    .journey-filter{min-height:40px;padding:0 17px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);font-size:12px;font-weight:750;cursor:pointer;transition:transform .2s,border-color .2s,color .2s,background .2s,box-shadow .2s}
    .journey-filter:hover{transform:translateY(-2px);border-color:#22d3ee;color:#22d3ee}
    .journey-filter[aria-pressed="true"]{border-color:#22d3ee;color:#06131a;background:linear-gradient(100deg,#22d3ee,#fbbf24);box-shadow:0 7px 22px rgba(34,211,238,.18)}
    .journey-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
    .journey-grid[hidden]{display:none}
    .journey-card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 14px 40px rgba(2,6,17,.14);transition:transform .3s,border-color .3s,box-shadow .3s}
    .journey-card.is-entering{animation:journey-card-in .55s cubic-bezier(.2,.8,.2,1) var(--journey-delay,0ms) both}
    .journey-card[hidden]{display:none}
    .journey-card:hover{transform:translateY(-6px);border-color:rgba(34,211,238,.65);box-shadow:var(--shadow)}
    .journey-card-media{position:relative;aspect-ratio:1.25;overflow:hidden;background:var(--raised);isolation:isolate}
    .journey-card-image,.journey-full-image{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transform:translateX(6%) scale(1.035);transition:opacity .7s ease,transform .9s cubic-bezier(.22,1,.36,1);pointer-events:none}
    .journey-card-image.is-active,.journey-full-image.is-active{opacity:1;transform:translateX(0) scale(1);pointer-events:auto}
    .journey-card-media:after{position:absolute;inset:45% 0 0;z-index:1;content:"";background:linear-gradient(transparent,rgba(0,0,0,.45));pointer-events:none}
    .journey-carousel-controls{position:absolute;z-index:2;inset:auto 11px 11px;display:flex;align-items:center;justify-content:space-between;gap:10px}
    .journey-carousel-controls[hidden]{display:none}
    .journey-arrow{width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.38);border-radius:50%;color:#fff;background:rgba(15,23,42,.62);backdrop-filter:blur(8px);cursor:pointer;transition:transform .2s,background .2s}
    .journey-arrow:hover{transform:scale(1.08);background:rgba(15,23,42,.9)}
    .journey-dots{display:flex;align-items:center;gap:6px;padding:6px 9px;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(15,23,42,.55);backdrop-filter:blur(8px)}
    .journey-dot{width:7px;height:7px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.58);cursor:pointer;transition:width .2s,background .2s}
    .journey-dot[aria-current="true"]{width:18px;border-radius:999px;background:#fff}
    .journey-card-copy{padding:19px 20px 20px}
    .journey-card-meta{display:flex;justify-content:space-between;gap:10px;color:var(--muted);font-size:11px;font-weight:700}
    .journey-card-meta span:first-child{color:#22d3ee}
    .journey-card-copy h3{margin:9px 0 8px;font-size:19px;letter-spacing:-.025em}
    .journey-card-title{display:flex;align-items:center;gap:11px}
    .journey-title-icon{width:37px;height:37px;display:grid;place-items:center;flex:0 0 auto;border:1px solid rgba(34,211,238,.28);border-radius:12px;color:#22d3ee;background:linear-gradient(135deg,rgba(34,211,238,.13),rgba(251,191,36,.12));font-size:15px;transition:transform .25s,border-color .25s}
    .journey-card:hover .journey-title-icon{transform:rotate(-7deg) scale(1.08);border-color:#fbbf24}
    .journey-card-title h3{margin:0;font-size:18px;letter-spacing:-.025em}
    .journey-card-copy>p{margin:0;color:var(--muted);font-size:13px;line-height:1.7}
    .journey-features{display:flex;flex-wrap:wrap;gap:7px;margin:12px 0 0;padding:0;list-style:none}
    .journey-features li{display:inline-flex;align-items:center;gap:6px;padding:5px 8px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--raised);font-size:10px;line-height:1.4}
    .journey-features i{color:#fbbf24;font-size:9px}
    .journey-attachment-count{display:inline-flex;align-items:center;gap:7px;margin-top:13px;color:var(--muted);font-size:11px}
    .journey-open{display:flex;align-items:center;gap:8px;margin-top:14px;padding:0;border:0;color:#22d3ee;background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    .journey-open i{transition:transform .2s}
    .journey-open:hover i{transform:translateX(4px)}
    .journey-full{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);align-items:center;gap:clamp(22px,4vw,48px);margin-top:28px;padding:clamp(18px,3vw,30px);border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:var(--shadow);scroll-margin-top:96px;animation:journey-card-in .4s ease both}
    .journey-full[hidden]{display:none}
    .journey-full-media{position:relative;min-width:0;aspect-ratio:1.12;overflow:hidden;border-radius:17px;background:#080b12}
    .journey-full-image{object-fit:contain;background:#080b12}
    .journey-full-copy{align-self:center}
    .journey-full-copy h3{margin:8px 0 14px;font-size:clamp(27px,4vw,40px);line-height:1.12;letter-spacing:-.045em}
    .journey-full-title{display:flex;align-items:center;gap:13px}
    .journey-full-title .journey-title-icon{width:46px;height:46px;font-size:18px}
    .journey-full-title h3{margin:8px 0 14px}
    .journey-full-copy p{color:var(--muted);font-size:14px;line-height:1.8}
    .journey-full-copy p+p{margin-top:12px}
    .journey-attachments{display:flex;flex-wrap:wrap;gap:9px;margin-top:18px}
    .journey-attachment{min-height:38px;display:inline-flex;align-items:center;gap:8px;padding:0 12px;border:1px solid var(--line);border-radius:10px;color:var(--text);background:var(--raised);font-size:11px;font-weight:700;text-decoration:none;transition:border-color .2s,transform .2s}
    .journey-attachment:hover{transform:translateY(-2px);border-color:#22d3ee}
    .journey-attachment i{color:#22d3ee}
    .journey-no-documents{width:100%;margin:0;color:var(--muted);font-size:12px}
    .journey-back{min-height:42px;display:inline-flex;align-items:center;gap:9px;margin-top:18px;padding:0 16px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--raised);font-size:12px;font-weight:750;cursor:pointer;transition:border-color .2s,transform .2s}
    .journey-back:hover{transform:translateY(-2px);border-color:#22d3ee}
    .journey-live-status{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @keyframes journey-arrow-bob{50%{transform:translateY(3px)}}
    @keyframes journey-flow{50%{transform:translateX(0)}to{transform:translateX(100%)}}
    @keyframes journey-card-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @media(max-width:800px){.journey-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.journey-full{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:22px}}
    @media(max-width:560px){.journey-grid,.journey-full{grid-template-columns:1fr}.journey-full-media{aspect-ratio:1.2}}
    @media(prefers-reduced-motion:reduce){.journey-scroll-button i,.journey-flow-line:after{animation:none}.journey-flow-line:after{transform:none}.journey-card,.journey-card-image,.journey-full-image,.journey-filter,.journey-attachment,.journey-back{transition:none}.journey-card:hover{transform:none}}
    .faq-intro{display:grid;justify-items:center;gap:12px;margin-bottom:25px;text-align:center}
    .faq-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;margin:0 auto 5px;padding:0 20px;border:1px solid rgba(34,211,238,.48);border-radius:999px;color:#67e8f9;background:rgba(34,211,238,.09);font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    .faq-scroll-button:hover{transform:translateY(-3px);border-color:#34d399;background:rgba(16,185,129,.12);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    .faq-scroll-button i{animation:faq-arrow-bob 1.5s ease-in-out infinite}
    .faq-intro h2{margin:0;background:linear-gradient(100deg,#67e8f9 8%,#34d399 48%,#fde68a 72%,#67e8f9 94%);background-size:220% auto;background-clip:text;-webkit-background-clip:text;color:transparent;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em;animation:faq-title-flow 7s linear infinite}
    .faq-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:3px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.16),rgba(52,211,153,.2),rgba(253,230,138,.18))}
    .faq-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 25%,#34d399 52%,#fde68a 76%,transparent);animation:faq-flow 3.2s ease-in-out infinite}
    .faq-filters{display:flex;justify-content:center;flex-wrap:wrap;gap:9px;margin:0 auto 24px}
    .faq-filter{min-height:40px;padding:0 17px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);font-size:12px;font-weight:750;cursor:pointer;transition:transform .2s,border-color .2s,color .2s,background .2s,box-shadow .2s}
    .faq-filter:hover{transform:translateY(-2px);border-color:#34d399;color:#67e8f9}
    .faq-filter[aria-pressed="true"],.faq-filter.active{border-color:#34d399;color:#071812;background:linear-gradient(100deg,#22d3ee,#34d399,#fde68a);box-shadow:0 7px 22px rgba(52,211,153,.18)}
    .faq-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .faq-card{padding:22px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(145deg,color-mix(in srgb,#22d3ee 5%,var(--surface)),var(--surface) 58%,color-mix(in srgb,#34d399 5%,var(--surface)));box-shadow:0 12px 32px rgba(2,6,17,.12);transition:transform .25s,border-color .25s,box-shadow .25s}
    .faq-card:hover{transform:translateY(-4px);border-color:rgba(52,211,153,.55);box-shadow:0 16px 38px rgba(34,211,238,.1),var(--shadow)}
    .faq-card[hidden]{display:none}
    .faq-category{color:#34d399;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .faq-card h3{margin:9px 0 8px;font-size:17px;line-height:1.4}
    .faq-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.75}
    @keyframes faq-arrow-bob{50%{transform:translateY(3px)}}
    @keyframes faq-flow{50%{transform:translateX(0)}to{transform:translateX(100%)}}
    @keyframes faq-title-flow{to{background-position:220% center}}
    #contact .contact-intro{display:grid;justify-items:center;gap:12px;margin-bottom:30px;text-align:center}
    #contact .contact-scroll-button{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 20px;border:1px solid rgba(34,211,238,.48);border-radius:999px;color:#67e8f9;background:rgba(34,211,238,.09);font-size:12px;font-weight:750;text-decoration:none;transition:transform .2s,border-color .2s,background .2s,box-shadow .2s}
    #contact .contact-scroll-button:hover{transform:translateY(-3px);border-color:#fbbf24;background:rgba(34,211,238,.15);box-shadow:0 8px 24px rgba(34,211,238,.18)}
    #contact .contact-scroll-button i{animation:contact-arrow-bob 1.5s ease-in-out infinite}
    #contact .contact-intro h2{margin:0;background:linear-gradient(100deg,#67e8f9 8%,#fbbf24 52%,#67e8f9 92%);background-size:220% auto;background-clip:text;-webkit-background-clip:text;color:transparent;font-size:clamp(34px,5vw,54px);letter-spacing:-.05em}
    #contact.visible .contact-intro h2{animation:contact-title-flow 7s linear infinite,contact-reveal .7s .08s both}
    #contact .contact-flow-line{position:relative;width:min(560px,85%);height:3px;margin-top:3px;overflow:hidden;border-radius:99px;background:linear-gradient(90deg,rgba(34,211,238,.16),rgba(251,191,36,.18))}
    #contact .contact-flow-line:after{position:absolute;inset:0;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 34%,#fbbf24 68%,transparent);animation:contact-flow 3.2s ease-in-out infinite}
    #contact .contact-intro>p{max-width:660px;margin:0;color:var(--muted);font-size:15px}
    #contact .contact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:18px}
    #contact .contact-card{position:relative;min-width:0;display:flex;flex-direction:column;padding:23px;border:1px solid var(--line);border-radius:20px;background:linear-gradient(145deg,rgba(34,211,238,.055),var(--surface) 48%,rgba(251,191,36,.045));box-shadow:0 14px 40px rgba(2,6,17,.14);transition:transform .3s,border-color .3s,box-shadow .3s}
    #contact.visible .contact-card{animation:contact-reveal .55s cubic-bezier(.2,.8,.2,1) var(--contact-delay,0ms) both}
    #contact.visible .contact-card:nth-child(2){--contact-delay:90ms}
    #contact.visible .contact-card:nth-child(3){--contact-delay:180ms}
    #contact .contact-card:hover{transform:translateY(-6px);border-color:rgba(34,211,238,.6);box-shadow:var(--shadow)}
    #contact .contact-card-heading{display:flex;align-items:center;gap:13px;margin-bottom:20px}
    #contact .contact-card-icon{width:44px;height:44px;flex:0 0 auto;display:grid;place-items:center;border:1px solid rgba(34,211,238,.26);border-radius:14px;color:#67e8f9;background:rgba(34,211,238,.09);font-size:17px}
    #contact .contact-card-heading h3{margin:0;font-size:18px;letter-spacing:-.025em}
    #contact .contact-detail-list{display:grid;gap:15px;margin:0;padding:0;list-style:none}
    #contact .contact-detail{display:grid;grid-template-columns:34px minmax(0,1fr);align-items:center;gap:11px}
    #contact .contact-detail>i{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;color:#fbbf24;background:rgba(251,191,36,.09)}
    #contact .contact-detail-label{display:block;color:var(--muted);font-size:11px}
    #contact .contact-detail-value{display:block;overflow-wrap:anywhere;color:var(--text);font-size:13px;font-weight:650;text-decoration:none}
    #contact a.contact-detail-value:hover{color:#67e8f9}
    #contact .contact-copy-email{display:inline-flex;align-items:center;gap:6px;margin-top:5px;padding:4px 0;border:0;color:#67e8f9;background:transparent;font-size:11px;font-weight:700;cursor:pointer}
    #contact .contact-copy-email:hover{color:#fbbf24}
    #contact .contact-note{margin:auto 0 0;padding-top:17px;color:var(--muted);font-size:11px;line-height:1.6}
    #contact .contact-form{display:grid;gap:12px}
    #contact .contact-field{display:grid;gap:5px}
    #contact .contact-field label{color:var(--muted);font-size:11px;font-weight:650}
    #contact .contact-field input,#contact .contact-field textarea{width:100%;min-width:0;padding:10px 12px;border:1px solid var(--line);border-radius:10px;color:var(--text);background:var(--page);font:inherit;font-size:13px;transition:border-color .2s,box-shadow .2s}
    #contact .contact-field input:focus,#contact .contact-field textarea:focus{border-color:#22d3ee;outline:0;box-shadow:0 0 0 3px rgba(34,211,238,.12)}
    #contact .contact-field textarea{min-height:106px;resize:vertical}
    #contact .contact-submit{min-height:43px;display:inline-flex;align-items:center;justify-content:center;gap:9px;margin-top:2px;padding:0 17px;border:0;border-radius:999px;color:#06131a;background:linear-gradient(100deg,#22d3ee,#fbbf24);font-size:12px;font-weight:800;cursor:pointer;transition:transform .2s,box-shadow .2s}
    #contact .contact-submit:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(34,211,238,.2)}
    #contact .contact-form-status{min-height:18px;margin:0;color:var(--muted);font-size:11px;line-height:1.5}
    #contact .contact-social-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    #contact .contact-social-link{min-height:48px;display:flex;align-items:center;gap:10px;padding:0 12px;border:1px solid var(--line);border-radius:13px;color:var(--text);background:var(--raised);font-size:12px;font-weight:700;text-decoration:none;transition:transform .2s,border-color .2s,color .2s,background .2s}
    #contact .contact-social-link i{width:20px;text-align:center;color:#67e8f9;font-size:16px}
    #contact .contact-social-link:hover{transform:translateY(-3px);border-color:#fbbf24;color:#fbbf24;background:rgba(34,211,238,.07)}
    @keyframes contact-arrow-bob{50%{transform:translateY(3px)}}
    @keyframes contact-flow{50%{transform:translateX(0)}to{transform:translateX(100%)}}
    @keyframes contact-title-flow{to{background-position:220% center}}
    @keyframes contact-reveal{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
    @media(max-width:900px){#contact .contact-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#contact .contact-message-card{grid-column:span 2;grid-row:1}}
    @media(max-width:560px){#contact .contact-grid{grid-template-columns:1fr}#contact .contact-message-card{grid-column:auto;grid-row:auto}#contact .contact-card{padding:20px}}
    @media(prefers-reduced-motion:reduce){#contact .contact-scroll-button i,#contact .contact-flow-line:after{animation:none}#contact .contact-flow-line:after{transform:none}#contact .contact-card,#contact .contact-social-link,#contact .contact-submit{transition:none}#contact .contact-card:hover,#contact .contact-social-link:hover,#contact .contact-submit:hover{transform:none}}
    .footer{position:relative;width:100%;margin-top:auto;overflow:hidden;border-top:1px solid var(--line);background:linear-gradient(145deg,color-mix(in srgb,var(--accent) 5%,var(--surface)),var(--surface) 48%,color-mix(in srgb,var(--gold) 5%,var(--surface)))}
    .footer:before{position:absolute;inset:0 0 auto;height:2px;content:"";transform:translateX(-100%);background:linear-gradient(90deg,transparent,#22d3ee 36%,#fbbf24 68%,transparent);animation:footer-flow 5s ease-in-out infinite}
    .footer-inner{display:grid;grid-template-columns:1.35fr 1fr 1fr 1.15fr;gap:clamp(22px,4vw,48px);padding-block:46px 32px;color:var(--muted);font-size:13px}
    .footer-column{min-width:0}
    .footer-brand{display:inline-flex;align-items:center;gap:11px;color:#06b6d4;font-size:16px;font-weight:800;text-decoration:none}
    .footer-brand:hover,.footer-brand:focus-visible{animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-logo{width:46px;height:46px;flex:0 0 auto;display:grid;place-items:center;overflow:hidden;border:1px solid rgba(6,182,212,.5);border-radius:14px;color:#06b6d4;background:linear-gradient(145deg,rgba(6,182,212,.12),var(--surface));box-shadow:0 0 0 4px rgba(6,182,212,.08);transition:transform .25s,box-shadow .25s}
    .footer-logo img{width:100%;height:100%;display:block;object-fit:contain;padding:4px}
    .footer-logo-initials{font-family:Poppins,Inter,sans-serif;font-size:13px;font-weight:850;letter-spacing:-.04em}
    .footer-brand:hover .footer-logo{transform:translateY(-3px) rotate(-4deg);box-shadow:0 0 18px rgba(251,191,36,.42)}
    .footer-about-copy{max-width:330px;margin:14px 0 17px;color:#06b6d4;font-size:13px;font-weight:650;line-height:1.8}
    .footer-column h2{margin:3px 0 16px;color:#06b6d4;font-size:13px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
    .footer-column h2:after{width:34px;height:2px;display:block;margin-top:8px;content:"";border-radius:99px;background:linear-gradient(90deg,#22d3ee,#fbbf24,#a3e635);animation:footer-heading-glow 2.8s ease-in-out infinite alternate}
    .footer-list{display:grid;gap:10px;margin:0;padding:0;list-style:none}
    .footer-list a{width:max-content;max-width:100%;display:inline-flex;align-items:center;gap:9px;color:#06b6d4;font-weight:700;text-decoration:none;transition:color .2s,transform .2s}
    .footer-list a:before{width:6px;height:6px;flex:0 0 auto;content:"";border-radius:50%;background:#06b6d4;opacity:.8;transition:transform .2s,background .2s}
    .footer-list a:hover,.footer-list a:focus-visible{transform:translateX(4px);color:#fbbf24;animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-list a:hover:before{transform:scale(1.35);background:#a3e635;box-shadow:0 0 10px rgba(163,230,53,.75)}
    .footer-socials{display:flex;flex-wrap:wrap;gap:9px}
    .footer-social-link{width:36px;height:36px;display:grid;place-items:center;border:1px solid rgba(6,182,212,.35);border-radius:12px;color:#06b6d4;background:var(--surface);text-decoration:none;transition:transform .22s,border-color .22s,color .22s,background .22s,box-shadow .22s}
    .footer-social-link:hover,.footer-social-link:focus-visible{transform:translateY(-4px) rotate(-4deg);border-color:#fbbf24;color:#fbbf24;background:rgba(6,182,212,.09);box-shadow:0 8px 20px rgba(6,182,212,.2);animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-contact-item{display:flex;align-items:flex-start;gap:11px;margin:0 0 13px;color:#06b6d4;font-weight:650;line-height:1.65}
    .footer-contact-item>i{width:18px;flex:0 0 auto;margin-top:3px;color:#06b6d4;text-align:center;transition:color .2s,text-shadow .2s}
    .footer-contact-item a{color:#06b6d4;overflow-wrap:anywhere;font-weight:700;text-decoration:none;transition:color .2s}
    .footer-contact-item a:hover{color:#fbbf24;text-shadow:0 0 10px rgba(163,230,53,.7);animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-contact-cta{min-height:38px;display:inline-flex;align-items:center;gap:9px;margin-top:3px;padding:0 14px;border:1px solid rgba(6,182,212,.45);border-radius:999px;color:#06b6d4;background:rgba(6,182,212,.07);font-size:12px;font-weight:800;text-decoration:none;transition:transform .2s,border-color .2s,box-shadow .2s}
    .footer-contact-cta:hover{transform:translateY(-2px);border-color:#fbbf24;color:#fbbf24;box-shadow:0 8px 22px rgba(6,182,212,.2);animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-bottom{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-block:17px;border-top:1px solid var(--line);color:#06b6d4;font-size:11px;font-weight:700}
    .footer-bottom p{margin:0}
    .footer-back-top{display:inline-flex;align-items:center;gap:8px;color:#06b6d4;font-weight:800;text-decoration:none;transition:color .2s}
    .footer-back-top i{transition:transform .2s}
    .footer-back-top:hover{color:#fbbf24;animation:footer-hover-flash .75s ease-in-out infinite alternate}
    .footer-back-top:hover i{transform:translateY(-3px)}
    @keyframes footer-flow{to{transform:translateX(100%)}}
    @keyframes footer-heading-glow{from{filter:drop-shadow(0 0 2px rgba(251,191,36,.45))}to{filter:drop-shadow(0 0 6px rgba(163,230,53,.75))}}
    @keyframes footer-hover-flash{from{color:#fbbf24;text-shadow:0 0 3px rgba(251,191,36,.4)}to{color:#a3e635;text-shadow:0 0 12px rgba(163,230,53,.8)}}
    .js-reveal .footer.reveal .footer-column{opacity:0;transform:translateY(14px);transition:opacity .55s ease,transform .55s cubic-bezier(.2,.8,.2,1)}
    .js-reveal .footer.reveal.visible .footer-column{opacity:1;transform:translateY(0)}
    .js-reveal .footer.reveal.visible .footer-column:nth-child(2){transition-delay:.08s}
    .js-reveal .footer.reveal.visible .footer-column:nth-child(3){transition-delay:.16s}
    .js-reveal .footer.reveal.visible .footer-column:nth-child(4){transition-delay:.24s}
    @media(max-width:900px){.footer-inner{grid-template-columns:repeat(2,minmax(0,1fr));gap:30px}}
    @media(max-width:560px){.footer-inner{grid-template-columns:1fr;gap:26px;padding-block:34px 26px}.footer-column h2{margin-bottom:12px}.footer-bottom{align-items:flex-start;flex-direction:column;gap:10px}}
    @media(prefers-reduced-motion:reduce){.footer:before,.footer-column h2:after,.footer-brand:hover,.footer-brand:focus-visible,.footer-list a:hover,.footer-social-link:hover,.footer-contact-item a:hover,.footer-contact-cta:hover,.footer-back-top:hover{animation:none;transform:none;filter:none}.footer-logo,.footer-list a,.footer-list a:before,.footer-social-link,.footer-contact-cta,.footer-back-top i{transition:none}.footer-brand:hover .footer-logo,.footer-list a:hover,.footer-social-link:hover,.footer-contact-cta:hover,.footer-back-top:hover i{transform:none}.js-reveal .footer.reveal .footer-column{opacity:1;transform:none;transition:none}}
    .dialog{width:min(620px,calc(100% - 28px));max-height:min(82vh,760px);padding:0;overflow:auto;border:1px solid var(--line);border-radius:24px;color:var(--text);background:var(--surface);box-shadow:var(--shadow)}
    .dialog::backdrop{background:rgba(5,7,13,.72);backdrop-filter:blur(7px)}
    .dialog-inner{padding:clamp(24px,5vw,38px)}.dialog-top{display:flex;justify-content:space-between;gap:16px}
    .dialog-close{width:40px;height:40px;flex:0 0 auto;border:1px solid var(--line);border-radius:50%;color:var(--text);background:var(--raised);cursor:pointer}
    .dialog-category{color:var(--accent);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
    .dialog h2{margin:8px 0 16px;font-size:clamp(27px,5vw,40px)}.dialog-copy{color:var(--muted)}.dialog-copy p+p{margin-top:14px}
    .subscription-button:not(:disabled){position:relative;animation:subscription-attention 2.2s ease-in-out infinite}
    .subscription-button:not(:disabled):hover{animation-play-state:paused}
    @keyframes subscription-attention{0%,100%{box-shadow:0 0 0 0 rgba(34,211,238,.12);filter:saturate(1)}50%{box-shadow:0 0 0 7px rgba(34,211,238,0),0 0 22px rgba(251,191,36,.38);filter:saturate(1.25)}}
    .subscription-providers{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;margin-top:16px}
    .subscription-provider{position:relative;min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 10px;overflow:hidden;border:1px solid var(--line);border-radius:11px;color:var(--text);background:var(--raised);font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;transition:transform .2s,border-color .2s,box-shadow .2s;animation:subscription-provider-glow 2.8s ease-in-out infinite}
    .subscription-provider:nth-child(2){animation-delay:.45s}.subscription-provider:nth-child(3){animation-delay:.9s}
    .subscription-provider:before{position:absolute;inset:0;content:"";transform:translateX(-130%);background:linear-gradient(110deg,transparent 28%,rgba(255,255,255,.15) 48%,transparent 68%);animation:subscription-provider-sheen 4s ease-in-out infinite}
    .subscription-provider>*{position:relative;z-index:1}
    .subscription-provider:hover,.subscription-provider:focus-visible{transform:translateY(-2px);border-color:#22d3ee;box-shadow:0 7px 20px rgba(34,211,238,.2);animation-play-state:paused}
    .subscription-provider:focus-visible{outline:2px solid #22d3ee;outline-offset:3px}
    .subscription-provider-indicator{position:absolute!important;top:3px;right:5px;color:#fbbf24;font-size:8px;letter-spacing:.04em;text-transform:uppercase}
    .subscription-provider.is-loading{pointer-events:none;opacity:.72}
    .subscription-provider.is-loading:after{width:13px;height:13px;content:"";border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:subscription-provider-spin .7s linear infinite}
    @keyframes subscription-provider-glow{0%,100%{box-shadow:0 0 0 0 rgba(34,211,238,0)}50%{box-shadow:0 0 16px rgba(34,211,238,.13)}}
    @keyframes subscription-provider-sheen{0%,65%,100%{transform:translateX(-130%)}82%{transform:translateX(130%)}}
    @keyframes subscription-provider-spin{to{transform:rotate(360deg)}}
    .subscription-provider-setup{margin:9px 0 0;color:var(--muted);font-size:11px;line-height:1.6}
    .subscription-divider{display:flex;align-items:center;gap:12px;margin:17px 0 0;color:var(--muted);font-size:11px}
    .subscription-divider:before,.subscription-divider:after{height:1px;flex:1;content:"";background:var(--line)}
    .subscription-form-status[data-state="success"]{color:#34d399}.subscription-form-status[data-state="error"]{color:#fca5a5}
    @media(max-width:440px){.subscription-providers{grid-template-columns:1fr}}
    .feature-grid-projects{margin-top:0}
    .feature-grid-card{scroll-margin-top:90px}
    .feature-grid-media video{background:#000}
    .feature-grid-pdf-link{position:absolute;z-index:2;inset:0;display:grid;place-content:center;gap:12px;color:#fff;background:linear-gradient(135deg,rgba(15,23,42,.9),rgba(15,23,42,.58));font-size:20px;font-weight:800;text-align:center;text-decoration:none}
    .feature-grid-pdf-link i{font-size:42px}
    .feature-grid-copy h2{margin:9px 0 7px;font-size:20px}
    .feature-grid-images{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:16px}
    .feature-grid-images figure{min-width:0;margin:0;overflow:hidden;border:1px solid var(--line);border-radius:12px;background:var(--raised)}
    .feature-grid-images img{width:100%;aspect-ratio:1.45;display:block;object-fit:cover}
    .feature-grid-images figcaption{padding:8px 10px;color:var(--muted);font-size:12px}
    @media(max-width:560px){.feature-grid-images{grid-template-columns:1fr}}
    .toast{position:fixed;z-index:80;right:20px;bottom:20px;max-width:calc(100% - 40px);padding:13px 17px;transform:translateY(20px);border:1px solid var(--line);border-radius:12px;opacity:0;color:var(--text);background:var(--surface);box-shadow:var(--shadow);pointer-events:none;transition:opacity .2s,transform .2s}
    .toast.visible{transform:translateY(0);opacity:1}
    .top-button{position:fixed;z-index:40;right:20px;bottom:20px;width:44px;height:44px;display:grid;place-items:center;transform:translateY(12px);border:1px solid var(--line);border-radius:50%;opacity:0;color:var(--text);background:var(--surface);box-shadow:var(--shadow);text-decoration:none;pointer-events:none;transition:opacity .2s,transform .2s,bottom .2s}
    .top-button.visible{bottom:78px;transform:translateY(0);opacity:1;pointer-events:auto}
    .reveal{opacity:1;transform:none}.js-reveal .reveal{opacity:0;transform:translateY(22px);transition:opacity .65s ease,transform .65s cubic-bezier(.2,.8,.2,1)}
    .js-reveal .reveal.visible{opacity:1;transform:translateY(0)}
    @keyframes pulse{50%{box-shadow:0 0 0 9px color-mix(in srgb,var(--accent) 0%,transparent)}}
    @keyframes blink{50%{opacity:0}}
    @media(max-width:1000px) and (min-width:801px){.hero-grid{grid-template-columns:minmax(0,1fr) minmax(290px,.85fr);gap:28px}.portrait{width:calc(100% - 10px)}.hero-actions{gap:7px}.hero-actions .button{padding-inline:8px;font-size:12px}}
    @media(max-width:800px){.hero{height:auto;min-height:0;align-items:initial}.hero:before{inset:-5%;background-position:52% center}.hero-grid{height:auto;grid-template-columns:1fr;gap:32px;padding-block:clamp(38px,7vw,58px)}.hero-grid>.reveal:first-child{align-self:auto}.portrait{width:min(100%,400px);height:auto;min-height:clamp(340px,68svh,560px);aspect-ratio:auto;justify-self:center;align-self:auto;margin-right:0}.about-profile-layout{grid-template-columns:minmax(220px,.8fr) minmax(0,1.2fr);gap:28px}.about-intro{padding-block:52px 42px}.about-body{padding-top:48px}.about-objectives{margin-top:48px}.section{padding-block:62px}.about-section{padding-top:0}.section-head{align-items:flex-start;flex-direction:column}.services-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.projects{grid-template-columns:repeat(2,minmax(0,1fr))}.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.hero-grid{gap:28px;padding-block:34px}.portrait{width:100%;min-height:clamp(320px,64svh,480px);padding:8px}.portrait-flip{inset:8px;width:calc(100% - 16px);height:calc(100% - 16px)}.portrait-back{gap:12px;padding:22px}.portrait-back-copy{font-size:13px}.hero:before{background-position:58% center}}
    @media(max-height:520px) and (min-width:801px){.hero{min-height:520px}.hero-grid{min-height:520px}.portrait{min-height:520px}}
    @media(max-width:950px){.nav{gap:12px}.nav-right{gap:10px}.nav-links{gap:12px}.nav-links a{font-size:13px}.search-input{width:105px}}
    @media(max-width:760px){.nav{width:calc(100% - 28px);min-height:66px}.menu-button{display:grid}.nav-right{gap:9px}.nav-links{position:absolute;top:calc(100% + 1px);right:0;left:0;max-height:calc(100vh - 72px);overflow-y:auto;overscroll-behavior:contain;display:none;align-items:stretch;gap:0;padding:8px 18px 16px;border-bottom:1px solid var(--line);background:var(--page)}.nav-links.open{display:flex;flex-direction:column}.nav-links>a{padding:12px 8px;border-bottom:1px solid var(--line)}.nav-dropdown{border-bottom:1px solid var(--line)}.nav-dropdown>summary{min-height:44px;padding:10px 8px}.nav-dropdown-menu{position:static;width:100%;max-width:none;gap:0;padding:4px 8px 10px;border:0;border-radius:0;background:transparent;box-shadow:none;animation:none}.nav-dropdown-menu a{padding:10px 14px}.nav-subdropdown-menu{position:static;width:auto;min-width:0;margin:0 0 6px 12px;padding:4px 8px;border:0;border-left:1px solid var(--line);border-radius:0;background:transparent;box-shadow:none;animation:none}.nav-subdropdown>summary{min-height:40px;padding:9px 14px}.nav-subdropdown-chevron{transform:rotate(90deg)}.nav-subdropdown[open] .nav-subdropdown-chevron{transform:rotate(180deg)}.search-wrap{margin-top:12px}.search-form{height:44px;border-radius:10px}.search-input{width:100%;font-size:14px}.search-results{position:static;width:100%;margin-top:7px}.container{width:calc(100% - 30px)}.hero-grid{padding-top:48px}.hero-note{align-items:flex-start;flex-direction:column}.projects{grid-template-columns:repeat(2,minmax(0,1fr))}.project-art{min-height:190px}.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.social-feed{width:min(780px,calc(100% - 30px))}.social-intro{padding-top:50px}.article-grid,.faq-grid{grid-template-columns:1fr}.timeline-item{grid-template-columns:1fr;gap:8px}.contact{grid-template-columns:1fr}.contact-actions{align-items:stretch;flex-direction:column}.footer-inner{align-items:flex-start;flex-direction:column}}
    @media(max-width:480px){.gallery-grid{grid-template-columns:1fr}.gallery-category-filters{gap:6px}.gallery-category-filter{min-height:37px;padding:0 12px;font-size:11px}}
    @media(max-width:560px){.projects{grid-template-columns:1fr}.project-body{min-height:0}.project-actions{margin-top:18px}.tags{margin-top:16px}}
    @media(max-width:560px){.about-profile-layout{grid-template-columns:1fr}.about-photo{aspect-ratio:1.08;max-height:430px}.about-values{grid-template-columns:1fr}.about-value{min-height:0}.about-footer{align-items:flex-start;flex-direction:column}.about-links{width:100%}}
    @media(max-width:560px){.stats-section{padding-bottom:28px}.stats-row{grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 8px}.stat-item+.stat-item{border-left:0}.stat-item:nth-child(even){border-left:1px solid var(--line)}.stat-item:nth-child(n+3){padding-top:14px;border-top:1px solid var(--line)}}
    @media(max-width:560px){.services-grid{grid-template-columns:1fr}}
    @media(max-width:400px){.hero-actions{gap:7px}.hero-actions .button{width:auto;min-width:0;gap:4px;padding-inline:5px;font-size:clamp(10px,2.8vw,11px)}h1{font-size:44px}}
    @media(max-width:760px){.gallery-full{grid-template-columns:1fr}.gallery-full-media{aspect-ratio:1.35}}
    @media(max-width:560px){.gallery-full{padding:16px}.gallery-full-media{aspect-ratio:1}.gallery-full-copy h3{font-size:30px}}
    .subscription-consent{display:flex;align-items:flex-start;gap:9px;margin:12px 0 16px;color:var(--muted);font-size:12px;line-height:1.55;cursor:pointer}
    .subscription-consent input{flex:0 0 auto;margin:3px 0 0;accent-color:var(--accent)}
    .subscription-consent-note{margin:14px 0 0;color:var(--muted);font-size:12px;line-height:1.5}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.js-reveal .reveal{opacity:1;transform:none}.nav-links a:hover{transform:none}}
  </style>
</head>
<body class="<?= $isGalleryPage ? 'portfolio-gallery-page' : 'portfolio-home' ?>">
  <a class="skip" href="#main">Skip to content</a>
  <div class="progress" id="progress" aria-hidden="true"></div>
  <header class="header">
    <div class="nav">
      <a class="brand" href="<?= $isGalleryPage ? 'portfolio.php#home' : '#home' ?>" aria-label="<?= e($portfolio['brand_name']) ?> home"><?php if ($logoImage !== ''): ?><img class="brand-logo-image" src="<?= e($logoImage) ?>" alt=""><?php else: ?><span class="brand-mark" aria-hidden="true"><?= e($portfolio['initials']) ?></span><?php endif; ?><span><?= e($portfolio['brand_name']) ?><span class="accent">.</span></span></a>
      <div class="nav-right">
        <nav class="nav-links" id="siteNav" aria-label="Main navigation">
          <?php foreach ($homeMenuLinks as $menuItem): ?>
          <?php $menuUrl = $menuItem['label'] === 'Gallery' ? 'portfolio-gallery.php#gallery' : ($isGalleryPage && isset($menuItem['url'][0]) && $menuItem['url'][0] === '#' ? 'portfolio.php' . $menuItem['url'] : $menuItem['url']); ?>
          <?php if ($menuItem['children'] !== []): ?>
          <details class="nav-dropdown">
            <summary><?= e($menuItem['label']) ?><i class="fa-solid fa-chevron-down nav-dropdown-chevron" aria-hidden="true"></i></summary>
            <div class="nav-dropdown-menu">
              <?php foreach ($menuItem['children'] as $childItem): ?>
              <?php $childUrl = $childItem['label'] === 'Gallery' ? 'portfolio-gallery.php#gallery' : ($isGalleryPage && isset($childItem['url'][0]) && $childItem['url'][0] === '#' ? 'portfolio.php' . $childItem['url'] : $childItem['url']); ?>
              <?php if (!empty($childItem['children'])): ?>
              <details class="nav-subdropdown">
                <summary><?= e($childItem['label']) ?><i class="fa-solid fa-chevron-right nav-subdropdown-chevron" aria-hidden="true"></i></summary>
                <div class="nav-subdropdown-menu">
                  <?php foreach ($childItem['children'] as $grandchildItem): ?>
                  <?php $grandchildUrl = $isGalleryPage && isset($grandchildItem['url'][0]) && $grandchildItem['url'][0] === '#' ? 'portfolio.php' . $grandchildItem['url'] : $grandchildItem['url']; ?>
                  <a href="<?= e($grandchildUrl) ?>" data-goal-link><?= e($grandchildItem['label']) ?></a>
                  <?php endforeach; ?>
                </div>
              </details>
              <?php else: ?>
              <a href="<?= e($childUrl) ?>"<?php if (!empty($childItem['filter'])): ?> data-project-filter="<?= e($childItem['filter']) ?>"<?php endif; ?>><?= e($childItem['label']) ?></a>
              <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </details>
          <?php else: ?>
          <a href="<?= e($menuUrl) ?>"<?php if ($isGalleryPage && $menuItem['label'] === 'Gallery'): ?> aria-current="location"<?php endif; ?><?php if (!empty($menuItem['filter'])): ?> data-project-filter="<?= e($menuItem['filter']) ?>"<?php endif; ?>><?= e($menuItem['label']) ?></a>
          <?php endif; ?>
          <?php endforeach; ?>
          <div class="search-wrap"<?php echo $searchVisible ? '' : ' hidden'; ?>>
            <form class="search-form" id="siteSearch" role="search">
              <span class="search-icon" aria-hidden="true">⌕</span>
              <input class="search-input" id="searchInput" type="search" placeholder="<?= e($searchPlaceholder) ?>" autocomplete="off" aria-label="Search portfolio" aria-controls="searchResults" aria-expanded="false">
              <button class="search-submit" type="submit" aria-label="Show search results">↵</button>
            </form>
            <div class="search-results" id="searchResults" role="listbox" aria-label="Search results" hidden></div>
          </div>
        </nav>
        <button class="icon-button" id="themeToggle" type="button" aria-label="Switch to <?= e($nextThemeLabels[$defaultTheme]) ?> theme" title="Current theme: <?= e(ucwords(str_replace('-', ' ', $defaultTheme))) ?>. Switch to <?= e($nextThemeLabels[$defaultTheme]) ?>."<?= $themeVisible ? '' : ' hidden' ?>><?= e($initialThemeIcon) ?></button>
        <button class="menu-button" id="menuToggle" type="button" aria-label="Open navigation menu" aria-controls="siteNav" aria-expanded="false">☰</button>
      </div>
    </div>
  </header>
  <main id="main" tabindex="-1">
    <section class="hero" id="home">
      <?php if ($backgroundUrl !== '' && $homeVisibility['background']): ?><img class="hero-background-image" src="<?= e($backgroundUrl) ?>" alt="" aria-hidden="true"><?php endif; ?>
      <div class="container hero-grid">
        <div class="reveal">
          <div class="eyebrow"><span class="status" aria-hidden="true"></span><?= e($portfolio['availability']) ?></div>
          <h1 class="hero-title"<?= $homeVisibility['headline'] ? '' : ' hidden' ?>><?= e($homeHeadline) ?></h1>
          <p class="hero-role"<?= $homeVisibility['roles'] ? '' : ' hidden' ?>><span class="visually-hidden"><?= e(implode(' | ', $homeRoles)) ?></span><span id="heroRoleTyping" aria-hidden="true"><?= e(implode(' | ', $homeRoles)) ?></span><span class="caret" aria-hidden="true"></span></p>
          <p class="hero-copy"<?= $homeVisibility['intro'] ? '' : ' hidden' ?>><span class="visually-hidden"><?= e($portfolio['intro']) ?></span><span id="heroIntroTyping" aria-hidden="true"><?= e($portfolio['intro']) ?></span><span class="caret" aria-hidden="true"></span></p>
          <div class="hero-actions">
            <a class="button" href="<?= e($hireUrl) ?>"<?= $homeVisibility['hire_button'] ? '' : ' hidden' ?>><?= e($homeActionLabels['hire']) ?> <span aria-hidden="true">↗</span></a>
            <a class="button ghost" href="<?= e($cvUrl) ?>" download<?= $homeVisibility['cv_button'] ? '' : ' hidden' ?>><?= e($homeActionLabels['cv']) ?> <span aria-hidden="true">↓</span></a>
            <a class="button ghost" id="certificatesButton" href="<?= e($certificatesUrl) ?>"<?= $homeVisibility['certificates_button'] ? '' : ' hidden' ?>><?= e($homeActionLabels['certificates']) ?> <span aria-hidden="true">↗</span></a>
            <button class="button ghost subscription-button" id="subscriptionButton" type="button" aria-haspopup="dialog" aria-label="<?= $isPortfolioSubscribed ? 'Review subscription privacy consent' : 'Subscribe to portfolio updates' ?>"<?= $homeVisibility['subscription_button'] ? '' : ' hidden' ?>><?= $isPortfolioSubscribed ? 'Subscribed ✓ · Update consent' : e($homeActionLabels['subscription']) . ' <span aria-hidden="true">✦</span>' ?></button>
          </div>
          <div class="hero-note"><span class="avatar-stack" aria-hidden="true"><span>PHP</span><span>SQL</span><span>WEB</span></span><span>PHP · MySQL · JavaScript · Web development</span></div>
        </div>
        <div class="portrait reveal" id="portraitCard"<?= $homeVisibility['portrait'] ? '' : ' hidden' ?>>
          <button class="portrait-flip" id="portraitFlip" type="button" aria-expanded="false" aria-controls="portraitBio" aria-label="Read Shumi’s biography">
            <span class="portrait-flip-inner">
              <span class="portrait-face portrait-front" id="portraitFront" aria-hidden="false">
                <img class="portrait-photo" src="<?= e($portraitUrl) ?>" alt="Portrait of <?= e($portfolio['name']) ?>" decoding="async">
                <span class="portrait-flip-hint"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Click to read bio</span>
                <span class="portrait-label"><strong><?= e($portfolio['name']) ?></strong><?= e($portfolio['role']) ?></span>
              </span>
              <span class="portrait-face portrait-back" id="portraitBio" aria-hidden="true">
                <span class="portrait-back-kicker"><?= e($homePortraitBioKicker) ?></span>
                <span class="portrait-back-name"><?= e($homePortraitBioName) ?></span>
                <span class="portrait-back-role"><i class="fa-solid fa-code" aria-hidden="true"></i><?= e($portfolio['role']) ?> <span aria-hidden="true">·</span> <?= e($portfolio['location']) ?></span>
                <span class="portrait-back-copy"><?= e($homePortraitBio) ?></span>
                <span class="portrait-back-skills"><?= e($homePortraitSkills) ?></span>
                <span class="portrait-back-hint"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Click to see portrait</span>
              </span>
            </span>
          </button>
        </div>
      </div>
    </section>
    <?php if (!$isGalleryPage && $headerFeatureGrids !== []): ?>
    <section class="container section reveal" id="header-content-grids" aria-label="Featured content">
      <div class="projects feature-grid-projects">
        <?php foreach ($headerFeatureGrids as $grid): ?>
        <article class="project feature-grid-card" id="feature-<?= e($grid['id']) ?>">
          <div class="project-art feature-grid-media">
            <?php if ($grid['media_type'] === 'image'): ?>
            <img class="project-preview" src="<?= e($grid['media_url']) ?>" alt="<?= e($grid['title']) ?>" loading="lazy" decoding="async">
            <?php elseif ($grid['media_type'] === 'video'): ?>
            <video class="project-preview" controls preload="metadata" playsinline<?= $grid['poster_url'] !== '' ? ' poster="' . e($grid['poster_url']) . '"' : '' ?>><source src="<?= e($grid['media_url']) ?>">Your browser does not support embedded video.</video>
            <?php else: ?>
            <?php if ($grid['poster_url'] !== ''): ?><img class="project-preview" src="<?= e($grid['poster_url']) ?>" alt="<?= e($grid['title']) ?> poster" loading="lazy" decoding="async"><?php endif; ?>
            <a class="feature-grid-pdf-link" href="<?= e($grid['media_url']) ?>" target="_blank" rel="noopener noreferrer">Open PDF <i class="fa-solid fa-file-pdf" aria-hidden="true"></i></a>
            <?php endif; ?>
          </div>
          <div class="project-body feature-grid-copy">
            <div class="project-meta"><span><?= e($grid['nav_label']) ?></span><span><?= strtoupper(e($grid['media_type'])) ?></span></div>
            <h2><?= e($grid['title']) ?></h2>
            <p><?= e($grid['description']) ?></p>
            <?php if ($grid['images'] !== []): ?>
            <div class="feature-grid-images">
              <?php foreach ($grid['images'] as $image): ?>
              <figure><img src="<?= e($image['src']) ?>" alt="<?= e($image['caption']) ?>" loading="lazy" decoding="async"><figcaption><?= e($image['caption']) ?></figcaption></figure>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <button class="feature-grid-details" type="button" data-header-feature-details aria-haspopup="dialog" aria-controls="headerFeatureDialog">Open details <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></button>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <dialog class="header-feature-dialog" id="headerFeatureDialog" aria-labelledby="headerFeatureDialogTitle">
      <h2 class="visually-hidden" id="headerFeatureDialogTitle">Featured content details</h2>
      <div class="header-feature-dialog-head">
        <p id="headerFeatureDialogKicker">Featured content</p>
        <button class="header-feature-dialog-close" type="button" data-header-feature-close aria-label="Close content details">Close</button>
      </div>
      <div class="header-feature-dialog-body">
        <div class="header-feature-dialog-media" id="headerFeatureDialogMedia"></div>
        <div class="header-feature-dialog-copy" id="headerFeatureDialogCopy"></div>
      </div>
    </dialog>
    <script>
      (() => {
        const dialog = document.getElementById('headerFeatureDialog');
        const section = document.getElementById('header-content-grids');
        if (!dialog || !section) return;
        const dialogMedia = document.getElementById('headerFeatureDialogMedia');
        const dialogCopy = document.getElementById('headerFeatureDialogCopy');
        const dialogKicker = document.getElementById('headerFeatureDialogKicker');
        let activeTrigger = null;
        section.querySelectorAll('[data-header-feature-details]').forEach(button => {
          button.addEventListener('click', () => {
            const card = button.closest('.feature-grid-card');
            const media = card.querySelector('.feature-grid-media').cloneNode(true);
            const copy = card.querySelector('.feature-grid-copy').cloneNode(true);
            copy.querySelector('[data-header-feature-details]')?.remove();
            const title = copy.querySelector('h2');
            document.getElementById('headerFeatureDialogTitle').textContent =
              title ? `${title.textContent.trim()} details` : 'Featured content details';
            dialogKicker.textContent = card.querySelector('.project-meta span')?.textContent.trim() || 'Featured content';
            dialogMedia.replaceChildren(media);
            dialogCopy.replaceChildren(copy);
            activeTrigger = button;
            if (!dialog.open) dialog.showModal();
            dialog.querySelector('[data-header-feature-close]').focus();
          });
        });
        dialog.querySelector('[data-header-feature-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
          if (event.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', () => {
          dialogMedia.replaceChildren();
          dialogCopy.replaceChildren();
          activeTrigger?.focus();
          activeTrigger = null;
        });
      })();
    </script>
    <?php endif; ?>
    <?php if (!$isGalleryPage && $portfolioAdvertisements !== []): ?>
    <aside class="product-ad-grid-panel" aria-labelledby="productAdGridTitle">
      <header class="product-ad-grid-header">
        <div><h2 id="productAdGridTitle">Featured offers</h2><p>Browse sponsored products and promotions.</p></div>
        <div class="product-ad-popup-actions">
          <button type="button" data-ad-dismiss>Dismiss</button>
          <button type="button" data-ad-see-more aria-expanded="false">See more</button>
        </div>
      </header>
      <section class="product-ad-section" id="advertisements" aria-label="Sponsored advertisements">
        <div class="product-ad-grid">
          <?php foreach ($portfolioAdvertisements as $adIndex => $advertisement): ?>
          <article class="product-ad-card">
            <div class="product-ad-image-wrap">
              <img class="product-ad-art" src="<?= e($advertisement['product_image_url']) ?>" alt="<?= e($advertisement['product_name']) ?>" loading="lazy" decoding="async">
            </div>
            <div class="product-ad-brand">
              <span class="product-ad-brand-mark"><?php if (!empty($advertisement['brand_logo_url'])): ?><img src="<?= e($advertisement['brand_logo_url']) ?>" alt=""><?php else: ?><i class="fa-solid fa-store" aria-hidden="true"></i><?php endif; ?></span>
              <span class="product-ad-brand-name"><?= e($advertisement['brand_name']) ?></span>
            </div>
            <div class="product-ad-copy">
              <span class="product-ad-label"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Sponsored offer</span>
              <h3><?= e($advertisement['product_name']) ?></h3>
              <p><?= e($advertisement['description']) ?></p>
            </div>
            <div class="product-ad-footer">
              <span><?= !empty($advertisement['is_pinned']) ? 'Featured promotion' : 'Sponsored offer' ?></span>
              <div class="product-ad-actions">
                <?php if (!empty($advertisement['poster_image_url'])): ?>
                <a href="<?= e($advertisement['poster_image_url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-image" aria-hidden="true"></i> View poster</a>
                <?php endif; ?>
                <?php if (!empty($advertisement['pdf_file_url'])): ?>
                <a href="<?= e($advertisement['pdf_file_url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i> PDF</a>
                <?php endif; ?>
                <button class="product-ad-details" type="button" data-ad-details="<?= $adIndex ?>"
                  data-ad-image="<?= e($advertisement['product_image_url']) ?>"
                  data-ad-poster="<?= e($advertisement['poster_image_url'] ?? '') ?>"
                  data-ad-video="<?= e($advertisement['video_file_url'] ?? '') ?>"
                  data-ad-pdf="<?= e($advertisement['pdf_file_url'] ?? '') ?>"
                  data-ad-title="<?= e($advertisement['product_name']) ?>"
                  data-ad-brand="<?= e($advertisement['brand_name']) ?>"
                  data-ad-description="<?= e($advertisement['description']) ?>"
                  data-ad-target-url="<?= e($advertisement['target_url']) ?>"
                  data-ad-cta-label="<?= e($advertisement['cta_label']) ?>"
                  aria-haspopup="dialog" aria-controls="productAdDialog">
                  Learn more <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </button>
                <?php if (!empty($advertisement['target_url'])): ?>
                <a href="<?= e($advertisement['target_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($advertisement['cta_label']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
    </aside>
    <dialog class="product-ad-dialog" id="productAdDialog" aria-labelledby="productAdDialogTitle">
      <div class="product-ad-dialog-content">
        <div class="product-ad-dialog-media">
          <div class="product-ad-slideshow" id="productAdSlideshow" aria-roledescription="carousel" aria-label="Advertisement media">
            <div class="product-ad-slides" id="productAdSlides"></div>
            <div class="product-ad-slide-controls">
              <button class="product-ad-slide-step" type="button" data-ad-slide-step="-1" aria-label="Previous media"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
              <span class="product-ad-slide-status" id="productAdSlideStatus" aria-live="polite"></span>
              <button class="product-ad-slide-step" type="button" data-ad-slide-step="1" aria-label="Next media"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            </div>
            <div class="product-ad-slide-dots" id="productAdSlideDots" role="group" aria-label="Choose advertisement media"></div>
          </div>
        </div>
        <div class="product-ad-dialog-copy">
          <div class="product-ad-dialog-top">
            <div><span class="product-ad-label" id="productAdDialogBrand"></span><h2 id="productAdDialogTitle"></h2></div>
            <button class="product-ad-dialog-close" type="button" data-ad-dialog-close aria-label="Close advertisement details">Close</button>
          </div>
          <p id="productAdDialogDescription"></p>
          <div class="product-ad-dialog-actions">
            <a class="product-ad-footer-link" id="productAdDialogLink" href="" target="_blank" rel="noopener noreferrer" hidden></a>
            <a class="product-ad-footer-link" id="productAdDialogPosterLink" href="" target="_blank" rel="noopener noreferrer" hidden><i class="fa-regular fa-image" aria-hidden="true"></i> View poster</a>
            <a class="product-ad-footer-link" id="productAdDialogPdfLink" href="" target="_blank" rel="noopener noreferrer" hidden><i class="fa-regular fa-file-pdf" aria-hidden="true"></i> Open PDF</a>
          </div>
        </div>
      </div>
    </dialog>
    <?php endif; ?>
    <?php if (!$isGalleryPage): ?>
    <dialog class="dialog" id="subscriptionDialog" aria-labelledby="subscriptionDialogTitle">
      <div class="dialog-inner">
        <div class="dialog-top">
          <div><span class="dialog-category">Portfolio updates</span><h2 id="subscriptionDialogTitle">Stay in the loop</h2></div>
          <button class="dialog-close" type="button" data-subscription-close aria-label="Close subscription form"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <p class="dialog-copy">Get an email when a new featured update or news post is published. You can unsubscribe at any time.</p>
        <form id="subscriptionForm" novalidate>
          <div class="subscription-field">
            <label for="subscriptionEmail">Email address</label>
            <input id="subscriptionEmail" name="email" type="email" autocomplete="email" maxlength="254" required>
          </div>
          <p class="subscription-consent-note">Consent is required to store your signup IP address, ISP, broad device type, and approximate location details. Your IP is sent to ipwho.is to look up this information.</p>
          <label class="subscription-consent"><input id="subscriptionMetadataConsent" name="metadata_consent" type="checkbox" required> I agree to store my IP address, ISP name, general device type, and approximate city, region, and country. My IP will be sent to ipwho.is for this lookup. This information is kept until I unsubscribe. MAC addresses are not collected.</label>
          <div class="visually-hidden" aria-hidden="true"><label for="subscriptionCompany">Leave this field empty</label><input id="subscriptionCompany" name="company" type="text" tabindex="-1" autocomplete="off"></div>
          <button class="subscription-submit" id="subscriptionSubmit" type="submit">Subscribe to updates</button>
          <p class="subscription-form-status" id="subscriptionFormStatus" role="status" aria-live="polite"></p>
        </form>
        <div class="subscription-divider" aria-hidden="true">or sign up with</div>
        <div class="subscription-providers" role="group" aria-label="Sign up with a social account">
          <?php foreach (['google' => ['fa-brands fa-google', 'Google'], 'facebook' => ['fa-brands fa-facebook-f', 'Facebook'], 'github' => ['fa-brands fa-github', 'GitHub']] as $providerKey => [$providerIcon, $providerLabel]): ?>
          <a class="subscription-provider" href="portfolio_subscription_api.php?oauth=<?= e($providerKey) ?>" aria-describedby="subscriptionProviderSetup">
            <i class="<?= e($providerIcon) ?>" aria-hidden="true"></i> <?= e($providerLabel) ?>
            <?php if (empty($subscriptionOAuthProviders[$providerKey]['configured'])): ?><span class="subscription-provider-indicator">Setup needed</span><?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
        <p class="subscription-provider-setup" id="subscriptionProviderSetup">Select a social provider to continue. If its app credentials are not configured, the next page will show the required server variables and callback URL. Email sign-up is always available.</p>
      </div>
    </dialog>
    <?php endif; ?>
    <section class="container stats-section reveal" aria-label="Career statistics"<?= count(array_filter($homeStats, static fn(array $stat): bool => $stat['visible'])) > 0 ? '' : ' hidden' ?>>
      <div class="stats-row">
        <?php foreach ($homeStats as $stat): ?>
        <?php if (!$stat['visible']) { continue; } ?>
        <div class="stat-item"><strong data-count="<?= (int) $stat['value'] ?>" data-suffix="<?= e($stat['suffix']) ?>"><?= (int) $stat['value'] ?><?= e($stat['suffix']) ?></strong><span><?= e($stat['label']) ?></span></div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section social-section reveal" id="social">
      <div class="social-intro">
        <a class="social-scroll-button" href="#socialFeed">Social &amp; Post Widgets <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <h2>What People Say</h2>
        <p>Updates from my work and a place for everyone to share a comment, leave a like, or pass a post along.</p>
      </div>
      <div class="social-feed" id="socialFeed" role="feed" aria-label="Public posts">
        <?php foreach ($socialPosts as $post): ?>
        <?php if (isset($post['visible']) && $post['visible'] === false) { continue; } ?>
        <article class="social-post" id="social-post-<?= e($post['id']) ?>" data-social-post="<?= e($post['id']) ?>" aria-labelledby="social-title-<?= e($post['id']) ?>">
          <header class="social-post-header">
            <?php if ($logoImage !== ''): ?>
            <img class="social-avatar" src="<?= e($logoImage) ?>" alt="<?= e($portfolio['brand_name']) ?> logo" width="44" height="44" style="object-fit:cover">
            <?php else: ?>
            <span class="social-avatar" aria-hidden="true"><?= e($portfolio['initials']) ?></span>
            <?php endif; ?>
            <div class="social-author">
              <strong>Published by: <?= e($post['publisher_name']) ?> (<?= e($post['publisher_role']) ?>)</strong>
              <span>Portfolio update</span>
              <?php if ($post['featured']): ?><span class="social-featured-badge"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span><?php endif; ?>
              <?php $publishedAt = strtotime($post['published_at']); ?>
              <time datetime="<?= e(date(DATE_ATOM, $publishedAt)) ?>"><?= e(date('F j, Y \a\t g:i A T', $publishedAt)) ?></time>
            </div>
          </header>
          <?php if ($post['media_type'] === 'video'): ?>
          <video class="social-post-image" controls preload="metadata" playsinline data-social-media><source src="<?= e($post['image']) ?>" type="video/mp4">Your browser does not support embedded video.</video>
          <?php else: ?>
          <img class="social-post-image" src="<?= e($post['image']) ?>" alt="<?= e($post['alt']) ?>" loading="lazy" decoding="async" data-social-media data-social-full-post tabindex="0" role="button" aria-label="Read full post: <?= e($post['title']) ?>">
          <?php endif; ?>
          <div class="social-post-copy">
            <h3 id="social-title-<?= e($post['id']) ?>"><?= e($post['title']) ?></h3>
            <p><?= e($post['description']) ?></p>
            <button class="project-open social-read-full" type="button" data-social-full-post>See more · Read full post</button>
          </div>
          <?php
            $postShareUrl = $socialShareOrigin . $socialRequestPath . '?' . http_build_query(['social_post' => $post['id']]) . '#social-post-' . $post['id'];
            $postShareImageUrl = filter_var($post['image'], FILTER_VALIDATE_URL) !== false
                ? $post['image']
                : $socialShareOrigin . '/' . ltrim($post['image'], '/');
          ?>
          <div class="social-post-actions" aria-label="Post actions">
            <button class="social-action" type="button" data-social-like aria-pressed="false" aria-label="Like post">
              <i class="fa-regular fa-heart" aria-hidden="true"></i><span>Like</span><span data-social-like-count>0</span>
            </button>
            <button class="social-action" type="button" data-social-comment-focus aria-label="Show comments" aria-expanded="false" aria-controls="social-comments-<?= e($post['id']) ?> social-comment-form-<?= e($post['id']) ?>">
              <i class="fa-regular fa-comment" aria-hidden="true"></i><span data-social-comment-count>0 comments</span>
            </button>
            <details class="social-share-menu">
              <summary aria-label="Choose where to share <?= e($post['title']) ?>">
                <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>Share</span><span data-social-share-count>0</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
              </summary>
              <div class="social-share-dropdown" aria-label="Share destinations">
                <button class="social-action" type="button" data-social-share data-social-share-url="<?= e($postShareUrl) ?>" data-social-share-image="<?= e($postShareImageUrl) ?>" aria-label="Share <?= e($post['title']) ?>">
                  <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>More options…</span>
                </button>
                <button class="social-action" type="button" data-social-copy-link="<?= e($postShareUrl) ?>">
                  <i class="fa-solid fa-link" aria-hidden="true"></i><span>Copy link</span>
                </button>
                <a class="social-action" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($postShareUrl) ?>" target="_blank" rel="noopener noreferrer" data-social-platform-share aria-label="Share <?= e($post['title']) ?> on Facebook">
                  <i class="fa-brands fa-facebook" aria-hidden="true"></i><span>Facebook</span>
                </a>
                <a class="social-action" href="https://twitter.com/intent/tweet?text=<?= rawurlencode($post['title'] . "\n" . $post['description']) ?>&amp;url=<?= rawurlencode($postShareUrl) ?>" target="_blank" rel="noopener noreferrer" data-social-platform-share aria-label="Share <?= e($post['title']) ?> on X">
                  <i class="fa-brands fa-x-twitter" aria-hidden="true"></i><span>X</span>
                </a>
                <a class="social-action" href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" data-social-copy-platform-share data-share-title="<?= e($post['title']) ?>" data-share-description="<?= e($post['description']) ?>" data-share-image="<?= e($postShareImageUrl) ?>" data-share-url="<?= e($postShareUrl) ?>" aria-label="Copy post content and open TikTok">
                  <i class="fa-brands fa-tiktok" aria-hidden="true"></i><span>TikTok · copy link</span>
                </a>
                <a class="social-action" href="https://api.whatsapp.com/send?text=<?= rawurlencode($post['title'] . "\n" . $post['description'] . "\n" . $postShareUrl) ?>" target="_blank" rel="noopener noreferrer" data-social-platform-share aria-label="Share <?= e($post['title']) ?> on WhatsApp">
                  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span>
                </a>
                <a class="social-action" href="https://t.me/share/url?url=<?= rawurlencode($postShareUrl) ?>&amp;text=<?= rawurlencode($post['title'] . "\n" . $post['description']) ?>" target="_blank" rel="noopener noreferrer" data-social-platform-share aria-label="Share <?= e($post['title']) ?> on Telegram">
                  <i class="fa-brands fa-telegram" aria-hidden="true"></i><span>Telegram</span>
                </a>
                <a class="social-action" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" data-social-copy-platform-share data-share-title="<?= e($post['title']) ?>" data-share-description="<?= e($post['description']) ?>" data-share-image="<?= e($postShareImageUrl) ?>" data-share-url="<?= e($postShareUrl) ?>" aria-label="Copy post content and open Instagram">
                  <i class="fa-brands fa-instagram" aria-hidden="true"></i><span>Instagram · copy link</span>
                </a>
                <a class="social-action" href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" data-social-copy-platform-share data-share-title="<?= e($post['title']) ?>" data-share-description="<?= e($post['description']) ?>" data-share-image="<?= e($postShareImageUrl) ?>" data-share-url="<?= e($postShareUrl) ?>" aria-label="Copy post content and open YouTube">
                  <i class="fa-brands fa-youtube" aria-hidden="true"></i><span>YouTube · copy link</span>
                </a>
              </div>
            </details>
          </div>
          <div class="social-post-more-actions" aria-label="More post activity">
            <button class="social-see-more" type="button" data-social-see-more="likes" aria-expanded="false">See more likes</button>
            <button class="social-see-more" type="button" data-social-see-more="comments" aria-expanded="false" aria-controls="social-comments-<?= e($post['id']) ?>">See more comments</button>
            <button class="social-see-more" type="button" data-social-see-more="shares" aria-expanded="false">See more shares</button>
          </div>
          <section class="social-activity-details" data-social-details="likes" aria-label="People who liked this post" hidden><h4>Likes</h4><ul data-social-likers></ul></section>
          <section class="social-activity-details" data-social-details="shares" aria-label="People who shared this post" hidden><h4>Shares</h4><ul data-social-sharers></ul></section>
          <section class="social-comments" id="social-comments-<?= e($post['id']) ?>" aria-label="Comments" hidden>
            <p class="social-comment-status" data-social-comment-status aria-live="polite">Loading comments…</p>
            <div class="social-comment-list" data-social-comment-list></div>
          </section>
          <form class="social-comment-form" id="social-comment-form-<?= e($post['id']) ?>" data-social-comment-form hidden>
            <div class="social-comment-fields">
              <label class="visually-hidden" for="comment-name-<?= e($post['id']) ?>">Your name</label>
              <input id="comment-name-<?= e($post['id']) ?>" name="name" type="text" maxlength="80" autocomplete="name" placeholder="Your name" required>
              <label class="visually-hidden" for="comment-text-<?= e($post['id']) ?>">Write a comment</label>
              <textarea id="comment-text-<?= e($post['id']) ?>" name="text" maxlength="1000" placeholder="Write a comment…" required></textarea>
            </div>
            <button type="submit">Comment</button>
          </form>
        </article>
        <?php endforeach; ?>
        <p class="social-feed-error" data-social-feed-error role="status" hidden></p>
      </div>
      <section class="social-full-post" id="socialFullPost" aria-labelledby="socialFullPostTitle" tabindex="-1" hidden>
        <div class="social-full-post-top">
          <div><span class="dialog-category">Full post</span><h3 id="socialFullPostTitle"></h3></div>
          <button class="social-full-post-close" id="socialFullPostClose" type="button">Back to posts</button>
        </div>
        <div class="social-full-post-media" id="socialFullPostMedia"></div>
        <p class="social-full-post-copy" id="socialFullPostCopy"></p>
      </section>
    </section>

    <section class="section about-section reveal" id="about">
      <div class="about-intro">
        <div class="about-intro-inner">
          <a class="about-label-button" href="#whoIAm"><?= e($aboutContent['header_label']) ?></a>
          <a class="about-down" href="#whoIAm" aria-label="Scroll down to Who I Am"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
          <h2><?= e($aboutContent['header_title']) ?><span class="accent">.</span></h2>
        </div>
      </div>
      <div class="container about-body" id="whoIAm">
        <div class="about-profile-layout">
          <div class="about-photo-wrap">
            <div class="about-photo-carousel" id="aboutPhotoCarousel" aria-label="About photos" aria-roledescription="carousel">
              <?php foreach ($aboutImages as $index => $image): ?>
              <img class="about-photo-slide<?= $index === 0 ? ' is-active' : '' ?>" src="<?= e($image) ?>" alt="<?= e($index === 0 ? 'Portrait of ' . $portfolio['name'] : 'About ' . $portfolio['name'] . ' — photo ' . ($index + 1)) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" decoding="async"<?= $index === 0 ? ' aria-hidden="false"' : ' aria-hidden="true"' ?>>
              <?php endforeach; ?>
              <?php if (count($aboutImages) > 1): ?>
              <div class="about-photo-controls">
                <button class="about-photo-arrow" type="button" data-about-photo-step="-1" aria-label="Show previous photo"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                <div class="about-photo-dots" role="group" aria-label="Choose an About photo">
                  <?php foreach ($aboutImages as $index => $image): ?>
                  <button class="about-photo-dot" type="button" data-about-photo-index="<?= $index ?>" aria-label="Show photo <?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                  <?php endforeach; ?>
                </div>
                <button class="about-photo-arrow" type="button" data-about-photo-step="1" aria-label="Show next photo"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
              </div>
              <span class="about-photo-status" id="aboutPhotoStatus" aria-live="polite">Photo 1 of <?= count($aboutImages) ?></span>
              <?php endif; ?>
            </div>
            <div class="about-photo-caption">
              <div><strong><?= e($portfolio['name']) ?></strong><span><?= e($portfolio['role']) ?></span></div>
              <span class="about-location"><?= e($portfolio['location']) ?></span>
            </div>
          </div>
          <article class="about-statement">
            <span class="kicker"><?= e($aboutContent['profile_kicker']) ?></span>
            <h3><?= e($aboutContent['statement_title']) ?><br><span class="accent"><?= e($aboutContent['statement_accent']) ?></span></h3>
            <div class="about-description">
              <?php foreach ($aboutContent['descriptions'] as $paragraph): ?>
              <p><?= e($paragraph) ?></p>
              <?php endforeach; ?>
            </div>
            <span class="about-availability"><span class="status" aria-hidden="true"></span><?= e($portfolio['availability']) ?></span>
          </article>
        </div>
        <div class="about-objectives">
          <div class="about-objectives-head"><span class="kicker"><?= e($aboutContent['objectives_kicker']) ?></span><h3><?= e($aboutContent['objectives_title']) ?></h3></div>
          <div class="about-values" role="group" aria-label="Objectives and values">
            <?php foreach ($aboutValues as $key => $value): ?>
            <button class="about-value" type="button" data-about-value="<?= e($key) ?>" aria-pressed="<?= $key === 'curiosity' ? 'true' : 'false' ?>">
              <strong><?= e($value['label']) ?></strong>
              <p><?= e($value['description']) ?></p>
              <span class="about-value-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
            </button>
            <?php endforeach; ?>
          </div>
          <p class="about-value-status" id="aboutValueStatus" aria-live="polite">Selected objective: <?= e($aboutValues['curiosity']['label']) ?></p>
        </div>
        <div class="about-footer"><p><?= e($aboutContent['footer_text']) ?></p><div class="about-links"><a class="about-link" href="#work">Explore projects <span aria-hidden="true">↗</span></a><a class="about-link" href="#contact">Get in touch <span aria-hidden="true">↗</span></a></div></div>
      </div>
    </section>

    <section class="container section reveal" id="work" data-contact-email="<?= e($portfolio['email']) ?>">
      <span id="project" aria-hidden="true"></span>
      <header class="work-intro" id="featuredProjects">
        <a class="work-scroll-button" href="#featuredProjects">Projects <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <h2>Featured Projects</h2>
        <p>Explore a selection of web projects, their features, and the technologies behind them.</p>
        <span class="work-flow-line" aria-hidden="true"></span>
      </header>
      <div class="filters" role="group" aria-label="Filter projects">
        <button class="filter active" type="button" data-filter="all" aria-pressed="true">Works</button>
        <button class="filter" type="button" data-filter="portfolio" aria-pressed="false">Personal Portfolio</button>
        <button class="filter" type="button" data-filter="platform" aria-pressed="false">Web Platform</button>
        <button class="filter" type="button" data-filter="application" aria-pressed="false">Web Application</button>
      </div>
      <div class="projects" id="projectGrid">
        <?php foreach ($projects as $index => $project): ?>
        <article class="project" id="project-<?= $index ?>" data-category="<?= e($project['category']) ?>" data-project-key="<?= e($project['project_key']) ?>">
          <div class="project-art"><img class="project-preview" src="<?= e($project['image']) ?>" alt="<?= e($project['image_alt']) ?>" loading="lazy" decoding="async"></div>
          <div class="project-body">
            <div class="project-meta"><span><?= e($project['category_label']) ?></span><span><?= sprintf('%02d / %02d', $index + 1, count($projects)) ?></span></div>
            <h3><?= e($project['title']) ?></h3><p><?= e($project['summary']) ?></p>
            <div class="tags"><?php foreach ($project['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div>
            <div class="project-rating" role="radiogroup" aria-label="Rate <?= e($project['title']) ?>">
              <?php for ($rating = 1; $rating <= 5; $rating++): ?>
              <button type="button" role="radio" aria-label="<?= $rating ?> out of 5 stars" aria-checked="false" tabindex="<?= $rating === 1 ? '0' : '-1' ?>" data-rating="<?= $rating ?>">★</button>
              <?php endfor; ?>
            </div>
            <p class="project-rating-status" aria-live="polite">Choose a rating</p>
            <div class="project-actions">
              <button class="project-open" type="button" data-project="<?= $index ?>">See more <span aria-hidden="true">↗</span></button>
              <a class="project-download-link" href="portfolio_project_payment.php?project_key=<?= rawurlencode($project['project_key']) ?>">Request ZIP · <?= e((string) $project['price_birr']) ?> Birr</a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
        <?php if ($projects === []): ?>
        <p class="project-empty-state">Projects are being updated. Please check back soon.</p>
        <?php endif; ?>
      </div>
      <section class="project-full" id="projectFull" aria-labelledby="projectFullTitle" tabindex="-1" hidden>
        <div class="project-full-media"><img id="projectFullImage" src="" alt=""></div>
        <div class="project-full-copy">
          <span class="kicker" id="projectFullCategory"></span>
          <h3 id="projectFullTitle"></h3>
          <p id="projectFullSummary"></p>
          <p><strong id="projectFullContributionLabel"></strong> <span id="projectFullContribution"></span></p>
          <p><strong id="projectFullOutcomeLabel"></strong> <span id="projectFullOutcome"></span></p>
          <div class="dialog-tags" id="projectFullTags" aria-label="Project technologies"></div>
          <span class="project-full-price">Request the source ZIP, submit your payment reference, and download after approval. Price: <strong id="projectFullPrice"></strong> Birr.</span>
          <div class="project-full-actions">
            <a class="project-download-link" id="projectFullDownload" href="#">Request source ZIP</a>
            <button class="project-back" id="projectBack" type="button"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to projects</button>
          </div>
        </div>
      </section>
      <section class="goals-section" id="workGoals" aria-labelledby="goalsTitle">
        <header class="goals-intro">
          <span class="kicker">What guides the work</span>
          <h3 id="goalsTitle">Our Goals</h3>
          <span class="goals-flow-line" aria-hidden="true"></span>
          <p>Clear principles shape the way I plan, build, and improve every project.</p>
        </header>
        <div class="goals-grid">
          <?php foreach ($projectGoals as $goal): ?>
          <article class="goal-card" id="goal-<?= e($goal['id']) ?>" tabindex="-1">
            <div class="goal-card-top"><span class="goal-card-icon" aria-hidden="true"><i class="fa-solid <?= e($goal['icon']) ?>"></i></span><h4><?= e($goal['title']) ?></h4></div>
            <p class="goal-card-description"><?= e($goal['description']) ?></p>
            <p class="goal-card-more"><?= e($goal['detail']) ?></p>
            <a class="goal-card-link" href="#projectGrid">Explore the projects <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </article>
          <?php endforeach; ?>
          <?php if ($projectGoals === []): ?>
          <p class="project-empty-state">Strategy information is being updated.</p>
          <?php endif; ?>
        </div>
      </section>
    </section>

    <section class="section gallery-section reveal" id="gallery"<?= $galleryVisible ? '' : ' hidden' ?>>
      <div class="container" id="galleryWall">
        <header class="gallery-intro">
          <a class="gallery-scroll-button" href="#galleryTitle">Gallery <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
          <h2 id="galleryTitle"><?= e($galleryTitle) ?></h2>
          <p><?= e($galleryDescription) ?></p>
          <span class="gallery-flow-line" aria-hidden="true"></span>
        </header>
        <div class="gallery-category-filters" role="group" aria-label="Filter gallery categories">
          <button class="gallery-category-filter" type="button" data-gallery-filter="all" aria-pressed="true">All</button>
          <?php foreach ($galleryCategories as $category => $label): ?>
          <button class="gallery-category-filter" type="button" data-gallery-filter="<?= e($category) ?>" aria-pressed="false"><?= e($label) ?></button>
          <?php endforeach; ?>
        </div>
        <div class="gallery-grid" id="galleryGrid">
          <?php foreach ($gallery as $index => $item): ?>
          <article class="gallery-card" id="gallery-item-<?= $index ?>" data-gallery-card="<?= $index ?>" data-gallery-category="<?= e($item['category']) ?>">
            <div class="gallery-slideshow" data-gallery-carousel aria-label="<?= e($item['title']) ?> images" aria-roledescription="carousel" tabindex="0">
              <?php foreach ($item['images'] as $imageIndex => $image): ?>
              <img class="gallery-slide<?= $imageIndex === 0 ? ' is-active' : '' ?>" src="<?= e($image['src']) ?>" alt="<?= e($image['alt']) ?>" loading="lazy" decoding="async"<?= $imageIndex === 0 ? ' aria-hidden="false"' : ' aria-hidden="true"' ?>>
              <?php endforeach; ?>
              <div class="gallery-slideshow-controls">
                <button class="gallery-arrow-button" type="button" data-gallery-step="-1" aria-label="Previous image"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                <div class="gallery-dots" role="group" aria-label="Choose image">
                  <?php foreach ($item['images'] as $imageIndex => $image): ?>
                  <button class="gallery-dot" type="button" data-gallery-index="<?= $imageIndex ?>" aria-label="Show image <?= $imageIndex + 1 ?>" aria-current="<?= $imageIndex === 0 ? 'true' : 'false' ?>"></button>
                  <?php endforeach; ?>
                </div>
                <button class="gallery-arrow-button" type="button" data-gallery-step="1" aria-label="Next image"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
              </div>
              <span class="gallery-image-caption" data-gallery-caption aria-live="polite"><?= e($item['images'][0]['alt']) ?></span>
            </div>
            <div class="gallery-card-copy">
              <small><?= e($item['type']) ?></small>
              <h3><?= e($item['title']) ?></h3>
              <p><?= e($item['summary']) ?></p>
              <button class="gallery-open" type="button" data-gallery-item="<?= $index ?>">See more <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if ($gallery === []): ?><p class="project-empty-state">Gallery content is being updated.</p><?php endif; ?>
        </div>
        <section class="gallery-full" id="galleryFull" aria-labelledby="galleryFullTitle" tabindex="-1" hidden>
          <div class="gallery-full-media" data-gallery-full-carousel aria-label="Selected gallery images" aria-roledescription="carousel">
            <div class="gallery-slideshow-controls">
              <button class="gallery-arrow-button" type="button" data-gallery-full-step="-1" aria-label="Previous image"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
              <div class="gallery-dots" id="galleryFullDots" role="group" aria-label="Choose image"></div>
              <button class="gallery-arrow-button" type="button" data-gallery-full-step="1" aria-label="Next image"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </div>
          </div>
          <div class="gallery-full-copy">
            <span class="kicker" id="galleryFullType"></span>
            <h3 id="galleryFullTitle"></h3>
            <p id="galleryFullSummary"></p>
            <p id="galleryFullDetail"></p>
            <p id="galleryFullOutcome"></p>
            <button class="gallery-back" id="galleryBack" type="button"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to moments</button>
          </div>
          <span class="gallery-status" id="galleryFullStatus" aria-live="polite"></span>
        </section>
      </div>
    </section>

    <section class="container section reveal" id="services"<?= $skillsVisible ? '' : ' hidden' ?>>
      <span id="skills" aria-hidden="true"></span>
      <a class="services-scroll-button" href="#whatIDo">Skills <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <div class="services-intro" id="whatIDo">
        <h2><?= e($skillsTitle) ?></h2>
        <p><?= e($skillsDescription) ?></p>
      </div>
      <div class="services-grid">
        <?php foreach ($services as $index => $service): ?>
        <article class="service-card" id="service-<?= $index ?>">
          <span class="service-icon" aria-hidden="true"><i class="fa-solid <?= e($service['icon']) ?>"></i></span>
          <h3><?= e($service['title']) ?></h3>
          <p><?= e($service['description']) ?></p>
          <button class="service-details" type="button" aria-expanded="false" aria-controls="service-detail-<?= $index ?>">
            <span>See details</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </button>
          <p class="service-detail-copy" id="service-detail-<?= $index ?>" hidden><?= e($service['details']) ?></p>
        </article>
        <?php endforeach; ?>
        <?php if ($services === []): ?><p class="project-empty-state">Skills content is being updated.</p><?php endif; ?>
      </div>
    </section>

    <section class="container section reveal journey-section" id="journey">
      <div class="journey-intro" id="journeyContent">
        <a class="journey-scroll-button" href="#journeyTitle">Journey <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <span class="kicker"><?= e($journeyEyebrow) ?></span>
        <h2 id="journeyTitle"><?= e($journeyTitle) ?></h2>
        <p><?= e($journeyDescription) ?></p>
        <span class="journey-flow-line" aria-hidden="true"></span>
      </div>
      <div class="journey-category-filters" role="group" aria-label="Filter journey highlights">
        <button class="journey-filter" type="button" data-journey-filter="all" aria-pressed="true">All</button>
        <button class="journey-filter" type="button" data-journey-filter="educational" aria-pressed="false">Educational</button>
        <button class="journey-filter" type="button" data-journey-filter="experience" aria-pressed="false">Experience</button>
        <button class="journey-filter" type="button" data-journey-filter="talent" aria-pressed="false">Special Talent</button>
      </div>
      <div class="journey-grid" id="journeyGrid">
        <?php foreach ($journeyItems as $index => $item): ?>
        <?php $journeyImages = array_values(array_filter($item['attachments'], static fn(array $attachment): bool => $attachment['type'] === 'image')); ?>
        <article class="journey-card" id="journey-card-<?= $index ?>" data-journey-card="<?= $index ?>" data-journey-category="<?= e($item['category']) ?>">
          <div class="journey-card-media" data-journey-carousel aria-label="<?= e($item['title']) ?> images" aria-roledescription="carousel" tabindex="0">
            <?php foreach ($journeyImages as $imageIndex => $image): ?>
            <img class="journey-card-image<?= $imageIndex === 0 ? ' is-active' : '' ?>" src="<?= e($image['src']) ?>" alt="<?= e($image['alt']) ?>" loading="lazy" decoding="async"<?= $imageIndex === 0 ? ' aria-hidden="false"' : ' aria-hidden="true"' ?>>
            <?php endforeach; ?>
            <?php if (count($journeyImages) > 1): ?>
            <div class="journey-carousel-controls">
              <button class="journey-arrow" type="button" data-journey-step="-1" aria-label="Previous journey image"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
              <div class="journey-dots" role="group" aria-label="Choose journey image">
                <?php foreach ($journeyImages as $imageIndex => $image): ?>
                <button class="journey-dot" type="button" data-journey-index="<?= $imageIndex ?>" aria-label="Show image <?= $imageIndex + 1 ?>" aria-current="<?= $imageIndex === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
              </div>
              <button class="journey-arrow" type="button" data-journey-step="1" aria-label="Next journey image"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </div>
            <?php endif; ?>
          </div>
          <div class="journey-card-copy">
            <div class="journey-card-meta"><span><?= e($item['category_label']) ?></span><span><?= e($item['date']) ?></span></div>
            <div class="journey-card-title"><span class="journey-title-icon" aria-hidden="true"><i class="fa-solid <?= e($item['icon']) ?>"></i></span><h3><?= e($item['title']) ?></h3></div>
            <p><?= e($item['summary']) ?></p>
            <ul class="journey-features" aria-label="<?= e($item['title']) ?> features">
              <?php foreach ($item['features'] as $feature): ?>
              <li><i class="fa-solid fa-check" aria-hidden="true"></i><?= e($feature) ?></li>
              <?php endforeach; ?>
            </ul>
            <span class="journey-attachment-count"><i class="fa-solid fa-paperclip" aria-hidden="true"></i><?= count($item['attachments']) ?> attachments</span>
            <button class="journey-open" type="button" data-journey-open="<?= $index ?>">See more <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <section class="journey-full" id="journeyFull" aria-labelledby="journeyFullTitle" tabindex="-1" hidden>
        <div class="journey-full-media" id="journeyFullMedia" aria-label="Selected journey images" aria-roledescription="carousel">
          <div class="journey-carousel-controls">
            <button class="journey-arrow" type="button" data-journey-full-step="-1" aria-label="Previous image"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
            <div class="journey-dots" id="journeyFullDots" role="group" aria-label="Choose image"></div>
            <button class="journey-arrow" type="button" data-journey-full-step="1" aria-label="Next image"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
          </div>
        </div>
        <div class="journey-full-copy">
          <span class="kicker" id="journeyFullCategory"></span>
          <div class="journey-full-title"><span class="journey-title-icon" id="journeyFullIcon" aria-hidden="true"></span><h3 id="journeyFullTitle"></h3></div>
          <p id="journeyFullSummary"></p>
          <p id="journeyFullDetail"></p>
          <ul class="journey-features" id="journeyFullFeatures" aria-label="Experience and education features"></ul>
          <p id="journeyFullOutcome"></p>
          <div class="journey-attachments" id="journeyAttachments" aria-label="Journey attachments"></div>
          <button class="journey-back" id="journeyBack" type="button"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to journey</button>
        </div>
        <span class="journey-live-status" id="journeyFullStatus" aria-live="polite"></span>
      </section>
    </section>

    <section class="container section reveal" id="faq">
      <header class="faq-intro">
        <a class="faq-scroll-button" href="#faqGrid">A few helpful answers <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <h2><?= e($faqTitle) ?></h2>
        <span class="faq-flow-line" aria-hidden="true"></span>
      </header>
      <div class="faq-filters" role="group" aria-label="Filter FAQs">
        <button class="faq-filter active" type="button" data-faq-filter="all" aria-pressed="true">All questions</button>
        <?php foreach ($faqCategories as $category => $label): ?>
        <button class="faq-filter" type="button" data-faq-filter="<?= e($category) ?>" aria-pressed="false"><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="faq-grid" id="faqGrid">
        <?php foreach ($faqs as $index => $faq): ?>
        <article class="faq-card" id="faq-<?= $index ?>" data-faq-category="<?= e($faq['category']) ?>">
          <span class="faq-category"><?= e($faqCategories[$faq['category']]) ?></span>
          <h3><?= e($faq['question']) ?></h3>
          <p><?= e($faq['answer']) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="container section reveal" id="blog"<?= $blogVisible ? '' : ' hidden' ?>>
      <header class="blog-intro">
        <a class="blog-scroll-button" href="#blogTitle">Blog <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <h2 id="blogTitle"><?= e($blogTitle) ?></h2>
        <span class="blog-flow-line" aria-hidden="true"></span>
        <p><?= e($blogDescription) ?></p>
      </header>
      <div class="blog-article-grid" id="blogGrid">
        <?php foreach ($articles as $index => $article): ?>
        <article class="article-card blog-article" id="article-<?= $index ?>" data-blog-article="<?= $index ?>">
          <div class="blog-article-media">
            <?php if ($article['media_type'] === 'video'): ?>
            <video src="<?= e($article['image']) ?>" controls preload="metadata" aria-label="<?= e($article['image_alt']) ?>"></video>
            <?php else: ?>
            <img src="<?= e($article['image']) ?>" alt="<?= e($article['image_alt']) ?>" loading="lazy" decoding="async">
            <?php endif; ?>
          </div>
          <div class="blog-article-copy">
            <div class="blog-article-byline"><strong><?= e($portfolio['name']) ?></strong><time datetime="<?= e($article['date_iso']) ?>"><?= e($article['date']) ?></time></div>
            <span class="blog-article-category"><?= e($article['category']) ?></span>
            <h3><?= e($article['title']) ?></h3>
            <p><?= e($article['summary']) ?></p>
            <button class="blog-read-more" type="button" data-blog-open="<?= $index ?>" aria-controls="blogFull" aria-expanded="false">
              Read more <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
        </article>
        <?php endforeach; ?>
        <?php if ($articles === []): ?><p class="project-empty-state">Blog articles are being updated.</p><?php endif; ?>
      </div>
      <section class="blog-full" id="blogFull" aria-labelledby="blogFullTitle" tabindex="-1" hidden>
        <div class="blog-full-media" id="blogFullMedia"></div>
        <div class="blog-full-copy">
          <span class="blog-article-category" id="blogFullCategory"></span>
          <h3 id="blogFullTitle"></h3>
          <div class="blog-full-meta">
            <span><i class="fa-regular fa-calendar" aria-hidden="true"></i><time id="blogFullDate"></time></span>
            <span><i class="fa-regular fa-eye" aria-hidden="true"></i><span id="blogFullViews" aria-label="0 views on this device" title="Views counted on this device only">0</span></span>
            <span><i class="fa-regular fa-user" aria-hidden="true"></i><?= e($portfolio['name']) ?></span>
          </div>
          <p class="blog-full-body" id="blogFullBody"></p>
          <div class="blog-full-share-row">
            <button class="blog-share-button" id="blogShare" type="button" aria-label="Share this article"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button>
            <nav class="blog-social-links" id="blogSocialLinks" aria-label="Share article on social media"></nav>
          </div>
          <button class="blog-back" id="blogBack" type="button"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to articles</button>
        </div>
      </section>
      <span class="visually-hidden" id="blogStatus" aria-live="polite"></span>
    </section>

    <section class="container section reveal" id="contact"<?= !empty($contactSettings['visible']) ? '' : ' hidden' ?>>
      <header class="contact-intro">
        <a class="contact-scroll-button" href="#contactTitle">Contact <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <h2 id="contactTitle"><?= e($contactSettings['title']) ?></h2>
        <span class="contact-flow-line" aria-hidden="true"></span>
        <p><?= e($contactSettings['description']) ?></p>
      </header>
      <div class="contact-grid">
        <article class="contact-card contact-information-card">
          <div class="contact-card-heading">
            <span class="contact-card-icon" aria-hidden="true"><i class="fa-solid fa-address-card"></i></span>
            <h3>Contact Information</h3>
          </div>
          <ul class="contact-detail-list">
            <li class="contact-detail">
              <i class="fa-solid fa-phone" aria-hidden="true"></i>
              <span><span class="contact-detail-label">Phone number</span><span class="contact-detail-value"><?= e($contactSettings['phone']) ?></span></span>
            </li>
            <li class="contact-detail">
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
              <span>
                <span class="contact-detail-label">Email address</span>
                <a class="contact-detail-value" href="mailto:<?= e($portfolio['email']) ?>"><?= e($portfolio['email']) ?></a>
                <button class="contact-copy-email" id="copyEmail" type="button" data-email="<?= e($portfolio['email']) ?>"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy email</button>
              </span>
            </li>
            <li class="contact-detail">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
              <span><span class="contact-detail-label">Address</span><span class="contact-detail-value"><?= e($portfolio['location']) ?></span></span>
            </li>
          </ul>
          <p class="contact-note"><?= e($contactSettings['info_note']) ?></p>
        </article>
        <?php if (!empty($contactSettings['form_visible'])): ?>
        <article class="contact-card contact-message-card">
          <div class="contact-card-heading">
            <span class="contact-card-icon" aria-hidden="true"><i class="fa-regular fa-paper-plane"></i></span>
            <h3><?= e($contactSettings['form_title']) ?></h3>
          </div>
          <form class="contact-form" id="contactForm">
            <div class="contact-field">
              <label for="contactName">Your name</label>
              <input id="contactName" name="name" type="text" autocomplete="name" maxlength="120" required>
            </div>
            <div class="contact-field">
              <label for="contactEmail">Email address</label>
              <input id="contactEmail" name="email" type="email" autocomplete="email" maxlength="254" required>
            </div>
            <div class="contact-field">
              <label for="contactSubject">Subject</label>
              <input id="contactSubject" name="subject" type="text" maxlength="160" required>
            </div>
            <div class="contact-field">
              <label for="contactMessage">Message</label>
              <textarea id="contactMessage" name="message" rows="4" maxlength="5000" required></textarea>
            </div>
            <button class="contact-submit" type="submit">Send message <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <p class="contact-form-status" id="contactFormStatus" role="status" aria-live="polite">Your message will be delivered to the portfolio inbox.</p>
          </form>
        </article>
        <?php endif; ?>
        <?php if (!empty($contactSettings['social_visible'])): ?>
        <article class="contact-card contact-social-card">
          <div class="contact-card-heading">
            <span class="contact-card-icon" aria-hidden="true"><i class="fa-solid fa-share-nodes"></i></span>
            <h3><?= e($contactSettings['social_title']) ?></h3>
          </div>
          <nav class="contact-social-links" aria-label="Social media">
            <?php foreach ($contactSocialLinks as $link): ?>
            <a class="contact-social-link" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Open <?= e($link['label']) ?>">
              <i class="fa-brands <?= e($link['icon']) ?>" aria-hidden="true"></i><span><?= e($link['label']) ?></span>
            </a>
            <?php endforeach; ?>
          </nav>
          <?php if ($contactSocialLinks === []): ?><p class="contact-note">No social links are currently available.</p><?php else: ?><p class="contact-note"><?= e($contactSettings['social_note']) ?></p><?php endif; ?>
        </article>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <dialog class="dialog" id="projectDialog" aria-labelledby="dialogTitle">
    <div class="dialog-inner"><div class="dialog-top"><div><span class="dialog-category" id="dialogCategory"></span><h2 id="dialogTitle"></h2></div><button class="dialog-close" id="dialogClose" type="button" aria-label="Close project details">×</button></div>
      <div class="dialog-copy"><p id="dialogSummary"></p><p><strong id="dialogContributionLabel">My contribution:</strong> <span id="dialogContribution"></span></p><p><strong id="dialogOutcomeLabel">Outcome:</strong> <span id="dialogOutcome"></span></p></div>
      <div class="dialog-tags" id="dialogTags"></div>
    </div>
  </dialog>
  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <a class="top-button" id="topButton" href="#home" aria-label="Back to top">↑</a>
  <footer class="footer reveal" aria-label="Site footer">
    <div class="container footer-inner">
      <section class="footer-column" aria-labelledby="footer-about-title">
        <a class="footer-brand" id="footer-about-title" href="<?= $isGalleryPage ? 'portfolio.php#home' : '#home' ?>">
          <span class="footer-logo">
            <?php if ($logoImage !== ''): ?>
            <img src="<?= e($logoImage) ?>" alt="<?= e($portfolio['brand_name']) ?> logo" loading="lazy" decoding="async">
            <?php else: ?>
            <span class="footer-logo-initials" aria-hidden="true"><?= e($portfolio['initials']) ?></span>
            <?php endif; ?>
          </span>
          <span><?= e($portfolio['name']) ?></span>
        </a>
        <p class="footer-about-copy"><?= e($portfolio['intro']) ?> Based in <?= e($portfolio['location']) ?>, I build thoughtful web experiences with PHP, MySQL, and modern frontend tools.</p>
        <nav class="footer-socials" aria-label="Social media links">
          <a class="footer-social-link" href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://instagram.com/aashish_zha0" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://t.me/" target="_blank" rel="noopener noreferrer" aria-label="Telegram" title="Telegram"><i class="fa-brands fa-telegram" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><i class="fa-brands fa-tiktok" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://youtube.com/" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><i class="fa-brands fa-youtube" aria-hidden="true"></i></a>
          <a class="footer-social-link" href="https://x.com/Aashish_Zha0" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
        </nav>
      </section>
      <nav class="footer-column" aria-labelledby="footer-explore-title">
        <h2 id="footer-explore-title">Explore</h2>
        <ul class="footer-list">
          <?php foreach ($homeMenuLinks as $menuItem): ?>
          <?php
            $footerUrl = $menuItem['label'] === 'Gallery'
                ? ($isGalleryPage ? '#gallery' : 'portfolio-gallery.php#gallery')
                : ($isGalleryPage && isset($menuItem['url'][0]) && $menuItem['url'][0] === '#'
                    ? 'portfolio.php' . $menuItem['url']
                    : ($menuItem['url'] !== '' ? $menuItem['url'] : '#home'));
          ?>
          <li><a href="<?= e($footerUrl) ?>"><?= e($menuItem['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav class="footer-column" aria-labelledby="footer-services-title">
        <h2 id="footer-services-title">Services</h2>
        <ul class="footer-list">
          <?php foreach ($services as $index => $service): ?>
          <li><a href="<?= $isGalleryPage ? 'portfolio.php#service-' . $index : '#service-' . $index ?>"><?= e($service['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <section class="footer-column" aria-labelledby="footer-contact-title">
        <h2 id="footer-contact-title">Get me in touch</h2>
        <p class="footer-contact-item"><i class="fa-regular fa-envelope" aria-hidden="true"></i><a href="mailto:<?= e($portfolio['email']) ?>"><?= e($portfolio['email']) ?></a></p>
        <p class="footer-contact-item"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span><?= e($portfolio['location']) ?></span></p>
        <p class="footer-contact-item"><i class="fa-solid fa-phone" aria-hidden="true"></i><span><?= e($contactSettings['phone']) ?></span></p>
        <?php if (!empty($contactSettings['visible'])): ?><a class="footer-contact-cta" href="<?= $isGalleryPage ? 'portfolio.php#contact' : '#contact' ?>">Start a conversation <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
      </section>
    </div>
    <div class="container footer-bottom">
      <p>© <span id="year"></span> <?= e($portfolio['name']) ?> · <?= e($portfolio['role']) ?> · <?= e($portfolio['location']) ?></p>
      <a class="footer-back-top" href="<?= $isGalleryPage ? 'portfolio.php#home' : '#home' ?>">Back to top <i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>
    </div>
  </footer>

  <script>
    (() => {
      const root = document.documentElement;
      const themeButton = document.getElementById('themeToggle');
      const menuButton = document.getElementById('menuToggle');
      const nav = document.getElementById('siteNav');
      const progress = document.getElementById('progress');
      const topButton = document.getElementById('topButton');
      const dialog = document.getElementById('projectDialog');
      const toast = document.getElementById('toast');
      const searchForm = document.getElementById('siteSearch');
      const searchInput = document.getElementById('searchInput');
      const searchResults = document.getElementById('searchResults');
      const socialFeedCsrfToken = <?= json_encode($socialFeedCsrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
      const socialFeedApiUrl = 'social_feed_api.php';
      const subscriptionButton = document.getElementById('subscriptionButton');
      const subscriptionDialog = document.getElementById('subscriptionDialog');
      const subscriptionForm = document.getElementById('subscriptionForm');
      const subscriptionEmail = document.getElementById('subscriptionEmail');
      const subscriptionMetadataConsent = document.getElementById('subscriptionMetadataConsent');
      const subscriptionStatus = document.getElementById('subscriptionFormStatus');
      const subscriptionSubmit = document.getElementById('subscriptionSubmit');
      const socialFullPost = document.getElementById('socialFullPost');
      const projects = <?= json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const galleryItems = <?= json_encode($gallery, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const journeyItems = <?= json_encode($journeyItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const blogArticles = <?= json_encode($articles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const isGalleryPage = <?= $isGalleryPage ? 'true' : 'false' ?>;
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const portraitCard = document.getElementById('portraitCard');
      const portraitFlip = document.getElementById('portraitFlip');
      const portraitFront = document.getElementById('portraitFront');
      const portraitBio = document.getElementById('portraitBio');
      const blogGrid = document.getElementById('blogGrid');
      const blogFull = document.getElementById('blogFull');
      const blogFullMedia = document.getElementById('blogFullMedia');
      const blogStatus = document.getElementById('blogStatus');
      let activeBlogTrigger = null;
      let currentBlogArticle = null;
      let currentBlogUrl = '';
      let activeTrigger = null;
      let toastTimer;
      let searchItems = [];
      let matchingItems = [];
      let activeSearchIndex = -1;
      const contactForm = document.getElementById('contactForm');
      const contactFormStatus = document.getElementById('contactFormStatus');
      if (contactForm && contactFormStatus) {
        contactForm.addEventListener('submit', async event => {
          event.preventDefault();
          const submitButton = contactForm.querySelector('button[type="submit"]');
          const formData = new FormData(contactForm);
          if (submitButton) submitButton.disabled = true;
          contactFormStatus.textContent = 'Sending your message…';
          contactFormStatus.dataset.state = 'pending';
          try {
            const response = await fetch('portfolio_contact_api.php', {
              method:'POST',
              headers:{'Content-Type':'application/json','X-CSRF-Token':socialFeedCsrfToken},
              body:JSON.stringify({
                name:formData.get('name'),
                email:formData.get('email'),
                subject:formData.get('subject'),
                message:formData.get('message')
              })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Your message could not be sent.');
            contactForm.reset();
            contactFormStatus.textContent = 'Your message was sent. Thank you for getting in touch!';
            contactFormStatus.dataset.state = 'success';
          } catch (error) {
            contactFormStatus.textContent = error instanceof Error ? error.message : 'Unable to send your message. Please try again.';
            contactFormStatus.dataset.state = 'error';
          } finally {
            if (submitButton) submitButton.disabled = false;
          }
        });
      }
      if (subscriptionButton && subscriptionDialog && subscriptionForm && subscriptionEmail && subscriptionStatus && subscriptionSubmit) {
        subscriptionButton.addEventListener('click', () => {
          subscriptionStatus.textContent = '';
          subscriptionStatus.removeAttribute('data-state');
          subscriptionDialog.showModal();
          subscriptionEmail.focus();
        });
        subscriptionDialog.querySelectorAll('[data-subscription-close]').forEach(button => {
          button.addEventListener('click', () => subscriptionDialog.close());
        });
        subscriptionDialog.addEventListener('click', event => {
          if (event.target === subscriptionDialog) subscriptionDialog.close();
        });
        subscriptionDialog.addEventListener('close', () => {
          if (subscriptionButton.disabled) menuButton.focus();
          else subscriptionButton.focus();
        });
        subscriptionForm.addEventListener('submit', async event => {
          event.preventDefault();
          if (!subscriptionMetadataConsent.checked) {
            subscriptionStatus.textContent = 'Consent is required to store signup IP/device details and request approximate location from ipwho.is.';
            subscriptionStatus.dataset.state = 'error';
            subscriptionMetadataConsent.focus();
            return;
          }
          if (!subscriptionForm.reportValidity()) return;

          subscriptionSubmit.disabled = true;
          subscriptionEmail.disabled = true;
          subscriptionStatus.textContent = 'Sending your subscription request…';
          subscriptionStatus.removeAttribute('data-state');
          try {
            const response = await fetch('portfolio_subscription_api.php', {
              method:'POST',
              headers:{'Content-Type':'application/json','X-CSRF-Token':socialFeedCsrfToken},
              body:JSON.stringify({
                email:subscriptionEmail.value.trim(),
                company:document.getElementById('subscriptionCompany').value,
                metadata_consent:subscriptionMetadataConsent.checked
              })
            });
            const responseBody = await response.text();
            let result;
            try {
              result = JSON.parse(responseBody);
            } catch {
              throw new Error('The subscription service returned an invalid response. Please check the server PHP error log.');
            }
            if (!response.ok) throw new Error(result.error || 'The subscription could not be completed.');
            subscriptionButton.disabled = true;
            subscriptionButton.textContent = 'Subscribed ✓';
            subscriptionButton.setAttribute('aria-label', 'You are subscribed to portfolio updates');
            subscriptionButton.classList.remove('subscription-button');
            subscriptionSubmit.textContent = 'Subscribed';
            subscriptionStatus.textContent = result.already_subscribed
              ? 'This email is already subscribed to new-post updates.'
              : result.email_sent === false
                ? 'You are subscribed. The confirmation email could not be sent right now.'
                : 'Thanks for subscribing! A thank-you email is on its way.';
            subscriptionStatus.dataset.state = 'success';
            if (Number.isInteger(result.subscriber_count)) {
              document.querySelectorAll('.stat-item').forEach(stat => {
                const label = stat.querySelector('span');
                const value = stat.querySelector('strong');
                if (!label || !value || !['subscriber number', 'subscribers'].includes(label.textContent.trim().toLowerCase())) return;
                value.dataset.count = String(result.subscriber_count);
                value.textContent = `${result.subscriber_count}${value.dataset.suffix || ''}`;
              });
            }
          } catch (error) {
            subscriptionEmail.disabled = false;
            subscriptionSubmit.disabled = false;
            subscriptionStatus.textContent = error instanceof Error ? error.message : 'Unable to reach the subscription service. Please try again.';
            subscriptionStatus.dataset.state = 'error';
          }
        });
      }
      document.querySelectorAll('.subscription-provider[href]').forEach(link => {
        link.addEventListener('click', () => {
          link.classList.add('is-loading');
          link.setAttribute('aria-busy', 'true');
          link.querySelector('.subscription-provider-indicator')?.remove();
        });
      });
      const subscriptionRedirect = new URLSearchParams(window.location.search);
      if (subscriptionRedirect.get('subscription') === 'oauth' && subscriptionDialog && subscriptionForm) {
        window.history.replaceState(null, '', `${window.location.pathname}${window.location.hash}`);
        subscriptionDialog.showModal();
        subscriptionStatus.textContent = 'Verifying your social account…';
        fetch('portfolio_subscription_api.php?oauth_result=1', {
          credentials:'same-origin',
          headers:{Accept:'application/json'}
        }).then(async response => {
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'Could not verify the social account.');
          if (typeof result.email !== 'string') throw new Error('The identity provider did not return an email address.');
          subscriptionEmail.value = result.email;
          subscriptionStatus.textContent = 'Review the privacy notice and check the consent box to finish subscribing.';
          subscriptionStatus.dataset.state = 'info';
        }).catch(error => {
          subscriptionStatus.textContent = error instanceof Error ? error.message : 'Could not complete social sign-in.';
          subscriptionStatus.dataset.state = 'error';
        });
      }
      const productAdDialog = document.getElementById('productAdDialog');
      if (productAdDialog) {
        const productAdPanel = document.querySelector('.product-ad-grid-panel');
        if (productAdPanel) {
          const dismissButton = productAdPanel.querySelector('[data-ad-dismiss]');
          const seeMoreButton = productAdPanel.querySelector('[data-ad-see-more]');
          let dismissTimer = null;
          let remainingDismissTime = 5000;
          let dismissDeadline = 0;
          let panelDismissed = false;
          const pauseDismissTimer = () => {
            if (dismissTimer === null) return;
            window.clearTimeout(dismissTimer);
            dismissTimer = null;
            remainingDismissTime = Math.max(0, dismissDeadline - performance.now());
          };
          const startDismissTimer = () => {
            if (dismissTimer !== null || panelDismissed || productAdPanel.classList.contains('is-expanded') ||
                document.hidden || productAdPanel.matches(':hover') || productAdPanel.contains(document.activeElement)) return;
            dismissDeadline = performance.now() + remainingDismissTime;
            dismissTimer = window.setTimeout(() => {
              dismissTimer = null;
              dismissPanel();
            }, remainingDismissTime);
          };
          const dismissPanel = () => {
            if (panelDismissed) return;
            pauseDismissTimer();
            panelDismissed = true;
            productAdPanel.classList.add('is-dismissed');
            productAdPanel.setAttribute('aria-hidden', 'true');
            productAdPanel.inert = true;
            document.getElementById('main').focus({preventScroll:true});
          };
          productAdPanel.addEventListener('pointerenter', pauseDismissTimer);
          productAdPanel.addEventListener('pointerleave', startDismissTimer);
          productAdPanel.addEventListener('focusin', pauseDismissTimer);
          productAdPanel.addEventListener('focusout', event => {
            if (!productAdPanel.contains(event.relatedTarget)) startDismissTimer();
          });
          dismissButton.addEventListener('click', dismissPanel);
          seeMoreButton.addEventListener('click', () => {
            const expanded = !productAdPanel.classList.contains('is-expanded');
            productAdPanel.classList.toggle('is-expanded', expanded);
            seeMoreButton.setAttribute('aria-expanded', String(expanded));
            seeMoreButton.textContent = expanded ? 'Show less' : 'See more';
            if (expanded) pauseDismissTimer();
            else startDismissTimer();
          });
          document.addEventListener('visibilitychange', () => {
            if (document.hidden) pauseDismissTimer();
            else startDismissTimer();
          });
          startDismissTimer();
        }
        const productAdSlideshow = document.getElementById('productAdSlideshow');
        const productAdSlides = document.getElementById('productAdSlides');
        const productAdSlideControls = productAdSlideshow.querySelector('.product-ad-slide-controls');
        const productAdSlideDots = document.getElementById('productAdSlideDots');
        const productAdSlideStatus = document.getElementById('productAdSlideStatus');
        const productAdDialogBrand = document.getElementById('productAdDialogBrand');
        const productAdDialogTitle = document.getElementById('productAdDialogTitle');
        const productAdDialogDescription = document.getElementById('productAdDialogDescription');
        const productAdDialogLink = document.getElementById('productAdDialogLink');
        const productAdDialogPosterLink = document.getElementById('productAdDialogPosterLink');
        const productAdDialogPdfLink = document.getElementById('productAdDialogPdfLink');
        let productAdDialogCloseTimer = null;
        let productAdSlideTimer = null;
        let productAdCurrentSlide = 0;
        let productAdMediaSlides = [];
        const stopProductAdSlideTimer = () => {
          window.clearInterval(productAdSlideTimer);
          productAdSlideTimer = null;
        };
        const showProductAdSlide = index => {
          if (productAdMediaSlides.length === 0) return;
          productAdCurrentSlide = (index + productAdMediaSlides.length) % productAdMediaSlides.length;
          productAdMediaSlides.forEach((slide, slideIndex) => {
            const active = slideIndex === productAdCurrentSlide;
            slide.element.classList.toggle('is-active', active);
            slide.element.setAttribute('aria-hidden', String(!active));
            const video = slide.element.querySelector('video');
            if (video && !active) video.pause();
          });
          productAdSlideDots.querySelectorAll('.product-ad-slide-dot').forEach((dot, dotIndex) => {
            dot.setAttribute('aria-current', String(dotIndex === productAdCurrentSlide));
          });
          productAdSlideStatus.textContent = `${productAdCurrentSlide + 1} of ${productAdMediaSlides.length} · ${productAdMediaSlides[productAdCurrentSlide].label}`;
        };
        const startProductAdSlideTimer = () => {
          stopProductAdSlideTimer();
          if (reducedMotion || productAdMediaSlides.length < 2 || !productAdDialog.open ||
              document.hidden || productAdSlideshow.matches(':hover') ||
              productAdSlideshow.contains(document.activeElement) ||
              Array.from(productAdSlideshow.querySelectorAll('video')).some(video => !video.paused && !video.ended)) return;
          productAdSlideTimer = window.setInterval(() => {
            showProductAdSlide(productAdCurrentSlide + 1);
          }, 5000);
        };
        const setProductAdSlides = (mediaItems, title) => {
          stopProductAdSlideTimer();
          productAdMediaSlides = mediaItems.map((media, index) => {
            const slide = document.createElement('div');
            slide.className = `product-ad-media-slide${index === 0 ? ' is-active' : ''}`;
            slide.setAttribute('role', 'group');
            slide.setAttribute('aria-roledescription', 'slide');
            slide.setAttribute('aria-label', `${media.label}, ${index + 1} of ${mediaItems.length}`);
            slide.setAttribute('aria-hidden', String(index !== 0));
            if (media.type === 'video') {
              const video = document.createElement('video');
              video.controls = true;
              video.preload = 'metadata';
              video.playsInline = true;
              video.setAttribute('aria-label', `${title} video`);
              if (media.poster) video.poster = media.poster;
              const source = document.createElement('source');
              source.src = media.src;
              video.append(source);
              slide.append(video);
            } else {
              const image = document.createElement('img');
              image.src = media.src;
              image.alt = `${title} ${media.label.toLowerCase()}`;
              image.loading = 'eager';
              image.decoding = 'async';
              slide.append(image);
            }
            productAdSlides.append(slide);
            return {element:slide, label:media.label};
          });
          productAdSlideDots.replaceChildren(...mediaItems.map((media, index) => {
            const dot = document.createElement('button');
            dot.className = 'product-ad-slide-dot';
            dot.type = 'button';
            dot.setAttribute('aria-label', `Show ${media.label.toLowerCase()}`);
            dot.setAttribute('aria-current', String(index === 0));
            dot.addEventListener('click', () => {
              showProductAdSlide(index);
              startProductAdSlideTimer();
            });
            return dot;
          }));
          productAdSlideControls.hidden = mediaItems.length < 2;
          productAdSlideDots.hidden = mediaItems.length < 2;
          showProductAdSlide(0);
          productAdSlideshow.querySelectorAll('video').forEach(video => {
            video.addEventListener('play', stopProductAdSlideTimer);
            video.addEventListener('pause', startProductAdSlideTimer);
            video.addEventListener('ended', startProductAdSlideTimer);
          });
        };
        const closeProductAdDialog = () => {
          if (!productAdDialog.open) return;
          stopProductAdSlideTimer();
          productAdDialog.classList.remove('is-open');
          window.clearTimeout(productAdDialogCloseTimer);
          productAdDialogCloseTimer = window.setTimeout(() => {
            if (productAdDialog.open) productAdDialog.close();
            productAdDialogCloseTimer = null;
          }, reducedMotion ? 0 : 320);
        };
        document.querySelectorAll('[data-ad-details]').forEach(button => {
          button.addEventListener('click', () => {
            const {adImage, adPoster, adVideo, adPdf, adTitle, adBrand, adDescription, adTargetUrl, adCtaLabel} = button.dataset;
            window.clearTimeout(productAdDialogCloseTimer);
            productAdDialogCloseTimer = null;
            const mediaItems = [{type:'image', src:adImage, label:'Product image'}];
            if (adPoster) mediaItems.push({type:'image', src:adPoster, label:'Poster'});
            if (adVideo) mediaItems.push({type:'video', src:adVideo, poster:adPoster, label:'Video'});
            setProductAdSlides(mediaItems, adTitle);
            productAdDialogBrand.textContent = `Sponsored by ${adBrand}`;
            productAdDialogTitle.textContent = adTitle;
            productAdDialogDescription.textContent = adDescription;
            if (adTargetUrl) {
              productAdDialogLink.href = adTargetUrl;
              productAdDialogLink.textContent = `${adCtaLabel || 'Learn more'} →`;
              productAdDialogLink.hidden = false;
            } else {
              productAdDialogLink.removeAttribute('href');
              productAdDialogLink.hidden = true;
            }
            productAdDialogPosterLink.hidden = !adPoster;
            if (adPoster) productAdDialogPosterLink.href = adPoster;
            else productAdDialogPosterLink.removeAttribute('href');
            productAdDialogPdfLink.hidden = !adPdf;
            if (adPdf) productAdDialogPdfLink.href = adPdf;
            else productAdDialogPdfLink.removeAttribute('href');
            if (!productAdDialog.open) productAdDialog.showModal();
            productAdDialog.classList.remove('is-open');
            requestAnimationFrame(() => {
              if (productAdDialog.open) {
                productAdDialog.classList.add('is-open');
                startProductAdSlideTimer();
              }
            });
          });
        });
        productAdSlideshow.querySelectorAll('[data-ad-slide-step]').forEach(button => {
          button.addEventListener('click', () => {
            showProductAdSlide(productAdCurrentSlide + Number(button.dataset.adSlideStep));
            startProductAdSlideTimer();
          });
        });
        productAdSlideshow.addEventListener('keydown', event => {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
          event.preventDefault();
          showProductAdSlide(productAdCurrentSlide + (event.key === 'ArrowRight' ? 1 : -1));
          startProductAdSlideTimer();
        });
        productAdSlideshow.addEventListener('mouseenter', stopProductAdSlideTimer);
        productAdSlideshow.addEventListener('mouseleave', startProductAdSlideTimer);
        productAdSlideshow.addEventListener('focusin', stopProductAdSlideTimer);
        productAdSlideshow.addEventListener('focusout', event => {
          if (!productAdSlideshow.contains(event.relatedTarget)) startProductAdSlideTimer();
        });
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) stopProductAdSlideTimer();
          else startProductAdSlideTimer();
        });
        productAdDialog.querySelectorAll('[data-ad-dialog-close]').forEach(button => {
          button.addEventListener('click', closeProductAdDialog);
        });
        productAdDialog.addEventListener('click', event => {
          if (event.target === productAdDialog) closeProductAdDialog();
        });
        productAdDialog.addEventListener('cancel', event => {
          event.preventDefault();
          closeProductAdDialog();
        });
        productAdDialog.addEventListener('close', () => {
          stopProductAdSlideTimer();
          productAdSlides.replaceChildren();
          productAdSlideDots.replaceChildren();
          productAdMediaSlides = [];
          productAdDialog.classList.remove('is-open');
          window.clearTimeout(productAdDialogCloseTimer);
          productAdDialogCloseTimer = null;
        });
      }

      portraitFlip.addEventListener('click', () => {
        const isExpanded = portraitFlip.getAttribute('aria-expanded') !== 'true';
        portraitCard.classList.toggle('is-flipped', isExpanded);
        portraitFlip.setAttribute('aria-expanded', String(isExpanded));
        portraitFlip.setAttribute('aria-label', isExpanded ? 'Show Shumi’s portrait' : 'Read Shumi’s biography');
        portraitFront.setAttribute('aria-hidden', String(isExpanded));
        portraitBio.setAttribute('aria-hidden', String(!isExpanded));
      });

      const blogViewKey = index => `portfolio-blog-views-${index}`;
      const getBlogViews = index => {
        try {
          const value = Number(window.localStorage.getItem(blogViewKey(index)) || 0);
          return Number.isSafeInteger(value) && value >= 0 ? value : 0;
        } catch (error) {
          console.error('Could not read the local blog view count.', error);
          return 0;
        }
      };
      const incrementBlogViews = index => {
        const views = getBlogViews(index) + 1;
        try {
          window.localStorage.setItem(blogViewKey(index), String(views));
        } catch (error) {
          console.error('Could not save the local blog view count.', error);
          showToast('This device could not save the article view count.');
        }
        return views;
      };
      const closeBlogFull = (restoreFocus = true) => {
        blogFull.hidden = true;
        blogGrid.hidden = false;
        blogFullMedia.replaceChildren();
        currentBlogArticle = null;
        activeBlogTrigger?.setAttribute('aria-expanded', 'false');
        if (restoreFocus) activeBlogTrigger?.focus();
      };
      const openBlogFull = (index, trigger) => {
        const article = blogArticles[index];
        if (!article) {
          console.error('Could not find details for the selected blog article.');
          return;
        }
        activeBlogTrigger?.setAttribute('aria-expanded', 'false');
        activeBlogTrigger = trigger;
        activeBlogTrigger.setAttribute('aria-expanded', 'true');
        currentBlogArticle = article;
        const articleId = `article-${index}`;
        currentBlogUrl = new URL(`#${articleId}`, window.location.href).href;
        document.getElementById('blogFullCategory').textContent = article.category;
        document.getElementById('blogFullTitle').textContent = article.title;
        const fullDate = document.getElementById('blogFullDate');
        fullDate.textContent = article.date;
        fullDate.dateTime = article.date_iso;
        const viewCount = incrementBlogViews(index);
        const blogViews = document.getElementById('blogFullViews');
        blogViews.textContent = String(viewCount);
        blogViews.setAttribute('aria-label', `${viewCount} views on this device`);
        document.getElementById('blogFullBody').textContent = article.body;

        blogFullMedia.replaceChildren();
        const media = document.createElement(article.media_type === 'video' ? 'video' : 'img');
        media.src = article.image;
        if (media instanceof HTMLImageElement) {
          media.alt = article.image_alt || article.title;
        } else {
          media.controls = true;
          media.preload = 'metadata';
          media.setAttribute('aria-label', article.image_alt || article.title);
        }
        blogFullMedia.append(media);

        const shareUrl = encodeURIComponent(currentBlogUrl);
        const shareTitle = encodeURIComponent(article.title);
        const shareText = encodeURIComponent(`${article.title} — ${currentBlogUrl}`);
        const socialDestinations = [
          ['Facebook', 'fa-brands fa-facebook-f', `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`],
          ['Telegram', 'fa-brands fa-telegram', `https://t.me/share/url?url=${shareUrl}&text=${shareTitle}`],
          ['WhatsApp', 'fa-brands fa-whatsapp', `https://wa.me/?text=${shareText}`],
          ['TikTok', 'fa-brands fa-tiktok', 'https://www.tiktok.com/'],
          ['LinkedIn', 'fa-brands fa-linkedin-in', `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`],
        ];
        const socialLinks = document.getElementById('blogSocialLinks');
        socialLinks.replaceChildren(...socialDestinations.map(([name, iconClass, href]) => {
          const link = document.createElement('a');
          link.className = 'blog-social-link';
          link.href = href;
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          link.setAttribute('aria-label', name === 'TikTok' ? 'Open TikTok to share this article manually' : `Share on ${name}`);
          link.title = name === 'TikTok' ? 'Open TikTok to share manually' : `Share on ${name}`;
          const icon = document.createElement('i');
          icon.className = iconClass;
          icon.setAttribute('aria-hidden', 'true');
          link.append(icon);
          return link;
        }));
        blogGrid.hidden = true;
        blogFull.hidden = false;
        blogStatus.textContent = `Opened article: ${article.title}. View count is stored on this device only.`;
        blogFull.focus({preventScroll:true});
        blogFull.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'start'});
      };
      document.querySelectorAll('[data-blog-open]').forEach(button => button.addEventListener('click', () => {
        openBlogFull(Number(button.dataset.blogOpen), button);
      }));
      document.getElementById('blogBack').addEventListener('click', () => closeBlogFull());
      document.getElementById('blogShare').addEventListener('click', async () => {
        if (!currentBlogArticle || !currentBlogUrl) {
          console.error('Cannot share a blog article before opening it.');
          showToast('Open an article before sharing.');
          return;
        }
        try {
          if (navigator.share) {
            await navigator.share({title:currentBlogArticle.title, text:currentBlogArticle.summary, url:currentBlogUrl});
          } else if (navigator.clipboard) {
            await navigator.clipboard.writeText(currentBlogUrl);
            showToast('Article link copied.');
          } else {
            showToast('Sharing is unavailable in this browser. Copy the article URL from your address bar.');
          }
        } catch (error) {
          if (error.name !== 'AbortError') {
            console.error('Could not share the blog article.', error);
            showToast('The article could not be shared.');
          }
        }
      });
      window.setTimeout(() => {
        const sharedArticle = /^#article-(\d+)$/.exec(window.location.hash);
        if (!sharedArticle) return;
        const trigger = document.querySelector(`[data-blog-open="${sharedArticle[1]}"]`);
        if (trigger) openBlogFull(Number(sharedArticle[1]), trigger);
      }, 0);

      document.getElementById('year').textContent = String(new Date().getFullYear());
      const roleTyping = document.getElementById('heroRoleTyping');
      const introTyping = document.getElementById('heroIntroTyping');
      if (reducedMotion) {
        roleTyping.textContent = <?= json_encode(implode(' | ', $homeRoles), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
      } else {
        const roles = <?= json_encode($homeRoles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const introduction = <?= json_encode($portfolio['intro'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
        let roleIndex = 0;
        let roleCharacter = 0;
        let deletingRole = false;
        let introCharacter = 0;
        introTyping.textContent = '';
        const typeIntroduction = () => {
          introCharacter += 1;
          introTyping.textContent = introduction.slice(0, introCharacter);
          if (introCharacter < introduction.length) window.setTimeout(typeIntroduction, 38);
        };
        const typeRole = () => {
          const role = roles[roleIndex];
          roleCharacter += deletingRole ? -1 : 1;
          roleTyping.textContent = role.slice(0, roleCharacter);
          if (!deletingRole && roleCharacter === role.length) {
            deletingRole = true;
            window.setTimeout(typeRole, 1400);
          } else if (deletingRole && roleCharacter === 0) {
            deletingRole = false;
            roleIndex = (roleIndex + 1) % roles.length;
            window.setTimeout(typeRole, 260);
          } else {
            window.setTimeout(typeRole, deletingRole ? 35 : 72);
          }
        };
        roleTyping.textContent = '';
        typeIntroduction();
        typeRole();
      }
      const themes = ['dark', 'light', 'black', 'light-black', 'natural', 'light-pure'];
      const themeNames = {dark:'Dark', light:'Light', black:'Black', 'light-black':'Light Black', natural:'Natural', 'light-pure':'Light Pure'};
      const themeColors = {dark:'#11131b', light:'#f0fff0', black:'#000000', 'light-black':'#18181b', natural:'#f4efe6', 'light-pure':'#ffffff'};
      const themeColorMeta = document.getElementById('themeColorMeta');
      const updateThemeButton = theme => {
        const nextTheme = themes[(themes.indexOf(theme) + 1) % themes.length];
        themeButton.setAttribute('aria-label', `Switch to ${themeNames[nextTheme]} theme`);
        themeButton.title = `Current theme: ${themeNames[theme]}. Switch to ${themeNames[nextTheme]}.`;
        themeButton.textContent = theme === 'dark' || theme === 'black' ? '☼' : '☾';
        if (themeColorMeta) themeColorMeta.content = themeColors[theme];
      };
      updateThemeButton(root.dataset.theme);
      themeButton.addEventListener('click', () => {
        const currentTheme = themes.includes(root.dataset.theme) ? root.dataset.theme : 'dark';
        const nextTheme = themes[(themes.indexOf(currentTheme) + 1) % themes.length];
        root.dataset.theme = nextTheme;
        updateThemeButton(nextTheme);
      });
      menuButton.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        menuButton.textContent = open ? '×' : '☰';
        nav.classList.toggle('open', open);
      });
      const closeMenu = () => {
        nav.classList.remove('open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', 'Open navigation menu');
        menuButton.textContent = '☰';
      };
      let navDropdownCloseTimer = null;
      const closeNavDropdownsAnimated = () => {
        window.clearTimeout(navDropdownCloseTimer);
        const openDropdowns = Array.from(nav.querySelectorAll('.nav-dropdown[open],.nav-subdropdown[open]'));
        if (!openDropdowns.length) return;
        if (reducedMotion) {
          openDropdowns.forEach(dropdown => {
            dropdown.open = false;
            dropdown.classList.remove('is-closing');
          });
          return;
        }
        openDropdowns.forEach(dropdown => dropdown.classList.add('is-closing'));
        navDropdownCloseTimer = window.setTimeout(() => {
          openDropdowns.forEach(dropdown => {
            dropdown.open = false;
            dropdown.classList.remove('is-closing');
          });
          navDropdownCloseTimer = null;
        }, 180);
      };
      nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        closeNavDropdownsAnimated();
        if (window.matchMedia('(max-width: 760px)').matches) {
          window.setTimeout(closeMenu, reducedMotion ? 0 : 180);
        }
      }));
      nav.querySelectorAll('a[data-project-filter]').forEach(link => link.addEventListener('click', () => {
        const filter = ({design:'portfolio', development:'platform', strategy:'application'})[link.dataset.projectFilter] || link.dataset.projectFilter;
        if (filter) document.querySelector(`.filter[data-filter="${filter}"]`)?.click();
      }));
      nav.querySelectorAll('.nav-dropdown').forEach(dropdown => dropdown.addEventListener('toggle', () => {
        if (!dropdown.open) {
          dropdown.querySelectorAll('.nav-subdropdown[open]').forEach(submenu => {
            submenu.open = false;
          });
          return;
        }
        nav.querySelectorAll('.nav-dropdown[open]').forEach(other => {
          if (other !== dropdown) other.open = false;
        });
      }));
      const closeSearch = () => {
        searchResults.hidden = true;
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.removeAttribute('aria-activedescendant');
        activeSearchIndex = -1;
      };
      const selectSearchResult = item => {
        const target = document.getElementById(item.id);
        if (!target) return;
        if (!blogFull.hidden && !target.closest('#blogFull')) {
          closeBlogFull(false);
        }
        if (target.closest('.gallery-card') && !galleryFull.hidden) {
          galleryFull.hidden = true;
          galleryGrid.hidden = false;
          stopGalleryFullTimer();
          currentGalleryItem = null;
        }
        if (target.closest('.journey-card') && !journeyFull.hidden) {
          closeJourneyFull(false);
        }
        if (target.closest('#work .project') && !projectFull.hidden) {
          closeProjectFull(false);
        }
        if (target.closest('.project[hidden]')) {
          document.querySelector('.filter[data-filter="all"]')?.click();
        }
        if (target.closest('.gallery-card[hidden]')) {
          document.querySelector('.gallery-category-filter[data-gallery-filter="all"]')?.click();
        }
        if (target.closest('.journey-card[hidden]')) {
          document.querySelector('.journey-filter[data-journey-filter="all"]')?.click();
        }
        closeSearch();
        searchInput.value = '';
        target.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'start'});
        if (window.matchMedia('(max-width: 760px)').matches) closeMenu();
      };
      const renderSearchResults = () => {
        const query = searchInput.value.trim().toLocaleLowerCase();
        searchResults.replaceChildren();
        searchInput.removeAttribute('aria-activedescendant');
        matchingItems = [];
        activeSearchIndex = -1;
        if (!query) {
          closeSearch();
          return;
        }

        matchingItems = searchItems.filter(item => `${item.title} ${item.content}`.toLocaleLowerCase().includes(query)).slice(0, 7);
        searchInput.setAttribute('aria-expanded', 'true');
        searchResults.hidden = false;
        if (matchingItems.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'search-empty';
          empty.setAttribute('role', 'status');
          empty.textContent = 'No results found.';
          searchResults.append(empty);
          return;
        }

        matchingItems.forEach((item, index) => {
          const option = document.createElement('button');
          option.className = 'search-result';
          option.type = 'button';
          option.id = `search-option-${index}`;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', 'false');
          const title = document.createElement('span');
          title.textContent = item.title;
          const category = document.createElement('small');
          category.textContent = item.category;
          option.append(title, category);
          option.addEventListener('click', () => selectSearchResult(item));
          searchResults.append(option);
        });
      };
      document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if (event.target instanceof Element && event.target.closest('.search-wrap')) {
          closeSearch();
          searchInput.value = '';
          matchingItems = [];
          return;
        }
        if (!projectFull.hidden) {
          closeProjectFull();
          return;
        }
        if (!blogFull.hidden) {
          closeBlogFull();
          return;
        }
        const openSubdropdown = nav.querySelector('.nav-subdropdown[open]');
        if (openSubdropdown) {
          openSubdropdown.open = false;
          openSubdropdown.querySelector('summary').focus();
          return;
        }
        const openDropdown = nav.querySelector('.nav-dropdown[open]');
        if (openDropdown) {
          openDropdown.open = false;
          openDropdown.querySelector('summary').focus();
          return;
        }
        if (nav.classList.contains('open')) {
          closeMenu();
          menuButton.focus();
        }
      });
      searchItems = [
        ...Array.from(document.querySelectorAll('main section[id]'), section => {
          const heading = section.querySelector('h1, h2');
          return {id:section.id, title:heading?.textContent.trim() || section.id, category:'Section', content:section.textContent};
        }),
        ...Array.from(document.querySelectorAll('.project[id]'), card => ({
          id:card.id, title:card.querySelector('h3')?.textContent.trim() || 'Project', category:'Project', content:card.textContent
        })),
        ...Array.from(document.querySelectorAll('.gallery-card[id]'), card => ({
          id:card.id, title:card.querySelector('strong')?.textContent.trim() || 'Gallery', category:'Gallery', content:card.textContent
        })),
        ...Array.from(document.querySelectorAll('.journey-card[id]'), card => ({
          id:card.id, title:card.querySelector('h3')?.textContent.trim() || 'Journey', category:'Journey', content:card.textContent
        })),
        ...Array.from(document.querySelectorAll('.article-card[id]'), card => ({
          id:card.id, title:card.querySelector('h3')?.textContent.trim() || 'Blog note', category:'Blog', content:card.textContent
        }))
      ];
      searchItems = searchItems.filter(item => isGalleryPage
        ? item.id === 'gallery' || item.id.startsWith('gallery-item-')
        : item.id !== 'gallery' && !item.id.startsWith('gallery-item-'));
      searchItems = searchItems.filter(item => {
        const target = document.getElementById(item.id);
        return target !== null && target.closest('section[hidden]') === null;
      });
      searchInput.addEventListener('input', renderSearchResults);
      searchInput.addEventListener('keydown', event => {
        if (matchingItems.length === 0) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          const direction = event.key === 'ArrowDown' ? 1 : -1;
          activeSearchIndex = activeSearchIndex < 0
            ? (direction > 0 ? 0 : matchingItems.length - 1)
            : (activeSearchIndex + direction + matchingItems.length) % matchingItems.length;
          searchResults.querySelectorAll('[role="option"]').forEach((option, index) => {
            const selected = index === activeSearchIndex;
            option.setAttribute('aria-selected', String(selected));
            if (selected) searchInput.setAttribute('aria-activedescendant', option.id);
          });
        } else if (event.key === 'Enter' && activeSearchIndex >= 0) {
          event.preventDefault();
          selectSearchResult(matchingItems[activeSearchIndex]);
        }
      });
      searchForm.addEventListener('submit', event => {
        event.preventDefault();
        const item = matchingItems[activeSearchIndex] || matchingItems[0];
        if (item) selectSearchResult(item);
        else renderSearchResults();
      });
      document.addEventListener('click', event => {
        if (!event.target.closest('.search-wrap')) closeSearch();
        if (!event.target.closest('.nav-dropdown')) {
          nav.querySelectorAll('.nav-dropdown[open]').forEach(dropdown => {
            dropdown.open = false;
          });
        }
      });

      const updateScroll = () => {
        const range = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.transform = `scaleX(${range > 0 ? window.scrollY / range : 0})`;
        topButton.classList.toggle('visible', window.scrollY > 560);
      };
      window.addEventListener('scroll', updateScroll, {passive:true});
      updateScroll();

      const projectGrid = document.getElementById('projectGrid');
      const projectFull = document.getElementById('projectFull');
      let activeProjectTrigger = null;
      const closeProjectFull = (restoreFocus = true) => {
        projectFull.hidden = true;
        projectGrid.hidden = false;
        if (restoreFocus) activeProjectTrigger?.focus();
      };
      document.querySelectorAll('#work .filter').forEach(button => button.addEventListener('click', () => {
        const filter = button.dataset.filter;
        document.querySelectorAll('#work .filter').forEach(item => {
          const selected = item === button;
          item.classList.toggle('active', selected);
          item.setAttribute('aria-pressed', String(selected));
        });
        if (!projectFull.hidden) closeProjectFull(false);
        document.querySelectorAll('#work .project').forEach(card => {
          card.hidden = filter !== 'all' && card.dataset.category !== filter;
        });
      }));

      document.querySelectorAll('.faq-filter').forEach(button => button.addEventListener('click', () => {
        const filter = button.dataset.faqFilter;
        document.querySelectorAll('.faq-filter').forEach(item => {
          const selected = item === button;
          item.classList.toggle('active', selected);
          item.setAttribute('aria-pressed', String(selected));
        });
        document.querySelectorAll('.faq-card').forEach(card => {
          card.hidden = filter !== 'all' && card.dataset.faqCategory !== filter;
        });
      }));

      const journeyGrid = document.getElementById('journeyGrid');
      const journeyFull = document.getElementById('journeyFull');
      const journeyFullMedia = document.getElementById('journeyFullMedia');
      const journeyFullDots = document.getElementById('journeyFullDots');
      const journeyAttachments = document.getElementById('journeyAttachments');
      const journeyFullStatus = document.getElementById('journeyFullStatus');
      let activeJourneyTrigger = null;
      let currentJourneyImages = [];
      let currentJourneyImage = 0;
      let journeyFullTimer = null;
      const showJourneyImage = (images, dots, index, status) => {
        if (!images.length) return;
        const selectedIndex = (index + images.length) % images.length;
        images.forEach((image, imageIndex) => {
          const active = imageIndex === selectedIndex;
          image.classList.toggle('is-active', active);
          image.setAttribute('aria-hidden', String(!active));
        });
        dots.querySelectorAll('.journey-dot').forEach((dot, dotIndex) => {
          dot.setAttribute('aria-current', String(dotIndex === selectedIndex));
        });
        if (status) status.textContent = `Image ${selectedIndex + 1} of ${images.length}`;
        return selectedIndex;
      };
      const stopJourneyFullTimer = () => {
        window.clearInterval(journeyFullTimer);
        journeyFullTimer = null;
      };
      const startJourneyFullTimer = () => {
        stopJourneyFullTimer();
        if (reducedMotion || journeyFull.hidden || currentJourneyImages.length < 2 ||
            document.hidden || journeyFull.matches(':hover') || journeyFullMedia.contains(document.activeElement)) return;
        journeyFullTimer = window.setInterval(() => {
          currentJourneyImage = showJourneyImage(currentJourneyImages, journeyFullDots, currentJourneyImage + 1, journeyFullStatus);
        }, 5000);
      };
      journeyFullMedia.querySelectorAll('[data-journey-full-step]').forEach(button => button.addEventListener('click', () => {
        currentJourneyImage = showJourneyImage(
          currentJourneyImages,
          journeyFullDots,
          currentJourneyImage + Number(button.dataset.journeyFullStep),
          journeyFullStatus
        );
        startJourneyFullTimer();
      }));
      journeyFullDots.addEventListener('click', event => {
        const dot = event.target.closest('[data-journey-full-index]');
        if (!dot) return;
        currentJourneyImage = showJourneyImage(currentJourneyImages, journeyFullDots, Number(dot.dataset.journeyFullIndex), journeyFullStatus);
        startJourneyFullTimer();
      });
      journeyFullMedia.addEventListener('keydown', event => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        currentJourneyImage = showJourneyImage(
          currentJourneyImages,
          journeyFullDots,
          currentJourneyImage + (event.key === 'ArrowRight' ? 1 : -1),
          journeyFullStatus
        );
        startJourneyFullTimer();
      });
      journeyFullMedia.addEventListener('mouseenter', stopJourneyFullTimer);
      journeyFullMedia.addEventListener('mouseleave', startJourneyFullTimer);
      journeyFullMedia.addEventListener('focusin', stopJourneyFullTimer);
      journeyFullMedia.addEventListener('focusout', event => {
        if (!journeyFullMedia.contains(event.relatedTarget)) startJourneyFullTimer();
      });
      journeyFull.addEventListener('mouseenter', stopJourneyFullTimer);
      journeyFull.addEventListener('mouseleave', startJourneyFullTimer);
      journeyFull.addEventListener('focusout', event => {
        if (!journeyFull.contains(event.relatedTarget)) startJourneyFullTimer();
      });
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopJourneyFullTimer();
        else startJourneyFullTimer();
      });
      const closeJourneyFull = (restoreFocus = true) => {
        stopJourneyFullTimer();
        journeyFull.hidden = true;
        journeyGrid.hidden = false;
        journeyFullMedia.querySelectorAll('.journey-full-image').forEach(image => image.remove());
        journeyAttachments.replaceChildren();
        document.getElementById('journeyFullFeatures').replaceChildren();
        document.getElementById('journeyFullIcon').replaceChildren();
        if (restoreFocus) activeJourneyTrigger?.focus();
      };
      const openJourneyFull = (index, trigger) => {
        const item = journeyItems[index];
        if (!item) {
          console.error('Could not find details for the selected journey item.');
          return;
        }
        activeJourneyTrigger = trigger;
        document.getElementById('journeyFullCategory').textContent = item.category_label;
        document.getElementById('journeyFullTitle').textContent = item.title;
        document.getElementById('journeyFullSummary').textContent = item.summary;
        document.getElementById('journeyFullDetail').textContent = item.detail;
        document.getElementById('journeyFullOutcome').textContent = item.outcome;
        const titleIcon = document.getElementById('journeyFullIcon');
        titleIcon.replaceChildren();
        const icon = document.createElement('i');
        icon.classList.add('fa-solid', item.icon);
        titleIcon.append(icon);
        const features = document.getElementById('journeyFullFeatures');
        features.replaceChildren(...item.features.map(feature => {
          const entry = document.createElement('li');
          const check = document.createElement('i');
          check.className = 'fa-solid fa-check';
          check.setAttribute('aria-hidden', 'true');
          entry.append(check, document.createTextNode(feature));
          return entry;
        }));
        const controls = journeyFullMedia.querySelector('.journey-carousel-controls');
        journeyFullMedia.querySelectorAll('.journey-full-image').forEach(image => image.remove());
        currentJourneyImages = item.attachments
          .filter(attachment => attachment.type === 'image')
          .map((attachment, imageIndex) => {
            const image = document.createElement('img');
            image.className = `journey-full-image${imageIndex === 0 ? ' is-active' : ''}`;
            image.src = attachment.src;
            image.alt = attachment.alt || attachment.label || item.title;
            image.setAttribute('aria-hidden', String(imageIndex !== 0));
            journeyFullMedia.insertBefore(image, controls);
            return image;
          });
        currentJourneyImage = 0;
        controls.hidden = currentJourneyImages.length < 2;
        journeyFullDots.replaceChildren(...currentJourneyImages.map((image, imageIndex) => {
          const dot = document.createElement('button');
          dot.className = 'journey-dot';
          dot.type = 'button';
          dot.dataset.journeyFullIndex = String(imageIndex);
          dot.setAttribute('aria-label', `Show image ${imageIndex + 1}`);
          dot.setAttribute('aria-current', String(imageIndex === 0));
          return dot;
        }));
        journeyAttachments.replaceChildren();
        const documentFiles = item.attachments.filter(attachment => attachment.type !== 'image');
        if (item.attachments.length === 0) {
          const note = document.createElement('p');
          note.className = 'journey-no-documents';
          note.textContent = 'No attachments have been added yet.';
          journeyAttachments.append(note);
        } else {
          item.attachments.forEach((attachment, attachmentIndex) => {
            const type = String(attachment.type || '').toLowerCase();
            const icon = document.createElement('i');
            icon.className = `fa-solid ${type === 'image' ? 'fa-image' : (type === 'pdf' ? 'fa-file-pdf' : (type === 'ppt' || type === 'pptx' ? 'fa-file-powerpoint' : 'fa-file'))}`;
            icon.setAttribute('aria-hidden', 'true');
            if (type === 'image') {
              const button = document.createElement('button');
              button.className = 'journey-attachment';
              button.type = 'button';
              button.append(icon, document.createTextNode(attachment.label || `Image ${attachmentIndex + 1}`));
              button.addEventListener('click', () => {
                const imageIndex = currentJourneyImages.findIndex(image => image.src.endsWith(attachment.src));
                if (imageIndex < 0) return;
                currentJourneyImage = showJourneyImage(currentJourneyImages, journeyFullDots, imageIndex, journeyFullStatus);
                startJourneyFullTimer();
              });
              journeyAttachments.append(button);
              return;
            }
            const link = document.createElement('a');
            link.className = 'journey-attachment';
            link.href = attachment.src;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.append(icon, document.createTextNode(attachment.label || type.toUpperCase()));
            journeyAttachments.append(link);
          });
          if (documentFiles.length === 0) {
            const note = document.createElement('p');
            note.className = 'journey-no-documents';
            note.textContent = 'No PDF or PowerPoint attachments have been added yet.';
            journeyAttachments.append(note);
          }
        }
        journeyGrid.hidden = true;
        journeyFull.hidden = false;
        journeyFull.focus({preventScroll:true});
        journeyFull.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'center'});
        startJourneyFullTimer();
      };
      document.querySelectorAll('.journey-filter').forEach(button => button.addEventListener('click', () => {
        const filter = button.dataset.journeyFilter;
        document.querySelectorAll('.journey-filter').forEach(item => {
          const selected = item === button;
          item.setAttribute('aria-pressed', String(selected));
        });
        document.querySelectorAll('.journey-card').forEach((card, index) => {
          const visible = filter === 'all' || card.dataset.journeyCategory === filter;
          card.hidden = !visible;
          if (visible && !reducedMotion) {
            card.style.setProperty('--journey-delay', `${index * 80}ms`);
            card.classList.remove('is-entering');
            requestAnimationFrame(() => card.classList.add('is-entering'));
          }
        });
        if (!journeyFull.hidden && filter !== 'all' && journeyItems[Number(activeJourneyTrigger?.dataset.journeyOpen)]?.category !== filter) {
          closeJourneyFull(false);
        }
      }));
      document.querySelectorAll('[data-journey-carousel]').forEach(carousel => {
        const card = carousel.closest('.journey-card');
        const images = Array.from(carousel.querySelectorAll('.journey-card-image'));
        const dots = carousel.querySelector('.journey-dots');
        if (images.length < 2 || !dots) return;
        let currentImage = 0;
        let timer = null;
        let inView = false;
        const stop = () => {
          window.clearInterval(timer);
          timer = null;
        };
        const start = () => {
          stop();
          if (reducedMotion || !inView || card.hidden || document.hidden ||
              carousel.matches(':hover') || carousel.contains(document.activeElement)) return;
          timer = window.setInterval(() => {
            currentImage = showJourneyImage(images, dots, currentImage + 1);
          }, 4500);
        };
        carousel.querySelectorAll('[data-journey-step]').forEach(button => button.addEventListener('click', () => {
          currentImage = showJourneyImage(images, dots, currentImage + Number(button.dataset.journeyStep));
          start();
        }));
        dots.addEventListener('click', event => {
          const dot = event.target.closest('[data-journey-index]');
          if (!dot) return;
          currentImage = showJourneyImage(images, dots, Number(dot.dataset.journeyIndex));
          start();
        });
        carousel.addEventListener('keydown', event => {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
          event.preventDefault();
          currentImage = showJourneyImage(images, dots, currentImage + (event.key === 'ArrowRight' ? 1 : -1));
          start();
        });
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', event => {
          if (!carousel.contains(event.relatedTarget)) start();
        });
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) stop();
          else start();
        });
        if ('IntersectionObserver' in window) {
          const observer = new IntersectionObserver(entries => {
            inView = entries[0].isIntersecting;
            if (inView) {
              if (!reducedMotion) {
                card.style.setProperty('--journey-delay', `${Number(card.dataset.journeyCard) * 80}ms`);
                card.classList.add('is-entering');
              }
              start();
            } else {
              stop();
            }
          }, {threshold:.1});
          observer.observe(card);
        } else {
          inView = true;
          start();
        }
      });
      document.querySelectorAll('[data-journey-open]').forEach(button => button.addEventListener('click', () => {
        openJourneyFull(Number(button.dataset.journeyOpen), button);
      }));
      document.getElementById('journeyBack').addEventListener('click', closeJourneyFull);

      document.querySelectorAll('.project-open').forEach(button => button.addEventListener('click', () => {
        const project = projects[Number(button.dataset.project)];
        if (!project) return;
        activeProjectTrigger = button;
        document.getElementById('projectFullCategory').textContent = project.category_label;
        document.getElementById('projectFullTitle').textContent = project.title;
        document.getElementById('projectFullSummary').textContent = project.summary;
        document.getElementById('projectFullContributionLabel').textContent = 'My contribution:';
        document.getElementById('projectFullContribution').textContent = project.contribution;
        document.getElementById('projectFullOutcomeLabel').textContent = 'Outcome:';
        document.getElementById('projectFullOutcome').textContent = project.outcome;
        document.getElementById('projectFullImage').src = project.image;
        document.getElementById('projectFullImage').alt = project.image_alt;
        document.getElementById('projectFullPrice').textContent = String(project.price_birr);
        const download = document.getElementById('projectFullDownload');
        download.href = `portfolio_project_payment.php?project_key=${encodeURIComponent(project.project_key)}`;
        download.textContent = `Request source ZIP · ${project.price_birr} Birr`;
        const tags = document.getElementById('projectFullTags');
        tags.replaceChildren(...project.tags.map(tag => {
          const element = document.createElement('span');
          element.textContent = tag;
          return element;
        }));
        projectGrid.hidden = true;
        projectFull.hidden = false;
        projectFull.focus({preventScroll:true});
        projectFull.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'center'});
      }));
      const getProjectRatingKey = group => `portfolio-project-rating-${group.closest('.project')?.dataset.category || ''}`;
      const setProjectRating = (group, rating) => {
        const radios = Array.from(group.querySelectorAll('[role="radio"]'));
        if (!Number.isInteger(rating) || rating < 1 || rating > radios.length) {
          console.error('Could not save an invalid project rating.');
          return;
        }
        radios.forEach((radio, index) => {
          radio.setAttribute('aria-checked', String(index + 1 === rating));
          radio.tabIndex = index + 1 === rating ? 0 : -1;
          radio.dataset.filled = String(index + 1 <= rating);
        });
        const status = group.nextElementSibling;
        status.textContent = `Your rating: ${rating} out of 5 stars`;
        try {
          localStorage.setItem(getProjectRatingKey(group), String(rating));
        } catch (error) {
          console.error('Could not save the project rating in this browser.', error);
          status.textContent = `Your rating: ${rating} out of 5 stars (could not save on this device)`;
        }
      };
      document.querySelectorAll('.project-rating').forEach(group => {
        try {
          const savedRating = Number(localStorage.getItem(getProjectRatingKey(group)));
          if (Number.isInteger(savedRating) && savedRating >= 1 && savedRating <= 5) {
            setProjectRating(group, savedRating);
          }
        } catch (error) {
          console.error('Could not load the saved project rating from this browser.', error);
        }
        group.addEventListener('click', event => {
          const radio = event.target.closest('[role="radio"]');
          if (radio) setProjectRating(group, Number(radio.dataset.rating));
        });
        group.addEventListener('keydown', event => {
          const radio = event.target.closest('[role="radio"]');
          if (!radio) return;
          const currentRating = Number(radio.dataset.rating);
          let nextRating = currentRating;
          if (event.key === 'ArrowRight' || event.key === 'ArrowUp') nextRating = Math.min(5, currentRating + 1);
          else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') nextRating = Math.max(1, currentRating - 1);
          else if (event.key === 'Home') nextRating = 1;
          else if (event.key === 'End') nextRating = 5;
          else return;
          event.preventDefault();
          setProjectRating(group, nextRating);
          group.querySelector(`[data-rating="${nextRating}"]`).focus();
        });
      });
      document.getElementById('projectBack').addEventListener('click', () => closeProjectFull());
      document.querySelectorAll('.service-details').forEach(button => button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        const details = document.getElementById(button.getAttribute('aria-controls'));
        if (!details) {
          console.error('Could not find details for the selected service.');
          return;
        }
        button.setAttribute('aria-expanded', String(!expanded));
        button.querySelector('span').textContent = expanded ? 'See details' : 'Hide details';
        details.hidden = expanded;
      }));
      document.querySelectorAll('.about-value').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('.about-value').forEach(valueButton => {
          const selected = valueButton === button;
          valueButton.setAttribute('aria-pressed', String(selected));
        });
        document.getElementById('aboutValueStatus').textContent =
          `Selected objective: ${button.querySelector('strong').textContent}`;
      }));
      const aboutPhotoCarousel = document.getElementById('aboutPhotoCarousel');
      if (aboutPhotoCarousel) {
        const aboutPhotoSlides = Array.from(aboutPhotoCarousel.querySelectorAll('.about-photo-slide'));
        const aboutPhotoDots = Array.from(aboutPhotoCarousel.querySelectorAll('.about-photo-dot'));
        const aboutPhotoStatus = document.getElementById('aboutPhotoStatus');
        let currentAboutPhoto = 0;
        let aboutPhotoTimer = null;
        const showAboutPhoto = index => {
          currentAboutPhoto = (index + aboutPhotoSlides.length) % aboutPhotoSlides.length;
          aboutPhotoSlides.forEach((slide, slideIndex) => {
            const active = slideIndex === currentAboutPhoto;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
          });
          aboutPhotoDots.forEach((dot, dotIndex) => {
            dot.setAttribute('aria-current', String(dotIndex === currentAboutPhoto));
          });
          if (aboutPhotoStatus) {
            aboutPhotoStatus.textContent = `Photo ${currentAboutPhoto + 1} of ${aboutPhotoSlides.length}`;
          }
        };
        const stopAboutPhotoTimer = () => {
          window.clearInterval(aboutPhotoTimer);
          aboutPhotoTimer = null;
        };
        const startAboutPhotoTimer = () => {
          stopAboutPhotoTimer();
          if (reducedMotion || aboutPhotoSlides.length < 2 || document.hidden ||
              aboutPhotoCarousel.matches(':hover') || aboutPhotoCarousel.contains(document.activeElement)) return;
          aboutPhotoTimer = window.setInterval(() => showAboutPhoto(currentAboutPhoto + 1), 5000);
        };
        aboutPhotoCarousel.querySelectorAll('[data-about-photo-step]').forEach(button => {
          button.addEventListener('click', () => {
            showAboutPhoto(currentAboutPhoto + Number(button.dataset.aboutPhotoStep));
            startAboutPhotoTimer();
          });
        });
        aboutPhotoDots.forEach(dot => dot.addEventListener('click', () => {
          showAboutPhoto(Number(dot.dataset.aboutPhotoIndex));
          startAboutPhotoTimer();
        }));
        aboutPhotoCarousel.addEventListener('keydown', event => {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
          event.preventDefault();
          showAboutPhoto(currentAboutPhoto + (event.key === 'ArrowRight' ? 1 : -1));
          startAboutPhotoTimer();
        });
        aboutPhotoCarousel.addEventListener('mouseenter', stopAboutPhotoTimer);
        aboutPhotoCarousel.addEventListener('mouseleave', startAboutPhotoTimer);
        aboutPhotoCarousel.addEventListener('focusin', stopAboutPhotoTimer);
        aboutPhotoCarousel.addEventListener('focusout', event => {
          if (!aboutPhotoCarousel.contains(event.relatedTarget)) startAboutPhotoTimer();
        });
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) stopAboutPhotoTimer();
          else startAboutPhotoTimer();
        });
        startAboutPhotoTimer();
      }
      const galleryFull = document.getElementById('galleryFull');
      const galleryFullMedia = galleryFull.querySelector('.gallery-full-media');
      const galleryFullDots = document.getElementById('galleryFullDots');
      const galleryFullStatus = document.getElementById('galleryFullStatus');
      const galleryGrid = document.getElementById('galleryGrid');
      let activeGalleryTrigger = null;
      let currentGalleryItem = null;
      let currentGalleryImage = 0;
      let galleryFullTimer = null;
      const stopGalleryFullTimer = () => {
        window.clearInterval(galleryFullTimer);
        galleryFullTimer = null;
      };
      const showGalleryFullImage = index => {
        if (!currentGalleryItem) return;
        const images = Array.from(galleryFullMedia.querySelectorAll('.gallery-slide'));
        if (!images.length) return;
        currentGalleryImage = (index + images.length) % images.length;
        images.forEach((image, imageIndex) => {
          const active = imageIndex === currentGalleryImage;
          image.classList.toggle('is-active', active);
          image.setAttribute('aria-hidden', String(!active));
        });
        galleryFullDots.querySelectorAll('.gallery-dot').forEach((dot, dotIndex) => {
          dot.setAttribute('aria-current', String(dotIndex === currentGalleryImage));
        });
        galleryFullStatus.textContent = `Image ${currentGalleryImage + 1} of ${images.length}`;
      };
      const startGalleryFullTimer = () => {
        stopGalleryFullTimer();
        if (reducedMotion || !currentGalleryItem || currentGalleryItem.images.length < 2 ||
            document.hidden || galleryFullMedia.matches(':hover') || galleryFullMedia.contains(document.activeElement)) return;
        galleryFullTimer = window.setInterval(() => showGalleryFullImage(currentGalleryImage + 1), 5000);
      };
      galleryFullMedia.querySelectorAll('[data-gallery-full-step]').forEach(button => {
        button.addEventListener('click', () => {
          showGalleryFullImage(currentGalleryImage + Number(button.dataset.galleryFullStep));
          startGalleryFullTimer();
        });
      });
      galleryFullMedia.addEventListener('keydown', event => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        showGalleryFullImage(currentGalleryImage + (event.key === 'ArrowRight' ? 1 : -1));
        startGalleryFullTimer();
      });
      galleryFullMedia.addEventListener('mouseenter', stopGalleryFullTimer);
      galleryFullMedia.addEventListener('mouseleave', startGalleryFullTimer);
      galleryFullMedia.addEventListener('focusin', stopGalleryFullTimer);
      galleryFullMedia.addEventListener('focusout', event => {
        if (!galleryFullMedia.contains(event.relatedTarget)) startGalleryFullTimer();
      });
      const fullGalleryControls = galleryFullMedia.querySelector('.gallery-slideshow-controls');
      const galleryCardCarousels = [];
      const galleryCardCarouselByElement = new Map();
      const galleryCardObserver = 'IntersectionObserver' in window && !reducedMotion
        ? new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-entering');
            galleryCardObserver.unobserve(entry.target);
          }), {threshold:.12})
        : null;
      document.querySelectorAll('.gallery-category-filter').forEach((button, index) => {
        button.style.setProperty('--gallery-delay', `${index * 70}ms`);
      });
      const galleryCarouselObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver(entries => entries.forEach(entry => {
            const carousel = galleryCardCarouselByElement.get(entry.target);
            if (!carousel) return;
            if (entry.isIntersecting && !document.hidden) carousel.start();
            else carousel.stop();
          }), {threshold:.15})
        : null;
      document.querySelectorAll('[data-gallery-carousel]').forEach(carousel => {
        const card = carousel.closest('.gallery-card');
        if (card && galleryCardObserver) {
          card.style.setProperty('--gallery-delay', `${galleryCardCarousels.length * 100}ms`);
          galleryCardObserver.observe(card);
        }
        const slides = Array.from(carousel.querySelectorAll('.gallery-slide'));
        const dots = Array.from(carousel.querySelectorAll('.gallery-dot'));
        const caption = carousel.querySelector('[data-gallery-caption]');
        let current = 0;
        let timer = null;
        const showImage = index => {
          current = (index + slides.length) % slides.length;
          slides.forEach((slide, slideIndex) => {
            const active = slideIndex === current;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
          });
          dots.forEach((dot, dotIndex) => {
            dot.setAttribute('aria-current', String(dotIndex === current));
          });
          if (caption) caption.textContent = slides[current]?.alt || '';
        };
        const stop = () => {
          window.clearInterval(timer);
          timer = null;
        };
        const start = () => {
          stop();
          if (!isGalleryPage || reducedMotion || slides.length < 2 || document.hidden ||
              carousel.matches(':hover') || carousel.contains(document.activeElement)) return;
          timer = window.setInterval(() => showImage(current + 1), 4500);
        };
        carousel.querySelectorAll('[data-gallery-step]').forEach(button => {
          button.addEventListener('click', () => {
            showImage(current + Number(button.dataset.galleryStep));
            start();
          });
        });
        dots.forEach(dot => dot.addEventListener('click', () => {
          showImage(Number(dot.dataset.galleryIndex));
          start();
        }));
        carousel.addEventListener('keydown', event => {
          if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
          event.preventDefault();
          showImage(current + (event.key === 'ArrowRight' ? 1 : -1));
          start();
        });
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', event => {
          if (!carousel.contains(event.relatedTarget)) start();
        });
        const carouselControls = {start, stop};
        galleryCardCarousels.push(carouselControls);
        galleryCardCarouselByElement.set(carousel, carouselControls);
        galleryCarouselObserver?.observe(carousel);
        start();
      });
      document.querySelectorAll('.gallery-category-filter').forEach(button => button.addEventListener('click', () => {
        const category = button.dataset.galleryFilter;
        document.querySelectorAll('.gallery-category-filter').forEach(filterButton => {
          filterButton.setAttribute('aria-pressed', String(filterButton === button));
        });
        if (!galleryFull.hidden) {
          galleryFull.hidden = true;
          galleryGrid.hidden = false;
          stopGalleryFullTimer();
          currentGalleryItem = null;
        }
        document.querySelectorAll('.gallery-card').forEach((card, index) => {
          const visible = category === 'all' || card.dataset.galleryCategory === category;
          card.hidden = !visible;
          if (visible && !reducedMotion) {
            card.style.setProperty('--gallery-delay', `${index * 80}ms`);
            card.classList.remove('is-entering');
            requestAnimationFrame(() => card.classList.add('is-entering'));
          }
        });
      }));
      document.addEventListener('visibilitychange', () => {
        galleryCardCarousels.forEach(carousel => {
          if (document.hidden) carousel.stop();
          else carousel.start();
        });
        if (document.hidden) stopGalleryFullTimer();
        else startGalleryFullTimer();
      });
      document.querySelectorAll('.gallery-open').forEach(button => button.addEventListener('click', () => {
        const item = galleryItems[Number(button.dataset.galleryItem)];
        if (!item) return;
        activeGalleryTrigger = button;
        currentGalleryItem = item;
        currentGalleryImage = 0;
        document.getElementById('galleryFullType').textContent = item.type;
        document.getElementById('galleryFullTitle').textContent = item.title;
        document.getElementById('galleryFullSummary').textContent = item.summary;
        document.getElementById('galleryFullDetail').textContent = item.detail;
        document.getElementById('galleryFullOutcome').textContent = item.outcome;
        galleryFullMedia.querySelectorAll('.gallery-slide').forEach(image => image.remove());

        galleryFullDots.replaceChildren(...item.images.map((image, imageIndex) => {
          const slide = document.createElement('img');
          slide.className = `gallery-slide${imageIndex === 0 ? ' is-active' : ''}`;
          slide.src = image.src;
          slide.alt = image.alt;
          slide.loading = 'eager';
          slide.decoding = 'async';
          slide.setAttribute('aria-hidden', String(imageIndex !== 0));
          galleryFullMedia.insertBefore(slide, fullGalleryControls);
          const dot = document.createElement('button');
          dot.className = 'gallery-dot';
          dot.type = 'button';
          dot.setAttribute('aria-label', `Show image ${imageIndex + 1}`);
          dot.setAttribute('aria-current', String(imageIndex === 0));
          dot.addEventListener('click', () => {
            showGalleryFullImage(imageIndex);
            startGalleryFullTimer();
          });
          return dot;
        }));
        showGalleryFullImage(0);
        galleryGrid.hidden = true;
        galleryFull.hidden = false;
        galleryFull.focus({preventScroll:true});
        galleryFull.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'center'});
        startGalleryFullTimer();
      }));
      document.getElementById('galleryBack').addEventListener('click', () => {
        galleryFull.hidden = true;
        galleryGrid.hidden = false;
        stopGalleryFullTimer();
        currentGalleryItem = null;
        galleryFullMedia.querySelectorAll('.gallery-slide').forEach(image => image.remove());
        galleryFullDots.replaceChildren();
        activeGalleryTrigger?.focus();
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !galleryFull.hidden) {
          galleryFull.hidden = true;
          galleryGrid.hidden = false;
          stopGalleryFullTimer();
          currentGalleryItem = null;
          galleryFullMedia.querySelectorAll('.gallery-slide').forEach(image => image.remove());
          galleryFullDots.replaceChildren();
          activeGalleryTrigger?.focus();
        }
      });
      document.getElementById('dialogClose').addEventListener('click', () => dialog.close());
      dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
      });
      dialog.addEventListener('close', () => activeTrigger?.focus());

      const showToast = message => {
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove('visible'), 2600);
      };
      const socialFeedError = document.querySelector('[data-social-feed-error]');
      const reportSocialFeedError = message => {
        socialFeedError.textContent = message;
        socialFeedError.hidden = false;
      };
      const requestSocialFeed = async (method, data = null) => {
        const options = {method, credentials:'same-origin', headers:{Accept:'application/json'}};
        if (data) {
          options.headers['Content-Type'] = 'application/json';
          options.headers['X-CSRF-Token'] = socialFeedCsrfToken;
          options.body = JSON.stringify(data);
        }
        const response = await fetch(data ? socialFeedApiUrl : `${socialFeedApiUrl}?action=load`, options);
        let result;
        try {
          result = await response.json();
        } catch {
          throw new Error('The social feed returned an unreadable response.');
        }
        if (!response.ok) throw new Error(result.error || 'The social feed request failed.');
        return result;
      };
      const updateSocialCommentCount = (post, count) => {
        const countLabel = post.querySelector('[data-social-comment-count]');
        post.dataset.commentCount = String(count);
        countLabel.textContent = `${count} ${count === 1 ? 'comment' : 'comments'}`;
        const moreButton = post.querySelector('[data-social-see-more="comments"]');
        if (moreButton && post.dataset.commentsExpanded !== 'true') {
          moreButton.textContent = `See more comments (${count})`;
        }
      };
      const createSocialComment = comment => {
        const item = document.createElement('article');
        item.className = `social-comment${comment.is_hidden ? ' is-hidden' : ''}`;
        item.dataset.commentId = comment.id || '';
        item.dataset.commentHidden = String(Boolean(comment.is_hidden));
        if (comment.id) item.id = `social-comment-${comment.id}`;
        const author = document.createElement('strong');
        author.textContent = comment.name;
        const text = document.createElement('p');
        text.textContent = comment.text;
        text.dataset.commentText = '';
        const date = document.createElement('time');
        date.textContent = comment.created_at;
        const actions = document.createElement('div');
        actions.className = 'social-comment-actions';
        if (!comment.is_hidden) {
          const like = document.createElement('button');
          like.type = 'button';
          like.dataset.commentAction = 'like';
          like.setAttribute('aria-pressed', String(Boolean(comment.liked)));
          like.textContent = `${comment.liked ? 'Unlike' : 'Like'} · ${Array.isArray(comment.likes) ? comment.likes.length : Number(comment.like_count || 0)}`;
          const reply = document.createElement('button');
          reply.type = 'button';
          reply.dataset.commentAction = 'reply';
          reply.textContent = 'Reply';
          const share = document.createElement('button');
          share.type = 'button';
          share.dataset.commentAction = 'share';
          share.textContent = `Share · ${Array.isArray(comment.shares) ? comment.shares.length : Number(comment.share_count || 0)}`;
          const reactionTray = document.createElement('div');
          reactionTray.className = 'social-comment-reactions';
          reactionTray.setAttribute('aria-label', 'React to comment');
          ['❤️', '👍', '😂', '😮', '😢', '🎉'].forEach(emoji => {
            const reaction = document.createElement('button');
            reaction.type = 'button';
            reaction.dataset.commentAction = 'react';
            reaction.dataset.reaction = emoji;
            reaction.setAttribute('aria-pressed', String(comment.my_reaction === emoji));
            reaction.setAttribute('aria-label', `React ${emoji}`);
            reaction.textContent = emoji;
            reactionTray.append(reaction);
          });
          actions.append(like, reply, share, reactionTray);
        } else {
          const hiddenNotice = document.createElement('p');
          hiddenNotice.className = 'social-comment-hidden-notice';
          hiddenNotice.textContent = 'This comment is hidden from other viewers.';
          item.append(hiddenNotice);
        }
        if (comment.is_owner) {
          const edit = document.createElement('button');
          edit.type = 'button';
          edit.dataset.commentAction = 'edit';
          edit.textContent = 'Edit';
          const remove = document.createElement('button');
          remove.type = 'button';
          remove.dataset.commentAction = 'delete';
          remove.textContent = 'Delete';
          const visibility = document.createElement('button');
          visibility.type = 'button';
          visibility.dataset.commentAction = 'visibility';
          visibility.textContent = comment.is_hidden ? 'Unhide' : 'Hide';
          actions.append(edit, visibility, remove);
        }
        const reactionSummary = document.createElement('p');
        reactionSummary.className = 'social-comment-reaction-summary';
        const reactionValues = Array.isArray(comment.reactions) ? comment.reactions : [];
        reactionSummary.textContent = [...new Set(reactionValues.map(value => value.emoji).filter(Boolean))]
          .map(emoji => `${emoji} ${reactionValues.filter(value => value.emoji === emoji).length}`).join(' · ');
        const replies = document.createElement('div');
        replies.className = 'social-comment-replies';
        (Array.isArray(comment.replies) ? comment.replies : []).forEach(replyData => {
          const replyItem = document.createElement('div');
          replyItem.className = 'social-comment-reply';
          replyItem.dataset.replyId = replyData.id || '';
          const replyAuthor = document.createElement('strong');
          replyAuthor.textContent = replyData.name;
          const replyText = document.createElement('span');
          replyText.dataset.replyText = '';
          replyText.textContent = ` ${replyData.text}`;
          replyItem.append(replyAuthor, replyText);
          if (replyData.is_owner) {
            const replyActions = document.createElement('div');
            replyActions.className = 'social-comment-actions';
            ['edit', 'delete'].forEach(actionName => {
              const actionButton = document.createElement('button');
              actionButton.type = 'button';
              actionButton.dataset.replyAction = actionName;
              actionButton.textContent = actionName === 'edit' ? 'Edit' : 'Delete';
              replyActions.append(actionButton);
            });
            replyItem.append(replyActions);
          }
          const editForm = document.createElement('form');
          editForm.className = 'social-inline-edit-form';
          editForm.dataset.socialEditForm = 'reply';
          editForm.hidden = true;
          const editInput = document.createElement('textarea');
          editInput.name = 'text';
          editInput.maxLength = 1000;
          editInput.required = true;
          editInput.value = replyData.text;
          const saveEdit = document.createElement('button');
          saveEdit.type = 'submit';
          saveEdit.textContent = 'Save';
          const cancelEdit = document.createElement('button');
          cancelEdit.type = 'button';
          cancelEdit.dataset.cancelEdit = '';
          cancelEdit.textContent = 'Cancel';
          editForm.append(editInput, saveEdit, cancelEdit);
          replyItem.append(editForm);
          replies.append(replyItem);
        });
        const replyForm = document.createElement('form');
        replyForm.className = 'social-comment-reply-form';
        replyForm.hidden = true;
        const replyInput = document.createElement('input');
        replyInput.name = 'text';
        replyInput.type = 'text';
        replyInput.maxLength = 1000;
        replyInput.placeholder = 'Write a reply…';
        replyInput.required = true;
        const replySubmit = document.createElement('button');
        replySubmit.type = 'submit';
        replySubmit.textContent = 'Send reply';
        replyForm.append(replyInput, replySubmit);
        const editForm = document.createElement('form');
        editForm.className = 'social-inline-edit-form';
        editForm.dataset.socialEditForm = 'comment';
        editForm.hidden = true;
        const editInput = document.createElement('textarea');
        editInput.name = 'text';
        editInput.maxLength = 1000;
        editInput.required = true;
        editInput.value = comment.text;
        const saveEdit = document.createElement('button');
        saveEdit.type = 'submit';
        saveEdit.textContent = 'Save';
        const cancelEdit = document.createElement('button');
        cancelEdit.type = 'button';
        cancelEdit.dataset.cancelEdit = '';
        cancelEdit.textContent = 'Cancel';
        editForm.append(editInput, saveEdit, cancelEdit);
        item.prepend(author, text, date);
        item.append(actions, editForm, reactionSummary, replies);
        if (!comment.is_hidden) item.append(replyForm);
        return item;
      };
      const renderSocialComments = (post, comments, count = comments.length) => {
        const list = post.querySelector('[data-social-comment-list]');
        list.replaceChildren(...comments.map((comment, index) => {
          const item = createSocialComment(comment);
          item.dataset.commentIndex = String(index);
          if (index >= 3 && !post.dataset.commentsExpanded) item.hidden = true;
          return item;
        }));
        updateSocialCommentCount(post, count);
        post.querySelector('[data-social-comment-status]').textContent =
          comments.length ? '' : 'Be the first to comment.';
        const moreButton = post.querySelector('[data-social-see-more="comments"]');
        moreButton.hidden = false;
        moreButton.textContent = post.dataset.commentsExpanded ? 'Show fewer comments' : `See more comments (${count})`;
      };
      const renderNames = (container, names) => {
        container.replaceChildren(...names.map(name => {
          const item = document.createElement('li');
          item.textContent = name;
          return item;
        }));
        if (!names.length) {
          const empty = document.createElement('li');
          empty.textContent = 'No activity yet.';
          container.append(empty);
        }
      };
      const recordSocialPostShare = async post => {
        const result = await requestSocialFeed('POST', {action:'share', post_id:post.dataset.socialPost});
        post.querySelector('[data-social-share-count]').textContent = String(result.shares);
        renderNames(post.querySelector('[data-social-sharers]'), result.sharers || []);
      };
      const loadSocialFeed = async () => {
        try {
          const result = await requestSocialFeed('GET');
          document.querySelectorAll('[data-social-post]').forEach(post => {
            const state = result.posts[post.dataset.socialPost];
            if (!state) return;
            const likeButton = post.querySelector('[data-social-like]');
            likeButton.setAttribute('aria-pressed', String(state.liked));
            likeButton.querySelector('span').textContent = state.liked ? 'Liked' : 'Like';
            likeButton.querySelector('.fa-heart').classList.toggle('fa-solid', state.liked);
            likeButton.querySelector('.fa-heart').classList.toggle('fa-regular', !state.liked);
            post.querySelector('[data-social-like-count]').textContent = String(state.likes);
            post.querySelector('[data-social-share-count]').textContent = String(state.shares);
            renderNames(post.querySelector('[data-social-likers]'), state.likers || []);
            renderNames(post.querySelector('[data-social-sharers]'), state.sharers || []);
            renderSocialComments(post, state.comments, state.comment_count);
          });
        } catch (error) {
          reportSocialFeedError(error.message);
          document.querySelectorAll('[data-social-comment-status]').forEach(status => {
            status.textContent = 'Comments could not be loaded.';
          });
        }
      };
      document.querySelectorAll('[data-social-like]').forEach(button => button.addEventListener('click', async () => {
        const post = button.closest('[data-social-post]');
        button.disabled = true;
        try {
          const result = await requestSocialFeed('POST', {action:'like', post_id:post.dataset.socialPost});
          button.setAttribute('aria-pressed', String(result.liked));
          button.querySelector('span').textContent = result.liked ? 'Liked' : 'Like';
          button.querySelector('.fa-heart').classList.toggle('fa-solid', result.liked);
          button.querySelector('.fa-heart').classList.toggle('fa-regular', !result.liked);
          post.querySelector('[data-social-like-count]').textContent = String(result.likes);
          renderNames(post.querySelector('[data-social-likers]'), result.likers || []);
          if (result.liked) {
            button.classList.remove('is-liking');
            const burst = document.createElement('span');
            burst.className = 'social-like-burst';
            burst.setAttribute('aria-hidden', 'true');
            burst.textContent = '♥';
            button.append(burst);
            button.classList.add('is-liking');
            burst.addEventListener('animationend', () => burst.remove(), {once:true});
            window.setTimeout(() => button.classList.remove('is-liking'), 500);
          }
        } catch (error) {
          reportSocialFeedError(error.message);
        } finally {
          button.disabled = false;
        }
      }));
      document.querySelectorAll('[data-social-comment-focus]').forEach(button => button.addEventListener('click', () => {
        const post = button.closest('[data-social-post]');
        const isExpanded = button.getAttribute('aria-expanded') === 'true';
        const comments = post.querySelector('.social-comments');
        const form = post.querySelector('[data-social-comment-form]');
        button.setAttribute('aria-expanded', String(!isExpanded));
        button.setAttribute('aria-label', isExpanded ? 'Show comments' : 'Hide comments');
        comments.hidden = isExpanded;
        form.hidden = isExpanded;
        if (!isExpanded) form.querySelector('[name="text"]').focus();
      }));
      document.querySelectorAll('[data-social-see-more]').forEach(button => button.addEventListener('click', () => {
        const post = button.closest('[data-social-post]');
        const type = button.dataset.socialSeeMore;
        if (type === 'comments') {
          post.dataset.commentsExpanded = post.dataset.commentsExpanded === 'true' ? '' : 'true';
          const expanded = post.dataset.commentsExpanded === 'true';
          post.querySelector('.social-comments').hidden = !expanded;
          button.setAttribute('aria-expanded', String(expanded));
          button.textContent = expanded ? 'Show fewer comments' : `See more comments (${post.dataset.commentCount || 0})`;
          post.querySelectorAll('[data-social-comment-list]>.social-comment').forEach(comment => {
            comment.hidden = !expanded && Number(comment.dataset.commentIndex || 0) >= 3;
          });
          return;
        }
        const details = post.querySelector(`[data-social-details="${type}"]`);
        const expanded = details.hidden;
        details.hidden = !expanded;
        button.setAttribute('aria-expanded', String(expanded));
        button.textContent = expanded
          ? (type === 'likes' ? 'Show fewer likes' : 'Show fewer shares')
          : (type === 'likes' ? 'See more likes' : 'See more shares');
        if (expanded) details.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'nearest'});
      }));
      document.querySelector('#socialFeed').addEventListener('click', async event => {
        const cancel = event.target.closest('[data-cancel-edit]');
        if (cancel) {
          cancel.closest('form').hidden = true;
          return;
        }
        const replyAction = event.target.closest('[data-reply-action]');
        if (replyAction) {
          const reply = replyAction.closest('[data-reply-id]');
          if (replyAction.dataset.replyAction === 'edit') {
            reply.querySelector('[data-social-edit-form="reply"]').hidden = false;
            reply.querySelector('[data-social-edit-form="reply"] textarea').focus();
          } else if (window.confirm('Delete your reply?')) {
            const post = reply.closest('[data-social-post]');
            const comment = reply.closest('[data-comment-id]');
            replyAction.disabled = true;
            try {
              const result = await requestSocialFeed('POST', {
                action:'reply_delete',
                post_id:post.dataset.socialPost,
                comment_id:comment.dataset.commentId,
                reply_id:reply.dataset.replyId,
              });
              reply.remove();
              updateSocialCommentCount(post, result.comment_count);
            } catch (error) {
              reportSocialFeedError(error.message);
              replyAction.disabled = false;
            }
          }
          return;
        }
        const button = event.target.closest('[data-comment-action]');
        if (!button) return;
        const comment = button.closest('[data-comment-id]');
        const post = button.closest('[data-social-post]');
        if (!comment || !post) return;
        const action = button.dataset.commentAction;
        if (action === 'reply') {
          const replyForm = comment.querySelector('.social-comment-reply-form');
          replyForm.hidden = !replyForm.hidden;
          if (!replyForm.hidden) replyForm.elements.text.focus();
          return;
        }
        if (action === 'edit') {
          const editForm = comment.querySelector('[data-social-edit-form="comment"]');
          editForm.hidden = false;
          editForm.querySelector('textarea').focus();
          return;
        }
        if (action === 'visibility') {
          button.disabled = true;
          try {
            await requestSocialFeed('POST', {
              action:'comment_visibility',
              post_id:post.dataset.socialPost,
              comment_id:comment.dataset.commentId,
              hidden:comment.dataset.commentHidden !== 'true',
            });
            await loadSocialFeed();
            showToast(comment.dataset.commentHidden === 'true'
              ? 'Comment is visible to viewers again.'
              : 'Comment hidden from viewers.');
          } catch (error) {
            reportSocialFeedError(error.message);
            button.disabled = false;
          }
          return;
        }
        if (action === 'delete') {
          if (!window.confirm('Delete your comment and its replies?')) return;
          button.disabled = true;
          try {
            const result = await requestSocialFeed('POST', {
              action:'comment_delete',
              post_id:post.dataset.socialPost,
              comment_id:comment.dataset.commentId,
            });
            comment.remove();
            updateSocialCommentCount(post, result.comment_count);
            post.querySelector('[data-social-comment-status]').textContent =
              post.querySelectorAll('[data-social-comment-list]>.social-comment').length ? '' : 'Be the first to comment.';
          } catch (error) {
            reportSocialFeedError(error.message);
            button.disabled = false;
          }
          return;
        }
        button.disabled = true;
        try {
          if (action === 'share') {
            const url = new URL(`#${comment.id}`, window.location.href).href;
            if (navigator.share) {
              await navigator.share({title:'Portfolio comment', text:comment.querySelector('p').textContent, url});
            } else if (navigator.clipboard) {
              await navigator.clipboard.writeText(url);
              showToast('Comment link copied.');
            } else {
              if (window.prompt('Copy this comment link to share:', url) === null) return;
            }
            const result = await requestSocialFeed('POST', {
              action:'comment_share', post_id:post.dataset.socialPost, comment_id:comment.dataset.commentId
            });
            button.textContent = `Share · ${result.shares}`;
            return;
          }
          const payload = {action:action === 'like' ? 'comment_like' : 'comment_react', post_id:post.dataset.socialPost, comment_id:comment.dataset.commentId};
          if (action === 'react') payload.reaction = button.dataset.reaction;
          const result = await requestSocialFeed('POST', payload);
          if (action === 'like') {
            button.textContent = `${result.liked ? 'Unlike' : 'Like'} · ${result.likes}`;
            button.setAttribute('aria-pressed', String(result.liked));
          } else {
            comment.querySelectorAll('[data-comment-action="react"]').forEach(reactionButton => {
              reactionButton.setAttribute('aria-pressed', String(reactionButton.dataset.reaction === result.my_reaction));
            });
            const emojis = [...new Set(result.reactions.map(reaction => reaction.emoji))];
            comment.querySelector('.social-comment-reaction-summary').textContent = emojis
              .map(emoji => `${emoji} ${result.reactions.filter(reaction => reaction.emoji === emoji).length}`).join(' · ');
          }
        } catch (error) {
          if (error.name !== 'AbortError') reportSocialFeedError(error.message);
        } finally {
          button.disabled = false;
        }
      });
      document.querySelector('#socialFeed').addEventListener('submit', async event => {
        const editForm = event.target.closest('[data-social-edit-form]');
        if (editForm) {
          event.preventDefault();
          const post = editForm.closest('[data-social-post]');
          const comment = editForm.closest('[data-comment-id]');
          const reply = editForm.closest('[data-reply-id]');
          const isReply = editForm.dataset.socialEditForm === 'reply';
          const submit = editForm.querySelector('button[type="submit"]');
          const updatedText = editForm.elements.text.value.trim();
          if (!updatedText) return;
          submit.disabled = true;
          try {
            await requestSocialFeed('POST', {
              action:isReply ? 'reply_edit' : 'comment_edit',
              post_id:post.dataset.socialPost,
              comment_id:comment.dataset.commentId,
              ...(isReply ? {reply_id:reply.dataset.replyId} : {}),
              text:updatedText,
            });
            if (isReply) {
              reply.querySelector('[data-reply-text]').textContent = ` ${updatedText}`;
            } else {
              comment.querySelector('[data-comment-text]').textContent = updatedText;
            }
            editForm.hidden = true;
          } catch (error) {
            reportSocialFeedError(error.message);
          } finally {
            submit.disabled = false;
          }
          return;
        }
        const form = event.target.closest('.social-comment-reply-form');
        if (!form) return;
        event.preventDefault();
        const post = form.closest('[data-social-post]');
        const comment = form.closest('[data-comment-id]');
        const submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
          const result = await requestSocialFeed('POST', {
            action:'comment_reply',
            post_id:post.dataset.socialPost,
            comment_id:comment.dataset.commentId,
            text:form.elements.text.value.trim(),
          });
          updateSocialCommentCount(post, result.comment_count);
          form.reset();
          form.hidden = true;
          await loadSocialFeed();
        } catch (error) {
          reportSocialFeedError(error.message);
        } finally {
          submit.disabled = false;
        }
      });
      document.querySelectorAll('[data-social-comment-form]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        const post = form.closest('[data-social-post]');
        const submitButton = form.querySelector('button[type="submit"]');
        const status = post.querySelector('[data-social-comment-status]');
        const name = form.elements.name.value.trim();
        const text = form.elements.text.value.trim();
        if (!name || !text) {
          status.textContent = 'Enter your name and comment before posting.';
          return;
        }
        submitButton.disabled = true;
        status.textContent = 'Posting comment…';
        try {
          const result = await requestSocialFeed('POST', {
            action:'comment',
            post_id:post.dataset.socialPost,
            name,
            text,
          });
          const list = post.querySelector('[data-social-comment-list]');
          const newComment = createSocialComment(result.comment);
          list.prepend(newComment);
          list.querySelectorAll('.social-comment').forEach((comment, index) => {
            comment.dataset.commentIndex = String(index);
            comment.hidden = index >= 3;
          });
          post.dataset.commentsExpanded = 'false';
          post.querySelector('.social-comments').hidden = false;
          updateSocialCommentCount(post, result.comment_count);
          status.textContent = 'Comment posted.';
          form.elements.text.value = '';
          form.hidden = true;
          const commentToggle = post.querySelector('[data-social-comment-focus]');
          commentToggle.setAttribute('aria-expanded', 'true');
          commentToggle.setAttribute('aria-label', 'Hide comments');
          commentToggle.focus();
        } catch (error) {
          status.textContent = error.message;
        } finally {
          submitButton.disabled = false;
        }
      }));
      document.querySelectorAll('[data-social-share]').forEach(button => button.addEventListener('click', async () => {
        const post = button.closest('[data-social-post]');
        const url = button.dataset.socialShareUrl;
        const title = post.querySelector('h3').textContent;
        const description = post.querySelector('.social-post-copy>p').textContent;
        const shareData = {title, text:description, url};
        try {
          if (navigator.share) {
            await navigator.share(shareData);
          } else if (navigator.clipboard) {
            await navigator.clipboard.writeText(url);
            showToast('Post link copied.');
          } else {
            if (window.prompt('Copy this post link to share:', url) === null) return;
            showToast('Post link ready to share.');
          }
          await recordSocialPostShare(post);
        } catch (error) {
          if (error.name !== 'AbortError') reportSocialFeedError(error.message);
        }
      }));
      document.querySelectorAll('[data-social-copy-link]').forEach(button => button.addEventListener('click', async () => {
        const post = button.closest('[data-social-post]');
        const url = button.dataset.socialCopyLink;
        try {
          if (navigator.clipboard) {
            await navigator.clipboard.writeText(url);
          } else if (window.prompt('Copy this post link to share:', url) === null) {
            return;
          }
          showToast('Post link copied.');
          await recordSocialPostShare(post);
        } catch (error) {
          reportSocialFeedError(error.message);
        }
      }));
      document.querySelectorAll('[data-social-platform-share]').forEach(link => link.addEventListener('click', () => {
        const post = link.closest('[data-social-post]');
        recordSocialPostShare(post).catch(error => reportSocialFeedError(error.message));
      }));
      document.querySelectorAll('[data-social-copy-platform-share]').forEach(link => link.addEventListener('click', async () => {
        const post = link.closest('[data-social-post]');
        const {shareTitle, shareDescription, shareImage, shareUrl} = link.dataset;
        if (!shareTitle || !shareDescription || !shareImage || !shareUrl) return;
        const shareText = `${shareTitle}\n\n${shareDescription}\n\nImage: ${shareImage}\nPost: ${shareUrl}`;
        try {
          if (navigator.clipboard) {
            await navigator.clipboard.writeText(shareText);
          } else {
            window.prompt('Copy the post title, description, image, and link before sharing:', shareText);
          }
          showToast('Post content and image link copied. Paste them into your social post.');
          await recordSocialPostShare(post);
        } catch (error) {
          reportSocialFeedError(`Could not copy the post link: ${error.message}`);
        }
      }));
      document.addEventListener('click', event => {
        document.querySelectorAll('.social-share-menu[open]').forEach(menu => {
          if (!menu.contains(event.target) || event.target.closest('.social-share-dropdown')) {
            menu.open = false;
          }
        });
      });
      document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const menu = document.querySelector('.social-share-menu[open]');
        if (!menu) return;
        menu.open = false;
        menu.querySelector('summary').focus();
      });
      document.querySelectorAll('.social-read-full,.social-post-image[data-social-full-post]').forEach(control => control.addEventListener('click', () => {
        const post = control.closest('[data-social-post]');
        const media = post.querySelector('[data-social-media]');
        const fullMedia = media.cloneNode(true);
        fullMedia.removeAttribute('data-social-media');
        fullMedia.removeAttribute('data-social-full-post');
        document.getElementById('socialFullPostTitle').textContent = post.querySelector('h3').textContent;
        document.getElementById('socialFullPostMedia').replaceChildren(fullMedia);
        document.getElementById('socialFullPostCopy').textContent = post.querySelector('.social-post-copy>p').textContent;
        activeTrigger = control;
        socialFullPost.hidden = false;
        socialFullPost.focus({preventScroll:true});
        socialFullPost.scrollIntoView({behavior:reducedMotion ? 'auto' : 'smooth', block:'center'});
      }));
      document.querySelectorAll('.social-post-image[data-social-full-post]').forEach(image => image.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          image.click();
        }
      }));
      document.getElementById('socialFullPostClose').addEventListener('click', () => {
        socialFullPost.hidden = true;
        document.getElementById('socialFullPostMedia').replaceChildren();
        activeTrigger?.focus();
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !socialFullPost.hidden) {
          socialFullPost.hidden = true;
          document.getElementById('socialFullPostMedia').replaceChildren();
          activeTrigger?.focus();
        }
      });
      if ('IntersectionObserver' in window && !reducedMotion) {
        const postObserver = new IntersectionObserver(entries => entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('social-entering');
          postObserver.unobserve(entry.target);
        }), {threshold:.12});
        document.querySelectorAll('[data-social-post]').forEach((post, index) => {
          post.style.setProperty('--social-delay', `${Math.min(index, 4) * 90}ms`);
          postObserver.observe(post);
        });
      }
      loadSocialFeed();
      const copyEmailButton = document.getElementById('copyEmail');
      if (copyEmailButton) {
        copyEmailButton.addEventListener('click', async event => {
          const address = event.currentTarget.dataset.email;
          try {
            await navigator.clipboard.writeText(address);
            showToast('Email address copied to clipboard.');
          } catch {
            showToast(`Copy is unavailable here. Email: ${address}`);
          }
        });
      }
      const sectionLinks = Array.from(nav.querySelectorAll('a[href^="#"]'));
      if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver(entries => entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          sectionLinks.forEach(link => {
            if (link.hash === `#${entry.target.id}`) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
          });
        }), {rootMargin:'-30% 0px -60% 0px'});
        sectionLinks.forEach(link => {
          const target = document.querySelector(link.hash);
          if (target) sectionObserver.observe(target);
        });
      }

      if ('IntersectionObserver' in window && !reducedMotion) {
        document.body.classList.add('js-reveal');
        const revealObserver = new IntersectionObserver(entries => entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObserver.unobserve(entry.target);
          }
        }), {threshold:.12});
        document.querySelectorAll('.reveal').forEach(element => revealObserver.observe(element));

        const countObserver = new IntersectionObserver(entries => entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const node = entry.target;
          const target = Number(node.dataset.count);
          const start = performance.now();
          const animate = now => {
            const ratio = Math.min((now - start) / 950, 1);
            node.textContent = `${Math.round(target * (1 - Math.pow(1 - ratio, 3)))}${node.dataset.suffix}`;
            if (ratio < 1) requestAnimationFrame(animate);
          };
          requestAnimationFrame(animate);
          countObserver.unobserve(node);
        }), {threshold:.6});
        document.querySelectorAll('[data-count]').forEach(node => countObserver.observe(node));
      } else {
        document.querySelectorAll('[data-count]').forEach(node => {
          node.textContent = `${node.dataset.count}${node.dataset.suffix}`;
        });
      }

      const aboutIntro = document.querySelector('.about-intro-inner');
      if ('IntersectionObserver' in window && !reducedMotion) {
        const aboutIntroObserver = new IntersectionObserver(entries => {
          if (entries.some(entry => entry.isIntersecting)) {
            aboutIntro.classList.add('is-visible');
            aboutIntroObserver.disconnect();
          }
        }, {threshold:.35});
        aboutIntroObserver.observe(aboutIntro);
      } else {
        aboutIntro.classList.add('is-visible');
      }
    })();
  </script>
</body>
</html>