<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['POST']);

try {
    $pdo = (new Database())->connect();
    exigirUsuario($pdo, true);

    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responderJson(['success' => false, 'message' => 'Dados inválidos'], 400);
    }

    $obrigatorios = ['id', 'nome', 'tipo', 'porte', 'idade', 'sexo', 'pelagem', 'cor'];
    foreach ($obrigatorios as $campo) {
        if (!isset($dados[$campo]) || trim((string) $dados[$campo]) === '') {
            responderJson(['success' => false, 'message' => "Campo obrigatório: {$campo}"], 400);
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE animais SET
            nome = :nome, tipo = :tipo, porte = :porte, idade = :idade,
            idade_texto = :idade_texto, sexo = :sexo, raca = :raca,
            pelagem = :pelagem, cor = :cor, descricao = :descricao,
            fotos = :fotos, castrado = :castrado, vacinado = :vacinado
         WHERE id = :id'
    );

    $stmt->execute([
        ':id' => (int) $dados['id'],
        ':nome' => trim($dados['nome']),
        ':tipo' => $dados['tipo'],
        ':porte' => $dados['porte'],
        ':idade' => $dados['idade'],
        ':idade_texto' => trim($dados['idade_texto'] ?? ''),
        ':sexo' => $dados['sexo'],
        ':raca' => trim($dados['raca'] ?? 'SRD'),
        ':pelagem' => $dados['pelagem'],
        ':cor' => trim($dados['cor']),
        ':descricao' => trim($dados['descricao'] ?? ''),
        ':fotos' => json_encode($dados['fotos'] ?? [], JSON_UNESCAPED_SLASHES),
        ':castrado' => !empty($dados['castrado']) ? 1 : 0,
        ':vacinado' => !empty($dados['vacinado']) ? 1 : 0,
    ]);

    responderJson(['success' => true, 'message' => 'Animal atualizado com sucesso']);
} catch (Throwable $e) {
    error_log('animais/editar.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível atualizar o animal'], 500);
}

