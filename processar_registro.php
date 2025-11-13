<?php
require_once 'config.php';

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha = $_POST['password'] ?? '';

if (empty($nome) || empty($email) || empty($senha)) {
    $params = http_build_query([
        'erro' => 'campos_vazios',
        'nome' => $nome,
        'email' => $email
    ]);
    header('Location: register.php?' . $params);
    exit;
}

$stmt_check = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$resultado = $stmt_check->get_result();

if ($resultado->num_rows > 0) {
    $stmt_check->close();
    $params = http_build_query([
        'erro' => 'email_existe',
        'nome' => $nome,
        'email' => $email
    ]);
    header('Location: register.php?' . $params);
    exit;
}

$stmt_check->close();

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nome, $email, $senha_hash);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: index.php?status=registrado');
    exit;
} else {
    $stmt->close();
    $params = http_build_query([
        'erro' => 'falha_registro',
        'nome' => $nome,
        'email' => $email
    ]);
    header('Location: register.php?' . $params);
    exit;
}
?>