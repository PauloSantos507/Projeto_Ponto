## :page_with_curl: Documentação Ferramenta de Registro de Pontos.

### 1. Visão Geral 

Este sistema foi progetado para gerenciar o registro de jornada de trabalho dos colaboradores, permitindo controle de entradas, saídas, alteração de pontos e justificativas. O foco é na integridade de dados, utilizando uma estrutura segura, que permite justificativas acumulativas que impede a perda de histórico.

### 2. Requisitos de Ambiente

- Linguagem: PHP (Versão 7.4 ou superior)
- Banco de Dados: Mysql / MariaDb
- Extensões PHP: PDO, PDO_MYSQL

### 3. Arquitetura de Dados

#### :file_folder: Arquitetura de Pastas

```text
/projeto
├── config/      # Arquivos de conexão com o banco de dados
├── includes/    # Lógicas de processamento (PHP puro)
└── pages/       # Interfaces do usuário (HTML/JS/CSS/PHP)
```

#### :: Estrutura do Banco de Dados

O banco de dados é constituido por 3 tabelas principais,

- "usuarios": Guarda as informações dos usuários cadastrados.
A tabela é constituida por 7 colunas principais:
    - id: Chave Primária, id do usuário;
    - matricula: Matricula utilizada para registrar o ponto do usuário;
    - nome: nome do perfil do usuário;
    - e-mail: Guarda o e-mail do usuário;
    - senha: Guarda a senha em Hash, criptografada dentro do banco
    - perfil: Define 1 para administrador e 0 para usuário padrão.
    - carga_horaria: Guarda a carga horária do usário.

- "registros_ponto": Guarda as informações dos registros de ponto realizados.
A tabela é constituida por 7 colunas principais:
    - id: Chave Primária, id do usuário;
    - matricula: Matricula utilizada para registrar o ponto do usuário;
    - nome: nome do perfil do usuário;
    - e-mail: Guarda o e-mail do usuário;
    - senha: Guarda a senha em Hash, criptografada dentro do banco
    - perfil: Define 1 para administrador e 0 para usuário padrão.
    - carga_horaria: Guarda a carga horária do usário.

