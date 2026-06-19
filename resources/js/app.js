import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const startAlpine = () => {
    if (window.alpineInitialized) return;
    window.alpineInitialized = true;
    document.dispatchEvent(new CustomEvent('alpine:init'));
    Alpine.start();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startAlpine);
} else {
    setTimeout(startAlpine, 0);
}

