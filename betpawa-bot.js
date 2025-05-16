import { chromium } from 'playwright';
import fs from 'fs';
import * as dotenv from 'dotenv';

// Load environment variables
const env = dotenv.config().parsed;

// Configure logging
const logFile = fs.createWriteStream('betpawa-bot.log', { flags: 'a' });
const log = (message) => {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] ${message}\n`;
    logFile.write(logMessage);
    console.log(message);
};

// Map betting options to their selectors
const BETTING_OPTIONS = {
    '1': '1',
    'X': 'X',
    '2': '2',
    '-2.5': '-2.5',
    'GG': 'GG',
    '+2.5': '+2.5'
};

export async function placeBet(tips, stake) {
    log('Starting bet placement process...');
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 100 // Add slight delay between actions
    });

    try {
        const context = await browser.newContext();
        const page = await context.newPage();

        // Navigate to Betpawa
        log('Navigating to Betpawa...');
        await page.goto('https://www.betpawa.co.tz');
        await page.waitForLoadState('networkidle');

        // Click login button
        log('Clicking login button...');
        await page.click('a[href="/login"]');
        await page.waitForLoadState('networkidle');

        // Wait for login form and fill credentials
        log('Filling login form...');
        await page.waitForSelector('#login-form-phoneNumber');
        await page.fill('#login-form-phoneNumber', env.BETPAWA_PHONE);
        await page.fill('#login-form-password-input', env.BETPAWA_PASSWORD);

        // Click login submit button
        log('Submitting login form...');
        await page.click('input[data-test-id="logInButton"]');

        // Wait for navigation after login
        log('Waiting for login to complete...');
        await page.waitForLoadState('networkidle');

        // Verify login success by checking balance
        log('Verifying login success...');
        await page.waitForSelector('.button.balance');
        const balance = await page.textContent('.button.balance');
        log(`Login successful! Current balance: ${balance}`);

        // Process each tip
        for (const tip of tips) {
            log(`Processing tip: ${tip.match} - ${tip.tip} (${tip.odds})`);
            
            // Click search icon and search for the match
            await page.click('use[xlink:href="#icon-search"]');
            await page.waitForSelector('#search-form-search-mobile');
            
            // Split match name into teams
            const [team1, team2] = tip.match.split(' - ').map(t => t.trim());
            log(`Searching for match between ${team1} and ${team2}`);
            
            // Try searching with team names
            await page.fill('#search-form-search-mobile', team1);
            await page.waitForTimeout(1000); // Wait for search results
            
            // Look for the match in search results
            const matchFound = await page.evaluate((team1, team2) => {
                const results = Array.from(document.querySelectorAll('.search-result-item'));
                return results.some(result => {
                    const text = result.textContent.toLowerCase();
                    return text.includes(team1.toLowerCase()) && text.includes(team2.toLowerCase());
                });
            }, team1, team2);
            
            if (!matchFound) {
                log(`Warning: Could not find match ${tip.match}`);
                continue;
            }
            
            // Click the match
            await page.click(`.search-result-item:has-text("${team1}")`);
            await page.waitForLoadState('networkidle');

            // Verify the betting option exists
            const optionText = BETTING_OPTIONS[tip.tip];
            if (!optionText) {
                throw new Error(`Invalid betting option: ${tip.tip}`);
            }

            // Select the tip
            const tipSelector = `span[data-test-id="selection"]:has-text("${optionText}")`;
            try {
                await page.waitForSelector(tipSelector, { timeout: 5000 });
                await page.click(tipSelector);
                log(`Selected tip: ${tip.tip}`);
            } catch (error) {
                log(`Warning: Could not find betting option ${tip.tip} for match ${tip.match}`);
                continue;
            }

            // Wait for betslip to update
            await page.waitForTimeout(500);
        }

        // Enter stake amount
        log(`Entering stake amount: ${stake}`);
        await page.waitForSelector('#betslip-form-stake-input');
        await page.fill('#betslip-form-stake-input', stake.toString());

        // Verify total odds
        const totalOdds = await page.textContent('[data-test-id="totalOdds"]');
        log(`Total odds: ${totalOdds}`);

        // Place bet
        log('Placing bet...');
        await page.click('input[value="PLACE BET"]');
        await page.waitForLoadState('networkidle');

        // Take screenshot of confirmation
        await page.screenshot({ path: 'bet-confirmation.png' });
        log('Screenshot saved as bet-confirmation.png');

        // Get updated balance
        const newBalance = await page.textContent('.button.balance');
        log(`New balance after bet: ${newBalance}`);

        return {
            balance: newBalance,
            totalOdds: totalOdds
        };

    } catch (error) {
        log(`Error: ${error.message}`);
        // Take error screenshot
        await page.screenshot({ path: 'error-screenshot.png' });
        log('Error screenshot saved as error-screenshot.png');
        throw error;
    } finally {
        await browser.close();
        log('Browser closed');
    }
}

// If you need to run this file directly
if (import.meta.url === `file://${process.argv[1]}`) {
    const betData = JSON.parse(process.argv[2]);
    placeBet(betData.tips, betData.stake)
        .then(result => {
            console.log(JSON.stringify(result, null, 2));
        })
        .catch(error => {
            console.error('Error:', error);
            process.exit(1);
        });
} 