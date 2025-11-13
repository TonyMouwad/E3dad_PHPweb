<?php
// db.php - ملف الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = ""; // عدّل لو فيه باسورد
$dbname = "attendance_db_plain";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين الترميز
$conn->set_charset("utf8mb4");
?>
