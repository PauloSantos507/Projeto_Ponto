# Funcionalidade de Exportação de Relatórios

## 📋 Visão Geral

Sistema de exportação de relatórios de ponto em formato CSV, implementado em 21/01/2026.

## ✨ Funcionalidades

### Para Todos os Usuários:
- **Exportar Meu Relatório**: Exporta os registros de ponto do próprio usuário no período selecionado

### Para Administradores:
- **Exportar Relatório do Usuário**: Exporta os registros de ponto de um usuário específico selecionado
- **Exportar Relatório de TODOS os Usuários**: Exporta um arquivo consolidado com todos os usuários do sistema

## 📊 Formato do Arquivo

### Arquivo CSV com:
- Codificação UTF-8 com BOM (compatível com Excel)
- Separador: ponto e vírgula (`;`)
- Extensão: `.csv`

### Estrutura do Relatório Individual:
```
RELATÓRIO DE PONTO - NOME DO FUNCIONÁRIO
Matrícula: XXXX
Período: DD/MM/YYYY a DD/MM/YYYY
Carga Horária: Xh
Gerado em: DD/MM/YYYY HH:MM:SS

Data | Entrada | Saída Almoço | Volta Almoço | Saída Final | Horas Trabalhadas | Saldo | Observações
...dados...

SALDO TOTAL DO PERÍODO: ±XXh XXm
```

### Estrutura do Relatório Consolidado:
- Todos os usuários em um único arquivo
- Separados por seções com divisórias visuais
- Saldo individual de cada funcionário

## 🔒 Segurança

### Proteções Implementadas:
1. ✅ Verificação de sessão ativa obrigatória
2. ✅ Validação de permissões (admin vs usuário comum)
3. ✅ Bloqueio de acesso via GET (apenas POST permitido)
4. ✅ Sanitização de nomes de arquivo
5. ✅ Usuários comuns só podem exportar seus próprios dados

### Controle de Acesso:
- **Usuário comum**: Pode exportar apenas seus próprios registros
- **Administrador**: Pode exportar qualquer usuário ou todos

## 🎯 Como Usar

1. Acesse a página de Relatórios (`relatorio_pontos.php`)
2. Selecione o funcionário (admin) e o período desejado
3. Clique em **Filtrar** para visualizar os dados
4. Escolha uma das opções de exportação:
   - 📥 **Exportar Relatório do Usuário**: Gera CSV do usuário selecionado
   - 📦 **Exportar Relatório de TODOS**: Gera CSV consolidado (somente admin)

## 📝 Informações Exportadas

### Dados Incluídos:
- Data de cada registro
- Horários de entrada e saída
- Horas trabalhadas por dia
- Saldo diário (diferença entre trabalhado e carga horária)
- Observações (justificativas de edições)
- Saldo total do período

### Justificativas:
- Horários editados são marcados nas observações
- Formato: `HH:MM: Texto da justificativa`
- Múltiplas justificativas separadas por ` | `

## 🗂️ Nomenclatura dos Arquivos

### Relatório Individual:
`relatorio_[nome_usuario]_YYYY-MM-DD.csv`

Exemplo: `relatorio_joao_silva_2026-01-21.csv`

### Relatório Consolidado:
`relatorio_todos_usuarios_YYYY-MM-DD.csv`

Exemplo: `relatorio_todos_usuarios_2026-01-21.csv`

## 🛠️ Arquivos Envolvidos

### Backend:
- `includes/exportar_relatorio.php` - Processamento e geração dos arquivos CSV

### Frontend:
- `pages/relatorio_pontos.php` - Interface com botões de exportação

## ⚙️ Processamento

### Fluxo de Exportação:
1. Usuário clica no botão de exportação
2. Dados são enviados via POST para `exportar_relatorio.php`
3. Sistema valida permissões e sessão
4. Consulta banco de dados (registros + justificativas)
5. Processa e organiza dados por dia
6. Calcula saldos e totalizadores
7. Gera arquivo CSV com headers apropriados
8. Força download no navegador

### Cálculos Realizados:
- ✅ Total de horas trabalhadas por dia
- ✅ Saldo diário (trabalhado - carga horária)
- ✅ Saldo acumulado do período
- ✅ Formatação em HH:MM

## 📌 Observações Importantes

- Os arquivos são gerados dinamicamente e não são salvos no servidor
- O download inicia automaticamente após o processamento
- Compatível com Excel, LibreOffice Calc, Google Sheets
- Acentos e caracteres especiais preservados (UTF-8 BOM)
- Registros sem batidas são ignorados no relatório consolidado

## 🔄 Atualizações Futuras Possíveis

- [ ] Exportação em formato PDF
- [ ] Exportação em formato Excel (.xlsx)
- [ ] Gráficos e dashboards visuais
- [ ] Envio automático por e-mail
- [ ] Agendamento de relatórios periódicos
