<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['POST']);

try {
    $pdo = (new Database())->connect();
    exigirUsuario($pdo, true);

    $dados = json_decode(file_get_contents('php://input'), true);
    $statusPermitidos = ['pendente', 'aprovada', 'rejeitada', 'concluida'];

    if (!is_array($dados) || empty($dados['id']) || !in_array($dados['status'] ?? '', $statusPermitidos, true)) {
        responderJson(['success' => false, 'message' => 'Pedido ou situação inválidos'], 400);
    }

    $pdo->beginTransaction();
    $busca = $pdo->prepare('SELECT pet_id FROM adocoes WHERE id = :id FOR UPDATE');
    $busca->execute([':id' => (int) $dados['id']]);
    $adocao = $busca->fetch(PDO::FETCH_ASSOC);

    if (!$adocao) {
        $pdo->rollBack();
        responderJson(['success' => false, 'message' => 'Pedido de adoção não encontrado'], 404);
    }

    $atualiza = $pdo->prepare(
        'UPDATE adocoes SET status = :status, data_resposta = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $atualiza->execute([':status' => $dados['status'], ':id' => (int) $dados['id']]);

    if ($dados['status'] === 'aprovada') {
        $pet = $pdo->prepare("UPDATE animais SET status = 'adotado' WHERE id = :id");
        $pet->execute([':id' => (int) $adocao['pet_id']]);
    }

    $pdo->commit();
    responderJson(['success' => true, 'message' => 'Situação da adoção atualizada']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('adocoes/status.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível atualizar o pedido'], 500);
}

