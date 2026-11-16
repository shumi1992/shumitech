<?php
declare(strict_types=1);

$portfolio = [
    'name' => 'Your Name',
    'initials' => 'YN',
    'role' => '[your role]',
    'location' => '[your city]',
    'email' => 'you@example.com',
    'availability' => 'Available for select opportunities',
    'intro' => 'I turn thoughtful ideas into useful, accessible, and memorable digital experiences. Replace this introduction with a few lines about your work and what you care about.',
    'about' => [
        'I’m Your Name, a [role / specialty] based in [your city]. I enjoy working across design and technology to make complex things feel simple and intuitive.',
        'Over the past [number] years, I’ve collaborated with [teams, clients, or industries]. I believe the best work comes from listening closely, staying curious, and paying attention to the details that make a great experience.',
        'Outside work, you’ll find me [hobby or personal detail]. I’m always open to thoughtful conversations and new collaborations.',
    ],
];

$aboutValues = [
    'curiosity' => ['label' => 'Curiosity', 'description' => 'I ask questions, stay open to new perspectives, and look for the human need behind every brief.'],
    'craft' => ['label' => 'Craft', 'description' => 'I care about the details: clear words, thoughtful interactions, and work that feels as good as it functions.'],
    'care' => ['label' => 'Care', 'description' => 'I make room for collaboration and accessibility so more people can feel considered in the outcome.'],
    'learning' => ['label' => 'Always learning', 'description' => 'I treat each project as a chance to experiment, reflect, and make the next iteration better.'],
];

$projects = [
    [
        'category' => 'design',
        'category_label' => 'Product design',
        'title' => 'Project One — Digital Product',
        'summary' => 'Describe the user challenge, your approach, and the measurable outcome of this project.',
        'tags' => ['Research', 'UI/UX', '2025'],
        'contribution' => 'Replace this with your role, decisions, and the people you collaborated with.',
        'outcome' => 'Add evidence or results that show what changed because of this work.',
    ],
    [
        'category' => 'development',
        'category_label' => 'Development',
        'title' => 'Project Two — Web Platform',
        'summary' => 'Show how you built a reliable, responsive experience and the needs it helped address.',
        'tags' => ['Frontend', 'Accessibility', '2024'],
        'contribution' => 'Describe the parts you designed, built, tested, or maintained.',
        'outcome' => 'Explain how the platform improved an important user or business task.',
    ],
    [
        'category' => 'strategy',
        'category_label' => 'Strategy',
        'title' => 'Project Three — Service Journey',
        'summary' => 'Share how research and collaboration shaped a clearer, more effective service.',
        'tags' => ['Discovery', 'Mapping', '2024'],
        'contribution' => 'Describe the research and planning methods you contributed.',
        'outcome' => 'Highlight the service improvements or insights delivered.',
    ],
    [
        'category' => 'design',
        'category_label' => 'Product design',
        'title' => 'Project Four — Design System',
        'summary' => 'Explain how reusable components helped a team work faster and create consistency.',
        'tags' => ['Systems', 'Components', '2023'],
        'contribution' => 'Explain how you developed components, patterns, and team guidance.',
        'outcome' => 'Describe the improvements to quality, speed, or consistency.',
    ],
];

$skills = [
    ['HTML', 'Semantic structure · accessible markup'],
    ['CSS', 'Responsive layouts · animations · design systems'],
    ['JavaScript', 'Interactive interfaces · browser APIs'],
    ['PHP', 'Server-rendered pages · application logic'],
    ['MySQL', 'Relational data · queries · schema basics'],
    ['Git', 'Version control · collaboration · code history'],
];

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
    ['category' => 'about', 'question' => 'What do you do?', 'answer' => 'I work across technology and design to create useful digital experiences. Replace this sample with a short introduction to your focus.'],
    ['category' => 'experience', 'question' => 'What kind of experience do you have?', 'answer' => 'My experience includes the roles and projects listed in the Journey section. Add a concise summary of your background and areas of expertise.'],
    ['category' => 'skills', 'question' => 'What technologies do you work with?', 'answer' => 'My current toolkit includes HTML, CSS, JavaScript, PHP, MySQL, and Git. Update this list to reflect your skills and preferred tools.'],
    ['category' => 'working', 'question' => 'Are you open to new collaborations?', 'answer' => 'I’m available for select opportunities. Use the contact section to share a little about your project and what you have in mind.'],
];

$gallery = [
    ['category' => 'workspace', 'title' => 'A place to think', 'type' => 'Workspace', 'art' => 'workspace-desk', 'summary' => 'A calm, practical corner for focused work, sketches, and the occasional cup of coffee.', 'detail' => 'Workspace notes', 'outcome' => 'A glimpse into the small rituals and tools that make room for creative thinking.'],
    ['category' => 'workspace', 'title' => 'Notes in progress', 'type' => 'Workspace', 'art' => 'workspace-notes', 'summary' => 'Ideas start messy: quick notes, open questions, and rough directions on the wall.', 'detail' => 'Behind the scenes', 'outcome' => 'An evolving collection of thoughts that help turn a first question into a useful direction.'],
    ['category' => 'workspace', 'title' => 'Tools of the trade', 'type' => 'Workspace', 'art' => 'workspace-tools', 'summary' => 'A few well-loved tools for sketching, prototyping, testing, and making things real.', 'detail' => 'Everyday essentials', 'outcome' => 'The best tool is the one that helps the team learn and move forward.'],
    ['category' => 'awards', 'title' => 'Add an award or recognition', 'type' => 'Awards', 'art' => 'award-laurel', 'summary' => 'Showcase a prize, nomination, certification, or milestone that matters to you.', 'detail' => 'Recognition details', 'outcome' => 'Replace this sample with the awarding organization, date, and what the honor recognized.'],
    ['category' => 'awards', 'title' => 'Add a team achievement', 'type' => 'Awards', 'art' => 'award-star', 'summary' => 'Celebrate a shared milestone made possible by generous collaboration.', 'detail' => 'Team recognition', 'outcome' => 'Add context about the team, your contribution, and why this achievement mattered.'],
    ['category' => 'project', 'title' => 'A clearer digital journey', 'type' => 'Project', 'art' => 'project-mobile', 'summary' => 'A mobile-first experience designed to make an important everyday task easier to complete.', 'detail' => 'Product design · 2025', 'outcome' => 'A more direct journey, with clearer next steps and fewer points of friction.'],
    ['category' => 'project', 'title' => 'Making complex things simple', 'type' => 'Project', 'art' => 'project-dashboard', 'summary' => 'A focused dashboard concept that makes useful information easier to scan and act on.', 'detail' => 'Interface design · 2024', 'outcome' => 'A calmer information hierarchy that helps people find what matters faster.'],
    ['category' => 'project', 'title' => 'A system that scales', 'type' => 'Project', 'art' => 'project-system', 'summary' => 'A set of reusable visual patterns created to help a growing product stay consistent.', 'detail' => 'Design system · 2024', 'outcome' => 'Shared foundations that make future screens easier to build and maintain.'],
    ['category' => 'project', 'title' => 'A more welcoming web', 'type' => 'Project', 'art' => 'project-web', 'summary' => 'A responsive web concept balancing a warm visual identity with accessible interaction.', 'detail' => 'Web experience · 2023', 'outcome' => 'A cohesive experience designed to work gracefully across screen sizes.'],
];

$articles = [
    ['date' => 'FIELD NOTES · 5 MIN READ', 'title' => 'Designing for the questions people actually ask', 'summary' => 'A practical reminder to start with real user needs, not assumptions.', 'body' => 'Good product work begins by listening. Ask people what they are trying to do, what gets in their way, and what a successful outcome looks like. Those answers create a stronger foundation than adding features because they seem familiar.'],
    ['date' => 'PROCESS · 4 MIN READ', 'title' => 'A small design system can make a big difference', 'summary' => 'How a few clear patterns can improve consistency and team momentum.', 'body' => 'A useful design system does not need to be large. Start with shared typography, spacing, colors, and a handful of repeated components. Document why each pattern exists and make it easy for teammates to suggest improvements.'],
    ['date' => 'LEARNING · 3 MIN READ', 'title' => 'What I learned from building in the open', 'summary' => 'Sharing work early can make collaboration clearer and more generous.', 'body' => 'Showing unfinished work invites better questions sooner. Explain what you are testing, make the current limitations visible, and treat feedback as material for the next iteration rather than a verdict on the first one.'],
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#11131b">
  <meta name="description" content="A personal portfolio template. Replace the sample name, biography, projects, and contact details with your own.">
  <title><?= e($portfolio['name']) ?> — Portfolio</title>
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
        "name": "Aashish Jha",
        "url": "https://aashish-jha.com.np",
        "image": "https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg",
        "jobTitle": "Web Developer",
        "description": "Portfolio of Aashish Jha, a web developer from Nepal building modern PHP & MySQL applications. Explore projects, blog, services and more.",
        "email": "mailto:info@aashish-jha.com.np",
        "address": {
          "@type": "PostalAddress",
          "addressLocality": "Kathmandu",
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
        "name": "Aashish Jha — Portfolio",
        "publisher": {
          "@id": "https://aashish-jha.com.np/#person"
        }
      }
    ]
  }
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
      color-scheme:light; --page:#f8fafc; --surface:#fff; --raised:#eef2f7;
      --text:#0f172a; --muted:#64748b; --line:rgba(15,23,42,.08);
      --accent:#16a34a; --gold:#16a34a; --accent-ink:#fff; --purple:#15803d;
      --shadow:0 24px 80px rgba(15,23,42,.1); --header:rgba(248,250,252,.86);
    }
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;background:var(--page);color:var(--text);line-height:1.6;transition:background .25s,color .25s}
    a{color:inherit} button{font:inherit}
    button:focus-visible,a:focus-visible{outline:3px solid var(--accent);outline-offset:4px}
    .skip{position:fixed;z-index:100;top:10px;left:10px;padding:10px 14px;transform:translateY(-160%);border-radius:8px;color:var(--accent-ink);background:var(--accent)}
    .skip:focus{transform:translateY(0)}
    .progress{position:fixed;z-index:70;inset:0 0 auto;height:3px;transform:scaleX(0);transform-origin:left;background:var(--accent)}
    .header{position:sticky;z-index:60;top:0;border-bottom:1px solid var(--line);background:var(--header);backdrop-filter:blur(18px)}
    .nav{width:min(1160px,calc(100% - 40px));min-height:74px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:20px}
    .brand{display:flex;align-items:center;gap:11px;text-decoration:none;font-weight:750;letter-spacing:-.03em}
    .brand-mark{width:37px;height:37px;display:grid;place-items:center;border-radius:12px;color:var(--accent-ink);background:var(--accent);font-size:13px}
    .nav-right,.nav-links{display:flex;align-items:center}
    .nav-right{gap:16px}.nav-links{gap:clamp(12px,1.5vw,22px)}
    .nav-links a{position:relative;padding:8px 0;color:var(--muted);text-decoration:none;font-size:14px;transition:color .25s,transform .25s}
    .nav-links a:after{position:absolute;right:50%;bottom:2px;left:50%;height:2px;content:"";border-radius:99px;background:linear-gradient(90deg,color-mix(in srgb,var(--gold) 55%,#fff),var(--gold));box-shadow:0 0 10px color-mix(in srgb,var(--gold) 55%,transparent);transition:left .28s cubic-bezier(.2,.8,.2,1),right .28s cubic-bezier(.2,.8,.2,1)}
    .nav-links a:hover,.nav-links a:focus-visible,.nav-links a[aria-current="location"]{color:var(--gold)}
    .nav-links a:hover{transform:translateY(-1px)}
    .nav-links a:hover:after,.nav-links a:focus-visible:after,.nav-links a[aria-current="location"]:after{right:0;left:0}
    .nav-dropdown{position:relative}
    .nav-dropdown>summary{display:flex;align-items:center;gap:7px;padding:8px 0;color:var(--muted);font-size:14px;cursor:pointer;list-style:none;transition:color .25s}
    .nav-dropdown>summary::-webkit-details-marker{display:none}
    .nav-dropdown>summary:hover,.nav-dropdown[open]>summary{color:var(--gold)}
    .nav-dropdown>summary:focus-visible{outline:3px solid var(--accent);outline-offset:4px}
    .nav-dropdown-chevron{font-size:10px;transition:transform .2s}
    .nav-dropdown[open] .nav-dropdown-chevron{transform:rotate(180deg)}
    .nav-dropdown-menu{position:absolute;z-index:90;top:calc(100% + 10px);left:-14px;width:max-content;min-width:190px;max-width:min(320px,calc(100vw - 30px));display:grid;gap:3px;padding:8px;border:1px solid var(--line);border-radius:14px;background:var(--surface);box-shadow:var(--shadow);animation:dropdown-in .16s ease-out}
    .nav-dropdown-menu a{display:block;padding:9px 11px;border-radius:8px;color:var(--muted);font-size:13px;text-decoration:none;white-space:normal}
    .nav-dropdown-menu a:after{display:none}
    .nav-dropdown-menu a:hover,.nav-dropdown-menu a:focus-visible{transform:none;color:var(--text);background:var(--raised)}
    @keyframes dropdown-in{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
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
    .hero{position:relative;min-height:650px;display:grid;align-items:center;overflow:hidden}
    .hero:before,.hero:after{position:absolute;z-index:-1;content:"";pointer-events:none}
    .hero:before{width:min(55vw,650px);height:min(55vw,650px);top:-240px;right:-120px;border-radius:50%;background:radial-gradient(circle,rgba(168,156,255,.22),transparent 68%)}
    .hero:after{width:420px;height:420px;bottom:-260px;left:-180px;border-radius:50%;background:radial-gradient(circle,rgba(196,243,107,.14),transparent 70%)}
    .hero-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(260px,.7fr);align-items:center;gap:70px;padding-block:86px}
    .eyebrow{display:inline-flex;align-items:center;gap:9px;color:var(--accent);font-size:12px;font-weight:750;letter-spacing:.14em;text-transform:uppercase}
    .status{width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 5px color-mix(in srgb,var(--accent) 15%,transparent);animation:pulse 2s infinite}
    h1{max-width:760px;margin:22px 0 18px;font-size:clamp(46px,7vw,82px);line-height:.99;letter-spacing:-.065em}
    .accent{color:var(--accent)}.typed{display:inline-block;min-height:1.12em;color:var(--purple)}
    .caret{display:inline-block;width:2px;height:.85em;margin-left:4px;vertical-align:-.04em;background:var(--purple);animation:blink 1s steps(2,start) infinite}
    .hero-copy{max-width:590px;color:var(--muted);font-size:clamp(16px,2vw,19px)}
    .hero-actions{display:flex;flex-wrap:wrap;gap:13px;margin-top:30px}
    .button{min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:0 20px;border:1px solid transparent;border-radius:999px;color:var(--accent-ink);background:var(--accent);font-size:14px;font-weight:750;text-decoration:none;cursor:pointer;transition:transform .2s,filter .2s}
    .button:hover{transform:translateY(-2px);filter:brightness(1.05)}
    .button.ghost{border-color:var(--line);color:var(--text);background:var(--surface)}
    .hero-note{display:flex;align-items:center;gap:11px;margin-top:36px;color:var(--muted);font-size:13px}
    .avatar-stack{display:flex;padding-left:8px}.avatar-stack span{width:26px;height:26px;display:grid;place-items:center;margin-left:-8px;border:2px solid var(--page);border-radius:50%;color:#18210a;background:var(--purple);font-size:8px;font-weight:800}
    .avatar-stack span:nth-child(2){background:#f6b982}.avatar-stack span:nth-child(3){background:#8ed3c2}
    .portrait{position:relative;width:min(100%,330px);justify-self:center;aspect-ratio:.82}
    .portrait-shape{position:absolute;inset:8% 2% 2%;overflow:hidden;border:1px solid var(--line);border-radius:48% 48% 28px 28px;background:linear-gradient(150deg,#2c3042,#1b1e2b 62%,#292447);box-shadow:var(--shadow)}
    .portrait-shape:before{position:absolute;content:"";width:170px;height:170px;top:14%;left:50%;transform:translateX(-50%);border:1px solid rgba(255,255,255,.18);border-radius:50%;background:radial-gradient(circle at 35% 30%,#f1c8a9,#b37b62 70%);box-shadow:inset -15px -15px 25px rgba(42,22,18,.18)}
    .portrait-shape:after{position:absolute;content:"";width:290px;height:260px;left:50%;bottom:-22%;transform:translateX(-50%);border-radius:48% 48% 0 0;background:linear-gradient(135deg,#a89cff,#5949a9)}
    .initials{position:absolute;z-index:2;top:42%;left:50%;transform:translate(-50%,-50%);color:#242036;font-size:38px;font-weight:850;letter-spacing:-.08em}
    .portrait-label{position:absolute;z-index:3;right:-20px;bottom:13%;padding:13px 16px;border:1px solid var(--line);border-radius:14px;color:var(--text);background:var(--surface);box-shadow:var(--shadow);font-size:12px}
    .portrait-label strong{display:block;font-size:15px}
    .section{padding-block:82px;scroll-margin-top:82px}
    .section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:30px}
    .kicker{color:var(--accent);font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
    h2{margin:8px 0 0;font-size:clamp(30px,4.6vw,48px);line-height:1.08;letter-spacing:-.045em}
    .section-head>p{max-width:450px;margin:0;color:var(--muted)}
    .about-section{padding-top:0}
    .about-intro{min-height:min(48vh,430px);position:relative;display:grid;place-items:center;overflow:hidden;padding:70px 20px;background:radial-gradient(ellipse at 50% 42%,rgba(34,197,94,.12),transparent 42%),linear-gradient(180deg,var(--page),var(--surface))}
    .about-intro:before,.about-intro:after{position:absolute;content:"";border:1px solid var(--line);border-radius:50%;pointer-events:none}
    .about-intro:before{width:min(72vw,650px);aspect-ratio:1;opacity:.5}
    .about-intro:after{width:min(54vw,480px);aspect-ratio:1;opacity:.35}
    .about-intro-inner{position:relative;z-index:1;max-width:760px;text-align:center}
    .about-intro .kicker{display:inline-flex;align-items:center;gap:9px}
    .about-intro h2{margin:18px 0;font-size:clamp(48px,9vw,100px);letter-spacing:-.075em}
    .about-me-button{display:inline-flex;align-items:center;gap:12px;padding:13px 20px;border:1px solid color-mix(in srgb,var(--accent) 45%,var(--line));border-radius:999px;color:var(--text);background:color-mix(in srgb,var(--surface) 78%,transparent);font-size:14px;font-weight:750;text-decoration:none;box-shadow:0 12px 35px rgba(0,0,0,.16);transition:transform .2s,border-color .2s,color .2s}
    .about-me-button:hover{transform:translateY(-2px);border-color:var(--accent);color:var(--accent)}
    .about-me-button .about-down{width:30px;height:30px;margin:0;border:0;background:var(--accent);color:var(--accent-ink);font-size:13px;animation:none}
    .about-me-button:hover .about-down{transform:translateY(2px);border-color:var(--accent)}
    .about-down:hover{transform:translateY(4px);border-color:var(--accent);color:var(--accent)}
    .about-body{padding-top:76px;scroll-margin-top:94px}
    .about-heading{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:26px}
    .about-heading h2{max-width:620px;margin-top:8px;font-size:clamp(36px,5vw,58px)}
    .about-layout{display:grid;grid-template-columns:minmax(250px,.78fr) minmax(0,1.22fr);align-items:start;gap:clamp(24px,5vw,58px)}
    .about-photo-wrap{position:relative;max-width:390px}
    .about-photo{width:100%;aspect-ratio:4/5;display:block;object-fit:cover;object-position:center;border:1px solid var(--line);border-radius:20px;background:var(--raised);box-shadow:var(--shadow)}
    .about-statement{min-width:0}
    .about-statement h3{margin:0;font-size:clamp(24px,3vw,34px);line-height:1.2}
    .about-lede{max-width:650px;margin:12px 0 0;color:var(--muted);font-size:16px;line-height:1.8}
    .about-lede p{margin:0}.about-lede p+p{margin-top:13px}
    .about-values{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:26px}
    .about-value{min-height:145px;display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:18px;border:1px solid var(--line);border-radius:17px;color:var(--text);background:var(--surface);text-align:left;cursor:pointer;transition:transform .2s,border-color .2s,background .2s}
    .about-value:hover{transform:translateY(-3px);border-color:var(--purple)}
    .about-value[aria-pressed="true"]{border-color:var(--accent);background:color-mix(in srgb,var(--accent) 9%,var(--surface))}
    .about-value strong{font-size:14px}
    .about-value span{color:var(--muted);font-size:13px;line-height:1.6}
    .about-footer{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:28px;padding:18px 22px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(110deg,color-mix(in srgb,var(--accent) 10%,var(--surface)),var(--surface) 60%)}
    .about-footer p{margin:0;color:var(--muted);font-size:13px}
    .about-links{display:flex;flex-wrap:wrap;gap:10px}
    .about-link{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid var(--line);border-radius:999px;color:var(--text);background:var(--surface);font-size:12px;font-weight:700;text-decoration:none}
    .about-link:hover{border-color:var(--accent);color:var(--accent)}
    @keyframes about-bob{50%{transform:translateY(7px)}}
    .filters{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:20px}
    .filter{min-height:38px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:transparent;cursor:pointer;font-size:13px}
    .filter:hover,.filter.active{border-color:var(--accent);color:var(--accent-ink);background:var(--accent)}
    .projects{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .project{overflow:hidden;border:1px solid var(--line);border-radius:22px;background:var(--surface);transition:transform .25s,border-color .25s,box-shadow .25s}
    .project:hover{transform:translateY(-5px);border-color:color-mix(in srgb,var(--accent) 55%,var(--line));box-shadow:var(--shadow)}
    .project[hidden]{display:none}
    .project-art{min-height:210px;display:grid;place-items:center;overflow:hidden;background:linear-gradient(135deg,#30264f,#705ec0)}
    .project:nth-child(2) .project-art{background:linear-gradient(135deg,#163d48,#56a78e)}
    .project:nth-child(3) .project-art{background:linear-gradient(135deg,#493522,#d18b53)}
    .project:nth-child(4) .project-art{background:linear-gradient(135deg,#283550,#607cb8)}
    .mock-window{width:72%;min-height:132px;padding:14px;transform:rotate(-4deg);border:1px solid rgba(255,255,255,.38);border-radius:13px;background:rgba(255,255,255,.88);box-shadow:0 16px 35px rgba(0,0,0,.2);transition:transform .35s}
    .project:hover .mock-window{transform:rotate(0) scale(1.04)}
    .mock-bar{width:34%;height:8px;margin-bottom:10px;border-radius:99px;background:#a89cff}
    .mock-line{height:6px;margin:7px 0;border-radius:99px;background:#d9dce6}.mock-line.short{width:60%}
    .mock-blocks{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:14px}.mock-blocks span{height:34px;border-radius:6px;background:#e8e7f2}
    .project-body{padding:19px 21px 21px}
    .project-meta{display:flex;justify-content:space-between;gap:12px;color:var(--accent);font-size:11px;font-weight:750;letter-spacing:.12em;text-transform:uppercase}
    .project h3{margin:9px 0 7px;font-size:20px;letter-spacing:-.025em}
    .project p{min-height:48px;margin:0;color:var(--muted);font-size:14px}
    .tags,.dialog-tags{display:flex;flex-wrap:wrap;gap:7px;margin-top:15px}
    .tags span,.dialog-tags span{padding:5px 9px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:11px}
    .project-open{margin-top:15px;padding:0;border:0;color:var(--accent);background:transparent;font-size:13px;font-weight:750;cursor:pointer}
    .project-open:hover{text-decoration:underline;text-underline-offset:4px}
    .gallery-section{padding-top:0}
    .gallery-intro{min-height:min(67vh,600px);position:relative;display:grid;place-items:center;overflow:hidden;padding:70px 20px;background:radial-gradient(ellipse at 50% 42%,rgba(168,156,255,.14),transparent 42%),linear-gradient(180deg,var(--page),var(--surface))}
    .gallery-intro:before,.gallery-intro:after{position:absolute;content:"";border:1px solid var(--line);border-radius:50%;pointer-events:none}
    .gallery-intro:before{width:min(72vw,650px);aspect-ratio:1;opacity:.5}
    .gallery-intro:after{width:min(54vw,480px);aspect-ratio:1;opacity:.35}
    .gallery-intro-inner{position:relative;z-index:1;max-width:740px;text-align:center}
    .gallery-intro .kicker{display:inline-flex;align-items:center;gap:9px}
    .gallery-intro h2{margin:18px 0;font-size:clamp(48px,9vw,100px);letter-spacing:-.075em}
    .gallery-intro p{max-width:530px;margin:0 auto;color:var(--muted);font-size:clamp(15px,2vw,18px)}
    .gallery-down{width:54px;height:54px;display:grid;place-items:center;margin:32px auto 0;border:1px solid var(--line);border-radius:50%;color:var(--text);background:var(--surface);font-size:22px;text-decoration:none;animation:gallery-bob 1.8s ease-in-out infinite;transition:color .2s,border-color .2s,transform .2s}
    .gallery-down:hover{transform:translateY(4px);border-color:var(--accent);color:var(--accent)}
    .gallery-collection{padding-top:76px}
    .gallery-collection-head{text-align:center;margin-bottom:26px}
    .gallery-collection-head h3{margin:8px 0 0;font-size:clamp(25px,4vw,36px);letter-spacing:-.04em}
    .gallery-filters{position:sticky;z-index:10;top:82px;display:flex;justify-content:center;flex-wrap:wrap;gap:7px;width:max-content;max-width:100%;margin:0 auto 30px;padding:7px;border:1px solid var(--line);border-radius:999px;background:color-mix(in srgb,var(--surface) 88%,transparent);backdrop-filter:blur(18px)}
    .gallery-filter{min-height:39px;padding:0 17px;border:0;border-radius:999px;color:var(--muted);background:transparent;font-size:13px;font-weight:700;cursor:pointer;transition:color .2s,background .2s,transform .2s}
    .gallery-filter:hover{color:var(--text);transform:translateY(-1px)}
    .gallery-filter[aria-pressed="true"]{color:var(--accent-ink);background:var(--accent)}
    .gallery-count{min-height:22px;margin:-15px 0 18px;color:var(--muted);font-size:12px;text-align:center}
    .gallery-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
    .gallery-card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:19px;background:var(--surface);transition:transform .3s,border-color .3s,box-shadow .3s}
    .gallery-card.is-entering{animation:gallery-in .42s both}
    .gallery-card[hidden]{display:none}
    .gallery-card:hover{transform:translateY(-6px);border-color:color-mix(in srgb,var(--accent) 45%,var(--line));box-shadow:var(--shadow)}
    .gallery-open{width:100%;display:block;padding:0;border:0;color:inherit;background:transparent;text-align:left;cursor:pointer}
    .gallery-art{height:228px;position:relative;display:grid;place-items:center;overflow:hidden;background:linear-gradient(145deg,#30264f,#705ec0)}
    .gallery-art:before,.gallery-art:after{position:absolute;content:"";border-radius:50%;pointer-events:none}
    .gallery-art:before{width:250px;height:250px;top:-150px;right:-80px;background:rgba(255,255,255,.1)}
    .gallery-art:after{width:200px;height:200px;bottom:-135px;left:-75px;background:rgba(255,255,255,.1)}
    .gallery-art.workspace-desk{background:linear-gradient(145deg,#754f3d,#d99467 60%,#f1cb91)}
    .gallery-art.workspace-notes{background:linear-gradient(145deg,#4d6574,#a6c2b1 58%,#e8dcaf)}
    .gallery-art.workspace-tools{background:linear-gradient(145deg,#3b4754,#72868d 58%,#c4b18e)}
    .gallery-art.award-laurel{background:linear-gradient(145deg,#342850,#836bb4 55%,#d8a873)}
    .gallery-art.award-star{background:linear-gradient(145deg,#24484c,#5da08e 58%,#cad39c)}
    .gallery-art.project-mobile{background:linear-gradient(145deg,#364467,#6d8bc5 58%,#b5c8ef)}
    .gallery-art.project-dashboard{background:linear-gradient(145deg,#24565a,#55a898 58%,#b8d5be)}
    .gallery-art.project-system{background:linear-gradient(145deg,#58436f,#a082b6 58%,#e3b2a1)}
    .gallery-art.project-web{background:linear-gradient(145deg,#744b49,#cb8271 58%,#edc69c)}
    .gallery-art-inner{width:min(74%,290px);height:68%;position:relative;z-index:1;display:grid;place-items:center}
    .art-window{width:100%;padding:15px;border:1px solid rgba(255,255,255,.55);border-radius:13px;background:rgba(255,255,255,.88);box-shadow:0 18px 45px rgba(17,19,27,.2);transform:rotate(-3deg);transition:transform .35s}
    .gallery-card:hover .art-window{transform:rotate(0) scale(1.04)}
    .art-window-bar{height:7px;width:34%;margin-bottom:13px;border-radius:9px;background:#8875c6}
    .art-lines{display:grid;gap:7px}
    .art-lines span{height:6px;border-radius:9px;background:#d9dce6}
    .art-lines span:nth-child(2){width:72%}.art-lines span:nth-child(3){width:52%}
    .art-blocks{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:13px}
    .art-blocks span{height:30px;border-radius:6px;background:#e5e5ef}
    .workspace-desk .art-window{width:82%;transform:rotate(-5deg);background:#f1ddc2}
    .workspace-desk .art-window-bar{width:100%;height:28px;margin:0 0 12px;background:linear-gradient(90deg,#d9aa76 0 18%,#f2e7d4 18%)}
    .workspace-notes .art-window{width:72%;transform:rotate(5deg);background:#fff9e9}
    .workspace-notes .art-window-bar{background:#db8e78}
    .workspace-tools .art-blocks span{height:46px;border-radius:50%;background:linear-gradient(145deg,#d7e6dc,#729c8b)}
    .art-award{width:126px;height:126px;position:relative;display:grid;place-items:center;border:1px solid rgba(255,255,255,.75);border-radius:50%;color:#fff4d8;background:radial-gradient(circle,#fff4d833 0 46%,transparent 47%),rgba(255,255,255,.12);box-shadow:0 12px 35px rgba(25,18,40,.22);transition:transform .35s}
    .gallery-card:hover .art-award{transform:scale(1.08) rotate(-5deg)}
    .art-award:before,.art-award:after{position:absolute;top:104px;width:29px;height:63px;content:"";background:linear-gradient(160deg,#eacb8c,#b87b62);clip-path:polygon(0 0,100% 0,80% 100%,50% 78%,20% 100%)}
    .art-award:before{left:32px;transform:rotate(12deg)}.art-award:after{right:32px;transform:rotate(-12deg)}
    .art-award span{font-size:45px;text-shadow:0 3px 12px rgba(0,0,0,.2)}
    .gallery-copy{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 17px}
    .gallery-copy small{display:block;color:var(--accent);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .gallery-copy strong{display:block;margin-top:4px;font-size:15px;line-height:1.35}
    .gallery-arrow{flex:0 0 auto;color:var(--muted);font-size:18px;transition:transform .2s,color .2s}
    .gallery-open:hover .gallery-arrow{transform:translate(2px,-2px);color:var(--accent)}
    @keyframes gallery-bob{50%{transform:translateY(7px)}}
    @keyframes gallery-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .article-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
    .article-card{padding:22px;border:1px solid var(--line);border-radius:18px;background:var(--surface);transition:transform .2s,border-color .2s}
    .article-card:hover{transform:translateY(-4px);border-color:var(--purple)}
    .article-date{color:var(--accent);font-size:10px;font-weight:800;letter-spacing:.11em}
    .article-card h3{margin:12px 0 8px;font-size:18px;line-height:1.3}
    .article-card>p{margin:0;color:var(--muted);font-size:13px}
    .article-card details{margin-top:15px}
    .article-card summary{color:var(--purple);font-size:13px;font-weight:750;cursor:pointer}
    .article-card details p{margin:10px 0 0;color:var(--muted);font-size:13px}
    .skills{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .skill{padding:17px;border:1px solid var(--line);border-radius:15px;color:var(--muted);background:var(--surface);font-size:13px}
    .skill strong{display:block;margin-bottom:4px;color:var(--text);font-size:15px}
    .timeline{display:grid;gap:12px}
    .timeline-item{display:grid;grid-template-columns:140px 1fr;gap:24px;padding:22px 24px;border:1px solid var(--line);border-radius:17px;background:var(--surface)}
    .timeline-date{color:var(--accent);font-size:12px;font-weight:750}
    .timeline-item h3{margin:0 0 5px;font-size:17px}.timeline-item p{margin:0;color:var(--muted);font-size:14px}
    .journey-group+.journey-group{margin-top:42px}
    .journey-subhead{margin-bottom:16px}
    .journey-subhead h3{margin:7px 0 0;font-size:clamp(22px,3vw,30px)}
    .education-status{display:inline-flex;margin-bottom:8px;padding:5px 10px;border:1px solid var(--line);border-radius:999px;color:var(--accent);background:var(--raised);font-size:11px;font-weight:750}
    .faq-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
    .faq-filter{min-height:38px;padding:0 15px;border:1px solid var(--line);border-radius:999px;color:var(--muted);background:var(--surface);cursor:pointer;transition:color .2s,border-color .2s,background .2s}
    .faq-filter:hover,.faq-filter.active{border-color:var(--accent);color:var(--text);background:color-mix(in srgb,var(--accent) 12%,var(--surface))}
    .faq-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .faq-card{padding:20px;border:1px solid var(--line);border-radius:17px;background:var(--surface);transition:transform .2s,border-color .2s}
    .faq-card:hover{transform:translateY(-3px);border-color:var(--purple)}
    .faq-card[hidden]{display:none}
    .faq-category{color:var(--accent);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
    .faq-card h3{margin:9px 0 7px;font-size:17px}
    .faq-card p{margin:0;color:var(--muted);font-size:13px}
    .contact{display:grid;grid-template-columns:1fr auto;align-items:center;gap:25px;padding:clamp(26px,5vw,46px);border:1px solid var(--line);border-radius:26px;background:radial-gradient(circle at 100% 0,rgba(168,156,255,.18),transparent 40%),var(--surface)}
    .contact p{max-width:560px;margin:12px 0 0;color:var(--muted)}
    .contact-actions{display:flex;flex-wrap:wrap;gap:10px}
    .footer{display:flex;justify-content:space-between;gap:20px;padding:26px 0 38px;border-top:1px solid var(--line);color:var(--muted);font-size:12px}
    .footer-links{display:flex;gap:18px}.footer-links a{color:var(--muted);text-decoration:none}.footer-links a:hover{color:var(--text)}
    .dialog{width:min(620px,calc(100% - 28px));max-height:min(82vh,760px);padding:0;overflow:auto;border:1px solid var(--line);border-radius:24px;color:var(--text);background:var(--surface);box-shadow:var(--shadow)}
    .dialog::backdrop{background:rgba(5,7,13,.72);backdrop-filter:blur(7px)}
    .dialog-inner{padding:clamp(24px,5vw,38px)}.dialog-top{display:flex;justify-content:space-between;gap:16px}
    .dialog-close{width:40px;height:40px;flex:0 0 auto;border:1px solid var(--line);border-radius:50%;color:var(--text);background:var(--raised);cursor:pointer}
    .dialog-category{color:var(--accent);font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
    .dialog h2{margin:8px 0 16px;font-size:clamp(27px,5vw,40px)}.dialog-copy{color:var(--muted)}.dialog-copy p+p{margin-top:14px}
    .toast{position:fixed;z-index:80;right:20px;bottom:20px;max-width:calc(100% - 40px);padding:13px 17px;transform:translateY(20px);border:1px solid var(--line);border-radius:12px;opacity:0;color:var(--text);background:var(--surface);box-shadow:var(--shadow);pointer-events:none;transition:opacity .2s,transform .2s}
    .toast.visible{transform:translateY(0);opacity:1}
    .top-button{position:fixed;z-index:40;right:20px;bottom:20px;width:44px;height:44px;display:grid;place-items:center;transform:translateY(12px);border:1px solid var(--line);border-radius:50%;opacity:0;color:var(--text);background:var(--surface);box-shadow:var(--shadow);text-decoration:none;pointer-events:none;transition:opacity .2s,transform .2s,bottom .2s}
    .top-button.visible{bottom:78px;transform:translateY(0);opacity:1;pointer-events:auto}
    .reveal{opacity:1;transform:none}.js-reveal .reveal{opacity:0;transform:translateY(22px);transition:opacity .65s ease,transform .65s cubic-bezier(.2,.8,.2,1)}
    .js-reveal .reveal.visible{opacity:1;transform:translateY(0)}
    @keyframes pulse{50%{box-shadow:0 0 0 9px color-mix(in srgb,var(--accent) 0%,transparent)}}
    @keyframes blink{50%{opacity:0}}
    @media(max-width:800px){.hero{min-height:auto}.hero-grid{grid-template-columns:1fr;gap:38px;padding-block:66px 48px}.portrait{width:min(75%,300px)}.skills{grid-template-columns:repeat(2,minmax(0,1fr))}.section{padding-block:62px}.about-section{padding-top:0}.about-intro{min-height:55vh}.about-body{padding-top:56px}.section-head{align-items:flex-start;flex-direction:column}.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:950px){.nav{gap:12px}.nav-right{gap:10px}.nav-links{gap:12px}.nav-links a{font-size:13px}.search-input{width:105px}}
    @media(max-width:760px){.nav{width:calc(100% - 28px);min-height:66px}.menu-button{display:grid}.nav-right{gap:9px}.nav-links{position:absolute;top:calc(100% + 1px);right:0;left:0;max-height:calc(100vh - 72px);overflow-y:auto;overscroll-behavior:contain;display:none;align-items:stretch;gap:0;padding:8px 18px 16px;border-bottom:1px solid var(--line);background:var(--page)}.nav-links.open{display:flex;flex-direction:column}.nav-links>a{padding:12px 8px;border-bottom:1px solid var(--line)}.nav-dropdown{border-bottom:1px solid var(--line)}.nav-dropdown>summary{min-height:44px;padding:10px 8px}.nav-dropdown-menu{position:static;width:100%;max-width:none;gap:0;padding:4px 8px 10px;border:0;border-radius:0;background:transparent;box-shadow:none;animation:none}.nav-dropdown-menu a{padding:10px 14px}.search-wrap{margin-top:12px}.search-form{height:44px;border-radius:10px}.search-input{width:100%;font-size:14px}.search-results{position:static;width:100%;margin-top:7px}.container{width:calc(100% - 30px)}.hero-grid{padding-top:48px}.hero-note{align-items:flex-start;flex-direction:column}.projects{grid-template-columns:1fr}.project-art{min-height:190px}.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.gallery-intro{min-height:55vh}.gallery-filters{top:68px}.gallery-collection{padding-top:56px}.article-grid,.faq-grid{grid-template-columns:1fr}.timeline-item{grid-template-columns:1fr;gap:8px}.contact{grid-template-columns:1fr}.contact-actions{align-items:stretch;flex-direction:column}.footer{align-items:flex-start;flex-direction:column}}
    @media(max-width:480px){.gallery-grid{grid-template-columns:1fr}.gallery-art{height:245px}.gallery-filters{gap:3px;padding:5px}.gallery-filter{min-height:36px;padding:0 11px;font-size:12px}}
    @media(max-width:560px){.about-layout{grid-template-columns:1fr}.about-photo-wrap{width:min(100%,360px);margin-inline:auto}.about-values{grid-template-columns:1fr}.about-footer{align-items:flex-start;flex-direction:column}.about-links{width:100%}}
    @media(max-width:400px){.skills{grid-template-columns:1fr}.hero-actions .button{width:100%}h1{font-size:44px}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}.js-reveal .reveal{opacity:1;transform:none}.nav-links a:hover{transform:none}}
  </style>
</head>
<body>
  <a class="skip" href="#main">Skip to content</a>
  <div class="progress" id="progress" aria-hidden="true"></div>
  <header class="header">
    <div class="nav">
      <a class="brand" href="#home" aria-label="<?= e($portfolio['name']) ?> home"><span class="brand-mark" aria-hidden="true"><?= e($portfolio['initials']) ?></span><span><?= e($portfolio['name']) ?><span class="accent">.</span></span></a>
      <div class="nav-right">
        <nav class="nav-links" id="siteNav" aria-label="Main navigation">
          <a href="#home">Home</a>
          <details class="nav-dropdown">
            <summary>About <i class="fa-solid fa-chevron-down nav-dropdown-chevron" aria-hidden="true"></i></summary>
            <div class="nav-dropdown-menu">
              <a href="#about">About me</a>
              <a href="#journey">Journey</a>
              <a href="#skills">Skills</a>
              <a href="#faq">FAQs</a>
            </div>
          </details>
          <details class="nav-dropdown">
            <summary>Projects <i class="fa-solid fa-chevron-down nav-dropdown-chevron" aria-hidden="true"></i></summary>
            <div class="nav-dropdown-menu">
              <a href="#work" data-project-filter="all">All projects</a>
              <a href="#work" data-project-filter="design">Product design</a>
              <a href="#work" data-project-filter="development">Development</a>
              <a href="#work" data-project-filter="strategy">Strategy</a>
            </div>
          </details>
          <a href="#gallery">Gallery</a>
          <details class="nav-dropdown">
            <summary>Blog <i class="fa-solid fa-chevron-down nav-dropdown-chevron" aria-hidden="true"></i></summary>
            <div class="nav-dropdown-menu">
              <a href="#blog">All notes</a>
              <?php foreach ($articles as $index => $article): ?>
              <a href="#article-<?= $index ?>"><?= e($article['title']) ?></a>
              <?php endforeach; ?>
            </div>
          </details>
          <a href="#contact">Contact</a>
          <div class="search-wrap">
            <form class="search-form" id="siteSearch" role="search">
              <span class="search-icon" aria-hidden="true">⌕</span>
              <input class="search-input" id="searchInput" type="search" placeholder="Search..." autocomplete="off" aria-label="Search portfolio" aria-controls="searchResults" aria-expanded="false">
              <button class="search-submit" type="submit" aria-label="Show search results">↵</button>
            </form>
            <div class="search-results" id="searchResults" role="listbox" aria-label="Search results" hidden></div>
          </div>
        </nav>
        <button class="icon-button" id="themeToggle" type="button" aria-label="Switch to light theme" title="Toggle color theme">☼</button>
        <button class="menu-button" id="menuToggle" type="button" aria-label="Open navigation menu" aria-controls="siteNav" aria-expanded="false">☰</button>
      </div>
    </div>
  </header>
  <main id="main">
    <section class="hero" id="home">
      <div class="container hero-grid">
        <div class="reveal">
          <div class="eyebrow"><span class="status" aria-hidden="true"></span><?= e($portfolio['availability']) ?></div>
          <h1>Hi, I’m <span class="accent"><?= e($portfolio['name']) ?>.</span><br>I build <span class="typed" id="typedRole">digital experiences</span><span class="caret" aria-hidden="true"></span></h1>
          <p class="hero-copy"><?= e($portfolio['intro']) ?></p>
          <div class="hero-actions"><a class="button" href="#work">Explore my work <span aria-hidden="true">↗</span></a><a class="button ghost" href="#contact">Let’s talk</a></div>
          <div class="hero-note"><span class="avatar-stack" aria-hidden="true"><span>UX</span><span>UI</span><span>DEV</span></span><span>Thoughtful design · purposeful technology · real outcomes</span></div>
        </div>
        <div class="portrait reveal" role="img" aria-label="Illustrated portrait placeholder. Replace with your own photograph.">
          <div class="portrait-shape"><span class="initials"><?= e($portfolio['initials']) ?></span></div>
          <div class="portrait-label"><strong>Your portrait</strong>Replace with a photo</div>
        </div>
      </div>
    </section>

    <section class="section about-section reveal" id="about">
      <div class="about-intro">
        <div class="about-intro-inner">
          <span class="kicker"><span class="status" aria-hidden="true"></span> A little about me</span>
          <h2>My story<span class="accent">.</span></h2>
          <a class="about-me-button" href="#whoIAm">About Me <span class="about-down" aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></span></a>
        </div>
      </div>
      <div class="container about-body" id="whoIAm">
        <div class="about-heading">
          <span class="kicker">The person behind the work</span>
          <h2>Who I Am<span class="accent">.</span></h2>
        </div>
        <div class="about-layout">
          <div class="about-photo-wrap">
            <img class="about-photo" src="https://aashish-jha.com.np/uploads/profile/b846793874e63209.jpg" alt="Portrait of <?= e($portfolio['name']) ?>" loading="lazy">
          </div>
          <div class="about-statement">
            <span class="kicker"><?= e($portfolio['role']) ?> · <?= e($portfolio['location']) ?></span>
            <h3><?= e($portfolio['name']) ?></h3>
            <div class="about-lede"><?php foreach ($portfolio['about'] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?></div>
            <div class="about-values" role="group" aria-label="Objectives and values that guide my work">
              <?php foreach ($aboutValues as $key => $value): ?>
              <button class="about-value" type="button" data-about-value="<?= e($key) ?>" aria-pressed="<?= $key === 'curiosity' ? 'true' : 'false' ?>">
                <strong><?= e($value['label']) ?></strong><span><?= e($value['description']) ?></span>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="about-footer"><p>Thoughtful work starts with a good conversation.</p><div class="about-links"><a class="about-link" href="#work">Explore projects <span aria-hidden="true">↗</span></a><a class="about-link" href="#contact">Get in touch <span aria-hidden="true">↗</span></a></div></div>
      </div>
    </section>

    <section class="container section reveal" id="work">
      <div class="section-head"><div><span class="kicker">Selected projects</span><h2>Work I’m proud of.</h2></div><p>Replace these sample cards with real case studies. Filter by the kind of work you want to explore.</p></div>
      <div class="filters" role="group" aria-label="Filter projects">
        <button class="filter active" type="button" data-filter="all" aria-pressed="true">All work</button>
        <button class="filter" type="button" data-filter="design" aria-pressed="false">Product design</button>
        <button class="filter" type="button" data-filter="development" aria-pressed="false">Development</button>
        <button class="filter" type="button" data-filter="strategy" aria-pressed="false">Strategy</button>
      </div>
      <div class="projects">
        <?php foreach ($projects as $index => $project): ?>
        <article class="project" id="project-<?= $index ?>" data-category="<?= e($project['category']) ?>">
          <div class="project-art" aria-hidden="true"><div class="mock-window"><div class="mock-bar"></div><div class="mock-line"></div><div class="mock-line short"></div><div class="mock-blocks"><span></span><span></span><span></span></div></div></div>
          <div class="project-body">
            <div class="project-meta"><span><?= e($project['category_label']) ?></span><span><?= sprintf('%02d / %02d', $index + 1, count($projects)) ?></span></div>
            <h3><?= e($project['title']) ?></h3><p><?= e($project['summary']) ?></p>
            <div class="tags"><?php foreach ($project['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div>
            <button class="project-open" type="button" data-project="<?= $index ?>">View case study <span aria-hidden="true">↗</span></button>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section gallery-section reveal" id="gallery">
      <div class="gallery-intro">
        <div class="gallery-intro-inner">
          <span class="kicker"><span class="status" aria-hidden="true"></span> A few things I’ve made</span>
          <h2>My gallery<span class="accent">.</span></h2>
          <p>A little window into my workspace, the moments worth celebrating, and the projects that brought ideas to life.</p>
          <a class="gallery-down" href="#galleryWall" aria-label="Scroll down to explore the gallery">↓</a>
        </div>
      </div>
      <div class="container gallery-collection" id="galleryWall">
        <div class="gallery-collection-head"><span class="kicker">The collection</span><h3>Made, found, and remembered.</h3></div>
        <div class="gallery-filters" role="group" aria-label="Filter gallery">
          <button class="gallery-filter" type="button" data-gallery-filter="all" aria-pressed="true">All</button>
          <button class="gallery-filter" type="button" data-gallery-filter="workspace" aria-pressed="false">Workspace</button>
          <button class="gallery-filter" type="button" data-gallery-filter="awards" aria-pressed="false">Awards</button>
          <button class="gallery-filter" type="button" data-gallery-filter="project" aria-pressed="false">Project</button>
        </div>
        <p class="gallery-count" id="galleryCount" aria-live="polite"><?= count($gallery) ?> moments</p>
        <div class="gallery-grid" id="galleryGrid">
          <?php foreach ($gallery as $index => $item): ?>
          <article class="gallery-card" id="gallery-item-<?= $index ?>" data-gallery-category="<?= e($item['category']) ?>">
            <button class="gallery-open" type="button" data-gallery-item="<?= $index ?>" aria-label="View <?= e($item['title']) ?>">
              <span class="gallery-art <?= e($item['art']) ?>" aria-hidden="true">
                <?php if (strpos($item['art'], 'award-') === 0): ?>
                <span class="art-award"><span><?= $item['art'] === 'award-laurel' ? '✺' : '★' ?></span></span>
                <?php else: ?>
                <span class="gallery-art-inner"><span class="art-window"><span class="art-window-bar"></span><span class="art-lines"><span></span><span></span><span></span></span><span class="art-blocks"><span></span><span></span><span></span></span></span></span>
                <?php endif; ?>
              </span>
              <span class="gallery-copy"><span><small><?= e($item['type']) ?></small><strong><?= e($item['title']) ?></strong></span><span class="gallery-arrow" aria-hidden="true">↗</span></span>
            </button>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="container section reveal" id="skills">
      <div class="section-head"><div><span class="kicker">Technical toolkit</span><h2>Skills &amp; tools.</h2></div><p>Update these technical skills and descriptions to reflect your experience and preferred tools.</p></div>
      <div class="skills"><?php foreach ($skills as [$title, $detail]): ?><div class="skill"><strong><?= e($title) ?></strong><?= e($detail) ?></div><?php endforeach; ?></div>
    </section>

    <section class="container section reveal" id="journey">
      <div class="section-head"><div><span class="kicker">The journey so far</span><h2>Experience &amp; education.</h2></div><p>Share the roles, learning, and milestones that have shaped your path.</p></div>
      <div class="journey-group" id="experience">
        <div class="journey-subhead"><span class="kicker">Work</span><h3>Experience</h3></div>
        <div class="timeline"><?php foreach ($experience as [$date, $title, $detail]): ?><article class="timeline-item"><div class="timeline-date"><?= e($date) ?></div><div><h3><?= e($title) ?></h3><p><?= e($detail) ?></p></div></article><?php endforeach; ?></div>
      </div>
      <div class="journey-group" id="education">
        <div class="journey-subhead"><span class="kicker">Learning</span><h3>Education</h3></div>
        <div class="timeline"><?php foreach ($education as [$status, $title, $detail]): ?><article class="timeline-item"><div><span class="education-status"><?= e($status) ?></span></div><div><h3><?= e($title) ?></h3><p><?= e($detail) ?></p></div></article><?php endforeach; ?></div>
      </div>
    </section>

    <section class="container section reveal" id="faq">
      <div class="section-head"><div><span class="kicker">A few helpful answers</span><h2>FAQs.</h2></div><p>Browse common questions by topic. Replace these sample answers with details that fit your work.</p></div>
      <div class="faq-filters" role="group" aria-label="Filter FAQs">
        <button class="faq-filter active" type="button" data-faq-filter="all" aria-pressed="true">All questions</button>
        <?php foreach ($faqCategories as $category => $label): ?>
        <button class="faq-filter" type="button" data-faq-filter="<?= e($category) ?>" aria-pressed="false"><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="faq-grid">
        <?php foreach ($faqs as $index => $faq): ?>
        <article class="faq-card" id="faq-<?= $index ?>" data-faq-category="<?= e($faq['category']) ?>">
          <span class="faq-category"><?= e($faqCategories[$faq['category']]) ?></span>
          <h3><?= e($faq['question']) ?></h3>
          <p><?= e($faq['answer']) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="container section reveal" id="blog">
      <div class="section-head"><div><span class="kicker">Notes &amp; perspectives</span><h2>From the blog.</h2></div><p>Short notes on design, technology, collaboration, and the lessons that come from making things.</p></div>
      <div class="article-grid">
        <?php foreach ($articles as $index => $article): ?>
        <article class="article-card" id="article-<?= $index ?>">
          <span class="article-date"><?= e($article['date']) ?></span>
          <h3><?= e($article['title']) ?></h3>
          <p><?= e($article['summary']) ?></p>
          <details><summary>Read note</summary><p><?= e($article['body']) ?></p></details>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="container section reveal" id="contact">
      <div class="contact"><div><span class="kicker">Have a good one in mind?</span><h2>Let’s make it happen.</h2><p>Tell me a little about what you’re working on. Replace the sample email below with your preferred contact address.</p></div>
        <div class="contact-actions"><a class="button" href="mailto:<?= e($portfolio['email']) ?>?subject=Let%27s%20work%20together">Email me <span aria-hidden="true">↗</span></a><button class="button ghost" id="copyEmail" type="button" data-email="<?= e($portfolio['email']) ?>">Copy email</button></div>
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
  <footer class="container footer"><span>© <span id="year"></span> <?= e($portfolio['name']) ?> · Made with intention.</span><nav class="footer-links" aria-label="Social links"><a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer">LinkedIn</a><a href="https://github.com/" target="_blank" rel="noopener noreferrer">GitHub</a><a href="#home">Back to top ↑</a></nav></footer>

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
      const projects = <?= json_encode($projects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const galleryItems = <?= json_encode($gallery, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      let activeTrigger = null;
      let toastTimer;
      let searchItems = [];
      let matchingItems = [];
      let activeSearchIndex = -1;

      document.getElementById('year').textContent = String(new Date().getFullYear());
      themeButton.addEventListener('click', () => {
        const light = root.dataset.theme !== 'light';
        root.dataset.theme = light ? 'light' : 'dark';
        themeButton.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
        themeButton.textContent = light ? '☾' : '☼';
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
      nav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
      nav.querySelectorAll('.nav-dropdown-menu a').forEach(link => link.addEventListener('click', () => {
        const filter = link.dataset.projectFilter;
        if (filter) document.querySelector(`.filter[data-filter="${filter}"]`)?.click();
        link.closest('.nav-dropdown').open = false;
      }));
      nav.querySelectorAll('.nav-dropdown').forEach(dropdown => dropdown.addEventListener('toggle', () => {
        if (!dropdown.open) return;
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
        if (target.closest('.project[hidden]')) {
          document.querySelector('.filter[data-filter="all"]')?.click();
        }
        if (target.closest('.gallery-card[hidden]')) {
          document.querySelector('.gallery-filter[data-gallery-filter="all"]')?.click();
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
        ...Array.from(document.querySelectorAll('.article-card[id]'), card => ({
          id:card.id, title:card.querySelector('h3')?.textContent.trim() || 'Blog note', category:'Blog', content:card.textContent
        }))
      ];
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

      document.querySelectorAll('.filter').forEach(button => button.addEventListener('click', () => {
        const filter = button.dataset.filter;
        document.querySelectorAll('.filter').forEach(item => {
          const selected = item === button;
          item.classList.toggle('active', selected);
          item.setAttribute('aria-pressed', String(selected));
        });
        document.querySelectorAll('.project').forEach(card => {
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

      document.querySelectorAll('.project-open').forEach(button => button.addEventListener('click', () => {
        const project = projects[Number(button.dataset.project)];
        if (!project) return;
        activeTrigger = button;
        document.getElementById('dialogCategory').textContent = project.category_label;
        document.getElementById('dialogTitle').textContent = project.title;
        document.getElementById('dialogSummary').textContent = project.summary;
        document.getElementById('dialogContributionLabel').textContent = 'My contribution:';
        document.getElementById('dialogContribution').textContent = project.contribution;
        document.getElementById('dialogOutcomeLabel').textContent = 'Outcome:';
        document.getElementById('dialogOutcome').textContent = project.outcome;
        const tags = document.getElementById('dialogTags');
        tags.replaceChildren(...project.tags.map(tag => {
          const element = document.createElement('span');
          element.textContent = tag;
          return element;
        }));
        dialog.showModal();
      }));
      document.querySelectorAll('.about-value').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('.about-value').forEach(valueButton => {
          valueButton.setAttribute('aria-pressed', String(valueButton === button));
        });
      }));
      document.querySelectorAll('.gallery-open').forEach(button => button.addEventListener('click', () => {
        const item = galleryItems[Number(button.dataset.galleryItem)];
        if (!item) return;
        activeTrigger = button;
        document.getElementById('dialogCategory').textContent = item.type;
        document.getElementById('dialogTitle').textContent = item.title;
        document.getElementById('dialogSummary').textContent = item.summary;
        document.getElementById('dialogContributionLabel').textContent = 'A closer look:';
        document.getElementById('dialogContribution').textContent = item.detail;
        document.getElementById('dialogOutcomeLabel').textContent = 'The story:';
        document.getElementById('dialogOutcome').textContent = item.outcome;
        document.getElementById('dialogTags').replaceChildren();
        dialog.showModal();
      }));

      const galleryCards = Array.from(document.querySelectorAll('.gallery-card'));
      document.querySelectorAll('.gallery-filter').forEach(button => button.addEventListener('click', () => {
        const filter = button.dataset.galleryFilter;
        document.querySelectorAll('.gallery-filter').forEach(item => {
          const selected = item === button;
          item.setAttribute('aria-pressed', String(selected));
        });
        let visibleCount = 0;
        galleryCards.forEach((card, index) => {
          const visible = filter === 'all' || card.dataset.galleryCategory === filter;
          card.hidden = !visible;
          if (visible) {
            visibleCount += 1;
            card.style.animationDelay = `${Math.min(index % 3, 2) * 55}ms`;
            card.classList.remove('is-entering');
            requestAnimationFrame(() => card.classList.add('is-entering'));
          } else {
            card.style.animationDelay = '';
            card.classList.remove('is-entering');
          }
        });
        document.getElementById('galleryCount').textContent = `${visibleCount} ${visibleCount === 1 ? 'moment' : 'moments'}`;
      }));
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
      document.getElementById('copyEmail').addEventListener('click', async event => {
        const address = event.currentTarget.dataset.email;
        try {
          await navigator.clipboard.writeText(address);
          showToast('Email address copied to clipboard.');
        } catch {
          showToast(`Copy is unavailable here. Email: ${address}`);
        }
      });

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

      if (!reducedMotion) {
        const typed = document.getElementById('typedRole');
        const phrases = ['digital experiences','thoughtful products','useful solutions'];
        let phraseIndex = 0, characterIndex = phrases[0].length, deleting = false;
        const type = () => {
          const phrase = phrases[phraseIndex];
          characterIndex += deleting ? -1 : 1;
          typed.textContent = phrase.slice(0, characterIndex);
          let delay = deleting ? 38 : 68;
          if (!deleting && characterIndex >= phrase.length) { deleting = true; delay = 1450; }
          else if (deleting && characterIndex <= 0) { deleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; delay = 260; }
          window.setTimeout(type, delay);
        };
        window.setTimeout(type, 900);
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
    })();
  </script>
</body>
</html>