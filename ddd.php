<?php
require'config.php';
if(session_status()===PHP_SESSION_NONE) session_start();
if(empty($_SESSION['admin_id'])){header('Location:index.php'); exit;}

function flash($msg){ $_SESSION['flash']=$msg; }
function get_flash(){ $m=$_SESSION['flash']??null; unset($_SESSION['flash']); return $m; }

// إضافة نتيجة
if(isset($_POST['add_result'])){
    $subject = trim($_POST['subject']??'');
    $student_code = trim($_POST['student_code']??'');
    $score = trim($_POST['score']??'');
    
    if($subject && $student_code && is_numeric($score)){
        $stmt = $pdo->prepare("INSERT INTO exam_results (subject, student_code, score, created_at) VALUES (?,?,?,NOW())");
        $stmt->execute([$subject,$student_code,$score]);
        flash('تم إضافة النتيجة بنجاح.');
        header('Location:exam_results.php'); exit;
    } else {
        flash('الرجاء إدخال جميع الحقول بشكل صحيح.');
    }
}

// حذف نتيجة
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $pdo->prepare("DELETE FROM exam_results WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('تم حذف النتيجة.');
    header('Location:exam_results.php'); exit;
}

// جلب جميع النتائج
$results = $pdo->query("SELECT * FROM exam_results ORDER BY created_at DESC")->fetchAll();
$msg = get_flash();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📝 نتائج الامتحانات</title>
<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#C9A34A;
  --deep-red:#6C1E25;
  --deep-blue:#1E3A5F;
  --ivory:#FAF5E6;
  --paper:#FFF8E7;
  --shadow:rgba(0,0,0,0.2);
}
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:'El Messiri',sans-serif;
  background:linear-gradient(180deg,#f9f4ea,#f0e8d0);
  color:#333;
  min-height:100vh;
  display:flex;
}
.container{
  flex:1;
  max-width:900px;
  margin:30px auto;
  background:var(--paper);
  border-radius:20px;
  box-shadow:0 15px 40px var(--shadow);
  padding:25px;
}
.header{
  text-align:center;
  font-size:1.6em;
  font-weight:600;
  margin-bottom:20px;
  color:var(--deep-blue);
}
.msg{
  background:#e7fbe7;
  color:#084;
  padding:10px;
  border-radius:8px;
  text-align:center;
  font-weight:600;
  margin-bottom:12px;
}
form{
  display:flex;
  flex-wrap:wrap;
  gap:15px;
  margin-bottom:20px;
}
form input{
  flex:1 1 200px;
  padding:10px;
  border-radius:10px;
  border:1px solid #ccc;
}
form button{
  padding:10px 20px;
  border:none;
  border-radius:10px;
  font-weight:700;
  background:linear-gradient(90deg,var(--deep-blue),var(--deep-red));
  color:#fff;
  cursor:pointer;
  transition:0.2s;
}
form button:hover{opacity:0.9;}
table{
  width:100%;
  border-collapse:collapse;
  margin-top:10px;
}
table th, table td{
  padding:10px;
  border:1px solid var(--gold);
  text-align:center;
}
table th{
  background:var(--deep-blue);
  color:#fff;
}
.delete-btn{
  background:#b23a3a;
  color:#fff;
  padding:5px 10px;
  border-radius:8px;
  text-decoration:none;
  font-weight:600;
  transition:0.2s;
}
.delete-btn:hover{opacity:0.8;}
@media(max-width:600px){
  form{flex-direction:column;}
}
</style>
</head>
<body>
<div class="container">
  <div class="header">📝 إدارة نتائج الامتحانات</div>
  <?php if($msg): ?><div class="msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>

  <!-- نموذج إضافة نتيجة -->
  <form method="post">
    <input type="text" name="subject" placeholder="اسم المادة" required>
    <input type="text" name="student_code" placeholder="كود الطالب" required>
    <input type="number" name="score" placeholder="الدرجة" min="0" max="100" required>
    <button type="submit" name="add_result">➕ إضافة</button>
  </form>

  <!-- جدول النتائج -->
  <table>
    <thead>
      <tr>
        <th>المادة</th>
        <th>كود الطالب</th>
        <th>الدرجة</th>
        <th>تاريخ الإضافة</th>
        <th>حذف</th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($results)): ?>
        <tr><td colspan="5">لا توجد نتائج حالياً</td></tr>
      <?php else: foreach($results as $res): ?>
        <tr>
          <td><?=htmlspecialchars($res['subject'])?></td>
          <td><?=htmlspecialchars($res['student_code'])?></td>
          <td><?=htmlspecialchars($res['score'])?></td>
          <td><?=htmlspecialchars($res['created_at'])?></td>
          <td><a href="exam_results.php?delete=<?=$res['id']?>" class="delete-btn" onclick="return confirm('تأكيد حذف النتيجة؟')">حذف</a></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
