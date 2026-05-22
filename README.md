# Back-End Developer Task - Supplier Stock Processing

**A small, Symfony-based project checking your backend development skills.**

### Requirements/prerequisites:
* Symfony 6.x, Doctrine, MySQL/Postgresql
* Docker environment
* Unit and functional test

### Task description
Create a Symfony app with a REST API returning JSON containing stock information.

1. Create docker-compose based development environment (you can use webdeveops/php-nginx-dev image)
2. Create command consuming csv files and importing its data to database
    * command should have two arguments: absolute filepath and supplier name
    * every supplier might have different file format (take this into account)
    * ideally every supplier should have his own processor/transformer
3. Create unit tests for classes responsible for transforming csv file records into entities.
4. On our stock item entity we would like to store information:
    * EAN (or null if not present)
    * MPN (manufacturer producer number)
    * Producer name
    * External id
    * Price
    * Quantity
5. Prepare *anonymous* API with one endpoint:

    a) /get-stocks endpoint

    * with two query attributes:
      1. mpn
      2. ean

        where at least one must be specified in order to return any results.
    * respectively return results based on filtering by mpn or ean field.
    * result should contain all data available for each stock item

6. Create functional test of created api endpoint (using phpunit + symfony WebTestCase), ideally using zenstruck/foundry for fixtures but its not a must.

Inside *data* directory there are two files from different suppliers with some additional information about how to parse them.

---

## Implementation

Symfony **6.4** application with:
- Docker (`webdevops/php-nginx-dev:8.4` + MySQL 8)
- Supplier-specific CSV processors (`LorotomProcessor`, `TrahProcessor`)
- Console import command `app:import-stock` (portable bulk upsert: batch SELECT + INSERT/UPDATE, works on **MySQL and PostgreSQL**)
- REST endpoint `GET /get-stocks`
- PHPUnit unit + functional tests (Foundry fixtures)

### Architecture — why not CQRS?

This project **does separate write and read paths**, but intentionally **does not use full CQRS** (command/query buses, separate read models, Messenger handlers for every use case).

| Aspect | This solution |
|--------|----------------|
| **Write** | `app:import-stock` → supplier processors → `StockItemBulkUpserter` (DBAL bulk upsert) |
| **Read** | `GET /get-stocks` → `StockSearchService` → `StockItemRepository` (ORM) → `StockItemSerializer` |

**Why CQRS was not introduced here:**

1. **Scope of the task** — one import command, one read endpoint, and a single `stock_item` table. There is no need for independent scaling of reads, projections, or multiple write workflows.
2. **Cost vs benefit** — full CQRS would add buses, extra DTOs/handlers, and more indirection without solving a real problem in a recruitment-sized codebase.
3. **Import performance** — bulk upsert via DBAL is a deliberate write-path optimization; a query bus does not improve that.
4. **Clarity for reviewers** — a thin controller, application service, and repository are easier to follow in a time-boxed exercise than enterprise ceremony.

**When CQRS would make sense** (future evolution): many commands (sync, delete, multi-source import), rich read APIs (pagination, aggregates, search index), or separate read stores. Then introducing command/query handlers or read models would be justified.

### Quick start

Files in `.gitignore` (`vendor/`, `var/`, `.env.dev.local`, `.idea/`, …) are **not in the repo** but are created locally after clone — see steps below.

Passwords (`APP_SECRET`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`) are stored in **Symfony secrets** (`config/secrets/dev/`). Docker Compose reads them from `.env.dev.local` (gitignored; copy from `.env.dev.local.example` on first run).

After clone (first run creates `.env.dev.local` from the example if missing):

```bash
make up
docker compose exec app composer install
make secrets   # sync .env.dev.local from encrypted vault (optional if example matches)
docker compose exec app bin/console doctrine:migrations:migrate -n
```

Or with Makefile:

```bash
make up
make install
make secrets
make migrate
```

To change a secret:

```bash
docker compose exec app bin/console secrets:set MYSQL_PASSWORD --env=dev
make secrets
docker compose up -d --force-recreate database
```

### Import sample data

```bash
docker compose exec app bin/console app:import-stock /app/data/lorotom.csv lorotom
docker compose exec app bin/console app:import-stock /app/data/trah.csv trah
```

Or: `make import`

### API examples

Application URL: **http://localhost:18080** (port may differ if 18080 is busy).

**Filtering rules:**
- At least one query parameter (`mpn` or `ean`) is required — otherwise HTTP 400.
- Only `mpn` → all items with that MPN.
- Only `ean` → all items with that EAN.
- Both `mpn` and `ean` → **AND** filter (item must match both fields).

```bash
# Filter by MPN
curl "http://localhost:18080/get-stocks?mpn=19-598"

# Filter by EAN
curl "http://localhost:18080/get-stocks?ean=5905694015970"

# Both parameters (AND — same row must match mpn and ean)
curl "http://localhost:18080/get-stocks?mpn=19-598&ean=5905694015970"

# Missing parameters -> 400
curl "http://localhost:18080/get-stocks"
```

### Tests

```bash
make test
```

Runs PHPUnit inside the app container against database `stock_test` (Doctrine test suffix).

Unit tests cover CSV transformers; functional tests cover the API with Foundry factories.

### Project structure

```
src/
  Command/ImportStockCommand.php
  Controller/StockController.php
  Dto/GetStocksQuery.php
  Entity/StockItem.php
  Exception/
  Serializer/StockItemSerializer.php
  Service/StockSearchService.php
  Supplier/
    Contract/        # SupplierProcessorInterface, ImportSkipLoggerInterface
    Processor/       # LorotomProcessor, TrahProcessor
    Registry/        # SupplierRegistry
    Import/          # skip log during CSV import
    Normalizer/      # shared CSV value parsing
    Dto/             # StockRowDto
tests/
  Unit/Supplier/
  Functional/
data/                # Sample CSV files (see data/README.md)
compose.yaml         # Docker services
```

### Environment

| Variable | Source |
|----------|--------|
| `APP_SECRET` | Symfony secrets → `.env.dev.local` |
| `MYSQL_PASSWORD` | Symfony secrets → `.env.dev.local` |
| `MYSQL_ROOT_PASSWORD` | Symfony secrets → `.env.dev.local` |
| `DATABASE_URL` | Built from `MYSQL_*` in `.env` (password from secrets) |
| Docker `MYSQL_HOST` / `MYSQL_PORT` | Overridden in `compose.yaml` (`database:3306`) |

MySQL is exposed on host port **3307** (to avoid conflicts with local MySQL).

**PostgreSQL:** set `DATABASE_URL=postgresql://user:pass@host:5432/stock?serverVersion=16` — import upsert uses portable SQL (no MySQL-only syntax).

Templates: `.env.example`, `.env.dev.local.example`.
