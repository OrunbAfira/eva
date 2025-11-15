<?php
session_start();
require_once 'session_guard.php';

require_once 'config.php';

$nome_usuario = $_SESSION['usuario_nome'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_completo = $_POST['nome_completo'] ?? '';
    $ra_matricula = $_POST['ra_matricula'] ?? '';
    $turma = $_POST['turma'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $endereco = $_POST['endereco'] ?? '';

    if (empty($nome_completo) || empty($ra_matricula)) {
        $mensagem = "<div class='mensagem erro'>Erro: Nome completo e Matrícula são obrigatórios.</div>";
    } else {
        $stmt_check = $conexao->prepare("SELECT id FROM alunos WHERE ra_matricula = ?");
        $stmt_check->bind_param("s", $ra_matricula);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();

        if ($resultado->num_rows > 0) {
            $mensagem = "<div class='mensagem erro'>Erro: Esta matrícula já está cadastrada no sistema.</div>";
        } else {
            $stmt = $conexao->prepare("INSERT INTO alunos (nome_completo, ra_matricula, turma, email, telefone, data_nascimento, endereco) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nome_completo, $ra_matricula, $turma, $email, $telefone, $data_nascimento, $endereco);

            if ($stmt->execute()) {
                $mensagem = "<div class='mensagem sucesso'>Aluno '". htmlspecialchars($nome_completo) ."' cadastrado com sucesso!</div>";
                $nome_completo = $ra_matricula = $turma = $email = $telefone = $data_nascimento = $endereco = '';
            } else {
                $mensagem = "<div class='mensagem erro'>Erro ao cadastrar o aluno. Tente novamente.</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

$alunos = [];
$stmt_alunos = $conexao->prepare("SELECT id, nome_completo, ra_matricula, turma, email, pontuacao_risco, nivel_risco, data_cadastro FROM alunos ORDER BY data_cadastro DESC");
$stmt_alunos->execute();
$resultado_alunos = $stmt_alunos->get_result();
while ($row = $resultado_alunos->fetch_assoc()) {
    $alunos[] = $row;
}
$stmt_alunos->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alunos - Plataforma EVA</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="alunos.css"> </head>
<body>

    <nav class="sidebar">
        <div class="logo">EVA</div>
        <div class="nav-menu">
            <a href="dash.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="alunos.php" class="active"><i class='bx bxs-user-detail'></i> Alunos</a>
            <a href="alertas.php"><i class='bx bxs-bell'></i> Alertas</a>
            <a href="#"><i class='bx bxs-report'></i> Relatórios</a>
            <a href="#"><i class='bx bxs-cog'></i> Configurações</a>
        </div>
        <div class="user-profile">
            <p><strong><?php echo htmlspecialchars($nome_usuario); ?></strong></p>
            <p><small>Usuário</small></p>
            <a href="logout.php"><i class='bx bx-log-out'></i> Sair</a>
        </div>
    </nav>

    <main class="main-content">
        <header class="header">
            <h1>Gestão de Alunos</h1>
            <p>Cadastre um novo aluno no sistema.</p>
        </header>

        <section class="widget">
            <div class="widget-header">
                <h2>Cadastrar Novo Aluno</h2>
            </div>

            <?php echo $mensagem; // Exibe a mensagem de sucesso ou erro aqui ?>

            <div class="form-container">
                <form action="alunos.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_completo">Nome Completo *</label>
                            <input type="text" id="nome_completo" name="nome_completo" value="<?php echo htmlspecialchars($nome_completo ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ra_matricula">RA (Matrícula) *</label>
                            <input type="text" id="ra_matricula" name="ra_matricula" value="<?php echo htmlspecialchars($ra_matricula ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="turma">Turma</label>
                            <input type="text" id="turma" name="turma" value="<?php echo htmlspecialchars($turma ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento</label>
                            <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($data_nascimento ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço</label>
                        <textarea id="endereco" name="endereco" rows="3"><?php echo htmlspecialchars($endereco ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Cadastrar Aluno</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Lista de Alunos Cadastrados -->
        <section class="widget">
            <div class="widget-header">
                <h2>Alunos Cadastrados</h2>
                <span class="badge"><?php echo count($alunos); ?> alunos</span>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Matrícula</th>
                            <th>Turma</th>
                            <th>Nível de Risco</th>
                            <th>Pontuação</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alunos)): ?>
                            <tr class="empty-row">
                                <td colspan="7">Nenhum aluno cadastrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['ra_matricula']); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['turma'] ?: '-'); ?></td>
                                    <td>
                                        <span class="risk-level <?php echo $aluno['nivel_risco']; ?>">
                                            <?php echo ucfirst($aluno['nivel_risco']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $aluno['pontuacao_risco']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($aluno['data_cadastro'])); ?></td>
                                    <td>
                                        <a href="#" class="btn-action" title="Editar">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <a href="#" class="btn-action delete" title="Excluir">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
    <script src="assets/js/session_timeout.js"></script>
</body>
</html>