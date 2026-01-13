<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Bater Ponto - Sistema de Ponto</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7f4ff; display: flex; flex-direction: column; align-items: center; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #333; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ca521f; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #ca521f; }
        .admin-nav { background: #868788; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; width: 100%; max-width: 800px; text-align: center; }
        .admin-nav a { color: #dc931a; text-decoration: none; margin: 0 10px; font-size: 14px; }
        .mensagem { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; width: 100%; max-width: 400px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="admin-nav">
            <strong>
                <?php 
                    echo $_SESSION['usuario_perfil'] == 1 ? 'Painel Admin' : 'Painel do Usuário'; 
                ?>:
            </strong>
            
            <?php if ($_SESSION['usuario_perfil'] == 1): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a> | 
            <?php endif; ?>
            
            <a href="relatorio_pontos.php">Ver Relatórios</a> | 
            <a href="edita_usuario.php">Meu Perfil</a> | 
            <a href="../includes/encerrar_sessao.php" style="color: #dc931a;">Sair</a>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mensagem'])): ?>
        <div class="mensagem">
            <?php echo htmlspecialchars($_GET['mensagem']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Registrar Ponto</h2>
        <p style="color: #666; font-size: 14px;">Digite sua matrícula e senha para registrar sua batida.</p>
        <form action="../includes/registrar_ponto.php" method="POST">
            <input type="text" name="matricula_ponto" placeholder="Sua Matrícula" required autocomplete="off">
            <input type="password" name="senha_ponto" placeholder="Sua Senha" required autocomplete="off">
            <button type="submit">🕐 Registrar Ponto</button>
        </form>
        <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
            <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <a href="login.php" style="color: #dc931a; text-decoration: none; font-size: 13px;">🔐 Login Administrativo</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>