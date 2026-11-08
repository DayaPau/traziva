/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./config/traziva.php",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Fraunces', 'Georgia', 'serif'],
      },
      colors: {
        // Paleta principal
        'agro-primary': '#3C8D40',
        'agro-accent': '#79C86E',
        'agro-sand': '#F2F0E4',
        'agro-dark': '#2F2F2F',

        // Tonos de la referencia visual (demo para la Coordinadora)
        'agro-forest': '#1F3A2A',   // barra lateral
        'agro-leaf': '#5E9E5A',     // ítem activo del menú
        'agro-moss': '#2F7A47',     // botones y producción real
        'agro-earth': '#C4955F',    // meta productiva / eventos
        'agro-clay': '#B0644F',     // bajo desempeño
        'agro-cream': '#F8F7F2',    // fondo de página

        // Paleta verde extendida
        'agro-green': {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },

        // Paleta dorada extendida
        'agro-gold': {
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
          800: '#92400e',
          900: '#78350f',
        },
      },
    },
  },
  plugins: [],
};
