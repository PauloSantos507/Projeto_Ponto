<?php
require_once __DIR__ . '/config/conexao.php';

echo "<h2>Debug Direto do Banco de Dados</h2>";
echo "<pre>";

// Consulta exatamente como está no relatorio_pontos.php
$usuario_id = 26;
$data_inicio = '2026-01-01';
$data_fim = '2026-01-14';

$sql = "SELECT id, data_registro, hora_registro, tipo_batida, justificativa FROM registros_ponto 
        WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
        ORDER BY data_registro ASC, hora_registro ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
$registros = $stmt->fetchAll();

echo "Total de registros: " . count($registros) . "\n\n";

foreach ($registros as $reg) {
    echo "ID: {$reg['id']}\n";
    echo "Data: {$reg['data_registro']}\n";
    echo "Hora: {$reg['hora_registro']}\n";
    echo "Tipo: {$reg['tipo_batida']}\n";
    echo "Justificativa: {$reg['justificativa']}\n";
    echo str_repeat("-", 50) . "\n";
}

echo "\n\nAgrupamento por data:\n";
$dados_relatorio = [];

foreach ($registros as $reg) {
    $dia = $reg['data_registro'];
    if (!isset($dados_relatorio[$dia])) {
        $dados_relatorio[$dia] = ['batidas' => [], 'total_segundos' => 0];
    }
    
    $tipo_batida = $reg['tipo_batida'] ?? 'entrada';
    
    $dados_relatorio[$dia]['batidas'][] = [
        'id' => $reg['id'], 
        'hora' => $reg['hora_registro'],
        'tipo' => $tipo_batida,
        'justificativa' => $reg['justificativa']
    ];
}

foreach ($dados_relatorio as $dia => $info) {
    echo "\nDia: $dia\n";
    echo "Total de batidas: " . count($info['batidas']) . "\n";
    foreach ($info['batidas'] as $idx => $bt) {
        echo "  [$idx] ID={$bt['id']} | Hora={$bt['hora']} | Tipo={$bt['tipo']}\n";
    }
}

echo "</pre>";
?>
