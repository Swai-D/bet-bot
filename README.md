
# ⚽ BetPawa Automation Bot

A professional betting automation system that integrates with **BetPawa**, **Adibet**, and **Odds API** to provide automated betting capabilities.

---

## 🔥 Features

- 🔐 Secure BetPawa login via Puppeteer or Cookie Injection  
- 📊 Adibet match scraping with intelligent filtering  
- 📈 Real-time odds fetching via Odds API  
- 🤖 Automated bet placement with configurable rules  
- 🧠 Intelligent match scoring and prioritization  
- 📱 Modern web interface for monitoring and control  
- 🔔 Real-time notifications and logging  
- 🗄️ Backup & Restore system  
- 🔧 Extensive CLI commands for control and testing  

---

## 🛠️ Tech Stack

- Laravel (Backend Framework)  
- Puppeteer (Browser Automation)  
- Vue.js + Tailwind CSS (Frontend)  
- WebSockets (Real-time Updates)  
- MySQL (Database)  

---

## 🚀 Setup Instructions

1. **Clone repository**
2. **Install dependencies**
```bash
composer install
npm install
````

3. **Configure Environment**

```bash
cp .env.example .env
```

Update `.env`:

```
BETPAWA_PHONE=your_phone
BETPAWA_PASSWORD=your_password
ODDS_API_KEY=your_api_key
```

4. **Run Migrations**

```bash
php artisan migrate
```

5. **Start Server**

```bash
php artisan serve
npm run dev
```

---

## 📁 Project Structure

```
├── app/
│   ├── Console/Commands/    # Artisan commands
│   ├── Http/Controllers/    # Web Controllers
│   ├── Models/              # Eloquent Models
│   ├── Services/            # Core services
│   └── Providers/
│
├── config/                  # Betting, services, scraper configs
├── database/                # Migrations & Seeders
├── resources/
│   ├── js/components/       # Vue Components
│   ├── js/pages/
│   └── views/               # Blade views
│
├── routes/
│   ├── web.php              # Web Routes
│   └── api.php              # API Routes
│
├── public/screenshots/     # Saved Screenshots
├── storage/logs/           # Log files
├── betpawa-bot.js
├── betpawa-login.js
├── adibet-scraper.js
└── test-bet.js
```

---

## ⚙️ Configuration

### League Scores

Located in `AdibetScraperService.php`:

```php
protected $leagueScores = [
    'top' => [
        'England' => 5,
        'Germany' => 4,
        'Spain' => 4,
        'Italy' => 3,
        'France' => 3
    ],
    'moderate' => [
        'Netherlands' => 2,
        'Portugal' => 2,
        'Turkey' => 2,
        // ...
    ]
];
```

### Important Matches

```php
protected $importantMatches = [
    'Manchester' => ['United', 'City'],
    'Barcelona' => ['Real Madrid'],
    // Add more rivalries here
];
```

---

## 🧩 Settings Structure (Per User)

Stored in `settings` table:

| Key                 | Description                      | Default |
| ------------------- | -------------------------------- | ------- |
| `user_id`           | User identifier                  | -       |
| `min_odds`          | Minimum odds to consider         | `2.00`  |
| `auto_select_count` | Number of matches to auto select | `3`     |
| `bet_amount`        | Amount to bet per match          | `1000`  |
| `selection_mode`    | `auto` or `manual`               | `auto`  |
| `auto_run_scraper`  | Run scraper automatically        | `false` |

---

## 💻 Web Usage

1. Visit: `http://localhost:8000`
2. Login & set preferences
3. Start automation from dashboard
4. Monitor and manage in real-time

---

## 🧪 Artisan CLI Commands

### 🔍 Predictions

```bash
php artisan predictions:show
php artisan predictions:show --best
php artisan predictions:show --moderate
php artisan predictions:show --date=2025-05-16

php artisan predictions:save
php artisan predictions:list
php artisan predictions:delete --date=2025-05-16 --force
```

### 📦 Backup Management

```bash
php artisan backup:create
php artisan backup:list
php artisan backup:restore {backup_id}
php artisan backup:delete {backup_id}
php artisan backup:cleanup --days=30
```

### ⚙️ Betting Engine

```bash
php artisan betting:run
php artisan betting:test-integration --platform=betpawa
```

Options:

* `--dry-run`
* `--force`
* `--bet-id`
* `--date`
* `--status`
* `--export`
* `--format`
* `--sort`

### 🕸️ Scraper Commands

```bash
php artisan scrape:adibet
php artisan scrape:test --url=https://example.com --verbose
```

### 🧹 Maintenance

```bash
php artisan cleanup:logs --days=15
php artisan cleanup:predictions --days=30
```

### 🔄 Import/Export

```bash
php artisan predictions:import predictions.csv --format=csv --validate
php artisan predictions:export predictions.json --format=json
php artisan predictions:import-historical history.json --start-date=2024-01-01 --end-date=2024-06-01
```

### ✅ Testing Commands

```bash
php artisan test:place-bet --amount=2000 --type=single
php artisan test:odds-integration --platform=oddsapi
php artisan test:betpawa-login --verbose
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit changes
4. Push and submit PR

---

## 📜 License

MIT License - see `LICENSE`

---

## ⚠️ Disclaimer

This bot is for **educational purposes** only. Use at your own risk. The developers are not responsible for any financial losses.

---

## 📞 Support

1. Open an issue
2. Check documentation
3. Contact maintainers

---

## 🔄 Regular Updates Include

* League & match data refresh
* Bug fixes
* Improved scoring logic
* Enhanced automation rules


