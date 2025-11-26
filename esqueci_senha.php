<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - EVA</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* Estilos específicos para esta página podem ser mantidos aqui ou movidos */
        .info { background: #ecf7ff; border: 1px solid #b9e1ff; color: #2c3e50; padding: 12px 14px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .btn-primary {
            width: 100%; padding: 15px; background-color: #3498db; border: none; border-radius: 5px;
            color: #fff; font-size: 18px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;
        }
        .btn-primary:hover { background-color: #2980b9; }
        .links { text-align: center; margin-top: 25px; font-size: 14px; color: #7f8c8d; }
        .links a { color: #3498db; text-decoration: none; font-weight: bold; }
        .links a:hover { text-decoration: underline; }
    </style>
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
            <h1>Esqueci minha senha</h1>
            <p>Informe seu e-mail. Caso exista uma conta, você receberá instruções para redefinir a senha.</p>

            <div class="info">Por segurança, a resposta será a mesma independentemente do e-mail informado.</div>

            <form method="post" action="processar_esqueci_senha.php">
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required>
                </div>
                <button type="submit" class="btn-primary">Enviar instruções</button>
            </form>

            <div class="links">
                <p><a href="index.php">Voltar ao login</a></p>
                <p>Não tem uma conta? <a href="register.php">Registre-se</a></p>
            </div>

            <div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #7f8c8d;">
                <p>&copy; 2025 - EVA | <a href="termos.php" target="_blank" style="color: #3498db; text-decoration: none;">Termos de Uso</a> | <a href="politica_privacidade.php" target="_blank" style="color: #3498db; text-decoration: none;">Política de Privacidade</a></p>
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