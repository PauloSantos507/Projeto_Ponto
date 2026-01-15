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
// NOTA: justificativa é opcional agora (será salva na tabela justificativas separadamente)
if (!$dados || empty($dados['id']) || empty($dados['hora'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos: ID e hora são obrigatórios.']);
    exit();
}

$id_registro   = $dados['id'];
$nova_hora     = $dados['hora'];

try {
    // 4. PERSISTÊNCIA: Atualiza apenas o horário no banco de dados
    // A justificativa é salva separadamente na tabela justificativas
    $sql = "UPDATE registros_ponto 
            SET hora_registro = :hora 
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
        ':hora' => $nova_hora,
        ':id'   => $id_registro
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