<?php
session_start();
require_once 'session_guard.php';

require_once 'config.php';
require_once 'risk_functions.php';

$nome_usuario = $_SESSION['usuario_nome'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aluno_id = $_POST['aluno_id'] ?? 0;
    $regra_id = $_POST['regra_id'] ?? 0;
    $observacoes = $_POST['observacoes'] ?? '';
    
    if ($aluno_id && $regra_id) {
        $stmt = $conexao->prepare("SELECT pontos FROM regras_risco WHERE id = ?");
        $stmt->bind_param("i", $regra_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pontos = $result->fetch_assoc()['pontos'];
        $stmt->close();
        
        if (adicionarAlerta($conexao, $aluno_id, $regra_id, $pontos, $observacoes)) {
            $mensagem = "<div class='mensagem sucesso'>Alerta adicionado com sucesso!</div>";
        } else {
            $mensagem = "<div class='mensagem erro'>Erro ao adicionar alerta.</div>";
        }
    }
}

$alunos = [];
$stmt = $conexao->prepare("SELECT id, nome_completo, ra_matricula FROM alunos WHERE status = 'ativo' ORDER BY nome_completo");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $alunos[] = $row;
}
$stmt->close();

$regras = [];
$stmt = $conexao->prepare("SELECT id, nome_regra, pontos, descricao FROM regras_risco WHERE ativo = 1 ORDER BY nome_regra");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $regras[] = $row;
}
$stmt->close();

$alertas_recentes = [];
$stmt = $conexao->prepare("
    SELECT a.nome_completo, a.ra_matricula, r.nome_regra, al.pontos_atribuidos, al.data_alerta, al.status
    FROM alertas al 
    JOIN alunos a ON al.aluno_id = a.id 
    JOIN regras_risco r ON al.regra_id = r.id 
    ORDER BY al.data_alerta DESC 
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $alertas_recentes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Alertas - Plataforma EVA</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/componentes/dashboard.css">
</head>
<body>

    <nav class="sidebar">
        <div class="logo">EVA</div>
        <div class="nav-menu">
            <a href="dash.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="alunos.php"><i class='bx bxs-user-detail'></i> Alunos</a>
            <a href="alertas.php" class="active"><i class='bx bxs-bell'></i> Alertas</a>
            <a href="relatorios.php"><i class='bx bxs-report'></i> Relatórios</a>
            <a href="configuracoes.php"><i class='bx bxs-cog'></i> Configurações</a>
        </div>
        <div class="user-profile">
            <p><strong><?php echo htmlspecialchars($nome_usuario); ?></strong></p>
            <p><small>Usuário</small></p>
            <a href="logout.php"><i class='bx bx-log-out'></i> Sair</a>
        </div>
    </nav>

    <main class="main-content">
        <header class="header">
            <h1>Gestão de Alertas</h1>
            <p>Adicione novos alertas e monitore riscos dos alunos.</p>
        </header>

        <section class="widget">
            <div class="widget-header">
                <h2>Adicionar Novo Alerta</h2>
            </div>

            <?php echo $mensagem; ?>

            <div class="form-container">
                <form action="alertas.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="aluno_id">Aluno *</label>
                            <select id="aluno_id" name="aluno_id" required>
                                <option value="">Selecione um aluno</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome_completo'] . ' (' . $aluno['ra_matricula'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="regra_id">Regra de Risco *</label>
                            <select id="regra_id" name="regra_id" required>
                                <option value="">Selecione uma regra</option>
                                <?php foreach ($regras as $regra): ?>
                                    <option value="<?php echo $regra['id']; ?>" 
                                            title="<?php echo htmlspecialchars($regra['descricao'] ?: 'Sem descrição'); ?>">
                                        <?php echo htmlspecialchars($regra['nome_regra'] . ' (' . $regra['pontos'] . ' pontos)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3" placeholder="Detalhes sobre o alerta..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Adicionar Alerta</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="widget">
            <div class="widget-header">
                <h2>Alertas Recentes</h2>
                <span class="badge"><?php echo count($alertas_recentes); ?> alertas</span>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Matrícula</th>
                            <th>Regra Acionada</th>
                            <th>Pontos</th>
                            <th>Data/Hora</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alertas_recentes)): ?>
                            <tr class="empty-row">
                                <td colspan="6">Nenhum alerta registrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alertas_recentes as $alerta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alerta['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($alerta['ra_matricula']); ?></td>
                                    <td><?php echo htmlspecialchars($alerta['nome_regra']); ?></td>
                                    <td><strong><?php echo $alerta['pontos_atribuidos']; ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($alerta['data_alerta'])); ?></td>
                                    <td>
                                        <span class="status <?php echo $alerta['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $alerta['status'])); ?>
                                        </span>
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