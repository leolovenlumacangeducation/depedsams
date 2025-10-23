document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myProfileForm');
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('profile-photo-preview');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const resetBtn = document.getElementById('resetForm');

    if (!form) return;

    // --- Photo Preview ---
    if (photoInput && photoPreview) {
        photoPreview.setAttribute('data-default', photoPreview.src);
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) { // 2MB limit
                    alert('Profile photo must be less than 2MB.');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) { photoPreview.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        });
    }

    // --- Password visibility toggles ---
    function setupToggle(btn, inputId) {
        const input = document.getElementById(inputId);
        if (!btn || !input) return;
        btn.addEventListener('click', function() {
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    setupToggle(togglePassword, 'new_password');
    setupToggle(toggleConfirmPassword, 'confirm_password');

    // --- Reset handler ---
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            form.reset();
            const defaultSrc = photoPreview.getAttribute('data-default');
            if (defaultSrc) photoPreview.src = defaultSrc;
        });
    }

    // --- Load quick asset stats for the current user ---
    async function loadAssetStats() {
        try {
            const [ppeResp, sepResp, consumableResp] = await Promise.all([
                fetch('api/get_my_ppe.php'),
                fetch('api/get_my_sep.php'),
                fetch('api/get_my_consumables.php')
            ]);

            const [ppeJson, sepJson, consumableJson] = await Promise.all([
                ppeResp.ok ? ppeResp.json() : { data: [] },
                sepResp.ok ? sepResp.json() : { data: [] },
                consumableResp.ok ? consumableResp.json() : { data: [] }
            ]);

            document.getElementById('ppe-count').textContent = (ppeJson.data || []).length;
            document.getElementById('sep-count').textContent = (sepJson.data || []).length;
            document.getElementById('consumable-count').textContent = (consumableJson.data || []).length;
        } catch (err) {
            console.error('Failed to load asset stats', err);
        }
    }

    // --- Handle Form Submission ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);

        // Client-side password match validation
        const newPw = formData.get('new_password');
        const confirmPw = formData.get('confirm_password');
        if (newPw && newPw !== confirmPw) {
            alert('New passwords do not match.');
            return;
        }

        const originalHtml = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saving...`;

        fetch('api/my_profile_api.php', { method: 'POST', body: formData })
        .then(async response => {
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'Failed to update profile');
            return body;
        })
        .then(data => {
            if (data.success) {
                // Reload to reflect changes
                window.location.reload();
            } else {
                throw new Error(data.message || 'Update failed');
            }
        })
        .catch(err => alert(err.message))
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalHtml;
        });
    });

    // Initialize auxiliary data
    loadAssetStats();
});