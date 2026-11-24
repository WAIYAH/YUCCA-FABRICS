import { env } from 'cloudflare:workers';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { constraintFailed } from '~/lib/db';
import { saveOrDiscard } from '~/lib/uploads';

const uploads = env.UPLOADS as unknown as { delete?: (key: string) => Promise<void> };
const IMAGE = `/media/blog/${'a'.repeat(40)}.jpg`;

describe('constraintFailed', () => {
  it('tells foreign key failures apart from unique ones, including in the error cause', () => {
    const foreignKey = new Error('D1_ERROR', { cause: new Error('FOREIGN KEY constraint failed: SQLITE_CONSTRAINT') });
    expect(constraintFailed(foreignKey, 'FOREIGN KEY')).toBe(true);
    expect(constraintFailed(foreignKey, 'UNIQUE')).toBe(false);
    expect(constraintFailed(foreignKey)).toBe(true);
    expect(constraintFailed(new Error('UNIQUE constraint failed: products.sku'), 'FOREIGN KEY')).toBe(false);
  });
});

describe('saveOrDiscard', () => {
  afterEach(() => { delete uploads.delete; });

  it('keeps the uploaded image when the database write succeeds', async () => {
    uploads.delete = vi.fn(async () => {});
    await expect(saveOrDiscard(IMAGE, async () => 'saved')).resolves.toBe('saved');
    expect(uploads.delete).not.toHaveBeenCalled();
  });

  it('deletes the uploaded image and rethrows when the database write fails', async () => {
    uploads.delete = vi.fn(async () => {});
    await expect(saveOrDiscard(IMAGE, async () => { throw new Error('FOREIGN KEY constraint failed'); })).rejects.toThrow('FOREIGN KEY');
    expect(uploads.delete).toHaveBeenCalledWith(IMAGE.slice('/media/'.length));
  });
});
