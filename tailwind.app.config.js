/**
 * Feuille de style de l'application (public/css/app.css), compilée une fois et versionnée : elle
 * remplace le CDN Tailwind, qui générait les styles dans le navigateur à chaque page (lent sur mobile
 * et sur les réseaux faibles). Régénérer après une modification de vues : npm run build:css
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './app/**/*.php',
    ],

    // Classes construites à la volée dans les vues (ex. « bg-{{ $couleur }}-100 ») : invisibles pour
    // l'analyse du code, elles sont toujours incluses.
    safelist: [
        { pattern: /^(bg|text|border)-(gray|green|blue|yellow|red|purple|orange|indigo|pink|teal)-(50|100|200|300|500|600|700|800)$/ },
    ],

    theme: {
        extend: {
            // Palette pastel de l'interface (identique à celle définie jusqu'ici pour le CDN)
            colors: {
                primary: '#87CEEB',
                'primary-dark': '#5FB3D9',
                secondary: '#FFF9C4',
                accent: '#A5D6A7',
                danger: '#EF9A9A',
            },
        },
    },

    plugins: [],
};
