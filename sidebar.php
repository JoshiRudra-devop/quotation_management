<?php
/**
 * sidebar.php → App Shell (Top Title Bar + Bottom Navigation)
 * Included at the top of every authenticated page's layout.
 */
$current_page = basename($_SERVER['PHP_SELF']);
if (!function_exists('sb_active')) {
    function sb_active($page, $current) {
        return $page === $current ? 'active' : '';
    }
}

$_sb_is_trial = false;
if (!empty($_SESSION['user_id']) && function_exists('is_trial_user')) {
    $_sb_is_trial = is_trial_user($_SESSION['user_id']);
}
$_sb_on_form = ($current_page === 'form2.php');

// Page title & breadcrumb map
$page_meta = [
    'home.php'        => ['title' => 'Home',             'crumb' => 'Home'],
    'form2.php'       => ['title' => 'New Quotation',    'crumb' => 'Home / New Quotation'],
    'add-Product.php' => ['title' => 'Add Product',      'crumb' => 'Home / Products / Add'],
    'add-company.php' => ['title' => 'Add Company',      'crumb' => 'Home / Companies / Add'],
    'settings.php'    => ['title' => 'Settings',         'crumb' => 'Home / Settings'],
    'about.php'       => ['title' => 'About',            'crumb' => 'Home / About'],
    'contact.php'     => ['title' => 'Contact',          'crumb' => 'Home / Contact'],
    'premium.php'     => ['title' => 'Upgrade',          'crumb' => 'Home / Upgrade'],
];
$_meta_title  = $page_meta[$current_page]['title']  ?? ucfirst(str_replace(['.php','-'], ['',' '], $current_page));
$_meta_crumb  = $page_meta[$current_page]['crumb']  ?? '';
?>
<!-- Native App Injection (Transforms Web View to App View) -->
<script>
    (function() {
        // Prevent viewport zooming
        let metaViewport = document.querySelector('meta[name="viewport"]');
        if (!metaViewport) {
            metaViewport = document.createElement('meta');
            metaViewport.name = "viewport";
            document.head.appendChild(metaViewport);
        }
        // viewport-fit=cover fills notches, user-scalable=no prevents zoom
        metaViewport.content = "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover";

        // iOS & Android web app capable tags
        const metaTags = [
            { name: "apple-mobile-web-app-capable", content: "yes" },
            { name: "apple-mobile-web-app-status-bar-style", content: "black-translucent" },
            { name: "theme-color", content: "#070a0a" },
            { name: "mobile-web-app-capable", content: "yes" }
        ];

        metaTags.forEach(tag => {
            const meta = document.createElement('meta');
            meta.name = tag.name;
            meta.content = tag.content;
            document.head.appendChild(meta);
        });

        // Inject Native App CSS tweaks
        const nativeStyles = document.createElement('style');
        nativeStyles.textContent = `
            html, body {
                /* Prevent pull-to-refresh bouncy effect */
                overscroll-behavior-y: none;
                /* Disable default link long-press menu on iOS */
                -webkit-touch-callout: none;
                /* Prevent text selection everywhere (feels native) */
                -webkit-user-select: none;
                user-select: none;
                /* Remove default browser tap highlight colors */
                -webkit-tap-highlight-color: transparent;
                /* Accommodate notch/dynamic island on modern phones */
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }
            
            /* Give back text selection and interaction for inputs */
            input, textarea, select, [contenteditable="true"] {
                -webkit-user-select: auto !important;
                user-select: auto !important;
            }

            /* Smooth momentum scrolling everywhere */
            * {
                -webkit-overflow-scrolling: touch;
            }
        `;
        document.head.appendChild(nativeStyles);
    })();
</script>

<!-- Global Top App Bar (Native Style) --><?php if (function_exists('flash_render')) flash_render(); ?>

<!-- ═══════════════════════════════════════════
     TOP TITLE BAR  (breadcrumb + user actions)
     ═══════════════════════════════════════════ -->
<header class="app-topbar" id="appTopbar">
    <div class="topbar-left">
        <a href="home.php" class="topbar-brand">
            <img class="topbar-logo" src="logo-new.png" alt="QuaTation" />
        </a>
        <div class="topbar-breadcrumb">
            <span class="topbar-page-title"><?php echo htmlspecialchars($_meta_title); ?></span>
            <span class="topbar-crumb"><?php echo htmlspecialchars($_meta_crumb); ?></span>
        </div>
    </div>
    <div class="topbar-right">
        <?php if ($_sb_is_trial): ?>
        <a href="premium.php" class="topbar-trial-badge">⭐ Trial</a>
        <?php endif; ?>
        <button class="topbar-icon-btn" id="themeToggleBtn" onclick="if(typeof QT!=='undefined')QT.toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <button class="topbar-icon-btn topbar-profile-btn" onclick="window.location.href='settings.php'" title="Profile" aria-label="Profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
    </div>
</header>

<!-- ═══════════════════════════════════════════
     BOTTOM NAVIGATION BAR  (6 items)
     ═══════════════════════════════════════════ -->
<nav class="bottom-nav" id="bottomNav" role="navigation" aria-label="Main navigation">

    <!-- 1. Home (Recent) -->
    <a href="home.php" class="bnav-item <?php echo sb_active('home.php', $current_page); ?>" id="bnavRecent" title="Home" onclick="if(window.switchTab){switchTab('recent'); return false;}">
        <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span class="bnav-label">Home</span>
    </a>

    <!-- 2. By Company -->
    <a href="home.php#bycompany" class="bnav-item" id="bnavByCompany" title="By Company" onclick="if(window.switchTab){switchTab('bycompany'); return false;}">
        <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
        </svg>
        <span class="bnav-label">By Company</span>
    </a>

    <!-- 3. New Quotation (center highlight) -->
    <a href="form2.php" class="bnav-item bnav-center <?php echo sb_active('form2.php', $current_page); ?>" title="New Quotation">
        <div class="bnav-center-ring">
            <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </div>
        <span class="bnav-label">New Quote</span>
    </a>

    <!-- 4. Customers -->
    <a href="home.php#companies" class="bnav-item" id="bnavCompanies" title="Customers" onclick="if(window.switchTab){switchTab('companies'); return false;}">
        <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
        </svg>
        <span class="bnav-label">Customers</span>
    </a>

    <!-- 5. Products -->
    <a href="home.php#products" class="bnav-item" id="bnavProducts" title="Products" onclick="if(window.switchTab){switchTab('products'); return false;}">
        <svg class="bnav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><line x1="3.27" y1="6.96" x2="12" y2="12.01"/><line x1="12" y1="22.08" x2="12" y2="12"/><line x1="20.73" y1="6.96" x2="12" y2="12.01"/>
        </svg>
        <span class="bnav-label">Products</span>
    </a>

</nav>

<script>
function logout() {
    if (typeof QT !== 'undefined' && QT.confirm) {
        QT.confirm('Are you sure you want to logout?', function() {
            window.location.href = 'logout.php';
        });
    } else {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
}
function showQuotationsTab() {
    if (typeof switchTab === 'function') switchTab('bycompany');
}
function showCompaniesTab() {
    if (typeof switchTab === 'function') switchTab('companies');
}
function showProductsTab() {
    if (typeof switchTab === 'function') switchTab('products');
}
function showRecentTab() {
    if (typeof switchTab === 'function') switchTab('recent');
}
</script>
