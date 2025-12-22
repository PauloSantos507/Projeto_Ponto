<?php
session_start();
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email_login'];
    $senha = $_POST['senha_login'];

    try {
        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch();

        //Verifica se o usuário que está tentando acessar existe, e se sua senha bate com a senha que está no banco
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['ids'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            header("Location: bater_ponto.php");
            exit(); // É uma boa prática adicionar exit() após um redirecionamento de cabeçalho
        } else {
            echo "<script>alert('E-mail ou senha inválidos!'); window.location.href='login.html';</script>";
        }
    } catch (PDOException $e) {
        echo "Erro no sistema: " . $e->getMessage();
    }
}
