<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/covid_lib.php';
require_once('index1.html');
// require_once __DIR__ . '/auth_guard.php'; 


date_default_timezone_set('Asia/Bangkok');

/* ---- รับพารามิเตอร์ ---- */
$start = isset($_GET['start']) && $_GET['start'] ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$end   = isset($_GET['end'])   && $_GET['end']   ? $_GET['end']   : date('Y-m-d');
$station = trim($_GET['mainstation'] ?? '');
$pdx     = strtoupper(trim($_GET['pdx'] ?? ''));    // พิมพ์รหัสบางส่วน เช่น S72
$sex     = trim($_GET['sex'] ?? '');                // M/F/ว่าง
$ageMin  = isset($_GET['age_min']) && is_numeric($_GET['age_min']) ? (int)$_GET['age_min'] : null;
$ageMax  = isset($_GET['age_max']) && is_numeric($_GET['age_max']) ? (int)$_GET['age_max'] : null;
$status  = $_GET['status'] ?? 'all';                // all | 0 | 1

/* ---- สร้าง where ---- */
$w = ["created_at BETWEEN :s AND :e"];
$p = [':s'=>$start.' 00:00:00', ':e'=>$end.' 23:59:59'];

if ($station !== '') { $w[]="mainstation LIKE :st"; $p[':st']="%$station%"; }
if ($pdx !== '')     { $w[]="(UPPER(pdx_code) LIKE :px OR UPPER(pdx_name) LIKE :px)"; $p[':px']="%$pdx%"; }
if ($sex !== '')     { $w[]="sex = :sx"; $p[':sx']=$sex; }
if ($ageMin !== null){ $w[]="age >= :amin"; $p[':amin']=$ageMin; }
if ($ageMax !== null){ $w[]="age <= :amax"; $p[':amax']=$ageMax; }
if ($status==='0')   { $w[]="status=0"; }
if ($status==='1')   { $w[]="status=1"; }

$where = implode(' AND ', $w);
 
/* ---- สรุปต่อวัน ---- */ 
$q = $dbcon->prepare("SELECT DATE(created_at) d,
  COUNT(*) total,
  SUM(status=1) sent_ok,
  SUM(status=0) pending,
  SUM(CASE WHEN last_error IS NOT NULL THEN 1 ELSE 0 END) failed
  FROM fracture_queue
  WHERE $where
  GROUP BY DATE(created_at)
  ORDER BY d
");
$q->execute($p);
$rows = $q->fetchAll();

/* ---- Top mainstation / PDx ---- */
$q2 = $dbcon->prepare("SELECT COALESCE(mainstation,'-') mainstation, COUNT(*) c
  FROM fracture_queue WHERE $where
  GROUP BY mainstation ORDER BY c DESC LIMIT 10");
$q2->execute($p); $topStation = array_map('row_to_utf8', $q2->fetchAll());

$q3 = $dbcon->prepare("SELECT pdx_code, pdx_name, COUNT(*) c
  FROM fracture_queue WHERE $where
  GROUP BY pdx_code, pdx_name ORDER BY c DESC LIMIT 10");
$q3->execute($p); $topPdx = array_map('row_to_utf8', $q3->fetchAll());

/* ---- เตรียมข้อมูล Chart ---- */
$labels=[]; $total=[]; $sent=[]; $pend=[]; $fail=[];
foreach ($rows as $r) { $labels[]=$r['d']; $total[]=(int)$r['total']; $sent[]=(int)$r['sent_ok']; $pend[]=(int)$r['pending']; $fail[]=(int)$r['failed']; }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Fracture Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,"Kanit",sans-serif}
  .card{border-radius:14px}
</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Fracture Dashboard</h3>

  <form class="row g-2 mb-3" method="get">
    <div class="col-auto">
      <label class="form-label">ตั้งแต่</label>
      <input type="date" class="form-control" name="start" value="<?=htmlspecialchars($start)?>">
    </div>
    <div class="col-auto">
      <label class="form-label">ถึง</label>
      <input type="date" class="form-control" name="end" value="<?=htmlspecialchars($end)?>">
    </div>
    <div class="col-auto">
      <label class="form-label">สถานบริการหลัก</label>
      <input type="text" class="form-control" name="mainstation" placeholder="พิมพ์บางส่วน" value="<?=htmlspecialchars($station)?>">
    </div>
    <div class="col-auto">
      <label class="form-label">PDX (รหัส/ชื่อ)</label>
      <input type="text" class="form-control" name="pdx" placeholder="เช่น S72" value="<?=htmlspecialchars($pdx)?>">
    </div>
    <div class="col-auto">
      <label class="form-label">เพศ</label>
      <select class="form-select" name="sex">
        <option value=""  <?=$sex===''?'selected':''?>>ทั้งหมด</option>
        <option value="ชาย" <?=$sex==='ชาย'?'selected':''?>>ชาย</option>
        <option value="หญิง" <?=$sex==='หญิง'?'selected':''?>>หญิง</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">อายุ</label>
      <div class="d-flex gap-2">
        <input type="number" class="form-control" name="age_min" placeholder="Min" value="<?=htmlspecialchars($ageMin)?>">
        <input type="number" class="form-control" name="age_max" placeholder="Max" value="<?=htmlspecialchars($ageMax)?>">
      </div>
    </div>
    <div class="col-auto">
      <label class="form-label">สถานะ</label>
      <select class="form-select" name="status">
        <option value="all" <?=$status==='all'?'selected':''?>>ทั้งหมด</option>
        <option value="0" <?=$status==='0'?'selected':''?>>ค้างส่ง (0)</option>
        <option value="1" <?=$status==='1'?'selected':''?>>ส่งแล้ว (1)</option>
      </select>
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-primary">กรองข้อมูล</button>
      <a class="btn btn-outline-secondary" href="fracture_dashboard.php">รีเซ็ต</a>
    </div>
  </form>

  <div class="card p-3 mb-3">
    <div class="fw-bold mb-2">กราฟสรุปต่อวัน</div>
    <canvas id="lineChart" height="90"></canvas>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card p-3">
        <div class="fw-bold mb-2">Top สถานบริการหลัก</div>
        <table class="table table-sm mb-0">
          <thead><tr><th>สถานบริการหลัก</th><th class="text-end">จำนวน</th></tr></thead>
          <tbody>
          <?php foreach($topStation as $r): ?>
            <tr><td><?=htmlspecialchars($r['mainstation']?:'-')?></td><td class="text-end"><?=$r['c']?></td></tr>
          <?php endforeach; if(!$topStation) echo '<tr><td colspan="2" class="text-center text-secondary">-</td></tr>'; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <div class="fw-bold mb-2">Top PDX</div>
        <table class="table table-sm mb-0">
          <thead><tr><th>รหัส</th><th>ชื่อ</th><th class="text-end">จำนวน</th></tr></thead>
          <tbody>
          <?php foreach($topPdx as $r): ?>
            <tr><td><?=$r['pdx_code']?:'-'?></td><td><?=htmlspecialchars($r['pdx_name']?:'-')?></td><td class="text-end"><?=$r['c']?></td></tr>
          <?php endforeach; if(!$topPdx) echo '<tr><td colspan="3" class="text-center text-secondary">-</td></tr>'; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const labels = <?=json_encode($labels, JSON_UNESCAPED_UNICODE)?>;
const dataAll = <?=json_encode($total)?>;
const dataSent= <?=json_encode($sent)?>;
const dataPend= <?=json_encode($pend)?>;
const dataFail= <?=json_encode($fail)?>;
const ctx = document.getElementById('lineChart');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: labels,
    datasets: [
      {label:'ทั้งหมด', data:dataAll},
      {label:'ส่งสำเร็จ', data:dataSent},
      {label:'ค้างส่ง', data:dataPend},
      {label:'ล้มเหลว', data:dataFail},
    ]
  },
  options: {
    responsive:true,
    interaction:{mode:'index', intersect:false},
    plugins:{legend:{position:'top'}},
    scales:{y:{beginAtZero:true}}
  }
});
</script>
</body>
</html>
