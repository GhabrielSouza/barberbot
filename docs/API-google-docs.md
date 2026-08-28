# API — BarberBot

Base URL: `{APP_URL}/api`

## Autenticação

A API usa **autenticação por sessão** (Laravel + Sanctum SPA/stateful), não Bearer token. O client precisa manter cookies entre requests (cookie jar habilitado). Rotas marcadas como 🔒 exigem sessão autenticada (`auth`).

Para rotas fora do grupo de auth que dependem de `EnsureFrontendRequestsAreStateful` (as protegidas por `auth` dentro do grupo `companies/{company}/...`), o client deve enviar um header `Origin`/`Referer` que bata com `SANCTUM_STATEFUL_DOMAINS` no `.env`.

---

### `POST /api/register`

Cria um novo tenant (empresa) e o usuário owner, e já autentica a sessão.

**Body (JSON)**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim | máx. 255 |
| `email` | string | sim | e-mail válido, único |
| `password` | string | sim | precisa de `password_confirmation` igual (regra `confirmed`) |
| `password_confirmation` | string | sim | deve bater com `password` |
| `tenant_name` | string | sim | nome da empresa, máx. 255 |

**Resposta 201**

    {
      "user": { "id": "uuid", "name": "...", "email": "...", "role": "owner", "tenant_id": "uuid" },
      "tenant": { "id": "uuid", "name": "...", "slug": "...", "schema_name": "...", "status": "active" }
    }

---

### `POST /api/login`

**Body (JSON)**

| Campo | Tipo | Obrigatório |
|---|---|---|
| `email` | string | sim |
| `password` | string | sim |

Rate limit: 5 tentativas por `email+ip`, depois bloqueia temporariamente (`422` com mensagem de throttle).

**Resposta 200**

    {
      "user": { "id": "uuid", "name": "...", "email": "...", "role": "owner", "tenant_id": "uuid" },
      "tenant": { "id": "uuid", "name": "...", "slug": "...", "schema_name": "...", "status": "active" }
    }

**Erros**: `422` credenciais inválidas ou rate limit.

---

### `POST /api/logout` 🔒

Sem body. **Resposta 204** (sem conteúdo).

---

### `GET /api/user` 🔒

Retorna o usuário autenticado com o tenant carregado.

**Resposta 200**

    {
      "id": "uuid",
      "tenant_id": "uuid",
      "name": "...",
      "email": "...",
      "role": "owner",
      "created_at": "...",
      "updated_at": "...",
      "tenant": { "id": "uuid", "name": "...", "slug": "...", "schema_name": "...", "status": "active", ... }
    }

---

## Tenants (catálogo global — schema `public`)

CRUD da empresa/assinante. Ao criar, o schema Postgres do tenant é provisionado de forma assíncrona (job em fila).

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/tenants` | Lista tenants (paginado) |
| `POST` | `/api/tenants` | Cria um tenant |
| `GET` | `/api/tenants/{tenant}` | Detalhe de um tenant |
| `PUT` | `/api/tenants/{tenant}` | Atualiza um tenant |
| `PATCH` | `/api/tenants/{tenant}/cancel` | Cancela um tenant |
| `DELETE` | `/api/tenants/{tenant}` | Remove um tenant |

**Body `POST /api/tenants`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim | máx. 255 |
| `segment` | string | não | máx. 255 |
| `city` | string | não | máx. 255 |
| `phone` | string | não | máx. 255 |
| `status` | string | não | `active`\|`suspended`\|`cancelled` (default `active`) |

`PUT` aceita os mesmos campos, todos opcionais (`sometimes`).

**Resposta (`TenantResource`)**

    {
      "id": "uuid",
      "name": "...",
      "slug": "...",
      "schema_name": "...",
      "segment": "...",
      "city": "...",
      "phone": "...",
      "status": "active",
      "provisioning_error": null,
      "created_at": "...",
      "updated_at": "..."
    }

---

## Tenant Users (logins globais do tenant — schema `public`)

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/tenants/{tenant}/users` | Lista usuários do tenant |
| `POST` | `/api/tenants/{tenant}/users` | Cria um usuário no tenant |
| `GET` | `/api/tenants/{tenant}/users/{user}` | Detalhe de um usuário |
| `PUT` | `/api/tenants/{tenant}/users/{user}` | Atualiza um usuário |
| `DELETE` | `/api/tenants/{tenant}/users/{user}` | Remove um usuário |

**Body `POST`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim | máx. 255 |
| `email` | string | sim | único |
| `password` | string | sim | mín. 8 caracteres |
| `role` | string | não | `owner`\|`supervisor`\|`atendente` (default `owner`) |

`PUT` aceita os mesmos campos como opcionais; `password` só é re-hasheado se enviado.

**Resposta (`UserResource`)** — nunca expõe a senha

    {
      "id": "uuid",
      "tenant_id": "uuid",
      "name": "...",
      "email": "...",
      "role": "owner",
      "created_at": "...",
      "updated_at": "..."
    }

---

## Plans (catálogo global de planos do SaaS)

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/plans` | Lista planos (aceita `?active=1\|0`) |
| `POST` | `/api/plans` | Cria um plano |
| `GET` | `/api/plans/active` | Lista só planos ativos |
| `GET` | `/api/plans/{plan}` | Detalhe de um plano |
| `PUT` | `/api/plans/{plan}` | Atualiza um plano |
| `PATCH` | `/api/plans/{plan}/toggle` | Alterna `active` |
| `DELETE` | `/api/plans/{plan}` | Remove um plano |

**Body `POST`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim | máx. 255 |
| `price_month` | numeric | sim | >= 0 |
| `included_members` | integer | sim | >= 0 |
| `price_per_extra_member` | numeric | sim | >= 0 |
| `limits` | object | não | jsonb livre, ex. `{"max_appointments_month": 200, "ai": false}` |
| `active` | boolean | não | default `true` |

`PUT` aceita os mesmos campos como opcionais.

**Resposta (`PlanResource`)**

    {
      "id": "uuid",
      "name": "Pro",
      "price_month": 99.9,
      "included_members": 3,
      "price_per_extra_member": 19.9,
      "limits": { "max_appointments_month": 200, "ai": false },
      "active": true,
      "created_at": "...",
      "updated_at": "..."
    }

---

## Commission Methods (catálogo global de métodos de comissão)

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/commission-methods` | Lista métodos |
| `POST` | `/api/commission-methods` | Cria um método |
| `GET` | `/api/commission-methods/{commission_method}` | Detalhe |
| `PUT` | `/api/commission-methods/{commission_method}` | Atualiza |
| `DELETE` | `/api/commission-methods/{commission_method}` | Remove |

**Body `POST`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `code` | string | sim | único, ex. `per_attendance` |
| `name` | string | sim | máx. 255 |
| `description` | string | não | |

`PUT` aceita os mesmos campos como opcionais.

**Resposta (`CommissionMethodResource`)**

    {
      "id": "uuid",
      "code": "per_attendance",
      "name": "Cada um recebe o que atende",
      "description": "...",
      "created_at": "...",
      "updated_at": "..."
    }

> Seed fixo: `per_attendance`, `equal_split`, `custom_percent` já vêm criados pela migration.

---

## Rotas de negócio (schema do tenant — exigem 🔒 `auth`)

Todas abaixo ficam sob `Route::middleware(['api', 'auth'])` e usam o prefixo `companies/{company}`.

### Dashboard

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/dashboard/metrics` | Métricas gerais |
| `GET` | `/api/companies/{company}/dashboard/appointments` | Agendamentos por intervalo de data |
| `GET` | `/api/companies/{company}/dashboard/revenue` | Receita |
| `GET` | `/api/companies/{company}/dashboard/top-services` | Serviços mais vendidos |
| `GET` | `/api/companies/{company}/dashboard/barbers-performance` | Performance por barbeiro |
| `GET` | `/api/companies/{company}/dashboard/clients-stats` | Estatísticas de clientes |

### Appointments

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/appointments` | Lista |
| `POST` | `/api/companies/{company}/appointments` | Cria |
| `GET` | `/api/companies/{company}/appointments/today` | Agendamentos de hoje |
| `GET` | `/api/companies/{company}/appointments/{appointment}` | Detalhe |
| `PUT` | `/api/companies/{company}/appointments/{appointment}` | Atualiza status |
| `PATCH` | `/api/companies/{company}/appointments/{appointment}/cancel` | Cancela |
| `DELETE` | `/api/companies/{company}/appointments/{appointment}` | Remove |

**Body `POST`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `user_id` | integer | sim | precisa existir em `users` |
| `barber_id` | integer | sim | precisa existir em `barbers` |
| `service_id` | integer | sim | precisa existir em `services` |
| `date` | string | sim | formato `Y-m-d`, hoje ou futuro |
| `time` | string | sim | formato `H:i` |

**Body `PUT`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `status` | string | sim | `pending`\|`confirmed`\|`canceled`\|`completed` |

**Resposta (`AppointmentResource`)**

    {
      "id": 1,
      "date": "2026-08-24",
      "time": "14:30",
      "status": "confirmed",
      "user": { ... },
      "barber": { ... },
      "service": { ... },
      "total_value": 50.0,
      "created_at": "...",
      "updated_at": "..."
    }

### Barbers

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/barbers` | Lista |
| `POST` | `/api/companies/{company}/barbers` | Cria |
| `GET` | `/api/companies/{company}/barbers/active` | Só ativos |
| `GET` | `/api/companies/{company}/barbers/{barber}` | Detalhe |
| `PUT` | `/api/companies/{company}/barbers/{barber}` | Atualiza |
| `PATCH` | `/api/companies/{company}/barbers/{barber}/toggle` | Alterna `active` |
| `DELETE` | `/api/companies/{company}/barbers/{barber}` | Remove |

**Body `POST`/`PUT`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim (POST) / opcional (PUT) | máx. 255 |
| `role` | string | não | máx. 100 |
| `color` | string | não | máx. 50 |
| `is_admin` | boolean | não | |
| `user_id` | uuid | não | |
| `active` | boolean | não | |

**Resposta (`BarberResource`)**

    {
      "id": 1,
      "name": "...",
      "role": "...",
      "color": "...",
      "is_admin": false,
      "user_id": "uuid",
      "active": true,
      "schedules": [ ... ],
      "appointments_count": 12,
      "created_at": "...",
      "updated_at": "..."
    }

#### Schedules (aninhado em Barbers)

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/barbers/{barber}/schedules` | Lista |
| `POST` | `/api/companies/{company}/barbers/{barber}/schedules` | Cria |
| `GET` | `/api/companies/{company}/barbers/{barber}/schedules/day/{day}` | Por dia da semana |
| `POST` | `/api/companies/{company}/barbers/{barber}/schedules/bulk` | Criação em lote |
| `GET` | `/api/companies/{company}/barbers/{barber}/schedules/{schedule}` | Detalhe |
| `PUT` | `/api/companies/{company}/barbers/{barber}/schedules/{schedule}` | Atualiza |
| `DELETE` | `/api/companies/{company}/barbers/{barber}/schedules/{schedule}` | Remove |

**Body `POST`/`PUT`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `day_of_week` | integer | sim | `0` (domingo) a `6` (sábado) |
| `start_time` | string | sim | formato `H:i` |
| `end_time` | string | sim | formato `H:i`, precisa ser depois de `start_time` |

**Resposta (`ScheduleResource`)**

    {
      "id": 1,
      "barber_id": 1,
      "day_of_week": 1,
      "day_name": "Monday",
      "start_time": "09:00",
      "end_time": "18:00",
      "created_at": "...",
      "updated_at": "..."
    }

### Services

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/services` | Lista |
| `POST` | `/api/companies/{company}/services` | Cria |
| `GET` | `/api/companies/{company}/services/active` | Só ativos |
| `GET` | `/api/companies/{company}/services/{service}` | Detalhe |
| `PUT` | `/api/companies/{company}/services/{service}` | Atualiza |
| `PATCH` | `/api/companies/{company}/services/{service}/toggle` | Alterna `active` |
| `DELETE` | `/api/companies/{company}/services/{service}` | Remove |

**Resposta (`ServiceResource`)**

    {
      "id": 1,
      "name": "Corte",
      "price": 50.0,
      "duration_minutes": 30,
      "duration_min": 30,
      "category": "...",
      "active": true,
      "created_at": "...",
      "updated_at": "..."
    }

### Products

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/companies/{company}/products` | Lista |
| `POST` | `/api/companies/{company}/products` | Cria |
| `GET` | `/api/companies/{company}/products/active` | Só ativos |
| `GET` | `/api/companies/{company}/products/{product}` | Detalhe |
| `PUT` | `/api/companies/{company}/products/{product}` | Atualiza |
| `PATCH` | `/api/companies/{company}/products/{product}/toggle` | Alterna `active` |
| `DELETE` | `/api/companies/{company}/products/{product}` | Remove |

**Body `POST`**

| Campo | Tipo | Obrigatório | Obs |
|---|---|---|---|
| `name` | string | sim | máx. 255 |
| `price` | numeric | sim | >= 0 |
| `stock` | integer | não | >= 0 |
| `category` | string | não | máx. 100 |
| `active` | boolean | não | |

`PUT` aceita os mesmos campos como opcionais.

**Resposta (`ProductResource`)**

    {
      "id": 1,
      "name": "Pomada",
      "price": 35.0,
      "stock": 10,
      "category": "...",
      "active": true,
      "created_at": "...",
      "updated_at": "..."
    }

---

## Webhook (público)

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/webhook/whatsapp` | Recebe mensagens do WhatsApp |
| `GET` | `/api/webhook/test` | Teste de conectividade do webhook |

## Status

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/status` | Health check simples (hora/data do servidor) |

---

## Erros padrão

| Status | Quando |
|---|---|
| `401` | Sem sessão autenticada em rota `auth` |
| `403` | Registro pertence a outro tenant/company |
| `404` | Registro não encontrado (route-model binding) |
| `422` | Validação falhou (`{"message": "...", "errors": {"campo": ["..."]}}`) |
| `500` | Erro inesperado no servidor |
