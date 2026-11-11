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

## Continuar Spec