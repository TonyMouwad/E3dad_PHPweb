<?php
require'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php'); exit;
}

// ترتيب المحاضرات حسب التاريخ الأقرب للمستقبل
$stmt = $pdo->query("SELECT id, title, lecture_date FROM lectures ORDER BY lecture_date ASC");
$lectures = $stmt->fetchAll();

$selectedLectureId = !empty($_GET['lecture_id']) ? (int)$_GET['lecture_id'] : null;
?>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>إدارة الحضور والانصراف</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --gold:#C9A34A;
  --maroon:#7B1E2D;
  --church-blue:#1E4A7B;
  --ivory:#F7F1E1;
  --card-bg:rgba(255,255,255,0.95);
}
*{box-sizing:border-box;}
body{
  font-family:'Cairo',Tahoma,Arial,sans-serif;
  margin:0;
  min-height:100vh;
  background: linear-gradient(180deg,#0f2746 0%,#3b2a2a 60%);
  color:#222;
  padding:30px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:flex-start;
  position:relative;
}
body::before{
  content:"";
  position:fixed;
  inset:0;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='800' viewBox='0 0 200 200'><g fill='none' stroke='%23caa55a' stroke-opacity='0.06' stroke-width='1'><path d='M50 10 L150 10 L190 50 L190 150 L150 190 L50 190 L10 150 L10 50 Z'/><circle cx='100' cy='100' r='60'/></g></svg>");
  opacity:0.2;
  pointer-events:none;
  mix-blend-mode:overlay;
}
.container{
  max-width:600px;
  width:100%;
  background: var(--card-bg);
  border-radius:18px;
  padding:28px;
  box-shadow:0 10px 28px rgba(0,0,0,0.15);
  margin-bottom:90px; /* مساحة لشريط التنقل */
}
h2{
  text-align:center;
  color:var(--gold);
  margin-top:0;
  margin-bottom:20px;
}
.form-group{
  display:flex;
  flex-direction:column;
  gap:10px;
  margin-bottom:20px;
}
label{
  font-weight:600;
  color:var(--church-blue);
}
select{
  padding:12px;
  font-size:16px;
  border-radius:10px;
  border:1px solid #ccc;
  outline:none;
  transition: all .18s ease;
}
select:focus{
  border-color: var(--church-blue);
  box-shadow:0 6px 18px rgba(30,74,123,0.08);
}
.actions{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  justify-content:center;
}
.actions a, .actions button{
  flex:1 1 130px;
  text-decoration:none;
  font-weight:700;
  padding:12px;
  border-radius:12px;
  color:#fff;
  text-align:center;
  transition:transform .12s ease, box-shadow .12s ease;
  border:none;
  cursor:pointer;
}
.actions a.scan, .actions button{
  background: linear-gradient(90deg,var(--church-blue),var(--gold));
  box-shadow:0 6px 16px rgba(30,74,123,0.2);
}
.actions a.details{
  background: linear-gradient(90deg,var(--maroon),var(--gold));
  box-shadow:0 6px 16px rgba(123,30,45,0.2);
}
.actions a:hover, .actions button:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 24px rgba(0,0,0,0.2);
}

/* ✅ شريط التنقل السفلي */
.navbar{
  position:fixed;
  bottom:0;
  left:0;
  right:0;
  background:rgba(255,255,255,0.95);
  backdrop-filter:blur(10px);
  box-shadow:0 -4px 12px rgba(0,0,0,0.15);
  display:flex;
  justify-content:space-around;
  align-items:center;
  padding:10px 0;
  border-top:2px solid rgba(201,163,74,0.25);
  z-index:99;
}
.navbar a{
  text-decoration:none;
  color:var(--church-blue);
  font-size:22px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:4px;
  transition:color .2s, transform .2s;
}
.navbar a span{
  font-size:12px;
  font-weight:600;
}
.navbar a:hover{
  color:var(--gold);
  transform:translateY(-3px);
}
.navbar a.active{
  color:var(--gold);
}
@media(max-width:600px){
  body{padding:15px;}
  .container{padding:20px;}
  select{font-size:15px;}
  .navbar a span{font-size:11px;}
}
</style>
</head>
<body>

<div class="container">
  <h2>📋 إدارة الحضور والانصراف</h2>

  <form method="get">
    <div class="form-group">
      <label for="lecture_id">اختر المحاضرة:</label>
      <select name="lecture_id" id="lecture_id" required onchange="this.form.submit()">
        <option value="">-- اختر --</option>
        <?php foreach($lectures as $lec): ?>
          <option value="<?= $lec['id'] ?>" <?= ($lec['id'] === $selectedLectureId) ?'selected' :'' ?>>
            <?= htmlspecialchars($lec['title']) ?> (<?= $lec['lecture_date'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if($selectedLectureId): ?>
    <div class="actions">
      <a href="attendance_scan.php?lecture_id=<?= $selectedLectureId ?>" class="scan">
        📷 عمل اسكان
      </a>
      <a href="attendance_details.php?lecture_id=<?= $selectedLectureId ?>" class="details">
        📋 جدول الحاضرين
      </a>
    </div>
    <?php endif; ?>
  </form>
</div>

<!-- ✅ شريط التنقل السفلي -->
<nav class="navbar">
  <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">
    <i class="fa-solid fa-house"></i>
    <span>الرئيسية</span>
  </a>
  <a href="attendance.php" class="<?= basename($_SERVER['PHP_SELF'])=='attendance.php'?'active':'' ?>">
    <i class="fa-solid fa-clipboard-check"></i>
    <span>الحضور</span>
  </a>
  <a href="create_lecture.php" class="<?= basename($_SERVER['PHP_SELF'])=='create_lecture.php'?'active':'' ?>">
    <i class="fa-solid fa-gear"></i>
    <span>الإعدادات</span>
  </a>
</nav>

</body>
</html>
