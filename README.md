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
```

## Como executar o projeto

### Pré-requisitos

- Docker
- Docker Compose v2 (`docker compose`)

### 1. Configurar o ambiente

Na raiz do projeto, copie o arquivo de exemplo:

```bash
cp src/.env.example src/.env
```

Confira no arquivo `src/.env` se as conexões utilizam os nomes dos serviços do Docker:

```dotenv
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=amar_assist
DB_USERNAME=amar_assist
DB_PASSWORD=amar_assist

REDIS_HOST=redis
```

Defina também as credenciais do usuário administrativo. A senha precisa ter pelo menos 12 caracteres:

```dotenv
ADMIN_NAME="Administrador"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="uma-senha-forte"
```

### 2. Construir as imagens e instalar as dependências

```bash
docker compose build
docker compose run --rm app composer install
docker compose run --rm node npm install
```

### 3. Subir os contêineres

```bash
docker compose up -d
```

Verifique se os serviços estão em execução:

```bash
docker compose ps
```

### 4. Gerar a chave da aplicação

Execute este comando caso `APP_KEY` ainda esteja vazio no `src/.env`:

```bash
docker compose exec app php artisan key:generate
```

### 5. Criar as tabelas e popular o banco

Execute as migrations:

```bash
docker compose exec app php artisan migrate
```

Em seguida, execute explicitamente o `DatabaseSeeder`. Ele cria o usuário administrativo e os dados demonstrativos de clientes, contratos e cobranças:

```bash
docker compose exec app php artisan db:seed --class=DatabaseSeeder
```

O seeder pode ser executado novamente, pois os dados demonstrativos usam `updateOrCreate`.

### 6. Limpar o cache da listagem

Depois de executar o seeder, limpe o cache para que os clientes criados apareçam imediatamente na tela:

```bash
docker compose exec app php artisan cache:clear
```

### 7. Acessar a aplicação

Abra [http://localhost:8080](http://localhost:8080) e entre usando `ADMIN_EMAIL` e `ADMIN_PASSWORD` definidos no `src/.env`.

O Vite fica disponível na porta `5173` para servir os assets do Vue durante o desenvolvimento.

## Testes automatizados

```bash
docker compose exec app php artisan test
```

Os testes utilizam um banco SQLite em memória e não alteram os dados do MySQL usado pela aplicação.

## Comandos úteis

Exibir os logs de todos os serviços:

```bash
docker compose logs -f
```

Exibir somente os logs da aplicação ou da fila:

```bash
docker compose logs -f app
docker compose logs -f queue
```

Parar os contêineres sem apagar os dados:

```bash
docker compose down
```

Parar os contêineres e apagar os volumes do MySQL e Redis:

```bash
docker compose down -v
```

> PS: o último comando remove permanentemente o banco local e deve ser usado apenas quando for necessário reiniciar o ambiente do zero.
