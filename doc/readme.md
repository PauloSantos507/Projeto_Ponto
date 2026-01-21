## :page_with_curl: Documentação Ferramenta de Registro de Pontos.

### 1. Visão Geral 

Este sistema foi projetado para gerenciar o registro de jornada de trabalho dos colaboradores, permitindo controle de entradas, saídas, alteração de pontos e justificativas. O foco é na integridade de dados, utilizando uma estrutura segura, que permite justificativas acumulativas que impede a perda de histórico.

### 2. Requisitos de Ambiente

- Linguagem: PHP (Versão 7.4 ou superior)
- Banco de Dados: MySQL / MariaDb
- Extensões PHP: PDO, PDO_MYSQL

### 3. Arquitetura de Dados

#### :file_folder: Arquitetura de Pastas

```text
/projeto
├── config/      # Arquivos de conexão com o banco de dados
├── includes/    # Lógicas de processamento (PHP puro)
└── pages/       # Interfaces do usuário (HTML/JS/CSS/PHP)
```

#### :page_with_curl: Estrutura do Banco de Dados

O sistema utiliza três tabelas principais interligadas:

- usuarios: Armazena dados de acesso, matrícula e carga horária contratual.

- registros_ponto: Armazena as batidas brutas (data e hora) vinculadas ao usuário.

- justificativas: Tabela de auditoria que armazena notas inseridas pelo administrador para cada ponto específico.

### Criação Banco de Dados:

Para criar o banco de dados, foram utilizados os seguintes comandos `SQL`:

#### Para a criação da Tabela **"usuarios"**: 

``` sql 
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` tinyint(1) DEFAULT 0,
  `carga_horaria` int(11) DEFAULT 8,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```
#### Lógica:

- Identificação Dupla: O sistema utiliza o email para autenticação de login e a matricula como identificador único para o registro de ponto.

- Controle de Acesso: O campo perfil define as permissões (Admin vs. Usuário), enquanto a carga_horaria serve como base de cálculo para o saldo de horas no relatório.

#### Para a criação da Tabela **"registros_ponto"**: 

``` sql 
CREATE TABLE `registros_ponto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `data_registro` date NOT NULL,
  `hora_registro` time NOT NULL,
  `tipo_batida` enum('entrada','saida') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `registros_ponto_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
#### Lógica:

- Rastreabilidade: Cada registro é vinculado ao id_usuario, garantindo que os pontos pertençam ao colaborador correto.

- Integridade: O uso de ENUM para tipo_batida força o sistema a aceitar apenas "entrada" ou "saida", evitando erros de processamento no cálculo das horas.

- Edição Direta: O campo justificativa nesta tabela armazena o motivo de uma alteração direta no horário feita pelo administrador.


#### Para a criação da Tabela **"justificativas"**: 

``` sql 
CREATE TABLE `justificativas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_ponto` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `texto_justificativa` text NOT NULL,
  `data_hora_criacao` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_ponto` (`id_ponto`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `justificativas_ibfk_1` FOREIGN KEY (`id_ponto`) REFERENCES `registros_ponto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `justificativas_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Lógica:

- Esta tabela permite "N" comentários para um único ponto. Isso impede que uma explicação sobrescreva a outra.

- Responsabilidade de Auditoria: O vínculo com id_admin registra quem foi o responsável por cada nota inserida, garantindo transparência total nos ajustes de folha de ponto.

### 4. Fluxo de Funcionamento:

Esta seçao detalha o ciclo de vida dos dados dentro do sistema de pontos e a interação do usuário com as principais funcionalidades do sistema.

#### 4.1. Registro de Jornada (Batida de Ponto)

O fluxo de registro é projetado para ser intuitivo, minimizando erros de entrada por parte do colaborador.

- **Entrada de Dados:** O usuário acessa a interface de registro e informa seu e-mail e senha para acessar o sistema:

- **Identificação:** Internamente, o sistema vincula o acesso à matrícula única do funcionário.

- **Lógica de Registro:** O sistema realiza uma consulta ao banco de dados para verificar o último registro do colaborador no dia atual. Se o último registro for "entrada", o próximo será automaticamente "saída", e vice-versa.

- **Armazenamento:** Os dados de data, hora e tipo de batida são armazenados na tabela registros_ponto.

#### 4.2. Processamento do Relatório e Saldo

O relatório de pontos é gerado com base nos registros armazenados, considerando carga horária contratual e justificativas.

- **Pareamento de Horários:** O sistema agrupa os registros de entrada e saída para calcular o total de horas trabalhadas por dia.

- **Cálculo de Horas Trabalhadas:** O cálculo é realizado pela soma dos intervalos entre os pares:

- **Gestão de Saldo:** O total de horas trabalhadas é subtraído da carga horária diária cadastrada para o usuário (ex: 8 horas), resultando em saldo positivo (extra) ou negativo (atraso/falta).

#### 4.3. Justificativas e Auditoria

O sistema de justificativas é uma funcionalidade crítica para manter a integridade dos registros de ponto. Para que um ponto seja alterado/editado, o administrador deve inserir uma justificativa.

- **Inclusão de notas:** Ao editar um ponto, o administrador é solicitado a fornecer uma justificativa detalhada.

- **Rastreabilidade:** Cada justificativa é vinculada ao ponto específico e ao administrador que a inseriu, garantindo um histórico completo de alterações.

- **Persistência Acumulativa:** Cada justificativa é armazenada na tabela justificativas, permitindo múltiplas notas para um único ponto sem sobrescrever informações anteriores.

### :interrobang:5. Explicação dos Arquivos Principais:

#### 5.1. Conexão com o Banco de dados (config/conexao.php)
O arquivo conexao.php estabelece a conexão com o banco de dados utilizando PDO, garantindo segurança e eficiência nas operações de banco de dados.
  A conexão com o banco é feita através das seguintes variáveis:
  ```php
    $host = 'localhost';
    $db = 'sistema_ponto';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
```
O arquivo utiliza um bloco try-catch para capturar possíveis erros de conexão e exibir em log.

#### 5.2. Login e Autenticação (pageslogin.php)

O usuário acessa a página de Login, onde insere suas credenciais (e-mail e senha). O sistema valida as informações contra a tabela de usuário e inicia uma sessão segura, mantendo o estado de autenticação do usuário.

O usuário é direcionado para a página principal, onde pode registar ponto, visualizar relatórios e, se for Administrador, gerenciar usuários e editar pontos.

#### 5.3. Registro de Ponto (pages/registrar_ponto.php)

- **Interface:** O usuário insere matrícula e senha para registrar sua batida de ponto.
- **Processamento(Back):** 
  1. O sistema valida a matrícula e senha (criptografada com `password_hash`);
  2. Verifica a última batida registrada na tabela, se o último registro for "entrada", o próximo será "saída" e vice-versa;
  3. Insere o novo registro na tabela registros_ponto com data, hora e tipo de batida. Através de um comando `date_default_timezone_set('America/Sao_Paulo')` para garantir o fuso horário correto.
  4. Prevenção de erros: O sistema oculta os campos de entrada de dados, e exibe uma tela, com um cronometro regressivo, para evitar múltiplos cliques acidentais.

#### 5.4. Relatório de Pontos (pages/relatorio.php)
- **Interface:** O usuário administrador possúi privilegios para filtrar por outros funcionários, filtrando por data inicial e final. Dessa forma um relatório completo dos pontos registrados é exibido.
- **Processamento(Back):**
  1. O sistema consulta a tabela registros_ponto para obter todos os registros do usuário no período selecionado;
  2. Calcula as horas trabalhadas diárias, comparando com a carga horária contratual do usuário;
  3. Exibe o saldo total de horas (positivas ou negativas) no período.

#### 5.5. Edição de Pontos e Justificativas.
-  **Interface:** O administrador pode selecionar qualquer ponto, e clicar no lápis que se encontra ao lado do ponto. Uma janela em tooltip é exibida, onde o administrador pode alterar o horário do ponto, inserir uma justificativa e salvar as alterações.
- **Processamento(Back):** 
  1. As consultas no banco de dados são feitas através de um comando SQL `JOIN`, que une as tabelas "registros_ponto" e "justificativas", permitindo exibir os pontos junto com suas respectivas justificativas.
  2. O sistema consulta o banco de dados, validando se o usuário logado é administrador.
  3. Se o usuário for administrador, o sistema permite a edição de ponto do usuário selecionado, se não for, ele mostra apenas o relatório do usuário logado.
  4. Ao clicar no botão de edição, e preencher o novo horário com a justificativa, o sistema atualiza a tabela "registros_ponto" e a tabela "justificativas" com as novas informações.
  5. O sistema mantém o histórico completo de justificativas, vinculando cada justificativa ao ponto editado e ao administrador que realizou a alteração. Dentro do Banco de dados, as justificativas são relacionadas ao usuário que as fez, através da coluna de "id_admin", e ao ponto alterado através da coluna "id_ponto". 

#### 5.6 Gerenciamento de Usuários
- **Interface:** O administrador pode selecionar o campo "Gerenciar Usuários" no menu superior. Ele será direcionado a uma página, onde pode verificar as informações de todos os usuários cadastrados (ID, Nome, E-mail, Matrícula, Perfil e Carga Horária). O administrador pode adicionar novos usuários, clicando no botão "Cadastrar Novo Usuário", ou editar usuários existentes, clicando no ícone de lápis ao lado do usuário desejado; O administrador será enviado a uma página de edição, onde poderá alterar as informações do usuário selecionado, ou criar um novo usuário, a depender da opção escolhida.
- **Processamento(Back):**
  1. O sistema consulta a tabela "usuários" trazendo todas as informações dos usuários cadastrados e alocando dentro da página.
  2. Ao clicar no botão "Cadastrar Novo Usuário", preencher os dados e clicar em "salvar", o sistema, através de um comando `SQL INSERT`, presente no arquivo "criar_usuario.php", insere os dados na tabela "usuários".
  3. Ao clicar no ícone de lápis ao lado do usuário desejado, preencher os dados solicitados e clicar em "salvar", o sistema através do comando `SQL UPDATE`, faz a atualização dos dados na tabela "usuários".

#### 5.7. Logout 
- **Interface:** O usuário, dentro do Menu de registro de Ponto, clica na opção "Sair", que o redireciona para a página de Login.
- **Processamento(Back):** O sistema, através do arquivo "encerrar_sessao.php", encerra a sessão do usuário que se encontra logado, destruindo todas as variáveis de sessão e redirecionando o usuário para a página de Login, através da instrução `header("Location: login.php")`.

### 6. Utilização da Aplicação
#### 6.1. Passo a Passo para registro de ponto, visualização de relatórios e edição de pontos:

1. Acesse a página de Login.
![Login](/Imagens/Tela_login.png)
2. Preencha suas credenciais e clique em "Entrar".
3. Na página Principal, preencha sua matrícula e senha e clique em "Registrar Ponto".
![Registro_Ponto](/Imagens/Registro_ponto.png)
4. O ponto será registrado, e uma mensagem de confirmação será exibida.
![Confirmação_Ponto](/Imagens/Mensagem_registro.png)
5. Após o timer de 30 segundos, será possível registrar o ponto novamente.
6. É possível acessar o relatório de Pontos, clicando na opção "Ver Relatórios" no menu superior.
7. Na nova tela, selecione o período desejado e clique em "Filtrar". Um relatório contendo os pontos registrados será exibido.
![Relatório_Pontos](/Imagens/Relatorio_pontos.png)
8. Se você for um administrador, poderá filtrar o usuário e editar pontos clicando no ícone de lápis ao lado do ponto desejado.
![Edição_Pontos](/Imagens/Editar_ponto.png)
9. Insira o novo horário, a justificativa e clique em "Salvar Alteração".
![Salvar_Alteração](/Imagens/Edicao_salva.png)
10. A alteração será salva, e a justificativa será registrada no sistema. As justificativas pode ser visualizada ao passar o mouse por cima do ponto que estará evidenciado em amarelo.
![Justificativa_Pontos](/Imagens/Mostrar_justificativa.png)
11. Para exportar o relatório, clique no botão "Exportar Relatório do Usuário" para gerar um arquivo CSV do usuário selecionado, ou "Exportar Relatório de TODOS os Usuários" para gerar um arquivo CSV consolidado (somente para administradores).
![Exportar_Relatório](/Imagens/Exportar_relatorio.png)

#### 6.2. Passo a Passo para Gerenciamento e Criação de Usuários pelo Administrados:
1. Acesse a página de Login.
2. Preencha suas credenciais de administrador e faça login.
3. No menu superior, clique na opção "Gerenciar Usuários".
![Gerenciar_Usuários](/Imagens/Gerenciar_usuarios.png)
4. Na nova tela, será possível visualizar todos os usuários, assim como suas informações (ID, Nome, E-mail, Matrícula, Perfil e Carga Horária).
5. Para criar um novo usuário, clique no botão "Cadastrar Novo Usuário".
![Cadastrar_Novo_Usuário](/Imagens/Tela_usuarios.png)
6. Preencha os dados solicitados para a criação e clique em "Finalizar Cadastro".
![Finalizar_Cadastro](/Imagens/Cadastro_usuario.png)
7. O novo usuário será criado e adicionado à lista de usuários.
8. Para editar um usuário existente, clique na opção ":pencil2:Editar" ao Lado do usuário desejado.
9. Altere as informações conforme necessário e clique em "Salvar Alterações".
![Salvar_Alterações_Usuário](/Imagens/Editar_usuario.png).
10. As alterações serão salvas e refletidas na lista de usuários.

### 7. Considerações Finais

Este sistema de registro de pontos foi desenvolvido com foco na segurança, integridade dos dados e facilidade de uso. A estrutura de banco de dados foi cuidadosamente projetada para permitir rastreabilidade completa das alterações, garantindo que todas as justificativas sejam armazenadas de forma acumulativa. A interface do usuário é intuitiva, facilitando o registro de pontos e a gestão administrativa. Com essas funcionalidades, o sistema atende às necessidades de controle de jornada de trabalho de forma eficiente e confiável.













