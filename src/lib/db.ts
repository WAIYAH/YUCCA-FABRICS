import { env } from 'cloudflare:workers';
import type { BatchItem } from 'drizzle-orm/batch';
import { drizzle } from 'drizzle-orm/d1';
import * as schema from '~/db/schema';

export const db = drizzle(env.DB, { schema });
export type Db = typeof db;
export { schema };

/**
 * Runs statements atomically: D1 batches succeed or fail as a whole. Accepts a dynamic list
 * (drizzle's batch() wants a non-empty tuple type).
 */
export async function runBatch(statements: BatchItem<'sqlite'>[]) {
  if (statements.length === 0) return [];
  return db.batch(statements as [BatchItem<'sqlite'>, ...BatchItem<'sqlite'>[]]);
}
