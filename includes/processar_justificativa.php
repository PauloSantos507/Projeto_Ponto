<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/conexao.php';

if(!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ponto = $_POST['id_ponto'] ?? null;
    $texto = $_POST['texto_justificativa'] ?? '';
    $id_admin = $_SESSION['usuario_id']; // O admin logado que está justificando

    if (!empty($texto) && !empty($id_ponto)) {
        try {
            // Insere com data_hora_criacao automática
            $sql = "INSERT INTO justificativas (id_ponto, id_admin, texto_justificativa, data_hora_criacao) 
                    VALUES (:ponto, :admin, :texto, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ponto' => $id_ponto,
                ':admin' => $id_admin,
                ':texto' => $texto
            ]);

            // Redireciona de volta ao relatório mantendo os filtros
            $usuario_id_origem = $_POST['usuario_id_origem'] ?? '';
            $redirect_url = "../pages/relatorio_pontos.php";
            if (!empty($usuario_id_origem)) {
                $redirect_url .= "?usuario_id=" . urlencode($usuario_id_origem);
            }
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "mensagem=" . urlencode("Justificativa adicionada com sucesso!");
            
            header("Location: " . $redirect_url);
            exit();
        } catch (PDOException $e) {
            error_log("Erro ao adicionar justificativa: " . $e->getMessage());
            die("Ocorreu um erro ao processar a justificativa: " . $e->getMessage());
        }
    } else {
        die("Dados incompletos. Preencha todos os campos.");
    }
}