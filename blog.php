<?php
// Include database configuration
require_once 'config.php';

// Function to get system settings by keys
function getSystemSettingsByKeys($keys) {
    global $conn;
    if (!is_array($keys) || empty($keys)) {
        return [];
    }

    $placeholders = str_repeat('?,', count($keys) - 1) . '?';
    $sql = "SELECT setting_key, setting_value, setting_type FROM system_settings WHERE setting_key IN ($placeholders)";

    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('Could not load blog settings: ' . $conn->error);
            return [];
        }

        $types = str_repeat('s', count($keys));
        $stmt->bind_param($types, ...$keys);
        $stmt->execute();
        $result = $stmt->get_result();
    } catch (mysqli_sql_exception $e) {
        error_log('Could not load blog settings: ' . $e->getMessage());
        return [];
    }

    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $value = $row['setting_value'];

        // Handle different setting types
        switch ($row['setting_type']) {
            case 'boolean':
                $value = strtolower($value) === 'true';
                break;
            case 'number':
                $value = is_numeric($value) ? (float) $value : $value;
                break;
            case 'json':
                $decoded = json_decode($value, true);
                $value = $decoded !== null ? $decoded : $value;
                break;
            default:
                // text type, keep as string
                break;
        }

        $settings[$row['setting_key']] = $value;
    }

    $stmt->close();
    return $settings;
}

// Get blog settings from database
$blog_settings = getSystemSettingsByKeys([
    'blog_banner_slides',
    'blog_banner_duration',
    'blog_feature_update',
    'blog_insurance_news',
    'blog_core_pillars',
    'blog_performance',
    'blog_product_catalog',
    'blog_executive_management'
]);

// Set defaults if not found in database
$blog_banner_slides = $blog_settings['blog_banner_slides'] ?? [
    [
        'title' => 'Global insurance, local care',
        'description' => 'Protecting what matters most — tailored coverage for families & enterprises',
        'alt' => 'Insurance team',
        'image' => 'https://images.unsplash.com/photo-1556742525-6e07f5c2a1a2?w=1600&q=80'
    ],
    [
        'title' => 'Business insurance solutions',
        'description' => 'Comprehensive coverage for enterprises of all sizes',
        'alt' => 'Business insurance',
        'image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1600&q=80'
    ],
    [
        'title' => 'Fast claim support',
        'description' => 'Quick and efficient claim processing when you need it most',
        'alt' => 'Claim support',
        'image' => 'https://images.unsplash.com/photo-1556741533-6e6a3bd8c1cf?w=1600&q=80'
    ]
];

$blog_banner_duration = $blog_settings['blog_banner_duration'] ?? 5;

$blog_feature_update = $blog_settings['blog_feature_update'] ?? [
    'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80',
    'alt' => 'Latest Insurance Updates',
    'label' => 'FEATURED UPDATE',
    'title' => 'New Insurance Policy Updates',
    'description' => 'Stay informed with the latest changes in insurance policies and coverage options.',
    'link_url' => '#'
];

$blog_insurance_news = $blog_settings['blog_insurance_news'] ?? [
    [
        'label' => 'NEWS',
        'title' => 'Insurance Market Trends 2026',
        'description' => 'Global insurance premium growth +6.2% driven by cyber & climate coverage.',
        'link_url' => '#'
    ],
    [
        'label' => 'AWARD',
        'title' => 'Award Winner Recognition',
        'description' => 'PrimeShield named "Best Claims Experience" by International Finance Awards.',
        'link_url' => '#'
    ],
    [
        'label' => 'UPDATE',
        'title' => 'AI Risk Assistant Launch',
        'description' => 'Launch of real-time risk dashboard for commercial clients.',
        'link_url' => '#'
    ],
    [
        'label' => 'GREEN',
        'title' => 'Green Insurance Initiative',
        'description' => 'New parametric cover for renewable energy projects.',
        'link_url' => '#'
    ],
    [
        'label' => 'DIGITAL',
        'title' => 'Digital Wallet Integration',
        'description' => 'Instant claims payment via PrimeShield app now in 12 countries.',
        'link_url' => '#'
    ]
];

$blog_core_pillars = $blog_settings['blog_core_pillars'] ?? [
    [
        'title' => 'Vision',
        'description' => 'To be the world\'s most trusted insurance partner, shaping a resilient future through innovation and empathy.'
    ],
    [
        'title' => 'Mission',
        'description' => 'Empower individuals and businesses with seamless coverage, fair claims, and proactive risk management worldwide.'
    ],
    [
        'title' => 'Values',
        'description' => 'Integrity, client-first, innovation, and community strength — the heart of PrimeShield.'
    ],
    [
        'title' => 'Goal 2030',
        'description' => 'Reach 50M policyholders, achieve carbon-neutral operations, and set new standards for digital insurance.'
    ]
];

$blog_feature_updates = $blog_settings['blog_feature_updates'] ?? [
    [
        'title' => 'Global Network',
        'description' => 'Operates in 45+ countries with 24/7 multilingual support.',
        'link_url' => '#'
    ],
    [
        'title' => 'Rapid Claims',
        'description' => '90% of claims processed within 48h using AI validation.',
        'link_url' => '#'
    ],
    [
        'title' => 'A+ Financial Rating',
        'description' => 'AM Best A+ (Superior) — reliability you can trust.',
        'link_url' => '#'
    ]
];

$blog_performance = $blog_settings['blog_performance'] ?? [
    'title' => '📊 Company Performance Highlights',
    'description' => '💰 Gross Written Premium 2025: $12.4B | ↗️ +11.3% YoY | Combined Ratio: 92.4%<br>⭐ Customer Satisfaction Score: 94% | 🏆 Claims Settlement Rate: 98.7%',
    'link_url' => '#',
    'link_text' => 'Annual Report →'
];
$executive_management = $blog_settings['blog_executive_management'] ?? [
    'banner' => [
        'title' => 'Executive Management Members',
        'description' => 'Meet the leadership team guiding our strategy, service culture, and long-term customer promise.',
        'alt' => 'Executive management team',
        'image' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1600&q=80'
    ],
    'members' => [
        [
            'name' => 'Chief Executive Officer',
            'description' => 'Leads corporate strategy, governance, and sustainable growth across the company.',
            'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=900&q=80',
            'history' => 'Over 25 years in insurance leadership, driving digital transformation and customer-centric innovation.',
            'education' => 'MBA from Harvard Business School, BSc in Business Administration',
            'experience' => '25+ years',
            'profession' => 'Corporate Strategy & Governance',
            'skills' => 'Strategic Planning, Risk Management, Leadership Development'
        ],
        [
            'name' => 'Chief Operations Officer',
            'description' => 'Oversees operational excellence, customer service delivery, and branch performance.',
            'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=900&q=80',
            'history' => '20 years of operational management experience in financial services and insurance sectors.',
            'education' => 'MSc in Operations Management, BSc in Industrial Engineering',
            'experience' => '20+ years',
            'profession' => 'Operations Management',
            'skills' => 'Process Optimization, Team Leadership, Performance Metrics'
        ],
        [
            'name' => 'Chief Finance Officer',
            'description' => 'Directs financial planning, reporting, capital strength, and investment discipline.',
            'image' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=900&q=80',
            'history' => 'Extensive background in financial management and capital markets within insurance industry.',
            'education' => 'CPA, MBA in Finance, BSc in Accounting',
            'experience' => '18+ years',
            'profession' => 'Financial Management',
            'skills' => 'Financial Planning, Investment Strategy, Regulatory Compliance'
        ],
        [
            'name' => 'Chief Underwriting Officer',
            'description' => 'Guides underwriting standards, product discipline, and portfolio risk quality.',
            'image' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=900&q=80',
            'history' => 'Specialized in underwriting and risk assessment across multiple insurance lines.',
            'education' => 'ACAS, BSc in Risk Management',
            'experience' => '15+ years',
            'profession' => 'Risk Underwriting',
            'skills' => 'Risk Assessment, Product Development, Pricing Strategy'
        ],
        [
            'name' => 'Claims Director',
            'description' => 'Champions fair, transparent, and timely claim settlement for policyholders.',
            'image' => 'https://images.unsplash.com/photo-1556157382-97eda2d62296?auto=format&fit=crop&w=900&q=80',
            'history' => 'Dedicated to improving claims processes and customer satisfaction in insurance claims.',
            'education' => 'Claims Management Certification, BSc in Business',
            'experience' => '12+ years',
            'profession' => 'Claims Management',
            'skills' => 'Claims Processing, Customer Service, Dispute Resolution'
        ],
        [
            'name' => 'Marketing Director',
            'description' => 'Builds customer relationships, market presence, and product communication.',
            'image' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=900&q=80',
            'history' => 'Marketing expert with focus on brand building and customer engagement in insurance.',
            'education' => 'MBA in Marketing, BSc in Communications',
            'experience' => '14+ years',
            'profession' => 'Marketing & Communications',
            'skills' => 'Brand Management, Digital Marketing, Customer Insights'
        ],
        [
            'name' => 'Human Capital Director',
            'description' => 'Develops talent, leadership capability, and a strong performance culture.',
            'image' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=900&q=80',
            'history' => 'Human resources leader focused on organizational development and employee engagement.',
            'education' => 'MSc in Human Resources, BSc in Psychology',
            'experience' => '16+ years',
            'profession' => 'Human Resources',
            'skills' => 'Talent Development, Organizational Culture, Performance Management'
        ],
        [
            'name' => 'Digital Transformation Director',
            'description' => 'Leads digital services, data modernization, and technology-enabled customer journeys.',
            'image' => 'https://images.unsplash.com/photo-1562788869-4ed32648eb72?auto=format&fit=crop&w=900&q=80',
            'history' => 'Technology innovator driving digital change and customer experience improvements.',
            'education' => 'MSc in Information Technology, BSc in Computer Science',
            'experience' => '13+ years',
            'profession' => 'Digital Technology',
            'skills' => 'Digital Strategy, Data Analytics, Technology Implementation'
        ]
    ]
];

$product_catalog = [
    'General Insurance' => [
        [
            'key' => 'motor-insurance',
            'title' => 'Motor Insurance',
            'tagline' => 'Protect every trip, driver, and vehicle with fast claim support.',
            'description' => 'Motor Insurance helps private and commercial vehicle owners recover from accidents, theft, third-party liability, fire, and unexpected road risks.',
            'highlights' => ['Third-party and comprehensive options', 'Own damage protection', 'Theft and fire cover', 'Repair network guidance'],
            'ideal_for' => ['Private car owners', 'Commercial fleets', 'Taxi and ride service operators'],
            'price_note' => 'Premium depends on vehicle value, use, cover type, and driver profile.',
            'image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'fire-allied-perils',
            'title' => 'Fire and Allied Perils',
            'tagline' => 'Secure buildings, stock, and business assets from fire-related loss.',
            'description' => 'This cover supports homes and businesses after fire, lightning, explosion, and selected allied risks that can interrupt operations or damage property.',
            'highlights' => ['Building and contents cover', 'Stock and machinery options', 'Business interruption support', 'Risk inspection guidance'],
            'ideal_for' => ['Home owners', 'Retailers', 'Warehouses', 'Factories'],
            'price_note' => 'Premium is based on asset value, location, occupancy, and risk controls.',
            'image' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'engineering-insurance',
            'title' => 'Engineering Insurance',
            'tagline' => 'Keep projects, machinery, and contractors moving with technical risk cover.',
            'description' => 'Engineering Insurance protects construction works, installation projects, machinery breakdown, plant equipment, and related third-party risks.',
            'highlights' => ['Contractors all risk', 'Machinery breakdown', 'Erection all risk', 'Plant and equipment protection'],
            'ideal_for' => ['Contractors', 'Manufacturers', 'Project owners', 'Equipment operators'],
            'price_note' => 'Premium depends on project value, duration, equipment type, and safety controls.',
            'image' => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'marine-insurance',
            'title' => 'Marine Insurance',
            'tagline' => 'Protect goods in transit across sea, air, road, and inland routes.',
            'description' => 'Marine Insurance covers cargo and transit risks from origin to destination, helping importers, exporters, and logistics teams trade with confidence.',
            'highlights' => ['Import and export cargo cover', 'Transit loss protection', 'Warehouse-to-warehouse options', 'Flexible shipment declarations'],
            'ideal_for' => ['Importers', 'Exporters', 'Freight forwarders', 'Distributors'],
            'price_note' => 'Premium is guided by cargo type, route, value, packing, and terms of cover.',
            'image' => 'https://images.unsplash.com/photo-1494412519320-aa613dfb7738?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'aviation-insurance',
            'title' => 'Aviation Insurance',
            'tagline' => 'Specialized cover for aircraft, operators, passengers, and liabilities.',
            'description' => 'Aviation Insurance supports aircraft owners and operators with hull, liability, passenger, and ground risk protection.',
            'highlights' => ['Aircraft hull options', 'Passenger liability', 'Third-party liability', 'Ground risk extensions'],
            'ideal_for' => ['Aircraft owners', 'Charter operators', 'Aviation service providers'],
            'price_note' => 'Premium depends on aircraft type, use, pilot experience, and territory.',
            'image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'burglary-insurance',
            'title' => 'Burglary Insurance',
            'tagline' => 'Defend business property against forced entry and theft loss.',
            'description' => 'Burglary Insurance covers loss or damage to insured property following forcible and violent entry into premises.',
            'highlights' => ['Stock and contents protection', 'Office equipment cover', 'Damage to premises options', 'Security improvement guidance'],
            'ideal_for' => ['Shops', 'Offices', 'Clinics', 'Warehouses'],
            'price_note' => 'Premium depends on property value, location, security, and occupancy.',
            'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'money-insurance',
            'title' => 'Money Insurance',
            'tagline' => 'Protect cash in transit, on premises, and in secure custody.',
            'description' => 'Money Insurance supports organizations that handle cash by covering insured money during transit, in safe custody, or at business premises.',
            'highlights' => ['Cash-in-transit cover', 'Cash on premises', 'Safe and cashier limits', 'Flexible movement limits'],
            'ideal_for' => ['Retailers', 'Schools', 'Hospitals', 'Cash collection teams'],
            'price_note' => 'Premium is based on movement limits, custody controls, and security arrangements.',
            'image' => 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?auto=format&fit=crop&w=1200&q=80'
        ]
    ],
    'Life and Health' => [
        [
            'key' => 'individual-life-assurance',
            'title' => 'Individual Life Assurance',
            'tagline' => 'Build family protection around your income, goals, and legacy.',
            'description' => 'Individual Life Assurance provides financial support to beneficiaries and can help policyholders plan for long-term protection needs.',
            'highlights' => ['Flexible sum assured', 'Beneficiary protection', 'Optional savings features', 'Long-term planning support'],
            'ideal_for' => ['Parents', 'Home buyers', 'Professionals', 'Business owners'],
            'price_note' => 'Premium depends on age, health, sum assured, and policy term.',
            'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'group-life-assurance',
            'title' => 'Group Life Assurance',
            'tagline' => 'Give employees meaningful protection with one organized group policy.',
            'description' => 'Group Life Assurance helps employers provide life protection benefits to staff members under a single policy structure.',
            'highlights' => ['Employer-sponsored protection', 'Simple member administration', 'Death benefit cover', 'Optional disability extensions'],
            'ideal_for' => ['Companies', 'Associations', 'Unions', 'Cooperatives'],
            'price_note' => 'Premium depends on member count, age profile, benefit level, and occupation risk.',
            'image' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'endowment-assurance',
            'title' => 'Endowment Assurance',
            'tagline' => 'Combine protection with disciplined savings for future milestones.',
            'description' => 'Endowment Assurance is designed for customers who want life protection and a maturity benefit for education, retirement, or major life plans.',
            'highlights' => ['Protection plus maturity value', 'Goal-based savings', 'Fixed policy term', 'Beneficiary support'],
            'ideal_for' => ['Education planners', 'Long-term savers', 'Families', 'Retirement planners'],
            'price_note' => 'Premium is influenced by target benefit, term, age, and payment frequency.',
            'image' => 'https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'term-life-assurance',
            'title' => 'Term Life Assurance',
            'tagline' => 'Affordable life protection for a defined period of responsibility.',
            'description' => 'Term Life Assurance provides life cover for a selected period, helping protect families, loans, and business obligations.',
            'highlights' => ['High protection at focused cost', 'Loan protection options', 'Flexible terms', 'Clear beneficiary benefit'],
            'ideal_for' => ['Mortgage customers', 'Young families', 'Business partners', 'Loan borrowers'],
            'price_note' => 'Premium depends on age, health, term length, and sum assured.',
            'image' => 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'medical-insurance',
            'title' => 'Medical Insurance',
            'tagline' => 'Access care faster with health cover built for real treatment needs.',
            'description' => 'Medical Insurance helps individuals, families, and groups manage hospital, outpatient, and emergency treatment expenses.',
            'highlights' => ['Inpatient and outpatient options', 'Emergency treatment support', 'Family and group plans', 'Provider network guidance'],
            'ideal_for' => ['Families', 'Employers', 'Individuals', 'Frequent travelers'],
            'price_note' => 'Premium depends on benefit limits, age, family size, and selected options.',
            'image' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'personal-accident',
            'title' => 'Personal Accident',
            'tagline' => 'Financial support after accidental injury, disability, or death.',
            'description' => 'Personal Accident cover offers defined benefits for accidental death, permanent disability, temporary disability, and medical expenses.',
            'highlights' => ['Accidental death benefit', 'Disability benefit options', 'Medical expense support', 'Worldwide cover options'],
            'ideal_for' => ['Employees', 'Drivers', 'Students', 'Field teams'],
            'price_note' => 'Premium depends on occupation, benefit level, and cover scope.',
            'image' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'travel-health-cover',
            'title' => 'Travel Health Cover',
            'tagline' => 'Travel with emergency medical and assistance protection.',
            'description' => 'Travel Health Cover supports travelers with medical emergency costs, assistance services, and selected travel-related benefits abroad.',
            'highlights' => ['Emergency medical expenses', 'Trip assistance support', 'Short-term travel plans', 'Family travel options'],
            'ideal_for' => ['Business travelers', 'Students abroad', 'Tourists', 'Families'],
            'price_note' => 'Premium depends on destination, duration, age, and selected limit.',
            'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=80'
        ]
    ],
    'Selam Takaful' => [
        [
            'key' => 'motor-takaful',
            'title' => 'Motor Takaful',
            'tagline' => 'Sharia-conscious vehicle protection based on mutual assistance.',
            'description' => 'Motor Takaful provides vehicle protection through a cooperative model for participants seeking ethical risk sharing.',
            'highlights' => ['Comprehensive and third-party options', 'Participant-focused model', 'Accident and theft protection', 'Transparent contribution basis'],
            'ideal_for' => ['Private vehicle owners', 'Commercial operators', 'Fleet participants'],
            'price_note' => 'Contribution depends on vehicle value, use, and selected protection.',
            'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'property-takaful',
            'title' => 'Property Takaful',
            'tagline' => 'Protect homes and businesses through ethical risk sharing.',
            'description' => 'Property Takaful protects buildings, contents, stock, and business assets from selected property risks under a takaful framework.',
            'highlights' => ['Building and contents protection', 'Fire and allied risk options', 'Business asset cover', 'Risk improvement guidance'],
            'ideal_for' => ['Home owners', 'Retailers', 'Warehouses', 'SMEs'],
            'price_note' => 'Contribution depends on asset value, location, and selected risks.',
            'image' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'family-takaful',
            'title' => 'Family Takaful',
            'tagline' => 'Plan family protection with cooperative, values-aligned cover.',
            'description' => 'Family Takaful helps participants protect loved ones and plan future needs through a mutual protection arrangement.',
            'highlights' => ['Family protection benefit', 'Long-term planning options', 'Beneficiary support', 'Transparent participant contributions'],
            'ideal_for' => ['Parents', 'Young families', 'Long-term planners'],
            'price_note' => 'Contribution depends on age, benefit amount, term, and plan design.',
            'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'health-takaful',
            'title' => 'Health Takaful',
            'tagline' => 'Healthcare protection with a mutual assistance approach.',
            'description' => 'Health Takaful helps participants manage medical expenses with options for inpatient, outpatient, and emergency benefits.',
            'highlights' => ['Inpatient and outpatient options', 'Family participation plans', 'Emergency treatment support', 'Provider network guidance'],
            'ideal_for' => ['Families', 'Employers', 'Individuals'],
            'price_note' => 'Contribution depends on benefits, age, family size, and selected limits.',
            'image' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'marine-takaful',
            'title' => 'Marine Takaful',
            'tagline' => 'Ethical cargo and transit protection for trade movements.',
            'description' => 'Marine Takaful supports importers, exporters, and logistics providers by protecting goods in transit under takaful principles.',
            'highlights' => ['Cargo transit protection', 'Import and export options', 'Flexible shipment declarations', 'Route-based contribution'],
            'ideal_for' => ['Importers', 'Exporters', 'Logistics companies'],
            'price_note' => 'Contribution depends on cargo value, route, packing, and cover terms.',
            'image' => 'https://images.unsplash.com/photo-1494412519320-aa613dfb7738?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'engineering-takaful',
            'title' => 'Engineering Takaful',
            'tagline' => 'Project and machinery protection through cooperative cover.',
            'description' => 'Engineering Takaful covers construction, erection, machinery, and plant risks for participants who prefer takaful protection.',
            'highlights' => ['Contract works protection', 'Machinery breakdown options', 'Plant and equipment cover', 'Third-party extensions'],
            'ideal_for' => ['Contractors', 'Project owners', 'Manufacturers'],
            'price_note' => 'Contribution depends on project value, duration, equipment, and risk controls.',
            'image' => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=1200&q=80'
        ],
        [
            'key' => 'micro-takaful',
            'title' => 'Micro Takaful',
            'tagline' => 'Simple, affordable protection for small enterprises and families.',
            'description' => 'Micro Takaful provides accessible protection packages for low-ticket risks, community groups, and small income activities.',
            'highlights' => ['Low contribution options', 'Simple benefit structures', 'Community group suitability', 'Fast enrollment journeys'],
            'ideal_for' => ['Micro businesses', 'Community groups', 'Smallholder families'],
            'price_note' => 'Contribution depends on package, benefit level, and group size.',
            'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=80'
        ]
    ]
];
$saved_product_catalog = $blog_settings['blog_product_catalog'] ?? null;
if (is_array($saved_product_catalog)) {
    foreach ($product_catalog as $category => $default_products) {
        if (!isset($saved_product_catalog[$category]) || !is_array($saved_product_catalog[$category])) {
            continue;
        }
        foreach ($default_products as $index => $default_product) {
            if (!isset($saved_product_catalog[$category][$index]) || !is_array($saved_product_catalog[$category][$index])) {
                continue;
            }
            $saved_product = $saved_product_catalog[$category][$index];
            $product_catalog[$category][$index] = [
                'key' => $default_product['key'],
                'title' => trim((string) ($saved_product['title'] ?? $default_product['title'])),
                'tagline' => trim((string) ($saved_product['tagline'] ?? $default_product['tagline'])),
                'description' => trim((string) ($saved_product['description'] ?? $default_product['description'])),
                'highlights' => is_array($saved_product['highlights'] ?? null) ? $saved_product['highlights'] : $default_product['highlights'],
                'ideal_for' => is_array($saved_product['ideal_for'] ?? null) ? $saved_product['ideal_for'] : $default_product['ideal_for'],
                'price_note' => trim((string) ($saved_product['price_note'] ?? $default_product['price_note'])),
                'image' => trim((string) ($saved_product['image'] ?? $default_product['image']))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Awashinsurance.com</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #0c3d81;
      --primary-dark: #082d5f;
      --accent: #ff9f1c;
      --accent-hover: #0092e6;
      --white: #ffffff;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-600: #475569;
      --gray-800: #1e293b;
      --shadow-sm: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
      --shadow-md: 0 20px 25px -12px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: honeydew;
      color: var(--gray-800);
      line-height: 1.5;
    }

    /* HEADER — white background, refined sticky */
    .site-header {
      background: #0579af;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      position: sticky;
      top: 0;
      z-index: 1000;
      padding: 1rem 1rem 1.35rem 0;
      box-shadow: 0 12px 30px rgba(12, 61, 129, 0.08);
    }
    .navbar-container {
      max-width: none;background: honeydew;
      margin: 0;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr) auto;
      align-items: center;
      gap: 1rem 1.25rem;
    }
    .navbar-container > .brand,
    .navbar-container > .nav-links,
    .navbar-container > .search-wrapper {
      background: var(--white);
      border-top: none;
      border-bottom: none;background: honeydew;
      border-radius: 0;
      box-shadow: var(--shadow-sm);
      min-height: 50px; 
    }
    .brand {
      display: flex;
      align-items: flex-start;
      justify-content: flex-start;
      text-decoration: none;
      padding: 0.85rem 1.25rem 0.85rem 5px;
      align-self: start;
      justify-self: start;
    }
    .company-logo {
      width: clamp(110px, 14vw, 160px);
      margin-left: 80px;
      display: flex;
      align-items: flex-start;
      justify-content: flex-start;  min-height: 120px; padding-top:20px;
    }
    .company-logo img {
      display: block;
      width: 120%;
      height: auto;
      object-fit: contain;
    }
    
  
    .nav-links {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
      padding: 1rem 1.4rem;
    }
    .nav-links a, .dropdown-btn {
      text-decoration: none;
      font-weight: none;
      color: var(--gray-2000);
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 0.5rem 0.8rem;
      border-radius: 0;
      transition: var(--transition);
    }
    .nav-links a:hover, .dropdown-btn:hover {
      color: cyan;
      background:  #00367c;
      transform: translateY(-1px);
    }
    .dropdown {
      position: relative;
    }
    .dropdown-btn {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    @keyframes goldUnderlineBlink {
      0%, 100% {
        color: var(--gray-2000);
        text-decoration-color: #09a5cc;
        text-shadow: 0 0 0 rgba(12, 17, 87, 0);
      }
      50% {
        color: var(--gray-2000);
        text-decoration-color: #ceb211;
        text-shadow: 0 0 8px rgba(16, 206, 240, 0.45);
      }
    }
    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      background: var(--white);
      min-width: 260px;
      border-radius: 0;
      box-shadow: 0 18px 32px -14px rgba(12, 61, 129, 0.24);
      border: none;
      padding: 0.95rem 1.25rem;
      opacity: 0;
      visibility: hidden;
      transform: translateY(12px);
      transition: all 0.25s ease;
      z-index: 1050;
    }
    .about-dropdown-menu {
      min-width: 520px;
      width: min(700px, calc(200vw - 1.8rem));
      padding: 1rem 1.4rem;
       
    }
    .products-dropdown-menu {
      left: 50%;
      min-width: 760px;
      width: min(920px, calc(100vw - 2rem));
      padding: 1.15rem 1.35rem;
      transform: translate(-50%, 12px);
      transform-origin: top center;
    }
    .products-dropdown-menu.show {
      transform: translate(-50%, 0);
      animation: productWaveDown 0.38s cubic-bezier(0.2, 0.9, 0.35, 1.25);
    }
    @keyframes productWaveDown {
      0% {
        opacity: 0;
        transform: translate(-50%, -10px) scaleY(0.72);
      }
      55% {
        opacity: 1;
        transform: translate(-50%, 4px) scaleY(1.04);
      }
      100% {
        opacity: 1;
        transform: translate(-50%, 0) scaleY(1);
      }
    }
    .about-menu-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(210px, 1fr));
      gap: 0.45rem 2.25rem;
      align-items: start;
    }
    .products-menu-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(180px, 1fr));
      gap: 1rem 1.35rem;
      align-items: start;
    }
    .about-menu-column {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      align-content: start;
      gap: 0.1rem;
      border: none;
      border-radius: 0;
      overflow: visible;
      background: transparent;
    }
    .products-menu-column {
      display: flex;
      flex-direction: column;
      gap: 0.1rem;
      min-width: 0;
    }
    .products-menu-title {
      color: var(--primary);
      font-size: 0.95rem;
      font-weight: 800;
      margin-bottom: 0.4rem;
      padding-bottom: 0.45rem;
      border-bottom: 2px solid rgba(255, 159, 28, 0.55);
    }
    
    .media-dropdown-menu {
      min-width: 320px;
      width: min(400px, calc(100vw - 2rem));
      padding: 1rem 1.2rem;
      transform: translateY(12px);
      transform-origin: top center;
      transition: all 0.8s ease-out;
    }
    .media-dropdown-menu.show {
      transform: translateY(0);
      animation: mediaFadeIn 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    @keyframes mediaFadeIn {
      0% {
        opacity: 0;
        transform: translateY(-8px) scale(0.95);
      }
      50% {
        opacity: 0.7;
        transform: translateY(2px) scale(1.02);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    .media-menu-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(140px, 1fr));
      gap: 0.5rem 1.5rem;
      align-items: start;
    }
    .media-menu-column {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0.2rem;
    }
    .media-menu-column a {
      display: block;
      padding: 0.5rem 0;
      color: var(--gray-2000);
      font-weight: 500;
      transition: color 0.4s ease;
    }
    .media-menu-column a:hover {
      color: var(--primary);
    }
    
    .about-dropdown-menu .about-menu-column a {
      display: block;
      width: auto;
      max-width: 500%;
      padding: 0.38rem 0;
      border-top: none;
      line-height: rem;
      white-space: normal;
    }
    .about-dropdown-menu .about-menu-column a:first-of-type {
      border-top: none;
    }
    .dropdown-menu.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .dropdown-menu.products-dropdown-menu.show {
      transform: translate(-50%, 0);
    }
    .dropdown-menu a {
      display: block;
      padding: 0.7rem 0;
      color: var(--gray-2000);
      font-weight: 400;
      background: transparent;
      text-decoration-line: underline;
      text-decoration-thickness: 1px;
      text-decoration-color: transparent;
      text-underline-offset: 0.14em;
    }
    .dropdown-menu a:hover {
      background: #05a7f1;
      color:cyan;
      text-decoration-line: underline;
      animation: goldUnderlineBlink 1s linear infinite;
    }
    .dropdown-caret {
      display: inline-block;
      transition: transform 0.2s ease;
    }
    .dropdown-btn[aria-expanded="true"] .dropdown-caret {
      transform: rotate(180deg);
    }
    .search-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      justify-self: end;
      padding: 1rem 1.25rem;
    }
    .search-wrapper input {
      border: 1px solid var(--gray-200);
      border-radius: 0;
      padding: 0.6rem 1rem 0.6rem 2.5rem;
      font-size: 0.9rem;
      width: 240px;
      background: var(--white);
      transition: var(--transition);
    }
    .search-wrapper input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(255,159,28,0.2);
    }
    .search-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray-600);
      font-size: 1rem;
    }
    .menu-toggle {
      display: none;
      align-items: center;
      justify-content: center;
      justify-self: end;
      width: 72px;
      min-height: 120px;
      border: none;
      background: var(--white);
      border-radius: 0;
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      transition: var(--transition);
    }
    .menu-toggle:hover {
      background: #e0f2fe;
    }
    .menu-toggle:focus-visible {
      outline: 3px solid rgba(255,159,28,0.45);
      outline-offset: 3px;
    }
    .menu-toggle-icon {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .menu-toggle-bar {
      width: 28px;
      height: 3px;
      border-radius: 999px;
      background: var(--primary);
      transition: transform 0.25s ease, opacity 0.25s ease;
    }
    .menu-toggle[aria-expanded="true"] .menu-toggle-bar:nth-child(1) {
      transform: translateY(9px) rotate(45deg);
    }
    .menu-toggle[aria-expanded="true"] .menu-toggle-bar:nth-child(2) {
      opacity: 0;
    }
    .menu-toggle[aria-expanded="true"] .menu-toggle-bar:nth-child(3) {
      transform: translateY(-9px) rotate(-45deg);
    }

    /* FULLSCREEN BANNER (margin top 20px, left/right 5px) */
    .hero-banner-wrapper {
      margin: 0 5px 0 5px;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      position: relative;
      background: #000;
    }
    .banner-full {
      width: 100%;
      height: calc(100vh - 110px);
      min-height: 520px;
      max-height: 800px;
      position: relative;
      overflow: hidden;
    }
    .banner-slide,
    .banner-video {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity 1.2s ease-in-out, transform 1.5s ease;
      transform: scale(1.02);
    }
    .banner-slide.active,
    .banner-video.active {
      opacity: 1;
      transform: scale(1);
      z-index: 1;
    }
    .banner-gradient-text {
      position: absolute;
      bottom: 8%;
      left: 5%;
      z-index: 12;
      max-width: 800px;
      background: linear-gradient(90deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0.3) 100%);
      backdrop-filter: blur(4px);
      padding: 1.6rem 2rem;
      border-radius: 32px;
      color: white;
      border-left: 6px solid var(--accent);
      animation: captionEnter 0.65s both;
    }
    @keyframes captionEnter {
      from { opacity: 0; transform: translateY(22px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .banner-gradient-text h2 {
      font-size: clamp(1.6rem, 4vw, 2.6rem);
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .banner-gradient-text.caption-change { animation: captionEnter 0.65s both; }
    .banner-gradient-text p {
      font-size: 1rem;
      opacity: 0.9;
    }
    .scroll-icon {
      position: absolute;
      bottom: 20px;
      left: 30px;
      transform: none;
      z-index: 15;
      display: flex;
      gap: 20px;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(12px);
      border-radius: 60px;
      padding: 0.6rem 1.2rem;
    }
    .scroll-icon button {
      color: white;
      font-size: 1.8rem;
      cursor: pointer;
      transition: transform 0.2s;
      user-select: none;
    }
    .scroll-icon button:hover {
      transform: scale(1.1);
      color: var(--accent);
    }
    .banner-pause {
      min-width: 42px;
      border: 0;
      border-radius: 999px;
      padding: 0.55rem 0.75rem;
      color: #fff;
      background: rgba(0,0,0,0.5);
      cursor: pointer;
      font: inherit;
    }
    .banner-pause:hover,
    .banner-pause:focus-visible {
      color: var(--accent);
      outline: 2px solid var(--accent);
    }
    .hero-cta {
      display: inline-flex;
      margin-top: 1rem;
      padding: 0.7rem 1.1rem;
      border-radius: 999px;
      color: #082d5f;
      background: var(--accent);
      font-weight: 800;
      text-decoration: none;
      transition: transform 0.2s ease, background 0.2s ease;
    }
    .hero-cta:hover { transform: translateY(-2px); background: #ffc15c; }
    .home-intro {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      align-items: center;
      gap: clamp(1.5rem, 4vw, 4rem);
      padding-block: 1rem;
    }
    .intro-media {
      min-height: 340px;
      position: relative;
      overflow: hidden;
      border-radius: 24px;
      background: #dbeafe;
      box-shadow: var(--shadow-md);
    }
    .intro-media img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.7s ease;
    }
    .intro-media:hover img { transform: scale(1.04); }
    .intro-media:after {
      content: '';
      position: absolute;
      inset: 45% 0 0;
      background: linear-gradient(transparent, rgba(3,24,48,.75));
    }
    .intro-media-caption {
      position: absolute;
      z-index: 1;
      right: 1.25rem;
      bottom: 1.25rem;
      left: 1.25rem;
      color: #fff;
      font-size: 1.1rem;
      font-weight: 700;
    }
    .intro-copy .eyebrow {
      margin-bottom: 0.65rem;
      color: #a45b00;
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }
    .intro-copy h2 {
      margin-bottom: 1rem;
      color: var(--primary-dark);
      font-size: clamp(2rem, 4vw, 3.25rem);
      line-height: 1.08;
    }
    .intro-copy p {
      max-width: 62ch;
      color: var(--gray-600);
      font-size: 1.08rem;
      line-height: 1.8;
    }
    .intro-actions { display: flex; flex-wrap: wrap; gap: 0.8rem; margin-top: 1.4rem; }
    .intro-actions .btn-soft { margin-top: 0; }
    .intro-actions .btn-outline {
      display: inline-block;
      padding: 0.65rem 1.3rem;
      border: 2px solid var(--primary);
      border-radius: 40px;
      color: var(--primary);
      font-weight: 700;
      text-decoration: none;
      transition: var(--transition);
    }
    .intro-actions .btn-outline:hover { color: #fff; background: var(--primary); }
    .intro-benefits-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1.1rem;
      margin-top: 2rem;
    }
    .intro-benefit {
      min-height: 190px;
      padding: 1.35rem;
      border: 1px solid var(--gray-200);
      border-radius: 18px;
      background: #fff;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
    .intro-benefit:hover { transform: translateY(-6px); border-color: var(--accent); box-shadow: var(--shadow-md); }
    .intro-benefit-icon { display: block; margin-bottom: 0.65rem; font-size: 1.8rem; }
    .intro-benefit h3 { margin-bottom: 0.45rem; color: var(--primary); font-size: 1.05rem; }
    .intro-benefit p { color: var(--gray-600); font-size: 0.94rem; line-height: 1.6; }
    .scroll-reveal {
      opacity: 1;
      transform: none;
    }
    .has-scroll-reveal .scroll-reveal {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .has-scroll-reveal .scroll-reveal.is-visible {
      opacity: 1;
      transform: translateY(0);
    }
    .banner-dots {
      position: absolute;
      bottom: 20px;
      right: 30px;
      display: flex;
      gap: 12px;
      z-index: 12;
    }
    .dot {
      width: 12px;
      height: 12px;
      padding: 0;
      border: 0;
      background: rgba(255,255,255,0.5);
      border-radius: 50%;
      cursor: pointer;
      transition: 0.2s;
    }
    .dot.active {
      background: var(--accent);
      width: 28px;
      border-radius: 12px;
    }

    .executive-section {
      scroll-margin-top: 145px;
      margin-top: 3rem;
    }
    .executive-hero {
      min-height: calc(100vh - 110px);
      max-height: 820px;
      margin: 0 5px;
      background-size: cover;
      background-position: center;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      display: flex;
      align-items: flex-end;
    }
    .executive-hero:before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(2, 6, 23, 0.08), rgba(2, 6, 23, 0.78));
    }
    .executive-hero-copy {
      position: relative;
      z-index: 1;
      width: min(900px, calc(100% - 2rem));
      margin: 0 0 6vh 5vw;
      color: #fff;
      border-left: 6px solid var(--accent);
      background: linear-gradient(90deg, rgba(0,0,0,0.68), rgba(0,0,0,0.24));
      padding: 1.6rem 2rem;
    }
    .executive-hero-copy h2 {
      font-size: clamp(2rem, 5vw, 3.8rem);
      line-height: 1.05;
      margin-bottom: 0.75rem;
    }
    .executive-hero-copy p {
      font-size: 1.05rem;
      max-width: 760px;
    }
    .executive-members-wrap {
      max-width: 1280px;
      margin: 3rem auto;
      padding: 0 1.5rem;
      text-align: center;
    }
    .executive-members-grid {
      display: flex;
      flex-direction: column;
      gap: 3rem;
      margin-top: 2rem;
    }
    .executive-row {
      display: flex;
      justify-content: center;
      gap: 0.8rem;
      flex-wrap: wrap;
    }
    .card-flip {
      width: 250px;
      height: 350px;
      perspective: 2000px;
    }
    .card-inner {
      position: relative;
      width: 100%;
      height: 100%;
      text-align: center;
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }
    .card-flip:hover .card-inner {
      transform: rotateY(180deg);
    }
    .card-front, .card-back {
      position: absolute;
      width: 100%;
      height: 100%;
      -webkit-backface-visibility: hidden;
      backface-visibility: hidden;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 0.5rem;
    }
    .card-front {
      background: #fff;
    }
    .card-back {
      background: #052561;
      transform: rotateY(180deg);
      overflow-y: auto;
    }
    .executive-image {
      width: 200px;
      height: 400px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 0.5rem;padding-top:1.5rem;
    }
    .card-front h3 {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
      color:navy;
    }
    .card-front p {
      font-size: 0.9rem;
      color: navy;
      line-height: 1.2; padding-bottom:0.9rem;
    }
    .back-content {
      width: 100%;
      text-align: left;
    }
    .back-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 0.5rem;
      font-size: 0.8rem;
      color: white;
    }
    .back-item i {
      margin-right: 0.5rem;
      width: 16px;
      flex-shrink: 0;
    }
    .icon-history:before { content: "📜"; }
    .icon-education:before { content: "🎓"; }
    .icon-experience:before { content: "💼"; }
    .icon-profession:before { content: "🏢"; }
    .icon-skills:before { content: "🛠️"; }

    /* General sections */
    .container {
      max-width: 1280px;
      margin: 3rem auto;
      padding: 0 1.5rem;
    }
    .section-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 2rem;
      position: relative;
      display: inline-block;
    }
    .section-title:after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background: var(--accent);
      border-radius: 4px;
    }

    /* Cards for Vision/Mission/Values (image overlay + timegap reveal) */
    .pillars-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin: 2rem 0;
    }
    .pillar-card {
      background: white;
      border-radius: 28px;
      overflow: hidden;
      position: relative;
      transition: var(--transition);
      box-shadow: var(--shadow-sm);
      cursor: pointer;
      transform: translateY(0);
      opacity: 0;
      animation: fadeUp 0.6s forwards;
    }
    .pillar-card:nth-child(1) { animation-delay: 0.1s; }
    .pillar-card:nth-child(2) { animation-delay: 0.3s; }
    .pillar-card:nth-child(3) { animation-delay: 0.5s; }
    .pillar-card:nth-child(4) { animation-delay: 0.7s; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(28px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .pillar-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 30px 40px -18px rgba(0,0,0,0.2);
    }
    .pillar-img {
      width: 100%;
      height: 300px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .pillar-card:hover .pillar-img {
      transform: scale(1.05);
    }
    .pillar-content {
      padding: 1rem;
      background: white;
    }
    .pillar-content h3 {
      font-size: 1.7rem;
      color: var(--primary);
      margin-bottom: 0.75rem;
    }
    .pillar-content p {
      color: var(--gray-600);
      line-height: 1.6;
    }

    /* Feature overflow / interactive grid */
    .feature-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 2rem;
      margin: 2rem 0;
    }
    .feature-card {
      background: white;
      border-radius: 28px;
      padding: 1.8rem;
      transition: var(--transition);
      border: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      border-color: var(--accent);
      box-shadow: var(--shadow-md);
    }
    .feature-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }
    .btn-soft {
      background: var(--primary);
      color: white;
      border: none;
      padding: 0.7rem 1.4rem;
      border-radius: 40px;
      font-weight: bold;
      margin-top: 1rem;
      display: inline-block;
      transition: 0.2s;
      text-decoration: none;
    }
    .btn-soft:hover {
      background: cyan;
      color: var(--gray-800);
      transform: scale(0.98);
    }

    .product-page-shell {
      background: #ffffff;
      border: 1px solid rgba(12, 61, 129, 0.12);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }
    #productPages {
      scroll-margin-top: 150px;
    }
    .product-page-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      padding: 1rem 1.2rem;
      background: #eaf7fb;
      border-bottom: 1px solid rgba(12, 61, 129, 0.12);
    }
    .product-category-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.55rem;
    }
    .product-tab,
    .product-edit-btn,
    .quote-submit {
      border: none;
      background: var(--primary);
      color: #fff;
      padding: 0.65rem 1rem;
      cursor: pointer;
      font-weight: 700;
      transition: var(--transition);
    }
    .product-tab:hover,
    .product-tab.active,
    .product-edit-btn:hover,
    .quote-submit:hover {
      background: #05a7f1;
      color: #082d5f;
    }
    .product-edit-actions {
      display: none;
      gap: 0.55rem;
      flex-wrap: wrap;
    }
    .product-page-grid {
      display: grid;
      grid-template-columns: minmax(220px, 290px) minmax(0, 1fr);
      min-height: 560px;
    }
    .product-list-panel {
      background: #f8fafc;
      border-right: 1px solid rgba(12, 61, 129, 0.12);
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .product-list-button {
      width: 100%;
      border: 1px solid rgba(12, 61, 129, 0.12);
      background: #fff;
      color: var(--gray-800);
      cursor: pointer;
      padding: 0.85rem 0.9rem;
      text-align: left;
      font-weight: 700;
      transition: var(--transition);
    }
    .product-list-button:hover,
    .product-list-button.active {
      background: var(--primary);
      color: #fff;
      transform: translateX(3px);
    }
    .product-detail-panel {
      display: grid;
      grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.05fr);
      gap: 1.4rem;
      padding: 1.35rem;
    }
    .product-hero-card {
      min-height: 0;
      aspect-ratio: 4 / 3;
      background-size: cover;
      background-position: center;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: flex-end;
      border: 1px solid rgba(12, 61, 129, 0.16);
      box-shadow: var(--shadow-sm);
    }
    .product-hero-card:before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0.05), rgba(3,24,48,0.78));
    }
    .product-hero-copy {
      position: relative;
      z-index: 1;
      color: #fff;
      padding: 1.5rem;
      width: 100%;
      background: linear-gradient(90deg, rgba(3,24,48,0.82), rgba(3,24,48,0.42));
    }
    .product-kicker {
      display: inline-block;
      background: rgba(255, 159, 28, 0.95);
      color: #082d5f;
      font-weight: 800;
      padding: 0.35rem 0.6rem;
      margin-bottom: 0.85rem;
    }
    .product-hero-copy h2 {
      font-size: clamp(1.8rem, 4vw, 3rem);
      line-height: 1.05;
      margin-bottom: 0.75rem;
    }
    .product-hero-copy p {
      font-size: 1.05rem;
      max-width: 760px;
    }
    .product-detail-content {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .product-info-card,
    .quote-card {
      background: #ffffff;
      border: 1px solid rgba(12, 61, 129, 0.12);
      box-shadow: var(--shadow-sm);
      padding: 1rem;
    }
    .product-info-card h3,
    .quote-card h3 {
      color: var(--primary);
      margin-bottom: 0.65rem;
    }
    .product-newspaper-copy p {
      columns: 2 220px;
      column-gap: 1.6rem;
      text-align: justify;
      font-family: Georgia, 'Times New Roman', serif;
      font-size: 1.02rem;
      line-height: 1.75;
    }
    .product-newspaper-copy p::first-letter {
      float: left;
      color: var(--primary);
      font-size: 3.35rem;
      line-height: 0.9;
      padding-right: 0.35rem;
      font-weight: 800;
    }
    .product-chip-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.55rem;
    }
    .product-chip {
      background: #e0f2fe;
      color: #075985;
      padding: 0.45rem 0.65rem;
      font-weight: 700;
      font-size: 0.88rem;
    }
    .quote-card label {
      display: block;
      color: var(--gray-600);
      font-size: 0.86rem;
      font-weight: 700;
      margin: 0.75rem 0 0.3rem;
    }
    .quote-card input,
    .quote-card select,
    .quote-card textarea {
      width: 100%;
      border: 1px solid var(--gray-200);
      padding: 0.72rem 0.8rem;
      font: inherit;
      background: #f8fafc;
    }
    .quote-result {
      margin: 0.85rem 0;
      background: #fff7ed;
      color: #7c2d12;
      padding: 0.8rem;
      font-weight: 800;
      border-left: 4px solid var(--accent);
    }
    .editable-on [data-edit-field] {
      outline: 2px dashed #05a7f1;
      outline-offset: 4px;
      cursor: text;
    }

    /* Insurance News + dynamic */
    .news-slider {
      display: flex;
      gap: 1.8rem;
      overflow-x: auto;
      scroll-behavior: smooth;
      padding: 1rem 0.2rem 2rem;
      scrollbar-width: thin;
    }
    .news-slider::-webkit-scrollbar {
      height: 6px;
    }
    .news-item-card {
      min-width: 300px;
      background: white;
      border-radius: 28px;
      padding: 1.5rem;
      flex-shrink: 0;
      transition: var(--transition);
      border: 1px solid var(--gray-200);
    }
    .news-item-card:hover {
      background: var(--gray-100);
    }
    .slider-controls {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .slider-controls button {
      background: var(--primary);
      border: none;
      color: white;
      font-size: 1.6rem;
      width: 44px;
      border-radius: 60px;
      cursor: pointer;
      transition: 0.2s;
    }

    footer {
      background: var(--primary-dark);
      color: white;
      padding: 3rem 2rem;
      margin-top: 4rem;
    }
    .footer-grid {
      max-width: 1280px;
      margin: auto;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 2rem;
    }

    @media (max-width: 1000px) {
      .intro-benefits-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .feature-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 860px) {
      .site-header { padding: 0.85rem 0.75rem 1rem; }
      .navbar-container {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
      }
      .menu-toggle { display: inline-flex; }
      .navbar-container > .brand {
        width: auto;
        min-width: 0;
      }
      .navbar-container > .nav-links,
      .navbar-container > .search-wrapper {
        width: 100%;
        grid-column: 1 / -1;
        display: none;
      }
      .navbar-container.mobile-open > .nav-links,
      .navbar-container.mobile-open > .search-wrapper {
        display: flex;
      }
      .brand {
        width: 100%;
        padding-right: 0.5rem;
      }
      .company-logo {
        width: clamp(100px, 34vw, 150px);
        margin-left: 0.75rem;
        min-height: auto;
        padding-top: 0;
      }
      .company-logo img {
        width: 100%;
      }
      .nav-links {
        justify-content: flex-start;
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 1rem;
      }
      .nav-links a,
      .dropdown-btn {
        width: 100%;
        justify-content: space-between;
        font-size: 0.5rem;
        padding: 0.85rem 1rem;
        color: var(--primary);
        background: rgba(12, 61, 129, 0.06);
      }
      .dropdown {
        width: 100%;
      }
      .dropdown-menu {
        position: static;
        min-width: 100%;
        margin-top: 0.5rem;
        border-radius: 0;
        box-shadow: none;
        border: none;
        padding: 0.35rem 0;
        display: none;
        opacity: 1;
        visibility: visible;
        transform: none;
      }
      .dropdown-menu.show {
        display: block;
      }
      .dropdown-menu a {
        padding: 0.75rem 1rem;
      }
      .about-dropdown-menu {
        width: 100%;
        min-width: 100%;
        padding: 0.85rem 1rem;
      }
      .products-dropdown-menu {
        left: auto;
        width: 100%;
        min-width: 100%;
        padding: 0.85rem 1rem;
        transform: none;
        transform-origin: top center;
      }
      .products-dropdown-menu.show {
        transform: none;
        animation: mobileProductWaveDown 0.32s ease;
      }
      .dropdown-menu.products-dropdown-menu.show {
        transform: none;
      }
      @keyframes mobileProductWaveDown {
        0% {
          opacity: 0;
          transform: translateY(-8px) scaleY(0.82);
        }
        100% {
          opacity: 1;
          transform: translateY(0) scaleY(1);
        }
      }
      .about-menu-grid {
        grid-template-columns: 1fr;
        gap: 0.6rem;
      }
      .products-menu-grid {
        grid-template-columns: 1fr;
        gap: 0.8rem;
      }
      .about-menu-title {
        padding: 0 0 0.35rem;
      }
      .about-dropdown-menu .about-menu-column a {
        padding: 0.5rem 0;
      }
      .search-wrapper {
        justify-content: flex-start;
        justify-self: stretch;
        padding: 0 1rem 1rem;
      }
      .search-wrapper input { width: 100%; }
      .search-icon { left: 28px; }
      .banner-full { height: 60vh; min-height: 400px; }
      .hero-banner-wrapper { margin: 0 5px 0 5px; }
      .banner-gradient-text { padding: 1rem; bottom: 12%; }
      .scroll-icon { left: 50%; transform: translateX(-50%); }
      .home-intro { grid-template-columns: 1fr; }
      .intro-media { min-height: 280px; }
      .executive-hero {
        min-height: 60vh;
      }
      .executive-hero-copy {
        margin: 0 1rem 2rem;
        padding: 1rem;
      }
      .executive-members-grid {
        gap: 1rem;
      }
      .executive-row {
        gap: 1rem;
      }
      .card-flip {
        width: 220px;
        height: 320px;
      }
      .executive-image {
        width: 120px;
        height: 120px;
      }
      .product-page-grid,
      .product-detail-panel {
        grid-template-columns: 1fr;
      }
      .product-list-panel {
        border-right: none;
        border-bottom: 1px solid rgba(12, 61, 129, 0.12);
      }
      .product-hero-card {
        min-height: 360px;
      }
    }
    @media (max-width: 560px) {
      .intro-benefits-grid,
      .feature-grid { grid-template-columns: 1fr; }
      .intro-media { min-height: 230px; }
      .executive-row {
        flex-direction: column;
        align-items: center;
      }
      .card-flip {
        width: 280px;
        height: 350px;
      }
      .executive-image {
        width: 140px;
        height: 140px;
      }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        scroll-behavior: auto !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
      .has-scroll-reveal .scroll-reveal { opacity: 1; transform: none; }
    }
  </style>
</head>
<body>

<header class="site-header">
  <div class="navbar-container">
    <a href="#" class="brand" aria-label="Awash Insurance home">
      <div class="company-logo">
        <img src="logob.png" alt="Awash Insurance logo">
      </div>
    </a>
    <button class="menu-toggle" id="mobileMenuToggle" type="button" aria-label="Toggle navigation" aria-controls="primaryNav headerSearch" aria-expanded="false">
      <span class="menu-toggle-icon" aria-hidden="true">
        <span class="menu-toggle-bar"></span>
        <span class="menu-toggle-bar"></span>
        <span class="menu-toggle-bar"></span>
      </span>
    </button>
    <nav class="nav-links" id="primaryNav" aria-label="Primary navigation">
      <a href="#">Home</a>
      <div class="dropdown">
        <button class="dropdown-btn" id="aboutDropdownBtn">About Us ▾</button>
        <div class="dropdown-menu about-dropdown-menu" id="aboutSubmenu">
          <div class="about-menu-grid">
            <div class="about-menu-column">

              <a href="#">About Us</a>
              <a href="#">Company Profile</a>
              <a href="#">Organization Structure</a>
               <a href="#">Company Performance</a>
            </div> 
            <div class="about-menu-column">
              <a href="#executiveManagement" data-about-target="executiveManagement">Executive Managements Members</a>
              <a href="#">Boards Directors Members</a>
             
            </div>
          </div>
        </div>
      </div>
      <div class="dropdown products-dropdown" id="productsDropdown">
        <button class="dropdown-btn" id="productsDropdownBtn" type="button">Our Products <span class="dropdown-caret" aria-hidden="true">&#9662;</span></button>
        <div class="dropdown-menu products-dropdown-menu" id="productsSubmenu">
          <div class="products-menu-grid">
            <?php foreach ($product_catalog as $category => $products): ?>
            <div class="products-menu-column">
              <div class="products-menu-title"><?php echo htmlspecialchars($category); ?></div>
              <?php foreach ($products as $product): ?>
              <a href="#product-<?php echo htmlspecialchars($product['key']); ?>" data-product-key="<?php echo htmlspecialchars($product['key']); ?>">
                <?php echo htmlspecialchars($product['title']); ?>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <a href="#">Services</a>
      <div class="dropdown media-dropdown" id="mediaDropdown">
        <button class="dropdown-btn" id="mediaDropdownBtn" type="button">Media <span class="dropdown-caret" aria-hidden="true">&#9662;</span></button>
        <div class="dropdown-menu media-dropdown-menu" id="mediaSubmenu">
          <div class="media-menu-grid">
            <div class="media-menu-column">
              <a href="news_room.php">News Room</a>
              <a href="advertisement.php">Advertisement</a>
            </div>
            <div class="media-menu-column">
              <a href="gallery_room.php">Gallery Room</a>
              <a href="live.php">Live</a>
            </div>
          </div>
        </div>
      </div>
      <a href="#">Contact Us</a>
    </nav>
    <div class="search-wrapper" id="headerSearch">
      <span class="search-icon">🔍</span>
      <input type="search" placeholder="">
    </div>
  </div>
</header>

<!-- FULL SCREEN BANNER with margin top 20px, left/right 5px, image timegap reveal + scroll icons left/right -->
<div class="hero-banner-wrapper">
  <div class="banner-full" id="heroFullscreen">
    <?php foreach ($blog_banner_slides as $index => $slide): ?>
    <?php
      $slideImage = is_scalar($slide['image'] ?? null) ? (string) $slide['image'] : '';
      $slideAlt = is_scalar($slide['alt'] ?? null) ? (string) $slide['alt'] : '';
      $slideVideo = is_scalar($slide['video'] ?? null) ? trim((string) $slide['video']) : '';
      $videoScheme = strtolower((string) parse_url($slideVideo, PHP_URL_SCHEME));
      $isSafeVideoUrl = $slideVideo !== '' && (
          (in_array($videoScheme, ['http', 'https'], true) && filter_var($slideVideo, FILTER_VALIDATE_URL) !== false)
          || (strpos($slideVideo, '/') === 0 && strpos($slideVideo, '//') !== 0)
      );
    ?>
    <?php if ($isSafeVideoUrl): ?>
    <video class="banner-slide banner-video<?php echo $index === 0 ? ' active' : ''; ?>" muted playsinline loop preload="metadata" poster="<?php echo htmlspecialchars($slideImage, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($slideAlt, ENT_QUOTES, 'UTF-8'); ?>">
      <source src="<?php echo htmlspecialchars($slideVideo, ENT_QUOTES, 'UTF-8'); ?>">
    </video>
    <?php else: ?>
    <img class="banner-slide<?php echo $index === 0 ? ' active' : ''; ?>" src="<?php echo htmlspecialchars($slideImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($slideAlt, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php endforeach; ?>
    <div class="banner-gradient-text" id="bannerCaption" aria-live="polite">
      <h2><?php echo htmlspecialchars($blog_banner_slides[0]['title'] ?? 'Global insurance, local care'); ?></h2>
      <p><?php echo htmlspecialchars($blog_banner_slides[0]['description'] ?? 'Protecting what matters most — tailored coverage for families & enterprises'); ?></p>
      <a class="hero-cta" href="#productPages">Explore our protection</a>
    </div>
    <div class="scroll-icon" aria-label="Homepage slides">
      <button class="banner-pause" id="bannerPauseBtn" type="button" aria-label="Pause slideshow" aria-pressed="false">Ⅱ</button>
      <button class="banner-pause" id="scrollLeftBtn" type="button" aria-label="Previous slide">◀</button>
      <button class="banner-pause" id="scrollRightBtn" type="button" aria-label="Next slide">▶</button>
    </div>
    <div class="banner-dots" id="bannerDotsContainer"></div>
  </div>
</div>

<section class="container home-intro scroll-reveal" aria-labelledby="homeIntroTitle">
  <div class="intro-media">
    <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1400&q=85" alt="A welcoming modern office where customers can get support">
    <span class="intro-media-caption">People first. Protection that moves with you.</span>
  </div>
  <div class="intro-copy">
    <div class="eyebrow">Here for what matters</div>
    <h2 id="homeIntroTitle">Confidence for every step ahead</h2>
    <p>Explore protection for your family, your work, and the plans you are building. Our team is here to make your options easier to understand, help you choose cover that fits, and support you when you need us.</p>
    <div class="intro-actions">
      <a class="btn-soft" href="#productPages">Explore products</a>
      <a class="btn-outline" href="#latestInsights">Read latest insights</a>
    </div>
  </div>
</section>

<section class="container scroll-reveal" aria-label="How we help">
  <div class="intro-benefits-grid">
    <article class="intro-benefit scroll-reveal">
      <span class="intro-benefit-icon" aria-hidden="true">🧭</span>
      <h3>Clear guidance</h3>
      <p>Understand your options with practical explanations and help choosing suitable protection.</p>
    </article>
    <article class="intro-benefit scroll-reveal">
      <span class="intro-benefit-icon" aria-hidden="true">🤝</span>
      <h3>People who listen</h3>
      <p>Get support shaped around your questions, priorities, and changing needs.</p>
    </article>
    <article class="intro-benefit scroll-reveal">
      <span class="intro-benefit-icon" aria-hidden="true">⚡</span>
      <h3>Simple next steps</h3>
      <p>Find useful information and take the next step without unnecessary complexity.</p>
    </article>
    <article class="intro-benefit scroll-reveal">
      <span class="intro-benefit-icon" aria-hidden="true">🛡️</span>
      <h3>Support when it counts</h3>
      <p>Know where to turn when you need assistance with your protection or a claim.</p>
    </article>
  </div>
</section>

<section class="executive-section" id="executiveManagement" style="display: none;">
  <div class="executive-members-wrap">
    <h2 class="section-title">Executive Management Members</h2>
    <div class="executive-members-grid">
      <?php $members = $executive_management['members'] ?? []; ?>
      <!-- First row: 1 column -->
      <div class="executive-row">
        <?php if (isset($members[0])): ?>
        <div class="card-flip">
          <div class="card-inner">
            <div class="card-front">
              <img src="<?php echo htmlspecialchars($members[0]['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($members[0]['name'] ?? ''); ?>" class="executive-image">
              <h3><?php echo htmlspecialchars($members[0]['name'] ?? 'Executive Member'); ?></h3>
              <p><?php echo htmlspecialchars($members[0]['description'] ?? ''); ?></p>
            </div>
            <div class="card-back">
              <div class="back-content">
                <div class="back-item"><i class="icon-history"></i><span>History: <?php echo htmlspecialchars($members[0]['history'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-education"></i><span>Education: <?php echo htmlspecialchars($members[0]['education'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-experience"></i><span>Experience: <?php echo htmlspecialchars($members[0]['experience'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-profession"></i><span>Profession: <?php echo htmlspecialchars($members[0]['profession'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-skills"></i><span>Skills: <?php echo htmlspecialchars($members[0]['skills'] ?? ''); ?></span></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <!-- Second row: 2 columns -->
      <div class="executive-row">
        <?php for ($i = 1; $i <= 2; $i++): ?>
        <?php if (isset($members[$i])): ?>
        <div class="card-flip">
          <div class="card-inner">
            <div class="card-front">
              <img src="<?php echo htmlspecialchars($members[$i]['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($members[$i]['name'] ?? ''); ?>" class="executive-image">
              <h3><?php echo htmlspecialchars($members[$i]['name'] ?? 'Executive Member'); ?></h3>
              <p><?php echo htmlspecialchars($members[$i]['description'] ?? ''); ?></p>
            </div>
            <div class="card-back">
              <div class="back-content">
                <div class="back-item"><i class="icon-history"></i><span>History: <?php echo htmlspecialchars($members[$i]['history'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-education"></i><span>Education: <?php echo htmlspecialchars($members[$i]['education'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-experience"></i><span>Experience: <?php echo htmlspecialchars($members[$i]['experience'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-profession"></i><span>Profession: <?php echo htmlspecialchars($members[$i]['profession'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-skills"></i><span>Skills: <?php echo htmlspecialchars($members[$i]['skills'] ?? ''); ?></span></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php endfor; ?>
      </div>
      <!-- Bottom row: 5 columns -->
      <div class="executive-row">
        <?php for ($i = 3; $i <= 7; $i++): ?>
        <?php if (isset($members[$i])): ?>
        <div class="card-flip">
          <div class="card-inner">
            <div class="card-front">
              <img src="<?php echo htmlspecialchars($members[$i]['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($members[$i]['name'] ?? ''); ?>" class="executive-image">
              <h3><?php echo htmlspecialchars($members[$i]['name'] ?? 'Executive Member'); ?></h3>
              <p><?php echo htmlspecialchars($members[$i]['description'] ?? ''); ?></p>
            </div>
            <div class="card-back">
              <div class="back-content">
                <div class="back-item"><i class="icon-history"></i><span>History: <?php echo htmlspecialchars($members[$i]['history'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-education"></i><span>Education: <?php echo htmlspecialchars($members[$i]['education'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-experience"></i><span>Experience: <?php echo htmlspecialchars($members[$i]['experience'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-profession"></i><span>Profession: <?php echo htmlspecialchars($members[$i]['profession'] ?? ''); ?></span></div>
                <div class="back-item"><i class="icon-skills"></i><span>Skills: <?php echo htmlspecialchars($members[$i]['skills'] ?? ''); ?></span></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</section>

<div class="container scroll-reveal" id="productPages">
  <h2 class="section-title">Our Products</h2>
  <div class="product-page-shell" id="productPageShell">
    <div class="product-page-toolbar">
      <div class="product-category-tabs" id="productCategoryTabs"></div>
      <div class="product-edit-actions">
        <button class="product-edit-btn" type="button" id="toggleProductEdit">Edit Content</button>
        <button class="product-edit-btn" type="button" id="saveProductEdit">Save</button>
        <button class="product-edit-btn" type="button" id="resetProductEdit">Reset</button>
      </div>
    </div>
    <div class="product-page-grid">
      <aside class="product-list-panel" id="productListPanel" aria-label="Product list"></aside>
      <section class="product-detail-panel" aria-live="polite">
        <article class="product-hero-card" id="productHeroCard">
          <div class="product-hero-copy">
            <span class="product-kicker" id="productCategoryLabel"></span>
            <h2 id="productTitle" data-edit-field="title"></h2>
            <p id="productTagline" data-edit-field="tagline"></p>
          </div>
        </article>
        <div class="product-detail-content">
          <div class="product-info-card product-newspaper-copy">
            <h3>Why choose this cover</h3>
            <p id="productDescription" data-edit-field="description"></p>
          </div>
          <div class="product-info-card">
            <h3>Key benefits</h3>
            <div class="product-chip-list" id="productHighlights"></div>
          </div>
          <div class="product-info-card">
            <h3>Best for</h3>
            <div class="product-chip-list" id="productIdealFor"></div>
          </div>
          <div class="quote-card">
            <h3>Start a quote</h3>
            <label for="quoteName">Name</label>
            <input id="quoteName" type="text" placeholder="Your name">
            <label for="quotePhone">Phone or email</label>
            <input id="quotePhone" type="text" placeholder="How we can reach you">
            <label for="quoteValue">Estimated value to insure</label>
            <input id="quoteValue" type="number" min="0" step="1000" value="500000">
            <label for="quotePackage">Preferred package</label>
            <select id="quotePackage">
              <option value="0.012">Essential</option>
              <option value="0.018">Plus</option>
              <option value="0.026">Premium</option>
            </select>
            <div class="quote-result" id="quoteResult"></div>
            <label for="quoteMessage">Request details</label>
            <textarea id="quoteMessage" rows="3" placeholder="Tell us what you want covered"></textarea>
            <button class="quote-submit" type="button" id="quoteSubmit">Request Product Advice</button>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<!-- Goal, Mission, Vision, Values with image overlays (time gap reveal) -->
<div class="container scroll-reveal">
  <h2 class="section-title">Our Compass</h2>
  <div class="pillars-grid" id="coreGrid">
    <?php
    $pillar_images = [
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=800&q=80'
    ];
    foreach ($blog_core_pillars as $index => $pillar):
        $image_url = $pillar_images[$index] ?? $pillar_images[0];
    ?>
    <div class="pillar-card" data-pillar="<?php echo strtolower(str_replace(' ', '', $pillar['title'])); ?>">
      <img class="pillar-img" src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars(strtolower($pillar['title'])); ?>">
      <div class="pillar-content">
        <h3><?php echo htmlspecialchars($pillar['title']); ?></h3>
        <p><?php echo htmlspecialchars($pillar['description']); ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Insurance News with slide left/right overflow interactive -->
<div class="container scroll-reveal" id="latestInsights">
  <div class="slider-controls">
    <button id="newsSlideLeft">◀</button>
    <button id="newsSlideRight">▶</button>
  </div>
  <h2 class="section-title">Latest Insurance Insights</h2>
  <div class="news-slider" id="newsSlider">
    <?php
    $news_icons = ['📈', '🏆', '🤖', '🌱', '📱', '💡', '🚀', '📊'];
    $default_labels = ['NEWS', 'AWARD', 'UPDATE', 'GREEN', 'DIGITAL', 'INNOVATION', 'LAUNCH', 'ANALYTICS'];
    foreach ($blog_insurance_news as $index => $news_item):
        $icon = $news_icons[$index % count($news_icons)] ?? '📈';
        $label = $news_item['label'] ?: ($default_labels[$index % count($default_labels)] ?? 'NEWS');
    ?>
    <div class="news-item-card">
      <strong><?php echo htmlspecialchars($icon . ' ' . $news_item['title']); ?></strong>
      <p><?php echo htmlspecialchars($news_item['description']); ?></p>
      <a href="<?php echo htmlspecialchars($news_item['link_url'] ?: '#'); ?>" class="btn-soft" style="margin-top:12px; display:inline-block;">
        <?php
        $link_text = 'Read more';
        if (stripos($news_item['link_url'], 'learn') !== false) $link_text = 'Learn more';
        elseif (stripos($news_item['link_url'], 'explore') !== false) $link_text = 'Explore';
        elseif (stripos($news_item['link_url'], 'update') !== false) $link_text = 'Update';
        echo htmlspecialchars($link_text);
        ?>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Feature overflow interactive block -->
<div class="container scroll-reveal">
  <h2 class="section-title">Awashinsurance.com</h2>
  <div class="feature-grid">
    <?php
    $feature_icons = ['🛡️', '⚡', '🏅', '🌍', '🤖', '💎'];
    foreach ($blog_feature_updates as $index => $feature):
        $icon = $feature_icons[$index % count($feature_icons)] ?? '🛡️';
    ?>
    <div class="feature-card">
      <div class="feature-icon"><?php echo htmlspecialchars($icon); ?></div>
      <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
      <p><?php echo htmlspecialchars($feature['description']); ?></p>
      <a href="<?php echo htmlspecialchars($feature['link_url'] ?: '#'); ?>" class="btn-soft">
        <?php
        $link_text = 'Discover';
        if (stripos($feature['link_url'], 'experience') !== false) $link_text = 'Experience';
        elseif (stripos($feature['link_url'], 'view') !== false) $link_text = 'View rating';
        elseif (stripos($feature['link_url'], 'learn') !== false) $link_text = 'Learn more';
        echo htmlspecialchars($link_text);
        ?>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Additional Dynamic Content: Company Performance snippet -->
<div class="container">
  <div class="feature-card" style="background:linear-gradient(135deg, #fff, #f0f4fe);">
    <h3><?php echo htmlspecialchars($blog_performance['title'] ?: '📊 Company Performance Highlights'); ?></h3>
    <p><?php echo htmlspecialchars($blog_performance['description'] ?: 'Performance metrics and highlights'); ?></p>
    <a href="<?php echo htmlspecialchars($blog_performance['link_url'] ?: '#'); ?>" class="btn-soft">
      <?php echo htmlspecialchars($blog_performance['link_text'] ?: 'Annual Report →'); ?>
    </a>
  </div>
</div>

<footer>
  <div class="footer-grid">
    <div><strong>PrimeShield Insurance</strong><br>© 2026 All rights reserved.<br>Leading global insurer with local empathy.</div>
    <div><strong>Quick Links</strong><br><a href="#" style="color:#ccc;">About us</a><br><a href="#" style="color:#ccc;">Privacy</a><br><a href="#" style="color:#ccc;">Claims</a></div>
    <div><strong>Connect</strong><br>📞 1-800-PRIME-88<br>📧 hello@primeshield.com</div>
  </div>
</footer>

<script>
  // 1. Dropdown for About Us (preserve other parts)
  const navbarContainer = document.querySelector('.navbar-container');
  const menuToggle = document.getElementById('mobileMenuToggle');
  const aboutBtn = document.getElementById('aboutDropdownBtn');
  const aboutMenu = document.getElementById('aboutSubmenu');
  const productsDropdown = document.getElementById('productsDropdown');
  const productsBtn = document.getElementById('productsDropdownBtn');
  const productsMenu = document.getElementById('productsSubmenu');
  const mediaDropdown = document.getElementById('mediaDropdown');
  const mediaBtn = document.getElementById('mediaDropdownBtn');
  const mediaMenu = document.getElementById('mediaSubmenu');
  const mobileNavQuery = window.matchMedia('(max-width: 860px)');
  const mobileNavLinks = document.querySelectorAll('.nav-links > a, .dropdown-menu a');
  const productCatalog = <?php echo json_encode($product_catalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
  const productPages = document.getElementById('productPages');
  const productPageShell = document.getElementById('productPageShell');
  const productCategoryTabs = document.getElementById('productCategoryTabs');
  const productListPanel = document.getElementById('productListPanel');
  const productHeroCard = document.getElementById('productHeroCard');
  const productCategoryLabel = document.getElementById('productCategoryLabel');
  const productTitle = document.getElementById('productTitle');
  const productTagline = document.getElementById('productTagline');
  const productDescription = document.getElementById('productDescription');
  const productHighlights = document.getElementById('productHighlights');
  const productIdealFor = document.getElementById('productIdealFor');
  const toggleProductEdit = document.getElementById('toggleProductEdit');
  const saveProductEdit = document.getElementById('saveProductEdit');
  const resetProductEdit = document.getElementById('resetProductEdit');
  const quoteValue = document.getElementById('quoteValue');
  const quotePackage = document.getElementById('quotePackage');
  const quoteResult = document.getElementById('quoteResult');
  const quoteMessage = document.getElementById('quoteMessage');
  const quoteSubmit = document.getElementById('quoteSubmit');
  const productByKey = {};
  const productCategoryByKey = {};
  let currentProductKey = '';
  let productEditMode = false;

  Object.entries(productCatalog).forEach(([category, products]) => {
    products.forEach(product => {
      productByKey[product.key] = product;
      productCategoryByKey[product.key] = category;
    });
  });

  aboutBtn.setAttribute('aria-haspopup', 'true');
  aboutBtn.setAttribute('aria-controls', 'aboutSubmenu');
  aboutBtn.setAttribute('aria-expanded', 'false');
  productsBtn.setAttribute('aria-haspopup', 'true');
  productsBtn.setAttribute('aria-controls', 'productsSubmenu');
  productsBtn.setAttribute('aria-expanded', 'false');
  mediaBtn.setAttribute('aria-haspopup', 'true');
  mediaBtn.setAttribute('aria-controls', 'mediaSubmenu');
  mediaBtn.setAttribute('aria-expanded', 'false');

  function setAboutMenuOpen(isOpen) {
    aboutMenu.classList.toggle('show', isOpen);
    aboutBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  function setProductsMenuOpen(isOpen) {
    productsMenu.classList.toggle('show', isOpen);
    productsBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  function setMediaMenuOpen(isOpen) {
    mediaMenu.classList.toggle('show', isOpen);
    mediaBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  function closeHeaderDropdowns(exceptMenu) {
    if (exceptMenu !== aboutMenu) {
      setAboutMenuOpen(false);
    }
    if (exceptMenu !== productsMenu) {
      setProductsMenuOpen(false);
    }
    if (exceptMenu !== mediaMenu) {
      setMediaMenuOpen(false);
    }
  }

  function getEditableProduct(product) {
    const saved = localStorage.getItem(`product-copy-${product.key}`);
    if (!saved) return { ...product };
    try {
      return { ...product, ...JSON.parse(saved) };
    } catch (error) {
      return { ...product };
    }
  }

  function renderChipList(container, values) {
    container.innerHTML = '';
    values.forEach(value => {
      const chip = document.createElement('span');
      chip.className = 'product-chip';
      chip.textContent = value;
      container.appendChild(chip);
    });
  }

  function renderProductList(category, activeKey) {
    productListPanel.innerHTML = '';
    productCatalog[category].forEach(product => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `product-list-button${product.key === activeKey ? ' active' : ''}`;
      button.textContent = product.title;
      button.addEventListener('click', () => renderProduct(product.key, true));
      productListPanel.appendChild(button);
    });
  }

  function renderCategoryTabs(activeCategory) {
    productCategoryTabs.innerHTML = '';
    Object.keys(productCatalog).forEach(category => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `product-tab${category === activeCategory ? ' active' : ''}`;
      button.textContent = category;
      button.addEventListener('click', () => {
        renderProduct(productCatalog[category][0].key, true);
      });
      productCategoryTabs.appendChild(button);
    });
  }

  function updateQuoteResult() {
    const value = Math.max(Number(quoteValue.value) || 0, 0);
    const rate = Number(quotePackage.value) || 0.012;
    const estimate = Math.max(Math.round(value * rate), 0);
    quoteResult.textContent = `Estimated starting premium: ${estimate.toLocaleString()} ETB`;
  }

  function renderProduct(key, shouldScroll = false) {
    const baseProduct = productByKey[key] || Object.values(productByKey)[0];
    const product = getEditableProduct(baseProduct);
    const category = productCategoryByKey[baseProduct.key];
    currentProductKey = baseProduct.key;
    productCategoryLabel.textContent = category;
    productTitle.textContent = product.title;
    productTagline.textContent = product.tagline;
    productDescription.textContent = product.description;
    productHeroCard.style.backgroundImage = `url('${product.image}')`;
    renderChipList(productHighlights, product.highlights);
    renderChipList(productIdealFor, product.ideal_for);
    quoteMessage.value = `I am interested in ${product.title}. ${product.price_note}`;
    renderCategoryTabs(category);
    renderProductList(category, baseProduct.key);
    updateQuoteResult();
    document.querySelectorAll('[data-product-key]').forEach(link => {
      link.classList.toggle('active', link.dataset.productKey === baseProduct.key);
    });
    if (productEditMode) {
      [productTitle, productTagline, productDescription].forEach(el => {
        el.setAttribute('contenteditable', 'true');
      });
    }
    if (shouldScroll) {
      history.replaceState(null, '', `#product-${baseProduct.key}`);
      productPages.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function setProductEditMode(isEditing) {
    productEditMode = isEditing;
    productPageShell.classList.toggle('editable-on', isEditing);
    [productTitle, productTagline, productDescription].forEach(el => {
      el.setAttribute('contenteditable', isEditing ? 'true' : 'false');
    });
    toggleProductEdit.textContent = isEditing ? 'Finish Editing' : 'Edit Content';
  }

  function closeMobileMenu() {
    navbarContainer.classList.remove('mobile-open');
    menuToggle.setAttribute('aria-expanded', 'false');
  }

  function toggleMobileMenu(forceState) {
    const shouldOpen = typeof forceState === 'boolean'
      ? forceState
      : !navbarContainer.classList.contains('mobile-open');
    navbarContainer.classList.toggle('mobile-open', shouldOpen);
    menuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    if (!shouldOpen) {
      closeHeaderDropdowns();
    }
  }

  menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMobileMenu();
  });

  aboutBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (mobileNavQuery.matches && !navbarContainer.classList.contains('mobile-open')) {
      toggleMobileMenu(true);
    }
    setProductsMenuOpen(false);
    setMediaMenuOpen(false);
    setAboutMenuOpen(!aboutMenu.classList.contains('show'));
  });

  productsBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (mobileNavQuery.matches && !navbarContainer.classList.contains('mobile-open')) {
      toggleMobileMenu(true);
    }
    setAboutMenuOpen(false);
    setMediaMenuOpen(false);
    setProductsMenuOpen(!productsMenu.classList.contains('show'));
  });

  mediaBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (mobileNavQuery.matches && !navbarContainer.classList.contains('mobile-open')) {
      toggleMobileMenu(true);
    }
    setAboutMenuOpen(false);
    setProductsMenuOpen(false);
    setMediaMenuOpen(!mediaMenu.classList.contains('show'));
  });

  productsDropdown.addEventListener('mouseenter', () => {
    if (!mobileNavQuery.matches) {
      setAboutMenuOpen(false);
      setMediaMenuOpen(false);
      setProductsMenuOpen(true);
    }
  });

  productsDropdown.addEventListener('mouseleave', () => {
    if (!mobileNavQuery.matches) {
      setProductsMenuOpen(false);
    }
  });

  mediaDropdown.addEventListener('mouseenter', () => {
    if (!mobileNavQuery.matches) {
      setAboutMenuOpen(false);
      setProductsMenuOpen(false);
      setMediaMenuOpen(true);
    }
  });

  mediaDropdown.addEventListener('mouseleave', () => {
    if (!mobileNavQuery.matches) {
      setMediaMenuOpen(false);
    }
  });

  productsDropdown.addEventListener('focusin', () => {
    setAboutMenuOpen(false);
    setProductsMenuOpen(true);
  });

  document.querySelectorAll('[data-product-key]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      renderProduct(link.dataset.productKey, true);
      closeHeaderDropdowns();
      if (mobileNavQuery.matches) {
        closeMobileMenu();
      }
    });
  });

  document.querySelectorAll('[data-about-target]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const target = link.dataset.aboutTarget;
      const element = document.getElementById(target);
      if (element) {
        element.style.display = 'block';
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      closeHeaderDropdowns();
      if (mobileNavQuery.matches) {
        closeMobileMenu();
      }
    });
  });

  toggleProductEdit.addEventListener('click', () => {
    setProductEditMode(!productEditMode);
  });

  saveProductEdit.addEventListener('click', () => {
    if (!currentProductKey) return;
    localStorage.setItem(`product-copy-${currentProductKey}`, JSON.stringify({
      title: productTitle.textContent.trim(),
      tagline: productTagline.textContent.trim(),
      description: productDescription.textContent.trim()
    }));
    setProductEditMode(false);
  });

  resetProductEdit.addEventListener('click', () => {
    if (!currentProductKey) return;
    localStorage.removeItem(`product-copy-${currentProductKey}`);
    setProductEditMode(false);
    renderProduct(currentProductKey);
  });

  quoteValue.addEventListener('input', updateQuoteResult);
  quotePackage.addEventListener('change', updateQuoteResult);
  quoteSubmit.addEventListener('click', () => {
    const activeProduct = productByKey[currentProductKey];
    alert(`Request prepared for ${activeProduct.title}. Our advisor can follow up using the contact details entered.`);
  });

  const initialProductKey = location.hash.startsWith('#product-')
    ? location.hash.replace('#product-', '')
    : Object.values(productCatalog)[0][0].key;
  renderProduct(initialProductKey, location.hash.startsWith('#product-'));

  document.addEventListener('click', (e) => {
    if (!aboutBtn.contains(e.target) && !aboutMenu.contains(e.target)) {
      setAboutMenuOpen(false);
    }
    if (!productsBtn.contains(e.target) && !productsMenu.contains(e.target)) {
      setProductsMenuOpen(false);
    }
    if (mobileNavQuery.matches && !navbarContainer.contains(e.target)) {
      closeMobileMenu();
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeHeaderDropdowns();
      closeMobileMenu();
    }
  });
  mobileNavQuery.addEventListener('change', (e) => {
    if (!e.matches) {
      closeMobileMenu();
      closeHeaderDropdowns();
    }
  });
  mobileNavLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (mobileNavQuery.matches) {
        closeMobileMenu();
      }
    });
  });

  // 2. BANNER FULLSCREEN: slides + timegap (6s) + scroll icons left/right
  const slides = document.querySelectorAll('.banner-slide');
  const dotsContainer = document.getElementById('bannerDotsContainer');
  const captionDiv = document.getElementById('bannerCaption');
  const captionTitle = captionDiv.querySelector('h2');
  const captionDescription = captionDiv.querySelector('p');
  const pauseButton = document.getElementById('bannerPauseBtn');
  const hero = document.getElementById('heroFullscreen');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const captions = <?php echo json_encode(array_map(function ($slide) {
      return ['title' => $slide['title'] ?? '', 'desc' => $slide['description'] ?? ''];
  }, $blog_banner_slides), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  let currentIdx = 0;
  let intervalId;
  let userPaused = prefersReducedMotion.matches;
  let heroIsVisible = true;
  let pointerIsOverHero = false;
  let focusIsInHero = false;
  const duration = <?php echo (int) $blog_banner_duration * 1000; ?>;

  function buildDots() {
    dotsContainer.innerHTML = '';
    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.classList.add('dot');
      dot.setAttribute('aria-label', `Show slide ${i + 1}`);
      if (i === currentIdx) {
        dot.classList.add('active');
        dot.setAttribute('aria-current', 'true');
      }
      dot.addEventListener('click', () => goToSlide(i));
      dotsContainer.appendChild(dot);
    });
  }

  function canAutoAdvance() {
    return slides.length > 1
      && !userPaused
      && !pointerIsOverHero
      && !focusIsInHero
      && heroIsVisible
      && !document.hidden
      && !prefersReducedMotion.matches;
  }

  function updatePlayback() {
    if (intervalId) clearInterval(intervalId);
    intervalId = null;
    pauseButton.setAttribute('aria-pressed', String(userPaused));
    pauseButton.setAttribute('aria-label', userPaused ? 'Play slideshow' : 'Pause slideshow');
    pauseButton.textContent = userPaused ? '▶' : 'Ⅱ';
    slides.forEach((slide, index) => {
      if (slide instanceof HTMLVideoElement) {
        if (index === currentIdx && !userPaused && heroIsVisible && !document.hidden) {
          const playRequest = slide.play();
          if (playRequest && typeof playRequest.catch === 'function') {
            playRequest.catch(error => console.warn('Hero video could not start playback:', error));
          }
        } else {
          slide.pause();
        }
      }
    });
    if (canAutoAdvance()) {
      intervalId = setInterval(() => goToSlide((currentIdx + 1) % slides.length), duration);
    }
    pauseButton.disabled = slides.length < 2
      && !Array.from(slides).some(slide => slide instanceof HTMLVideoElement);
  }

  function goToSlide(index) {
    if (!slides.length || !captions.length) return;
    currentIdx = (index + slides.length) % slides.length;
    slides.forEach((s, i) => s.classList.toggle('active', i === currentIdx));
    dotsContainer.querySelectorAll('.dot').forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIdx);
      if (i === currentIdx) {
        dot.setAttribute('aria-current', 'true');
      } else {
        dot.removeAttribute('aria-current');
      }
    });
    const cap = captions[currentIdx];
    captionTitle.textContent = cap.title;
    captionDescription.textContent = cap.desc;
    captionDiv.classList.remove('caption-change');
    void captionDiv.offsetWidth;
    captionDiv.classList.add('caption-change');
    updatePlayback();
  }

  document.getElementById('scrollRightBtn').addEventListener('click', () => goToSlide(currentIdx + 1));
  document.getElementById('scrollLeftBtn').addEventListener('click', () => goToSlide(currentIdx - 1));
  pauseButton.addEventListener('click', () => {
    userPaused = !userPaused;
    updatePlayback();
  });
  hero.addEventListener('mouseenter', () => { pointerIsOverHero = true; updatePlayback(); });
  hero.addEventListener('mouseleave', () => { pointerIsOverHero = false; updatePlayback(); });
  hero.addEventListener('focusin', () => { focusIsInHero = true; updatePlayback(); });
  hero.addEventListener('focusout', event => {
    if (!hero.contains(event.relatedTarget)) {
      focusIsInHero = false;
      updatePlayback();
    }
  });
  document.addEventListener('visibilitychange', updatePlayback);
  if (typeof prefersReducedMotion.addEventListener === 'function') {
    prefersReducedMotion.addEventListener('change', updatePlayback);
  }
  if ('IntersectionObserver' in window) {
    const heroObserver = new IntersectionObserver(entries => {
      heroIsVisible = entries[0].isIntersecting;
      updatePlayback();
    });
    heroObserver.observe(hero);
  }
  buildDots();
  updatePlayback();

  if ('IntersectionObserver' in window) {
    document.body.classList.add('has-scroll-reveal');
    const revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.scroll-reveal').forEach(element => revealObserver.observe(element));
  }

  // 3. News slider left/right (image slide to left and right)
  const newsSlider = document.getElementById('newsSlider');
  const leftNews = document.getElementById('newsSlideLeft');
  const rightNews = document.getElementById('newsSlideRight');
  leftNews.addEventListener('click', () => { newsSlider.scrollBy({ left: -320, behavior: 'smooth' }); });
  rightNews.addEventListener('click', () => { newsSlider.scrollBy({ left: 320, behavior: 'smooth' }); });

  // 4. Additional Interactive: core pillar cards reveal (already animated by css), hover background gradients and interactive read.
  const pillarCards = document.querySelectorAll('.pillar-card');
  pillarCards.forEach(card => {
    card.addEventListener('click', () => {
      const pillarType = card.getAttribute('data-pillar');
      alert(`✨ Explore our ${pillarType.toUpperCase()}: we are dedicated to excellence and integrity.`);
    });
  });

  // 5. simulate dynamic dropdown content for Executive Management/Board performance but not modifying original structure — we add extra hover effects & gradient
  // Also apply hover background like php admin header (already button links best color)
  // Button links color: primary + accent hover; plus dynamic active state for menu

  // Make any link and button have elegant hover (global)
  const allLinks = document.querySelectorAll('a, .btn-soft, .dropdown-btn');
  allLinks.forEach(link => {
    link.addEventListener('mouseenter', (e) => {
      if (link.classList.contains('btn-soft')) link.style.transform = 'translateY(-2px)';
    });
    link.addEventListener('mouseleave', (e) => {
      if (link.classList.contains('btn-soft')) link.style.transform = '';
    });
  });

  // Additional Insurance news random interactive animation (timegap and dynamic)
  // Make images over reveals (time gap) for pillars (already appear staggered)
  // Goal / mission/vision images stay original style unchanged but interactive hover overflow
  console.log("PrimeShield dynamic insurance portal ready");

  // 6. Make header background white and button links best color (already white header, accent primary)
  // banner image overlay left bottom gradient already set.
  // Ensure full screen image before scroll up - done: hero banner wrapper margin top 20px, left/right 5px, height full viewport minus header.
  // also improve slider time gap reveal.

  // 7. for organization performance extra dynamic
  const performanceDiv = document.querySelector('.feature-card:last-child');
  if(performanceDiv) {
    performanceDiv.style.transition = "all 0.2s";
    performanceDiv.addEventListener('mouseover', () => performanceDiv.style.backgroundColor = "#fff6e5");
    performanceDiv.addEventListener('mouseout', () => performanceDiv.style.backgroundColor = "");
  }
  
  // make all dropdown menus fully functional without modifying others
  // ensure that about dropdown contains links
  const aboutLinks = aboutMenu.querySelectorAll('a');
  aboutLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = link.dataset.aboutTarget;
      if (targetId) {
        const element = document.getElementById(targetId);
        if (element) {
          element.style.display = 'block';
          element.scrollIntoView({ behavior: 'smooth', block: 'start' });
          history.replaceState(null, '', `#${targetId}`);
        }
        setAboutMenuOpen(false);
        if (mobileNavQuery.matches) {
          closeMobileMenu();
        }
        return;
      }
      alert(`📄 ${link.innerText} page — coming soon. Explore our company depth.`);
      setAboutMenuOpen(false);
    });
  });
  
  // add image slider timegap also to pillar hover but keep dynamic
  // insurance news scroll also has overflow image interactive
  // set interactive scroll icon to left and right
  const heroBanner = document.querySelector('.hero-banner-wrapper');
  if(heroBanner) heroBanner.style.cursor = 'grab';
  
  // 8. Gradient by text at bottom left of images (already done)
  // 9. Also make dynamic toggle for core pillars background
  const style = document.createElement('style');
  style.textContent = `
    .pillar-card {<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Insurance Website</title>
        <style>
            /* Reset and base styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: Arial, sans-serif;
            }
            
            body {
                overflow-x: hidden;
            }
            
            /* Header styles */
            header {
                margin-top: 10px;
                padding: 20px;
                background-color: #f8f9fa;
                text-align: center;
            }
            
            /* Full size image section */
            .full-size-img {
                width: 100%;
                margin: 20px 0;
            }
            
            .full-size-img img {
                width: 100%;
                height: auto;
                display: block;
            }
            
            /* Image with text side by side */
            .image<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Insurance Website</title>
                <style>
                    /* Reset and base styles */
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        font-family: Arial, sans-serif;
                    }
                    
                    body {
                        overflow-x: hidden;
                    }
                    
                    /* Header styles */
                    header {
                        margin-top: 10px;
                        padding: 20px;
                        background-color: #f8f9fa;
                        text-align: center;
                    }
                    
                    /* Full size image section */
                    .full-size-img {
                        width: 100%;
                        margin: 20px 0;
                    }
                    
                    .full-size-img img {
                        width: 100%;
                        height: auto;
                        display: block;
                    }
                    
                    /* Image with text side by side */
                    .image
    .pillar-card:hover .pillar-img {
      filter: brightness(1.02) contrast(1.02);
    }
    .banner-slide {
      transition: opacity 1.2s ease, transform 1.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }
    .btn-soft {
      background: var(--primary);
      color: white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      font-weight: bold;
    }
    .btn-soft:hover {
      background: cyan;
      color: var(--gray-800);
    }
    .dropdown-menu a {
      transition: color 0.2s ease, text-decoration-color 0.2s ease, text-shadow 0.2s ease;
      font-weight: bold;
    }
    .dropdown-menu a:hover {
      background: transparent;
      color: #7a5b00;
      text-decoration: underline;
      text-decoration-color: #ffd700;
      text-decoration-thickness: 2px;
      text-underline-offset: 0.24em;
      animation: goldUnderlineBlink 0.95s linear infinite;
    }
    .site-header {
      background: #ffffff;
    }
  `;
  document.head.appendChild(style);

  // Check if URL has #executiveManagement and show the section
  if (location.hash === '#executiveManagement') {
    const element = document.getElementById('executiveManagement');
    if (element) {
      element.style.display = 'block';
    }
  }
</script>
</body>
</html>
