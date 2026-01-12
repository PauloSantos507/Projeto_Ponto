<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Apenas administradores podem processar edições
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado: Permissão insuficiente.']);
    exit();
}

// 2. CAPTURA DE DADOS: Lê o corpo da requisição JSON enviada pelo Fetch
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

// 3. VALIDAÇÃO: Verifica se os campos obrigatórios foram enviados
if (!$dados || empty($dados['id']) || empty($dados['hora']) || empty($dados['justificativa'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos ou justificativa ausente.']);
    exit();
}

$id_registro   = $dados['id'];
$nova_hora     = $dados['hora'];
$justificativa = $dados['justificativa'];

try {
    // 4. PERSISTÊNCIA: Atualiza o registro no banco de dados
    // É fundamental usar Prepared Statements para evitar SQL Injection
    $sql = "UPDATE registros_ponto 
            SET hora_registro = :hora, 
                justificativa = :justificativa 
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        ':hora'          => $nova_hora,
        ':justificativa' => $justificativa,
        ':id'            => $id_registro
    ]);

    if ($resultado) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível atualizar o banco de dados.']);
    }

} catch (PDOException $e) {
    // Registra o erro detalhado no log configurado no conexao.php
    error_log("Erro na Edição de Ponto: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no servidor.']);
}