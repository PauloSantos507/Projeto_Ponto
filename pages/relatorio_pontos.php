<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// SEGURANÇA: Só permite acesso se for Admin (perfil 1)
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: bater_ponto.php?mensagem=" . urlencode("Acesso negado."));
    exit();
}

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

try {
    $sql = "SELECT u.nome, r.data_registro, r.hora_registro, r.tipo_batida 
            FROM registros_ponto r 
            JOIN usuarios u ON r.id_usuario = u.id 
            WHERE r.data_registro BETWEEN :inicio AND :fim 
            ORDER BY r.data_registro DESC, r.hora_registro DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
    $relatorio = $stmt->fetchAll();
} catch (PDOException $e) {
    // Escreve o erro detalhado no arquivo erro_sistema.log
    error_log("ERRO NO RELATÓRIO: " . $e->getMessage());
    
    // Mensagem genérica para o usuário não ver detalhes técnicos
    die("Desculpe, ocorreu um erro ao gerar o relatório. O administrador já foi notificado.");
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
        .filtros { background: #eee; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Relatório de Frequência</h1>
    <a href="bater_ponto.php">⬅ Voltar para Início</a>
    <hr>

    <div class="filtros">
        <form method="GET">
            <label>De:</label>
            <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>">
            <label>Até:</label>
            <input type="date" name="data_fim" value="<?php echo $data_fim; ?>">
            <button type="submit">Filtrar</button>
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
                <tr><td colspan="4">Nenhum registro encontrado para este período.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>