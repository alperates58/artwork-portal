export default {
  content: [
    './resources/views/**/*.blade.php',
    './app/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        brand: {
          50: '#fff9ed',
          100: '#fff0cf',
          200: '#ffe09c',
          300: '#ffca5d',
          400: '#ffb632',
          500: '#f49a0b',
          600: '#db7906',
          700: '#b65909',
          800: '#944511',
          900: '#7a3a12'
        }
      }
    },
  },
  plugins: [],
};
