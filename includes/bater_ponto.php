<?php
session_start();
require_once '../Projeto_Ponto/config/conexao.php/';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso Negado!");
}

$id_usuario = $_SESSION['usuario_id'];
$data_hoje = $_SESSION['Y-m-d'];
$hora_atual = $_SESSION['H:i:s'];

try {
    $sql_check = "SELECT id, hora_saida FROM registros_ponto WHERE id_usuario = :uid AND data_registro = :data";
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([':uid' => $id_usuario, ':data' => $data_hoje]);
    $ponto = $stmt->fetch();

    if (!$ponto) {
        // Ação de registrar o ponto
        $sql = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_entrada VALUES (:uid, :data, :hora)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $id_usuario, ':data' => $data_hoje, ':hora' => $hora_atual, ':hora_atual']);
        $msg = "Entrada Registrada com sucesso!";
    } elseif ($ponto['hora_saida'] == null) {
        $sql = "UPDATE registros_pontoa SET hora_saida = :hora WHERE id = :id_ponto";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':hora' => $hora_agora, ':id_ponto' => $ponto['id']]);
        $msg = "Saída registrada com sucesso!";
    } else {
        $msg = "Você completou sua jornada de trabalho!";
    }
    header("Location: ponto.php?mensagem= " . urlencode($msg));
    exit();
} catch (PDOException $e) {
    echo "Erro no banco de dados:" . $e->getMessage();
}
