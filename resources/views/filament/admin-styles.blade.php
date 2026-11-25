<style>
    /* TalentBridge admin polish: one gentle load-in, red accents, softer surfaces. */
    @keyframes tb-admin-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @keyframes tb-admin-glow { 0%, 100% { box-shadow: 0 0 0 0 rgba(200, 16, 46, 0); } 50% { box-shadow: 0 0 0 4px rgba(200, 16, 46, .12); } }

    .fi-main .fi-wi-stats-overview-stat,
    .fi-main .fi-wi > *,
    .fi-main .fi-ta-ctn,
    .fi-main .fi-section {
        animation: tb-admin-in .55s cubic-bezier(.2, .7, .2, 1) both;
    }
    .fi-main .fi-wi-stats-overview-stat:nth-child(2) { animation-delay: .05s; }
    .fi-main .fi-wi-stats-overview-stat:nth-child(3) { animation-delay: .1s; }
    .fi-main .fi-wi-stats-overview-stat:nth-child(4) { animation-delay: .15s; }
    .fi-main .fi-wi-stats-overview-stat:nth-child(5) { animation-delay: .2s; }
    .fi-main .fi-wi-stats-overview-stat:nth-child(6) { animation-delay: .25s; }

    .fi-wi-stats-overview-stat {
        border-radius: 1rem;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px -18px rgba(200, 16, 46, .55);
    }
    .fi-section, .fi-ta-ctn { border-radius: 1rem; }

    .fi-sidebar-item-active > a,
    .fi-sidebar-item.fi-active > a { box-shadow: inset 3px 0 0 #c8102e; }

    .fi-badge { font-weight: 700; }
    .fi-sidebar-item-badge-ctn .fi-badge,
    .fi-sidebar-item .fi-badge { animation: tb-admin-glow 2.4s ease-in-out 3; }

    .fi-simple-main { border-top: 4px solid #c8102e; border-radius: 1rem; }
    .fi-body { background-image: radial-gradient(circle at 100% 0, rgba(200, 16, 46, .05), transparent 40%); }

    .fi-no-notification, .fi-notification { border-radius: .9rem; }

    @media (prefers-reduced-motion: reduce) {
        .fi-main * { animation: none !important; transition: none !important; }
    }
</style>
