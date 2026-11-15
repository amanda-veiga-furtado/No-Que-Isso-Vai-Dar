<?php
session_start();
ob_start();

include_once '../include_once.php';
require_once __DIR__ . '/../functions/imagem_receita.php';

if (!isset($_SESSION['id_usuario'])) {
    // header("Location: login.php");
    header("Location: http://localhost/TCC/src/usuario/login.php?mensagem=" . urlencode("Para prosseguir, é necessário estar logado."));
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$nome_usuario = $_SESSION['nome_usuario'];
$email_usuario = $_SESSION['email_usuario'];

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $novo_nome_usuario = trim($_POST['nome_usuario']);
    $limite_tamanho = 2 * 1024 * 1024; // 2MB
    $imagem = null;

    if (
        isset($_FILES['imagem_usuario_blob']) &&
        $_FILES['imagem_usuario_blob']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $imagem = handleImageUpload($mensagem, 'imagem_usuario_blob', $limite_tamanho);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE nome_usuario = :nome_usuario AND id_usuario != :id_usuario");
    $stmt->bindParam(':nome_usuario', $novo_nome_usuario);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
        $mensagem = "Desculpe, esse nome de usuário já está em uso. Tente um nome diferente";
    } elseif (empty($mensagem)) {
        $query_update = $imagem
            ? "UPDATE usuario SET nome_usuario = :nome_usuario, imagem_usuario_blob = :imagem_usuario_blob, imagem_receita_mime = :imagem_usuario_mime WHERE id_usuario = :id_usuario"
            : "UPDATE usuario SET nome_usuario = :nome_usuario WHERE id_usuario = :id_usuario";
        $stmt = $conn->prepare($query_update);
        $stmt->bindParam(':nome_usuario', $novo_nome_usuario);
        $stmt->bindParam(':id_usuario', $id_usuario);

        if ($imagem) {
            $stmt->bindValue(':imagem_usuario_blob', $imagem['conteudo'], PDO::PARAM_LOB);
            $stmt->bindValue(':imagem_usuario_mime', $imagem['tipo'], PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            $_SESSION['nome_usuario'] = $novo_nome_usuario;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

$src_imagem_usuario = 'imagem_usuario.php?id=' . (int) $id_usuario;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Dashboard</title>
    <meta charset="UTF-8">
</head>

<body>
    <div class="container_background_image_grow">
        <div class="container_whitecard_grow">
            <div class="container_form">
                <div class="form_switch">
                    <h1>Bem-vindo(a) <?php echo htmlspecialchars($nome_usuario); ?>!</h1><br>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="margin-right: 10px;">
                        <img src="<?php echo htmlspecialchars($src_imagem_usuario, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" style="width:150px;height:150px;border-radius: 50%;">
                    </div>

                    <div style="margin-top:5px;">
                        <?php if (!empty($mensagem)): ?>
                            <p class="error-message"><?php echo htmlspecialchars($mensagem); ?></p>
                        <?php endif; ?>

                        <!-- Form for profile update -->
                        <form method="POST" enctype="multipart/form-data">
                            <input type="text" name="nome_usuario" value="<?php echo htmlspecialchars($nome_usuario); ?>" required>
                            <input type="file" name="imagem_usuario_blob">
                    </div>
                </div>
            </div>
            <!-- <input type="submit" name="update_profile" value="Atualizar Perfil" class="button-long" style="margin-top:25px;"> -->



            <!-- <div class="container-button-long"> -->
            <input type="submit" name="update_profile" value="Atualizar Perfil" class="button-long" style="margin-top:25px;">
            <div class="div_link"><a href="sair.php">Deslogar</a></div>

            <!-- </div> -->


            </form>
        </div>
    </div>
    </div>

    <script>
        <?php if (!empty($mensagem)): ?>
            alert('<?php echo addslashes($mensagem); ?>');
        <?php endif; ?>

        // Recarrega a imagem do perfil automaticamente sem recarregar a página inteira
        document.addEventListener("DOMContentLoaded", function() {
            const imgElement = document.querySelector("img[alt='Foto de perfil']");
            if (imgElement) {
                const src = imgElement.src;
                const separador = src.indexOf("?") === -1 ? "?" : "&";
                imgElement.src = src + separador + "t=" + new Date().getTime();
            }
        });
    </script>


</body>

</html>