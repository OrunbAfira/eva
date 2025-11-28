<?php
// Finaliza sessão do usuário com limpeza segura
session_start();

// Limpa dados da sessão
$_SESSION = [];

// Invalida cookie de sessão se existir
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destrói a sessão
session_destroy();

// Redireciona para tela de login
header('Location: index.php');
exit;
?>