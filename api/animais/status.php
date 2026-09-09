<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['POST']);

try {
    $pdo = (new Database())->connect();
    exigirUsuario($pdo, true);

    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados) || !isset($dados['id'], $dados['ativo'])) {
        responderJson(['success' => false, 'message' => 'ID e situação são obrigatórios'], 400);
    }

    $stmt = $pdo->prepare('UPDATE animais SET ativo = :ativo WHERE id = :id');
    $stmt->execute([
        ':id' => (int) $dados['id'],
        ':ativo' => (int) ((bool) $dados['ativo']),
    ]);

    responderJson(['success' => true, 'message' => 'Visibilidade atualizada']);
} catch (Throwable $e) {
    error_log('animais/status.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível alterar a visibilidade'], 500);
}

