# EVA v0.3.0 — Sessão e Inatividade

## Destaques
- Timeout de sessão por inatividade: 5 minutos no servidor e no cliente.
- Regeneração de ID de sessão no login para mitigar fixation.
- Guard de sessão reutilizável aplicado nas páginas protegidas.

## Alterações
- `processar_login.php`: adiciona `session_regenerate_id(true)` e registra `$_SESSION['last_activity']` após login.
- `session_guard.php` (novo): valida sessão ativa, expira após 300s sem atividade, destrói sessão e redireciona com `?session=expired`.
- `assets/js/session_timeout.js` (novo): redireciona automaticamente para `logout.php?session=expired` após 5 minutos sem interação do usuário.
- `dash.php`, `alunos.php`, `alertas.php`: incluem o guard e o script de inatividade.

## Como usar
- Após login, mantenha a página aberta: sem interação por 5 minutos você será desconectado automaticamente.
- Qualquer interação (clique, digitação, rolagem, toque) renova o contador no cliente; cada request válida renova `last_activity` no servidor.

## Notas de segurança
- Cookies de sessão: recomendável ativar `HttpOnly`, `Secure` (em HTTPS) e `SameSite=Strict`/`Lax` conforme necessidade.
- CSRF: ainda não implementado; considerar adicionar tokens CSRF nos formulários.
- Tempo configurável: caso deseje um valor diferente, ajuste `timeout` em `session_guard.php` e `TIMEOUT_MS` em `session_timeout.js`.

## Migração/Compatibilidade
- Sem mudanças de schema. O comportamento de expiração por inatividade passa a ser padrão em páginas protegidas.

## Créditos
- Implementação orientada pela solicitação de logout automático por inatividade (5 minutos).
