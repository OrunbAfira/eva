<?php
// Timezone
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(getenv('APP_TZ') ?: 'America/Sao_Paulo');
}

// DB config via env (fallback para local)
$db_server = getenv('DB_HOST') ?: '127.0.0.1';
$db_user   = getenv('DB_USER') ?: 'root';
$db_pass   = getenv('DB_PASS') ?: '';
$db_name   = getenv('DB_NAME') ?: 'eva';

// Conexão
$conexao = new mysqli($db_server, $db_user, $db_pass, $db_name);
if ($conexao->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conexao->connect_error);
}
$conexao->set_charset('utf8mb4');
?>
