<?php
// Verifica se o usuário está autenticado
session_start();
// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');
// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../config/conexao.php';
// Proteção: Processa somente requisições POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
        exit();
    }
    // Valiação se o usuário está logado
    elseif (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'Usuário não autenticado.']);
        exit();
    }
    // Validação se o usuário é administrador
    elseif (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'erro' => 'Acesso não autorizado.']);
        exit();
    }

// Receber dados

$id_usuario = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
$data_registro = $_POST['data_registro'] ?? '';
$hora_registro = $_POST['hora_registro'] ?? '';
$tipo_batida = $_POST['tipo_batida'] ?? '';
$id_just_padrao = filter_var($_POST['id_justificativa_padrao'] ?? null, FILTER_VALIDATE_INT);
$texto_personalizado = trim($_POST['texto_personalizado'] ?? '');

// validações básicas
if (!$id_usuario || !$data_registro || !$hora_registro || !in_array($tipo_batida, ['entrada', 'saida']) || ((!$id_just_padrao && empty($texto_personalizado)))) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos fornecidos.']);
    exit();
}

// algumas validações de funcionalidades
// Verifica se a data que está sendo preenchdia, não é maior do que a data atual
$data_hoje = date ('Y-m-d');
if ($data_registro > $data_hoje){
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'A data do registro não pode ser futura.']);
    exit();
}

$sql_usuario = "SELECT id FROM usuarios WHERE id = :id_usuario";
$stmt = $pdo->prepare ($sql_usuario);
$stmt->execute([':id_usuario'=>$id_usuario]);
if (!$stmt->fetch()){
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado.']);
    exit();
}

// Vou testar sem um nova verificação. Uma ideia seria acrescentar uma verificação para não permitir que o ponto manual fosse preenchido com o mesmo horário.

// Insere o ponto manual no banco de dadso

try {
    $sql_ponto = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_registro, tipo_batida) 
                  VALUES (:uid, :data, :hora, :tipo)";
    $stmt_ponto = $pdo->prepare($sql_ponto);
    $stmt_ponto->execute([
        ':uid' => $id_usuario,
        ':data' => $data_registro,
        ':hora' => $hora_registro . ':00',
        ':tipo' => $tipo_batida
    ]);

    // Pegar o Id do ponto inserido

    $id_ponto = $pdo->lastInsertId();
    
    // Adiciona a justificativa.

    if ($id_just_padrao) {
        $sql_just_texto = "SELECT descricao FROM justificativas_padrao WHERE id = :id AND ativa = 1";
        $stmt_just_texto = $pdo->prepare($sql_just_texto);
        $stmt_just_texto->execute([':id' => $id_just_padrao]);
        $just_row = $stmt_just_texto->fetch();
        $texto_justificativa = $just_row['descricao'] ?? 'Adição manual';
        } else {
            $texto_justificativa = $texto_personalizado;
        }


        // Insere a justificativa no banco

        $sql_justificativa = "INSERT INTO justificativas 
                            (id_ponto, id_admin, id_justificativa_padrao, texto_justificativa, data_hora_criacao) 
                            VALUES (:id_ponto, :id_admin, :id_just_padrao, :texto, NOW())";

        $stmt_justificativa = $pdo->prepare($sql_justificativa);
        $stmt_justificativa->execute([
            ':id_ponto' => $id_ponto,
            ':id_admin' => $_SESSION['usuario_id'],
            ':id_just_padrao' => $id_just_padrao ?: null,
            ':texto' => $texto_justificativa
        ]);

        // Retornar sucesso
        echo json_encode(['sucesso' => true]);
        exit();
            
        } catch (PDOException $e) {
            // Erro no banco de dados
            error_log("Erro ao adicionar ponto manual: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar no banco de dados']);
            exit();
        }
