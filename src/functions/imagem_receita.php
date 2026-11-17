<?php
function handleImageUpload(&$erro, $campo = 'imagem_receita_blob', $limiteTamanho = null)
{
    $arquivo = $_FILES[$campo] ?? null;

    if (!$arquivo || !isset($arquivo['error'])) {
        $erro .= "O envio não incluiu um arquivo de imagem.";
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $errosUpload = [
            UPLOAD_ERR_INI_SIZE => "A imagem excede o limite definido pelo PHP (upload_max_filesize).",
            UPLOAD_ERR_FORM_SIZE => "A imagem excede o limite permitido pelo formulário.",
            UPLOAD_ERR_PARTIAL => "O envio da imagem foi interrompido antes de terminar. Tente novamente.",
            UPLOAD_ERR_NO_FILE => "Nenhuma imagem foi selecionada.",
            UPLOAD_ERR_NO_TMP_DIR => "O servidor não tem uma pasta temporária configurada para uploads.",
            UPLOAD_ERR_CANT_WRITE => "O servidor não conseguiu gravar o arquivo enviado.",
            UPLOAD_ERR_EXTENSION => "Uma configuração do PHP impediu o envio da imagem.",
        ];
        $erro .= $errosUpload[$arquivo['error']] ?? "Não foi possível receber a imagem (código de erro {$arquivo['error']}).";
        return null;
    }

    if ($limiteTamanho !== null && $arquivo['size'] > $limiteTamanho) {
        $erro .= "A imagem excede o limite permitido.";
        return null;
    }

    $informacoes = getimagesize($arquivo['tmp_name']);
    $tipo = $informacoes['mime'] ?? '';
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!$informacoes || !in_array($tipo, $tiposPermitidos, true)) {
        $erro .= "Formato de imagem inválido. Use JPG, PNG, GIF ou WebP.";
        return null;
    }

    $conteudo = file_get_contents($arquivo['tmp_name']);
    if ($conteudo === false) {
        $erro .= "Não foi possível ler o arquivo da imagem.";
        return null;
    }

    return ['conteudo' => $conteudo, 'tipo' => $tipo];
}
