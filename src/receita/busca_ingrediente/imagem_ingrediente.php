<?php
$id_ingrediente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_ingrediente) {
    http_response_code(400);
    exit;
}

require_once __DIR__ . '/../../conexao.php';
if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    exit;
}

try {
    $consulta = $conn->prepare(
        'SELECT imagem_ingrediente_blob
         FROM ingrediente
         WHERE id_ingrediente = :id_ingrediente'
    );
    $consulta->bindValue(':id_ingrediente', $id_ingrediente, PDO::PARAM_INT);
    $consulta->execute();
    $ingrediente = $consulta->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit;
}

if (!$ingrediente) {
    http_response_code(404);
    exit;
}

$conteudo = $ingrediente['imagem_ingrediente_blob'];
if (is_resource($conteudo)) {
    $conteudo = stream_get_contents($conteudo);
}
if ($conteudo === false) {
    http_response_code(500);
    exit;
}

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if ($conteudo === null || $conteudo === '') {
    require __DIR__ . '/../../imagem_padrao.php';
    exit;
}

$tipo = null;
if (strncmp($conteudo, "\xFF\xD8\xFF", 3) === 0) {
    $tipo = 'image/jpeg';
} elseif (strncmp($conteudo, "\x89PNG\r\n\x1a\n", 8) === 0) {
    $tipo = 'image/png';
} elseif (strncmp($conteudo, 'GIF87a', 6) === 0 || strncmp($conteudo, 'GIF89a', 6) === 0) {
    $tipo = 'image/gif';
} elseif (strncmp($conteudo, 'RIFF', 4) === 0 && substr($conteudo, 8, 4) === 'WEBP') {
    $tipo = 'image/webp';
}

if ($tipo === null || !in_array($tipo, $tiposPermitidos, true)) {
    require __DIR__ . '/../../imagem_padrao.php';
    exit;
}

$informacoes = @getimagesizefromstring($conteudo);
if (!$informacoes || $informacoes['mime'] !== $tipo) {
    error_log('BLOB de imagem inválido para o ingrediente ' . $id_ingrediente);
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $conteudo;
