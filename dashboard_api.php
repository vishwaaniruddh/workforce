<?php
ob_start();
session_start();
include('config.php');
date_default_timezone_set('Asia/Kolkata');
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : 'all');
$today = date('Y-m-d');
$firstDate = date('Y-m-01');
$lastDate = date('Y-m-t');
$yearStart = date('Y-01-01');
$yearEnd = date('Y-12-31');
$response = array();
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
    $ml = $response['month_leads']; $mc = $response['month_converted'];
    $response['conversion_rate'] = ($ml > 0) ? round(($mc / $ml) * 100, 1) : 0;
}
if ($type === 'all' || $type === 'monthly_leads') {
    $chart = array();
    $sql = "SELECT DATE_FORMAT(Creation,'%b %Y') as mo, DATE_FORMAT(Creation,'%Y-%m') as ym, COUNT(Lead_id) as cnt FROM Leads_table WHERE Creation >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) { $chart[] = array('month' => $row['mo'], 'leads' => (int)$row['cnt']); }
    $response['monthly_leads'] = $chart;
}
if ($type === 'all' || $type === 'monthly_converted') {
    $chart = array();
    $sql = "SELECT DATE_FORMAT(ld.DelegatedTIme,'%b %Y') as mo, DATE_FORMAT(ld.DelegatedTIme,'%Y-%m') as ym, COUNT(ld.LeadId) as cnt FROM LeadDelegation ld INNER JOIN Leads_table lt ON lt.Lead_id = ld.LeadId AND lt.Status='5' WHERE ld.DelegatedTIme >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) { $chart[] = array('month' => $row['mo'], 'converted' => (int)$row['cnt']); }
    $response['monthly_converted'] = $chart;
}
if ($type === 'all' || $type === 'status_dist') {
    $labels = array('1' => 'New Lead', '2' => 'Presentation', '3' => 'Suspended', '4' => 'Payment Received', '5' => 'Converted');
    $dist = array();
    $res = mysqli_query($conn, "SELECT Status, COUNT(Lead_id) as cnt FROM Leads_table GROUP BY Status");
    while ($row = mysqli_fetch_assoc($res)) { $lbl = isset($labels[$row['Status']]) ? $labels[$row['Status']] : ('Status '.$row['Status']); $dist[] = array('status' => $lbl, 'count' => (int)$row['cnt']); }
    $response['status_dist'] = $dist;
}
if ($type === 'all' || $type === 'daily') {
    $daily = array();
    $sql = "SELECT date(Creation) as d, COUNT(Lead_id) as cnt FROM Leads_table WHERE date(Creation) BETWEEN '$firstDate' AND '$lastDate' GROUP BY d ORDER BY d ASC";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) { $daily[] = array('date' => date('d M', strtotime($row['d'])), 'count' => (int)$row['cnt']); }
    $response['daily_leads'] = $daily;
}
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