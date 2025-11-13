<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 - ممنوع الوصول</title>
<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#C79C4B;
  --burgundy:#6C1E25;
  --darkblue:#1E3A5F;
  --ivory:#FAF5E6;
  --shadow:rgba(0,0,0,0.2);
}

*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'El Messiri',sans-serif;
  background:linear-gradient(135deg,#fef8ee,#f4eed8);
  min-height:100vh;
  display:flex;
  justify-content:center;
  align-items:center;
  text-align:center;
  color:#333;
  padding:20px;
}

.container{
  background:var(--ivory);
  padding:50px 40px;
  border-radius:25px;
  box-shadow:0 15px 40px var(--shadow);
  border:4px solid var(--gold);
  max-width:500px;
}

h1{
  font-size:4em;
  color:var(--burgundy);
  margin-bottom:20px;
}

h2{
  font-size:1.5em;
  margin-bottom:20px;
  color:var(--darkblue);
}

p{
  font-size:1em;
  margin-bottom:30px;
  color:#555;
}

button, a.button{
  background:var(--gold);
  color:var(--burgundy);
  padding:12px 25px;
  border:none;
  border-radius:15px;
  font-size:1.1em;
  font-weight:700;
  text-decoration:none;
  cursor:pointer;
  transition:0.3s;
}
button:hover, a.button:hover{
  transform:translateY(-3px);
  box-shadow:0 8px 25px var(--shadow);
}

.icon{
  font-size:5em;
  color:var(--darkblue);
  margin-bottom:20px;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container">
  <div class="icon"><i class="fa-solid fa-lock"></i></div>
  <h1>403</h1>
  <h2>ممنوع الوصول</h2>
  <p>عذرًا، ليس لديك صلاحية الوصول لهذه الصفحة. يجب أن تكون مسؤول (Admin).</p>
  <a href="dashboard.php" class="button"><i class="fa-solid fa-house"></i> العودة للصفحة الرئيسية</a>
</div>

</body>
</html>
