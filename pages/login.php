<?php
session_start();
// funciona da seguinte forma:
    // 1. Verifica se o usuário que está logando é admin, ou funcionário padrão.
    // -Admin: tem acesso ao painel de registro de pontos, pode cadastrar novos usuários e ver relatorios
    // -Funcionario padrão: Pode registrar os pontos.

require_once '../config/conexao.php';

$mensagem_erro = "";

// Processar o login quando o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (!empty($email) && !empty($senha)) {
        try {
            // Buscar usuário no banco de dados
            $sql = "SELECT id, nome, email, senha, perfil 
                    FROM usuarios WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $usuario = $stmt->fetch();

            if ($usuario) {
                // Verificar se a senha está correta
                if (password_verify($senha, $usuario['senha'])) {
                    // Login bem-sucedido - criar sessão
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_perfil'] = $usuario['perfil'];
                    
                    // Redirecionar para o painel
                    header("Location: bater_ponto.php");
                    exit();
                } else {
                    $mensagem_erro = "Senha incorreta. Tente novamente.";
                }
            } else {
                $mensagem_erro = "Usuário não encontrado.";
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $mensagem_erro = "Erro ao processar login. Tente novamente.";
        }
    } else {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Ponto</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #26272dff 0%, #d62a07ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #dc931a;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #dc931a 0%, #ca521f 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .mensagem-erro {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 14px;
        }

        .icone-login {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc931a 0%, #ca521f 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
            border-left: 4px solid #667eea;
        }

        .info-box strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icone-login">🕐</div>
            <h1>Sistema de Ponto</h1>
            <p>Faça login para acessar</p>
        </div>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="mensagem-erro">
                ⚠️ <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="seu@email.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    placeholder="Digite sua senha"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-login">
                Entrar no Sistema
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="font-size: 14px; color: #666;">
                Ou acesse: 
                <a href="bater_ponto.php" style="color: #dc931a; text-decoration: none; font-weight: bold;">
                    Registro de Ponto
                </a>
            </p>
        </div>
</body>
</html>