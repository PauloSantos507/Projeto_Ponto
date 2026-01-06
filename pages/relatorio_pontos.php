<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// SEGURANÇA: Só Admin acessa
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: bater_ponto.php?mensagem=" . urlencode("Acesso negado."));
    exit();
}

// BUSCA A LISTA PARA O SELECT (Isso é o que está faltando para preencher a lista)
try {
    // Buscamos apenas quem não é admin (perfil 0) ou todos, conforme sua preferência
    $stmt_lista = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome ASC");
    $lista_usuarios = $stmt_lista->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao carregar lista de usuários: " . $e->getMessage());
    $lista_usuarios = []; // Evita erro no foreach se falhar
}
//busca o usuário através de um filtro
$usuario = $pdo->query("SELECT id, nome, carga_horaria FROM usuarios ORDER BY nome ASC")->fetchAll();

//busca registros brutos
$usuario_id = $_GET['usuario_id'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Padrão: início do mês
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');     // Padrão: hoje

// Busca a carga horária do usuário que foi filtrado
if ($usuario_id) {
    foreach ($usuario as $u) {
        if ($u['id'] == $usuario_id) {
            $carga_do_usuario = $u['carga_horaria'];
        }
    }

    $sql = "SELECT data_registro, hora_registro, tipo_batida 
            FROM registros_ponto 
            WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
            ORDER BY data_registro ASC, hora_registro ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $registros = $stmt->fetchAll();

    // calculo agrupado de dias, por horas

    foreach ($dados_relatorio as $reg) {
        $dia = $reg['data_registro'];
        if (!isset($dados_relatorio[$dia])) {
            $dados_relatorio[$dia] = ['batidas' => [], 'total_batidas' => 0];
        }
        $dados_relatorio[$dia]['batidas'][] = $reg;
    }

    foreach ($dados_relatorio as $dia => &$info) {
        $segundos_dia = 0;
        $entrada_temp = null;

        foreach ($info['batidas'] as $b) {
            if ($b['tipo_batida'] == 'entrada') {
                $entrada_temp = strtotime($b['hora_registro']);
            } elseif ($b['tipo_batida'] == 'saida' && $entrada_temp) {
                $segundos_dia += strtotime($b['hora_registro']) - $entrada_temp;
                $entrada_temp = null;
            }
        }
        $info['total_segundos'] = $segundos_dia;
    }
}

// função que formata segundos em horas
function formatarHoras($segundos)
{
    $h = floor(abs($segundos) / 3600);
    $m = floor((abs($segundos) % 3600) / 60);
    $sinal = $segundos < 0 ? "-" : "";
    return sprintf("%s%02dh %02min", $sinal, $h, $m);
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Painel Admin - Relatório</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f9f9f9;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .positivo {
            color: green;
            font-weight: bold;
        }

        .negativo {
            color: red;
            font-weight: bold;
        }

        .filtro-bar {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Relatório de Frequência</h2>

        <form method="GET" class="filtro-bar">
            <div>
                <label>Funcionário:</label><br>
                <select name="usuario_id" required>
                    <option value="">Selecione um funcionário...</option>

                    <?php if (!empty($lista_usuarios)): ?>
                        <?php foreach ($lista_usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (isset($_GET['usuario_id']) && $_GET['usuario_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Nenhum usuário cadastrado</option>
                    <?php endif; ?>

                </select>
            </div>
            <div>
                <label>De:</label><br>
                <input type="date" name="data_inicio" value="<?= $data_inicio ?>">
            </div>
            <div>
                <label>Até:</label><br>
                <input type="date" name="data_fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit">Gerar Relatório</button>
        </form>

        <?php if ($usuario_id): ?>
            <h3>Resultados para o período:</h3>
            <p>Carga horária diária: <strong><?= $carga_do_usuario ?>h 00min</strong></p>

            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Batidas Realizadas</th>
                        <th>Horas Trabalhadas</th>
                        <th>Saldo do dia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $saldo_total_periodo = 0;
                    foreach ($dados_relatorio as $dia => $info):
                        $segundos_carga = $carga_do_usuario * 3600;
                        $saldo_dia = $info['total_segundos'] - $segundos_carga;
                        $saldo_total_periodo += $saldo_dia;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($dia)) ?></td>
                            <td>
                                <?php foreach ($info['batidas'] as $b) echo "[" . substr($b['hora_registro'], 0, 5) . " " . strtoupper($b['tipo_batida'][0]) . "] "; ?>
                            </td>
                            <td><?= formatarHoras($info['total_segundos']) ?></td>
                            <td class="<?= $saldo_dia >= 0 ? 'positivo' : 'negativo' ?>">
                                <?= formatarHoras($saldo_dia) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #eee;">
                        <td colspan="3" align="right"><strong>Saldo total no período:</strong></td>
                        <td class="<?= $saldo_total_periodo >= 0 ? 'positivo' : 'negativo' ?>">
                            <?= formatarHoras($saldo_total_periodo) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>