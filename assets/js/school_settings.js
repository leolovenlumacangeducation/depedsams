document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('schoolSettingsForm');
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logo-preview');

    if (!form) return;

    // Handle form submission
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        // UI Feedback
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/school_settings_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'An unknown error occurred.');
            }

            showToast(result.message, 'Success', 'success');
            // Optional: Reload to see changes immediately, especially the logo in the header if it exists there.
            setTimeout(() => window.location.reload(), 1500);

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-save"></i> Save Settings';
        }
    });

    // Handle live preview for logo upload
    logoInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                logoPreview.src = event.target.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
});