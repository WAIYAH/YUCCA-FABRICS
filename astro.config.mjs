// @ts-check
import { defineConfig } from 'astro/config';
import cloudflare from '@astrojs/cloudflare';
import tailwindcss from '@tailwindcss/vite';
import icon from 'astro-icon';

export default defineConfig({
  output: 'server',
  // Site images are optimised once at build time; uploads in R2 are served as stored.
  adapter: cloudflare({ imageService: 'compile' }),
  integrations: [
    icon({ include: { 'fa6-solid': ['*'], 'fa6-regular': ['*'], 'fa6-brands': ['*'] } }),
  ],
  // Rejects cross-site form posts (CSRF) by checking the Origin header on every non-GET request.
  security: { checkOrigin: true },
  devToolbar: { enabled: false },
  vite: { plugins: [tailwindcss()] },
});
