<?php
session_start();
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, private');
require_once __DIR__ . '/../../conexao.php';
$autoload = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

$mensagem = $_SESSION['mensagem_recuperacao_senha'] ?? '';
unset($_SESSION['mensagem_recuperacao_senha']);
$tokenInformado = $_GET['token'] ?? '';
$token = is_string($tokenInformado) ? trim($tokenInformado) : '';
$tokenValido = false;

if (isset($conn) && $conn instanceof PDO && preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
    try {
        $consulta = $conn->prepare(
            'SELECT id_usuario
             FROM recuperacao_senha
             WHERE token_hash = :token_hash AND expira_em > CURRENT_TIMESTAMP'
        );
        $consulta->execute([':token_hash' => hash('sha256', $token)]);
        $tokenValido = $consulta->fetchColumn() !== false;
    } catch (PDOException $erro) {
        error_log('Falha ao verificar link de recuperação: ' . $erro->getMessage());
        http_response_code(500);
        $mensagem = 'Não foi possível verificar o link no momento.';
    }
}

if ($token !== '' && !$tokenValido && $mensagem === '') {
    $mensagem = 'O link de recuperação é inválido ou expirou. Solicite um novo link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'solicitar') {
        $emailInformado = $_POST['email_usuario'] ?? '';
        $email = is_string($emailInformado) ? trim($emailInformado) : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = 'Informe um endereço de e-mail válido.';
        } elseif (!isset($conn) || !$conn instanceof PDO) {
            http_response_code(500);
            $mensagem = 'Não foi possível processar a solicitação no momento.';
            error_log('Falha na recuperação de senha: conexão com o banco indisponível.');
        } else {
            try {
                $consulta = $conn->prepare(
                    'SELECT id_usuario, email_usuario
                     FROM usuario
                     WHERE email_usuario = :email_usuario
                     LIMIT 1'
                );
                $consulta->execute([':email_usuario' => $email]);
                $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

                if ($usuario) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);

                    $salvarToken = $conn->prepare(
                        'INSERT INTO recuperacao_senha (id_usuario, token_hash, expira_em)
                         VALUES (:id_usuario, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR))
                         ON DUPLICATE KEY UPDATE
                             token_hash = VALUES(token_hash),
                             expira_em = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR),
                             criado_em = CURRENT_TIMESTAMP'
                    );
                    $salvarToken->execute([
                        ':id_usuario' => $usuario['id_usuario'],
                        ':token_hash' => $tokenHash,
                    ]);

                    $urlBase = getenv('TCC_BASE_URL') ?: 'http://localhost/TCC';
                    $link = rtrim($urlBase, '/') . '/src/usuario/recuperar_senha/recuperar_senha.php?token=' . urlencode($token);
                    $hostSmtp = trim((string) (getenv('TCC_SMTP_HOST') ?: ''));
                    $usuarioSmtp = trim((string) (getenv('TCC_SMTP_USER') ?: ''));
                    $senhaSmtp = getenv('TCC_SMTP_PASSWORD');
                    $portaSmtp = filter_var(
                        getenv('TCC_SMTP_PORT') ?: '587',
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1, 'max_range' => 65535]]
                    );
                    $criptografiaSmtp = strtolower(trim((string) (getenv('TCC_SMTP_ENCRYPTION') ?: 'tls')));
                    $remetente = trim((string) (getenv('TCC_EMAIL_FROM') ?: $usuarioSmtp));

                    $enviado = false;
                    $erroEnvio = '';
                    $criptografiasPermitidas = ['tls', 'ssl'];

                    if (
                        class_exists(\PHPMailer\PHPMailer\PHPMailer::class)
                        && $hostSmtp !== ''
                        && $usuarioSmtp !== ''
                        && is_string($senhaSmtp)
                        && $senhaSmtp !== ''
                        && $portaSmtp !== false
                        && filter_var($remetente, FILTER_VALIDATE_EMAIL)
                        && in_array($criptografiaSmtp, $criptografiasPermitidas, true)
                    ) {
                        $assunto = 'Recuperação de senha';
                        $corpo = "Recebemos uma solicitação para alterar a senha da sua conta.\n\n"
                            . "Acesse o link abaixo para criar uma nova senha. O link expira em 1 hora e só pode ser usado uma vez:\n\n"
                            . $link . "\n\n"
                            . "Se você não solicitou a alteração, ignore este e-mail.";

                        try {
                            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                            $mailer->isSMTP();
                            $mailer->Host = $hostSmtp;
                            $mailer->SMTPAuth = true;
                            $mailer->Username = $usuarioSmtp;
                            $mailer->Password = $senhaSmtp;
                            $mailer->Port = $portaSmtp;
                            $mailer->SMTPSecure = $criptografiaSmtp === 'ssl'
                                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                            $mailer->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
                            $mailer->setFrom($remetente, 'No Que Isso Vai Dar?');
                            $mailer->addAddress($usuario['email_usuario']);
                            $mailer->Subject = $assunto;
                            $mailer->Body = $corpo;
                            $mailer->isHTML(false);
                            $mailer->send();
                            $enviado = true;
                        } catch (\PHPMailer\PHPMailer\Exception $erroMailer) {
                            $erroEnvio = $erroMailer->getMessage();
                        }
                    } else {
                        $erroEnvio = 'Dependência PHPMailer ou configuração SMTP ausente ou inválida.';
                    }

                    if (!$enviado) {
                        $removerToken = $conn->prepare(
                            'DELETE FROM recuperacao_senha
                             WHERE id_usuario = :id_usuario AND token_hash = :token_hash'
                        );
                        $removerToken->execute([
                            ':id_usuario' => $usuario['id_usuario'],
                            ':token_hash' => $tokenHash,
                        ]);
                        error_log(
                            'Falha no envio do e-mail de recuperação de senha. '
                            . $erroEnvio
                        );
                    }
                }

                $mensagem = 'Se o e-mail estiver cadastrado e o serviço de envio estiver configurado, você receberá um link para redefinir sua senha.';
            } catch (PDOException $erro) {
                error_log('Falha na solicitação de recuperação de senha: ' . $erro->getMessage());
                http_response_code(500);
                $mensagem = 'Não foi possível processar a solicitação no momento.';
            }
        }
    } elseif ($acao === 'redefinir') {
        $senha = $_POST['senha_nova'] ?? '';
        $confirmacao = $_POST['confirmar_senha'] ?? '';
        $senha = is_string($senha) ? $senha : '';
        $confirmacao = is_string($confirmacao) ? $confirmacao : '';

        if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
            $mensagem = 'O link de recuperação é inválido ou expirou. Solicite um novo link.';
        } elseif (strlen($senha) < 8) {
            $mensagem = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($senha !== $confirmacao) {
            $mensagem = 'As senhas não coincidem.';
        } elseif (!isset($conn) || !$conn instanceof PDO) {
            http_response_code(500);
            $mensagem = 'Não foi possível alterar a senha no momento.';
            error_log('Falha na redefinição de senha: conexão com o banco indisponível.');
        } else {
            try {
                $conn->beginTransaction();

                $consulta = $conn->prepare(
                    'SELECT id_usuario
                     FROM recuperacao_senha
                     WHERE token_hash = :token_hash AND expira_em > CURRENT_TIMESTAMP
                     FOR UPDATE'
                );
                $consulta->execute([':token_hash' => hash('sha256', $token)]);
                $idUsuario = $consulta->fetchColumn();

                if ($idUsuario === false) {
                    $conn->rollBack();
                    $mensagem = 'O link de recuperação é inválido ou expirou. Solicite um novo link.';
                } else {
                    $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
                    $atualizarSenha = $conn->prepare(
                        'UPDATE usuario SET senha_usuario = :senha_usuario WHERE id_usuario = :id_usuario'
                    );
                    $atualizarSenha->execute([
                        ':senha_usuario' => $hashSenha,
                        ':id_usuario' => $idUsuario,
                    ]);

                    $removerToken = $conn->prepare(
                        'DELETE FROM recuperacao_senha WHERE id_usuario = :id_usuario'
                    );
                    $removerToken->execute([':id_usuario' => $idUsuario]);
                    $conn->commit();

                    $_SESSION['mensagem_recuperacao_senha'] = 'Senha alterada com sucesso. Você já pode entrar com a nova senha.';
                    header('Location: recuperar_senha.php');
                    exit();
                }
            } catch (PDOException $erro) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log('Falha na redefinição de senha: ' . $erro->getMessage());
                http_response_code(500);
                $mensagem = 'Não foi possível alterar a senha no momento.';
            }
        }
    }

}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 24px; }
        main { background: #fff; border-radius: 12px; margin: 40px auto; max-width: 460px; padding: 24px; }
        h1 { font-size: 1.5rem; }
        label { display: block; margin-top: 16px; }
        input { box-sizing: border-box; margin-top: 6px; padding: 10px; width: 100%; }
        button { cursor: pointer; margin-top: 20px; padding: 12px; width: 100%; }
        .mensagem { background: #f1f1f1; padding: 12px; }
    </style>
</head>
<body>
    <main>
        <h1>Recuperar senha</h1>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($tokenValido): ?>
            <form method="POST" action="recuperar_senha.php?token=<?php echo urlencode($token); ?>">
                <input type="hidden" name="acao" value="redefinir">
                <label for="senha_nova">Nova senha</label>
                <input type="password" id="senha_nova" name="senha_nova" minlength="8" autocomplete="new-password" required>
                <label for="confirmar_senha">Confirme a nova senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required>
                <button type="submit">Alterar senha</button>
            </form>
        <?php else: ?>
            <form method="POST" action="recuperar_senha.php">
                <input type="hidden" name="acao" value="solicitar">
                <label for="email_usuario">E-mail cadastrado</label>
                <input type="email" id="email_usuario" name="email_usuario" autocomplete="email" required>
                <button type="submit">Enviar link de recuperação</button>
            </form>
        <?php endif; ?>

        <p><a href="../login.php">Voltar ao login</a></p>
    </main>
</body>
</html>
