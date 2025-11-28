<?php
session_start();

require_once 'config.php'; // Inicializa conexão `$conexao`

if ($conexao->connect_error) {
    die('Erro de conexão: ' . $conexao->connect_error);
}

// Registro simples de tentativas de login em arquivo local
function log_login_attempt($email, $status) {
    $log_file = __DIR__ . '/assets/debug/login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $log_message = "[$timestamp] - IP: $ip_address - Email: $email - Status: $status" . PHP_EOL;
    
    // Cria a pasta de debug se não existir
    if (!is_dir(__DIR__ . '/assets/debug')) {
        mkdir(__DIR__ . '/assets/debug', 0777, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
// Fim do registro de login

$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$senha = $_POST['password'] ?? '';

// Consulta preparada e tratamento de erro amigável
$stmt = $conexao->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
if ($stmt === false) {
    log_login_attempt($email, 'ERRO - Query prepare falhou: ' . ($conexao->error ?: 'desconhecido'));
    header('Location: index.php?erro=erro_interno');
    exit;
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    log_login_attempt($email, 'ERRO - Query execute falhou: ' . ($stmt->error ?: 'desconhecido'));
    header('Location: index.php?erro=erro_interno');
    exit;
}
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $nome, $senha_hash);
    $stmt->fetch();
    // Diagnóstico: formato do hash de senha
    $hashInfo = 'unknown';
    if (is_string($senha_hash)) {
        if (strpos($senha_hash, '$2y$') === 0 || strpos($senha_hash, '$2a$') === 0) { $hashInfo = 'bcrypt'; }
        elseif (strpos($senha_hash, '$argon2') === 0) { $hashInfo = 'argon2'; }
        elseif (preg_match('/^[a-f0-9]{32}$/i', $senha_hash)) { $hashInfo = 'md5-like'; }
        elseif (preg_match('/^[a-f0-9]{40}$/i', $senha_hash)) { $hashInfo = 'sha1-like'; }
        else { $hashInfo = 'plain/other'; }
    }
    log_login_attempt($email, 'DEBUG - hash format: ' . $hashInfo);

    if (password_verify($senha, $senha_hash)) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nome'] = $nome;

        // 2FA: gera e envia código se habilitado
        $twofaEnabled = !empty($_SESSION['twofa_enabled']);
        // Lê flag do banco (usuarios.twofa_enabled)
        $st3 = $conexao->prepare('SELECT twofa_enabled, email FROM usuarios WHERE id = ? LIMIT 1');
        $emailDestino = $email;
        if ($st3) {
            $st3->bind_param('i', $id);
            if ($st3->execute()) {
                $st3->bind_result($twofaVal, $emailDb);
                if ($st3->fetch()) {
                    $twofaEnabled = (bool)$twofaVal;
                    if (!empty($emailDb)) { $emailDestino = $emailDb; }
                }
            }
            $st3->close();
        }

        if ($twofaEnabled) {
            $codigo = random_int(100000, 999999);
            $_SESSION['twofa_code'] = $codigo;
            $_SESSION['twofa_expires'] = time() + 300; // expira em 5 minutos
            $_SESSION['twofa_pending'] = true;
            $_SESSION['twofa_email'] = $email;

            // Envia e-mail com o código 2FA
            require_once __DIR__ . '/lib/smtp_send.php';
            require_once __DIR__ . '/mail_config.php';
            $assunto = 'Seu código de verificação (2FA)';
            $html = '<div style="font-family:Arial,sans-serif;font-size:16px">'
                . '<p>Olá, ' . htmlspecialchars($nome) . '.</p>'
                . '<p>Seu código de verificação é <strong>' . $codigo . '</strong>.</p>'
                . '<p>Ele expira em 5 minutos.</p>'
                . '<p>Se não foi você, ignore este e-mail.</p>'
                . '<p>— EVA</p>'
                . '</div>';
            $okEmail = @smtp_send($emailDestino, $assunto, $html, SMTP_FROM, SMTP_FROM_NAME, SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_ENCRYPTION);
            $_SESSION['twofa_last_sent'] = time();
            if (!$okEmail) { log_login_attempt($email, 'AVISO - falha ao enviar 2FA'); }

            log_login_attempt($email, 'SUCESSO - 2FA requerido');

            $stmt->close();
            $conexao->close();
            header('Location: verificar_2fa.php');
            exit;
        }

        log_login_attempt($email, 'SUCESSO - redirecionando para dash');

        $stmt->close();
        $conexao->close();
        
        // Log antes do redirecionamento
        @file_put_contents(__DIR__ . '/assets/debug/login_attempts.log', '['.date('Y-m-d H:i:s')."] Redirect -> dash.php\n", FILE_APPEND);
        header('Location: dash.php');
        exit;
    } else {
        log_login_attempt($email, 'FALHA - Senha incorreta');
        $stmt->close();
        $conexao->close();
        
        header('Location: index.php?erro=login_invalido');
        exit;
    }
} else {
    log_login_attempt($email, 'FALHA - Email não encontrado');
    $stmt->close();
    $conexao->close();
    
    header('Location: index.php?erro=login_invalido');
    exit;
}
?>