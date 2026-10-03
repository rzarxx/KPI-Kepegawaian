import { chromium } from '@playwright/test';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

async function capture() {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1000, height: 1400 } });
    await page.goto(pathToFileURL(resolve('MANUAL_BOOK_KPI_KEPEGAWAIAN.html')).href);
    await page.evaluate(() => document.fonts.ready);
    
    const pages = await page.$$('.page');
    console.log('Total pages found:', pages.length);
    
    if (pages[7]) {
        await pages[7].screenshot({ path: 'artifacts/manual-preview-goals.png' });
        console.log('Captured page 8 (Goals)');
    }
    if (pages[8]) {
        await pages[8].screenshot({ path: 'artifacts/manual-preview-self.png' });
        console.log('Captured page 9 (Self-Assessment)');
    }
    if (pages[10]) {
        await pages[10].screenshot({ path: 'artifacts/manual-preview-calibration.png' });
        console.log('Captured page 11 (Calibration)');
    }
    
    await browser.close();
}

capture().catch(console.error);
