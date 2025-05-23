import { chromium } from 'playwright';
import axios from 'axios';
import * as cheerio from 'cheerio';
import fs from 'fs';
import path from 'path';

class SportyTraderScraper {
    constructor() {
        this.baseUrl = 'https://www.sportytrader.com/en/betting-tips/football/';
        this.oddsUrl = 'https://www.sportytrader.com/en/odds/football/';
        this.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1'
        };
    }

    async fetchPredictions() {
        let browser;
        try {
            console.log('Launching browser with Playwright...');
            browser = await chromium.launch({ 
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox']
            });
            
            const page = await browser.newPage();
            await page.setViewportSize({ width: 1280, height: 800 });
            
            console.log('Navigating to SportyTrader predictions...');
            await page.goto(this.baseUrl, { 
                waitUntil: 'networkidle',
                timeout: 30000 
            });
            
            // Wait for predictions to load
            await page.waitForSelector('.prediction-card', { timeout: 10000 });
            
            const predictions = await page.evaluate(() => {
                const cards = document.querySelectorAll('.prediction-card');
                return Array.from(cards).map(card => {
                    const match = card.querySelector('.match-name')?.textContent.trim();
                    const country = card.querySelector('.country')?.textContent.trim();
                    const date = card.querySelector('.date')?.textContent.trim();
                    const tips = Array.from(card.querySelectorAll('.tip')).map(tip => tip.textContent.trim());
                    const confidence = card.querySelector('.confidence')?.textContent.trim();
                    
                    return {
                        match,
                        country,
                        date,
                        tips,
                        confidence
                    };
                });
            });

            return predictions;
        } catch (error) {
            console.error('Error fetching predictions:', error);
            throw error;
        } finally {
            if (browser) await browser.close();
        }
    }

    async fetchOdds(matchName, date) {
        let browser;
        try {
            browser = await chromium.launch({ 
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox']
            });
            
            const page = await browser.newPage();
            await page.goto(this.oddsUrl, { waitUntil: 'networkidle' });
            
            // Search for the match
            await page.fill('input[type="search"]', matchName);
            await page.keyboard.press('Enter');
            
            // Wait for results
            await page.waitForSelector('.match-row', { timeout: 5000 });
            
            const odds = await page.evaluate((targetMatch) => {
                const matches = document.querySelectorAll('.match-row');
                for (const match of matches) {
                    const matchName = match.querySelector('.match-name')?.textContent.trim();
                    if (matchName === targetMatch) {
                        return {
                            '1': match.querySelector('.home-odds')?.textContent.trim(),
                            'X': match.querySelector('.draw-odds')?.textContent.trim(),
                            '2': match.querySelector('.away-odds')?.textContent.trim(),
                            'GG': match.querySelector('.btts-yes')?.textContent.trim(),
                            'NG': match.querySelector('.btts-no')?.textContent.trim(),
                            '+2.5': match.querySelector('.over-2.5')?.textContent.trim(),
                            '-2.5': match.querySelector('.under-2.5')?.textContent.trim()
                        };
                    }
                }
                return null;
            }, matchName);

            return odds;
        } catch (error) {
            console.error('Error fetching odds:', error);
            return null;
        } finally {
            if (browser) await browser.close();
        }
    }

    async getPredictionsWithOdds() {
        try {
            const predictions = await this.fetchPredictions();
            const predictionsWithOdds = [];

            for (const prediction of predictions) {
                const odds = await this.fetchOdds(prediction.match, prediction.date);
                
                predictionsWithOdds.push({
                    match: prediction.match,
                    country: prediction.country,
                    league: 'Unknown League', // SportyTrader doesn't provide league info
                    date: prediction.date,
                    tips: prediction.tips,
                    odds: odds,
                    raw_data: prediction // Store original data
                });
            }

            return {
                success: true,
                data: predictionsWithOdds,
                timestamp: new Date().toISOString()
            };
        } catch (error) {
            return {
                success: false,
                error: error.message,
                timestamp: new Date().toISOString()
            };
        }
    }
}

// Main function to run the scraper
async function main() {
    try {
        const scraper = new SportyTraderScraper();
        const result = await scraper.getPredictionsWithOdds();
        
        if (result.success) {
            console.log(JSON.stringify(result.data, null, 2));
            process.exit(0);
        } else {
            console.error('Failed to scrape predictions:', result.error);
            process.exit(1);
        }
    } catch (error) {
        console.error('Error in main:', error);
        process.exit(1);
    }
}

// Run main when script is executed
main().catch(error => {
    console.error('Unhandled error:', error);
    process.exit(1);
});

export default SportyTraderScraper; 