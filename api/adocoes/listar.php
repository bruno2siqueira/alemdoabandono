<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['GET']);

try {
    $pdo = (new Database())->connect();
    exigirUsuario($pdo, true);

    $stmt = $pdo->query(
        'SELECT a.id, a.pet_id, a.adotante_id, a.doador_id, a.status,
            a.mensagem, a.data_pedido AS data_solicitacao, a.data_resposta,
            p.nome AS pet_nome, p.fotos AS pet_fotos,
            adotante.nome AS adotante_nome, adotante.email AS adotante_email,
            doador.nome AS doador_nome
         FROM adocoes a
         LEFT JOIN animais p ON p.id = a.pet_id
         LEFT JOIN usuarios adotante ON adotante.id = a.adotante_id
         LEFT JOIN usuarios doador ON doador.id = a.doador_id
         ORDER BY a.data_pedido DESC, a.id DESC'
    );

    $adocoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($adocoes as &$adocao) {
        $fotos = json_decode($adocao['pet_fotos'] ?? '[]', true);
        $adocao['pet_fotos'] = is_array($fotos) ? $fotos : [];
        $adocao['id'] = (int) $adocao['id'];
        $adocao['pet_id'] = (int) $adocao['pet_id'];
        $adocao['adotante_id'] = (int) $adocao['adotante_id'];
        $adocao['doador_id'] = (int) $adocao['doador_id'];
    }
    unset($adocao);

    responderJson(['success' => true, 'data' => $adocoes]);
} catch (Throwable $e) {
    error_log('adocoes/listar.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível carregar os pedidos'], 500);
}

