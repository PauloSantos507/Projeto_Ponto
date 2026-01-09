<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Bater Ponto - Sistema de Ponto</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f6; display: flex; flex-direction: column; align-items: center; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #333; margin-top: 0; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff2600; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #e62200; }
        .admin-nav { background: #333; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; width: 100%; max-width: 800px; text-align: center; }
        .admin-nav a { color: #ffeb3b; text-decoration: none; margin: 0 10px; font-size: 14px; }
        .mensagem { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; width: 100%; max-width: 400px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
        <div class="admin-nav">
            <strong>Painel Admin:</strong>
            <a href="criar_usuario.php">Cadastrar Usuário</a> | 
            <a href="relatorio_pontos.php">Ver Relatórios</a> | 
            <a href="../includes/encerrar_sessao.php" style="color: #ff6b6b;">Sair</a>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mensagem'])): ?>
        <div class="mensagem">
            <?php echo htmlspecialchars($_GET['mensagem']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Registrar Ponto</h2>
        <p style="color: #666; font-size: 14px;">Identifique-se para registrar sua batida.</p>
        <form action="../includes/autenticador.php" method="POST">
            <input type="email" name="email_login" placeholder="Seu E-mail" required>
            <input type="password" name="senha_login" placeholder="Sua Senha" required>
            <button type="submit">Registrar Ponto</button>
        </form>
    </div>

</body>
</html>