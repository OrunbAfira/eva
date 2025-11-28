<?php
// Inicializa sessão se necessário
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Tempo máximo de inatividade (segundos)
$timeout = 300; // 5 minutos

// Exige usuário autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Se 2FA está pendente, força verificação (exceto se já na página)
$current = basename($_SERVER['PHP_SELF'] ?? '');
if (!empty($_SESSION['twofa_pending']) && $current !== 'verificar_2fa.php') {
    header('Location: verificar_2fa.php');
    exit;
}

$now = time();
if (isset($_SESSION['last_activity'])) {
    $inativo = $now - (int)$_SESSION['last_activity'];
    if ($inativo > $timeout) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', $now - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: index.php?session=expired');
        exit;
    }
}

$_SESSION['last_activity'] = $now;
