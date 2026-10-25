<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    display: ['"Playfair Display"', 'ui-serif', 'Georgia', 'serif'],
                },
                colors: {
                    primary: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd5', 300: '#fda4af', 400: '#fb7185', 500: '#e11d48', 600: '#be123c', 700: '#9f1239', 800: '#881337', 900: '#6d0f2a', DEFAULT: '#e11d48' },
                    gold:    { 50: '#fdfcf5', 100: '#fcf7e3', 200: '#f8edc1', 300: '#f2de94', 400: '#eacb5f', 500: '#d4af37', 600: '#b8932a', 700: '#957023', 800: '#7a5a23', 900: '#674b21', DEFAULT: '#d4af37' },
                    surface: { 50: '#fafafa', 100: '#f4f4f5', 200: '#e4e4e7', 300: '#d4d4d8', 400: '#a1a1aa', 500: '#71717a', 600: '#52525b', 700: '#3f3f46', 800: '#27272a', 900: '#18181b' },
                },
                boxShadow: {
                    'soft': '0 1px 2px 0 rgba(24,24,27,0.04), 0 1px 3px 0 rgba(24,24,27,0.04)',
                    'soft-lg': '0 4px 16px -4px rgba(24,24,27,0.10), 0 2px 6px -2px rgba(24,24,27,0.06)',
                },
            }
        }
    }
</script>
<style>
    html { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    .font-display { font-family: 'Playfair Display', ui-serif, Georgia, serif; }

    /* ---------- Buttons (shadcn-style primitives) ---------- */
    .btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; white-space:nowrap; border-radius:.65rem; font-weight:600; font-size:.8125rem; line-height:1.1; padding:.65rem 1.1rem; transition:background-color .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease; border:1px solid transparent; cursor:pointer; }
    .btn:disabled { opacity:.5; pointer-events:none; }
    .btn-primary { background:#e11d48; color:#fff; box-shadow:0 1px 2px rgba(0,0,0,.05); }
    .btn-primary:hover { background:#be123c; }
    .btn-secondary { background:#f4f4f5; color:#27272a; }
    .btn-secondary:hover { background:#e4e4e7; }
    .btn-outline { background:#fff; border-color:#e4e4e7; color:#3f3f46; }
    .btn-outline:hover { background:#fafafa; border-color:#d4d4d8; }
    .btn-success { background:#16a34a; color:#fff; }
    .btn-success:hover { background:#15803d; }
    .btn-destructive { background:#dc2626; color:#fff; }
    .btn-destructive:hover { background:#b91c1c; }
    .btn-ghost { background:transparent; color:#52525b; border-color:transparent; }
    .btn-ghost:hover { background:#f4f4f5; color:#27272a; }
    .btn-sm { padding:.4rem .8rem; font-size:.75rem; border-radius:.55rem; }
    .btn-block { width:100%; }

    /* ---------- Surfaces ---------- */
    .card-surface { background:#fff; border:1px solid #ececec; border-radius:1rem; box-shadow: 0 1px 2px rgba(24,24,27,.04); }
    .card-surface-hover:hover { box-shadow: 0 4px 16px -4px rgba(24,24,27,.10), 0 2px 6px -2px rgba(24,24,27,.06); }

    /* ---------- Forms ---------- */
    .input-field, select.input-field, textarea.input-field { width:100%; padding:.68rem .95rem; border:1px solid #e4e4e7; border-radius:.65rem; font-size:.875rem; background:#fff; color:#27272a; transition:border-color .15s, box-shadow .15s; }
    .input-field::placeholder { color:#a1a1aa; }
    .input-field:focus { outline:none; border-color:#e11d48; box-shadow:0 0 0 3px rgba(225,29,72,.12); }
    .label-field { display:block; font-size:.78rem; font-weight:600; color:#3f3f46; margin-bottom:.4rem; letter-spacing:.01em; }

    /* ---------- Badges ---------- */
    .badge { display:inline-flex; align-items:center; border-radius:9999px; padding:.22rem .65rem; font-size:.7rem; font-weight:600; line-height:1.2; }

    /* ---------- Icon actions ---------- */
    .icon-action { display:inline-flex; width:2.25rem; height:2.25rem; align-items:center; justify-content:center; border-radius:.7rem; transition:background-color .15s ease, color .15s ease; }
    .icon-action:hover { background:#f4f4f5; }
    .icon-action.is-danger:hover { background:#fef2f2; }

    /* ---------- Responsive filter bar ---------- */
    .filter-bar { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem; align-items:center; }
    .filter-bar .filter-search { grid-column:1 / -1; }
    .filter-bar .filter-search input { width:100%; }
    .filter-bar select.input-field { width:100% !important; }
    @media (min-width: 768px) {
        .filter-bar { display:flex; flex-wrap:wrap; }
        .filter-bar .filter-search { flex:1 1 220px; grid-column:auto; }
        .filter-bar select.input-field { width:auto !important; }
    }

    /* ---------- Responsive data tables ---------- */
    @media (max-width: 1023px) {
        .responsive-table-wrap { overflow:visible; }
        .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td { display:block; width:100%; }
        .responsive-table thead { display:none; }
        .responsive-table tbody { display:grid; gap:.8rem; padding:.8rem; background:#fafafa; }
        .responsive-table tr { border:1px solid #ececec; border-radius:1rem; background:#fff; box-shadow:0 1px 2px rgba(24,24,27,.04); overflow:hidden; }
        .responsive-table td { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:.5rem 1rem; border:0; min-width:0; text-align:left !important; }
        .responsive-table td::before { content:attr(data-label); flex:0 0 76px; padding-top:.2rem; color:#a1a1aa; font-size:9px; font-weight:700; letter-spacing:.09em; line-height:1.25; text-transform:uppercase; }
        .responsive-table td[data-label=""]::before,
        .responsive-table td.mobile-actions::before,
        .responsive-table td.mobile-select::before { display:none; }
        .responsive-table td.mobile-select { justify-content:flex-end; padding-top:.8rem; padding-bottom:0; }
        .responsive-table td.mobile-actions { justify-content:flex-end; margin-top:.2rem; padding-top:.7rem; padding-bottom:.75rem; border-top:1px solid #f4f4f5; }
        .responsive-table td.mobile-actions > div { width:auto; }
        .responsive-table td > .min-w-0 { flex:1; }
        .responsive-table td .truncate { max-width:100%; }
    }

    /* ---------- Misc decorative ---------- */
    .bg-noise { background-image: radial-gradient(rgba(0,0,0,0.035) 1px, transparent 1px); background-size: 3px 3px; }
    .decorative-blob { filter: blur(60px); }

    ::selection { background:#fecdd3; color:#881337; }

    /* ---------- Loading indicators ---------- */
    body.is-loading { cursor: progress; }
    body.is-loading * { cursor: progress !important; }
    .action-loader-ring {
        width: 52px; height: 52px; border-radius: 9999px;
        border: 4px solid #ffe4e6; border-top-color: #e11d48;
        animation: action-loader-spin .7s linear infinite;
    }
    @keyframes action-loader-spin { to { transform: rotate(360deg); } }
    .btn[data-loading="true"] { pointer-events: none; }
</style>
<script>
    /* Loads a script trying multiple CDN mirrors in order; calls onready() once one succeeds. */
    window.loadScriptWithFallback = function (urls, onready) {
        var i = 0;
        function tryNext() {
            if (i >= urls.length) {
                console.error('Could not load script from any CDN mirror:', urls);
                return;
            }
            var s = document.createElement('script');
            s.src = urls[i++];
            s.onload = function () { if (onready) onready(); };
            s.onerror = tryNext;
            document.head.appendChild(s);
        }
        tryNext();
    };
</script>
