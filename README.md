# EVA

Aplicação web em PHP para gestão de alunos, regras de risco e alertas.

## Estrutura
- `config.php`: inicializa conexão MySQL via variáveis de ambiente (sem credenciais hardcoded).
- `mail_config.php`: configuração SMTP somente por variáveis de ambiente.
- `lib/`: utilidades (ex.: envio SMTP simples).
- `db/`: (se existir) scripts de schema/seed. Ajuste conforme seu banco.
- `assets/`: CSS, JS e pasta de debug (logs não versionados se configurado no `.gitignore`).

## Requisitos
- PHP 7.4+ (ideal 8.x)
- MySQL/MariaDB

## Variáveis de Ambiente (.env)
Crie um arquivo `.env` (não comitar) com:
```
DB_HOST=
DB_USER=
DB_PASS=
DB_NAME=
APP_TZ=America/Sao_Paulo

SMTP_HOST=
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_ENCRYPTION=tls
SMTP_FROM=
SMTP_FROM_NAME=EVA
SMTP_AUTH=true
```

## Segurança
- Nenhuma credencial permanece no código.
- Arquivo `.env` ignorado pelo git.
- Use `password_hash()` para armazenar senhas de usuários.

## Passos Locais
1. Criar `.env` com suas chaves.
2. Importar schema SQL se necessário.
3. Iniciar servidor (XAMPP ou similar) apontando para este diretório.

## Git
```
git init
git add .
git commit -m "Inicial: estrutura sanitizada"
git branch -M main
git remote add origin https://github.com/OrunbAfira/eva.git
git push -u origin main
```
Se pedir autenticação, use PAT (token) em vez de senha.

## Próximos Passos
- Adicionar testes básicos.
- Implementar página de health check.
- Revisar regras de risco e índices no banco para performance.
