<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?mensagem=' . urlencode("Você precisa estar logado."));
    exit();
}

$is_admin = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1;
$usuario_logado_id = $_SESSION['usuario_id'];

// Determina qual usuário será editado
if ($is_admin && isset($_GET['id'])) {
    // Admin pode editar qualquer usuário
    $usuario_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$usuario_id) {
        header('Location: gerenciar_usuarios.php?mensagem=' . urlencode("ID inválido.") . '&tipo=erro');
        exit();
    }
} else {
    // Usuário comum só pode editar seu próprio perfil
    $usuario_id = $usuario_logado_id;
}

// Busca os dados do usuário
$sql = "SELECT id, nome, email, matricula, perfil, carga_horaria FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $usuario_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ' . ($is_admin ? 'gerenciar_usuarios.php' : 'bater_ponto.php') . '?mensagem=' . urlencode("Usuário não encontrado.") . '&tipo=erro');
    exit();
}

// Verifica se usuário comum está tentando editar outro usuário
if (!$is_admin && $usuario_id != $usuario_logado_id) {
    header('Location: bater_ponto.php?mensagem=' . urlencode("Acesso negado.") . '&tipo=erro');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Sistema de Ponto</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            padding: 20px; 
            background: #f4f7f6; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
        }
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 550px; 
        }
        h1 { 
            color: #26272d; 
            font-size: 24px; 
            margin-bottom: 10px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            color: #555; 
            font-weight: bold; 
            margin-top: 15px; 
            font-size: 14px;
        }
        input, select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #dc931a;
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            margin-top: 25px; 
        }
        button:hover { 
            background: #218838; 
        }
        .voltar { 
            text-decoration: none; 
            color: #dc931a; 
            display: inline-block; 
            margin-bottom: 15px; 
            font-size: 14px;
        }
        .voltar:hover { text-decoration: underline; }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .senha-section {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #ffc107;
        }
        .senha-section h3 {
            margin-top: 0;
            color: #856404;
            font-size: 16px;
        }
        
        .mensagem {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .mensagem.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensagem.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="card">
        <a href="<?= $is_admin ? 'gerenciar_usuarios.php' : 'bater_ponto.php' ?>" class="voltar">⬅ Voltar</a>
        
        <h1>✏️ Editar <?= $is_admin && $usuario_id != $usuario_logado_id ? 'Usuário' : 'Meu Perfil' ?></h1>
        <p style="color: #666; font-size: 14px;">
            <?= $is_admin && $usuario_id != $usuario_logado_id 
                ? 'Edite as informações do usuário abaixo.' 
                : 'Atualize suas informações pessoais e senha.' 
            ?>
        </p>
        
        <?php if (isset($_GET['mensagem'])): ?>
            <div class="mensagem <?= isset($_GET['tipo']) && $_GET['tipo'] == 'erro' ? 'erro' : 'sucesso' ?>">
                <?= htmlspecialchars($_GET['mensagem']) ?>
            </div>
        <?php endif; ?>

        <form action="../includes/processar_editar_usuario.php" method="POST">
            <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
            
            <label>Nome Completo</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
            
            <label>E-mail (Login Administrativo)</label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
            
            <label>Matrícula (Para Registro de Ponto)</label>
            <input type="text" name="matricula" value="<?= htmlspecialchars($usuario['matricula']) ?>" required maxlength="20">
            <small>Usada para registrar ponto no sistema</small>
            
            <?php if ($is_admin): ?>
                <label>Tipo de Perfil</label>
                <select name="perfil">
                    <option value="0" <?= $usuario['perfil'] == 0 ? 'selected' : '' ?>>Funcionário</option>
                    <option value="1" <?= $usuario['perfil'] == 1 ? 'selected' : '' ?>>Administrador</option>
                </select>
                
                <label>Carga Horária Diária (Horas)</label>
                <input type="number" name="carga_horaria" value="<?= $usuario['carga_horaria'] ?>" min="1" max="24" required>
            <?php endif; ?>
            
            <div class="senha-section">
                <h3>🔒 Alterar Senha</h3>
                <p style="margin: 0 0 10px 0; font-size: 13px; color: #856404;">
                    Preencha apenas se deseja alterar a senha. Deixe em branco para manter a atual.
                </p>
                
                <label>Nova Senha</label>
                <input type="password" name="nova_senha" placeholder="Digite a nova senha">
                <small>Mínimo de 6 caracteres</small>
                
                <label>Confirmar Nova Senha</label>
                <input type="password" name="confirmar_senha" placeholder="Digite novamente a nova senha">
            </div>
            
            <button type="submit">💾 Salvar Alterações</button>
        </form>
    </div>

</body>
</html>
