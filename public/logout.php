<?php
/**
 * Logout - Flashnotes
 * Destrói a sessão do usuário e redireciona para o login
 */

// Inicia a sessão
// Sessão com cookie HttpOnly e SameSite
require_once __DIR__ . '/../app/helpers/sessao.php';

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se houver um cookie de sessão, remove-o
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("Location: login.php");
exit();
?>
