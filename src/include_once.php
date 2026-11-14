<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

require_once __DIR__ . '/functions/validacao_campos_texto.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && validarCamposTexto($_POST) !== []) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Dados inválidos. Remova os caracteres especiais dos campos de texto.');
}

include_once 'conexao.php';
include_once 'css/frontend.php';
include_once 'menu.php';
?>
<script src="/TCC/src/functions/validacao_campos_texto.js"></script>
