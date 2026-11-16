<?php
require_once 'config.php';
require_once __DIR__ . '/portfolio_subscription_mailer.php';
require_once __DIR__ . '/portfolio_contact_storage.php';
require_once __DIR__ . '/portfolio_advertising_storage.php';
require_once __DIR__ . '/portfolio_project_payment_storage.php';

if (!isLoggedIn()) {
    redirect('admin_login.php');
}
if (!hasRole('admin')) {
    redirect('admin_login.php');
}

$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$portfolioSubscriberCount = 0;
$portfolioSubscribers = [];
$portfolioSubscriberLoadError = false;
try {
    $portfolioSubscribers = portfolioLoadSubscribers($conn);
    foreach ($portfolioSubscribers as $subscriber) {
        if (!empty($subscriber['is_active'])) {
            $portfolioSubscriberCount++;
        }
    }
} catch (mysqli_sql_exception | RuntimeException $exception) {
    $portfolioSubscriberLoadError = true;
    error_log('Could not load portfolio subscriber count in admin dashboard: ' . $exception->getMessage());
}
$portfolioContactMessages = [];
$portfolioContactInboxError = false;
$portfolioAdvertisements = [];
$portfolioAdvertisingLoadError = false;
$advertisementForm = [
    'ad_id' => 0,
    'brand_name' => '',
    'product_name' => '',
    'description' => '',
    'brand_logo_url' => '',
    'product_image_url' => '',
    'poster_image_url' => '',
    'video_file_url' => '',
    'pdf_file_url' => '',
    'target_url' => '',
    'cta_label' => 'Learn more',
    'is_pinned' => false
];
try {
    portfolioEnsureAdvertisingTable($conn);
    $portfolioAdvertisements = portfolioLoadAdvertisements($conn, true);
} catch (Throwable $exception) {
    $portfolioAdvertisingLoadError = true;
    error_log('Could not load portfolio advertisements in admin dashboard: ' . $exception->getMessage());
}
$selectedContactMessageId = 0;
$unreadContactMessageCount = 0;
$validMailboxFolders = ['inbox', 'sent', 'drafts', 'archive', 'spam', 'trash', 'compose'];
$mailboxFolder = isset($_GET['contact_folder']) && is_string($_GET['contact_folder']) &&
    in_array($_GET['contact_folder'], $validMailboxFolders, true)
        ? $_GET['contact_folder']
        : 'inbox';
try {
    portfolioEnsureContactInboxTable($conn);
} catch (mysqli_sql_exception | RuntimeException $exception) {
    $portfolioContactInboxError = true;
    error_log('Could not initialize portfolio contact inbox in admin dashboard: ' . $exception->getMessage());
}

if (isset($_GET['mailbox_unread_count'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    if ($portfolioContactInboxError) {
        http_response_code(500);
        echo json_encode(['error' => 'The contact mailbox is unavailable.']);
        exit;
    }
    try {
        $unreadCountResult = $conn->query(
            "SELECT COUNT(*) AS unread_count FROM portfolio_contact_messages WHERE folder = 'inbox' AND is_read = 0"
        );
        if (!$unreadCountResult) {
            throw new RuntimeException('Could not retrieve unread portfolio contact message count: ' . $conn->error);
        }
        $unreadCountRow = $unreadCountResult->fetch_assoc();
        $unreadCountResult->free();
        echo json_encode(['unread' => (int) ($unreadCountRow['unread_count'] ?? 0)]);
    } catch (mysqli_sql_exception | RuntimeException $exception) {
        error_log('Could not refresh portfolio unread message count: ' . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'The unread message count could not be refreshed.']);
    }
    exit;
}

if (empty($_SESSION['blog_dashboard2_csrf'])) {
    $_SESSION['blog_dashboard2_csrf'] = bin2hex(random_bytes(32));
}
$portfolioProjectPaymentRows = [];
$portfolioProjectPaymentArchives = [];
$portfolioProjectPaymentCatalog = [];
$portfolioProjectPaymentLoadError = '';
$portfolioProjectPaymentFlash = $_SESSION['portfolio_project_payment_flash'] ?? null;
unset($_SESSION['portfolio_project_payment_flash']);
try {
    portfolioProjectPaymentEnsureTables($conn);
    $portfolioProjectPaymentCatalog = portfolioProjectPaymentCatalog($conn);
    $paymentRowsResult = $conn->query(
        'SELECT request_id, project_title, buyer_email, amount_birr, payment_method, payment_reference, status, submitted_at
         FROM portfolio_project_payment_requests ORDER BY submitted_at DESC, request_id DESC LIMIT 200'
    );
    if (!$paymentRowsResult) {
        throw new RuntimeException('Could not load project payment requests: ' . $conn->error);
    }
    while ($paymentRow = $paymentRowsResult->fetch_assoc()) {
        $portfolioProjectPaymentRows[] = $paymentRow;
    }
    $paymentRowsResult->free();
    $archivesResult = $conn->query('SELECT project_key, original_name FROM portfolio_project_archives');
    if (!$archivesResult) {
        throw new RuntimeException('Could not load project ZIP archives: ' . $conn->error);
    }
    while ($archiveRow = $archivesResult->fetch_assoc()) {
        $portfolioProjectPaymentArchives[$archiveRow['project_key']] = $archiveRow['original_name'];
    }
    $archivesResult->free();
} catch (Throwable $exception) {
    $portfolioProjectPaymentLoadError = 'Payment review is unavailable. Check the database and private ZIP storage configuration.';
    error_log('Could not load project payment review in admin dashboard: ' . $exception->getMessage());
}

function portfolioMailboxRelativeTime($value) {
    $timestamp = is_string($value) ? strtotime($value) : false;
    if ($timestamp === false) {
        return 'Unknown time';
    }
    $elapsed = max(0, time() - $timestamp);
    if ($elapsed < 60) {
        return 'Just now';
    }
    if ($elapsed < 3600) {
        $minutes = (int) floor($elapsed / 60);
        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }
    if ($elapsed < 86400) {
        $hours = (int) floor($elapsed / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($elapsed < 604800) {
        $days = (int) floor($elapsed / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
    if ($elapsed < 2592000) {
        $weeks = (int) floor($elapsed / 604800);
        return $weeks . ' week' . ($weeks === 1 ? '' : 's') . ' ago';
    }
    if ($elapsed < 31536000) {
        $months = (int) floor($elapsed / 2592000);
        return $months . ' month' . ($months === 1 ? '' : 's') . ' ago';
    }
    $years = (int) floor($elapsed / 31536000);
    return $years . ' year' . ($years === 1 ? '' : 's') . ' ago';
}

$default_settings = [
    'blog_banner_slides' => [
        ['title' => 'Global insurance, local care', 'description' => 'Protecting what matters most with tailored coverage.', 'alt' => 'Insurance team', 'image' => ''],
        ['title' => 'Business insurance solutions', 'description' => 'Comprehensive coverage for enterprises of all sizes.', 'alt' => 'Business insurance', 'image' => ''],
        ['title' => 'Fast claim support', 'description' => 'Quick and efficient claim processing when you need it most.', 'alt' => 'Claim support', 'image' => '']
    ],
    'blog_feature_update' => [
        'image' => '', 'alt' => 'Latest Insurance Updates', 'label' => 'FEATURED UPDATE',
        'title' => 'New Insurance Policy Updates',
        'description' => 'Stay informed with the latest changes in insurance policies and coverage options.',
        'link_url' => '#'
    ],
    'blog_insurance_news' => [
        ['label' => 'NEWS', 'title' => 'Insurance Market Trends', 'description' => 'Explore the latest trends shaping the insurance industry.', 'link_url' => '#'],
        ['label' => 'UPDATE', 'title' => 'New Claim Processing Guidelines', 'description' => 'Updated guidelines for efficient claim handling.', 'link_url' => '#'],
        ['label' => 'ANNOUNCEMENT', 'title' => 'Company Announcement', 'description' => 'The latest news from our organization.', 'link_url' => '#']
    ],
    'blog_core_pillars' => [
        ['title' => 'Vision', 'description' => 'To be a trusted insurance partner through innovation and empathy.'],
        ['title' => 'Mission', 'description' => 'Empower customers with seamless coverage and fair claims.'],
        ['title' => 'Values', 'description' => 'Integrity, customer focus, innovation, and community strength.'],
        ['title' => 'Goal 2030', 'description' => 'Build a resilient future for our customers and communities.']
    ],
    'blog_performance' => [
        'title' => 'Company Performance Highlights',
        'description' => 'Add the performance highlights shown near the bottom of the blog homepage.',
        'link_url' => '#', 'link_text' => 'Annual Report'
    ],
    'blog_product_catalog' => [],
    'blog_executive_management' => [
        'banner' => [
            'title' => 'Executive Management Members',
            'description' => 'Meet the leadership team guiding our organization.',
            'alt' => 'Executive management team', 'image' => ''
        ],
        'members' => [
            ['name' => 'Chief Executive Officer', 'description' => 'Leads corporate strategy, governance, and sustainable growth.', 'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Chief Operations Officer', 'description' => 'Oversees operations, customer service, and branch performance.', 'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Chief Finance Officer', 'description' => 'Directs financial planning, reporting, and investment discipline.', 'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Chief Underwriting Officer', 'description' => 'Guides underwriting standards, products, and portfolio risk.', 'image' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Claims Director', 'description' => 'Champions fair, transparent, and timely claim settlement.', 'image' => 'https://images.unsplash.com/photo-1556157382-97eda2d62296?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Marketing Director', 'description' => 'Builds customer relationships and communicates our products.', 'image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Human Capital Director', 'description' => 'Develops talent, leadership capability, and performance culture.', 'image' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=900&q=80'],
            ['name' => 'Digital Transformation Director', 'description' => 'Leads digital services, data modernization, and technology.', 'image' => 'https://images.unsplash.com/photo-1562788869-4ed32648eb72?auto=format&fit=crop&w=900&q=80']
        ]
    ],
    'portfolio_header' => [
        'name' => 'Aashish Jha',
        'brand_name' => 'Aashish Jha',
        'initials' => 'AJ',
        'role' => 'Web Developer',
        'location' => 'Kathmandu, Nepal',
        'email' => 'info@aashish-jha.com.np',
        'logo_image' => ''
    ],
    'portfolio_home' => [
        'headline' => "Hi, I'm Shumi.",
        'roles' => ['Web Developer', 'Student', 'Developer', 'Tech Explorer'],
        'intro' => 'I love to learn and build solutions that make a difference.',
        'portrait_url' => 'https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg',
        'background_url' => 'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg',
        'portrait_bio_name' => 'Shumi',
        'portrait_bio_kicker' => 'A little about me',
        'portrait_bio' => 'I love to learn and build solutions that make a difference.',
        'portrait_skills' => 'PHP · MySQL · JavaScript · HTML · CSS',
        'hire_label' => 'Hire Me',
        'cv_url' => 'cv.pdf',
        'cv_label' => 'Download CV',
        'hire_url' => 'mailto:info@aashish-jha.com.np?subject=Hiring%20inquiry',
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
                ['label' => 'All projects', 'url' => '#work', 'filter' => 'all'],
                ['label' => 'Product design', 'url' => '#work', 'filter' => 'design'],
                ['label' => 'Development', 'url' => '#work', 'filter' => 'development'],
                ['label' => 'Strategy', 'url' => '#work', 'filter' => 'strategy']
            ]],
            ['label' => 'Gallery', 'url' => '#gallery', 'children' => []],
            ['label' => 'Blog', 'url' => '#blog', 'children' => []],
            ['label' => 'Contact', 'url' => '#contact', 'children' => []]
        ]
    ],
    'portfolio_social_posts' => [
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
            'visible' => true
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
            'visible' => true
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
            'visible' => true
        ]
    ],
    'portfolio_about' => [
        'header_label' => 'About Me',
        'header_title' => 'Who I Am',
        'image' => 'https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg',
        'profile_kicker' => 'The person behind the work',
        'statement_title' => 'More than what I do.',
        'statement_accent' => 'Why I do it.',
        'descriptions' => [
            'I’m Aashish Jha, a web developer based in Kathmandu, Nepal. I build modern web applications with PHP and MySQL.',
            'My toolkit includes PHP, MySQL, JavaScript, HTML, CSS, Bootstrap, and SEO.',
            'I love to learn and build solutions that make a difference.'
        ],
        'objectives_kicker' => 'What guides me',
        'objectives_title' => 'My objectives & values.',
        'values' => [
            'curiosity' => ['label' => 'Curiosity', 'description' => 'I ask questions, stay open to new perspectives, and look for the human need behind every brief.'],
            'craft' => ['label' => 'Craft', 'description' => 'I care about the details: clear words, thoughtful interactions, and work that feels as good as it functions.'],
            'care' => ['label' => 'Care', 'description' => 'I make room for collaboration and accessibility so more people can feel considered in the outcome.'],
            'learning' => ['label' => 'Always learning', 'description' => 'I treat each project as a chance to experiment, reflect, and make the next iteration better.']
        ],
        'footer_text' => 'Explore my work, read my notes, or get in touch at info@aashish-jha.com.np.'
    ],
    'portfolio_skills' => [
        'visible' => true,
        'title' => 'What I Do',
        'description' => 'End-to-end web development services for businesses and individuals.',
        'items' => [
            ['icon' => 'fa-code', 'title' => 'Website Development', 'description' => 'Custom, responsive websites built to present your business clearly and help visitors take action.', 'details' => 'From page structure and visual layout to interactive features, I build websites around your goals and audience.', 'visible' => true],
            ['icon' => 'fa-screwdriver-wrench', 'title' => 'Website Maintenance', 'description' => 'Ongoing updates and improvements to keep your website reliable, current, and running smoothly.', 'details' => 'Maintenance can include content updates, routine checks, small enhancements, and help keeping your site in good shape.', 'visible' => true],
            ['icon' => 'fa-bug', 'title' => 'Bug Fixing', 'description' => 'Careful troubleshooting to find and fix website issues that interrupt the experience.', 'details' => 'I investigate reported behavior, identify the likely cause, and apply a focused fix while checking related functionality.', 'visible' => true],
            ['icon' => 'fa-mobile-screen-button', 'title' => 'Responsive Design', 'description' => 'Layouts that adapt cleanly to phones, tablets, laptops, and desktop displays.', 'details' => 'I refine page structure, sizing, spacing, and interactions so content remains usable across common screen sizes.', 'visible' => true],
            ['icon' => 'fa-database', 'title' => 'Database Design', 'description' => 'Organized database structures to support your application’s data and everyday workflows.', 'details' => 'Work may include planning tables and relationships, choosing appropriate fields, and connecting the database to your application.', 'visible' => true]
        ]
    ],
    'portfolio_journey' => [
        'eyebrow' => 'The journey so far',
        'title' => 'Experience & Education',
        'description' => 'Explore the education, experience, and special talents behind my work.',
        'items' => [
            [
                'category' => 'educational', 'title' => 'Education & Learning', 'icon' => 'fa-graduation-cap',
                'date' => 'Current status: Add your status', 'summary' => 'Qualification · Institution',
                'detail' => 'Add your area of study, dates, and a learning experience that shaped your work.',
                'features' => ['Education history', 'Qualifications and study', 'Ongoing learning'],
                'outcome' => 'Learning and practice continue to shape the way I approach new challenges.',
                'attachments' => [
                    'uploads/portfolio/f8395ea72410ef0f652972f4bb04bb4d.jpg',
                    'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
                    'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg'
                ],
                'visible' => true
            ],
            [
                'category' => 'experience', 'title' => 'Professional Experience', 'icon' => 'fa-briefcase',
                'date' => '2023 — Present', 'summary' => 'Your Current Role · Company',
                'detail' => '2023 — Present — Your Current Role · Company — Describe your focus, the people you work with, and the impact you make. 2020 — 2023 — Previous Role · Organization — Highlight an accomplishment, a skill you developed, or a challenge you helped solve.',
                'features' => ['Roles and responsibilities', 'Professional milestones', 'Skills developed through work'],
                'outcome' => 'Explore the roles, responsibilities, and milestones that have shaped my work.',
                'attachments' => [
                    'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg',
                    'uploads/portfolio/f010771f6a2a4d7b3af4b589b6c7ba2e.jpg',
                    'uploads/portfolio/f19238011240a10906f2f7d53e223e46.jpg'
                ],
                'visible' => true
            ],
            [
                'category' => 'talent', 'title' => 'Special Talent & Creativity', 'icon' => 'fa-wand-magic-sparkles',
                'date' => 'Skills & interests', 'summary' => 'A place to highlight creative strengths, personal interests, and special achievements.',
                'detail' => 'Add a description of the talents, creative work, or special accomplishments you would like to share.',
                'features' => ['Creative strengths', 'Personal projects and interests', 'Special achievements'],
                'outcome' => 'Personal strengths and creative practice bring a unique perspective to every project.',
                'attachments' => [
                    'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
                    'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
                    'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg'
                ],
                'visible' => true
            ]
        ]
    ],
    'portfolio_faqs' => [
        'title' => 'FAQs.',
        'categories' => [
            'about' => 'About me',
            'experience' => 'Experience',
            'skills' => 'Skills and tools',
            'working' => 'Working together'
        ],
        'items' => [
            ['category' => 'about', 'question' => 'What do you do?', 'answer' => 'I’m Shumi, a web developer who enjoys learning and building practical web experiences with PHP and MySQL.', 'visible' => true],
            ['category' => 'experience', 'question' => 'What kind of experience do you have?', 'answer' => 'I build personal web projects and keep growing my skills through hands-on learning. My current focus is responsive websites and applications using PHP, MySQL, JavaScript, HTML, and CSS.', 'visible' => true],
            ['category' => 'skills', 'question' => 'What technologies do you work with?', 'answer' => 'My areas of focus include PHP, MySQL, JavaScript, HTML, CSS, Bootstrap, SEO, and web development.', 'visible' => true],
            ['category' => 'working', 'question' => 'How can I get in touch?', 'answer' => 'For project or collaboration inquiries, contact Shumi using the contact form on this portfolio.', 'visible' => true]
        ]
    ],
    'portfolio_projects' => [
        'projects' => [
            [
                'category' => 'portfolio', 'title' => 'Personal Portfolio',
                'summary' => 'A personal website to introduce my work, share selected projects, and make it easy to get in touch.',
                'image' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg',
                'image_alt' => 'Personal portfolio project preview', 'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
                'contribution' => 'A portfolio project bringing profile, project highlights, and contact information together.',
                'outcome' => 'A single place for visitors to learn about my work and explore my projects.',
                'price_birr' => 100, 'visible' => true
            ],
            [
                'category' => 'platform', 'title' => 'Web Platform',
                'summary' => 'A responsive platform that organizes useful content and helps visitors find the information they need.',
                'image' => 'uploads/portfolio/f8395ea72410ef0f652972f4bb04bb4d.jpg',
                'image_alt' => 'Web platform project preview', 'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
                'contribution' => 'A web platform project focused on clear navigation and an adaptable interface.',
                'outcome' => 'A structured online experience designed to work across desktop and mobile screens.',
                'price_birr' => 100, 'visible' => true
            ],
            [
                'category' => 'application', 'title' => 'Web Application',
                'summary' => 'A web application concept combining interactive features with a clear, straightforward user interface.',
                'image' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg',
                'image_alt' => 'Web application project preview', 'tags' => ['PHP', 'MySQL', 'CSS', 'HTML', 'JS'],
                'contribution' => 'An application project bringing interface elements and server-side functionality together.',
                'outcome' => 'A foundation for delivering practical features through the web.',
                'price_birr' => 100, 'visible' => true
            ]
        ],
        'goals' => [
            [
                'id' => 'mission', 'title' => 'Mission', 'icon' => 'fa-bullseye',
                'description' => 'Build useful, accessible web experiences that solve real problems.',
                'detail' => 'I turn ideas into dependable websites and applications through thoughtful design, clear communication, and practical PHP, MySQL, JavaScript, HTML, and CSS development.',
                'visible' => true
            ],
            [
                'id' => 'vision', 'title' => 'Vision', 'icon' => 'fa-eye',
                'description' => 'Create digital experiences people can trust and enjoy using.',
                'detail' => 'My vision is to keep growing as a developer and contribute to an inclusive web where reliable technology, useful content, and intuitive interactions work well for everyone.',
                'visible' => true
            ],
            [
                'id' => 'core-value', 'title' => 'Core Value', 'icon' => 'fa-gem',
                'description' => 'Lead with curiosity, care, integrity, and a commitment to learning.',
                'detail' => 'I value honest collaboration, attention to detail, accessible choices, and continuous improvement. These principles guide decisions from the first conversation through delivery.',
                'visible' => true
            ]
        ]
    ],
    'portfolio_sections' => [
        'gallery' => [
            'visible' => true,
            'title' => 'Moments & Milestones',
            'description' => 'Explore the spaces, achievements, and creative ideas behind my work.',
            'items' => [
                ['category' => 'workspace', 'category_label' => 'Workspace', 'title' => 'Workspace', 'type' => 'Workspace & process', 'summary' => 'A glimpse into the ideas, tools, and quiet moments that make space for focused work.', 'detail' => 'The workspace is where sketches, experiments, and plans begin to take shape.', 'outcome' => 'A practical, thoughtful environment helps turn early ideas into useful digital experiences.', 'images' => [['src' => 'uploads/portfolio/a3c8b7c23d101b175fb68e7e150af49f.jpg', 'alt' => 'A workspace and creative process moment'], ['src' => 'uploads/portfolio/f010771f6a2a4d7b3af4b589b6c7ba2e.jpg', 'alt' => 'A focused work-in-progress moment'], ['src' => 'uploads/portfolio/f19238011240a10906f2f7d53e223e46.jpg', 'alt' => 'A detail from the workspace']], 'visible' => true],
                ['category' => 'awards', 'category_label' => 'Awards', 'title' => 'Awards', 'type' => 'Recognition & milestones', 'summary' => 'A rotating set of highlights that celebrate progress, learning, and meaningful milestones.', 'detail' => 'Milestones mark the effort, practice, and collaboration behind each step forward.', 'outcome' => 'Every achievement is a reminder to keep learning and creating with care.', 'images' => [['src' => 'uploads/portfolio/f8395ea72410ef0f652972f4bb04bb4d.jpg', 'alt' => 'A project achievement highlight'], ['src' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg', 'alt' => 'A web project milestone'], ['src' => 'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg', 'alt' => 'A portfolio milestone moment']], 'visible' => true],
                ['category' => 'creative', 'category_label' => 'Creative & Innovation', 'title' => 'Creative & Innovation', 'type' => 'Ideas & experiments', 'summary' => 'A collection of visual ideas and experiments exploring how thoughtful design can make the web more useful.', 'detail' => 'Creativity is a process of asking questions, exploring possibilities, and making ideas tangible.', 'outcome' => 'Small experiments open new directions for clear, engaging, and practical web experiences.', 'images' => [['src' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg', 'alt' => 'A creative web application idea'], ['src' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg', 'alt' => 'A web platform design experiment'], ['src' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg', 'alt' => 'A web development innovation highlight']], 'visible' => true]
            ]
        ],
        'blog' => [
            'visible' => true,
            'title' => 'Articles & Notes',
            'description' => 'Ideas, lessons, and reflections from designing and building for the web.',
            'items' => [
                ['category' => 'Field Notes', 'date' => 'June 12, 2025', 'date_iso' => '2025-06-12', 'title' => 'Designing for the questions people actually ask', 'summary' => 'A practical reminder to start with real user needs, not assumptions.', 'body' => 'Good product work begins by listening. Ask people what they are trying to do, what gets in their way, and what a successful outcome looks like. Those answers create a stronger foundation than adding features because they seem familiar. Start with what people are really trying to accomplish, then make the next step clear, useful, and easy to find.', 'media_type' => 'image', 'image' => 'uploads/portfolio/56755a6659cd876c780a33d0b4acf5e6.jpg', 'image_alt' => 'Portfolio project exploring user-focused design', 'visible' => true],
                ['category' => 'Process', 'date' => 'June 5, 2025', 'date_iso' => '2025-06-05', 'title' => 'A small design system can make a big difference', 'summary' => 'How a few clear patterns can improve consistency and team momentum.', 'body' => 'A useful design system does not need to be large. Start with shared typography, spacing, colors, and a handful of repeated components. Document why each pattern exists and make it easy for teammates to suggest improvements. Small, well-used patterns help a product feel cohesive and make everyday design decisions easier.', 'media_type' => 'image', 'image' => 'uploads/portfolio/64d8f2681b74aa60857e95488901da36.jpg', 'image_alt' => 'Web platform project demonstrating consistent design patterns', 'visible' => true],
                ['category' => 'Learning', 'date' => 'May 28, 2025', 'date_iso' => '2025-05-28', 'title' => 'What I learned from building in the open', 'summary' => 'Sharing work early can make collaboration clearer and more generous.', 'body' => 'Showing unfinished work invites better questions sooner. Explain what you are testing, make the current limitations visible, and treat feedback as material for the next iteration rather than a verdict on the first one. Sharing the process creates room to learn together and improve the result while changes are still easy to make.', 'media_type' => 'image', 'image' => 'uploads/portfolio/6baec982c3c550e9abef6ae81b3565f1.jpg', 'image_alt' => 'Web application concept shared while in progress', 'visible' => true]
            ]
        ],
        'contact' => [
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
        ],
        'search' => ['visible' => true, 'placeholder' => 'Search...'],
        'theme' => ['visible' => true, 'default' => 'dark']
    ]
];

$setting_labels = [
    'blog_banner_slides' => ['Homepage banner slides', 'Homepage hero slides. Keep each slide’s title, description, alt text, and image path.'],
    'blog_feature_update' => ['Featured update', 'The featured update shown on the homepage.'],
    'blog_insurance_news' => ['Homepage news cards', 'News cards displayed alongside the featured update.'],
    'blog_core_pillars' => ['About / company pillars', 'Vision, mission, values, and other company cards.'],
    'blog_performance' => ['Performance highlights', 'Metrics and report link shown near the bottom of the homepage.'],
    'blog_product_catalog' => ['Product catalog', 'Optional product overrides, organized by category and product key.'],
    'blog_executive_management' => ['Executive management', 'Executive banner and member cards shown on the About section.'],
    'portfolio_header' => ['Portfolio header panel', 'Edit the portfolio identity and upload a logo image.'],
    'portfolio_home' => ['Portfolio home page', 'Edit hero content and manage navigation links with optional dropdown sublinks.'],
    'portfolio_sections' => ['Portfolio sections', 'Manage Gallery, Blog, Contact, Search Bar, and Dark Mode.']
];

$settings = $default_settings;
$setting_result = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('blog_banner_slides','blog_feature_update','blog_insurance_news','blog_core_pillars','blog_performance','blog_product_catalog','blog_executive_management','portfolio_header','portfolio_home','portfolio_social_posts','portfolio_about','portfolio_skills','portfolio_journey','portfolio_faqs','portfolio_projects','portfolio_sections')");
if ($setting_result) {
    while ($row = $setting_result->fetch_assoc()) {
        $decoded = json_decode($row['setting_value'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if ($row['setting_key'] === 'portfolio_header' && !isset($decoded['brand_name']) && isset($decoded['name']) && is_string($decoded['name'])) {
                $decoded['brand_name'] = $decoded['name'];
            }
            if ($row['setting_key'] === 'portfolio_social_posts') {
                $settings[$row['setting_key']] = array_values($decoded);
            } elseif ($row['setting_key'] === 'portfolio_projects') {
                $settings[$row['setting_key']] = $decoded;
            } elseif ($row['setting_key'] === 'portfolio_sections') {
                $settings[$row['setting_key']] = array_replace($settings[$row['setting_key']], $decoded);
            } else {
                $settings[$row['setting_key']] = array_replace($settings[$row['setting_key']], $decoded);
            }
        }
    }
}
$aboutDefaults = $default_settings['portfolio_about'];
$aboutSaved = $settings['portfolio_about'];
foreach (['header_label', 'header_title', 'profile_kicker', 'statement_title', 'statement_accent', 'objectives_kicker', 'objectives_title', 'footer_text'] as $aboutField) {
    if (!isset($aboutSaved[$aboutField]) || !is_string($aboutSaved[$aboutField]) || trim($aboutSaved[$aboutField]) === '') {
        $aboutSaved[$aboutField] = $aboutDefaults[$aboutField];
    }
}
$aboutSaved['image'] = isset($aboutSaved['image']) && is_string($aboutSaved['image']) && ($aboutSaved['image'] === '' || portfolioDashboardAssetValid($aboutSaved['image']))
    ? $aboutSaved['image']
    : $aboutDefaults['image'];
$savedDescriptions = isset($aboutSaved['descriptions']) && is_array($aboutSaved['descriptions']) ? $aboutSaved['descriptions'] : [];
$aboutSaved['descriptions'] = [];
foreach ($aboutDefaults['descriptions'] as $index => $defaultDescription) {
    $aboutSaved['descriptions'][$index] = isset($savedDescriptions[$index]) && is_string($savedDescriptions[$index]) && trim($savedDescriptions[$index]) !== ''
        ? trim($savedDescriptions[$index])
        : $defaultDescription;
}
$savedValues = isset($aboutSaved['values']) && is_array($aboutSaved['values']) ? $aboutSaved['values'] : [];
$aboutSaved['values'] = [];
foreach ($aboutDefaults['values'] as $valueKey => $defaultValue) {
    $savedValue = isset($savedValues[$valueKey]) && is_array($savedValues[$valueKey]) ? $savedValues[$valueKey] : [];
    $aboutSaved['values'][$valueKey] = [
        'label' => isset($savedValue['label']) && is_string($savedValue['label']) && trim($savedValue['label']) !== '' ? trim($savedValue['label']) : $defaultValue['label'],
        'description' => isset($savedValue['description']) && is_string($savedValue['description']) && trim($savedValue['description']) !== '' ? trim($savedValue['description']) : $defaultValue['description']
    ];
}
$settings['portfolio_about'] = $aboutSaved;
$portfolioSkillsPanel = array_replace($default_settings['portfolio_skills'], $settings['portfolio_skills']);
if (!is_bool($portfolioSkillsPanel['visible'] ?? null)) {
    $portfolioSkillsPanel['visible'] = $default_settings['portfolio_skills']['visible'];
}
foreach (['title', 'description'] as $skillsField) {
    if (!isset($portfolioSkillsPanel[$skillsField]) || !is_string($portfolioSkillsPanel[$skillsField]) || trim($portfolioSkillsPanel[$skillsField]) === '') {
        $portfolioSkillsPanel[$skillsField] = $default_settings['portfolio_skills'][$skillsField];
    }
}
$normalizedSkills = [];
if (is_array($portfolioSkillsPanel['items'] ?? null)) {
    foreach (array_slice($portfolioSkillsPanel['items'], 0, 30) as $skill) {
        if (!is_array($skill) || !isset($skill['icon'], $skill['title'], $skill['description'], $skill['details']) ||
            !is_string($skill['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $skill['icon']) !== 1 ||
            !is_string($skill['title']) || trim($skill['title']) === '' || strlen($skill['title']) > 120 ||
            !is_string($skill['description']) || trim($skill['description']) === '' || strlen($skill['description']) > 1000 ||
            !is_string($skill['details']) || trim($skill['details']) === '' || strlen($skill['details']) > 2000) {
            continue;
        }
        $normalizedSkills[] = [
            'icon' => $skill['icon'],
            'title' => trim($skill['title']),
            'description' => trim($skill['description']),
            'details' => trim($skill['details']),
            'visible' => !isset($skill['visible']) || $skill['visible'] === true
        ];
    }
}
$portfolioSkillsPanel['items'] = $normalizedSkills !== [] || ($portfolioSkillsPanel['items'] ?? []) === []
    ? $normalizedSkills
    : $default_settings['portfolio_skills']['items'];
$portfolioJourneyPanel = array_replace($default_settings['portfolio_journey'], $settings['portfolio_journey']);
foreach (['eyebrow', 'title', 'description'] as $journeyField) {
    if (!isset($portfolioJourneyPanel[$journeyField]) || !is_string($portfolioJourneyPanel[$journeyField]) || trim($portfolioJourneyPanel[$journeyField]) === '') {
        $portfolioJourneyPanel[$journeyField] = $default_settings['portfolio_journey'][$journeyField];
    }
}
$normalizedJourneyItems = [];
if (is_array($portfolioJourneyPanel['items'] ?? null)) {
    foreach (array_slice($portfolioJourneyPanel['items'], 0, 20) as $item) {
        if (!is_array($item) || !isset($item['category'], $item['title'], $item['icon'], $item['date'], $item['summary'], $item['detail'], $item['features'], $item['outcome'], $item['attachments']) ||
            !in_array($item['category'], ['educational', 'experience', 'talent'], true) ||
            !is_string($item['title']) || trim($item['title']) === '' ||
            !is_string($item['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $item['icon']) !== 1 ||
            !is_string($item['date']) || !is_string($item['summary']) ||
            !is_string($item['detail']) || !is_array($item['features']) ||
            !is_string($item['outcome']) || !is_array($item['attachments'])) {
            continue;
        }
        $features = array_values(array_filter($item['features'], static fn($feature): bool => is_string($feature) && trim($feature) !== '' && strlen($feature) <= 120));
        $attachments = array_values(array_filter($item['attachments'], static fn($attachment): bool => is_string($attachment) && portfolioDashboardAssetValid($attachment)));
        $normalizedJourneyItems[] = [
            'category' => $item['category'],
            'title' => trim($item['title']),
            'icon' => $item['icon'],
            'date' => trim($item['date']),
            'summary' => trim($item['summary']),
            'detail' => trim($item['detail']),
            'features' => array_slice($features, 0, 12),
            'outcome' => trim($item['outcome']),
            'attachments' => array_slice($attachments, 0, 8),
            'visible' => !isset($item['visible']) || $item['visible'] === true
        ];
    }
}
$portfolioJourneyPanel['items'] = $normalizedJourneyItems;

$portfolioFaqsPanel = array_replace($default_settings['portfolio_faqs'], $settings['portfolio_faqs']);
if (!isset($portfolioFaqsPanel['title']) || !is_string($portfolioFaqsPanel['title']) || trim($portfolioFaqsPanel['title']) === '') {
    $portfolioFaqsPanel['title'] = $default_settings['portfolio_faqs']['title'];
}
$portfolioFaqsPanel['categories'] = $default_settings['portfolio_faqs']['categories'];
$savedFaqCategories = is_array($settings['portfolio_faqs']['categories'] ?? null) ? $settings['portfolio_faqs']['categories'] : [];
foreach ($portfolioFaqsPanel['categories'] as $categoryKey => $defaultLabel) {
    $savedLabel = $savedFaqCategories[$categoryKey] ?? null;
    if (is_string($savedLabel) && trim($savedLabel) !== '') {
        $portfolioFaqsPanel['categories'][$categoryKey] = trim($savedLabel);
    }
}
$normalizedFaqItems = [];
if (is_array($portfolioFaqsPanel['items'] ?? null)) {
    foreach (array_slice($portfolioFaqsPanel['items'], 0, 40) as $item) {
        if (!is_array($item) || !isset($item['category'], $item['question'], $item['answer']) ||
            !is_string($item['category']) ||
            !isset($portfolioFaqsPanel['categories'][$item['category']]) ||
            !is_string($item['question']) || trim($item['question']) === '' ||
            !is_string($item['answer']) || trim($item['answer']) === '') {
            continue;
        }
        $normalizedFaqItems[] = [
            'category' => $item['category'],
            'question' => trim($item['question']),
            'answer' => trim($item['answer']),
            'visible' => !isset($item['visible']) || $item['visible'] === true
        ];
    }
}
$portfolioFaqsPanel['items'] = $normalizedFaqItems;

$projectCategoryLabels = [
    'portfolio' => 'Personal Portfolio',
    'platform' => 'Web Platform',
    'application' => 'Web Application'
];
$projectSettings = is_array($settings['portfolio_projects'] ?? null)
    ? $settings['portfolio_projects']
    : $default_settings['portfolio_projects'];
$portfolioProjectsPanel = ['projects' => [], 'goals' => []];
foreach (array_slice(is_array($projectSettings['projects'] ?? null) ? $projectSettings['projects'] : [], 0, 24) as $project) {
    if (!is_array($project) ||
        !isset($project['category'], $project['title'], $project['summary'], $project['image'], $project['image_alt'], $project['tags'], $project['contribution'], $project['outcome'], $project['price_birr']) ||
        !is_string($project['category']) || !isset($projectCategoryLabels[$project['category']]) ||
        !is_string($project['title']) || trim($project['title']) === '' || strlen($project['title']) > 160 ||
        !is_string($project['summary']) || trim($project['summary']) === '' || strlen($project['summary']) > 1500 ||
        !is_string($project['image']) || !portfolioDashboardAssetValid($project['image']) ||
        !is_string($project['image_alt']) || trim($project['image_alt']) === '' || strlen($project['image_alt']) > 200 ||
        !is_array($project['tags']) ||
        !is_string($project['contribution']) || trim($project['contribution']) === '' || strlen($project['contribution']) > 1500 ||
        !is_string($project['outcome']) || trim($project['outcome']) === '' || strlen($project['outcome']) > 1500 ||
        filter_var($project['price_birr'], FILTER_VALIDATE_INT) === false ||
        (int) $project['price_birr'] < 0 || (int) $project['price_birr'] > 1000000) {
        continue;
    }
    $tags = array_values(array_filter(
        $project['tags'],
        static fn($tag): bool => is_string($tag) && trim($tag) !== '' && strlen($tag) <= 40
    ));
    $portfolioProjectsPanel['projects'][] = [
        'category' => $project['category'],
        'title' => trim($project['title']),
        'summary' => trim($project['summary']),
        'image' => trim($project['image']),
        'image_alt' => trim($project['image_alt']),
        'tags' => array_slice($tags, 0, 12),
        'contribution' => trim($project['contribution']),
        'outcome' => trim($project['outcome']),
        'price_birr' => (int) $project['price_birr'],
        'visible' => !isset($project['visible']) || $project['visible'] === true
    ];
}
$allowedProjectGoalIds = ['mission', 'vision', 'core-value'];
$seenProjectGoalIds = [];
foreach (array_slice(is_array($projectSettings['goals'] ?? null) ? $projectSettings['goals'] : [], 0, 3) as $goal) {
    if (!is_array($goal) ||
        !isset($goal['id'], $goal['title'], $goal['icon'], $goal['description'], $goal['detail']) ||
        !is_string($goal['id']) || !in_array($goal['id'], $allowedProjectGoalIds, true) || isset($seenProjectGoalIds[$goal['id']]) ||
        !is_string($goal['title']) || trim($goal['title']) === '' || strlen($goal['title']) > 100 ||
        !is_string($goal['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $goal['icon']) !== 1 ||
        !is_string($goal['description']) || trim($goal['description']) === '' || strlen($goal['description']) > 1500 ||
        !is_string($goal['detail']) || trim($goal['detail']) === '' || strlen($goal['detail']) > 3000) {
        continue;
    }
    $seenProjectGoalIds[$goal['id']] = true;
    $portfolioProjectsPanel['goals'][] = [
        'id' => $goal['id'],
        'title' => trim($goal['title']),
        'icon' => $goal['icon'],
        'description' => trim($goal['description']),
        'detail' => trim($goal['detail']),
        'visible' => !isset($goal['visible']) || $goal['visible'] === true
    ];
}
if (!array_key_exists('projects', $projectSettings)) {
    $portfolioProjectsPanel['projects'] = $default_settings['portfolio_projects']['projects'];
}
if (!array_key_exists('goals', $projectSettings)) {
    $portfolioProjectsPanel['goals'] = $default_settings['portfolio_projects']['goals'];
}

$duration_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'blog_banner_duration' LIMIT 1");
$duration_row = $duration_result ? $duration_result->fetch_assoc() : null;
$banner_duration = isset($duration_row['setting_value']) ? (int) $duration_row['setting_value'] : 5;
$banner_duration = ($banner_duration >= 2 && $banner_duration <= 20) ? $banner_duration : 5;

$message = '';
$error = '';
$newPostsToNotify = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['blog_dashboard2_csrf'], $submitted_token)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (isset($_POST['save_portfolio_advertisement'])) {
        $advertisementId = filter_var($_POST['advertisement_id'] ?? 0, FILTER_VALIDATE_INT);
        $advertisementForm = [
            'ad_id' => $advertisementId === false || $advertisementId < 0 ? 0 : $advertisementId,
            'brand_name' => is_string($_POST['advertisement_brand_name'] ?? null) ? trim($_POST['advertisement_brand_name']) : '',
            'product_name' => is_string($_POST['advertisement_product_name'] ?? null) ? trim($_POST['advertisement_product_name']) : '',
            'description' => is_string($_POST['advertisement_description'] ?? null) ? trim($_POST['advertisement_description']) : '',
            'brand_logo_url' => is_string($_POST['advertisement_brand_logo_url'] ?? null) ? trim($_POST['advertisement_brand_logo_url']) : '',
            'product_image_url' => is_string($_POST['advertisement_product_image_url'] ?? null) ? trim($_POST['advertisement_product_image_url']) : '',
            'poster_image_url' => is_string($_POST['advertisement_poster_image_url'] ?? null) ? trim($_POST['advertisement_poster_image_url']) : '',
            'video_file_url' => is_string($_POST['advertisement_video_file_url'] ?? null) ? trim($_POST['advertisement_video_file_url']) : '',
            'pdf_file_url' => is_string($_POST['advertisement_pdf_file_url'] ?? null) ? trim($_POST['advertisement_pdf_file_url']) : '',
            'target_url' => is_string($_POST['advertisement_target_url'] ?? null) ? trim($_POST['advertisement_target_url']) : '',
            'cta_label' => is_string($_POST['advertisement_cta_label'] ?? null) ? trim($_POST['advertisement_cta_label']) : '',
            'is_pinned' => isset($_POST['advertisement_is_pinned'])
        ];
        if ($advertisementId === false || $advertisementId < 0 ||
            $advertisementForm['brand_name'] === '' || strlen($advertisementForm['brand_name']) > 120 ||
            $advertisementForm['product_name'] === '' || strlen($advertisementForm['product_name']) > 160 ||
            $advertisementForm['description'] === '' || strlen($advertisementForm['description']) > 2000 ||
            strlen($advertisementForm['brand_logo_url']) > 500 ||
            strlen($advertisementForm['product_image_url']) > 500 ||
            strlen($advertisementForm['poster_image_url']) > 500 ||
            strlen($advertisementForm['video_file_url']) > 500 ||
            strlen($advertisementForm['pdf_file_url']) > 500 ||
            strlen($advertisementForm['target_url']) > 1000 ||
            $advertisementForm['cta_label'] === '' || strlen($advertisementForm['cta_label']) > 80) {
            $error = 'Complete all required advertisement fields and keep each value within its length limit.';
        } elseif (($advertisementForm['product_image_url'] !== '' && !portfolioAdvertisementAssetValid($advertisementForm['product_image_url'])) ||
            ($advertisementForm['brand_logo_url'] !== '' && !portfolioAdvertisementAssetValid($advertisementForm['brand_logo_url'])) ||
            ($advertisementForm['poster_image_url'] !== '' && !portfolioAdvertisementAssetValid($advertisementForm['poster_image_url'])) ||
            ($advertisementForm['video_file_url'] !== '' && !portfolioAdvertisementVideoValid($advertisementForm['video_file_url'])) ||
            ($advertisementForm['pdf_file_url'] !== '' && !portfolioAdvertisementPdfValid($advertisementForm['pdf_file_url'])) ||
            !portfolioAdvertisementLinkValid($advertisementForm['target_url'])) {
            $error = 'Image, MP4/WebM video, and PDF paths must be valid safe relative paths or HTTP(S) URLs. The optional destination must be a safe relative path, anchor, or HTTP(S) URL.';
        } elseif ($portfolioAdvertisingLoadError) {
            $error = 'Advertisement storage is unavailable; no changes were saved.';
        } else {
            $previousAdvertisement = null;
            if ($advertisementId > 0) {
                foreach ($portfolioAdvertisements as $existingAdvertisement) {
                    if ((int) $existingAdvertisement['ad_id'] === $advertisementId) {
                        $previousAdvertisement = $existingAdvertisement;
                        break;
                    }
                }
                if ($previousAdvertisement === null) {
                    $error = 'That advertisement could not be found.';
                }
            }
            $newUploadPaths = [];
            $originalAdvertisementMedia = [
                'product_image_url' => $advertisementForm['product_image_url'],
                'poster_image_url' => $advertisementForm['poster_image_url'],
                'video_file_url' => $advertisementForm['video_file_url'],
                'pdf_file_url' => $advertisementForm['pdf_file_url'],
            ];
            try {
                if ($error === '') {
                    $uploadedProductImage = portfolioStoreAdvertisementUpload($_FILES['advertisement_product_image_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'image');
                    if ($uploadedProductImage !== '') {
                        $newUploadPaths[] = $uploadedProductImage;
                        $advertisementForm['product_image_url'] = $uploadedProductImage;
                    }
                    $uploadedPosterImage = portfolioStoreAdvertisementUpload($_FILES['advertisement_poster_image_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'image');
                    if ($uploadedPosterImage !== '') {
                        $newUploadPaths[] = $uploadedPosterImage;
                        $advertisementForm['poster_image_url'] = $uploadedPosterImage;
                    }
                    $uploadedVideo = portfolioStoreAdvertisementUpload($_FILES['advertisement_video_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'video');
                    if ($uploadedVideo !== '') {
                        $newUploadPaths[] = $uploadedVideo;
                        $advertisementForm['video_file_url'] = $uploadedVideo;
                    }
                    $uploadedPdf = portfolioStoreAdvertisementUpload($_FILES['advertisement_pdf_file'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'pdf');
                    if ($uploadedPdf !== '') {
                        $newUploadPaths[] = $uploadedPdf;
                        $advertisementForm['pdf_file_url'] = $uploadedPdf;
                    }
                    if ($advertisementForm['product_image_url'] === '' && $previousAdvertisement !== null) {
                        $advertisementForm['product_image_url'] = (string) $previousAdvertisement['product_image_url'];
                    }
                    if ($advertisementForm['product_image_url'] === '') {
                        throw new RuntimeException('Upload a product image or enter its safe URL/path.');
                    }
                    if ($uploadedPosterImage === '' && !isset($_POST['advertisement_remove_poster_image']) && $advertisementForm['poster_image_url'] === '' && $previousAdvertisement !== null) {
                        $advertisementForm['poster_image_url'] = (string) ($previousAdvertisement['poster_image_url'] ?? '');
                    }
                    if ($uploadedPdf === '' && !isset($_POST['advertisement_remove_pdf_file']) && $advertisementForm['pdf_file_url'] === '' && $previousAdvertisement !== null) {
                        $advertisementForm['pdf_file_url'] = (string) ($previousAdvertisement['pdf_file_url'] ?? '');
                    }
                    if ($uploadedVideo === '' && !isset($_POST['advertisement_remove_video_file']) && $advertisementForm['video_file_url'] === '' && $previousAdvertisement !== null) {
                        $advertisementForm['video_file_url'] = (string) ($previousAdvertisement['video_file_url'] ?? '');
                    }
                    if ($uploadedPosterImage === '' && isset($_POST['advertisement_remove_poster_image'])) {
                        $advertisementForm['poster_image_url'] = '';
                    }
                    if ($uploadedPdf === '' && isset($_POST['advertisement_remove_pdf_file'])) {
                        $advertisementForm['pdf_file_url'] = '';
                    }
                    if ($uploadedVideo === '' && isset($_POST['advertisement_remove_video_file'])) {
                        $advertisementForm['video_file_url'] = '';
                    }
                }
            } catch (Throwable $uploadException) {
                foreach ($newUploadPaths as $newUploadPath) {
                    $newUploadAbsolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newUploadPath);
                    if (is_file($newUploadAbsolutePath)) {
                        unlink($newUploadAbsolutePath);
                    }
                }
                $advertisementForm = array_replace($advertisementForm, $originalAdvertisementMedia);
                $error = $uploadException instanceof RuntimeException
                    ? $uploadException->getMessage()
                    : 'An advertisement upload could not be processed.';
            }
        }
        if ($error === '') {
            $brandName = $advertisementForm['brand_name'];
            $productName = $advertisementForm['product_name'];
            $description = $advertisementForm['description'];
            $brandLogoUrl = $advertisementForm['brand_logo_url'] === '' ? null : $advertisementForm['brand_logo_url'];
            $productImageUrl = $advertisementForm['product_image_url'];
            $posterImageUrl = $advertisementForm['poster_image_url'] === '' ? null : $advertisementForm['poster_image_url'];
            $videoFileUrl = $advertisementForm['video_file_url'] === '' ? null : $advertisementForm['video_file_url'];
            $pdfFileUrl = $advertisementForm['pdf_file_url'] === '' ? null : $advertisementForm['pdf_file_url'];
            $targetUrl = $advertisementForm['target_url'] === '' ? null : $advertisementForm['target_url'];
            $ctaLabel = $advertisementForm['cta_label'];
            $isPinned = $advertisementForm['is_pinned'] ? 1 : 0;
            try {
                if ($advertisementId > 0) {
                    $saveAdvertisement = $conn->prepare(
                        'UPDATE portfolio_advertisements
                         SET brand_name = ?, product_name = ?, description = ?, brand_logo_url = ?, product_image_url = ?, poster_image_url = ?, video_file_url = ?, pdf_file_url = ?, target_url = ?, cta_label = ?, is_pinned = ?
                         WHERE ad_id = ?'
                    );
                    if (!$saveAdvertisement) {
                        throw new RuntimeException('Could not prepare advertisement update: ' . $conn->error);
                    }
                    $saveAdvertisement->bind_param('ssssssssssii', $brandName, $productName, $description, $brandLogoUrl, $productImageUrl, $posterImageUrl, $videoFileUrl, $pdfFileUrl, $targetUrl, $ctaLabel, $isPinned, $advertisementId);
                } else {
                    $saveAdvertisement = $conn->prepare(
                        'INSERT INTO portfolio_advertisements
                         (brand_name, product_name, description, brand_logo_url, product_image_url, poster_image_url, video_file_url, pdf_file_url, target_url, cta_label, is_pinned)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    if (!$saveAdvertisement) {
                        throw new RuntimeException('Could not prepare advertisement insert: ' . $conn->error);
                    }
                    $saveAdvertisement->bind_param('ssssssssssi', $brandName, $productName, $description, $brandLogoUrl, $productImageUrl, $posterImageUrl, $videoFileUrl, $pdfFileUrl, $targetUrl, $ctaLabel, $isPinned);
                }
                if (!$saveAdvertisement->execute()) {
                    throw new RuntimeException('Could not save advertisement: ' . $saveAdvertisement->error);
                }
                if ($advertisementId === 0) {
                    $advertisementId = (int) $saveAdvertisement->insert_id;
                    $advertisementForm['ad_id'] = $advertisementId;
                }
                $saveAdvertisement->close();
                $message = 'Advertisement saved.';
            } catch (Throwable $advertisementException) {
                foreach ($newUploadPaths as $newUploadPath) {
                    $newUploadAbsolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newUploadPath);
                    if (is_file($newUploadAbsolutePath)) {
                        unlink($newUploadAbsolutePath);
                    }
                }
                $advertisementForm = array_replace($advertisementForm, $originalAdvertisementMedia);
                error_log('Could not save portfolio advertisement: ' . $advertisementException->getMessage());
                $error = 'The advertisement could not be saved. Check the database and PHP error log.';
            }
        }
    } elseif (isset($_POST['set_portfolio_advertisement_status'])) {
        $advertisementId = filter_var($_POST['advertisement_id'] ?? null, FILTER_VALIDATE_INT);
        $advertisementStatus = is_string($_POST['advertisement_status'] ?? null) ? $_POST['advertisement_status'] : '';
        if ($advertisementId === false || $advertisementId < 1 || !in_array($advertisementStatus, ['active', 'banned'], true)) {
            $error = 'Choose a valid advertisement and status.';
        } elseif ($portfolioAdvertisingLoadError) {
            $error = 'Advertisement storage is unavailable; no changes were made.';
        } else {
            try {
                $statusStatement = $conn->prepare('UPDATE portfolio_advertisements SET status = ? WHERE ad_id = ?');
                if (!$statusStatement) {
                    throw new RuntimeException('Could not prepare advertisement status update: ' . $conn->error);
                }
                $statusStatement->bind_param('si', $advertisementStatus, $advertisementId);
                if (!$statusStatement->execute()) {
                    throw new RuntimeException('Could not update advertisement status: ' . $statusStatement->error);
                }
                $statusStatement->close();
                $verifyAdvertisement = $conn->prepare('SELECT ad_id FROM portfolio_advertisements WHERE ad_id = ? LIMIT 1');
                if (!$verifyAdvertisement) {
                    throw new RuntimeException('Could not verify the advertisement: ' . $conn->error);
                }
                $verifyAdvertisement->bind_param('i', $advertisementId);
                $verifyAdvertisement->execute();
                $verifyAdvertisement->store_result();
                $advertisementExists = $verifyAdvertisement->num_rows > 0;
                $verifyAdvertisement->close();
                if (!$advertisementExists) {
                    $error = 'That advertisement no longer exists.';
                } else {
                    $message = $advertisementStatus === 'banned'
                        ? 'Advertisement banned; it will no longer be shown.'
                        : 'Advertisement restored and active.';
                }
            } catch (Throwable $advertisementException) {
                error_log('Could not change portfolio advertisement status: ' . $advertisementException->getMessage());
                $error = 'The advertisement status could not be changed. Check the database and PHP error log.';
            }
        }
    } elseif (isset($_POST['send_subscriber_email']) || isset($_POST['save_subscriber_draft'])) {
        $isSavingDraft = isset($_POST['save_subscriber_draft']);
        $mailboxFolder = 'compose';
        $recipient = is_string($_POST['subscriber_email'] ?? null) ? strtolower(trim($_POST['subscriber_email'])) : '';
        $ccInput = is_string($_POST['subscriber_cc'] ?? null) ? trim($_POST['subscriber_cc']) : '';
        $subject = is_string($_POST['subscriber_subject'] ?? null) ? trim($_POST['subscriber_subject']) : '';
        $body = is_string($_POST['subscriber_body'] ?? null) ? trim($_POST['subscriber_body']) : '';
        $draftId = filter_var($_POST['draft_id'] ?? null, FILTER_VALIDATE_INT);
        $draftId = $draftId !== false && $draftId > 0 ? $draftId : 0;
        $isKnownRecipient = false;
        foreach ($portfolioSubscribers as $subscriber) {
            if (isset($subscriber['email']) && is_string($subscriber['email']) &&
                strtolower($subscriber['email']) === $recipient && !empty($subscriber['is_active'])) {
                $isKnownRecipient = true;
                break;
            }
        }
        if (!$isSavingDraft && !$isKnownRecipient && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $contactRecipient = $conn->prepare('SELECT message_id FROM portfolio_contact_messages WHERE sender_email = ? LIMIT 1');
            if ($contactRecipient) {
                $contactRecipient->bind_param('s', $recipient);
                $contactRecipient->execute();
                $contactRecipient->store_result();
                $isKnownRecipient = $contactRecipient->num_rows > 0;
                $contactRecipient->close();
            }
        }
        if (!$isSavingDraft && (!$isKnownRecipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL))) {
            $error = 'Choose a valid active subscriber or contact-message sender before sending.';
        } elseif ($subject === '' || strlen($subject) > 200 || preg_match('/[\r\n]/', $subject)) {
            $error = 'Enter a subject under 200 characters without line breaks.';
        } elseif ($body === '' || strlen($body) > 10000) {
            $error = 'Enter an email message under 10,000 characters.';
        } else {
            $ccAddresses = $ccInput === '' ? [] : array_map('trim', explode(',', $ccInput));
            $invalidCc = count($ccAddresses) > 20;
            foreach ($ccAddresses as $ccAddress) {
                if ($ccAddress === '' || !filter_var($ccAddress, FILTER_VALIDATE_EMAIL)) {
                    $invalidCc = true;
                    break;
                }
            }
            if ($invalidCc) {
                $error = 'Enter up to 20 valid CC email addresses, separated by commas.';
            } elseif ($isSavingDraft) {
                try {
                    $identity = portfolioMailIdentity($conn);
                    $draftInsert = $draftId > 0
                        ? $conn->prepare(
                            "UPDATE portfolio_contact_messages SET sender_name = ?, sender_email = ?, recipient_email = ?, cc_emails = ?, subject = ?, message = ?
                             WHERE message_id = ? AND folder = 'drafts'"
                        )
                        : $conn->prepare(
                            "INSERT INTO portfolio_contact_messages
                             (sender_name, sender_email, recipient_email, cc_emails, subject, message, is_read, folder)
                             VALUES (?, ?, ?, ?, ?, ?, 1, 'drafts')"
                        );
                    if (!$draftInsert) {
                        throw new RuntimeException('Could not prepare draft storage: ' . $conn->error);
                    }
                    if ($draftId > 0) {
                        $draftInsert->bind_param('ssssssi', $identity['name'], $identity['email'], $recipient, $ccInput, $subject, $body, $draftId);
                    } else {
                        $draftInsert->bind_param('ssssss', $identity['name'], $identity['email'], $recipient, $ccInput, $subject, $body);
                    }
                    if (!$draftInsert->execute()) {
                        throw new RuntimeException('Could not save draft: ' . $draftInsert->error);
                    }
                    if ($draftId > 0 && $draftInsert->affected_rows === 0) {
                        $draftCheck = $conn->prepare("SELECT message_id FROM portfolio_contact_messages WHERE message_id = ? AND folder = 'drafts' LIMIT 1");
                        if (!$draftCheck) {
                            $draftInsert->close();
                            throw new RuntimeException('Could not verify the selected draft: ' . $conn->error);
                        }
                        $draftCheck->bind_param('i', $draftId);
                        $draftCheck->execute();
                        $draftCheck->store_result();
                        $draftExists = $draftCheck->num_rows > 0;
                        $draftCheck->close();
                        if (!$draftExists) {
                            $draftInsert->close();
                            throw new RuntimeException('The selected draft no longer exists.');
                        }
                    }
                    $draftInsert->close();
                    $message = 'Draft saved.';
                    $mailboxFolder = 'drafts';
                } catch (Throwable $draftException) {
                    error_log('Could not save portfolio mailbox draft: ' . $draftException->getMessage());
                    $error = 'The draft could not be saved. Check the database and PHP error log.';
                }
            } else {
                $identity = portfolioMailIdentity($conn);
                $safeSenderName = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $identity['name']) ?? '');
                $headers = [
                    'From: ' . $safeSenderName . ' <' . $identity['email'] . '>',
                    'Reply-To: ' . $identity['email'],
                    'MIME-Version: 1.0',
                    'Content-Type: text/plain; charset=UTF-8',
                ];
                if ($ccAddresses !== []) {
                    $headers[] = 'Cc: ' . implode(', ', $ccAddresses);
                }
                try {
                    if (mail($recipient, $subject, $body, implode("\r\n", $headers))) {
                        try {
                            $sentInsert = $draftId > 0
                                ? $conn->prepare(
                                    "UPDATE portfolio_contact_messages SET sender_name = ?, sender_email = ?, recipient_email = ?, cc_emails = ?, subject = ?, message = ?, folder = 'sent', is_read = 1
                                     WHERE message_id = ? AND folder = 'drafts'"
                                )
                                : $conn->prepare(
                                    "INSERT INTO portfolio_contact_messages
                                     (sender_name, sender_email, recipient_email, cc_emails, subject, message, is_read, folder)
                                     VALUES (?, ?, ?, ?, ?, ?, 1, 'sent')"
                                );
                            if (!$sentInsert) {
                                throw new RuntimeException('Could not prepare sent message storage: ' . $conn->error);
                            }
                            if ($draftId > 0) {
                                $sentInsert->bind_param('ssssssi', $identity['name'], $identity['email'], $recipient, $ccInput, $subject, $body, $draftId);
                            } else {
                                $sentInsert->bind_param('ssssss', $identity['name'], $identity['email'], $recipient, $ccInput, $subject, $body);
                            }
                            if (!$sentInsert->execute()) {
                                throw new RuntimeException('Could not save sent message: ' . $sentInsert->error);
                            }
                            if ($draftId > 0 && $sentInsert->affected_rows === 0) {
                                $draftCheck = $conn->prepare("SELECT message_id FROM portfolio_contact_messages WHERE message_id = ? AND folder = 'sent' LIMIT 1");
                                if (!$draftCheck) {
                                    $sentInsert->close();
                                    throw new RuntimeException('Could not verify the sent message: ' . $conn->error);
                                }
                                $draftCheck->bind_param('i', $draftId);
                                $draftCheck->execute();
                                $draftCheck->store_result();
                                $sentExists = $draftCheck->num_rows > 0;
                                $draftCheck->close();
                                if (!$sentExists) {
                                    $sentInsert->close();
                                    throw new RuntimeException('The selected draft could not be moved to Sent.');
                                }
                            }
                            $sentInsert->close();
                            $message = 'Email sent to ' . $recipient . '.';
                            $mailboxFolder = 'sent';
                        } catch (Throwable $sentException) {
                            error_log('Email was sent but could not be saved in the portfolio Sent folder: ' . $sentException->getMessage());
                            $message = 'Email sent to ' . $recipient . ', but it could not be recorded in Sent.';
                        }
                    } else {
                        error_log('PHP mail() returned false while sending an admin subscriber email.');
                        $error = 'The email could not be sent. Check the server mail configuration and logs.';
                    }
                } catch (Throwable $mailException) {
                    error_log('Could not send admin subscriber email: ' . $mailException->getMessage());
                    $error = 'The email could not be sent. Check the server mail configuration and logs.';
                }
            }
        }
    } elseif (isset($_POST['open_contact_message'])) {
        $mailboxFolder = is_string($_POST['contact_folder'] ?? null) &&
            in_array($_POST['contact_folder'], $validMailboxFolders, true)
                ? $_POST['contact_folder']
                : 'inbox';
        $selectedContactMessageId = filter_var($_POST['open_contact_message'] ?? null, FILTER_VALIDATE_INT);
        if ($selectedContactMessageId === false || $selectedContactMessageId < 1) {
            $selectedContactMessageId = 0;
            $error = 'Choose a valid inbox message.';
        } elseif (!$portfolioContactInboxError) {
            try {
                $readStatement = $conn->prepare('UPDATE portfolio_contact_messages SET is_read = 1 WHERE message_id = ?');
                if (!$readStatement) {
                    throw new RuntimeException('Could not prepare contact inbox read update: ' . $conn->error);
                }
                $readStatement->bind_param('i', $selectedContactMessageId);
                if (!$readStatement->execute()) {
                    throw new RuntimeException('Could not mark contact message read: ' . $readStatement->error);
                }
                if ($readStatement->affected_rows === 0) {
                    $existsStatement = $conn->prepare('SELECT message_id FROM portfolio_contact_messages WHERE message_id = ? LIMIT 1');
                    if (!$existsStatement) {
                        throw new RuntimeException('Could not verify the selected inbox message: ' . $conn->error);
                    }
                    $existsStatement->bind_param('i', $selectedContactMessageId);
                    $existsStatement->execute();
                    $existsStatement->store_result();
                    if ($existsStatement->num_rows === 0) {
                        $selectedContactMessageId = 0;
                        $error = 'That inbox message is no longer available.';
                    }
                    $existsStatement->close();
                }
                $readStatement->close();
            } catch (Throwable $inboxException) {
                error_log('Could not open portfolio contact message: ' . $inboxException->getMessage());
                $error = 'The inbox message could not be opened.';
            }
        }
    } elseif (isset($_POST['mailbox_action'])) {
        $mailboxFolder = is_string($_POST['contact_folder'] ?? null) &&
            in_array($_POST['contact_folder'], $validMailboxFolders, true)
                ? $_POST['contact_folder']
                : 'inbox';
        $targetFolder = is_string($_POST['mailbox_action']) ? $_POST['mailbox_action'] : '';
        $messageIds = $_POST['message_ids'] ?? [];
        if (!in_array($targetFolder, ['archive', 'spam', 'trash'], true) ||
            !is_array($messageIds) || count($messageIds) > 100) {
            $error = 'Choose valid messages and a mailbox action.';
        } elseif ($portfolioContactInboxError) {
            $error = 'The mailbox is unavailable; messages were not changed.';
        } else {
            try {
                $moveStatement = $conn->prepare('UPDATE portfolio_contact_messages SET folder = ? WHERE message_id = ?');
                if (!$moveStatement) {
                    throw new RuntimeException('Could not prepare mailbox action: ' . $conn->error);
                }
                $movedCount = 0;
                foreach ($messageIds as $messageIdValue) {
                    $messageId = filter_var($messageIdValue, FILTER_VALIDATE_INT);
                    if ($messageId === false || $messageId < 1) {
                        continue;
                    }
                    $moveStatement->bind_param('si', $targetFolder, $messageId);
                    if (!$moveStatement->execute()) {
                        throw new RuntimeException('Could not move mailbox message: ' . $moveStatement->error);
                    }
                    $movedCount += $moveStatement->affected_rows;
                }
                $moveStatement->close();
                $message = $movedCount > 0
                    ? $movedCount . ' message' . ($movedCount === 1 ? '' : 's') . ' moved to ' . ucfirst($targetFolder) . '.'
                    : 'Select at least one message to update.';
            } catch (Throwable $mailboxException) {
                error_log('Could not update portfolio mailbox messages: ' . $mailboxException->getMessage());
                $error = 'The selected messages could not be updated.';
            }
        }
    } elseif (isset($_POST['open_subscriber_compose'])) {
        $mailboxFolder = 'compose';
    } elseif (isset($_POST['delete_subscriber_draft'])) {
        $mailboxFolder = 'drafts';
        $draftToDelete = filter_var($_POST['draft_id'] ?? null, FILTER_VALIDATE_INT);
        if ($draftToDelete === false || $draftToDelete < 1) {
            $error = 'Choose a valid draft to delete.';
        } else {
            $deleteDraft = $conn->prepare("UPDATE portfolio_contact_messages SET folder = 'trash' WHERE message_id = ? AND folder = 'drafts'");
            if (!$deleteDraft) {
                $error = 'The draft could not be deleted.';
                error_log('Could not prepare draft deletion: ' . $conn->error);
            } else {
                $deleteDraft->bind_param('i', $draftToDelete);
                if ($deleteDraft->execute() && $deleteDraft->affected_rows > 0) {
                    $message = 'Draft moved to Trash.';
                } else {
                    $error = 'That draft could not be found.';
                }
                $deleteDraft->close();
            }
        }
    } elseif (isset($_POST['save_banner_duration'])) {
        $new_duration = filter_var($_POST['blog_banner_duration'] ?? null, FILTER_VALIDATE_INT);
        if ($new_duration === false || $new_duration < 2 || $new_duration > 20) {
            $error = 'Banner duration must be between 2 and 20 seconds.';
        } else {
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('blog_banner_duration', ?, 'number') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            $duration_value = (string) $new_duration;
            if ($stmt && $stmt->bind_param('s', $duration_value) && $stmt->execute()) {
                $banner_duration = $new_duration;
                $message = 'Banner rotation duration saved.';
            } else {
                $error = 'Could not save banner duration.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_sections'])) {
        $galleryItemsInput = json_decode((string) ($_POST['portfolio_gallery_items'] ?? ''), true);
        $blogItemsInput = json_decode((string) ($_POST['portfolio_blog_items'] ?? ''), true);
        $contactLinksInput = json_decode((string) ($_POST['portfolio_contact_links'] ?? ''), true);
        $galleryValid = is_array($galleryItemsInput) && count($galleryItemsInput) <= 30;
        $blogValid = is_array($blogItemsInput) && count($blogItemsInput) <= 30;
        $contactLinksValid = is_array($contactLinksInput) && count($contactLinksInput) <= 12;
        $galleryItems = [];
        $blogItems = [];
        $contactLinks = [];
        $galleryCategoryLabels = [];
        if ($galleryValid) {
            foreach ($galleryItemsInput as $item) {
                if (!is_array($item) || !isset($item['category'], $item['category_label'], $item['title'], $item['type'], $item['summary'], $item['detail'], $item['outcome'], $item['images'], $item['visible']) ||
                    !is_string($item['category']) || preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $item['category']) !== 1 ||
                    !is_string($item['category_label']) || trim($item['category_label']) === '' || strlen($item['category_label']) > 80 ||
                    !is_string($item['title']) || trim($item['title']) === '' || strlen($item['title']) > 160 ||
                    !is_string($item['type']) || trim($item['type']) === '' || strlen($item['type']) > 120 ||
                    !is_string($item['summary']) || trim($item['summary']) === '' || strlen($item['summary']) > 1500 ||
                    !is_string($item['detail']) || trim($item['detail']) === '' || strlen($item['detail']) > 3000 ||
                    !is_string($item['outcome']) || trim($item['outcome']) === '' || strlen($item['outcome']) > 1500 ||
                    !is_array($item['images']) || count($item['images']) < 1 || count($item['images']) > 12 ||
                    !is_bool($item['visible'])) {
                    $galleryValid = false;
                    break;
                }
                if (isset($galleryCategoryLabels[$item['category']]) && $galleryCategoryLabels[$item['category']] !== trim($item['category_label'])) {
                    $galleryValid = false;
                    break;
                }
                $galleryCategoryLabels[$item['category']] = trim($item['category_label']);
                $images = [];
                foreach ($item['images'] as $image) {
                    if (!is_array($image) || !isset($image['src'], $image['alt']) ||
                        !is_string($image['src']) || trim($image['src']) === '' || strlen($image['src']) > 1000 || !portfolioDashboardAssetValid($image['src']) ||
                        !is_string($image['alt']) || trim($image['alt']) === '' || strlen($image['alt']) > 200) {
                        $galleryValid = false;
                        break 2;
                    }
                    $images[] = ['src' => trim($image['src']), 'alt' => trim($image['alt'])];
                }
                $galleryItems[] = [
                    'category' => trim($item['category']),
                    'category_label' => trim($item['category_label']),
                    'title' => trim($item['title']),
                    'type' => trim($item['type']),
                    'summary' => trim($item['summary']),
                    'detail' => trim($item['detail']),
                    'outcome' => trim($item['outcome']),
                    'images' => $images,
                    'visible' => $item['visible']
                ];
            }
        }
        if ($blogValid) {
            foreach ($blogItemsInput as $item) {
                $parsedArticleDate = is_array($item) && is_string($item['date_iso'] ?? null)
                    ? DateTimeImmutable::createFromFormat('!Y-m-d', $item['date_iso'])
                    : false;
                if (!is_array($item) || !isset($item['category'], $item['date'], $item['date_iso'], $item['title'], $item['summary'], $item['body'], $item['media_type'], $item['image'], $item['image_alt'], $item['visible']) ||
                    !is_string($item['category']) || trim($item['category']) === '' || strlen($item['category']) > 80 ||
                    !is_string($item['date']) || trim($item['date']) === '' || strlen($item['date']) > 100 ||
                    !is_string($item['date_iso']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date_iso']) !== 1 ||
                    $parsedArticleDate === false || $parsedArticleDate->format('Y-m-d') !== $item['date_iso'] ||
                    !is_string($item['title']) || trim($item['title']) === '' || strlen($item['title']) > 200 ||
                    !is_string($item['summary']) || trim($item['summary']) === '' || strlen($item['summary']) > 1500 ||
                    !is_string($item['body']) || trim($item['body']) === '' || strlen($item['body']) > 12000 ||
                    !is_string($item['media_type']) || !in_array($item['media_type'], ['image', 'video'], true) ||
                    !is_string($item['image']) || trim($item['image']) === '' || strlen($item['image']) > 1000 || !portfolioDashboardAssetValid($item['image']) ||
                    !is_string($item['image_alt']) || trim($item['image_alt']) === '' || strlen($item['image_alt']) > 200 ||
                    !is_bool($item['visible'])) {
                    $blogValid = false;
                    break;
                }
                $blogItems[] = [
                    'category' => trim($item['category']),
                    'date' => trim($item['date']),
                    'date_iso' => $item['date_iso'],
                    'title' => trim($item['title']),
                    'summary' => trim($item['summary']),
                    'body' => trim($item['body']),
                    'media_type' => $item['media_type'],
                    'image' => trim($item['image']),
                    'image_alt' => trim($item['image_alt']),
                    'visible' => $item['visible']
                ];
            }
        }
        if ($contactLinksValid) {
            foreach ($contactLinksInput as $link) {
                if (!is_array($link) || !isset($link['label'], $link['url'], $link['icon'], $link['visible']) ||
                    !is_string($link['label']) || trim($link['label']) === '' || strlen($link['label']) > 80 ||
                    !is_string($link['url']) || strlen($link['url']) > 500 || !filter_var($link['url'], FILTER_VALIDATE_URL) ||
                    !in_array(strtolower((string) parse_url($link['url'], PHP_URL_SCHEME)), ['https'], true) ||
                    !is_string($link['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $link['icon']) !== 1 ||
                    !is_bool($link['visible'])) {
                    $contactLinksValid = false;
                    break;
                }
                $contactLinks[] = [
                    'label' => trim($link['label']),
                    'url' => trim($link['url']),
                    'icon' => $link['icon'],
                    'visible' => $link['visible']
                ];
            }
        }
        $sections = [
            'gallery' => [
                'visible' => isset($_POST['gallery_visible']),
                'title' => trim((string) ($_POST['gallery_title'] ?? '')),
                'description' => trim((string) ($_POST['gallery_description'] ?? '')),
                'items' => $galleryItems
            ],
            'blog' => [
                'visible' => isset($_POST['blog_visible']),
                'title' => trim((string) ($_POST['blog_title'] ?? '')),
                'description' => trim((string) ($_POST['blog_description'] ?? '')),
                'items' => $blogItems
            ],
            'contact' => [
                'visible' => isset($_POST['contact_visible']),
                'form_visible' => isset($_POST['contact_form_visible']),
                'social_visible' => isset($_POST['contact_social_visible']),
                'title' => trim((string) ($_POST['contact_title'] ?? '')),
                'description' => trim((string) ($_POST['contact_description'] ?? '')),
                'phone' => trim((string) ($_POST['contact_phone'] ?? '')),
                'info_note' => trim((string) ($_POST['contact_info_note'] ?? '')),
                'form_title' => trim((string) ($_POST['contact_form_title'] ?? '')),
                'social_title' => trim((string) ($_POST['contact_social_title'] ?? '')),
                'social_note' => trim((string) ($_POST['contact_social_note'] ?? '')),
                'social_links' => $contactLinks
            ],
            'search' => [
                'visible' => isset($_POST['search_visible']),
                'placeholder' => trim((string) ($_POST['search_placeholder'] ?? ''))
            ],
            'theme' => [
                'visible' => isset($_POST['theme_visible']),
                'default' => (string) ($_POST['theme_default'] ?? '')
            ]
        ];
        $sectionSaveTarget = (string) ($_POST['sections_save_target'] ?? 'all');
        $validSectionTargets = ['all', 'gallery', 'blog', 'contact', 'search', 'theme'];
        if (!in_array($sectionSaveTarget, $validSectionTargets, true)) {
            $error = 'Choose a valid portfolio section to save.';
        }
        $sectionKeys = ['gallery', 'blog', 'contact', 'search', 'theme'];
        if ($error === '' && $sectionSaveTarget !== 'all') {
            $savedSections = array_replace(
                $default_settings['portfolio_sections'],
                is_array($settings['portfolio_sections'] ?? null) ? $settings['portfolio_sections'] : []
            );
            foreach ($sectionKeys as $sectionKey) {
                if ($sectionKey !== $sectionSaveTarget) {
                    $sections[$sectionKey] = $savedSections[$sectionKey] ?? $default_settings['portfolio_sections'][$sectionKey];
                }
            }
            if ($sectionSaveTarget !== 'gallery') {
                $galleryValid = true;
            }
            if ($sectionSaveTarget !== 'blog') {
                $blogValid = true;
            }
            if ($sectionSaveTarget !== 'contact') {
                $contactLinksValid = true;
            }
        }
        $sectionTextFields = [];
        if ($sectionSaveTarget === 'all' || $sectionSaveTarget === 'gallery') {
            $sectionTextFields = array_merge($sectionTextFields, [$sections['gallery']['title'], $sections['gallery']['description']]);
        }
        if ($sectionSaveTarget === 'all' || $sectionSaveTarget === 'blog') {
            $sectionTextFields = array_merge($sectionTextFields, [$sections['blog']['title'], $sections['blog']['description']]);
        }
        if ($sectionSaveTarget === 'all' || $sectionSaveTarget === 'contact') {
            $sectionTextFields = array_merge($sectionTextFields, [
                $sections['contact']['title'], $sections['contact']['description'],
                $sections['contact']['phone'], $sections['contact']['info_note'],
                $sections['contact']['form_title'], $sections['contact']['social_title'],
                $sections['contact']['social_note']
            ]);
        }
        if ($sectionSaveTarget === 'all' || $sectionSaveTarget === 'search') {
            $sectionTextFields[] = $sections['search']['placeholder'];
        }
        $sectionHasErrors =
            (($sectionSaveTarget === 'all' || $sectionSaveTarget === 'gallery') && !$galleryValid) ||
            (($sectionSaveTarget === 'all' || $sectionSaveTarget === 'blog') && !$blogValid) ||
            (($sectionSaveTarget === 'all' || $sectionSaveTarget === 'contact') && !$contactLinksValid) ||
            in_array('', $sectionTextFields, true) ||
            count(array_filter($sectionTextFields, static fn($text): bool => strlen($text) > 1500)) > 0 ||
            (($sectionSaveTarget === 'all' || $sectionSaveTarget === 'search') && strlen($sections['search']['placeholder']) > 80) ||
            (($sectionSaveTarget === 'all' || $sectionSaveTarget === 'theme') && !in_array($sections['theme']['default'], ['dark', 'light', 'black', 'light-black', 'natural', 'light-pure'], true));
        if ($error === '' && $sectionHasErrors) {
            $error = 'Check the selected section fields. Gallery and Blog support up to 30 cards, 12 images per Gallery card, and Contact supports up to 12 social links.';
        } elseif ($error === '') {
            $encoded = json_encode($sections, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_sections', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_sections'] = $sections;
                $portfolioSectionsPanel = $sections;
                $savedSectionNames = [
                    'gallery' => 'Gallery',
                    'blog' => 'Blog',
                    'contact' => 'Contact',
                    'search' => 'Search Bar',
                    'theme' => 'Dark Mode',
                    'all' => 'Gallery, Blog, Contact, Search Bar, and Dark Mode'
                ];
                $message = $savedSectionNames[$sectionSaveTarget] . ' settings saved.';
            } else {
                $error = $encoded === false
                    ? 'Could not save sections because the content contains invalid text encoding.'
                    : 'Could not save portfolio section settings to the database.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_header'])) {
        $header = [
            'name' => trim((string) ($_POST['header_name'] ?? '')),
            'brand_name' => trim((string) ($_POST['header_brand_name'] ?? '')),
            'initials' => trim((string) ($_POST['header_initials'] ?? '')),
            'role' => trim((string) ($_POST['header_role'] ?? '')),
            'location' => trim((string) ($_POST['header_location'] ?? '')),
            'email' => trim((string) ($_POST['header_email'] ?? '')),
            'logo_image' => (string) ($settings['portfolio_header']['logo_image'] ?? ''),
            'feature_grids' => []
        ];
        $submittedFeatureGrids = json_decode((string) ($_POST['header_feature_grids_json'] ?? ''), true);
        $featureGridsValid = is_array($submittedFeatureGrids) && count($submittedFeatureGrids) <= 20;
        $featureGrids = [];
        $seenFeatureGridIds = [];
        $newHeaderGridUploads = [];
        $featureGridUploadError = '';
        $hasHeaderGridUpload = static function (string $field, int $index): bool {
            return isset($_FILES[$field]['error'][$index]) &&
                is_int($_FILES[$field]['error'][$index]) &&
                $_FILES[$field]['error'][$index] !== UPLOAD_ERR_NO_FILE;
        };
        $storeHeaderGridUpload = static function (string $field, int $index, string $type) use (&$newHeaderGridUploads): string {
            $upload = $_FILES[$field] ?? null;
            if (!is_array($upload) || !isset($upload['error'][$index]) || !is_int($upload['error'][$index])) {
                return '';
            }
            $errorCode = $upload['error'][$index];
            if ($errorCode === UPLOAD_ERR_NO_FILE) {
                return '';
            }
            $file = [
                'error' => $errorCode,
                'name' => $upload['name'][$index] ?? '',
                'tmp_name' => $upload['tmp_name'][$index] ?? '',
                'size' => $upload['size'][$index] ?? 0
            ];
            $rules = [
                'image' => [
                    'max_bytes' => 8 * 1024 * 1024,
                    'extensions' => ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp']
                ],
                'video' => [
                    'max_bytes' => 50 * 1024 * 1024,
                    'extensions' => ['mp4' => 'video/mp4', 'webm' => 'video/webm']
                ],
                'pdf' => [
                    'max_bytes' => 15 * 1024 * 1024,
                    'extensions' => ['pdf' => 'application/pdf']
                ]
            ];
            if (!isset($rules[$type]) || $errorCode !== UPLOAD_ERR_OK ||
                !is_string($file['name']) || !is_string($file['tmp_name']) ||
                !is_int($file['size']) || $file['size'] < 1 || $file['size'] > $rules[$type]['max_bytes'] ||
                !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('A header grid upload failed or exceeded its file size limit.');
            }
            $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!isset($rules[$type]['extensions'][$extension])) {
                throw new RuntimeException('Choose a file that matches the selected image, video, or PDF media type.');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!is_string($mime) || $mime !== $rules[$type]['extensions'][$extension] ||
                ($type === 'image' && @getimagesize($file['tmp_name']) === false)) {
                throw new RuntimeException('The uploaded header grid file content does not match its file type.');
            }
            $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio' . DIRECTORY_SEPARATOR . 'header-grids';
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Could not create the header grid upload directory.');
            }
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $fileName)) {
                throw new RuntimeException('Could not save an uploaded header grid file.');
            }
            $relativePath = 'uploads/portfolio/header-grids/' . $fileName;
            $newHeaderGridUploads[] = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio' . DIRECTORY_SEPARATOR . 'header-grids' . DIRECTORY_SEPARATOR . $fileName;
            return $relativePath;
        };
        if ($featureGridsValid) {
            foreach ($submittedFeatureGrids as $gridIndex => $grid) {
                if (!is_array($grid) ||
                    !is_int($gridIndex) || $gridIndex < 0 ||
                    !isset($grid['id'], $grid['title'], $grid['description'], $grid['media_type'], $grid['media_url'], $grid['poster_url'], $grid['nav_label'], $grid['parent'], $grid['images'], $grid['visible']) ||
                    !is_string($grid['id']) || preg_match('/^[a-z0-9][a-z0-9-]{0,59}$/', $grid['id']) !== 1 || isset($seenFeatureGridIds[$grid['id']]) ||
                    !is_string($grid['title']) || trim($grid['title']) === '' || strlen($grid['title']) > 160 ||
                    !is_string($grid['description']) || trim($grid['description']) === '' || strlen($grid['description']) > 2000 ||
                    !is_string($grid['media_type']) || !in_array($grid['media_type'], ['image', 'video', 'pdf'], true) ||
                    !is_string($grid['media_url']) || strlen($grid['media_url']) > 1000 ||
                    (trim($grid['media_url']) === '' && !$hasHeaderGridUpload('header_feature_grid_media_files', $gridIndex)) ||
                    (trim($grid['media_url']) !== '' && !portfolioDashboardAssetValid($grid['media_url'])) ||
                    !is_string($grid['poster_url']) || strlen($grid['poster_url']) > 1000 || ($grid['poster_url'] !== '' && !portfolioDashboardAssetValid($grid['poster_url'])) ||
                    !is_string($grid['nav_label']) || trim($grid['nav_label']) === '' || strlen($grid['nav_label']) > 80 ||
                    !is_string($grid['parent']) || strlen($grid['parent']) > 160 ||
                    ($grid['parent'] !== '' && count(array_filter(explode('>', $grid['parent']), static fn($part): bool => trim($part) !== '')) === 0) ||
                    count(array_filter(explode('>', $grid['parent']), static fn($part): bool => trim($part) !== '')) > 2 ||
                    !is_array($grid['images']) || count($grid['images']) > 8 ||
                    !is_bool($grid['visible'])) {
                    $featureGridsValid = false;
                    break;
                }
                $images = [];
                foreach ($grid['images'] as $image) {
                    if (!is_array($image) || !isset($image['src'], $image['caption']) ||
                        !is_string($image['src']) || trim($image['src']) === '' || strlen($image['src']) > 1000 || !portfolioDashboardAssetValid($image['src']) ||
                        !is_string($image['caption']) || trim($image['caption']) === '' || strlen($image['caption']) > 200) {
                        $featureGridsValid = false;
                        break 2;
                    }
                    $images[] = ['src' => trim($image['src']), 'caption' => trim($image['caption'])];
                }
                $seenFeatureGridIds[$grid['id']] = true;
                $featureGrids[] = [
                    'id' => $grid['id'],
                    'title' => trim($grid['title']),
                    'description' => trim($grid['description']),
                    'media_type' => $grid['media_type'],
                    'media_url' => trim($grid['media_url']),
                    'poster_url' => trim($grid['poster_url']),
                    'nav_label' => trim($grid['nav_label']),
                    'parent' => trim($grid['parent']),
                    'images' => $images,
                    'visible' => $grid['visible']
                ];
            }
        }
        if ($featureGridsValid) {
            foreach ($featureGrids as $gridIndex => &$grid) {
                try {
                    $uploadedMedia = $storeHeaderGridUpload('header_feature_grid_media_files', $gridIndex, $grid['media_type']);
                    $uploadedPoster = $storeHeaderGridUpload('header_feature_grid_poster_files', $gridIndex, 'image');
                    if ($uploadedMedia !== '') {
                        $grid['media_url'] = $uploadedMedia;
                    }
                    if ($uploadedPoster !== '') {
                        $grid['poster_url'] = $uploadedPoster;
                    }
                    if ($grid['media_url'] === '') {
                        throw new RuntimeException('Upload a main media file or enter its safe URL/path for every content grid.');
                    }
                } catch (RuntimeException $uploadException) {
                    $featureGridUploadError = $uploadException->getMessage();
                    $featureGridsValid = false;
                    break;
                }
            }
            unset($grid);
        }
        $header['feature_grids'] = $featureGrids;
        if ($header['name'] === '' || $header['brand_name'] === '' || $header['initials'] === '' || $header['role'] === '' || $header['location'] === '' || !filter_var($header['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a name, logo name, initials, role, location, and valid email address.';
        } elseif ($featureGridUploadError !== '') {
            $error = $featureGridUploadError;
        } elseif (!$featureGridsValid) {
            $error = 'Check each content grid. Use up to 20 grids and 8 captioned images per grid, valid media paths or HTTP(S) URLs, and at most one parent/sub-link level.';
        } elseif (isset($_POST['remove_logo_image'])) {
            $header['logo_image'] = '';
        } elseif (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['logo_image'];
            if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
                $error = 'Logo upload failed. Choose an image under 2 MB and try again.';
            } elseif ($upload['size'] <= 0 || $upload['size'] > 2 * 1024 * 1024) {
                $error = 'Logo image must be smaller than 2 MB.';
            } else {
                $imageInfo = @getimagesize($upload['tmp_name']);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($upload['tmp_name']);
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if ($imageInfo === false || !isset($allowedTypes[$mime])) {
                    $error = 'Logo must be a valid JPEG, PNG, or WebP image.';
                } else {
                    $logoDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio';
                    if (!is_dir($logoDirectory) && !mkdir($logoDirectory, 0755, true) && !is_dir($logoDirectory)) {
                        $error = 'Could not create the portfolio logo upload directory.';
                    } else {
                        $logoFile = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                        $logoPath = $logoDirectory . DIRECTORY_SEPARATOR . $logoFile;
                        if (!move_uploaded_file($upload['tmp_name'], $logoPath)) {
                            $error = 'Could not save the uploaded logo image.';
                        } else {
                            $header['logo_image'] = 'uploads/portfolio/' . $logoFile;
                        }
                    }
                }
            }
        }
        if ($error === '') {
            $encoded = json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_header', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_header'] = $header;
                $message = 'Portfolio header settings saved.';
            } else {
                $error = 'Could not save portfolio header settings.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
        if ($error !== '') {
            foreach ($newHeaderGridUploads as $uploadedPath) {
                if (is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }
            }
        }
    } elseif (isset($_POST['save_portfolio_social_posts'])) {
        $submittedPosts = json_decode((string) ($_POST['portfolio_social_posts'] ?? ''), true);
        $postsValid = is_array($submittedPosts);
        $socialPosts = [];
        $seenPostIds = [];
        if ($postsValid) {
            foreach ($submittedPosts as $post) {
                if (!is_array($post) || !isset($post['id'], $post['media_type'], $post['image'], $post['alt'], $post['title'], $post['description'], $post['visible']) ||
                    !is_string($post['id']) || preg_match('/^[a-z0-9][a-z0-9-]{0,39}$/', $post['id']) !== 1 ||
                    isset($seenPostIds[$post['id']]) ||
                    !is_string($post['media_type']) || !in_array($post['media_type'], ['image', 'video'], true) ||
                    !is_string($post['image']) || trim($post['image']) === '' || strlen($post['image']) > 1000 || !portfolioDashboardAssetValid($post['image']) ||
                    !is_string($post['alt']) || trim($post['alt']) === '' || strlen($post['alt']) > 200 ||
                    !is_string($post['title']) || trim($post['title']) === '' || strlen($post['title']) > 160 ||
                    !is_string($post['description']) || trim($post['description']) === '' ||
                    (isset($post['publisher_name']) && !is_string($post['publisher_name'])) ||
                    (isset($post['publisher_role']) && !is_string($post['publisher_role'])) ||
                    (isset($post['published_at']) && !is_string($post['published_at'])) ||
                    (isset($post['featured']) && !is_bool($post['featured'])) ||
                    !is_bool($post['visible'])) {
                    $postsValid = false;
                    break;
                }
                $publisherName = isset($post['publisher_name']) && trim($post['publisher_name']) !== '' ? trim($post['publisher_name']) : 'Shumi';
                $publisherRole = isset($post['publisher_role']) && trim($post['publisher_role']) !== '' ? trim($post['publisher_role']) : 'Web Developer';
                $publishedAtInput = isset($post['published_at']) && trim($post['published_at']) !== '' ? trim($post['published_at']) : date(DATE_ATOM);
                $publishedAtTimestamp = strtotime($publishedAtInput);
                if (strlen($publisherName) > 100 || strlen($publisherRole) > 100 || $publishedAtTimestamp === false) {
                    $postsValid = false;
                    break;
                }
                $seenPostIds[$post['id']] = true;
                $socialPosts[] = [
                    'id' => $post['id'],
                    'media_type' => $post['media_type'],
                    'image' => trim($post['image']),
                    'alt' => trim($post['alt']),
                    'title' => trim($post['title']),
                    'description' => trim($post['description']),
                    'publisher_name' => $publisherName,
                    'publisher_role' => $publisherRole,
                    'published_at' => date(DATE_ATOM, $publishedAtTimestamp),
                    'featured' => isset($post['featured']) && $post['featured'] === true,
                    'visible' => $post['visible']
                ];
            }
        }
        if (!$postsValid) {
            $error = 'Could not save posts. Check that every post has a unique lowercase ID, valid image or video URL/path, complete required fields, and a valid publish date.';
        } else {
            $encoded = json_encode($socialPosts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_social_posts', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_social_posts'] = $socialPosts;
                $message = 'Social and Post Widgets saved.';
            } else {
                $error = $encoded === false
                    ? 'Could not save posts because the content contains invalid text encoding. Re-enter the affected description and try again.'
                    : 'Could not save Social and Post Widgets to the database. Check the database connection and available storage, then try again.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_skills'])) {
        $submittedSkills = json_decode((string) ($_POST['skills_items_json'] ?? ''), true);
        $skillsValid = is_array($submittedSkills) && count($submittedSkills) <= 30;
        $skills = [];
        if ($skillsValid) {
            foreach ($submittedSkills as $skill) {
                if (!is_array($skill) || !isset($skill['icon'], $skill['title'], $skill['description'], $skill['details'], $skill['visible']) ||
                    !is_string($skill['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $skill['icon']) !== 1 ||
                    !is_string($skill['title']) || trim($skill['title']) === '' || strlen($skill['title']) > 120 ||
                    !is_string($skill['description']) || trim($skill['description']) === '' || strlen($skill['description']) > 1000 ||
                    !is_string($skill['details']) || trim($skill['details']) === '' || strlen($skill['details']) > 2000 ||
                    !is_bool($skill['visible'])) {
                    $skillsValid = false;
                    break;
                }
                $skills[] = [
                    'icon' => trim($skill['icon']),
                    'title' => trim($skill['title']),
                    'description' => trim($skill['description']),
                    'details' => trim($skill['details']),
                    'visible' => $skill['visible']
                ];
            }
        }
        $skillsContent = [
            'visible' => isset($_POST['skills_visible']),
            'title' => trim((string) ($_POST['skills_title'] ?? '')),
            'description' => trim((string) ($_POST['skills_description'] ?? '')),
            'items' => $skills
        ];
        if (!$skillsValid || $skillsContent['title'] === '' || strlen($skillsContent['title']) > 160 ||
            $skillsContent['description'] === '' || strlen($skillsContent['description']) > 500) {
            $error = 'Complete the Skills section heading and every card. You can save up to 30 cards.';
        } else {
            $encoded = json_encode($skillsContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_skills', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_skills'] = $skillsContent;
                $portfolioSkillsPanel = $skillsContent;
                $message = 'Portfolio Skills content saved.';
            } else {
                $error = $encoded === false
                    ? 'Could not save Skills because the content contains invalid text encoding.'
                    : 'Could not save Portfolio Skills content.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_about'])) {
        $about = [
            'header_label' => trim((string) ($_POST['about_header_label'] ?? '')),
            'header_title' => trim((string) ($_POST['about_header_title'] ?? '')),
            'image' => trim((string) ($settings['portfolio_about']['image'] ?? '')),
            'profile_kicker' => trim((string) ($_POST['about_profile_kicker'] ?? '')),
            'statement_title' => trim((string) ($_POST['about_statement_title'] ?? '')),
            'statement_accent' => trim((string) ($_POST['about_statement_accent'] ?? '')),
            'descriptions' => [
                trim((string) ($_POST['about_description_1'] ?? '')),
                trim((string) ($_POST['about_description_2'] ?? '')),
                trim((string) ($_POST['about_description_3'] ?? ''))
            ],
            'objectives_kicker' => trim((string) ($_POST['about_objectives_kicker'] ?? '')),
            'objectives_title' => trim((string) ($_POST['about_objectives_title'] ?? '')),
            'values' => [],
            'footer_text' => trim((string) ($_POST['about_footer_text'] ?? ''))
        ];
        foreach (['curiosity', 'craft', 'care', 'learning'] as $valueKey) {
            $about['values'][$valueKey] = [
                'label' => trim((string) ($_POST['about_value_' . $valueKey . '_label'] ?? '')),
                'description' => trim((string) ($_POST['about_value_' . $valueKey . '_description'] ?? ''))
            ];
        }
        $aboutTextFields = [
            $about['header_label'], $about['header_title'], $about['profile_kicker'],
            $about['statement_title'], $about['statement_accent'],
            $about['objectives_kicker'], $about['objectives_title'], $about['footer_text']
        ];
        foreach ($about['descriptions'] as $description) {
            $aboutTextFields[] = $description;
        }
        foreach ($about['values'] as $value) {
            $aboutTextFields[] = $value['label'];
            $aboutTextFields[] = $value['description'];
        }
        if (in_array('', $aboutTextFields, true) || count(array_filter($aboutTextFields, static fn($text): bool => strlen($text) > 1500)) > 0) {
            $error = 'Complete every About field and keep each entry under 1,500 characters.';
        } elseif (isset($_POST['remove_about_image'])) {
            $about['image'] = '';
        } elseif (isset($_FILES['about_image']) && $_FILES['about_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['about_image'];
            if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
                $error = 'About image upload failed. Choose an image under 2 MB and try again.';
            } elseif ($upload['size'] <= 0 || $upload['size'] > 2 * 1024 * 1024) {
                $error = 'About image must be smaller than 2 MB.';
            } else {
                $imageInfo = @getimagesize($upload['tmp_name']);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($upload['tmp_name']);
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if ($imageInfo === false || !isset($allowedTypes[$mime])) {
                    $error = 'About image must be a valid JPEG, PNG, or WebP image.';
                } else {
                    $imageDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolio';
                    if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true) && !is_dir($imageDirectory)) {
                        $error = 'Could not create the portfolio image upload directory.';
                    } else {
                        $imageFile = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                        if (!move_uploaded_file($upload['tmp_name'], $imageDirectory . DIRECTORY_SEPARATOR . $imageFile)) {
                            $error = 'Could not save the About image.';
                        } else {
                            $about['image'] = 'uploads/portfolio/' . $imageFile;
                        }
                    }
                }
            }
        }
        if ($error === '' && $about['image'] !== '' && !portfolioDashboardAssetValid($about['image'])) {
            $error = 'About image must be an HTTP(S) URL or a safe relative path.';
        }
        if ($error === '') {
            $encoded = json_encode($about, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_about', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_about'] = $about;
                $message = 'Portfolio About Me content saved.';
            } else {
                $error = 'Could not save Portfolio About Me content.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_journey'])) {
        $submittedItems = json_decode((string) ($_POST['journey_items_json'] ?? ''), true);
        $journeyValid = is_array($submittedItems) && count($submittedItems) <= 20;
        $journeyItems = [];
        if ($journeyValid) {
            foreach ($submittedItems as $item) {
                if (!is_array($item) || !isset($item['category'], $item['title'], $item['icon'], $item['date'], $item['summary'], $item['detail'], $item['features'], $item['outcome'], $item['attachments'], $item['visible']) ||
                    !is_string($item['category']) || !in_array($item['category'], ['educational', 'experience', 'talent'], true) ||
                    !is_string($item['title']) || trim($item['title']) === '' || strlen($item['title']) > 160 ||
                    !is_string($item['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $item['icon']) !== 1 ||
                    !is_string($item['date']) || trim($item['date']) === '' || strlen($item['date']) > 120 ||
                    !is_string($item['summary']) || trim($item['summary']) === '' || strlen($item['summary']) > 1500 ||
                    !is_string($item['detail']) || trim($item['detail']) === '' || strlen($item['detail']) > 3000 ||
                    !is_array($item['features']) || count($item['features']) > 12 ||
                    !is_string($item['outcome']) || trim($item['outcome']) === '' || strlen($item['outcome']) > 1500 ||
                    !is_array($item['attachments']) || count($item['attachments']) > 8 ||
                    !is_bool($item['visible'])) {
                    $journeyValid = false;
                    break;
                }
                $features = [];
                foreach ($item['features'] as $feature) {
                    if (!is_string($feature) || trim($feature) === '' || strlen($feature) > 120) {
                        $journeyValid = false;
                        break 2;
                    }
                    $features[] = trim($feature);
                }
                $attachments = [];
                foreach ($item['attachments'] as $attachment) {
                    if (!is_string($attachment) || strlen($attachment) > 500 || !portfolioDashboardAssetValid($attachment)) {
                        $journeyValid = false;
                        break 2;
                    }
                    $attachments[] = trim($attachment);
                }
                $journeyItems[] = [
                    'category' => $item['category'],
                    'title' => trim($item['title']),
                    'icon' => $item['icon'],
                    'date' => trim($item['date']),
                    'summary' => trim($item['summary']),
                    'detail' => trim($item['detail']),
                    'features' => $features,
                    'outcome' => trim($item['outcome']),
                    'attachments' => $attachments,
                    'visible' => $item['visible']
                ];
            }
        }
        $journey = [
            'eyebrow' => trim((string) ($_POST['journey_eyebrow'] ?? '')),
            'title' => trim((string) ($_POST['journey_title'] ?? '')),
            'description' => trim((string) ($_POST['journey_description'] ?? '')),
            'items' => $journeyItems
        ];
        if (!$journeyValid || in_array('', [$journey['eyebrow'], $journey['title'], $journey['description']], true) ||
            count(array_filter([$journey['eyebrow'], $journey['title'], $journey['description']], static fn($text): bool => strlen($text) > 500)) > 0) {
            $error = 'Complete the Journey heading fields and check each card. You can save up to 20 cards, 12 highlights, and 8 attachments per card.';
        } else {
            $encoded = json_encode($journey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_journey', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_journey'] = $journey;
                $portfolioJourneyPanel = $journey;
                $message = 'Portfolio Journey content saved.';
            } else {
                $error = 'Could not save Portfolio Journey content.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_faqs'])) {
        $submittedItems = json_decode((string) ($_POST['faq_items_json'] ?? ''), true);
        $faqValid = is_array($submittedItems) && count($submittedItems) <= 40;
        $faqItems = [];
        if ($faqValid) {
            foreach ($submittedItems as $item) {
                if (!is_array($item) || !isset($item['category'], $item['question'], $item['answer'], $item['visible']) ||
                    !is_string($item['category']) || !in_array($item['category'], ['about', 'experience', 'skills', 'working'], true) ||
                    !is_string($item['question']) || trim($item['question']) === '' || strlen($item['question']) > 300 ||
                    !is_string($item['answer']) || trim($item['answer']) === '' || strlen($item['answer']) > 3000 ||
                    !is_bool($item['visible'])) {
                    $faqValid = false;
                    break;
                }
                $faqItems[] = [
                    'category' => $item['category'],
                    'question' => trim($item['question']),
                    'answer' => trim($item['answer']),
                    'visible' => $item['visible']
                ];
            }
        }
        $faqCategories = [];
        foreach (array_keys($default_settings['portfolio_faqs']['categories']) as $categoryKey) {
            $faqCategories[$categoryKey] = trim((string) ($_POST['faq_category_' . $categoryKey] ?? ''));
        }
        $faqContent = [
            'title' => trim((string) ($_POST['faq_title'] ?? '')),
            'categories' => $faqCategories,
            'items' => $faqItems
        ];
        if (!$faqValid || $faqContent['title'] === '' || strlen($faqContent['title']) > 160 ||
            in_array('', $faqCategories, true) ||
            count(array_filter($faqCategories, static fn($label): bool => strlen($label) > 80)) > 0) {
            $error = 'Complete the FAQ title and category labels, and check each question. You can save up to 40 questions.';
        } else {
            $encoded = json_encode($faqContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_faqs', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_faqs'] = $faqContent;
                $portfolioFaqsPanel = $faqContent;
                $message = 'Portfolio FAQs saved.';
            } else {
                $error = 'Could not save Portfolio FAQs.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_projects'])) {
        $submittedProjects = json_decode((string) ($_POST['portfolio_projects_json'] ?? ''), true);
        $projectsValid = is_array($submittedProjects) &&
            isset($submittedProjects['projects'], $submittedProjects['goals']) &&
            is_array($submittedProjects['projects']) && count($submittedProjects['projects']) <= 24 &&
            is_array($submittedProjects['goals']) && count($submittedProjects['goals']) <= 3;
        $projects = [];
        $projectIds = [];
        $goalIds = [];
        if ($projectsValid) {
            foreach ($submittedProjects['projects'] as $project) {
                if (!is_array($project) ||
                    !isset($project['category'], $project['title'], $project['summary'], $project['image'], $project['image_alt'], $project['tags'], $project['contribution'], $project['outcome'], $project['price_birr'], $project['visible']) ||
                    !is_string($project['category']) || !in_array($project['category'], ['portfolio', 'platform', 'application'], true) ||
                    !is_string($project['title']) || trim($project['title']) === '' || strlen($project['title']) > 160 ||
                    !is_string($project['summary']) || trim($project['summary']) === '' || strlen($project['summary']) > 1500 ||
                    !is_string($project['image']) || trim($project['image']) === '' || strlen($project['image']) > 1000 || !portfolioDashboardAssetValid($project['image']) ||
                    !is_string($project['image_alt']) || trim($project['image_alt']) === '' || strlen($project['image_alt']) > 200 ||
                    !is_array($project['tags']) || count($project['tags']) > 12 ||
                    !is_string($project['contribution']) || trim($project['contribution']) === '' || strlen($project['contribution']) > 1500 ||
                    !is_string($project['outcome']) || trim($project['outcome']) === '' || strlen($project['outcome']) > 1500 ||
                    filter_var($project['price_birr'], FILTER_VALIDATE_INT) === false ||
                    (int) $project['price_birr'] < 0 || (int) $project['price_birr'] > 1000000 ||
                    !is_bool($project['visible'])) {
                    $projectsValid = false;
                    break;
                }
                $projectId = isset($project['id']) && is_string($project['id']) &&
                    preg_match('/\A[a-z0-9][a-z0-9-]{0,59}\z/', $project['id']) === 1
                    ? $project['id']
                    : portfolioProjectPaymentLegacyId($project['category'], $project['title']);
                if (isset($projectIds[$projectId])) {
                    $projectsValid = false;
                    break;
                }
                $projectIds[$projectId] = true;
                $tags = [];
                foreach ($project['tags'] as $tag) {
                    if (!is_string($tag) || trim($tag) === '' || strlen($tag) > 40) {
                        $projectsValid = false;
                        break 2;
                    }
                    $tags[] = trim($tag);
                }
                $projects[] = [
                    'id' => $projectId,
                    'category' => $project['category'],
                    'title' => trim($project['title']),
                    'summary' => trim($project['summary']),
                    'image' => trim($project['image']),
                    'image_alt' => trim($project['image_alt']),
                    'tags' => $tags,
                    'contribution' => trim($project['contribution']),
                    'outcome' => trim($project['outcome']),
                    'price_birr' => (int) $project['price_birr'],
                    'visible' => $project['visible']
                ];
            }
        }
        $goals = [];
        if ($projectsValid) {
            foreach ($submittedProjects['goals'] as $goal) {
                if (!is_array($goal) ||
                    !isset($goal['id'], $goal['title'], $goal['icon'], $goal['description'], $goal['detail'], $goal['visible']) ||
                    !is_string($goal['id']) || !in_array($goal['id'], ['mission', 'vision', 'core-value'], true) || isset($goalIds[$goal['id']]) ||
                    !is_string($goal['title']) || trim($goal['title']) === '' || strlen($goal['title']) > 100 ||
                    !is_string($goal['icon']) || preg_match('/^fa-[a-z0-9-]{1,60}$/', $goal['icon']) !== 1 ||
                    !is_string($goal['description']) || trim($goal['description']) === '' || strlen($goal['description']) > 1500 ||
                    !is_string($goal['detail']) || trim($goal['detail']) === '' || strlen($goal['detail']) > 3000 ||
                    !is_bool($goal['visible'])) {
                    $projectsValid = false;
                    break;
                }
                $goalIds[$goal['id']] = true;
                $goals[] = [
                    'id' => $goal['id'],
                    'title' => trim($goal['title']),
                    'icon' => $goal['icon'],
                    'description' => trim($goal['description']),
                    'detail' => trim($goal['detail']),
                    'visible' => $goal['visible']
                ];
            }
        }
        if (!$projectsValid) {
            $error = 'Could not save Projects. Check each project field, image URL/path, category, tags, Birr price, and Strategy card. You can save up to 24 projects and 3 Strategy cards.';
        } else {
            $projectContent = ['projects' => $projects, 'goals' => $goals];
            $encoded = json_encode($projectContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $encoded === false ? false : $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_projects', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_projects'] = $projectContent;
                $portfolioProjectsPanel = $projectContent;
                $message = 'Portfolio Projects and Strategy content saved.';
            } else {
                $error = $encoded === false
                    ? 'Could not save Projects because the content contains invalid text encoding.'
                    : 'Could not save Projects to the database. Check the database connection and available storage, then try again.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_portfolio_home'])) {
        $visibility = [];
        foreach (['headline', 'roles', 'intro', 'portrait', 'background', 'hire_button', 'cv_button', 'certificates_button', 'subscription_button'] as $visibilityField) {
            $visibility[$visibilityField] = isset($_POST['home_show_' . $visibilityField]);
        }
        $submittedStats = json_decode((string) ($_POST['home_stats'] ?? ''), true);
        $statsValid = is_array($submittedStats) && count($submittedStats) <= 8;
        $homeStats = [];
        if ($statsValid) {
            foreach ($submittedStats as $stat) {
                if (!is_array($stat) || !isset($stat['label'], $stat['value'], $stat['suffix']) ||
                    !is_string($stat['label']) || trim($stat['label']) === '' || strlen($stat['label']) > 80 ||
                    filter_var($stat['value'], FILTER_VALIDATE_INT) === false || (int) $stat['value'] < 0 || (int) $stat['value'] > 999999 ||
                    !is_string($stat['suffix']) || strlen($stat['suffix']) > 12) {
                    $statsValid = false;
                    break;
                }
                $homeStats[] = [
                    'label' => trim($stat['label']),
                    'value' => (int) $stat['value'],
                    'suffix' => $stat['suffix'],
                    'visible' => !empty($stat['visible'])
                ];
            }
        }
        $home = [
            'headline' => trim((string) ($_POST['home_headline'] ?? '')),
            'roles' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($_POST['home_roles'] ?? '')) ?: []), static fn($role): bool => $role !== '')),
            'intro' => trim((string) ($_POST['home_intro'] ?? '')),
            'portrait_url' => trim((string) ($_POST['home_portrait_url'] ?? '')),
            'background_url' => trim((string) ($_POST['home_background_url'] ?? '')),
            'portrait_bio_name' => trim((string) ($_POST['home_portrait_bio_name'] ?? '')),
            'portrait_bio_kicker' => trim((string) ($_POST['home_portrait_bio_kicker'] ?? '')),
            'portrait_bio' => trim((string) ($_POST['home_portrait_bio'] ?? '')),
            'portrait_skills' => trim((string) ($_POST['home_portrait_skills'] ?? '')),
            'hire_label' => trim((string) ($_POST['home_hire_label'] ?? '')),
            'cv_url' => trim((string) ($_POST['home_cv_url'] ?? '')),
            'cv_label' => trim((string) ($_POST['home_cv_label'] ?? '')),
            'hire_url' => trim((string) ($_POST['home_hire_url'] ?? '')),
            'certificates_label' => trim((string) ($_POST['home_certificates_label'] ?? '')),
            'certificates_url' => trim((string) ($_POST['home_certificates_url'] ?? '')),
            'subscription_label' => trim((string) ($_POST['home_subscription_label'] ?? '')),
            'subscription_url' => (string) ($settings['portfolio_home']['subscription_url'] ?? '#contact'),
            'visibility' => $visibility,
            'stats' => $homeStats,
            'menu_links' => json_decode((string) ($_POST['home_menu_links'] ?? ''), true)
        ];
        if (($visibility['headline'] && $home['headline'] === '') ||
            ($visibility['roles'] && $home['roles'] === []) ||
            count($home['roles']) > 8 ||
            count(array_filter($home['roles'], static fn($role): bool => strlen($role) > 120)) > 0 ||
            ($visibility['intro'] && $home['intro'] === '') ||
            ($visibility['portrait'] && in_array('', [$home['portrait_bio_name'], $home['portrait_bio_kicker'], $home['portrait_bio'], $home['portrait_skills']], true)) ||
            strlen($home['portrait_bio_name']) > 100 || strlen($home['portrait_bio_kicker']) > 120 || strlen($home['portrait_skills']) > 300 ||
            (($visibility['hire_button']) && $home['hire_label'] === '') ||
            (($visibility['cv_button']) && $home['cv_label'] === '') ||
            (($visibility['certificates_button']) && $home['certificates_label'] === '') ||
            (($visibility['subscription_button']) && $home['subscription_label'] === '') ||
            count(array_filter([$home['hire_label'], $home['cv_label'], $home['certificates_label'], $home['subscription_label']], static fn($text): bool => strlen($text) > 80)) > 0 ||
            count(array_filter([$home['headline'], $home['intro'], $home['portrait_bio']], static fn($text): bool => strlen($text) > 1500)) > 0 ||
            count(array_filter([$home['portrait_url'], $home['background_url'], $home['cv_url'], $home['hire_url'], $home['certificates_url']], static fn($url): bool => strlen($url) > 500)) > 0) {
            $error = 'Complete the visible hero content and biography fields, and use no more than 8 role labels.';
        } elseif (($visibility['portrait'] && !portfolioDashboardAssetValid($home['portrait_url'])) ||
            ($home['background_url'] !== '' && !portfolioDashboardAssetValid($home['background_url'])) || !portfolioDashboardAssetValid($home['cv_url']) ||
            !portfolioDashboardLinkValid($home['hire_url']) || !portfolioDashboardLinkValid($home['certificates_url'])) {
            $error = 'Portrait, CV, and button links must use an HTTP(S) URL, a safe relative path, or a valid mailto link.';
        } elseif (!$statsValid) {
            $error = 'Check the stats: use up to 8 entries with a label, a whole number from 0 to 999999, and a suffix under 12 characters.';
        } elseif (!is_array($home['menu_links']) || !portfolioDashboardMenuIsValid($home['menu_links'])) {
            $error = 'Check navigation items: each needs a label and valid link; dropdowns may contain up to 10 sublinks.';
        } else {
            $encoded = json_encode($home, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES ('portfolio_home', ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            if ($stmt && $stmt->bind_param('s', $encoded) && $stmt->execute()) {
                $settings['portfolio_home'] = $home;
                $message = 'Portfolio home page settings saved.';
            } else {
                $error = 'Could not save portfolio home page settings.';
            }
            if ($stmt) {
                $stmt->close();
            }
        }
    } elseif (isset($_POST['save_blog_json'])) {
        $setting_key = (string) ($_POST['setting_key'] ?? '');
        $json_text = (string) ($_POST['setting_json'] ?? '');
        if (!array_key_exists($setting_key, $setting_labels)) {
            $error = 'Unknown blog setting.';
        } else {
            $decoded = json_decode($json_text, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $error = 'Please provide valid JSON containing an object or array.';
            } elseif ($setting_key === 'portfolio_header' && (
                !isset($decoded['name'], $decoded['initials'], $decoded['role'], $decoded['location'], $decoded['email'])
                || !is_string($decoded['name']) || trim($decoded['name']) === ''
                || !is_string($decoded['initials']) || trim($decoded['initials']) === ''
                || !is_string($decoded['role']) || trim($decoded['role']) === ''
                || !is_string($decoded['location']) || trim($decoded['location']) === ''
                || !is_string($decoded['email']) || !filter_var($decoded['email'], FILTER_VALIDATE_EMAIL)
            )) {
                $error = 'Header settings need a name, initials, role, location, and valid email address.';
            } elseif ($setting_key === 'portfolio_home' && (
                !isset($decoded['headline'], $decoded['roles'], $decoded['intro'], $decoded['portrait_url'], $decoded['cv_url'])
                || !is_string($decoded['headline']) || trim($decoded['headline']) === ''
                || !is_array($decoded['roles']) || $decoded['roles'] === []
                || count($decoded['roles']) > 8
                || count(array_filter($decoded['roles'], static fn($role): bool => is_string($role) && trim($role) !== '')) !== count($decoded['roles'])
                || !is_string($decoded['intro'])
                || !portfolioDashboardAssetValid($decoded['portrait_url'])
                || !portfolioDashboardAssetValid($decoded['cv_url'])
            )) {
                $error = 'Home page settings need a headline, introduction, 1–8 role labels, and valid portrait/CV URLs or relative paths.';
            } else {
                if (in_array($setting_key, ['blog_feature_update', 'blog_insurance_news'], true)) {
                    $newPostsToNotify = portfolioFindNewBlogPosts($setting_key, $settings[$setting_key] ?? [], $decoded);
                }
                if ($setting_key === 'portfolio_header' && !array_key_exists('feature_grids', $decoded)) {
                    $decoded['feature_grids'] = $settings['portfolio_header']['feature_grids'] ?? [];
                }
                $encoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'json') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
                if ($stmt && $stmt->bind_param('ss', $setting_key, $encoded) && $stmt->execute()) {
                    $settings[$setting_key] = $decoded;
                    $message = $setting_labels[$setting_key][0] . ' saved successfully.';
                    if ($newPostsToNotify !== []) {
                        try {
                            $notificationResult = portfolioNotifySubscribers(
                                $conn,
                                (string) ($settings['portfolio_header']['email'] ?? $default_settings['portfolio_header']['email']),
                                (string) ($settings['portfolio_header']['name'] ?? $default_settings['portfolio_header']['name']),
                                $newPostsToNotify
                            );
                            if ($notificationResult['failed'] > 0) {
                                $error = 'The new post was saved, but PHP mail() could not accept ' . $notificationResult['failed'] . ' notification email(s). Check the server mail configuration.';
                            } elseif ($notificationResult['sent'] > 0) {
                                $message .= ' PHP mail() accepted ' . $notificationResult['sent'] . ' new-post notification email(s).';
                            } else {
                                $message .= ' There are no active subscribers to notify.';
                            }
                        } catch (mysqli_sql_exception | RuntimeException $exception) {
                            error_log('Could not notify portfolio subscribers about a new blog post: ' . $exception->getMessage());
                            $error = 'The new post was saved, but subscriber notifications could not be prepared. Check the server logs and mail configuration.';
                        }
                    }
                } else {
                    $error = 'Could not save this blog setting.';
                }
                if ($stmt) {
                    $stmt->close();
                }
            }
        }
    }
}

if (!$portfolioContactInboxError) {
    try {
        $inboxResult = $conn->query(
            'SELECT message_id, sender_name, sender_email, recipient_email, cc_emails, subject, message, is_read, submitted_at, folder
             FROM portfolio_contact_messages ORDER BY submitted_at DESC, message_id DESC'
        );
        if (!$inboxResult) {
            throw new RuntimeException('Could not load portfolio contact inbox: ' . $conn->error);
        }
        while ($inboxMessage = $inboxResult->fetch_assoc()) {
            $portfolioContactMessages[] = $inboxMessage;
        }
        $inboxResult->free();
        $unreadContactMessageCount = count(array_filter($portfolioContactMessages, static function (array $inboxMessage): bool {
            return ($inboxMessage['folder'] ?? 'inbox') === 'inbox' && empty($inboxMessage['is_read']);
        }));
        if ($selectedContactMessageId > 0) {
            $selectedContactMessageFound = false;
            foreach ($portfolioContactMessages as $inboxMessage) {
                if ((int) $inboxMessage['message_id'] === $selectedContactMessageId) {
                    $selectedContactMessageFound = true;
                    break;
                }
            }
            if (!$selectedContactMessageFound) {
                $selectedContactMessageId = 0;
            }
        }
    } catch (mysqli_sql_exception | RuntimeException $exception) {
        $portfolioContactInboxError = true;
        error_log('Could not load portfolio contact inbox in admin dashboard: ' . $exception->getMessage());
    }
}

try {
    $portfolioAdvertisements = portfolioLoadAdvertisements($conn, true);
} catch (Throwable $exception) {
    $portfolioAdvertisingLoadError = true;
    error_log('Could not refresh portfolio advertisements in admin dashboard: ' . $exception->getMessage());
}

function blogDashboardJson($value) {
    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
function blogDashboardEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function portfolioDashboardAssetValid($value) {
    if (!is_string($value)) {
        return false;
    }
    $value = trim($value);
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
    return preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*$~D', $value) === 1;
}
function portfolioDashboardLinkValid($value) {
    return portfolioDashboardAssetValid($value)
        || (is_string($value) && preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]*(?:#[A-Za-z][A-Za-z0-9_-]*)?$~D', trim($value)) === 1)
        || (is_string($value) && preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/D', trim($value)) === 1)
        || (is_string($value) && preg_match('/^mailto:[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?:\?[A-Za-z0-9._~%&=+-]*)?$/iD', trim($value)) === 1);
}
function portfolioDashboardMenuIsValid(array $items, int $depth = 0): bool {
    if ($items === [] || count($items) > ($depth === 0 ? 12 : 10)) {
        return false;
    }
    foreach ($items as $item) {
        if (!is_array($item) || !isset($item['label'], $item['url']) || !is_string($item['label']) || trim($item['label']) === '' || strlen($item['label']) > 80 || !is_string($item['url']) || strlen($item['url']) > 500 || (isset($item['filter']) && !in_array($item['filter'], ['all', 'design', 'development', 'strategy'], true))) {
            return false;
        }
        $url = trim($item['url']);
        $validUrl = $url === '' && $depth === 0 && !empty($item['children'])
            || preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) === 1
            || portfolioDashboardAssetValid($url)
            || preg_match('/^(mailto|tel):[^\s]+$/i', $url) === 1;
        if (!$validUrl) {
            return false;
        }
        $children = $item['children'] ?? [];
        if (!is_array($children) || ($depth > 0 && $children !== []) || ($children !== [] && !portfolioDashboardMenuIsValid($children, $depth + 1))) {
            return false;
        }
    }
    return true;
}
$portfolioHeaderPanel = array_replace($default_settings['portfolio_header'], $settings['portfolio_header']);
$portfolioHomePanel = array_replace($default_settings['portfolio_home'], $settings['portfolio_home']);
$portfolioSocialPostsPanel = [];
foreach ($settings['portfolio_social_posts'] as $socialPost) {
    if (!is_array($socialPost) || !isset($socialPost['id'], $socialPost['media_type'], $socialPost['image'], $socialPost['alt'], $socialPost['title'], $socialPost['description']) ||
        !is_string($socialPost['id']) || !is_string($socialPost['media_type']) || !is_string($socialPost['image']) ||
        !is_string($socialPost['alt']) || !is_string($socialPost['title']) || !is_string($socialPost['description'])) {
        continue;
    }
    $portfolioSocialPostsPanel[] = [
        'id' => $socialPost['id'],
        'media_type' => $socialPost['media_type'],
        'image' => $socialPost['image'],
        'alt' => $socialPost['alt'],
        'title' => $socialPost['title'],
        'description' => $socialPost['description'],
        'publisher_name' => isset($socialPost['publisher_name']) && is_string($socialPost['publisher_name']) && trim($socialPost['publisher_name']) !== '' ? trim($socialPost['publisher_name']) : 'Shumi',
        'publisher_role' => isset($socialPost['publisher_role']) && is_string($socialPost['publisher_role']) && trim($socialPost['publisher_role']) !== '' ? trim($socialPost['publisher_role']) : 'Web Developer',
        'published_at' => isset($socialPost['published_at']) && is_string($socialPost['published_at']) && strtotime($socialPost['published_at']) !== false
            ? date(DATE_ATOM, strtotime($socialPost['published_at']))
            : date(DATE_ATOM),
        'featured' => isset($socialPost['featured']) && $socialPost['featured'] === true,
        'visible' => !isset($socialPost['visible']) || $socialPost['visible'] === true
    ];
}
if (($portfolioHomePanel['headline'] ?? '') === "Hi, I'm") {
    $portfolioHomePanel['headline'] = "Hi, I'm Shumi.";
}
$portfolioHomePanel['roles'] = is_array($portfolioHomePanel['roles'])
    ? array_values(array_filter($portfolioHomePanel['roles'], static fn($role): bool => is_string($role) && trim($role) !== ''))
    : $default_settings['portfolio_home']['roles'];
$portfolioHomePanel['menu_links'] = is_array($portfolioHomePanel['menu_links']) && portfolioDashboardMenuIsValid($portfolioHomePanel['menu_links'])
    ? array_values($portfolioHomePanel['menu_links'])
    : $default_settings['portfolio_home']['menu_links'];
$savedHomeVisibility = is_array($portfolioHomePanel['visibility'] ?? null) ? $portfolioHomePanel['visibility'] : [];
$portfolioHomePanel['visibility'] = $default_settings['portfolio_home']['visibility'];
foreach ($portfolioHomePanel['visibility'] as $key => $defaultVisible) {
    if (isset($savedHomeVisibility[$key]) && is_bool($savedHomeVisibility[$key])) {
        $portfolioHomePanel['visibility'][$key] = $savedHomeVisibility[$key];
    }
}
foreach (['portrait_bio_name', 'portrait_bio_kicker', 'portrait_bio', 'portrait_skills', 'hire_label', 'cv_label', 'hire_url', 'certificates_label', 'certificates_url', 'subscription_label', 'subscription_url'] as $homeField) {
    if (!isset($portfolioHomePanel[$homeField]) || !is_string($portfolioHomePanel[$homeField]) || trim($portfolioHomePanel[$homeField]) === '') {
        $portfolioHomePanel[$homeField] = $default_settings['portfolio_home'][$homeField];
    }
}
$homeStatsValid = is_array($portfolioHomePanel['stats'] ?? null) && count($portfolioHomePanel['stats']) <= 8;
$normalizedHomeStats = [];
if ($homeStatsValid) {
    foreach ($portfolioHomePanel['stats'] as $stat) {
        if (!is_array($stat) || !isset($stat['label'], $stat['value'], $stat['suffix']) ||
            !is_string($stat['label']) || trim($stat['label']) === '' ||
            filter_var($stat['value'], FILTER_VALIDATE_INT) === false || (int) $stat['value'] < 0 || (int) $stat['value'] > 999999 ||
            !is_string($stat['suffix']) || strlen($stat['suffix']) > 12) {
            $homeStatsValid = false;
            break;
        }
        $normalizedHomeStats[] = [
            'label' => trim($stat['label']),
            'value' => (int) $stat['value'],
            'suffix' => $stat['suffix'],
            'visible' => !empty($stat['visible'])
        ];
    }
}
$portfolioHomePanel['stats'] = $homeStatsValid ? $normalizedHomeStats : $default_settings['portfolio_home']['stats'];
foreach ($portfolioHomePanel['stats'] as &$stat) {
    if (in_array(strtolower(trim($stat['label'])), ['subscriber number', 'subscribers'], true)) {
        $stat['value'] = $portfolioSubscriberCount;
    }
}
unset($stat);
$portfolioSectionsPanel = is_array($settings['portfolio_sections'] ?? null)
    ? array_replace($default_settings['portfolio_sections'], $settings['portfolio_sections'])
    : $default_settings['portfolio_sections'];
foreach (['gallery', 'blog', 'contact', 'search', 'theme'] as $sectionKey) {
    if (!isset($portfolioSectionsPanel[$sectionKey]) || !is_array($portfolioSectionsPanel[$sectionKey])) {
        $portfolioSectionsPanel[$sectionKey] = $default_settings['portfolio_sections'][$sectionKey];
    } else {
        $portfolioSectionsPanel[$sectionKey] = array_replace($default_settings['portfolio_sections'][$sectionKey], $portfolioSectionsPanel[$sectionKey]);
    }
}
$portfolioSectionsPanel['gallery']['items'] = is_array($portfolioSectionsPanel['gallery']['items'] ?? null)
    ? array_slice($portfolioSectionsPanel['gallery']['items'], 0, 30)
    : $default_settings['portfolio_sections']['gallery']['items'];
$portfolioSectionsPanel['blog']['items'] = is_array($portfolioSectionsPanel['blog']['items'] ?? null)
    ? array_slice($portfolioSectionsPanel['blog']['items'], 0, 30)
    : $default_settings['portfolio_sections']['blog']['items'];
$portfolioSectionsPanel['contact']['social_links'] = is_array($portfolioSectionsPanel['contact']['social_links'] ?? null)
    ? array_slice($portfolioSectionsPanel['contact']['social_links'], 0, 12)
    : $default_settings['portfolio_sections']['contact']['social_links'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Content Dashboard | Admin</title>
    <style>
        :root { font-family:"Source Sans 3","Segoe UI",Arial,sans-serif; color:#343a40; background:#f4f6f9; font-synthesis:none; }
        * { box-sizing:border-box; }
        html { scroll-behavior:smooth; scroll-padding-top:72px; }
        body { min-width:320px; margin:0; background:#f4f6f9; font-size:15px; line-height:1.5; }
        a { color:#007bff; }
        .app { min-height:100vh; }
        .sidebar { position:fixed; z-index:20; inset:0 auto 0 0; width:250px; overflow-y:auto; color:#c2c7d0; background:#343a40; transition:transform .2s ease; }
        .brand { height:58px; display:flex; align-items:center; gap:10px; padding:0 16px; border-bottom:1px solid #4b545c; color:rgba(255,255,255,.85); text-decoration:none; font-size:19px; font-weight:300; }
        .brand img { width:32px; height:32px; object-fit:contain; border-radius:50%; background:#fff; }
        .brand strong { font-weight:700; }
        .user-panel { display:flex; align-items:center; gap:11px; margin:14px 12px 10px; padding:4px 4px 14px; border-bottom:1px solid #4f5962; }
        .user-avatar { width:34px; height:34px; display:grid; place-items:center; border-radius:50%; color:#fff; background:#6c757d; font-weight:700; }
        .user-name { color:#c2c7d0; text-decoration:none; font-size:14px; }
        .nav-title { padding:12px 18px 6px; color:#8d949b; font-size:11px; text-transform:uppercase; letter-spacing:.08em; }
        .side-nav { padding:0 8px 16px; }
        .side-nav a { display:flex; align-items:center; gap:10px; min-height:39px; margin:2px 0; padding:8px 11px; border-radius:4px; color:#c2c7d0; text-decoration:none; font-size:14px; }
        .side-nav a:hover { color:#fff; background:#494e53; }
        .side-nav a.active { color:#fff; background:#007bff; box-shadow:0 2px 4px rgb(0 0 0 / 18%); }
        .nav-icon { width:18px; color:#adb5bd; text-align:center; font-style:normal; }
        .side-nav a.active .nav-icon { color:#fff; }
        .side-nav-dropdown { margin:2px 0; }
        .side-nav-dropdown>summary { min-height:39px; display:flex; align-items:center; gap:10px; padding:8px 11px; border-radius:4px; color:#c2c7d0; font-size:14px; cursor:pointer; list-style:none; }
        .side-nav-dropdown>summary::-webkit-details-marker { display:none; }
        .side-nav-dropdown>summary:hover,.side-nav-dropdown[open]>summary { color:#fff; background:#494e53; }
        .side-nav-dropdown>summary:focus-visible,.side-nav-nested>summary:focus-visible { outline:2px solid #80bdff; outline-offset:2px; }
        .side-nav-dropdown>summary .nav-chevron { margin-left:auto; color:#adb5bd; transition:transform .2s ease; }
        .side-nav-dropdown[open]>summary .nav-chevron { transform:rotate(180deg); }
        .side-nav-submenu { padding:2px 0 5px 28px; }
        .side-nav-dropdown[open]>.side-nav-submenu,.side-nav-nested[open]>.side-nav-submenu { animation:sideNavReveal .22s ease-out both; }
        .side-nav-submenu a { min-height:35px; margin:1px 0; padding:7px 10px; font-size:13px; }
        .side-nav-submenu a[data-mailbox-link] { justify-content:space-between; }
        .side-nav-mailbox-badge { min-width:21px; padding:1px 7px; border-radius:999px; color:#fff; background:#dc3545; font-size:11px; font-weight:750; line-height:1.55; text-align:center; box-shadow:0 0 0 2px rgb(255 255 255 / 10%); }
        .side-nav-mailbox-badge[hidden] { display:none; }
        .side-nav-mailbox-badge.has-new-mail { animation:mailboxBadgePulse .65s ease-out; }
        .mailbox-live-status { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        @keyframes mailboxBadgePulse { 50% { transform:scale(1.25); box-shadow:0 0 0 5px rgb(220 53 69 / 18%); } }
        @media(prefers-reduced-motion:reduce) { .side-nav-mailbox-badge.has-new-mail { animation:none; } }
        .side-nav-nested { margin:2px 0; }
        .side-nav-nested>summary { min-height:35px; display:flex; align-items:center; gap:10px; padding:7px 10px; border-radius:4px; color:#c2c7d0; font-size:13px; cursor:pointer; list-style:none; }
        .side-nav-nested>summary::-webkit-details-marker { display:none; }
        .side-nav-nested>summary:hover,.side-nav-nested[open]>summary { color:#fff; background:#494e53; }
        .side-nav-nested>summary .nav-chevron { margin-left:auto; transition:transform .2s ease; }
        .side-nav-nested[open]>summary .nav-chevron { transform:rotate(180deg); }
        .side-nav-submenu .side-nav-submenu { padding-left:15px; }
        .side-nav-submenu a { transition:color .18s ease,background-color .18s ease,transform .18s ease; }
        .side-nav-submenu a:hover { transform:translateX(3px); }
        #website-theme-dropdown>.side-nav-submenu a::before { width:8px; height:8px; flex:0 0 auto; border:1px solid currentColor; border-radius:50%; content:""; }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="dark"]::before { background:#1e293b; }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="light"]::before { background:#f0fff0; }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="black"]::before { background:#000; }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="light-black"]::before { background:linear-gradient(135deg,#fff 50%,#000 50%); }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="natural"]::before { background:#d8c6a5; }
        #website-theme-dropdown>.side-nav-submenu a[data-admin-theme-preset="light-pure"]::before { background:#fff; }
        .side-nav-submenu a.active,.side-nav-submenu a[aria-current="true"] { color:#fff; background:#007bff; box-shadow:0 2px 7px rgb(0 123 255 / 24%); }
        .portfolio-card:target { animation:editorTargetReveal .45s ease-out; scroll-margin-top:78px; }
        @keyframes sideNavReveal { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
        @keyframes editorTargetReveal { from { box-shadow:0 0 0 0 rgb(0 123 255 / 35%); } to { box-shadow:0 1px 3px rgb(0 0 0 / 12%); } }
        .main-area { min-height:100vh; margin-left:250px; transition:margin-left .2s ease; }
        .navbar { height:58px; display:flex; align-items:center; justify-content:space-between; padding:0 18px; border-bottom:1px solid #dee2e6; background:#fff; box-shadow:0 1px 2px rgb(0 0 0 / 5%); }
        .nav-left,.nav-right { display:flex; align-items:center; gap:18px; }
        .menu-toggle { width:36px; height:36px; border:0; border-radius:4px; color:#6c757d; background:transparent; font-size:21px; cursor:pointer; }
        .menu-toggle:hover { background:#f1f3f5; }
        .nav-link { color:#6c757d; text-decoration:none; }
        .nav-link:hover { color:#343a40; }
        .nav-user { font-size:14px; }
        .content-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:19px 24px 12px; }
        h1 { margin:0; color:#343a40; font-size:27px; font-weight:400; }
        .breadcrumb { display:flex; gap:9px; padding:0; margin:0; color:#6c757d; list-style:none; font-size:14px; }
        .breadcrumb li+li::before { content:"/"; padding-right:9px; color:#adb5bd; }
        .content { padding:6px 24px 28px; }
        .page-description { margin:0 0 16px; color:#6c757d; }
        .notice { position:relative; margin:0 0 16px; padding:12px 15px; border-radius:3px; border-left:4px solid; background:#fff; box-shadow:0 1px 2px rgb(0 0 0 / 7%); }
        .notice.success { border-color:#28a745; color:#1e7e34; }
        .notice.error { border-color:#dc3545; color:#bd2130; }
        .duration-card,.setting-card,.help { border-radius:3px; background:#fff; box-shadow:0 1px 3px rgb(0 0 0 / 12%); }
        .duration-card { margin-bottom:20px; border-top:3px solid #007bff; }
        .card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:13px 16px; border-bottom:1px solid rgba(0,0,0,.125); }
        .card-head h2 { margin:0; color:#343a40; font-size:18px; font-weight:400; }
        .card-head p { margin:3px 0 0; color:#6c757d; font-size:13px; }
        .card-body { padding:16px; }
        .duration-form { display:flex; align-items:end; gap:12px; }
        label { display:block; margin-bottom:5px; font-weight:600; }
        input[type=number] { width:140px; min-height:38px; padding:7px 10px; border:1px solid #ced4da; border-radius:4px; font:inherit; }
        textarea { display:block; width:100%; min-height:250px; resize:vertical; padding:10px 12px; border:1px solid #ced4da; border-radius:4px; color:#343a40; background:#fff; font:13px/1.5 Consolas,"Courier New",monospace; tab-size:2; }
        input[type=text],input[type=email],input[type=url],input[type=file],input[type=date],input[type=datetime-local] { width:100%; min-height:38px; padding:7px 10px; border:1px solid #ced4da; border-radius:4px; color:#343a40; background:#fff; font:inherit; }
        select { width:100%; min-height:38px; padding:7px 10px; border:1px solid #ced4da; border-radius:4px; color:#343a40; background:#fff; font:inherit; }
        input[type=file] { padding:5px; }
        input[type=checkbox] { width:16px; height:16px; vertical-align:middle; }
        textarea:focus,input:focus { outline:3px solid rgb(0 123 255 / 18%); border-color:#80bdff; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:7px 13px; border:1px solid #007bff; border-radius:4px; color:#fff; background:#007bff; text-decoration:none; font:inherit; cursor:pointer; }
        .button:hover { border-color:#0062cc; color:#fff; background:#0069d9; }
        .button.secondary { border-color:#6c757d; background:#6c757d; }
        .settings-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; align-items:start; }
        .setting-card { min-width:0; overflow:hidden; border-top:3px solid #17a2b8; scroll-margin-top:74px; }
        .setting-card .card-head { min-height:68px; }
        .setting-card .card-body { padding:14px 16px 16px; }
        .setting-card.portfolio-card { border-top-color:#6f42c1; }
        .portfolio-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; align-items:start; }
        .portfolio-fields label { margin-bottom:5px; }
        .portfolio-fields .field-wide { grid-column:1/-1; }
        .portfolio-fields [id] { scroll-margin-top:74px; }
        .about-editor-shortcuts { display:flex; flex-wrap:wrap; gap:9px; margin:0 0 18px; }
        .about-editor-shortcuts a { display:inline-flex; align-items:center; min-height:36px; padding:7px 12px; border:1px solid #ced4da; border-radius:999px; color:#495057; background:#f8f9fa; font-size:13px; font-weight:600; text-decoration:none; }
        .about-editor-shortcuts a:hover,.about-editor-shortcuts a:focus-visible { border-color:#6f42c1; color:#563d7c; background:#f1ebf8; }
        .about-content-list { display:grid; gap:14px; }
        .about-content-item { min-width:0; padding:14px; border:1px solid #dee2e6; border-left:3px solid #6f42c1; border-radius:4px; background:#f8f9fa; }
        #portfolio-sections-form .home-fields-section { padding:16px; border:1px solid #e6eaf0; border-radius:10px; background:linear-gradient(145deg,#fff 0%,#fbfcff 100%); transition:border-color .22s,box-shadow .22s,transform .22s; }
        #portfolio-sections-form .home-fields-section:focus-within { border-color:#83d6df; box-shadow:0 10px 28px rgb(23 162 184 / 11%); transform:translateY(-2px); }
        #portfolio-sections-form .about-content-item { transition:transform .2s,border-color .2s,box-shadow .2s,opacity .2s; animation:sectionCardEnter .36s ease both; }
        #portfolio-sections-form .about-content-item:hover { transform:translateY(-2px); border-color:#b9dce1; box-shadow:0 8px 20px rgb(29 76 90 / 8%); }
        #portfolio-sections-form .about-content-item.is-section-hidden { border-left-color:#adb5bd; background:#f2f4f6; opacity:.78; }
        .section-editor-actions { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-top:18px; padding:13px 14px; border:1px solid #dfe8ec; border-radius:9px; background:linear-gradient(110deg,#f1fbfc,#fff9ed); }
        .section-editor-actions>div { display:flex; flex-wrap:wrap; gap:9px; }
        .section-editor-status { min-height:20px; color:#176b78; font-size:13px; font-weight:600; }
        .section-editor-status:empty { display:none; }
        .section-editor-actions .button { transition:transform .18s,box-shadow .18s; }
        .section-editor-actions .button:hover,.section-editor-actions .button:focus-visible { transform:translateY(-2px); box-shadow:0 5px 14px rgb(23 162 184 / 18%); }
        .section-reset-animation { animation:sectionResetPulse .55s ease; }
        .theme-preset-preview { display:flex; align-items:center; gap:12px; max-width:360px; margin-top:12px; padding:12px; border:1px solid #dfe8ec; border-radius:10px; background:#fff; transition:background .25s,border-color .25s,transform .25s; }
        .theme-preset-preview[data-theme-preview="dark"] { color:#fff; border-color:#334155; background:#0f172a; }
        .theme-preset-preview[data-theme-preview="light"] { color:#102a43; border-color:#dbe7e2; background:#f0fff0; }
        .theme-preset-preview[data-theme-preview="black"] { color:#fff; border-color:#383838; background:#000; }
        .theme-preset-preview[data-theme-preview="light-black"] { color:#f4f4f5; border-color:#52525b; background:#27272a; }
        .theme-preset-preview[data-theme-preview="natural"] { color:#352f27; border-color:#d6c9b3; background:#f4efe6; }
        .theme-preset-preview[data-theme-preview="light-pure"] { color:#111827; border-color:#d1d5db; background:#fff; }
        .theme-preview-swatch { width:38px; height:38px; flex:0 0 auto; border:2px solid currentColor; border-radius:12px; background:linear-gradient(135deg,#22d3ee,#fbbf24); box-shadow:0 0 16px rgb(34 211 238 / 18%); }
        .theme-preset-preview[data-theme-preview="natural"] .theme-preview-swatch { background:linear-gradient(135deg,#267765,#a96d18); }
        .theme-preset-preview[data-theme-preview="light-pure"] .theme-preview-swatch { background:linear-gradient(135deg,#007f9e,#a96d18); }
        .theme-preset-preview strong,.theme-preset-preview small { display:block; }
        .theme-preset-preview small { opacity:.72; font-size:11px; }
        .theme-preset-preview[data-theme-preview="dark"] small,.theme-preset-preview[data-theme-preview="black"] small { color:#cbd5e1; }
        @keyframes sectionCardEnter { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @keyframes sectionResetPulse { 50% { box-shadow:0 0 0 4px rgb(23 162 184 / 16%); } }
        .about-content-item h3 { margin:0 0 10px; font-size:15px; }
        .about-content-item-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:11px; }
        .about-content-item-fields .field-wide { grid-column:1/-1; }
        .about-content-item-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:10px; }
        .home-editor-shortcuts { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:7px; }
        .home-editor-shortcuts a { display:inline-flex; align-items:center; min-height:34px; padding:5px 10px; border:1px solid #d5c9e8; border-radius:999px; color:#563d7c; background:#f8f5fb; font-size:12px; font-weight:650; text-decoration:none; white-space:nowrap; transition:transform .2s,border-color .2s,box-shadow .2s; }
        .home-editor-shortcuts a:hover,.home-editor-shortcuts a:focus-visible,.home-editor-shortcuts a[aria-current="location"] { transform:translateY(-2px); border-color:#6f42c1; background:#f1ebf8; box-shadow:0 6px 16px rgb(111 66 193 / 13%); }
        .home-live-preview { position:relative; isolation:isolate; display:grid; grid-template-columns:100px minmax(0,1fr); gap:16px; align-items:center; margin:0 0 18px; padding:16px; overflow:hidden; border:1px solid #d5c9e8; border-radius:12px; background:#f8f5fb; box-shadow:0 6px 20px rgb(52 58 64 / 6%); animation:home-preview-in .45s ease both; }
        .home-live-preview-background { position:absolute; z-index:-1; inset:0; width:100%; height:100%; object-fit:cover; opacity:.12; transition:opacity .25s; }
        .home-live-preview img:not(.home-live-preview-background) { width:100px; height:118px; object-fit:cover; border-radius:9px; background:#e9ecef; }
        .home-live-preview-copy { min-width:0; padding:8px; border-radius:8px; background:rgb(255 255 255 / 84%); backdrop-filter:blur(4px); }
        .home-live-preview-copy>span { display:block; margin-bottom:5px; color:#6f42c1; font-size:10px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .home-live-preview h3 { margin:0; overflow-wrap:anywhere; color:#343a40; font-size:clamp(18px,2.4vw,25px); }
        .home-live-preview p { margin:4px 0 0; color:#6c757d; font-size:13px; overflow-wrap:anywhere; }
        .home-live-preview-roles,.home-live-preview-actions,.home-live-preview-stats { display:flex; flex-wrap:wrap; gap:6px; margin-top:9px; }
        .home-live-preview-roles span,.home-live-preview-actions span,.home-live-preview-stats span { max-width:100%; overflow:hidden; padding:4px 8px; border:1px solid #e1d8ee; border-radius:99px; color:#563d7c; background:#fff; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
        .home-live-preview .is-hidden { opacity:.42; filter:grayscale(.55); text-decoration:line-through; }
        @keyframes home-preview-in { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .home-action-editor,.home-stat-editor { transition:transform .2s,border-color .2s,box-shadow .2s; }
        .home-action-editor:focus-within,.home-stat-editor:focus-within { transform:translateY(-2px); border-color:#6f42c1; box-shadow:0 6px 18px rgb(111 66 193 / 10%); }
        .home-visibility { display:inline-flex; align-items:center; gap:6px; margin:7px 0 0; color:#495057; font-size:12px; font-weight:500; }
        .home-visibility input { width:16px; height:16px; }
        .home-action-editor { min-width:0; padding:13px; border:1px solid #dee2e6; border-left:3px solid #17a2b8; border-radius:4px; background:#f8f9fa; }
        .home-action-editor h3,.home-stat-editor h3 { margin:0 0 8px; font-size:14px; }
        .home-actions-editor,.home-stats-editor { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; grid-column:1/-1; }
        .home-stats-editor { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .home-stat-editor { display:grid; grid-template-columns:minmax(0,1fr) minmax(100px,.55fr) minmax(80px,.45fr) auto; gap:9px; align-items:end; padding:12px; border:1px solid #dee2e6; border-radius:4px; background:#f8f9fa; }
        .home-stat-editor input[type=number] { width:100%; }
        .home-fields-section { grid-column:1/-1; margin:12px 0 0; padding-top:14px; border-top:1px solid #dee2e6; scroll-margin-top:74px; }
        .home-fields-section h3 { margin:0 0 5px; font-size:16px; }
        .about-values-editor { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; grid-column:1/-1; }
        .about-value-editor { min-width:0; padding:13px; border:1px solid #dee2e6; border-left:3px solid #17a2b8; border-radius:4px; background:#f8f9fa; scroll-margin-top:74px; }
        .about-value-editor h3 { margin:0 0 10px; font-size:15px; }
        .portfolio-fields textarea { min-height:84px; font:inherit; }
        .logo-preview { max-width:110px; max-height:72px; display:block; margin:8px 0; object-fit:contain; }
        .menu-editor { display:grid; gap:12px; }
        .project-editor-shortcuts { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 16px; }
        .project-editor-shortcuts a { display:inline-flex; min-height:36px; align-items:center; padding:6px 12px; border:1px solid #d5e0e6; border-radius:999px; color:#176b78; background:#f2fbfc; font-size:13px; font-weight:650; text-decoration:none; transition:transform .18s ease,border-color .18s ease,background .18s ease; }
        .project-editor-shortcuts a:hover { transform:translateY(-2px); border-color:#17a2b8; background:#e4f8fa; }
        .project-editor-section { margin:18px 0 0; padding:16px; border:1px solid #e1e7eb; border-radius:8px; background:#fbfcfd; scroll-margin-top:78px; }
        .project-editor-section h3 { margin:0; color:#343a40; font-size:17px; }
        .project-admin-list { display:grid; gap:12px; margin-top:12px; }
        .project-admin-card { min-width:0; padding:14px; border:1px solid #dce4e8; border-left:4px solid #17a2b8; border-radius:8px; background:#fff; box-shadow:0 4px 14px rgb(21 51 65 / 5%); animation:sideNavReveal .25s ease both; }
        .project-admin-card[data-visible="false"] { border-left-color:#adb5bd; background:#f7f8f9; }
        .project-admin-card h4 { margin:0 0 12px; font-size:15px; }
        .project-admin-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .project-admin-fields .field-wide { grid-column:1/-1; }
        .project-admin-fields textarea { min-height:78px; font:inherit; }
        .project-admin-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:12px; padding-top:10px; border-top:1px solid #e9edef; }
        .project-admin-actions .home-visibility { margin-right:auto; }
        .project-admin-card [data-project-field],.project-admin-card [data-goal-field] { min-width:0; }
        .project-admin-status { min-height:20px; margin:14px 0 0; color:#1e7e34; font-size:13px; }
        .project-editor-section:target { animation:editorTargetReveal .45s ease-out; }
        .social-post-editor { display:grid; gap:12px; }
        .social-post-editor-card { padding:13px; border:1px solid #dee2e6; border-left:3px solid #17a2b8; border-radius:4px; background:#f8f9fa; }
        .social-post-editor-card[hidden] { display:none; }
        .social-post-editor-card:focus-within { border-color:#6f42c1; box-shadow:0 6px 18px rgb(111 66 193 / 10%); }
        .social-post-editor-heading { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:10px; }
        .social-post-selector-wrap { max-width:620px; margin:0 auto 16px; }
        .social-post-editor-tabs { display:flex; justify-content:center; flex-wrap:nowrap; gap:7px; margin:0 0 12px; padding:0 0 10px; overflow-x:auto; border-bottom:1px solid #dee2e6; }
        .social-post-editor-tab { flex:0 0 auto; min-height:36px; padding:6px 12px; border:1px solid #ced4da; border-radius:999px; color:#495057; background:#fff; font:inherit; font-size:12px; font-weight:650; white-space:nowrap; cursor:pointer; }
        .social-post-editor-tab[aria-selected="true"] { border-color:#17a2b8; color:#fff; background:#138496; }
        .social-post-editor-panel[hidden] { display:none; }
        .social-post-feedback { margin:0; padding:12px; border:1px solid #d5e8eb; border-radius:6px; color:#495057; background:#f0f8f9; font-size:13px; }
        .social-feedback-tabs { display:flex; justify-content:center; flex-wrap:wrap; gap:7px; margin:0 0 12px; }
        .social-feedback-tab { min-height:34px; padding:5px 11px; border:1px solid #ced4da; border-radius:999px; color:#495057; background:#fff; font:inherit; font-size:12px; font-weight:650; cursor:pointer; }
        .social-feedback-tab[aria-pressed="true"] { border-color:#138496; color:#fff; background:#138496; }
        .social-feedback-panel[hidden] { display:none; }
        .social-feedback-panel { padding:10px; border:1px solid #dee2e6; border-radius:6px; background:#fff; }
        .social-feedback-table-wrap { max-width:100%; overflow:auto; }
        .social-feedback-table { width:100%; min-width:1150px; border-collapse:collapse; text-align:left; }
        .social-feedback-table th,.social-feedback-table td { padding:9px; border-bottom:1px solid #e5e9ec; vertical-align:top; overflow-wrap:anywhere; }
        .social-feedback-table th { position:sticky; top:0; z-index:1; color:#343a40; background:#f1f3f5; font-size:12px; white-space:nowrap; }
        .social-feedback-table td { max-width:340px; color:#495057; font-size:13px; white-space:pre-wrap; }
        .social-feedback-table .social-feedback-actions { min-width:190px; white-space:normal; }
        .social-feedback-actions .button { margin:0 4px 5px 0; }
        .social-feedback-edit-input { min-height:72px; margin-top:7px; font:inherit; }
        .social-feedback-entry.is-hidden-feedback { opacity:.72; background:#fff3f3; }
        .social-feedback-entry.is-hidden-feedback td:first-child::after { content:" · hidden"; color:#b02a37; font-size:11px; }
        .social-feedback-empty,.social-feedback-error { margin:0; color:#6c757d; font-size:13px; }
        .social-feedback-error { color:#b02a37; }
        .social-feedback-success { margin:0; color:#1e7e34; font-size:13px; }
        .social-feedback-tools { display:flex; justify-content:center; margin-bottom:10px; }
        .social-feedback-count { display:inline-block; margin-left:4px; opacity:.8; }
        .social-post-editor-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .social-post-editor-fields textarea { min-height:88px; font:inherit; }
        .social-post-description { grid-column:1/-1; }
        .social-post-editor-actions { display:flex; justify-content:center; flex-wrap:wrap; gap:8px; margin-top:14px; padding-top:13px; border-top:1px solid #dee2e6; }
        .social-post-editor-actions[hidden] { display:none; }
        .social-post-editor-actions .button { min-height:36px; }
        .social-post-editor-actions .button[aria-pressed="true"] { border-color:#138496; background:#138496; }
        .social-post-editor-actions .button:disabled { opacity:.58; cursor:not-allowed; }
        .social-post-action-status { width:100%; min-height:20px; margin:0; color:#6c757d; font-size:12px; text-align:center; }
        .menu-item-editor { padding:13px; border:1px solid #dee2e6; border-left:3px solid #17a2b8; border-radius:4px; background:#f8f9fa; }
        .menu-item-fields { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.3fr) minmax(125px,.6fr) auto; gap:10px; align-items:end; }
        .menu-children { display:grid; gap:8px; margin:12px 0 0 20px; }
        .menu-child-editor { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.3fr) minmax(125px,.6fr) auto; gap:10px; align-items:end; padding:10px; border-left:2px solid #ced4da; background:#fff; scroll-margin-top:74px; }
        .menu-editor-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
        .button.small { min-height:32px; padding:5px 9px; font-size:13px; }
        .button.danger { border-color:#dc3545; background:#dc3545; }
        .button.danger:hover { border-color:#bd2130; background:#c82333; }
        .form-help { margin:5px 0 0; color:#6c757d; font-size:12px; }
        .card-footer { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:11px; }
        .hint { color:#6c757d; font-size:12px; }
        .help { margin-top:20px; padding:14px 16px; color:#6c757d; }
        code { color:#e83e8c; }
        .footer { padding:14px 24px; border-top:1px solid #dee2e6; color:#6c757d; font-size:13px; }
        .backdrop { display:none; padding:0; border:0; background:transparent; cursor:pointer; }
        .sidebar-collapsed .sidebar { transform:translateX(-250px); }
        .sidebar-collapsed .main-area { margin-left:0; }
        @media(max-width:900px) { .sidebar { transform:translateX(-250px); } .main-area { margin-left:0; } .sidebar-open .sidebar { transform:translateX(0); } .sidebar-open .backdrop { position:fixed; z-index:15; inset:0; display:block; background:rgb(0 0 0 / 45%); } }
        @media(max-width:1100px) { .setting-card#portfolio_home>.card-head { align-items:flex-start; flex-direction:column; } .home-editor-shortcuts { justify-content:flex-start; } }
        @media(max-width:900px) { .home-actions-editor,.home-stats-editor { grid-template-columns:repeat(2,minmax(0,1fr)); } .home-stat-editor { grid-template-columns:1fr 1fr; } }
        @media(max-width:680px) { .content-header { align-items:flex-start; flex-direction:column; padding:17px 16px 8px; } .content { padding:6px 16px 22px; } .navbar { padding:0 12px; } .nav-right { gap:10px; } .settings-grid { grid-template-columns:1fr; } .duration-form { align-items:flex-start; flex-direction:column; } input[type=number] { width:100%; } .card-footer { align-items:flex-start; flex-direction:column; } .portfolio-fields,.menu-item-fields,.menu-child-editor,.about-values-editor,.about-content-item-fields,.home-actions-editor,.home-stats-editor,.social-post-editor-fields,.project-admin-fields { grid-template-columns:1fr; } .about-content-item-fields .field-wide,.project-admin-fields .field-wide { grid-column:auto; } .about-content-item-actions,.project-admin-actions { align-items:flex-start; flex-direction:column; } .section-editor-actions { align-items:stretch; flex-direction:column; } .section-editor-actions>div { display:grid; grid-template-columns:1fr 1fr; } .section-editor-actions .button { width:100%; } .theme-preset-preview { max-width:none; } .social-post-description { grid-column:auto; } .home-editor-shortcuts { display:grid; width:100%; grid-template-columns:repeat(2,minmax(0,1fr)); } .home-editor-shortcuts a { justify-content:center; text-align:center; white-space:normal; } .home-live-preview { grid-template-columns:68px minmax(0,1fr); gap:10px; padding:10px; } .home-live-preview img:not(.home-live-preview-background) { width:68px; height:84px; } .menu-children { margin-left:0; } .side-nav-dropdown>summary { min-height:44px; } .side-nav-nested>summary,.side-nav-submenu a { min-height:42px; } .project-editor-section { padding:12px; } .project-admin-actions .home-visibility { margin-right:0; } .footer { padding:12px 16px; } }
        @media(prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto !important; transition:none !important; animation:none !important; } .home-live-preview { animation:none; } }
    </style>
</head>
<body>
<div class="app" id="app">
    <aside class="sidebar" aria-label="Admin sidebar">
        <a class="brand" href="admin_dashboard.php">
            <img src="logo.jpg" alt="">
            <span><strong>Admin</strong> Portal</span>
        </a>
        <div class="user-panel">
            <span class="user-avatar" aria-hidden="true">A</span>
            <a class="user-name" href="admin_dashboard.php"><?php echo blogDashboardEscape($_SESSION['username'] ?? 'Administrator'); ?></a>
        </div>
        <nav class="side-nav" aria-label="Dashboard navigation">
            <div class="nav-title">Blog management</div>
            <a class="active" href="#overview"><i class="nav-icon" aria-hidden="true">▦</i><span>Overview</span></a>
            <a href="#blog_banner_slides"><i class="nav-icon" aria-hidden="true">▤</i><span>Homepage banner</span></a>
            <a href="#blog_feature_update"><i class="nav-icon" aria-hidden="true">★</i><span>Featured update</span></a>
            <a href="#blog_insurance_news"><i class="nav-icon" aria-hidden="true">▣</i><span>News cards</span></a>
            <a href="#blog_core_pillars"><i class="nav-icon" aria-hidden="true">◈</i><span>About / pillars</span></a>
            <a href="#blog_product_catalog"><i class="nav-icon" aria-hidden="true">▦</i><span>Products</span></a>
            <a href="#blog_executive_management"><i class="nav-icon" aria-hidden="true">♙</i><span>Executive team</span></a>
            <a href="#blog_performance"><i class="nav-icon" aria-hidden="true">▥</i><span>Performance</span></a>
            <details class="side-nav-dropdown" id="subscriber-admin-dropdown">
                <summary><i class="nav-icon" aria-hidden="true">✉</i><span>Email subscribers</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                <div class="side-nav-submenu">
                    <a href="admin_dashboard.php?contact_folder=inbox#portfolioSubscriberMailbox" data-mailbox-link>
                        <span>Mailbox</span>
                        <span class="side-nav-mailbox-badge" id="mailboxUnreadBadge" data-unread-count="<?php echo $unreadContactMessageCount; ?>" title="<?php echo $unreadContactMessageCount; ?> unread message<?php echo $unreadContactMessageCount === 1 ? '' : 's'; ?>"<?php echo $unreadContactMessageCount > 0 ? '' : ' hidden'; ?> aria-label="<?php echo $unreadContactMessageCount; ?> unread messages"><?php echo $unreadContactMessageCount > 99 ? '99+' : $unreadContactMessageCount; ?></span>
                    </a>
                    <a href="admin_dashboard.php#portfolio-subscriber-info"><span>Subscriber info</span></a>
                    <a href="admin_dashboard.php#portfolio-project-payments"><span>Project ZIP payments</span></a>
                </div>
            </details>
            <span class="mailbox-live-status" id="mailboxUnreadLiveStatus" role="status" aria-live="polite" aria-atomic="true"></span>
            <details class="side-nav-dropdown" id="website-control-dropdown">
                <summary><i class="nav-icon" aria-hidden="true">⚙</i><span>Website Control</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                <div class="side-nav-submenu">
                    <a href="#portfolio_header"><span>Header Panel</span></a>
                    <details class="side-nav-nested" id="website-home-dropdown">
                        <summary><span>Home Page</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="side-nav-submenu">
                            <a href="#portfolio_home"><span>Page settings</span></a>
                            <a href="#portfolio_advertisements"><span>Advertise</span></a>
                        </div>
                    </details>
                    <details class="side-nav-nested" id="website-about-dropdown">
                        <summary><span>About</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="side-nav-submenu">
                            <a href="#portfolio_about"><span>About me</span></a>
                            <a href="#portfolio_journey"><span>Journey</span></a>
                            <a href="#portfolio_skills"><span>Skills</span></a>
                            <a href="#portfolio_faqs"><span>FAQs</span></a>
                        </div>
                    </details>
                    <details class="side-nav-nested" id="website-projects-dropdown">
                        <summary><span>Projects</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="side-nav-submenu">
                            <a href="#portfolio_projects"><span>All Projects</span></a>
                            <details class="side-nav-nested">
                                <summary><span>Strategy</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                                <div class="side-nav-submenu">
                                    <?php foreach ($portfolioProjectsPanel['goals'] as $goal): ?>
                                    <a href="#portfolio-goal-<?php echo blogDashboardEscape($goal['id']); ?>"><span><?php echo blogDashboardEscape($goal['title']); ?></span></a>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </div>
                    </details>
                    <a href="#portfolio_gallery"><span>Gallery</span></a>
                    <a href="#portfolio_blog"><span>Blog</span></a>
                    <a href="#portfolio_contact"><span>Contact</span></a>
                    <a href="#portfolio_search"><span>Search Bar</span></a>
                    <details class="side-nav-nested" id="website-theme-dropdown">
                        <summary><span>Dark Mode</span><span class="nav-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="side-nav-submenu">
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="dark"><span>Dark</span></a>
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="light"><span>Light</span></a>
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="black"><span>Black</span></a>
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="light-black"><span>Light Black</span></a>
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="natural"><span>Natural</span></a>
                            <a href="#portfolio_dark_mode" data-admin-theme-preset="light-pure"><span>Light Pure</span></a>
                        </div>
                    </details>
                </div>
            </details>
            <div class="nav-title">Actions</div>
            <a href="portfolio.php" target="_blank" rel="noopener noreferrer"><i class="nav-icon" aria-hidden="true">↗</i><span>Preview portfolio</span></a>
            <a href="blog.php" target="_blank" rel="noopener noreferrer"><i class="nav-icon" aria-hidden="true">↗</i><span>Preview public blog</span></a>
            <a href="logout.php"><i class="nav-icon" aria-hidden="true">⇥</i><span>Sign out</span></a>
        </nav>
    </aside>
    <script>
        (function () {
            const websiteDropdown = document.getElementById('website-control-dropdown');
            const homeDropdown = document.getElementById('website-home-dropdown');
            const openAdvertisementNavigation = function () {
                if (window.location.hash !== '#portfolio_advertisements') return;
                if (websiteDropdown) websiteDropdown.open = true;
                if (homeDropdown) homeDropdown.open = true;
            };
            document.querySelectorAll('#website-home-dropdown a[href="#portfolio_advertisements"]').forEach(function (link) {
                link.addEventListener('click', openAdvertisementNavigation);
            });
            openAdvertisementNavigation();
        }());
    </script>
    <button class="backdrop" id="sidebar-backdrop" type="button" aria-label="Close navigation"></button>
    <div class="main-area">
        <header class="navbar">
            <div class="nav-left">
                <button class="menu-toggle" id="sidebar-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
                <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
            </div>
            <div class="nav-right">
                <a class="nav-link" href="blog.php" target="_blank" rel="noopener noreferrer">View site ↗</a>
                <span class="nav-user"><?php echo blogDashboardEscape($_SESSION['username'] ?? 'Administrator'); ?></span>
            </div>
        </header>
        <div class="content-header" id="overview">
            <h1>Website content dashboard</h1>
            <ol class="breadcrumb"><li><a href="admin_dashboard.php">Home</a></li><li>Website control</li></ol>
        </div>
        <main class="content">
            <p class="page-description">Manage public blog content and portfolio header/home page settings. Each section saves independently.</p>
            <?php if ($message !== ''): ?><div class="notice success" role="status"><?php echo blogDashboardEscape($message); ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo blogDashboardEscape($error); ?></div><?php endif; ?>

            <section class="duration-card" aria-labelledby="duration-title">
                <div class="card-head"><div><h2 id="duration-title">Homepage banner rotation</h2><p>Choose how long each slide appears, from 2 to 20 seconds.</p></div></div>
                <div class="card-body">
                    <form method="post" class="duration-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <div><label for="blog_banner_duration">Seconds per slide</label><input id="blog_banner_duration" type="number" name="blog_banner_duration" min="2" max="20" value="<?php echo $banner_duration; ?>" required></div>
                        <button class="button" type="submit" name="save_banner_duration">Save duration</button>
                    </form>
                </div>
            </section>

            <section class="setting-card portfolio-card" id="portfolio_subscribers" aria-labelledby="portfolio-subscribers-title">
                <div class="card-head">
                    <div><h2 id="portfolio-subscribers-title">Portfolio email subscribers</h2><p><?php echo $portfolioSubscriberCount; ?> active subscriber<?php echo $portfolioSubscriberCount === 1 ? '' : 's'; ?>. These addresses receive notifications when you publish a new featured update or news card.</p></div>
                </div>
                <div class="card-body">
                    <section class="subscriber-info-section" id="portfolio-subscriber-info" aria-labelledby="portfolio-subscriber-info-title">
                    <h3 id="portfolio-subscriber-info-title">Subscriber info</h3>
                    <p class="form-help">Only subscribers who explicitly consent have their IP address, ISP name, broad device type, and approximate city, region, and country stored. The IP is sent to ipwho.is for this lookup. Metadata is cleared when they unsubscribe. Existing subscribers without recorded consent show unavailable metadata. MAC addresses cannot be read by a website and are not collected.</p>
                    <?php if ($portfolioSubscriberLoadError): ?>
                    <p class="notice error" role="alert">Subscriber records could not be loaded. Check the PHP error log and database permissions.</p>
                    <?php elseif ($portfolioSubscribers === []): ?>
                    <p class="form-help">No email addresses have subscribed yet.</p>
                    <?php else: ?>
                    <details id="subscriber-details" open>
                    <summary>Subscriber details (click to hide or show)</summary>
                    <div style="max-width:100%;overflow-x:auto">
                        <table id="subscriberInfoTable" data-paginated-table style="width:100%;min-width:1280px;border-collapse:collapse;text-align:left">
                            <thead><tr>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Email address</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Status</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Subscribed</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Subscriber IP address</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Internet ISP name</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Device type</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">City / Region / Country</th>
                                <th scope="col" style="padding:10px;border-bottom:1px solid #dee2e6">Action details</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($portfolioSubscribers as $subscriberIndex => $subscriber): ?>
                                <?php
                                    $subscriberLocation = array_values(array_filter(
                                        [$subscriber['city'] ?? '', $subscriber['region'] ?? '', $subscriber['country'] ?? ''],
                                        static fn($value): bool => is_string($value) && trim($value) !== ''
                                    ));
                                    $subscriberActive = !empty($subscriber['is_active']);
                                    $subscriberMetadataConsented = !empty($subscriber['metadata_consent_at']);
                                ?>
                                <tr data-pagination-row>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2;overflow-wrap:anywhere"><?php echo blogDashboardEscape($subscriber['email']); ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2"><?php echo $subscriberActive ? 'Active' : 'Unsubscribed'; ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2;white-space:nowrap"><?php echo blogDashboardEscape((string) ($subscriber['subscribed_at'] ?? '')); ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2;overflow-wrap:anywhere"><?php echo blogDashboardEscape($subscriberMetadataConsented && is_string($subscriber['ip_address'] ?? null) && $subscriber['ip_address'] !== '' ? $subscriber['ip_address'] : 'Unavailable'); ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2"><?php echo blogDashboardEscape($subscriberMetadataConsented && is_string($subscriber['isp_name'] ?? null) && $subscriber['isp_name'] !== '' ? $subscriber['isp_name'] : 'Unavailable'); ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2"><?php echo blogDashboardEscape($subscriberMetadataConsented && is_string($subscriber['device_type'] ?? null) && $subscriber['device_type'] !== '' ? $subscriber['device_type'] : 'Unavailable'); ?></td>
                                    <td style="padding:10px;border-bottom:1px solid #edf0f2"><?php echo blogDashboardEscape($subscriberMetadataConsented && $subscriberLocation !== [] ? implode(', ', $subscriberLocation) : 'Unavailable'); ?></td>
                                    <td style="min-width:220px;padding:10px;border-bottom:1px solid #edf0f2">
                                        <?php if ($subscriberActive): ?>
                                        <a class="button small" href="admin_dashboard.php?contact_folder=compose&amp;recipient=<?php echo rawurlencode($subscriber['email']); ?>#portfolio_subscribers">Open email composer</a>
                                        <?php else: ?>
                                        <span class="hint">Email disabled for unsubscribed addresses.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="subscriber-table-pagination" data-pagination-controls data-pager-for="subscriberInfoTable" aria-label="Subscriber table pagination">
                        <label>Rows per page
                            <select data-page-size aria-label="Subscriber rows per page">
                                <option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                            </select>
                        </label>
                        <span data-page-summary aria-live="polite"></span>
                        <button class="button small" type="button" data-page-previous aria-label="Previous subscriber page">Previous</button>
                        <button class="button small" type="button" data-page-next aria-label="Next subscriber page">Next</button>
                    </div>
                    </details>
                    <?php endif; ?>
                    </section>
                    <div class="subscriber-mailbox-entry">
                        <h3>Mailbox</h3>
                        <p class="form-help">Read and manage messages sent through the portfolio contact form.</p>
                        <button class="button" type="button" data-mailbox-toggle aria-expanded="<?php echo $mailboxFolder !== 'inbox' || isset($_GET['contact_folder']) || isset($_POST['open_contact_message']) || isset($_POST['mailbox_action']) || isset($_POST['send_subscriber_email']) || isset($_POST['save_subscriber_draft']) || isset($_POST['open_subscriber_compose']) ? 'true' : 'false'; ?>" aria-controls="portfolioSubscriberMailbox">Open mailbox<?php if ($unreadContactMessageCount > 0): ?> · <?php echo $unreadContactMessageCount; ?> unread<?php endif; ?></button>
                    </div>
                    <?php
                        $selectedContactMessage = null;
                        $mailboxFolders = ['inbox' => 'Inbox', 'sent' => 'Sent', 'drafts' => 'Drafts', 'archive' => 'Archive', 'spam' => 'Spam', 'trash' => 'Trash'];
                        $mailboxFolderCounts = array_fill_keys(array_keys($mailboxFolders), 0);
                        foreach ($portfolioContactMessages as $inboxMessage) {
                            $rowFolder = isset($inboxMessage['folder'], $mailboxFolderCounts[$inboxMessage['folder']])
                                ? $inboxMessage['folder']
                                : 'inbox';
                            $mailboxFolderCounts[$rowFolder]++;
                            if ((int) $inboxMessage['message_id'] === $selectedContactMessageId) {
                                $selectedContactMessage = $inboxMessage;
                            }
                        }
                        $visibleMailboxMessages = array_values(array_filter(
                            $portfolioContactMessages,
                            static fn(array $inboxMessage): bool => ($inboxMessage['folder'] ?? 'inbox') === $mailboxFolder
                        ));
                        $editingDraft = null;
                        $requestedDraftId = filter_var($_GET['draft_id'] ?? null, FILTER_VALIDATE_INT);
                        if ($requestedDraftId !== false && $requestedDraftId > 0) {
                            foreach ($portfolioContactMessages as $inboxMessage) {
                                if ((int) $inboxMessage['message_id'] === $requestedDraftId && ($inboxMessage['folder'] ?? '') === 'drafts') {
                                    $editingDraft = $inboxMessage;
                                    $mailboxFolder = 'compose';
                                    break;
                                }
                            }
                        }
                        $forwardingMessage = null;
                        $requestedForwardId = filter_var($_GET['forward_id'] ?? null, FILTER_VALIDATE_INT);
                        if ($editingDraft === null && $requestedForwardId !== false && $requestedForwardId > 0) {
                            foreach ($portfolioContactMessages as $inboxMessage) {
                                if ((int) $inboxMessage['message_id'] === $requestedForwardId) {
                                    $forwardingMessage = $inboxMessage;
                                    $mailboxFolder = 'compose';
                                    break;
                                }
                            }
                            if ($forwardingMessage === null) {
                                $error = 'That message is no longer available to forward.';
                            }
                        }
                        $forwardedBody = '';
                        if ($forwardingMessage !== null) {
                            $forwardedBody = "---------- Forwarded message ----------\n"
                                . 'From: ' . $forwardingMessage['sender_name'] . ' <' . $forwardingMessage['sender_email'] . ">\n"
                                . 'To: ' . (!empty($forwardingMessage['recipient_email']) ? $forwardingMessage['recipient_email'] : 'Portfolio inbox') . "\n"
                                . 'Date: ' . date('D, M j, Y \a\t g:i A', strtotime((string) $forwardingMessage['submitted_at'])) . "\n"
                                . 'Subject: ' . $forwardingMessage['subject'] . "\n\n"
                                . $forwardingMessage['message'];
                            if (strlen($forwardedBody) > 10000) {
                                $forwardedBody = substr($forwardedBody, 0, 10000);
                            }
                        }
                        $replyRecipient = isset($_GET['reply_to']) && is_string($_GET['reply_to']) ? $_GET['reply_to'] : '';
                        $queryRecipient = isset($_GET['recipient']) && is_string($_GET['recipient']) ? $_GET['recipient'] : '';
                        $querySubject = isset($_GET['subject']) && is_string($_GET['subject']) ? $_GET['subject'] : '';
                        $mailboxVisible = $mailboxFolder !== 'inbox' || isset($_GET['contact_folder']) ||
                            isset($_POST['open_contact_message']) ||
                            isset($_POST['mailbox_action']) || isset($_POST['send_subscriber_email']) ||
                            isset($_POST['save_subscriber_draft']) || isset($_POST['open_subscriber_compose']) ||
                            isset($_POST['delete_subscriber_draft']);
                        $composeRecipient = isset($recipient)
                            ? $recipient
                            : ($forwardingMessage !== null ? '' : (string) ($editingDraft['recipient_email'] ?? ($replyRecipient !== '' ? $replyRecipient : $queryRecipient)));
                        $composeCc = isset($ccInput) ? $ccInput : (string) ($editingDraft['cc_emails'] ?? '');
                        $composeSubject = isset($subject)
                            ? $subject
                            : (string) ($editingDraft['subject'] ?? ($forwardingMessage !== null ? 'Fwd: ' . $forwardingMessage['subject'] : $querySubject));
                        $composeBody = isset($body) ? $body : (string) ($editingDraft['message'] ?? $forwardedBody);
                        $composeDraftId = isset($draftId) && $draftId > 0 ? $draftId : (int) ($editingDraft['message_id'] ?? 0);
                    ?>
                    <style>
                        .subscriber-info-section,.subscriber-mailbox-entry,.subscriber-mailbox{scroll-margin-top:78px}
                        .subscriber-info-section:target,.subscriber-mailbox:target{animation:mailbox-content-in .3s ease both}
                        .subscriber-mailbox-entry{margin-top:24px;padding-top:16px;border-top:1px solid #e8edf2}
                        .subscriber-mailbox-entry h3,.subscriber-info-section h3{margin:0 0 8px;color:#343a40;font-size:18px}
                        .subscriber-mailbox[hidden]{display:none}
                        .subscriber-mailbox{margin-top:20px;border:1px solid #dfe5ec;border-radius:10px;background:#fff;overflow:hidden;transition:opacity .2s,transform .2s}
                        .subscriber-mailbox.is-loading{opacity:.62;transform:translateY(3px);pointer-events:none}
                        .subscriber-mailbox-header{padding:16px 18px;border-bottom:1px solid #e8edf2}
                        .subscriber-mailbox-status{margin:10px 0 0;padding:9px 12px;border-radius:6px;font-size:13px}
                        .subscriber-mailbox-status.success{color:#14532d;background:#dcfce7}
                        .subscriber-mailbox-status.error{color:#7f1d1d;background:#fee2e2}
                        .subscriber-mailbox-layout{display:grid;grid-template-columns:220px minmax(0,1fr);min-height:440px}
                        .subscriber-mailbox-sidebar{padding:16px 12px;border-right:1px solid #e8edf2;background:#f8fafc}
                        .subscriber-mailbox-sidebar h4{margin:18px 10px 8px;color:#6c757d;font-size:12px;text-transform:uppercase;letter-spacing:.06em}
                        .subscriber-mailbox-compose{width:100%;margin-bottom:20px}
                        .subscriber-folder{display:flex;justify-content:space-between;gap:8px;padding:9px 10px;border-radius:6px;color:#343a40;text-decoration:none}
                        .subscriber-folder:hover,.subscriber-folder.active{color:#0056b3;background:#e7f1ff}
                        .subscriber-mailbox-main{min-width:0;padding:16px}
                        .subscriber-mailbox-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
                        .subscriber-mailbox-tools{display:flex;align-items:center;flex-wrap:wrap;gap:7px}
                        .subscriber-mailbox-search{width:min(270px,100%);padding:8px 10px;border:1px solid #ced4da;border-radius:5px}
                        .subscriber-mailbox-table-wrap{max-width:100%;overflow-x:auto;border:1px solid #e2e8ef;border-radius:7px}
                        .subscriber-mailbox-table{width:100%;border-collapse:separate;border-spacing:0;text-align:left}
                        .subscriber-mailbox-table th,.subscriber-mailbox-table td{padding:12px 10px;border:0;vertical-align:middle}
                        .subscriber-mailbox-table th{color:#6c757d;font-size:12px;font-weight:650}
                        .subscriber-mailbox-row{cursor:pointer}
                        .subscriber-mailbox-row:hover{background:#f8fafc}
                        .subscriber-mailbox-row.is-unread{font-weight:700}
                        .subscriber-mailbox-row.is-selected{background:#edf6ff;box-shadow:inset 3px 0 #087ea4}
                        .subscriber-mailbox-subject{padding:0;border:0;color:#343a40;background:transparent;font:inherit;text-align:left;text-decoration:none;cursor:pointer}
                        .subscriber-mailbox-row td:first-child,.subscriber-mailbox-row td:last-child{cursor:default}
                        .subscriber-mailbox-sender{display:grid;gap:3px;min-width:150px;overflow-wrap:anywhere}
                        .subscriber-mailbox-sender-email{color:#6c757d;font-size:12px;font-weight:400}
                        .subscriber-mailbox-preview{display:block;max-width:440px;overflow:hidden;color:#6c757d;font-size:13px;font-weight:400;text-overflow:ellipsis;white-space:nowrap}
                        .subscriber-mailbox-time{color:#6c757d;font-size:12px;white-space:nowrap}
                        .subscriber-mailbox-read{margin-top:18px;padding:0;overflow:hidden;border:1px solid #e2e8ef;border-radius:8px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.04);animation:mailbox-content-in .24s ease both}
                        .subscriber-mailbox-read-content{padding:clamp(16px,3vw,26px)}
                        .subscriber-mailbox-read-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding-bottom:16px;border-bottom:1px solid #e9ecef}
                        .subscriber-mailbox-sender-info{display:grid;gap:3px;min-width:0}
                        .subscriber-mailbox-sender-info strong{color:#343a40;font-size:16px;overflow-wrap:anywhere}
                        .subscriber-mailbox-read-date{flex:0 0 auto;color:#6c757d;font-size:12px;text-align:right}
                        .subscriber-mailbox-read-heading{margin:18px 0;font-size:clamp(20px,3vw,26px);line-height:1.3;overflow-wrap:anywhere}
                        .subscriber-mailbox-delivery-meta{display:flex;flex-wrap:wrap;gap:6px 18px;margin:0;padding:12px 0;border-bottom:1px solid #f0f2f4;color:#6c757d;font-size:12px}
                        .subscriber-mailbox-delivery-meta span{overflow-wrap:anywhere}
                        .subscriber-mailbox-read-body{max-width:900px;min-height:130px;margin:0 auto;padding:22px clamp(4px,2vw,20px);color:#343a40}
                        .subscriber-mailbox-read-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:18px;padding-top:16px;border-top:1px solid #e9ecef}
                        .subscriber-mailbox-read-actions{display:flex;flex-wrap:wrap;gap:8px}
                        .subscriber-mailbox-read-actions .button{transition:transform .18s ease,box-shadow .18s ease}
                        .subscriber-mailbox-read-actions .button:hover{transform:translateY(-2px);box-shadow:0 5px 12px rgba(15,23,42,.12)}
                        .subscriber-mailbox-read-actions form{margin:0}
                        .subscriber-mailbox-main{animation:mailbox-content-in .24s ease both}
                        @keyframes mailbox-content-in{from{opacity:.6;transform:translateY(7px)}to{opacity:1;transform:translateY(0)}}
                        .subscriber-message-body{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.8}
                        .subscriber-compose-grid{display:grid;grid-template-columns:minmax(180px,.7fr) minmax(0,1.8fr);gap:18px}
                        .subscriber-compose-list{max-height:460px;overflow:auto;padding-right:10px;border-right:1px solid #e9ecef}
                        .subscriber-compose-list button{display:block;width:100%;padding:9px;text-align:left;border:0;border-bottom:1px solid #e9ecef;background:transparent;overflow-wrap:anywhere;cursor:pointer}
                        .subscriber-compose-form{display:grid;align-content:start;gap:12px}
                        .subscriber-compose-form label{display:grid;gap:5px;font-weight:600}
                        .subscriber-compose-form input,.subscriber-compose-form textarea{width:100%;padding:9px 11px;border:1px solid #ced4da;border-radius:5px;font:inherit}
                        .subscriber-compose-form textarea{min-height:160px;resize:vertical}
                        .subscriber-compose-actions{display:flex;justify-content:flex-end;gap:8px}
                        .subscriber-mailbox-empty{padding:24px;color:#6c757d;text-align:center}
                        .subscriber-table-pagination{display:flex;justify-content:flex-end;align-items:center;flex-wrap:wrap;gap:8px;margin:10px 0 0;color:#6c757d;font-size:13px}
                        .subscriber-table-pagination label{display:flex;align-items:center;gap:6px}
                        .subscriber-table-pagination select{min-height:32px;padding:4px 7px;border:1px solid #ced4da;border-radius:5px;color:#343a40;background:#fff;font:inherit}
                        .subscriber-table-pagination [data-page-summary]{min-width:90px;text-align:center}
                        [data-pagination-row][hidden]{display:none}
                        @media(max-width:760px){.subscriber-mailbox-layout{grid-template-columns:1fr}.subscriber-mailbox-sidebar{border-right:0;border-bottom:1px solid #e8edf2}.subscriber-mailbox-sidebar h4{margin-top:12px}.subscriber-folder{display:inline-flex;margin:2px}.subscriber-compose-grid{grid-template-columns:1fr}.subscriber-compose-list{max-height:180px;border-right:0;border-bottom:1px solid #e9ecef}.subscriber-mailbox-main{padding:10px}.subscriber-mailbox-preview{max-width:170px}}
                        @media(max-width:560px){.subscriber-mailbox-toolbar{align-items:stretch;flex-direction:column}.subscriber-mailbox-search{width:100%}.subscriber-mailbox-tools .button{flex:1;justify-content:center}.subscriber-mailbox-read-header{flex-direction:column;gap:7px}.subscriber-mailbox-read-date{text-align:left}.subscriber-mailbox-read-footer{align-items:stretch;flex-direction:column}.subscriber-mailbox-read-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.subscriber-mailbox-read-actions .button{justify-content:center}}
                        @media(max-width:560px){.subscriber-table-pagination{justify-content:flex-start}.subscriber-table-pagination label{width:100%}}
                    </style>
                    <section class="subscriber-mailbox" id="portfolioSubscriberMailbox"<?php echo $mailboxVisible ? '' : ' hidden'; ?> aria-labelledby="subscriber-mailbox-title">
                        <div class="subscriber-mailbox-header">
                            <h3 id="subscriber-mailbox-title" style="margin:0">Mailbox</h3>
                            <?php if ($mailboxVisible && $message !== ''): ?><p class="subscriber-mailbox-status success" id="mailboxRequestStatus" role="status"><?php echo blogDashboardEscape($message); ?></p><?php elseif ($mailboxVisible && $error !== ''): ?><p class="subscriber-mailbox-status error" id="mailboxRequestStatus" role="alert"><?php echo blogDashboardEscape($error); ?></p><?php else: ?><p class="subscriber-mailbox-status" id="mailboxRequestStatus" role="status" hidden></p><?php endif; ?>
                        </div>
                        <div class="subscriber-mailbox-layout">
                            <aside class="subscriber-mailbox-sidebar" aria-label="Mailbox folders">
                                <a class="button subscriber-mailbox-compose" href="admin_dashboard.php?contact_folder=compose#portfolio_subscribers">Compose</a>
                                <h4>Folders</h4>
                                <?php foreach ($mailboxFolders as $folderKey => $folderLabel): ?>
                                <a class="subscriber-folder<?php echo $mailboxFolder === $folderKey ? ' active' : ''; ?>" href="admin_dashboard.php?contact_folder=<?php echo rawurlencode($folderKey); ?>#portfolio_subscribers">
                                    <span><?php echo blogDashboardEscape($folderLabel); ?></span><span><?php echo (int) $mailboxFolderCounts[$folderKey]; ?></span>
                                </a>
                                <?php endforeach; ?>
                            </aside>
                            <div class="subscriber-mailbox-main">
                                <?php if ($mailboxFolder === 'compose'): ?>
                                <h4 style="margin:0 0 14px">Compose message<?php if ($composeDraftId > 0): ?> · Draft<?php endif; ?></h4>
                                <div class="subscriber-compose-grid">
                                    <aside class="subscriber-compose-list" aria-label="Choose a subscriber or contact">
                                        <strong>Subscribers &amp; contacts</strong>
                                        <?php foreach ($portfolioSubscribers as $subscriber): ?>
                                            <?php if (!empty($subscriber['is_active']) && isset($subscriber['email']) && is_string($subscriber['email'])): ?>
                                            <button type="button" data-select-mailbox-recipient="<?php echo blogDashboardEscape($subscriber['email']); ?>"><?php echo blogDashboardEscape($subscriber['email']); ?></button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php foreach ($portfolioContactMessages as $inboxMessage): ?>
                                            <?php if (filter_var($inboxMessage['sender_email'], FILTER_VALIDATE_EMAIL)): ?>
                                            <button type="button" data-select-mailbox-recipient="<?php echo blogDashboardEscape($inboxMessage['sender_email']); ?>"><?php echo blogDashboardEscape($inboxMessage['sender_name']); ?><small><?php echo blogDashboardEscape($inboxMessage['sender_email']); ?></small></button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </aside>
                                    <form method="post" action="admin_dashboard.php#portfolio_subscribers" class="subscriber-compose-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                                        <input type="hidden" name="draft_id" value="<?php echo $composeDraftId; ?>">
                                        <label>To<input id="subscriberEmailTo" type="email" name="subscriber_email" maxlength="254" value="<?php echo blogDashboardEscape($composeRecipient); ?>" required></label>
                                        <label>CC<input type="text" name="subscriber_cc" maxlength="500" value="<?php echo blogDashboardEscape($composeCc); ?>" placeholder="name@example.com, another@example.com"></label>
                                        <label>Subject<input type="text" name="subscriber_subject" maxlength="200" value="<?php echo blogDashboardEscape($composeSubject); ?>" required></label>
                                        <label>Message<textarea name="subscriber_body" maxlength="10000" required><?php echo blogDashboardEscape($composeBody); ?></textarea></label>
                                        <div class="subscriber-compose-actions">
                                            <button class="button" type="submit" name="send_subscriber_email" value="1">Send</button>
                                            <button class="button small" type="submit" name="save_subscriber_draft" value="1">Save draft</button>
                                            <a class="button small" href="admin_dashboard.php?contact_folder=inbox#portfolio_subscribers">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                                <?php else: ?>
                                <form method="post" action="admin_dashboard.php#portfolio_subscribers" id="mailboxMessagesForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                                    <input type="hidden" name="contact_folder" value="<?php echo blogDashboardEscape($mailboxFolder); ?>">
                                    <div class="subscriber-mailbox-toolbar">
                                        <div class="subscriber-mailbox-tools">
                                            <a class="button small" href="admin_dashboard.php?contact_folder=<?php echo rawurlencode($mailboxFolder); ?>#portfolio_subscribers" aria-label="Refresh folder" title="Refresh">↻</a>
                                            <button class="button small" type="submit" name="mailbox_action" value="archive">▤ Archive</button>
                                            <button class="button small" type="submit" name="mailbox_action" value="trash">▣ Trash</button>
                                            <button class="button small" type="submit" name="mailbox_action" value="spam">⚑ Spam</button>
                                        </div>
                                        <input class="subscriber-mailbox-search" id="mailboxSearch" type="search" placeholder="Search messages" aria-label="Search messages">
                                    </div>
                                    <?php if ($portfolioContactInboxError): ?>
                                    <p class="notice error" role="alert">The mailbox could not be loaded. Check the database permissions and PHP error log.</p>
                                    <?php elseif ($visibleMailboxMessages === []): ?>
                                    <p class="subscriber-mailbox-empty">No messages in <?php echo blogDashboardEscape(strtolower($mailboxFolders[$mailboxFolder] ?? 'this folder')); ?>.</p>
                                    <?php else: ?>
                                    <div class="subscriber-mailbox-table-wrap">
                                        <table id="mailboxMessagesTable" class="subscriber-mailbox-table" data-paginated-table>
                                            <thead><tr><th><input type="checkbox" id="mailboxSelectAll" aria-label="Select all messages"></th><th>Sender / email</th><th>Subject / message</th><th>Time</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($visibleMailboxMessages as $inboxMessage): ?>
                                                <?php
                                                    $messageId = (int) $inboxMessage['message_id'];
                                                    $preview = preg_replace('/\s+/', ' ', (string) $inboxMessage['message']) ?? '';
                                                    $searchableMessage = strtolower(
                                                        $inboxMessage['sender_name'] . ' ' .
                                                        $inboxMessage['sender_email'] . ' ' .
                                                        (string) ($inboxMessage['recipient_email'] ?? '') . ' ' .
                                                        (string) ($inboxMessage['cc_emails'] ?? '') . ' ' .
                                                        $inboxMessage['subject'] . ' ' . $preview
                                                    );
                                                    $isSelectedMessage = $messageId === $selectedContactMessageId;
                                                ?>
                                                <tr class="subscriber-mailbox-row<?php echo empty($inboxMessage['is_read']) ? ' is-unread' : ''; ?><?php echo $isSelectedMessage ? ' is-selected' : ''; ?>"<?php echo $isSelectedMessage ? ' aria-current="true"' : ''; ?> data-pagination-row data-search-match="true" data-mailbox-search="<?php echo blogDashboardEscape($searchableMessage); ?>">
                                                    <td><input type="checkbox" name="message_ids[]" value="<?php echo $messageId; ?>" aria-label="Select message from <?php echo blogDashboardEscape($inboxMessage['sender_name']); ?>"></td>
                                                    <td>
                                                        <span class="subscriber-mailbox-sender">
                                                            <strong><?php echo blogDashboardEscape($mailboxFolder === 'sent' ? (string) ($inboxMessage['recipient_email'] ?? '') : $inboxMessage['sender_name']); ?></strong>
                                                            <span class="subscriber-mailbox-sender-email"><?php echo blogDashboardEscape($mailboxFolder === 'sent' ? 'Recipient' : (string) $inboxMessage['sender_email']); ?></span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="subscriber-mailbox-subject" type="submit" name="open_contact_message" value="<?php echo $messageId; ?>"><?php echo blogDashboardEscape($inboxMessage['subject']); ?></button>
                                                        <span class="subscriber-mailbox-preview"><?php echo blogDashboardEscape($preview); ?></span>
                                                        <button class="subscriber-mailbox-subject" type="submit" name="open_contact_message" value="<?php echo $messageId; ?>">See more</button>
                                                        <?php if ($mailboxFolder === 'drafts'): ?><a href="admin_dashboard.php?contact_folder=compose&amp;draft_id=<?php echo $messageId; ?>#portfolio_subscribers">Edit draft</a><?php endif; ?>
                                                    </td>
                                                    <td class="subscriber-mailbox-time" title="<?php echo blogDashboardEscape((string) $inboxMessage['submitted_at']); ?>"><?php echo blogDashboardEscape(portfolioMailboxRelativeTime($inboxMessage['submitted_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="subscriber-table-pagination" data-pagination-controls data-pager-for="mailboxMessagesTable" aria-label="Mailbox table pagination">
                                        <label>Rows per page
                                            <select data-page-size aria-label="Mailbox messages per page">
                                                <option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                                            </select>
                                        </label>
                                        <span data-page-summary aria-live="polite"></span>
                                        <button class="button small" type="button" data-page-previous aria-label="Previous mailbox page">Previous</button>
                                        <button class="button small" type="button" data-page-next aria-label="Next mailbox page">Next</button>
                                    </div>
                                    <p class="subscriber-mailbox-empty" hidden data-mailbox-no-search-results>No messages match your search.</p>
                                    <?php endif; ?>
                                </form>
                                <?php if ($selectedContactMessage !== null && ($selectedContactMessage['folder'] ?? '') === $mailboxFolder): ?>
                                <article class="subscriber-mailbox-read" aria-labelledby="subscriber-mailbox-read-subject">
                                    <div class="subscriber-mailbox-read-content">
                                        <div class="subscriber-mailbox-read-header">
                                            <div class="subscriber-mailbox-sender-info">
                                                <strong><?php echo blogDashboardEscape($selectedContactMessage['sender_name']); ?></strong>
                                                <span class="subscriber-mailbox-sender-email"><?php echo blogDashboardEscape($selectedContactMessage['sender_email']); ?></span>
                                            </div>
                                            <time class="subscriber-mailbox-read-date" datetime="<?php echo blogDashboardEscape((string) $selectedContactMessage['submitted_at']); ?>" title="<?php echo blogDashboardEscape(date('D, M j, Y \a\t g:i A', strtotime((string) $selectedContactMessage['submitted_at']))); ?>">
                                                <?php echo blogDashboardEscape(portfolioMailboxRelativeTime($selectedContactMessage['submitted_at'])); ?><br>
                                                <?php echo blogDashboardEscape(date('M j, Y, g:i A', strtotime((string) $selectedContactMessage['submitted_at']))); ?>
                                            </time>
                                        </div>
                                        <h3 class="subscriber-mailbox-read-heading" id="subscriber-mailbox-read-subject"><?php echo blogDashboardEscape($selectedContactMessage['subject']); ?></h3>
                                        <p class="subscriber-mailbox-delivery-meta">
                                            <span><strong>To:</strong> <?php echo blogDashboardEscape(!empty($selectedContactMessage['recipient_email']) ? $selectedContactMessage['recipient_email'] : 'Portfolio inbox'); ?></span>
                                            <?php if (!empty($selectedContactMessage['cc_emails'])): ?><span><strong>CC:</strong> <?php echo blogDashboardEscape($selectedContactMessage['cc_emails']); ?></span><?php endif; ?>
                                        </p>
                                        <div class="subscriber-mailbox-read-body subscriber-message-body"><?php echo blogDashboardEscape($selectedContactMessage['message']); ?></div>
                                        <div class="subscriber-mailbox-read-footer">
                                            <div class="subscriber-mailbox-read-actions">
                                                <?php if ($mailboxFolder === 'inbox'): ?><a class="button small" href="admin_dashboard.php?contact_folder=compose&amp;reply_to=<?php echo rawurlencode($selectedContactMessage['sender_email']); ?>&amp;subject=<?php echo rawurlencode('Re: ' . $selectedContactMessage['subject']); ?>#portfolio_subscribers">Reply</a><?php endif; ?>
                                                <a class="button small" href="admin_dashboard.php?contact_folder=compose&amp;forward_id=<?php echo (int) $selectedContactMessage['message_id']; ?>#portfolio_subscribers">Forward</a>
                                            </div>
                                            <form id="mailboxReadActionsForm" method="post" action="admin_dashboard.php#portfolio_subscribers">
                                                <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                                                <input type="hidden" name="contact_folder" value="<?php echo blogDashboardEscape($mailboxFolder); ?>">
                                                <input type="hidden" name="message_ids[]" value="<?php echo (int) $selectedContactMessage['message_id']; ?>">
                                                <div class="subscriber-mailbox-read-actions">
                                                    <button class="button small" type="submit" name="mailbox_action" value="trash">Delete</button>
                                                    <button class="button small" type="submit" name="mailbox_action" value="archive">Archive</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    <script>
                        (function () {
                            const mailbox = document.getElementById('portfolioSubscriberMailbox');
                            if (!mailbox) return;
                            const unreadBadge = document.getElementById('mailboxUnreadBadge');
                            const unreadLiveStatus = document.getElementById('mailboxUnreadLiveStatus');
                            let displayedUnreadCount = unreadBadge ? (parseInt(unreadBadge.getAttribute('data-unread-count'), 10) || 0) : 0;
                            let unreadPollPending = false;
                            async function refreshUnreadBadge() {
                                if (unreadPollPending || document.visibilityState === 'hidden') return;
                                unreadPollPending = true;
                                const unreadUrl = new URL(window.location.href);
                                unreadUrl.search = '';
                                unreadUrl.hash = '';
                                unreadUrl.searchParams.set('mailbox_unread_count', '1');
                                try {
                                    const response = await fetch(unreadUrl.href, {
                                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                        cache: 'no-store'
                                    });
                                    if (!response.ok) throw new Error('Unread message check failed (HTTP ' + response.status + ').');
                                    const result = await response.json();
                                    if (!Number.isInteger(result.unread) || result.unread < 0) {
                                        throw new Error('The unread message check returned an invalid count.');
                                    }
                                    const previousCount = displayedUnreadCount;
                                    displayedUnreadCount = result.unread;
                                    if (unreadBadge) {
                                        unreadBadge.textContent = result.unread > 99 ? '99+' : String(result.unread);
                                        unreadBadge.setAttribute('data-unread-count', String(result.unread));
                                        unreadBadge.setAttribute('aria-label', result.unread + ' unread messages');
                                        unreadBadge.title = result.unread + ' unread message' + (result.unread === 1 ? '' : 's');
                                        unreadBadge.hidden = result.unread === 0;
                                        if (result.unread > previousCount) {
                                            unreadBadge.classList.remove('has-new-mail');
                                            void unreadBadge.offsetWidth;
                                            unreadBadge.classList.add('has-new-mail');
                                            if (unreadLiveStatus) {
                                                unreadLiveStatus.textContent = 'New mail: ' + result.unread + ' unread message' + (result.unread === 1 ? '' : 's') + '.';
                                            }
                                        } else if (result.unread === 0 && unreadLiveStatus) {
                                            unreadLiveStatus.textContent = '';
                                        }
                                    }
                                } catch (pollError) {
                                    const errorMessage = pollError instanceof Error ? pollError.message : 'Unread message notification could not be refreshed.';
                                    if (unreadLiveStatus && unreadLiveStatus.textContent !== errorMessage) {
                                        unreadLiveStatus.textContent = errorMessage;
                                    }
                                    console.error('Could not refresh the mailbox unread count.', pollError);
                                } finally {
                                    unreadPollPending = false;
                                }
                            }
                            refreshUnreadBadge();
                            window.setInterval(refreshUnreadBadge, 30000);
                            document.addEventListener('visibilitychange', refreshUnreadBadge);
                            function renderPagination(root) {
                                root.querySelectorAll('[data-pagination-controls]').forEach(function (pager) {
                                    const table = document.getElementById(pager.getAttribute('data-pager-for'));
                                    if (!table || !root.contains(table)) return;
                                    const rows = Array.from(table.querySelectorAll('tbody [data-pagination-row]'));
                                    const filteredRows = rows.filter(function (row) {
                                        return row.getAttribute('data-search-match') !== 'false';
                                    });
                                    const sizeSelect = pager.querySelector('[data-page-size]');
                                    const pageSize = Math.max(1, parseInt(sizeSelect.value, 10) || 10);
                                    const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                                    let page = Math.max(1, parseInt(pager.getAttribute('data-current-page'), 10) || 1);
                                    page = Math.min(page, pageCount);
                                    pager.setAttribute('data-current-page', String(page));
                                    const firstIndex = (page - 1) * pageSize;
                                    const lastIndex = Math.min(firstIndex + pageSize, filteredRows.length);
                                    const currentRows = filteredRows.slice(firstIndex, lastIndex);
                                    const currentSet = new Set(currentRows);
                                    rows.forEach(function (row) {
                                        row.hidden = !currentSet.has(row);
                                    });
                                    const summary = pager.querySelector('[data-page-summary]');
                                    if (summary) {
                                        summary.textContent = filteredRows.length === 0
                                            ? '0 of 0'
                                            : (firstIndex + 1) + '-' + lastIndex + ' of ' + filteredRows.length;
                                    }
                                    const previous = pager.querySelector('[data-page-previous]');
                                    const next = pager.querySelector('[data-page-next]');
                                    if (previous) previous.disabled = page <= 1;
                                    if (next) next.disabled = page >= pageCount;
                                    const selectAll = root.querySelector('#mailboxSelectAll');
                                    if (selectAll && table.id === 'mailboxMessagesTable') {
                                        const checks = currentRows.map(function (row) {
                                            return row.querySelector('input[name="message_ids[]"]');
                                        }).filter(Boolean);
                                        const selectedCount = checks.filter(function (checkbox) {
                                            return checkbox.checked;
                                        }).length;
                                        selectAll.checked = checks.length > 0 && selectedCount === checks.length;
                                        selectAll.indeterminate = selectedCount > 0 && selectedCount < checks.length;
                                    }
                                });
                            }
                            function initializePagination(root) {
                                if (!root || root.getAttribute('data-pagination-ready') === 'true') {
                                    if (root) renderPagination(root);
                                    return;
                                }
                                root.setAttribute('data-pagination-ready', 'true');
                                root.addEventListener('change', function (event) {
                                    const pageSize = event.target.closest('[data-page-size]');
                                    if (pageSize) {
                                        const pager = pageSize.closest('[data-pagination-controls]');
                                        if (pager) pager.setAttribute('data-current-page', '1');
                                        renderPagination(root);
                                    }
                                });
                                root.addEventListener('click', function (event) {
                                    const button = event.target.closest('[data-page-previous], [data-page-next]');
                                    if (!button || button.disabled) return;
                                    const pager = button.closest('[data-pagination-controls]');
                                    if (!pager) return;
                                    const page = parseInt(pager.getAttribute('data-current-page'), 10) || 1;
                                    pager.setAttribute('data-current-page', String(page + (button.matches('[data-page-next]') ? 1 : -1)));
                                    renderPagination(root);
                                });
                                renderPagination(root);
                            }
                            const subscriberInfo = document.getElementById('portfolio-subscriber-info');
                            initializePagination(subscriberInfo);
                            initializePagination(mailbox);
                            const subscriberNavDropdown = document.getElementById('subscriber-admin-dropdown');
                            function openSubscriberNavForHash() {
                                if (subscriberNavDropdown &&
                                    (window.location.hash === '#portfolio-subscriber-info' || window.location.hash === '#portfolioSubscriberMailbox')) {
                                    subscriberNavDropdown.open = true;
                                }
                            }
                            openSubscriberNavForHash();
                            window.addEventListener('hashchange', openSubscriberNavForHash);
                            let mailboxRequestPending = false;
                            function setMailboxStatus(text, isError) {
                                const status = mailbox.querySelector('#mailboxRequestStatus');
                                if (!status) return;
                                status.textContent = text;
                                status.hidden = text === '';
                                status.className = 'subscriber-mailbox-status' + (text === '' ? '' : (isError ? ' error' : ' success'));
                                if (text !== '') status.setAttribute('role', isError ? 'alert' : 'status');
                            }
                            async function loadMailbox(url, options, pushHistory) {
                                if (mailboxRequestPending) return;
                                mailboxRequestPending = true;
                                mailbox.classList.add('is-loading');
                                mailbox.setAttribute('aria-busy', 'true');
                                const previousSearch = mailbox.querySelector('#mailboxSearch');
                                const searchValue = previousSearch ? previousSearch.value : '';
                                const previousPageSize = mailbox.querySelector('[data-pager-for="mailboxMessagesTable"] [data-page-size]');
                                const pageSizeValue = previousPageSize ? previousPageSize.value : '10';
                                try {
                                    const response = await fetch(url, options || { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                    const html = await response.text();
                                    if (!response.ok) throw new Error('Mailbox request failed (HTTP ' + response.status + ').');
                                    const parsedPage = new DOMParser().parseFromString(html, 'text/html');
                                    const nextMailbox = parsedPage.getElementById('portfolioSubscriberMailbox');
                                    if (!nextMailbox) throw new Error('The server response did not contain the mailbox.');
                                    const nextMailboxToggle = parsedPage.querySelector('[data-mailbox-toggle]');
                                    mailbox.innerHTML = nextMailbox.innerHTML;
                                    mailbox.hidden = nextMailbox.hidden;
                                    const nextPageSize = mailbox.querySelector('[data-pager-for="mailboxMessagesTable"] [data-page-size]');
                                    if (nextPageSize) nextPageSize.value = pageSizeValue;
                                    renderPagination(mailbox);
                                    document.querySelectorAll('[data-mailbox-toggle]').forEach(function (button) {
                                        if (nextMailboxToggle) button.textContent = nextMailboxToggle.textContent;
                                        button.setAttribute('aria-expanded', mailbox.hidden ? 'false' : 'true');
                                    });
                                    mailbox.classList.remove('is-loading');
                                    mailbox.setAttribute('aria-busy', 'false');
                                    const restoredSearch = mailbox.querySelector('#mailboxSearch');
                                    if (restoredSearch && searchValue !== '') {
                                        restoredSearch.value = searchValue;
                                        restoredSearch.dispatchEvent(new Event('input', { bubbles: true }));
                                    }
                                    if (pushHistory) {
                                        let nextUrl = url;
                                        if (options && options.method === 'POST') {
                                            const selectedFolder = mailbox.querySelector('.subscriber-folder.active');
                                            const currentComposeLink = mailbox.querySelector('.subscriber-mailbox-compose');
                                            nextUrl = selectedFolder ? selectedFolder.href : (currentComposeLink ? currentComposeLink.href : url);
                                        }
                                        window.history.pushState({ mailbox: true }, '', nextUrl);
                                    }
                                    const activeFolder = mailbox.querySelector('.subscriber-folder.active');
                                    const folderName = activeFolder ? activeFolder.querySelector('span').textContent : '';
                                    const heading = document.getElementById('portfolio-subscribers-title');
                                    if (heading) heading.focus({ preventScroll: true });
                                    if (folderName) mailbox.setAttribute('aria-label', folderName + ' mailbox');
                                    refreshUnreadBadge();
                                } catch (requestError) {
                                    mailbox.classList.remove('is-loading');
                                    mailbox.setAttribute('aria-busy', 'false');
                                    setMailboxStatus(requestError instanceof Error ? requestError.message : 'The mailbox could not be updated. Try again.', true);
                                } finally {
                                    mailboxRequestPending = false;
                                }
                            }
                            document.querySelectorAll('[data-mailbox-toggle]').forEach(function (button) {
                                button.addEventListener('click', function () {
                                    mailbox.hidden = !mailbox.hidden;
                                    button.setAttribute('aria-expanded', mailbox.hidden ? 'false' : 'true');
                                    if (!mailbox.hidden) mailbox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                });
                            });
                            mailbox.addEventListener('input', function (event) {
                                const search = event.target.closest('#mailboxSearch');
                                if (!search) return;
                                const query = search.value.trim().toLowerCase();
                                mailbox.querySelectorAll('[data-mailbox-search]').forEach(function (row) {
                                    row.setAttribute('data-search-match', String(query === '' || row.getAttribute('data-mailbox-search').includes(query)));
                                });
                                const pager = mailbox.querySelector('[data-pager-for="mailboxMessagesTable"]');
                                if (pager) pager.setAttribute('data-current-page', '1');
                                renderPagination(mailbox);
                                const emptyState = mailbox.querySelector('[data-mailbox-no-search-results]');
                                if (emptyState) {
                                    emptyState.hidden = query === '' ||
                                        Array.from(mailbox.querySelectorAll('[data-mailbox-search]')).some(function (row) {
                                            return row.getAttribute('data-search-match') !== 'false';
                                        });
                                }
                            });
                            mailbox.addEventListener('change', function (event) {
                                if (event.target.matches('#mailboxSelectAll')) {
                                    const table = mailbox.querySelector('#mailboxMessagesTable');
                                    if (!table) return;
                                    table.querySelectorAll('tbody [data-pagination-row]:not([hidden]) input[name="message_ids[]"]').forEach(function (checkbox) {
                                        checkbox.checked = event.target.checked;
                                    });
                                    renderPagination(mailbox);
                                } else if (event.target.matches('input[name="message_ids[]"]')) {
                                    renderPagination(mailbox);
                                }
                            });
                            mailbox.addEventListener('click', function (event) {
                                const row = event.target.closest('.subscriber-mailbox-row');
                                if (row && !event.target.closest('input,button,a,label')) {
                                    const openButton = row.querySelector('button[name="open_contact_message"]');
                                    if (openButton) openButton.click();
                                    return;
                                }
                                const recipientButton = event.target.closest('[data-select-mailbox-recipient]');
                                const recipient = mailbox.querySelector('#subscriberEmailTo');
                                if (recipientButton && recipient) recipient.value = recipientButton.getAttribute('data-select-mailbox-recipient');
                                const link = event.target.closest('a[href]');
                                if (link && link.closest('#portfolioSubscriberMailbox')) {
                                    const targetUrl = new URL(link.href, window.location.href);
                                    if (targetUrl.origin === window.location.origin &&
                                        targetUrl.pathname === window.location.pathname &&
                                        targetUrl.searchParams.has('contact_folder')) {
                                        event.preventDefault();
                                        mailbox.hidden = false;
                                        document.querySelectorAll('[data-mailbox-toggle]').forEach(function (button) {
                                            button.setAttribute('aria-expanded', 'true');
                                        });
                                        loadMailbox(targetUrl.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, true);
                                    }
                                }
                            });
                            mailbox.addEventListener('submit', function (event) {
                                const form = event.target;
                                if (!form.matches('#mailboxMessagesForm, #mailboxReadActionsForm, .subscriber-compose-form')) return;
                                const submitter = event.submitter;
                                const isMailboxAction = submitter && (
                                    submitter.name === 'open_contact_message' ||
                                    submitter.name === 'mailbox_action' ||
                                    submitter.name === 'send_subscriber_email' ||
                                    submitter.name === 'save_subscriber_draft'
                                );
                                if (!isMailboxAction) return;
                                event.preventDefault();
                                const formData = new FormData(form, submitter);
                                loadMailbox(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }, true);
                            });
                            window.addEventListener('popstate', function () {
                                if (!window.location.pathname.endsWith('/admin_dashboard.php')) return;
                                mailbox.hidden = false;
                                document.querySelectorAll('[data-mailbox-toggle]').forEach(function (button) {
                                    button.setAttribute('aria-expanded', 'true');
                                });
                                loadMailbox(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, false);
                            });
                        }());
                    </script>
                </div>
            </section>

            <section class="setting-card portfolio-card" id="portfolio_header" aria-labelledby="portfolio-header-title">
                <div class="card-head"><div><h2 id="portfolio-header-title">Portfolio header panel</h2><p>Edit the name shown in the logo and upload or remove a logo image.</p></div></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <div class="portfolio-fields">
                            <div><label for="header_name">Profile name</label><input id="header_name" name="header_name" type="text" maxlength="100" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['name']); ?>" required></div>
                            <div><label for="header_brand_name">Logo name</label><input id="header_brand_name" name="header_brand_name" type="text" maxlength="100" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['brand_name']); ?>" required></div>
                            <div><label for="header_initials">Text logo fallback</label><input id="header_initials" name="header_initials" type="text" maxlength="10" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['initials']); ?>" required></div>
                            <div><label for="header_role">Role</label><input id="header_role" name="header_role" type="text" maxlength="100" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['role']); ?>" required></div>
                            <div><label for="header_location">Location</label><input id="header_location" name="header_location" type="text" maxlength="120" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['location']); ?>" required></div>
                            <div><label for="header_email">Email</label><input id="header_email" name="header_email" type="email" maxlength="254" value="<?php echo blogDashboardEscape($portfolioHeaderPanel['email']); ?>" required></div>
                            <div class="field-wide">
                                <label for="logo_image">Logo image (JPEG, PNG, or WebP; max 2 MB)</label>
                                <?php if (!empty($portfolioHeaderPanel['logo_image'])): ?>
                                <img class="logo-preview" src="<?php echo blogDashboardEscape($portfolioHeaderPanel['logo_image']); ?>" alt="Current portfolio logo">
                                <label><input type="checkbox" name="remove_logo_image" value="1"> Remove current logo and use text initials</label>
                                <?php endif; ?>
                                <input id="logo_image" name="logo_image" type="file" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                        <style>
                            #portfolio_header .header-feature-grids{display:grid;gap:14px}
                            #portfolio_header .header-feature-grid-editor-card{position:relative;padding:14px;border:1px solid #dce4e8;border-left:4px solid #17a2b8;border-radius:8px;background:#fff;box-shadow:0 4px 14px rgb(21 51 65 / 5%);animation:headerGridEnter .35s cubic-bezier(.2,.8,.2,1) both;transition:opacity .2s,transform .2s,border-color .2s,background .2s}
                            #portfolio_header .header-feature-grid-editor-card[data-visible="false"]{border-left-color:#adb5bd;background:#f7f8f9}
                            #portfolio_header .header-feature-grid-editor-card.is-removing{opacity:0;transform:translateX(18px) scale(.98)}
                            #portfolio_header .header-feature-grid-editor-card>h3{margin:0 0 12px;color:#343a40;font-size:15px}
                            #portfolio_header .header-feature-grid-preview{display:grid;grid-template-columns:minmax(120px,.8fr) minmax(0,1.2fr);gap:14px;align-items:center;margin-top:12px;padding:12px;border:1px solid #e1e7eb;border-radius:8px;background:linear-gradient(120deg,#f7fbfc,#fff)}
                            #portfolio_header .header-feature-grid-preview-media{min-height:110px;display:grid;place-items:center;overflow:hidden;border-radius:6px;background:#e9eef1}
                            #portfolio_header .header-feature-grid-preview-media img,#portfolio_header .header-feature-grid-preview-media video{width:100%;height:100%;max-height:180px;object-fit:contain}
                            #portfolio_header .header-feature-grid-preview-media a,#portfolio_header .header-feature-grid-preview-media span{padding:10px;color:#176b78;font-size:12px;font-weight:700;text-decoration:none}
                            #portfolio_header .header-feature-grid-preview-copy{min-width:0}
                            #portfolio_header .header-feature-grid-preview-copy strong{display:block;color:#343a40;font-size:14px}
                            #portfolio_header .header-feature-grid-preview-copy p{margin:6px 0;color:#6c757d;font-size:12px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
                            #portfolio_header .header-feature-grid-preview-copy span{display:inline-flex;padding:3px 8px;border-radius:999px;color:#176b78;background:#e4f8fa;font-size:10px;font-weight:750}
                            #portfolio_header .header-feature-grid-editor-card[data-visible="false"] .header-feature-grid-preview-copy span{color:#6c757d;background:#e9ecef}
                            #portfolio_header #add-header-feature-grid{transition:transform .2s,box-shadow .2s}
                            #portfolio_header #add-header-feature-grid:hover{transform:translateY(-2px);box-shadow:0 7px 18px rgb(23 162 184 / 20%)}
                            @keyframes headerGridEnter{from{opacity:0;transform:translateY(12px) scale(.985)}to{opacity:1;transform:translateY(0) scale(1)}}
                            @media(max-width:600px){#portfolio_header .header-feature-grid-preview{grid-template-columns:1fr}}
                            @media(prefers-reduced-motion:reduce){#portfolio_header .header-feature-grid-editor-card,#portfolio_header #add-header-feature-grid{animation:none;transition:none}}
                        </style>
                        <section class="home-fields-section" aria-labelledby="header-feature-grids-title">
                            <h3 id="header-feature-grids-title">Header content grids</h3>
                            <p class="form-help">Add image, MP4/WebM video, or PDF content with a title and description. Upload main media (images up to 8 MB, video up to 50 MB, PDF up to 15 MB) or enter a safe path/HTTPS URL; add an optional poster. For extra captioned images, enter one safe path or HTTPS URL and caption per line, separated by a vertical bar.</p>
                            <input type="hidden" id="header_feature_grids_json" name="header_feature_grids_json" value="<?php echo blogDashboardEscape(blogDashboardJson($settings['portfolio_header']['feature_grids'] ?? [])); ?>">
                            <div class="about-content-list header-feature-grids" id="header-feature-grids-editor"></div>
                            <div class="menu-editor-actions">
                                <button class="button secondary small" type="button" id="add-header-feature-grid">Add new header grid</button>
                                <span class="hint">Leave the parent blank for a top-level link. Enter a menu label or Menu &gt; sublink to add the grid under an existing or new dropdown.</span>
                            </div>
                        </section>
                        <script>
                        (() => {
                            const form = document.querySelector('#portfolio_header form');
                            const editor = document.getElementById('header-feature-grids-editor');
                            const jsonInput = document.getElementById('header_feature_grids_json');
                            if (!form || !editor || !jsonInput) return;
                            let initialGrids = [];
                            try {
                                initialGrids = JSON.parse(jsonInput.value || '[]');
                                if (!Array.isArray(initialGrids)) throw new Error('Stored content grids are not an array.');
                            } catch (error) {
                                window.alert('Saved header content grids could not be loaded. Reload the page or check the stored portfolio header settings before saving.');
                                console.error('Could not load saved portfolio header content grids.', error);
                                return;
                            }
                            const field = (labelText, value, type, maxLength, wide = false) => {
                                const label = document.createElement('label');
                                label.textContent = labelText;
                                if (wide) label.classList.add('field-wide');
                                const control = type === 'textarea' ? document.createElement('textarea') : document.createElement('input');
                                if (type !== 'textarea') control.type = type;
                                control.value = typeof value === 'string' ? value : '';
                                control.maxLength = maxLength;
                                control.required = !['Parent menu or path (optional)', 'Poster image URL/path (optional)', 'Additional images (one URL | caption per line)'].includes(labelText);
                                if (type === 'textarea') control.rows = labelText.startsWith('Description') ? 3 : 4;
                                label.append(control);
                                return {label, control};
                            };
                            const addGrid = (grid = {}) => {
                                if (editor.children.length >= 20) {
                                    window.alert('You can add up to 20 content grids.');
                                    return;
                                }
                                const card = document.createElement('article');
                                card.className = 'about-content-item header-feature-grid-editor-card';
                                card.dataset.headerFeatureGrid = '';
                                card.dataset.visible = String(grid.visible !== false);
                                const title = field('Title', grid.title, 'text', 160);
                                const navLabel = field('Navigation label', grid.nav_label || grid.title, 'text', 80);
                                const description = field('Description', grid.description, 'textarea', 2000, true);
                                const mediaTypeLabel = document.createElement('label');
                                mediaTypeLabel.textContent = 'Main media type';
                                const mediaType = document.createElement('select');
                                [['image', 'Image'], ['video', 'Video'], ['pdf', 'PDF document']].forEach(([value, text]) => {
                                    const option = document.createElement('option');
                                    option.value = value;
                                    option.textContent = text;
                                    mediaType.append(option);
                                });
                                mediaType.value = ['image', 'video', 'pdf'].includes(grid.media_type) ? grid.media_type : 'image';
                                mediaTypeLabel.append(mediaType);
                                const mediaUrl = field('Main media URL or safe relative path', grid.media_url, 'text', 1000, true);
                                mediaUrl.control.required = false;
                                const mediaUploadLabel = document.createElement('label');
                                mediaUploadLabel.textContent = 'Upload main media (optional; replaces URL/path)';
                                mediaUploadLabel.classList.add('field-wide');
                                const mediaUpload = document.createElement('input');
                                mediaUpload.type = 'file';
                                mediaUpload.accept = mediaType.value === 'image'
                                    ? 'image/jpeg,image/png,image/gif,image/webp'
                                    : mediaType.value === 'video' ? 'video/mp4,video/webm' : 'application/pdf';
                                mediaUploadLabel.append(mediaUpload);
                                mediaType.addEventListener('change', () => {
                                    mediaUpload.accept = mediaType.value === 'image'
                                        ? 'image/jpeg,image/png,image/gif,image/webp'
                                        : mediaType.value === 'video' ? 'video/mp4,video/webm' : 'application/pdf';
                                    if (mediaPreviewUrl) URL.revokeObjectURL(mediaPreviewUrl);
                                    mediaPreviewUrl = '';
                                    mediaUpload.value = '';
                                    updatePreview();
                                });
                                const poster = field('Poster image URL/path (optional)', grid.poster_url, 'text', 1000, true);
                                const posterUploadLabel = document.createElement('label');
                                posterUploadLabel.textContent = 'Upload poster image (optional; replaces URL/path)';
                                posterUploadLabel.classList.add('field-wide');
                                const posterUpload = document.createElement('input');
                                posterUpload.type = 'file';
                                posterUpload.accept = 'image/jpeg,image/png,image/gif,image/webp';
                                posterUploadLabel.append(posterUpload);
                                const parent = field('Parent menu or path (optional)', grid.parent, 'text', 160);
                                const imagesValue = Array.isArray(grid.images)
                                    ? grid.images.map(image => `${image.src || ''} | ${image.caption || ''}`).join('\n')
                                    : '';
                                const images = field('Additional images (one URL | caption per line)', imagesValue, 'textarea', 11000, true);
                                images.control.required = false;
                                const fields = document.createElement('div');
                                fields.className = 'about-content-item-fields';
                                fields.append(title.label, navLabel.label, mediaTypeLabel, parent.label, description.label, mediaUrl.label, mediaUploadLabel, poster.label, posterUploadLabel, images.label);
                                const actions = document.createElement('div');
                                actions.className = 'about-content-item-actions';
                                const visibilityLabel = document.createElement('label');
                                visibilityLabel.className = 'home-visibility';
                                const visible = document.createElement('input');
                                visible.type = 'checkbox';
                                visible.checked = grid.visible !== false;
                                visibilityLabel.append(visible, document.createTextNode(' Visible to visitors (uncheck to hide)'));
                                const remove = document.createElement('button');
                                remove.type = 'button';
                                remove.className = 'button danger small';
                                remove.textContent = 'Remove grid';
                                let mediaPreviewUrl = '';
                                let posterPreviewUrl = '';
                                remove.addEventListener('click', () => {
                                    card.classList.add('is-removing');
                                    window.setTimeout(() => {
                                        if (mediaPreviewUrl) URL.revokeObjectURL(mediaPreviewUrl);
                                        if (posterPreviewUrl) URL.revokeObjectURL(posterPreviewUrl);
                                        card.remove();
                                    }, 180);
                                });
                                actions.append(visibilityLabel, remove);
                                const heading = document.createElement('h3');
                                heading.textContent = title.control.value || 'New content grid';
                                const preview = document.createElement('section');
                                preview.className = 'header-feature-grid-preview';
                                preview.setAttribute('aria-label', 'Live content grid preview');
                                const previewMedia = document.createElement('div');
                                previewMedia.className = 'header-feature-grid-preview-media';
                                const previewCopy = document.createElement('div');
                                previewCopy.className = 'header-feature-grid-preview-copy';
                                const previewTitle = document.createElement('strong');
                                const previewDescription = document.createElement('p');
                                const previewStatus = document.createElement('span');
                                previewCopy.append(previewTitle, previewDescription, previewStatus);
                                preview.append(previewMedia, previewCopy);
                                const updatePreview = () => {
                                    const currentType = mediaType.value;
                                    const mediaSource = mediaPreviewUrl || mediaUrl.control.value.trim();
                                    const posterSource = posterPreviewUrl || poster.control.value.trim();
                                    previewTitle.textContent = title.control.value.trim() || 'New content grid';
                                    previewDescription.textContent = description.control.value.trim() || 'Your description will appear here.';
                                    const additionalImageCount = images.control.value.split(/\r\n|\r|\n/).filter(line => line.trim() !== '').length;
                                    previewStatus.textContent = `${visible.checked ? 'Visible on portfolio' : 'Hidden from portfolio'} · ${navLabel.control.value.trim() || 'Unlinked'} · ${currentType.toUpperCase()} · ${additionalImageCount} extra image${additionalImageCount === 1 ? '' : 's'}`;
                                    card.dataset.visible = String(visible.checked);
                                    previewMedia.replaceChildren();
                                    if (currentType === 'image' && mediaSource) {
                                        const image = document.createElement('img');
                                        image.src = mediaSource;
                                        image.alt = '';
                                        previewMedia.append(image);
                                    } else if (currentType === 'video' && mediaSource) {
                                        const video = document.createElement('video');
                                        video.controls = true;
                                        video.preload = 'metadata';
                                        if (posterSource) video.poster = posterSource;
                                        const source = document.createElement('source');
                                        source.src = mediaSource;
                                        video.append(source);
                                        previewMedia.append(video);
                                    } else if (currentType === 'pdf') {
                                        if (posterSource) {
                                            const posterImage = document.createElement('img');
                                            posterImage.src = posterSource;
                                            posterImage.alt = 'PDF poster preview';
                                            previewMedia.append(posterImage);
                                        }
                                        if (mediaSource) {
                                            const pdfLabel = document.createElement('span');
                                            pdfLabel.textContent = 'PDF document ready';
                                            previewMedia.append(pdfLabel);
                                        }
                                    }
                                    if (previewMedia.childElementCount === 0) {
                                        const empty = document.createElement('span');
                                        empty.textContent = 'Choose or link media to preview';
                                        previewMedia.append(empty);
                                    }
                                };
                                title.control.addEventListener('input', () => {
                                    heading.textContent = title.control.value.trim() || 'New content grid';
                                    if (!navLabel.control.dataset.edited) navLabel.control.value = title.control.value;
                                    updatePreview();
                                });
                                navLabel.control.addEventListener('input', () => { navLabel.control.dataset.edited = 'true'; updatePreview(); });
                                [description.control, mediaUrl.control, poster.control, images.control].forEach(control => control.addEventListener('input', updatePreview));
                                [mediaType, visible].forEach(control => control.addEventListener('change', updatePreview));
                                mediaUpload.addEventListener('change', () => {
                                    if (mediaPreviewUrl) URL.revokeObjectURL(mediaPreviewUrl);
                                    mediaPreviewUrl = mediaUpload.files[0] ? URL.createObjectURL(mediaUpload.files[0]) : '';
                                    updatePreview();
                                });
                                posterUpload.addEventListener('change', () => {
                                    if (posterPreviewUrl) URL.revokeObjectURL(posterPreviewUrl);
                                    posterPreviewUrl = posterUpload.files[0] ? URL.createObjectURL(posterUpload.files[0]) : '';
                                    updatePreview();
                                });
                                card.append(heading, fields, preview, actions);
                                card._gridFields = {title:title.control, navLabel:navLabel.control, description:description.control, mediaType, mediaUrl:mediaUrl.control, mediaUpload, poster:poster.control, posterUpload, parent:parent.control, images:images.control, visible};
                                editor.append(card);
                                updatePreview();
                            };
                            initialGrids.forEach(addGrid);
                            document.getElementById('add-header-feature-grid').addEventListener('click', () => {
                                addGrid();
                                editor.lastElementChild?.querySelector('input')?.focus();
                            });
                            form.addEventListener('submit', event => {
                                const cards = Array.from(editor.querySelectorAll('[data-header-feature-grid]'));
                                const usedIds = new Set();
                                let invalidImageLine = false;
                                let missingMainMedia = false;
                                cards.forEach((card, index) => {
                                    const values = card._gridFields;
                                    values.mediaUpload.name = `header_feature_grid_media_files[${index}]`;
                                    values.posterUpload.name = `header_feature_grid_poster_files[${index}]`;
                                    if (!values.mediaUrl.value.trim() && values.mediaUpload.files.length === 0) missingMainMedia = true;
                                });
                                if (missingMainMedia) {
                                    event.preventDefault();
                                    window.alert('For each content grid, enter a media URL/path or choose a main media upload.');
                                    return;
                                }
                                const grids = cards.map(card => {
                                    const values = card._gridFields;
                                    const slug = values.title.value.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) || 'content';
                                    let id = slug;
                                    let suffix = 2;
                                    while (usedIds.has(id)) id = `${slug.slice(0, 56)}-${suffix++}`;
                                    usedIds.add(id);
                                    const images = values.images.value.split(/\r\n|\r|\n/).map(line => {
                                        if (!line.trim()) return null;
                                        const separator = line.indexOf('|');
                                        if (separator < 0) {
                                            invalidImageLine = true;
                                            return null;
                                        }
                                        const image = {src:line.slice(0, separator).trim(), caption:line.slice(separator + 1).trim()};
                                        if (!image.src || !image.caption) invalidImageLine = true;
                                        return image;
                                    }).filter(Boolean);
                                    return {
                                        id,
                                        title:values.title.value.trim(),
                                        description:values.description.value.trim(),
                                        media_type:values.mediaType.value,
                                        media_url:values.mediaUrl.value.trim(),
                                        poster_url:values.poster.value.trim(),
                                        nav_label:values.navLabel.value.trim(),
                                        parent:values.parent.value.trim(),
                                        images,
                                        visible:values.visible.checked
                                    };
                                });
                                if (grids.length > 20 || invalidImageLine || grids.some(grid => {
                                    const parentParts = grid.parent.split('>').filter(part => part.trim() !== '');
                                    return grid.images.length > 8 || parentParts.length > 2 || (grid.parent.trim() !== '' && parentParts.length === 0);
                                })) {
                                    event.preventDefault();
                                    window.alert('Use up to 20 grids, 8 additional images per grid, and at most one parent/sub-link level. Each non-empty image line must include a URL and caption separated by |.');
                                    return;
                                }
                                jsonInput.value = JSON.stringify(grids);
                            });
                        })();
                        </script>
                        <div class="card-footer"><span class="hint">Logo image is optional; the initials are used as a fallback.</span><button class="button" type="submit" name="save_portfolio_header">Save header</button></div>
                    </form>
                </div>
            </section>

            <section class="setting-card portfolio-card" id="portfolio_home" aria-labelledby="portfolio-home-title">
                <div class="card-head">
                    <div><h2 id="portfolio-home-title">Portfolio home page</h2><p>Edit, remove, or hide home-page content and build the main navigation.</p></div>
                    <nav class="home-editor-shortcuts" aria-label="Home page editor sections">
                        <a href="#home-hero-settings">Headline &amp; roles</a>
                        <a href="#home-intro-settings">Introduction</a>
                        <a href="#home-portrait-settings">Flip image &amp; bio</a>
                        <a href="#home-background-settings">Background image</a>
                        <a href="#home-actions-settings">Buttons &amp; subscribe</a>
                        <a href="#home-stats-settings">Experience &amp; stats</a>
                        <a href="#home-menu-settings">Navigation</a>
                        <a href="#portfolio_social_posts">Social &amp; Post Widgets</a>
                    </nav>
                </div>
                <div class="card-body">
                    <form method="post" id="portfolio-home-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <div class="portfolio-fields">
                            <section class="home-live-preview field-wide" aria-label="Live home page preview">
                                <img class="home-live-preview-background" id="home-preview-background" src="<?php echo blogDashboardEscape($portfolioHomePanel['background_url']); ?>" alt="">
                                <img id="home-preview-image" src="<?php echo blogDashboardEscape($portfolioHomePanel['portrait_url']); ?>" alt="">
                                <div class="home-live-preview-copy">
                                    <span>Live preview · updates as you edit</span>
                                    <h3 id="home-preview-headline"><?php echo blogDashboardEscape($portfolioHomePanel['headline']); ?></h3>
                                    <p id="home-preview-intro"><?php echo blogDashboardEscape($portfolioHomePanel['intro']); ?></p>
                                    <div class="home-live-preview-roles" id="home-preview-roles" aria-label="Role labels"></div>
                                    <div class="home-live-preview-actions" id="home-preview-actions" aria-label="Homepage buttons"></div>
                                    <div class="home-live-preview-stats" id="home-preview-stats" aria-label="Visible metric cards"></div>
                                </div>
                            </section>
                            <div class="home-fields-section" id="home-hero-settings"><h3>Headline &amp; animated role labels</h3><p class="form-help">Remove role lines to delete them. Uncheck “Show on portfolio” to hide an element publicly.</p></div>
                            <div class="field-wide">
                                <label for="home_headline">Headline</label><input id="home_headline" name="home_headline" type="text" maxlength="120" value="<?php echo blogDashboardEscape($portfolioHomePanel['headline']); ?>">
                                <label class="home-visibility"><input type="checkbox" name="home_show_headline" value="1" <?php echo $portfolioHomePanel['visibility']['headline'] ? 'checked' : ''; ?>> Show on portfolio</label>
                            </div>
                            <div class="field-wide">
                                <label for="home_roles">Animated role labels (one per line, up to 8)</label><textarea id="home_roles" name="home_roles"><?php echo blogDashboardEscape(implode("\n", $portfolioHomePanel['roles'])); ?></textarea>
                                <label class="home-visibility"><input type="checkbox" name="home_show_roles" value="1" <?php echo $portfolioHomePanel['visibility']['roles'] ? 'checked' : ''; ?>> Show on portfolio</label>
                            </div>
                            <div class="field-wide" id="home-intro-settings">
                                <label for="home_intro">Introduction</label><textarea id="home_intro" name="home_intro"><?php echo blogDashboardEscape($portfolioHomePanel['intro']); ?></textarea>
                                <label class="home-visibility"><input type="checkbox" name="home_show_intro" value="1" <?php echo $portfolioHomePanel['visibility']['intro'] ? 'checked' : ''; ?>> Show on portfolio</label>
                            </div>
                            <div class="home-fields-section" id="home-portrait-settings"><h3>Flip image &amp; biography</h3><p class="form-help">Edit the image on the front and all text shown when a visitor flips it.</p></div>
                            <div>
                                <label for="home_portrait_url">Portrait URL or relative path</label><input id="home_portrait_url" name="home_portrait_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($portfolioHomePanel['portrait_url']); ?>" <?php echo $portfolioHomePanel['visibility']['portrait'] ? 'required' : ''; ?>>
                                <label class="home-visibility"><input type="checkbox" name="home_show_portrait" value="1" <?php echo $portfolioHomePanel['visibility']['portrait'] ? 'checked' : ''; ?>> Show portrait card on portfolio</label>
                            </div>
                            <div class="field-wide" id="home-background-settings">
                                <label for="home_background_url">Hero background image URL or relative path</label><input id="home_background_url" name="home_background_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($portfolioHomePanel['background_url']); ?>">
                                <label class="home-visibility"><input type="checkbox" name="home_show_background" value="1" <?php echo $portfolioHomePanel['visibility']['background'] ? 'checked' : ''; ?>> Show animated background image on portfolio</label>
                                <p class="form-help">Use a safe relative image path or HTTP(S) URL. Clear the path or switch off visibility to use the gradient background only.</p>
                            </div>
                            <div><label for="home_portrait_bio_name">Name on biography side</label><input id="home_portrait_bio_name" name="home_portrait_bio_name" type="text" maxlength="100" value="<?php echo blogDashboardEscape($portfolioHomePanel['portrait_bio_name']); ?>" required></div>
                            <div><label for="home_portrait_bio_kicker">Biography heading</label><input id="home_portrait_bio_kicker" name="home_portrait_bio_kicker" type="text" maxlength="120" value="<?php echo blogDashboardEscape($portfolioHomePanel['portrait_bio_kicker']); ?>" required></div>
                            <div><label for="home_portrait_skills">Biography skills line</label><input id="home_portrait_skills" name="home_portrait_skills" type="text" maxlength="300" value="<?php echo blogDashboardEscape($portfolioHomePanel['portrait_skills']); ?>" required></div>
                            <div class="field-wide"><label for="home_portrait_bio">Biography text</label><textarea id="home_portrait_bio" name="home_portrait_bio" maxlength="1500" required><?php echo blogDashboardEscape($portfolioHomePanel['portrait_bio']); ?></textarea></div>
                            <div class="home-fields-section" id="home-actions-settings"><h3>Hero buttons</h3><p class="form-help">Edit button labels and destinations. The subscription button opens an email sign-up popup. Uncheck a button to hide it from visitors.</p></div>
                            <div class="home-actions-editor">
                                <div class="home-action-editor">
                                    <h3>Hire Me</h3>
                                    <label for="home_hire_label">Button label</label><input id="home_hire_label" name="home_hire_label" type="text" maxlength="80" value="<?php echo blogDashboardEscape($portfolioHomePanel['hire_label'] ?? 'Hire Me'); ?>" required>
                                    <label for="home_hire_url">Button destination</label><input id="home_hire_url" name="home_hire_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($portfolioHomePanel['hire_url']); ?>" required>
                                    <label class="home-visibility"><input type="checkbox" name="home_show_hire_button" value="1" <?php echo $portfolioHomePanel['visibility']['hire_button'] ? 'checked' : ''; ?>> Show on portfolio</label>
                                </div>
                                <div class="home-action-editor">
                                    <h3>Download CV</h3>
                                    <label for="home_cv_label">Button label</label><input id="home_cv_label" name="home_cv_label" type="text" maxlength="80" value="<?php echo blogDashboardEscape($portfolioHomePanel['cv_label'] ?? 'Download CV'); ?>" required>
                                    <label for="home_cv_url">CV URL or relative path</label><input id="home_cv_url" name="home_cv_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($portfolioHomePanel['cv_url']); ?>" required>
                                    <label class="home-visibility"><input type="checkbox" name="home_show_cv_button" value="1" <?php echo $portfolioHomePanel['visibility']['cv_button'] ? 'checked' : ''; ?>> Show on portfolio</label>
                                </div>
                                <div class="home-action-editor">
                                    <h3>Certificates</h3>
                                    <label for="home_certificates_label">Button label</label><input id="home_certificates_label" name="home_certificates_label" type="text" maxlength="80" value="<?php echo blogDashboardEscape($portfolioHomePanel['certificates_label'] ?? 'Certificates'); ?>" required>
                                    <label for="home_certificates_url">Button destination</label><input id="home_certificates_url" name="home_certificates_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($portfolioHomePanel['certificates_url']); ?>" required>
                                    <label class="home-visibility"><input type="checkbox" name="home_show_certificates_button" value="1" <?php echo $portfolioHomePanel['visibility']['certificates_button'] ? 'checked' : ''; ?>> Show on portfolio</label>
                                </div>
                                <div class="home-action-editor">
                                    <h3>Subscription</h3>
                                    <label for="home_subscription_label">Button label</label><input id="home_subscription_label" name="home_subscription_label" type="text" maxlength="80" value="<?php echo blogDashboardEscape($portfolioHomePanel['subscription_label']); ?>" required>
                                    <p class="form-help">The button opens the email subscription popup on the portfolio.</p>
                                    <label class="home-visibility"><input type="checkbox" name="home_show_subscription_button" value="1" <?php echo $portfolioHomePanel['visibility']['subscription_button'] ? 'checked' : ''; ?>> Show on portfolio</label>
                                </div>
                            </div>
                            <div class="home-fields-section" id="home-stats-settings"><h3>Experience, subscribers, projects &amp; users</h3><p class="form-help">Edit the four metric cards, add up to eight, or remove a card. Uncheck its visibility to keep it saved but hide it from the public portfolio.</p></div>
                            <div class="home-stats-editor" id="home-stats-editor">
                                <?php foreach ($portfolioHomePanel['stats'] as $index => $stat): ?>
                                <div class="home-stat-editor">
                                    <label>Metric label<input type="text" data-stat-label maxlength="80" value="<?php echo blogDashboardEscape($stat['label']); ?>" required></label>
                                    <label>Number<input type="number" data-stat-value min="0" max="999999" step="1" value="<?php echo (int) $stat['value']; ?>" required></label>
                                    <label>Suffix<input type="text" data-stat-suffix maxlength="12" value="<?php echo blogDashboardEscape($stat['suffix']); ?>"></label>
                                    <button class="button danger small" type="button" data-remove-stat aria-label="Remove metric <?php echo blogDashboardEscape($stat['label']); ?>">Remove</button>
                                    <label class="home-visibility"><input type="checkbox" data-stat-visible value="1" <?php echo $stat['visible'] ? 'checked' : ''; ?>> Show on portfolio</label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="field-wide menu-editor-actions"><button class="button secondary small" type="button" id="add-home-stat">Add metric</button></div>
                        </div>
                        <input type="hidden" name="home_stats" id="home_stats">
                        <div class="home-fields-section" id="home-menu-settings"><h3>Main navigation links</h3></div>
                        <p class="form-help">Add regular links or give any link sublinks to turn it into a dropdown. Use section links such as #about or full HTTPS URLs.</p>
                        <div class="menu-editor" id="menu-editor"></div>
                        <div class="menu-editor-actions"><button class="button secondary small" type="button" id="add-menu-link">Add navigation link</button></div>
                        <input type="hidden" name="home_menu_links" id="home_menu_links">
                        <div class="card-footer"><span class="hint">Changes are published on the portfolio after saving.</span><button class="button" type="submit" name="save_portfolio_home">Save home page</button></div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_advertisements" aria-labelledby="portfolio-advertisements-title">
                <?php
                    $requestedAdvertisementId = filter_var($_GET['advertisement_edit'] ?? null, FILTER_VALIDATE_INT);
                    if ($requestedAdvertisementId !== false && $requestedAdvertisementId > 0 && !isset($_POST['save_portfolio_advertisement'])) {
                        $requestedAdvertisementFound = false;
                        foreach ($portfolioAdvertisements as $advertisement) {
                            if ((int) $advertisement['ad_id'] !== $requestedAdvertisementId) {
                                continue;
                            }
                            $advertisementForm = [
                                'ad_id' => (int) $advertisement['ad_id'],
                                'brand_name' => (string) $advertisement['brand_name'],
                                'product_name' => (string) $advertisement['product_name'],
                                'description' => (string) $advertisement['description'],
                                'brand_logo_url' => (string) ($advertisement['brand_logo_url'] ?? ''),
                                'product_image_url' => (string) $advertisement['product_image_url'],
                                'poster_image_url' => (string) ($advertisement['poster_image_url'] ?? ''),
                                'video_file_url' => (string) ($advertisement['video_file_url'] ?? ''),
                                'pdf_file_url' => (string) ($advertisement['pdf_file_url'] ?? ''),
                                'target_url' => (string) ($advertisement['target_url'] ?? ''),
                                'cta_label' => (string) $advertisement['cta_label'],
                                'is_pinned' => !empty($advertisement['is_pinned'])
                            ];
                            $requestedAdvertisementFound = true;
                            break;
                        }
                        if (!$requestedAdvertisementFound) {
                            $error = 'That advertisement could not be found.';
                        }
                    }
                    $requestHost = is_string($_SERVER['HTTP_HOST'] ?? null) ? $_SERVER['HTTP_HOST'] : '';
                    if (preg_match('/^(?:[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?|\[[0-9A-Fa-f:.]+\])(?::\d{1,5})?$/D', $requestHost) !== 1) {
                        $requestHost = 'your-domain.example';
                    }
                    $requestScheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off'
                        ? 'https'
                        : 'http';
                    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin_dashboard.php')));
                    $scriptDirectory = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
                    $advertisementEmbedUrl = $requestScheme . '://' . $requestHost . $scriptDirectory . '/portfolio_ad_embed.js';
                    $advertisementEmbedCode = '<script src="' . $advertisementEmbedUrl . '" defer></script>';
                ?>
                <div class="card-head">
                    <div><h2 id="portfolio-advertisements-title">Advertise</h2><p>Create product promotions for the portfolio and copy an embed for websites you own or are authorized to update.</p></div>
                    <a class="button" href="admin_dashboard.php#portfolio-advertisement-form">Add new advertisement</a>
                </div>
                <div class="card-body">
                    <?php if ($portfolioAdvertisingLoadError): ?>
                    <p class="notice error" role="alert">Advertisement records could not be loaded. Check the database connection and PHP error log.</p>
                    <?php endif; ?>
                    <style>
                        .advertisement-admin-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
                        .advertisement-admin-form .field-wide{grid-column:1/-1}
                        .advertisement-admin-form textarea{min-height:84px;font:inherit}
                        .advertisement-admin-form input[type=file]{width:100%;padding:8px;border:1px solid #d6dce2;border-radius:4px;background:#fff}
                        .advertisement-upload-current{display:block;margin-top:5px;font-size:12px;overflow-wrap:anywhere}
                        .advertisement-admin-actions{grid-column:1/-1;display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:9px;margin-top:8px;padding:14px;border-top:1px solid #dfe5ec;border-radius:0 0 6px 6px;background:#f8fafc}
                        .advertisement-admin-actions .button{min-height:42px;padding-inline:16px}
                        .advertisement-admin-actions [name="save_portfolio_advertisement"]{font-weight:700;box-shadow:0 3px 10px rgb(0 123 255 / 18%)}
                        .advertisement-admin-actions [name="save_portfolio_advertisement"]:hover{transform:translateY(-1px);box-shadow:0 6px 15px rgb(0 98 204 / 24%)}
                        .advertisement-admin-list{max-width:100%;overflow-x:auto;margin-top:22px;border:1px solid #e1e6eb;border-radius:6px}
                        .advertisement-admin-table{width:100%;min-width:1050px;border-collapse:collapse;text-align:left}
                        .advertisement-admin-table th,.advertisement-admin-table td{padding:10px;border-bottom:1px solid #edf0f2;vertical-align:top;overflow-wrap:anywhere}
                        .advertisement-admin-table th{color:#495057;background:#f8f9fa;font-size:12px;white-space:nowrap}
                        .advertisement-admin-table td{font-size:13px}
                        .advertisement-admin-image{width:78px;height:58px;object-fit:cover;border-radius:5px;background:#edf0f2}
                        .advertisement-embed{margin-top:24px;padding:15px;border:1px solid #dfe5ec;border-left:4px solid #17a2b8;border-radius:5px;background:#f8fafc}
                        .advertisement-embed textarea{min-height:70px;margin-top:8px}
                        @media(max-width:680px){.advertisement-admin-form{grid-template-columns:1fr}.advertisement-admin-form .field-wide{grid-column:auto}.advertisement-admin-actions{grid-column:1;align-items:stretch;flex-direction:column}.advertisement-admin-actions .button{width:100%}}
                    </style>
                    <form method="post" enctype="multipart/form-data" action="admin_dashboard.php#portfolio_advertisements" class="advertisement-admin-form" id="portfolio-advertisement-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" name="advertisement_id" value="<?php echo (int) $advertisementForm['ad_id']; ?>">
                        <div><label for="advertisement_brand_name">Advertiser / brand name</label><input id="advertisement_brand_name" name="advertisement_brand_name" type="text" maxlength="120" value="<?php echo blogDashboardEscape($advertisementForm['brand_name']); ?>" required></div>
                        <div><label for="advertisement_product_name">Product / promotion title</label><input id="advertisement_product_name" name="advertisement_product_name" type="text" maxlength="160" value="<?php echo blogDashboardEscape($advertisementForm['product_name']); ?>" required></div>
                        <div class="field-wide"><label for="advertisement_description">Description (shown in full-screen details)</label><textarea id="advertisement_description" name="advertisement_description" maxlength="2000" required><?php echo blogDashboardEscape($advertisementForm['description']); ?></textarea></div>
                        <div><label for="advertisement_brand_logo_url">Brand logo URL or relative image path (optional)</label><input id="advertisement_brand_logo_url" name="advertisement_brand_logo_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($advertisementForm['brand_logo_url']); ?>"></div>
                        <div>
                            <label for="advertisement_product_image_file">Product image upload (JPG, PNG, GIF, WebP; up to 8 MB)</label>
                            <input id="advertisement_product_image_file" name="advertisement_product_image_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                            <label for="advertisement_product_image_url">Or product image URL / relative path</label>
                            <input id="advertisement_product_image_url" name="advertisement_product_image_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($advertisementForm['product_image_url']); ?>">
                            <?php if ($advertisementForm['product_image_url'] !== ''): ?><span class="advertisement-upload-current">Current image: <?php echo blogDashboardEscape($advertisementForm['product_image_url']); ?>. A newly uploaded image replaces it.</span><?php endif; ?>
                        </div>
                        <div>
                            <label for="advertisement_poster_image_file">Poster image upload (optional; JPG, PNG, GIF, WebP; up to 8 MB)</label>
                            <input id="advertisement_poster_image_file" name="advertisement_poster_image_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                            <label for="advertisement_poster_image_url">Or poster image URL / relative path</label>
                            <input id="advertisement_poster_image_url" name="advertisement_poster_image_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($advertisementForm['poster_image_url']); ?>">
                            <?php if ($advertisementForm['poster_image_url'] !== ''): ?><span class="advertisement-upload-current">Current poster: <?php echo blogDashboardEscape($advertisementForm['poster_image_url']); ?>. A newly uploaded poster replaces it.</span><?php endif; ?>
                            <?php if ((int) $advertisementForm['ad_id'] > 0 && $advertisementForm['poster_image_url'] !== ''): ?><label class="home-visibility"><input type="checkbox" name="advertisement_remove_poster_image" value="1"> Remove current poster</label><?php endif; ?>
                        </div>
                        <div>
                            <label for="advertisement_video_file">Video upload (optional; MP4/WebM, up to 50 MB)</label>
                            <input id="advertisement_video_file" name="advertisement_video_file" type="file" accept="video/mp4,video/webm,.mp4,.webm">
                            <label for="advertisement_video_file_url">Or video URL / relative path</label>
                            <input id="advertisement_video_file_url" name="advertisement_video_file_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($advertisementForm['video_file_url']); ?>">
                            <?php if ($advertisementForm['video_file_url'] !== ''): ?><span class="advertisement-upload-current">Current video: <?php echo blogDashboardEscape($advertisementForm['video_file_url']); ?>. A newly uploaded video replaces it.</span><?php endif; ?>
                            <?php if ((int) $advertisementForm['ad_id'] > 0 && $advertisementForm['video_file_url'] !== ''): ?><label class="home-visibility"><input type="checkbox" name="advertisement_remove_video_file" value="1"> Remove current video</label><?php endif; ?>
                        </div>
                        <div>
                            <label for="advertisement_pdf_file">PDF brochure upload (optional; up to 15 MB)</label>
                            <input id="advertisement_pdf_file" name="advertisement_pdf_file" type="file" accept="application/pdf,.pdf">
                            <label for="advertisement_pdf_file_url">Or PDF URL / relative path</label>
                            <input id="advertisement_pdf_file_url" name="advertisement_pdf_file_url" type="text" maxlength="500" value="<?php echo blogDashboardEscape($advertisementForm['pdf_file_url']); ?>">
                            <?php if ($advertisementForm['pdf_file_url'] !== ''): ?><span class="advertisement-upload-current">Current PDF: <?php echo blogDashboardEscape($advertisementForm['pdf_file_url']); ?>. A newly uploaded PDF replaces it.</span><?php endif; ?>
                            <?php if ((int) $advertisementForm['ad_id'] > 0 && $advertisementForm['pdf_file_url'] !== ''): ?><label class="home-visibility"><input type="checkbox" name="advertisement_remove_pdf_file" value="1"> Remove current PDF</label><?php endif; ?>
                        </div>
                        <div><label for="advertisement_target_url">Destination URL or relative path (optional)</label><input id="advertisement_target_url" name="advertisement_target_url" type="text" maxlength="1000" value="<?php echo blogDashboardEscape($advertisementForm['target_url']); ?>"></div>
                        <div><label for="advertisement_cta_label">Button label</label><input id="advertisement_cta_label" name="advertisement_cta_label" type="text" maxlength="80" value="<?php echo blogDashboardEscape($advertisementForm['cta_label']); ?>" required></div>
                        <div class="field-wide">
                            <label class="home-visibility"><input type="checkbox" name="advertisement_is_pinned" value="1"<?php echo $advertisementForm['is_pinned'] ? ' checked' : ''; ?>> Pin this advertisement ahead of other active ads</label>
                        </div>
                        <div class="field-wide advertisement-admin-actions">
                            <button class="button" type="submit" name="save_portfolio_advertisement" value="1" aria-label="<?php echo (int) $advertisementForm['ad_id'] > 0 ? 'Save advertisement changes' : 'Save new advertisement'; ?>"><?php echo (int) $advertisementForm['ad_id'] > 0 ? 'Save changes' : 'Save advertisement'; ?></button>
                            <a class="button secondary" href="admin_dashboard.php#portfolio-advertisement-form" aria-label="Clear the advertisement form and start a new advertisement">Clear form</a>
                            <a class="button secondary" href="admin_dashboard.php#portfolio_advertisements">Cancel</a>
                        </div>
                    </form>

                    <?php if ($portfolioAdvertisements === [] && !$portfolioAdvertisingLoadError): ?>
                    <p class="form-help">No advertisements have been added. New promotions are saved as active and appear in the portfolio advertisement grid.</p>
                    <?php else: ?>
                    <div class="advertisement-admin-list">
                        <table class="advertisement-admin-table">
                            <thead><tr><th scope="col">Brand / product</th><th scope="col">Description</th><th scope="col">Images</th><th scope="col">PDF</th><th scope="col">Destination</th><th scope="col">Priority / status</th><th scope="col">Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($portfolioAdvertisements as $advertisement): ?>
                                <?php $advertisementActive = ($advertisement['status'] ?? '') === 'active'; ?>
                                <tr>
                                    <td><strong><?php echo blogDashboardEscape($advertisement['brand_name']); ?></strong><br><?php echo blogDashboardEscape($advertisement['product_name']); ?></td>
                                    <td style="max-width:300px;white-space:pre-wrap"><?php echo blogDashboardEscape($advertisement['description']); ?></td>
                                    <td>
                                        <img class="advertisement-admin-image" src="<?php echo blogDashboardEscape($advertisement['product_image_url']); ?>" alt="Product image" loading="lazy">
                                        <?php if (!empty($advertisement['brand_logo_url'])): ?><br><img class="advertisement-admin-image" src="<?php echo blogDashboardEscape($advertisement['brand_logo_url']); ?>" alt="Brand logo" loading="lazy"><?php endif; ?>
                                        <?php if (!empty($advertisement['poster_image_url'])): ?><br><img class="advertisement-admin-image" src="<?php echo blogDashboardEscape($advertisement['poster_image_url']); ?>" alt="Advertisement poster" loading="lazy"><?php endif; ?>
                                        <?php if (!empty($advertisement['video_file_url'])): ?><br><a href="<?php echo blogDashboardEscape($advertisement['video_file_url']); ?>" target="_blank" rel="noopener noreferrer">Open video</a><?php endif; ?>
                                    </td>
                                    <td><?php if (!empty($advertisement['pdf_file_url'])): ?><a href="<?php echo blogDashboardEscape($advertisement['pdf_file_url']); ?>" target="_blank" rel="noopener noreferrer">Open PDF</a><?php else: ?><span class="hint">No PDF</span><?php endif; ?></td>
                                    <td><?php if (!empty($advertisement['target_url'])): ?><a href="<?php echo blogDashboardEscape($advertisement['target_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo blogDashboardEscape($advertisement['target_url']); ?></a><?php else: ?><span class="hint">No link set</span><?php endif; ?><br><span class="hint">Button: <?php echo blogDashboardEscape($advertisement['cta_label']); ?></span></td>
                                    <td><?php echo !empty($advertisement['is_pinned']) ? 'Pinned' : 'Standard'; ?><br><?php echo $advertisementActive ? 'Active' : 'Banned'; ?></td>
                                    <td style="min-width:170px">
                                        <a class="button small" href="admin_dashboard.php?advertisement_edit=<?php echo (int) $advertisement['ad_id']; ?>#portfolio_advertisements">Edit</a>
                                        <form method="post" action="admin_dashboard.php#portfolio_advertisements" style="display:inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                                            <input type="hidden" name="advertisement_id" value="<?php echo (int) $advertisement['ad_id']; ?>">
                                            <input type="hidden" name="advertisement_status" value="<?php echo $advertisementActive ? 'banned' : 'active'; ?>">
                                            <button class="button small<?php echo $advertisementActive ? ' danger' : ' secondary'; ?>" type="submit" name="set_portfolio_advertisement_status" value="1"><?php echo $advertisementActive ? 'Ban' : 'Restore'; ?></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <div class="advertisement-embed">
                        <h3 style="margin:0 0 5px;font-size:16px">Embed on another authorized website</h3>
                        <p class="form-help">Copy this code into the page’s HTML where the advertisement widget should be enabled. It loads only active promotions from this portfolio. The popup appears after 5 seconds, stays for 5 seconds, then waits 60 seconds before the next ad. Close it to disable click interception while it is hidden.</p>
                        <label for="advertisement_embed_code">Embed code</label>
                        <textarea id="advertisement_embed_code" readonly rows="2"><?php echo blogDashboardEscape($advertisementEmbedCode); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="setting-card portfolio-card" id="portfolio_sections" aria-labelledby="portfolio-sections-title">
                <div class="card-head"><div><h2 id="portfolio-sections-title">Portfolio sections</h2><p>Manage the public Gallery and Blog cards, Contact content, Search Bar, and Dark Mode. Hidden cards remain editable here; removing a card deletes it when saved. Each editor saves only its own section.</p></div></div>
                <div class="card-body">
                    <form method="post" id="portfolio-sections-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" name="sections_save_target" id="sections-save-target" value="all">
                        <input type="hidden" id="portfolio_gallery_items" name="portfolio_gallery_items">
                        <input type="hidden" id="portfolio_blog_items" name="portfolio_blog_items">
                        <input type="hidden" id="portfolio_contact_links" name="portfolio_contact_links">
                        <section class="home-fields-section" id="portfolio_gallery">
                            <h3>Gallery</h3>
                            <label class="home-visibility"><input type="checkbox" name="gallery_visible" value="1"<?php echo !empty($portfolioSectionsPanel['gallery']['visible']) ? ' checked' : ''; ?>> Show Gallery section on the portfolio</label>
                            <div class="portfolio-fields">
                                <div><label for="gallery_title">Section title</label><input id="gallery_title" name="gallery_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['gallery']['title'] ?? ''); ?>" required></div>
                                <div class="field-wide"><label for="gallery_description">Section description</label><textarea id="gallery_description" name="gallery_description" maxlength="1500" required><?php echo blogDashboardEscape($portfolioSectionsPanel['gallery']['description'] ?? ''); ?></textarea></div>
                            </div>
                            <div class="about-content-list" id="portfolio-gallery-editor">
                                <?php foreach ($portfolioSectionsPanel['gallery']['items'] as $item): if (!is_array($item)) { continue; } ?>
                                <article class="about-content-item" data-section-card="gallery">
                                    <h3>Gallery item</h3>
                                    <div class="about-content-item-fields">
                                        <label>Category ID<input type="text" data-gallery-category maxlength="40" pattern="[a-z0-9][a-z0-9-]{0,39}" value="<?php echo blogDashboardEscape($item['category'] ?? ''); ?>" required></label>
                                        <label>Category label<input type="text" data-gallery-category-label maxlength="80" value="<?php echo blogDashboardEscape($item['category_label'] ?? ''); ?>" required></label>
                                        <label>Title<input type="text" data-gallery-title maxlength="160" value="<?php echo blogDashboardEscape($item['title'] ?? ''); ?>" required></label>
                                        <label>Type or subtitle<input type="text" data-gallery-type maxlength="120" value="<?php echo blogDashboardEscape($item['type'] ?? ''); ?>" required></label>
                                        <label class="field-wide">Summary<textarea data-gallery-summary maxlength="1500" required><?php echo blogDashboardEscape($item['summary'] ?? ''); ?></textarea></label>
                                        <label class="field-wide">Full details<textarea data-gallery-detail maxlength="3000" required><?php echo blogDashboardEscape($item['detail'] ?? ''); ?></textarea></label>
                                        <label class="field-wide">Outcome<textarea data-gallery-outcome maxlength="1500" required><?php echo blogDashboardEscape($item['outcome'] ?? ''); ?></textarea></label>
                                        <label class="field-wide">Images (one safe path or HTTPS URL per line, optionally followed by | alt text)<textarea data-gallery-images maxlength="12000" required><?php
                                            $galleryImageLines = [];
                                            foreach (is_array($item['images'] ?? null) ? $item['images'] : [] as $galleryImage) {
                                                if (is_array($galleryImage)) {
                                                    $galleryImageLines[] = ($galleryImage['src'] ?? '') . '|' . ($galleryImage['alt'] ?? '');
                                                }
                                            }
                                            echo blogDashboardEscape(implode("\n", $galleryImageLines));
                                        ?></textarea></label>
                                    </div>
                                    <div class="about-content-item-actions">
                                        <label class="home-visibility"><input type="checkbox" data-section-visible value="1"<?php echo !isset($item['visible']) || $item['visible'] === true ? ' checked' : ''; ?>> Show on portfolio (uncheck to ban)</label>
                                        <button class="button danger small" type="button" data-section-remove>Remove item</button>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                            <div class="menu-editor-actions"><button class="button secondary small" type="button" data-section-add="gallery">Add Gallery item</button></div>
                            <div class="section-editor-actions">
                                <span class="section-editor-status" data-section-status role="status" aria-live="polite"></span>
                                <div><button class="button secondary" type="button" data-section-cancel>Cancel</button><button class="button" type="submit" name="save_portfolio_sections" data-section-save="gallery" formnovalidate>Save Gallery</button></div>
                            </div>
                        </section>
                        <section class="home-fields-section" id="portfolio_blog">
                            <h3>Blog</h3>
                            <label class="home-visibility"><input type="checkbox" name="blog_visible" value="1"<?php echo !empty($portfolioSectionsPanel['blog']['visible']) ? ' checked' : ''; ?>> Show Blog section on the portfolio</label>
                            <div class="portfolio-fields">
                                <div><label for="blog_title">Section title</label><input id="blog_title" name="blog_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['blog']['title'] ?? ''); ?>" required></div>
                                <div class="field-wide"><label for="blog_description">Section description</label><textarea id="blog_description" name="blog_description" maxlength="1500" required><?php echo blogDashboardEscape($portfolioSectionsPanel['blog']['description'] ?? ''); ?></textarea></div>
                            </div>
                            <div class="about-content-list" id="portfolio-blog-editor">
                                <?php foreach ($portfolioSectionsPanel['blog']['items'] as $item): if (!is_array($item)) { continue; } ?>
                                <article class="about-content-item" data-section-card="blog">
                                    <h3>Blog article</h3>
                                    <div class="about-content-item-fields">
                                        <label>Category<input type="text" data-blog-category maxlength="80" value="<?php echo blogDashboardEscape($item['category'] ?? ''); ?>" required></label>
                                        <label>Display date<input type="text" data-blog-date maxlength="100" value="<?php echo blogDashboardEscape($item['date'] ?? ''); ?>" required></label>
                                        <label>ISO date<input type="date" data-blog-date-iso value="<?php echo blogDashboardEscape($item['date_iso'] ?? ''); ?>" required></label>
                                        <label>Media type<select data-blog-media-type><option value="image"<?php echo ($item['media_type'] ?? '') === 'image' ? ' selected' : ''; ?>>Image</option><option value="video"<?php echo ($item['media_type'] ?? '') === 'video' ? ' selected' : ''; ?>>Video</option></select></label>
                                        <label class="field-wide">Title<input type="text" data-blog-title maxlength="200" value="<?php echo blogDashboardEscape($item['title'] ?? ''); ?>" required></label>
                                        <label class="field-wide">Summary<textarea data-blog-summary maxlength="1500" required><?php echo blogDashboardEscape($item['summary'] ?? ''); ?></textarea></label>
                                        <label class="field-wide">Full article<textarea data-blog-body maxlength="12000" required><?php echo blogDashboardEscape($item['body'] ?? ''); ?></textarea></label>
                                        <label class="field-wide">Image/video safe path or HTTPS URL<input type="text" data-blog-image maxlength="1000" value="<?php echo blogDashboardEscape($item['image'] ?? ''); ?>" required></label>
                                        <label class="field-wide">Image/video description<input type="text" data-blog-image-alt maxlength="200" value="<?php echo blogDashboardEscape($item['image_alt'] ?? ''); ?>" required></label>
                                    </div>
                                    <div class="about-content-item-actions">
                                        <label class="home-visibility"><input type="checkbox" data-section-visible value="1"<?php echo !isset($item['visible']) || $item['visible'] === true ? ' checked' : ''; ?>> Show on portfolio (uncheck to ban)</label>
                                        <button class="button danger small" type="button" data-section-remove>Remove article</button>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                            <div class="menu-editor-actions"><button class="button secondary small" type="button" data-section-add="blog">Add Blog article</button></div>
                            <div class="section-editor-actions">
                                <span class="section-editor-status" data-section-status role="status" aria-live="polite"></span>
                                <div><button class="button secondary" type="button" data-section-cancel>Cancel</button><button class="button" type="submit" name="save_portfolio_sections" data-section-save="blog" formnovalidate>Save Blog</button></div>
                            </div>
                        </section>
                        <section class="home-fields-section" id="portfolio_contact">
                            <h3>Contact</h3>
                            <label class="home-visibility"><input type="checkbox" name="contact_visible" value="1"<?php echo !empty($portfolioSectionsPanel['contact']['visible']) ? ' checked' : ''; ?>> Show Contact section on the portfolio</label>
                            <div class="portfolio-fields">
                                <div><label for="contact_title">Section title</label><input id="contact_title" name="contact_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['title'] ?? ''); ?>" required></div>
                                <div class="field-wide"><label for="contact_description">Section description</label><textarea id="contact_description" name="contact_description" maxlength="1500" required><?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['description'] ?? ''); ?></textarea></div>
                                <div><label for="contact_phone">Phone information</label><input id="contact_phone" name="contact_phone" type="text" maxlength="150" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['phone'] ?? ''); ?>" required></div>
                                <div><label for="contact_info_note">Contact note</label><input id="contact_info_note" name="contact_info_note" type="text" maxlength="300" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['info_note'] ?? ''); ?>" required></div>
                                <div><label for="contact_form_title">Message form title</label><input id="contact_form_title" name="contact_form_title" type="text" maxlength="120" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['form_title'] ?? ''); ?>" required><label class="home-visibility"><input type="checkbox" name="contact_form_visible" value="1"<?php echo !empty($portfolioSectionsPanel['contact']['form_visible']) ? ' checked' : ''; ?>> Show message form</label></div>
                                <div><label for="contact_social_title">Social links title</label><input id="contact_social_title" name="contact_social_title" type="text" maxlength="120" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['social_title'] ?? ''); ?>" required><label class="home-visibility"><input type="checkbox" name="contact_social_visible" value="1"<?php echo !empty($portfolioSectionsPanel['contact']['social_visible']) ? ' checked' : ''; ?>> Show social links</label></div>
                                <div class="field-wide"><label for="contact_social_note">Social links note</label><textarea id="contact_social_note" name="contact_social_note" maxlength="500" required><?php echo blogDashboardEscape($portfolioSectionsPanel['contact']['social_note'] ?? ''); ?></textarea></div>
                            </div>
                            <h4>Contact social links</h4>
                            <div class="about-content-list" id="portfolio-contact-links-editor">
                                <?php foreach ($portfolioSectionsPanel['contact']['social_links'] as $link): if (!is_array($link)) { continue; } ?>
                                <article class="about-content-item" data-section-card="contact-link">
                                    <div class="about-content-item-fields">
                                        <label>Link label<input type="text" data-contact-label maxlength="80" value="<?php echo blogDashboardEscape($link['label'] ?? ''); ?>" required></label>
                                        <label>HTTPS URL<input type="url" data-contact-url maxlength="500" value="<?php echo blogDashboardEscape($link['url'] ?? ''); ?>" pattern="https://.*" required></label>
                                        <label>Icon class<input type="text" data-contact-icon maxlength="63" pattern="fa-[a-z0-9-]{1,60}" value="<?php echo blogDashboardEscape($link['icon'] ?? ''); ?>" required></label>
                                    </div>
                                    <div class="about-content-item-actions">
                                        <label class="home-visibility"><input type="checkbox" data-section-visible value="1"<?php echo !isset($link['visible']) || $link['visible'] === true ? ' checked' : ''; ?>> Show link (uncheck to ban)</label>
                                        <button class="button danger small" type="button" data-section-remove>Remove link</button>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                            <div class="menu-editor-actions"><button class="button secondary small" type="button" data-section-add="contact-link">Add social link</button></div>
                            <p class="form-help">Email and location are managed in Header Panel. Social destinations must use HTTPS links.</p>
                            <div class="section-editor-actions">
                                <span class="section-editor-status" data-section-status role="status" aria-live="polite"></span>
                                <div><button class="button secondary" type="button" data-section-cancel>Cancel</button><button class="button" type="submit" name="save_portfolio_sections" data-section-save="contact" formnovalidate>Save Contact</button></div>
                            </div>
                        </section>
                        <section class="home-fields-section" id="portfolio_search">
                            <h3>Search Bar</h3>
                            <label class="home-visibility"><input type="checkbox" name="search_visible" value="1"<?php echo !empty($portfolioSectionsPanel['search']['visible']) ? ' checked' : ''; ?>> Enable portfolio search</label>
                            <div><label for="search_placeholder">Search placeholder</label><input id="search_placeholder" name="search_placeholder" type="text" maxlength="80" value="<?php echo blogDashboardEscape($portfolioSectionsPanel['search']['placeholder'] ?? ''); ?>" required></div>
                            <div class="section-editor-actions">
                                <span class="section-editor-status" data-section-status role="status" aria-live="polite"></span>
                                <div><button class="button secondary" type="button" data-section-cancel>Cancel</button><button class="button" type="submit" name="save_portfolio_sections" data-section-save="search" formnovalidate>Save Search Bar</button></div>
                            </div>
                        </section>
                        <section class="home-fields-section" id="portfolio_dark_mode">
                            <?php $themeDefaultForEditor = in_array($portfolioSectionsPanel['theme']['default'] ?? 'dark', ['dark', 'light', 'black', 'light-black', 'natural', 'light-pure'], true) ? $portfolioSectionsPanel['theme']['default'] : 'dark'; ?>
                            <h3>Dark Mode</h3>
                            <label class="home-visibility"><input type="checkbox" name="theme_visible" value="1"<?php echo !empty($portfolioSectionsPanel['theme']['visible']) ? ' checked' : ''; ?>> Show the theme toggle on the portfolio</label>
                            <div><label for="theme_default">Default theme for new visitors</label><select id="theme_default" name="theme_default">
                                <?php foreach (['dark' => 'Dark', 'light' => 'Light', 'black' => 'Black', 'light-black' => 'Light Black', 'natural' => 'Natural', 'light-pure' => 'Light Pure'] as $themeValue => $themeLabel): ?>
                                <option value="<?php echo blogDashboardEscape($themeValue); ?>"<?php echo $themeDefaultForEditor === $themeValue ? ' selected' : ''; ?>><?php echo blogDashboardEscape($themeLabel); ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <div class="theme-preset-preview" id="theme-preset-preview" data-theme-preview="<?php echo blogDashboardEscape($themeDefaultForEditor); ?>" aria-live="polite">
                                <span class="theme-preview-swatch" aria-hidden="true"></span>
                                <span><strong data-theme-preview-label><?php echo blogDashboardEscape(ucwords(str_replace('-', ' ', $themeDefaultForEditor))); ?></strong><small>Portfolio palette preview</small></span>
                            </div>
                            <p class="form-help">The portfolio theme button cycles through all six palettes. Use the Dark Mode submenu to select a default palette, then save.</p>
                            <div class="section-editor-actions">
                                <span class="section-editor-status" data-section-status role="status" aria-live="polite"></span>
                                <div><button class="button secondary" type="button" data-section-cancel>Cancel</button><button class="button" type="submit" name="save_portfolio_sections" data-section-save="theme" formnovalidate>Save Dark Mode</button></div>
                            </div>
                        </section>
                        <p class="form-help">Hidden cards and links stay editable and can be restored by checking their visibility control. Remove deletes them when that section is saved.</p>
                    </form>
                    <template id="portfolio-gallery-card-template"><article class="about-content-item" data-section-card="gallery"><h3>New Gallery item</h3><div class="about-content-item-fields"><label>Category ID<input type="text" data-gallery-category maxlength="40" pattern="[a-z0-9][a-z0-9-]{0,39}" required></label><label>Category label<input type="text" data-gallery-category-label maxlength="80" required></label><label>Title<input type="text" data-gallery-title maxlength="160" required></label><label>Type or subtitle<input type="text" data-gallery-type maxlength="120" required></label><label class="field-wide">Summary<textarea data-gallery-summary maxlength="1500" required></textarea></label><label class="field-wide">Full details<textarea data-gallery-detail maxlength="3000" required></textarea></label><label class="field-wide">Outcome<textarea data-gallery-outcome maxlength="1500" required></textarea></label><label class="field-wide">Images (one safe path or HTTPS URL per line, optionally followed by | alt text)<textarea data-gallery-images maxlength="12000" required></textarea></label></div><div class="about-content-item-actions"><label class="home-visibility"><input type="checkbox" data-section-visible value="1" checked> Show on portfolio (uncheck to ban)</label><button class="button danger small" type="button" data-section-remove>Remove item</button></div></article></template>
                    <template id="portfolio-blog-card-template"><article class="about-content-item" data-section-card="blog"><h3>New Blog article</h3><div class="about-content-item-fields"><label>Category<input type="text" data-blog-category maxlength="80" required></label><label>Display date<input type="text" data-blog-date maxlength="100" required></label><label>ISO date<input type="date" data-blog-date-iso required></label><label>Media type<select data-blog-media-type><option value="image">Image</option><option value="video">Video</option></select></label><label class="field-wide">Title<input type="text" data-blog-title maxlength="200" required></label><label class="field-wide">Summary<textarea data-blog-summary maxlength="1500" required></textarea></label><label class="field-wide">Full article<textarea data-blog-body maxlength="12000" required></textarea></label><label class="field-wide">Image/video safe path or HTTPS URL<input type="text" data-blog-image maxlength="1000" required></label><label class="field-wide">Image/video description<input type="text" data-blog-image-alt maxlength="200" required></label></div><div class="about-content-item-actions"><label class="home-visibility"><input type="checkbox" data-section-visible value="1" checked> Show on portfolio (uncheck to ban)</label><button class="button danger small" type="button" data-section-remove>Remove article</button></div></article></template>
                    <template id="portfolio-contact-link-template"><article class="about-content-item" data-section-card="contact-link"><div class="about-content-item-fields"><label>Link label<input type="text" data-contact-label maxlength="80" required></label><label>HTTPS URL<input type="url" data-contact-url maxlength="500" pattern="https://.*" required></label><label>Icon class<input type="text" data-contact-icon maxlength="63" pattern="fa-[a-z0-9-]{1,60}" required></label></div><div class="about-content-item-actions"><label class="home-visibility"><input type="checkbox" data-section-visible value="1" checked> Show link (uncheck to ban)</label><button class="button danger small" type="button" data-section-remove>Remove link</button></div></article></template>
                </div>
            </section>

            <section class="setting-card portfolio-card" id="portfolio_social_posts" aria-labelledby="portfolio-social-posts-title">
                <div class="card-head"><div><h2 id="portfolio-social-posts-title">Social and Post Widgets</h2><p>Add, edit, hide, or remove the cards shown in the public portfolio social feed.</p></div></div>
                <div class="card-body">
                    <form method="post" id="portfolio-social-posts-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" name="portfolio_social_posts" id="portfolio-social-posts-value">
                        <p class="form-help">Hidden posts stay saved in the dashboard but disappear from the portfolio. Descriptions support unlimited text, including uppercase and lowercase letters, punctuation, and emoji. Use a safe image or MP4 video URL/path.</p>
                        <div class="social-post-selector-wrap">
                            <label for="social-post-selector">Select a post by title and date</label>
                            <select id="social-post-selector">
                                <option value="">Choose a post to edit…</option>
                                <?php foreach ($portfolioSocialPostsPanel as $postIndex => $post): ?>
                                <option value="social-post-editor-card-<?php echo $postIndex; ?>"><?php echo blogDashboardEscape($post['title'] . ' — ' . date('M j, Y g:i A', strtotime($post['published_at']))); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="social-post-editor" id="social-post-editor">
                            <?php foreach ($portfolioSocialPostsPanel as $postIndex => $post): ?>
                            <?php $socialEditorId = 'social-post-editor-card-' . $postIndex; ?>
                            <article class="social-post-editor-card" id="<?php echo $socialEditorId; ?>" hidden tabindex="-1">
                                <div class="social-post-editor-heading"><strong>Post widget</strong></div>
                                <div class="social-post-editor-tabs" role="tablist" aria-label="Post widget settings">
                                    <button class="social-post-editor-tab" id="<?php echo $socialEditorId; ?>-tab-publisher" type="button" role="tab" aria-selected="true" aria-controls="<?php echo $socialEditorId; ?>-panel-publisher" tabindex="0">About Publisher</button>
                                    <button class="social-post-editor-tab" id="<?php echo $socialEditorId; ?>-tab-media" type="button" role="tab" aria-selected="false" aria-controls="<?php echo $socialEditorId; ?>-panel-media" tabindex="-1">Image and Title</button>
                                    <button class="social-post-editor-tab" id="<?php echo $socialEditorId; ?>-tab-description" type="button" role="tab" aria-selected="false" aria-controls="<?php echo $socialEditorId; ?>-panel-description" tabindex="-1">Description Content</button>
                                    <button class="social-post-editor-tab" id="<?php echo $socialEditorId; ?>-tab-feedback" type="button" role="tab" aria-selected="false" aria-controls="<?php echo $socialEditorId; ?>-panel-feedback" tabindex="-1">Feedback and Reaction</button>
                                </div>
                                <section class="social-post-editor-panel" id="<?php echo $socialEditorId; ?>-panel-publisher" role="tabpanel" aria-labelledby="<?php echo $socialEditorId; ?>-tab-publisher">
                                    <div class="social-post-editor-fields">
                                        <label>Publisher name<input type="text" data-social-publisher-name maxlength="100" value="<?php echo blogDashboardEscape($post['publisher_name']); ?>" required></label>
                                        <label>Publisher role<input type="text" data-social-publisher-role maxlength="100" value="<?php echo blogDashboardEscape($post['publisher_role']); ?>" required></label>
                                        <label>Publish date and time<input type="datetime-local" data-social-published-at data-published-at-iso="<?php echo blogDashboardEscape($post['published_at']); ?>" value="<?php echo blogDashboardEscape(date('Y-m-d\TH:i', strtotime($post['published_at']))); ?>" required></label>
                                    </div>
                                </section>
                                <section class="social-post-editor-panel" id="<?php echo $socialEditorId; ?>-panel-media" role="tabpanel" aria-labelledby="<?php echo $socialEditorId; ?>-tab-media" hidden>
                                    <div class="social-post-editor-fields">
                                        <label>Post ID<input type="text" data-social-id maxlength="40" pattern="[a-z0-9][a-z0-9-]{0,39}" value="<?php echo blogDashboardEscape($post['id'] ?? ''); ?>" required></label>
                                        <label>Media type<select data-social-media-type required><option value="image"<?php echo ($post['media_type'] ?? '') === 'image' ? ' selected' : ''; ?>>Image</option><option value="video"<?php echo ($post['media_type'] ?? '') === 'video' ? ' selected' : ''; ?>>MP4 video</option></select></label>
                                        <label>Title<input type="text" data-social-title maxlength="160" value="<?php echo blogDashboardEscape($post['title'] ?? ''); ?>" required></label>
                                        <label>Image / video URL or path<input type="text" data-social-image maxlength="1000" value="<?php echo blogDashboardEscape($post['image'] ?? ''); ?>" required></label>
                                        <label>Image alt text<input type="text" data-social-alt maxlength="200" value="<?php echo blogDashboardEscape($post['alt'] ?? ''); ?>" required></label>
                                    </div>
                                </section>
                                <section class="social-post-editor-panel" id="<?php echo $socialEditorId; ?>-panel-description" role="tabpanel" aria-labelledby="<?php echo $socialEditorId; ?>-tab-description" hidden>
                                    <div class="social-post-editor-fields">
                                        <label class="social-post-description">Description<textarea data-social-description required><?php echo blogDashboardEscape($post['description'] ?? ''); ?></textarea></label>
                                    </div>
                                </section>
                                <section class="social-post-editor-panel" id="<?php echo $socialEditorId; ?>-panel-feedback" role="tabpanel" aria-labelledby="<?php echo $socialEditorId; ?>-tab-feedback" hidden>
                                    <div class="social-feedback-tools"><button class="button secondary small" type="button" data-social-feedback-refresh>Refresh feedback</button></div>
                                    <div class="social-feedback-tabs" role="group" aria-label="Social feedback categories">
                                        <button class="social-feedback-tab" type="button" data-feedback-view="comments" aria-pressed="true">Comments</button>
                                        <button class="social-feedback-tab" type="button" data-feedback-view="replies" aria-pressed="false">Comment replies</button>
                                        <button class="social-feedback-tab" type="button" data-feedback-view="likes" aria-pressed="false">Likes</button>
                                        <button class="social-feedback-tab" type="button" data-feedback-view="shares" aria-pressed="false">Shares</button>
                                        <button class="social-feedback-tab" type="button" data-feedback-view="share-replies" aria-pressed="false">Share replies</button>
                                    </div>
                                    <p class="social-feedback-error" data-social-feedback-error role="status" aria-live="polite" hidden></p>
                                    <?php foreach (['comments' => 'Comments', 'replies' => 'Comment replies', 'likes' => 'Likes', 'shares' => 'Shares', 'share-replies' => 'Share replies'] as $feedbackIndex => $feedbackLabel): ?>
                                    <section class="social-feedback-panel" data-feedback-panel="<?php echo blogDashboardEscape($feedbackIndex); ?>"<?php echo $feedbackIndex === 'comments' ? '' : ' hidden'; ?>>
                                        <div class="social-feedback-table-wrap" tabindex="0" role="region" aria-label="<?php echo blogDashboardEscape($feedbackLabel); ?> table; scroll horizontally to view all columns">
                                            <table class="social-feedback-table">
                                                <thead><tr><th scope="col">Name</th><th scope="col">Comments</th><th scope="col">Comments reply</th><th scope="col">Likes</th><th scope="col">Shares (details to where)</th><th scope="col">Share reply</th><th scope="col">Date and time</th><th scope="col">Action</th></tr></thead>
                                                <tbody data-feedback-list="<?php echo blogDashboardEscape($feedbackIndex); ?>"><tr><td class="social-feedback-empty" colspan="8">Select this tab to load feedback.</td></tr></tbody>
                                            </table>
                                        </div>
                                    </section>
                                    <?php endforeach; ?>
                                    <p class="form-help">Feedback edits and visibility changes are saved immediately. The public feed does not record share destinations or support replies to shares. Post-widget changes remain staged until Save Social Widgets.</p>
                                    <p class="form-help"><a href="portfolio.php#socialFeed" target="_blank" rel="noopener noreferrer">Open public Social Feed ↗</a></p>
                                </section>
                                <div class="social-post-editor-actions" aria-label="Actions for this post">
                                    <button class="button secondary small" type="button" data-social-edit>Edit / Modify</button>
                                    <button class="button small" type="button" data-social-update disabled>Update</button>
                                    <button class="button danger small" type="button" data-social-ban aria-pressed="<?php echo !empty($post['visible']) ? 'false' : 'true'; ?>"><?php echo !empty($post['visible']) ? 'Ban from user view' : 'Restore user view'; ?></button>
                                    <button class="button danger small" type="button" data-social-delete>Delete</button>
                                    <button class="button secondary small" type="button" data-social-add>Add new grid</button>
                                    <button class="button secondary small" type="button" data-social-feature aria-pressed="<?php echo !empty($post['featured']) ? 'true' : 'false'; ?>"><?php echo !empty($post['featured']) ? 'Featured in post grids' : 'Feature in post grids'; ?></button>
                                    <input type="checkbox" data-social-visible value="1"<?php echo !empty($post['visible']) ? ' checked' : ''; ?> hidden>
                                    <input type="checkbox" data-social-featured value="1"<?php echo !empty($post['featured']) ? ' checked' : ''; ?> hidden>
                                    <p class="social-post-action-status" data-social-action-status role="status" aria-live="polite"></p>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer"><span class="hint">Changes appear in the public social feed after saving.</span><button class="button" type="submit" name="save_portfolio_social_posts">Save Social Widgets</button></div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_about" aria-labelledby="portfolio-about-title">
                <div class="card-head"><div><h2 id="portfolio-about-title">About Me content</h2><p>Manage the About section header, left-side image, statement, descriptions, and objectives &amp; values cards.</p></div></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <nav class="about-editor-shortcuts" aria-label="About Me editor sections">
                            <a href="#about-header-settings">Header (Who I Am)</a>
                            <a href="#about-image-settings">Left-side image</a>
                            <a href="#about-statement-settings">Statement &amp; descriptions</a>
                            <a href="#about-values-settings">My objectives &amp; values</a>
                        </nav>
                        <div class="portfolio-fields">
                            <div id="about-header-settings">
                                <label for="about_header_label">Header label</label>
                                <input id="about_header_label" name="about_header_label" type="text" maxlength="120" value="<?php echo blogDashboardEscape($settings['portfolio_about']['header_label']); ?>" required>
                            </div>
                            <div>
                                <label for="about_header_title">Header title</label>
                                <input id="about_header_title" name="about_header_title" type="text" maxlength="120" value="<?php echo blogDashboardEscape($settings['portfolio_about']['header_title']); ?>" required>
                            </div>
                            <div class="field-wide" id="about-image-settings">
                                <label for="about_image">Left-side About image (JPEG, PNG, or WebP; max 2 MB)</label>
                                <?php if (!empty($settings['portfolio_about']['image'])): ?>
                                <img class="logo-preview" src="<?php echo blogDashboardEscape($settings['portfolio_about']['image']); ?>" alt="Current About portrait">
                                <label><input type="checkbox" name="remove_about_image" value="1"> Use the default portrait instead</label>
                                <?php endif; ?>
                                <input id="about_image" name="about_image" type="file" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="field-wide" id="about-statement-settings">
                                <label for="about_profile_kicker">Statement eyebrow</label>
                                <input id="about_profile_kicker" name="about_profile_kicker" type="text" maxlength="120" value="<?php echo blogDashboardEscape($settings['portfolio_about']['profile_kicker']); ?>" required>
                            </div>
                            <div>
                                <label for="about_statement_title">Statement title</label>
                                <input id="about_statement_title" name="about_statement_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($settings['portfolio_about']['statement_title']); ?>" required>
                            </div>
                            <div>
                                <label for="about_statement_accent">Statement highlighted title</label>
                                <input id="about_statement_accent" name="about_statement_accent" type="text" maxlength="160" value="<?php echo blogDashboardEscape($settings['portfolio_about']['statement_accent']); ?>" required>
                            </div>
                            <?php foreach ($settings['portfolio_about']['descriptions'] as $index => $description): ?>
                            <div class="field-wide">
                                <label for="about_description_<?php echo $index + 1; ?>">Description <?php echo $index + 1; ?></label>
                                <textarea id="about_description_<?php echo $index + 1; ?>" name="about_description_<?php echo $index + 1; ?>" maxlength="1500" required><?php echo blogDashboardEscape($description); ?></textarea>
                            </div>
                            <?php endforeach; ?>
                            <div id="about-values-settings">
                                <label for="about_objectives_kicker">Objectives section eyebrow</label>
                                <input id="about_objectives_kicker" name="about_objectives_kicker" type="text" maxlength="120" value="<?php echo blogDashboardEscape($settings['portfolio_about']['objectives_kicker']); ?>" required>
                            </div>
                            <div>
                                <label for="about_objectives_title">Objectives section title</label>
                                <input id="about_objectives_title" name="about_objectives_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($settings['portfolio_about']['objectives_title']); ?>" required>
                            </div>
                            <div class="about-values-editor">
                            <?php foreach ($settings['portfolio_about']['values'] as $key => $value): ?>
                            <div class="about-value-editor" id="about-value-<?php echo blogDashboardEscape($key); ?>">
                                <h3><?php echo blogDashboardEscape(ucfirst($key)); ?> card</h3>
                                <label for="about_value_<?php echo blogDashboardEscape($key); ?>_label">Grid title</label>
                                <input id="about_value_<?php echo blogDashboardEscape($key); ?>_label" name="about_value_<?php echo blogDashboardEscape($key); ?>_label" type="text" maxlength="100" value="<?php echo blogDashboardEscape($value['label']); ?>" required>
                                <label for="about_value_<?php echo blogDashboardEscape($key); ?>_description">Grid description</label>
                                <textarea id="about_value_<?php echo blogDashboardEscape($key); ?>_description" name="about_value_<?php echo blogDashboardEscape($key); ?>_description" maxlength="1500" required><?php echo blogDashboardEscape($value['description']); ?></textarea>
                            </div>
                            <?php endforeach; ?>
                            </div>
                            <div class="field-wide">
                                <label for="about_footer_text">Closing statement below the grids</label>
                                <textarea id="about_footer_text" name="about_footer_text" maxlength="1500" required><?php echo blogDashboardEscape($settings['portfolio_about']['footer_text']); ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer"><span class="hint">Changes are published in the portfolio About section after saving.</span><button class="button" type="submit" name="save_portfolio_about">Save About Me</button></div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_skills" aria-labelledby="portfolio-skills-title">
                <div class="card-head"><div><h2 id="portfolio-skills-title">Skills content</h2><p>Manage the public Skills / What I Do section, including its service cards. Hidden cards remain editable; removed cards are deleted when saved.</p></div></div>
                <div class="card-body">
                    <form method="post" id="portfolio-skills-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" id="skills_items_json" name="skills_items_json" value="">
                        <input type="hidden" id="skills-items-initial" value="<?php echo blogDashboardEscape(json_encode($portfolioSkillsPanel['items'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
                        <div class="portfolio-fields">
                            <div><label for="skills_title">Section title</label><input id="skills_title" name="skills_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioSkillsPanel['title']); ?>" required></div>
                            <div class="field-wide"><label for="skills_description">Section description</label><textarea id="skills_description" name="skills_description" maxlength="500" required><?php echo blogDashboardEscape($portfolioSkillsPanel['description']); ?></textarea></div>
                        </div>
                        <label class="home-visibility"><input type="checkbox" name="skills_visible" value="1"<?php echo !empty($portfolioSkillsPanel['visible']) ? ' checked' : ''; ?>> Show Skills section on the portfolio</label>
                        <p class="form-help">Use a Font Awesome icon class such as fa-code. Uncheck a card to hide it without deleting it. You can add up to 30 cards.</p>
                        <div class="about-content-list" id="skills-items-editor" aria-live="polite">
                            <?php foreach ($portfolioSkillsPanel['items'] as $index => $skill): ?>
                            <article class="about-content-item" data-skill-admin-card>
                                <h3>Skill card <?php echo $index + 1; ?></h3>
                                <div class="about-content-item-fields">
                                    <label>Title<input type="text" data-skill-title maxlength="120" value="<?php echo blogDashboardEscape($skill['title']); ?>" required></label>
                                    <label>Icon class<input type="text" data-skill-icon maxlength="63" pattern="fa-[a-z0-9-]{1,60}" value="<?php echo blogDashboardEscape($skill['icon']); ?>" required></label>
                                    <label class="field-wide">Description<textarea data-skill-description maxlength="1000" required><?php echo blogDashboardEscape($skill['description']); ?></textarea></label>
                                    <label class="field-wide">Details<textarea data-skill-details maxlength="2000" required><?php echo blogDashboardEscape($skill['details']); ?></textarea></label>
                                </div>
                                <div class="about-content-item-actions">
                                    <label class="home-visibility"><input type="checkbox" data-skill-visible value="1"<?php echo $skill['visible'] ? ' checked' : ''; ?>> Show this card on the portfolio</label>
                                    <button class="button danger small" type="button" data-remove-skill-card>Remove card</button>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="menu-editor-actions"><button class="button secondary small" type="button" id="add-skill-card">Add skill card</button></div>
                        <p class="section-editor-status" id="skills-editor-status" role="status" aria-live="polite"></p>
                        <div class="card-footer">
                            <span class="hint">Changes are published in the portfolio Skills section after saving.</span>
                            <div><button class="button secondary" type="button" id="cancel-skills-changes">Cancel</button><button class="button" type="submit" name="save_portfolio_skills">Save Skills</button></div>
                        </div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_journey" aria-labelledby="portfolio-journey-title">
                <div class="card-head"><div><h2 id="portfolio-journey-title">Journey content</h2><p>Edit the Journey section heading and the cards shown in its experience, education, and talent grid.</p></div></div>
                <div class="card-body">
                    <form method="post" id="portfolio-journey-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" id="journey_items_json" name="journey_items_json" value="">
                        <div class="portfolio-fields">
                            <div><label for="journey_eyebrow">Section eyebrow</label><input id="journey_eyebrow" name="journey_eyebrow" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioJourneyPanel['eyebrow']); ?>" required></div>
                            <div><label for="journey_title">Section title</label><input id="journey_title" name="journey_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioJourneyPanel['title']); ?>" required></div>
                            <div class="field-wide"><label for="journey_description">Section description</label><textarea id="journey_description" name="journey_description" maxlength="500" required><?php echo blogDashboardEscape($portfolioJourneyPanel['description']); ?></textarea></div>
                        </div>
                        <div class="about-content-list" id="journey-items-editor">
                            <?php foreach ($portfolioJourneyPanel['items'] as $index => $item): ?>
                            <article class="about-content-item" data-journey-admin-card>
                                <h3>Journey card <?php echo $index + 1; ?></h3>
                                <div class="about-content-item-fields">
                                    <label>Category<select data-journey-category required>
                                        <option value="educational"<?php echo $item['category'] === 'educational' ? ' selected' : ''; ?>>Educational</option>
                                        <option value="experience"<?php echo $item['category'] === 'experience' ? ' selected' : ''; ?>>Experience</option>
                                        <option value="talent"<?php echo $item['category'] === 'talent' ? ' selected' : ''; ?>>Special Talent</option>
                                    </select></label>
                                    <label>Card title<input type="text" data-journey-title maxlength="160" value="<?php echo blogDashboardEscape($item['title']); ?>" required></label>
                                    <label>Icon class<input type="text" data-journey-icon maxlength="63" pattern="fa-[a-z0-9-]{1,60}" value="<?php echo blogDashboardEscape($item['icon']); ?>" required></label>
                                    <label>Date or timeframe<input type="text" data-journey-date maxlength="120" value="<?php echo blogDashboardEscape($item['date']); ?>" required></label>
                                    <label class="field-wide">Summary<textarea data-journey-summary maxlength="1500" required><?php echo blogDashboardEscape($item['summary']); ?></textarea></label>
                                    <label class="field-wide">Full details<textarea data-journey-detail maxlength="3000" required><?php echo blogDashboardEscape($item['detail']); ?></textarea></label>
                                    <label class="field-wide">Highlights (one per line)<textarea data-journey-features maxlength="1500"><?php echo blogDashboardEscape(implode("\n", $item['features'])); ?></textarea></label>
                                    <label class="field-wide">Outcome<textarea data-journey-outcome maxlength="1500" required><?php echo blogDashboardEscape($item['outcome']); ?></textarea></label>
                                    <label class="field-wide">Image/document paths or URLs (one per line)<textarea data-journey-attachments maxlength="4000"><?php echo blogDashboardEscape(implode("\n", $item['attachments'])); ?></textarea><span class="form-help">Use safe relative paths or HTTP(S) URLs. Common image files display in the image viewer; other paths are offered as downloads.</span></label>
                                </div>
                                <div class="about-content-item-actions">
                                    <label class="home-visibility"><input type="checkbox" data-journey-visible value="1"<?php echo $item['visible'] ? ' checked' : ''; ?>> Show this card on the portfolio</label>
                                    <button class="button danger small" type="button" data-remove-journey-card>Remove card</button>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="menu-editor-actions">
                            <button class="button secondary small" type="button" id="add-journey-card">Add Journey card</button>
                        </div>
                        <div class="card-footer"><span class="hint">Changes are published in the Journey section after saving. Up to 20 cards, 12 highlights, and 8 attachments per card.</span><button class="button" type="submit" name="save_portfolio_journey">Save Journey</button></div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_faqs" aria-labelledby="portfolio-faqs-title">
                <div class="card-head"><div><h2 id="portfolio-faqs-title">FAQs content</h2><p>Edit the FAQ heading, category labels, and question cards shown on the portfolio.</p></div></div>
                <div class="card-body">
                    <form method="post" id="portfolio-faqs-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <input type="hidden" id="faq_items_json" name="faq_items_json" value="">
                        <div class="portfolio-fields">
                            <div class="field-wide"><label for="faq_title">Section title</label><input id="faq_title" name="faq_title" type="text" maxlength="160" value="<?php echo blogDashboardEscape($portfolioFaqsPanel['title']); ?>" required></div>
                            <?php foreach ($portfolioFaqsPanel['categories'] as $categoryKey => $categoryLabel): ?>
                            <div><label for="faq_category_<?php echo blogDashboardEscape($categoryKey); ?>">Category label: <?php echo blogDashboardEscape(ucfirst($categoryKey)); ?></label><input id="faq_category_<?php echo blogDashboardEscape($categoryKey); ?>" name="faq_category_<?php echo blogDashboardEscape($categoryKey); ?>" type="text" maxlength="80" value="<?php echo blogDashboardEscape($categoryLabel); ?>" required></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="about-content-list" id="faq-items-editor">
                            <?php foreach ($portfolioFaqsPanel['items'] as $index => $item): ?>
                            <article class="about-content-item" data-faq-admin-card>
                                <h3>FAQ <?php echo $index + 1; ?></h3>
                                <div class="about-content-item-fields">
                                    <label>Category<select data-faq-category required>
                                        <?php foreach ($portfolioFaqsPanel['categories'] as $categoryKey => $categoryLabel): ?>
                                        <option value="<?php echo blogDashboardEscape($categoryKey); ?>"<?php echo $item['category'] === $categoryKey ? ' selected' : ''; ?>><?php echo blogDashboardEscape($categoryLabel); ?></option>
                                        <?php endforeach; ?>
                                    </select></label>
                                    <label class="field-wide">Question<input type="text" data-faq-question maxlength="300" value="<?php echo blogDashboardEscape($item['question']); ?>" required></label>
                                    <label class="field-wide">Answer<textarea data-faq-answer maxlength="3000" required><?php echo blogDashboardEscape($item['answer']); ?></textarea></label>
                                </div>
                                <div class="about-content-item-actions">
                                    <label class="home-visibility"><input type="checkbox" data-faq-visible value="1"<?php echo $item['visible'] ? ' checked' : ''; ?>> Show this question on the portfolio</label>
                                    <button class="button danger small" type="button" data-remove-faq-card>Remove question</button>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="menu-editor-actions">
                            <button class="button secondary small" type="button" id="add-faq-card">Add FAQ</button>
                        </div>
                        <div class="card-footer"><span class="hint">Changes are published in the FAQ grid after saving. Up to 40 questions.</span><button class="button" type="submit" name="save_portfolio_faqs">Save FAQs</button></div>
                    </form>
                </div>
            </section>
            <section class="setting-card portfolio-card" id="portfolio_projects" aria-labelledby="portfolio-projects-title">
                <div class="card-head">
                    <div><h2 id="portfolio-projects-title">Projects and Strategy content</h2><p>Add, update, hide, restore, or remove the project cards and Mission, Vision, and Core Value cards shown in the public Projects section.</p></div>
                </div>
                <div class="card-body">
                    <form method="post" id="portfolio-projects-form">
                        <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                        <?php
                        $projectEditorInitial = $portfolioProjectsPanel;
                        foreach ($projectEditorInitial['projects'] as &$projectEditorItem) {
                            if (!isset($projectEditorItem['id']) || !is_string($projectEditorItem['id']) ||
                                preg_match('/\A[a-z0-9][a-z0-9-]{0,59}\z/', $projectEditorItem['id']) !== 1) {
                                $projectEditorItem['id'] = portfolioProjectPaymentLegacyId(
                                    (string) ($projectEditorItem['category'] ?? ''),
                                    (string) ($projectEditorItem['title'] ?? '')
                                );
                            }
                        }
                        unset($projectEditorItem);
                        ?>
                        <input type="hidden" id="portfolio-projects-initial" value="<?php echo blogDashboardEscape(json_encode($projectEditorInitial, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
                        <input type="hidden" id="portfolio-goal-defaults" value="<?php echo blogDashboardEscape(json_encode($default_settings['portfolio_projects']['goals'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
                        <input type="hidden" name="portfolio_projects_json" id="portfolio-projects-json">
                        <nav class="project-editor-shortcuts" aria-label="Projects editor sections">
                            <a href="#portfolio-project-list">All Projects</a>
                            <a href="#portfolio-goals-editor">Strategy</a>
                        </nav>
                        <section class="project-editor-section" id="portfolio-project-list" aria-labelledby="portfolio-project-list-title">
                            <h3 id="portfolio-project-list-title">All Projects</h3>
                            <p class="form-help">Hidden projects stay in the dashboard and are omitted from the public grid. Removing a card deletes it when you save.</p>
                            <div class="project-admin-list" id="project-admin-list" aria-live="polite"></div>
                            <div class="menu-editor-actions">
                                <button class="button secondary small" type="button" id="add-project-admin">Add project</button>
                            </div>
                        </section>
                        <section class="project-editor-section" id="portfolio-goals-editor" aria-labelledby="portfolio-goals-title">
                            <h3 id="portfolio-goals-title">Strategy</h3>
                            <p class="form-help">Edit and publish Mission, Vision, and Core Value. Hide a card to keep it saved but remove it from the public Strategy submenu.</p>
                            <div class="project-admin-list" id="goal-admin-list" aria-live="polite"></div>
                            <div class="menu-editor-actions">
                                <button class="button secondary small" type="button" id="add-goal-admin">Add Strategy card</button>
                            </div>
                        </section>
                        <p class="project-admin-status" id="project-admin-status" role="status" aria-live="polite"></p>
                        <div class="card-footer"><span class="hint">Changes are published on portfolio.php#work after saving.</span><button class="button" type="submit" name="save_portfolio_projects">Save Projects</button></div>
                    </form>
                    <section class="project-editor-section" id="portfolio-project-payments" aria-labelledby="portfolio-project-payments-title">
                        <h3 id="portfolio-project-payments-title">Source ZIP payments</h3>
                        <p class="form-help">Upload each project ZIP to private server storage. Buyers submit a transfer reference; approve only after you verify the payment. Transfers are manual, not gateway-processed.</p>
                        <?php if (is_array($portfolioProjectPaymentFlash) && !empty($portfolioProjectPaymentFlash['message'])): ?>
                        <p class="notice success" role="status"><?php echo blogDashboardEscape($portfolioProjectPaymentFlash['message']); ?></p>
                        <?php elseif (is_array($portfolioProjectPaymentFlash) && !empty($portfolioProjectPaymentFlash['error'])): ?>
                        <p class="notice error" role="alert"><?php echo blogDashboardEscape($portfolioProjectPaymentFlash['error']); ?></p>
                        <?php endif; ?>
                        <?php if ($portfolioProjectPaymentLoadError !== ''): ?>
                        <p class="notice error" role="alert"><?php echo blogDashboardEscape($portfolioProjectPaymentLoadError); ?></p>
                        <?php else: ?>
                        <form method="post" action="admin_project_payments.php" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                            <input type="hidden" name="payment_action" value="upload_archive">
                            <div class="portfolio-fields">
                                <label>Project<select name="project_key" required>
                                    <option value="">Choose a project</option>
                                    <?php foreach ($portfolioProjectPaymentCatalog as $projectKey => $projectRecord): ?>
                                    <option value="<?php echo blogDashboardEscape($projectKey); ?>"><?php echo blogDashboardEscape($projectRecord['title'] . ' · ' . $projectRecord['price_birr'] . ' Birr' . (isset($portfolioProjectPaymentArchives[$projectKey]) ? ' · ZIP uploaded' : ' · no ZIP')); ?></option>
                                    <?php endforeach; ?>
                                </select></label>
                                <label>Project ZIP (max 100 MB)<input type="file" name="project_zip" accept=".zip,application/zip" required></label>
                            </div>
                            <div class="card-footer"><span class="hint">Archives are stored outside the public web root.</span><button class="button" type="submit">Upload private ZIP</button></div>
                        </form>
                        <h4>Payment requests</h4>
                        <div class="social-feedback-table-wrap" tabindex="0" role="region" aria-label="Project payment requests; scroll horizontally to view all columns">
                            <table class="social-feedback-table">
                                <thead><tr><th scope="col">Project</th><th scope="col">Buyer email</th><th scope="col">Paid (Birr)</th><th scope="col">Method</th><th scope="col">Reference</th><th scope="col">Submitted</th><th scope="col">Status / action</th></tr></thead>
                                <tbody>
                                <?php if ($portfolioProjectPaymentRows === []): ?><tr><td colspan="7">No project payment requests have been submitted.</td></tr><?php endif; ?>
                                <?php foreach ($portfolioProjectPaymentRows as $paymentRequest): ?>
                                <tr>
                                    <td><?php echo blogDashboardEscape($paymentRequest['project_title']); ?></td>
                                    <td><?php echo blogDashboardEscape($paymentRequest['buyer_email']); ?></td>
                                    <td><?php echo blogDashboardEscape($paymentRequest['amount_birr']); ?></td>
                                    <td><?php echo blogDashboardEscape(['telebirr' => 'Telebirr', 'awash_pro' => 'Awash Pro', 'cbe_mobile' => 'CBE Mobile Bank'][$paymentRequest['payment_method']] ?? $paymentRequest['payment_method']); ?></td>
                                    <td><?php echo blogDashboardEscape($paymentRequest['payment_reference']); ?></td>
                                    <td><?php echo blogDashboardEscape($paymentRequest['submitted_at']); ?></td>
                                    <td>
                                        <strong><?php echo blogDashboardEscape(ucfirst($paymentRequest['status'])); ?></strong>
                                        <?php if ($paymentRequest['status'] === 'pending'): ?>
                                        <?php foreach (['approved' => 'Approve', 'rejected' => 'Reject'] as $decision => $decisionLabel): ?>
                                        <form method="post" action="admin_project_payments.php" style="display:inline-block;margin:5px 3px 0 0">
                                            <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                                            <input type="hidden" name="payment_action" value="review_request">
                                            <input type="hidden" name="request_id" value="<?php echo (int) $paymentRequest['request_id']; ?>">
                                            <input type="hidden" name="decision" value="<?php echo blogDashboardEscape($decision); ?>">
                                            <button class="button small<?php echo $decision === 'rejected' ? ' danger' : ''; ?>" type="submit"><?php echo blogDashboardEscape($decisionLabel); ?></button>
                                        </form>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>
            </section>
            <section class="settings-grid" aria-label="Blog content settings">
                <?php foreach ($setting_labels as $key => $meta): ?>
                <?php if ($key === 'portfolio_header' || $key === 'portfolio_home') { continue; } ?>
                <article class="setting-card" id="<?php echo blogDashboardEscape($key); ?>">
                    <div class="card-head"><div><h2><?php echo blogDashboardEscape($meta[0]); ?></h2><p><?php echo blogDashboardEscape($meta[1]); ?></p></div></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo blogDashboardEscape($_SESSION['blog_dashboard2_csrf']); ?>">
                            <input type="hidden" name="setting_key" value="<?php echo blogDashboardEscape($key); ?>">
                            <label for="json-<?php echo blogDashboardEscape($key); ?>">JSON content</label>
                            <textarea id="json-<?php echo blogDashboardEscape($key); ?>" name="setting_json" spellcheck="false" required><?php echo blogDashboardEscape(blogDashboardJson($settings[$key])); ?></textarea>
                            <div class="card-footer"><span class="hint">Valid JSON object or array required.</span><button class="button" type="submit" name="save_blog_json">Save section</button></div>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
            <div class="help"><strong>Tip:</strong> Images can use paths such as <code>uploads/blog/my-image.jpg</code> or full image URLs. Add an optional <code>video</code> URL to a homepage banner slide to use a muted looping video; its <code>image</code> remains the poster/fallback. Keep existing property names so the public blog can render the content.</div>
        </main>
        <footer class="footer"><strong>Admin Portal</strong> <span>Website content management</span></footer>
    </div>
</div>
<script>
    (() => {
        const form = document.getElementById('portfolio-projects-form');
        if (!form) return;
        const projectList = document.getElementById('project-admin-list');
        const goalList = document.getElementById('goal-admin-list');
        const output = document.getElementById('portfolio-projects-json');
        const status = document.getElementById('project-admin-status');
        const addProjectButton = document.getElementById('add-project-admin');
        const addGoalButton = document.getElementById('add-goal-admin');
        const categoryOptions = [
            ['portfolio', 'Personal Portfolio'],
            ['platform', 'Web Platform'],
            ['application', 'Web Application']
        ];
        const projectIds = new Set();
        const makeProjectId = project => {
            const suppliedId = typeof project.id === 'string' && /^[a-z0-9][a-z0-9-]{0,59}$/.test(project.id)
                ? project.id
                : `project-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
            let projectId = suppliedId;
            let suffix = 2;
            while (projectIds.has(projectId)) {
                projectId = `${suppliedId.slice(0, 55)}-${suffix++}`;
            }
            projectIds.add(projectId);
            return projectId;
        };
        let projectData;
        let goalDefaults;
        try {
            projectData = JSON.parse(document.getElementById('portfolio-projects-initial').value);
            goalDefaults = JSON.parse(document.getElementById('portfolio-goal-defaults').value);
            if (!projectData || !Array.isArray(projectData.projects) || !Array.isArray(projectData.goals) || !Array.isArray(goalDefaults)) {
                throw new Error('The saved project content has an invalid shape.');
            }
        } catch (error) {
            status.textContent = error instanceof Error ? error.message : 'Project content could not be loaded.';
            status.classList.add('social-feedback-error');
            addProjectButton.disabled = true;
            addGoalButton.disabled = true;
            form.querySelector('[name="save_portfolio_projects"]').disabled = true;
            return;
        }

        const createControl = (cardFields, labelText, key, value, type, options = [], maxLength = 1500) => {
            const label = document.createElement('label');
            label.append(document.createTextNode(labelText));
            const control = document.createElement(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input');
            if (type !== 'textarea' && type !== 'select') control.type = type;
            if (type === 'select') {
                options.forEach(([optionValue, optionLabel]) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionLabel;
                    control.append(option);
                });
            }
            control.dataset.projectField = key;
            control.value = value ?? '';
            if (maxLength > 0) control.maxLength = maxLength;
            label.append(control);
            cardFields.append(label);
            return control;
        };
        const createGoalControl = (cardFields, labelText, key, value, type, maxLength) => {
            const label = document.createElement('label');
            label.append(document.createTextNode(labelText));
            const control = document.createElement(type === 'textarea' ? 'textarea' : 'input');
            if (type !== 'textarea') control.type = 'text';
            control.dataset.goalField = key;
            control.value = value ?? '';
            control.maxLength = maxLength;
            label.append(control);
            cardFields.append(label);
            return control;
        };
        const createAction = (card, label, action, className = 'button secondary small') => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            button.dataset.projectAction = action;
            button.textContent = label;
            card.querySelector('.project-admin-actions').append(button);
            return button;
        };
        const addVisibilityToggle = (card, visible, kind) => {
            const label = document.createElement('label');
            label.className = 'home-visibility';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.dataset.projectVisible = '';
            checkbox.checked = visible !== false;
            label.append(checkbox, document.createTextNode(kind === 'project' ? ' Show this project on portfolio' : ' Show this Strategy card on portfolio'));
            card.querySelector('.project-admin-actions').append(label);
        };
        const toggleFields = (card, disabled, selector) => {
            card.querySelectorAll(selector).forEach(field => field.disabled = disabled);
        };
        const createProjectCard = (project = {}, editing = false) => {
            const card = document.createElement('article');
            card.className = 'project-admin-card';
            card.dataset.projectCard = '';
            card.dataset.projectId = makeProjectId(project);
            card.dataset.visible = String(project.visible !== false);
            const heading = document.createElement('h4');
            heading.textContent = project.title || 'New project';
            card.append(heading);
            const fields = document.createElement('div');
            fields.className = 'project-admin-fields';
            createControl(fields, 'Project category', 'category', project.category || 'portfolio', 'select', categoryOptions, 0).required = true;
            createControl(fields, 'Project title', 'title', project.title, 'text', [], 160).required = true;
            createControl(fields, 'Summary', 'summary', project.summary, 'textarea', [], 1500).required = true;
            createControl(fields, 'Image URL or safe relative path', 'image', project.image, 'text', [], 1000).required = true;
            createControl(fields, 'Image alt text', 'image_alt', project.image_alt, 'text', [], 200).required = true;
            createControl(fields, 'Technology tags (one per line)', 'tags', Array.isArray(project.tags) ? project.tags.join('\n') : '', 'textarea', [], 500).required = false;
            createControl(fields, 'My contribution', 'contribution', project.contribution, 'textarea', [], 1500).required = true;
            createControl(fields, 'Project outcome', 'outcome', project.outcome, 'textarea', [], 1500).required = true;
            const price = createControl(fields, 'Source ZIP price (Birr)', 'price_birr', project.price_birr ?? 100, 'number', [], 0);
            price.min = '0';
            price.max = '1000000';
            price.required = true;
            price.classList.add('project-price-field');
            fields.querySelectorAll('label').forEach((label, index) => {
                if ([2, 5, 6, 7].includes(index)) label.classList.add('field-wide');
            });
            card.append(fields);
            const actions = document.createElement('div');
            actions.className = 'project-admin-actions';
            card.append(actions);
            addVisibilityToggle(card, project.visible, 'project');
            const updateButton = createAction(card, 'Update', 'update', 'button small');
            updateButton.disabled = !editing;
            createAction(card, 'Edit / Modify', 'edit');
            createAction(card, project.visible === false ? 'Restore' : 'Ban from public view', 'visibility', 'button danger small');
            createAction(card, 'Remove', 'remove', 'button danger small');
            toggleFields(card, !editing, '[data-project-field]');
            projectList.append(card);
            return card;
        };
        const createGoalCard = (goal, editing = false) => {
            const card = document.createElement('article');
            card.className = 'project-admin-card';
            card.dataset.goalCard = '';
            card.dataset.goalId = goal.id;
            card.dataset.visible = String(goal.visible !== false);
            card.id = `portfolio-goal-${goal.id}`;
            const heading = document.createElement('h4');
            heading.textContent = goal.title || 'Strategy card';
            card.append(heading);
            const fields = document.createElement('div');
            fields.className = 'project-admin-fields';
            createGoalControl(fields, 'Card title', 'title', goal.title, 'text', 100).required = true;
            createGoalControl(fields, 'Font Awesome icon class', 'icon', goal.icon, 'text', 63).required = true;
            createGoalControl(fields, 'Short description', 'description', goal.description, 'textarea', 1500).required = true;
            createGoalControl(fields, 'Full details', 'detail', goal.detail, 'textarea', 3000).required = true;
            fields.querySelectorAll('label').forEach((label, index) => {
                if (index >= 2) label.classList.add('field-wide');
            });
            card.append(fields);
            const actions = document.createElement('div');
            actions.className = 'project-admin-actions';
            card.append(actions);
            addVisibilityToggle(card, goal.visible, 'goal');
            const updateButton = createAction(card, 'Update', 'update-goal', 'button small');
            updateButton.disabled = !editing;
            createAction(card, 'Edit / Modify', 'edit-goal');
            createAction(card, goal.visible === false ? 'Restore' : 'Ban from public view', 'visibility', 'button danger small');
            createAction(card, 'Remove', 'remove-goal', 'button danger small');
            toggleFields(card, !editing, '[data-goal-field]');
            goalList.append(card);
            return card;
        };
        const updateCardHeading = card => {
            const title = card.querySelector('[data-project-field="title"],[data-goal-field="title"]');
            card.querySelector('h4').textContent = title.value.trim() || (card.matches('[data-goal-card]') ? 'New Strategy card' : 'New project');
        };
        const updateCardVisibility = card => {
            const visible = card.querySelector('[data-project-visible]').checked;
            card.dataset.visible = String(visible);
            const button = card.querySelector('[data-project-action="visibility"]');
            button.textContent = visible ? 'Ban from public view' : 'Restore';
        };
        const readProject = card => ({
            id:card.dataset.projectId,
            category:card.querySelector('[data-project-field="category"]').value,
            title:card.querySelector('[data-project-field="title"]').value.trim(),
            summary:card.querySelector('[data-project-field="summary"]').value.trim(),
            image:card.querySelector('[data-project-field="image"]').value.trim(),
            image_alt:card.querySelector('[data-project-field="image_alt"]').value.trim(),
            tags:card.querySelector('[data-project-field="tags"]').value.split(/\r\n|\r|\n/).map(tag => tag.trim()).filter(Boolean),
            contribution:card.querySelector('[data-project-field="contribution"]').value.trim(),
            outcome:card.querySelector('[data-project-field="outcome"]').value.trim(),
            price_birr:Number(card.querySelector('[data-project-field="price_birr"]').value),
            visible:card.querySelector('[data-project-visible]').checked
        });
        const readGoal = card => ({
            id:card.dataset.goalId,
            title:card.querySelector('[data-goal-field="title"]').value.trim(),
            icon:card.querySelector('[data-goal-field="icon"]').value.trim(),
            description:card.querySelector('[data-goal-field="description"]').value.trim(),
            detail:card.querySelector('[data-goal-field="detail"]').value.trim(),
            visible:card.querySelector('[data-project-visible]').checked
        });

        projectData.projects.forEach(project => createProjectCard(project));
        projectData.goals.forEach(goal => createGoalCard(goal));
        addProjectButton.addEventListener('click', () => {
            if (projectList.children.length >= 24) {
                status.textContent = 'You can add up to 24 project cards.';
                return;
            }
            const card = createProjectCard({category:'portfolio', price_birr:100, tags:[], visible:true}, true);
            card.querySelector('[data-project-field="title"]').focus();
            status.textContent = 'Complete the new project fields, choose Update, then save Projects.';
        });
        addGoalButton.addEventListener('click', () => {
            const existing = new Set(Array.from(goalList.querySelectorAll('[data-goal-card]'), card => card.dataset.goalId));
            const nextGoal = goalDefaults.find(goal => !existing.has(goal.id));
            if (!nextGoal) {
                status.textContent = 'All three Strategy cards are already added.';
                return;
            }
            const card = createGoalCard(nextGoal, true);
            card.querySelector('[data-goal-field="title"]').focus();
            status.textContent = `Add or update the ${nextGoal.title} card, then save Projects.`;
        });
        [projectList, goalList].forEach(list => list.addEventListener('click', event => {
            const button = event.target.closest('[data-project-action]');
            const card = button?.closest('.project-admin-card');
            if (!button || !card) return;
            const isGoal = card.matches('[data-goal-card]');
            if (button.dataset.projectAction === 'edit' || button.dataset.projectAction === 'edit-goal') {
                toggleFields(card, false, isGoal ? '[data-goal-field]' : '[data-project-field]');
                card.querySelector(`[data-project-action="${isGoal ? 'update-goal' : 'update'}"]`).disabled = false;
                card.querySelector(`[data-${isGoal ? 'goal' : 'project'}-field]`).focus();
                status.textContent = 'Edit the card fields and choose Update before saving.';
            } else if (button.dataset.projectAction === 'update' || button.dataset.projectAction === 'update-goal') {
                const selector = isGoal ? '[data-goal-field]' : '[data-project-field]';
                const invalidField = Array.from(card.querySelectorAll(selector)).find(field => !field.checkValidity());
                if (invalidField) {
                    invalidField.reportValidity();
                    invalidField.focus();
                    return;
                }
                toggleFields(card, true, selector);
                button.disabled = true;
                updateCardHeading(card);
                status.textContent = 'Card updated in this form. Choose Save Projects to publish it.';
            } else if (button.dataset.projectAction === 'visibility') {
                const checkbox = card.querySelector('[data-project-visible]');
                checkbox.checked = !checkbox.checked;
                updateCardVisibility(card);
                status.textContent = checkbox.checked
                    ? 'Card will be visible on the portfolio after saving.'
                    : 'Card will be hidden from the portfolio after saving.';
            } else if (button.dataset.projectAction === 'remove' || button.dataset.projectAction === 'remove-goal') {
                const title = card.querySelector('[data-project-field="title"],[data-goal-field="title"]').value.trim();
                if (!window.confirm(`Remove "${title || 'this card'}"? Save Projects to publish the removal.`)) return;
                card.remove();
                status.textContent = 'Card removed from this form. Choose Save Projects to publish the removal.';
            }
        }));
        [projectList, goalList].forEach(list => list.addEventListener('input', event => {
            const card = event.target.closest('.project-admin-card');
            if (card && event.target.matches('[data-project-field="title"],[data-goal-field="title"]')) updateCardHeading(card);
        }));
        form.addEventListener('submit', event => {
            const projectCards = Array.from(projectList.querySelectorAll('[data-project-card]'));
            const goalCards = Array.from(goalList.querySelectorAll('[data-goal-card]'));
            const projects = projectCards.map(readProject);
            const goals = goalCards.map(readGoal);
            const invalidProjectCard = projectCards.find((card, index) => {
                const project = projects[index];
                return Array.from(card.querySelectorAll('[data-project-field]')).some(field => !field.checkValidity()) ||
                    project.tags.length > 12 || project.tags.some(tag => tag.length > 40) ||
                    !Number.isInteger(project.price_birr) || project.price_birr < 0 || project.price_birr > 1000000;
            });
            const invalidGoalCard = goalCards.find(card => Array.from(card.querySelectorAll('[data-goal-field]')).some(field => !field.checkValidity()));
            if (projects.length > 24 || goals.length > 3 || invalidProjectCard || invalidGoalCard) {
                event.preventDefault();
                status.textContent = 'Check required fields, project tags (up to 12), prices, and the 24-project / 3-Strategy-card limits.';
                status.classList.add('social-feedback-error');
                const invalidCard = invalidProjectCard || invalidGoalCard;
                invalidCard?.querySelector(':invalid')?.focus();
                return;
            }
            status.classList.remove('social-feedback-error');
            output.value = JSON.stringify({projects, goals});
        });
    })();

    (() => {
        const splitLines = value => value.split(/\r\n|\r|\n/).map(line => line.trim()).filter(Boolean);
        const makeControl = (labelText, value, type, attribute, options = []) => {
            const label = document.createElement('label');
            label.append(document.createTextNode(labelText));
            const control = document.createElement(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input');
            if (type !== 'textarea' && type !== 'select') control.type = type;
            control.dataset[attribute] = '';
            if (type === 'select') {
                options.forEach(([optionValue, optionLabel]) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionLabel;
                    control.append(option);
                });
            }
            control.value = value || '';
            label.append(control);
            return {label, control};
        };
        const makeVisibility = (checked, text, attribute) => {
            const label = document.createElement('label');
            label.className = 'home-visibility';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.dataset[attribute] = '';
            checkbox.checked = checked;
            label.append(checkbox, document.createTextNode(` ${text}`));
            return label;
        };
        const makeRemove = (text, attribute) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button danger small';
            button.dataset[attribute] = '';
            button.textContent = text;
            return button;
        };
        const renameCards = (editor, selector, title) => {
            editor.querySelectorAll(selector).forEach((card, index) => {
                card.querySelector('h3').textContent = `${title} ${index + 1}`;
            });
        };

        const journeyEditor = document.getElementById('journey-items-editor');
        const journeyForm = document.getElementById('portfolio-journey-form');
        const journeyJson = document.getElementById('journey_items_json');
        const journeyCategories = [['educational', 'Educational'], ['experience', 'Experience'], ['talent', 'Special Talent']];
        const addJourneyCard = (item = {}) => {
            if (journeyEditor.children.length >= 20) {
                window.alert('You can add up to 20 Journey cards.');
                return;
            }
            const card = document.createElement('article');
            card.className = 'about-content-item';
            card.dataset.journeyAdminCard = '';
            const heading = document.createElement('h3');
            card.append(heading);
            const fields = document.createElement('div');
            fields.className = 'about-content-item-fields';
            const controls = [
                makeControl('Category', item.category || 'experience', 'select', 'journeyCategory', journeyCategories),
                makeControl('Card title', item.title, 'text', 'journeyTitle'),
                makeControl('Icon class', item.icon || 'fa-briefcase', 'text', 'journeyIcon'),
                makeControl('Date or timeframe', item.date, 'text', 'journeyDate'),
                makeControl('Summary', item.summary, 'textarea', 'journeySummary'),
                makeControl('Full details', item.detail, 'textarea', 'journeyDetail'),
                makeControl('Highlights (one per line)', (item.features || []).join('\n'), 'textarea', 'journeyFeatures'),
                makeControl('Outcome', item.outcome, 'textarea', 'journeyOutcome'),
                makeControl('Image/document paths or URLs (one per line)', (item.attachments || []).join('\n'), 'textarea', 'journeyAttachments')
            ];
            controls.forEach(({label, control}, index) => {
                if (index >= 4) label.classList.add('field-wide');
                control.required = ![6, 8].includes(index);
                if (index === 1) control.maxLength = 160;
                if (index === 2) { control.maxLength = 63; control.pattern = 'fa-[a-z0-9-]{1,60}'; }
                if (index === 3) control.maxLength = 120;
                if ([4, 6, 7].includes(index)) control.maxLength = 1500;
                if (index === 5) { control.maxLength = 3000; control.required = true; }
                if (index === 8) control.maxLength = 4000;
                fields.append(label);
            });
            const attachmentHelp = document.createElement('p');
            attachmentHelp.className = 'form-help field-wide';
            attachmentHelp.textContent = 'Use safe relative paths or HTTP(S) URLs. Common image files display in the image viewer; other paths are offered as downloads.';
            fields.append(attachmentHelp);
            card.append(fields);
            const actions = document.createElement('div');
            actions.className = 'about-content-item-actions';
            actions.append(makeVisibility(item.visible !== false, 'Show this card on the portfolio', 'journeyVisible'));
            actions.append(makeRemove('Remove card', 'removeJourneyCard'));
            card.append(actions);
            journeyEditor.append(card);
            renameCards(journeyEditor, '[data-journey-admin-card]', 'Journey card');
        };
        if (journeyEditor && journeyForm && journeyJson) {
            document.getElementById('add-journey-card').addEventListener('click', () => addJourneyCard());
            journeyEditor.addEventListener('click', event => {
                if (!event.target.closest('[data-remove-journey-card]')) return;
                event.target.closest('[data-journey-admin-card]').remove();
                renameCards(journeyEditor, '[data-journey-admin-card]', 'Journey card');
            });
            journeyForm.addEventListener('submit', event => {
                const cards = Array.from(journeyEditor.querySelectorAll('[data-journey-admin-card]'));
                const items = cards.map(card => ({
                    category:card.querySelector('[data-journey-category]').value,
                    title:card.querySelector('[data-journey-title]').value.trim(),
                    icon:card.querySelector('[data-journey-icon]').value.trim(),
                    date:card.querySelector('[data-journey-date]').value.trim(),
                    summary:card.querySelector('[data-journey-summary]').value.trim(),
                    detail:card.querySelector('[data-journey-detail]').value.trim(),
                    features:splitLines(card.querySelector('[data-journey-features]').value),
                    outcome:card.querySelector('[data-journey-outcome]').value.trim(),
                    attachments:splitLines(card.querySelector('[data-journey-attachments]').value),
                    visible:card.querySelector('[data-journey-visible]').checked
                }));
                if (items.length > 20 || items.some(item => item.features.length > 12 || item.attachments.length > 8)) {
                    event.preventDefault();
                    window.alert('Each Journey card can have up to 12 highlights and 8 attachments.');
                    return;
                }
                journeyJson.value = JSON.stringify(items);
            });
        }

        const skillsEditor = document.getElementById('skills-items-editor');
        const skillsForm = document.getElementById('portfolio-skills-form');
        const skillsJson = document.getElementById('skills_items_json');
        const skillsStatus = document.getElementById('skills-editor-status');
        const skillsInitial = document.getElementById('skills-items-initial');
        if (skillsEditor && skillsForm && skillsJson && skillsStatus && skillsInitial) {
            const initialSkills = JSON.parse(skillsInitial.value);
            const updateSkillsVisibility = card => {
                card.classList.toggle('is-section-hidden', !card.querySelector('[data-skill-visible]').checked);
            };
            const addSkillCard = (skill = {}) => {
                if (skillsEditor.children.length >= 30) {
                    window.alert('You can add up to 30 Skills cards.');
                    return;
                }
                const card = document.createElement('article');
                card.className = 'about-content-item';
                card.dataset.skillAdminCard = '';
                const heading = document.createElement('h3');
                card.append(heading);
                const fields = document.createElement('div');
                fields.className = 'about-content-item-fields';
                const title = makeControl('Title', skill.title, 'text', 'skillTitle');
                title.control.maxLength = 120;
                title.control.required = true;
                const icon = makeControl('Icon class', skill.icon || 'fa-code', 'text', 'skillIcon');
                icon.control.maxLength = 63;
                icon.control.pattern = 'fa-[a-z0-9-]{1,60}';
                icon.control.required = true;
                const description = makeControl('Description', skill.description, 'textarea', 'skillDescription');
                description.control.maxLength = 1000;
                description.control.required = true;
                description.label.classList.add('field-wide');
                const details = makeControl('Details', skill.details, 'textarea', 'skillDetails');
                details.control.maxLength = 2000;
                details.control.required = true;
                details.label.classList.add('field-wide');
                fields.append(title.label, icon.label, description.label, details.label);
                card.append(fields);
                const actions = document.createElement('div');
                actions.className = 'about-content-item-actions';
                actions.append(makeVisibility(skill.visible !== false, 'Show this card on the portfolio', 'skillVisible'));
                actions.append(makeRemove('Remove card', 'removeSkillCard'));
                card.append(actions);
                skillsEditor.append(card);
                updateSkillsVisibility(card);
                renameCards(skillsEditor, '[data-skill-admin-card]', 'Skill card');
            };
            skillsEditor.addEventListener('input', event => {
                const card = event.target.closest('[data-skill-admin-card]');
                if (!card) return;
                if (event.target.matches('[data-skill-title]')) {
                    const index = Array.from(skillsEditor.children).indexOf(card) + 1;
                    card.querySelector('h3').textContent = event.target.value.trim() || `Skill card ${index}`;
                }
                skillsStatus.textContent = 'Unsaved changes';
            });
            skillsEditor.addEventListener('change', event => {
                const card = event.target.closest('[data-skill-admin-card]');
                if (card && event.target.matches('[data-skill-visible]')) updateSkillsVisibility(card);
                if (card) skillsStatus.textContent = 'Unsaved changes';
            });
            skillsEditor.addEventListener('click', event => {
                if (!event.target.closest('[data-remove-skill-card]')) return;
                event.target.closest('[data-skill-admin-card]').remove();
                renameCards(skillsEditor, '[data-skill-admin-card]', 'Skill card');
                skillsStatus.textContent = 'Unsaved changes';
            });
            document.getElementById('add-skill-card').addEventListener('click', () => {
                const previousCount = skillsEditor.children.length;
                addSkillCard();
                if (skillsEditor.children.length > previousCount) {
                    skillsEditor.lastElementChild.querySelector('[data-skill-title]').focus();
                    skillsStatus.textContent = 'Unsaved changes';
                }
            });
            document.getElementById('cancel-skills-changes').addEventListener('click', () => {
                skillsForm.reset();
                skillsEditor.replaceChildren();
                initialSkills.forEach(addSkillCard);
                skillsStatus.textContent = 'Changes cancelled.';
            });
            skillsForm.addEventListener('input', event => {
                if (event.target.name === 'skills_title' || event.target.name === 'skills_description') {
                    skillsStatus.textContent = 'Unsaved changes';
                }
            });
            skillsForm.addEventListener('change', event => {
                if (event.target.name === 'skills_visible') skillsStatus.textContent = 'Unsaved changes';
            });
            skillsForm.addEventListener('submit', event => {
                const cards = Array.from(skillsEditor.querySelectorAll('[data-skill-admin-card]'));
                const items = cards.map(card => ({
                    icon:card.querySelector('[data-skill-icon]').value.trim(),
                    title:card.querySelector('[data-skill-title]').value.trim(),
                    description:card.querySelector('[data-skill-description]').value.trim(),
                    details:card.querySelector('[data-skill-details]').value.trim(),
                    visible:card.querySelector('[data-skill-visible]').checked
                }));
                if (items.length > 30) {
                    event.preventDefault();
                    skillsStatus.textContent = 'You can save up to 30 Skills cards.';
                    return;
                }
                skillsJson.value = JSON.stringify(items);
            });
            skillsEditor.querySelectorAll('[data-skill-admin-card]').forEach(updateSkillsVisibility);
        }

        const faqEditor = document.getElementById('faq-items-editor');
        const faqForm = document.getElementById('portfolio-faqs-form');
        const faqJson = document.getElementById('faq_items_json');
        const faqCategoryLabels = [
            ['about', document.getElementById('faq_category_about')],
            ['experience', document.getElementById('faq_category_experience')],
            ['skills', document.getElementById('faq_category_skills')],
            ['working', document.getElementById('faq_category_working')]
        ];
        const makeFaqOptions = () => faqCategoryLabels.map(([key, input]) => [key, input.value.trim() || key]);
        const addFaqCard = (item = {}) => {
            if (faqEditor.children.length >= 40) {
                window.alert('You can add up to 40 FAQ cards.');
                return;
            }
            const card = document.createElement('article');
            card.className = 'about-content-item';
            card.dataset.faqAdminCard = '';
            const heading = document.createElement('h3');
            card.append(heading);
            const fields = document.createElement('div');
            fields.className = 'about-content-item-fields';
            const category = makeControl('Category', item.category || 'about', 'select', 'faqCategory', makeFaqOptions());
            const question = makeControl('Question', item.question, 'text', 'faqQuestion');
            const answer = makeControl('Answer', item.answer, 'textarea', 'faqAnswer');
            category.control.required = true;
            question.control.maxLength = 300;
            question.control.required = true;
            question.label.classList.add('field-wide');
            answer.control.maxLength = 3000;
            answer.control.required = true;
            answer.label.classList.add('field-wide');
            fields.append(category.label, question.label, answer.label);
            card.append(fields);
            const actions = document.createElement('div');
            actions.className = 'about-content-item-actions';
            actions.append(makeVisibility(item.visible !== false, 'Show this question on the portfolio', 'faqVisible'));
            actions.append(makeRemove('Remove question', 'removeFaqCard'));
            card.append(actions);
            faqEditor.append(card);
            renameCards(faqEditor, '[data-faq-admin-card]', 'FAQ');
        };
        if (faqEditor && faqForm && faqJson) {
            document.getElementById('add-faq-card').addEventListener('click', () => addFaqCard());
            faqEditor.addEventListener('click', event => {
                if (!event.target.closest('[data-remove-faq-card]')) return;
                event.target.closest('[data-faq-admin-card]').remove();
                renameCards(faqEditor, '[data-faq-admin-card]', 'FAQ');
            });
            faqForm.addEventListener('submit', event => {
                const items = Array.from(faqEditor.querySelectorAll('[data-faq-admin-card]'), card => ({
                    category:card.querySelector('[data-faq-category]').value,
                    question:card.querySelector('[data-faq-question]').value.trim(),
                    answer:card.querySelector('[data-faq-answer]').value.trim(),
                    visible:card.querySelector('[data-faq-visible]').checked
                }));
                if (items.length > 40) {
                    event.preventDefault();
                    window.alert('You can save up to 40 FAQ cards.');
                    return;
                }
                faqJson.value = JSON.stringify(items);
            });
            faqCategoryLabels.forEach(([, input]) => input.addEventListener('input', () => {
                faqEditor.querySelectorAll('[data-faq-category]').forEach(select => {
                    const selected = select.value;
                    select.replaceChildren(...makeFaqOptions().map(([value, label]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = label;
                        return option;
                    }));
                    select.value = selected;
                });
            }));
        }
    })();
</script>
<script>
    (() => {
        const editor = document.getElementById('menu-editor');
        const addLinkButton = document.getElementById('add-menu-link');
        const linksField = document.getElementById('home_menu_links');
        const statsEditor = document.getElementById('home-stats-editor');
        const statsField = document.getElementById('home_stats');
        const addStatButton = document.getElementById('add-home-stat');
        const form = document.getElementById('portfolio-home-form');
        const initialLinks = <?= json_encode($portfolioHomePanel['menu_links'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const filters = [['', 'No project filter'], ['all', 'All projects'], ['design', 'Product design'], ['development', 'Development'], ['strategy', 'Strategy']];
        const previewHeadline = document.getElementById('home-preview-headline');
        const previewIntro = document.getElementById('home-preview-intro');
        const previewRoles = document.getElementById('home-preview-roles');
        const previewActions = document.getElementById('home-preview-actions');
        const previewStats = document.getElementById('home-preview-stats');
        const previewImage = document.getElementById('home-preview-image');
        const previewBackground = document.getElementById('home-preview-background');
        const homeShortcuts = document.querySelectorAll('.home-editor-shortcuts a');
        const previewChip = (text, hidden = false) => {
            const chip = document.createElement('span');
            chip.textContent = text;
            if (hidden) chip.classList.add('is-hidden');
            return chip;
        };
        const updateHomePreview = () => {
            previewHeadline.textContent = form.elements.home_headline.value.trim() || 'Your headline';
            previewIntro.textContent = form.elements.home_intro.value.trim() || 'Your introduction';
            previewHeadline.classList.toggle('is-hidden', !form.elements.home_show_headline.checked);
            previewIntro.classList.toggle('is-hidden', !form.elements.home_show_intro.checked);
            const portraitSource = form.elements.home_portrait_url.value.trim();
            const backgroundSource = form.elements.home_background_url.value.trim();
            if (portraitSource) previewImage.src = portraitSource;
            else previewImage.removeAttribute('src');
            previewImage.classList.toggle('is-hidden', !form.elements.home_show_portrait.checked || !portraitSource);
            if (backgroundSource) previewBackground.src = backgroundSource;
            else previewBackground.removeAttribute('src');
            previewBackground.style.opacity = form.elements.home_show_background.checked && backgroundSource ? '.12' : '0';
            const roles = form.elements.home_roles.value.split(/\r\n|\r|\n/).map(role => role.trim()).filter(Boolean);
            previewRoles.replaceChildren(...(roles.length ? roles : ['No role labels']).map(role =>
                previewChip(role, !form.elements.home_show_roles.checked || role === 'No role labels')));
            const actions = [
                ['home_hire_label', 'home_show_hire_button'],
                ['home_cv_label', 'home_show_cv_button'],
                ['home_certificates_label', 'home_show_certificates_button'],
                ['home_subscription_label', 'home_show_subscription_button']
            ];
            previewActions.replaceChildren(...actions.map(([label, visible]) =>
                previewChip(form.elements[label].value.trim() || 'Button', !form.elements[visible].checked)));
            const stats = Array.from(statsEditor.querySelectorAll('.home-stat-editor'), row => ({
                label:row.querySelector('[data-stat-label]').value.trim() || 'Untitled metric',
                value:row.querySelector('[data-stat-value]').value,
                visible:row.querySelector('[data-stat-visible]').checked
            }));
            previewStats.replaceChildren(...(stats.length
                ? stats.map(stat => previewChip(`${stat.label}: ${stat.value}`, !stat.visible))
                : [previewChip('No metric cards', true)]));
            const portraitVisible = form.elements.home_show_portrait.checked;
            ['home_portrait_url', 'home_portrait_bio_name', 'home_portrait_bio_kicker', 'home_portrait_skills', 'home_portrait_bio'].forEach(name => {
                form.elements[name].required = portraitVisible;
            });
        };
        form.addEventListener('input', updateHomePreview);
        form.addEventListener('change', updateHomePreview);
        statsEditor.addEventListener('input', updateHomePreview);
        statsEditor.addEventListener('change', updateHomePreview);
        updateHomePreview();
        homeShortcuts.forEach(link => link.addEventListener('click', () => {
            homeShortcuts.forEach(item => item.removeAttribute('aria-current'));
            link.setAttribute('aria-current', 'location');
        }));
        if ('IntersectionObserver' in window) {
            const homeSectionObserver = new IntersectionObserver(entries => {
                const activeEntry = entries.filter(entry => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (!activeEntry) return;
                const activeLink = Array.from(homeShortcuts).find(link => link.hash === `#${activeEntry.target.id}`);
                if (!activeLink) return;
                homeShortcuts.forEach(link => link.removeAttribute('aria-current'));
                activeLink.setAttribute('aria-current', 'location');
            }, {rootMargin:'-18% 0px -65% 0px',threshold:[0,.25,.5]});
            document.querySelectorAll('#home-hero-settings,#home-intro-settings,#home-portrait-settings,#home-background-settings,#home-actions-settings,#home-stats-settings,#home-menu-settings').forEach(section => homeSectionObserver.observe(section));
        }

        const makeField = (labelText, value, placeholder, className) => {
            const wrapper = document.createElement('label');
            wrapper.textContent = labelText;
            if (className) wrapper.className = className;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = typeof value === 'string' ? value : '';
            input.placeholder = placeholder;
            input.maxLength = labelText.toLowerCase().includes('label') ? 80 : 500;
            wrapper.append(input);
            return {wrapper, input};
        };
        const makeFilter = value => {
            const wrapper = document.createElement('label');
            wrapper.textContent = 'Project filter';
            const select = document.createElement('select');
            select.style.cssText = 'width:100%;min-height:38px;padding:7px 10px;border:1px solid #ced4da;border-radius:4px;background:#fff;font:inherit';
            filters.forEach(([filter, label]) => {
                const option = document.createElement('option');
                option.value = filter;
                option.textContent = label;
                select.append(option);
            });
            select.value = filters.some(([filter]) => filter === value) ? value : '';
            wrapper.append(select);
            return {wrapper, select};
        };
        const makeRemoveButton = () => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button danger small';
            button.textContent = 'Remove';
            return button;
        };
        const makeStatInput = (labelText, value, type, dataAttribute) => {
            const label = document.createElement('label');
            label.append(document.createTextNode(labelText));
            const input = document.createElement('input');
            input.type = type;
            input.value = value;
            input.dataset[dataAttribute] = '';
            if (type === 'text') input.maxLength = dataAttribute === 'statSuffix' ? 12 : 80;
            if (type === 'number') {
                input.min = '0';
                input.max = '999999';
                input.step = '1';
                input.required = true;
            }
            if (dataAttribute === 'statLabel') input.required = true;
            label.append(input);
            return {label, input};
        };
        const addHomeStat = (stat = {}) => {
            if (statsEditor.children.length >= 8) return;
            const row = document.createElement('div');
            row.className = 'home-stat-editor';
            const label = makeStatInput('Metric label', stat.label || '', 'text', 'statLabel');
            const value = makeStatInput('Number', String(Number.isInteger(stat.value) ? stat.value : 0), 'number', 'statValue');
            const suffix = makeStatInput('Suffix', typeof stat.suffix === 'string' ? stat.suffix : '+', 'text', 'statSuffix');
            const remove = makeRemoveButton();
            remove.setAttribute('aria-label', 'Remove metric');
            remove.dataset.removeStat = '';
            const visibilityLabel = document.createElement('label');
            visibilityLabel.className = 'home-visibility';
            const visible = document.createElement('input');
            visible.type = 'checkbox';
            visible.dataset.statVisible = '';
            visible.checked = stat.visible !== false;
            visibilityLabel.append(visible, document.createTextNode(' Show on portfolio'));
            row.append(label.label, value.label, suffix.label, remove, visibilityLabel);
            statsEditor.append(row);
        };
        statsEditor.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-stat]');
            if (remove) remove.closest('.home-stat-editor').remove();
            updateHomePreview();
        });
        addStatButton.addEventListener('click', () => {
            if (statsEditor.children.length >= 8) {
                window.alert('You can add up to 8 metrics.');
                return;
            }
            addHomeStat();
            updateHomePreview();
        });
        const addChild = (children, child = {}) => {
            if (children.children.length >= 10) return;
            const row = document.createElement('div');
            row.className = 'menu-child-editor';
            const childId = typeof child.label === 'string'
                ? child.label.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
                : '';
            if (childId) row.id = `portfolio-menu-${childId}`;
            const label = makeField('Sublink label', child.label, 'e.g. Skills');
            const url = makeField('Sublink URL', child.url, '#skills');
            const filter = makeFilter(child.filter);
            const remove = makeRemoveButton();
            remove.addEventListener('click', () => row.remove());
            row.append(label.wrapper, url.wrapper, filter.wrapper, remove);
            row._menuFields = {label:label.input, url:url.input, filter:filter.select};
            children.append(row);
        };
        const addLink = (item = {}) => {
            if (editor.children.length >= 12) return;
            const card = document.createElement('div');
            card.className = 'menu-item-editor';
            const fields = document.createElement('div');
            fields.className = 'menu-item-fields';
            const label = makeField('Label', item.label, 'e.g. About');
            const url = makeField('Link URL', item.url, '#about');
            const filter = makeFilter(item.filter);
            const remove = makeRemoveButton();
            remove.addEventListener('click', () => card.remove());
            fields.append(label.wrapper, url.wrapper, filter.wrapper, remove);
            const children = document.createElement('div');
            children.className = 'menu-children';
            (Array.isArray(item.children) ? item.children : []).forEach(child => addChild(children, child));
            const actions = document.createElement('div');
            actions.className = 'menu-editor-actions';
            const addSublink = document.createElement('button');
            addSublink.type = 'button';
            addSublink.className = 'button secondary small';
            addSublink.textContent = 'Add sublink (makes dropdown)';
            addSublink.addEventListener('click', () => addChild(children));
            actions.append(addSublink);
            card.append(fields, children, actions);
            card._menuFields = {label:label.input, url:url.input, filter:filter.select, children};
            editor.append(card);
        };
        const serializeLinks = () => Array.from(editor.children, card => {
            const fields = card._menuFields;
            const item = {label:fields.label.value.trim(), url:fields.url.value.trim(), children:Array.from(fields.children.children, child => {
                const childFields = child._menuFields;
                const sublink = {label:childFields.label.value.trim(), url:childFields.url.value.trim()};
                if (childFields.filter.value) sublink.filter = childFields.filter.value;
                return sublink;
            })};
            if (fields.filter.value) item.filter = fields.filter.value;
            return item;
        });

        initialLinks.forEach(addLink);
        addLinkButton.addEventListener('click', () => addLink());
        form.addEventListener('submit', event => {
            const links = serializeLinks();
            if (links.length === 0 || links.some(link => !link.label || (!link.url && link.children.length === 0) || link.children.some(child => !child.label || !child.url))) {
                event.preventDefault();
                window.alert('Add at least one navigation link. Every link and sublink needs a label and valid URL.');
                return;
            }
            linksField.value = JSON.stringify(links);
            statsField.value = JSON.stringify(Array.from(statsEditor.querySelectorAll('.home-stat-editor'), row => ({
                label:row.querySelector('[data-stat-label]').value.trim(),
                value:Number(row.querySelector('[data-stat-value]').value),
                suffix:row.querySelector('[data-stat-suffix]').value,
                visible:row.querySelector('[data-stat-visible]').checked
            })));
        });
    })();

    (() => {
        const form = document.getElementById('portfolio-social-posts-form');
        const editor = document.getElementById('social-post-editor');
        const valueField = document.getElementById('portfolio-social-posts-value');
        const selector = document.getElementById('social-post-selector');
        if (!form || !editor || !valueField || !selector) return;

        const createField = (labelText, dataKey, value = '', type = 'text', maxLength = 1000) => {
            const label = document.createElement('label');
            label.append(document.createTextNode(labelText));
            const input = document.createElement(type === 'textarea' ? 'textarea' : 'input');
            if (type !== 'textarea') input.type = type;
            input.dataset[dataKey] = '';
            if (maxLength > 0) input.maxLength = maxLength;
            input.value = value;
            input.required = true;
            label.append(input);
            return {label, input};
        };
        const nextId = () => {
            const base = `post-${Date.now().toString(36)}`;
            const existing = new Set(Array.from(editor.querySelectorAll('[data-social-id]'), input => input.value));
            let id = base;
            let suffix = 2;
            while (existing.has(id)) id = `${base}-${suffix++}`;
            return id;
        };
        const getFields = card => ({
            id:card.querySelector('[data-social-id]'),
            mediaType:card.querySelector('[data-social-media-type]'),
            image:card.querySelector('[data-social-image]'),
            alt:card.querySelector('[data-social-alt]'),
            title:card.querySelector('[data-social-title]'),
            description:card.querySelector('[data-social-description]'),
            visible:card.querySelector('[data-social-visible]'),
            featured:card.querySelector('[data-social-featured]'),
            publisherName:card.querySelector('[data-social-publisher-name]'),
            publisherRole:card.querySelector('[data-social-publisher-role]'),
            publishedAt:card.querySelector('[data-social-published-at]')
        });
        const updateSelectorOption = card => {
            const fields = getFields(card);
            const option = selector.querySelector(`option[value="${CSS.escape(card.id)}"]`);
            if (!option) return;
            const date = fields.publishedAt.value ? new Date(fields.publishedAt.value) : null;
            const dateLabel = date && !Number.isNaN(date.getTime())
                ? new Intl.DateTimeFormat(undefined, {dateStyle:'medium', timeStyle:'short'}).format(date)
                : 'No date';
            option.textContent = `${fields.title.value.trim() || 'Untitled post'} — ${dateLabel}`;
        };
        const activateTab = (card, tab, moveFocus = false) => {
            card.querySelectorAll('[role="tab"]').forEach(item => {
                const selected = item === tab;
                item.setAttribute('aria-selected', String(selected));
                item.tabIndex = selected ? 0 : -1;
                const panel = card.querySelector(`#${CSS.escape(item.getAttribute('aria-controls'))}`);
                if (panel) panel.hidden = !selected;
            });
            const postActions = card.querySelector('.social-post-editor-actions');
            if (postActions) {
                postActions.hidden = tab.getAttribute('aria-controls')?.endsWith('-panel-feedback') === true;
            }
            if (moveFocus) tab.focus();
        };
        const showPost = card => {
            editor.querySelectorAll('.social-post-editor-card').forEach(item => {
                item.hidden = item !== card;
            });
            selector.value = card.id;
            card.focus({preventScroll:true});
        };
        const setEditing = (card, editing) => {
            card.dataset.editing = String(editing);
            const fields = getFields(card);
            [
                fields.id, fields.mediaType, fields.image, fields.alt, fields.title,
                fields.description, fields.publisherName, fields.publisherRole, fields.publishedAt
            ].forEach(field => field.disabled = !editing);
            const editButton = card.querySelector('[data-social-edit]');
            const updateButton = card.querySelector('[data-social-update]');
            editButton.textContent = editing ? 'Editing post…' : 'Edit / Modify';
            editButton.disabled = editing;
            updateButton.disabled = !editing;
        };
        const validatePost = card => {
            const fields = getFields(card);
            const requiredFields = [
                fields.id, fields.mediaType, fields.image, fields.alt, fields.title,
                fields.description, fields.publisherName, fields.publisherRole, fields.publishedAt
            ];
            return requiredFields.every(field => field.value.trim() !== '' && field.checkValidity()) &&
                /^[a-z0-9][a-z0-9-]{0,39}$/.test(fields.id.value.trim());
        };
        const feedbackCategories = [
            ['comments', 'Comments'],
            ['replies', 'Comment replies'],
            ['likes', 'Likes'],
            ['shares', 'Shares'],
            ['share-replies', 'Share replies']
        ];
        const categoryForFeedbackItem = item => {
            if (item.kind === 'comment') return 'comments';
            if (item.kind === 'reply') return 'replies';
            if (['post_like', 'comment_like', 'comment_reaction'].includes(item.kind)) return 'likes';
            if (['post_share', 'comment_share'].includes(item.kind)) return 'shares';
            return '';
        };
        const renderFeedbackTable = (card, category, items) => {
            const body = card.querySelector(`[data-feedback-list="${category}"]`);
            if (!body) return;
            const entries = items.filter(item => categoryForFeedbackItem(item) === category);
            if (!entries.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 8;
                cell.className = 'social-feedback-empty';
                cell.textContent = category === 'share-replies'
                    ? 'Replies to shares are not collected by the public feed.'
                    : 'No activity yet.';
                row.append(cell);
                body.replaceChildren(row);
                return;
            }
            body.replaceChildren(...entries.map(item => {
                const row = document.createElement('tr');
                row.className = 'social-feedback-entry';
                row.dataset.feedbackKind = item.kind;
                row.dataset.feedbackToken = item.record_token;
                row.dataset.feedbackCommentId = item.comment_id || '';
                row.dataset.feedbackRecordId = item.record_id || '';
                row._feedbackItem = item;
                if (item.hidden) row.classList.add('is-hidden-feedback');
                const values = [
                    item.name || 'Visitor',
                    item.comment || '',
                    item.kind === 'reply' ? item.reply : '',
                    ['post_like', 'comment_like', 'comment_reaction'].includes(item.kind) ? item.activity : '',
                    ['post_share', 'comment_share'].includes(item.kind) ? item.activity : '',
                    ['post_share', 'comment_share'].includes(item.kind) ? 'No share replies recorded' : '—',
                    item.created_at || 'Not recorded for likes and shares'
                ];
                const cells = values.map(value => {
                    const cell = document.createElement('td');
                    cell.textContent = value || '—';
                    row.append(cell);
                    return cell;
                });
                const actionCell = document.createElement('td');
                actionCell.className = 'social-feedback-actions';
                if (item.editable) {
                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'button secondary small';
                    editButton.dataset.feedbackEdit = '';
                    editButton.textContent = 'Edit / Modify';
                    const updateButton = document.createElement('button');
                    updateButton.type = 'button';
                    updateButton.className = 'button small';
                    updateButton.dataset.feedbackUpdate = '';
                    updateButton.textContent = 'Update';
                    updateButton.hidden = true;
                    const editor = document.createElement('textarea');
                    editor.className = 'social-feedback-edit-input';
                    editor.dataset.feedbackEditInput = '';
                    editor.maxLength = item.kind === 'reply' ? 1000 : 4000;
                    editor.value = item.kind === 'comment' ? item.comment : item.reply;
                    editor.hidden = true;
                    editor.setAttribute('aria-label', `Edit ${item.kind === 'comment' ? 'comment' : 'comment reply'}`);
                    if (item.kind === 'comment') cells[1].append(editor);
                    else cells[2].append(editor);
                    actionCell.append(editButton, updateButton);
                }
                const visibilityButton = document.createElement('button');
                visibilityButton.type = 'button';
                visibilityButton.className = 'button danger small';
                visibilityButton.dataset.feedbackVisibility = item.hidden ? 'restore' : 'hide';
                visibilityButton.textContent = item.hidden ? 'Restore' : 'Ban from user / Hide';
                actionCell.append(visibilityButton);
                row.append(actionCell);
                return row;
            }));
        };
        const moderateFeedback = async (card, row, moderationAction) => {
            const errorMessage = card.querySelector('[data-social-feedback-error]');
            const item = row._feedbackItem;
            const editInput = row.querySelector('[data-feedback-edit-input]');
            const csrfToken = document.querySelector('#portfolio-social-posts-form input[name="csrf_token"]')?.value || '';
            errorMessage.hidden = true;
            try {
                const response = await fetch('social_feed_api.php?action=admin_moderate', {
                    method:'POST',
                    credentials:'same-origin',
                    headers:{
                        Accept:'application/json',
                        'Content-Type':'application/json',
                        'X-CSRF-Token':csrfToken
                    },
                    body:JSON.stringify({
                        post_id:getFields(card).id.value.trim(),
                        kind:item.kind,
                        comment_id:item.comment_id || '',
                        record_id:item.record_id || '',
                        record_token:item.record_token,
                        moderation_action:moderationAction,
                        text:editInput?.value || ''
                    })
                });
                let result;
                try {
                    result = await response.json();
                } catch {
                    throw new Error('The social feedback service returned an unreadable response.');
                }
                if (!response.ok) throw new Error(result.error || 'Could not update social feedback.');
                card.dataset.feedbackLoaded = 'false';
                await loadAdminFeedback(card, true);
                errorMessage.className = 'social-feedback-success';
                errorMessage.textContent = moderationAction === 'edit'
                    ? 'Feedback updated.'
                    : moderationAction === 'hide' ? 'Feedback hidden from visitors.' : 'Feedback restored for visitors.';
                errorMessage.hidden = false;
            } catch (error) {
                errorMessage.className = 'social-feedback-error';
                errorMessage.textContent = error instanceof Error ? error.message : 'Could not update social feedback.';
                errorMessage.hidden = false;
            }
        };
        const loadAdminFeedback = async (card, refresh = false) => {
            if (card.dataset.feedbackLoaded === 'true' && !refresh) return;
            const errorMessage = card.querySelector('[data-social-feedback-error]');
            const refreshButton = card.querySelector('[data-social-feedback-refresh]');
            errorMessage.className = 'social-feedback-error';
            errorMessage.hidden = true;
            refreshButton.disabled = true;
            refreshButton.textContent = 'Loading…';
            try {
                const response = await fetch('social_feed_api.php?action=admin_load', {
                    method:'GET',
                    credentials:'same-origin',
                    headers:{Accept:'application/json'}
                });
                let result;
                try {
                    result = await response.json();
                } catch {
                    throw new Error('The social feedback service returned an unreadable response.');
                }
                if (!response.ok) throw new Error(result.error || 'Could not load social feedback.');
                const postId = getFields(card).id.value.trim();
                const state = result.posts && result.posts[postId] ? result.posts[postId] : {items:[]};
                const items = Array.isArray(state.items) ? state.items : [];
                card._adminFeedbackItems = items;
                feedbackCategories.forEach(([category]) => renderFeedbackTable(card, category, items));
                const counts = Object.fromEntries(feedbackCategories.map(([category]) => [
                    category,
                    items.filter(item => categoryForFeedbackItem(item) === category).length
                ]));
                card.querySelectorAll('[data-feedback-view]').forEach(button => {
                    const category = button.dataset.feedbackView;
                    const label = feedbackCategories.find(([key]) => key === category)?.[1];
                    if (label) {
                        button.replaceChildren(document.createTextNode(`${label} `));
                        const count = document.createElement('span');
                        count.className = 'social-feedback-count';
                        count.textContent = String(counts[category] || 0);
                        button.append(count);
                    }
                });
                card.dataset.feedbackLoaded = 'true';
            } catch (error) {
                errorMessage.textContent = error instanceof Error ? error.message : 'Could not load social feedback.';
                errorMessage.hidden = false;
            } finally {
                refreshButton.disabled = false;
                refreshButton.textContent = 'Refresh feedback';
            }
        };
        const createFeedbackPanel = panel => {
            const tools = document.createElement('div');
            tools.className = 'social-feedback-tools';
            const refresh = document.createElement('button');
            refresh.type = 'button';
            refresh.className = 'button secondary small';
            refresh.dataset.socialFeedbackRefresh = '';
            refresh.textContent = 'Refresh feedback';
            tools.append(refresh);
            panel.append(tools);
            const tabs = document.createElement('div');
            tabs.className = 'social-feedback-tabs';
            tabs.setAttribute('role', 'group');
            tabs.setAttribute('aria-label', 'Social feedback categories');
            feedbackCategories.forEach(([key, label], index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'social-feedback-tab';
                button.dataset.feedbackView = key;
                button.setAttribute('aria-pressed', String(index === 0));
                button.textContent = label;
                tabs.append(button);
            });
            panel.append(tabs);
            const error = document.createElement('p');
            error.className = 'social-feedback-error';
            error.dataset.socialFeedbackError = '';
            error.setAttribute('role', 'status');
            error.setAttribute('aria-live', 'polite');
            error.hidden = true;
            panel.append(error);
            feedbackCategories.forEach(([key], index) => {
                const categoryPanel = document.createElement('section');
                categoryPanel.className = 'social-feedback-panel';
                categoryPanel.dataset.feedbackPanel = key;
                categoryPanel.hidden = index !== 0;
                const tableWrap = document.createElement('div');
                tableWrap.className = 'social-feedback-table-wrap';
                tableWrap.tabIndex = 0;
                tableWrap.setAttribute('role', 'region');
                tableWrap.setAttribute('aria-label', `${feedbackCategories[index][1]} table; scroll horizontally to view all columns`);
                const table = document.createElement('table');
                table.className = 'social-feedback-table';
                const header = document.createElement('thead');
                const headerRow = document.createElement('tr');
                ['Name', 'Comments', 'Comments reply', 'Likes', 'Shares (details to where)', 'Share reply', 'Date and time', 'Action']
                    .forEach(label => {
                        const cell = document.createElement('th');
                        cell.scope = 'col';
                        cell.textContent = label;
                        headerRow.append(cell);
                    });
                header.append(headerRow);
                const body = document.createElement('tbody');
                body.dataset.feedbackList = key;
                const empty = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 8;
                emptyCell.className = 'social-feedback-empty';
                emptyCell.textContent = 'Select this tab to load feedback.';
                empty.append(emptyCell);
                body.append(empty);
                table.append(header, body);
                tableWrap.append(table);
                categoryPanel.append(tableWrap);
                panel.append(categoryPanel);
            });
            const note = document.createElement('p');
            note.className = 'form-help';
            note.textContent = 'Feedback edits and visibility changes are saved immediately. The public feed does not record share destinations or support replies to shares. Post-widget changes remain staged until Save Social Widgets.';
            panel.append(note);
            const linkNote = document.createElement('p');
            linkNote.className = 'form-help';
            const link = document.createElement('a');
            link.href = 'portfolio.php#socialFeed';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Open public Social Feed ↗';
            linkNote.append(link);
            panel.append(linkNote);
            return panel;
        };
        const addActionButton = (actions, label, key, className = 'button secondary small') => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            button.dataset[key] = '';
            button.textContent = label;
            actions.append(button);
            return button;
        };
        const addPost = (post = {}) => {
            if (editor.querySelectorAll('.social-post-editor-card').length >= 24) {
                window.alert('You can add up to 24 social post grids.');
                return null;
            }
            const card = document.createElement('article');
            card.className = 'social-post-editor-card';
            const baseCardId = `social-post-editor-card-${Date.now().toString(36)}`;
            let cardId = baseCardId;
            let cardIdSuffix = 2;
            while (document.getElementById(cardId)) cardId = `${baseCardId}-${cardIdSuffix++}`;
            card.id = cardId;
            card.hidden = true;
            card.tabIndex = -1;
            const heading = document.createElement('div');
            heading.className = 'social-post-editor-heading';
            const title = document.createElement('strong');
            title.textContent = 'Post widget';
            heading.append(title);

            const tabs = document.createElement('div');
            tabs.className = 'social-post-editor-tabs';
            tabs.setAttribute('role', 'tablist');
            tabs.setAttribute('aria-label', 'Post widget settings');
            const createPanel = (key, labelText, selected) => {
                const tab = document.createElement('button');
                tab.className = 'social-post-editor-tab';
                tab.type = 'button';
                tab.id = `${card.id}-tab-${key}`;
                tab.setAttribute('role', 'tab');
                tab.setAttribute('aria-selected', String(selected));
                tab.setAttribute('aria-controls', `${card.id}-panel-${key}`);
                tab.tabIndex = selected ? 0 : -1;
                tab.textContent = labelText;
                tabs.append(tab);
                const panel = document.createElement('section');
                panel.id = `${card.id}-panel-${key}`;
                panel.className = 'social-post-editor-panel';
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', tab.id);
                panel.hidden = !selected;
                return panel;
            };
            const publisherPanel = createPanel('publisher', 'About Publisher', true);
            const publisherFields = document.createElement('div');
            publisherFields.className = 'social-post-editor-fields';
            const publisherName = createField('Publisher name', 'socialPublisherName', post.publisher_name || 'Shumi', 'text', 100);
            const publisherRole = createField('Publisher role', 'socialPublisherRole', post.publisher_role || 'Web Developer', 'text', 100);
            const publishedAt = createField('Publish date and time', 'socialPublishedAt', post.published_at || '', 'datetime-local', 16);
            if (!publishedAt.input.value) {
                const now = new Date();
                const pad = value => String(value).padStart(2, '0');
                publishedAt.input.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            }
            publisherFields.append(publisherName.label, publisherRole.label, publishedAt.label);
            publisherPanel.append(publisherFields);
            const mediaPanel = createPanel('media', 'Image and Title', false);
            const fields = document.createElement('div');
            fields.className = 'social-post-editor-fields';
            const id = createField('Post ID', 'socialId', post.id || nextId(), 'text', 40);
            id.input.pattern = '[a-z0-9][a-z0-9-]{0,39}';
            const mediaLabel = document.createElement('label');
            mediaLabel.append(document.createTextNode('Media type'));
            const mediaType = document.createElement('select');
            mediaType.dataset.socialMediaType = '';
            mediaType.required = true;
            [['image', 'Image'], ['video', 'MP4 video']].forEach(([value, labelText]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = labelText;
                mediaType.append(option);
            });
            mediaType.value = post.media_type === 'video' ? 'video' : 'image';
            mediaLabel.append(mediaType);
            const postTitle = createField('Title', 'socialTitle', post.title || '', 'text', 160);
            const image = createField('Image / video URL or path', 'socialImage', post.image || '', 'text', 1000);
            const alt = createField('Image alt text', 'socialAlt', post.alt || '', 'text', 200);
            fields.append(id.label, mediaLabel, postTitle.label, image.label, alt.label);
            mediaPanel.append(fields);
            const descriptionPanel = createPanel('description', 'Description Content', false);
            const descriptionFields = document.createElement('div');
            descriptionFields.className = 'social-post-editor-fields';
            const description = createField('Description', 'socialDescription', post.description || '', 'textarea', 0);
            description.label.className = 'social-post-description';
            descriptionFields.append(description.label);
            descriptionPanel.append(descriptionFields);
            const feedbackTabPanel = createPanel('feedback', 'Feedback and Reaction', false);
            const feedbackPanel = createFeedbackPanel(feedbackTabPanel);

            const visible = document.createElement('input');
            visible.type = 'checkbox';
            visible.dataset.socialVisible = '';
            visible.checked = post.visible !== false;
            const featured = document.createElement('input');
            featured.type = 'checkbox';
            featured.dataset.socialFeatured = '';
            featured.checked = post.featured === true;
            const actions = document.createElement('div');
            actions.className = 'social-post-editor-actions';
            actions.setAttribute('aria-label', 'Actions for this post');
            const editButton = addActionButton(actions, 'Edit / Modify', 'socialEdit');
            const updateButton = addActionButton(actions, 'Update', 'socialUpdate', 'button small');
            updateButton.disabled = true;
            const banButton = addActionButton(actions, visible.checked ? 'Ban from user view' : 'Restore user view', 'socialBan', 'button danger small');
            banButton.setAttribute('aria-pressed', String(!visible.checked));
            const deleteButton = addActionButton(actions, 'Delete', 'socialDelete', 'button danger small');
            addActionButton(actions, 'Add new grid', 'socialAdd');
            const featureButton = addActionButton(actions, featured.checked ? 'Featured in post grids' : 'Feature in post grids', 'socialFeature');
            featureButton.setAttribute('aria-pressed', String(featured.checked));
            visible.hidden = true;
            featured.hidden = true;
            const actionStatus = document.createElement('p');
            actionStatus.className = 'social-post-action-status';
            actionStatus.dataset.socialActionStatus = '';
            actionStatus.setAttribute('role', 'status');
            actionStatus.setAttribute('aria-live', 'polite');
            actions.append(visible, featured, actionStatus);
            card.append(heading, tabs, publisherPanel, mediaPanel, descriptionPanel, feedbackPanel, actions);
            editor.append(card);
            card.querySelectorAll('input, select, textarea').forEach(field => {
                field.required = false;
                if (field !== visible && field !== featured) field.disabled = true;
            });
            const option = document.createElement('option');
            option.value = card.id;
            selector.append(option);
            [postTitle.input, publishedAt.input].forEach(input => input.addEventListener('input', () => updateSelectorOption(card)));
            id.input.addEventListener('input', () => {
                card.dataset.feedbackLoaded = 'false';
            });
            updateSelectorOption(card);
            setEditing(card, true);
            card.querySelector('[data-social-action-status]').textContent =
                'Complete the new grid details, then choose Update. Save Social Widgets to publish it.';
            return card;
        };

        editor.addEventListener('click', event => {
            const action = event.target.closest('button');
            const card = action?.closest('.social-post-editor-card');
            if (action && card && action.matches('[data-social-feedback-refresh]')) {
                loadAdminFeedback(card, true);
                return;
            }
            if (action && card && action.matches('[data-feedback-edit]')) {
                const row = action.closest('[data-feedback-kind]');
                const editorInput = row?.querySelector('[data-feedback-edit-input]');
                const updateButton = row?.querySelector('[data-feedback-update]');
                if (row && editorInput && updateButton) {
                    editorInput.hidden = false;
                    updateButton.hidden = false;
                    action.hidden = true;
                    editorInput.focus();
                }
                return;
            }
            if (action && card && action.matches('[data-feedback-update]')) {
                const row = action.closest('[data-feedback-kind]');
                if (row) moderateFeedback(card, row, 'edit');
                return;
            }
            if (action && card && action.matches('[data-feedback-visibility]')) {
                const row = action.closest('[data-feedback-kind]');
                if (row) moderateFeedback(card, row, action.dataset.feedbackVisibility);
                return;
            }
            if (action && card && action.matches('[data-feedback-view]')) {
                const category = action.dataset.feedbackView;
                card.querySelectorAll('[data-feedback-view]').forEach(button => {
                    button.setAttribute('aria-pressed', String(button === action));
                });
                card.querySelectorAll('[data-feedback-panel]').forEach(panel => {
                    panel.hidden = panel.dataset.feedbackPanel !== category;
                });
                const outerFeedbackTab = card.querySelector('[aria-controls$="-panel-feedback"]');
                if (outerFeedbackTab?.getAttribute('aria-selected') === 'true') loadAdminFeedback(card);
                return;
            }
            if (action && card) {
                const fields = getFields(card);
                const status = card.querySelector('[data-social-action-status]');
                if (action.matches('[data-social-edit]')) {
                    setEditing(card, true);
                    status.textContent = 'Edit the post, then choose Update. Save social widgets to publish changes.';
                    const firstField = card.querySelector('[data-social-title]');
                    const mediaTab = card.querySelector('[aria-controls$="-panel-media"]');
                    if (mediaTab) activateTab(card, mediaTab);
                    firstField.focus();
                    return;
                }
                if (action.matches('[data-social-update]')) {
                    if (!validatePost(card)) {
                        status.textContent = 'Complete all required fields and correct any invalid values before updating.';
                        let invalidField = [
                            fields.id, fields.mediaType, fields.image, fields.alt, fields.title,
                            fields.description, fields.publisherName, fields.publisherRole, fields.publishedAt
                        ].find(field => !field.value.trim() || !field.checkValidity());
                        if (invalidField) {
                            const panel = invalidField.closest('[role="tabpanel"]');
                            const tab = panel && card.querySelector(`[aria-controls="${CSS.escape(panel.id)}"]`);
                            if (tab) activateTab(card, tab);
                            invalidField.focus();
                        } else if (!/^[a-z0-9][a-z0-9-]{0,39}$/.test(fields.id.value.trim())) {
                            fields.id.focus();
                        }
                        return;
                    }
                    updateSelectorOption(card);
                    setEditing(card, false);
                    status.textContent = 'Post updated in this form. Select Save social widgets to publish it.';
                    return;
                }
                if (action.matches('[data-social-ban]')) {
                    fields.visible.checked = !fields.visible.checked;
                    action.setAttribute('aria-pressed', String(!fields.visible.checked));
                    action.textContent = fields.visible.checked ? 'Ban from user view' : 'Restore user view';
                    status.textContent = fields.visible.checked
                        ? 'Post will remain in admin but be hidden from visitors after saving.'
                        : 'Post will be visible to visitors after saving.';
                    return;
                }
                if (action.matches('[data-social-feature]')) {
                    fields.featured.checked = !fields.featured.checked;
                    action.setAttribute('aria-pressed', String(fields.featured.checked));
                    action.textContent = fields.featured.checked ? 'Featured in post grids' : 'Feature in post grids';
                    status.textContent = fields.featured.checked
                        ? 'Post will be marked as featured in the public feed after saving.'
                        : 'Featured marker will be removed after saving.';
                    return;
                }
                if (action.matches('[data-social-delete]')) {
                    if (!window.confirm(`Delete "${fields.title.value || 'this post'}"? Save social widgets to publish the removal.`)) return;
                    const wasSelected = selector.value === card.id;
                    selector.querySelector(`option[value="${CSS.escape(card.id)}"]`)?.remove();
                    card.remove();
                    if (wasSelected) {
                        selector.value = '';
                        editor.querySelectorAll('.social-post-editor-card').forEach(item => item.hidden = true);
                    }
                    return;
                }
                if (action.matches('[data-social-add]')) {
                    const newCard = addPost();
                    if (newCard) showPost(newCard);
                    return;
                }
            }
            const tab = event.target.closest('[role="tab"]');
            if (tab) {
                const tabCard = tab.closest('.social-post-editor-card');
                activateTab(tabCard, tab);
                if (tab.matches('[aria-controls$="-panel-feedback"]')) loadAdminFeedback(tabCard);
            }
        });
        editor.addEventListener('keydown', event => {
            const tab = event.target.closest('[role="tab"]');
            if (!tab || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const tabs = Array.from(tab.closest('[role="tablist"]').querySelectorAll('[role="tab"]'));
            const index = tabs.indexOf(tab);
            const nextIndex = event.key === 'Home' ? 0 : event.key === 'End'
                ? tabs.length - 1
                : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            activateTab(tab.closest('.social-post-editor-card'), tabs[nextIndex], true);
        });
        selector.addEventListener('change', () => {
            const selected = selector.value ? document.getElementById(selector.value) : null;
            editor.querySelectorAll('.social-post-editor-card').forEach(card => {
                card.hidden = card !== selected;
            });
            if (selected) selected.focus({preventScroll:true});
        });
        editor.querySelectorAll('.social-post-editor-card').forEach(card => {
            [card.querySelector('[data-social-title]'), card.querySelector('[data-social-published-at]')]
                .forEach(input => input.addEventListener('input', () => updateSelectorOption(card)));
            getFields(card).id.addEventListener('input', () => {
                card.dataset.feedbackLoaded = 'false';
            });
            card.querySelectorAll('input, select, textarea').forEach(field => {
                field.required = false;
                const fields = getFields(card);
                if (field !== fields.visible && field !== fields.featured) field.disabled = true;
            });
            setEditing(card, false);
        });
        const emptyState = document.createElement('div');
        emptyState.className = 'menu-editor-actions';
        emptyState.hidden = editor.children.length > 0;
        const emptyAddButton = document.createElement('button');
        emptyAddButton.className = 'button secondary small';
        emptyAddButton.type = 'button';
        emptyAddButton.textContent = 'Add new grid';
        emptyAddButton.addEventListener('click', () => {
            const card = addPost();
            if (card) {
                emptyState.hidden = true;
                showPost(card);
            }
        });
        emptyState.append(emptyAddButton);
        editor.after(emptyState);
        editor.addEventListener('click', event => {
            if (event.target.closest('[data-social-delete]')) {
                window.setTimeout(() => {
                    emptyState.hidden = editor.children.length > 0;
                }, 0);
            }
        });
        form.addEventListener('submit', event => {
            const cards = Array.from(editor.querySelectorAll('.social-post-editor-card'));
            const invalidPost = cards.find(card => !validatePost(card));
            if (invalidPost) {
                event.preventDefault();
                showPost(invalidPost);
                const fields = getFields(invalidPost);
                let invalidField = [
                    fields.id, fields.mediaType, fields.image, fields.alt, fields.title,
                    fields.description, fields.publisherName, fields.publisherRole, fields.publishedAt
                ].find(field => !field.value.trim() || !field.checkValidity());
                if (!invalidField && !/^[a-z0-9][a-z0-9-]{0,39}$/.test(fields.id.value.trim())) {
                    invalidField = fields.id;
                }
                const panel = invalidField?.closest('[role="tabpanel"]');
                const tab = panel && invalidPost.querySelector(`[aria-controls="${CSS.escape(panel.id)}"]`);
                if (tab) activateTab(invalidPost, tab);
                invalidField?.focus();
                if (invalidPost.dataset.editing === 'true') {
                    invalidPost.querySelector('[data-social-action-status]').textContent = 'Complete all required fields and correct invalid values before saving.';
                } else {
                    window.alert('Complete every post field with a valid value before saving.');
                }
                return;
            }
            const posts = cards.map(card => {
                const fields = getFields(card);
                return {
                    id:fields.id.value.trim(),
                    media_type:fields.mediaType.value,
                    image:fields.image.value.trim(),
                    alt:fields.alt.value.trim(),
                    title:fields.title.value.trim(),
                    description:fields.description.value.trim(),
                    publisher_name:fields.publisherName.value.trim(),
                    publisher_role:fields.publisherRole.value.trim(),
                    published_at:fields.publishedAt.value,
                    featured:fields.featured.checked,
                    visible:fields.visible.checked
                };
            });
            const ids = posts.map(post => post.id);
            if (posts.length > 24 || ids.some(id => !/^[a-z0-9][a-z0-9-]{0,39}$/.test(id)) || new Set(ids).size !== ids.length) {
                event.preventDefault();
                window.alert('Post IDs must be unique and use lowercase letters, numbers, and hyphens (up to 40 characters).');
                return;
            }
            valueField.value = JSON.stringify(posts);
        });
    })();

    (() => {
        const themeSelect = document.getElementById('theme_default');
        const themePreview = document.getElementById('theme-preset-preview');
        const themeLabel = themePreview?.querySelector('[data-theme-preview-label]');
        const themePresets = Array.from(document.querySelectorAll('[data-admin-theme-preset]'));
        if (!themeSelect || !themePreview || !themeLabel) return;

        const themeNames = {
            dark:'Dark',
            light:'Light',
            black:'Black',
            'light-black':'Light Black'
        };
        const updateThemePreview = () => {
            const theme = themeSelect.value;
            themePreview.dataset.themePreview = theme;
            themeLabel.textContent = themeNames[theme] || 'Dark';
            themePresets.forEach(link => {
                if (link.dataset.adminThemePreset === theme) link.setAttribute('aria-current', 'true');
                else link.removeAttribute('aria-current');
            });
        };
        themeSelect.addEventListener('input', updateThemePreview);
        themeSelect.addEventListener('change', updateThemePreview);
        themePresets.forEach(link => link.addEventListener('click', () => {
            themeSelect.value = link.dataset.adminThemePreset;
            themeSelect.dispatchEvent(new Event('input', {bubbles:true}));
            themeSelect.dispatchEvent(new Event('change', {bubbles:true}));
            const status = document.querySelector('#portfolio_dark_mode [data-section-status]');
            if (status) status.textContent = 'Unsaved changes';
        }));
        document.querySelector('#portfolio_dark_mode [data-section-cancel]')?.addEventListener('click', () => {
            window.setTimeout(updateThemePreview, 0);
        });
        updateThemePreview();
    })();

    (() => {
        const form = document.getElementById('portfolio-sections-form');
        if (!form) return;
        const galleryEditor = document.getElementById('portfolio-gallery-editor');
        const blogEditor = document.getElementById('portfolio-blog-editor');
        const contactEditor = document.getElementById('portfolio-contact-links-editor');
        const galleryTemplate = document.getElementById('portfolio-gallery-card-template');
        const blogTemplate = document.getElementById('portfolio-blog-card-template');
        const contactTemplate = document.getElementById('portfolio-contact-link-template');
        const maxCards = 30;
        const addCard = (editor, template, emptyValues = []) => {
            if (editor.children.length >= (editor === contactEditor ? 12 : maxCards)) {
                window.alert(editor === contactEditor ? 'You can add up to 12 contact links.' : 'You can add up to 30 cards in this section.');
                return null;
            }
            const card = template.content.firstElementChild.cloneNode(true);
            card.querySelectorAll('input, textarea').forEach((field, index) => {
                if (field.type === 'checkbox') return;
                if (emptyValues[index] !== undefined) field.value = emptyValues[index];
            });
            editor.append(card);
            return card;
        };
        form.addEventListener('click', event => {
            const addButton = event.target.closest('[data-section-add]');
            if (addButton) {
                const type = addButton.dataset.sectionAdd;
                const card = type === 'gallery'
                    ? addCard(galleryEditor, galleryTemplate)
                    : type === 'blog'
                        ? addCard(blogEditor, blogTemplate)
                        : addCard(contactEditor, contactTemplate);
                card?.querySelector('input, textarea, select')?.focus();
                return;
            }
            const removeButton = event.target.closest('[data-section-remove]');
            if (removeButton) {
                const card = removeButton.closest('[data-section-card]');
                const title = card.querySelector('[data-gallery-title], [data-blog-title], [data-contact-label]')?.value.trim();
                if (window.confirm(`Remove "${title || 'this item'}"? Save portfolio sections to publish the removal.`)) card.remove();
            }
        });
        const readCards = (editor, type) => Array.from(editor.querySelectorAll(`[data-section-card="${type}"]`), card => {
            const visible = card.querySelector('[data-section-visible]').checked;
            if (type === 'gallery') {
                const title = card.querySelector('[data-gallery-title]').value.trim();
                const images = card.querySelector('[data-gallery-images]').value.split(/\r\n|\r|\n/)
                    .map(line => line.trim()).filter(Boolean).map((line, index) => {
                        const separator = line.indexOf('|');
                        const src = (separator < 0 ? line : line.slice(0, separator)).trim();
                        const alt = (separator < 0 ? '' : line.slice(separator + 1)).trim();
                        return {src, alt:alt || `${title} image ${index + 1}`};
                    });
                return {
                    category:card.querySelector('[data-gallery-category]').value.trim(),
                    category_label:card.querySelector('[data-gallery-category-label]').value.trim(),
                    title,
                    type:card.querySelector('[data-gallery-type]').value.trim(),
                    summary:card.querySelector('[data-gallery-summary]').value.trim(),
                    detail:card.querySelector('[data-gallery-detail]').value.trim(),
                    outcome:card.querySelector('[data-gallery-outcome]').value.trim(),
                    images,
                    visible
                };
            }
            if (type === 'blog') {
                return {
                    category:card.querySelector('[data-blog-category]').value.trim(),
                    date:card.querySelector('[data-blog-date]').value.trim(),
                    date_iso:card.querySelector('[data-blog-date-iso]').value,
                    title:card.querySelector('[data-blog-title]').value.trim(),
                    summary:card.querySelector('[data-blog-summary]').value.trim(),
                    body:card.querySelector('[data-blog-body]').value.trim(),
                    media_type:card.querySelector('[data-blog-media-type]').value,
                    image:card.querySelector('[data-blog-image]').value.trim(),
                    image_alt:card.querySelector('[data-blog-image-alt]').value.trim(),
                    visible
                };
            }
            return {
                label:card.querySelector('[data-contact-label]').value.trim(),
                url:card.querySelector('[data-contact-url]').value.trim(),
                icon:card.querySelector('[data-contact-icon]').value.trim(),
                visible
            };
        });
        form.addEventListener('submit', event => {
            const saveTarget = document.getElementById('sections-save-target').value || 'all';
            const galleryItems = readCards(galleryEditor, 'gallery');
            const blogItems = readCards(blogEditor, 'blog');
            const contactLinks = readCards(contactEditor, 'contact-link');
            if ((saveTarget === 'all' || saveTarget === 'gallery') &&
                galleryItems.some(item => item.images.length < 1 || item.images.length > 12)) {
                event.preventDefault();
                window.alert('Each Gallery item needs 1–12 images.');
                return;
            }
            form.elements.portfolio_gallery_items.value = JSON.stringify(galleryItems);
            form.elements.portfolio_blog_items.value = JSON.stringify(blogItems);
            form.elements.portfolio_contact_links.value = JSON.stringify(contactLinks);
        });
        form.querySelectorAll('[data-section-add]').forEach(button => {
            const editor = button.dataset.sectionAdd === 'gallery' ? galleryEditor : button.dataset.sectionAdd === 'blog' ? blogEditor : contactEditor;
            button.disabled = editor.children.length >= (editor === contactEditor ? 12 : maxCards);
        });
        form.addEventListener('click', event => {
            if (!event.target.closest('[data-section-remove]')) return;
            window.setTimeout(() => {
                form.querySelectorAll('[data-section-add]').forEach(button => {
                    const editor = button.dataset.sectionAdd === 'gallery' ? galleryEditor : button.dataset.sectionAdd === 'blog' ? blogEditor : contactEditor;
                    button.disabled = editor.children.length >= (editor === contactEditor ? 12 : maxCards);
                });
            }, 0);
        });
    })();

    (() => {
        const app = document.getElementById('app');
        const toggle = document.getElementById('sidebar-toggle');
        const backdrop = document.getElementById('sidebar-backdrop');
        const mobile = window.matchMedia('(max-width: 900px)');
        toggle.setAttribute('aria-expanded', String(!mobile.matches));

        const closeSidebar = () => {
            app.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            if (mobile.matches) {
                const isOpen = app.classList.toggle('sidebar-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            } else {
                const isCollapsed = app.classList.toggle('sidebar-collapsed');
                toggle.setAttribute('aria-expanded', String(!isCollapsed));
            }
        });

        backdrop.addEventListener('click', closeSidebar);
        document.querySelectorAll('.side-nav a[href^="#"]').forEach((link) => {
            link.addEventListener('click', () => {
                document.querySelectorAll('.side-nav a.active').forEach((activeLink) => {
                    activeLink.classList.remove('active');
                    activeLink.removeAttribute('aria-current');
                });
                link.classList.add('active');
                link.setAttribute('aria-current', 'location');
                if (mobile.matches) closeSidebar();
            });
        });

        const syncSidebarLocation = () => {
            const currentLink = Array.from(document.querySelectorAll('.side-nav a[href^="#"]'))
                .find((link) => link.hash === window.location.hash);
            if (!currentLink) return;
            document.querySelectorAll('.side-nav a.active').forEach((activeLink) => {
                activeLink.classList.remove('active');
                activeLink.removeAttribute('aria-current');
            });
            currentLink.classList.add('active');
            currentLink.setAttribute('aria-current', 'location');
            for (let parent = currentLink.parentElement; parent && parent !== app; parent = parent.parentElement) {
                if (parent instanceof HTMLDetailsElement) parent.open = true;
            }
        };

        window.addEventListener('hashchange', syncSidebarLocation);
        syncSidebarLocation();
    })();

    (() => {
        const form = document.getElementById('portfolio-sections-form');
        if (!form) return;

        const saveTarget = document.getElementById('sections-save-target');
        const sections = Array.from(form.querySelectorAll('#portfolio_gallery, #portfolio_blog, #portfolio_contact, #portfolio_search, #portfolio_dark_mode'));
        const editorIds = ['portfolio-gallery-editor', 'portfolio-blog-editor', 'portfolio-contact-links-editor'];
        const snapshots = new Map(sections.map(section => {
            const editors = editorIds
                .map(id => section.querySelector(`#${id}`))
                .filter(Boolean)
                .map(editor => ({
                    editor,
                    cards: Array.from(editor.children, card => card.cloneNode(true))
                }));
            const controls = Array.from(section.querySelectorAll('input, textarea, select'))
                .filter(control => !control.closest('.about-content-list'))
                .map(control => ({
                    control,
                    value: control.value,
                    checked: control instanceof HTMLInputElement ? control.checked : null
                }));
            return [section, {controls, editors}];
        }));

        const setDirtyState = section => {
            const status = section.querySelector('[data-section-status]');
            if (status) status.textContent = 'Unsaved changes';
            section.querySelectorAll('[data-section-card]').forEach(card => {
                const visibility = card.querySelector('[data-section-visible]');
                card.classList.toggle('is-section-hidden', visibility !== null && !visibility.checked);
            });
        };

        sections.forEach(section => {
            section.querySelectorAll('[data-section-card]').forEach(card => {
                const visibility = card.querySelector('[data-section-visible]');
                card.classList.toggle('is-section-hidden', visibility !== null && !visibility.checked);
            });
        });

        form.addEventListener('click', event => {
            const saveButton = event.target.closest('[data-section-save]');
            if (saveButton && saveTarget) saveTarget.value = saveButton.dataset.sectionSave;
        });
        form.addEventListener('input', event => {
            const section = event.target.closest('.home-fields-section');
            if (section && snapshots.has(section)) setDirtyState(section);
        });
        form.addEventListener('change', event => {
            const section = event.target.closest('.home-fields-section');
            if (section && snapshots.has(section)) setDirtyState(section);
        });
        form.addEventListener('click', event => {
            const cancelButton = event.target.closest('[data-section-cancel]');
            if (cancelButton) {
                const section = cancelButton.closest('.home-fields-section');
                const snapshot = snapshots.get(section);
                if (!snapshot) return;
                snapshot.editors.forEach(({editor, cards}) => {
                    editor.replaceChildren(...cards.map(card => card.cloneNode(true)));
                });
                snapshot.controls.forEach(({control, value, checked}) => {
                    control.value = value;
                    if (checked !== null) control.checked = checked;
                });
                section.querySelectorAll('[data-section-card]').forEach(card => {
                    const visibility = card.querySelector('[data-section-visible]');
                    card.classList.toggle('is-section-hidden', visibility !== null && !visibility.checked);
                });
                const status = section.querySelector('[data-section-status]');
                if (status) status.textContent = 'Unsaved changes discarded';
                section.classList.remove('section-reset-animation');
                void section.offsetWidth;
                section.classList.add('section-reset-animation');
                window.setTimeout(() => section.classList.remove('section-reset-animation'), 600);
                return;
            }

            const actionButton = event.target.closest('[data-section-add], [data-section-remove]');
            if (actionButton) {
                const section = actionButton.closest('.home-fields-section');
                if (section && snapshots.has(section)) setDirtyState(section);
            }
        });
    })();

    (() => {
        const selector = document.getElementById('theme_default');
        const preview = document.getElementById('theme-preset-preview');
        const label = preview?.querySelector('[data-theme-preview-label]');
        if (!selector || !preview || !label) return;
        const names = {
            dark:'Dark',
            light:'Light',
            black:'Black',
            'light-black':'Light Black',
            natural:'Natural',
            'light-pure':'Light Pure'
        };
        const updatePreview = () => {
            const theme = selector.value;
            preview.dataset.themePreview = theme;
            label.textContent = names[theme] || 'Dark';
        };
        selector.addEventListener('change', updatePreview);
        document.querySelectorAll('[data-admin-theme-preset]').forEach(link => {
            link.addEventListener('click', () => {
                selector.value = link.dataset.adminThemePreset;
                selector.dispatchEvent(new Event('change', {bubbles:true}));
            });
        });
        selector.closest('.home-fields-section')
            ?.querySelector('[data-section-cancel]')
            ?.addEventListener('click', () => window.setTimeout(updatePreview, 0));
        updatePreview();
    })();
</script>
</body>
</html>
