import AdibetScraper from '../adibet-scraper.js';
import SportyTraderScraper from '../sportytrader-scraper.js';
import axios from 'axios';
import dotenv from 'dotenv';

dotenv.config();

const API_URL = process.env.API_URL || 'http://localhost:8000/api';

async function submitPrediction(prediction, source) {
    try {
        const response = await axios.post(`${API_URL}/predictions`, {
            ...prediction,
            source
        });
        console.log(`Successfully submitted ${source} prediction:`, prediction.match);
        return response.data;
    } catch (error) {
        console.error(`Error submitting ${source} prediction:`, error.message);
        return null;
    }
}

async function runScrapers() {
    try {
        // First try Adibet
        console.log('Running Adibet scraper...');
        const adibetScraper = new AdibetScraper();
        const adibetResult = await adibetScraper.getPredictions();

        if (adibetResult.success && adibetResult.data) {
            console.log('Adibet predictions found:', Object.keys(adibetResult.data).length);
            
            // Submit Adibet predictions
            for (const [date, predictions] of Object.entries(adibetResult.data)) {
                for (const prediction of predictions) {
                    await submitPrediction(prediction, 'adibet');
                }
            }
        } else {
            console.log('No Adibet predictions found, falling back to SportyTrader...');
            
            // Fallback to SportyTrader
            const sportyTraderScraper = new SportyTraderScraper();
            const sportyTraderResult = await sportyTraderScraper.getPredictionsWithOdds();

            if (sportyTraderResult.success && sportyTraderResult.data) {
                console.log('SportyTrader predictions found:', sportyTraderResult.data.length);
                
                // Submit SportyTrader predictions
                for (const prediction of sportyTraderResult.data) {
                    await submitPrediction(prediction, 'sportytrader');
                }
            } else {
                console.error('No predictions found from either source');
            }
        }
    } catch (error) {
        console.error('Error running scrapers:', error);
    }
}

// Run the scrapers
runScrapers().catch(console.error); 