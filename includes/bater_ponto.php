<?php
session_start();
// Removida a barra após o .php
require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso Negado!");
}

$id_usuario = $_SESSION['usuario_id'];

// CORREÇÃO: Usar date() em vez de $_SESSION para pegar o tempo real do servidor
$data_hoje = date('Y-m-d');
$hora_atual = date('H:i:s');

try {
    $sql_check = "SELECT id, hora_saida FROM registros_ponto WHERE id_usuario = :uid AND data_registro = :data";
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([':uid' => $id_usuario, ':data' => $data_hoje]);
    $ponto = $stmt->fetch();

    if (!$ponto) {
        // CORREÇÃO: Adicionado o ")" fechando as colunas antes do VALUES
        $sql = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_entrada) VALUES (:uid, :data, :hora)";
        $stmt = $pdo->prepare($sql);
        // CORREÇÃO: Removido o parâmetro extra ':hora_atual' que estava sobrando
        $stmt->execute([':uid' => $id_usuario, ':data' => $data_hoje, ':hora' => $hora_atual]);
        $msg = "Entrada Registrada com sucesso!";
    } elseif ($ponto['hora_saida'] == null) {
        // CORREÇÃO: Nome da tabela era 'registros_pontoa'
        $sql = "UPDATE registros_ponto SET hora_saida = :hora WHERE id = :id_ponto";
        $stmt = $pdo->prepare($sql);
        // CORREÇÃO: Alterado $hora_agora para $hora_atual
        $stmt->execute([':hora' => $hora_atual, ':id_ponto' => $ponto['id']]);
        $msg = "Saída registrada com sucesso!";
    } else {
        $msg = "Você já completou sua jornada de trabalho hoje!";
    }

    header("Location: ../pages/bater_ponto.html?mensagem=" . urlencode($msg));
exit();

}catch (PDOException $e) {
    // Grava uma mensagem personalizada no arquivo erro.log
    error_log("Erro no banco de dados: " . $e->getMessage());
    
    // Exibe uma mensagem amigável para o usuário
    echo "Ocorreu um erro interno. Por favor, tente novamente mais tarde.";
}