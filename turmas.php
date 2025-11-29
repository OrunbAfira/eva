<?php
session_start();
require_once 'session_guard.php';
require_once 'config.php';

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';

// Agrupa alunos por turma usando o campo texto existente
$turmas = [];
$stmt = $conexao->prepare("SELECT turma, id, nome_completo, ra_matricula, email, pontuacao_risco, nivel_risco FROM alunos ORDER BY turma, nome_completo");
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
      <?php 
        $selecionadasParam = $_GET['t'] ?? '';
        $selecionadas = array_filter(array_map('trim', explode(',', $selecionadasParam)));
      ?>
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
            <?php $isActive = (in_array($nomeTurma, $selecionadas)) ? ' active' : ''; ?>
            <button class="turma-card<?php echo $isActive; ?>" type="button" data-turma="<?php echo htmlspecialchars($nomeTurma); ?>">
              <span class="turma-name"><?php echo htmlspecialchars($nomeTurma); ?></span>
              <span class="badge"><?php echo count($alunosTurma); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <?php if (!empty($selecionadas)): ?>
        <?php 
          // agregação de alunos das turmas selecionadas
          $alunosSelecionados = [];
          foreach ($selecionadas as $tSel) {
            if (isset($turmas[$tSel])) {
              foreach ($turmas[$tSel] as $al) { $alunosSelecionados[] = $al; }
            }
          }
          // calcula maior ofensor geral dentre selecionados
          $maiorOfensor = null;
          foreach ($alunosSelecionados as $al) {
            if ($maiorOfensor === null || ((int)($al['pontuacao_risco'] ?? 0)) > (int)($maiorOfensor['pontuacao_risco'] ?? 0)) {
              $maiorOfensor = $al;
            }
          }
        ?>
        <section class="widget">
          <div class="widget-header">
            <h2>Turmas selecionadas</h2>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:8px;">
              <span class="badge" title="Turmas">Turmas: <?php echo count($selecionadas); ?></span>
              <span class="badge" title="Alunos">Alunos: <?php echo count($alunosSelecionados); ?></span>
              <?php if ($maiorOfensor): ?>
                <span class="badge" title="Maior ofensor" style="background:#ffe8e6;color:#9f2d20">
                  Maior ofensor: <?php echo htmlspecialchars($maiorOfensor['nome_completo']); ?> (<?php echo (int)$maiorOfensor['pontuacao_risco']; ?> pts)
                </span>
              <?php endif; ?>
            </div>
          </div>
          <div class="toolbar" style="margin-top:-6px">
            <label for="sortAlunos" style="color:#6b7c93">Ordenar por:</label>
            <select id="sortAlunos" class="search-input" style="max-width:240px">
              <option value="nome_asc">Nome (A-Z)</option>
              <option value="nome_desc">Nome (Z-A)</option>
              <option value="pontos_desc">Pontos (maior primeiro)</option>
              <option value="pontos_asc">Pontos (menor primeiro)</option>
              <option value="nivel_desc">Nível (alto→baixo)</option>
              <option value="nivel_asc">Nível (baixo→alto)</option>
            </select>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Aluno</th>
                  <th>Matrícula</th>
                  <th>E-mail</th>
                  <th>Pontos</th>
                  <th>Nível</th>
                  <th>Maior Ofensor</th>
                </tr>
              </thead>
              <tbody id="alunosSelecionadosBody">
                <?php foreach ($alunosSelecionados as $al): ?>
                  <tr class="aluno-row" 
                      data-busca="<?php echo htmlspecialchars(strtolower(($al['nome_completo'] ?? '') . ' ' . ($al['ra_matricula'] ?? '') . ' ' . ($al['email'] ?? ''))); ?>"
                      data-nome="<?php echo htmlspecialchars(strtolower($al['nome_completo'] ?? '')); ?>"
                      data-pontos="<?php echo (int)($al['pontuacao_risco'] ?? 0); ?>"
                      data-nivel="<?php echo htmlspecialchars(strtolower($al['nivel_risco'] ?? 'neutro')); ?>">
                    <td><?php echo htmlspecialchars($al['nome_completo']); ?></td>
                    <td><?php echo htmlspecialchars($al['ra_matricula']); ?></td>
                    <td><?php echo htmlspecialchars($al['email'] ?? '-'); ?></td>
                    <td><?php echo (int)($al['pontuacao_risco'] ?? 0); ?></td>
                    <td>
                      <?php $nivel = $al['nivel_risco'] ?? 'neutro'; ?>
                      <span class="badge" style="background:#f0f4ff;color:#3b5bdb"><?php echo htmlspecialchars(ucfirst($nivel)); ?></span>
                    </td>
                    <td>
                      <?php
                        $topOfensor = '-';
                        if (!empty($al['id'])) {
                          $stmtTop = $conexao->prepare("SELECT r.nome_regra, SUM(al.pontos_atribuidos) as total FROM alertas al JOIN regras_risco r ON r.id = al.regra_id WHERE al.aluno_id = ? GROUP BY r.id, r.nome_regra ORDER BY total DESC LIMIT 1");
                          $stmtTop->bind_param("i", $al['id']);
                          if ($stmtTop->execute()) {
                            $resTop = $stmtTop->get_result();
                            if ($rowTop = $resTop->fetch_assoc()) {
                              $topOfensor = $rowTop['nome_regra'] . ' (' . (int)$rowTop['total'] . ' pts)';
                            }
                          }
                          $stmtTop->close();
                        }
                      ?>
                      <span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba"><?php echo htmlspecialchars($topOfensor); ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php else: ?>
        <section class="widget">
          <div class="table-container empty-note">Selecione uma ou mais turmas acima para visualizar os alunos.</div>
        </section>
      <?php endif; ?>
    <?php endif; ?>

  </main>
  <script src="assets/js/session_timeout.js"></script>
  <script>
    const search = document.getElementById('search');
    const clearBtn = document.getElementById('clearSearch');
    const turmaCards = Array.from(document.querySelectorAll('.turma-card'));
    const sortSelect = document.getElementById('sortAlunos');
    function norm(s){
      return (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    }
    function getSelectedFromURL(){
      const url = new URL(window.location.href);
      const t = url.searchParams.get('t') || '';
      return t.split(',').map(s=>s.trim()).filter(Boolean);
    }
    function setSelectedToURL(selected){
      const url = new URL(window.location.href);
      if (selected.length) url.searchParams.set('t', selected.join(',')); else url.searchParams.delete('t');
      url.searchParams.delete('q');
      window.location.href = url.toString();
    }
    function toggleTurma(name){
      const sel = new Set(getSelectedFromURL());
      if (sel.has(name)) sel.delete(name); else sel.add(name);
      setSelectedToURL(Array.from(sel));
    }
    turmaCards.forEach(c => c.addEventListener('click', () => toggleTurma(c.dataset.turma)));

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
    function sortRows() {
      const tbody = document.getElementById('alunosSelecionadosBody');
      if (!tbody || !sortSelect) return;
      const rows = Array.from(tbody.querySelectorAll('.aluno-row'));
      const byNivelRank = (nivel) => {
        const order = { alto:3, medio:2, baixo:1, neutro:0 };
        return order[nivel] ?? 0;
      };
      const val = sortSelect.value;
      rows.sort((a,b) => {
        if (val === 'nome_asc') return a.dataset.nome.localeCompare(b.dataset.nome);
        if (val === 'nome_desc') return b.dataset.nome.localeCompare(a.dataset.nome);
        if (val === 'pontos_desc') return (parseInt(b.dataset.pontos)||0) - (parseInt(a.dataset.pontos)||0);
        if (val === 'pontos_asc') return (parseInt(a.dataset.pontos)||0) - (parseInt(b.dataset.pontos)||0);
        if (val === 'nivel_desc') return byNivelRank(b.dataset.nivel) - byNivelRank(a.dataset.nivel);
        if (val === 'nivel_asc') return byNivelRank(a.dataset.nivel) - byNivelRank(b.dataset.nivel);
        return 0;
      });
      rows.forEach(r => tbody.appendChild(r));
    }
    if (search) {
      search.addEventListener('input', filterUI);
      search.addEventListener('keydown', (e) => { if (e.key === 'Escape') { search.value=''; filterUI(); } });
    }
    if (clearBtn) {
      clearBtn.addEventListener('click', () => { search.value = ''; filterUI(); });
    }
    if (sortSelect) {
      sortSelect.addEventListener('change', () => { sortRows(); });
      window.addEventListener('load', () => { sortRows(); });
    }
  </script>
</body>
</html>
