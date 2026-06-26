import preset from './vendor/filament/support/tailwind.config.preset'

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        // Autodiscover views and components inside our custom modules
        './app/Modules/**/*.blade.php',
        './app/Modules/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
