
<?php

// ============================================
// TASTYFOOD - PÁGINA INICIAL
// ============================================

require_once 'database.php';

$pratos = [];
$erroBD = false;
$mensagem = '';
$tipoMensagem = '';

// ============================================
// FUNÇÃO DE PROTECÇÃO
// ============================================

function limpar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// ============================================
// CAMINHO DA IMAGEM
// ============================================

function caminhoImagem($imagem) {

    $imagem = trim((string)$imagem);

    if ($imagem === '') {
        return '';
    }

    if (filter_var($imagem, FILTER_VALIDATE_URL)) {
        return $imagem;
    }

    if (strpos($imagem, 'uploads/') === 0) {
        return $imagem;
    }

    return basename($imagem);
}

// ============================================
// PROCESSAR ACTUALIZAÇÃO DE UM PRATO
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['acao'])
    && $_POST['acao'] === 'editar_prato') {

    try {

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = trim($_POST['preco'] ?? '');

        if (!$id || $id <= 0) {
            throw new Exception('ID do prato inválido.');
        }

        if ($nome === '' || $descricao === '' || $preco === '') {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

        if (!is_numeric($preco) || (float)$preco <= 0) {
            throw new Exception('Introduza um preço válido.');
        }

        // Procurar prato existente
        $stmt = $pdo->prepare(
            "SELECT imagem FROM pratos WHERE id = :id LIMIT 1"
        );

        $stmt->execute(['id' => $id]);

        $pratoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pratoExistente) {
            throw new Exception('Prato não encontrado.');
        }

        // Manter imagem anterior
        $imagem = $pratoExistente['imagem'];

        // Verificar nova imagem
        if (
            isset($_FILES['imagem']) &&
            $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro ao enviar a imagem.');
            }

            if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
                throw new Exception('A imagem não pode ultrapassar 5 MB.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $tipo = $finfo->file($_FILES['imagem']['tmp_name']);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($tiposPermitidos[$tipo])) {
                throw new Exception('Formato de imagem não permitido.');
            }

            $pasta = __DIR__ . '/uploads/';

            if (!is_dir($pasta)) {
                if (!mkdir($pasta, 0755, true)) {
                    throw new Exception('Não foi possível criar a pasta uploads.');
                }
            }

            $nomeImagem = bin2hex(random_bytes(16)) . '.' . $tiposPermitidos[$tipo];

            $destino = $pasta . $nomeImagem;

            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                throw new Exception('Não foi possível guardar a nova imagem.');
            }

            $imagem = 'uploads/' . $nomeImagem;
        }

        // Actualizar o prato existente
        $sql = "UPDATE pratos
                SET nome = :nome,
                    descricao = :descricao,
                    preco = :preco,
                    imagem = :imagem
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao,
            'preco' => $preco,
            'imagem' => $imagem,
            'id' => $id
        ]);

        header('Location: index.php?sucesso=editado#pratos');
        exit;

    } catch (Throwable $e) {

        error_log('Erro ao editar prato: ' . $e->getMessage());

        $mensagem = $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// ============================================
// MENSAGEM DE SUCESSO
// ============================================

if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'editado') {
    $mensagem = 'Prato actualizado com sucesso!';
    $tipoMensagem = 'sucesso';
}

// ============================================
// BUSCAR EXCLUSIVAMENTE PRATOS PUBLICADOS
// ============================================

try {

    $sql = "SELECT id, nome, descricao, preco, imagem, data_publicacao
            FROM pratos
            WHERE estado = :estado
            ORDER BY data_publicacao DESC, id DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute(['estado' => 'publicado']);

    $pratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $erroBD = true;

    error_log("Erro TastyFood: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="TastyFood - Descubra pratos deliciosos e aproveite o sabor em cada mordida.">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Goblin+One&display=swap"
          rel="stylesheet">

    <title>TastyFood | Sabores especiais</title>

    <style>

        html {
            scroll-behavior: smooth;
        }

        .marca {
            font-family: 'Goblin One', cursive;
        }

        .slide {
            transition: opacity 1s ease-in-out;
        }

        .card-prato img {
            transition: transform 0.5s ease;
        }

        .card-prato:hover img {
            transform: scale(1.07);
        }

    </style>

</head>

<body class="bg-white">

<!-- ==========================================
     NAVBAR
=========================================== -->

<header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">

    <nav class="max-w-7xl mx-auto px-4 sm:px-6 py-4
                flex flex-wrap items-center justify-between">

        <a href="index.php"
           class="marca text-2xl text-red-600 font-bold">

            Tasty<span class="text-yellow-500">Food</span>

        </a>

        <div class="flex flex-wrap items-center gap-4 sm:gap-6 mt-3 sm:mt-0">

            <a href="#inicio"
               class="text-sm font-semibold text-red-600 hover:text-yellow-500 transition">

                <i class="fi fi-br-home"></i>
                Inicio

            </a>

            <a href="#pratos"
               class="text-sm font-medium text-gray-700 hover:text-red-600 transition">

                <i class="fi fi-br-restaurant"></i>
                Pratos

            </a>

            <a href="#contacto"
               class="text-sm font-medium text-gray-700 hover:text-red-600 transition">

                <i class="fi fi-br-envelope"></i>
                Contacto

            </a>

            <a href="#sobre"
               class="text-sm font-medium text-gray-700 hover:text-red-600 transition">

                <i class="fi fi-br-info"></i>
                Sobre

            </a>

            <a href="publicar_prato.php"
               class="inline-flex items-center gap-2 bg-red-600
                      hover:bg-red-700 text-white text-sm font-bold
                      px-5 py-3 rounded-full shadow-md transition">

                <i class="fi fi-br-plus"></i>
                Publicar Prato

            </a>

        </div>

    </nav>

</header>


<!-- ==========================================
     SLIDER TASTYFOOD
=========================================== -->

<section id="inicio" class="relative w-full overflow-hidden">

    <div id="slider" class="relative w-full h-[450px] md:h-[600px]">

        <!-- SLIDE 1 -->

        <div class="slide absolute inset-0 opacity-100">

            <img src="assets/imagens/slider1.PNG"
                 class="w-full h-full object-cover"
                 alt="Massas cremosas TastyFood">

            <div class="absolute inset-0 bg-black/50"></div>

            <div class="absolute inset-0 flex items-center px-8 md:px-20">

                <div class="max-w-2xl text-white">

                    <span class="text-yellow-400 font-bold uppercase tracking-widest">

                        Bem-vindo à TastyFood

                    </span>

                    <h1 class="text-4xl md:text-6xl font-extrabold mt-4 leading-tight">

                        O verdadeiro sabor

                        <span class="text-yellow-400">
                            das melhores massas!
                        </span>

                    </h1>

                    <p class="text-xl md:text-2xl font-semibold mt-5">

                        Aproveite o sabor em cada mordida!

                    </p>

                    <p class="text-gray-200 text-base md:text-lg mt-3">

                        Descubra pratos deliciosos, preparados com ingredientes
                        selecionados e muito carinho.

                    </p>

                    <a href="#pratos"
                       class="inline-flex items-center gap-3 mt-8
                              bg-yellow-500 hover:bg-red-600 text-gray-900
                              hover:text-white font-bold px-8 py-4 rounded-full
                              shadow-lg transition duration-300">

                        Ver pratos disponíveis
                        <span class="text-xl">→</span>

                    </a>

                </div>

            </div>

        </div>


        <!-- SLIDE 2 -->

        <div class="slide absolute inset-0 opacity-0">

            <img src="assets/imagens/slider2.PNG"
                 class="w-full h-full object-cover"
                 alt="Lasanha especial">

            <div class="absolute inset-0 bg-black/50"></div>

            <div class="absolute inset-0 flex items-center px-8 md:px-20">

                <div class="max-w-xl text-white">

                    <span class="text-yellow-400 font-bold uppercase tracking-widest">

                        Receita especial

                    </span>

                    <h2 class="text-4xl md:text-6xl font-extrabold mt-4 leading-tight">

                        Lasanha

                        <span class="text-yellow-400">
                            Especial
                        </span>

                    </h2>

                    <p class="text-lg md:text-xl mt-5">

                        Camadas de sabor, queijo derretido e uma experiência
                        gastronómica inesquecível.

                    </p>

                    <a href="#pratos"
                       class="inline-flex items-center gap-3 mt-8
                              bg-red-600 hover:bg-yellow-500 text-white
                              hover:text-gray-900 font-bold px-8 py-4 rounded-full
                              shadow-lg transition duration-300">

                        Experimenta já!
                        <span class="text-xl">→</span>

                    </a>

                </div>

            </div>

        </div>


        <!-- SLIDE 3 -->

        <div class="slide absolute inset-0 opacity-0">

            <img src="assets/imagens/slider3.PNG"
                 class="w-full h-full object-cover"
                 alt="Combos TastyFood">

            <div class="absolute inset-0 bg-black/50"></div>

            <div class="absolute inset-0 flex items-center px-8 md:px-20">

                <div class="max-w-xl text-white">

                    <span class="text-yellow-400 font-bold uppercase tracking-widest">

                        Ofertas especiais

                    </span>

                    <h2 class="text-4xl md:text-6xl font-extrabold mt-4 leading-tight">

                        Combos

                        <span class="text-yellow-400">
                            TastyFood
                        </span>

                    </h2>

                    <p class="text-lg md:text-xl mt-5">

                        Uma refeição completa, muito sabor e vantagens
                        especiais para ti.

                    </p>

                    <a href="#pratos"
                       class="inline-flex items-center gap-3 mt-8
                              bg-yellow-500 hover:bg-red-600 text-gray-900
                              hover:text-white font-bold px-8 py-4 rounded-full
                              shadow-lg transition duration-300">

                        Descobre os combos!
                        <span class="text-xl">→</span>

                    </a>

                </div>

            </div>

        </div>


        <!-- BOTÃO ANTERIOR -->

        <button id="prev"
                aria-label="Slide anterior"
                class="absolute left-4 top-1/2 -translate-y-1/2
                       bg-white/80 hover:bg-red-600 hover:text-white
                       w-12 h-12 rounded-full text-2xl shadow-lg
                       transition z-10">

            ❮

        </button>


        <!-- BOTÃO PRÓXIMO -->

        <button id="next"
                aria-label="Próximo slide"
                class="absolute right-4 top-1/2 -translate-y-1/2
                       bg-white/80 hover:bg-red-600 hover:text-white
                       w-12 h-12 rounded-full text-2xl shadow-lg
                       transition z-10">

            ❯

        </button>


        <!-- INDICADORES -->

        <div class="absolute bottom-7 left-0 right-0
                    flex justify-center gap-3 z-10">

            <button class="indicator w-8 h-3 rounded-full bg-yellow-400"
                    aria-label="Mostrar slide 1"></button>

            <button class="indicator w-3 h-3 rounded-full bg-white"
                    aria-label="Mostrar slide 2"></button>

            <button class="indicator w-3 h-3 rounded-full bg-white"
                    aria-label="Mostrar slide 3"></button>

        </div>

    </div>

</section>


<!-- ==========================================
     SECÇÃO DOS PRATOS
=========================================== -->

<section id="pratos" class="bg-gray-50 py-16 px-4 sm:px-6 lg:px-10">

    <div class="max-w-7xl mx-auto">

        <!-- CABEÇALHO -->

        <div class="text-center mb-12">

            <span class="text-red-600 font-bold uppercase tracking-widest text-sm">

                O nosso menu

            </span>

            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mt-3">

                Os nossos pratos

            </h2>

            <p class="text-gray-500 mt-3 max-w-xl mx-auto">

                Descubra os sabores especiais da TastyFood.
                Escolha o seu prato favorito!

            </p>

        </div>


        <!-- MENSAGENS -->

        <?php if ($mensagem): ?>

            <div class="max-w-2xl mx-auto mb-8 p-4 rounded-xl text-center
                <?= $tipoMensagem === 'sucesso'
                    ? 'bg-green-100 border border-green-300 text-green-700'
                    : 'bg-red-100 border border-red-300 text-red-700' ?>">

                <?= limpar($mensagem) ?>

            </div>

        <?php endif; ?>


        <?php if ($erroBD): ?>

            <!-- ERRO DE LIGAÇÃO -->

            <div class="max-w-2xl mx-auto bg-red-100 border border-red-300
                        text-red-700 p-6 rounded-xl text-center">

                <i class="fi fi-br-triangle-warning text-3xl"></i>

                <h3 class="font-bold text-lg mt-3">

                    Não foi possível carregar os pratos.

                </h3>

                <p class="text-sm mt-2">

                    Verifique a ligação à base de dados e tente novamente.

                </p>

            </div>


        <?php elseif (count($pratos) > 0): ?>


            <!-- GRELHA DINÂMICA DE PRATOS -->

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">


                <?php foreach ($pratos as $prato): ?>

                    <?php
                        $imagem = caminhoImagem($prato['imagem']);
                    ?>


                    <!-- CARD DINÂMICO -->

                    <article class="card-prato bg-white rounded-2xl
                                    overflow-hidden shadow-md
                                    hover:shadow-2xl transition duration-300">


                        <!-- IMAGEM -->

                        <div class="w-full h-60 overflow-hidden bg-gray-200">

                            <?php if (!empty($imagem)): ?>

                                <img src="<?= limpar($imagem) ?>"
                                     alt="<?= limpar($prato['nome']) ?>"
                                     class="w-full h-full object-cover"
                                     loading="lazy"
                                     onerror="this.style.display='none';">

                            <?php else: ?>

                                <div class="w-full h-full flex items-center
                                            justify-center text-gray-400">

                                    <i class="fi fi-br-restaurant text-5xl"></i>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- INFORMAÇÕES -->

                        <div class="p-6">

                            <h3 class="text-xl font-bold text-gray-800">

                                <?= limpar($prato['nome']) ?>

                            </h3>


                            <p class="text-gray-500 text-sm mt-3 leading-relaxed">

                                <?= nl2br(limpar($prato['descricao'])) ?>

                            </p>


                            <!-- PREÇO E ESTADO -->

                            <div class="flex items-center justify-between
                                        gap-3 mt-6 pt-4 border-t border-gray-100">

                                <div>

                                    <span class="block text-xs text-gray-400 uppercase">

                                        Preço

                                    </span>

                                    <span class="text-xl font-extrabold text-red-600">

                                        <?= number_format((float)$prato['preco'], 0, ',', '.') ?>

                                        Kz

                                    </span>

                                </div>


                                <a href="#contacto"
                                   class="bg-red-600 hover:bg-red-700
                                          text-white px-5 py-3 rounded-lg
                                          font-semibold text-sm transition">

                                    Encomendar

                                </a>

                            </div>


                            <!-- BOTÃO EDITAR -->

                            <div class="mt-4">

                                <button type="button"
                                        onclick="abrirEdicao(<?= (int)$prato['id'] ?>)"
                                        class="w-full bg-yellow-500 hover:bg-yellow-600
                                               text-gray-900 px-5 py-3 rounded-lg
                                               font-semibold text-sm transition">

                                    <i class="fi fi-br-edit mr-1"></i>

                                    Editar prato

                                </button>

                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- MENSAGEM QUANDO NÃO EXISTEM PRATOS -->

            <div class="max-w-2xl mx-auto text-center
                        bg-white border border-gray-200
                        rounded-2xl p-10 shadow-sm">

                <div class="w-20 h-20 mx-auto rounded-full
                            bg-red-50 flex items-center
                            justify-center">

                    <i class="fi fi-br-restaurant text-4xl text-red-500"></i>

                </div>


                <h3 class="text-2xl font-bold text-gray-800 mt-6">

                    Ainda não existem pratos disponíveis.

                </h3>


                <p class="text-gray-500 mt-3 leading-relaxed">

                    Seja o primeiro a publicar um prato na TastyFood!
                    Os pratos publicados aparecerão automaticamente nesta página.

                </p>


                <a href="publicar_prato.php"
                   class="inline-flex items-center gap-2
                          mt-7 bg-red-600 hover:bg-red-700
                          text-white font-bold px-7 py-3
                          rounded-full transition">

                    <i class="fi fi-br-plus"></i>

                    Publicar primeiro prato

                </a>

            </div>


        <?php endif; ?>


    </div>

</section>


<!-- ==========================================
     FORMULÁRIO DE EDIÇÃO
=========================================== -->

<div id="modalEdicao"
     class="fixed inset-0 z-[100] hidden bg-black/60
            flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl
                max-h-[90vh] overflow-y-auto">

        <div class="bg-red-600 text-white p-6 flex justify-between items-center">

            <div>

                <h2 class="text-xl font-bold">

                    <i class="fi fi-br-edit mr-2"></i>
                    Editar prato

                </h2>

                <p class="text-red-100 text-sm mt-1">

                    Actualize os detalhes do prato.

                </p>

            </div>

            <button type="button"
                    onclick="fecharEdicao()"
                    class="text-2xl hover:text-yellow-300"
                    aria-label="Fechar">

                &times;

            </button>

        </div>


        <form method="POST"
              enctype="multipart/form-data"
              class="p-6 space-y-5">

            <input type="hidden" name="acao" value="editar_prato">

            <input type="hidden" name="id" id="editar_id">


            <div>

                <label class="block font-semibold text-gray-700 mb-2">

                    Nome do prato

                </label>

                <input type="text"
                       name="nome"
                       id="editar_nome"
                       required
                       maxlength="150"
                       class="w-full border border-gray-300 rounded-xl p-4
                              outline-none focus:ring-2 focus:ring-red-500">

            </div>


            <div>

                <label class="block font-semibold text-gray-700 mb-2">

                    Descrição

                </label>

                <textarea name="descricao"
                          id="editar_descricao"
                          rows="4"
                          required
                          class="w-full border border-gray-300 rounded-xl p-4
                                 outline-none focus:ring-2 focus:ring-red-500"></textarea>

            </div>


            <div>

                <label class="block font-semibold text-gray-700 mb-2">

                    Preço em Kwanzas (Kz)

                </label>

                <input type="number"
                       name="preco"
                       id="editar_preco"
                       min="0.01"
                       step="0.01"
                       required
                       class="w-full border border-gray-300 rounded-xl p-4
                              outline-none focus:ring-2 focus:ring-red-500">

            </div>


            <div>

                <label class="block font-semibold text-gray-700 mb-2">

                    Imagem actual

                </label>

                <img id="editar_preview"
                     src=""
                     alt="Imagem do prato"
                     class="hidden w-full h-48 object-cover rounded-xl mb-4">

                <label class="block font-semibold text-gray-700 mb-2">

                    Escolher nova imagem (opcional)

                </label>

                <input type="file"
                       name="imagem"
                       id="editar_imagem"
                       accept="image/jpeg,image/png,image/webp"
                       class="w-full border border-gray-300 rounded-xl p-3">

                <p class="text-xs text-gray-500 mt-2">

                    JPG, PNG ou WEBP. Máximo 5 MB.
                    Se não escolher uma nova imagem, a actual será mantida.

                </p>

            </div>


            <div class="flex flex-col sm:flex-row gap-3 pt-3">

                <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700
                               text-white font-bold py-4 rounded-xl transition">

                    <i class="fi fi-br-check mr-2"></i>

                    Guardar alterações

                </button>


                <button type="button"
                        onclick="fecharEdicao()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300
                               text-gray-800 font-bold py-4 rounded-xl transition">

                    Cancelar

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================
     SOBRE A TASTYFOOD
=========================================== -->

<section id="sobre" class="py-16 px-4 bg-white">

    <div class="max-w-5xl mx-auto text-center">

        <span class="text-red-600 font-bold uppercase tracking-widest text-sm">

            Sobre nós

        </span>

        <h2 class="text-3xl font-extrabold text-gray-800 mt-3">

            Bem-vindo à TastyFood!

        </h2>

        <p class="text-gray-500 max-w-3xl mx-auto mt-5 leading-relaxed">

            A TastyFood é um espaço dedicado à descoberta de sabores.
            Aqui, os utilizadores podem publicar os seus pratos e partilhar
            as suas criações gastronómicas com todos os visitantes.
            O nosso objetivo é proporcionar uma experiência simples,
            agradável e saborosa.

        </p>

    </div>

</section>


<!-- ==========================================
     FOOTER TASTYFOOD
=========================================== -->

<footer id="contacto"
        class="w-full bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-7xl mx-auto text-center">


        <!-- MARCA -->

        <div>

            <a href="index.php"
               class="marca text-2xl font-bold text-white">

                Tasty<span class="text-red-500">Food</span>

            </a>

            <p class="mt-3 text-sm text-gray-400">

                Aproveite o sabor em cada mordida!

            </p>

        </div>


        <!-- CONTACTOS -->

        <div class="mt-7 space-y-4">

            <p class="text-gray-300">

                <i class="fi fi-br-envelope mr-2 text-red-400"></i>

                <a href="mailto:kazivovako91@gmail.com"
                   class="hover:text-red-400 transition">

                    kazivovako91@gmail.com

                </a>

            </p>


            <p class="text-gray-300">

                <i class="fi fi-br-phone-call mr-2 text-red-400"></i>

                <a href="tel:+244943926269"
                   class="hover:text-red-400 transition">

                    943 926 269

                </a>

            </p>

        </div>


        <!-- REDES SOCIAIS -->

        <div class="mt-7 flex justify-center gap-4">

            <a href="https://web.facebook.com/"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="Facebook"
               class="size-11 inline-flex justify-center items-center
                      rounded-full text-white bg-gray-800
                      hover:bg-blue-600 transition">

                <i class="fi fi-brands-facebook"></i>

            </a>


            <a href="mailto:kazivovako91@gmail.com"
               aria-label="Email"
               class="size-11 inline-flex justify-center items-center
                      rounded-full text-white bg-gray-800
                      hover:bg-red-600 transition">

                <i class="fi fi-br-envelope"></i>

            </a>


            <a href="tel:+244943926269"
               aria-label="Telefone"
               class="size-11 inline-flex justify-center items-center
                      rounded-full text-white bg-gray-800
                      hover:bg-green-600 transition">

                <i class="fi fi-br-phone-call"></i>

            </a>

        </div>


        <!-- DIREITOS AUTORAIS -->

        <div class="mt-9 border-t border-gray-700 pt-6">

            <p class="text-sm text-gray-400">

                © <?= date('Y') ?>

                <span class="font-semibold text-white">

                    TastyFood

                </span>.

                Todos os direitos reservados.

            </p>


            <p class="mt-2 text-xs text-gray-500">

                Tecnologia, criatividade e inovação.

            </p>

            <p class="mt-2 text-xs text-gray-500">

                Desenvolvido por Kazivovako Joaquim.

            </p>

        </div>


    </div>

</footer>


<!-- ==========================================
     JAVASCRIPT DO SLIDER E EDIÇÃO
=========================================== -->

<script>

    // ========================================
    // SLIDER
    // ========================================

    const slides = document.querySelectorAll(".slide");

    const indicators = document.querySelectorAll(".indicator");

    const prev = document.getElementById("prev");

    const next = document.getElementById("next");

    let currentSlide = 0;

    let interval;


    function showSlide(index) {

        slides.forEach((slide, i) => {

            slide.classList.toggle("opacity-100", i === index);

            slide.classList.toggle("opacity-0", i !== index);

            indicators[i].classList.toggle("bg-yellow-400", i === index);

            indicators[i].classList.toggle("bg-white", i !== index);

            indicators[i].classList.toggle("w-8", i === index);

            indicators[i].classList.toggle("w-3", i !== index);

        });

        currentSlide = index;

    }


    function nextSlide() {

        showSlide((currentSlide + 1) % slides.length);

    }


    function prevSlide() {

        showSlide((currentSlide - 1 + slides.length) % slides.length);

    }


    function resetAutoPlay() {

        clearInterval(interval);

        interval = setInterval(nextSlide, 5000);

    }


    next.addEventListener("click", () => {

        nextSlide();

        resetAutoPlay();

    });


    prev.addEventListener("click", () => {

        prevSlide();

        resetAutoPlay();

    });


    indicators.forEach((indicator, index) => {

        indicator.addEventListener("click", () => {

            showSlide(index);

            resetAutoPlay();

        });

    });


    resetAutoPlay();


    // ========================================
    // DADOS DOS PRATOS PARA EDIÇÃO
    // ========================================

    const pratos = <?= json_encode(
        array_map(function($p) {
            return [
                'id' => (int)$p['id'],
                'nome' => $p['nome'],
                'descricao' => $p['descricao'],
                'preco' => $p['preco'],
                'imagem' => caminhoImagem($p['imagem'])
            ];
        }, $pratos),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;


    // ========================================
    // ABRIR FORMULÁRIO DE EDIÇÃO
    // ========================================

    function abrirEdicao(id) {

        const prato = pratos.find(p => p.id === id);

        if (!prato) {

            alert("Não foi possível encontrar este prato.");

            return;

        }

        document.getElementById("editar_id").value = prato.id;

        document.getElementById("editar_nome").value = prato.nome;

        document.getElementById("editar_descricao").value = prato.descricao;

        document.getElementById("editar_preco").value = prato.preco;

        document.getElementById("editar_imagem").value = "";


        const preview = document.getElementById("editar_preview");

        if (prato.imagem) {

            preview.src = prato.imagem;

            preview.classList.remove("hidden");

        } else {

            preview.src = "";

            preview.classList.add("hidden");

        }


        document.getElementById("modalEdicao").classList.remove("hidden");

        document.body.style.overflow = "hidden";

    }


    // ========================================
    // FECHAR FORMULÁRIO
    // ========================================

    function fecharEdicao() {

        document.getElementById("modalEdicao").classList.add("hidden");

        document.body.style.overflow = "";

    }


    // Fechar ao clicar fora do formulário

    document.getElementById("modalEdicao").addEventListener("click", function(e) {

        if (e.target === this) {

            fecharEdicao();

        }

    });


    // Fechar com ESC

    document.addEventListener("keydown", function(e) {

        if (e.key === "Escape") {

            fecharEdicao();

        }

    });


    // ========================================
    // PRÉ-VISUALIZAR NOVA IMAGEM
    // ========================================

    document.getElementById("editar_imagem").addEventListener("change", function() {

        const ficheiro = this.files[0];

        if (!ficheiro) {
            return;
        }

        const preview = document.getElementById("editar_preview");

        preview.src = URL.createObjectURL(ficheiro);

        preview.classList.remove("hidden");

    });

</script>


</body>

</html>
