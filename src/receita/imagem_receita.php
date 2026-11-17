<?php
$id_receita = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_receita) {
    http_response_code(400);
    exit;
}

require_once __DIR__ . '/../conexao.php';
if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    exit;
}

try {
    $consulta = $conn->prepare(
        'SELECT imagem_receita_blob, imagem_receita_mime
         FROM receita
         WHERE id_receita = :id_receita'
    );
    $consulta->bindValue(':id_receita', $id_receita, PDO::PARAM_INT);
    $consulta->execute();
    $receita = $consulta->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit;
}

if (!$receita) {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store');

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$conteudo = $receita['imagem_receita_blob'];
if (is_resource($conteudo)) {
    $conteudo = stream_get_contents($conteudo);
}

if ($conteudo === false) {
    http_response_code(500);
    exit;
}

if ($conteudo === null || $conteudo === '') {
    require __DIR__ . '/../imagem_padrao.php';
    exit;
}

$tipo = $receita['imagem_receita_mime'];
if (!in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');
echo $conteudo;
