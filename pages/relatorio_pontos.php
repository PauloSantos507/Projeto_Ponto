<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Só permite acesso se for Admin (perfil 1)
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: bater_ponto.php?mensagem=" . urlencode("Acesso negado."));
    exit();
}

// 2. Busca todos os funcionários para o SELECT do filtro
try {
    $stmt_users = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 0 ORDER BY nome ASC");
    $lista_usuarios = $stmt_users->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar usuários: " . $e->getMessage());
    $lista_usuarios = [];
}

// 3. Captura os filtros da URL (GET)
$usuario_id = $_GET['usuario_id'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Padrão: início do mês
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');     // Padrão: hoje

try {
    // 4. Monta a Query Dinâmica
    $sql = "SELECT u.nome, r.data_registro, r.hora_registro, r.tipo_batida 
            FROM registros_ponto r 
            JOIN usuarios u ON r.id_usuario = u.id 
            WHERE r.data_registro BETWEEN :inicio AND :fim";

    // Se um usuário específico foi selecionado, adiciona ao WHERE
    if (!empty($usuario_id)) {
        $sql .= " AND r.id_usuario = :uid";
    }

    $sql .= " ORDER BY r.data_registro DESC, r.hora_registro DESC";

    $stmt = $pdo->prepare($sql);
    $params = [':inicio' => $data_inicio, ':fim' => $data_fim];
    
    if (!empty($usuario_id)) {
        $params[':uid'] = $usuario_id;
    }

    $stmt->execute($params);
    $relatorio = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("ERRO NO RELATÓRIO: " . $e->getMessage());
    die("Erro ao gerar relatório.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Pontos</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f4f4f4; }
        .filtros { background: #eee; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        select, input, button { padding: 8px; margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Relatório de Frequência</h1>
    <a href="bater_ponto.php">⬅ Voltar para Início</a>
    <hr>

    <div class="filtros">
        <form method="GET" action="relatorio_pontos.php">
            <label>Colaborador:</label>
            <select name="usuario_id">
                <option value="">Todos os Funcionários</option>
                <?php foreach ($lista_usuarios as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($usuario_id == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>De:</label>
            <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>">
            
            <label>Até:</label>
            <input type="date" name="data_fim" value="<?php echo $data_fim; ?>">
            
            <button type="submit">Filtrar</button>
            <a href="relatorio_pontos.php">Limpar Filtros</a>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Colaborador</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($relatorio) > 0): ?>
                <?php foreach ($relatorio as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['data_registro'])); ?></td>
                        <td><?php echo $row['hora_registro']; ?></td>
                        <td><?php echo strtoupper($row['tipo_batida']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Nenhum registro encontrado para este filtro.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>