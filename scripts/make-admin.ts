// Promotes a registered account to admin. Register through /auth/register first (so Better Auth hashes the
// password), then run:  pnpm make-admin you@example.com          (local database)
//                       pnpm make-admin you@example.com --remote (production database)
import { spawnSync } from 'node:child_process';

const [email, flag] = process.argv.slice(2);
// Strict pattern: the email is placed in SQL below, so nothing outside this character set may reach it.
if (!email || !/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(email)) {
  console.error('Usage: pnpm make-admin <email> [--remote]');
  process.exit(1);
}
const target = flag === '--remote' ? '--remote' : '--local';
const sql = `UPDATE user SET role = 'admin', status = 'active' WHERE email = '${email.toLowerCase()}' RETURNING email, role`;

// Wrangler runs directly under Node, so no shell ever parses the SQL. It is passed with --command, not --file:
// a remote --file run prints upload progress before the JSON and returns only a summary, not the RETURNING rows.
const run = spawnSync(process.execPath, ['node_modules/wrangler/bin/wrangler.js', 'd1', 'execute', 'yucca-db', target, '--json', `--command=${sql}`], { encoding: 'utf8' });

// Wrangler can crash on exit on Windows after printing its result, so trust the JSON rather than the exit code.
let rows: { email: string; role: string }[];
try {
  rows = (JSON.parse(run.stdout) as { results: { email: string; role: string }[] }[])[0]?.results ?? [];
} catch {
  console.error(`Wrangler failed:\n${run.stderr || run.stdout}`);
  process.exit(1);
}
if (rows.length === 0) {
  console.error(`No account found for ${email}. Register it at /auth/register first.`);
  process.exit(1);
}
console.log(`${rows[0]!.email} is now an admin (${target.slice(2)} database). Sign out and in again to load the admin menu.`);
