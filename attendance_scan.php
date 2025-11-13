<?php
require'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$lectureId = isset($_GET['lecture_id']) ? (int)$_GET['lecture_id'] : 0;
if ($lectureId <= 0) die("محاضرة غير محددة.");

$stmt = $pdo->prepare("SELECT title, lecture_date FROM lectures WHERE id = ?");
$stmt->execute([$lectureId]);
$lecture = $stmt->fetch();
if (!$lecture) die("المحاضرة غير موجودة.");

if ($_SERVER['REQUEST_METHOD'] ==='POST' && !empty($_POST['id_number'])) {
    $idNumber = trim($_POST['id_number']);
    $result = ['success' => false,'message' =>'','name' =>''];

    $stmt = $pdo->prepare("SELECT id, name FROM members WHERE id_number = ? LIMIT 1");
    $stmt->execute([$idNumber]);
    $member = $stmt->fetch();

    if ($member) {
        $memberId = $member['id'];
        $name = $member['name'];

        $check = $pdo->prepare("SELECT id FROM attendance WHERE lecture_id = ? AND member_id = ? LIMIT 1");
        $check->execute([$lectureId, $memberId]);
        $row = $check->fetch();

        if ($row) {
            $result['success'] = false;
            $result['message'] = "العضو $name مسجّل حضوره بالفعل.";
            $result['name'] = $name;
        } else {
            $ins = $pdo->prepare("INSERT INTO attendance (lecture_id, member_id, scanned_id_number, check_in, status, created_at) VALUES (?, ?, ?, NOW(),'present', NOW())");
            $ins->execute([$lectureId, $memberId, $idNumber]);
            $result['success'] = true;
            $result['message'] = "تم تسجيل الحضور ✅";
            $result['name'] = $name;
        }
    } else {
        $ins = $pdo->prepare("INSERT INTO attendance (lecture_id, scanned_id_number, check_in, status, created_at) VALUES (?, ?, NOW(),'pending', NOW())");
        $ins->execute([$lectureId, $idNumber]);
        $result['success'] = false;
        $result['message'] = "لم يُعثر على العضو. تم إنشاء سجل مُعلق.";
        $result['name'] ='-';
    }

    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>📷 مسح QR - <?= htmlspecialchars($lecture['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body,html{
  margin:0;padding:0;height:100%;overflow:hidden;
  font-family:'Cairo',sans-serif;background:#0f1a2a;color:#fff;
}
#video{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;filter:brightness(0.8);}
#overlay{position:absolute;inset:0;/*background:rgba(0,0,0,0.4); backdrop-filter:blur(2px);*/}
#scan-frame{
  position:absolute;top:30%;left:20%;width:60%;height:40%;
  border:3px solid #d4af37;border-radius:12px;
  box-shadow:0 0 40px rgba(212,175,55,0.6);
  animation:pulse 1.8s infinite alternate;
}
@keyframes pulse{from{box-shadow:0 0 10px rgba(212,175,55,.4);}to{box-shadow:0 0 35px rgba(212,175,55,.9);}}
#lecture-info{
  position:fixed;top:15px;left:50%;transform:translateX(-50%);
  background:rgba(15,26,42,0.85);border:1px solid #d4af37;
  color:#f9eec2;padding:8px 16px;border-radius:10px;
  font-weight:700;font-size:1.1em;z-index:10;
}
#messageBox{
  position:fixed;bottom:8%;left:50%;transform:translateX(-50%);
  background:rgba(255,255,255,0.1);padding:16px 24px;border-radius:14px;
  text-align:center;font-weight:bold;min-width:260px;display:none;
  transition:opacity .3s ease;
}
#messageBox.success{background:rgba(0,255,120,0.85);color:#000;}
#messageBox.error{background:rgba(255,0,0,0.85);color:#fff;}
#flashBtn{
  position:fixed;top:15px;right:15px;z-index:12;
  background:rgba(212,175,55,0.9);color:#111;border:none;
  border-radius:50%;width:46px;height:46px;cursor:pointer;
  font-size:20px;font-weight:bold;
  box-shadow:0 0 10px rgba(0,0,0,0.3);
}
#backBtn{
  position:fixed;top:15px;left:15px;z-index:12;
  background:none;border:2px solid #d4af37;color:#d4af37;
  border-radius:10px;padding:6px 12px;cursor:pointer;font-weight:bold;
}
#instruction{
  position:fixed;bottom:5%;width:100%;text-align:center;
  font-size:1.1em;color:#f9eec2;text-shadow:0 0 6px rgba(0,0,0,0.6);
}
</style>
</head>
<body>
<video id="video" autoplay playsinline></video>
<div id="overlay"></div>
<div id="scan-frame"></div>
<div id="lecture-info"><?= htmlspecialchars($lecture['title']) ?> — <?= htmlspecialchars($lecture['lecture_date']) ?></div>
<button id="flashBtn">💡</button>
<button id="backBtn" onclick="history.back()">← رجوع</button>
<div id="messageBox"></div>
<div id="instruction">ضع QR داخل الإطار لمسحه</div>

<script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
<script>
const video = document.getElementById('video');
const msg = document.getElementById('messageBox');
const flashBtn = document.getElementById('flashBtn');

let scanning = true, lastData = null, track;
let scanningPaused = false; // 👈 متغيّر جديد لوقف المسح مؤقتًا بعد القراءة
let lastScanTime = 0;

navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
  .then(stream => {
    video.srcObject = stream;
    track = stream.getVideoTracks()[0];
    requestAnimationFrame(scan);
  })
  .catch(e => alert("تعذر تشغيل الكاميرا: " + e.message));

const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');

function showMessage(name, message, ok) {
  msg.className = ok ?'success' :'error';
  msg.textContent = name + " → " + message;
  msg.style.display ='block';
  msg.style.opacity ='1';
  setTimeout(() => { msg.style.opacity ='0'; }, 800);
}

// دالة المسح الدقيقة (تركّز فقط داخل المربع)
function scan() {
  if (!scanning) { requestAnimationFrame(scan); return; }

  const now = performance.now();
  if (now - lastScanTime < 100) { requestAnimationFrame(scan); return; }
  lastScanTime = now;

  if (video.readyState === video.HAVE_ENOUGH_DATA && !scanningPaused) {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const frame = document.getElementById('scan-frame');
    const rect = frame.getBoundingClientRect();
    const videoRect = video.getBoundingClientRect();

    const scaleX = canvas.width / videoRect.width;
    const scaleY = canvas.height / videoRect.height;

    const sx = (rect.left - videoRect.left) * scaleX;
    const sy = (rect.top - videoRect.top) * scaleY;
    const sw = rect.width * scaleX;
    const sh = rect.height * scaleY;

    // تكبير المنطقة 2x لتحسين القراءة
    const tmpCanvas = document.createElement('canvas');
    const tmpCtx = tmpCanvas.getContext('2d');
    tmpCanvas.width = sw * 2;
    tmpCanvas.height = sh * 2;
    tmpCtx.drawImage(canvas, sx, sy, sw, sh, 0, 0, tmpCanvas.width, tmpCanvas.height);

    const img = tmpCtx.getImageData(0, 0, tmpCanvas.width, tmpCanvas.height);
    const code = jsQR(img.data, tmpCanvas.width, tmpCanvas.height);

    if (code) {
      const now2 = Date.now();
      if (code.data !== lastData) {
        lastData = code.data;
        scanningPaused = true; // ⏸️ أوقف المسح مؤقتًا أثناء المعالجة

        let id = code.data;
        try {
          const d = JSON.parse(code.data);
          if (d.idNumber) id = d.idNumber;
        } catch { /* إذا مش JSON */ }

        fetch('', {
          method:'POST',
          headers: {'Content-Type':'application/x-www-form-urlencoded' },
          body:'id_number=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(r => {
          showMessage(r.name, r.message, r.success);
          if (navigator.vibrate) navigator.vibrate(150);
          const audio = new Audio('https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg');
          audio.play().catch(() => {});
        })
        .catch(() => {
          showMessage("خطأ", "تعذر الاتصال بالخادم", false);
        })
        .finally(() => {
          // ✅ إعادة التمكين بعد ثانية ونصف (مهلة بسيطة قبل قراءة كود جديد)
          setTimeout(() => {
            scanningPaused = false;
            lastData = null;
          }, 1500);
        });
      }
    }
  }
  requestAnimationFrame(scan);
}

// التحكم في الفلاش
let flashOn = false;
flashBtn.onclick = () => {
  if (!track) return;
  const cap = track.getCapabilities();
  if (cap.torch) {
    flashOn = !flashOn;
    track.applyConstraints({ advanced: [{ torch: flashOn }] });
    flashBtn.style.background = flashOn ?'#fff' :'rgba(212,175,55,0.9)';
    flashBtn.style.color = flashOn ?'#000' :'#111';
  } else alert("الفلاش غير مدعوم في هذا الجهاز");
};
</script>


</body>
</html>
