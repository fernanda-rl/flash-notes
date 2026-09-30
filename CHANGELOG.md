# CHANGELOG

Registro das alterações de segurança, organização e documentação
feitas no Flashnotes.

---

## [Não publicado] — 21/09/2026

### 1. Segredos removidos do código

**O problema:** a senha de app do Gmail estava escrita em texto puro em
três arquivos (`app/helpers/enviar_email.php`, `public/esqueciasenha.php`
e `public/faleconosco.php`), e as credenciais do banco apareciam repetidas
em sete arquivos.

**Adicionado**
- `app/config/config.php` — credenciais reais de banco e SMTP (**não versionado**)
- `app/config/config.example.php` — modelo sem valores reais, este sim versionado
- `app/config/carregar_config.php` — função `config('banco.senha')` que lê o arquivo acima
- `app/helpers/csrf.php` — token anti-CSRF reaproveitável
- `app/helpers/sessao.php` — sessão com cookie `HttpOnly` e `SameSite=Lax`
- `.gitignore` — cobre `config.php`, `uploads/`, `vendor/`, backups `.sql` e `.claude/`

**Alterado**
- `app/config/conexao.php` — passou a ler do config e virou o ponto único de conexão.
  Também detecta conexão já fechada (via `ping`) e reabre.
- `app/helpers/enviar_email.php` — lê SMTP do config; ganhou parâmetros para
  `Reply-To` e texto alternativo, que antes só existiam no código solto do
  "Fale conosco". O erro detalhado do SMTP passou a ir para o log, não para a tela.
- `public/faleconosco.php` e `public/esqueciasenha.php` — deixaram de montar o
  PHPMailer na mão e passaram a usar `enviarEmail()`
- `public/cadastro.php`, `dashboard.php`, `tarefas.php`, `horarios.php`,
  `tema.php`, `actions/crud_tarefas.php` — deixaram de criar
  `new mysqli(...)` e passaram a usar `conexao.php`

> ⚠️ **Ação necessária sua:** a senha de app antiga ficou exposta e precisa ser
> **revogada no Google**. Gere uma nova e coloque em `app/config/config.php`,
> no campo `'email' => 'senha'` (hoje está com o texto `COLOQUE_A_NOVA_SENHA_DE_APP`).
> Enquanto isso não for feito, os e-mails não serão enviados.

---

### 2. Recuperação de senha refeita (`public/esqueciasenha.php`)

**Como era:** o código de 6 dígitos vinha de `rand()`, ficava guardado em
texto puro na sessão, não expirava, aceitava tentativas infinitas, a tela
dizia "E-mail não encontrado" (revelando quem tem conta) e a etapa 3
confiava apenas em uma flag de sessão.

**Como ficou**
- `rand()` → `random_int()` (gerador criptográfico)
- O código vai para o banco como **hash bcrypt** em `token_recuperacao`,
  com validade em `expiracao_token` — as duas colunas já existiam e não eram usadas
- **Expira em 15 minutos** (configurável em `config.php`)
- **Limite de 5 tentativas**; ao estourar, o código é apagado do banco
- A etapa 3 **revalida o código no banco** antes de gravar a nova senha,
  em vez de confiar só na sessão
- Resposta sempre igual — *"Se o e-mail estiver cadastrado, enviaremos um código"* —
  exista ou não a conta
- O código é **invalidado no mesmo UPDATE** que grava a senha nova
- `session_regenerate_id(true)` ao concluir

---

### 3. Login e sessões

**Alterado — `public/login.php`**
- `session_regenerate_id(true)` após o login (evita fixação de sessão)
- "Senha incorreta" e "Usuário não encontrado" viraram uma única mensagem:
  **"E-mail ou senha incorretos"**
- Limite de **5 tentativas** com bloqueio de **15 minutos**
- Erros do banco não são mais exibidos na tela

**Alterado — sessões em todo o sistema**
- 15 arquivos trocaram `session_start()` por `app/helpers/sessao.php`,
  que configura o cookie com `HttpOnly`, `SameSite=Lax`, `Secure` (quando HTTPS)
  e `session.use_strict_mode`

**Adicionado — proteção CSRF**
- Token em **todos os formulários POST**: login, cadastro, recuperação de senha,
  fale conosco, disciplinas, caderno, tarefas e configurações
- Validação em **todas as actions**: `crud_disciplinas`, `crud_tarefas`,
  `crud_anotacoes`, `crud_arquivos` e `crud_agenda`
- `crud_agenda.php` responde a falha de token em JSON, por ser chamado via AJAX;
  `public/js/agenda.js` passou a enviar o token nas três requisições

---

### 4. Segurança dos uploads (`public/actions/crud_arquivos.php`)

- Além da extensão, o **tipo real do arquivo** é verificado com `finfo`
  e precisa combinar com a extensão declarada. Um `.php` renomeado para
  `.pdf` agora é recusado.
- A tabela de tipos aceitos trata os casos em que um formato tem mais de um
  MIME válido (`.docx`/`.xlsx`/`.pptx` são ZIP por dentro; `.txt` às vezes é
  detectado como `application/octet-stream`)
- Confirmado que o download continua **exclusivamente** por
  `actions/baixar_arquivo.php`, que confere o dono antes de entregar,
  e que a pasta de uploads segue bloqueada por `.htaccess`

---

### 5. Limpeza e correções

**Adicionado**
- `database/seed_exemplo.sql` — dois usuários fictícios (Ana e Bruno, senha
  `senha123`) com disciplinas, aulas, anotações, tarefas e eventos, para
  demonstrar o sistema sem expor dados reais

**Corrigido**
- E-mail com erro de digitação `flahsnotes@email` → `flashnotess@gmail.com`
  em `index.html`, `login.php`, `cadastro.php` e `esqueciasenha.php`
- Link morto `<a href="#">@flashnotes</a>` virou texto simples nas mesmas telas
- `public/cadastro.php` — o item "Fale conosco" do menu apontava para `#`

---

### 6. Documentação

- `README.md` reescrito por completo (antes tinha só `# flashnotes`):
  descrição, funcionalidades, tecnologias, requisitos, instalação passo a passo,
  estrutura de pastas comentada, guia de uso, seção de segurança e solução
  de problemas
- Documentado o **agendamento** de `verificar_notificacoes.php` (diário) e
  `resumo_semanal.php` (semanal), com instruções para o Agendador de Tarefas
  do Windows (interface e `schtasks`) e para o cron do Linux/Mac

---

### 7. Decisões tomadas

**Coluna `usuarios.tipo_perfil` removida**

A coluna aceitava 'estudante' e 'professor', mas nenhuma tela lia esse
campo e todas as 9 contas do banco eram 'estudante' — o banco descrevia
um recurso que o sistema não tinha.

- Adicionado `database/migracao_remover_tipo_perfil.sql`, que confere os
  dados antes de remover (se houvesse alguma conta 'professor', a
  consulta inicial mostraria)
- Migração aplicada; backup em
  `database/backup_usuarios_antes_remover_perfil_20260921_163213.sql`
- `public/cadastro.php` — o `INSERT` não grava mais o campo
- `database/flashnotes_estrutura.sql` e `database/seed_exemplo.sql` atualizados
- O perfil de professor com compartilhamento de material ficou registrado
  no README como **trabalho futuro**, com a explicação do que seria preciso
  (tabela de turmas, vínculo aluno–turma e revisão das regras de acesso,
  já que hoje toda consulta filtra por `usuario_id`)

**Opção "Notificações de Navegador" renomeada**

O rótulo prometia uma notificação do sistema operacional, mas o que o
sistema faz é mostrar os avisos no ícone de sino, dentro do site.

- `public/configuracoes.php` — passou a **"Notificações no sistema"**, com a
  descrição *"Veja os avisos de tarefas e provas no ícone de sino, aqui
  dentro do Flashnotes"*
- `app/controllers/crud_configuracoes.php` — o e-mail de confirmação de
  preferências usa o mesmo texto
- A coluna `notificacao_navegador` foi **mantida** com esse nome: renomeá-la
  exigiria mexer em 8 pontos do código sem nenhum ganho funcional
- O motivo de não usar a Notification API (HTTPS, Service Worker, Web Push
  e chaves VAPID, inviáveis no XAMPP local) está documentado no README

---

### Correções de regressão encontradas nos testes

- `public/tarefas.php` e `public/horarios.php` quebravam com
  *"mysqli object is already closed"* depois da centralização da conexão:
  elas fechavam `$conn` antes do HTML e o `require_once` da sidebar não
  reabria. O `close()` prematuro foi removido.

---

## Pendências

### Precisa de ação sua
1. **Revogar a senha de app do Gmail** exposta e colocar a nova em
   `app/config/config.php`. Sem isso, nenhum e-mail é enviado.
   *Esta é a única pendência que depende de você.*

### Arquivos a remover antes da entrega final
Não foram apagados, conforme combinado — apenas listados:

| Arquivo | Motivo |
|---|---|
| `database/backup_antes_cadernos_*.sql` | Contém e-mails e hashes de senha reais |
| `database/backup_usuarios_antes_tema_*.sql` | Contém e-mails e hashes de senha reais |
| `database/flashnotes.sql` | Dump antigo **com dados reais** e estrutura desatualizada (anterior aos Cadernos) |
| `public/uploads/disciplinas/*.pdf` | Material pessoal enviado durante os testes |
| `.claude/` | Arquivos do assistente de IA, já no `.gitignore` |

**Manter:** `flashnotes_estrutura.sql` (cria o banco), `seed_exemplo.sql`
(dados fictícios) e as duas migrações (`migracao_cadernos.sql` e
`migracao_tema.sql`), que documentam a evolução do banco.

### Melhorias que ficaram de fora
- O limite de tentativas de login é **por sessão**, não por IP. Contorna-se
  limpando os cookies. Um controle real exigiria registrar as tentativas em
  tabela, indexadas por IP.
- A tabela `notificacoes` não tem chave estrangeira para `usuarios`
  (é assim desde o início; foi mantido para não alterar o que não foi pedido).
- Não há HTTPS no ambiente local, então o cookie de sessão só recebe a flag
  `Secure` quando o sistema for publicado em um servidor com certificado.
