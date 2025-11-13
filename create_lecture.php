<?php
require 'config.php';

// ✅ بدء الجلسة والتحقق من تسجيل الدخول
session_start();

// تحقق أولًا أن المستخدم مسجل دخول
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php'); 
    exit;
}

// تحقق أن الدور هو admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    // يمكن إعادة توجيه المستخدم إلى صفحة أخرى أو عرض رسالة خطأ
    header('Location: access_denied.php');
    exit;
}

// الاسم لعرضه
$admin_name = $_SESSION['admin_username'] ?? 'Admin';

// ✅ دوال الفلاش
function flash($msg){ $_SESSION['flash']=$msg; }
function get_flash(){ $m=$_SESSION['flash']??null; unset($_SESSION['flash']); return $m; }

/////////////////////////////////////////
// ✅ تصدير Excel باسم المحاضرة
/////////////////////////////////////////
if (isset($_GET['export_excel'], $_GET['lecture_id'])) {
    $currentLectureId = (int)$_GET['lecture_id'];

    // 🔹 جلب اسم المحاضرة
    $stmtTitle = $pdo->prepare("SELECT title FROM lectures WHERE id = ?");
    $stmtTitle->execute([$currentLectureId]);
    $lectureTitle = $stmtTitle->fetchColumn() ?: 'lecture';

    // 🔹 تنظيف الاسم ليكون صالح كاسم ملف
    $safeTitle = preg_replace('/[^\p{Arabic}\w\d_-]+/u', '_', $lectureTitle);

    // 🔹 جلب بيانات الحضور
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            COALESCE(m.name, '-') AS member_name,
            COALESCE(m.id_number, a.scanned_id_number) AS id_number,
            a.check_in,
            a.check_out,
            a.duration_minutes,
            a.deducted_minutes,
            a.status
        FROM attendance a
        LEFT JOIN members m ON a.member_id = m.id
        WHERE a.lecture_id = ?
        ORDER BY a.id ASC
    ");
    $stmt->execute([$currentLectureId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 تهيئة الملف للتنزيل
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=attendance_{$safeTitle}.xls");
    echo "\xEF\xBB\xBF"; // BOM للغة العربية

    // 🔹 رأس الجدول
    echo "ID\tالاسم\tرقم الهوية\tوقت الحضور\tوقت الانصراف\tالمدة بالدقائق\tالمخصوم\tالحالة\n";

    // 🔹 طباعة البيانات
    foreach ($rows as $r) {
        echo "{$r['id']}\t{$r['member_name']}\t{$r['id_number']}\t{$r['check_in']}\t{$r['check_out']}\t{$r['duration_minutes']}\t{$r['deducted_minutes']}\t{$r['status']}\n";
    }
    exit;
}

/////////////////////////////////////////
// ✅ حذف محاضرة
/////////////////////////////////////////
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM lectures WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('تم حذف المحاضرة بنجاح.');
    header('Location:create_lecture.php'); exit;
}

/////////////////////////////////////////
// ✅ تعديل محاضرة
/////////////////////////////////////////
if (isset($_POST['edit_lecture'])) {
    $lecId = (int)$_POST['lecture_id']; 
    $title = trim($_POST['title'] ?? '');
    if ($title !== '') {
        $pdo->prepare("UPDATE lectures SET title=? WHERE id=?")->execute([$title, $lecId]);
        flash('تم تعديل عنوان المحاضرة.');
    } else {
        flash('الرجاء إدخال عنوان صالح.');
    }
    header('Location:create_lecture.php'); exit;
}

/////////////////////////////////////////
// ✅ إنشاء محاضرة جديدة
/////////////////////////////////////////
if (isset($_POST['create_lecture'])) {
    $title = trim($_POST['title'] ?? ''); 
    $lecture_date = $_POST['lecture_date'] ?: date('Y-m-d'); 
    $lecture_time = $_POST['lecture_time'] ?: date('H:i:s');
    $notes = trim($_POST['notes'] ?? ''); 
    $created_by = $_SESSION['admin_id'];

    if ($title === '') {
        flash('الرجاء إدخال عنوان المحاضرة.');
    } else {
        $pdo->prepare("INSERT INTO lectures (title,lecture_date,lecture_time,notes,created_by,created_at) VALUES (?,?,?,?,?,NOW())")
            ->execute([$title, $lecture_date, $lecture_time, $notes, $created_by]);
        flash('تم إنشاء المحاضرة بنجاح.');
    }
    header('Location:create_lecture.php'); exit;
}

/////////////////////////////////////////
// ✅ جلب المحاضرات للعرض
/////////////////////////////////////////
$stmt = $pdo->query("
    SELECT l.*, 
           (SELECT COUNT(*) FROM attendance a WHERE a.lecture_id = l.id) AS attendees_count
    FROM lectures l
    ORDER BY l.id DESC
");
$lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);
$msg = get_flash();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📜 إدارة المحاضرات - الفن القبطي</title>
<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --gold:#C79C4B;
  --burgundy:#6C1E25;
  --darkblue:#1E3A5F;
  --ivory:#FAF5E6;
  --shadow:rgba(0,0,0,0.2);
  --btn-hover:rgba(199,156,75,0.9);
}

/* Reset */
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'El Messiri',sans-serif;
  background:linear-gradient(180deg,#fef8ee,#f4eed8);
  min-height:100vh;
  padding:20px;
  color:#333;
}

/* Container */
.container{
  max-width:1200px;
  margin: 60px auto;
  background:var(--ivory);
  border-radius:25px;
  padding:35px;
  box-shadow:0 20px 50px var(--shadow);
  border:4px solid var(--gold);
}

/* Header */
.header{
  background:linear-gradient(90deg,var(--darkblue),var(--burgundy));
  color:#fff;
  text-align:center;
  padding:25px;
  font-size:2em;
  font-weight:700;
  border-radius:20px 20px 0 0;
  position:relative;
}
.header i{
  position:absolute;
  left:25px; top:22px;
  font-size:1.6em;
  color:var(--gold);
}

/* Messages */
.msg{
  background:#E6F7E6;
  color:#046622;
  padding:14px;
  border-radius:12px;
  text-align:center;
  font-weight:600;
  margin-bottom:20px;
  box-shadow:0 3px 12px var(--shadow);
  display:flex;
  justify-content:center;
  align-items:center;
  gap:10px;
}
.msg i{color:var(--gold);}

/* Buttons */
.add-btn{
  background:var(--gold);
  color:var(--burgundy);
  padding:14px 25px;
  border:none;
  border-radius:15px;
  font-size:1.2em;
  font-weight:700;
  cursor:pointer;
  transition:0.3s;
  margin: 15px 0px;
}
.add-btn:hover{
  background:var(--btn-hover);
  transform:translateY(-3px);
  box-shadow:0 8px 20px var(--shadow);
}

/* Cards */
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
  gap:25px;
  margin-top:30px;
}
.card{
  background:#fff9f0;
  border:2px solid var(--gold);
  border-radius:20px;
  padding:25px;
  box-shadow:0 10px 30px var(--shadow);
  transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover{
  transform:translateY(-7px);
  box-shadow:0 15px 35px rgba(0,0,0,0.25);
}
.card h4{
  color:var(--darkblue);
  font-size:1.3em;
  margin-bottom:10px;
  display:flex;
  align-items:center;
  gap:10px;
}
.card .info{color:#555;font-size:0.95em;margin-bottom:6px;}
.card .notes{color:#666;font-size:0.92em;font-style:italic;margin-bottom:10px;}
.actions{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
}
.button{
  flex:1;
  padding:10px 12px;
  font-weight:600;
  border:none;
  border-radius:12px;
  cursor:pointer;
  display:flex;
  justify-content:center;
  align-items:center;
  gap:6px;
  transition:0.3s;
}
.edit-btn{background:var(--gold); color:var(--burgundy);}
.delete-btn{background:#A8323E;color:#fff;}
.excel-btn{background:#2E7D32;color:#fff;}
.button:hover{transform:scale(1.05);opacity:0.9;}

/* Modal */
.modal{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.6);
  justify-content:center;
  align-items:center;
  z-index:2000;
}
.modal-content{
  background:#FFFDF8;
  border:3px solid var(--gold);
  border-radius:20px;
  padding:30px;
  max-width:500px;
  width:90%;
  box-shadow:0 15px 35px var(--shadow);
  animation:fadeIn 0.3s ease;
}
@keyframes fadeIn{0%{opacity:0;transform:translateY(-15px);}100%{opacity:1;transform:translateY(0);}}
.close{float:left;font-size:24px;cursor:pointer;color:var(--burgundy);}
input,textarea{
  width:100%;
  padding:12px;
  margin-bottom:15px;
  border:1px solid #ccc;
  border-radius:12px;
  font-family:'El Messiri',sans-serif;
  font-size:1em;
}
input:focus,textarea:focus{border-color:var(--darkblue);outline:none;}
.btn-submit{
  background:linear-gradient(90deg,var(--darkblue),var(--burgundy));
  color:#fff;
  padding:14px;
  border:none;
  border-radius:15px;
  font-weight:700;
  width:100%;
  cursor:pointer;
  transition:0.3s;
}
.btn-submit:hover{transform:translateY(-2px);}

/* Bottom Navbar */
.bottom-navbar {
  display:none;
  position:fixed;
  bottom:0;left:0;right:0;
  height:70px;
  background:linear-gradient(90deg,var(--darkblue),var(--burgundy));
  border-top:3px solid var(--gold);
  display:flex;
  justify-content:space-around;
  align-items:center;
  box-shadow:0 -3px 15px var(--shadow);
  z-index:1000;
}
.bottom-navbar a{
  flex:1;
  color:#eee;
  text-decoration:none;
  font-size:1em;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:5px;
  transition:all 0.3s ease;
}
.bottom-navbar a.active{color:var(--gold);transform:translateY(-5px);}
.bottom-navbar a:hover{color:var(--gold);}
.bottom-navbar a i{font-size:1.5em;}
@media(max-width:800px){
  .bottom-navbar{display:flex;}
  body{padding-bottom:80px;}
}
</style>
</head>
<body>

<div class="container">
  <div class="header"><i class="fa-solid fa-scroll"></i> إدارة المحاضرات - الفن القبطي</div>

  <div class="panel-form">
    <?php if($msg): ?>
      <div class="msg"><i class="fa-solid fa-circle-check"></i> <?=htmlspecialchars($msg)?></div>
    <?php endif; ?>
    <button class="add-btn" onclick="openCreateModal()"><i class="fa-solid fa-plus"></i> إضافة محاضرة</button>

    <div class="cards">
      <?php if(empty($lectures)): ?>
        <p style="grid-column:1/-1;text-align:center;font-weight:600;color:#555;">لا توجد محاضرات حالياً</p>
      <?php else: foreach($lectures as $lec): ?>
        <div class="card">
          <h4><i class="fa-solid fa-book-open"></i> <?=htmlspecialchars($lec['title'])?></h4>
          <div class="info"><i class="fa-regular fa-calendar"></i> <?=htmlspecialchars($lec['lecture_date'])?> ⏰ <?=htmlspecialchars($lec['lecture_time'])?></div>
          <div class="info"><i class="fa-solid fa-users"></i> الحضور: <?=$lec['attendees_count']?></div>
          <div class="notes"><?=htmlspecialchars($lec['notes'] ?:'-')?></div>
          <div class="actions">
            <button class="button edit-btn" onclick="openEditModal(<?=$lec['id']?>,'<?=htmlspecialchars($lec['title'],ENT_QUOTES)?>')"><i class="fa-solid fa-pen"></i> تعديل</button>
            <a href="create_lecture.php?delete=<?=$lec['id']?>" class="button delete-btn" onclick="return confirm('تأكيد حذف المحاضرة؟ ✝')"><i class="fa-solid fa-trash"></i> حذف</a>
            <a href="create_lecture.php?export_excel=1&lecture_id=<?=$lec['id']?>" class="button excel-btn"><i class="fa-solid fa-file-excel"></i> Excel</a>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Modal إنشاء -->
<div class="modal" id="createModal">
  <div class="modal-content">
    <span class="close" onclick="closeCreateModal()">&times;</span>
    <h3><i class="fa-solid fa-plus"></i> إنشاء محاضرة جديدة</h3>
    <form method="post">
      <label>عنوان المحاضرة *</label>
      <input type="text" name="title" required>
      <label>تاريخ المحاضرة</label>
      <input type="date" name="lecture_date" value="<?=date('Y-m-d')?>">
      <label>وقت المحاضرة</label>
      <input type="time" name="lecture_time" value="<?=date('H:i')?>">
      <label>ملاحظات</label>
      <textarea name="notes" rows="3"></textarea>
      <button type="submit" name="create_lecture" class="btn-submit"><i class="fa-solid fa-check"></i> إنشاء</button>
    </form>
  </div>
</div>

<!-- Modal تعديل -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h3><i class="fa-solid fa-pen"></i> تعديل عنوان المحاضرة</h3>
    <form method="post">
      <input type="hidden" name="lecture_id" id="editLectureId">
      <label>عنوان المحاضرة *</label>
      <input type="text" name="title" id="editLectureTitle" required>
      <button type="submit" name="edit_lecture" class="btn-submit"><i class="fa-solid fa-check"></i> تحديث</button>
    </form>
  </div>
</div>

<!-- Bottom Navbar -->
<nav class="bottom-navbar">
  <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>"><i class="fa-solid fa-house"></i><span>الرئيسية</span></a>
  <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF'])=='attendance.php'?'active':'' ?>"><i class="fa-solid fa-clipboard-check"></i><span>الحضور</span></a>
  <a href="create_lecture.php" class="<?= basename($_SERVER['PHP_SELF'])=='create_lecture.php'?'active':'' ?>"><i class="fa-solid fa-gear"></i><span>المحاضرات</span></a>
  <a href="reports.php" class="<?= basename($_SERVER['PHP_SELF'])=='reports.php'?'active':'' ?>"><i class="fa-solid fa-chart-column"></i><span>التقارير</span></a>
</nav>

<script>
function openCreateModal(){document.getElementById('createModal').style.display='flex';}
function closeCreateModal(){document.getElementById('createModal').style.display='none';}
function openEditModal(id,title){
  document.getElementById('editLectureId').value=id;
  document.getElementById('editLectureTitle').value=title;
  document.getElementById('editModal').style.display='flex';
}
function closeEditModal(){document.getElementById('editModal').style.display='none';}
window.onclick=function(e){
  if(e.target.id==='createModal')closeCreateModal();
  if(e.target.id==='editModal')closeEditModal();
}
</script>

</body>
</html>
