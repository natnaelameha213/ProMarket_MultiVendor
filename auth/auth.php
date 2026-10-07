<?php
function require_login(){
 if(empty($_SESSION["user"])){
   header("Location: auth/login.php");
   exit;
 }
}
function e($x){return htmlspecialchars((string)$x,ENT_QUOTES,"UTF-8");}
?>