Perfeito. Com **Clean Architecture** como decisão arquitetural, eu ajustaria o MVP não apenas nas funcionalidades, mas também nas **fronteiras do sistema**.

A ideia central será:

> **O WP Trace deve registrar e consultar eventos de auditoria, mantendo o domínio independente do WordPress e isolando toda dependência de WordPress/MySQL nas camadas externas.**

Isso também nos dá um critério importante: **não colocar abstrações só porque "Clean Architecture pede".** Cada camada precisa ter uma responsabilidade real.

# WP Trace — MVP v0.1

## 1. Objetivo

O WP Trace será um plugin de auditoria para WordPress capaz de:

* registrar atividades relevantes;
* identificar quem realizou uma ação;
* identificar o recurso afetado;
* registrar quando e em qual contexto a ação ocorreu;
* registrar alterações relevantes;
* consultar e filtrar o histórico;
* proteger os registros contra acesso não autorizado;
* remover registros antigos de acordo com uma política de retenção.

O MVP terá como foco:

```text
WordPress
    ↓
Eventos
    ↓
Audit Domain
    ↓
Persistência
    ↓
Admin UI
```

---

# 2. Arquitetura

Vamos organizar o sistema em quatro grandes áreas:

```text
src/
│
├── Domain/
│
├── Application/
│
├── Infrastructure/
│
└── Presentation/
```

Com a regra de dependência:

```text
Presentation
      ↓
Application
      ↓
Domain
      ↑
Infrastructure
```

Ou, visualmente:

```text
                  ┌──────────────────────┐
                  │     Presentation     │
                  │                      │
                  │ Admin / REST / CLI   │
                  └──────────┬───────────┘
                             │
                             ▼
                  ┌──────────────────────┐
                  │     Application      │
                  │                      │
                  │ Use Cases / DTOs     │
                  └──────────┬───────────┘
                             │
                             ▼
                  ┌──────────────────────┐
                  │        Domain        │
                  │                      │
                  │ Events / Actors      │
                  │ Interfaces / Rules  │
                  └──────────────────────┘
                             ▲
                             │
                  ┌──────────┴───────────┐
                  │    Infrastructure   │
                  │                      │
                  │ WP / MySQL / Cron   │
                  └──────────────────────┘
```

**Regra fundamental:**

> O Domain não pode depender de WordPress.

Nada de:

```php
WP_Post
wpdb
get_current_user_id()
add_action()
WP_User
```

dentro do domínio.

---

# 3. Domain

Essa é a parte mais importante da arquitetura.

## 3.1 `AuditEvent`

Entidade principal:

```text
AuditEvent
├── id
├── action
├── actor
├── object
├── context
├── createdAt
└── metadata
```

Exemplo:

```text
AuditEvent

action:
    post.updated

actor:
    user #42

object:
    post #381

context:
    wp-admin

createdAt:
    2026-09-21 14:32:18

metadata:
    changes:
        title:
            old: "Meu artigo"
            new: "Meu artigo atualizado"
```

---

# 4. Value Objects

Em vez de deixar tudo como `string` e `int`, algumas informações terão objetos próprios.

Por exemplo:

```text
AuditAction
Actor
AuditObject
AuditContext
AuditEventId
```

### `AuditAction`

```text
post.created
post.updated
post.deleted

user.created
user.updated
user.deleted

comment.created
comment.updated
comment.deleted

plugin.activated
plugin.deactivated
plugin.updated

theme.activated
theme.updated
```

---

# 5. Actor

O domínio precisa representar quem realizou a ação.

```text
Actor
├── type
└── id
```

No MVP:

```text
user
system
```

Exemplo:

```text
Actor
type = user
id = 42
```

ou:

```text
Actor
type = system
id = null
```

Isso evita que o domínio assuma que toda ação vem de um usuário do WordPress.

---

# 6. Audit Object

Representa o recurso afetado.

```text
AuditObject
├── type
└── id
```

Exemplos:

```text
post #381
user #42
comment #928
```

Para plugins/temas, o ID poderá ser uma string:

```text
plugin/woocommerce
theme/twentytwentyfive
```

---

# 7. Audit Context

Representa de onde veio a ação:

```text
wp-admin
frontend
rest-api
wp-cli
cron
system
```

No MVP, alguns desses contextos podem existir apenas no modelo e serem utilizados conforme os listeners forem implementados.

---

# 8. Repository

No Domain:

```php
interface AuditEventRepository
{
    public function save(AuditEvent $event): void;

    public function findById(
        AuditEventId $id
    ): ?AuditEvent;

    public function search(
        AuditEventQuery $query
    ): AuditEventCollection;

    public function deleteBefore(
        DateTimeImmutable $date
    ): int;
}
```

Essa interface é um **port**.

O domínio não sabe se os dados estão em:

```text
MySQL
PostgreSQL
Redis
API
arquivo
```

---

# 9. Application

A camada Application representa os **casos de uso** do sistema.

No MVP:

```text
Application/
├── Audit/
│   ├── RecordAuditEvent.php
│   ├── FindAuditEvent.php
│   ├── SearchAuditEvents.php
│   └── DeleteExpiredAuditEvents.php
│
└── DTO/
    ├── RecordAuditEventInput.php
    └── SearchAuditEventsInput.php
```

---

# 10. Use Case — Record Audit Event

Responsável por registrar um evento.

Fluxo:

```text
Listener
   ↓
RecordAuditEvent
   ↓
Domain Event
   ↓
Repository
```

Exemplo conceitual:

```php
$recordAuditEvent->execute(
    new RecordAuditEventInput(...)
);
```

O use case não sabe que está sendo executado pelo `post_updated`.

---

# 11. Use Case — Search Audit Events

Responsável pela consulta.

Entrada:

```text
SearchAuditEventsInput

dateFrom
dateTo
actorId
action
objectType
objectId
search
page
perPage
```

Saída:

```text
AuditEventCollection
```

Isso evita colocar lógica de consulta dentro do Controller.

---

# 12. Use Case — Find Audit Event

Responsável pela página de detalhes.

```text
Admin Controller
      ↓
FindAuditEvent
      ↓
Repository
      ↓
AuditEvent
```

---

# 13. Use Case — Delete Expired Events

Responsável pela retenção:

```text
DeleteExpiredAuditEvents
```

Recebe:

```text
retention period
```

e executa:

```text
Repository
    ↓
deleteBefore(...)
```

O Application não precisa saber que isso será executado pelo WP-Cron.

---

# 14. Infrastructure

Aqui entra tudo que depende do WordPress.

Estrutura inicial:

```text
Infrastructure/
├── Persistence/
│   └── WordPressAuditEventRepository.php
│
├── WordPress/
│   ├── Hooks/
│   ├── Users/
│   ├── Posts/
│   ├── Comments/
│   ├── Plugins/
│   └── Themes/
│
├── Cron/
│   └── AuditRetentionJob.php
│
└── Database/
    └── Migrations/
```

---

# 15. WordPress Hooks como Adapters

Por exemplo:

```text
post_updated
      ↓
PostUpdatedListener
      ↓
RecordAuditEvent
```

O listener traduz o mundo WordPress para o mundo do domínio.

Ele pode receber:

```php
WP_Post $postAfter
WP_Post $postBefore
```

e produzir:

```text
AuditEvent
```

O domínio nunca precisa conhecer `WP_Post`.

---

# 16. Eventos do MVP

## Posts

Implementar:

```text
post.created
post.updated
post.published
post.trashed
post.restored
post.deleted
```

Aplicável a:

* post
* page
* custom post types

---

## Users

```text
user.created
user.updated
user.deleted
user.login
user.logout
user.role_changed
```

---

## Comments

```text
comment.created
comment.updated
comment.approved
comment.unapproved
comment.spammed
comment.deleted
```

---

## Plugins

```text
plugin.activated
plugin.deactivated
plugin.updated
```

---

## Themes

```text
theme.activated
theme.updated
```

---

# 17. Detecção de alterações

Para `post.updated`, por exemplo:

```text
PostUpdatedListener
        ↓
ChangeDetector
        ↓
metadata
```

Resultado:

```json
{
    "changes": {
        "title": {
            "old": "Título antigo",
            "new": "Título novo"
        },
        "status": {
            "old": "draft",
            "new": "publish"
        }
    }
}
```

No MVP:

### Capturar

* title
* status
* author
* slug

### Não capturar por padrão

* conteúdo completo
* metadados completos
* dados arbitrários de custom fields

Isso evita gerar logs gigantes.

---

# 18. Sensitive Data Protection

Eu colocaria isso como um componente próprio da Application/Domain, dependendo de como você modelar a regra.

Por exemplo:

```text
SensitiveDataFilter
```

Antes de persistir:

```text
Input
 ↓
SensitiveDataFilter
 ↓
AuditEvent
 ↓
Repository
```

Nunca devemos persistir inadvertidamente:

```text
password
token
cookie
authorization
secret
```

---

# 19. Infrastructure — Database

Criar uma tabela própria:

```text
{prefix}wp_trace_events
```

Campos:

```text
id
created_at

actor_type
actor_id

action

object_type
object_id

context

ip_address
user_agent

metadata
```

### Índices

```text
PRIMARY KEY (id)

INDEX created_at
INDEX actor_id
INDEX action
INDEX object_type, object_id
```

---

# 20. Mapper

Aqui entra uma peça importante da Clean Architecture.

O banco não deve retornar diretamente um `AuditEvent`.

Teremos algo como:

```text
Database Record
      ↓
AuditEventMapper
      ↓
AuditEvent
```

E no sentido inverso:

```text
AuditEvent
      ↓
AuditEventMapper
      ↓
Database Record
```

Isso mantém o domínio independente do formato da tabela.

---

# 21. Presentation

Aqui eu usaria uma estrutura próxima de MVC.

```text
Presentation/
├── Admin/
│   ├── Controllers/
│   │   ├── AuditLogController.php
│   │   └── SettingsController.php
│   │
│   └── Views/
│       ├── audit-log.php
│       ├── audit-event.php
│       └── settings.php
│
├── Rest/
└── Cli/
```

### Importante

O Controller **não contém regra de negócio**.

Ele faz basicamente:

```text
Request
   ↓
Input DTO
   ↓
Use Case
   ↓
Response/View
```

---

# 22. Admin UI

O MVP terá:

### Audit Log

```text
WP Trace
└── Audit Log
```

Com:

* listagem;
* paginação;
* filtros;
* busca;
* detalhes.

---

# 23. Filtros

Implementar:

```text
Date
Actor
Action
Object type
Object ID
Search
```

Exemplo:

```text
Date:
[Last 7 days]

Actor:
[João Silva]

Action:
[post.updated]

Object:
[Post]

[Search]
```

---

# 24. Paginação

50 registros por página:

```text
1 2 3 4 5 ... 20
```

A paginação acontece no banco.

Nunca:

```text
SELECT * → PHP → array_slice()
```

---

# 25. Segurança da Presentation

O Admin Controller será responsável por verificar:

```text
Capability
   ↓
Nonce
   ↓
Input validation
   ↓
Use Case
```

Criar capabilities próprias:

```text
view_wp_trace_logs
manage_wp_trace
```

---

# 26. Retenção

Configuração:

```text
Retention

Keep forever
90 days
180 days
1 year
```

No MVP eu provavelmente escolheria:

```text
90 days
```

como default.

A execução:

```text
WP-Cron
   ↓
AuditRetentionJob
   ↓
DeleteExpiredAuditEvents
   ↓
Repository
```

Novamente, o Application não sabe que existe WP-Cron.

---

# 27. O que fica fora do MVP

Aqui eu seria rigoroso.

## ❌ REST API pública

Fica para `v0.2`.

## ❌ WP-CLI

`v0.2`.

## ❌ Webhooks

`v0.3`.

## ❌ Queue

`v0.3`.

## ❌ Exportação

`v0.2`.

## ❌ Analytics

`v0.2+`.

## ❌ React

Não necessário no MVP.

## ❌ Elasticsearch

Não faz sentido.

## ❌ Auditoria de SQL

Fora do escopo.

## ❌ Auditoria completa de Options

Fora do MVP.

---

# 28. Testes

Com Clean Architecture, essa parte fica particularmente interessante.

## Domain tests

Não precisam inicializar WordPress.

```text
tests/
└── Unit/
    └── Domain/
        ├── AuditEventTest.php
        ├── ActorTest.php
        ├── AuditActionTest.php
        └── AuditObjectTest.php
```

Exemplo:

```text
✓ creates valid event
✓ rejects invalid action
✓ accepts system actor
✓ accepts user actor
✓ validates object
```

---

# 29. Application tests

Mockar o repository:

```text
RecordAuditEventTest
SearchAuditEventsTest
FindAuditEventTest
DeleteExpiredAuditEventsTest
```

Por exemplo:

```text
RecordAuditEvent
      ↓
Mock Repository
      ↓
assert save() called
```

Nenhum MySQL.

Nenhum WordPress.

---

# 30. Integration tests

Aqui sim:

```text
tests/
└── Integration/
    ├── Persistence/
    ├── Hooks/
    └── Admin/
```

Testar:

```text
WordPress
    ↓
Hook
    ↓
Listener
    ↓
Use Case
    ↓
Repository
    ↓
Database
```

Exemplo:

```text
✓ updating a post creates audit event
✓ creating user creates audit event
✓ activating plugin creates audit event
```

---

# 31. Qualidade

O MVP deverá ter:

```text
PHPUnit
PHPStan
PHPCS
WordPress Coding Standards
GitHub Actions
```

Pipeline:

```text
                 Pull Request
                       │
          ┌────────────┼────────────┐
          ↓            ↓            ↓
      PHPUnit       PHPStan       PHPCS
          │            │            │
          └────────────┼────────────┘
                       ↓
                  Integration
                     Tests
```

---

# 32. Estrutura final do MVP

Uma primeira versão poderia chegar a:

```text
wp-trace/
│
├── wp-trace.php
├── composer.json
├── phpunit.xml.dist
├── phpstan.neon
├── phpcs.xml.dist
│
├── src/
│   │
│   ├── Domain/
│   │   ├── AuditEvent/
│   │   │   ├── AuditEvent.php
│   │   │   ├── AuditEventId.php
│   │   │   ├── AuditAction.php
│   │   │   ├── AuditObject.php
│   │   │   └── AuditEventRepository.php
│   │   │
│   │   └── Actor/
│   │       └── Actor.php
│   │
│   ├── Application/
│   │   ├── Audit/
│   │   │   ├── RecordAuditEvent.php
│   │   │   ├── FindAuditEvent.php
│   │   │   ├── SearchAuditEvents.php
│   │   │   └── DeleteExpiredAuditEvents.php
│   │   │
│   │   └── DTO/
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── WordPressAuditEventRepository.php
│   │   │   └── AuditEventMapper.php
│   │   │
│   │   ├── WordPress/
│   │   │   ├── Posts/
│   │   │   ├── Users/
│   │   │   ├── Comments/
│   │   │   ├── Plugins/
│   │   │   └── Themes/
│   │   │
│   │   ├── Database/
│   │   │   └── Migrations/
│   │   │
│   │   └── Cron/
│   │
│   └── Presentation/
│       └── Admin/
│           ├── Controllers/
│           └── Views/
│
├── tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   └── Application/
│   │
│   └── Integration/
│
└── README.md
```

---

# 33. Definition of Done

Eu usaria esta checklist como **critério real para dizer que o MVP acabou**:

### Core

* [ ] `AuditEvent` implementado
* [ ] `Actor` implementado
* [ ] `AuditObject` implementado
* [ ] `AuditAction` implementado
* [ ] Repository interface implementada
* [ ] Database repository implementado

### Eventos

* [ ] Post created
* [ ] Post updated
* [ ] Post published
* [ ] Post trashed
* [ ] Post restored
* [ ] Post deleted
* [ ] User created
* [ ] User updated
* [ ] User deleted
* [ ] User login
* [ ] User logout
* [ ] Role changed
* [ ] Comment events
* [ ] Plugin events
* [ ] Theme events

### Admin

* [ ] Audit log
* [ ] Event details
* [ ] Filters
* [ ] Search
* [ ] Pagination

### Security

* [ ] Custom capabilities
* [ ] Nonces
* [ ] Input validation
* [ ] Output escaping
* [ ] SQL prepared statements
* [ ] Sensitive data filtering

### Retention

* [ ] Retention setting
* [ ] WP-Cron
* [ ] Automatic cleanup

### Quality

* [ ] Unit tests
* [ ] Integration tests
* [ ] PHPStan
* [ ] PHPCS
* [ ] GitHub Actions

---

# 34. Fluxo completo do MVP

No final, o fluxo principal será:

```text
                    WORDPRESS
                        │
              ┌─────────┴─────────┐
              │                   │
          post_updated        user_created
              │                   │
              ▼                   ▼
        ┌───────────┐       ┌───────────┐
        │ Listener  │       │ Listener  │
        └─────┬─────┘       └─────┬─────┘
              │                   │
              └─────────┬─────────┘
                        ▼
                ┌───────────────┐
                │  Application  │
                │               │
                │ Record Event  │
                └───────┬───────┘
                        │
                        ▼
                 ┌────────────┐
                 │   Domain   │
                 │            │
                 │ AuditEvent │
                 └──────┬─────┘
                        │
                  Repository
                        │
                        ▼
                 ┌────────────┐
                 │   WPDB     │
                 └────────────┘
                        │
                        ▼
                  Audit Logs
                        │
                        ▼
                 ┌────────────┐
                 │ Admin UI   │
                 └────────────┘
```

Esse desenho, para mim, é o ponto ideal para o projeto: **Clean Architecture suficiente para criar boas fronteiras e demonstrar engenharia, mas sem transformar um plugin WordPress em um framework arquitetural de 50 classes antes de existir uma funcionalidade.**

A regra que eu manteria durante o desenvolvimento é especialmente importante:

> **Se uma decisão arquitetural não facilitar teste, manutenção, extensão ou isolamento do WordPress, provavelmente ela não pertence ao MVP.**

**Referência:** [WordPress Plugin Handbook — Plugin Architecture](https://developer.wordpress.org/plugins/plugin-basics/?utm_source=chatgpt.com)
