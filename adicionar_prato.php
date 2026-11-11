
<?php
require_once 'database.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Prato | TastyFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

<section class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-6">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-lg p-7 md:p-8">

        <div class="text-center mb-6">
            <h1 class="text-3xl font-extrabold text-red-600">TastyFood</h1>
            <h2 class="text-2xl font-bold text-gray-800 mt-2">
                Adicionar Prato
            </h2>
            <p class="text-gray-500 text-sm mt-1">
                Preencha os dados para adicionar um prato ao menu.
            </p>
        </div>

        <form action="publicar_prato.php"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4">

            <div>
                <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">
                    Nome do prato <span class="text-red-600">*</span>
                </label>
                <input type="text" id="nome" name="nome"
                    placeholder="Ex.: Lasanha Especial"
                    maxlength="150" required
                    class="w-full h-12 px-4 text-base border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
            </div>

            <div>
                <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">
                    Descrição do prato <span class="text-red-600">*</span>
                </label>
                <textarea id="descricao" name="descricao"
                    rows="3" maxlength="5000"
                    placeholder="Descreva os ingredientes e detalhes do prato..."
                    required
                    class="w-full p-4 text-base border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition resize-none"></textarea>
            </div>

            <div>
                <label for="preco" class="block text-sm font-semibold text-gray-700 mb-2">
                    Preço do prato <span class="text-red-600">*</span>
                </label>
                <div class="relative">
                    <input type="number" id="preco" name="preco"
                        min="1" step="1" placeholder="Ex.: 5000"
                        required
                        class="w-full h-12 px-4 pr-16 text-base border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 font-semibold text-gray-500">
                        Kz
                    </span>
                </div>
            </div>

            <div>
                <label for="imagem" class="block text-sm font-semibold text-gray-700 mb-2">
                    Imagem do prato <span class="text-red-600">*</span>
                </label>
                <input type="file" id="imagem" name="imagem"
                    accept="image/jpeg,image/png,image/webp"
                    required
                    class="w-full p-3 text-sm border border-gray-300 rounded-xl bg-gray-50 text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-red-600 file:text-white file:font-semibold hover:file:bg-red-700">
            </div>

            <p class="text-xs text-gray-500">
                <span class="text-red-600">*</span>
                Todos os campos são obrigatórios. Imagem até 5 MB.
            </p>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 h-12 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition duration-300 shadow-md">
                    Cadastrar Prato
                </button>

                <button type="reset"
                    class="px-6 h-12 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition duration-300">
                    Limpar
                </button>
            </div>

        </form>
    </div>
</section>

</body>
</html>
