// Shared Tailwind theme for the storefront and admin. Colours are taken from the Yucca logo
// (olive leaves, wooden pot, cream ground); every text colour passes WCAG AA on white and sand.
tailwind.config = {
  theme: {
    extend: {
      colors: {
        forest: '#3F5320', // deep olive: primary buttons, headings accents
        olive: '#5B7029', // logo leaf green: hovers, links
        sage: '#647536', // small labels on light backgrounds
        clay: '#8C5530', // logo wood brown: secondary accent and CTAs
        wheat: '#D8B07E', // warm accent on dark backgrounds
        sand: '#F7F4EE', // page background
        cream: '#EFEADF', // alternate section background
        ink: '#2A2B25', // body text
        night: '#252E16', // footer and top bar
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        serif: ['Poppins', 'sans-serif'],
      },
    },
  },
};
