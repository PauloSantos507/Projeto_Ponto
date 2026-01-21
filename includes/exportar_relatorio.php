<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../pages/login.php?mensagem=" . urlencode("Você precisa estar logado."));
    exit();
}

$is_admin = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1;

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/relatorio_pontos.php");
    exit();
}

$exportar_tipo = $_POST['exportar_tipo'] ?? '';
$data_inicio = $_POST['data_inicio'] ?? date('Y-m-01');
$data_fim = $_POST['data_fim'] ?? date('Y-m-d');

// Função para formatar horas
function formatarHoras($segundos) {
    $sinal = $segundos < 0 ? "-" : "";
    $abs = abs($segundos);
    return sprintf("%s%02dh %02dm", $sinal, floor($abs/3600), floor(($abs%3600)/60));
}

// Função para processar dados de um usuário
function processarDadosUsuario($pdo, $usuario_id, $data_inicio, $data_fim) {
    // Buscar informações do usuário
    $sql_user = "SELECT nome, matricula, carga_horaria FROM usuarios WHERE id = :uid";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([':uid' => $usuario_id]);
    $usuario = $stmt_user->fetch();
    
    if (!$usuario) {
        return null;
    }
    
    // Buscar registros de ponto
    $sql = "SELECT id, data_registro, hora_registro, tipo_batida FROM registros_ponto 
            WHERE id_usuario = :uid AND data_registro BETWEEN :inicio AND :fim 
            ORDER BY data_registro ASC, hora_registro ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $usuario_id, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $registros = $stmt->fetchAll();
    
    // Buscar justificativas
    $ids_registros = array_column($registros, 'id');
    $justificativas_map = [];
    
    if (!empty($ids_registros)) {
        $placeholders = implode(',', array_fill(0, count($ids_registros), '?'));
        $sql_just = "SELECT j.id_ponto, j.texto_justificativa, j.data_hora_criacao, u.nome as admin_nome
                     FROM justificativas j
                     LEFT JOIN usuarios u ON j.id_admin = u.id
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
    
    // Organizar dados por dia
    $dados_relatorio = [];
    foreach ($registros as $reg) {
        $dia = $reg['data_registro'];
        if (!isset($dados_relatorio[$dia])) {
            $dados_relatorio[$dia] = ['batidas' => [], 'total_segundos' => 0];
        }
        
        $tipo_batida = $reg['tipo_batida'] ?? 'entrada';
        $justificativas_lista = $justificativas_map[$reg['id']] ?? [];
        
        $dados_relatorio[$dia]['batidas'][] = [
            'id' => $reg['id'], 
            'hora' => $reg['hora_registro'],
            'tipo' => $tipo_batida,
            'justificativas' => $justificativas_lista
        ];
    }
    
    // Calcular totais
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
    
    return [
        'usuario' => $usuario,
        'dados' => $dados_relatorio
    ];
}

// Processar exportação
if ($exportar_tipo === 'usuario') {
    // Exportar relatório de um usuário específico
    $usuario_id = $_POST['usuario_id'] ?? null;
    
    if (!$usuario_id) {
        header("Location: ../pages/relatorio_pontos.php?mensagem=" . urlencode("Erro: Usuário não especificado."));
        exit();
    }
    
    // Verifica permissão: admin pode exportar qualquer usuário, usuário comum só o próprio
    if (!$is_admin && $usuario_id != $_SESSION['usuario_id']) {
        header("Location: ../pages/relatorio_pontos.php?mensagem=" . urlencode("Erro: Sem permissão."));
        exit();
    }
    
    $resultado = processarDadosUsuario($pdo, $usuario_id, $data_inicio, $data_fim);
    
    if (!$resultado) {
        header("Location: ../pages/relatorio_pontos.php?mensagem=" . urlencode("Erro: Usuário não encontrado."));
        exit();
    }
    
    // Gerar arquivo CSV
    $usuario = $resultado['usuario'];
    $dados_relatorio = $resultado['dados'];
    
    $nome_arquivo = "relatorio_" . sanitizarNomeArquivo($usuario['nome']) . "_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // Calcular número máximo de batidas no período
    $max_batidas = 0;
    foreach ($dados_relatorio as $info) {
        $num = count($info['batidas']);
        if ($num > $max_batidas) $max_batidas = $num;
    }
    if ($max_batidas < 4) $max_batidas = 4; // Mínimo 4 colunas
    
    // Adicionar BOM UTF-8 para Excel reconhecer acentos
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho do relatório
    fputcsv($output, ['RELATÓRIO DE PONTO - ' . mb_strtoupper($usuario['nome'])], ';');
    fputcsv($output, ['Matrícula: ' . $usuario['matricula']], ';');
    fputcsv($output, ['Período: ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim))], ';');
    fputcsv($output, ['Carga Horária: ' . $usuario['carga_horaria'] . 'h'], ';');
    fputcsv($output, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($output, [], ';');
    
    // Cabeçalho da tabela dinâmico
    $header = ['Data'];
    $nomes_padrao = ['Entrada', 'Saída', 'Entrada', 'Saída'];
    for ($i = 0; $i < $max_batidas; $i++) {
        if ($i < 4) {
            $header[] = $nomes_padrao[$i];
        } else {
            //$numero = floor($i / 2) + 1;
            // Caso queira numerar as batidas extras, descomente a linha acima e adicione o variavel $numero abaixo
            $tipo = ($i % 2 == 0) ? 'Entrada ' : 'Saída ';
            $header[] = $tipo;
        }
    }
    $header[] = 'Horas Trabalhadas';
    $header[] = 'Saldo';
    $header[] = 'Observações';
    fputcsv($output, $header, ';');
    
    $total_segundos_periodo = 0;
    
    foreach ($dados_relatorio as $dia => $info) {
        // Organizar batidas em ordem cronológica
        $batidas_ordenadas = [];
        $observacoes = [];
        
        foreach ($info['batidas'] as $bt) {
            $batidas_ordenadas[] = $bt;
            
            // Verificar se tem justificativa
            if (!empty($bt['justificativas'])) {
                foreach ($bt['justificativas'] as $just) {
                    $observacoes[] = substr($bt['hora'], 0, 5) . ': ' . $just['texto_justificativa'];
                }
            }
        }
        
        $saldo = $info['total_segundos'] - ($usuario['carga_horaria'] * 3600);
        $total_segundos_periodo += $saldo;
        
        // Construir linha do CSV
        $row = [date('d/m/Y', strtotime($dia))];
        
        // Adicionar todas as batidas
        for ($i = 0; $i < $max_batidas; $i++) {
            if (isset($batidas_ordenadas[$i])) {
                $row[] = substr($batidas_ordenadas[$i]['hora'], 0, 5);
            } else {
                $row[] = '---';
            }
        }
        
        $row[] = formatarHoras($info['total_segundos']);
        $row[] = formatarHoras($saldo);
        $row[] = implode(' | ', $observacoes);
        
        fputcsv($output, $row, ';');
    }
    
    // Total do período
    fputcsv($output, [], ';');
    fputcsv($output, ['SALDO TOTAL DO PERÍODO:', '', '', '', '', '', formatarHoras($total_segundos_periodo)], ';');
    
    fclose($output);
    exit();
    
} elseif ($exportar_tipo === 'todos' && $is_admin) {
    // Exportar relatório de todos os usuários
    
    $sql_usuarios = "SELECT id, nome, matricula, carga_horaria FROM usuarios ORDER BY nome ASC";
    $stmt_usuarios = $pdo->query($sql_usuarios);
    $usuarios = $stmt_usuarios->fetchAll();
    
    $nome_arquivo = "relatorio_todos_usuarios_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho geral
    fputcsv($output, ['RELATÓRIO GERAL DE PONTOS - TODOS OS USUÁRIOS'], ';');
    fputcsv($output, ['Período: ' . date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim))], ';');
    fputcsv($output, ['Gerado em: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($output, [], ';');
    
    foreach ($usuarios as $usuario) {
        $resultado = processarDadosUsuario($pdo, $usuario['id'], $data_inicio, $data_fim);
        
        if (!$resultado || empty($resultado['dados'])) {
            continue; // Pula usuários sem registros
        }
        
        $dados_relatorio = $resultado['dados'];
        
        // Calcular número máximo de batidas para este usuário
        $max_batidas = 0;
        foreach ($dados_relatorio as $info) {
            $num = count($info['batidas']);
            if ($num > $max_batidas) $max_batidas = $num;
        }
        if ($max_batidas < 4) $max_batidas = 4;
        
        // Cabeçalho do usuário
        fputcsv($output, ['═══════════════════════════════════════════════════════════'], ';');
        fputcsv($output, ['FUNCIONÁRIO: ' . mb_strtoupper($usuario['nome'])], ';');
        fputcsv($output, ['Matrícula: ' . $usuario['matricula'] . ' | Carga Horária: ' . $usuario['carga_horaria'] . 'h'], ';');
        fputcsv($output, [], ';');
        
        // Cabeçalho da tabela dinâmico
        $header = ['Data'];
        $nomes_padrao = ['Entrada', 'Saída Almoço', 'Volta Almoço', 'Saída Final'];
        for ($i = 0; $i < $max_batidas; $i++) {
            if ($i < 4) {
                $header[] = $nomes_padrao[$i];
            } else {
                $numero = floor($i / 2) + 1;
                $tipo = ($i % 2 == 0) ? 'Entrada ' . $numero : 'Saída ' . $numero;
                $header[] = $tipo;
            }
        }
        $header[] = 'Horas Trabalhadas';
        $header[] = 'Saldo';
        $header[] = 'Observações';
        fputcsv($output, $header, ';');
        
        $total_segundos_periodo = 0;
        
        foreach ($dados_relatorio as $dia => $info) {
            // Organizar batidas em ordem cronológica
            $batidas_ordenadas = [];
            $observacoes = [];
            
            foreach ($info['batidas'] as $bt) {
                $batidas_ordenadas[] = $bt;
                
                if (!empty($bt['justificativas'])) {
                    foreach ($bt['justificativas'] as $just) {
                        $observacoes[] = substr($bt['hora'], 0, 5) . ': ' . $just['texto_justificativa'];
                    }
                }
            }
            
            $saldo = $info['total_segundos'] - ($usuario['carga_horaria'] * 3600);
            $total_segundos_periodo += $saldo;
            
            // Construir linha do CSV
            $row = [date('d/m/Y', strtotime($dia))];
            
            // Adicionar todas as batidas
            for ($i = 0; $i < $max_batidas; $i++) {
                if (isset($batidas_ordenadas[$i])) {
                    $row[] = substr($batidas_ordenadas[$i]['hora'], 0, 5);
                } else {
                    $row[] = '---';
                }
            }
            
            $row[] = formatarHoras($info['total_segundos']);
            $row[] = formatarHoras($saldo);
            $row[] = implode(' | ', $observacoes);
            
            fputcsv($output, $row, ';');
        }
        
        fputcsv($output, [], ';');
        fputcsv($output, ['SALDO TOTAL:', '', '', '', '', '', formatarHoras($total_segundos_periodo)], ';');
        fputcsv($output, [], ';');
        fputcsv($output, [], ';');
    }
    
    fclose($output);
    exit();
    
} else {
    // Tipo de exportação inválido ou sem permissão
    header("Location: ../pages/relatorio_pontos.php?mensagem=" . urlencode("Erro: Operação inválida."));
    exit();
}

// Função auxiliar para sanitizar nome de arquivo
function sanitizarNomeArquivo($nome) {
    $nome = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nome);
    return mb_strtolower($nome);
}
