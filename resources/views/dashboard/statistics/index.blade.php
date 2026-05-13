<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.statistics') }}</span>
    </x-slot>

    <div class="sbl-content">

        @if (!$restaurant)
            <div class="qp-card">
                <div class="qp-card-body">
                    <p style="color:var(--muted);font-size:13px">{{ __('menu_owner.statistics.create_menu_first') }}</p>
                    <a href="{{ route('menu-owner.restaurant.index') }}" class="st-btn st-btn--primary" style="margin-top:12px;display:inline-flex">
                        {{ __('menu_owner.common.create') }} {{ __('menu_owner.menus.title') }}
                    </a>
                </div>
            </div>
        @else
        <div class="st-stack">

        {{-- ── Header + period tabs ─────────────────────────── --}}
        <div class="st-page-head">
            <div>
                <h1 class="sbl-page-title" style="margin:0">{{ __('menu_owner.statistics.title') }}</h1>
                <p class="st-head-sub">{{ __('menu_owner.statistics.menu_link') }}: <a href="{{ $menuUrl }}" target="_blank" class="st-menu-link">{{ $menuUrl }}</a></p>
            </div>
            <div class="st-period-tabs">
                @foreach(['7' => __('menu_owner.statistics.period_7'), '30' => __('menu_owner.statistics.period_30'), '90' => __('menu_owner.statistics.period_90'), 'all' => __('menu_owner.statistics.period_all')] as $val => $label)
                    <a href="?period={{ $val }}" class="st-period-tab {{ $period === $val ? 'st-period-tab--active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Hero KPI cards ───────────────────────────────── --}}
        <div class="st-kpi-grid">

            {{-- Visits --}}
            <div class="qp-card st-kpi-card">
                <div class="qp-card-body">
                    <div class="st-kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="st-kpi-body">
                        <p class="st-kpi-label">{{ __('menu_owner.statistics.total_visits') }}</p>
                        <p class="st-kpi-value">{{ number_format($periodViews) }}</p>
                        @if($comparisons['views'] !== null)
                            <span class="st-badge {{ $comparisons['views'] >= 0 ? 'st-badge--up' : 'st-badge--down' }}">
                                {{ $comparisons['views'] >= 0 ? '↑' : '↓' }} {{ abs($comparisons['views']) }}%
                                <span class="st-badge-sub">{{ __('menu_owner.statistics.vs_previous') }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Unique visitors --}}
            <div class="qp-card st-kpi-card">
                <div class="qp-card-body">
                    <div class="st-kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="st-kpi-body">
                        <p class="st-kpi-label">{{ __('menu_owner.statistics.unique_visitors') }}</p>
                        <p class="st-kpi-value">{{ number_format($periodUnique) }}</p>
                        @if($comparisons['unique'] !== null)
                            <span class="st-badge {{ $comparisons['unique'] >= 0 ? 'st-badge--up' : 'st-badge--down' }}">
                                {{ $comparisons['unique'] >= 0 ? '↑' : '↓' }} {{ abs($comparisons['unique']) }}%
                                <span class="st-badge-sub">{{ __('menu_owner.statistics.vs_previous') }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- WhatsApp orders --}}
            <div class="qp-card st-kpi-card">
                <div class="qp-card-body">
                    <div class="st-kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div class="st-kpi-body">
                        <p class="st-kpi-label">{{ __('menu_owner.statistics.whatsapp_orders') }}</p>
                        <p class="st-kpi-value">{{ number_format($periodWhatsapp) }}</p>
                        @if($comparisons['whatsapp'] !== null)
                            <span class="st-badge {{ $comparisons['whatsapp'] >= 0 ? 'st-badge--up' : 'st-badge--down' }}">
                                {{ $comparisons['whatsapp'] >= 0 ? '↑' : '↓' }} {{ abs($comparisons['whatsapp']) }}%
                                <span class="st-badge-sub">{{ __('menu_owner.statistics.vs_previous') }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Avg time spent --}}
            <div class="qp-card st-kpi-card">
                <div class="qp-card-body">
                    <div class="st-kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="st-kpi-body">
                        <p class="st-kpi-label">{{ __('menu_owner.statistics.avg_time_spent') }}</p>
                        <p class="st-kpi-value">
                            @if($averageTimeSpent > 0)
                                @php $m = floor($averageTimeSpent/60); $s = $averageTimeSpent % 60; @endphp
                                {{ $m > 0 ? $m.'m '.$s.'s' : $s.'s' }}
                            @else
                                {{ __('menu_owner.common.n_a') }}
                            @endif
                        </p>
                        <p class="st-kpi-sub">{{ __('menu_owner.statistics.sessions_with_recorded_exit', ['count' => number_format($sessionsWithTimeSpent)]) }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Visits over time chart ──────────────────────── --}}
        <div class="qp-card">
            <div class="qp-card-body">
                <div class="st-chart-head">
                    <p class="st-section-title">{{ __('menu_owner.statistics.chart_title') }}</p>
                    <span class="st-chart-period">{{ ['7' => __('menu_owner.statistics.period_7'), '30' => __('menu_owner.statistics.period_30'), '90' => __('menu_owner.statistics.period_90'), 'all' => __('menu_owner.statistics.period_all')][$period] }}</span>
                </div>
                @if($visitsPerDay->isEmpty())
                    <p class="st-empty">{{ __('menu_owner.statistics.no_data_yet') }}</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="visitsChart"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Secondary metrics ───────────────────────────── --}}
        <div class="st-secondary-grid">

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.page_views_today') }}</p>
                    <p class="st-sec-value">{{ number_format($viewsToday) }}</p>
                </div>
            </div>

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.page_views_this_week') }}</p>
                    <p class="st-sec-value">{{ number_format($viewsThisWeek) }}</p>
                </div>
            </div>

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.page_views_this_month') }}</p>
                    <p class="st-sec-value">{{ number_format($viewsThisMonth) }}</p>
                </div>
            </div>

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.bounce_rate') }}</p>
                    <p class="st-sec-value">{{ $bounceRate }}%</p>
                </div>
            </div>

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.avg_page_views_per_session') }}</p>
                    <p class="st-sec-value">{{ $avgPageViewsPerSession }}</p>
                </div>
            </div>

            <div class="qp-card st-secondary-card">
                <div class="qp-card-body">
                    <p class="st-sec-label">{{ __('menu_owner.statistics.qr_visits') }}</p>
                    <p class="st-sec-value">{{ number_format($qrVisits) }}</p>
                    <p class="st-sec-sub">{{ __('menu_owner.statistics.qr_visits_desc') }}</p>
                </div>
            </div>

        </div>

        {{-- ── Breakdowns ───────────────────────────────────── --}}
        <div class="st-breakdown-grid">

            {{-- Device --}}
            <div class="qp-card">
                <div class="qp-card-body">
                    <p class="st-section-title">{{ __('menu_owner.statistics.device_breakdown') }}</p>
                    @if(!empty($deviceBreakdown))
                        @php $devTotal = array_sum($deviceBreakdown); @endphp
                        <div class="st-bars">
                            @foreach($deviceBreakdown as $device => $count)
                                @php $pct = $devTotal > 0 ? round(($count / $devTotal) * 100, 1) : 0; @endphp
                                <div class="st-bar-row">
                                    <div class="st-bar-label">
                                        <span class="st-bar-name">{{ ucfirst($device) }}</span>
                                        <span class="st-bar-pct">{{ $pct }}%</span>
                                    </div>
                                    <div class="st-bar-track">
                                        <div class="st-bar-fill" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="st-empty">{{ __('menu_owner.statistics.no_device_data') }}</p>
                    @endif
                </div>
            </div>

            {{-- Browser --}}
            <div class="qp-card">
                <div class="qp-card-body">
                    <p class="st-section-title">{{ __('menu_owner.statistics.top_browsers') }}</p>
                    @if(!empty($browserBreakdown))
                        @php $bTotal = array_sum($browserBreakdown); @endphp
                        <div class="st-bars">
                            @foreach($browserBreakdown as $browser => $count)
                                @php $pct = $bTotal > 0 ? round(($count / $bTotal) * 100, 1) : 0; @endphp
                                <div class="st-bar-row">
                                    <div class="st-bar-label">
                                        <span class="st-bar-name">{{ $browser }}</span>
                                        <span class="st-bar-pct">{{ $pct }}%</span>
                                    </div>
                                    <div class="st-bar-track">
                                        <div class="st-bar-fill" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="st-empty">{{ __('menu_owner.statistics.no_browser_data') }}</p>
                    @endif
                </div>
            </div>

            {{-- OS --}}
            <div class="qp-card">
                <div class="qp-card-body">
                    <p class="st-section-title">{{ __('menu_owner.statistics.top_os') }}</p>
                    @if(!empty($osBreakdown))
                        @php $oTotal = array_sum($osBreakdown); @endphp
                        <div class="st-bars">
                            @foreach($osBreakdown as $os => $count)
                                @php $pct = $oTotal > 0 ? round(($count / $oTotal) * 100, 1) : 0; @endphp
                                <div class="st-bar-row">
                                    <div class="st-bar-label">
                                        <span class="st-bar-name">{{ $os }}</span>
                                        <span class="st-bar-pct">{{ $pct }}%</span>
                                    </div>
                                    <div class="st-bar-track">
                                        <div class="st-bar-fill" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="st-empty">{{ __('menu_owner.statistics.no_os_data') }}</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── Recent visitors table ────────────────────────── --}}
        <div class="qp-card">
            <div class="qp-card-body">
                <p class="st-section-title" style="margin-bottom:16px">{{ __('menu_owner.statistics.recent_visitors') }}</p>
                @if($statistics->count() > 0)
                    <div class="st-table-wrap">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th>{{ __('menu_owner.statistics.viewed_at') }}</th>
                                    <th>{{ __('menu_owner.statistics.device') }}</th>
                                    <th>{{ __('menu_owner.statistics.browser_col') }}</th>
                                    <th>{{ __('menu_owner.statistics.os_col') }}</th>
                                    <th>{{ __('menu_owner.statistics.time_spent') }}</th>
                                    <th>{{ __('menu_owner.statistics.page_views') }}</th>
                                    <th>{{ __('menu_owner.statistics.qr_col') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics as $stat)
                                    <tr>
                                        <td>{{ $stat->viewed_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="st-device-badge st-device-badge--{{ $stat->device_type ?? 'unknown' }}">
                                                {{ ucfirst($stat->device_type ?? __('menu_owner.statistics.unknown')) }}
                                            </span>
                                        </td>
                                        <td style="color:var(--muted);font-size:12px">{{ $stat->browser ?? '—' }}</td>
                                        <td style="color:var(--muted);font-size:12px">{{ $stat->os ?? '—' }}</td>
                                        <td>
                                            @if($stat->time_spent)
                                                @php $m = floor($stat->time_spent/60); $s = $stat->time_spent % 60; @endphp
                                                {{ $m > 0 ? $m.'m '.$s.'s' : $s.'s' }}
                                            @else
                                                <span style="color:var(--muted)">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $stat->page_views }}</td>
                                        <td>
                                            @if($stat->via_qr)
                                                <span class="st-qr-badge">QR</span>
                                            @else
                                                <span style="color:var(--muted)">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="st-empty">{{ __('menu_owner.statistics.no_visitors_yet') }}</p>
                @endif
            </div>
        </div>

        {{-- ── Menu link copy ───────────────────────────────── --}}
        <div class="qp-card">
            <div class="qp-card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;flex-shrink:0">{{ __('menu_owner.statistics.menu_link_title') }}</p>
                <input id="menuUrlInput" type="text" readonly value="{{ $menuUrl }}"
                       style="flex:1;min-width:200px;padding:7px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--muted);background:var(--surface);outline:none">
                <button type="button" onclick="copyMenuUrl()" class="st-btn st-btn--primary" style="flex-shrink:0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    {{ __('menu_owner.common.copy_link') }}
                </button>
                <span id="copyMsg" style="font-size:12px;color:#16a34a;display:none">{{ __('menu_owner.common.link_copied') }}</span>
            </div>
        </div>

        </div>{{-- /st-stack --}}
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        /* ── Statistics page ── */
        .st-stack {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .st-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .st-head-sub { font-size: 12px; color: var(--muted); margin: 4px 0 0; }
        .st-menu-link { color: var(--olive-deep); text-decoration: none; }
        .st-menu-link:hover { text-decoration: underline; }

        /* Period tabs */
        .st-period-tabs { display: flex; gap: 4px; flex-wrap: wrap; }
        .st-period-tab {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            color: var(--muted);
            border: 1px solid var(--border);
            background: var(--surface);
            transition: all .15s;
        }
        .st-period-tab:hover { color: var(--ink); border-color: var(--ink); }
        .st-period-tab--active {
            background: var(--olive-deep, #4a6741);
            color: #fff;
            border-color: var(--olive-deep, #4a6741);
        }

        /* KPI grid */
        .st-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        .st-kpi-card .qp-card-body { display: flex; align-items: flex-start; gap: 14px; }
        .st-kpi-icon {
            width: 42px; height: 42px;
            border-radius: 11px;
            background: var(--olive-pale, #eef3ee);
            color: var(--olive-deep, #4a6741);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .st-kpi-icon svg { width: 18px; height: 18px; }
        .st-kpi-body { flex: 1; min-width: 0; }
        .st-kpi-label { font-size: 12px; color: var(--muted); margin: 0 0 4px; }
        .st-kpi-value { font-size: 24px; font-weight: 700; color: var(--ink); margin: 0 0 6px; line-height: 1; }
        .st-kpi-sub { font-size: 11px; color: var(--muted); margin: 4px 0 0; }

        /* Change badges */
        .st-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .st-badge-sub { font-weight: 400; font-size: 10px; opacity: .8; }
        .st-badge--up { background: #dcfce7; color: #15803d; }
        .st-badge--down { background: #fee2e2; color: #b91c1c; }

        /* Chart */
        .st-chart-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .st-chart-period {
            font-size: 11px;
            color: var(--muted);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2px 10px;
        }

        /* Secondary grid */
        .st-secondary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
        }
        .st-secondary-card .qp-card-body { padding: 16px; }
        .st-sec-label { font-size: 11px; color: var(--muted); margin: 0 0 6px; line-height: 1.3; }
        .st-sec-value { font-size: 20px; font-weight: 700; color: var(--ink); margin: 0; }
        .st-sec-sub { font-size: 10px; color: var(--muted); margin: 4px 0 0; }

        /* Breakdown grid */
        .st-breakdown-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .st-section-title { font-size: 13px; font-weight: 700; color: var(--ink); margin: 0 0 14px; }
        .st-bars { display: flex; flex-direction: column; gap: 12px; }
        .st-bar-row {}
        .st-bar-label { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .st-bar-name { font-size: 12px; font-weight: 500; color: var(--ink); }
        .st-bar-pct { font-size: 12px; color: var(--muted); }
        .st-bar-track {
            height: 6px;
            background: var(--border, #e5e7eb);
            border-radius: 3px;
            overflow: hidden;
        }
        .st-bar-fill {
            height: 100%;
            background: var(--olive-deep, #4a6741);
            border-radius: 3px;
            transition: width .4s ease;
            min-width: 2px;
        }

        /* Table */
        .st-table-wrap { overflow-x: auto; }
        .st-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .st-table th {
            padding: 8px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .st-table td {
            padding: 10px 12px;
            color: var(--ink);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .st-table tbody tr:hover { background: var(--surface); }
        .st-table tbody tr:last-child td { border-bottom: none; }

        .st-device-badge {
            display: inline-flex;
            align-items: center;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .st-device-badge--mobile  { background: #dbeafe; color: #1d4ed8; }
        .st-device-badge--tablet  { background: #ede9fe; color: #6d28d9; }
        .st-device-badge--desktop { background: #dcfce7; color: #15803d; }
        .st-device-badge--unknown { background: var(--surface); color: var(--muted); }

        .st-qr-badge {
            display: inline-flex;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            background: rgba(216,170,99,0.15);
            color: #B8893F;
            border: 1px solid rgba(216,170,99,0.3);
        }

        /* Buttons */
        .st-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
            font-family: inherit;
        }
        .st-btn--primary { background: var(--olive-deep, #4a6741); color: #fff; }
        .st-btn--primary:hover { opacity: .88; }

        .st-empty { font-size: 13px; color: var(--muted); margin: 20px 0; text-align: center; }

        /* Responsive */
        @media (max-width: 1024px) {
            .st-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .st-secondary-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .st-page-head { flex-direction: column; }
            .st-kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .st-secondary-grid { grid-template-columns: repeat(2, 1fr); }
            .st-breakdown-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .st-kpi-grid { grid-template-columns: 1fr; }
            .st-secondary-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
    <script>
        @if(!$visitsPerDay->isEmpty())
        (function () {
            const raw = @json($visitsPerDay->map(fn($r) => ['date' => $r->date, 'visits' => (int) $r->visits]));
            const labels = raw.map(r => r.date);
            const data   = raw.map(r => r.visits);

            const ctx = document.getElementById('visitsChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: '{{ __('menu_owner.statistics.total_visits') }}',
                        data,
                        borderColor: '#D8AA63',
                        backgroundColor: 'rgba(216,170,99,0.12)',
                        borderWidth: 2,
                        pointRadius: data.length > 30 ? 0 : 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#D8AA63',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            titleColor: '#111',
                            bodyColor: '#6b7280',
                            padding: 10,
                            callbacks: {
                                title: items => items[0].label,
                                label: item => ' ' + item.parsed.y + ' {{ __('menu_owner.statistics.total_visits') }}',
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#9ca3af',
                                font: { size: 11 },
                                maxTicksLimit: 10,
                                maxRotation: 0,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' },
                            ticks: {
                                color: '#9ca3af',
                                font: { size: 11 },
                                precision: 0,
                            },
                        },
                    },
                },
            });
        })();
        @endif

        function copyMenuUrl() {
            const input = document.getElementById('menuUrlInput');
            const msg   = document.getElementById('copyMsg');
            navigator.clipboard.writeText(input.value).then(() => {
                msg.style.display = 'inline';
                setTimeout(() => { msg.style.display = 'none'; }, 3000);
            });
        }
    </script>
    @endpush

</x-sidebar-layout>
