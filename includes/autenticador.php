<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email_login'];
    $senha = $_POST['senha_login'];

    try {
        $sql = "SELECT id, nome, senha, perfil FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Salva os dados na sessão
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil']; // 0 para Func, 1 para Admin

            $data_hoje = date('Y-m-d');
            $hora_agora = date('H:i:s');

            // LÓGICA DE ALTERNÂNCIA: Verifica a última batida de hoje
            $sql_check = "SELECT tipo FROM registros_ponto 
                        WHERE id_usuario = :uid AND data_registro = :data 
                        ORDER BY id DESC LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([':uid' => $usuario['id'], ':data' => $data_hoje]);
            $ultima = $stmt_check->fetch();

            // Se a última foi 'entrada', agora será 'saida'. Caso contrário, 'entrada'.
            $novo_tipo = ($ultima && $ultima['tipo'] == 'entrada') ? 'saida' : 'entrada';

            $sql_ins = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_registro, tipo) 
                        VALUES (:uid, :data, :hora, :tipo)";
            $stmt_ins = $pdo->prepare($sql_ins);
            $stmt_ins->execute([
                ':uid' => $usuario['id'], 
                ':data' => $data_hoje, 
                ':hora' => $hora_agora,
                ':tipo' => $novo_tipo
            ]);

            $msg = "Ponto de " . strtoupper($novo_tipo) . " registrado com sucesso às $hora_agora!";
            header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode($msg));
            exit();

        } else {
            header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode("Erro: Dados inválidos."));
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode("Erro no servidor."));
        exit();
    }
}