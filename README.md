# Betting Bot

A Laravel-based betting bot that scrapes predictions from Adibet and provides smart match recommendations. This bot helps you make informed betting decisions by analyzing match data, league quality, and historical performance.

## 🎯 Project Overview

This bot is designed to help bettors make smarter betting decisions by:
1. Scraping predictions from Adibet
2. Analyzing match quality and importance
3. Filtering matches by league tier
4. Preventing duplicate matches
5. Providing daily updates

## ⚡ Features

### Smart Match Selection
The bot automatically identifies the best matches to bet on based on:
- **League Quality**: Prioritizes top leagues (Premier League, La Liga, etc.)
- **Match Importance**: Identifies derbies and rivalries
- **Number of Tips**: More tips = higher confidence
- **Historical Performance**: Considers team form and history

### League Tiers
Matches are categorized into two tiers:

#### Top Tier (5-3 points)
- **England** (Premier League) - 5 points
  - Highest quality league
  - Most competitive matches
  - Best for betting
- **Germany** (Bundesliga) - 4 points
  - High-scoring matches
  - Strong home advantage
- **Spain** (La Liga) - 4 points
  - Technical football
  - Strong top teams
- **Italy** (Serie A) - 3 points
  - Tactical matches
  - Good for under/over
- **France** (Ligue 1) - 3 points
  - Emerging talent
  - Competitive matches

#### Moderate Tier (2 points)
- **Netherlands** (Eredivisie)
- **Portugal** (Primeira Liga)
- **Turkey** (Super Lig)
- **Belgium** (Pro League)
- **Scotland** (Premiership)

### Additional Features
- **Duplicate Prevention**: Ensures no match appears twice
- **Daily Updates**: Automatically fetches new predictions
- **Smart Filtering**: Filter by league tier or date
- **Match Scoring**: Each match gets a score based on:
  - League quality (3-5 points)
  - Match importance (2 points for derbies)
  - Number of tips (0.5 points per tip)

## 🚀 Installation

1. **Clone the repository**:
```bash
git clone https://github.com/yourusername/betting-bot.git
cd betting-bot
```

2. **Install dependencies**:
```bash
composer install
```

3. **Set up environment**:
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database**:
```bash
php artisan migrate
```

## 📖 Usage Guide

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

### Understanding Match Scores

Each match gets a score based on:

1. **League Quality**:
   - Premier League: 5 points
   - Bundesliga/La Liga: 4 points
   - Serie A/Ligue 1: 3 points
   - Other leagues: 2 points

2. **Match Importance**:
   - Derbies: +2 points
   - Rivalries: +1 point
   - Big matches: +1 point

3. **Tips Confidence**:
   - Each tip: +0.5 points
   - More tips = higher confidence

### Example Output
```
Match: Chelsea vs Manchester Utd
Country: England
Date: 2025-05-16
Tips:
  - 1
  - GG
  - +2.5
--------------------------------------------------
```

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
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

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
