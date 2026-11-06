import { chromium } from 'playwright-core';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');
const screenshots = resolve(root, '..', 'docs', 'assets', 'screenshots');
const reports = resolve(root, '..', 'docs', 'qa');
const base = process.env.QA_BASE_URL ?? 'http://127.0.0.1:5173';
const chrome = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

const themes = ['aurora', 'neon', 'sunset', 'ocean', 'forest', 'candy', 'cyber', 'royal', 'terracotta', 'mono', 'tropical', 'galaxy'];
const modes = ['light', 'dark', 'contrast'];

const consoleErrors = [];
const pageErrors = [];
const checks = [];

function check(name, condition, detail = '') {
  checks.push({ name, ok: Boolean(condition), detail });
  if (!condition) console.error(`✗ ${name} ${detail}`);
  else console.log(`✓ ${name}`);
}

async function shot(page, name) {
  await page.screenshot({ path: resolve(screenshots, `${name}.png`), fullPage: false });
}

async function main() {
  await mkdir(screenshots, { recursive: true });
  await mkdir(reports, { recursive: true });
  const browser = await chromium.launch({ executablePath: chrome, headless: true, args: ['--disable-dev-shm-usage'] });
  try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
  const page = await context.newPage();
  page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
  page.on('pageerror', (error) => pageErrors.push(String(error)));

  await page.goto(`${base}/`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('h1');
  check('Landing pública carga', await page.locator('h1').count() > 0, page.url());
  await shot(page, '01-public-landing');

  await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('form');
  check('Login carga', await page.locator('input[type="email"]').count() === 1);
  await shot(page, '02-login');

  await page.fill('input[type="email"]', 'admin@skatenea.local');
  await page.fill('input[type="password"]', 'Admin#2026!');
  await page.getByRole('button', { name: 'Entrar a SKATENEA' }).click();
  await page.waitForURL('**/app', { timeout: 15000 });
  await page.waitForSelector('h1', { timeout: 15000 });
  check('Login y dashboard', page.url().endsWith('/app'), page.url());
  await shot(page, '03-dashboard-admin');

  const menuTrigger = page.locator('.user-menu__trigger');
  check('Menú de usuario visible', await menuTrigger.count() === 1);
  await menuTrigger.click();
  check('Dropdown con panel de control', await page.getByRole('menuitem', { name: /Panel de control/i }).count() === 1);
  await page.getByRole('menuitem', { name: /Panel de control/i }).click();
  await page.waitForSelector('[role="dialog"]');
  check('Panel de control abre', await page.locator('[role="dialog"]').count() === 1);
  await shot(page, '04-control-panel');

  await page.getByRole('tab', { name: /Apariencia/i }).click();
  await page.waitForTimeout(250);
  await shot(page, '05-control-panel-appearance');
  await page.keyboard.press('Escape');
  await page.waitForTimeout(250);

  await page.goto(`${base}/app/raffles`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('h1');
  await page.waitForTimeout(300);
  check('Página de rifas carga', await page.locator('h1').count() > 0);
  await shot(page, '06-raffles');

  const raffleLink = page.locator('a[href^="/app/raffles/"]').first();
  if (await raffleLink.count()) {
    await raffleLink.click();
    await page.waitForSelector('h1');
    await page.waitForTimeout(300);
    check('Detalle de rifa carga', await page.locator('h1').count() > 0);
    await shot(page, '07-raffle-detail');
  }

  await page.goto(`${base}/app/tickets`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('h1');
  await page.waitForTimeout(300);
  check('Página de boletas carga', await page.locator('h1').count() > 0);
  await shot(page, '08-tickets');

  await page.goto(`${base}/app/users`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('h1');
  await page.waitForTimeout(300);
  check('Página de usuarios carga', await page.locator('h1').count() > 0);
  await shot(page, '09-users');

  await page.goto(`${base}/app/audit`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('h1');
  await page.waitForTimeout(300);
  check('Página de auditoría carga', await page.locator('h1').count() > 0);
  await shot(page, '10-audit');

  // Captura de los 12 temas en los 3 modos.
  for (const theme of themes) {
    for (const mode of modes) {
      await page.evaluate(({ theme: t, mode: m }) => {
        window.localStorage.setItem('skatenea:appearance', JSON.stringify({
          theme: t,
          mode: m,
          geometry: 'soft',
          background: 'aurora',
          motion: m !== 'contrast',
        }));
      }, { theme, mode });
      await page.goto(`${base}/app`, { waitUntil: 'domcontentloaded' });
      await page.waitForSelector('h1');
      await page.waitForTimeout(180);
      check(`Tema ${theme} / ${mode}`, await page.locator('h1').count() > 0);
      await shot(page, `theme-${theme}-${mode}`);
    }
  }

  const report = {
    generatedAt: new Date().toISOString(),
    base,
    browser: chrome,
    checks,
    consoleErrors,
    pageErrors,
    screenshots,
  };
  await writeFile(resolve(reports, 'smoke-result.json'), JSON.stringify(report, null, 2), 'utf8');

  const failed = checks.filter((c) => !c.ok);
  console.log(`\nQA smoke: ${checks.length - failed.length}/${checks.length} checks OK`);
  if (failed.length || consoleErrors.length || pageErrors.length) {
    console.error('Errores de consola:', consoleErrors);
    console.error('Errores de página:', pageErrors);
    process.exitCode = 1;
  }
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
