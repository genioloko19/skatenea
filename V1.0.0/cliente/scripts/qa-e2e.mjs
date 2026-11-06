import { chromium } from 'playwright-core';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const execFileAsync = promisify(execFile);
const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');
const server = resolve(root, '..', 'servidor');
const reports = resolve(root, '..', 'docs', 'qa');
const screenshots = resolve(root, '..', 'docs', 'assets', 'screenshots');
const web = process.env.QA_BASE_URL ?? 'http://127.0.0.1:5173';
const apiBase = process.env.QA_API_URL ?? 'http://127.0.0.1:8000/api/v1';
const chrome = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';

const checks = [];
const errors = [];

function check(name, ok, detail = '') {
  checks.push({ name, ok: Boolean(ok), detail });
  console.log(`${ok ? '✓' : '✗'} ${name}${detail ? ' — ' + detail : ''}`);
  if (!ok) errors.push(`${name} ${detail}`);
}

async function api(path, { method = 'GET', token, body } = {}) {
  const headers = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const response = await fetch(`${apiBase}${path}`, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  const text = await response.text();
  let payload = null;
  try { payload = text ? JSON.parse(text) : null; } catch { payload = null; }
  if (!response.ok) {
    const message = payload?.error?.message ?? `HTTP ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    error.payload = payload;
    throw error;
  }
  return payload?.data ?? payload;
}

async function loginApi(email, password) {
  const data = await api('/auth/login', { method: 'POST', body: { email, password } });
  return { token: data.access_token, user: data.user };
}

async function uiLogin(browser, email, password) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  page.on('pageerror', (error) => errors.push(`pageerror: ${error}`));
  page.on('console', (message) => {
    if (message.type() === 'error' && !message.text().includes('Failed to load resource')) {
      errors.push(`console: ${message.text()}`);
    }
  });
  await page.goto(`${web}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await page.getByRole('button', { name: 'Entrar a SKATENEA' }).click();
  await page.waitForURL('**/app', { timeout: 20000 });
  await page.waitForSelector('h1', { timeout: 20000 });
  await page.waitForTimeout(400);
  await page.locator('.toast button').first().click().catch(() => undefined);
  return { context, page };
}

function sqlDate(offsetMs) {
  return new Date(Date.now() + offsetMs).toISOString().slice(0, 19).replace('T', ' ');
}

async function main() {
  await mkdir(reports, { recursive: true });
  await mkdir(screenshots, { recursive: true });

  const admin = await loginApi('admin@skatenea.local', 'Admin#2026!');
  const leader = await loginApi('lider@skatenea.local', 'Lider#2026!');
  const seller = await loginApi('vendedor@skatenea.local', 'Vendedor#2026!');
  const buyer = await loginApi('comprador@skatenea.local', 'Comprador#2026!');
  check('API login de los cuatro roles', true);

  const sellers = await api('/sellers', { token: leader.token });
  const sellerUser = sellers.users.find((item) => item.email === 'vendedor@skatenea.local');
  check('Listado de vendedores para líder', Boolean(sellerUser), sellerUser?.full_name);

  const stamp = Date.now().toString().slice(-6);
  const code = `QA-${stamp}`;
  const created = await api('/raffles', {
    method: 'POST',
    token: leader.token,
    body: {
      name: `Rifa QA ${stamp}`,
      description: 'Rifa creada por el QA automatizado de V1.0.0.',
      prize: 'Premio QA',
      ticket_price: 5000,
      total_tickets: 20,
      start_at: sqlDate(-60_000),
      end_at: sqlDate(86_400_000),
      code,
    },
  });
  const raffleId = created.raffle.id;
  check('Líder crea rifa', created.raffle.status === 'draft', code);

  await api(`/raffles/${raffleId}/submit`, { method: 'POST', token: leader.token });
  const pending = await api(`/raffles/${raffleId}`, { token: leader.token });
  check('Líder envía a aprobación', pending.raffle.status === 'pending_approval');

  const browser = await chromium.launch({ executablePath: chrome, headless: true, args: ['--disable-dev-shm-usage'] });
  try {
    const adminUi = await uiLogin(browser, 'admin@skatenea.local', 'Admin#2026!');
    await adminUi.page.goto(`${web}/app/approvals`, { waitUntil: 'domcontentloaded' });
    await adminUi.page.waitForSelector('h1');
    await adminUi.page.waitForTimeout(400);
    const approvalCard = adminUi.page.locator('.approval-card', { hasText: `Rifa QA ${stamp}` }).first();
    check('Admin ve la rifa pendiente en la UI', await approvalCard.count() === 1);
    if (await approvalCard.count()) {
      await approvalCard.getByRole('button', { name: 'Aprobar' }).click();
      await adminUi.page.waitForTimeout(900);
    }
    const approved = await api(`/raffles/${raffleId}`, { token: admin.token });
    check('Admin aprueba la rifa', approved.raffle.status === 'approved');
    await adminUi.page.screenshot({ path: resolve(screenshots, 'qa-e2e-approvals.png') });
    await adminUi.context.close();

    const leaderUi = await uiLogin(browser, 'lider@skatenea.local', 'Lider#2026!');
    await leaderUi.page.goto(`${web}/app/raffles/${raffleId}`, { waitUntil: 'domcontentloaded' });
    await leaderUi.page.waitForSelector('h1');
    await leaderUi.page.waitForTimeout(500);
    await leaderUi.page.getByRole('button', { name: /Activar rifa/i }).click();
    await leaderUi.page.waitForTimeout(900);
    await leaderUi.page.getByRole('button', { name: /Generar boletas/i }).click();
    await leaderUi.page.waitForTimeout(1200);
    const generated = await api(`/raffles/${raffleId}`, { token: leader.token });
    check('Líder activa y genera boletas', generated.raffle.stats.total === 20, String(generated.raffle.stats.total));

    await leaderUi.page.reload({ waitUntil: 'domcontentloaded' });
    await leaderUi.page.waitForSelector('h1');
    await leaderUi.page.waitForTimeout(400);
    const sellerRow = leaderUi.page.locator('.check-grid .check-row', { hasText: 'Vendedor Demo' }).first();
    if (await sellerRow.count()) {
      await sellerRow.locator('input[type="checkbox"]').check();
      await leaderUi.page.getByRole('button', { name: 'Asignar vendedores' }).click();
      await leaderUi.page.waitForTimeout(900);
    }
    const assigned = await api(`/raffles/${raffleId}`, { token: leader.token });
    check('Líder asigna vendedor', assigned.sellers.some((item) => item.id === sellerUser.id));

    await leaderUi.page.locator('select').last().selectOption({ label: 'Vendedor Demo' });
    await leaderUi.page.locator('input[type="number"]').nth(0).fill('1');
    await leaderUi.page.locator('input[type="number"]').nth(1).fill('10');
    await leaderUi.page.getByRole('button', { name: 'Asignar rango' }).click();
    await leaderUi.page.waitForTimeout(900);
    const ticketsPayload = await api(`/raffles/${raffleId}/tickets`, { token: leader.token });
    const assignedCount = ticketsPayload.tickets.filter((ticket) => ticket.seller_id === sellerUser.id).length;
    check('Líder asigna rango de 10 boletas', assignedCount === 10, String(assignedCount));
    await leaderUi.page.screenshot({ path: resolve(screenshots, 'qa-e2e-raffle-managed.png') });
    await leaderUi.context.close();

    // Autorización: vendedor no accede a usuarios ni a boletas no asignadas.
    const sellerUi = await uiLogin(browser, 'vendedor@skatenea.local', 'Vendedor#2026!');
    await sellerUi.page.goto(`${web}/app/users`, { waitUntil: 'domcontentloaded' });
    await sellerUi.page.waitForTimeout(400);
    check('Vendedor recibe 403 en usuarios', (await sellerUi.page.locator('body').innerText()).includes('No tienes acceso'));

    const foreign = ticketsPayload.tickets.find((ticket) => ticket.number === 11);
    let forbidden = false;
    try {
      await api(`/tickets/${foreign.id}/reserve`, {
        method: 'POST',
        token: seller.token,
        body: { full_name: 'Intruso', phone: '3110000000' },
      });
    } catch (error) {
      forbidden = error.status === 403;
    }
    check('API rechaza reserva de boleta no asignada', forbidden);

    await sellerUi.page.goto(`${web}/app/tickets`, { waitUntil: 'domcontentloaded' });
    await sellerUi.page.waitForSelector('h1');
    await sellerUi.page.waitForTimeout(500);
    const ticketRow = sellerUi.page
      .locator('tbody tr', { hasText: `Rifa QA ${stamp}` })
      .filter({ has: sellerUi.page.getByText('#1', { exact: true }) })
      .first();
    check('Vendedor ve su boleta en la UI', await ticketRow.count() === 1);
    await ticketRow.getByRole('button', { name: 'Separar' }).click();
    await sellerUi.page.waitForSelector('#reserve-form');
    await sellerUi.page.getByLabel('Nombre completo').fill('Comprador Demo');
    await sellerUi.page.getByLabel('Teléfono').fill('3000000004');
    await sellerUi.page.getByLabel('Correo').fill('comprador@skatenea.local');
    await sellerUi.page.getByLabel('Número de documento').fill('900000001');
    await sellerUi.page.getByRole('button', { name: 'Separar boleta' }).click();
    await sellerUi.page.waitForTimeout(900);
    await ticketRow.getByRole('button', { name: 'Pagar' }).click();
    await sellerUi.page.waitForSelector('#pay-form');
    await sellerUi.page.getByLabel('Método de pago').selectOption('cash');
    await sellerUi.page.getByLabel('Referencia (opcional)').fill('QA-E2E');
    await sellerUi.page.getByRole('button', { name: 'Confirmar pago' }).click();
    await sellerUi.page.waitForTimeout(1000);
    const paid = await api(`/tickets/${ticketsPayload.tickets.find((ticket) => ticket.number === 1).id}`, { token: seller.token });
    check('Vendedor separa y paga la boleta', paid.ticket.status === 'paid', paid.ticket.status);
    await sellerUi.page.screenshot({ path: resolve(screenshots, 'qa-e2e-ticket-paid.png') });

    const printResponse = await api(`/tickets/${paid.ticket.id}/print`, { method: 'POST', token: seller.token });
    const printJobId = printResponse.print_job.id;
    await execFileAsync('php', ['bin/print-worker.php', '--once'], { cwd: server, timeout: 30000 });
    const printStatus = await api(`/print-jobs/${printJobId}`, { token: seller.token });
    check('Worker de impresión procesa la boleta', printStatus.print_job.status === 'done', printStatus.print_job.status);

    const receipt = await fetch(`${apiBase}/tickets/${paid.ticket.id}/receipt.pdf`, {
      headers: { Authorization: `Bearer ${seller.token}` },
    });
    const pdfBuffer = Buffer.from(await receipt.arrayBuffer());
    check('PDF de recibo generado', receipt.ok && pdfBuffer.subarray(0, 4).toString() === '%PDF', String(pdfBuffer.length));

    const exportResponse = await fetch(`${apiBase}/raffles/${raffleId}/export.csv`, {
      headers: { Authorization: `Bearer ${leader.token}` },
    });
    const csv = await exportResponse.text();
    check('Exportación CSV de rifa', exportResponse.ok && csv.includes(code));

    const notifications = await api('/notifications', { token: leader.token });
    check('Líder recibe notificación de aprobación', notifications.notifications.some((item) => item.title.includes('Rifa aprobada')));
    await sellerUi.context.close();

    // Comprador: ve su boleta y verifica por QR.
    const buyerUi = await uiLogin(browser, 'comprador@skatenea.local', 'Comprador#2026!');
    await buyerUi.page.goto(`${web}/app/tickets`, { waitUntil: 'domcontentloaded' });
    await buyerUi.page.waitForSelector('h1');
    await buyerUi.page.waitForTimeout(500);
    check('Comprador ve su boleta pagada', (await buyerUi.page.locator('body').innerText()).includes(`Rifa QA ${stamp}`));
    const buyerTickets = await api('/tickets/mine', { token: buyer.token });
    const buyerTicket = buyerTickets.tickets.find((ticket) => ticket.raffle_id === raffleId);
    check('Comprador tiene token QR', Boolean(buyerTicket?.qr_token));
    await buyerUi.page.goto(`${web}/verify/${buyerTicket.qr_token}`, { waitUntil: 'domcontentloaded' });
    await buyerUi.page.waitForSelector('h1');
    await buyerUi.page.waitForTimeout(400);
    check('Página pública verifica la boleta', (await buyerUi.page.locator('body').innerText()).includes('Boleta verificada'));
    await buyerUi.page.screenshot({ path: resolve(screenshots, 'qa-e2e-verify.png') });
    await buyerUi.context.close();

    // Recuperación de contraseña completa (se restaura al final).
    const oldPassword = 'Comprador#2026!';
    const newPassword = 'Comprador#2026!QA';
    await api('/auth/forgot-password', { method: 'POST', body: { email: 'comprador@skatenea.local' } });
    await execFileAsync('php', ['bin/mail-worker.php', '--once'], { cwd: server, timeout: 30000 });
    const { readFile } = await import('node:fs/promises');
    const mailLog = await readFile(resolve(server, 'storage', 'logs', 'mail.log'), 'utf8');
    const matches = [...mailLog.matchAll(/reset-password\?token=([a-f0-9]+)/g)];
    const resetToken = matches.at(-1)?.[1];
    check('Correo de recuperación encolado y enviado', Boolean(resetToken));
    if (resetToken) {
      await api('/auth/reset-password', { method: 'POST', body: { token: resetToken, password: newPassword } });
      const relogin = await loginApi('comprador@skatenea.local', newPassword);
      check('Contraseña temporal funciona', Boolean(relogin.token));
      await api('/auth/password', {
        method: 'PATCH',
        token: relogin.token,
        body: { current_password: newPassword, new_password: oldPassword },
      });
      check('Contraseña restaurada', true);
    }

    const report = {
      generatedAt: new Date().toISOString(),
      raffleId,
      code,
      checks,
      errors,
      summary: { total: checks.length, ok: checks.filter((item) => item.ok).length },
    };
    await writeFile(resolve(reports, 'e2e-result.json'), JSON.stringify(report, null, 2), 'utf8');
    console.log(`\nE2E: ${report.summary.ok}/${report.summary.total} checks OK`);
    if (errors.length) {
      console.error(errors);
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
