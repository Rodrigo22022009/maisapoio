# Guião de apresentação — +Apoio

**Duração sugerida:** 8 a 10 minutos  
**Apresentação:** PAP-Cuida-Plus-atualizado-v2.pptx

## Antes de começar

- Confirmar que o PowerPoint está aberto no primeiro diapositivo.
- Falar para o público e usar os diapositivos apenas como apoio visual.
- Não ler todos os textos do ecrã; explicar os pontos principais com exemplos.

## Diapositivo 1 — Capa

> Bom dia/boa tarde. O meu projeto chama-se **+Apoio** e consiste numa plataforma de apoio domiciliário.
>
> A ideia surgiu da necessidade de organizar melhor a informação sobre beneficiários, médicos voluntários e visitas domiciliárias.
>
> Durante esta apresentação vou explicar o problema, a solução desenvolvida, as tecnologias utilizadas e o funcionamento da aplicação.

## Diapositivo 2 — O desafio

> O apoio domiciliário envolve várias pessoas e muita informação: contactos, moradas, necessidades, médicos, horários e estado das visitas.
>
> Quando estes dados estão dispersos em papel ou em ficheiros diferentes, torna-se mais difícil encontrar rapidamente a informação certa.
>
> O +Apoio pretende resolver este problema através de uma plataforma centralizada, simples e visual.

**Transição:**

> Depois de identificar este desafio, defini os principais objetivos do projeto.

## Diapositivo 3 — Objetivos do projeto

> Os objetivos foram centralizar os dados, permitir criar, editar, pesquisar e eliminar registos e acompanhar os apoios agendados.
>
> Outro objetivo importante foi facilitar as deslocações dos profissionais através da navegação GPS.
>
> A aplicação foi pensada como um protótipo funcional, mas também como uma base para uma solução real com autenticação, permissões e maior segurança.

## Diapositivo 4 — Funcionalidades principais

> A aplicação está organizada em várias áreas.
>
> O dashboard apresenta um resumo dos indicadores. A área de beneficiários guarda os dados pessoais, a morada e as necessidades de cada pessoa.
>
> Também existe uma área para médicos voluntários, uma agenda de apoios, pesquisa, relatórios e navegação GPS.
>
> Desta forma, cada utilizador consegue consultar a informação necessária sem percorrer vários ficheiros ou sistemas.

## Diapositivo 5 — Gestão de dados

> Inicialmente, a aplicação funcionava apenas como uma demonstração local.
>
> Depois foi criada uma arquitetura com uma interface em HTML, CSS e JavaScript, um servidor PHP e uma base de dados SQLite.
>
> A interface envia os pedidos para a API PHP, que valida e guarda os dados na base de dados.
>
> O SQLite foi escolhido por ser leve, gratuito e adequado à fase de protótipo deste projeto.

## Diapositivo 6 — Sistema de navegação GPS

> Uma das funcionalidades práticas é a navegação GPS.
>
> O médico seleciona o beneficiário, a aplicação tenta obter a localização atual do dispositivo e abre uma rota no Google Maps.
>
> Mesmo que o utilizador não autorize a localização, a rota pode continuar a ser criada utilizando a morada registada do beneficiário.
>
> Esta funcionalidade pode poupar tempo e reduzir erros durante as visitas.

## Diapositivo 7 — Tecnologias utilizadas

> As principais tecnologias utilizadas foram HTML para a estrutura, CSS e Bootstrap para o aspeto visual e JavaScript para as interações.
>
> No lado do servidor foi utilizado PHP, com PDO para comunicar com a base de dados SQLite.
>
> A escolha destas tecnologias permitiu criar uma aplicação simples, responsiva e relativamente fácil de manter.

## Diapositivo 8 — Como funciona

> O funcionamento pode ser resumido neste fluxo: o utilizador interage com a interface, a interface comunica com a API PHP e a API consulta ou altera os dados no SQLite.
>
> Esta separação torna o projeto mais organizado e permite, no futuro, substituir ou expandir cada parte sem reconstruir toda a aplicação.
>
> A aplicação também mantém uma cópia local de segurança durante o funcionamento do protótipo.

## Diapositivo 9 — Demonstração da aplicação

> Neste diapositivo vemos a interface real da aplicação.
>
> O fluxo principal começa no dashboard, onde é possível consultar os indicadores. Depois podemos pesquisar um beneficiário, consultar as suas necessidades, agendar um apoio e atualizar o estado da visita.
>
> Também podemos abrir a rota GPS e consultar relatórios ou exportar os dados.
>
> O objetivo é que o percurso seja simples: consultar, decidir, registar e acompanhar.

**Dica:** apontar para a imagem enquanto se explica o fluxo, sem tentar descrever todos os elementos pequenos do ecrã.

## Diapositivo 10 — Para que serve o +Apoio?

> Este diapositivo resume a utilização da aplicação através de três ecrãs.
>
> Primeiro, o utilizador entra na plataforma. Depois autentica-se e, caso ainda não tenha conta, pode fazer o seu registo.
>
> O +Apoio serve para aproximar beneficiários, médicos voluntários e coordenação, mantendo os pedidos e acompanhamentos organizados.
>
> Cada perfil tem funções diferentes: o beneficiário pode pedir apoio, o médico pode acompanhar visitas e a coordenação pode gerir a informação global.
>
> Assim, a aplicação não é apenas um formulário: é um ponto central para organizar todo o processo de apoio domiciliário.

## Diapositivo 11 — Conclusão e evolução

> Em conclusão, foi desenvolvido um protótipo funcional com gestão de informação, ligação a uma base de dados, agenda de apoios e navegação GPS.
>
> Como próximos passos, seria importante acrescentar autenticação mais completa, perfis e permissões detalhadas, auditoria de acessos e cópias de segurança automáticas.
>
> Também seria necessário cumprir todos os requisitos de proteção de dados, principalmente porque podem existir informações pessoais e de saúde.
>
> O +Apoio mostra como uma solução digital simples pode ajudar a organizar melhor o trabalho de quem presta apoio domiciliário.
>
> Obrigado pela atenção. Estou disponível para responder às perguntas.

## Perguntas prováveis

### Porque foi escolhido o SQLite?

> Porque é uma base de dados leve, gratuita e suficiente para um protótipo. Numa versão maior, poderia ser substituída por MySQL ou PostgreSQL.

### A aplicação já está pronta para dados reais?

> Ainda não. Antes de utilizar dados reais, seria necessário implementar autenticação segura, permissões, HTTPS, cópias de segurança e medidas de proteção de dados.

### O que acontece se o GPS for recusado?

> A aplicação continua a utilizar a morada registada do beneficiário para abrir a rota no Google Maps.

### Qual foi a maior dificuldade?

> A maior dificuldade foi ligar a interface, a API PHP e a base de dados de forma consistente, garantindo que os registos criados ou alterados ficam disponíveis corretamente.

### O que melhoraria no futuro?

> Melhoraria a segurança, criaria notificações, acrescentaria uma aplicação móvel e permitiria acompanhar o histórico completo de cada apoio.
