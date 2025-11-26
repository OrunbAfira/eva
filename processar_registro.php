<?php
require_once 'config.php';

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha = $_POST['password'] ?? '';
$confirmar_senha = $_POST['confirm_password'] ?? '';
$termos = isset($_POST['termos']) ? 'aceito' : '';

// Verifica se os termos foram aceitos
if ($termos !== 'aceito') {
    $params = http_build_query([
        'erro' => 'termos_nao_aceitos',
        'nome' => $nome,
        'email' => $email
    ]);
    header('Location: register.php?' . $params);
    exit;
}

// Verifica se as senhas coincidem
if ($senha !== $confirmar_senha) {
    $params = http_build_query([
        'erro' => 'senhas_nao_coincidem',
        'nome' => $nome,
        'email' => $email
    ]);
    header('Location: register.php?' . $params);
    exit;
}

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
$data_aceite = date('Y-m-d H:i:s'); // Pega a data e hora atuais

$stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, senha, termos_aceitos_em) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nome, $email, $senha_hash, $data_aceite);

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