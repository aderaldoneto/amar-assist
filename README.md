# Amar Assist — Sistema de Cobranças

Sistema desenvolvido como teste técnico para gerenciamento de clientes, contratos e cobranças.

## Tecnologias

- Laravel 9
- PHP 8.1
- Vue 3
- Vue Router
- Vite
- MySQL 8
- Redis
- Nginx
- Docker Compose
- PHPUnit
- Laravel Sanctum

## Funcionalidades

- Autenticação por token com Laravel Sanctum
- Limitação de tentativas de login
- Listagem paginada de clientes
- Filtros por nome, CPF/CNPJ e situação
- Contratos PF e PJ
- Ciclo de vencimento entre os dias 1 e 31
- Ajuste automático para o último dia válido do mês
- Cobranças por boleto, cartão e Pix
- Multa simples de 1% ao dia
- Listagem priorizando cobranças abertas e vencidas
- Registro de pagamento
- Cache Redis
- Filas processadas por worker
- Testes automatizados com PHPUnit
- Banco separado para testes

## Regras de negócio

### Cliente

Um cliente com contrato não pode ser desativado. A operação é protegida por transação e bloqueio de linha no banco.

CPF e CNPJ são armazenados somente com números e não podem ser duplicados.

### Ciclo de vencimento

O contrato possui um dia de cobrança entre 1 e 31.

Quando o mês não possui o dia solicitado, utiliza-se seu último dia:

- Dia 31 em abril: 30 de abril
- Dia 31 em fevereiro de 2026: 28 de fevereiro
- Dia 31 em fevereiro de 2024: 29 de fevereiro

### Multa

Cobranças vencidas recebem multa simples de 1% ao dia:

```text
multa = valor original × 1% × dias em atraso
total = valor original + multa