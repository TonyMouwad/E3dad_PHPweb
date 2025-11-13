<?php
require'config.php';
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php'); exit;
}
$admin_name = $_SESSION['admin_username'] ??'Admin';

// عرض إحصاءات بسيطة
$stmt = $pdo->query('SELECT COUNT(*) FROM lectures'); 
$lectures_count = $stmt->fetchColumn();
$stmt = $pdo->query('SELECT COUNT(*) FROM members'); 
$members_count = $stmt->fetchColumn();
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>لوحة التحكم - نظام الحضور</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --primary:#1E4A7B;
  --secondary:#7B1E2D;
  --gold:#C9A34A;
  --bg:#F7F7F9;
  --card:#fff;
}
*{box-sizing:border-box;}
body{
  font-family:'Cairo',Tahoma,Arial,sans-serif;
  margin:0;
  min-height:100vh;
  background: linear-gradient(180deg,#0f2746 0%,#3b2a2a 60%);
  color:#222;
  padding:20px;
  display:flex;
  justify-content:center;
  align-items:flex-start;
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
  width:100%;
  max-width:1000px;
  z-index:1;
}
header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:30px;
  color:#fff;
  flex-wrap:wrap;
  gap:10px;
}
header h2{
  margin:0;
  color:var(--gold);
  font-size:clamp(18px,2.5vw,26px);
}
header a{
  text-decoration:none;
  color:#fff;
  background: var(--secondary);
  padding:8px 14px;
  border-radius:6px;
  font-weight:600;
  transition:background 0.3s;
}
header a:hover{background:#9d253a;}
.stats{
  display:flex;
  gap:20px;
  flex-wrap:wrap;
  margin-bottom:30px;
  justify-content:center;
}
.card{
  background: rgba(255,255,255,0.95);
  padding:24px;
  border-radius:14px;
  flex:1 1 220px;
  max-width:300px;
  box-shadow:0 8px 20px rgba(0,0,0,0.08);
  text-align:center;
  transition:transform .2s ease, box-shadow .2s ease;
}
.card:hover{
  transform:translateY(-5px);
  box-shadow:0 10px 30px rgba(0,0,0,0.15);
}
.card i{
  font-size:40px;
  margin-bottom:12px;
  color:var(--gold);
  transition:transform .3s;
}
.card:hover i{transform:scale(1.1);}
.card h3{
  margin:0 0 10px 0;
  color:var(--primary);
}
.card p{
  font-size:22px;
  font-weight:700;
  margin:0;
  color:var(--secondary);
}
.actions{
  display:flex;
  gap:16px;
  flex-wrap:wrap;
  justify-content:center;
}
.actions a{
  flex:1 1 240px;
  text-align:center;
  padding:18px;
  border-radius:14px;
  text-decoration:none;
  color:#fff;
  font-weight:600;
  font-size:18px;
  transition:transform .15s ease, box-shadow .15s ease, background .3s;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
}
.actions a i{font-size:22px;}
.actions a.attendance{
  background: var(--primary);
  box-shadow:0 6px 16px rgba(30,74,123,0.25);
}
.actions a.lecture{
  background: var(--secondary);
  box-shadow:0 6px 16px rgba(123,30,45,0.25);
}
.actions a:hover{
  transform:translateY(-3px);
  box-shadow:0 10px 24px rgba(0,0,0,0.2);
}
@media(max-width:600px){
  body{padding:10px;}
  .card p{font-size:18px;}
  .actions a{font-size:16px; padding:14px;}
  .card i{font-size:34px;}
}
</style>
</head>
<body>
<div class="container">
<header>
  <h2>مرحبًا، <?=htmlspecialchars($admin_name)?></h2>
  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
</header>

<section class="stats">
  <div class="card">
    <i class="fas fa-chalkboard-teacher"></i>
    <h3>عدد المحاضرات</h3>
    <p><?= $lectures_count ?></p>
  </div>
  <div class="card">
    <i class="fas fa-users"></i>
    <h3>عدد الأعضاء</h3>
    <p><?= $members_count ?></p>
  </div>
</section>

<section class="actions">
  <a href="attendance.php" class="attendance">
    <i class="fas fa-clipboard-check"></i> تسجيل الحضور
  </a>
  <a href="create_lecture.php" class="lecture">
    <i class="fas fa-book-open"></i> إدارة المحاضرات
  </a>
</section>
</div>
</body>
</html>
