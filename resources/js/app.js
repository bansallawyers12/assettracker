import './bootstrap';
import './documents.js';
import { initFlatpickr } from './flatpickr-init';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', function() {
    initFlatpickr();

    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    function switchTab(event) {
        event.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        tabContents.forEach(function(content) {
            content.classList.add('hidden');
        });
        document.getElementById(targetId).classList.remove('hidden');
        tabLinks.forEach(function(link) {
            link.classList.remove('border-blue-500', 'text-blue-700');
            link.classList.add('border-transparent', 'text-gray-500');
        });
        this.classList.remove('border-transparent', 'text-gray-500');
        this.classList.add('border-blue-500', 'text-blue-700');
    }

    tabLinks.forEach(function(link) {
        link.addEventListener('click', switchTab);
    });

    if (tabLinks.length > 0) {
        const firstTabLink = tabLinks[0];
        firstTabLink.classList.add('border-blue-500', 'text-blue-700');
        const firstTabContent = document.getElementById(firstTabLink.getAttribute('href').substring(1));
        firstTabContent.classList.remove('hidden');
    }
});