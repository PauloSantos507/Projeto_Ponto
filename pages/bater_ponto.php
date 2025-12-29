<?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
    <nav style="background: #333; color: white; padding: 10px; margin-bottom: 20px;">
        <strong>Painel Administrativo:</strong> | 
        <a href="criar_usuario.html" style="color: white;">Cadastrar Novo Usuário</a> | 
        <a href="relatorio_pontos.php" style="color: white;">Ver Relatórios</a> | 
        <a href="../includes/encerrar_sessao.php" style="color: white;">Sair</a>
    </nav>
<?php endif; ?>

<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Bater Ponto</title>
</head>
<body>
    <h2>Registrar Ponto</h2>

    <?php if (isset($_GET['mensagem'])): ?>
        <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($_GET['mensagem']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
        <div style="background: #eee; padding: 10px; margin-bottom: 20px;">
            <strong>Painel Admin:</strong> 
            <a href="criar_usuario.html">Cadastrar Usuário</a> | 
            <a href="relatorio_pontos.php">Ver Relatórios</a> |
            <a href="../includes/encerrar_sessao.php">Sair</a>
        </div>
    <?php endif; ?>

    <form action="../includes/autenticador.php" method="POST">
        <input type="email" name="email_login" placeholder="Seu E-mail" required><br><br>
        <input type="password" name="senha_login" placeholder="Sua Senha" required><br><br>
        <button type="submit">Registrar Ponto</button>
    </form>
</body>
</html>