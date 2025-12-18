Vamos de quarto commit. 

Dei uma pausa pro almoço antes de continuar, pois é bastante coisa nesse commit.

Vamos recapitular a logica, no meu commit passado eu meio que "Construi" o sistema, preparei o terreno para pensar nos dominios e as funcionalidades.

Agora o ideal é fazer a implementação do comportamento do domínio (as regras), que por consequencia habilita as funcionalidades.

Baseado na documentação, pelo que eu entendi, a regra-chave é aonde somente o membro do projeto pode ser atribuído a uma tarefa. 

Então vamos pensar em como montar isso. 

Como teremos como 3 "classes" principais de Project + membros + task. Logo o a entidade principal do projeto é o Aggregate Root, que no caso é o Project. Então a logica de negocio será montada em volta do que a entidade principal pode ou não fazer.

Então o que carai o Project pode fazer?

Vamos pensar como um UX profissional, o usuário clica em atribuir tarefa, seleciona um membro e depois clica em salvar.

Dessa forma nos temos que raciocinar de tal maneira que, vamos fazer uma requisição para atribuir um membro a uma tarefa.

1- Interface para atribuir membro a tarefa (Vue.js, ainda não fiz comunicação com o backend mas ja estou pensando em como funcionara)
2- Requisição no controller que vai receber os dados, validar e se comunicar com a camada de application
3- Orquestrar os objetos do domínio (Project + Member + Task) pelo handler aonde vai busca o projeto/tarefa no repositorio e depois pedir para o dominio executar a ação.
4- Agora o dominio entra em ação chamando e utilizando a regra dentro do project.
   - O Domain Project vai verificar se o UserID está na minha lista de membros, se não estiver lança uma exceção de domínio (\DomainException) com a mensagem "User must be a member...".
   - Se estiver ele terá que localizar a task, manda a tarefa se atribuir ao usuário e registra um evento: TaskAssigned.
5- Se tudo der certo, o handler vai  receber o dominio e ja é resolvido e tudo certo.

RESUMINDO TUDO: Interface -> Controller -> Application -> Domain -> Application -> Controller -> Interface