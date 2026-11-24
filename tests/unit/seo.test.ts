import { describe, expect, it } from 'vitest';
import { breadcrumbJsonLd, businessJsonLd, metaDescription } from '~/lib/seo';
import { DEFAULT_ADDRESS, DEFAULT_PHONE, telHref, whatsappUrl } from '~/lib/settings';

const ORIGIN = 'https://yuccahomeware.com';

describe('metaDescription', () => {
  it('keeps short text and collapses whitespace', () => {
    expect(metaDescription('  Soft   linen,\nwoven in Kenya. ')).toBe('Soft linen, woven in Kenya.');
  });

  it('cuts long text at a word boundary with an ellipsis', () => {
    const result = metaDescription('word '.repeat(60));
    expect(result.length).toBeLessThanOrEqual(158);
    expect(result).toMatch(/word…$/);
  });
});

describe('businessJsonLd', () => {
  it('places the business in Nairobi County, uses the business number and leaves out a placeholder address', () => {
    const data = businessJsonLd({ business_address: DEFAULT_ADDRESS }, ORIGIN);
    expect(data.address).toMatchObject({ addressLocality: 'Nairobi', addressRegion: 'Nairobi County', addressCountry: 'KE' });
    expect(data.address.streetAddress).toBeUndefined();
    expect(data.telephone).toBe(DEFAULT_PHONE);
    expect(data.sameAs).toBeUndefined();
    expect(data['@id']).toBe(`${ORIGIN}/#business`);
  });

  it('includes real contact details and social profiles once an admin sets them', () => {
    const data = businessJsonLd({
      business_phone: '+254 712 345 678', business_address: 'Ngong Road, Nairobi', instagram_url: 'https://www.instagram.com/yucca',
    }, ORIGIN);
    expect(data.telephone).toBe('+254 712 345 678');
    expect(data.address.streetAddress).toBe('Ngong Road, Nairobi');
    expect(data.sameAs).toEqual(['https://www.instagram.com/yucca']);
  });
});

describe('contact links for the business number', () => {
  it('builds tel: and WhatsApp links in international format from a local Kenyan number', () => {
    expect(telHref(DEFAULT_PHONE)).toBe('tel:+254119935009');
    expect(whatsappUrl({ business_phone: '0119935009' })).toBe('https://wa.me/254119935009');
    expect(whatsappUrl({})).toBe('https://wa.me/254119935009');
  });
});

describe('breadcrumbJsonLd', () => {
  it('starts at Home and numbers each absolute link', () => {
    const data = breadcrumbJsonLd(ORIGIN, [['Shop', '/shop'], ['Santa Boucle', '/products/santa-boucle']]);
    expect(data.itemListElement.map((item) => [item.position, item.name, item.item])).toEqual([
      [1, 'Home', `${ORIGIN}/`],
      [2, 'Shop', `${ORIGIN}/shop`],
      [3, 'Santa Boucle', `${ORIGIN}/products/santa-boucle`],
    ]);
  });
});
