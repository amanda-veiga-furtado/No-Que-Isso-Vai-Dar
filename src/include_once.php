<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();

require_once __DIR__ . '/functions/validacao_campos_texto.php';

include_once 'conexao.php';
include_once 'css/frontend.php';
include_once 'menu.php';
?>
<script src="/TCC/src/functions/validacao_campos_texto.js"></script>
