<?php
require'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ??'';

if ($name) {
  $stmt = $pdo->prepare("INSERT INTO attendance (name, time) VALUES (?, NOW())");
  $stmt->execute([$name]);
  echo "تم تسجيل الحضور لـ $name";
} else {
  echo "لم يتم استقبال اسم";
}
