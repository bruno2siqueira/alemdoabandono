<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['GET']);

function normalizarAnimal(array $animal): array
{
    $fotos = json_decode($animal['fotos'] ?? '[]', true);
    $animal['fotos'] = is_array($fotos) ? $fotos : [];
    $animal['id'] = (int) $animal['id'];
    $animal['ativo'] = (int) ($animal['ativo'] ?? 0);
    $animal['castrado'] = (int) ($animal['castrado'] ?? 0);
    $animal['vacinado'] = (int) ($animal['vacinado'] ?? 0);
    $animal['dono_id'] = isset($animal['dono_id']) ? (int) $animal['dono_id'] : null;
    return $animal;
}

try {
    $pdo = (new Database())->connect();

    $stmt = $pdo->query(
        "SELECT *
         FROM animais
         WHERE ativo = 1 AND status = 'disponivel'
         ORDER BY id DESC"
    );
    $animais = array_map('normalizarAnimal', $stmt->fetchAll(PDO::FETCH_ASSOC));

    $contagem = $pdo->query(
        "SELECT
            SUM(CASE WHEN ativo = 1 AND status = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis,
            SUM(CASE WHEN status = 'adotado' THEN 1 ELSE 0 END) AS adotados
         FROM animais"
    )->fetch(PDO::FETCH_ASSOC);

    responderJson([
        'success' => true,
        'data' => $animais,
        'animais' => $animais,
        'total' => (int) ($contagem['disponiveis'] ?? 0),
        'adotados' => (int) ($contagem['adotados'] ?? 0),
    ]);
} catch (Throwable $e) {
    error_log('animais/listar.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível carregar os animais'], 500);
}

