```php
<?php
/**
 * ==========================================
 * SGC v1.0
 * Logout
 * ==========================================
 */

session_start();

// حذف جميع متغيرات الجلسة
$_SESSION = [];

// حذف Cookie ديال Session إذا كانت موجودة
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// تدمير الجلسة
session_destroy();

// الرجوع لصفحة Login
header("Location: login.php");
exit();
```
