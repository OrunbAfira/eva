<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - EVA</title>
    <style>
        /* Estilo geral e reset (baseado no index.php) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6; color: #333;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; overflow: hidden;
        }
        /* Container principal */
        .login-container {
            display: flex; width: 900px; height: 600px; background-color: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); border-radius: 10px; overflow: hidden;
        }
        .login-image {
            flex: 1; background-color: #3498db; display: flex; justify-content: flex-start;
            align-items: flex-start; padding: 50px;
        }
        /* Coluna do formulário (direita) */
        .login-form { flex: 1; padding: 40px; display: flex; flex-direction: column; justify-content: center; }
        .login-form h1 { font-size: 28px; margin-bottom: 10px; color: #2c3e50; }
        .login-form p { font-size: 16px; color: #7f8c8d; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #34495e; }
        .input-group input { width: 100%; padding: 12px 15px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        .input-group input:focus { outline: none; border-color: #3498db; }
        .btn-primary {
            width: 100%; padding: 15px; background-color: #3498db; border: none; border-radius: 5px;
            color: #fff; font-size: 18px; font-weight: bold; cursor: pointer; transition: background-color 0.3s;
        }
        .btn-primary:hover { background-color: #2980b9; }
        .links { text-align: center; margin-top: 25px; font-size: 14px; color: #7f8c8d; }
        .links a { color: #3498db; text-decoration: none; font-weight: bold; }
        .links a:hover { text-decoration: underline; }
        .info { background: #ecf7ff; border: 1px solid #b9e1ff; color: #2c3e50; padding: 12px 14px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }

        /* Animações (base do index) */
        .container-animacao { position: relative; width: 300px; height: 450px; display: flex; flex-direction: column; align-items: flex-start; }
        .logo-animacao { margin: 20px 0; width: 100px; height: auto; z-index: 10; }
        .bloco-letra { position: absolute; display: flex; align-items: baseline; font-size: 4em; font-weight: bold; color: #ffffff; white-space: nowrap; }
        .letra { z-index: 10; }
        .palavra { font-size: 0.5em; font-weight: normal; margin-left: 10px; color: #f0f0f0; opacity: 0; transition: opacity 0.5s ease-out; }
        #bloco-e { top: 140px; left: 0; }
        #bloco-v { top: 140px; left: 70px; }
        #bloco-a { top: 140px; left: 140px; }
        #bloco-v { animation: girarDescerV 1.5s ease-out forwards; animation-delay: 0.5s; }
        #bloco-a { animation: girarDescerA 1.5s ease-out forwards; animation-delay: 1s; }
        .bloco-letra.animacao-completa .palavra { opacity: 1; }
        @keyframes girarDescerV { 0% { transform: rotate(0deg); left: 70px; top: 140px; } 100% { transform: rotate(360deg); left: 0; top: 220px; } }
        @keyframes girarDescerA { 0% { transform: rotate(0deg); left: 140px; top: 140px; } 100% { transform: rotate(360deg); left: 0; top: 300px; } }
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

            <form method="post" action="#">
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