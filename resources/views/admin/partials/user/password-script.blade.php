<script>
    document.addEventListener('DOMContentLoaded', () => {

        const pass = document.getElementById('password');
        const passHelp = document.getElementById('passwordHelp');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (!pass) return;

        const form = pass.closest('form');

        // Strong password validation message
        const message =
            'Password must be at least 8 characters and include uppercase, lowercase, number, and special character (@, #, $).';

        // Password format validation
        function isStrong(password) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$])[A-Za-z\d@#$]{8,}$/.test(password);
        }

        // Validate password while typing
        function validatePassword() {
            const value = pass.value;
            const valid = isStrong(value);

            if (passHelp) {
                if (!value || valid) {
                    passHelp.textContent = '';
                    passHelp.classList.add('d-none');
                } else {
                    passHelp.textContent = message;
                    passHelp.classList.remove('d-none');
                }
            }

            // Prevent invalid password submission
            pass.setCustomValidity(
                valid ? '' : message
            );

            return valid;
        }

        pass.addEventListener('input', validatePassword);

        // Prevent form submission when password format is invalid
        if (form) {
            form.addEventListener('submit', function (event) {
                if (!validatePassword()) {
                    event.preventDefault();
                    pass.reportValidity();
                    pass.focus();
                }
            });
        }

        // Show / Hide Password
        if (toggleBtn && toggleIcon) {
            toggleBtn.addEventListener('click', () => {
                const showPassword = pass.type === 'password';

                pass.type = showPassword ? 'text' : 'password';

                toggleIcon.classList.toggle('fa-eye', showPassword);
                toggleIcon.classList.toggle('fa-eye-slash', !showPassword);

                toggleBtn.setAttribute(
                    'aria-label',
                    showPassword ? 'Hide password' : 'Show password'
                );
            });
        }

    });
</script>

