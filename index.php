<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plataforma Educacional</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
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

        <div class="login-form">
            <h1>Bem-vindo(a) de volta!</h1>
            <p>Acesse a plataforma para continuar seu trabalho.</p>

            <?php 
            $status = $_GET['status'] ?? '';
            if ($status === 'registrado'): 
            ?>
                <div class="success-message">
                    Conta criada com sucesso! Agora você pode fazer login.
                </div>
            <?php endif; ?>

            <form method="post" action="processar_login.php">
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="E-mail" required>
                </div>

                <div class="input-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" placeholder="Senha" required>
                </div>

                <div class="form-options">
                    <div></div> <a href="esqueci_senha.php">Esqueci minha senha</a>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>
            <div class="register-link">
                <p>Não tem uma conta? <a href="register.php">Registre-se</a></p>
            </div>
            <div class="footer">
                <p>&copy; 2025 - EVA | Educação Valoriza Aluno</p>
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