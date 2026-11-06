import { chromium } from 'playwright-core';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');
const reports = resolve(root, '..', 'docs', 'qa');
const web = process.env.QA_BASE_URL ?? 'http://127.0.0.1:5173';
const chrome = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

const pages = [
  { name: 'landing', path: '/' },
  { name: 'login', path: '/login' },
  { name: 'forgot', path: '/forgot-password' },
  { name: 'reset', path: '/reset-password' },
  { name: 'dashboard', path: '/app' },
  { name: 'raffles', path: '/app/raffles' },
  { name: 'tickets', path: '/app/tickets' },
  { name: 'users', path: '/app/users' },
  { name: 'audit', path: '/app/audit' },
];

const modes = ['light', 'dark', 'contrast'];
const results = [];

async function login(page) {
  await page.goto(`${web}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', 'admin@skatenea.local');
  await page.fill('input[type="password"]', 'Admin#2026!');
  await page.getByRole('button', { name: 'Entrar a SKATENEA' }).click();
  await page.waitForURL('**/app', { timeout: 20000 });
  await page.waitForSelector('h1');
  await page.locator('.toast button').first().click().catch(() => undefined);
}

async function runAxe(page, axeSource, name) {
  await page.addScriptTag({ content: axeSource });
  const result = await page.evaluate(async () => {
    // @ts-expect-error axe se inyecta en tiempo de ejecución
    return await window.axe.run(document, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
      resultTypes: ['violations'],
    });
  });

  const serious = result.violations.filter((violation) => ['serious', 'critical'].includes(violation.impact));
  results.push({
    page: name,
    violations: result.violations.map((violation) => ({
      id: violation.id,
      impact: violation.impact,
      help: violation.help,
      nodes: violation.nodes.map((node) => ({
        target: node.target,
        html: node.html.slice(0, 220),
        failureSummary: node.failureSummary,
      })),
    })),
    serious: serious.length,
  });
  console.log(`${serious.length ? '✗' : '✓'} ${name}: ${result.violations.length} violaciones (${serious.length} graves)`);
  return serious.length;
}

async function main() {
  await mkdir(reports, { recursive: true });
  const axeSource = await readFile(resolve(root, 'node_modules', 'axe-core', 'axe.min.js'), 'utf8');
  const browser = await chromium.launch({ executablePath: chrome, headless: true, args: ['--disable-dev-shm-usage'] });
  try {
    for (const mode of modes) {
      const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
      const page = await context.newPage();
      for (const item of pages) {
        if (item.path.startsWith('/app') && item.name !== 'dashboard') {
          await login(page);
          await page.goto(`${web}${item.path}`, { waitUntil: 'domcontentloaded' });
          await page.waitForSelector('h1');
        } else if (item.path.startsWith('/app')) {
          await login(page);
          await page.goto(`${web}${item.path}`, { waitUntil: 'domcontentloaded' });
          await page.waitForSelector('h1');
        } else {
          await page.goto(`${web}${item.path}`, { waitUntil: 'domcontentloaded' });
          await page.waitForSelector('h1');
        }
        await page.evaluate((m) => {
          const current = JSON.parse(window.localStorage.getItem('skatenea:appearance') ?? '{}');
          window.localStorage.setItem('skatenea:appearance', JSON.stringify({
            theme: current.theme ?? 'aurora',
            mode: m,
            geometry: current.geometry ?? 'soft',
            background: current.background ?? 'aurora',
            motion: true,
          }));
        }, mode);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.waitForSelector('h1');
        await page.waitForTimeout(350);
        await runAxe(page, axeSource, `${mode}:${item.name}`);
      }
      await context.close();
    }

    const report = {
      generatedAt: new Date().toISOString(),
      standard: 'WCAG 2.2 AA (axe-core tags wcag2a/wcag2aa/wcag21aa/wcag22aa)',
      results,
      serious: results.reduce((sum, item) => sum + item.serious, 0),
    };
    await writeFile(resolve(reports, 'axe-result.json'), JSON.stringify(report, null, 2), 'utf8');
    console.log(`\nAxe: ${report.serious} hallazgos graves/críticos`);
    if (report.serious > 0) process.exitCode = 1;
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
