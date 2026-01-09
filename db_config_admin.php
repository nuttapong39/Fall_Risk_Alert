<?php
// db_config_admin.php
// หน้าเว็บสำหรับตั้งค่า DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS

// ข้ามการต่อ DB เวลาโหลด config.php (กันกรณีตั้งค่าเดิมผิด)
define('CONFIG_SKIP_DB', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/index1.html';

date_default_timezone_set('Asia/Bangkok');

$dir  = __DIR__ . DIRECTORY_SEPARATOR . 'secrets';
$file = $dir . DIRECTORY_SEPARATOR . 'db_config.json';

$now = date('Y-m-d H:i:s');
$msg = ''; $err = '';

// ค่า default (ต้องตรงกับใน config.php)
$current = [
  'host' => $DB_HOST ?? '192.168.1.249',
  'port' => $DB_PORT ?? 3306,
  'name' => $DB_NAME ?? 'hosxp',
  'user' => $DB_USER ?? 'root',
  'pass' => $DB_PASS ?? 'comsci',
];

// ถ้ามีไฟล์ db_config.json ให้โหลดมาทับค่า default
if (is_readable($file)) {
  $j = json_decode(@file_get_contents($file), true);
  if (is_array($j)) {
    $current['host'] = $j['host'] ?? $current['host'];
    $current['port'] = $j['port'] ?? $current['port'];
    $current['name'] = $j['name'] ?? $current['name'];
    $current['user'] = $j['user'] ?? $current['user'];
    $current['pass'] = $j['pass'] ?? $current['pass'];
  }
}

// บันทึกเมื่อมี POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['token']) || $_POST['token'] !== (defined('UI_ACTION_TOKEN') ? UI_ACTION_TOKEN : '')) {
    http_response_code(403);
    $err = 'Invalid token';
  } else {
    $payload = [
      'host' => trim($_POST['db_host'] ?? ''),
      'port' => (int)($_POST['db_port'] ?? 3306),
      'name' => trim($_POST['db_name'] ?? ''),
      'user' => trim($_POST['db_user'] ?? ''),
      'pass' => trim($_POST['db_pass'] ?? ''),
      '_meta' => ['updated_at' => $now]
    ];

    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $ok = @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    if ($ok !== false) {
      @chmod($file, 0660);
      $msg = 'บันทึกค่าฐานข้อมูลสำเร็จ';
      // อัปเดตค่าปัจจุบันในฟอร์ม
      $current = array_merge($current, $payload);
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
<title>ตั้งค่า Database (HOSxP)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#0f172a;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,"Kanit",sans-serif}
  .card{background:#111827;border:1px solid #1f2937;border-radius:16px}
  .form-control,.form-control:focus{background:#0b1220;color:#e5e7eb;border:1px solid #374151}
  .btn-primary{background:#10b981;border-color:#10b981}
  .badge-soft{background:#1f2937;border:1px solid #374151;color:#a5b4fc}
  .hint{color:#9ca3af;font-size:.9rem}
</style>
</head>
<body class="py-4">
<div class="container" style="max-width:720px">
  <div class="mb-4 text-center">
    <h3 class="mb-1">ตั้งค่า <span class="text-info">Database HOSxP (ควรใช้ Slave)</span></h3>
    <div class="badge badge-soft rounded-pill px-3 py-2">
      อัปเดตล่าสุด: <strong><?=htmlspecialchars($now)?></strong>
    </div>
  </div>

  <?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="token" value="<?=htmlspecialchars(defined('UI_ACTION_TOKEN')?UI_ACTION_TOKEN:'')?>">

    <div class="card mb-3">
      <div class="card-body">
        <h5 class="mb-3">การเชื่อมต่อฐานข้อมูล HOSxP</h5>

        <div class="mb-3">
          <label class="form-label">DB_HOST (IP / Hostname)</label>
          <input type="text" name="db_host" class="form-control"
                 value="<?=htmlspecialchars($current['host'])?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">DB_PORT</label>
          <input type="number" name="db_port" class="form-control"
                 value="<?=htmlspecialchars($current['port'])?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">DB_NAME</label>
          <input type="text" name="db_name" class="form-control"
                 value="<?=htmlspecialchars($current['name'])?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">DB_USER</label>
          <input type="text" name="db_user" class="form-control"
                 value="<?=htmlspecialchars($current['user'])?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">DB_PASS</label>
          <input type="password" name="db_pass" class="form-control"
                 value="<?=htmlspecialchars($current['pass'])?>" required>
        </div>

        <div class="hint">
          ระบบจะบันทึกไฟล์ที่ <code>secrets/db_config.json</code> และไฟล์ <code>config.php</code>
          จะอ่านค่าจากไฟล์นี้ทุกครั้งที่เรียกใช้งาน
        </div>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary">บันทึก</button>
      <a class="btn btn-outline-light" href="db_config_admin.php">รีเฟรช</a>
    </div>
  </form>
</div>
</body>
</html>
