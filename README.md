# Finance Flow 💰
Um sistema moderno para gerenciamento financeiro desenvolvido com Laravel 12 e Livewire 4. Gerencie suas despesas, receitas, investimentos e tenha uma visão de todo seu fluxo de caixa de forma intuitiva e em tempo real.

## 🌟 Recursos

### Funcionalidades principais
- **Gerenciamento de despesas** - Monitore e categorize suas despesas de acordo com a forma de pagamento, tipo (variável, fixa, parcelada) e status.
- **Gerenciamento de receitas** - Registre suas fontes de renda e monitore as origens, as formas de recebimento e o status.
- **Gerenciamento de investimentos** - Monitor de investimentos com descrição, status, instituição, categoria e tipo.
- **Fluxo de caixa** - Visualize o seu fluxo financeiro ao longo dos meses com entradas e saídas consolidadas, além da projeção para cada mês.
- **Autenticação** - Cadastro seguro, login e funcionalidade de reset de senha via e-mail.

### Destaques técnicos
- Atualizações em tempo real com componentes livewire;
- Design responsivo com TailwindCSS;
- PHP 8.2+ com Laravel 12;
- Banco de dados MySQL;
- Gerenciamento de consentimento de cookies;
- Controle de acesso baseado em funções;

## 🚀 Execução local

### Pré-requisitos
- PHP 8.2+
- Node.js 18+ (para ferramentas de frontend)
- Composer
- MySQL

### Instalação

1. **Clonar o repositório**
   ```bash
   git clone <repository-url>
   cd finance-flow
   ```

2. **Instalar dependências**
   ```bash
   composer install
   npm install
   ```

3. **Ambiente de configuração**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar banco de dados e criar ativos**
   ```bash
   composer run setup
   ```

   Ou manualmente:
   ```bash
   php artisan migrate
   npm run build
   ```

### Desenvolvimento

Inicie o servidor de desenvolvimento com recarregamento em tempo real:

```bash
composer run dev
```

Esse comando inicial:
- servidor de desenvolvimento Laravel
- Visualizador de logs
- Servidor Vite para compilação de assets

## 📁 Estrutura do projeto

```
finance-flow/
├── app/
│   ├── Http/Controllers/      # Request handlers
│   ├── Mail/                  # Email classes
│   ├── Models/                # Database models (Expense, Revenue, Investment, User)
│   └── Providers/             # Service providers
├── resources/
│   ├── css/                   # Tailwind CSS
│   ├── js/                    # Frontend JavaScript
│   ├── views/                 # Blade templates
│   └── livewire/              # Livewire components
├── routes/
│   ├── web.php                # Web routes (authentication & dashboard routes)
│   └── console.php            # Console commands
├── database/
│   ├── migrations/            # Database schema
│   ├── factories/             # Model factories for testing
│   └── seeders/               # Database seeders
├── config/                    # Configuration files
├── tests/                     # Test suites
└── public/                    # Publicly accessible assets
```

## 🔧 Demais comandos

### Desenvolvimento
```bash
# Inicie o servidor de desenvolvimento com todos os serviços.
composer run dev

# Execute apenas o servidor de desenvolvimento do Laravel.
php artisan serve

# Execute o servidor de desenvolvimento do Vite
npm run dev

# Crie ativos de front-end
npm run build
```

## 🎨 Frontend Stack

- **Livewire 4** - Real-time interactive components
- **Tailwind CSS 4** - Utility-first CSS framework
- **Vite** - Modern frontend build tool
- **Alpine.js** - Lightweight JavaScript framework

## 📝 Licença

Este projeto é um software de código aberto licenciado sob a licença [MIT license](LICENSE).

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para enviar solicitações de pull request ou abrir issues para relatar bugs e sugerir novas funcionalidades.

## 📧 Suporte

Para suporte, dúvidas ou feedback, abra uma solicitação no repositório.