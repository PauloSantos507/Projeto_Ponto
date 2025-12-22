<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/erro.log');
error_reporting(E_ALL);

require_once __DIR__ . "../config/conexao.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    error_log("POST RECEBIDO: " . print_r($_POST, true));

    $nome   = $_POST['nome_usuario'] ?? null;
    $email  = $_POST['email_usuario'] ?? null;
    $perfil = $_POST['perfil_usuario'] ?? null;
    $senhaHash = password_hash($_POST['senha_usuario'], PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil)
                VALUES (:nome, :email, :senha, :perfil)";

        error_log("SQL: $sql");

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':perfil', $perfil);

        $stmt->execute();

        echo "Usuário cadastrado com sucesso!";

    } catch (PDOException $e) {
        error_log("ERRO PDO: " . $e->getMessage());
        echo "Erro ao cadastrar usuário. Verifique o log.";
    }
}