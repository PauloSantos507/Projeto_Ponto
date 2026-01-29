<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Busca as justificativas na nova tabela - Modificado
$justificativas_padrao = [];
try {
    $stmt_just_padrao = $pdo->query(
        "SELECT id, descricao FROM justificativas_padrao 
         WHERE ativa = 1 
         ORDER BY ordem ASC"
    );
    $justificativas_padrao = $stmt_just_padrao->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar justificativas padrão: " . $e->getMessage());
}

// restante código 

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

    $sql = "SELECT id, data_registro, hora_registro, tipo_batida FROM registros_ponto 
            WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
            ORDER BY data_registro ASC, hora_registro ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $registros = $stmt->fetchAll();

    // Buscar todas as justificativas da nova tabela
    $ids_registros = array_column($registros, 'id');
    $justificativas_map = [];

    if (!empty($ids_registros)) {
        $placeholders = implode(',', array_fill(0, count($ids_registros), '?'));

        // COnsulta para buscar justificativas na nova tabela, modificado a lógica
        $sql_just = "SELECT j.id_ponto, j.texto_justificativa, j.id_justificativa_padrao, j.data_hora_criacao, u.nome as admin_nome, jp.descricao as just_padrao_descricao
                     FROM justificativas j
                     LEFT JOIN usuarios u ON j.id_admin = u.id
                     LEFT JOIN justificativas_padrao jp ON j.id_justificativa_padrao = jp.id
                     WHERE j.id_ponto IN ($placeholders)
                     ORDER BY j.data_hora_criacao ASC";

        $stmt_just = $pdo->prepare($sql_just);
        $stmt_just->execute($ids_registros);

        foreach ($stmt_just->fetchAll() as $just) {
            if (!isset($justificativas_map[$just['id_ponto']])) {
                $justificativas_map[$just['id_ponto']] = [];
            }
            $justificativas_map[$just['id_ponto']][] = $just;
        }
    }

    foreach ($registros as $reg) {
        $dia = $reg['data_registro'];
        if (!isset($dados_relatorio[$dia])) {
            $dados_relatorio[$dia] = ['batidas' => [], 'total_segundos' => 0];
        }

        $tipo_batida = $reg['tipo_batida'] ?? 'entrada';

        // Buscar justificativas da nova tabela para este registro
        $justificativas_lista = $justificativas_map[$reg['id']] ?? [];

        $dados_relatorio[$dia]['batidas'][] = [
            'id' => $reg['id'],
            'hora' => $reg['hora_registro'],
            'tipo' => $tipo_batida,
            'justificativas' => $justificativas_lista // Array de justificativas da nova tabela
        ];
    }

    // Calcular número máximo de batidas em um dia (para criar colunas dinâmicas)
    $max_batidas = 0;
    foreach ($dados_relatorio as $dia => $info) {
        $num_batidas = count($info['batidas']);
        if ($num_batidas > $max_batidas) {
            $max_batidas = $num_batidas;
        }
    }
    // Garantir no mínimo 4 colunas de batidas
    if ($max_batidas < 4) $max_batidas = 4;

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
    unset($info); // CRÍTICO: Libera a referência para evitar bugs
}

function formatarHoras($segundos)
{
    $sinal = $segundos < 0 ? "-" : "";
    $abs = abs($segundos);
    return sprintf("%s%02dh %02dm", $sinal, floor($abs / 3600), floor(($abs % 3600) / 60));
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Pontos</title>
    <style>
        /* CSS Integrado para evitar quebras de layout */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f7f4;
            padding: 20px;
            color: #333;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            color: #26272d;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .filtros {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            align-items: flex-end;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .filtros label {
            font-size: 13px;
            font-weight: bold;
            color: #666;
        }

        select,
        input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-filtrar {
            background: #ca521f;
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Botões de Exportação */
        .export-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #218838;
        }

        .btn-export-all {
            background: #007bff;
        }

        .btn-export-all:hover {
            background: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #868788;
            color: white;
            padding: 12px;
            font-size: 14px;
        }

        td {
            border-bottom: 1px solid #eee;
            padding: 12px;
            text-align: center;
            font-size: 14px;
        }

        .btn-edit-icon {
            background: none;
            border: none;
            cursor: pointer;
            color: #dc931a;
            font-size: 16px;
            margin-left: 5px;
            transition: 0.2s;
        }

        .btn-edit-icon:hover {
            transform: scale(1.2);
        }

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
            content: '❗';
            font-size: 15px;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
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

        .positivo {
            color: #28a745;
            font-weight: bold;
        }

        .negativo {
            color: #d62a07;
            font-weight: bold;
        }

        /* Estilo do Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
        }

        .modal-container {
            background: white;
            width: 90%;
            max-width: 400px;
            margin: 10% auto;
            padding: 25px;
            border-radius: 12px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            resize: none;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-save {
            flex: 2;
            background: #28a745;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancel {
            flex: 1;
            background: #868788;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        tfoot tr {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 15px;
            border-top: 3px solid #868788;
        }

        tfoot td {
            padding: 15px 12px;
        }
        
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
            <!-- Botões de Exportação -->
            <div class="export-buttons">
                <form method="POST" action="../includes/exportar_relatorio.php" style="display: inline;">
                    <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                    <input type="hidden" name="data_inicio" value="<?= $data_inicio ?>">
                    <input type="hidden" name="data_fim" value="<?= $data_fim ?>">
                    <button type="submit" name="exportar_tipo" value="usuario" class="btn-export">
                        📥 Exportar Relatório <?= $is_admin ? 'do Usuário' : 'Meu Relatório' ?>
                    </button>
                </form>

                <?php if ($is_admin): ?>
                    <form method="POST" action="../includes/exportar_relatorio.php" style="display: inline;">
                        <input type="hidden" name="data_inicio" value="<?= $data_inicio ?>">
                        <input type="hidden" name="data_fim" value="<?= $data_fim ?>">
                        <button type="submit" name="exportar_tipo" value="todos" class="btn-export btn-export-all">
                            📦 Exportar Relatório de TODOS os Usuários
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <?php
                        // Gerar cabeçalhos dinâmicos baseado no número de batidas
                        $nomes_padrao = ['Entrada', 'Saída', 'Entrada', 'Saída'];
                        for ($i = 0; $i < $max_batidas; $i++):
                            if ($i < 4) {
                                echo '<th>' . $nomes_padrao[$i] . '</th>';
                            } else {
                                // Após a 4ª, alterna entre Entrada/Saída
                                //$numero = floor($i / 2) + 1;
                                //$numero = floor($i / 2) + 1;
                                // Caso queira numerar as batidas extras, descomente a linha acima e adicione o variavel $numero abaixo
                                $tipo = ($i % 2 == 0) ? 'Entrada ' : 'Saída ';
                                echo '<th>' . $tipo . '</th>';
                            }
                        endfor;
                        ?>
                        <th>Trabalhadas</th>
                        <th>Saldo</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $saldo_total = 0; // Variável que vai acmular o saldo geral
                    // Loop de repetição que preenche as linhas da tabela
                    foreach ($dados_relatorio as $dia => $info):
                        $saldo = $info['total_segundos'] - ($carga_do_usuario * 3600);
                        $saldo_total += $saldo;
                        // Organiza as batidas em ordem cronológica (já vêm ordenadas do banco)
                        // Cria array com todas as batidas na sequência
                        $batidas_ordenadas = [];
                        foreach ($info['batidas'] as $bt) {
                            $batidas_ordenadas[] = $bt;
                        }

                        // Preencher com null para completar as colunas vazias
                        while (count($batidas_ordenadas) < $max_batidas) {
                            $batidas_ordenadas[] = null;
                        }
                    ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($dia)) ?></strong></td>
                            <?php for ($i = 0; $i < $max_batidas; $i++): ?>
                                <td>
                                    <?php if ($batidas_ordenadas[$i] !== null):
                                        $bt = $batidas_ordenadas[$i];
                                        $hora_exibida = substr($bt['hora'], 0, 5);
                                        $tem_justificativas = !empty($bt['justificativas']);
                                        $foi_editado = $tem_justificativas;

                                        if ($foi_editado): ?>
                                            <span class="tooltip">
                                                <span class="hora-editada"><?= $hora_exibida ?></span>
                                                <span class="tooltiptext">
                                                    <div class="tooltip-label">📝 Histórico de Edições:</div>
                                                    <?php if ($tem_justificativas): ?>
                                                        <?php foreach ($bt['justificativas'] as $idx => $just): ?>
                                                            <div style="margin-bottom: 10px; padding-bottom: 10px; <?= $idx < count($bt['justificativas']) - 1 ? 'border-bottom: 1px solid #555;' : '' ?>">

                                                                <!-- Adicionar indicador visual -->
                                                                <?php if ($just['id_justificativa_padrao']): ?>
                                                                    <div style="background: #4CAF50; color: white; display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-bottom: 3px;">
                                                                        📋 PADRÃO
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div style="background: #FF9800; color: white; display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-bottom: 3px;">
                                                                        ✏️ PERSONALIZADA
                                                                    </div>
                                                                <?php endif; ?>

                                                                <div style="font-size: 11px; color: #aaa; margin-bottom: 3px;">
                                                                    <?= date('d/m/Y H:i', strtotime($just['data_hora_criacao'])) ?> - <?= htmlspecialchars($just['admin_nome']) ?>
                                                                </div>
                                                                <div><?= htmlspecialchars($just['texto_justificativa']) ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        <?php else: ?>
                                            <span><?= $hora_exibida ?></span>
                                        <?php endif;

                                        if ($is_admin): ?>
                                            <button class="btn-edit-icon" onclick="abrirModal('<?= $bt['id'] ?>', '<?= $hora_exibida ?>')">✏️</button>
                                    <?php endif;
                                    else: echo "---";
                                    endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td><?= formatarHoras($info['total_segundos']) ?></td>
                            <td class="<?= $saldo >= 0 ? 'positivo' : 'negativo' ?>"><?= formatarHoras($saldo) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <!--
                Calculo de Saldo total e nova seção de rodapé
                                -->
                <tfoot>
                    <tr style="background-color: #f0f0f0; font-weight: bold; border-top: 2px solid #868788;">
                        <td colspan="<?= $max_batidas + 1 ?>">SALDO TOTAL</td>
                        <td>---</td>
                        <td class="<?= $saldo_total >= 0 ? 'positivo' : 'negativo' ?>">
                            <?= formatarHoras($saldo_total) ?>
                        </td>
                    </tr>
                </tfoot>

            </table>
        <?php endif; ?>
    </div>

    <div id="modalEdicao" class="modal-overlay">
        <div class="modal-container">
            <h3>Editar Horário</h3>
            <hr><br>
            <input type="hidden" id="edit_id">
            <div class="form-group">
                <label>Novo Horário:</label>
                <input type="time" id="edit_hora" style="width: 100%;">
            </div>

            <!--
            Novo campo de justificativa para edição. A nova justificattva será salva no banco, poderá ser escolhida através de uma caixa de select fixa
                            -->
            <div class="form-group">
                <label>Justificativa:</label>
                <select id="edit_justificativa" onchange="verificarOutro()" required>
                    <option value="">Selecione uma justificativa...</option>
                    <?php foreach ($justificativas_padrao as $jp): ?>
                        <option value="<?= $jp['id'] ?>">
                            <?= htmlspecialchars($jp['descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="outro">📝 Outro motivo (descrever)...</option>
                </select>
            </div>
            <div class="form-group" id="grupo_personalizada" style="display: none;">
                <label>Descreva o motivo:</label>
                <textarea id="edit_justificativa_personalizada"
                    placeholder="Descreva o motivo da edição..."></textarea>
            </div>
            <!--
            Linha para separar a parte das justificativas
                            -->


            <div class="modal-actions">
                <button onclick="salvarEdicao()" class="btn-save">Salvar Alteração</button>
                <button onclick="fecharModal()" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // JS Integrado para garantir funcionamento
        // Adicionar função para mostrar/ocultar textarea personalizada.

        function verificarOutro() {
            const select = document.getElementById('edit_justificativa');
            const grupoPersonalizada = document.getElementById('grupo_personalizada');
            const textarea = document.getElementById('edit_justificativa_personalizada');

            if (select.value === 'outro') {
                grupoPersonalizada.style.display = 'block';
                textarea.required = true;
                textarea.focus();
            } else {
                grupoPersonalizada.style.display = 'none';
                textarea.required = false;
                textarea.value = '';
            }
        }

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

            const selectJust = document.getElementById('edit_justificativa');
            const textareaPersonalizada = document.getElementById('edit_justificativa_personalizada');

            let idJustificativaPadrao = null;
            let textoPersonalizado = '';

            // Validar seleção
            if (!selectJust.value) {
                alert("Por favor, selecione uma justificativa!");
                return;
            }

            if (selectJust.value === 'outro') {
                // Justificativa personalizada
                textoPersonalizado = textareaPersonalizada.value.trim();
                if (!textoPersonalizado) {
                    alert("Por favor, descreva o motivo da alteração!");
                    return;
                }
                if (textoPersonalizado.length < 10) {
                    alert("A justificativa deve ter pelo menos 10 caracteres!");
                    return;
                }
            } else {
                // Justificativa padrão
                idJustificativaPadrao = selectJust.value;
            }

            // Criar FormData para enviar ao novo endpoint
            const formData = new FormData();
            formData.append('id_ponto', id);
            if (idJustificativaPadrao) {
                formData.append('id_justificativa_padrao', idJustificativaPadrao);
            } else {
                formData.append('texto_personalizado', textoPersonalizado);
            }

            // Obter usuario_id da URL para redirecionar corretamente
            const urlParams = new URLSearchParams(window.location.search);
            const usuarioId = urlParams.get('usuario_id') || '';
            formData.append('usuario_id_origem', usuarioId);

            // Primeiro atualiza o horário via processar_edicao.php
            fetch('../includes/processar_edicao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        hora,
                        justificativa: ''
                    }) // Remove justificativa antiga
                })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        // Agora salva a justificativa na nova tabela
                        return fetch('../includes/processar_justificativa.php', {
                            method: 'POST',
                            body: formData
                        });
                    } else {
                        throw new Error(data.erro || 'Erro ao atualizar horário');
                    }
                })
                .then(response => {
                    // Recarrega a página após salvar a justificativa
                    location.reload();
                })
                .catch(err => {
                    alert("Erro ao salvar: " + err.message);
                });
        }
    </script>
</body>

</html>