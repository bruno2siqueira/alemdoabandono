<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';
prepararApi(['POST']);

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    $nome = trim((string) ($dados['nome'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');
    $telefone = preg_replace('/\D+/', '', (string) ($dados['telefone'] ?? ''));
    if (strlen($nome) < 2 || strlen($nome) > 150) responderJson(['success' => false, 'message' => 'Informe um nome válido'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responderJson(['success' => false, 'message' => 'Informe um e-mail válido'], 400);
    if (strlen($senha) < 8) responderJson(['success' => false, 'message' => 'A senha deve possuir pelo menos 8 caracteres'], 400);

    $pdo = (new Database())->connect();
    $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $consulta->execute([':email' => $email]);
    if ($consulta->fetch()) responderJson(['success' => false, 'message' => 'Este e-mail já está cadastrado'], 409);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, tipo, ativo) VALUES (:nome, :email, :senha, :telefone, 'usuario', 1)");
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => password_hash($senha, PASSWORD_DEFAULT),
        ':telefone' => $telefone !== '' ? $telefone : null,
    ]);
    responderJson(['success' => true, 'message' => 'Cadastro realizado com sucesso'], 201);
} catch (Throwable $e) {
    error_log('auth/cadastro.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível realizar o cadastro'], 500);
}
