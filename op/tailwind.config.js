/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    './**/*.js',
    "./pages/**/*.php",
    "./layout/**/*.php",
    "./**/*.php",
    "./op/pages/**/*.php",
    "./op/layout/**/*.php",
    "./op/**/*.php",
  ],
  safelist: [
      'bg-amber-100',
      'text-amber-600',
      'bg-lime-100',
      'text-lime-600',
   ],
  theme: {
    extend: {},
  },
  plugins: [],

};
