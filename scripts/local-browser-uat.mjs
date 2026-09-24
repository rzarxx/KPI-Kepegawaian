import { mkdir, writeFile } from 'node:fs/promises';

const baseUrl = process.env.UAT_BASE_URL ?? 'http://127.0.0.1:8010';
const cdpUrl = process.env.UAT_CDP_URL ?? 'http://127.0.0.1:9223';
const password = process.env.UAT_ROLE_PASSWORD ?? 'KpiLokal2026!';
const outputDir = new URL('../artifacts/local-uat/', import.meta.url);

const allAccounts = [
    ['super-admin', 'super.admin@kpi.local.test', ['Audit Aktivitas', 'Pengguna dan Akses'], []],
    ['hr-admin', 'hr.admin@kpi.local.test', ['Pengguna dan Akses'], ['Audit Aktivitas']],
    ['hr-manager', 'hr.manager@kpi.local.test', ['Audit Aktivitas', 'Laporan'], ['Pengguna dan Akses']],
    ['branch-head', 'branch.head@kpi.local.test', ['Laporan'], ['Audit Aktivitas', 'Pengguna dan Akses']],
    ['division-head', 'division.head@kpi.local.test', ['Laporan'], ['Audit Aktivitas', 'Pengguna dan Akses']],
    ['sub-division-head', 'sub.division.head@kpi.local.test', ['Penilaian'], ['Laporan', 'Audit Aktivitas', 'Pengguna dan Akses']],
    ['auditor', 'auditor@kpi.local.test', ['Audit Aktivitas', 'Laporan'], ['Pengguna dan Akses']],
];
const selectedSlugs = (process.env.UAT_ACCOUNT_SLUGS ?? '').split(',').filter(Boolean);
const accounts = selectedSlugs.length ? allAccounts.filter(([slug]) => selectedSlugs.includes(slug)) : allAccounts;

class Cdp {
    constructor(url) {
        this.nextId = 1;
        this.pending = new Map();
        this.events = [];
        this.socket = new WebSocket(url);
    }

    async open(timeout = 10000) {
        await new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => reject(new Error(`Timeout menghubungkan CDP setelah ${timeout} ms.`)), timeout);
            const finish = (callback) => (event) => {
                clearTimeout(timeoutId);
                callback(event);
            };

            this.socket.addEventListener('open', finish(resolve), { once: true });
            this.socket.addEventListener('error', finish(reject), { once: true });
        });
        this.socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);
            if (message.id) {
                const handler = this.pending.get(message.id);
                if (!handler) return;
                this.pending.delete(message.id);
                message.error ? handler.reject(new Error(message.error.message)) : handler.resolve(message.result);
                return;
            }
            this.events.push(message);
        });
    }

    send(method, params = {}) {
        const id = this.nextId++;
        return new Promise((resolve, reject) => {
            this.pending.set(id, { resolve, reject });
            this.socket.send(JSON.stringify({ id, method, params }));
        });
    }

    close() {
        this.socket.close();
    }
}

const delay = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

async function evaluate(cdp, expression) {
    const result = await cdp.send('Runtime.evaluate', { expression, awaitPromise: true, returnByValue: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
    return result.result.value;
}

async function waitFor(cdp, expression, timeout = 15000) {
    const startedAt = Date.now();
    while (Date.now() - startedAt < timeout) {
        if (await evaluate(cdp, expression)) return;
        await delay(150);
    }
    throw new Error(`Timeout menunggu kondisi: ${expression}`);
}

async function waitForRendered(cdp, expression, diagnosticName) {
    try {
        await waitFor(cdp, expression);
    } catch (error) {
        const state = await evaluate(cdp, `(() => ({
            url: location.href,
            readyState: document.readyState,
            body: document.body?.innerText?.slice(0, 500),
            scripts: [...document.scripts].map((script) => script.src || '[inline]'),
            styles: [...document.querySelectorAll('link[rel=stylesheet]')].map((link) => link.href)
        }))()`);
        await screenshot(cdp, diagnosticName);
        throw new Error(`${error.message}; state=${JSON.stringify(state)}; browserErrors=${JSON.stringify(browserErrors(cdp))}`);
    }
}

async function navigate(cdp, url) {
    await cdp.send('Page.navigate', { url });
    await waitFor(cdp, 'document.readyState === "complete"');
    await delay(500);
}

async function screenshot(cdp, filename) {
    const result = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
    await writeFile(new URL(filename, outputDir), Buffer.from(result.data, 'base64'));
}

function browserErrors(cdp) {
    return cdp.events.filter((event) => event.method === 'Runtime.exceptionThrown'
        || (event.method === 'Log.entryAdded' && event.params.entry.level === 'error')
        || (event.method === 'Runtime.consoleAPICalled' && event.params.type === 'error'));
}

await mkdir(outputDir, { recursive: true });
const targetResponse = await fetch(`${cdpUrl}/json/new?${encodeURIComponent(`${baseUrl}/forgot-password`)}`, { method: 'PUT' });
if (!targetResponse.ok) throw new Error(`Tidak dapat membuat target CDP: HTTP ${targetResponse.status}`);
const target = await targetResponse.json();
if (!target.webSocketDebuggerUrl) throw new Error('Target CDP tidak menyediakan WebSocket debugger URL.');
const cdp = new Cdp(target.webSocketDebuggerUrl);
await cdp.open();
await Promise.all([cdp.send('Page.enable'), cdp.send('Runtime.enable'), cdp.send('Network.enable'), cdp.send('Log.enable')]);
await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
await cdp.send('Network.clearBrowserCookies');

const results = [];
await navigate(cdp, `${baseUrl}/forgot-password`);
await waitForRendered(cdp, 'document.querySelector("h2") !== null && document.querySelector("button[type=submit]") !== null', 'forgot-password-render-failure.png');
const forgot = await evaluate(cdp, '({ heading: document.querySelector("h2")?.textContent, button: document.querySelector("button[type=submit]")?.textContent })');
if (forgot.heading !== 'Lupa kata sandi?' || !forgot.button?.includes('Kirim Tautan')) throw new Error(`Halaman lupa kata sandi tidak lengkap: ${JSON.stringify(forgot)}`);
await screenshot(cdp, 'forgot-password-rbac.png');
results.push({ page: 'forgot-password', ...forgot });

if (process.env.RESET_UAT_URL) {
    await navigate(cdp, process.env.RESET_UAT_URL);
    await waitFor(cdp, 'document.querySelector("h2") !== null');
    const reset = await evaluate(cdp, '({ heading: document.querySelector("h2")?.textContent, eyeButtons: [...document.querySelectorAll("button[aria-label]")].filter((button) => button.getAttribute("aria-label").includes("kata sandi")).length })');
    if (reset.heading !== 'Atur ulang kata sandi' || reset.eyeButtons !== 2) throw new Error('Halaman atur ulang kata sandi tidak lengkap.');
    await screenshot(cdp, 'reset-password-rbac.png');
    results.push({ page: 'reset-password', ...reset });
}

if (process.env.UAT_TEST_PASSWORD_REVEAL === '1') {
    await navigate(cdp, `${baseUrl}/login`);
    await waitFor(cdp, 'document.querySelector("input[name=password]") !== null');
    await evaluate(cdp, 'document.querySelector("input[name=password]").focus()');
    for (const character of ['a', 'b', 'c']) {
        await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: character, code: `Key${character.toUpperCase()}`, text: character });
        await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: character, code: `Key${character.toUpperCase()}` });
    }
    await delay(60);
    const atEnd = await evaluate(cdp, `(() => {
        const reveal = document.querySelector('[data-password-reveal]');
        const character = document.querySelector('[data-password-reveal-character]');
        const eye = document.querySelector('button[aria-label*=kata]');
        return { mask: reveal?.textContent, index: reveal?.dataset.revealIndex, character: character?.textContent, characterRight: character?.getBoundingClientRect().right, eyeLeft: eye?.getBoundingClientRect().left };
    })()`);
    if (atEnd.mask !== '••c' || atEnd.index !== '2' || atEnd.character !== 'c' || atEnd.characterRight >= atEnd.eyeLeft) throw new Error(`Reveal pada akhir tidak tepat: ${JSON.stringify(atEnd)}`);

    await evaluate(cdp, 'document.querySelector("input[name=password]").setSelectionRange(1, 1)');
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'x', code: 'KeyX', text: 'x' });
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'x', code: 'KeyX' });
    await delay(60);
    const inMiddle = await evaluate(cdp, `(() => {
        const reveal = document.querySelector('[data-password-reveal]');
        const character = document.querySelector('[data-password-reveal-character]');
        return { mask: reveal?.textContent, index: reveal?.dataset.revealIndex, character: character?.textContent };
    })()`);
    if (inMiddle.mask !== '•x••' || inMiddle.index !== '1' || inMiddle.character !== 'x') throw new Error(`Reveal di tengah tidak tepat: ${JSON.stringify(inMiddle)}`);
    await screenshot(cdp, 'password-temporary-reveal-position.png');
    await delay(550);
    if (await evaluate(cdp, 'document.querySelector("[data-password-reveal]") !== null')) throw new Error('Karakter tidak dimasking kembali setelah 500 ms.');
    results.push({ page: 'password-temporary-reveal', atEnd, inMiddle, remasked: true });
}

for (const [accountIndex, [slug, email, requiredMenus, forbiddenMenus]] of accounts.entries()) {
    if (accountIndex > 0 && accountIndex % 5 === 0) {
        await delay(61000);
    }

    await cdp.send('Network.clearBrowserCookies');
    await navigate(cdp, `${baseUrl}/login`);
    await waitFor(cdp, 'document.querySelector("input[name=email]") !== null && document.querySelector("input[name=password]") !== null');
    await evaluate(cdp, `(() => {
        const setValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
        const email = document.querySelector('input[name=email]');
        const password = document.querySelector('input[name=password]');
        setValue.call(email, ${JSON.stringify(email)});
        email.dispatchEvent(new Event('input', { bubbles: true }));
        setValue.call(password, ${JSON.stringify(password)});
        password.dispatchEvent(new Event('input', { bubbles: true }));
        document.querySelector('form').requestSubmit();
        return true;
    })()`);
    await waitFor(cdp, 'location.pathname === "/dashboard"', 20000);
    await waitFor(cdp, 'document.querySelector("aside nav") !== null');
    const page = await evaluate(cdp, `(() => ({
        role: [...document.querySelectorAll('header span')].map((node) => node.textContent.trim()).find((text) => ${JSON.stringify(['Super Admin', 'HR Admin', 'HR Manager', 'Branch Head', 'Division Head', 'Sub Division Head', 'Auditor'])}.includes(text)),
        menus: [...document.querySelectorAll('aside nav a')].map((node) => node.textContent.trim()),
        text: document.body.innerText
    }))()`);
    for (const menu of requiredMenus) if (!page.menus.includes(menu)) throw new Error(`${slug}: menu ${menu} tidak ditemukan.`);
    for (const menu of forbiddenMenus) if (page.menus.includes(menu)) throw new Error(`${slug}: menu ${menu} seharusnya tidak tampil.`);
    await screenshot(cdp, `dashboard-${slug}.png`);
    results.push({ page: slug, role: page.role, menus: page.menus });
}

const errors = browserErrors(cdp);
await cdp.send('Network.clearBrowserCookies');
await cdp.send('Network.clearBrowserCache');
cdp.close();
if (errors.length) throw new Error(`Browser menemukan ${errors.length} console/page error: ${JSON.stringify(errors)}`);
const summary = { passed: true, results };
await writeFile(new URL('browser-role-results.json', outputDir), JSON.stringify(summary, null, 2));
console.log(JSON.stringify(summary, null, 2));
