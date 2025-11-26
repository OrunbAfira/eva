<?php
$erro = $_GET['erro'] ?? '';
$mensagem_erro = '';

$nome_anterior = $_GET['nome'] ?? '';
$email_anterior = $_GET['email'] ?? '';

switch ($erro) {
    case 'email_existe':
        $mensagem_erro = 'Este email já está cadastrado. <a href="index.php" style="color: #2980b9; text-decoration: underline;">Login</a> ou use outro email.';
        break;
    case 'campos_vazios':
        $mensagem_erro = 'Por favor, preencha todos os campos obrigatórios.';
        break;
    case 'falha_registro':
        $mensagem_erro = 'Erro interno. Tente novamente mais tarde.';
        break;
    case 'senhas_nao_coincidem':
        $mensagem_erro = 'As senhas não coincidem. Por favor, tente novamente.';
        break;
    case 'termos_nao_aceitos':
        $mensagem_erro = 'Você deve aceitar os Termos de Uso para criar uma conta.';
        break;
}
?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Plataforma Educacional</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <div class="container-animacao">
                <img src="images/logo.png" alt="Logo EVA" class="logo-animacao"> 
                
                <div class="bloco-letra" id="bloco-e"><span class="letra">E</span><span class="palavra">ducação</span></div>
                <div class="bloco-letra" id="bloco-v"><span class="letra">V</span><span class="palavra">aloriza</span></div>
                <div class="bloco-letra" id="bloco-a"><span class="letra">A</span><span class="palavra">luno</span></div>
            </div>
        </div>
        <div class="login-form register-form">
            <h1>Crie sua conta</h1>
            <p>Preencha os dados para começar a usar a plataforma.</p>

            <?php if (!empty($mensagem_erro)): ?>
                <div class="error-message">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="processar_registro.php">
                <div class="input-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome" 
                           value="<?php echo htmlspecialchars($nome_anterior); ?>" required>
                </div>

                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" 
                           value="<?php echo htmlspecialchars($email_anterior); ?>" required>
                </div>

                <div class="input-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" placeholder="Crie uma senha forte" required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirmar Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Digite a senha novamente" required>
                </div>

                <div class="input-group-checkbox">
                    <input type="checkbox" id="termos" name="termos" required>
                    <label for="termos">Eu li e aceito os <a href="termos.php" target="_blank">Termos de Uso</a> e a <a href="politica_privacidade.php" target="_blank">Política de Privacidade</a>.</label>
                </div>

                <button type="submit" class="btn-login">Registrar</button>
            </form>
            <div class="register-link">
                <p>Já tem uma conta? <a href="index.php">Faça login</a></p>
            </div>
            <div class="footer">
                <p>&copy; 2025 - EVA | <a href="termos.php" target="_blank">Termos de Uso</a> | <a href="politica_privacidade.php" target="_blank">Política de Privacidade</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalAnimationTimeA = 1.5 * 1000 + 1 * 1000; // 2.5s

            setTimeout(() => {
                document.getElementById('bloco-e').classList.add('animacao-completa');
                document.getElementById('bloco-v').classList.add('animacao-completa');
                document.getElementById('bloco-a').classList.add('animacao-completa');
            }, totalAnimationTimeA); 
        });
    </script>
    
</body>
</html>