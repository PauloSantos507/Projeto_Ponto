<?php
session_start();
// Segurança: Só Admin acessa a tela de cadastro
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: bater_ponto.php?mensagem=" . urlencode("Acesso negado."));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - Sistema de Ponto</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f6; display: flex; flex-direction: column; align-items: center; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h1 { color: #333; font-size: 24px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; margin-top: 15px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 25px; }
        button:hover { background: #218838; }
        .voltar { text-decoration: none; color: #007bff; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="card">
        <a href="bater_ponto.php" class="voltar">⬅ Voltar ao Painel</a>
        <h1>Cadastrar Novo Usuário</h1>
        <hr>

        <form action="../includes/criar_usuario.php" method="post">
            <label>Nome Completo</label>
            <input type="text" name="nome_usuario" placeholder="Ex: João Silva" required>

            <label>E-mail (Login)</label>
            <input type="email" name="email_usuario" placeholder="joao@empresa.com" required>

            <label>Senha Provisória</label>
            <input type="password" name="senha_usuario" required>

            <label>Tipo de Perfil</label>
            <select name="perfil_usuario">
                <option value="0">Funcionário (Padrão)</option>
                <option value="1">Administrador (Acesso Total)</option>
            </select>

            <label>Carga Horária Diária (Horas)</label>
            <input type="number" name="carga_horaria" placeholder="Ex: 8" min="1" max="24" required>

            <button type="submit">Finalizar Cadastro</button>
        </form>
    </div>

</body>
</html>