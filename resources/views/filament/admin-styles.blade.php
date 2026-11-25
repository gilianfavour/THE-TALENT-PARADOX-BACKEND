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
<style>
    /* Dashboard banner: full width, light */
    .tb-banner { grid-column: 1 / -1; position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.75rem; color: #0a0a0a;
        background: radial-gradient(800px 320px at 100% 0, rgba(200, 16, 46, .10), transparent 60%), #ffffff;
        box-shadow: 0 0 0 1px #e4e4e7, 0 18px 40px -28px rgba(10, 10, 10, .25);
        animation: tb-admin-in .55s cubic-bezier(.2, .7, .2, 1) both; }
    .tb-banner::after { content: ""; position: absolute; right: -40px; bottom: -80px; width: 280px; height: 170px;
        border: 12px solid rgba(200, 16, 46, .07); border-bottom: 0; border-radius: 200px 200px 0 0; pointer-events: none; }
    .tb-banner-top { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; justify-content: space-between; position: relative; z-index: 1; }
    .tb-banner-date { font-size: .8rem; color: #71717a; }
    .tb-banner-title { margin-top: .25rem; font-size: 1.75rem; line-height: 1.15; font-weight: 800; letter-spacing: -.02em; color: #0a0a0a; }
    .tb-banner-sub { margin-top: .35rem; color: #52525b; font-size: .95rem; }
    .tb-banner-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .tb-btn { display: inline-flex; align-items: center; border-radius: .75rem; padding: .6rem 1rem; font-size: .875rem; font-weight: 700; transition: transform .15s, background .15s, box-shadow .15s; }
    .tb-btn:hover { transform: translateY(-1px); }
    .tb-btn-red { background: #c8102e; color: #fff; box-shadow: 0 8px 20px -10px rgba(200, 16, 46, .7); }
    .tb-btn-red:hover { background: #a50d26; }
    .tb-btn-ghost { background: #fff; color: #0a0a0a; box-shadow: inset 0 0 0 1px #d4d4d8; }
    .tb-btn-ghost:hover { box-shadow: inset 0 0 0 1px #a1a1aa; }
    .tb-tasks { position: relative; z-index: 1; margin-top: 1.5rem; display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
    .tb-task { display: flex; align-items: center; gap: .75rem; border-radius: .9rem; padding: .85rem .95rem; background: #fafafa;
        box-shadow: inset 0 0 0 1px #e4e4e7; transition: background .15s, transform .15s, box-shadow .15s; }
    .tb-task:hover { background: #fff; transform: translateY(-1px); box-shadow: inset 0 0 0 1px #fecaca, 0 10px 24px -16px rgba(200, 16, 46, .45); }
    .tb-task-count { min-width: 2.2rem; height: 2.2rem; padding: 0 .5rem; display: inline-flex; align-items: center; justify-content: center;
        border-radius: .7rem; font-weight: 800; font-size: 1rem; }
    .tb-task-count.tb-red { background: #c8102e; color: #fff; animation: tb-admin-glow 2.4s ease-in-out 3; }
    .tb-task-count.tb-dark { background: #0a0a0a; color: #fff; }
    .tb-task-count.tb-zero { background: #f4f4f5; color: #a1a1aa; }
    .tb-task-label { font-size: .82rem; line-height: 1.25; color: #3f3f46; font-weight: 600; }
    .tb-pipeline { position: relative; z-index: 1; margin-top: 1.5rem; }
    .tb-pipeline-title { font-size: .85rem; font-weight: 700; color: #0a0a0a; }
    .tb-pipeline-title span { margin-left: .4rem; color: #71717a; font-weight: 500; }
    .tb-pipeline-bar { margin-top: .6rem; display: flex; gap: 3px; height: .6rem; border-radius: 999px; overflow: hidden; background: #f4f4f5; }
    .tb-pipeline-bar span { background: #c8102e; transform-origin: left; animation: tb-grow-x .9s cubic-bezier(.2, .7, .2, 1) both; }
    .tb-pipeline-bar span:nth-child(even) { background: #0a0a0a; }
    @keyframes tb-grow-x { from { transform: scaleX(0); } to { transform: scaleX(1); } }
    .tb-pipeline-legend { margin-top: .7rem; display: flex; flex-wrap: wrap; gap: .4rem; }
    .tb-pill { display: inline-flex; gap: .35rem; align-items: center; border-radius: 999px; padding: .25rem .65rem; font-size: .75rem;
        background: #f4f4f5; color: #52525b; }
    .tb-pill b { color: #c8102e; }

    /* Login page: light */
    .fi-simple-layout { position: relative; isolation: isolate;
        background: radial-gradient(900px 500px at 0% 0%, rgba(200, 16, 46, .10), transparent 60%),
                    radial-gradient(700px 450px at 100% 100%, rgba(200, 16, 46, .08), transparent 60%), #fafafa !important; }
    .fi-simple-layout::before { content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(rgba(10, 10, 10, .07) 1px, transparent 1px); background-size: 22px 22px; }
    .fi-simple-main { box-shadow: 0 0 0 1px #e4e4e7, 0 30px 70px -35px rgba(10, 10, 10, .35) !important; border-radius: 1.25rem !important;
        animation: tb-admin-in .6s cubic-bezier(.2, .7, .2, 1) both; }
    .tb-login-intro { margin-bottom: 1.25rem; text-align: center; }
    .tb-login-kicker { font-size: .78rem; font-weight: 700; color: #c8102e; }
    .tb-login-lead { margin-top: .3rem; font-size: .92rem; color: #52525b; line-height: 1.45; }
    .tb-login-pills { margin-top: .8rem; display: flex; justify-content: center; gap: .4rem; }
    .tb-login-pills span { border-radius: 999px; padding: .2rem .7rem; font-size: .72rem; font-weight: 700; background: #fef2f2; color: #8f0b1f; }
    .tb-login-pills span:nth-child(2) { background: #0a0a0a; color: #fff; }
    .tb-login-demo { margin-top: 1.1rem; border-radius: .75rem; background: #f4f4f5; padding: .6rem .8rem; text-align: center; font-size: .8rem; color: #3f3f46; }
    .tb-login-demo span { margin-right: .35rem; font-weight: 700; color: #c8102e; }

    @media (prefers-reduced-motion: reduce) {
        .tb-banner, .tb-pipeline-bar span, .tb-task-count, .fi-simple-main { animation: none !important; }
    }
</style>
