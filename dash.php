<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
require_once 'risk_functions.php';

$nome_usuario = $_SESSION['usuario_nome'];
$primeiro_nome = explode(' ', $nome_usuario)[0];

date_default_timezone_set('America/Sao_Paulo');

// Formatação português com UTF-8
$dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

$timestamp = time();
$dia_semana = $dias[date('w', $timestamp)];
$dia = date('d', $timestamp);
$mes = $meses[date('n', $timestamp) - 1];
$ano = date('Y', $timestamp);

$data_atual = $dia_semana . ', ' . $dia . ' de ' . $mes . ' de ' . $ano;

$stats = [
    'alunos_risco_alto' => 0,
    'novos_alertas_hoje' => 0,
    'intervencoes_andamento' => 0,
    'regras_ativas' => 0,
    'total_alunos' => 0
];

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM alunos WHERE nivel_risco = 'alto'");
$stmt->execute();
$result = $stmt->get_result();
$stats['alunos_risco_alto'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM alertas WHERE DATE(data_alerta) = CURDATE() AND status = 'novo'");
$stmt->execute();
$result = $stmt->get_result();
$stats['novos_alertas_hoje'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM intervencoes WHERE status = 'em_andamento'");
$stmt->execute();
$result = $stmt->get_result();
$stats['intervencoes_andamento'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM regras_risco WHERE ativo = 1");
$stmt->execute();
$result = $stmt->get_result();
$stats['regras_ativas'] = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
$stmt->execute();
$result = $stmt->get_result();
$stats['total_alunos'] = $result->fetch_assoc()['total'];
$stmt->close();

$novos_alertas = [];
$stmt = $conexao->prepare("
    SELECT a.nome_completo, a.turma, r.nome_regra, al.pontos_atribuidos 
    FROM alertas al 
    JOIN alunos a ON al.aluno_id = a.id 
    JOIN regras_risco r ON al.regra_id = r.id 
    WHERE DATE(al.data_alerta) = CURDATE() AND al.status = 'novo'
    ORDER BY al.data_alerta DESC 
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $novos_alertas[] = $row;
}
$stmt->close();

$alunos_maior_risco = [];
$stmt = $conexao->prepare("
    SELECT nome_completo, turma, pontuacao_risco, nivel_risco 
    FROM alunos 
    WHERE pontuacao_risco > 0 
    ORDER BY pontuacao_risco DESC 
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $alunos_maior_risco[] = $row;
}
$stmt->close();

$regras_impacto = [];
$stmt = $conexao->prepare("
    SELECT r.nome_regra, SUM(al.pontos_atribuidos) as total_pontos, COUNT(DISTINCT al.aluno_id) as alunos_afetados
    FROM alertas al 
    JOIN regras_risco r ON al.regra_id = r.id 
    WHERE al.data_alerta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY r.id, r.nome_regra
    ORDER BY total_pontos DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $regras_impacto[] = $row;
}
$stmt->close();

$mensagem_regra = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'nova_regra') {
        $nome_regra = $_POST['nome_regra'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $pontos = $_POST['pontos'] ?? 0;
        
        if (!empty($nome_regra) && $pontos > 0) {
            $stmt_check = $conexao->prepare("SELECT id FROM regras_risco WHERE nome_regra = ?");
            $stmt_check->bind_param("s", $nome_regra);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $mensagem_regra = "<div class='mensagem erro'>Já existe um critério com este nome. Escolha um nome diferente.</div>";
            } else {
                $stmt = $conexao->prepare("INSERT INTO regras_risco (nome_regra, descricao, pontos) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $nome_regra, $descricao, $pontos);
                
                if ($stmt->execute()) {
                    $mensagem_regra = "<div class='mensagem sucesso'>Critério de avaliação criado com sucesso!</div>";
                    $_POST = [];
                } else {
                    $mensagem_regra = "<div class='mensagem erro'>Erro ao criar critério de avaliação.</div>";
                }
                $stmt->close();
            }
            $stmt_check->close();
        } else {
            $mensagem_regra = "<div class='mensagem erro'>Nome do critério e pontos são obrigatórios. Pontos devem ser maior que zero.</div>";
        }
    } elseif ($_POST['action'] === 'toggle_regra') {
        // Validação rigorosa dos inputs
        $regra_id = filter_var($_POST['regra_id'] ?? 0, FILTER_VALIDATE_INT);
        $novo_status = filter_var($_POST['novo_status'] ?? 0, FILTER_VALIDATE_INT);
        
        // Verifica se os valores são válidos
        if ($regra_id === false || $regra_id <= 0) {
            $mensagem_regra = "<div class='mensagem erro'>ID de regra inválido.</div>";
        } elseif ($novo_status === false || ($novo_status !== 0 && $novo_status !== 1)) {
            $mensagem_regra = "<div class='mensagem erro'>Status inválido.</div>";
        } else {
            $stmt = $conexao->prepare("UPDATE regras_risco SET ativo = ? WHERE id = ?");
            
            if (!$stmt) {
                $mensagem_regra = "<div class='mensagem erro'>Erro ao preparar atualização.</div>";
                error_log("Erro prepare toggle_regra: " . $conexao->error);
            } else {
                $stmt->bind_param("ii", $novo_status, $regra_id);
                
                if ($stmt->execute()) {
                    $linhas_afetadas = $stmt->affected_rows;
                    $stmt->close();
                    
                    if ($linhas_afetadas > 0) {
                        // Recalcula a pontuação de todos os alunos afetados por esta regra
                        $alunos_atualizados = recalcularAlunosPorRegra($conexao, $regra_id);
                        
                        $status_texto = $novo_status ? 'ativada' : 'desativada';
                        $mensagem_regra = "<div class='mensagem sucesso'>Regra {$status_texto} com sucesso! {$alunos_atualizados} aluno(s) atualizado(s).</div>";
                    } else {
                        $mensagem_regra = "<div class='mensagem erro'>Regra não encontrada.</div>";
                    }
                } else {
                    $mensagem_regra = "<div class='mensagem erro'>Erro ao alterar status da regra.</div>";
                    error_log("Erro execute toggle_regra: " . $stmt->error);
                    $stmt->close();
                }
            }
        }
    }
}

$todas_regras = [];
$stmt = $conexao->prepare("
    SELECT id, nome_regra, descricao, pontos, ativo,
           (SELECT COUNT(*) FROM alertas WHERE regra_id = regras_risco.id) as total_usos
    FROM regras_risco 
    ORDER BY ativo DESC, nome_regra ASC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $todas_regras[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Plataforma EVA</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <nav class="sidebar">
        <div class="logo">EVA</div>
        <div class="nav-menu">
            <a href="#" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="alunos.php"><i class='bx bxs-user-detail'></i> Alunos</a>
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
            <h1>Olá, <?php echo htmlspecialchars($primeiro_nome); ?>! Bem-vindo(a) de volta.</h1>
            <p><?php echo ucfirst($data_atual); ?></p>
        </header>

        <section class="kpi-cards">
            <div class="card">
                <h3>Alunos em Risco Alto</h3>
                <div class="value high"><?php echo $stats['alunos_risco_alto']; ?></div>
            </div>
            <div class="card">
                <h3>Novos Alertas (hoje)</h3>
                <div class="value medium"><?php echo $stats['novos_alertas_hoje']; ?></div>
            </div>
            <div class="card">
                <h3>Intervenções em Andamento</h3>
                <div class="value success"><?php echo $stats['intervencoes_andamento']; ?></div>
            </div>
            <div class="card">
                <h3>Total de Alunos</h3>
                <div class="value"><?php echo $stats['total_alunos']; ?></div>
            </div>
        </section>

        <section class="widget">
            <div class="widget-header">
                <h2>Ações Prioritárias</h2>
            </div>
            <div class="tabs">
                <div class="tab-link active" data-tab="1">Novos Alertas</div>
                <div class="tab-link" data-tab="2">Alunos de Maior Risco</div>
            </div>
            
            <div id="tab-1" class="tab-content active">
                <table>
                    <thead>
                        <tr><th>Aluno</th><th>Turma</th><th>Regra Acionada</th><th>Pontos</th></tr>
                    </thead>
                    <tbody id="novos-alertas-tbody">
                        <?php if (empty($novos_alertas)): ?>
                            <tr class="empty-row">
                                <td colspan="4">Nenhum novo alerta hoje.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($novos_alertas as $alerta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alerta['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($alerta['turma'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($alerta['nome_regra']); ?></td>
                                    <td><strong><?php echo $alerta['pontos_atribuidos']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-2" class="tab-content">
                <table>
                    <thead>
                        <tr><th>Aluno</th><th>Turma</th><th>Pontuação Total</th><th>Nível</th></tr>
                    </thead>
                    <tbody id="maior-risco-tbody">
                        <?php if (empty($alunos_maior_risco)): ?>
                            <tr class="empty-row">
                                <td colspan="4">Nenhum aluno em categoria de risco elevado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alunos_maior_risco as $aluno): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($aluno['turma'] ?: '-'); ?></td>
                                    <td><strong><?php echo $aluno['pontuacao_risco']; ?></strong></td>
                                    <td>
                                        <span class="risk-level <?php echo $aluno['nivel_risco']; ?>"></span>
                                        <?php echo ucfirst($aluno['nivel_risco']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="widget">
            <div class="widget-header">
                <h2>Impacto das Regras Ativas (7d)</h2>
                <a href="#regras-criterios" class="btn" onclick="document.getElementById('regras-criterios').scrollIntoView({behavior: 'smooth'})">Gerenciar Regras</a>
            </div>
            <table>
                <thead><tr><th>Nome da Regra</th><th>Pontos</th><th>Alunos Afetados</th></tr></thead>
                <tbody id="regras-impacto-tbody">
                    <?php if (empty($regras_impacto)): ?>
                        <tr class="empty-row">
                            <td colspan="3">Nenhum dado de impacto de regras disponível.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($regras_impacto as $regra): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($regra['nome_regra']); ?></td>
                                <td><strong><?php echo $regra['total_pontos']; ?></strong></td>
                                <td><?php echo $regra['alunos_afetados']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Seção de Gerenciamento de Critérios/Regras -->
        <section class="widget" id="regras-criterios">
            <div class="widget-header">
                <h2>Critérios de Avaliação</h2>
                <span class="badge"><?php echo count($todas_regras); ?> regras</span>
            </div>

            <!-- Card informativo -->
            <div class="info-card">
                <div class="info-content">
                    <h4><i class='bx bx-info-circle'></i> Como funcionam os Critérios</h4>
                    <p>Os critérios de avaliação definem situações que aumentam o risco acadêmico dos alunos. 
                    Cada critério possui uma pontuação que é somada ao aluno quando um alerta é criado. 
                    O nível de risco é calculado automaticamente: <strong>Baixo (0-9)</strong>, <strong>Médio (10-24)</strong>, <strong>Alto (25+)</strong>.</p>
                </div>
            </div>

            <?php echo $mensagem_regra; ?>

            <!-- Formulário para Nova Regra -->
            <div class="form-container">
                <h3>Adicionar Novo Critério</h3>
                <form action="dash.php#regras-criterios" method="POST">
                    <input type="hidden" name="action" value="nova_regra">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_regra">Nome do Critério *</label>
                            <input type="text" id="nome_regra" name="nome_regra" 
                                   value="<?php echo htmlspecialchars($_POST['nome_regra'] ?? ''); ?>"
                                   placeholder="Ex: Faltas Excessivas" required>
                        </div>
                        <div class="form-group">
                            <label for="pontos">Pontos de Risco *</label>
                            <input type="number" id="pontos" name="pontos" min="1" max="50" 
                                   value="<?php echo htmlspecialchars($_POST['pontos'] ?? ''); ?>"
                                   placeholder="Ex: 15" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="2" 
                                  placeholder="Descreva quando este critério deve ser aplicado..."><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Criar Critério</button>
                    </div>
                </form>
            </div>

            <!-- Lista de Regras Existentes -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Critério</th>
                            <th>Descrição</th>
                            <th>Pontos</th>
                            <th>Status</th>
                            <th>Usos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todas_regras)): ?>
                            <tr class="empty-row">
                                <td colspan="6">Nenhum critério configurado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todas_regras as $regra): ?>
                                <tr class="<?php echo $regra['ativo'] ? '' : 'regra-inativa'; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($regra['nome_regra']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($regra['descricao'] ?: 'Sem descrição'); ?>
                                    </td>
                                    <td>
                                        <span class="pontos-badge"><?php echo $regra['pontos']; ?></span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $regra['ativo'] ? 'ativo' : 'inativo'; ?>">
                                            <?php echo $regra['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $regra['total_usos']; ?></td>
                                    <td>
                                        <form style="display: inline;" action="dash.php#regras-criterios" method="POST">
                                            <input type="hidden" name="action" value="toggle_regra">
                                            <input type="hidden" name="regra_id" value="<?php echo $regra['id']; ?>">
                                            <input type="hidden" name="novo_status" value="<?php echo $regra['ativo'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn-action <?php echo $regra['ativo'] ? 'desativar' : 'ativar'; ?>" 
                                                    title="<?php echo $regra['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                                <i class='bx <?php echo $regra['ativo'] ? 'bx-pause' : 'bx-play'; ?>'></i>
                                            </button>
                                        </form>
                                        <a href="#" class="btn-action" title="Editar" onclick="editarRegra(<?php echo $regra['id']; ?>)">
                                            <i class='bx bx-edit'></i>
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
    
    <script>
        const tabs = document.querySelectorAll('.tab-link');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('active'));
                contents.forEach(item => item.classList.remove('active'));

                const targetContent = document.getElementById('tab-' + tab.dataset.tab);
                tab.classList.add('active');
                if(targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });

        function editarRegra(id) {
            alert('Funcionalidade de edição será implementada em breve.\nID da regra: ' + id);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const formsToggle = document.querySelectorAll('form input[name="action"][value="toggle_regra"]');
            formsToggle.forEach(function(input) {
                const form = input.closest('form');
                const novoStatus = form.querySelector('input[name="novo_status"]').value;
                
                if (novoStatus === '0') {
                    form.addEventListener('submit', function(e) {
                        const confirmacao = confirm('Tem certeza que deseja desativar esta regra?\n\nAtenção: Regras desativadas não aparecerão mais nas opções de novos alertas.');
                        if (!confirmacao) {
                            e.preventDefault();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>