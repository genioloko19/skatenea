import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { writeFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const execFileAsync = promisify(execFile);
const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');
const reports = resolve(root, '..', 'docs', 'qa');
const api = process.env.QA_API_URL ?? 'http://127.0.0.1:8000/api/v1';
const ab = process.env.AB_PATH ?? 'C:\\xampp\\apache\\bin\\ab.exe';

async function run(name, url) {
  const { stdout } = await execFileAsync(ab, ['-n', '100', '-c', '10', url], { timeout: 60000 });
  const grab = (pattern) => {
    const match = stdout.match(pattern);
    return match ? Number(match[1]) : null;
  };
  return {
    name,
    url,
    requestsPerSecond: grab(/Requests per second:\s+([\d.]+)/),
    timePerRequestMs: grab(/Time per request:\s+([\d.]+) \[ms\] \(mean\)/),
    failedRequests: grab(/Failed requests:\s+(\d+)/) ?? 0,
    non2xx: grab(/Non-2xx responses:\s+(\d+)/) ?? 0,
    transferRateKbps: grab(/Transfer rate:\s+([\d.]+)/),
  };
}

async function main() {
  await mkdir(reports, { recursive: true });
  const endpoints = [
    ['health', `${api}/health`],
    ['public_raffles', `${api}/public/raffles`],
  ];
  const results = [];
  for (const [name, url] of endpoints) {
    const result = await run(name, url);
    results.push(result);
    console.log(`${name}: ${result.requestsPerSecond} req/s, ${result.timePerRequestMs} ms, failed=${result.failedRequests}, non2xx=${result.non2xx}`);
  }
  await writeFile(resolve(reports, 'perf-result.json'), JSON.stringify({
    generatedAt: new Date().toISOString(),
    tool: 'ApacheBench (ab)',
    requests: 100,
    concurrency: 10,
    results,
  }, null, 2), 'utf8');
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
