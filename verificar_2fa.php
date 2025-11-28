<?php
// Página de verificação 2FA: exige sessão iniciada e 2FA pendente
session_start();
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/config.php';

// Se não houver 2FA pendente, vai para a dashboard
if (empty($_SESSION['twofa_pending']) || empty($_SESSION['usuario_id'])) {
    header('Location: dash.php');
    exit;
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'verificar') {
        $codigoInformado = trim($_POST['codigo'] ?? '');
        $codigoEsperado = $_SESSION['twofa_code'] ?? null;
        $expira = $_SESSION['twofa_expires'] ?? 0;
        if (!$codigoEsperado || time() > $expira) {
            $erro = 'Código expirado. Solicite um novo código.';
        } elseif ($codigoInformado !== (string)$codigoEsperado) {
            $erro = 'Código inválido. Tente novamente.';
        } else {
            // Sucesso: limpar flags e prosseguir
            unset($_SESSION['twofa_code'], $_SESSION['twofa_expires'], $_SESSION['twofa_pending']);
            $mensagem = 'Verificação concluída com sucesso!';
            header('Location: dash.php');
            exit;
        }
    } elseif ($acao === 'reenviar') {
        // Rate limit simples: só permite reenviar após 60s
        $agora = time();
        $ultimoEnvio = $_SESSION['twofa_last_sent'] ?? 0;
        if ($agora - (int)$ultimoEnvio < 60) {
            $restante = 60 - ($agora - (int)$ultimoEnvio);
            $erro = 'Aguarde ' . $restante . 's para reenviar o código.';
        } else {
            // Regenera e reenviando código
            $codigo = random_int(100000, 999999);
            $_SESSION['twofa_code'] = $codigo;
            $_SESSION['twofa_expires'] = $agora + 300;
            $_SESSION['twofa_last_sent'] = $agora;
            $nome = $_SESSION['usuario_nome'] ?? 'Usuário';
            $emailDestino = $_SESSION['twofa_email'] ?? '';
            if (!$emailDestino) {
                // Busca e-mail do banco como fallback
                $emailDestino = '';
                if (isset($_SESSION['usuario_id'])) {
                    $stmt = $conexao->prepare('SELECT email FROM usuarios WHERE id = ? LIMIT 1');
                    if ($stmt) {
                        $stmt->bind_param('i', $_SESSION['usuario_id']);
                        if ($stmt->execute()) {
                            $stmt->bind_result($emailDestino);
                            $stmt->fetch();
                        }
                        $stmt->close();
                    }
                }
            }
            require_once __DIR__ . '/lib/smtp_send.php';
            require_once __DIR__ . '/mail_config.php';
            $assunto = 'Seu código de verificação (2FA)';
            $html = '<div style="font-family:Arial,sans-serif;font-size:16px">'
                  . '<p>Olá, ' . htmlspecialchars($nome) . '.</p>'
                  . '<p>Seu código de verificação é <strong>' . $codigo . '</strong>.</p>'
                  . '<p>Ele expira em 5 minutos.</p>'
                  . '<p>— EVA</p>'
                  . '</div>';
            @smtp_send($emailDestino, $assunto, $html, SMTP_FROM, SMTP_FROM_NAME, SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_ENCRYPTION);
            $mensagem = 'Novo código enviado por e-mail.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação 2FA - EVA</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/componentes/login.css">
</head>
<body>
    <div class="login-container" style="max-width:460px">
        <div style="background:#fff;border:1px solid #e6e9ec;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.05);padding:22px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <div style="width:36px;height:36px;border-radius:8px;background:#eef3ff;color:#3b6cff;display:flex;align-items:center;justify-content:center"><i class='bx bx-shield'></i></div>
            <h1 style="margin:0;font-size:20px;color:#2c3e50">Verificação em duas etapas</h1>
        </div>
        <p style="color:#6b7a8d;margin:0 0 14px">Enviamos um código de 6 dígitos para seu e-mail. Digite-o abaixo para concluir o login.</p>
        <?php if (!empty($erro)): ?>
            <div class="mensagem erro" style="border-radius:8px;margin-bottom:12px"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem sucesso" style="border-radius:8px;margin-bottom:12px"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <form method="POST" action="verificar_2fa.php" style="margin-top:12px">
            <input type="hidden" name="acao" value="verificar">
            <div class="form-group" style="display:block">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px">Código de verificação</label>
                <div style="display:flex;gap:10px;align-items:center">
                    <input id="codigo" type="text" name="codigo" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" required style="letter-spacing:8px;text-align:center;font-size:20px;padding:12px 14px;border-radius:10px;border:1px solid #d5dce3;flex:1">
                    <button type="submit" class="btn-submit" style="width:auto; padding:10px 14px; font-size:14px; border-radius:10px;">Confirmar</button>
                </div>
                <small class="helper" style="color:#6b7a8d;margin-top:8px;display:block">O código expira em 5 minutos. Dica: você pode colar diretamente (Ctrl+V).</small>
            </div>
            <div class="form-group" style="display:flex;gap:8px;align-items:center;justify-content:space-between">
                <div></div>
                <a href="logout.php" style="font-size:13px; color:#2c3e50">Sair e voltar ao login</a>
            </div>
        </form>
        <?php 
            $agora = time();
            $ultimoEnvio = $_SESSION['twofa_last_sent'] ?? 0;
            $restante = max(0, 60 - ($agora - (int)$ultimoEnvio));
        ?>
        <form method="POST" action="verificar_2fa.php" style="margin-top:8px;display:flex;gap:8px;align-items:center">
            <input type="hidden" name="acao" value="reenviar">
            <button id="btnReenviar" type="submit" class="btn-submit" style="width:auto; padding:8px 12px; font-size:14px; background:#6c7a89;border-radius:10px" <?php echo $restante > 0 ? 'disabled' : ''; ?>>Reenviar código</button>
            <small id="hintCooldown" class="helper" style="color:#6b7a8d;<?php echo $restante > 0 ? '' : 'display:none'; ?>">Disponível em <span id="cooldown"><?php echo $restante; ?></span>s</small>
        </form>
        </div>
        <script>
        // Foco inicial e limpeza de caracteres não numéricos
        (function(){
            var el = document.getElementById('codigo');
            if (el) { el.focus(); }
            function onlyDigits(e){
                var v = e.target.value || '';
                var cleaned = v.replace(/[^\d]/g,'').slice(0,6);
                if (v !== cleaned) { e.target.value = cleaned; }
            }
            if (el) {
                el.addEventListener('input', onlyDigits);
                el.addEventListener('paste', function(){ setTimeout(function(){ onlyDigits({target:el}); }, 0); });
            }
            // cooldown do botão reenviar
            var btn = document.getElementById('btnReenviar');
            var cdSpan = document.getElementById('cooldown');
            var hint = document.getElementById('hintCooldown');
            var restante = parseInt(cdSpan ? cdSpan.textContent : '0', 10) || 0;
            if (btn && restante > 0) {
                var iv = setInterval(function(){
                    restante -= 1;
                    if (cdSpan) cdSpan.textContent = Math.max(0, restante);
                    if (restante <= 0) {
                        clearInterval(iv);
                        btn.disabled = false;
                        if (hint) hint.style.display = 'none';
                    }
                }, 1000);
            }
        })();
        </script>
    </div>
</body>
</html>
