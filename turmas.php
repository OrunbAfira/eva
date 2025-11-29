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
  <style>
    .toolbar { display:flex; gap:12px; align-items:center; margin-bottom:16px; }
    .search-input { flex:1; padding:10px 12px; border:1px solid #ccd6dd; border-radius:8px; }
    .turmas-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; }
    .turma-card { padding:12px 14px; border:1px solid #e0e6ea; border-radius:10px; background:#fff; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition: border-color .2s, box-shadow .2s, transform .1s; }
    .turma-card:hover { border-color:#b8c4cc; transform: translateY(-1px); }
    .turma-card.active { border-color:#2c7be5; box-shadow:0 0 0 2px rgba(44,123,229,.15); }
    .turma-name { font-weight:600; color:#2c3e50; }
    .badge { background:#eef3f7; color:#334e68; border-radius:999px; padding:2px 8px; font-size:12px; }
    .empty-note { color:#6b7c93; }
    .hidden { display:none !important; }
  </style>
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
      <?php $selecionada = $_GET['t'] ?? ''; ?>
      <section class="widget">
        <div class="widget-header">
          <h2>Turmas</h2>
          <span class="badge"><?php echo count($turmas); ?> turma(s)</span>
        </div>
        <div class="toolbar">
          <input type="text" id="search" class="search-input" placeholder="Pesquisar turma ou aluno..." autocomplete="off">
          <button id="clearSearch" class="btn-submit" type="button">Limpar</button>
        </div>
        <div class="turmas-grid">
          <?php foreach ($turmas as $nomeTurma => $alunosTurma): ?>
            <?php $isActive = ($selecionada === $nomeTurma) ? ' active' : ''; ?>
            <button class="turma-card<?php echo $isActive; ?>" type="button" data-turma="<?php echo htmlspecialchars($nomeTurma); ?>">
              <span class="turma-name"><?php echo htmlspecialchars($nomeTurma); ?></span>
              <span class="badge"><?php echo count($alunosTurma); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if ($selecionada && isset($turmas[$selecionada])): ?>
        <section class="widget">
          <div class="widget-header">
            <h2><?php echo htmlspecialchars($selecionada); ?></h2>
            <span class="badge"><?php echo count($turmas[$selecionada]); ?> aluno(s)</span>
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
                <?php foreach ($turmas[$selecionada] as $al): ?>
                  <tr class="aluno-row" data-busca="<?php echo htmlspecialchars(strtolower(($al['nome_completo'] ?? '') . ' ' . ($al['ra_matricula'] ?? '') . ' ' . ($al['email'] ?? ''))); ?>">
                    <td><?php echo htmlspecialchars($al['nome_completo']); ?></td>
                    <td><?php echo htmlspecialchars($al['ra_matricula']); ?></td>
                    <td><?php echo htmlspecialchars($al['email'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php elseif ($selecionada): ?>
        <section class="widget">
          <div class="table-container empty-note">Nenhuma turma encontrada.</div>
        </section>
      <?php endif; ?>
    <?php endif; ?>

  </main>
  <script src="assets/js/session_timeout.js"></script>
  <script>
    const search = document.getElementById('search');
    const clearBtn = document.getElementById('clearSearch');
    const turmaCards = Array.from(document.querySelectorAll('.turma-card'));
    function norm(s){
      return (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    }
    function setActiveTurma(name) {
      // Atualiza estilo ativo
      turmaCards.forEach(c => c.classList.toggle('active', c.dataset.turma === name));
      // Navega mantendo apenas parâmetro t (sem recarregar por busca)
      const url = new URL(window.location.href);
      url.searchParams.set('t', name);
      url.searchParams.delete('q');
      window.location.href = url.toString();
    }
    turmaCards.forEach(c => c.addEventListener('click', () => setActiveTurma(c.dataset.turma)));

    function filterUI() {
      const raw = norm(search.value || '');
      const tokens = raw.split(/[\s,]+/).map(t => t.trim()).filter(Boolean);
      const hasQuery = tokens.length > 0;
      // Filtra cards de turmas por nome (qualquer token por prefixo)
      turmaCards.forEach(c => {
        const name = norm(c.dataset.turma || '');
        const match = !hasQuery || tokens.some(tok => name.startsWith(tok));
        c.classList.toggle('hidden', !match);
      });
      // Filtra linhas da tabela atual por prefixo em qualquer palavra
      document.querySelectorAll('.aluno-row').forEach(row => {
        const hay = norm(row.getAttribute('data-busca') || '');
        const parts = hay.split(/\s+/);
        const match = !hasQuery || tokens.some(tok => parts.some(p => p.startsWith(tok)));
        row.classList.toggle('hidden', !match);
      });
    }
    if (search) {
      search.addEventListener('input', filterUI);
      search.addEventListener('keydown', (e) => { if (e.key === 'Escape') { search.value=''; filterUI(); } });
    }
    if (clearBtn) {
      clearBtn.addEventListener('click', () => { search.value = ''; filterUI(); });
    }
  </script>
</body>
</html>
