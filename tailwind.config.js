/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.vue',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Primary: indigo/blue tones for aviation feel
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
