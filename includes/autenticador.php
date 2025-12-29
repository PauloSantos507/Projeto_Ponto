
<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email_login'];
    $senha = $_POST['senha_login'];

    try {
        // 1. Busca o usuário e o perfil (Admin ou Funcionário)
        $sql = "SELECT id, senha, perfil FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        // 2. Valida a senha
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Salva o perfil na sessão para controlar o menu admin
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_perfil'] = $usuario['perfil']; 

            $data_hoje = date('Y-m-d');
            $hora_agora = date('H:i:s');

            // 3. Lógica de Alternância: Busca a última batida do usuário hoje
            // Note que aqui já usamos o novo nome: tipo_batida
            $sql_ultimo = "SELECT tipo_batida FROM registros_ponto 
                        WHERE id_usuario = :uid AND data_registro = :data 
                        ORDER BY id DESC LIMIT 1";
            $stmt_u = $pdo->prepare($sql_ultimo);
            $stmt_u->execute([':uid' => $usuario['id'], ':data' => $data_hoje]);
            $ultimo_ponto = $stmt_u->fetch();

            // Se a última foi 'entrada', a próxima é 'saida'. Caso contrário, 'entrada'.
            $novo_tipo = ($ultimo_ponto && $ultimo_ponto['tipo_batida'] === 'entrada') ? 'saida' : 'entrada';

            // 4. Registra o ponto com a coluna correta: tipo_batida
            $sql_ponto = "INSERT INTO registros_ponto (id_usuario, data_registro, hora_registro, tipo_batida) 
                        VALUES (:uid, :data, :hora, :tipo)";
            $stmt_ponto = $pdo->prepare($sql_ponto);
            $stmt_ponto->execute([
                ':uid' => $usuario['id'], 
                ':data' => $data_hoje, 
                ':hora' => $hora_agora,
                ':tipo' => $novo_tipo
            ]);

            $msg = "Ponto de " . strtoupper($novo_tipo) . " registrado com sucesso!";
        } else {
            $msg = "Erro: E-mail ou senha incorretos.";
        }

        // 5. Retorna para a página inicial com a mensagem
        header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        // Registra o erro no log que configuramos no conexao.php
        error_log("Erro no Autenticador: " . $e->getMessage());
        header("Location: ../pages/bater_ponto.php?mensagem=" . urlencode("Erro interno no servidor."));
        exit();
    }
}