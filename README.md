# 📅 Agenda Master

Sistema inteligente de agendamento profissional desenvolvido em PHP puro com arquitetura MVC.

## 🚀 Características

- ✅ **Calendário Interativo** - FullCalendar com 4 modos de visualização (Mês, Semana, Dia, Agenda)
- ✅ **Gestão de Clientes** - Cadastro completo com cores personalizadas
- ✅ **Tags de Serviços** - Categorize seus agendamentos com ícones e cores
- ✅ **Sistema de Segurança** - reCAPTCHA v3, CSRF, Rate Limiting, Account Lock
- ✅ **Autenticação Completa** - Login, registro, recuperação de senha
- ✅ **Validação de Senha** - Gerador automático de senhas fortes
- ✅ **E-mails Transacionais** - PHPMailer com templates HTML
- ✅ **Logs de Segurança** - Rastreamento completo de ações
- ✅ **Responsivo** - Design moderno com TailwindCSS
- ✅ **Dark Mode Ready** - Interface preparada para tema escuro

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Composer
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, mbstring, openssl, json

## 🔧 Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/dantetesta/agendamento.git
cd agendamento
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure o banco de dados

```bash
# Copie o arquivo de exemplo
cp config/database.php.example config/database.php

# Edite com suas credenciais
nano config/database.php
```

### 4. Configure o SMTP (opcional)

```bash
# Copie o arquivo de exemplo
cp config/smtp.php.example config/smtp.php

# Edite com suas credenciais SMTP
nano config/smtp.php
```

### 5. Configure o reCAPTCHA (opcional)

```bash
# Copie o arquivo de exemplo
cp config/recaptcha.php.example config/recaptcha.php

# Obtenha suas chaves em: https://www.google.com/recaptcha/admin
# Edite o arquivo e ative o reCAPTCHA
nano config/recaptcha.php
```

### 6. Importe o banco de dados

```bash
mysql -u seu_usuario -p seu_banco < database/schema.sql
```

### 7. Configure permissões

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

### 8. Acesse o sistema

```
http://localhost/agendamento
```

## 📁 Estrutura do Projeto

```
agendamento/
├── app/                    # Aplicação
│   ├── Controllers/        # Controladores
│   ├── Models/            # Modelos
│   └── Views/             # Views e partials
├── config/                # Configurações
│   ├── app.php           # Config geral
│   ├── database.php      # Config banco (não versionado)
│   ├── smtp.php          # Config SMTP (não versionado)
│   └── recaptcha.php     # Config reCAPTCHA (não versionado)
├── core/                  # Classes core do sistema
│   ├── Database.php      # Conexão PDO
│   ├── Mailer.php        # Envio de e-mails
│   ├── ReCaptcha.php     # Validação reCAPTCHA
│   ├── CSRF.php          # Proteção CSRF
│   ├── RateLimit.php     # Limitação de requisições
│   ├── AccountLock.php   # Bloqueio de contas
│   └── SecurityLogger.php # Logs de segurança
├── public/               # Arquivos públicos
│   ├── assets/          # CSS, JS, imagens
│   ├── uploads/         # Uploads de usuários
│   ├── api/             # Endpoints da API
│   ├── dashboard.php    # Dashboard principal
│   ├── login.php        # Login
│   ├── registro.php     # Registro
│   └── ...
├── storage/             # Armazenamento
│   ├── logs/           # Logs do sistema
│   └── rate_limit/     # Cache de rate limiting
├── vendor/             # Dependências Composer
├── .htaccess          # Configurações Apache
├── routes.php         # Rotas do sistema
└── index.php          # Entry point
```

## 🔐 Segurança

O sistema implementa múltiplas camadas de segurança:

- **reCAPTCHA v3** - Proteção contra bots
- **CSRF Tokens** - Proteção contra ataques CSRF
- **Rate Limiting** - Limitação de tentativas de login
- **Account Lock** - Bloqueio automático após falhas
- **Password Hashing** - Senhas criptografadas com bcrypt
- **Security Headers** - Headers HTTP de segurança
- **Security Logs** - Rastreamento de ações sensíveis
- **SQL Injection Protection** - Prepared statements
- **XSS Protection** - Sanitização de inputs

## 📧 Configuração de E-mail

O sistema usa PHPMailer para envio de e-mails. Configure o SMTP em `config/smtp.php`:

```php
return [
    'host' => 'smtp.seuservidor.com',
    'port' => 465,
    'username' => 'seu@email.com',
    'password' => 'sua_senha',
    'encryption' => 'ssl',
    'from_email' => 'noreply@seusite.com',
    'from_name' => 'Agenda Master',
];
```

## 🎨 Personalização

### Alterar nome e logo do sistema

Edite `config/app.php`:

```php
return [
    'name' => 'Seu Nome Aqui',
    'logo' => [
        'icon' => 'fas fa-calendar-check',
        'text' => 'Seu Logo'
    ],
];
```

### Cores e tema

As cores são configuradas via TailwindCSS. Edite os arquivos em `public/assets/css/`.

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:

- 💻 Desktop
- 📱 Tablet
- 📱 Mobile

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👨‍💻 Autor

**Dante Testa**

- Website: [dantetesta.com.br](https://dantetesta.com.br)
- GitHub: [@dantetesta](https://github.com/dantetesta)

## 🙏 Agradecimentos

- [FullCalendar](https://fullcalendar.io/) - Calendário interativo
- [TailwindCSS](https://tailwindcss.com/) - Framework CSS
- [Font Awesome](https://fontawesome.com/) - Ícones
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Envio de e-mails

---

⭐ Se este projeto foi útil para você, considere dar uma estrela!
