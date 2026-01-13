<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../pages/login.php?mensagem=' . urlencode("Você precisa estar logado.") . '&tipo=erro');
    exit();
}

// Verifica se é requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/bater_ponto.php?mensagem=' . urlencode("Método inválido.") . '&tipo=erro');
    exit();
}

$is_admin = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1;
$usuario_logado_id = $_SESSION['usuario_id'];
$usuario_id = filter_var($_POST['usuario_id'] ?? 0, FILTER_VALIDATE_INT);

// Validação: usuário deve existir
if (!$usuario_id) {
    header('Location: ../pages/bater_ponto.php?mensagem=' . urlencode("ID de usuário inválido.") . '&tipo=erro');
    exit();
}

// Segurança: usuário comum só pode editar próprio perfil
if (!$is_admin && $usuario_id != $usuario_logado_id) {
    header('Location: ../pages/bater_ponto.php?mensagem=' . urlencode("Acesso negado.") . '&tipo=erro');
    exit();
}

// Captura dos dados do formulário
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$matricula = trim($_POST['matricula'] ?? '');
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

// Validações básicas
if (empty($nome) || empty($email) || empty($matricula)) {
    header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("Nome, e-mail e matrícula são obrigatórios.") . '&tipo=erro');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("E-mail inválido.") . '&tipo=erro');
    exit();
}

// Validação de senha
$atualizar_senha = false;
$senha_hash = null;

if (!empty($nova_senha)) {
    if (strlen($nova_senha) < 6) {
        header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("A senha deve ter no mínimo 6 caracteres.") . '&tipo=erro');
        exit();
    }
    
    if ($nova_senha !== $confirmar_senha) {
        header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("As senhas não coincidem.") . '&tipo=erro');
        exit();
    }
    
    $atualizar_senha = true;
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
}

try {
    // Verifica se o e-mail já está em uso por outro usuário
    $sql_check = "SELECT id FROM usuarios WHERE email = :email AND id != :usuario_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':email' => $email, ':usuario_id' => $usuario_id]);
    
    if ($stmt_check->fetch()) {
        header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("Este e-mail já está cadastrado por outro usuário.") . '&tipo=erro');
        exit();
    }
    
    // Verifica se a matrícula já está em uso por outro usuário
    $sql_check_mat = "SELECT id FROM usuarios WHERE matricula = :matricula AND id != :usuario_id";
    $stmt_check_mat = $pdo->prepare($sql_check_mat);
    $stmt_check_mat->execute([':matricula' => $matricula, ':usuario_id' => $usuario_id]);
    
    if ($stmt_check_mat->fetch()) {
        header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("Esta matrícula já está cadastrada por outro usuário.") . '&tipo=erro');
        exit();
    }
    
    // Monta a query de atualização
    if ($is_admin) {
        // Admin pode alterar perfil e carga horária
        $perfil = filter_var($_POST['perfil'] ?? 0, FILTER_VALIDATE_INT);
        $carga_horaria = filter_var($_POST['carga_horaria'] ?? 8, FILTER_VALIDATE_INT);
        
        if ($carga_horaria < 1 || $carga_horaria > 24) {
            header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("Carga horária inválida (1-24).") . '&tipo=erro');
            exit();
        }
        
        if ($atualizar_senha) {
            $sql = "UPDATE usuarios 
                    SET nome = :nome, 
                        email = :email,
                        matricula = :matricula,
                        senha = :senha,
                        perfil = :perfil,
                        carga_horaria = :carga_horaria
                    WHERE id = :id";
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':matricula' => $matricula,
                ':senha' => $senha_hash,
                ':perfil' => $perfil,
                ':carga_horaria' => $carga_horaria,
                ':id' => $usuario_id
            ];
        } else {
            $sql = "UPDATE usuarios 
                    SET nome = :nome, 
                        email = :email,
                        matricula = :matricula,
                        perfil = :perfil,
                        carga_horaria = :carga_horaria
                    WHERE id = :id";
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':matricula' => $matricula,
                ':perfil' => $perfil,
                ':carga_horaria' => $carga_horaria,
                ':id' => $usuario_id
            ];
        }
    } else {
        // Usuário comum atualiza apenas nome, email, matrícula e senha
        if ($atualizar_senha) {
            $sql = "UPDATE usuarios 
                    SET nome = :nome, 
                        email = :email,
                        matricula = :matricula,
                        senha = :senha
                    WHERE id = :id";
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':matricula' => $matricula,
                ':senha' => $senha_hash,
                ':id' => $usuario_id
            ];
        } else {
            $sql = "UPDATE usuarios 
                    SET nome = :nome, 
                        email = :email,
                        matricula = :matricula
                    WHERE id = :id";
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':matricula' => $matricula,
                ':id' => $usuario_id
            ];
        }
    }
    
    // Executa a atualização
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute($params);
    
    if ($resultado) {
        // Atualiza a sessão se o usuário editou o próprio nome
        if ($usuario_id == $usuario_logado_id) {
            $_SESSION['usuario_nome'] = $nome;
        }
        
        // Redireciona com mensagem de sucesso
        $redirect = $is_admin && $usuario_id != $usuario_logado_id 
                    ? '../pages/gerenciar_usuarios.php' 
                    : '../pages/edita_usuario.php?id=' . $usuario_id;
        
        $mensagem = $atualizar_senha 
                    ? "Usuário atualizado com sucesso! Senha alterada." 
                    : "Usuário atualizado com sucesso!";
        
        header('Location: ' . $redirect . '&mensagem=' . urlencode($mensagem) . '&tipo=sucesso');
        exit();
    } else {
        throw new Exception("Erro ao atualizar os dados no banco.");
    }
    
} catch (PDOException $e) {
    error_log("Erro ao editar usuário: " . $e->getMessage());
    header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode("Erro ao atualizar usuário. Tente novamente.") . '&tipo=erro');
    exit();
} catch (Exception $e) {
    error_log("Erro ao editar usuário: " . $e->getMessage());
    header('Location: ../pages/edita_usuario.php?id=' . $usuario_id . '&mensagem=' . urlencode($e->getMessage()) . '&tipo=erro');
    exit();
}
