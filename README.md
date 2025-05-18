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

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

MIT License - See LICENSE file for details
