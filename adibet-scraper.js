import { chromium } from 'playwright';
import axios from 'axios';
import * as cheerio from 'cheerio';
import fs from 'fs';
import path from 'path';

process.on('uncaughtException', err => {
  console.error('Uncaught Exception:', err);
  process.exit(1);
});
process.on('unhandledRejection', err => {
  console.error('Unhandled Rejection:', err);
  process.exit(1);
});
console.log('Script started');

class AdibetScraper {
    constructor() {
        this.baseUrl = 'https://adibet.com/';
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
            console.log('Browser launched successfully');
            
            const page = await browser.newPage();
            console.log('New page created');
            
            // Set viewport size
            await page.setViewportSize({ width: 1280, height: 800 });
            console.log('Viewport size set');
            
            console.log('Navigating to Adibet.com...');
            const response = await page.goto(this.baseUrl, { 
                waitUntil: 'networkidle',
                timeout: 30000 
            });
            console.log('Navigation response status:', response.status());
            
            // Wait for the content to load
            console.log('Waiting for content to load...');
            await page.waitForSelector('table', { timeout: 10000 });
            console.log('Table element found');
            
            // Get the page content
            const html = await page.content();
            console.log('Page content length:', html.length);
            
            if (!html || html.length < 100) {
                throw new Error('Received empty or invalid HTML content');
            }
            
            // Take a screenshot for debugging
            await page.screenshot({ path: 'debug-screenshot.png' });
            console.log('Screenshot saved as debug-screenshot.png');
            
            return this.parsePredictions(html);
        } catch (error) {
            console.error('Error fetching HTML from Adibet.com with Playwright:', error);
            throw new Error(`Failed to fetch predictions: ${error.message}`);
        } finally {
            if (browser) {
                await browser.close();
                console.log('Browser closed');
            }
        }
    }

    parsePredictions(html) {
        console.log('Starting to parse predictions...');
        const $ = cheerio.load(html);
        const predictions = {};
        let currentDate = null;

        // Find all tables in the content area
        const tables = $('table');
        console.log('Found', tables.length, 'tables');

        tables.each((_, table) => {
            const $table = $(table);
            
            // Check if this is a date header table (has font with color="#C0C0C0")
            const dateHeader = $table.find('font[color="#C0C0C0"]');
            if (dateHeader.length) {
                // Clean and format the date
                const rawDate = dateHeader.text().trim();
                console.log('Raw date:', rawDate);
                
                // Extract date parts using regex
                const dateMatch = rawDate.match(/(\d+)\s*-\s*(\d+)\s*-\s*(\d+)/);
                if (dateMatch) {
                    const [_, day, month, year] = dateMatch;
                    currentDate = `${day.padStart(2, '0')}-${month.padStart(2, '0')}-${year}`;
                console.log(`Found date header: ${currentDate}`);
                } else {
                    console.log('Could not parse date:', rawDate);
                }
                return; // Skip to next table
            }

            // Check if this is a matches table (has width="620" and bgcolor="#666666")
            if ($table.attr('width') === '620' && $table.attr('bgcolor') === '#666666' && currentDate) {
                console.log(`Processing matches table for date: ${currentDate}`);
                
                // Initialize array for this date if not exists
                if (!predictions[currentDate]) {
                    predictions[currentDate] = [];
                }
                
                // Process each match row
                $table.find('tr').each((_, row) => {
                    const $row = $(row);
                    
                    // Skip header rows
                    if ($row.find('th').length) return;

                    // Get country from image alt
                    const country = $row.find('img').attr('alt') || 'Unknown';

                    // Get teams from yellow text
                    const teamsText = $row.find('font[color="#D5B438"]').text().trim();
                    const [homeTeam, awayTeam] = teamsText.split(' - ').map(t => t.trim());

                    // Find all prediction cells (cells with bgcolor="#272727")
                    const predictionCells = $row.find('td[bgcolor="#272727"]');
                    
                    // Process each prediction cell
                    predictionCells.each((_, cell) => {
                        const $cell = $(cell);
                        const prediction = $cell.find('font[color="#D5B438"]').text().trim();
                        const odds = $cell.next('td').text().trim();

                        // Only process if this is a highlighted prediction
                        if (prediction && odds && $cell.find('font[color="#D5B438"]').length > 0) {
                            const matchData = {
                                match: `${homeTeam} vs ${awayTeam}`,
                                prediction: prediction,
                                odd: parseFloat(odds) || 0,
                                confidence: this.calculateConfidence($cell)
                            };

                            predictions[currentDate].push(matchData);
                            console.log(`Added match: ${matchData.match} (${matchData.prediction})`);
                        }
                    });
                });
            }
        });

        return predictions;
    }

    calculateConfidence(predictionCell) {
        // Calculate confidence based on the prediction cell's content
        // This is a placeholder - implement your confidence calculation logic
        return 'Medium';
    }

    async getPredictions() {
        try {
            const predictions = await this.fetchPredictions();
            return {
                success: true,
                data: predictions,
                timestamp: new Date().toISOString(),
                totalMatches: Object.values(predictions).reduce((sum, matches) => sum + matches.length, 0)
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
    const scraper = new AdibetScraper();
    const result = await scraper.getPredictions();
    
    if (result.success) {
            // Format the data as expected by the PHP code
            const formattedData = {};
            Object.entries(result.data).forEach(([date, matches]) => {
                formattedData[date] = matches.map(match => ({
                    match: `${match.homeTeam} vs ${match.awayTeam}`,
                    prediction: match.prediction,
                    odd: match.odds,
                    confidence: match.confidence
                }));
            });
            
            // Ensure we're outputting valid JSON
            const jsonOutput = JSON.stringify(formattedData, null, 2);
            console.log(jsonOutput);
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

// Always run main when this script is executed
main().catch(error => {
    console.error('Unhandled error:', error);
    process.exit(1);
});

export default AdibetScraper; 