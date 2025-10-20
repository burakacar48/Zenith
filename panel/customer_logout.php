<?php
// Oturumu başlat
session_start();
 
// Tüm müşteri oturum değişkenlerini temizle
unset($_SESSION["customer_loggedin"]);
unset($_SESSION["customer_id"]);
unset($_SESSION["customer_unique_id"]);
unset($_SESSION["customer_name"]);
 
// Müşteri giriş sayfasına yönlendir
header("location: customer_login.php");
exit;
?>
