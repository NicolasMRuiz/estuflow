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

// Presentation only: bulk changes stay in the visible draft until a normal POST.
function initializeAcademicForms() {
    const firstError = document.querySelector('[data-academic-form] [aria-invalid="true"], [data-academic-error]');
    if (firstError) {
        for (let ancestor = firstError.parentElement; ancestor; ancestor = ancestor.parentElement) {
            if (ancestor.tagName === 'DETAILS') ancestor.open = true;
        }
        requestAnimationFrame(() => firstError.focus());
    }
    document.querySelectorAll('[data-academic-form]').forEach(form => {
        if (form.dataset.academicInitialized) return;
        form.dataset.academicInitialized = 'true';
        form.addEventListener('submit', event => {
            if (form.dataset.submitting) { event.preventDefault(); return; }
            const chosen = event.submitter;
            if (chosen?.name) {
                const value = document.createElement('input');
                value.type = 'hidden'; value.name = chosen.name; value.value = chosen.value;
                form.append(value);
            }
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = true; });
        });
    });
    document.querySelectorAll('[data-year]').forEach(section => {
        const controls = section.querySelector('[data-bulk-controls]');
        if (!controls || controls.dataset.initialized) return;
        controls.dataset.initialized = 'true'; controls.hidden = false;
        const undo = controls.querySelector('[data-bulk-undo]');
        const notice = controls.querySelector('[data-bulk-notice]');
        let previous;
        function setValue(select, value) {
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        controls.querySelector('[data-bulk-apply]').addEventListener('click', () => {
            const fields = [...section.querySelectorAll('[data-status-select], [data-method-select]')];
            previous = fields.map(select => [select, select.value]);
            const state = controls.querySelector('[data-bulk-state]').value;
            section.querySelectorAll('[data-status-select]').forEach(select => setValue(select, state));
            section.querySelectorAll('[data-method-select]').forEach(select => setValue(select, 'unspecified'));
            undo.hidden = false;
            const count = section.querySelectorAll('[data-status-select]').length;
            notice.textContent = `Cambiaste ${count} materias del año ${section.dataset.year}. Los cambios se aplicarán al guardar.`;
        });
        undo.addEventListener('click', () => {
            previous?.forEach(([select, value]) => setValue(select, value));
            previous = null; undo.hidden = true;
            notice.textContent = `Restauraste los valores anteriores del año ${section.dataset.year}.`;
        });
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAcademicForms);
else initializeAcademicForms();
document.addEventListener('livewire:navigated', initializeAcademicForms);
window.addEventListener('career-focus', event => {
    requestAnimationFrame(() => {
        const target = document.querySelector(`#materia-${event.detail.courseId} [data-edit-course]`) || document.querySelector('#resultados');
        target?.focus({ preventScroll: true });
    });
});
