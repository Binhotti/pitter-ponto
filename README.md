# Pitter Ponto

Sistema de registro de ponto em PHP + MySQL/MariaDB para uso interno da Pitter Pan Festas.

## O que já vem pronto

- Login e cadastro de colaboradores
- Usuário administrador
- Dashboard no visual azul/vermelho/amarelo da Pitter Pan
- Status atual da jornada
- Registro de entrada
- Início do almoço
- Volta do almoço
- Finalização do expediente
- Bloqueio da ordem incorreta das marcações
- Horas trabalhadas no dia
- Hora extra em dias úteis
- Sábados e domingos contabilizados como extra 100%
- Resumo do mês
- Gráfico semanal
- Histórico
- Calendário
- Perfil
- Lista de funcionários para administrador
- Configurações de adicionais
- Banco preparado para histórico de ajustes manuais

## Requisitos

- XAMPP
- PHP 8.1 ou superior
- MySQL/MariaDB
- Apache

## Instalação

1. Copie a pasta `pitter-ponto` para:

   `C:\xampp\htdocs\`

2. Abra o XAMPP e inicie:

   - Apache
   - MySQL

3. Abra o phpMyAdmin:

   `http://localhost/phpmyadmin`

4. Clique em **Importar** e importe:

   `database/schema.sql`

   O arquivo cria automaticamente o banco `pitter_ponto`.

5. Opcionalmente, copie `.env.example` para `.env`.

   Com o XAMPP padrão não é necessário alterar nada.

6. Abra:

   `http://localhost/pitter-ponto/public`

## Login inicial

- E-mail: `admin@pitterpan.com`
- Senha: `123456`

## Estrutura

```text
pitter-ponto/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
├── database/
│   └── schema.sql
├── public/
│   ├── assets/
│   └── index.php
├── routes/
├── .env.example
├── bootstrap.php
└── README.md
```

## Regras atuais

- Jornada padrão do usuário administrador: 8h48 por dia.
- Em segunda a sexta:
  - até a jornada diária = horas normais
  - acima da jornada = horas extras
- Sábado = todas as horas como 100%
- Domingo = todas as horas como 100%

Essas regras podem ser ajustadas na próxima etapa do projeto.

## Importante

A primeira versão é voltada para uso pessoal/interno. Para uso oficial como sistema trabalhista de registro eletrônico de ponto, há requisitos legais adicionais que ainda não foram implementados.

## Próximas melhorias sugeridas

- Tema claro/escuro
- Correção manual de ponto com justificativa
- Aprovação das correções pelo administrador
- Feriados
- Relatórios PDF/Excel
- Cálculo financeiro de horas extras
- Fechamento mensal
- Banco de horas positivo/negativo
- Notificações de ponto esquecido
- Dashboard administrativo completo


## Configuração do MySQL

Este projeto já está configurado para usar a porta **4406** no MySQL/MariaDB.


## Patch 001

Se você já importou a primeira versão do banco, importe também:

`database/patch_001_limpar_demo_e_jornada.sql`

Esse patch remove os pontos fictícios de demonstração e configura a jornada padrão como:

- Entrada: 08:00
- Saída: 17:48
- Intervalo de almoço: 60 minutos

O patch não apaga os usuários.


## Regras de cálculo do banco de horas — v1.2

A versão atual usa estas regras:

- Segunda a sexta: jornada esperada de **8h48 por dia**.
- Horário padrão: **08:00 às 17:48**, com **60 minutos de almoço**.
- Tolerância padrão: **5 minutos**.
- O banco de horas só é consolidado quando o usuário registra a **saída do expediente**.
- Se a diferença para 8h48 estiver dentro da tolerância, o saldo do dia fica zerado.
- Acima da jornada, a diferença entra como:
  - **hora extra 65%** em dias úteis;
  - **hora extra 100%** aos sábados e domingos.
- Abaixo da jornada, a diferença entra como **saldo negativo** no banco de horas.
- Sábado e domingo possuem jornada esperada de zero; portanto, todas as horas trabalhadas entram positivamente no banco.
- Dias futuros não entram no cálculo.
- Dias sem qualquer registro ainda não geram saldo negativo automaticamente. Essa regra evita criar faltas falsas antes de implementarmos feriados, folgas e justificativas.
- O banco é calculado em minutos, evitando erros de arredondamento.

Exemplos:

- 08h48 trabalhadas em um dia útil → banco `0h00`.
- 09h18 trabalhadas → banco `+0h30` e 30 min de extra 65%.
- 08h18 trabalhadas → banco `-0h30`.
- 04h00 em um sábado → banco `+4h00` e 4h de extra 100%.


## Feriados e ausências — v1.3

- Feriados nacionais brasileiros são adicionados automaticamente ao banco/calendário.
- Trabalho em sábado, domingo ou feriado é classificado como 100%.
- Dia útil passado sem ponto, sem feriado e sem justificativa gera ausência e débito de 8h48.
- A tabela `day_offs` prepara folga, férias, falta justificada, atestado e compensação.
- Feriados estaduais e municipais continuam preparados para cadastro manual futuro.
- Se o banco já existe, importe `database/patch_002_feriados_ausencias.sql`.


## Validação de ausência por data de cadastro — v1.4

- O sistema não marca como ausência nenhum dia anterior à criação da conta.
- O próprio dia em que a conta foi criada também não é convertido automaticamente em ausência.
- As ausências automáticas só começam a valer a partir do dia seguinte ao cadastro, respeitando feriados, finais de semana e justificativas.
- Nenhuma alteração no banco de dados é necessária para esta versão, pois a tabela `users` já possui `created_at`.


## Edição e inclusão manual de pontos — v1.5

- O colaborador pode alterar o horário de uma marcação já registrada.
- Toda alteração exige um motivo.
- Alterações são gravadas na tabela `time_adjustments`, preservando horário antigo, horário novo, usuário responsável e motivo.
- O registro alterado passa a ter `source = manual`.
- É possível adicionar uma marcação esquecida diretamente pelo Histórico.
- O sistema impede duplicar o mesmo tipo de ponto no mesmo dia.
- O sistema valida a sequência: entrada → início do almoço → volta do almoço → saída.
- Nesta versão, uma marcação existente pode ter o horário alterado, mas permanece no mesmo dia.
- Não é necessário importar SQL novo: a tabela `time_adjustments` já existe no schema atual.


## Ausências e Justificativas — v1.6

Nova área pessoal disponível para todos os colaboradores.

Tipos disponíveis:
- Falta justificada
- Atestado
- Folga
- Férias
- Compensação

Regras:
- Faltas sem justificativa continuam sendo detectadas automaticamente em dias úteis sem ponto.
- Um período cadastrado em Ausências e Justificativas deixa de gerar débito automático no banco de horas.
- Cada usuário só visualiza, edita e exclui os próprios registros.
- O sistema impede períodos sobrepostos.
- Não é permitido criar ocorrências anteriores à criação da conta.
- Os registros já aparecem automaticamente no calendário.
- Não há necessidade de novo SQL se `patch_002_feriados_ausencias.sql` já foi importado.


## Configurações pessoais — v1.7

A tela de Configurações voltou ao menu e agora é pessoal para cada colaborador.

Inclui:
- horário padrão de entrada;
- horário padrão de saída;
- duração do almoço;
- recálculo automático da jornada líquida diária;
- tema claro/escuro;
- preferência de avisos no sistema;
- permissão para notificações do navegador;
- edição de nome e e-mail;
- foto de perfil JPG/PNG/WEBP de até 3 MB.

Se o banco já existe, importe:

`database/patch_003_configuracoes_pessoais.sql`

As fotos são salvas em:

`public/assets/uploads/avatars/`


## Perfil consolidado — v1.8

- A seção de Perfil foi removida de Configurações.
- Foto de perfil, nome, e-mail e salário ficam agora apenas em Perfil.
- O visual mais completo de edição de perfil foi movido para a página Perfil.
- Configurações fica responsável somente por jornada, tema e notificações.


## Comparação diária — v1.9

O card "Horas hoje" agora mostra a variação percentual em relação ao dia anterior.

Fórmula:

`((horas de hoje - horas de ontem) / horas de ontem) × 100`

Regras:
- valor positivo: seta para cima e porcentagem positiva;
- valor negativo: seta para baixo;
- mesmo total: 0%;
- se ontem tiver 0 minutos trabalhados, o sistema mostra "Sem comparação com ontem" para evitar divisão por zero.
- A comparação usa horas realmente registradas, não a jornada prevista.


## Comparação mensal de horas extras — v2.0

O card "Horas extras" agora:
- soma horas extras de 65% e 100%;
- compara o total do mês atual com o mês anterior;
- mostra variação positiva, negativa ou zero;
- quando o mês anterior não possui horas extras, exibe "Sem comparação com o mês passado" para evitar divisão por zero.

Fórmula:

`((extras do mês atual - extras do mês anterior) / extras do mês anterior) × 100`


## Histórico compacto — v2.1

A tela de Histórico foi redesenhada para evitar formulários grandes ocupando espaço.

Agora:
- cada dia possui um cabeçalho com data, dia da semana e resumo de horas;
- as quatro marcações aparecem em cards compactos;
- o formulário de edição não fica mais aberto o tempo todo;
- cada marcação possui um botão "Editar";
- ao editar, é aberto um modal com horário e motivo da alteração;
- o histórico continua preservando auditoria pela tabela `time_adjustments`;
- o nome do dia da semana é exibido em português.


## Valor estimado das horas extras — v2.2

O dashboard agora mostra uma estimativa financeira das horas extras acumuladas no mês.

Cálculo:
- valor da hora = salário mensal / horas mensais;
- hora extra 65% = valor da hora × 1,65;
- hora extra 100% = valor da hora × 2;
- total estimado = soma dos valores de extras 65% e 100%.

O Perfil também mostra o valor estimado da hora.

Quando o salário não estiver cadastrado, o dashboard oferece um atalho para o Perfil.

Importante: o valor é apenas estimativo e não substitui o cálculo oficial da folha de pagamento.


## Gráfico semanal navegável — v2.3

O seletor do card "Minhas horas na semana" agora é funcional.

Opções:
- Esta semana
- Semana passada
- Há 2 semanas
- Há 3 semanas
- Há 4 semanas

Ao selecionar outro período:
- o Dashboard recarrega somente com o período semanal escolhido;
- o gráfico usa os registros reais daquela semana;
- total e média são recalculados;
- as datas exibidas abaixo das barras mudam automaticamente.

Não é necessário importar SQL novo.


## Relatórios e notificações inteligentes — v2.4

### Relatórios
A aba Relatórios agora é funcional e possui:
- seleção de mês;
- total de horas trabalhadas;
- horas extras 65% e 100%;
- banco de horas;
- valor estimado das horas extras;
- média de horas por dia trabalhado;
- ausências, justificativas, feriados e dias finalizados;
- gráfico diário do mês;
- tabela detalhada por dia.

### Notificações inteligentes
O Dashboard passa a criar lembretes automáticos quando:
- a entrada não foi registrada depois do horário padrão + tolerância;
- o intervalo de almoço passou da duração configurada e a volta ainda não foi registrada;
- o horário padrão de saída passou e o expediente ainda não foi finalizado.

Os lembretes aparecem:
- no Dashboard;
- no sino de notificações;
- como notificação do navegador quando essa opção estiver habilitada e autorizada.

Cada notificação do navegador é exibida apenas uma vez por sessão para evitar repetição excessiva.

### Ajustes preservados
- removido o bloco "Acesso de demonstração" da tela de login;
- mantido o alinhamento corrigido dos botões "Adicionar marcação" e "Filtrar" no Histórico.

Não é necessário importar SQL novo.


## Notificações avançadas, financeiro, filtros e mobile — v2.5

### Notificações inteligentes
As notificações agora funcionam em todas as páginas autenticadas e podem avisar:
- antes da entrada;
- quando a entrada está atrasada;
- antes do início do almoço;
- quando o início do almoço está atrasado;
- antes da volta do almoço;
- quando a volta do almoço está atrasada;
- antes da saída;
- quando a saída ainda não foi registrada.

Em Configurações o usuário pode:
- ativar/desativar cada tipo de lembrete;
- definir quantos minutos antes deseja ser avisado;
- definir quantos minutos depois um evento passa a ser considerado atrasado;
- definir o horário padrão de início do almoço.

### Histórico
- exibe automaticamente os últimos 7 dias;
- atalhos rápidos para Hoje, 3 dias, 5 dias e 7 dias;
- intervalo personalizado com no máximo 7 dias;
- mantém edição de ponto e auditoria.

### Resumo financeiro
Relatórios agora mostram:
- salário base;
- valor da hora normal;
- valor da hora extra 65%;
- valor da hora extra 100%;
- extras acumuladas no mês;
- bruto estimado somando salário + extras.

### Responsividade
Foram revisados:
- sidebar móvel com backdrop;
- topbar;
- dashboard;
- cards de ponto;
- histórico;
- relatórios;
- calendário;
- configurações;
- notificações;
- grids financeiros.

### Ajustes preservados
- login sem “Acesso de demonstração”;
- alinhamento corrigido dos botões do Histórico;
- banco MySQL na porta 4406;
- config/database.php usando \PDO e \PDOException.

Se seu banco já existe, importe:

`database/patch_004_notificacoes_avancadas.sql`


## Navegação mensal mobile dos Relatórios — v2.6

No celular, a navegação de mês dos Relatórios agora fica em uma única linha:
- mês anterior à esquerda;
- mês atual centralizado;
- próximo mês à direita.

O ajuste é responsivo e mantém o layout desktop inalterado.


## Horário de almoço aprendido automaticamente — v2.7

O usuário não precisa mais cadastrar um horário fixo para iniciar o almoço.

O Pitter Ponto aprende o padrão usando os últimos registros de `lunch_start`:
- considera somente dias úteis anteriores ao dia atual;
- usa até os 10 registros mais recentes;
- só começa a gerar lembretes depois de pelo menos 3 dias com almoço registrado;
- considera apenas horários entre 10:00 e 15:00 para reduzir registros anormais;
- com 5 ou mais amostras, o horário mais cedo e o mais tarde são removidos antes da média, reduzindo o efeito de exceções.

Exemplo:
- 11:58
- 12:04
- 12:01
- 12:07
- 12:00

O sistema aprende aproximadamente `12:02` e usa esse horário para avisar conforme os minutos configurados em Configurações.

A Configuração manual de "Início padrão do almoço" foi removida da interface.

Nenhum novo SQL é necessário nesta versão. A coluna antiga pode permanecer no banco sem causar problemas.


## Validação de expediente finalizado — v2.8

As notificações operacionais do dia são encerradas assim que o usuário registra a saída (`clock_out`).

Isso impede avisos indevidos depois do expediente, por exemplo:
- "inicie o almoço" após a pessoa já ter saído;
- "volte do almoço" depois do expediente;
- "finalize o expediente" após a saída já ter sido registrada.

Também foi reforçada a condição do lembrete de início do almoço para nunca ser exibido quando já existir uma saída naquele dia.

Nenhum SQL novo é necessário.


## Sidebar recolhível — v2.9

No desktop, a sidebar agora pode ser recolhida para o modo compacto:
- mostra apenas os ícones;
- mantém tooltips ao passar o mouse;
- reduz a largura da barra lateral;
- desloca o conteúdo principal automaticamente;
- salva a preferência no navegador com `localStorage`.

No mobile, o comportamento continua igual ao menu lateral tradicional.


## Correção da sidebar recolhível — v3.0

Corrigido o problema da v2.9 que interrompia todo o JavaScript da aplicação.

Causa:
- `desktopSidebarToggle` estava declarado duas vezes com `const`, causando erro de sintaxe;
- como o `app.js` não executava, os ícones Lucide também não eram renderizados;
- seletores CSS da sidebar compacta não correspondiam a algumas classes reais do layout.

Agora:
- todos os ícones voltam a funcionar;
- layout expandido mantém exatamente a largura original;
- modo compacto usa 82px e mostra apenas os ícones;
- conteúdo principal acompanha corretamente a largura;
- foto do perfil e botão Sair ficam compactos;
- tooltips aparecem ao passar o mouse;
- o estado aberto/fechado continua salvo no navegador;
- o botão de recolher fica na borda da sidebar e não cobre a logo;
- mobile continua independente do modo compacto.


## Pendências, calendário interativo e busca — v3.1

### Detecção de inconsistências
O Dashboard agora verifica os últimos 30 dias e sinaliza:
- dia útil passado sem ponto ou justificativa;
- expediente iniciado mas não finalizado;
- sequência de marcações fora da ordem;
- marcações duplicadas;
- jornada superior a 16 horas;
- intervalo de almoço superior a 3 horas.

As pendências mostram a data e um atalho para corrigir no Histórico ou justificar.

### Calendário interativo
Os dias do Calendário agora são clicáveis.
Ao clicar, abre um modal com:
- entrada;
- início do almoço;
- volta do almoço;
- saída;
- horas trabalhadas;
- extras 65%;
- extras 100%;
- banco de horas;
- valor estimado das extras;
- feriado, justificativa ou ausência;
- atalho para abrir o dia no Histórico.

### Busca global
A barra "Buscar algo..." agora funciona de verdade.
Ela permite buscar:
- páginas do sistema;
- entrada, saída, almoço e retorno;
- registros manuais e observações;
- datas como 12/09/2026;
- ausências e justificativas;
- feriados.

### Correção preservada
O `PageController.php` NÃO contém mais a validação antiga de `$lunchStartTime`.
O horário do almoço continua sendo aprendido automaticamente pelo histórico.

Nenhum SQL novo é necessário.


## Correção da busca global — v3.1.1

Corrigido `SQLSTATE[HY093]: Invalid parameter number` na busca global.

A causa era a reutilização do mesmo placeholder nomeado (`:query`) mais de uma vez
na mesma instrução preparada enquanto o PDO usa prepared statements nativos
(`PDO::ATTR_EMULATE_PREPARES => false`).

Agora cada comparação usa um placeholder próprio:
- `:query_note`
- `:query_source`
- `:query_type`

A correção foi aplicada em `TimeEntry::searchForUser()` e `DayOff::searchForUser()`.


## Filtro do Histórico refinado — v3.1.2

O bloco de período do Histórico foi reorganizado para ficar mais consistente:
- atalhos Hoje / 3 dias / 5 dias / 7 dias melhor alinhados;
- campos De e Até mantidos na mesma linha;
- botão Aplicar período alinhado com os inputs;
- texto de ajuda movido para baixo da linha principal;
- comportamento mobile revisado;
- tema escuro preservado.

Também foi mantida a correção do `PageController.php` sem a validação antiga de `$lunchStartTime`.


## Extra 100% no fim de semana + revisão do tema escuro — v3.2

### Regra de horas extras
A jornada obrigatória é de segunda a sexta.

Por isso, agora:
- sábado: todo o período trabalhado é hora extra 100%;
- domingo: todo o período trabalhado é hora extra 100%;
- feriado: todo o período trabalhado é hora extra 100%;
- esses dias não geram falta quando não há ponto.

Exemplos:
- sábado com 4h trabalhadas => 4h de extra 100%;
- sábado com 9h09 trabalhadas => 9h09 de extra 100%.

O banco de horas recebe positivamente o mesmo período trabalhado nesses dias.

### Tema escuro
Foi feita uma revisão mais ampla das telas:
- Calendário;
- Ausências e Justificativas;
- Histórico;
- Relatórios;
- Configurações;
- Perfil;
- Busca;
- notificações e pendências;
- modais;
- campos, selects e botões auxiliares.

Foram removidos diversos fundos brancos e textos de baixo contraste que apareciam no modo escuro.

### Correção preservada
O `PageController.php` continua sem a validação antiga de `$lunchStartTime`.

Nenhum SQL novo é necessário.


## Segurança de cadastro e login — v3.3

### Cadastro
- e-mail normalizado em minúsculas;
- validação de formato;
- validação do domínio por DNS (MX/A/AAAA);
- e-mail único verificado no PHP e protegido também por `UNIQUE` no MySQL;
- condição de corrida tratada com captura de erro `23000`;
- nomes duplicados continuam permitidos, mas geram aviso;
- nomes têm espaços repetidos normalizados;
- senha com 8 a 128 caracteres;
- senha exige pelo menos uma letra e um número;
- confirmação de senha obrigatória.

### Login
- máximo padrão de 5 tentativas inválidas;
- janela padrão de 15 minutos;
- após atingir o limite, bloqueio padrão de 5 minutos;
- tentativas armazenadas por e-mail normalizado + IP;
- mensagem de credenciais inválidas não revela se a conta existe;
- `session_regenerate_id(true)` após login válido;
- timeout padrão de 30 minutos por inatividade;
- cookie de sessão com `HttpOnly` e `SameSite=Lax`;
- cookie `Secure` é ativado automaticamente quando o site estiver em HTTPS;
- logout remove a sessão e o cookie.

### Perfil
A troca de e-mail também respeita:
- formato válido;
- domínio válido;
- e-mail único;
- aviso de nome duplicado.

### Configuração
Os valores podem ser personalizados no `.env`:

`SECURITY_MAX_LOGIN_ATTEMPTS=5`
`SECURITY_LOGIN_WINDOW_MINUTES=15`
`SECURITY_LOGIN_LOCK_MINUTES=5`
`SECURITY_SESSION_TIMEOUT_MINUTES=480`

### Banco
Para bancos existentes, importe:

`database/patch_005_seguranca_auth.sql`

A ideia de confirmação real do e-mail por link ficou reservada para uma versão futura.

A correção antiga do `$lunchStartTime` no `PageController.php` permanece preservada.


## Navegação inferior no celular — v3.4

Em telas de até 680px, a sidebar lateral é substituída por uma barra inferior fixa.

Itens principais:
- Início
- Meu Ponto
- Histórico
- Calendário
- Mais

O botão Mais abre um bottom sheet com:
- Ausências
- Relatórios
- Perfil
- Configurações
- Busca
- Sair

Desktop permanece com a sidebar lateral normal. Nenhum SQL novo é necessário.


## Área Administrativa — v4.0

Admins continuam usando normalmente o próprio ponto e recebem uma opção extra
**Administração**.

O dashboard administrativo mostra usuários, pessoas trabalhando, almoço,
finalizados, horas da equipe, extras, logins, ausências, pendências e últimos
acessos. Também existe uma lista de usuários e uma página individual com
jornada, status, horas mensais, extras, banco, registros, pendências e logins.

A primeira versão é somente de consulta: o admin não altera o ponto ou senha
de outro usuário.

Para tornar uma conta admin, use diretamente no banco:

`UPDATE users SET role = 'admin' WHERE email = 'email@empresa.com';`

SQL:
- `database/patch_006_area_administrativa_local.sql`
- `database/patch_006_area_administrativa_infinityfree.sql`


## Gestão administrativa de contas — v4.2

Na página Administração > Usuários, administradores agora podem:
- abrir os detalhes;
- editar nome;
- editar e-mail;
- alterar perfil entre Colaborador e Administrador;
- ativar/desativar a conta;
- definir uma nova senha opcional;
- excluir contas.

Proteções:
- um admin não pode excluir a própria conta enquanto está conectado;
- o último administrador ativo não pode ser rebaixado, desativado ou excluído;
- e-mails duplicados continuam bloqueados;
- nova senha continua exigindo 8+ caracteres, letra e número;
- se o banco impedir a exclusão por existir histórico protegido,
  o sistema orienta a desativar a conta em vez de quebrar a integridade dos dados.

Não há alteração de estrutura no banco nesta versão.


## Ajustes de jornada e notebook — v4.3

- marcações de ponto usam o horário da aplicação (`America/Sao_Paulo`) em vez de `NOW()` do MySQL;
- o relógio do Dashboard atualiza a cada segundo;
- o horário exibido após bater ponto é exatamente o horário persistido;
- histórico de login também passa a gravar data/hora pela aplicação;
- hora extra em dia útil mudou de 65% para 65%;
- estimativa financeira usa multiplicador 1,65;
- sábado, domingo e feriados permanecem em 100%;
- layout ganhou modo compacto para notebooks entre aproximadamente 861px e 1440px.

Bancos existentes:
- local: `database/patch_007_hora_extra_65_local.sql`
- InfinityFree: `database/patch_007_hora_extra_65_infinityfree.sql`


## v4.4 — Sessão de 8 horas e calendário mobile

- Timeout padrão de inatividade: 480 minutos (8 horas).
- Em instalações existentes, altere também o `.env` para:
  `SECURITY_SESSION_TIMEOUT_MINUTES=480`
- Calendário mobile sem rolagem horizontal.
- Grade mensal responsiva com 7 colunas.
- Dia atual destacado.
- Marcadores coloridos para trabalho, extra, ausência, feriado e folga.
- Detalhes do dia continuam disponíveis ao tocar.
- Modal de detalhes vira bottom sheet no celular.
- Compatível com tema claro e escuro.
