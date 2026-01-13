<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($_POST['matricula_ponto'] ?? '');
    $senha = $_POST['senha_ponto'] ?? '';

    try {
        // 1. Busca o usuário pela MATRÍCULA
        $sql = "SELECT id, nome, senha, perfil FROM usuarios WHERE matricula = :matricula";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':matricula' => $matricula]);
        $usuario = $stmt->fetch();

        // 2. Valida a senha
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $data_hoje = date('Y-m-d');
            $hora_agora = date('H:i:s');

            // 3. Lógica de Alternância: Busca a última batida do usuário hoje
            $sql_ultimo = "SELECT tipo_batida FROM registros_ponto 
                            WHERE id_usuario = :uid AND data_registro = :data 
                            ORDER BY id DESC LIMIT 1";
            $stmt_u = $pdo->prepare($sql_ultimo);
            $stmt_u->execute([':uid' => $usuario['id'], ':data' => $data_hoje]);
            $ultimo_ponto = $stmt_u->fetch();

            // Se a última foi 'entrada', a próxima é 'saida'. Caso contrário, 'entrada'.
            $novo_tipo = ($ultimo_ponto && $ultimo_ponto['tipo_batida'] === 'entrada') ? 'saida' : 'entrada';

            // 4. Registra o ponto
            $sql_ponto = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_registro, tipo_batida) 
                        VALUES (:uid, :data, :hora, :tipo)";
            $stmt_ponto = $pdo->prepare($sql_ponto);
            $stmt_ponto->execute([
                ':uid' => $usuario['id'], 
                ':data' => $data_hoje, 
                ':hora' => $hora_agora,
                ':tipo' => $novo_tipo
            ]);

            $msg = "Ponto de " . strtoupper($novo_tipo) . " registrado com sucesso, " . htmlspecialchars($usuario['nome']) . "!";
        } else {
            $msg = "Erro: Matrícula ou senha incorretos.";
        }

        // 5. Retorna para a página de registro de ponto com a mensagem
        header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao Registrar Ponto: " . $e->getMessage());
        header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode("Erro interno no servidor."));
        exit();
    }
}
