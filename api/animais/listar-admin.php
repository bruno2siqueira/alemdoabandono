<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['GET']);

try {
    $pdo = (new Database())->connect();
    exigirUsuario($pdo, true);

    $stmt = $pdo->query('SELECT * FROM animais ORDER BY id DESC');
    $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($animais as &$animal) {
        $fotos = json_decode($animal['fotos'] ?? '[]', true);
        $animal['fotos'] = is_array($fotos) ? $fotos : [];
        $animal['id'] = (int) $animal['id'];
        $animal['ativo'] = (int) ($animal['ativo'] ?? 0);
        $animal['castrado'] = (int) ($animal['castrado'] ?? 0);
        $animal['vacinado'] = (int) ($animal['vacinado'] ?? 0);
        $animal['dono_id'] = isset($animal['dono_id']) ? (int) $animal['dono_id'] : null;
    }
    unset($animal);

    responderJson(['success' => true, 'data' => $animais, 'animais' => $animais]);
} catch (Throwable $e) {
    error_log('animais/listar-admin.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível carregar os animais'], 500);
}

