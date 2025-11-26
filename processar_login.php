<?php
session_start();

require_once 'config.php'; // Inclui a configuração do banco de dados

// A variável $conexao agora está disponível a partir do config.php

if ($conexao->connect_error) {
    die('Erro de conexão: ' . $conexao->connect_error);
}

// --- INÍCIO DA LÓGICA DE LOG ---
function log_login_attempt($email, $status) {
    $log_file = __DIR__ . '/assets/debug/login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $log_message = "[$timestamp] - IP: $ip_address - Email: $email - Status: $status" . PHP_EOL;
    
    // Garante que o diretório de debug exista
    if (!is_dir(__DIR__ . '/assets/debug')) {
        mkdir(__DIR__ . '/assets/debug', 0777, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
// --- FIM DA LÓGICA DE LOG ---

$email = $_POST['email'] ?? '';
$senha = $_POST['password'] ?? '';

$stmt = $conexao->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $nome, $senha_hash);
    $stmt->fetch();

    if (password_verify($senha, $senha_hash)) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario_nome'] = $nome;
        
        log_login_attempt($email, 'SUCESSO'); // Log de sucesso

        $stmt->close();
        $conexao->close();
        
        header('Location: dash.php');
        exit;
    } else {
        log_login_attempt($email, 'FALHA - Senha incorreta'); // Log de falha
        $stmt->close();
        $conexao->close();
        
        header('Location: index.php?erro=login_invalido');
        exit;
    }
} else {
    log_login_attempt($email, 'FALHA - Email não encontrado'); // Log de falha
    $stmt->close();
    $conexao->close();
    
    header('Location: index.php?erro=login_invalido');
    exit;
}
?>