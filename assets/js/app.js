import '../css/app.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';

document.addEventListener('DOMContentLoaded', () => {
    console.log('Inventory app loaded');

    // Toggle like functionality
    document.querySelectorAll('[data-like-url]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const url = btn.getAttribute('data-like-url');
            const response = await fetch(url, { method: 'POST' });
            const data = await response.json();
            btn.innerHTML = `<i class="bi bi-heart-fill"></i> ${data.likes}`;
        });
    });

    // Table row hover
    document.querySelectorAll('table.table tbody tr').forEach((row) => {
        row.addEventListener('mouseenter', () => row.classList.add('table-active'));
        row.addEventListener('mouseleave', () => row.classList.remove('table-active'));
    });
});