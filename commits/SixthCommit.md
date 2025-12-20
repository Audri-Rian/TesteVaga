Vamos de sexto commit.

Esse aqui foi o commit onde o projeto virou “produto”. Porque nos commits anteriores eu tinha o domínio preparado e a base de infra montada, mas agora eu fechei o ciclo completo: regra de negócio funcionando, API funcionando e tela consumindo tudo.

Eu basicamente peguei o caminho inteiro e fiz ele ficar redondo tlg: domínio → aplicação → infraestrutura → API → frontend.

Primeiro passo: organizar a cabeça no domínio.

Eu comecei confirmando o que realmente manda no sistema: o Project.

A lógica é simples: se eu deixar regra espalhada em controller ou em service, vai virar bagunça do caraio e eu vou acabar permitindo coisa errada, tipo atribuir tarefa para alguém que nem é membro do projeto.

Então o Project virou o ponto central onde as regras ficam protegidas.

Foi nessa parte que eu finalizei a modelagem:

Project como a entidade principal que segura membros e tarefas.

Task como a entidade de tarefa, com status e possibilidade de atribuição.

Comment como entidade de comentário, ligada a uma tarefa.

E pra deixar as mudanças do sistema rastreáveis, quando algo importante acontece (tipo criar tarefa, atribuir tarefa, adicionar membro ou comentar), eu registro isso como “evento”. Não é porque eu vou usar agora, mas porque mais pra frente eu posso ligar notificações ou auditoria sem reescrever regra.

Depois que o domínio ficou bem amarrado, eu fui para a camada de application.

Aqui a ideia foi: a mizera do controller não pode virar “cérebro” do sistema. Controller tem que ser fino.

Então eu criei os casos de uso que orquestram tudo:

criar projeto

criar tarefa

atribuir tarefa

adicionar comentário

Esses handlers recebem dados simples, montam os objetos certinhos e chamam o Project para executar a ação do jeito correto. Se der erro, é erro de regra de negócio mesmo, não gambiarra.

Com isso pronto, eu precisei garantir que tudo isso realmente fosse salvo e recuperado certo.

Então eu finalizei a persistência.

Criei/ajustei as tabelas no banco (projeto, membros do projeto, tarefas, comentários), configurei os relacionamentos e fiz os repositórios que salvam e trazem os dados.

A ideia aqui foi sempre a mesma: o banco só obedece. Quem manda é o domínio. Eu só faço a ponte tlg.

E também deixei um repositório em memória para facilitar testes rápidos e simulação sem depender do banco toda hora.

Depois disso, eu subi isso para a API.

Aqui foi quando o sistema começou a ter “entrada e saída” de verdade.

Criei controllers, requests para validar dados e resources para padronizar a resposta pro frontend. A API toda ficou protegida por autenticação, e eu coloquei as verificações de segurança mais críticas (tipo “o cara é membro do projeto?” e “ele é autor desse comentário?”) antes de deixar fazer qualquer coisa.

Agora vem a parte que eu considero o diferencial do commit: o frontend.

Eu mantive o Inertia para navegação e páginas, mas usei Axios + Pinia para dados e estado. Por quê caraios?

Porque o Inertia resolve a parte de “troca de páginas” e experiência de SPA.
Mas o Axios + Pinia resolve a parte mais importante: consumir uma API real e manter estado entre telas sem recarregar tudo.

Aí sim eu construí as telas utilizando a IA do Sitch para a criação do desing(eu sei que não foi pedido mas eu quis fazer pq odeio desing feio):

Dashboard com resumo (projetos e tarefas)

Página de projetos (listagem e criação)

Kanban de tarefas (por status, bem visual)

Detalhe e ações (criar tarefa, atribuir, mudar status)

Seção de comentários reutilizável (com listar, adicionar e deletar)

E aqui o fluxo final ficou redondinho, tipo “de ponta a ponta” mesmo.

Exemplo prático de como eu penso esse fluxo:

Criar projeto:
Usuário clica → frontend manda POST → controller recebe e valida → handler cria o Project e adiciona o dono como membro → repositório salva → API responde certinho → store atualiza e a tela já mostra na lista.

Criar tarefa:
Usuário cria → backend pega o projeto → o Project cria a task do jeito certo → salva → devolve → o kanban já encaixa na coluna correta.

Atribuir tarefa:
Usuário escolhe membro → backend verifica se o membro faz parte do projeto → se não for, dá erro de regra → se for, atribui e salva → frontend atualiza na hora.


E vamos de proximo commmit nessa mizera.