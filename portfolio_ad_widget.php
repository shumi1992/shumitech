<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/portfolio_advertising_storage.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

try {
    portfolioEnsureAdvertisingTable($conn);
    $advertisements = array_values(array_filter(
        portfolioLoadAdvertisements($conn),
        static function (array $advertisement): bool {
            $logo = trim((string) ($advertisement['brand_logo_url'] ?? ''));
            return portfolioAdvertisementAssetValid($advertisement['product_image_url'] ?? null)
                && ($logo === '' || portfolioAdvertisementAssetValid($logo))
                && portfolioAdvertisementLinkValid($advertisement['target_url'] ?? '');
        }
    ));
} catch (Throwable $exception) {
    error_log('Could not load the public portfolio advertisement widget: ' . $exception->getMessage());
    http_response_code(503);
    $advertisements = [];
}

$publicAdvertisements = array_map(static function (array $advertisement): array {
    return [
        'brand' => (string) $advertisement['brand_name'],
        'product' => (string) $advertisement['product_name'],
        'description' => (string) $advertisement['description'],
        'logo' => (string) ($advertisement['brand_logo_url'] ?? ''),
        'image' => (string) $advertisement['product_image_url'],
        'url' => (string) ($advertisement['target_url'] ?? ''),
        'cta' => (string) $advertisement['cta_label'],
    ];
}, $advertisements);
$advertisementsJson = json_encode(
    $publicAdvertisements,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
if ($advertisementsJson === false) {
    error_log('Could not encode portfolio advertisements for the public widget.');
    http_response_code(500);
    $advertisementsJson = '[]';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    *{box-sizing:border-box}
    html,body{width:100%;height:100%;margin:0;overflow:hidden;background:transparent;font-family:Inter,Arial,sans-serif}
    .ad-shell{position:relative;width:100%;height:100%;padding:5px;opacity:0;transform:translateY(14px) scale(.98);pointer-events:none}
    .ad-shell.is-visible{animation:ad-enter .32s ease forwards}
    .ad-card{position:relative;display:grid;grid-template-rows:auto minmax(0,1fr) auto;gap:8px;width:100%;height:100%;overflow:hidden;padding:13px;border:1px solid rgba(103,232,249,.42);border-radius:16px;color:#f8fafc;background:linear-gradient(135deg,#102531 0%,#172333 53%,#342817 100%);box-shadow:0 12px 36px rgba(0,0,0,.4)}
    .ad-card:before{position:absolute;inset:0 0 auto;height:2px;content:"";background:linear-gradient(90deg,#22d3ee,#fbbf24,#22d3ee);background-size:200% 100%;animation:ad-flow 3s linear infinite}
    .ad-top,.ad-brand,.ad-main,.ad-footer{position:relative;z-index:1}
    .ad-top,.ad-footer{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .ad-label{overflow:hidden;color:#bae6fd;font-size:10px;font-weight:800;letter-spacing:.08em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}
    .ad-close{width:28px;height:28px;flex:0 0 auto;display:grid;place-items:center;border:1px solid rgba(255,255,255,.2);border-radius:9px;color:#fff;background:rgba(255,255,255,.08);font-size:18px;cursor:pointer}
    .ad-close:hover,.ad-close:focus-visible{border-color:#fbbf24;color:#fbbf24;outline:2px solid #67e8f9;outline-offset:2px}
    .ad-main{display:grid;grid-template-columns:37% minmax(0,1fr);align-items:center;gap:12px;min-height:0}
    .ad-image{width:100%;height:100%;max-height:116px;object-fit:cover;border-radius:11px;background:rgba(255,255,255,.08)}
    .ad-copy{min-width:0}
    .ad-brand{display:flex;align-items:center;gap:7px;margin-bottom:4px;color:#fbbf24;font-size:10px;font-weight:750}
    .ad-logo{width:20px;height:20px;flex:0 0 auto;object-fit:cover;border-radius:50%;background:rgba(255,255,255,.12)}
    .ad-product{display:block;display:-webkit-box;overflow:hidden;color:#fff;font-size:16px;font-weight:850;line-height:1.2;-webkit-box-orient:vertical;-webkit-line-clamp:2}
    .ad-description{display:-webkit-box;overflow:hidden;margin:5px 0 0;color:#d4e4eb;font-size:11px;line-height:1.4;-webkit-box-orient:vertical;-webkit-line-clamp:3}
    .ad-footer{padding-top:7px;border-top:1px solid rgba(255,255,255,.14)}
    .ad-note{color:#cbd5e1;font-size:9px}
    .ad-link{max-width:60%;overflow:hidden;padding:6px 10px;border-radius:999px;color:#06131a;background:linear-gradient(100deg,#22d3ee,#fbbf24);font-size:10px;font-weight:850;text-decoration:none;text-overflow:ellipsis;white-space:nowrap}
    .ad-link:hover,.ad-link:focus-visible{outline:2px solid white;outline-offset:2px}
    .ad-progress{position:absolute;right:0;bottom:0;left:0;height:3px;transform:scaleX(1);transform-origin:left;background:linear-gradient(90deg,#22d3ee,#fbbf24)}
    .ad-shell.is-visible .ad-progress{animation:ad-progress 5s linear forwards}
    @keyframes ad-enter{to{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}}
    @keyframes ad-flow{to{background-position:200% 0}}
    @keyframes ad-progress{to{transform:scaleX(0)}}
    @media(max-width:350px){.ad-card{padding:10px}.ad-main{grid-template-columns:32% minmax(0,1fr);gap:8px}.ad-product{font-size:14px}.ad-description{-webkit-line-clamp:2}}
    @media(prefers-reduced-motion:reduce){*,*:before,*:after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
  </style>
</head>
<body>
  <main class="ad-shell" id="adShell" aria-label="Sponsored advertisement" aria-live="polite">
    <article class="ad-card">
      <div class="ad-top"><span class="ad-label">Sponsored · Featured offer</span><button class="ad-close" id="adClose" type="button" aria-label="Close advertisement">×</button></div>
      <div class="ad-main">
        <img class="ad-image" id="adImage" alt="" referrerpolicy="no-referrer">
        <div class="ad-copy"><span class="ad-brand"><img class="ad-logo" id="adLogo" alt="" hidden><span id="adBrand"></span></span><strong class="ad-product" id="adProduct"></strong><p class="ad-description" id="adDescription"></p></div>
      </div>
      <div class="ad-footer"><span class="ad-note">Advertisement</span><a class="ad-link" id="adLink" target="_blank" rel="noopener noreferrer"><span id="adCta"></span> <span aria-hidden="true">↗</span></a></div>
      <span class="ad-progress" aria-hidden="true"></span>
    </article>
  </main>
  <script>
    (function () {
      'use strict';
      var ads = <?= $advertisementsJson ?>;
      var shell = document.getElementById('adShell');
      var closeButton = document.getElementById('adClose');
      var currentIndex = 0;
      var startTimer = null;
      var hideTimer = null;
      var paused = false;
      var nextEligibleAt = 0;
      var cooldownKey = 'portfolio-ad-next-eligible';
      var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      function notifyParent(visible) {
        window.parent.postMessage({ type: 'portfolio-ad-visibility', visible: visible }, '*');
      }

      function clearTimers() {
        window.clearTimeout(startTimer);
        window.clearTimeout(hideTimer);
        startTimer = null;
        hideTimer = null;
      }

      function rememberCooldown() {
        nextEligibleAt = Date.now() + 60000;
        try {
          window.localStorage.setItem(cooldownKey, String(nextEligibleAt));
        } catch (error) {
          console.error('Could not save the advertisement cooldown in this browser.', error);
        }
      }

      function hideAdvertisement(startCooldown) {
        clearTimers();
        shell.classList.remove('is-visible');
        shell.style.pointerEvents = 'none';
        notifyParent(false);
        if (startCooldown) rememberCooldown();
        scheduleAdvertisement(startCooldown ? 60000 : 5000);
      }

      function showAdvertisement() {
        if (paused || document.hidden || ads.length === 0) return;
        var ad = ads[currentIndex % ads.length];
        currentIndex = (currentIndex + 1) % ads.length;
        document.getElementById('adImage').src = ad.image;
        document.getElementById('adBrand').textContent = ad.brand;
        document.getElementById('adProduct').textContent = ad.product;
        document.getElementById('adDescription').textContent = ad.description;
        document.getElementById('adCta').textContent = ad.cta;
        var logo = document.getElementById('adLogo');
        if (ad.logo) {
          logo.src = ad.logo;
          logo.hidden = false;
        } else {
          logo.removeAttribute('src');
          logo.hidden = true;
        }
        var link = document.getElementById('adLink');
        if (ad.url) {
          link.href = ad.url;
          link.hidden = false;
        } else {
          link.removeAttribute('href');
          link.hidden = true;
        }
        shell.classList.remove('is-visible');
        shell.style.pointerEvents = 'auto';
        if (!reducedMotion) void shell.offsetWidth;
        shell.classList.add('is-visible');
        notifyParent(true);
        hideTimer = window.setTimeout(function () {
          hideAdvertisement(true);
        }, 5000);
      }

      function scheduleAdvertisement(delay) {
        clearTimers();
        if (paused || document.hidden || ads.length === 0) return;
        var timeUntilEligible = Math.max(0, nextEligibleAt - Date.now());
        var wait = Math.max(delay || 0, timeUntilEligible);
        startTimer = window.setTimeout(showAdvertisement, wait);
      }

      try {
        var storedNextEligibleAt = Number(window.localStorage.getItem(cooldownKey));
        if (Number.isFinite(storedNextEligibleAt) && storedNextEligibleAt > Date.now()) {
          nextEligibleAt = storedNextEligibleAt;
        }
      } catch (error) {
        console.error('Could not read the advertisement cooldown from this browser.', error);
      }

      closeButton.addEventListener('click', function () {
        hideAdvertisement(true);
      });
      window.addEventListener('message', function (event) {
        if (event.source !== window.parent || !event.data || event.data.type !== 'portfolio-ad-page-state') return;
        paused = event.data.allowed !== true;
        if (paused) {
          clearTimers();
          shell.classList.remove('is-visible');
          shell.style.pointerEvents = 'none';
          notifyParent(false);
        } else if (shell.classList.contains('is-visible')) {
          return;
        } else {
          scheduleAdvertisement(5000);
        }
      });
      document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
          if (shell.classList.contains('is-visible')) hideAdvertisement(true);
          else clearTimers();
        } else {
          scheduleAdvertisement(5000);
        }
      });
      if (ads.length > 0) scheduleAdvertisement(5000);
    }());
  </script>
</body>
</html>
