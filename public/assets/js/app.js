/**
 * Bank Sampah Digital Desa - Main JavaScript
 *assets/css/app.js
 * File JavaScript utama untuk interaksi frontend
 */

(function() {
    'use strict';

    // Console log untuk development
    console.log('Bank Sampah Digital Desa - App Loaded');

    // Fungsi untuk handle AJAX requests
    window.ajax = async function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaults, ...options };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    };

    // Fungsi untuk menampilkan toast notification
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Konfirmasi sebelum submit form
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.dataset.confirm) {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        }
    });

})();
