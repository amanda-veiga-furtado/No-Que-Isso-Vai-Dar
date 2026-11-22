<?php
require_once __DIR__ . '/../conexao.php';

if (!isset($conn) || !($conn instanceof PDO)) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

try {
    $queryImages = $conn->query(
        "SELECT imagem_id, imagem_descricao
         FROM imagem_adicional
         WHERE blob_imagem IS NOT NULL
           AND OCTET_LENGTH(blob_imagem) > 0
         ORDER BY RAND()
         LIMIT 5"
    );
    $selectedImages = $queryImages->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar as imagens do carrossel.');
}

$slides = [
    ['Explore Receitas', 'Descubra uma variedade de receitas deliciosas.', '/TCC/src/receita/listagem_receitas.php', 'Ver Receitas'],
    ['Crie Sua Receita', 'Compartilhe suas próprias receitas.', '/TCC/src/receita/cadastrar_receita.php', 'Cadastrar'],
    ['Busca por Ingredientes', 'Receitas com o que você tem em casa.', '/TCC/src/receita/busca_ingrediente/busca_ingrediente.php', 'Buscar'],
    ['Sugestões', 'Receitas pensadas para você.', '/TCC/src/receita/sugestao.php', 'Ver'],
    ['Faça Login', 'Gerencie suas receitas.', '/TCC/src/usuario/login.php', 'Login'],
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/carousel_frontend.css">
</head>

<body>

    <div id="carouselExampleIndicators" class="carousel" aria-label="Carrossel principal do site">

        <?php if (count($selectedImages) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($selectedImages as $i => $img): ?>
                    <button type="button" class="carousel-indicator <?= $i === 0 ? 'active' : '' ?>"
                        data-slide-to="<?= $i ?>" aria-label="Ir para o slide <?= $i + 1 ?>"
                        aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="carousel-inner">
            <?php foreach ($selectedImages as $index => $img): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>"
                    style="background-image: url('imagem_carousel.php?id=<?= (int) $img['imagem_id'] ?>');"
                    role="group" aria-label="<?= htmlspecialchars($img['imagem_descricao'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="carousel-caption text-center">
                        <h5><?= $slides[$index][0] ?></h5>
                        <p><?= $slides[$index][1] ?></p>
                        <a class="btn-ver-receitas" href="<?= $slides[$index][2] ?>" target="_top">
                            <?= $slides[$index][3] ?>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
            <?php if (empty($selectedImages)): ?>
                <div class="carousel-item active">
                    <div class="carousel-caption text-center">
                        <h5>Receitas para descobrir</h5>
                        <p>As imagens do carrossel ainda não estão disponíveis.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($selectedImages) > 1): ?>
            <button type="button" class="carousel-control-prev" data-slide="prev" aria-label="Slide anterior">
                <span aria-hidden="true">&#10094;</span>
            </button>
            <button type="button" class="carousel-control-next" data-slide="next" aria-label="Próximo slide">
                <span aria-hidden="true">&#10095;</span>
            </button>
        <?php endif; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('carouselExampleIndicators');
            const slides = Array.from(carousel.querySelectorAll('.carousel-item'));
            const indicators = Array.from(carousel.querySelectorAll('.carousel-indicator'));

            if (slides.length < 2) {
                return;
            }

            let currentIndex = 0;

            function showSlide(index) {
                slides[currentIndex].classList.remove('active');
                if (indicators[currentIndex]) {
                    indicators[currentIndex].classList.remove('active');
                    indicators[currentIndex].setAttribute('aria-current', 'false');
                }

                currentIndex = (index + slides.length) % slides.length;
                slides[currentIndex].classList.add('active');
                if (indicators[currentIndex]) {
                    indicators[currentIndex].classList.add('active');
                    indicators[currentIndex].setAttribute('aria-current', 'true');
                }
            }

            carousel.querySelector('[data-slide="prev"]').addEventListener('click', function() {
                showSlide(currentIndex - 1);
            });
            carousel.querySelector('[data-slide="next"]').addEventListener('click', function() {
                showSlide(currentIndex + 1);
            });
            indicators.forEach(function(indicator) {
                indicator.addEventListener('click', function() {
                    showSlide(Number(indicator.dataset.slideTo));
                });
            });

            window.setInterval(function() {
                showSlide(currentIndex + 1);
            }, 4000);
        });
    </script>

</body>

</html>