<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'eva';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}

$email = $_POST['email'] ?? '';
$senha = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
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
        $_SESSION['last_activity'] = time();
        
        $stmt->close();
        $conn->close();
        
        header('Location: dash.php');
        exit;
    } else {
        $stmt->close();
        $conn->close();
        
        header('Location: index.php?erro=1');
        exit;
    }
} else {
    $stmt->close();
    $conn->close();
    
    header('Location: index.php?erro=1');
    exit;
}
?>