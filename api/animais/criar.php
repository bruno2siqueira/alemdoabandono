<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Auth.php';

prepararApi(['POST']);

try {
    $pdo = (new Database())->connect();
    $usuario = exigirUsuario($pdo);

    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responderJson(['success' => false, 'message' => 'Dados inválidos'], 400);
    }

    $obrigatorios = ['nome', 'tipo', 'porte', 'idade', 'sexo', 'pelagem', 'cor'];
    foreach ($obrigatorios as $campo) {
        if (!isset($dados[$campo]) || trim((string) $dados[$campo]) === '') {
            responderJson(['success' => false, 'message' => "Campo obrigatório: {$campo}"], 400);
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO animais (
            nome, tipo, porte, idade, idade_texto, sexo, raca, pelagem, cor,
            descricao, fotos, castrado, vacinado, status, id_usuario,
            tutor_nome, ativo, dono_id
        ) VALUES (
            :nome, :tipo, :porte, :idade, :idade_texto, :sexo, :raca, :pelagem, :cor,
            :descricao, :fotos, :castrado, :vacinado, 'disponivel', :id_usuario,
            :tutor_nome, 1, :dono_id
        )"
    );

    $stmt->execute([
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
        ':id_usuario' => $usuario['id'],
        ':tutor_nome' => $usuario['nome'],
        ':dono_id' => $usuario['id'],
    ]);

    responderJson([
        'success' => true,
        'message' => 'Animal cadastrado com sucesso',
        'id' => (int) $pdo->lastInsertId(),
    ], 201);
} catch (Throwable $e) {
    error_log('animais/criar.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível cadastrar o animal'], 500);
}

