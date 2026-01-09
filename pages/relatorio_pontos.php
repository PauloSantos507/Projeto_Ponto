<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . '/../config/conexao.php';

// 1. SEGURANÇA: Usuário precisa estar logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?mensagem=" . urlencode("Você precisa estar logado."));
    exit();
}

// Verifica se é admin ou funcionário padrão
$is_admin = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1;

// 2. Busca lista de usuários e filtros
if ($is_admin) {
    // Admin pode ver todos os usuários
    $usuarios_lista = $pdo->query("SELECT id, nome, carga_horaria FROM usuarios ORDER BY nome ASC")->fetchAll();
    $usuario_id = filter_var($_GET['usuario_id'] ?? '', FILTER_VALIDATE_INT) ?: '';
} else {
    // Funcionário padrão só vê ele mesmo
    $sql = "SELECT id, nome, carga_horaria FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuarios_lista = $stmt->fetchAll();
    $usuario_id = $_SESSION['usuario_id']; // Força o ID do próprio usuário
}

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$dados_relatorio = [];
$carga_do_usuario = 0;

if ($usuario_id) {
    // Busca carga horária do selecionado
    foreach ($usuarios_lista as $u) {
        if ($u['id'] == $usuario_id) $carga_do_usuario = $u['carga_horaria'];
    }

    // Busca registros brutos ordenados por tempo
    $sql = "SELECT data_registro, hora_registro, tipo_batida 
            FROM registros_ponto 
            WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
            ORDER BY data_registro ASC, hora_registro ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $registros = $stmt->fetchAll();

    // 3. ORGANIZAÇÃO EM COLUNAS
    foreach ($registros as $reg) {
        $dia = $reg['data_registro'];
        if (!isset($dados_relatorio[$dia])) {
            $dados_relatorio[$dia] = [
                'e1' => '---', 's1' => '---', 'e2' => '---', 's2' => '---',
                'total_segundos' => 0, 'batidas' => []
            ];
        }
        $dados_relatorio[$dia]['batidas'][] = $reg;
    }

    // Processa os cálculos e atribui às colunas
foreach ($dados_relatorio as $dia => &$info) {
    $b = $info['batidas'];
    
    // Atribuição visual para as colunas (HH:MM)
    $info['e1'] = isset($b[0]['hora_registro']) ? substr($b[0]['hora_registro'], 0, 5) : '---';
    $info['s1'] = isset($b[1]['hora_registro']) ? substr($b[1]['hora_registro'], 0, 5) : '---';
    $info['e2'] = isset($b[2]['hora_registro']) ? substr($b[2]['hora_registro'], 0, 5) : '---';
    $info['s2'] = isset($b[3]['hora_registro']) ? substr($b[3]['hora_registro'], 0, 5) : '---';

    $segundos = 0;

    // Cálculo do 1º Período: Da 1ª batida até a 2ª batida
    if (isset($b[0]['hora_registro']) && isset($b[1]['hora_registro'])) {
        $segundos += strtotime($b[1]['hora_registro']) - strtotime($b[0]['hora_registro']);
    }

    // Cálculo do 2º Período: Da 3ª batida até a 4ª batida
    if (isset($b[2]['hora_registro']) && isset($b[3]['hora_registro'])) {
        $segundos += strtotime($b[3]['hora_registro']) - strtotime($b[2]['hora_registro']);
    }

    $info['total_segundos'] = $segundos;
    }
}

function formatarHoras($segundos) {
    $h = floor(abs($segundos) / 3600);
    $m = floor((abs($segundos) % 3600) / 60);
    $sinal = $segundos < 0 ? "-" : "";
    return sprintf("%s%02dh %02dm", $sinal, $h, $m);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Pontos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f7f6; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: center; }
        th { background: #f8f9fa; color: #333; }
        .positivo { color: #28a745; font-weight: bold; }
        .negativo { color: #ca521f; font-weight: bold; }
        .filtros { display: flex; gap: 15px; margin-bottom: 25px; align-items: flex-end; }
        select, input, button { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Folha de Ponto Detalhada</h1>
        <a href="bater_ponto.php" style="text-decoration: none;">⬅ Voltar</a>
        <hr>

        <form method="GET" class="filtros">
            <?php if ($is_admin): ?>
            <div>
                <label>Funcionário:</label><br>
                <select name="usuario_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($usuarios_lista as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $usuario_id == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
                <!-- Funcionário padrão: exibir apenas o nome dele -->
                <div>
                    <label>Funcionário:</label><br>
                    <input type="text" value="<?= htmlspecialchars($usuarios_lista[0]['nome']) ?>" disabled style="background: #f0f0f0;">
                </div>
            <?php endif; ?>
            <div>
                <label>Início:</label><br>
                <input type="date" name="data_inicio" value="<?= $data_inicio ?>">
            </div>
            <div>
                <label>Fim:</label><br>
                <input type="date" name="data_fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" style="background: #ff2600ff; color: white; cursor: pointer;">Filtrar</button>
        </form>

        <?php if ($usuario_id): ?>
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
                        $segundos_carga = $carga_do_usuario * 3600;
                        $saldo = $info['total_segundos'] - $segundos_carga;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($dia)) ?></td>
                        <td><?= $info['e1'] ?></td>
                        <td><?= $info['s1'] ?></td>
                        <td><?= $info['e2'] ?></td>
                        <td><?= $info['s2'] ?></td>
                        <td><?= formatarHoras($info['total_segundos']) ?></td>
                        <td class="<?= $saldo >= 0 ? 'positivo' : 'negativo' ?>">
                            <?= formatarHoras($saldo) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>