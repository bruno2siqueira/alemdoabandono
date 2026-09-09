<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';
prepararApi(['POST']);

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    $email = trim((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        responderJson(['success' => false, 'message' => 'E-mail ou senha inválidos'], 400);
    }

    $pdo = (new Database())->connect();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha, telefone, tipo, ativo FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario || (int) $usuario['ativo'] !== 1 || !password_verify($senha, $usuario['senha'])) {
        responderJson(['success' => false, 'message' => 'E-mail ou senha inválidos'], 401);
    }

    $token = JwtHelper::encode([
        'user_id' => (int) $usuario['id'],
        'tipo' => $usuario['tipo'],
        'iat' => time(),
        'exp' => time() + 86400,
    ]);
    unset($usuario['senha']);
    $usuario['id'] = (int) $usuario['id'];
    $usuario['ativo'] = (int) $usuario['ativo'];
    responderJson(['success' => true, 'message' => 'Login realizado com sucesso', 'token' => $token, 'usuario' => $usuario]);
} catch (Throwable $e) {
    error_log('auth/login.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível realizar o login'], 500);
}

