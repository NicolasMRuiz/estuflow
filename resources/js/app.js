// Alpine is loaded once by Livewire's @livewireScripts directive.
// These enhancements preserve normal server-validated POSTs without JavaScript.
function initializeAuthForms() {
    document.querySelectorAll('form[data-auth-form]').forEach(form => {
        if (form.dataset.initialized) return;
        form.dataset.initialized = 'true';

        const fields = [...form.querySelectorAll('input[data-label]')];
        const summary = form.querySelector('[data-form-errors]') || document.querySelector('[data-form-errors]');
        let attempted = false;

        function errorFor(field) {
            return [...form.querySelectorAll('[data-error-for]')].find(error => error.dataset.errorFor === field.name);
        }

        function setError(field, message) {
            const error = errorFor(field);
            if (!error) return;
            error.textContent = message;
            error.hidden = !message;
            field.setAttribute('aria-invalid', message ? 'true' : 'false');
            const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== error.id);
            if (message) descriptions.push(error.id);
            if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
            else field.removeAttribute('aria-describedby');
        }

        function validationMessage(field) {
            const value = field.value;
            if (field.required && !(field.type === 'password' ? value : value.trim())) {
                if (field.name === 'email') return 'Ingresá tu email.';
                if (field.name === 'password') return 'Ingresá tu contraseña.';
                if (field.name === 'password_confirmation') return 'Confirmá tu contraseña.';
                if (field.name === 'name') return 'Ingresá tu nombre.';
                return `Completá ${field.dataset.label.toLocaleLowerCase('es')}.`;
            }
            if (field.type === 'email' && field.validity.typeMismatch) return 'Ingresá un email válido.';
            if (field.maxLength > 0 && value.length > field.maxLength) return `Usá como máximo ${field.maxLength} caracteres.`;
            if (field.minLength > 0 && value.length < field.minLength) return `Usá al menos ${field.minLength} caracteres.`;
            if (field.name === 'password_confirmation') {
                const password = fields.find(input => input.name === 'password');
                if (password && value !== password.value) return 'Las contraseñas no coinciden.';
            }
            return '';
        }

        function updateSummary() {
            if (!summary) return;
            const section = summary.querySelector('[data-field-summary]');
            if (!section) return;
            const invalid = fields.filter(field => field.getAttribute('aria-invalid') === 'true');
            section.replaceChildren();
            section.hidden = invalid.length < 2;
            if (invalid.length > 1) {
                const introduction = document.createElement('p');
                introduction.textContent = 'Revisá los campos indicados:';
                const list = document.createElement('ul');
                invalid.forEach(field => {
                    const item = document.createElement('li');
                    const link = document.createElement('a');
                    link.href = `#${field.id}`;
                    link.textContent = `${field.dataset.label}: ${errorFor(field).textContent}`;
                    link.addEventListener('click', () => field.focus());
                    item.append(link);
                    list.append(item);
                });
                section.append(introduction, list);
            }
            summary.hidden = section.hidden && !summary.querySelector('[data-general-error]');
        }

        fields.forEach(field => {
            let edited = false;
            let hadError = field.getAttribute('aria-invalid') === 'true';
            field.addEventListener('input', () => {
                edited = true;
                hadError = hadError || field.getAttribute('aria-invalid') === 'true';
                setError(field, '');
                summary?.querySelectorAll('[data-general-error]').forEach(error => error.remove());
                if (field.name === 'password') {
                    const confirmation = fields.find(input => input.name === 'password_confirmation');
                    if (confirmation) setError(confirmation, '');
                }
                updateSummary();
            });
            field.addEventListener('blur', () => {
                if (edited && (attempted || hadError)) {
                    setError(field, validationMessage(field));
                    updateSummary();
                }
            });
        });

        summary?.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', () => document.getElementById(link.hash.slice(1))?.focus());
        });

        form.addEventListener('submit', event => {
            attempted = true;
            summary?.querySelectorAll('[data-general-error]').forEach(error => error.remove());
            fields.forEach(field => setError(field, validationMessage(field)));
            updateSummary();
            const firstInvalid = fields.find(field => field.getAttribute('aria-invalid') === 'true');
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                return;
            }
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach(button => {
                button.disabled = true;
                const text = button.querySelector('span');
                if (text && button.dataset.busyLabel) text.textContent = button.dataset.busyLabel;
            });
        });

        const firstInvalid = fields.find(field => field.getAttribute('aria-invalid') === 'true');
        if (firstInvalid) firstInvalid.focus();
        else if (summary && !summary.hidden && summary.querySelector('[data-general-error]')) summary.focus();

        let remaining = Number.parseInt(form.dataset.cooldown || '0', 10);
        if (remaining > 0) {
            const message = form.querySelector('[data-cooldown-message]');
            const seconds = form.querySelector('[data-cooldown-seconds]');
            const button = form.querySelector('button[type="submit"]');
            const timer = window.setInterval(() => {
                remaining = Math.max(0, remaining - 1);
                if (seconds) seconds.textContent = String(remaining);
                if (!remaining) {
                    window.clearInterval(timer);
                    if (message) message.hidden = true;
                    if (button) button.disabled = false;
                }
            }, 1000);
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAuthForms);
else initializeAuthForms();
document.addEventListener('livewire:navigated', initializeAuthForms);
