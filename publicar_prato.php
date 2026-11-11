
<?php

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adicionar_prato.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$preco = $_POST['preco'] ?? '';

if ($nome === '' || $descricao === '' ||
    !is_numeric($preco) || (float)$preco <= 0 ||
    (float)$preco > 9999999999.99) {
    exit('Dados inválidos. Verifique o formulário.');
}

if (!isset($_FILES['imagem']) ||
    $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    exit('Selecione uma imagem válida.');
}

$imagem = $_FILES['imagem'];

if ($imagem['size'] > 5 * 1024 * 1024) {
    exit('A imagem não pode ultrapassar 5 MB.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$tipo = $finfo->file($imagem['tmp_name']);

$formatos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($formatos[$tipo]) ||
    getimagesize($imagem['tmp_name']) === false) {
    exit('Formato de imagem não permitido.');
}

// Criar nome único
$nomeImagem = bin2hex(random_bytes(16))
            . '.' . $formatos[$tipo];

// Guardar imagem na pasta principal
$caminho = __DIR__ . '/' . $nomeImagem;

if (!move_uploaded_file($imagem['tmp_name'], $caminho)) {
    exit('Não foi possível guardar a imagem.');
}

try {

    $sql = "INSERT INTO pratos
        (usuario_id, categoria_id, nome, descricao,
         preco, imagem, estado)
        VALUES
        (NULL, NULL, :nome, :descricao,
         :preco, :imagem, 'publicado')";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nome' => $nome,
        ':descricao' => $descricao,
        ':preco' => $preco,
        ':imagem' => $nomeImagem
    ]);

    header('Location: index.php?publicacao=sucesso');
    exit;

} catch (PDOException $e) {

    if (file_exists($caminho)) {
        unlink($caminho);
    }

    error_log($e->getMessage());
    exit('Erro ao publicar o prato. Tente novamente.');

}
