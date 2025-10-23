document.addEventListener('DOMContentLoaded', function() {
    const selectAllPpe = document.getElementById('select-all-ppe');
    const selectAllSep = document.getElementById('select-all-sep');
    const ppeCheckboxes = document.querySelectorAll('.ppe-item-checkbox');
    const sepCheckboxes = document.querySelectorAll('.sep-item-checkbox');
    const generatePdfBtn = document.getElementById('generate-pdf-btn');

    function toggleCheckboxes(masterCheckbox, childCheckboxes) {
        childCheckboxes.forEach(checkbox => {
            checkbox.checked = masterCheckbox.checked;
        });
        updateButtonState();
    }

    function updateButtonState() {
        const anyChecked = document.querySelector('#qr-generator-form input[type="checkbox"]:checked');
        generatePdfBtn.disabled = !anyChecked;
    }

    if (selectAllPpe) {
        selectAllPpe.addEventListener('change', () => {
            toggleCheckboxes(selectAllPpe, ppeCheckboxes);
        });
    }

    if (selectAllSep) {
        selectAllSep.addEventListener('change', () => {
            toggleCheckboxes(selectAllSep, sepCheckboxes);
        });
    }

    ppeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonState);
    });

    sepCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonState);
    });

    document.getElementById('qr-generator-form').addEventListener('submit', function(e) {
        if (document.querySelectorAll('#qr-generator-form input[type="checkbox"]:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one item to generate QR codes.');
        }
    });

    // Initial state
    updateButtonState();
});