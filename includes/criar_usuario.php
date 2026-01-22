<?php
session_start(); // Iniciar sessão para usar mensagens seguras

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/erro.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    error_log("POST RECEBIDO: " . print_r($_POST, true));

    $nome   = $_POST['nome_usuario'] ?? null;
    $matricula = $_POST['matricula_usuario'] ?? null;
    $email  = $_POST['email_usuario'] ?? null;
    $perfil = $_POST['perfil_usuario'] ?? null;
    $carga_horaria = $_POST['carga_horaria'] ?? null;
    $senhaHash = password_hash($_POST['senha_usuario'], PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (nome, matricula, email, senha, perfil, carga_horaria)
                VALUES (:nome, :matricula, :email, :senha, :perfil, :carga)";

        error_log("SQL: $sql");

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':matricula', $matricula);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':perfil', $perfil);
        $stmt->bindParam(':carga', $carga_horaria);

        $stmt->execute();

        // Armazena mensagem na sessão (seguro contra manipulação)
        $_SESSION['mensagem_sucesso'] = "Usuário cadastrado com sucesso!";
        header("Location: ../pages/gerenciar_usuarios.php");
        exit();

    } catch (PDOException $e) {
        error_log("ERRO PDO: " . $e->getMessage());
        
        // Armazena mensagem de erro na sessão
        $_SESSION['mensagem_erro'] = "Erro ao cadastrar usuário. Verifique os dados e tente novamente.";
        header("Location: ../pages/criar_usuario.php");
        exit();
    }
}