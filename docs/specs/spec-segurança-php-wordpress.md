# SPEC — Segurança de Aplicações PHP e WordPress

**Categoria:** Segurança da Aplicação (Application Security)  
**Escopo:** Backend PHP, WordPress, Plugins, Temas, Banco de Dados e Infraestrutura  
**Prioridade:** Alta  
**Aplicabilidade:** Obrigatória para novos desenvolvimentos e manutenções.

---

## 1. Objetivo

Estabelecer padrões e requisitos mínimos de segurança para o desenvolvimento, manutenção e operação de aplicações construídas em PHP e WordPress.

A implementação deverá seguir o princípio de defesa em profundidade (*Defense in Depth*), garantindo que diferentes camadas de proteção sejam aplicadas para reduzir riscos de exploração, acesso não autorizado, exposição de informações e comprometimento da aplicação.

Todos os requisitos desta especificação deverão ser considerados durante o desenvolvimento, revisão de código, testes e implantação em produção.

## 2. Princípios gerais de segurança

Toda implementação deverá respeitar os seguintes princípios:

- **Security by Design:** segurança deverá ser considerada desde a arquitetura inicial.
- **Least Privilege:** usuários, processos e serviços deverão possuir somente as permissões necessárias.
- **Input Validation:** todos os dados externos deverão ser validados.
- **Output Encoding:** dados deverão ser escapados conforme o contexto de saída.
- **Defense in Depth:** diferentes mecanismos de proteção deverão atuar em conjunto.
- **Fail Securely:** falhas de segurança deverão resultar em comportamento seguro.
- **Secure Defaults:** configurações deverão adotar padrões seguros por padrão.
- **Continuous Security:** vulnerabilidades deverão ser monitoradas e corrigidas continuamente.

## 3. Requisitos de segurança — PHP

### SEC-PHP-001 — Validação de entradas

Todas as entradas externas deverão ser consideradas não confiáveis.

Deverão ser validados os dados provenientes de:

- `$_GET`
- `$_POST`
- `$_REQUEST`
- `$_COOKIE`
- Headers HTTP
- APIs externas
- Arquivos enviados por usuários.

A validação deverá considerar tipo, formato, tamanho e regras de negócio.

A sanitização não deverá substituir a validação.

### SEC-PHP-002 — Proteção contra SQL Injection

Todas as consultas SQL que utilizem dados variáveis deverão utilizar consultas parametrizadas (*Prepared Statements*).

Deverão ser utilizados PDO ou MySQLi com parâmetros vinculados.

É proibida a concatenação direta de entradas externas em instruções SQL.

Identificadores dinâmicos de tabelas, colunas e ordenação deverão ser controlados por listas de autorização (*Allowlist*).

### SEC-PHP-003 — Proteção contra XSS

