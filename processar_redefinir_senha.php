<?php
require_once __DIR__ . '/config.php';

function page($titulo, $html) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titulo) . '</title>';
    echo '<style>*{box-sizing:border-box}body{font-family:Segoe UI,Tahoma,Arial,sans-serif;background:#f4f7f6;margin:0;padding:0;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#2c3e50}.card{background:#fff;max-width:520px;width:92%;padding:28px 26px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.08)}h1{font-size:22px;margin:0 0 10px}p{margin:10px 0}a.btn{display:inline-block;margin-top:14px;text-decoration:none;color:#3498db}</style></head><body>';
    echo '<div class="card">' . $html . '</div></body></html>';
}

$token = trim($_POST['token'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    page('Link inválido', '<h1>Link inválido</h1><p>O link de redefinição é inválido.</p><a class="btn" href="index.php">Voltar ao login</a>');
    exit;
}

if (strlen($senha) < 6 || $senha !== $confirmar) {
    page('Dados inválidos', '<h1>Dados inválidos</h1><p>Verifique a senha informada e a confirmação.</p><a class="btn" href="' . 'redefinir_senha.php?token=' . urlencode($token) . '">Voltar</a>');
    exit;
}

$email = null; $expires_at = null; $used = 1; $tokenOk = false;
if ($conexao instanceof mysqli) {
    if ($stmt = $conexao->prepare('SELECT email, expires_at, used FROM recuperacao_senha WHERE token = ? LIMIT 1')) {
        $stmt->bind_param('s', $token);
        if ($stmt->execute()) {
            $stmt->bind_result($email, $expires_at, $used);
            if ($stmt->fetch()) {
                $tokenOk = ($used == 0 && strtotime($expires_at) > time());
            }
        }
        $stmt->close();
    }
}

if (!$tokenOk || !$email) {
    page('Link inválido ou expirado', '<h1>Link inválido ou expirado</h1><p>Solicite uma nova recuperação.</p><a class="btn" href="esqueci_senha.php">Solicitar novamente</a>');
    exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$conexao->begin_transaction();
try {
    // Atualiza senha do usuário
    if ($stmt = $conexao->prepare('UPDATE usuarios SET senha = ? WHERE email = ? LIMIT 1')) {
        $stmt->bind_param('ss', $hash, $email);
        if (!$stmt->execute() || $stmt->affected_rows < 1) { throw new Exception('Falha ao atualizar senha'); }
        $stmt->close();
    } else { throw new Exception('Falha ao preparar update'); }

    // Invalida o token (marca como usado)
    if ($stmt2 = $conexao->prepare('UPDATE recuperacao_senha SET used = 1 WHERE token = ? LIMIT 1')) {
        $stmt2->bind_param('s', $token);
        if (!$stmt2->execute()) { throw new Exception('Falha ao invalidar token'); }
        $stmt2->close();
    } else { throw new Exception('Falha ao preparar invalidacao token'); }

    // (Opcional) remover tokens antigos do mesmo email
    $conexao->query("DELETE FROM recuperacao_senha WHERE email = '" . $conexao->real_escape_string($email) . "' AND used = 1 AND expires_at < NOW() - INTERVAL 7 DAY");

    $conexao->commit();
    page('Senha redefinida', '<h1>Senha redefinida</h1><p>Sua senha foi atualizada com sucesso.</p><a class="btn" href="index.php">Ir para o login</a>');
    exit;
} catch (Throwable $e) {
    $conexao->rollback();
    page('Erro', '<h1>Erro</h1><p>Não foi possível concluir a redefinição agora. Tente novamente.</p><a class="btn" href="index.php">Voltar</a>');
    exit;
}
