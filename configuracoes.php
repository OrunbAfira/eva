<?php
session_start();
require_once 'session_guard.php';
require_once 'config.php';

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$primeiro_nome = explode(' ', $nome_usuario)[0];
// Mensagens de feedback
$mensagens = [];

// Processamento do formulário de Perfil de Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$usuarioId) {
        $mensagens[] = ['tipo' => 'erro', 'texto' => 'Sessão inválida. Faça login novamente.'];
    } else {
        $acao = $_POST['acao'] ?? '';
        if ($acao === 'atualizar_perfil') {
            $novoNome = trim($_POST['nome'] ?? '');
            $novoEmail = trim($_POST['email'] ?? '');
            if ($novoNome === '' || !filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Informe um nome válido e um e-mail válido.'];
            } else {
                // Verifica se e-mail já está em uso por outro usuário
                if ($stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1')) {
                    $stmt->bind_param('si', $novoEmail, $usuarioId);
                    $stmt->execute();
                    $stmt->store_result();
                    $emailEmUso = $stmt->num_rows > 0;
                    $stmt->close();
                } else { $emailEmUso = true; }

                if ($emailEmUso) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Este e-mail já está em uso por outro usuário.'];
                } else {
                    if ($stmt = $conexao->prepare('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?')) {
                        $stmt->bind_param('ssi', $novoNome, $novoEmail, $usuarioId);
                        if ($stmt->execute()) {
                            $_SESSION['usuario_nome'] = $novoNome;
                            $nome_usuario = $novoNome;
                            $primeiro_nome = explode(' ', $novoNome)[0];
                            $mensagens[] = ['tipo' => 'sucesso', 'texto' => 'Perfil atualizado com sucesso.'];
                        } else {
                            $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar perfil.'];
                        }
                        $stmt->close();
                    } else {
                        $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao preparar atualização de perfil.'];
                    }
                }
            }
        } elseif ($acao === 'alterar_senha') {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmar = $_POST['confirmar_senha'] ?? '';
            if (strlen($novaSenha) < 6 || $novaSenha !== $confirmar) {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'A nova senha deve ter ao menos 6 caracteres e coincidir com a confirmação.'];
            } else {
                // Carrega hash atual
                $hashAtual = null;
                if ($stmt = $conexao->prepare('SELECT senha FROM usuarios WHERE id = ? LIMIT 1')) {
                    $stmt->bind_param('i', $usuarioId);
                    $stmt->execute();
                    $stmt->bind_result($hashAtual);
                    $stmt->fetch();
                    $stmt->close();
                }
                if (!$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Senha atual incorreta.'];
                } else {
                    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
                    if ($stmt = $conexao->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')) {
                        $stmt->bind_param('si', $novoHash, $usuarioId);
                        if ($stmt->execute()) {
                            // Regenera ID de sessão por segurança
                            session_regenerate_id(true);
                            $mensagens[] = ['tipo' => 'sucesso', 'texto' => 'Senha alterada com sucesso.'];
                        } else {
                            $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao atualizar senha.'];
                        }
                        $stmt->close();
                    } else {
                        $mensagens[] = ['tipo' => 'erro', 'texto' => 'Erro ao preparar atualização de senha.'];
                    }
                }
            }
        } elseif ($acao === 'toggle_2fa') {
            $enable = isset($_POST['twofa']) && $_POST['twofa'] === 'on';
            // Tenta persistir no banco (coluna twofa_enabled); se falhar, usa sessão
            $persistido = false;
            if ($stmt = $conexao->prepare('UPDATE usuarios SET twofa_enabled = ? WHERE id = ?')) {
                $val = $enable ? 1 : 0;
                $stmt->bind_param('ii', $val, $usuarioId);
                if ($stmt->execute()) { $persistido = true; }
                $stmt->close();
            }
            if (!$persistido) {
                $_SESSION['twofa_enabled'] = $enable;
            }
            $mensagens[] = ['tipo' => 'sucesso', 'texto' => $enable ? 'Autenticação em duas etapas ativada.' : 'Autenticação em duas etapas desativada.'];
        } elseif ($acao === 'deletar_conta') {
            $confirm = $_POST['confirmacao'] ?? '';
            if ($confirm !== 'DELETAR') {
                $mensagens[] = ['tipo' => 'erro', 'texto' => 'Confirmação inválida. Digite DELETAR para confirmar.'];
            } else {
                // Remove o usuário do banco
                $ok = false;
                if ($stmt = $conexao->prepare('DELETE FROM usuarios WHERE id = ?')) {
                    $stmt->bind_param('i', $usuarioId);
                    $ok = $stmt->execute();
                    $stmt->close();
                }
                if ($ok) {
                    // Encerra a sessão e redireciona
                    session_unset();
                    session_destroy();
                    if (!headers_sent()) {
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $mensagens[] = ['tipo' => 'erro', 'texto' => 'Não foi possível deletar sua conta.'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Plataforma EVA</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/componentes/dashboard.css">
</head>
<body>
    <nav class="sidebar">
        <div class="logo">EVA</div>
        <div class="nav-menu">
            <a href="dash.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="alunos.php"><i class='bx bxs-user-detail'></i> Alunos</a>
            <a href="turmas.php"><i class='bx bxs-group'></i> Turmas</a>
            <a href="alertas.php"><i class='bx bxs-bell'></i> Alertas</a>
            <a href="#"><i class='bx bxs-report'></i> Relatórios</a>
            <a href="configuracoes.php" class="active"><i class='bx bxs-cog'></i> Configurações</a>
        </div>
        <div class="user-profile">
            <p><strong><?php echo htmlspecialchars($nome_usuario); ?></strong></p>
            <p><small>Usuário</small></p>
            <a href="logout.php"><i class='bx bx-log-out'></i> Sair</a>
        </div>
    </nav>

    <main class="main-content">
        <header class="header">
            <h1>Configurações</h1>
            <p>Olá, <?php echo htmlspecialchars($primeiro_nome); ?>. Ajustes visuais (sem salvar).</p>
        </header>

        <?php if (!empty($mensagens)): ?>
            <section class="widget">
                <div class="widget-header"><h2>Mensagens</h2></div>
                <?php foreach ($mensagens as $m): ?>
                    <div class="mensagem <?php echo $m['tipo']; ?>"><?php echo htmlspecialchars($m['texto']); ?></div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="widget" id="perfil-usuario">
            <div class="widget-header">
                <h2>Perfil de Usuário</h2>
            </div>
            <details>
                <summary style="cursor:pointer;font-weight:600;padding:8px 0">Editar perfil e senha</summary>
                <div class="form-container" style="margin-top:8px">
                    <div style="margin-bottom:14px">
                        <h3 style="margin:0 0 6px;font-size:16px">Informações do Perfil</h3>
                        <p style="margin:0;color:#7f8c8d;font-size:13px">Atualize seu nome e e-mail de contato.</p>
                    </div>
                    <form method="POST" action="configuracoes.php#perfil-usuario">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        <div class="form-group" style="display:block">
                            <label>Nome completo</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($nome_usuario); ?>" required>
                            <small class="helper">Como deseja que seu nome apareça no sistema.</small>
                        </div>
                        <div class="form-group" style="display:block">
                            <label>Endereço de e-mail</label>
                            <input type="email" name="email" value="<?php 
                                // Carrega e-mail atual do usuário
                                $emailAtual = 'usuario@example.com';
                                if (isset($_SESSION['usuario_id'])) {
                                    if ($stmt = $conexao->prepare('SELECT email FROM usuarios WHERE id = ? LIMIT 1')) {
                                        $stmt->bind_param('i', $_SESSION['usuario_id']);
                                        $stmt->execute();
                                        $stmt->bind_result($emailAtual);
                                        $stmt->fetch();
                                        $stmt->close();
                                    }
                                }
                                echo htmlspecialchars($emailAtual);
                            ?>" required>
                            <small class="helper">Usado para comunicações e recuperação de senha.</small>
                        </div>
                        <div class="form-group" style="display:block">
                            <button type="submit" class="btn-submit" style="width:auto; padding:8px 12px; font-size:14px;">Salvar perfil</button>
                        </div>
                    </form>

                    <hr style="margin:18px 0">

                    <div style="margin-bottom:14px">
                        <h3 style="margin:0 0 6px;font-size:16px">Segurança da Conta</h3>
                        <p style="margin:0;color:#7f8c8d;font-size:13px">Altere sua senha para fortalecer a segurança.</p>
                    </div>
                    <form method="POST" action="configuracoes.php#perfil-usuario">
                        <input type="hidden" name="acao" value="alterar_senha">
                        <div class="form-group" style="display:block">
                            <label>Senha atual</label>
                            <input type="password" name="senha_atual" required>
                            <small class="helper">Digite sua senha atual para confirmar.</small>
                        </div>
                        <div class="form-group" style="display:block">
                            <label>Nova senha</label>
                            <input type="password" name="nova_senha" minlength="6" required>
                            <small class="helper">Mínimo de 6 caracteres. Evite reutilizar senhas.</small>
                        </div>
                        <div class="form-group" style="display:block">
                            <label>Confirmar nova senha</label>
                            <input type="password" name="confirmar_senha" minlength="6" required>
                            <small class="helper">Repita a nova senha exatamente.</small>
                        </div>
                        <div class="form-group" style="display:block">
                            <button type="submit" class="btn-submit" style="width:auto; padding:8px 12px; font-size:14px;">Alterar senha</button>
                        </div>
                    </form>

                    <div class="form-group" style="display:block; margin-top:12px">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                            <label style="margin:0;font-weight:600;color:#2c3e50">Autenticação em duas etapas (2FA)</label>
                            <?php 
                                $twofa = $_SESSION['twofa_enabled'] ?? false;
                                if (isset($_SESSION['usuario_id'])) {
                                    if ($st = $conexao->prepare('SELECT twofa_enabled FROM usuarios WHERE id = ? LIMIT 1')) {
                                        $st->bind_param('i', $_SESSION['usuario_id']);
                                        if ($st->execute()) { $st->bind_result($twofaVal); if ($st->fetch()) { $twofa = (bool)$twofaVal; } }
                                        $st->close();
                                    }
                                }
                            ?>
                            <span class="badge" style="background:<?php echo $twofa ? '#eaf9f0' : '#fff3f0'; ?>;color:<?php echo $twofa ? '#2ecc71' : '#e67e22'; ?>;border:1px solid <?php echo $twofa ? '#c9f1d9' : '#ffd9cc'; ?>;">
                                <?php echo $twofa ? 'Ativado' : 'Desativado'; ?>
                            </span>
                        </div>
                        <p style="margin:0 0 10px;color:#6b7a8d">Adicione uma etapa extra de segurança. Com 2FA ativo, um código de 6 dígitos será enviado por e-mail após o login.</p>
                        <form method="POST" action="configuracoes.php#perfil-usuario" style="display:flex;align-items:center;gap:12px;background:#f7f9fc;border:1px solid #e6e9ec;border-radius:12px;padding:10px 12px">
                            <input type="hidden" name="acao" value="toggle_2fa">
                            <label style="display:flex;align-items:center;gap:8px;color:#2c3e50">
                                <input type="checkbox" name="twofa" <?php echo $twofa ? 'checked' : ''; ?> style="width:18px;height:18px">
                                Ativar 2FA por e-mail
                            </label>
                            <button type="submit" class="btn-submit" style="width:auto; padding:8px 12px; font-size:13px; border-radius:10px">Salvar</button>
                        </form>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:8px;color:#6b7a8d;font-size:12px">
                            <i class='bx bx-info-circle'></i>
                            <span>Você poderá reenviar o código após 1 minuto, se necessário.</span>
                        </div>
                    </div>
                    <hr style="margin:18px 0">
                    <div style="margin-bottom:14px">
                        <h3 style="margin:0 0 6px;font-size:16px;color:#c0392b">Excluir Conta</h3>
                        <p style="margin:0;color:#7f8c8d;font-size:13px">Esta ação é permanente e removerá seu perfil imediatamente.</p>
                    </div>
                    <form method="POST" action="configuracoes.php#perfil-usuario" onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Esta ação é irreversível.');">
                        <input type="hidden" name="acao" value="deletar_conta">
                        <div class="form-group" style="display:block">
                            <label>Digite <strong>DELETAR</strong> para confirmar</label>
                            <input type="text" name="confirmacao" placeholder="DELETAR" required>
                        </div>
                        <div class="form-group" style="display:block">
                            <button type="submit" class="btn-submit" style="background:#e74c3c;border-color:#e74c3c;color:#fff;width:auto;padding:8px 12px;font-size:14px">Excluir minha conta</button>
                        </div>
                    </form>
                </div>
            </details>
        </section>

        <section class="widget">
            <div class="widget-header">
                <h2>Preferências de Interface</h2>
                <span class="badge">UI</span>
            </div>
            <details>
                <summary style="cursor:pointer;font-weight:600;padding:8px 0">Ajustes de tema, fonte e idioma</summary>
                <div class="form-container" style="margin-top:8px">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tema</label>
                        <select disabled>
                            <option>Claro</option>
                            <option>Escuro</option>
                            <option>Sistema</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tamanho da fonte</label>
                        <select disabled>
                            <option>Padrão</option>
                            <option>Compacto</option>
                            <option>Grande</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Idioma</label>
                        <select disabled>
                            <option>Português (Brasil)</option>
                            <option>Inglês</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notificações</label>
                        <select disabled>
                            <option>Ativadas</option>
                            <option>Desativadas</option>
                        </select>
                    </div>
                </div>
                <p class="hint">Visual demonstrativo — sem persistência.</p>
                </div>
            </details>
        </section>

        <section class="widget">
            <div class="widget-header">
                <h2>Risco e Alertas</h2>
                <span class="badge">UI</span>
            </div>
            <details>
                <summary style="cursor:pointer;font-weight:600;padding:8px 0">Threshold, frequência e canais</summary>
                <div class="form-container" style="margin-top:8px">
                <div class="form-row">
                    <div class="form-group">
                        <label>Threshold risco alto</label>
                        <input type="number" min="1" max="100" value="25" disabled>
                    </div>
                    <div class="form-group">
                        <label>Frequência dos alertas</label>
                        <select disabled>
                            <option>Imediato</option>
                            <option>Diário</option>
                            <option>Semanal</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Canais de alerta</label>
                        <select disabled>
                            <option>Dashboard</option>
                            <option>E-mail</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Regras ativas</label>
                        <input type="text" value="<?php 
                            // Contagem de regras ativas (visual)
                            $count = 0; 
                            if (isset($conexao)) {
                                if ($stmt = $conexao->prepare("SELECT COUNT(*) AS c FROM regras_risco WHERE ativo = 1")) {
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $row = $res ? $res->fetch_assoc() : ['c' => 0];
                                    $count = (int)($row['c'] ?? 0);
                                    $stmt->close();
                                }
                            }
                            echo $count . ' regras ativas';
                        ?>" disabled>
                    </div>
                </div>
                <p class="hint">Apenas UI — integração futura com regras e alertas.</p>
                </div>
            </details>
        </section>
    </main>

    <script src="assets/js/session_timeout.js"></script>
</body>
</html>
