<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Auth.php';
prepararApi(['POST']);

try {
    $pdo = (new Database())->connect();
    $usuario = exigirUsuario($pdo);
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        responderJson(['success' => false, 'message' => 'Selecione uma imagem válida'], 400);
    }

    $arquivo = $_FILES['foto'];
    if ((int) $arquivo['size'] <= 0 || (int) $arquivo['size'] > 5 * 1024 * 1024) {
        responderJson(['success' => false, 'message' => 'A imagem deve possuir no máximo 5 MB'], 400);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensoes[$mime])) responderJson(['success' => false, 'message' => 'Formato permitido: JPG, PNG ou WebP'], 400);

    $dimensoes = @getimagesize($arquivo['tmp_name']);
    if (!$dimensoes || $dimensoes[0] <= 0 || $dimensoes[1] <= 0 || $dimensoes[0] * $dimensoes[1] > 40000000) {
        responderJson(['success' => false, 'message' => 'O arquivo não contém uma imagem válida'], 400);
    }

    $diretorio = __DIR__ . '/uploads';
    if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true)) throw new RuntimeException('Não foi possível criar o diretório de imagens');
    $nome = sprintf('pet_%d_%s.%s', $usuario['id'], bin2hex(random_bytes(16)), $extensoes[$mime]);
    if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . '/' . $nome)) throw new RuntimeException('Falha ao gravar a imagem');

    responderJson(['success' => true, 'message' => 'Imagem enviada com sucesso', 'url' => '/api/uploads/' . rawurlencode($nome)], 201);
} catch (Throwable $e) {
    error_log('upload.php: ' . $e->getMessage());
    responderJson(['success' => false, 'message' => 'Não foi possível enviar a imagem'], 500);
}

