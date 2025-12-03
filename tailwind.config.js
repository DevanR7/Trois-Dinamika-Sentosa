import defaultTheme from 'tailwindcss/defaultTheme'
import flowbitePlugin from 'flowbite/plugin'

export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './node_modules/flowbite/**/*.js'
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                slate: {
                    850: '#151f32'
                }
            },

            zIndex: {
                '60': '60',
                '70': '70',
                '100': '100',
            }
        },
    },

    plugins: [
        flowbitePlugin
    ],
}
