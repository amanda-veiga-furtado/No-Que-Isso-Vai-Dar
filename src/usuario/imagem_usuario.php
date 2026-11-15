<?php
$id_usuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_usuario) {
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
        'SELECT imagem_usuario_blob, imagem_receita_mime, imagem_usuario
         FROM usuario
         WHERE id_usuario = :id_usuario'
    );
    $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $consulta->execute();
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit;
}

if (!$usuario) {
    http_response_code(404);
    exit;
}

header('Cache-Control: no-store');

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$conteudo = $usuario['imagem_usuario_blob'] ?? '';
if (is_resource($conteudo)) {
    $conteudo = stream_get_contents($conteudo);
}
if ($conteudo === false) {
    http_response_code(500);
    exit;
}

$prefixoAntigo = '../css/img/usuario/';
$caminhosAntigos = [$usuario['imagem_usuario'] ?? ''];
if (is_string($conteudo) && strpos($conteudo, $prefixoAntigo) === 0) {
    $caminhosAntigos[] = $conteudo;
} elseif ($conteudo !== '') {
    $tipo = $usuario['imagem_receita_mime'] ?? '';
    if (!in_array($tipo, $tiposPermitidos, true)) {
        http_response_code(415);
        exit;
    }

    header('Content-Type: ' . $tipo);
    header('X-Content-Type-Options: nosniff');
    echo $conteudo;
    exit;
}

$pastaImagens = realpath(__DIR__ . '/../css/img/usuario');
$arquivoAntigo = false;
if ($pastaImagens) {
    foreach ($caminhosAntigos as $caminhoAntigo) {
        $nomeArquivo = substr($caminhoAntigo, strlen($prefixoAntigo));
        if (
            strpos($caminhoAntigo, $prefixoAntigo) !== 0 ||
            $nomeArquivo === '' ||
            basename($nomeArquivo) !== $nomeArquivo
        ) {
            continue;
        }

        $candidatoAntigo = realpath(__DIR__ . '/' . $caminhoAntigo);
        if (
            $candidatoAntigo &&
            strpos($candidatoAntigo, $pastaImagens . DIRECTORY_SEPARATOR) === 0 &&
            is_file($candidatoAntigo)
        ) {
            $arquivoAntigo = $candidatoAntigo;
            break;
        }
    }
}

if (!$arquivoAntigo) {
    require __DIR__ . '/../imagem_padrao.php';
    exit;
}

$informacoes = getimagesize($arquivoAntigo);
$tipo = $informacoes['mime'] ?? '';
if (!$informacoes || !in_array($tipo, $tiposPermitidos, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $tipo);
header('X-Content-Type-Options: nosniff');
readfile($arquivoAntigo);
