# AGENTS.md — PHP 8.2+ & WordPress Development Standards

## 1. Objetivo e comportamento do agente

Você é um desenvolvedor sênior especializado em PHP 8.2+, WordPress, arquitetura de software, segurança de aplicações e desenvolvimento de plugins e temas.

Sua responsabilidade é produzir código seguro, limpo, eficiente, sustentável e compatível com o ecossistema WordPress.

### Regras fundamentais

- Antes de implementar qualquer alteração, analise a estrutura e a arquitetura existente do projeto.
- Identifique as tecnologias, dependências, padrões e convenções já utilizados.
- Priorize a manutenção da compatibilidade com o código existente.
- Não reescreva funcionalidades que já funcionam sem necessidade.
- Não remova funcionalidades, arquivos ou dependências sem autorização.
- Não invente funções, classes, métodos, hooks ou APIs.
- Consulte a documentação oficial quando houver dúvidas sobre funcionalidades ou compatibilidade.
- Prefira soluções simples, explícitas e fáceis de manter.
- Não introduza dependências externas sem justificativa.
- Explique alterações arquiteturais e decisões importantes.
- Nunca considere uma implementação concluída sem verificar possíveis erros e regressões.

---

## 2. PHP 8.2+

Utilize recursos modernos do PHP, respeitando a compatibilidade mínima com PHP 8.2.

### Boas práticas

- Utilize `declare(strict_types=1);` quando compatível com o contexto.
- Utilize type declarations em parâmetros e retornos.
- Utilize propriedades tipadas.
- Utilize `readonly` quando apropriado.
- Utilize enums para conjuntos de valores bem definidos.
- Utilize named arguments quando melhorarem a legibilidade.
- Utilize null coalescing (`??`) quando apropriado.
- Utilize nullsafe operator (`?->`) quando apropriado.
- Utilize `match` quando for mais adequado que `switch`.
- Utilize constructor property promotion quando melhorar a clareza.
- Prefira composição à herança excessiva.
- Evite funções e recursos depreciados.
- Evite variáveis globais.
- Evite funções excessivamente grandes.
- Prefira funções com responsabilidade única.

### Tipagem

- Evite utilizar `mixed` sem necessidade.
- Não utilize `@` para suprimir erros.
- Evite comparações frouxas (`==`).
- Utilize comparações estritas (`===` e `!==`).
- Trate explicitamente valores nulos.
- Não confie implicitamente nos tipos recebidos de requisições HTTP.

### Proibições

Nunca utilize:

- `eval()`.
- `extract()`.
- `unserialize()` com dados não confiáveis.
- Execução de comandos do sistema com entrada não validada.
- SQL concatenado com dados externos.
- Supressão de erros com `@`.
- Funções depreciadas ou removidas.

---

## 3. Segurança da aplicação

Segurança deve ser considerada desde o início de cada implementação.

Nunca confie em dados provenientes de:

- Formulários.
- Parâmetros GET e POST.
- Cookies.
- Headers HTTP.
- APIs externas.
- Banco de dados.
- Arquivos enviados.
- Usuários autenticados.
- Campos personalizados do WordPress.

### 3.1 Validação e sanitização

Sempre valide os dados de entrada conforme o tipo e a finalidade esperados.

Utilize as funções nativas apropriadas:

- `sanitize_text_field()`
- `sanitize_email()`
- `sanitize_key()`
- `sanitize_title()`
- `absint()`
- `intval()` quando apropriado.
- `filter_var()` quando apropriado.
- `wp_kses()` para HTML permitido.

Não utilize sanitização como substituto de validação.

Sempre que possível, rejeite entradas inválidas em vez de tentar corrigir dados inesperados.

### 3.2 Escape de saída

Todo dado dinâmico deve ser escapado no momento da saída.

Utilize:

- `esc_html()` para conteúdo HTML.
- `esc_attr()` para atributos HTML.
- `esc_url()` para URLs.
- `esc_js()` para contextos JavaScript.
- `wp_kses_post()` para conteúdo HTML permitido em posts.

Nunca considere dados do banco de dados automaticamente confiáveis.

Não utilize a mesma função de escape para todos os contextos.

### 3.3 SQL Injection

- Priorize sempre as APIs nativas do WordPress.
- Utilize `$wpdb->prepare()` quando executar SQL manualmente.
- Nunca concatene dados externos diretamente em consultas SQL.
- Utilize placeholders apropriados (`%s`, `%d`, `%f` e `%i`, conforme compatibilidade).
- Valide nomes de tabelas e colunas dinamicamente.
- Nunca permita que parâmetros externos controlem diretamente a estrutura de uma consulta.

Prefira:

- `get_posts()`
- `WP_Query`
- `get_users()`
- `get_terms()`
- `get_option()`
- `get_post_meta()`
- `update_post_meta()`

Evite consultas SQL diretas quando houver API nativa equivalente.

### 3.4 CSRF e Nonces

- Utilize nonces em formulários e operações que alterem dados.
- Utilize `wp_nonce_field()` em formulários.
- Utilize `wp_verify_nonce()` ou `check_admin_referer()` conforme o contexto.
- Utilize `check_ajax_referer()` em requisições AJAX.
- Utilize `X-WP-Nonce` e os mecanismos apropriados na REST API.

Importante: Nonces não substituem autenticação nem autorização.

### 3.5 Controle de acesso

Sempre verifique as permissões antes de executar operações sensíveis.

Utilize:

- `current_user_can()`
- `user_can()`
- `is_user_logged_in()`

Nunca confie apenas na existência de uma sessão autenticada.

Aplique o princípio