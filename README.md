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
  - **hora extra 50%** em dias úteis;
  - **hora extra 100%** aos sábados e domingos.
- Abaixo da jornada, a diferença entra como **saldo negativo** no banco de horas.
- Sábado e domingo possuem jornada esperada de zero; portanto, todas as horas trabalhadas entram positivamente no banco.
- Dias futuros não entram no cálculo.
- Dias sem qualquer registro ainda não geram saldo negativo automaticamente. Essa regra evita criar faltas falsas antes de implementarmos feriados, folgas e justificativas.
- O banco é calculado em minutos, evitando erros de arredondamento.

Exemplos:

- 08h48 trabalhadas em um dia útil → banco `0h00`.
- 09h18 trabalhadas → banco `+0h30` e 30 min de extra 50%.
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
