import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import { dirname, resolve } from 'node:path';

const cdpUrl = process.env.MANUAL_CDP_URL ?? 'http://127.0.0.1:9333';
const source = resolve(process.argv[2] ?? 'MANUAL_BOOK_KPI_KEPEGAWAIAN.html');
const output = resolve(process.argv[3] ?? 'Manual Book KPI Kepegawaian.pdf');
const preview = resolve(process.env.MANUAL_PREVIEW_PATH ?? 'artifacts/manual-book-cover-preview.png');

class Cdp {
    constructor(url) {
        this.nextId = 1;
        this.pending = new Map();
        this.socket = new WebSocket(url);
    }

    async open() {
        await new Promise((resolveConnection, reject) => {
            this.socket.addEventListener('open', resolveConnection, { once: true });
            this.socket.addEventListener('error', reject, { once: true });
        });
        this.socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);
            if (!message.id) return;
            const handler = this.pending.get(message.id);
            if (!handler) return;
            this.pending.delete(message.id);
            message.error ? handler.reject(new Error(message.error.message)) : handler.resolve(message.result);
        });
    }

    send(method, params = {}) {
        const id = this.nextId++;
        return new Promise((resolveCommand, reject) => {
            this.pending.set(id, { resolve: resolveCommand, reject });
            this.socket.send(JSON.stringify({ id, method, params }));
        });
    }

    close() {
        this.socket.close();
    }
}

const target = await fetch(`${cdpUrl}/json/new?${encodeURIComponent(pathToFileURL(source).href)}`, { method: 'PUT' }).then((response) => {
    if (!response.ok) throw new Error(`Tidak dapat membuat target browser: HTTP ${response.status}`);
    return response.json();
});
const cdp = new Cdp(target.webSocketDebuggerUrl);
await cdp.open();
await Promise.all([cdp.send('Page.enable'), cdp.send('Runtime.enable')]);
await cdp.send('Emulation.setDeviceMetricsOverride', { width: 900, height: 1273, deviceScaleFactor: 1, mobile: false });
await new Promise((resolveDelay) => setTimeout(resolveDelay, 750));
await cdp.send('Runtime.evaluate', { expression: 'document.fonts.ready', awaitPromise: true });
const screenshot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
await mkdir(dirname(preview), { recursive: true });
await writeFile(preview, Buffer.from(screenshot.data, 'base64'));
const result = await cdp.send('Page.printToPDF', {
    printBackground: true,
    preferCSSPageSize: true,
    marginTop: 0,
    marginRight: 0,
    marginBottom: 0,
    marginLeft: 0,
});
await writeFile(output, Buffer.from(result.data, 'base64'));
const pdf = await readFile(output);
const pageCount = (pdf.toString('latin1').match(/\/Type\s*\/Page\b/g) ?? []).length;
cdp.close();
await fetch(`${cdpUrl}/json/close/${target.id}`);
console.log(JSON.stringify({ source, output, preview, bytes: pdf.length, pages: pageCount }, null, 2));
