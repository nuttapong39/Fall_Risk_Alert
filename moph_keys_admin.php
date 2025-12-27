<?php
// moph_keys_admin.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once ('index1.html');
date_default_timezone_set('Asia/Bangkok');

$dir  = __DIR__ . DIRECTORY_SEPARATOR . 'secrets';
$file = $dir . DIRECTORY_SEPARATOR . 'moph_keys.json';

$msg = ''; $err = '';
$now = date('Y-m-d H:i:s');

// โหลดค่าปัจจุบัน
$current = [
  'default'  => ['client'=>'', 'secret'=>''],
  'covid'    => ['client'=>'', 'secret'=>''],
  'fracture' => ['client'=>'', 'secret'=>''],
  'accident' => ['client'=>'', 'secret'=>''],
  'pharm_lab'=> ['client'=>'', 'secret'=>''],
];
if (is_readable($file)) {
  $j = json_decode(@file_get_contents($file), true);
  if (is_array($j)) {
    foreach ($current as $k => $_) {
      $current[$k]['client'] = $j[$k]['client'] ?? '';
      $current[$k]['secret'] = $j[$k]['secret'] ?? '';
    }
  }
}

// บันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['token']) || $_POST['token'] !== (defined('UI_ACTION_TOKEN')?UI_ACTION_TOKEN:'')) {
    http_response_code(403); $err = 'Invalid token';
  } else {
    // รับค่าจากฟอร์ม
    $payload = [
      'default'  => ['client'=>trim($_POST['default_client']??''),  'secret'=>trim($_POST['default_secret']??'')],
      'covid'    => ['client'=>trim($_POST['covid_client']??''),    'secret'=>trim($_POST['covid_secret']??'')],
      'fracture' => ['client'=>trim($_POST['fracture_client']??''), 'secret'=>trim($_POST['fracture_secret']??'')],
      'accident' => ['client'=>trim($_POST['accident_client']??''), 'secret'=>trim($_POST['accident_secret']??'')],
      'pharm_lab'=> ['client'=>trim($_POST['pharm_client']??''),    'secret'=>trim($_POST['pharm_secret']??'')],
      '_meta'    => ['updated_at'=>$now]
    ];
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $ok = @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    if ($ok !== false) {
      @chmod($file, 0660);
      $msg = 'บันทึกสำเร็จ';
      foreach ($current as $k=>$_) $current[$k] = $payload[$k];
    } else {
      $err = 'บันทึกไม่สำเร็จ กรุณาตรวจสิทธิ์โฟลเดอร์ secrets/';
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตั้งค่า MOPH ALERT Tokens (หลายโมดูล)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#0f172a;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,"Kanit",sans-serif}
  .card{background:#111827;border:1px solid #1f2937;border-radius:16px}
  .form-control, .form-control:focus {background:#0b1220;color:#e5e7eb;border:1px solid #374151}
  .btn-primary{background:#2563eb;border-color:#2563eb}
  .badge-soft{background:#1f2937;border:1px solid #374151;color:#93c5fd}
  .hint{color:#9ca3af}
  .grid{display:grid;grid-template-columns:1fr;gap:16px}
  @media(min-width:840px){ .grid{grid-template-columns:1fr 1fr} }
</style>
</head>
<body class="py-4">
<div class="container" style="max-width:980px">
  <div class="mb-4 text-center">
    <h3 class="mb-1">ตั้งค่า <span class="text-info">MOPH ALERT</span> Tokens</h3>
    <div class="badge badge-soft rounded-pill px-3 py-2">อัปเดต: <strong><?=htmlspecialchars($now)?></strong></div>
  </div>

  <?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="token" value="<?=htmlspecialchars(defined('UI_ACTION_TOKEN')?UI_ACTION_TOKEN:'')?>">

    <!-- DEFAULT -->
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="mb-3">ค่าเริ่มต้น (Default)</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">MOPH_CLIENT_KEY (default)</label>
            <input type="text" name="default_client" class="form-control" value="<?=htmlspecialchars($current['default']['client'])?>" placeholder="ค่าเริ่มต้น (ใช้เมื่อโมดูลนั้นๆ ว่าง)">
          </div>
          <div class="col-md-6">
            <label class="form-label">MOPH_SECRET_KEY (default)</label>
            <input type="password" name="default_secret" class="form-control" value="<?=htmlspecialchars($current['default']['secret'])?>" placeholder="ค่าเริ่มต้น (ใช้เมื่อโมดูลนั้นๆ ว่าง)">
          </div>
        </div>
        <div class="hint mt-2">ถ้าโมดูลไหนไม่กรอก จะ fallback มาใช้ค่า default</div>
      </div>
    </div>

    <div class="grid">
      <!-- COVID -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">COVID</h5>
          <div class="mb-3">
            <label class="form-label">COVID_CLIENT_KEY</label>
            <input type="text" name="covid_client" class="form-control" value="<?=htmlspecialchars($current['covid']['client'])?>">
          </div>
          <div>
            <label class="form-label">COVID_SECRET_KEY</label>
            <input type="password" name="covid_secret" class="form-control" value="<?=htmlspecialchars($current['covid']['secret'])?>">
          </div>
        </div>
      </div>

      <!-- FRACTURE -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">FRACTURE</h5>
          <div class="mb-3">
            <label class="form-label">FRACTURE_CLIENT_KEY</label>
            <input type="text" name="fracture_client" class="form-control" value="<?=htmlspecialchars($current['fracture']['client'])?>">
          </div>
          <div>
            <label class="form-label">FRACTURE_SECRET_KEY</label>
            <input type="password" name="fracture_secret" class="form-control" value="<?=htmlspecialchars($current['fracture']['secret'])?>">
          </div>
        </div>
      </div>

      <!-- ACCIDENT -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">ACCIDENT</h5>
          <div class="mb-3">
            <label class="form-label">ACCIDENT_CLIENT_KEY</label>
            <input type="text" name="accident_client" class="form-control" value="<?=htmlspecialchars($current['accident']['client'])?>">
          </div>
          <div>
            <label class="form-label">ACCIDENT_SECRET_KEY</label>
            <input type="password" name="accident_secret" class="form-control" value="<?=htmlspecialchars($current['accident']['secret'])?>">
          </div>
        </div>
      </div>

      <!-- PHARM LAB -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">PHARM LAB</h5>
          <div class="mb-3">
            <label class="form-label">PHARM_CLIENT_KEY</label>
            <input type="text" name="pharm_client" class="form-control" value="<?=htmlspecialchars($current['pharm_lab']['client'])?>">
          </div>
          <div>
            <label class="form-label">PHARM_SECRET_KEY</label>
            <input type="password" name="pharm_secret" class="form-control" value="<?=htmlspecialchars($current['pharm_lab']['secret'])?>">
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-primary">บันทึก</button>
      <a class="btn btn-outline-light" href="moph_keys_admin.php">รีเฟรช</a>
    </div>
  </form>

  <div class="mt-3 small hint">
    ระบบจะอ่านค่าจากไฟล์ <code>secrets/moph_keys.json</code> ผ่าน <code>moph_keys_loader.php</code> และ define เป็นคอนสแตนต์:
    <code>COVID_CLIENT_KEY</code>, <code>FRACTURE_CLIENT_KEY</code>, <code>ACCIDENT_CLIENT_KEY</code>, <code>PHARM_CLIENT_KEY</code> (และคู่ SECRET)
    หากไม่ได้กำหนดไว้ จะ fallback เป็น <code>MOPH_CLIENT_KEY / MOPH_SECRET_KEY</code> (default)
  </div>
</div>
</body>
</html>
