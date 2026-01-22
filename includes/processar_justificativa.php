<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/conexao.php';

if(!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ponto = $_POST['id_ponto'] ?? null;
    $id_admin = $_SESSION['usuario_id']; // O admin logado que está justificando
    
    // Receber justificativa padrão OU personalizada
    $id_just_padrao = filter_var($_POST['id_justificativa_padrao'] ?? null, FILTER_VALIDATE_INT);
    $texto_personalizado = trim($_POST['texto_personalizado'] ?? '');
    
    // Variáveis finais para inserção
    $texto_final = '';
    $id_padrao_final = null;
    
    // Determinar qual tipo de justificativa foi enviada
    if ($id_just_padrao !== false && $id_just_padrao > 0) {
        // JUSTIFICATIVA PADRÃO - buscar descrição da tabela
        try {
            $stmt_busca = $pdo->prepare("SELECT descricao FROM justificativas_padrao WHERE id = :id AND ativa = 1");
            $stmt_busca->execute([':id' => $id_just_padrao]);
            $descricao = $stmt_busca->fetchColumn();
            
            if (!$descricao) {
                die("Justificativa padrão inválida ou inativa.");
            }
            
            $texto_final = $descricao;
            $id_padrao_final = $id_just_padrao;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar justificativa padrão: " . $e->getMessage());
            die("Erro ao buscar justificativa padrão.");
        }
        
    } else if (!empty($texto_personalizado)) {
        // JUSTIFICATIVA PERSONALIZADA
        if (strlen($texto_personalizado) < 10) {
            die("A justificativa personalizada deve ter pelo menos 10 caracteres.");
        }
        if (strlen($texto_personalizado) > 500) {
            die("A justificativa personalizada não pode ter mais de 500 caracteres.");
        }
        
        $texto_final = $texto_personalizado;
        $id_padrao_final = null; // NULL indica que é personalizada
        
    } else {
        die("Nenhuma justificativa fornecida. Selecione uma justificativa padrão ou descreva um motivo personalizado.");
    }

    // Inserir no banco de dados
    if (!empty($texto_final) && !empty($id_ponto)) {
        try {
            // Insere com id_justificativa_padrao (NULL para personalizadas)
            $sql = "INSERT INTO justificativas (id_ponto, id_admin, texto_justificativa, id_justificativa_padrao, data_hora_criacao) 
                    VALUES (:ponto, :admin, :texto, :id_padrao, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ponto' => $id_ponto,
                ':admin' => $id_admin,
                ':texto' => $texto_final,
                ':id_padrao' => $id_padrao_final
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