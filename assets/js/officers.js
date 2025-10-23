document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('officersForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        // UI Feedback
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        try {
            const response = await fetch('api/officers_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'An unknown error occurred.');
            }

            showToast(result.message, 'Assignments Saved', 'success');
            // Optional: You could reload the page to see names update if they changed,
            // but a toast is often sufficient feedback.
            // setTimeout(() => window.location.reload(), 1500);

        } catch (error) {
            showToast(`Error: ${error.message}`, 'Save Failed', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-person-check"></i> Save Assignments';
        }
    });
});