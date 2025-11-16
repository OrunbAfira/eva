<?php
require_once __DIR__ . '/config.php';

function page($titulo, $html) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titulo) . '</title>';
    echo '<style>*{box-sizing:border-box}body{font-family:Segoe UI,Tahoma,Arial,sans-serif;background:#f4f7f6;margin:0;padding:0;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#2c3e50}.card{background:#fff;max-width:520px;width:92%;padding:28px 26px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{font-size:22px;margin:0 0 10px}p{margin:10px 0}label{display:block;margin:10px 0 6px}input[type=password]{width:100%;padding:10px;border:1px solid #ccd6dd;border-radius:6px}button{margin-top:16px;background:#2ecc71;color:#fff;border:none;padding:10px 14px;border-radius:6px;cursor:pointer}a.btn{display:inline-block;margin-top:14px;text-decoration:none;color:#3498db}</style></head><body>';
    echo '<div class="card">' . $html . '</div></body></html>';
}

$token = $_GET['token'] ?? '';
$token = trim($token);
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    page('Link inválido', '<h1>Link inválido</h1><p>O link de redefinição é inválido ou foi alterado.</p><a class="btn" href="index.php">Voltar ao login</a>');
    exit;
}

$valido = false;
$email = null;
if ($conexao instanceof mysqli) {
    if ($stmt = $conexao->prepare('SELECT email, expires_at, used FROM recuperacao_senha WHERE token = ? LIMIT 1')) {
        $stmt->bind_param('s', $token);
        if ($stmt->execute()) {
            $stmt->bind_result($email, $expires_at, $used);
            if ($stmt->fetch()) {
                $valido = ($used == 0 && strtotime($expires_at) > time());
            }
        }
        $stmt->close();
    }
}

if (!$valido) {
    page('Link inválido ou expirado', '<h1>Link inválido ou expirado</h1><p>Solicite uma nova recuperação de senha.</p><a class="btn" href="esqueci_senha.php">Solicitar novamente</a>');
    exit;
}

$form = '<h1>Redefinir senha</h1>' .
    '<form method="POST" action="processar_redefinir_senha.php">' .
    '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">' .
    '<label>Nova senha</label><input type="password" name="senha" required minlength="6" />' .
    '<label>Confirmar nova senha</label><input type="password" name="confirmar" required minlength="6" />' .
    '<button type="submit">Redefinir</button>' .
    '</form>' .
    '<a class="btn" href="index.php">Voltar</a>';

page('Redefinir senha', $form);
