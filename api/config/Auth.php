<?php

require_once __DIR__ . '/JwtHelper.php';

function responderJson(array $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function prepararApi(array $metodos): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: ' . implode(', ', $metodos) . ', OPTIONS');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], $metodos, true)) {
        responderJson(['success' => false, 'message' => 'Método não permitido'], 405);
    }
}

function obterTokenBearer(): ?string
{
    $cabecalho = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if ($cabecalho === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $cabecalho = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/Bearer\s+(\S+)/i', trim($cabecalho), $matches)) {
        return null;
    }

    return $matches[1];
}

function exigirUsuario(PDO $pdo, bool $somenteAdmin = false): array
{
    $token = obterTokenBearer();
    if (!$token) {
        responderJson(['success' => false, 'message' => 'Token não enviado'], 401);
    }

    $payload = JwtHelper::decode($token);
    $usuarioId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;

    if (!$payload || $usuarioId <= 0) {
        responderJson(['success' => false, 'message' => 'Token inválido ou expirado'], 401);
    }

    $stmt = $pdo->prepare(
        'SELECT id, nome, email, telefone, tipo, ativo
         FROM usuarios
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || (int) $usuario['ativo'] !== 1) {
        responderJson(['success' => false, 'message' => 'Usuário inválido ou desativado'], 403);
    }

    if ($somenteAdmin && $usuario['tipo'] !== 'admin') {
        responderJson(['success' => false, 'message' => 'Acesso permitido somente para administradores'], 403);
    }

    $usuario['id'] = (int) $usuario['id'];
    $usuario['ativo'] = (int) $usuario['ativo'];
    return $usuario;
}
