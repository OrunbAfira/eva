<?php
session_start();
require_once 'session_guard.php';
require_once 'config.php';

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Agrupa alunos por turma usando o campo texto existente
$turmas = [];
$stmt = $conexao->prepare("SELECT turma, id, nome_completo, ra_matricula, email FROM alunos ORDER BY turma, nome_completo");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $key = $row['turma'] ?: '(Sem turma)';
  if (!isset($turmas[$key])) { $turmas[$key] = []; }
  $turmas[$key][] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Turmas - Plataforma EVA</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="css/componentes/dashboard.css">
</head>
<body>
  <nav class="sidebar">
    <div class="logo">EVA</div>
    <div class="nav-menu">
      <a href="dash.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
      <a href="alunos.php"><i class='bx bxs-user-detail'></i> Alunos</a>
      <a href="turmas.php" class="active"><i class='bx bxs-group'></i> Turmas</a>
      <a href="alertas.php"><i class='bx bxs-bell'></i> Alertas</a>
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
      <h1>Turmas</h1>
      <p>Visualize as turmas e seus alunos cadastrados.</p>
    </header>

    <?php if (empty($turmas)) : ?>
      <section class="widget">
        <div class="widget-header"><h2>Turmas</h2></div>
        <div class="table-container">
          <p>Não há alunos cadastrados ainda.</p>
        </div>
      </section>
    <?php else: ?>
      <?php foreach ($turmas as $nomeTurma => $alunosTurma): ?>
        <section class="widget">
          <div class="widget-header">
            <h2><?php echo htmlspecialchars($nomeTurma); ?></h2>
            <span class="badge"><?php echo count($alunosTurma); ?> aluno(s)</span>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Aluno</th>
                  <th>Matrícula</th>
                  <th>E-mail</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alunosTurma as $al): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($al['nome_completo']); ?></td>
                    <td><?php echo htmlspecialchars($al['ra_matricula']); ?></td>
                    <td><?php echo htmlspecialchars($al['email'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>
  <script src="assets/js/session_timeout.js"></script>
</body>
</html>
