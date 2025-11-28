<?php
// Fuso horário padrão (pode ser alterado via APP_TZ)
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(getenv('APP_TZ') ?: 'America/Sao_Paulo');
}

// Credenciais do banco via variáveis de ambiente com padrões para XAMPP/MySQL
// Padrões: host=localhost, user=root, pass="", db=eva
$db_server = getenv('DB_HOST') ?: 'localhost';
$db_user   = getenv('DB_USER') ?: 'root';
$db_pass   = getenv('DB_PASS') ?: '';
$db_name   = getenv('DB_NAME') ?: 'eva';

if (!defined('DB_SERVER')) { define('DB_SERVER', $db_server); }
if (!defined('DB_USERNAME')) { define('DB_USERNAME', $db_user); }
if (!defined('DB_PASSWORD')) { define('DB_PASSWORD', $db_pass); }
if (!defined('DB_NAME')) { define('DB_NAME', $db_name); }

// Conecta somente se todas as variáveis necessárias estiverem definidas
if ($db_server !== '' && $db_user !== '' && $db_name !== '') {
    $conexao = @new mysqli($db_server, $db_user, $db_pass, $db_name);
    if ($conexao->connect_error) {
        if (!headers_sent()) header('HTTP/1.1 500 Internal Server Error');
        exit('Erro interno. Tente novamente mais tarde.');
    }
    $conexao->set_charset('utf8mb4');
} else {
    // Sem configuração, mantém `$conexao` como null
    $conexao = null;
}
?>