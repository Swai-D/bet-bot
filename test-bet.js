const AdibetScraper = require('./adibet-scraper');
const { placeBet } = require('./betpawa-bot');

async function runTest() {
    try {
        console.log('Starting test run...');
        
        // Initialize scraper
        const scraper = new AdibetScraper();
        
        // Get predictions
        console.log('Fetching predictions from Adibet...');
        const result = await scraper.getPredictions();
        
        if (!result.success) {
            throw new Error(`Failed to get predictions: ${result.error}`);
        }
        
        console.log(`Found ${result.totalMatches} predictions`);
        
        // Filter for today's matches with high confidence
        const today = new Date().toLocaleDateString('en-US', { 
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }).replace(/\//g, ' - ');
        
        const todaysMatches = result.data.filter(match => 
            match.date.includes(today) && 
            match.confidence === 'High'
        );
        
        console.log(`Found ${todaysMatches.length} high-confidence matches for today`);
        
        if (todaysMatches.length === 0) {
            console.log('No suitable matches found for betting');
            return;
        }
        
        // Format matches for betting
        const tips = todaysMatches.map(match => ({
            match: `${match.homeTeam} - ${match.awayTeam}`,
            tip: match.prediction,
            odds: match.odds
        }));
        
        // Place test bet with small stake
        const stake = 100; // TZS 100 for testing
        console.log(`Placing test bet with stake: ${stake} TZS`);
        
        await placeBet(tips, stake);
        
        console.log('Test completed successfully');
        
    } catch (error) {
        console.error('Test failed:', error.message);
    }
}

// Run the test
runTest().catch(console.error);
 