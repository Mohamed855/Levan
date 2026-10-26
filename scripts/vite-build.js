import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const viteBin = path.resolve(__dirname, '../node_modules/vite/bin/vite.js');

// Normalize common CLI typos like --whatch -> --watch
const rawArgs = process.argv.slice(2);
const args = rawArgs.map((arg) => (arg === '--whatch' ? '--watch' : arg));

const child = spawn(process.execPath, ['--max-old-space-size=4096', viteBin, 'build', ...args], {
    stdio: 'inherit',
    env: {
        ...process.env,
        NODE_OPTIONS: `${process.env.NODE_OPTIONS || ''} --max-old-space-size=4096`.trim(),
    },
});

child.on('exit', (code) => {
    process.exit(code ?? 0);
});
