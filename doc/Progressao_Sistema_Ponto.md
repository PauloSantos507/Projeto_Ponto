# Realização Sistema Ponto

### Etapas Concluídas:

1. Criação da Página de Login

2. Criação da Página de Registro de Ponto
    - Registro inteligente de entrada/saída

3. Criação da Página de Gerenciamento de usuário
    - Dentro da Página: 
        - Visualização de Usuários do Sistema
        - Cadastro de novos usuários
        - Edição de usuários

4. Criação da página para gerenciamento do prório Perfil;

5. Criação de Página de Relatório:
    - Admin: 
        - Pode acessar o relatório de todos os usuários, e verificar os registros de pontos.
        - Pode editar o Registro de Pontos de usuários, adicionando uma justificativa.
    
    - Usuário Padrão:
        - Consegue ver os Pontos registrados em seu perfil.

6. Campo para Logoff do usuário.

7. Elaboração do Banco de dados.
    - Criação de duas tabelas:
    1. Tabela "usuarios":
        - Guarda informações de registro de usuários (id, senha, e-mail, matricula, tipo de usuário, carga horária)
    2. Tabela "registros_ponto"
        - Guarda informações relacionado ao registro de pontos (tipo de batida, data de registro, horário de registro, justificativa, id)

### Etapas Restantes:

1. Criação da tabela "Justificativas" e configuração no código.
    - No momento, as justificativas são preenchidas em uma coluna dentro da tabela de registros_ponto, no banco de dados. A ideia é criar uma tebela separada para as justificativas, para evitar sobreposição. No momento, as justificativas se sobrescrevem se alteradas no mesmo ponto.
    Da mesma forma, será necessário reconfigurar o código para que possa guardar e receber as informações dessa tabela.

2. Esconder o Registro de Pontos após novo registro.
    - A ideia é que ao se registrar um ponto, os campos contendo o login para registro do ponto, fiquem escondidas, mostrando apenas uma frase mostrando que o registro de pontos foi feito com sucesso. Esse processo evitará que pontos sejam registrados mais de uma vez por acidente.



