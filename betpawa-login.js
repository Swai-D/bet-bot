import { chromium } from 'playwright';
import * as dotenv from 'dotenv';
import fs from 'fs';
import path from 'path';

// Load environment variables
const env = dotenv.config().parsed;
if (!env) {
    console.error('Error: .env file not found or empty');
    process.exit(1);
}

// Validate required environment variables
const requiredEnv = ['BETPAWA_PHONE', 'BETPAWA_PASSWORD'];
for (const key of requiredEnv) {
    if (!env[key]) {
        throw new Error(`Missing required env variable: ${key}`);
    }
}

// Configure logging with rotation
const setupLogging = () => {
    const logDir = 'logs';
    if (!fs.existsSync(logDir)) {
        fs.mkdirSync(logDir);
    }

    const today = new Date().toISOString().split('T')[0];
    const logFile = fs.createWriteStream(path.join(logDir, `betpawa-login-${today}.log`), { flags: 'a' });

    // Cleanup old logs (older than 7 days)
    const cleanupOldLogs = () => {
        const files = fs.readdirSync(logDir);
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);

        files.forEach(file => {
            const filePath = path.join(logDir, file);
            const stats = fs.statSync(filePath);
            if (stats.mtime < sevenDaysAgo) {
                fs.unlinkSync(filePath);
            }
        });
    };

    cleanupOldLogs();

    return {
        log: (message) => {
            const timestamp = new Date().toISOString();
            const logMessage = `[${timestamp}] ${message}\n`;
            logFile.write(logMessage);
            console.log(message);
        }
    };
};

const { log } = setupLogging();

// Retry mechanism
const retry = async (fn, retries = 3, delay = 1000) => {
    for (let i = 0; i < retries; i++) {
        try {
            return await fn();
        } catch (e) {
            if (i === retries - 1) throw e;
            log(`Attempt ${i + 1} failed, retrying in ${delay}ms...`);
            await new Promise(res => setTimeout(res, delay));
        }
    }
};

// Screenshot helper
const takeScreenshot = async (page, name) => {
    const screenshotDir = path.join('public', 'screenshots');
    if (!fs.existsSync(screenshotDir)) {
        fs.mkdirSync(screenshotDir, { recursive: true });
    }
    
    const screenshotPath = path.join(screenshotDir, `${name}-${Date.now()}.png`);
    await page.screenshot({ path: screenshotPath });
    log(`Screenshot saved: ${screenshotPath}`);
    return screenshotPath;
};

export async function loginToBetpawa() {
    log('Starting Betpawa login process...');
    let browser;
    try {
        const isDev = process.env.NODE_ENV !== 'production';
        log('Launching browser...');
        browser = await chromium.launch({ 
            headless: !isDev,
            slowMo: isDev ? 100 : 0
        });
        log('Browser launched successfully');

        const context = await browser.newContext();
        const page = await context.newPage();

        // Navigate to Betpawa with retry
        await retry(async () => {
            log('Navigating to Betpawa...');
            await page.goto('https://www.betpawa.co.tz', { timeout: 30000 });
            await page.waitForLoadState('networkidle');
            log('Navigation completed');
        });

        // Login process with retry
        await retry(async () => {
            log('Looking for login button...');
            await page.waitForSelector('[data-test-id="loginButton"]', { timeout: 5000 });
            await page.click('[data-test-id="loginButton"]');
            log('Login button clicked');

            await page.waitForSelector('[data-test-id="phoneNumberInput"]', { timeout: 5000 });
            await page.fill('[data-test-id="phoneNumberInput"]', env.BETPAWA_PHONE);
            log('Phone number filled');

            await page.fill('[data-test-id="passwordInput"]', env.BETPAWA_PASSWORD);
            log('Password filled');

            await page.click('[data-test-id="logInButton"]');
            log('Login submit button clicked');

            await page.waitForLoadState('networkidle');
            log('Login completed');
        });

        // Verify login success
        await retry(async () => {
            log('Looking for balance element...');
            await page.waitForSelector('[data-test-id="balanceButton"]', { timeout: 5000 });
            const balance = await page.textContent('[data-test-id="balanceButton"]');
            log(`Login successful! Current balance: ${balance}`);

            await takeScreenshot(page, 'dashboard');
            return balance;
        });

        return {
            success: true,
            balance: balance,
            timestamp: new Date().toISOString()
        };

    } catch (error) {
        log(`Error: ${error.message}`);
        log(`Error stack: ${error.stack}`);
        
        if (browser) {
            try {
                const page = await browser.newPage();
                await takeScreenshot(page, 'error');
            } catch (screenshotError) {
                log(`Failed to take error screenshot: ${screenshotError.message}`);
            }
        }
        
        return {
            success: false,
            error: error.message,
            timestamp: new Date().toISOString()
        };
    } finally {
        if (browser) {
            try {
                await browser.close();
                log('Browser closed');
            } catch (closeError) {
                log(`Failed to close browser: ${closeError.message}`);
            }
        }
    }
}

// CLI usage
if (import.meta.url === `file://${process.argv[1]}`) {
    if (process.argv.length < 3) {
        console.log(`
Usage: node betpawa-login.js [command]

Commands:
  login      Login to Betpawa and check balance
  help       Show this help message
        `);
        process.exit(0);
    }

    const command = process.argv[2];
    switch (command) {
        case 'login':
            loginToBetpawa()
                .then(result => console.log(JSON.stringify(result, null, 2)))
                .catch(error => {
                    console.error('Error:', error);
                    process.exit(1);
                });
            break;
        case 'help':
            console.log('Help message here...');
            break;
        default:
            console.error(`Unknown command: ${command}`);
            process.exit(1);
    }
} 