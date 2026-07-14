```php
<?php
/**
 * ==========================================
 * SGC v1.0
 * Authentication Check
 * ==========================================
 */

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION["user_id"])) {

    header("Location: ../auth/login.php");
    exit();

}

// معلومات المستخدم
$user_id = $_SESSION["user_id"];
$fullname = $_SESSION["fullname"];
$role = $_SESSION["role"];
```
