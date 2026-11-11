
<?php

session_start();
require_once 'database.php';

// Verificar se foi fornecido um ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('Prato não encontrado.');
}

// Consultar prato
$stmt = $pdo->prepare(
    "SELECT * FROM pratos WHERE id = :id"
);

$stmt->execute([':id' => $id]);
$prato = $stmt->fetch();

if (!$prato) {
    exit('Este prato não existe.');
}

// Criar token de segurança
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Prato | TastyFood</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 px-4 py-8">

<section class="flex justify-center items-center">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg p-7 md:p-8">

        <!-- Cabeçalho -->
        <div class="text-center mb-6">

            <h1 class="text-3xl font-extrabold text-red-600">
                TastyFood
            </h1>

            <h2 class="text-2xl font-bold text-gray-800 mt-2">
                Editar Prato
            </h2>

            <p class="text-gray-500 text-sm mt-1">
                Atualize os detalhes do prato.
            </p>

        </div>

        <form action="atualizar_prato.php"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4">

            <input type="hidden" name="id"
                   value="<?= (int)$prato['id'] ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <!-- Nome -->
            <div>

                <label for="nome"
                       class="block text-sm font-semibold text-gray-700 mb-2">
                    Nome do prato
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= e($prato['nome']) ?>"
                    maxlength="150"
                    required
                    class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500"
                >

            </div>

            <!-- Descrição -->
            <div>

                <label for="descricao"
                       class="block text-sm font-semibold text-gray-700 mb-2">
                    Descrição
                </label>

                <textarea
                    id="descricao"
                    name="descricao"
                    rows="3"
                    maxlength="5000"
                    required
                    class="w-full p-4 border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500 resize-none"
                ><?= e($prato['descricao']) ?></textarea>

            </div>

            <!-- Preço -->
            <div>

                <label for="preco"
                       class="block text-sm font-semibold text-gray-700 mb-2">
                    Preço do prato
                </label>

                <div class="relative">

                    <input
                        type="number"
                        id="preco"
                        name="preco"
                        value="<?= e($prato['preco']) ?>"
                        min="1"
                        step="1"
                        required
                        class="w-full h-12 px-4 pr-16 border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500"
                    >

                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">
                        Kz
                    </span>

                </div>

            </div>

            <!-- Imagem atual -->
            <div>

                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Imagem atual
                </label>

                <img
                    src="<?= e($prato['imagem']) ?>"
                    alt="Imagem atual do prato"
                    class="w-full h-48 object-cover rounded-xl border"
                >

            </div>

            <!-- Nova imagem -->
            <div>

                <label for="imagem"
                       class="block text-sm font-semibold text-gray-700 mb-2">
                    Substituir imagem (opcional)
                </label>

                <input
                    type="file"
                    id="imagem"
                    name="imagem"
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full p-3 text-sm border border-gray-300 rounded-xl bg-gray-50"
                >

                <p class="text-xs text-gray-500 mt-2">
                    Deixe vazio para manter a imagem atual. Máximo: 5 MB.
                </p>

            </div>

            <!-- Estado -->
            <div>

                <label for="estado"
                       class="block text-sm font-semibold text-gray-700 mb-2">
                    Estado da publicação
                </label>

                <select
                    name="estado"
                    id="estado"
                    required
                    class="w-full h-12 px-4 border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500"
                >

                    <option value="publicado"
                        <?= $prato['estado'] === 'publicado' ? 'selected' : '' ?>>
                        Publicado
                    </option>

                    <option value="rascunho"
                        <?= $prato['estado'] === 'rascunho' ? 'selected' : '' ?>>
                        Rascunho
                    </option>

                    <option value="arquivado"
                        <?= $prato['estado'] === 'arquivado' ? 'selected' : '' ?>>
                        Arquivado
                    </option>

                </select>

            </div>

            <!-- Botões -->
            <div class="flex gap-3 pt-3">

                <button
                    type="submit"
                    class="flex-1 h-12 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-md"
                >
                    Guardar Alterações
                </button>

                <a
                    href="index.php"
                    class="px-6 h-12 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition"
                >
                    Voltar
                </a>

            </div>

        </form>

    </div>

</section>

</body>
</html>
