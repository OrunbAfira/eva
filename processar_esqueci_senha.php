<?php
// Handler para envio de e-mail ao solicitar recuperação de senha
// Politica: não revelar se o e-mail existe; resposta sempre positiva.

// Opcional: usar conexão caso queira verificar existência sem expor (não obrigatório)
require_once __DIR__ . '/config.php';
// Configuração de e-mail (SMTP opcional)
@require_once __DIR__ . '/mail_config.php';
// Autoload do Composer (PHPMailer) se existir
@include_once __DIR__ . '/vendor/autoload.php';
// SMTP mínimo sem Composer
@require_once __DIR__ . '/lib/smtp_send.php';

// Função utilitária para renderizar uma página simples de resposta
function renderizarResposta($titulo, $mensagem, $link = 'index.php', $linkTexto = 'Voltar ao login') {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titulo) . '</title>';
    echo '<style>*{box-sizing:border-box}body{font-family:Segoe UI,Tahoma,Arial,sans-serif;background:#f4f7f6;margin:0;padding:0;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#2c3e50}';
    echo '.card{background:#fff;max-width:520px;width:92%;padding:28px 26px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.08)}';
    echo 'h1{font-size:22px;margin:0 0 10px}p{margin:10px 0 0;line-height:1.55}a.btn{display:inline-block;margin-top:18px;background:#3498db;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px}';
    echo '.hint{margin-top:12px;font-size:13px;color:#7f8c8d}</style></head><body>';
    echo '<div class="card">';
    echo '<h1>' . htmlspecialchars($titulo) . '</h1>';
    echo '<p>' . $mensagem . '</p>';
    echo '<a class="btn" href="' . htmlspecialchars($link) . '">' . htmlspecialchars($linkTexto) . '</a>';
    echo '</div></body></html>';
}

// Sanitização e validação
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    renderizarResposta('Solicitação inválida', 'O e-mail informado é inválido. Por favor, tente novamente.', 'esqueci_senha.php', 'Voltar');
    exit;
}

// Garante tabela de tokens
if ($conexao instanceof mysqli) {
    $conexao->query("CREATE TABLE IF NOT EXISTS recuperacao_senha (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX(token), INDEX(email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Verifica se usuário existe
$usuarioExiste = false;
if ($conexao instanceof mysqli) {
    if ($stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1')) {
        $stmt->bind_param('s', $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            $usuarioExiste = ($stmt->num_rows > 0);
        }
        $stmt->close();
    }
}

// Gera token (sempre gera para não expor existência) e salva apenas se usuário existir
$token = bin2hex(random_bytes(32));
$expiraEm = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
if ($usuarioExiste && $conexao instanceof mysqli) {
    if ($stmt = $conexao->prepare('INSERT INTO recuperacao_senha (email, token, expires_at, used, created_at) VALUES (?,?,?,?,NOW())')) {
        $used = 0;
        $stmt->bind_param('sssi', $email, $token, $expiraEm, $used);
        $stmt->execute();
        $stmt->close();
    }
}

// Monta link de redefinição
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim(dirname($_SERVER['REQUEST_URI'] ?? '/'), '/\\') . '/';
$resetUrl = $scheme . '://' . $host . $base . 'redefinir_senha.php?token=' . urlencode($token);

// Conteúdo do e-mail (inclui link de redefinição)
$assunto = 'EVA - Redefinição de senha';
$mensagemHtml = '<p>Olá,</p>' .
    '<p>Recebemos sua solicitação de recuperação de senha. Se existir uma conta associada a este e-mail, você pode redefinir sua senha pelo link abaixo. Este link expira em 1 hora.</p>' .
    '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Redefinir minha senha</a></p>' .
    '<p>Se você não solicitou, pode ignorar esta mensagem.</p>' .
    '<p>— Equipe EVA</p>';
// Texto alternativo (para clientes que não renderizam HTML)
$mensagemTexto = "Olá,\r\n\r\nRecebemos sua solicitação de recuperação de senha. Se existir uma conta associada a este e-mail, use o link abaixo para redefinir sua senha (expira em 1 hora):\r\n\r\n" . $resetUrl . "\r\n\r\nSe você não solicitou, ignore esta mensagem.\r\n\r\n— Equipe EVA";

// Cabeçalhos (UTF-8 e HTML)
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=UTF-8';
$headers[] = 'From: EVA <no-reply@localhost>';
$headers[] = 'Reply-To: no-reply@localhost';
$headers[] = 'X-Mailer: PHP/' . phpversion();
$headersStr = implode("\r\n", $headers);

// Política de envio
$podeEnviar = true;
if (defined('SMTP_SEND_ONLY_IF_USER_EXISTS') && SMTP_SEND_ONLY_IF_USER_EXISTS === true) {
    if (!$usuarioExiste) { $podeEnviar = false; }
}

$enviado = false;
if ($podeEnviar) {
    if (!empty(SMTP_HOST)) {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                $mail->SMTPAuth = SMTP_AUTH;
                if (SMTP_AUTH) {
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                }
                if (in_array(strtolower(SMTP_ENCRYPTION), ['tls','ssl'])) {
                    $mail->SMTPSecure = strtolower(SMTP_ENCRYPTION);
                }
                $mail->CharSet = 'UTF-8';
                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $assunto;
                $mail->Body    = $mensagemHtml;
                $mail->AltBody = 'Recebemos sua solicitação de recuperação de senha. Se você não solicitou, ignore.';
                $enviado = $mail->send();
            } catch (\Throwable $e) {
                $enviado = false;
            }
        }
        if (!$enviado) {
            $enviado = smtp_send($email, $assunto, $mensagemHtml, SMTP_FROM, SMTP_FROM_NAME, SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_ENCRYPTION, 30);
        }
    } else {
        // Fallback: tenta mail() (requer SMTP no php.ini em Windows)
        $enviado = @mail($email, $assunto, $mensagemHtml, $headersStr);
    }
}

if (!$enviado) {
    // Fallback de desenvolvimento: grava última mensagem em HTML para inspecionar
    $debugDir = __DIR__ . '/assets/debug';
    if (!is_dir($debugDir)) {
        @mkdir($debugDir, 0777, true);
    }
    $preview = '<h2>Pré-visualização de e-mail (ambiente sem SMTP)</h2>' .
        '<p><strong>Para:</strong> ' . htmlspecialchars($email) . '</p>' .
        '<p><strong>Assunto:</strong> ' . htmlspecialchars($assunto) . '</p>' .
        '<hr>' . $mensagemHtml;
    @file_put_contents($debugDir . '/last_email.html', $preview);
}

// Resposta sempre genérica por segurança
renderizarResposta(
    'Se houver uma conta, você receberá um e-mail',
    'Se este e-mail estiver cadastrado, enviaremos instruções. ' .
    (!$enviado ? '<span class="hint">(Ambiente de desenvolvimento: a última pré-visualização foi salva em <code>assets/debug/last_email.html</code>.)</span>' : ''),
    'index.php',
    'Voltar ao login'
);
exit;
?>
