import { defineAction } from 'astro:actions';
import { z } from 'astro/zod';
import { db, schema } from '~/lib/db';

export const forms = {
  newsletter: defineAction({
    accept: 'form',
    input: z.object({ email: z.email('Enter a valid email address.').max(190).transform((v) => v.toLowerCase()) }),
    async handler({ email }) {
      await db.insert(schema.newsletter).values({ email })
        .onConflictDoUpdate({ target: schema.newsletter.email, set: { isActive: true } });
      return { message: 'Thank you for subscribing. Look out for notes from the studio.' };
    },
  }),
};
