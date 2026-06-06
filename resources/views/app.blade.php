<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo/apple-touch-icon.png">

    {{-- PWA (installable / Add to Home Screen) — no service worker (PHI-safe) --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#1B365D">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ \App\Models\Setting::get('clinic_name', 'Doctorato') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">

    {{-- Google Tag Manager --}}
    @if($gtmId = \App\Models\Setting::get('tracking_gtm_id'))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    @endif

    {{-- Google Analytics 4 --}}
    @if($ga4Id = \App\Models\Setting::get('tracking_ga4_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $ga4Id }}');
    </script>
    @endif

    {{-- Facebook Pixel --}}
    @if($fbPixelId = \App\Models\Setting::get('tracking_fb_pixel_id'))
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $fbPixelId }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"/></noscript>
    @endif

    {{-- TikTok Pixel --}}
    @if($tiktokPixelId = \App\Models\Setting::get('tracking_tiktok_pixel_id'))
    <script>
    !function (w, d, t) {
    w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
    ttq.load('{{ $tiktokPixelId }}');
    ttq.page();
    }(window, document, 'ttq');
    </script>
    @endif

    {{-- Snapchat Pixel --}}
    @if($snapPixelId = \App\Models\Setting::get('tracking_snapchat_pixel_id'))
    <script type="text/javascript">
    (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
    {a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
    a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
    r.src=n;var u=t.getElementsByTagName(s)[0];
    u.parentNode.insertBefore(r,u);})(window,document,
    'https://sc-static.net/scevent.min.js');
    snaptr('init', '{{ $snapPixelId }}', {});
    snaptr('track', 'PAGE_VIEW');
    </script>
    @endif

    {{-- Twitter/X Pixel --}}
    @if($twitterPixelId = \App\Models\Setting::get('tracking_twitter_pixel_id'))
    <script>
    !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
    },s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
    a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
    twq('config','{{ $twitterPixelId }}');
    </script>
    @endif

    {{-- Custom Head Scripts --}}
    @if($headScripts = \App\Models\Setting::get('tracking_head_scripts'))
    {!! $headScripts !!}
    @endif

    @inertiaHead
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    {{-- Google Tag Manager (noscript) --}}
    @if($gtmId = \App\Models\Setting::get('tracking_gtm_id'))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Custom Body Start Scripts --}}
    @if($bodyStartScripts = \App\Models\Setting::get('tracking_body_start_scripts'))
    {!! $bodyStartScripts !!}
    @endif

    @inertia

    {{-- Custom Body End Scripts --}}
    @if($bodyEndScripts = \App\Models\Setting::get('tracking_body_end_scripts'))
    {!! $bodyEndScripts !!}
    @endif
</body>
</html>
