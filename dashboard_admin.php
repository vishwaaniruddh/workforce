<?php 
session_start();
include('config.php');
date_default_timezone_set('Asia/Kolkata');

// ─── API HANDLER ─────────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'get_dashboard_data') {
    header('Content-Type: application/json');
    $type = isset($_POST['type']) ? $_POST['type'] : 'all';
    $today = date('Y-m-d');
    $firstDate = date('Y-m-01');
    $lastDate = date('Y-m-t');
    $yearStart = date('Y-01-01');
    $yearEnd = date('Y-12-31');
    
    $response = array();
    
    // KPI Stats
    if ($type === 'all' || $type === 'stats') {
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(Lead_id) FROM Leads_table WHERE date(Creation)='$today'"));
        $response['today_leads'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(Lead_id) FROM Leads_table WHERE date(Creation) BETWEEN '$firstDate' AND '$lastDate'"));
        $response['month_leads'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(LeadId) FROM LeadDelegation WHERE LeadId IN (SELECT Lead_id FROM Leads_table WHERE Status='5') AND date(DelegatedTIme)='$today'"));
        $response['today_converted'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(LeadId) FROM LeadDelegation WHERE LeadId IN (SELECT Lead_id FROM Leads_table WHERE Status='5') AND date(DelegatedTIme) BETWEEN '$firstDate' AND '$lastDate'"));
        $response['month_converted'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(Lead_id) FROM Leads_table WHERE Status='3' OR Excel='1'"));
        $response['suspended_leads'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(mem_id) FROM Members"));
        $response['total_members'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(Lead_id) FROM Leads_table"));
        $response['total_leads'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(LeadId) FROM LeadDelegation WHERE LeadId IN (SELECT Lead_id FROM Leads_table WHERE Status='5') AND date(DelegatedTIme) BETWEEN '$yearStart' AND '$yearEnd'"));
        $response['year_converted'] = (int)($r ? $r[0] : 0);
    
        $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(Lead_id) FROM Leads_table WHERE Status='4'"));
        $response['pipeline'] = (int)($r ? $r[0] : 0);
    
        $ml = $response['month_leads'];
        $mc = $response['month_converted'];
        $response['conversion_rate'] = ($ml > 0) ? round(($mc / $ml) * 100, 1) : 0;
    }
    
    // Monthly Leads
    if ($type === 'all' || $type === 'monthly_leads') {
        $chart = array();
        $sql = "SELECT DATE_FORMAT(Creation,'%b %Y') as mo, DATE_FORMAT(Creation,'%Y-%m') as ym, COUNT(Lead_id) as cnt FROM Leads_table WHERE Creation >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC";
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) { $chart[] = array('month' => $row['mo'], 'leads' => (int)$row['cnt']); }
        $response['monthly_leads'] = $chart;
    }
    
    // Monthly Conversions
    if ($type === 'all' || $type === 'monthly_converted') {
        $chart = array();
        $sql = "SELECT DATE_FORMAT(ld.DelegatedTIme,'%b %Y') as mo, DATE_FORMAT(ld.DelegatedTIme,'%Y-%m') as ym, COUNT(ld.LeadId) as cnt FROM LeadDelegation ld INNER JOIN Leads_table lt ON lt.Lead_id = ld.LeadId AND lt.Status='5' WHERE ld.DelegatedTIme >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC";
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) { $chart[] = array('month' => $row['mo'], 'converted' => (int)$row['cnt']); }
        $response['monthly_converted'] = $chart;
    }
    
    // Lead Status Distribution
    if ($type === 'all' || $type === 'status_dist') {
        $labels = array('1' => 'New Lead', '2' => 'Presentation', '3' => 'Suspended', '4' => 'Payment Received', '5' => 'Converted');
        $dist = array();
        $res = mysqli_query($conn, "SELECT Status, COUNT(Lead_id) as cnt FROM Leads_table GROUP BY Status");
        while ($row = mysqli_fetch_assoc($res)) { $lbl = isset($labels[$row['Status']]) ? $labels[$row['Status']] : ('Status '.$row['Status']); $dist[] = array('status' => $lbl, 'count' => (int)$row['cnt']); }
        $response['status_dist'] = $dist;
    }
    
    // Daily Leads
    if ($type === 'all' || $type === 'daily') {
        $daily = array();
        $sql = "SELECT date(Creation) as d, COUNT(Lead_id) as cnt FROM Leads_table WHERE date(Creation) BETWEEN '$firstDate' AND '$lastDate' GROUP BY d ORDER BY d ASC";
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) { $daily[] = array('date' => date('d M', strtotime($row['d'])), 'count' => (int)$row['cnt']); }
        $response['daily_leads'] = $daily;
    }
    
    // Recent Leads
    if ($type === 'all' || $type === 'recent') {
        $recent = array();
        $sql = "SELECT Lead_id, FirstName, LastName, MobileNumber, City, Status, Creation FROM Leads_table ORDER BY Creation DESC LIMIT 8";
        $res = mysqli_query($conn, $sql);
        $statusMap = array('1' => 'New', '2' => 'Presentation', '3' => 'Suspended', '4' => 'Payment Recv.', '5' => 'Converted');
        while ($row = mysqli_fetch_assoc($res)) { $row['Status_label'] = isset($statusMap[$row['Status']]) ? $statusMap[$row['Status']] : $row['Status']; $row['Creation_fmt'] = date('d M Y', strtotime($row['Creation'])); $recent[] = $row; }
        $response['recent_leads'] = $recent;
    }
    
    echo json_encode($response);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <?php include("header.php");?>
   <style>
    /* ══════════════════════════════════════════════════════════
       CRM Dashboard – Premium Dark Design
    ══════════════════════════════════════════════════════════ */
    :root {
        --accent:    #6c63ff;
        --accent2:   #00d4aa;
        --accent3:   #ff6b6b;
        --accent4:   #ffa94d;
        --card-bg:   #1a1d2e;
        --card-border:#2a2d42;
        --text-primary:#e8e9f3;
        --text-muted: #8b8fa8;
        --bg-main:   #0f1120;
        --success:   #00d4aa;
        --danger:    #ff6b6b;
        --warning:   #ffa94d;
        --info:      #4fc3f7;
    }
    body { background: var(--bg-main) !important; }
    .admin-content { background: var(--bg-main) !important; }

    /* ── Hero Banner ── */
    .dash-hero {
        background: linear-gradient(135deg, #1a1d2e 0%, #0f1120 40%, #0d1035 100%);
        border-bottom: 1px solid var(--card-border);
        padding: 28px 32px 80px;
        position: relative;
        overflow: hidden;
    }
    .dash-hero::before {
        content:'';
        position: absolute;
        top: -80px; right: -80px;
        width: 320px; height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(108,99,255,.25) 0%, transparent 70%);
        pointer-events: none;
    }
    .dash-hero::after {
        content:'';
        position: absolute;
        bottom: -100px; left: 30%;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(0,212,170,.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .hero-greeting { font-size: 1.1rem; color: var(--text-muted); font-weight: 400; }
    .hero-title    { font-size: 1.85rem; color: var(--text-primary); font-weight: 700; margin: 2px 0 6px; }
    .hero-date     { font-size: .85rem; color: var(--text-muted); }
    .hero-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(108,99,255,.18); border: 1px solid rgba(108,99,255,.4);
        color: #a99fff; border-radius: 50px; padding: 5px 14px;
        font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
    }
    .pulse-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--accent2);
        animation: pulse-anim 1.8s infinite;
    }
    @keyframes pulse-anim {
        0%,100%{ opacity:1; transform: scale(1); }
        50%{ opacity:.5; transform: scale(1.4); }
    }

    /* ── KPI Cards ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-top: -30px;
        padding: 0 24px 24px;
        position: relative;
        z-index: 10;
    }
    .kpi-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 14px 16px;
        cursor: pointer;
        transition: transform .2s, border-color .2s, box-shadow .2s;
        text-decoration: none !important;
        display: block;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        border-color: rgba(108,99,255,.5);
        box-shadow: 0 8px 24px rgba(0,0,0,.2);
        text-decoration: none;
    }

    .kpi-header {
        display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;
    }

    .kpi-icon {
        width: 28px; height: 28px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .kpi-icon.purple  { background: rgba(108,99,255,.18); color: #a99fff; }
    .kpi-icon.teal    { background: rgba(0,212,170,.18);  color: #00d4aa; }
    .kpi-icon.coral   { background: rgba(255,107,107,.18);color: #ff8a8a; }
    .kpi-icon.amber   { background: rgba(255,169,77,.18); color: #ffc07a; }
    .kpi-icon.blue    { background: rgba(79,195,247,.18); color: #4fc3f7; }
    .kpi-icon.green   { background: rgba(74,222,128,.18); color: #4ade80; }
    .kpi-icon.rose    { background: rgba(251,113,133,.18);color: #fb7185; }
    .kpi-icon.indigo  { background: rgba(129,140,248,.18);color: #818cf8; }
    .kpi-icon.orange  { background: rgba(251,146,60,.18); color: #fb923c; }

    .kpi-value {
        font-size: 1.4rem; font-weight: 800;
        color: var(--text-primary); line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .kpi-value.loading { opacity:.3; }
    .kpi-label { font-size: .68rem; text-transform: uppercase; letter-spacing:.05em; color: var(--text-muted); font-weight: 600; }
    .kpi-badge {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: .65rem; font-weight: 600; padding: 2px 6px; border-radius: 12px; margin-top: 6px;
    }
    .kpi-badge.up   { background: rgba(0,212,170,.15); color: #00d4aa; }
    .kpi-badge.down { background: rgba(255,107,107,.15); color: #ff6b6b; }
    .kpi-badge.neutral { background: rgba(139,143,168,.15); color: #8b8fa8; }

    /* ── Section Layout ── */
    .dash-section { padding: 0 24px 24px; }
    .section-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 16px;
    }
    .section-title {
        font-size: 1rem; font-weight: 700; color: var(--text-primary);
        display: flex; align-items: center; gap: 8px;
    }
    .section-title i { color: var(--accent); font-size: 1.1rem; }

    /* ── Chart Cards ── */
    .chart-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 14px;
        padding: 20px 22px;
        height: 100%;
    }
    .chart-card .ct { color: var(--text-muted); font-size: .78rem; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
    .chart-card .cv { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
    .chart-tabs {
        display: flex; gap: 6px; background: rgba(255,255,255,.05);
        border-radius: 8px; padding: 4px;
    }
    .chart-tab {
        padding: 5px 14px; border-radius: 6px; font-size: .78rem; font-weight: 600;
        color: var(--text-muted); cursor: pointer; transition: all .2s; border: none; background: none;
    }
    .chart-tab.active { background: var(--accent); color: #fff; }

    /* ── Table ── */
    .crm-table { width: 100%; border-collapse: collapse; }
    .crm-table th {
        font-size: .7rem; text-transform: uppercase; letter-spacing: .08em;
        color: var(--text-muted); padding: 10px 16px; border-bottom: 1px solid var(--card-border);
        font-weight: 600; text-align: left;
    }
    .crm-table td {
        padding: 12px 16px; border-bottom: 1px solid rgba(42,45,66,.6);
        font-size: .85rem; color: var(--text-primary); vertical-align: middle;
    }
    .crm-table tr:hover td { background: rgba(108,99,255,.05); }
    .crm-table tr:last-child td { border-bottom: none; }
    .status-pill {
        display: inline-flex; padding: 3px 10px; border-radius: 20px;
        font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing:.04em;
    }
    .s-new          { background:rgba(108,99,255,.2);  color:#a99fff; }
    .s-presentation { background:rgba(79,195,247,.2);  color:#4fc3f7; }
    .s-suspended    { background:rgba(255,107,107,.2); color:#ff8a8a; }
    .s-payment      { background:rgba(255,169,77,.2);  color:#ffc07a; }
    .s-converted    { background:rgba(0,212,170,.2);   color:#00d4aa; }

    /* ── Progress bars ── */
    .prog-bar-wrap { background: rgba(255,255,255,.07); border-radius: 4px; height: 6px; overflow: hidden; }
    .prog-bar { height: 100%; border-radius: 4px; transition: width 1s cubic-bezier(.4,0,.2,1); }

    /* ── Funnel / Mini Stats ── */
    .funnel-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid rgba(42,45,66,.6); }
    .funnel-item:last-child { border-bottom: none; }
    .funnel-icon { width: 36px; height: 36px; border-radius: 9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .funnel-label { font-size: .82rem; color: var(--text-muted); }
    .funnel-value { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); }
    .funnel-right { margin-left: auto; text-align: right; }

    /* ── Scroll container ── */
    .table-scroll { overflow-x: auto; }

    /* ── Sparkline row ── */
    .spark-row { display: flex; gap: 2px; align-items: flex-end; height: 36px; }
    .spark-bar { flex: 1; border-radius: 2px 2px 0 0; min-height: 4px; background: rgba(108,99,255,.4); transition: background .2s; }
    .spark-bar:hover { background: var(--accent); }

    /* ── Loader ── */
    .dash-loader {
        position: fixed; inset: 0; background: var(--bg-main);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999; transition: opacity .4s;
    }
    .dash-loader.hide { opacity: 0; pointer-events: none; }
    .loader-ring {
        width: 50px; height: 50px; border-radius: 50%;
        border: 3px solid rgba(108,99,255,.2);
        border-top-color: var(--accent);
        animation: spin .8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Refresh btn ── */
    .btn-refresh {
        background: rgba(108,99,255,.15); border: 1px solid rgba(108,99,255,.35);
        color: #a99fff; border-radius: 8px; padding: 6px 14px;
        font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-refresh:hover { background: rgba(108,99,255,.3); }
    .btn-refresh.spinning i { animation: spin .6s linear infinite; }

    /* ── Responsive tweaks ── */
    @media(max-width:768px) {
        .admin-header {
            z-index: 1050 !important;
            position: relative;
        }
        .admin-header .sidebar-toggle {
            z-index: 1051 !important;
            pointer-events: auto !important;
        }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); padding: 0 14px 14px; }
        .dash-section { padding: 0 14px 14px; }
        .dash-hero { padding: 20px 18px 70px; }
    }
   </style>
</head>
<body class="sidebar-pinned">
<?php include("vertical_menu.php") ?>

<!-- Page Loader -->
<div class="dash-loader" id="dashLoader">
    <div class="loader-ring"></div>
</div>

<main class="admin-main">
    <!-- ── Site Header ── -->
    <header class="admin-header">
        <a href="#" class="sidebar-toggle" data-toggleclass="sidebar-open" data-target="body"></a>
        <nav class="mr-auto my-auto">
            <ul class="nav align-items-center">
                
            </ul>
        </nav>
        <nav class="ml-auto">
            <ul class="nav align-items-center">
                <li class="nav-item mr-2">
                    <button class="btn-refresh" id="refreshBtn" onclick="loadDashboard()">
                        <i class="mdi mdi-refresh"></i> Refresh
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar avatar-sm avatar-online">
                            <span class="avatar-title rounded-circle bg-dark">V</span>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#">Add Account</a>
                        <a class="dropdown-item" href="#">Reset Password</a>
                        <a class="dropdown-item" href="#">Help</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <section class="admin-content">

        <!-- ── HERO ── -->
        <div class="dash-hero">
            <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px">
                <div>
                    <div class="hero-greeting">Good <?php echo date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening'); ?>, Admin 👋</div>
                    <h1 class="hero-title">CRM Dashboard</h1>
                    <div class="hero-date"><?php echo date('l, d F Y — H:i A'); ?></div>
                </div>
                <div class="d-flex align-items-center gap-2" style="gap:10px">
                    <div class="hero-pill"><span class="pulse-dot"></span>Live Data</div>
                </div>
            </div>
        </div>

        <!-- ── KPI CARDS ── -->
        <div class="kpi-grid">
            <a href="prospect_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Today's Leads</div>
                    <div class="kpi-icon purple"><i class="mdi mdi-account-plus"></i></div>
                </div>
                <div class="kpi-value" id="kpi-today-leads">—</div>
                <div class="kpi-badge neutral" id="badge-today-leads">Loading…</div>
            </a>
            <a href="prospect_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Monthly Leads</div>
                    <div class="kpi-icon teal"><i class="mdi mdi-calendar-month"></i></div>
                </div>
                <div class="kpi-value" id="kpi-month-leads">—</div>
                <div class="kpi-badge neutral" id="badge-month-leads">Loading…</div>
            </a>
            <a href="Members_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Today's Converted</div>
                    <div class="kpi-icon coral"><i class="mdi mdi-check-decagram"></i></div>
                </div>
                <div class="kpi-value" id="kpi-today-conv">—</div>
                <div class="kpi-badge neutral" id="badge-today-conv">Loading…</div>
            </a>
            <a href="Members_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Monthly Converted</div>
                    <div class="kpi-icon amber"><i class="mdi mdi-trending-up"></i></div>
                </div>
                <div class="kpi-value" id="kpi-month-conv">—</div>
                <div class="kpi-badge neutral" id="badge-month-conv">Loading…</div>
            </a>
            <a href="prospect_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">In Pipeline</div>
                    <div class="kpi-icon blue"><i class="mdi mdi-filter-variant"></i></div>
                </div>
                <div class="kpi-value" id="kpi-pipeline">—</div>
                <div class="kpi-badge neutral">Payment Received</div>
            </a>
            <a href="Members_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Total Members</div>
                    <div class="kpi-icon green"><i class="mdi mdi-account-group"></i></div>
                </div>
                <div class="kpi-value" id="kpi-total-members">—</div>
                <div class="kpi-badge neutral">All Time</div>
            </a>
            <a href="suspendLead_view.php" class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-label">Suspended Leads</div>
                    <div class="kpi-icon rose"><i class="mdi mdi-account-off"></i></div>
                </div>
                <div class="kpi-value" id="kpi-suspended">—</div>
                <div class="kpi-badge neutral">Requires Attention</div>
            </a>
            <div class="kpi-card" style="cursor:default">
                <div class="kpi-header">
                    <div class="kpi-label">Conversion Rate</div>
                    <div class="kpi-icon indigo"><i class="mdi mdi-percent"></i></div>
                </div>
                <div class="kpi-value" id="kpi-conv-rate">—<span style="font-size:1rem;font-weight:400">%</span></div>
                <div class="kpi-badge neutral">This Month</div>
            </div>
        </div>

        <!-- ── MAIN CHARTS ROW ── -->
        <div class="dash-section">
            <div class="row">
                <!-- Lead Activity Chart (large) -->
                <div class="col-lg-8 mb-4">
                    <div class="chart-card">
                        <div class="section-header mb-3">
                            <div>
                                <div class="ct">Lead Activity</div>
                                <div class="cv">Last 12 Months</div>
                            </div>
                            <div class="chart-tabs">
                                <button class="chart-tab active" onclick="switchChart('leads',this)">Leads</button>
                                <button class="chart-tab" onclick="switchChart('converted',this)">Converted</button>
                                <button class="chart-tab" onclick="switchChart('both',this)">Both</button>
                            </div>
                        </div>
                        <div id="chart-main" style="min-height:280px;"></div>
                    </div>
                </div>
                <!-- Status Distribution Donut -->
                <div class="col-lg-4 mb-4">
                    <div class="chart-card">
                        <div class="ct">Lead Status</div>
                        <div class="cv mb-3">Distribution</div>
                        <div id="chart-donut" style="min-height:230px;"></div>
                        <div id="status-legend" style="margin-top:8px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── SECOND ROW ── -->
        <div class="dash-section">
            <div class="row">
                <!-- Daily Leads This Month -->
                <div class="col-lg-5 mb-4">
                    <div class="chart-card">
                        <div class="ct">Daily Leads</div>
                        <div class="cv mb-3">This Month</div>
                        <div id="chart-daily" style="min-height:200px;"></div>
                    </div>
                </div>
                <!-- Sales Funnel -->
                <div class="col-lg-3 mb-4">
                    <div class="chart-card">
                        <div class="section-header mb-1">
                            <div class="section-title"><i class="mdi mdi-filter"></i>Sales Funnel</div>
                        </div>
                        <div id="funnel-container">
                            <div class="funnel-item" style="opacity:.4">
                                <div class="funnel-icon" style="background:rgba(108,99,255,.15)"><i class="mdi mdi-account-plus" style="color:#a99fff"></i></div>
                                <div><div class="funnel-label">Total Leads</div><div class="funnel-value" id="f-total">—</div></div>
                                <div class="funnel-right">100%</div>
                            </div>
                            <div class="funnel-item" style="opacity:.4">
                                <div class="funnel-icon" style="background:rgba(79,195,247,.15)"><i class="mdi mdi-presentation" style="color:#4fc3f7"></i></div>
                                <div><div class="funnel-label">In Pipeline</div><div class="funnel-value" id="f-pipeline">—</div></div>
                                <div class="funnel-right" id="f-pipeline-pct">—</div>
                            </div>
                            <div class="funnel-item" style="opacity:.4">
                                <div class="funnel-icon" style="background:rgba(0,212,170,.15)"><i class="mdi mdi-check-decagram" style="color:#00d4aa"></i></div>
                                <div><div class="funnel-label">Converted</div><div class="funnel-value" id="f-converted">—</div></div>
                                <div class="funnel-right" id="f-converted-pct">—</div>
                            </div>
                            <div class="funnel-item" style="opacity:.4">
                                <div class="funnel-icon" style="background:rgba(255,107,107,.15)"><i class="mdi mdi-account-off" style="color:#ff8a8a"></i></div>
                                <div><div class="funnel-label">Suspended</div><div class="funnel-value" id="f-suspended">—</div></div>
                                <div class="funnel-right" id="f-suspended-pct">—</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Conversion Rate Radial -->
                <div class="col-lg-4 mb-4">
                    <div class="chart-card text-center">
                        <div class="ct">Performance</div>
                        <div class="cv mb-2">Conversion Rate</div>
                        <div id="chart-radial" style="min-height:200px;"></div>
                        <div style="color:var(--text-muted);font-size:.8rem;margin-top:4px;">Monthly lead-to-member rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── RECENT LEADS TABLE ── -->
        <div class="dash-section">
            <div class="chart-card">
                <div class="section-header">
                    <div class="section-title"><i class="mdi mdi-table-large"></i>Recent Leads</div>
                    <a href="prospect_view.php" style="font-size:.8rem;color:var(--accent);text-decoration:none;font-weight:600;">View All →</a>
                </div>
                <div class="table-scroll">
                    <table class="crm-table" id="recent-table">
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>City</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="recent-tbody">
                            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </section><!-- /admin-content -->
</main>

<!-- ══════════════ SCRIPTS ══════════════ -->
<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/vendor/popper/popper.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/vendor/select2/js/select2.full.min.js"></script>
<script src="assets/vendor/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/vendor/listjs/listjs.min.js"></script>
<script src="assets/vendor/moment/moment.min.js"></script>
<script src="assets/vendor/daterangepicker/daterangepicker.js"></script>
<script src="assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="assets/vendor/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/atmos.min.js"></script>
<script src="assets/vendor/apexchart/apexcharts.min.js"></script>

<script>
/* ══════════════════════════════════════════════════════════
   Dashboard Controller
══════════════════════════════════════════════════════════ */
var _dashData = null;
var _mainChart = null;
var _donutChart = null;
var _dailyChart = null;
var _radialChart = null;

var COLORS = {
    purple: '#6c63ff',
    teal:   '#00d4aa',
    coral:  '#ff6b6b',
    amber:  '#ffa94d',
    blue:   '#4fc3f7',
    green:  '#4ade80',
};

/* ─── Animate counter ─────────────────────────────────────── */
function animateCount(el, target, suffix) {
    suffix = suffix || '';
    var start = 0, dur = 900, step = 16;
    var inc = target / (dur / step);
    var timer = setInterval(function() {
        start += inc;
        if (start >= target) { start = target; clearInterval(timer); }
        el.textContent = Math.floor(start) + suffix;
    }, step);
}

/* ─── Load dashboard data ─────────────────────────────────── */
function loadDashboard() {
    var btn = document.getElementById('refreshBtn');
    btn.classList.add('spinning');

    $.ajax({
        url: 'dashboard_admin.php',
        type: 'POST',
        data: { action: 'get_dashboard_data', type: 'all' },
        dataType: 'json',
        success: function(data) {
            _dashData = data;
            renderKPIs(data);
            renderMainChart(data, 'leads');
            renderDonut(data);
            renderDaily(data);
            renderRadial(data);
            renderTable(data);
            renderFunnel(data);

            // Animate funnel items in
            $('.funnel-item').each(function(i, el) {
                setTimeout(function() { $(el).css('opacity', 1).css('transition', 'opacity .4s'); }, i * 120);
            });

            btn.classList.remove('spinning');
            document.getElementById('dashLoader').classList.add('hide');
        },
        error: function() {
            btn.classList.remove('spinning');
            document.getElementById('dashLoader').classList.add('hide');
        }
    });
}

/* ─── KPI Cards ─────────────────────────────────────────────── */
function renderKPIs(d) {
    var fields = [
        ['kpi-today-leads',    d.today_leads,    'badge-today-leads',   d.today_leads > 0 ? 'up':'down', d.today_leads > 0 ? '↑ Active today' : '↓ No leads today'],
        ['kpi-month-leads',    d.month_leads,    'badge-month-leads',   d.month_leads > 0 ? 'up':'down', d.month_leads + ' this month'],
        ['kpi-today-conv',     d.today_converted,'badge-today-conv',    d.today_converted > 0 ? 'up':'down', d.today_converted > 0 ? '↑ Converted today' : 'None today'],
        ['kpi-month-conv',     d.month_converted,'badge-month-conv',    d.month_converted > 0 ? 'up':'down', d.month_converted + ' this month'],
        ['kpi-pipeline',       d.pipeline,       null, null, null],
        ['kpi-total-members',  d.total_members,  null, null, null],
        ['kpi-suspended',      d.suspended_leads,null, null, null],
        ['kpi-conv-rate',      null,              null, null, null],
    ];

    fields.forEach(function(f) {
        var el = document.getElementById(f[0]);
        if (!el) return;
        if (f[0] === 'kpi-conv-rate') {
            el.innerHTML = d.conversion_rate + '<span style="font-size:1rem;font-weight:400">%</span>';
        } else {
            animateCount(el, f[1]);
        }
        if (f[2]) {
            var badge = document.getElementById(f[2]);
            if (badge) {
                badge.className = 'kpi-badge ' + f[3];
                badge.textContent = f[4];
            }
        }
    });
}

/* ─── Main Line / Area Chart ─────────────────────────────────── */
function renderMainChart(d, mode) {
    var leadsData = (d.monthly_leads || []).map(function(r){ return r.leads; });
    var leadsLabels = (d.monthly_leads || []).map(function(r){ return r.month; });
    var convData  = [];

    // Align converted to same labels
    leadsLabels.forEach(function(mo) {
        var found = (d.monthly_converted || []).find(function(r){ return r.month === mo; });
        convData.push(found ? found.converted : 0);
    });

    var series = [];
    if (mode === 'leads' || mode === 'both') {
        series.push({ name: 'Leads', data: leadsData });
    }
    if (mode === 'converted' || mode === 'both') {
        series.push({ name: 'Converted', data: convData });
    }

    var opts = {
        series: series,
        chart: {
            type: mode === 'both' ? 'line' : 'area',
            height: 280,
            background: 'transparent',
            toolbar: { 
                show: true,
                tools: { zoom: true, pan: true, reset: true },
                autoSelected: 'zoom'
            },
            animations: { enabled: true, easing: 'easeinout', speed: 800, dynamicAnimation: { speed: 350 } },
        },
        colors: mode === 'both' ? [COLORS.purple, COLORS.teal] : [mode === 'leads' ? COLORS.purple : COLORS.teal],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: .5, opacityFrom: .35, opacityTo: 0.02, stops: [0, 95] }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2.5 },
        grid: { borderColor: 'rgba(255,255,255,.06)', strokeDashArray: 3 },
        xaxis: { categories: leadsLabels, labels: { style: { colors: '#8b8fa8', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { colors: '#8b8fa8' } } },
        tooltip: { theme: 'dark', x: { show: true } },
        legend: { labels: { colors: '#e8e9f3' } },
        markers: { size: 4, colors: ['#1a1d2e'], strokeColors: mode === 'both' ? [COLORS.purple, COLORS.teal] : [mode === 'leads' ? COLORS.purple : COLORS.teal], strokeWidth: 2, hover: { size: 6 } }
    };

    if (_mainChart) { _mainChart.destroy(); }
    _mainChart = new ApexCharts(document.querySelector('#chart-main'), opts);
    _mainChart.render();
}

function switchChart(mode, btn) {
    document.querySelectorAll('.chart-tab').forEach(function(t){ t.classList.remove('active'); });
    btn.classList.add('active');
    if (_dashData) renderMainChart(_dashData, mode);
}

/* ─── Donut Chart ──────────────────────────────────────────── */
function renderDonut(d) {
    var dist = d.status_dist || [];
    var labels = dist.map(function(r){ return r.status; });
    var values = dist.map(function(r){ return r.count; });
    var pallete = [COLORS.purple, COLORS.blue, COLORS.coral, COLORS.amber, COLORS.teal];

    var opts = {
        series: values,
        labels: labels,
        chart: { type: 'donut', height: 230, background: 'transparent' },
        colors: pallete,
        dataLabels: { enabled: false },
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true,
            total: { show: true, label: 'Total', color: '#8b8fa8', fontSize: '12px',
                formatter: function(w) { return w.globals.seriesTotals.reduce(function(a,b){return a+b;},0); }
            },
            value: { color: '#e8e9f3', fontSize: '22px', fontWeight: 700 }
        }}}},
        stroke: { colors: ['#1a1d2e'], width: 3 },
        tooltip: { theme: 'dark' },
    };

    if (_donutChart) { _donutChart.destroy(); }
    _donutChart = new ApexCharts(document.querySelector('#chart-donut'), opts);
    _donutChart.render();

    // Custom legend
    var html = labels.map(function(l, i) {
        return '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:.78rem;color:#8b8fa8">'
            + '<span style="width:10px;height:10px;border-radius:50%;background:' + pallete[i] + ';flex-shrink:0"></span>'
            + '<span style="flex:1">' + l + '</span>'
            + '<span style="color:#e8e9f3;font-weight:700">' + values[i] + '</span></div>';
    }).join('');
    document.getElementById('status-legend').innerHTML = html;
}

/* ─── Daily Bar Chart ───────────────────────────────────────── */
function renderDaily(d) {
    var daily = d.daily_leads || [];
    var opts = {
        series: [{ name: 'Leads', data: daily.map(function(r){ return r.count; }) }],
        chart: { type: 'bar', height: 200, background: 'transparent', toolbar: { show: false },
            animations: { enabled: true, speed: 500 } },
        colors: [COLORS.purple],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(255,255,255,.06)', strokeDashArray: 3 },
        xaxis: { categories: daily.map(function(r){ return r.date; }), labels: { style: { colors: '#8b8fa8', fontSize: '10px' }, rotate: -45 },
            axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { colors: '#8b8fa8' } } },
        tooltip: { theme: 'dark' },
    };

    if (_dailyChart) { _dailyChart.destroy(); }
    _dailyChart = new ApexCharts(document.querySelector('#chart-daily'), opts);
    _dailyChart.render();
}

/* ─── Radial / Gauge Chart ──────────────────────────────────── */
function renderRadial(d) {
    var rate = d.conversion_rate || 0;
    var opts = {
        series: [rate],
        chart: { type: 'radialBar', height: 200, background: 'transparent' },
        colors: [COLORS.teal],
        plotOptions: { radialBar: {
            startAngle: -135, endAngle: 135,
            hollow: { size: '65%' },
            dataLabels: {
                name: { show: false },
                value: { show: true, color: '#e8e9f3', fontSize: '2rem', fontWeight: 700,
                    formatter: function(val) { return val + '%'; } }
            },
            track: { background: 'rgba(255,255,255,.07)' }
        }},
        stroke: { lineCap: 'round' },
    };

    if (_radialChart) { _radialChart.destroy(); }
    _radialChart = new ApexCharts(document.querySelector('#chart-radial'), opts);
    _radialChart.render();
}

/* ─── Funnel ────────────────────────────────────────────────── */
function renderFunnel(d) {
    var total = d.total_leads || 0;
    document.getElementById('f-total').textContent     = total.toLocaleString();
    document.getElementById('f-pipeline').textContent  = (d.pipeline || 0).toLocaleString();
    document.getElementById('f-converted').textContent = (d.year_converted || 0).toLocaleString();
    document.getElementById('f-suspended').textContent = (d.suspended_leads || 0).toLocaleString();

    var pct = function(v) { return total > 0 ? Math.round((v/total)*100) + '%' : '—'; };
    document.getElementById('f-pipeline-pct').textContent  = pct(d.pipeline);
    document.getElementById('f-converted-pct').textContent = pct(d.year_converted);
    document.getElementById('f-suspended-pct').textContent = pct(d.suspended_leads);
}

/* ─── Recent Leads Table ────────────────────────────────────── */
function renderTable(d) {
    var rows = d.recent_leads || [];
    var map = { 'New':'s-new','Presentation':'s-presentation','Suspended':'s-suspended','Payment Recv.':'s-payment','Converted':'s-converted' };

    if (!rows.length) {
        document.getElementById('recent-tbody').innerHTML =
            '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px">No recent leads found.</td></tr>';
        return;
    }

    var html = rows.map(function(r) {
        var cls = map[r.Status_label] || 's-new';
        return '<tr>'
            + '<td><span style="font-family:monospace;color:var(--accent);font-weight:600">#' + r.Lead_id + '</span></td>'
            + '<td><strong>' + (r.FirstName||'') + ' ' + (r.LastName||'') + '</strong></td>'
            + '<td>' + (r.MobileNumber || '—') + '</td>'
            + '<td>' + (r.City || '—') + '</td>'
            + '<td><span class="status-pill ' + cls + '">' + r.Status_label + '</span></td>'
            + '<td style="color:var(--text-muted)">' + r.Creation_fmt + '</td>'
            + '</tr>';
    }).join('');

    document.getElementById('recent-tbody').innerHTML = html;
}

/* ─── Auto-refresh every 60 seconds ─────────────────────────── */
setInterval(loadDashboard, 60000);

/* ─── Init ───────────────────────────────────────────────────── */
$(document).ready(function() {
    loadDashboard();
});
</script>

<!-- Search Modal (kept from original) -->
<div class="modal modal-slide-left fade" id="siteSearchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-all-0" id="site-search">
                <button type="button" class="close light" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="form-dark bg-dark text-white p-t-60 p-b-20 bg-dots">
                    <h3 class="text-uppercase text-center fw-300">Search</h3>
                    <div class="container-fluid">
                        <div class="col-md-10 p-t-10 m-auto">
                            <input type="search" placeholder="Search Something" class="search form-control form-control-lg">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>