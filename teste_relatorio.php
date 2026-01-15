<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . '/config/conexao.php';

// Força limpeza de cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$usuario_id = 26;
$data_inicio = '2026-01-01';
$data_fim = '2026-01-14';
$carga_do_usuario = 6;

$dados_relatorio = [];

$sql = "SELECT id, data_registro, hora_registro, tipo_batida, justificativa FROM registros_ponto 
        WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
        ORDER BY data_registro ASC, hora_registro ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
$registros = $stmt->fetchAll();

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

foreach ($dados_relatorio as $dia => &$info) {
    $b = $info['batidas'];
    $segundos_dia = 0;
    $entrada_temp = null;
    
    foreach ($b as $batida) {
        if ($batida['tipo'] === 'entrada') {
            $entrada_temp = strtotime($batida['hora']);
        } elseif ($batida['tipo'] === 'saida' && $entrada_temp !== null) {
            $segundos_dia += (strtotime($batida['hora']) - $entrada_temp);
            $entrada_temp = null;
        }
    }
    
    $info['total_segundos'] = $segundos_dia;
}
unset($info);

function formatarHoras($segundos) {
    $sinal = $segundos < 0 ? "-" : "";
    $abs = abs($segundos);
    return sprintf("%s%02dh %02dm", $sinal, floor($abs/3600), floor(($abs%3600)/60));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste Relatório</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #868788; color: white; padding: 12px; }
        td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .debug { background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffc107; }
    </style>
</head>
<body>
    <h1>Teste de Relatório - Sem Cache</h1>
    
    <div class="debug">
        <strong>Debug do Array:</strong><br>
        <?php foreach ($dados_relatorio as $dia => $info): ?>
            Dia: <?= $dia ?> → Total batidas: <?= count($info['batidas']) ?> →
            <?php foreach ($info['batidas'] as $bt): ?>
                [ID=<?= $bt['id'] ?> Hora=<?= $bt['hora'] ?>]
            <?php endforeach; ?>
            <br>
        <?php endforeach; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Entrada</th>
                <th>Saída Almoço</th>
                <th>Volta Almoço</th>
                <th>Saída Final</th>
                <th>Trabalhadas</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($dados_relatorio as $dia => $info): 
                $saldo = $info['total_segundos'] - ($carga_do_usuario * 3600);
                
                $slots = [null, null, null, null];
                $index_entrada = 0;
                
                foreach ($info['batidas'] as $bt) {
                    if ($bt['tipo'] === 'entrada') {
                        if ($index_entrada == 0) {
                            $slots[0] = $bt;
                            $index_entrada++;
                        } else {
                            $slots[2] = $bt;
                        }
                    } else {
                        if ($slots[0] !== null && $slots[1] === null) {
                            $slots[1] = $bt;
                        } else {
                            $slots[3] = $bt;
                        }
                    }
                }
            ?>
            <tr>
                <td><strong><?= date('d/m/Y', strtotime($dia)) ?></strong></td>
                <?php for ($i = 0; $i < 4; $i++): ?>
                    <td>
                        <?php if ($slots[$i] !== null): 
                            echo substr($slots[$i]['hora'], 0, 5);
                            echo " <small>(ID: {$slots[$i]['id']})</small>";
                        else: 
                            echo "---"; 
                        endif; ?>
                    </td>
                <?php endfor; ?>
                <td><?= formatarHoras($info['total_segundos']) ?></td>
                <td style="color: <?= $saldo >= 0 ? 'green' : 'red' ?>;"><?= formatarHoras($saldo) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
