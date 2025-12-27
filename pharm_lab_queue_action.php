<?php
require_once __DIR__ . '/config.php';

/* สำคัญมาก: กันไม่ให้ main block ใน pharm_lab.php ทำงานตอน include */
define('PHARM_LIB_ONLY', true);

require_once __DIR__ . '/pharm_lab.php'; // ใช้ฟังก์ชันส่ง
date_default_timezone_set('Asia/Bangkok');



if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
if (!isset($_POST['token']) || $_POST['token'] !== UI_ACTION_TOKEN) { http_response_code(403); exit('Forbidden'); }

$action = $_POST['action'] ?? '';
$ids    = isset($_POST['ids']) ? (array)$_POST['ids'] : [];
$ids    = array_values(array_filter($ids, fn($x)=>ctype_digit((string)$x)));
if (!$ids) { header('Location: pharm_lab_queue_ui.php?msg=no_ids'); exit; }

function send_one(PDO $db, int $id): array {
  $q=$db->prepare("SELECT * FROM pharm_lab_queue WHERE id=?");
  $q->execute([$id]); $row=$q->fetch(); if(!$row) return [false,null,'not found'];
  [$ok,$ref,$err] = send_via_moph_alert_pharm($row);
  if($ok){
    $u=$db->prepare("UPDATE pharm_lab_queue SET status=1,sent_at=NOW(),last_attempt_at=NOW(),attempt=attempt+1,last_error=NULL,out_ref=?,line_message_id=? WHERE id=?");
    $u->execute([$ref,$ref,$id]);
  } else {
    $u=$db->prepare("UPDATE pharm_lab_queue SET last_attempt_at=NOW(),attempt=attempt+1,last_error=? WHERE id=?");
    $u->execute([$err,$id]);
  }
  return [$ok,$ref,$err];
}

try {
  if ($action==='requeue'){
    $place=implode(',', array_fill(0,count($ids),'?'));
    $st=$dbcon->prepare("UPDATE pharm_lab_queue SET status=0, attempt=0, last_error=NULL, out_ref=NULL, line_message_id=NULL WHERE id IN ($place)");
    $st->execute($ids);
    header('Location: pharm_lab_queue_ui.php?msg=requeued&affected='.$st->rowCount()); exit;

  } elseif ($action==='clear_error'){
    $place=implode(',', array_fill(0,count($ids),'?'));
    $st=$dbcon->prepare("UPDATE pharm_lab_queue SET last_error=NULL WHERE id IN ($place)");
    $st->execute($ids);
    header('Location: pharm_lab_queue_ui.php?msg=cleared&affected='.$st->rowCount()); exit;

  } elseif ($action==='send_now'){
    $ok=0; $fail=0; foreach($ids as $id){ [$o,$r,$e]=send_one($dbcon,(int)$id); if($o)$ok++; else $fail++; }
    header('Location: pharm_lab_queue_ui.php?msg=sendnow&ok='.$ok.'&fail='.$fail); exit;

  } else {
    header('Location: pharm_lab_queue_ui.php?msg=bad_action'); exit;
  }
} catch (Throwable $e) {
  header('Location: pharm_lab_queue_ui.php?msg=err&detail='.urlencode($e->getMessage()));
}
