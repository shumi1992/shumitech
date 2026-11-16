(function () {
  'use strict';

  var script = document.currentScript;
  if (!script || script.dataset.portfolioAdEmbedLoaded === 'true') return;
  script.dataset.portfolioAdEmbedLoaded = 'true';

  var widgetUrl = new URL('portfolio_ad_widget.php', script.src);
  var frame = document.createElement('iframe');
  var homeOnly = script.dataset.homeOnly === 'true';
  frame.title = 'Sponsored product advertisements';
  frame.loading = 'lazy';
  frame.src = widgetUrl.href;
  frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups');
  frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
  frame.style.cssText = [
    'position:fixed',
    'z-index:2147483000',
    'right:12px',
    'bottom:10px',
    'width:min(390px,calc(100vw - 24px))',
    'height:230px',
    'border:0',
    'background:transparent',
    'visibility:hidden',
    'opacity:0',
    'pointer-events:none',
    'transition:opacity 220ms ease,visibility 220ms ease'
  ].join(';');

  function isAllowedPage() {
    return !homeOnly || !window.location.hash || window.location.hash === '#home';
  }

  function sendPageState() {
    if (frame.contentWindow) {
      frame.contentWindow.postMessage({
        type: 'portfolio-ad-page-state',
        allowed: isAllowedPage()
      }, widgetUrl.origin);
    }
    if (!isAllowedPage()) hideFrame();
  }

  function hideFrame() {
    frame.style.visibility = 'hidden';
    frame.style.opacity = '0';
    frame.style.pointerEvents = 'none';
  }

  window.addEventListener('message', function (event) {
    if (event.source !== frame.contentWindow || event.origin !== widgetUrl.origin ||
        !event.data || event.data.type !== 'portfolio-ad-visibility') return;
    var visible = event.data.visible === true && isAllowedPage();
    frame.style.visibility = visible ? 'visible' : 'hidden';
    frame.style.opacity = visible ? '1' : '0';
    frame.style.pointerEvents = visible ? 'auto' : 'none';
  });

  frame.addEventListener('load', sendPageState);
  window.addEventListener('hashchange', sendPageState);
  if (document.body) {
    document.body.appendChild(frame);
  } else {
    document.addEventListener('DOMContentLoaded', function () {
      document.body.appendChild(frame);
    }, { once: true });
  }
}());
