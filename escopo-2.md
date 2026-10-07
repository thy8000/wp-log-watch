Sim. Para esse tipo de plugin, eu recomendo tratá-lo como um **sistema de trilha de auditoria (audit trail)**, e não simplesmente como um logger.

Uma arquitetura sólida seria:

```text
WordPress
   │
   ├── Hooks / eventos
   │       │
   │       ▼
   │   Audit Event Collector
   │       │
   │       ▼
   │   Normalização
   │       │
   │       ▼
   │   Audit Repository
   │       │
   │       ▼
   │   wp_audit_log
   │
   ├── Consulta / filtros
   │       └── REST/Admin UI
   │
   ├── Controle de acesso
   │
   └── Retenção / limpeza
           └── WP-Cron
```

## 1. Comece definindo o evento de auditoria

Antes de escrever os hooks, defina um formato único para qualquer evento.

Por exemplo:

```php
[
    'event_type'      => 'post.updated',
    'action'          => 'update',
    'actor_user_id'   => 123,
    'actor_login'     => 'joao',
    'resource_type'   => 'post',
    'resource_id'     => 456,
    'resource_name'   => 'Meu artigo',
    'context'         => 'wp-admin',
    'ip_address'      => '192.0.2.10',
    'user_agent'      => 'Mozilla/5.0...',
    'request_id'      => '...',
    'occurred_at'     => '2026-10-06 23:10:00',
    'changes'         => [
        'post_status' => [
            'old' => 'draft',
            'new' => 'publish',
        ],
    ],
]
```

Isso é importante porque você não quer que cada hook grave informações em um formato diferente.

---

# 2. Crie uma tabela própria

Eu **não colocaria o histórico inteiro em `wp_options` ou post meta**.

Para auditoria, uma tabela própria é muito mais adequada.

Algo como:

```sql
wp_audit_log
```

com:

```sql
CREATE TABLE wp_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    event_type VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,

    actor_user_id BIGINT UNSIGNED NULL,
    actor_login VARCHAR(60) NULL,

    resource_type VARCHAR(50) NULL,
    resource_id BIGINT UNSIGNED NULL,
    resource_name VARCHAR(255) NULL,

    context VARCHAR(50) NULL,

    ip_address VARBINARY(16) NULL,
    user_agent TEXT NULL,
    request_id CHAR(36) NULL,

    changes_json LONGTEXT NULL,
    metadata_json LONGTEXT NULL,

    occurred_at DATETIME NOT NULL,

    PRIMARY KEY (id),

    KEY event_type (event_type),
    KEY actor_user_id (actor_user_id),
    KEY resource (resource_type, resource_id),
    KEY occurred_at (occurred_at),
    KEY action (action)
);
```

No plugin, use `$wpdb->prefix` em vez de assumir que a tabela será `wp_audit_log`.

Também use `dbDelta()` para criação/atualização da tabela.

Documentação:

[WordPress — Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/?utm_source=chatgpt.com)

---

# 3. Não registre diretamente nos hooks

Crie uma camada intermediária:

```php
final class AuditLogger
{
    public function log(array $event): void
    {
        // validação
        // normalização
        // sanitização
        // persistência
    }
}
```

Então seus hooks fazem somente:

```php
$logger->log([
    'event_type' => 'post.updated',
    'action'     => 'update',
    // ...
]);
```

Isso evita espalhar lógica de auditoria pelo plugin.

Uma estrutura poderia ser:

```text
my-audit/
├── my-audit.php
├── src/
│   ├── AuditLogger.php
│   ├── AuditRepository.php
│   ├── AuditEvent.php
│   ├── AuditQuery.php
│   ├── RetentionPolicy.php
│   ├── Hooks/
│   │   ├── PostHooks.php
│   │   ├── UserHooks.php
│   │   ├── OptionHooks.php
│   │   └── PluginHooks.php
│   └── Admin/
│       └── AuditPage.php
└── uninstall.php
```

---

# 4. Identifique o ator

Você precisa diferenciar pelo menos:

### Usuário autenticado

```php
$user_id = get_current_user_id();
```

Depois:

```php
$user = wp_get_current_user();

$actor = [
    'user_id' => $user->ID,
    'login'   => $user->user_login,
];
```

### Sistema

Nem toda alteração será feita diretamente por uma pessoa.

Por exemplo:

```text
WP-Cron
WP-CLI
REST API
plugin
tema
processo automático
```

Então eu adicionaria um conceito de:

```text
actor_type
```

por exemplo:

```text
user
system
cron
wp-cli
api
plugin
```

Assim você consegue diferenciar:

```text
João publicou o post
```

de:

```text
WP-Cron alterou o post
```

---

# 5. Identifique o recurso

Um evento deveria responder:

> "O que foi afetado?"

Por exemplo:

```php
[
    'resource_type' => 'post',
    'resource_id'   => 123,
    'resource_name' => 'Política de Privacidade',
]
```

Outros tipos:

```text
post
user
comment
term
option
plugin
theme
media
site
```

Isso torna a consulta muito mais útil:

```text
Mostrar todas as alterações feitas no usuário 123
```

ou:

```text
Mostrar tudo que aconteceu com o post 456
```

---

# 6. Use hooks do WordPress para capturar as ações

Essa é provavelmente a parte mais extensa do projeto.

Por exemplo, posts:

```php
add_action(
    'save_post',
    [$this, 'on_save_post'],
    10,
    3
);
```

Usuários:

```php
add_action(
    'user_register',
    [$this, 'on_user_register']
);
```

Exclusão:

```php
add_action(
    'delete_post',
    [$this, 'on_delete_post']
);
```

Comentários:

```php
add_action(
    'comment_post',
    [$this, 'on_comment_post'],
    10,
    3
);
```

Você deve montar uma matriz de eventos que quer auditar.

Por exemplo:

| Categoria    | Evento               |
| ------------ | -------------------- |
| Posts        | criado               |
| Posts        | atualizado           |
| Posts        | publicado            |
| Posts        | colocado em rascunho |
| Posts        | excluído             |
| Posts        | restaurado           |
| Usuários     | criado               |
| Usuários     | login                |
| Usuários     | logout               |
| Usuários     | role alterada        |
| Usuários     | senha alterada       |
| Plugins      | ativado              |
| Plugins      | desativado           |
| Plugins      | instalado            |
| Plugins      | atualizado           |
| Temas        | ativado              |
| Configuração | opção alterada       |
| Comentários  | criado               |
| Comentários  | aprovado             |
| Comentários  | rejeitado            |
| Comentários  | excluído             |

Não tente registrar literalmente **tudo** no primeiro release. Isso gera muito ruído.

---

# 7. Registre alterações, não apenas eventos

Esse é um dos recursos mais importantes.

Imagine:

```text
Post #123 atualizado
```

Isso é pouco útil.

Melhor:

```text
Post #123 atualizado

status:
    draft → publish

title:
    "Meu artigo" → "Meu artigo atualizado"

author:
    10 → 15
```

Você pode armazenar:

```php
$changes = [
    'post_status' => [
        'old' => 'draft',
        'new' => 'publish',
    ],
    'post_title' => [
        'old' => 'Meu artigo',
        'new' => 'Meu artigo atualizado',
    ],
];
```

E persistir em JSON:

```php
wp_json_encode($changes);
```

Documentação:

[WordPress — wp_json_encode()](https://developer.wordpress.org/reference/functions/wp_json_encode/?utm_source=chatgpt.com)

---

# 8. Cuidado com dados sensíveis

Esse é um dos maiores riscos de um plugin de auditoria.

**Não registre automaticamente tudo que passar pelo request.**

Evite armazenar:

```text
password
pass1
pass2
auth
cookie
nonce
token
API keys
secret keys
credit card data
```

Por exemplo, isso seria perigoso:

```php
$_POST
```

inteiro dentro de `metadata_json`.

Faça uma whitelist:

```php
$allowed_fields = [
    'post_title',
    'post_status',
    'post_author',
];
```

E filtre explicitamente.

---

# 9. Registre o contexto

O evento deve dizer de onde veio a ação.

Por exemplo:

```php
'context' => 'wp-admin'
```

Mas você pode ter:

```text
wp-admin
rest-api
wp-cli
cron
frontend
system
```

Também pode registrar:

```php
'request_id'
```

Isso é extremamente útil para correlacionar eventos.

Por exemplo:

```text
Request: 7f8e...
 ├── usuário atualizado
 ├── role alterada
 └── e-mail alterado
```

---

# 10. IP e User-Agent

Você pode registrar o IP, mas faça isso conscientemente por questões de privacidade e legislação.

Não confie cegamente em:

```php
$_SERVER['REMOTE_ADDR']
```

se sua infraestrutura estiver atrás de proxy/CDN.

E **não confie em `X-Forwarded-For` sem configurar uma cadeia de proxies confiáveis**.

Para um plugin distribuído publicamente, eu tornaria essa funcionalidade configurável.

---

# 11. Proteja o acesso aos logs

Essa parte é tão importante quanto registrar.

Por exemplo, não faça:

```php
if (is_user_logged_in()) {
    // mostra logs
}
```

Isso é insuficiente.

Use uma capability específica:

```php
if (! current_user_can('manage_audit_log')) {
    wp_die(__('Acesso negado.', 'my-audit'));
}
```

Mas há uma questão importante: **não crie uma capability que qualquer administrador possa receber automaticamente sem pensar no modelo de segurança.**

Uma abordagem inicial pode ser:

```text
manage_audit_log
```

e uma role/capability específica para visualizar e outra para administrar:

```text
view_audit_log
manage_audit_log
delete_audit_log
```

Assim você pode ter:

```text
Auditor:
    view_audit_log

Administrador de auditoria:
    view_audit_log
    manage_audit_log

Administrador:
    depende da política da instalação
```

Use `current_user_can()` para autorização.

[WordPress — Roles and Capabilities](https://developer.wordpress.org/plugins/users/roles-and-capabilities/?utm_source=chatgpt.com)

---

# 12. Não permita apagar logs arbitrariamente

Esse é um ponto que muitas implementações esquecem.

Se um administrador consegue simplesmente clicar:

> "Excluir todos os logs"

você perde parte do propósito da auditoria.

Eu separaria:

```text
consulta
exportação
configuração
retenção
exclusão manual
```

E deixaria exclusão manual extremamente restrita.

Idealmente:

```text
Logs normais
     ↓
política de retenção
     ↓
remoção automática
```

em vez de:

```text
Administrador → botão "Apagar histórico"
```

---

# 13. Faça uma tela de consulta

Algo como:

```text
┌──────────────────────────────────────────────────────┐
│ Auditoria                                            │
├──────────────────────────────────────────────────────┤
│ Usuário: [ Todos ▼ ]                                 │
│ Evento: [ Todos ▼ ]                                  │
│ Recurso: [ Post ▼ ]                                  │
│ Data: [01/09/2026] até [06/10/2026]                 │
│                                                      │
│ [ Filtrar ]                                          │
├──────────────────────────────────────────────────────┤
│ Data       Usuário    Evento       Recurso           │
│ 06/10 22h  João       Atualizou    Post #123         │
│ 06/10 21h  Maria      Publicou     Post #456         │
│ 06/10 20h  João       Login        —                 │
└──────────────────────────────────────────────────────┘
```

Não carregue todos os registros.

Faça paginação no banco:

```sql
LIMIT 50 OFFSET 0
```

ou, para grandes volumes, considere paginação por cursor/ID.

---

# 14. Use `$wpdb->prepare()`

Para consultas construídas dinamicamente:

```php
$sql = $wpdb->prepare(
    "
    SELECT *
    FROM {$table}
    WHERE actor_user_id = %d
    ORDER BY occurred_at DESC
    LIMIT %d OFFSET %d
    ",
    $user_id,
    $limit,
    $offset
);
```

Nunca concatene entrada do usuário diretamente na SQL.

Documentação oficial:

[WordPress — wpdb::prepare()](https://developer.wordpress.org/reference/classes/wpdb/prepare/?utm_source=chatgpt.com)

---

# 15. Pense em retenção desde o começo

Por exemplo:

```text
Retenção:
90 dias
```

O cron executaria:

```sql
DELETE FROM wp_audit_log
WHERE occurred_at < '2026-07-08 00:00:00'
```

Mas em uma tabela grande, um `DELETE` gigantesco pode ser ruim.

Melhor processar em lotes:

```sql
DELETE FROM wp_audit_log
WHERE occurred_at < %s
LIMIT 1000
```

e repetir até não haver mais registros.

Você pode configurar:

```text
7 dias
30 dias
90 dias
180 dias
365 dias
indefinido
```

---

# 16. Use WP-Cron para retenção

Por exemplo:

```php
if (! wp_next_scheduled('my_audit_cleanup')) {
    wp_schedule_event(
        time(),
        'daily',
        'my_audit_cleanup'
    );
}
```

Depois:

```php
add_action(
    'my_audit_cleanup',
    [$retention, 'cleanup']
);
```

E no `uninstall.php`, remova o cron.

Documentação:

[WordPress — Cron API](https://developer.wordpress.org/plugins/cron/?utm_source=chatgpt.com)

---

# 17. Uma melhoria importante: hash de integridade

Se o objetivo é uma **auditoria de segurança realmente séria**, eu acrescentaria proteção contra adulteração.

Por exemplo:

```text
registro 100
hash anterior = abc123
conteúdo = ...
hash atual = def456
```

O próximo registro usa o hash anterior:

```text
record 100
    hash = A

record 101
    previous_hash = A
    hash = B

record 102
    previous_hash = B
    hash = C
```

Isso cria uma cadeia:

```text
A → B → C → D → E
```

Se alguém alterar silenciosamente o registro 101, a cadeia deixa de bater.

Você poderia calcular algo conceitualmente como:

```php
$hash = hash(
    'sha256',
    $previous_hash . wp_json_encode($event)
);
```

Isso **não torna o banco imutável**, mas permite detectar alterações posteriores.

Para auditoria de alto valor, eu iria além e armazenaria os logs também fora do mesmo banco/site.

---

# 18. Não confunda auditoria com debug

Eu separaria:

```text
Debug log
    ↓
informação para desenvolvedor

Audit log
    ↓
registro de ações relevantes
```

O primeiro pode conter:

```text
SQL executada
stack trace
warnings
debug information
```

O segundo deve responder:

```text
Quem?
Fez o quê?
Em qual recurso?
Quando?
De onde?
O que mudou?
```

---

# 19. Uma API interna deixa o sistema muito mais limpo

Por exemplo:

```php
$audit->record(
    event: 'post.updated',
    actor: $actor,
    resource: $resource,
    changes: $changes,
    context: $context
);
```

Conceitualmente:

```php
$audit->record([
    'event' => 'post.updated',

    'actor' => [
        'type' => 'user',
        'id'   => get_current_user_id(),
    ],

    'resource' => [
        'type' => 'post',
        'id'   => $post_id,
    ],

    'changes' => [
        'post_status' => [
            'old' => 'draft',
            'new' => 'publish',
        ],
    ],
]);
```

Isso permite que amanhã você altere o armazenamento sem precisar alterar todos os hooks.

---

# 20. Eu dividiria o plugin em 5 componentes

Uma arquitetura que considero particularmente adequada seria:

### `AuditCollector`

Descobre o que aconteceu.

```text
WordPress hook
     ↓
AuditCollector
```

### `AuditNormalizer`

Transforma eventos diferentes em um formato comum.

```text
save_post
user_register
activated_plugin
     ↓
AuditNormalizer
     ↓
AuditEvent
```

### `AuditRepository`

Só se preocupa com persistência:

```text
AuditEvent
    ↓
AuditRepository
    ↓
wpdb
```

### `AuditQuery`

Só consulta:

```text
filtros
  ↓
AuditQuery
  ↓
wpdb
```

### `RetentionPolicy`

Só administra expiração:

```text
WP-Cron
   ↓
RetentionPolicy
   ↓
AuditRepository
```

Essa separação evita criar um gigantesco:

```php
audit.php
```

com milhares de linhas.

---

# 21. Um MVP que eu faria

Não tentaria implementar todas as funcionalidades de uma vez.

### Fase 1

Implementaria:

```text
✓ tabela própria
✓ AuditEvent
✓ AuditLogger
✓ AuditRepository
✓ criação de post
✓ atualização de post
✓ exclusão de post
✓ criação de usuário
✓ alteração de usuário
✓ login
✓ logout
✓ ator
✓ recurso
✓ timestamp
✓ IP
✓ alterações
```

### Fase 2

Depois:

```text
✓ tela administrativa
✓ filtros
✓ paginação
✓ pesquisa por usuário
✓ pesquisa por recurso
✓ intervalo de datas
✓ filtros por evento
```

### Fase 3

Depois:

```text
✓ retenção
✓ WP-Cron
✓ exportação
✓ REST API
✓ capabilities específicas
```

### Fase 4

Para um produto mais sério:

```text
✓ hash de integridade
✓ request ID
✓ detecção de adulteração
✓ armazenamento externo
✓ anonimização
✓ políticas de retenção configuráveis
✓ multisite
✓ WP-CLI
```

---

## Uma observação importante sobre "auditar tudo"

Eu evitaria criar um plugin que tenta capturar **toda e qualquer alteração no WordPress** por padrão.

Isso tende a produzir:

```text
milhares de eventos
       ↓
muito ruído
       ↓
banco crescendo rapidamente
       ↓
logs difíceis de consultar
       ↓
auditoria perde valor
```

É melhor ter **eventos semanticamente relevantes**.

Por exemplo:

```text
❌ "UPDATE wp_posts executado"

✓ "João publicou o post 'Política de Segurança'"
```

A segunda informação é muito mais útil para auditoria.

---

## Arquitetura final

Eu chegaria aproximadamente nisso:

```text
                    ┌──────────────────┐
                    │ WordPress Hooks  │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Audit Collector  │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Audit Normalizer │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │   Audit Event    │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Audit Repository │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ wp_audit_log     │
                    └────────┬─────────┘
                             │
              ┌──────────────┴──────────────┐
              ▼                             ▼
       ┌──────────────┐              ┌───────────────┐
       │ Audit Query  │              │ Retention     │
       └──────┬───────┘              │ Policy        │
              │                      └───────┬───────┘
              ▼                              │
       ┌──────────────┐                      ▼
       │ Admin / REST │                  WP-Cron
       └──────────────┘
```

Esse desenho também facilita bastante transformar o projeto posteriormente em um **plugin profissional de auditoria**, porque captura, armazenamento, consulta, segurança e retenção ficam desacoplados.

Como referência principal da API do WordPress para esse projeto: [WordPress Developer Resources](https://developer.wordpress.org/?utm_source=chatgpt.com)
