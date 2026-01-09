<?php
require_once __DIR__ . '/config.php';
// require_once __DIR__ . '/auth_guard.php';
require_once('index1.html'); // ถ้ามี header()/redirect อย่าเรียกหลังจากบรรทัดนี้
date_default_timezone_set('Asia/Bangkok');
mb_internal_encoding('UTF-8');

/* ---------- Filters ---------- */
$start   = isset($_GET['start']) && $_GET['start'] ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$end     = isset($_GET['end'])   && $_GET['end']   ? $_GET['end']   : date('Y-m-d');
$status  = isset($_GET['status']) ? $_GET['status'] : 'all'; // all | 0 | 1
$pttypes = isset($_GET['pttypes']) ? trim($_GET['pttypes']) : ''; // comma list (optional)

/* ---------- Token (กันกด action มั่ว) ---------- */
if(!defined('ACCIDENT_UI_ACTION_TOKEN')){
  define('ACCIDENT_UI_ACTION_TOKEN', hash('sha256', __FILE__ . php_uname() . date('Y-m-d')));
}

/* ---------- UTF-8 helper ---------- */
function to_utf8($s){
  if(!is_string($s)) return $s;
  if(mb_check_encoding($s,'UTF-8')) return $s;
  foreach(['TIS-620','TIS620','Windows-874','CP874','ISO-8859-11','ISO-8859-1'] as $enc){
    $t=@iconv($enc,'UTF-8//IGNORE',$s); if($t!==false && $t!=='') return $t;
    $t=@mb_convert_encoding($s,'UTF-8',$enc); if($t!==false && $t!=='') return $t;
  }
  return @iconv('UTF-8','UTF-8//IGNORE',$s);
}

/* ---------- Query conditions (ให้ logic เหมือน fracture UI) ---------- */
$w = ["created_at BETWEEN :s AND :e"];
$p = [':s'=>$start.' 00:00:00', ':e'=>$end.' 23:59:59'];

if ($status==='0') { $w[] = "status=0"; }
if ($status==='1') { $w[] = "status=1"; }

/* pttypes filter: ให้สอดคล้องกับ accident.php (ACCIDENT_PTTYPES) */
$ptList = [];
if ($pttypes !== '') {
  $ptList = array_values(array_filter(array_map('trim', explode(',', $pttypes)), fn($x)=>$x!==''));
  $ptList = array_map(fn($x)=>preg_replace('/[^A-Z0-9]/i','',$x), $ptList);
  $ptList = array_values(array_unique($ptList));
}
if ($ptList) {
  $ph = [];
  foreach ($ptList as $i=>$code){
    $k = ":pt{$i}";
    $ph[] = $k;
    $p[$k] = $code;
  }
  $w[] = "pttype IN (".implode(',', $ph).")";
}

$sql = "
  SELECT
    id, hn, an, regdate, regtime, pttype, pttname, fullname,
    status, attempt, last_attempt_at, out_ref, last_error,
    created_at, sent_at, line_message_id
  FROM accident_queue
  WHERE ".implode(' AND ', $w)."
  ORDER BY id DESC
  LIMIT 2000
";
$stmt = $dbcon->prepare($sql);
$stmt->execute($p);
$rows = $stmt->fetchAll() ?: [];
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Accident Queue Monitor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,"Kanit",sans-serif}
  td.small{font-size:.92rem}
  .badge-pending{background:#ffc107}
  .badge-ok{background:#28a745}
  .table-responsive{overflow-x:auto}
</style>
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Accident Queue Monitor</h3>

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
      <label class="form-label">สถานะ</label>
      <select class="form-select" name="status">
        <option value="all" <?=$status==='all'?'selected':''?>>ทั้งหมด</option>
        <option value="0"   <?=$status==='0'?'selected':''?>>ค้างส่ง (0)</option>
        <option value="1"   <?=$status==='1'?'selected':''?>>ส่งแล้ว (1)</option>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">pttypes (คั่นด้วย ,)</label>
      <input type="text" class="form-control" name="pttypes" value="<?=htmlspecialchars($pttypes)?>" placeholder="เช่น 33,35,36,39">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-primary">ค้นหา</button>
      <a class="btn btn-outline-secondary" href="accident_queue_ui.php">รีเซ็ต</a>
    </div>
  </form>

  <form method="post" action="accident_queue_action.php" onsubmit="return confirm('ยืนยันดำเนินการกับรายการที่เลือก?');">
    <input type="hidden" name="token" value="<?=htmlspecialchars(ACCIDENT_UI_ACTION_TOKEN)?>">
    <div class="mb-2">
      <button class="btn btn-sm btn-success" name="action" value="send_now">ส่งซ้ำทันที</button>
      <button class="btn btn-sm btn-warning" name="action" value="requeue">Requeue (ตั้งสถานะเป็น 0)</button>
      <button class="btn btn-sm btn-outline-danger" name="action" value="clear_error">ล้าง error</button>
    </div>

    <div class="table-responsive">
      <table id="tbl" class="table table-striped table-hover nowrap" style="width:100%">
        <thead class="table-light">
          <tr>
            <th><input type="checkbox" onclick="document.querySelectorAll('.chk').forEach(c=>c.checked=this.checked)"></th>
            <th>ID</th>
            <th>สถานะ</th>
            <th>HN</th>
            <th>AN</th>
            <th>ชื่อ-สกุล</th>
            <th>regdate</th>
            <th>regtime</th>
            <th>pttype</th>
            <th>สิทธิ</th>
            <th>attempt</th>
            <th>last_attempt</th>
            <th>out_ref</th>
            <th>error</th>
            <th>created_at</th>
            <th>sent_at</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><input type="checkbox" class="chk" name="ids[]" value="<?=$r['id']?>"></td>
            <td><?=$r['id']?></td>
            <td><?= $r['status'] ? '<span class="badge badge-ok">1</span>' : '<span class="badge badge-pending">0</span>' ?></td>
            <td><?=htmlspecialchars($r['hn'])?></td>
            <td><?=htmlspecialchars($r['an'])?></td>
            <td><?=htmlspecialchars(to_utf8($r['fullname']))?></td>
            <td><?=htmlspecialchars($r['regdate'])?></td>
            <td><?=htmlspecialchars($r['regtime'])?></td>
            <td><?=htmlspecialchars($r['pttype'])?></td>
            <td class="small"><?=htmlspecialchars(to_utf8($r['pttname']))?></td>
            <td><?=$r['attempt']?></td>
            <td><?=htmlspecialchars($r['last_attempt_at'])?></td>
            <td><?=htmlspecialchars($r['out_ref'])?></td>
            <td class="small"><?=htmlspecialchars(to_utf8($r['last_error']))?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td><?=htmlspecialchars($r['sent_at'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
$(function(){
  $('#tbl').DataTable({
    responsive: true,
    autoWidth: false,
    pageLength: 25,
    order: [[1,'desc']],
    language: {
      search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ",
      info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
      paginate: { first:"หน้าแรก", last:"หน้าสุดท้าย", next:"ถัดไป", previous:"ก่อนหน้า" }
    },
    columnDefs: [
      { targets: [0,1,2,6,10,11,14,15], className: 'text-nowrap' }
    ]
  });
});
</script>
</body>
</html>
