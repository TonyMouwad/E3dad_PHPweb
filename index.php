<?php
require'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$error ='';
if ($_SERVER['REQUEST_METHOD'] ==='POST') {
    $username = trim($_POST['username'] ??'');
    $password = trim($_POST['password'] ??'');

    if ($username ==='' || $password ==='') {
        $error ='ادخل اسم المستخدم وكلمة المرور';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_plain, full_name FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && $password === $user['password_plain']) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error ='بيانات الدخول غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>تسجيل الدخول - فصل حبيب جرجس</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --gold: #C9A34A;
    --maroon: #7B1E2D;
    --church-blue: #1E4A7B;
    --ivory: #F7F1E1;
    --card-bg: rgba(255,255,255,0.97);
}
*{box-sizing:border-box;margin:0;padding:0;}

body{
    font-family:'Cairo', Tahoma, Arial, sans-serif;
    background: linear-gradient(180deg, #0f2746 0%, #3b2a2a 80%);
    color:#222;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
    overflow-x:hidden;
    overflow-y:auto;
}

/* خلفية زخرفية خفيفة */
body::before{
    content:"";
    position:fixed;
    inset:0;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='800' viewBox='0 0 200 200'%3E%3Cg fill='none' stroke='%23caa55a' stroke-opacity='0.06' stroke-width='1'%3E%3Cpath d='M50 10 L150 10 L190 50 L190 150 L150 190 L50 190 L10 150 L10 50 Z'/%3E%3Ccircle cx='100' cy='100' r='60'/%3E%3C/g%3E%3C/svg%3E");
    background-size:contain;
    opacity:0.25;
    pointer-events:none;
    mix-blend-mode:overlay;
    z-index:0;
}

/* الإطار العام */
.container{
    width:100%;
    max-width:1050px;
    display:flex;
    flex-direction:row;
    background:rgba(255,255,255,0.03);
    border-radius:18px;
    box-shadow:0 20px 60px rgba(0,0,0,0.4);
    overflow:hidden;
    z-index:1;
    backdrop-filter:blur(8px);
}

/* الجزء الأيسر (المعلومات) */
.panel-hero{
    flex:1;
    background:linear-gradient(180deg, rgba(198,153,72,0.08), rgba(30,74,123,0.05));
    padding:50px 40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    color:var(--ivory);
}

.crest{
    display:flex;
    align-items:center;
    gap:18px;
    margin-bottom:20px;
}
.crest svg{
    width:72px;height:72px;
    filter:drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}
.title{
    font-size:26px;
    color:var(--gold);
    font-weight:700;
}
.slogan{
    font-size:18px;
    color:#fff;
    font-weight:600;
    margin-bottom:10px;
}
.desc{
    font-size:15px;
    line-height:1.6;
    opacity:0.95;
}
.tag{
    margin-top:24px;
    display:inline-block;
    background:rgba(255,255,255,0.08);
    padding:10px 16px;
    border-radius:12px;
    color:#fff;
    font-weight:600;
}

/* الجزء الأيمن (الفورم) */
.panel-form{
    flex:1;
    background:var(--card-bg);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:50px 30px;
}

.form-card{
    width:100%;
    max-width:400px;
}
.form-card h3{
    color:var(--maroon);
    font-size:22px;
    text-align:center;
    margin-bottom:8px;
}
.form-card p.lead{
    color:#555;
    text-align:center;
    margin-bottom:20px;
    font-size:14px;
}

.input-group{margin-bottom:15px;}
label.small{
    font-size:13px;
    color:#555;
    margin-bottom:5px;
    display:block;
}
input[type="text"], input[type="password"]{
    width:100%;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid #ddd;
    font-size:15px;
    background:#fff;
    transition:border-color .2s, box-shadow .2s;
}
input[type="text"]:focus, input[type="password"]:focus{
    border-color:var(--church-blue);
    box-shadow:0 0 6px rgba(30,74,123,0.2);
    outline:none;
}

.btn{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:linear-gradient(90deg,var(--church-blue),var(--maroon));
    color:#fff;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    transition:transform .12s ease, box-shadow .12s ease;
    box-shadow:0 10px 24px rgba(30,74,123,0.18);
}
.btn:hover{box-shadow:0 12px 28px rgba(30,74,123,0.25);}
.btn:active{transform:translateY(1px);}
.error{
    background:#ffecec;
    color:#7b1e1e;
    padding:10px;
    border-radius:8px;
    border:1px solid rgba(123,30,45,0.1);
    margin-bottom:12px;
    font-weight:600;
    text-align:center;
}
.minor{
    text-align:center;
    font-size:13px;
    color:#666;
    margin-top:10px;
}

/* 💡 تحسينات كاملة للشاشات الصغيرة */
@media (max-width:950px){
    .container{
        flex-direction:column;
        max-width:600px;
    }
    .panel-hero{
        order:2;
        text-align:center;
        padding:30px 24px;
        align-items:center;
    }
    .panel-form{
        order:1;
        padding:30px 24px;
    }
    .title{font-size:22px;}
    .slogan{font-size:16px;}
    .desc{font-size:14px;}
    .crest svg{width:60px;height:60px;}
}

/* 📱 تحسين إضافي للموبايل الصغير جدًا */
@media (max-width:480px){
    body{padding:10px;}
    .container{border-radius:10px;}
    .panel-hero{
        display:none; /* إخفاء اللوحة اليسرى على الموبايل الصغير لتبسيط الواجهة */
    }
    .panel-form{
        padding:20px 18px;
    }
    .form-card h3{font-size:20px;}
    .btn{font-size:15px;}
}
</style>

</head>
<body>
<div class="container" role="main">

    <div class="panel-hero">
        <div class="crest">
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" stroke="#C9A34A" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M32 4 L32 28" />
                    <path d="M20 20 L44 20" />
                    <circle cx="32" cy="36" r="10" />
                    <path d="M32 48 L32 60" />
                </g>
            </svg>
            <div>
                <div class="title">فصل حبيب جرجس لإعداد خدام</div>
                <div class="slogan">"لكي تكون كاملاً في المسيح يسوع"</div>
            </div>
        </div>

        <div class="desc">
            مرحبًا بك في نظام إدارة الحضور الخاص بخدمة إعداد الخدام.  
            من هنا يمكنك الدخول إلى لوحة التحكم لإدارة المحاضرات،  
            تسجيل الحضور بالـQR، أو متابعة التقارير بكل سهولة.
        </div>

        <div class="tag">مرحبًا بخدام وخادمات الكنيسة ❤️</div>
    </div>

    <div class="panel-form">
        <div class="form-card">
            <h3>تسجيل الدخول</h3>
            <p class="lead">أدخل اسم المستخدم وكلمة المرور للمتابعة</p>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <label class="small">اسم المستخدم</label>
                    <input name="username" type="text" placeholder="اسم المستخدم" required>
                </div>
                <div class="input-group">
                    <label class="small">كلمة المرور</label>
                    <input name="password" type="password" placeholder="كلمة المرور" required>
                </div>
                <button class="btn" type="submit">دخول</button>
                <div class="minor">هل نسيت كلمة المرور؟ تواصل مع المسؤول.</div>
            </form>
        </div>
    </div>

</div>
</body>
</html>
