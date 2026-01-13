<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores podem acessar
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: bater_ponto.php?mensagem=" . urlencode("Acesso negado."));
    exit();
}

// Buscar todos os usuários
$sql = "SELECT id, nome, email, matricula, perfil, carga_horaria FROM usuarios ORDER BY nome ASC";
$stmt = $pdo->query($sql);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Sistema de Ponto</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f5f7f4; 
            padding: 20px; 
            color: #333; 
        }
        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
        }
        .card { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #26272d; 
            margin-bottom: 10px; 
            font-size: 24px; 
        }
        .voltar { 
            display: inline-block;
            color: #dc931a; 
            text-decoration: none; 
            margin-bottom: 20px; 
            font-size: 14px; 
        }
        .voltar:hover { text-decoration: underline; }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .btn-novo {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-novo:hover {
            background: #218838;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th { 
            background: #868788; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-size: 14px; 
        }
        td { 
            border-bottom: 1px solid #eee; 
            padding: 12px; 
            font-size: 14px; 
        }
        tr:hover { 
            background: #f8f9fa; 
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-admin {
            background: #dc931a;
            color: white;
        }
        .badge-user {
            background: #6c757d;
            color: white;
        }
        
        .btn-editar {
            background: #dc931a;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        .btn-editar:hover {
            background: #c4831c;
        }
        
        .mensagem {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <a href="bater_ponto.php" class="voltar">⬅ Voltar ao Painel</a>
            
            <h1>👥 Gerenciar Usuários</h1>
            <p style="color: #666; font-size: 14px;">Visualize, edite e gerencie todos os usuários do sistema.</p>
            
            <?php if (isset($_GET['mensagem'])): ?>
                <div class="mensagem <?= isset($_GET['tipo']) && $_GET['tipo'] == 'erro' ? 'erro' : 'sucesso' ?>">
                    <?= htmlspecialchars($_GET['mensagem']) ?>
                </div>
            <?php endif; ?>
            
            <div class="actions-bar">
                <div>
                    <strong>Total de usuários:</strong> <?= count($usuarios) ?>
                </div>
                <a href="criar_usuario.php" class="btn-novo">➕ Cadastrar Novo Usuário</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Matrícula</th>
                        <th>Perfil</th>
                        <th>Carga Horária</th>
                        <th style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><strong>#<?= $usuario['id'] ?></strong></td>
                            <td><?= htmlspecialchars($usuario['nome']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?= htmlspecialchars($usuario['matricula']) ?></code></td>
                            <td>
                                <span class="badge <?= $usuario['perfil'] == 1 ? 'badge-admin' : 'badge-user' ?>">
                                    <?= $usuario['perfil'] == 1 ? 'Administrador' : 'Funcionário' ?>
                                </span>
                            </td>
                            <td><?= $usuario['carga_horaria'] ?>h/dia</td>
                            <td style="text-align: center;">
                                <a href="edita_usuario.php?id=<?= $usuario['id'] ?>" class="btn-editar">
                                    ✏️ Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                Nenhum usuário cadastrado no sistema.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
