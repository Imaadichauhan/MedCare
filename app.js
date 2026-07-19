/**
 * MediCare HMS — shared front-end behavior.
 * Plain JS, no dependencies, so it runs as-is on any XAMPP/WAMP setup.
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Mobile sidebar toggle ----
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // ---- Confirm before destructive actions ----
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ---- Auto-dismiss flash alerts after 5s ----
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.transition = 'opacity 0.4s ease';
            alertEl.style.opacity = '0';
            setTimeout(function () { alertEl.remove(); }, 400);
        }, 5000);
    });

    // ---- Simple client-side table search ----
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        const tableId = input.getAttribute('data-table-search');
        const table = document.getElementById(tableId);
        if (!table) return;
        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // ---- Modal open/close ----
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modal = document.getElementById(btn.getAttribute('data-modal-open'));
            if (modal) modal.classList.add('open');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('open');
        });
    });

    // ---- Auto-calc invoice total on billing form ----
    const billingForm = document.getElementById('billingForm');
    if (billingForm) {
        const fields = ['consultation_fee', 'medicine_charges', 'lab_charges', 'ward_charges', 'other_charges', 'discount'];
        const totalDisplay = document.getElementById('totalAmountDisplay');
        const totalHidden = document.getElementById('totalAmountHidden');

        function recalc() {
            let total = 0;
            fields.forEach(function (name) {
                const input = billingForm.querySelector('[name="' + name + '"]');
                const val = parseFloat(input && input.value ? input.value : 0) || 0;
                total += (name === 'discount') ? -val : val;
            });
            total = Math.max(total, 0);
            if (totalDisplay) totalDisplay.textContent = total.toFixed(2);
            if (totalHidden) totalHidden.value = total.toFixed(2);
        }

        fields.forEach(function (name) {
            const input = billingForm.querySelector('[name="' + name + '"]');
            if (input) input.addEventListener('input', recalc);
        });
        recalc();
    }

    // ---- Prescription: add/remove medicine rows ----
    const addMedBtn = document.getElementById('addMedicineRow');
    if (addMedBtn) {
        addMedBtn.addEventListener('click', function () {
            const container = document.getElementById('medicineRows');
            const template = document.getElementById('medicineRowTemplate');
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
        });
        document.getElementById('medicineRows').addEventListener('click', function (e) {
            if (e.target.matches('[data-remove-row]')) {
                e.target.closest('.med-row').remove();
            }
        });
    }
});
