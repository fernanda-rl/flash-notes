# Flashnotes

Sistema web de organização de estudos. O estudante cadastra suas disciplinas
e, em cada uma delas, mantém um caderno com anotações, arquivos de aula,
tarefas e eventos — tudo reunido em um lugar só, com lembretes por e-mail.

Projeto em PHP com MySQL, feito para rodar em XAMPP.

---

## Funcionalidades

**Contas e acesso**
- Cadastro com nome, e-mail e senha (senha guardada como hash `bcrypt`)
- Login com limite de tentativas e bloqueio temporário
- Recuperação de senha por código enviado ao e-mail, com validade e
  limite de tentativas
- Alteração de nome, e-mail e senha
- Exclusão da própria conta

**Cadernos (o coração do sistema)**
- Uma disciplina = um caderno, com nome, professor e cor
- **Anotações**: criar, editar e excluir
- **Arquivos**: enviar PDFs e materiais de aula (até 10 MB), abrir ou baixar
- **Tarefas** e **Eventos** vinculados à disciplina, criados de dentro do caderno
- **Aulas**: horários da disciplina na semana

**Organização**
- Dashboard com tarefas pendentes, próximos eventos e as aulas do dia
- Tarefas com prioridade, status e vencimento
- Agenda com calendário mensal
- Grade de horários semanal

**Notificações e preferências**
- Avisos de tarefas que vencem e de provas próximas, no ícone de sino
- E-mail de lembrete e resumo semanal (opcionais)
- Tema **claro**, **escuro** ou **automático** (acompanha o navegador)

---

## Tecnologias

| Camada | O que é usado |
|---|---|
| Servidor | PHP 8 |
| Banco de dados | MySQL / MariaDB |
| Front-end | HTML5, CSS3 e JavaScript puro (sem framework) |
| E-mail | PHPMailer via SMTP |
| Dependências | Composer |
| Ambiente | XAMPP (Apache + MySQL) |

---

## Requisitos

- **XAMPP** com Apache e MySQL (PHP 8.0 ou superior)
- **Composer** — https://getcomposer.org
- Uma conta de e-mail com SMTP para os envios. No Gmail é preciso gerar uma
  **senha de app** (Conta Google → Segurança → Senhas de app); a senha normal
  da conta não funciona.

---

## Instalação

### 1. Colocar os arquivos no lugar

Copie a pasta do projeto para dentro de `htdocs` do XAMPP:

```
C:\xampp\htdocs\flashnotes
```

### 2. Ligar o Apache e o MySQL

Abra o **Painel de Controle do XAMPP** e clique em *Start* em **Apache** e
em **MySQL**.

### 3. Criar o banco de dados

Abra o phpMyAdmin em <http://localhost/phpmyadmin>, vá na aba **Importar**,
escolha o arquivo abaixo e clique em *Executar*:

```
database/flashnotes_estrutura.sql
```

Esse arquivo cria o banco `flashnotes` com todas as tabelas e também o
usuário de banco `flashuser`, que é quem a aplicação usa para conectar.

> **Atenção:** o arquivo começa com `DROP DATABASE IF EXISTS flashnotes`.
> Se você já tiver um banco com esse nome e quiser preservá-lo, comente
> essa linha antes de importar.

Pela linha de comando o equivalente é:

```bash
mysql -u root -p < database/flashnotes_estrutura.sql
```

### 4. Instalar as dependências

Na pasta do projeto:

```bash
composer install
```

Isso cria a pasta `vendor/` com o PHPMailer.

### 5. Configurar

Copie o arquivo de modelo e preencha com os seus dados:

```bash
# Windows
copy app\config\config.example.php app\config\config.php

# Linux / Mac
cp app/config/config.example.php app/config/config.php
```

Depois abra `app/config/config.php` e ajuste:

```php
'banco' => [
    'host'    => 'localhost',
    'usuario' => 'flashuser',
    'senha'   => '1234',        // a senha definida no SQL
    'nome'    => 'flashnotes',
],

'email' => [
    'usuario' => 'seu-email@gmail.com',
    'senha'   => 'sua senha de app',   // 16 caracteres, do Google
    // ...
],
```

> `config.php` **não vai para o controle de versão** — ele está no
> `.gitignore` porque contém senhas. O que é versionado é o
> `config.example.php`, sem valores reais.

### 6. Acessar

<http://localhost/flashnotes/public/>

Crie uma conta pela tela de cadastro e pronto.

---

## Estrutura de pastas

```
flashnotes/
│
├── app/                          Código que não é acessado direto pelo navegador
│   ├── config/
│   │   ├── config.php            Senhas reais (NÃO versionado)
│   │   ├── config.example.php    Modelo do config, sem senhas
│   │   ├── carregar_config.php   Função config() que lê o arquivo acima
│   │   └── conexao.php           Conexão única com o banco ($conn)
│   │
│   ├── controllers/
│   │   └── crud_configuracoes.php   Processa a tela de Configurações
│   │
│   ├── helpers/                  Funções reaproveitadas pelo sistema
│   │   ├── csrf.php              Token anti-CSRF dos formulários
│   │   ├── disciplinas.php       Busca e validação de disciplinas
│   │   ├── enviar_email.php      Envio de e-mail (ponto único)
│   │   └── sessao.php            Sessão com cookie HttpOnly/SameSite
│   │
│   └── scripts/                  Rotinas executadas por agendamento
│       ├── verificar_notificacoes.php   Avisos de tarefas e provas (diário)
│       └── resumo_semanal.php           Resumo da semana (semanal)
│
├── database/
│   ├── flashnotes_estrutura.sql  Cria o banco do zero (use este)
│   └── seed_exemplo.sql          Dados fictícios para demonstração
│
├── public/                       Raiz do site — o que o navegador acessa
│   ├── index.html                Página inicial
│   ├── login.php  cadastro.php  esqueciasenha.php  faleconosco.php
│   ├── dashboard.php             Painel com o resumo do dia
│   ├── disciplinas.php           Lista de disciplinas / cadernos
│   ├── caderno.php               Caderno de uma disciplina (abas)
│   ├── tarefas.php  agenda.php  horarios.php  configuracoes.php
│   ├── sidebar.php               Menu lateral e barra superior
│   ├── tema.php                  Resolve o tema antes de montar a página
│   │
│   ├── actions/                  Recebem os formulários (POST)
│   │   ├── crud_disciplinas.php  crud_tarefas.php  crud_agenda.php
│   │   ├── crud_anotacoes.php    crud_arquivos.php
│   │   └── baixar_arquivo.php    Entrega arquivos conferindo o dono
│   │
│   ├── css/  js/  img/  icons/
│   └── uploads/disciplinas/      Arquivos enviados (protegido por .htaccess)
│
├── vendor/                       Dependências do Composer (gerado)
├── composer.json
└── README.md
```

---

## Como usar

1. **Crie sua conta** em *Cadastre-se* e faça login.
2. **Cadastre as disciplinas** em *Disciplinas → Adicionar disciplina*.
   Informe nome, professor e uma cor; a primeira aula é opcional.
3. **Abra o caderno** clicando no card da disciplina. Lá dentro você tem:
   - **Anotações** — resumos e conteúdos de aula
   - **Arquivos** — PDFs e materiais (abrir ou baixar)
   - **Tarefas** e **Eventos** — criados já vinculados à disciplina
   - **Aulas** — os horários dela na semana
4. **Acompanhe pelo Dashboard**, que mostra o que vence, o que está por vir
   e as aulas de hoje. Os cards são clicáveis.
5. **Ajuste o sistema** em *Configurações*: nome, e-mail, senha,
   notificações e tema (claro / escuro / automático).

---

## Agendamento das rotinas de e-mail

Dois scripts precisam rodar periodicamente. **Eles não são executados
sozinhos** — é preciso agendá-los no sistema operacional.

| Script | Frequência sugerida | O que faz |
|---|---|---|
| `app/scripts/verificar_notificacoes.php` | Diária, de manhã | Avisa sobre tarefas que vencem e provas próximas |
| `app/scripts/resumo_semanal.php` | Semanal, segunda-feira | Envia o resumo da semana a quem ativou a opção |

Antes de agendar, teste na mão para confirmar que funcionam:

```bash
C:\xampp\php\php.exe C:\xampp\htdocs\flashnotes\app\scripts\verificar_notificacoes.php
```

### Windows — Agendador de Tarefas

1. Abra o menu Iniciar e procure por **Agendador de Tarefas**.
2. No painel da direita, clique em **Criar Tarefa** (não em "tarefa básica",
   porque precisamos da aba Ações).
3. Na aba **Geral**: dê o nome `Flashnotes - Notificações diárias` e marque
   *Executar estando o usuário conectado ou não*.
4. Na aba **Disparadores** → *Novo*: escolha **Diariamente**, defina o
   horário (por exemplo 07:00) e confirme.
5. Na aba **Ações** → *Novo*:
   - **Programa/script:** `C:\xampp\php\php.exe`
   - **Adicione argumentos:** `C:\xampp\htdocs\flashnotes\app\scripts\verificar_notificacoes.php`
   - **Iniciar em:** `C:\xampp\htdocs\flashnotes\app\scripts`
6. Clique em OK e informe a senha do Windows quando for pedida.

Repita para o resumo semanal, mudando o disparador para **Semanalmente**,
segunda-feira, e o argumento para `resumo_semanal.php`.

O mesmo pode ser feito por linha de comando (Prompt como Administrador):

```cmd
schtasks /create /tn "Flashnotes - Notificacoes" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\flashnotes\app\scripts\verificar_notificacoes.php" /sc daily /st 07:00

schtasks /create /tn "Flashnotes - Resumo Semanal" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\flashnotes\app\scripts\resumo_semanal.php" /sc weekly /d MON /st 08:00
```

### Linux / Mac — cron

Abra o editor do cron:

```bash
crontab -e
```

E acrescente as duas linhas (ajustando os caminhos):

```cron
# Notificações de tarefas e provas — todo dia às 07:00
0 7 * * * /usr/bin/php /var/www/flashnotes/app/scripts/verificar_notificacoes.php >> /var/log/flashnotes.log 2>&1

# Resumo semanal — toda segunda-feira às 08:00
0 8 * * 1 /usr/bin/php /var/www/flashnotes/app/scripts/resumo_semanal.php >> /var/log/flashnotes.log 2>&1
```

O `>> ... 2>&1` no final grava a saída em um arquivo de log, o que ajuda a
descobrir o motivo caso um envio falhe.

---

## Segurança

O que já está implementado:

- Todas as consultas usam **prepared statements** (sem concatenar SQL)
- Toda saída em HTML passa por **`htmlspecialchars`**
- Senhas guardadas com **`password_hash`** (bcrypt)
- **Token CSRF** em todos os formulários POST
- Cookie de sessão com **HttpOnly** e **SameSite=Lax**
- **`session_regenerate_id`** após o login
- Mensagens de erro genéricas no login e na recuperação de senha, para não
  revelar quais e-mails existem
- Uploads validados por **extensão e tipo real** (`finfo`), gravados com nome
  aleatório em pasta protegida por `.htaccess`
- Download apenas via `actions/baixar_arquivo.php`, que confere o dono
- Credenciais fora do código, em `config.php` não versionado

---

## Possibilidades de trabalho futuro

Ideias que foram consideradas e ficaram deliberadamente fora do escopo
desta versão:

**Perfil de professor e compartilhamento de material**
O banco chegou a ter uma coluna `usuarios.tipo_perfil` com os valores
'estudante' e 'professor', mas nenhuma funcionalidade usava esse campo —
todas as contas eram criadas como 'estudante'. A coluna foi removida
(`database/migracao_remover_tipo_perfil.sql`) para que o banco não
descrevesse um recurso que o sistema não tem.

Uma evolução natural seria criar o perfil de professor de verdade: ele
cadastraria uma disciplina, marcaria materiais como compartilhados e os
alunos vinculados àquela turma veriam esses arquivos em seus próprios
cadernos. Isso exigiria uma tabela de turmas, o vínculo entre alunos e
turma, e uma revisão das regras de acesso — hoje toda consulta filtra
por `usuario_id`, partindo do princípio de que o conteúdo é individual.

**Notificações fora do navegador**
A opção "Notificações no sistema" mostra os avisos no ícone de sino,
dentro do Flashnotes. Para que aparecessem como notificação do sistema
operacional mesmo com o site fechado, seria preciso a Notification API
com um Service Worker e Web Push, que exige HTTPS, chaves VAPID e um
serviço de push — algo que não funciona no XAMPP local. A opção foi
nomeada de acordo com o que ela realmente faz.

**Controle de tentativas de login por IP**
O limite de tentativas usado hoje vive na sessão do visitante, o que
segura o chute repetido de senha no mesmo navegador, mas é contornável
limpando os cookies. Uma versão mais robusta registraria as tentativas
em uma tabela indexada por IP.

---

## Solução de problemas

**"Arquivo de configuração não encontrado"**
Você pulou o passo 5. Copie `config.example.php` para `config.php`.

**"Erro na conexão" ao abrir o site**
O MySQL não está ligado no painel do XAMPP, ou o usuário/senha em
`config.php` não batem com os do banco.

**Os e-mails não chegam**
Confira se a senha em `'email' => 'senha'` é uma **senha de app** do Google
(16 caracteres), e não a senha normal da conta. Verifique também se a porta
587 não está bloqueada pelo antivírus ou pela rede.

**"Sua sessão expirou" ao enviar um formulário**
O token CSRF venceu porque a página ficou aberta por muito tempo.
Recarregue a página e envie de novo.

**Erro ao enviar arquivo**
Arquivos acima de 10 MB são recusados. Se precisar de mais, ajuste
`upload_max_filesize` e `post_max_size` no `php.ini` e a constante
`TAMANHO_MAXIMO_ARQUIVO` em `public/actions/crud_arquivos.php`.
