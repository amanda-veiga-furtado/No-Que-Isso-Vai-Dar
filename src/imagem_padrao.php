<?php
require_once __DIR__ . '/conexao.php';

if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    exit;
}

try {
    $consulta = $conn->prepare(
        'SELECT blob_imagem, mime_imagem
         FROM imagem_adicional
         WHERE imagem_id = :imagem_id
         LIMIT 1'
    );
    $consulta->bindValue(':imagem_id', 1, PDO::PARAM_INT);
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

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$tipo = $imagem['mime_imagem'] ?? '';
if (!in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');
echo $imagem['blob_imagem'];
