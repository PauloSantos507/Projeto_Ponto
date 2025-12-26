<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if ($_SESSION['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email_login'];
    $senha = $_POST['senha_login'];
        
    try{
        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $id_usuario = $usuario['id'];
            $data_hoje = date('Y-m-d');
            $hora_atual = date('H:m:s');

            $sql_last = "SELECT tipo_batida FROM registros_ponto
                        WHERE id_usuario = :uid AND data_registro = :data
                        ORDER BY id DESC LIMIT 1";

            $stmt_last = $pdo->prepare($sql_last);
            $stmt_last->execute([':uid' => $id_usuario, ':data' => $data_hoje]);
            $ultimo = $stmt_last->fetch();

            $tipo = ($ultimo && $$ultimo['tipo_batida'] === 'entrada') ? 'saida' : 'entrada';

            $sql_ins = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_registro, tipo_batida)
                        VALUES (:uid, :data, :hora, :tipo)";

            $pdo->prepare($sql_ins)->execute([
                ':uid' => $id_usuario, ':data' => $data_hoje, ':hora' => $hora_atual, ':tipo' => $tipo
            ]);

            $_SESSION['ultimo_email'] = $email;
            header("Location: ../pages/bater_ponto.php?mensagem = Ponto de $tipo registrado às $hora_atual");
            exit();

        } else{ 
            header("Location: ../pages/bater_ponto.php?mensagem = Erro: E-mail ou senha incorretos");
            exit();
        }
} catch (PDOException $e) {
        error_log($e->getMessage());
        die("Erro interno no servidor.");
    }
}
