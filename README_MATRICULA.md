# 📋 Sistema de Ponto - Guia de Atualização da Matrícula

## ⚠️ IMPORTANTE: Execute na ordem abaixo

### Passo 1: Adicionar coluna matrícula
Execute o arquivo SQL no phpMyAdmin:
- Arquivo: `db/adicionar_matricula.sql`
- Banco: `sistema_ponto`

### Passo 2: Atualizar usuários existentes (se houver)
Se você já tem usuários cadastrados, execute:

```sql
-- Gerar matrículas automáticas baseadas no ID
UPDATE usuarios SET matricula = CONCAT('USR', LPAD(id, 4, '0')) WHERE matricula IS NULL;

-- Exemplo de resultado:
-- ID 1 = USR0001
-- ID 2 = USR0002
-- ID 15 = USR0015
```

### Passo 3: Inserir usuário administrador
Execute o arquivo SQL:
- Arquivo: `db/inserir_admin_com_matricula.sql`
- Banco: `sistema_ponto`

## 🎯 Como Usar o Sistema Atualizado

### Login Administrativo (Gerenciamento)
**URL:** `http://localhost/Projeto_Ponto/pages/login.php`
- **E-mail:** admin@sistema.com
- **Senha:** admin123
- **Acesso:** Gerenciar usuários, ver relatórios, configurações

### Registro de Ponto (Funcionários)
**URL:** `http://localhost/Projeto_Ponto/pages/bater_ponto.php`
- **Matrícula:** ADMIN001 (ou a matrícula do funcionário)
- **Senha:** admin123
- **Acesso:** Registrar entrada/saída

## 📝 Mudanças Implementadas

1. ✅ Campo **matrícula** adicionado ao banco de dados
2. ✅ **bater_ponto.php** agora usa matrícula ao invés de e-mail
3. ✅ **login.php** continua usando e-mail (para admins)
4. ✅ Novo arquivo **registrar_ponto.php** para processar registros
5. ✅ **autenticador.php** mantido para login administrativo
6. ✅ Tela de edição permite alterar matrícula
7. ✅ Tela de gerenciamento exibe matrícula dos usuários
8. ✅ Validação de matrícula duplicada

## 🔄 Fluxo do Sistema

### Funcionário
1. Acessa `bater_ponto.php`
2. Digita matrícula + senha
3. Sistema registra ponto de entrada/saída

### Administrador
1. Acessa `login.php`
2. Digita e-mail + senha
3. Acessa painel administrativo
4. Pode também registrar ponto usando sua matrícula

## 🛠️ Arquivos Modificados

- ✏️ `pages/bater_ponto.php` - Usa matrícula
- ✏️ `pages/criar_usuario.php` - Adiciona campo matrícula
- ✏️ `pages/edita_usuario.php` - Permite editar matrícula
- ✏️ `pages/gerenciar_usuarios.php` - Exibe matrícula
- ✏️ `includes/criar_usuario.php` - Salva matrícula
- ✏️ `includes/processar_editar_usuario.php` - Atualiza matrícula
- 🆕 `includes/registrar_ponto.php` - Processa registro por matrícula
- 🆕 `db/adicionar_matricula.sql` - Script para adicionar coluna
- 🆕 `db/inserir_admin_com_matricula.sql` - Inserir admin com matrícula
