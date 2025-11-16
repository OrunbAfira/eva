<?php
// Detalhes da conexão com o banco de dados
// Define o fuso horário padrão do PHP para garantir horários corretos em logs e datas
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('America/Sao_Paulo');
}
 
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');     
define('DB_NAME', 'eva'); 

// Tenta estabelecer a conexão
$conexao = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica se houve erro na conexão
if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}
?>