<?php
require_once __DIR__ . '/../conexao.php';

if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    exit;
}

$imagemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$imagemId || $imagemId < 1) {
    http_response_code(400);
    exit;
}

try {
    $consulta = $conn->prepare(
        'SELECT blob_imagem, mime_imagem
         FROM imagem_adicional
         WHERE imagem_id = :imagem_id
         LIMIT 1'
    );
    $consulta->bindValue(':imagem_id', $imagemId, PDO::PARAM_INT);
    $consulta->execute();
    $imagem = $consulta->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit;
}

if (!$imagem || empty($imagem['blob_imagem'])) {
    http_response_code(404);
    exit;
}

$informacoesImagem = getimagesizefromstring($imagem['blob_imagem']);
$tipoImagem = $informacoesImagem['mime'] ?? null;
$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($tipoImagem, $tiposPermitidos, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $tipoImagem);
header('X-Content-Type-Options: nosniff');
echo $imagem['blob_imagem'];
