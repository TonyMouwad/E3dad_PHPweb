<?php
require'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$lectureId = (int)($_GET['lecture_id'] ?? 0);
if (!$lectureId) { echo "اختر محاضرة صحيحة"; exit; }

$stmt = $pdo->prepare("SELECT title, lecture_date FROM lectures WHERE id = ?");
$stmt->execute([$lectureId]);
$lecture = $stmt->fetch();
if (!$lecture) { echo "المحاضرة غير موجودة"; exit; }

function flash($msg) { $_SESSION['flash']=$msg; }
function get_flash() { if(!empty($_SESSION['flash'])) { $m=$_SESSION['flash']; unset($_SESSION['flash']); return $m;} return null; }

// --- تسجيل حضور يدوي ---
if(isset($_POST['add_attendance'])){
    $idNumber = trim($_POST['id_number'] ??'');
    if($idNumber===''){ flash('ادخل رقم العضو'); header("Location: ?lecture_id=$lectureId"); exit; }

    $stmt=$pdo->prepare("SELECT id FROM members WHERE id_number=? LIMIT 1");
    $stmt->execute([$idNumber]);
    $member=$stmt->fetch();

    if($member){
        $memberId=$member['id'];
        $check=$pdo->prepare("SELECT id FROM attendance WHERE lecture_id=? AND member_id=? LIMIT 1");
        $check->execute([$lectureId,$memberId]);
        if(!$check->fetch()){
            $ins=$pdo->prepare("INSERT INTO attendance (lecture_id, member_id, scanned_id_number, check_in, status, created_at) VALUES (?,?,?,NOW(),'present',NOW())");
            $ins->execute([$lectureId,$memberId,$idNumber]);
            flash('تم تسجيل الحضور.');
        } else flash('العضو مسجّل بالفعل.');
    } else {
        $ins=$pdo->prepare("INSERT INTO attendance (lecture_id, member_id, scanned_id_number, check_in, status, created_at) VALUES (?,?,?,NOW(),'pending',NOW())");
        $ins->execute([$lectureId,NULL,$idNumber]);
        flash('تم إنشاء سجل مُعلق للعضو غير الموجود.');
    }
    header("Location: ?lecture_id=$lectureId");
    exit;
}

// --- تحديث المخصوم ---
if(isset($_POST['update_deduct'])){
    $attendanceId=(int)($_POST['attendance_id']);
    $deducted=max(0,(int)($_POST['deducted_minutes']??0));
    $stmt=$pdo->prepare("UPDATE attendance SET deducted_minutes=? WHERE id=?");
    $stmt->execute([$deducted,$attendanceId]);
    flash('تم تحديث المخصوم.');
    header("Location: ?lecture_id=$lectureId");
    exit;
}

// --- انصراف فردي ---
if(isset($_GET['end_one'])){
    $attendanceId=(int)$_GET['end_one'];
    $update=$pdo->prepare("UPDATE attendance SET check_out=NOW(), duration_minutes=CASE WHEN check_in IS NOT NULL THEN GREATEST(0,TIMESTAMPDIFF(MINUTE,check_in,NOW())-COALESCE(deducted_minutes,0)) ELSE duration_minutes END, status='present' WHERE id=? AND check_out IS NULL");
    $update->execute([$attendanceId]);
    flash('تم تسجيل الانصراف للفرد.');
    header("Location: ?lecture_id=$lectureId");
    exit;
}

// --- انصراف عام ---
if(isset($_POST['end_all'])){
    $update1=$pdo->prepare("UPDATE attendance SET check_out=NOW(), duration_minutes=CASE WHEN check_in IS NOT NULL THEN GREATEST(0,TIMESTAMPDIFF(MINUTE,check_in,NOW())-COALESCE(deducted_minutes,0)) ELSE duration_minutes END, status='present' WHERE lecture_id=? AND check_out IS NULL");
    $update1->execute([$lectureId]);
    flash('تم تنفيذ الانصراف العام لجميع الحاضرين.');
    header("Location: ?lecture_id=$lectureId");
    exit;
}

// --- جلب الحضور ---
$stmt=$pdo->prepare("SELECT a.*, m.name AS member_name, m.id_number AS member_idnum FROM attendance a LEFT JOIN members m ON a.member_id=m.id WHERE a.lecture_id=? ORDER BY a.check_in ASC, a.id ASC");
$stmt->execute([$lectureId]);
$attendanceList=$stmt->fetchAll();

$msg=get_flash();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>الحضور - <?= htmlspecialchars($lecture['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Cairo',sans-serif;background:#f0f4f8;margin:0;padding:0;}
.container{max-width:1100px;margin:auto;padding:15px;}
h1{font-size:24px;text-align:center;margin:15px 0;color:#1E4A7B;}
.msg{background:#e7f7e7;color:#064;padding:10px;border-radius:6px;margin-bottom:12px;text-align:center;font-weight:bold;}
.add-form{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:20px;}
.add-form input{padding:8px;flex:1;min-width:150px;border:1px solid #ccc;border-radius:6px;}
.add-form button{padding:8px 12px;border:none;background:#1E4A7B;color:#fff;border-radius:6px;cursor:pointer;}
.end-all{background:#d9534f;color:#fff;padding:8px 12px;border:none;border-radius:6px;margin-bottom:15px;cursor:pointer;}
.table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table{width:100%;border-collapse:collapse;min-width:800px;box-shadow:0 2px 10px rgba(0,0,0,.08);}
th,td{text-align:center;padding:10px;font-size:14px;}
th{background:#1E4A7B;color:#fff;}
tr:nth-child(even){background:#f9f9f9;}
tr.pending{background:#fff3cd;}
td form{display:flex;gap:5px;justify-content:center;}
td input[type=number]{width:60px;padding:4px;border:1px solid #ccc;border-radius:4px;}
.btn-action{background:#0b79d0;color:#fff;padding:4px 8px;border-radius:4px;text-decoration:none;font-size:13px;}
.btn-action:hover{opacity:0.9;}
.back-link{display:inline-block;margin-top:15px;color:#1E4A7B;text-decoration:none;}
@media(max-width:600px){
    table,thead,tbody,tr,th,td{white-space:nowrap;}
}
</style>
</head>
<body>
<div class="container">
<h1>📋 <?= htmlspecialchars($lecture['title']) ?> - <?= $lecture['lecture_date'] ?></h1>

<?php if($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="post" class="add-form">
    <input type="text" name="id_number" placeholder="ادخل رقم العضو/الكود" required>
    <button name="add_attendance">➕ تسجيل حضور</button>
</form>

<form method="post">
    <button class="end-all" name="end_all" onclick="return confirm('تأكيد: تنفيذ انصراف عام لجميع الحاضرين؟')">🔚 انصراف عام لجميع الحاضرين</button>
</form>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>#</th>
<th>العضو</th>
<th>رقم الهوية</th>
<th>وقت الحضور</th>
<th>وقت الانصراف</th>
<th>المدة</th>
<th>المخصوم</th>
<th>الإجراء</th>
</tr>
</thead>
<tbody>
<?php if(empty($attendanceList)): ?>
<tr><td colspan="8">لا يوجد حضور مسجل بعد</td></tr>
<?php else: foreach($attendanceList as $i=>$a):
$displayIdNum=$a['member_idnum'] ?: $a['scanned_id_number'];
$displayName=$a['member_name'] ?:'-';
$duration = ($a['duration_minutes'] !== null) ? $a['duration_minutes'] :'-';
$rowClass = (!$a['check_out']) ?'pending' :'';
?>
<tr class="<?= $rowClass ?>">
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($displayName) ?></td>
<td><?= htmlspecialchars($displayIdNum) ?></td>
<td><?= $a['check_in'] ?:'-' ?></td>
<td><?= $a['check_out'] ?:'-' ?></td>
<td><?= $duration ?></td>
<td>
<form method="post">
<input type="number" name="deducted_minutes" value="<?= (int)$a['deducted_minutes'] ?>" min="0">
<input type="hidden" name="attendance_id" value="<?= $a['id'] ?>">
<button class="btn-action" name="update_deduct">تحديث</button>
</form>
</td>
<td>
<?php if(!$a['check_out']): ?>
<a class="btn-action" href="?lecture_id=<?= $lectureId ?>&end_one=<?= $a['id'] ?>" onclick="return confirm('تأكيد انصراف هذا العضو؟')">انصراف</a>
<?php else: ?>-
<?php endif; ?>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>

<a href="attendance.php" class="back-link">⬅ العودة</a>
</div>
</body>
</html>
