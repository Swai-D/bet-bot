# BetPawa Automation Bot

A professional betting automation system that integrates with BetPawa, Adibet, and Odds API to provide automated betting capabilities.

## Features

- 🔐 Secure BetPawa login via Puppeteer or Cookie Injection
- 📊 Adibet match scraping with intelligent filtering
- 📈 Real-time odds fetching via Odds API
- 🤖 Automated bet placement with configurable rules
- 📱 Modern web interface for monitoring and control
- 🔔 Real-time notifications and logging

## Tech Stack

- Laravel (Backend Framework)
- Puppeteer (Browser Automation)
- Vue.js + Tailwind CSS (Frontend)
- WebSockets (Real-time Updates)
- MySQL (Database)

## Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure:
   ```
   BETPAWA_PHONE=your_phone
   BETPAWA_PASSWORD=your_password
   ODDS_API_KEY=your_api_key
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the development server:
   ```bash
   php artisan serve
   npm run dev
   ```

## Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── BetController.php
│   │   └── AutomationController.php
│   ├── Services/
│   │   ├── BetPawaService.php
│   │   ├── AdibetScraperService.php
│   │   └── OddsApiService.php
│   └── Models/
│       ├── Match.php
│       └── Bet.php
├── resources/
│   └── js/
│       ├── components/
│       └── pages/
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
```

## Security

- All credentials stored in `.env`
- Session management with secure cookies
- Rate limiting on API endpoints
- Automated retry mechanisms
- Comprehensive logging

## Usage

1. Access the web interface at `http://localhost:8000`
2. Configure betting rules and preferences
3. Start automation via the dashboard
4. Monitor bets and results in real-time

## Contributing

### Basic Commands

1. **View all matches**:
```bash
php artisan predictions:show
```
This shows all available matches for today.

2. **View top tier matches**:
```bash
php artisan predictions:show --best
```
Shows only matches from top 5 leagues (England, Germany, Spain, Italy, France).

3. **View moderate tier matches**:
```bash
php artisan predictions:show --moderate
```
Shows matches from other leagues (Netherlands, Portugal, Turkey, etc.).

4. **View matches for specific date**:
```bash
php artisan predictions:show --date=2025-05-16
```
Shows matches for a specific date.

### Available Commands

The betting bot comes with a comprehensive set of commands for managing predictions, backups, and system maintenance:

#### Prediction Management Commands

1. **Show Predictions**
```bash
php artisan predictions:show
```
Displays all available predictions with options:
- `--best`: Show only top tier matches
- `--moderate`: Show only moderate tier matches
- `--date`: Filter by specific date

2. **Save Predictions**
```bash
php artisan predictions:save
```
Saves new predictions to the database with options:
- `--source`: Specify prediction source
- `--date`: Filter by date

3. **List Predictions**
```bash
php artisan predictions:list
```
Lists all stored predictions with filtering options:
- `--status`: Filter by status
- `--date`: Filter by date
- `--league`: Filter by league

4. **Delete Predictions**
```bash
php artisan predictions:delete
```
Removes predictions with options:
- `--date`: Delete predictions for specific date
- `--force`: Force delete without confirmation

#### Backup Management Commands

1. **Create Backup**
```bash
php artisan backup:create
```
Creates a new backup with options:
- `--date`: Filter by date
- `--path`: Specify backup location
- `--type`: Backup type (full/incremental)

2. **List Backups**
```bash
php artisan backup:list
```
Shows all available backups with options:
- `--sort`: Sort by date/size
- `--format`: Output format (table/json)

3. **Restore Backup**
```bash
php artisan backup:restore {backup_id}
```
Restores a specific backup with options:
- `--force`: Force restore without confirmation
- `--validate`: Validate backup before restore

4. **Delete Backup**
```bash
php artisan backup:delete {backup_id}
```
Removes a specific backup with options:
- `--force`: Force delete without confirmation

5. **Cleanup Backups**
```bash
php artisan backup:cleanup
```
Removes old backups with options:
- `--days`: Keep backups newer than X days
- `--dry-run`: Show what would be deleted

#### Betting Bot Commands

1. **Run Betting Bot**
```bash
php artisan betting:run
```
Main command to run the betting bot with options:
- `--dry-run`: Check status without making changes
- `--force`: Force check status
- `--bet-id`: Check specific bet
- `--date`: Filter by date
- `--status`: Filter by status
- `--export`: Export statistics (json/csv/xlsx/pdf)
- `--format`: Output format (table/json/csv)
- `--sort`: Sort statistics (date/amount/profit/streak)

2. **Test Betting Integration**
```bash
php artisan betting:test-integration
```
Tests betting platform integration with options:
- `--platform`: Specific platform to test
- `--verbose`: Show detailed output

#### Scraping Commands

1. **Scrape Adibet**
```bash
php artisan scrape:adibet
```
Scrapes predictions from Adibet with options:
- `--date`: Specific date to scrape
- `--league`: Specific league to scrape
- `--force`: Force re-scrape

2. **Test Scraper**
```bash
php artisan scrape:test
```
Tests scraper functionality with options:
- `--url`: Test specific URL
- `--verbose`: Show detailed output

#### Maintenance Commands

1. **Cleanup Logs**
```bash
php artisan cleanup:logs
```
Cleans up old log files with options:
- `--days`: Keep logs newer than X days
- `--dry-run`: Show what would be deleted

2. **Cleanup Predictions**
```bash
php artisan cleanup:predictions
```
Removes old predictions with options:
- `--days`: Keep predictions newer than X days
- `--dry-run`: Show what would be deleted

#### Import/Export Commands

1. **Import Predictions**
```bash
php artisan predictions:import {file}
```
Imports predictions from file with options:
- `--format`: File format (json/csv)
- `--validate`: Validate before import
- `--force`: Force import without confirmation

2. **Export Predictions**
```bash
php artisan predictions:export {file}
```
Exports predictions to file with options:
- `--format`: File format (json/csv/xlsx)
- `--date`: Filter by date
- `--league`: Filter by league

3. **Import Historical Predictions**
```bash
php artisan predictions:import-historical {file}
```
Imports historical prediction data with options:
- `--start-date`: Start date
- `--end-date`: End date
- `--validate`: Validate before import

#### Testing Commands

1. **Test Place Bet**
```bash
php artisan test:place-bet
```
Tests bet placement functionality with options:
- `--amount`: Test bet amount
- `--type`: Bet type to test

2. **Test Odds Integration**
```bash
php artisan test:odds-integration
```
Tests odds integration with options:
- `--platform`: Specific platform to test
- `--verbose`: Show detailed output

3. **Test Betpawa Login**
```bash
php artisan test:betpawa-login
```
Tests Betpawa login functionality with options:
- `--verbose`: Show detailed output

## 🔧 Configuration

### League Settings
You can modify league scores in `app/Services/AdibetScraper.php`:
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
        // ...
    ]
];
```

### Important Matches
Add new rivalries in `isImportantMatch()` method:
```php
protected $importantMatches = [
    'Manchester' => ['United', 'City'],
    'Barcelona' => ['Real Madrid'],
    // Add more rivalries here
];
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## ⚠️ Disclaimer

This bot is for educational purposes only. Always bet responsibly and within your means. The developers are not responsible for any financial losses incurred through the use of this bot.

## 📞 Support

If you need help or have questions:
1. Open an issue on GitHub
2. Check the documentation
3. Contact the maintainers

## 🔄 Updates

The bot is regularly updated with:
- New league data
- Improved match selection
- Better scoring system
- Bug fixes

Stay tuned for more features!
