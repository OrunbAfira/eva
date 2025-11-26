<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - Plataforma Educacional</title>
    <link rel="stylesheet" href="css/componentes/legal.css">
</head>
<body>
    <div class="container">
        <h1>Política de Privacidade</h1>
        <p><strong>Vigência/Última atualização:</strong> <?php echo date('d/m/Y'); ?></p>

        <h2>1. Quem somos (Controlador)</h2>
        <p>A plataforma EVA (Educação Valoriza Aluno) é a controladora dos dados pessoais tratados neste ambiente. Contato do encarregado (DPO): <a href="mailto:privacidade@eva.com">privacidade@eva.com</a>.</p>

        <h2>2. Quais dados tratamos</h2>
        <ul>
            <li><strong>Cadastrais:</strong> nome, e-mail, credenciais de acesso (senha armazenada com hash).</li>
            <li><strong>Acadêmicos:</strong> matrícula, turma, pontuação de risco, alertas e intervenções.</li>
            <li><strong>Técnicos/uso:</strong> IP, data/hora de acesso, agente de usuário, logs de sessão.</li>
        </ul>

        <h2>3. Finalidades do tratamento</h2>
        <ul>
            <li>Autenticar usuários e manter sessões ativas (cookies de sessão/JWT).</li>
            <li>Executar funcionalidades acadêmicas (cadastro de alunos, alertas, regras de risco).</li>
            <li>Enviar comunicações operacionais (ex.: recuperação de senha via e-mail).</li>
            <li>Garantir segurança, auditoria e prevenção a fraudes.</li>
        </ul>

        <h2>4. Bases legais (LGPD)</h2>
        <ul>
            <li><strong>Execução de contrato</strong> e procedimentos preliminares: prestação do serviço educacional.</li>
            <li><strong>Legítimo interesse</strong> do controlador: segurança, melhoria e monitoramento do desempenho acadêmico.</li>
            <li><strong>Consentimento</strong> quando exigido, especialmente para menores, conforme ECA e LGPD.</li>
            <li><strong>Cumprimento de obrigação legal/regulatória</strong> quando aplicável.</li>
        </ul>

        <h2>5. Crianças e adolescentes</h2>
        <p>Quando houver tratamento de dados de crianças/adolescentes, observamos o melhor interesse do menor e, quando necessário, coletamos consentimento de pelo menos um dos responsáveis legais.</p>

        <h2>6. Compartilhamento e Operadores</h2>
        <p>Compartilhamos dados com: (i) equipe pedagógica e de gestão escolar autorizada; (ii) provedores de serviços (ex.: envio de e-mail/SMTP, hospedagem). Tais operadores seguem instruções do controlador e adotam medidas de segurança compatíveis.</p>

        <h2>7. Transferência internacional</h2>
        <p>Pode ocorrer quando utilizamos serviços com infraestrutura fora do Brasil (ex.: provedor de e-mail). Nesses casos, buscamos garantias adequadas de proteção de dados.</p>

        <h2>8. Segurança da informação</h2>
        <ul>
            <li>Senhas com <strong>hash</strong> (ex.: bcrypt) e tokens de sessão/JWT com expiração.</li>
            <li>Transmissão protegida por <strong>TLS</strong> quando em produção.</li>
            <li>Privilégios mínimos e registro de acessos relevantes.</li>
        </ul>
        <p>Nenhuma medida é infalível; mantemos melhoria contínua e resposta a incidentes.</p>

        <h2>9. Cookies e tecnologias semelhantes</h2>
        <ul>
            <li><strong>Estritamente necessários:</strong> autenticação e manutenção de sessão.</li>
            <li><strong>Preferências/funcionais:</strong> melhorar a experiência do usuário (quando aplicável).</li>
        </ul>
        <p>Você pode ajustar cookies no navegador; funcionalidades essenciais podem depender deles.</p>

        <h2>10. Decisões automatizadas e perfilamento</h2>
        <p>A pontuação de risco é um <strong>indicador automatizado</strong> baseado em regras configuráveis, com <strong>revisão humana</strong> e possibilidade de contestação. Não há decisões exclusivamente automatizadas com efeitos jurídicos plenos.</p>

        <h2>11. Retenção de dados</h2>
        <ul>
            <li>Dados de conta: enquanto a conta estiver ativa e/ou conforme exigências legais.</li>
            <li>Logs e tokens: por períodos compatíveis com segurança e auditoria (ex.: tokens de refresh revogados e logs de acesso por até 6–12 meses).</li>
            <li>Alertas e histórico acadêmico: conforme política institucional e normas aplicáveis.</li>
        </ul>

        <h2>12. Direitos do titular</h2>
        <p>Você pode solicitar: confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação, informação de compartilhamentos e revisão de decisões. Contato: <a href="mailto:privacidade@eva.com">privacidade@eva.com</a>.</p>

        <h2>13. Alterações desta política</h2>
        <p>Podemos atualizar esta política para refletir mudanças legais ou operacionais. Publicaremos a versão vigente nesta página.</p>

        <p>Consulte também os <a href="termos.php" target="_blank">Termos de Uso</a>.</p>
        <button onclick="window.close();" class="btn-voltar">Fechar e Voltar</button>
    </div>
</body>
</html>
