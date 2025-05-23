const puppeteer = require('puppeteer');

const tipTypeMap = {
    "1": "1X2",
    "X": "1X2",
    "2": "1X2",
    "+2.5": "Over/Under",
    "-2.5": "Over/Under",
    "GG": "Both Teams to Score",
    "NG": "Both Teams to Score",
    "1X": "Double Chance",
    "12": "Double Chance",
    "X2": "Double Chance",
    "AH": "Asian Handicap"
};

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function scrapeOdds({ homeTeam, awayTeam, tipType }, retryCount = 0) {
    const maxRetries = 3;
    console.log('🚀 Starting scraper...');
    console.log(`📊 Looking for match: ${homeTeam} vs ${awayTeam}`);
    console.log(`🎯 Tip type: ${tipType}`);

    const browser = await puppeteer.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    console.log('🌐 Browser launched');

    const page = await browser.newPage();
    console.log('📄 New page created');

    try {
        console.log('🔍 Navigating to OddsPortal...');
        await page.goto('https://www.oddsportal.com/football/today/', { 
            waitUntil: 'networkidle2',
            timeout: 30000 
        });
        console.log('✅ Successfully loaded OddsPortal');

        // Handle cookie consent popup
        try {
            await page.waitForSelector('button#onetrust-accept-btn-handler, button[title="I agree"], button[mode="primary"]', { timeout: 5000 });
            await page.click('button#onetrust-accept-btn-handler, button[title="I agree"], button[mode="primary"]');
            console.log('✅ Cookie consent accepted');
        } catch (e) {
            console.log('ℹ️ No cookie consent popup found');
        }

        // Take screenshot for debugging
        await page.screenshot({ path: 'debug-oddsportal.png' });
        console.log('📸 Screenshot saved as debug-oddsportal.png');

        // Wait for the matches to load
        console.log('⏳ Waiting for matches to load...');
        await page.waitForSelector('a[href*="/match/"]', { timeout: 10000 });
        console.log('✅ Matches loaded');

        // Get all match links for debugging
        const allMatches = await page.evaluate(() => {
            const anchors = [...document.querySelectorAll('a[href*="/match/"]')];
            return anchors.map(a => ({
                text: a.textContent.trim(),
                url: a.href
            }));
        });

        console.log(`📋 Found ${allMatches.length} total matches on the page`);
        console.log('First 5 matches:');
        allMatches.slice(0, 5).forEach((match, index) => {
            console.log(`${index + 1}. ${match.text}`);
        });

        // Search for our specific match
        console.log(`🔍 Searching for match: ${homeTeam} vs ${awayTeam}`);
        const matchLinks = await page.evaluate(({ home, away }) => {
            const anchors = [...document.querySelectorAll('a[href*="/match/"]')];
            console.log(`Found ${anchors.length} total matches`);
            
            return anchors
                .filter(a => {
                    const text = a.textContent.toLowerCase();
                    const homeLower = home.toLowerCase();
                    const awayLower = away.toLowerCase();
                    
                    console.log(`Checking match: ${text}`);
                    console.log(`Against: ${homeLower} vs ${awayLower}`);
                    
                    // Check for exact team names first
                    if (text.includes(homeLower) && text.includes(awayLower)) {
                        console.log('Found exact match!');
                        return true;
                    }
                    
                    // Check for partial matches
                    const homeWords = homeLower.split(' ');
                    const awayWords = awayLower.split(' ');
                    
                    const hasHomeMatch = homeWords.some(word => text.includes(word));
                    const hasAwayMatch = awayWords.some(word => text.includes(word));
                    
                    if (hasHomeMatch && hasAwayMatch) {
                        console.log('Found partial match!');
                        return true;
                    }
                    
                    return false;
                })
                .map(a => ({
                    url: a.href,
                    text: a.textContent.trim(),
                    date: a.closest('tr')?.querySelector('.date')?.textContent.trim() || ''
                }));
        }, { home: homeTeam, away: awayTeam });

        if (!matchLinks.length) {
            throw new Error(`Match not found: ${homeTeam} vs ${awayTeam}`);
        }

        console.log(`Found ${matchLinks.length} potential matches:`);
        matchLinks.forEach((match, index) => {
            console.log(`${index + 1}. ${match.text} (${match.date})`);
        });

        const matchUrl = matchLinks[0].url;
        console.log(`➡️ Opening match: ${matchUrl}`);
        await page.goto(matchUrl, { waitUntil: 'networkidle2' });
        console.log('✅ Match page loaded');

        // Take screenshot of match page
        await page.screenshot({ path: 'debug-match.png' });
        console.log('📸 Screenshot saved as debug-match.png');

        // Wait for odds to load
        console.log('⏳ Waiting for odds table to load...');
        await page.waitForSelector('table.table-main', { timeout: 10000 });
        console.log('✅ Odds table loaded');

        // Get all bookmakers for debugging
        const bookmakers = await page.evaluate(() => {
            const rows = document.querySelectorAll('table.table-main tr');
            return Array.from(rows).map(row => {
                const nameCell = row.querySelector('.name');
                return nameCell ? nameCell.textContent.trim() : null;
            }).filter(Boolean);
        });

        console.log('📚 Found bookmakers:');
        bookmakers.forEach((bookmaker, index) => {
            console.log(`${index + 1}. ${bookmaker}`);
        });

        // Look specifically for Betway
        console.log('🔍 Looking for Betway odds...');
        const oddsData = await page.evaluate(() => {
            const rows = document.querySelectorAll('table.table-main tr');
            let data = [];

            rows.forEach(row => {
                const nameCell = row.querySelector('.name');
                const oddsCells = row.querySelectorAll('.odds-nowrp');

                if (nameCell && oddsCells.length) {
                    const bookmaker = nameCell.textContent.trim();
                    
                    // Only process Betway odds
                    if (bookmaker.toLowerCase() !== 'betway') {
                        return;
                    }

                    console.log(`Found Betway row: ${bookmaker}`);
                    const odds = Array.from(oddsCells).map(cell => {
                        const odd = cell.textContent.trim();
                        console.log(`Odd value: ${odd}`);
                        return odd === '-' ? null : parseFloat(odd);
                    });

                    if (odds.some(odd => odd !== null)) {
                        data.push({ bookmaker, odds });
                    }
                }
            });

            return data;
        });

        if (!oddsData.length) {
            throw new Error(`No Betway odds found`);
        }

        console.log("✅ Done!");
        await browser.close();
        return oddsData;

    } catch (err) {
        console.error(`❌ Error (Attempt ${retryCount + 1}/${maxRetries}):`, err.message);
        await browser.close();

        if (retryCount < maxRetries - 1) {
            console.log(`🔄 Retrying in 5 seconds...`);
            await sleep(5000);
            return scrapeOdds({ homeTeam, awayTeam, tipType }, retryCount + 1);
        }

        return [];
    }
}

// Get command line arguments
const args = JSON.parse(process.argv[2]);

// Run the scraper
scrapeOdds(args)
    .then(result => {
        console.log(JSON.stringify(result));
        process.exit(0);
    })
    .catch(error => {
        console.error(error);
        process.exit(1);
    }); 