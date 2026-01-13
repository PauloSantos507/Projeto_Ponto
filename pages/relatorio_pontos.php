<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?mensagem=" . urlencode("Você precisa estar logado."));
    exit();
}

$is_admin = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1;

// Filtros de busca
if ($is_admin) {
    $usuarios_lista = $pdo->query("SELECT id, nome, carga_horaria FROM usuarios ORDER BY nome ASC")->fetchAll();
    $usuario_id = filter_var($_GET['usuario_id'] ?? '', FILTER_VALIDATE_INT) ?: '';
} else {
    $sql = "SELECT id, nome, carga_horaria FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuarios_lista = $stmt->fetchAll();
    $usuario_id = $_SESSION['usuario_id'];
}

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$dados_relatorio = [];
$carga_do_usuario = 0;

if ($usuario_id) {
    foreach ($usuarios_lista as $u) {
        if ($u['id'] == $usuario_id) $carga_do_usuario = $u['carga_horaria'];
    }

    $sql = "SELECT id, data_registro, hora_registro, justificativa FROM registros_ponto 
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
        $dados_relatorio[$dia]['batidas'][] = [
            'id' => $reg['id'], 
            'hora' => $reg['hora_registro'],
            'justificativa' => $reg['justificativa']
        ];
    }

    foreach ($dados_relatorio as $dia => &$info) {
        $b = $info['batidas'];
        $segundos_dia = 0;
        for ($i = 0; $i < count($b); $i += 2) {
            if (isset($b[$i]) && isset($b[$i+1])) {
                $segundos_dia += (strtotime($b[$i+1]['hora']) - strtotime($b[$i]['hora']));
            }
        }
        $info['total_segundos'] = $segundos_dia;
    }
}

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
    <title>Relatório de Pontos</title>
    <style>
        /* CSS Integrado para evitar quebras de layout */
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7f4; padding: 20px; color: #333; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 1100px; margin: auto; }
        h1 { color: #26272d; margin-bottom: 20px; font-size: 24px; }
        .filtros { display: flex; gap: 15px; margin: 20px 0; align-items: flex-end; background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .filtros label { font-size: 13px; font-weight: bold; color: #666; }
        select, input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-filtrar { background: #ca521f; color: white; border: none; padding: 9px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #868788; color: white; padding: 12px; font-size: 14px; }
        td { border-bottom: 1px solid #eee; padding: 12px; text-align: center; font-size: 14px; }
        
        .btn-edit-icon { background: none; border: none; cursor: pointer; color: #dc931a; font-size: 16px; margin-left: 5px; transition: 0.2s; }
        .btn-edit-icon:hover { transform: scale(1.2); }
        
        /* Estilo para horários editados */
        .hora-editada { 
            background: #fff3cd; 
            color: #856404; 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-weight: bold;
            display: inline-block;
            position: relative;
            cursor: help;
        }
        .hora-editada::before {
            content: '✏️ ';
            font-size: 12px;
        }
        
        /* Tooltip para justificativa */
        .tooltip {
            position: relative;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 280px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -140px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            line-height: 1.4;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        .tooltip-label {
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 5px;
        }
        
        .positivo { color: #28a745; font-weight: bold; }
        .negativo { color: #d62a07; font-weight: bold; }

        /* Estilo do Modal */
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; }
        .modal-container { background:white; width:90%; max-width:400px; margin:10% auto; padding:25px; border-radius:12px; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: none; }
        .modal-actions { display: flex; gap: 10px; }
        .btn-save { flex: 2; background: #28a745; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-cancel { flex: 1; background: #868788; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Folha de Ponto Detalhada</h1>
        <a href="bater_ponto.php" style="color: #dc931a; text-decoration: none; font-size: 14px;">⬅ Voltar para Registro</a>

        <form method="GET" class="filtros">
            <div>
                <label>Funcionário:</label><br>
                <?php if ($is_admin): ?>
                    <select name="usuario_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($usuarios_lista as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $usuario_id == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" value="<?= htmlspecialchars($usuarios_lista[0]['nome']) ?>" disabled>
                <?php endif; ?>
            </div>
            <div>
                <label>Início:</label><br>
                <input type="date" name="data_inicio" value="<?= $data_inicio ?>">
            </div>
            <div>
                <label>Fim:</label><br>
                <input type="date" name="data_fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" class="btn-filtrar">Filtrar</button>
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
                        $saldo = $info['total_segundos'] - ($carga_do_usuario * 3600);
                    ?>
                    <tr>
                        <td><strong><?= date('d/m/Y', strtotime($dia)) ?></strong></td>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <td>
                                <?php if (isset($info['batidas'][$i])): 
                                    $bt = $info['batidas'][$i];
                                    $hora_exibida = substr($bt['hora'], 0, 5);
                                    $foi_editado = !empty($bt['justificativa']);
                                    
                                    if ($foi_editado): ?>
                                        <span class="tooltip">
                                            <span class="hora-editada"><?= $hora_exibida ?></span>
                                            <span class="tooltiptext">
                                                <div class="tooltip-label">📝 Justificativa da Edição:</div>
                                                <?= htmlspecialchars($bt['justificativa']) ?>
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span><?= $hora_exibida ?></span>
                                    <?php endif;
                                    
                                    if ($is_admin): ?>
                                        <button class="btn-edit-icon" onclick="abrirModal('<?= $bt['id'] ?>', '<?= $hora_exibida ?>')">✏️</button>
                                    <?php endif;
                                else: echo "---"; endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td><?= formatarHoras($info['total_segundos']) ?></td>
                        <td class="<?= $saldo >= 0 ? 'positivo' : 'negativo' ?>"><?= formatarHoras($saldo) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="modalEdicao" class="modal-overlay">s
        <div class="modal-container">
            <h3>Editar Horário</h3>
            <hr><br>
            <input type="hidden" id="edit_id">
            <div class="form-group">
                <label>Novo Horário:</label>
                <input type="time" id="edit_hora" style="width: 100%;">
            </div>
            <div class="form-group">
                <label>Justificativa:</label>
                <textarea id="edit_justificativa" placeholder="Descreva o motivo da alteração..."></textarea>
            </div>
            <div class="modal-actions">
                <button onclick="salvarEdicao()" class="btn-save">Salvar Alteração</button>
                <button onclick="fecharModal()" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // JS Integrado para garantir funcionamento
        function abrirModal(id, hora) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_hora').value = hora;
            document.getElementById('modalEdicao').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modalEdicao').style.display = 'none';
            document.getElementById('edit_justificativa').value = '';
        }

        function salvarEdicao() {
            const id = document.getElementById('edit_id').value;
            const hora = document.getElementById('edit_hora').value;
            const justificativa = document.getElementById('edit_justificativa').value;

            if (!justificativa) {
                alert("Por favor, preencha a justificativa!");
                return;
            }

            fetch('../includes/processar_edicao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, hora, justificativa })
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert("Erro ao salvar: " + data.erro);
                }
            })
            .catch(err => alert("Erro na requisição. Verifique o arquivo processar_edicao.php"));
        }
    </script>
</body>
</html>