// Usage: node tests/e2e/run.mjs [--phase 1] [--fresh] [--base http://...]
// Starts the app on a throw-away database, runs the chosen phase scripts, prints only the problems.
import { setupDatabase, startServer, waitForServer, launch, report, Findings, BASE } from './lib/harness.mjs';
import { pathToFileURL } from 'node:url';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const args = process.argv.slice(2);
const flag = n => args.includes(n);
const val = n => { const i = args.indexOf(n); return i >= 0 ? args[i + 1] : undefined; };
const phases = (val('--phase') ?? 'all').split(',');
const dir = path.dirname(fileURLToPath(import.meta.url));

const ALL = ['phase1', 'phase2', 'phase3', 'phase4', 'phase5'];
const chosen = phases[0] === 'all' ? ALL : phases.map(p => (p.startsWith('phase') ? p : `phase${p}`));

let server;
let problems = 0;
try {
    if (!process.env.E2E_BASE) {
        setupDatabase({ fresh: flag('--fresh') || !flag('--keep') });
        server = startServer();
    }
    await waitForServer();
    const browser = await launch();
    for (const name of chosen) {
        const mod = await import(pathToFileURL(path.join(dir, `${name}.mjs`)).href);
        const findings = new Findings();
        try { await mod.default({ browser, findings, BASE }); }
        catch (e) { findings.add('script crashed', name, (e.message.split('\n')[0] + ' @ ' + (e.stack?.split('\n').find(l => l.includes(name + '.mjs')) ?? '').trim()).replace(/file:\/\/\S*\//, '')); }
        problems += report(findings, name);
    }
    await browser.close();
} finally {
    server?.kill();
}
process.exit(problems ? 1 : 0);
