<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se houver um cookie de sessão, destruí-lo também
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: ../pages/login.php");
exit();
