# Spec: Auditoria WP

## Sobre o projeto

O projeto será um plugin para WordPress que irá fazer a auditoria do WordPress. O plugin poderá fazer a auditoria dos seguintes módulos:

1. Auditoria de conteúdo:

- Criação de posts e páginas
- Edição e exclusão de conteúdo
- Alteração de status (publicado, rascunho, etc...)
- Alteração de categorias, tags e metadados

2. Auditoria de usuários:

- Login e logout
- Falhas de autenticação
- Criação e exclusão de usuários
- Alteração de perfil e permissões

3. Auditoria do sistema:

- Instalação e remoção de plugins
- Ativação e desativação de plugins
- Alterações de temas
- Atualizações do WordPress

4. Auditoria de segurança

- Tentativas de login
- Alterações de privilégios
- Ações administrativas sensíveis
- Identificação da origem das operações, quando disponível

5. Consultas e relatórios

- Histórico de atividades
- Filtros por usuário, data e tipo de evento
- Detalhamento de cada evento
- Exportação de registros

O plugin irá registrar eventos de auditoria, não de banco de dados. Então, ele deve capturar quem fez, o que fez, quando fez e qual entidade.

## Arquitetura

A Arquitetura será Clean Architecture, com quatro camadas principais: 

1. Apresentação: Interface administrativa, REST API, Controllers, DTOs

2. Aplicação: Casos de uso, Services, Input/Output Ports

3. Domínio: Entidades, Value Objects, Domain Rules, Interfaces

4. Infraestrutura: Hooks WordPress, Banco de dados, Repositórios, Adapters

A camada de Domínio não deve conhecer o WordPress, nem depender de funções como get_current_user_id(), wp_insert_post() ou $wpbd.

A camada de Infraestrutura é responsável por adaptar o ambiente WordPress às necessidades da aplicação.

## Estrutura dos diretórios

Utilizar namespaces PSR-4 e Composer para o autoload das classes.

A estrutura inicial seria: 

wp-audit/
│
├── wp-audit.php
├── uninstall.php
├── composer.json
│
├── src/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   ├── AuditLog.php
│   │   │   └── Actor.php
│   │   ├── ValueObjects/
│   │   │   ├── EventType.php
│   │   │   └── ResourceType.php
│   │   ├── Contracts/
│   │   │   └── AuditLogRepositoryInterface.php
│   │   └── Exceptions/
│   │
│   ├── Application/
│   │   ├── UseCases/
│   │   │   ├── RecordAuditEvent.php
│   │   │   ├── SearchAuditLogs.php
│   │   │   ├── GetAuditLog.php
│   │   │   └── ExportAuditLogs.php
│   │   ├── DTOs/
│   │   └── Contracts/
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── WordPressAuditLogRepository.php
│   │   │   └── Database/
│   │   │       └── AuditTableSchema.php
│   │   ├── WordPress/
│   │   │   ├── Hooks/
│   │   │   │   ├── PostHooks.php
│   │   │   │   ├── UserHooks.php
│   │   │   │   └── PluginHooks.php
│   │   │   └── WordPressUserProvider.php
│   │   └── Logging/
│   │
│   ├── Presentation/
│   │   ├── Admin/
│   │   │   ├── Pages/
│   │   │   ├── Controllers/
│   │   │   └── Views/
│   │   └── Rest/
│   │       └── AuditLogController.php
│   │
│   └── Shared/
│       └── Container/
│
├── assets/
│   ├── css/
│   └── js/
│
├── languages/
│
└── tests/
    ├── Unit/
    ├── Integration/
    └── Functional/

## Modelagem do banco de dados

Utilizar tabelas próprias, em vez de Custom Post Types ou opções do WordPress.

Criar uma tabela principal: wp_log_watch_audit_logs com a seguinte estrutura:

Campo	           Tipo	               Descrição
id	               BIGINT	           Identificador
event_uuid	       CHAR(36)	           Identificador único
actor_id	       BIGINT	           ID do usuário
event_type	       VARCHAR(100)	       Tipo do evento
resource_type	   VARCHAR(100)	       Tipo do recurso
resource_id	       BIGINT	           ID do recurso
description	       TEXT	               Descrição
metadata	       LONGTEXT	           Dados adicionais em JSON
ip_address	       VARCHAR(45)	       IP, quando disponível
user_agent	       TEXT	               Navegador ou cliente
created_at	       DATETIME	           Data da ocorrência

Índices recomendados:
- actor_id
- event_type
- resource_type, resource_id
- created_at
- event_uuid (único)

Se necessário, criar uma tabela de configurações e outra para política de retenção posteriormente.

Exemplo de registro:

``
{
  "event": "post.updated",
  "actor_id": 15,
  "resource": {
    "type": "post",
    "id": 320
  },
  "metadata": {
    "before": {
      "title": "Título antigo",
      "status": "draft"
    },
    "after": {
      "title": "Título novo",
      "status": "publish"
    }
  },
  "created_at": "2026-11-11 01:00:00"
}
`` 

## Segurança e integridade dos registros

Estabelecer algumas regras de confiabilidade dos registros:

1. Os registros não podem ser editados pela interface administrativa.
2. Apenas usuários com uma capability específica podem consultar os registros.
3. Toda entrada precisa ser validada.
4. Toda saída precisa ser escapada.
5. Consultas SQL devem utilizar prepared statements.
6. Ações administrativas devem validar capabilities e nonces.
7. Deve existir uma política de retenção dos registros.
8. Informações sensíveis precisam ser removidas ou mascaradas.

## Tecnologias e ferramentas

Utilizar as stacks:

1. PHP 8.2+: Linguagem principal, condicionado à versão mínima do WordPress suportada
2. Composer: Autoload e dependências
3. PSR-4: Organização dos namespaces
4. MySQL: Persistência de dados
5. PHPUnit: Testes automatizados
6. PHPStan: Análise estática
7. PHP_CodeSniffer: Padronização de código
8. WP-CLI: Administração e manutenção
9. Git: Versionamento

## Segurança e boas práticas

Considerar sempre a segurança e as boas práticas do código do projeto. Considerar a seção de boas práticas e segurança do arquivo AGENTS.md e da spec: spec-segurança-php-wordpress.md