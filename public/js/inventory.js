(() => {
    const form = document.querySelector('#entry-form');
    const rows = document.querySelector('#entry-rows');
    const heading = document.querySelector('#form-title');
    const submit = document.querySelector('#save-entry');
    const cancel = document.querySelector('#cancel-edit');
    const status = document.querySelector('#form-status');
    const error = document.querySelector('#form-error');
    const total = document.querySelector('#grand-total');
    const count = document.querySelector('#entries-count');
    const fields = ['name', 'quantity', 'price'].map((key) => form.elements.namedItem(key));
    let editing = null;

    function clearFeedback() {
        status.textContent = '';
        error.textContent = '';

        for (const field of fields) {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
            document.getElementById(field.name + '-feedback').textContent = '';
        }
    }

    function resetForm() {
        editing = null;
        form.reset();
        fields.forEach((field) => { field.value = ''; });
        heading.textContent = 'Add an entry';
        submit.textContent = 'Add product';
        cancel.hidden = true;
    }

    rows.addEventListener('click', (event) => {
        const button = event.target.closest('[data-edit-url]');
        if (!button || submit.disabled) return;

        clearFeedback();
        editing = button.dataset.editUrl;
        fields.forEach((field) => { field.value = button.dataset[field.name]; });
        heading.textContent = 'Edit entry';
        submit.textContent = 'Save changes';
        cancel.hidden = false;
        fields[0].focus();
    });

    cancel.addEventListener('click', () => {
        const previousButton = Array.from(rows.querySelectorAll('[data-edit-url]'))
            .find((button) => button.dataset.editUrl === editing);
        clearFeedback();
        resetForm();
        (previousButton ?? fields[0]).focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submit.disabled) return;

        const target = editing ?? form.action;
        const method = editing ? 'PATCH' : 'POST';
        const data = Object.fromEntries(new FormData(form));
        const idleLabel = submit.textContent;
        clearFeedback();
        submit.disabled = true;
        cancel.disabled = true;
        fields.forEach((field) => { field.readOnly = true; });
        submit.textContent = 'Saving…';

        let nextFocus = submit;
        try {
            const response = await fetch(target, {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data),
            });
            const result = await response.json();

            if (response.status === 422) {
                const invalid = fields.filter((field) => result.errors?.[field.name]);

                for (const field of invalid) {
                    field.classList.add('is-invalid');
                    field.setAttribute('aria-invalid', 'true');
                    document.getElementById(field.name + '-feedback').textContent = result.errors[field.name][0];
                }
                error.textContent = 'Check the highlighted fields and try again.';
                nextFocus = invalid[0] ?? submit;
                return;
            }

            if (!response.ok || typeof result.rows !== 'string') {
                error.textContent = response.status === 419
                    ? 'This page has expired. Reload it before trying again.'
                    : 'The entry could not be saved. Reload to check the inventory before retrying.';
                return;
            }

            // The same Blade partial renders initial and updated rows. Its values are escaped.
            rows.innerHTML = result.rows;
            total.textContent = result.grand_total;
            count.textContent = result.count + (result.count === 1 ? ' entry' : ' entries');
            status.textContent = result.message;
            resetForm();
            nextFocus = fields[0];
        } catch {
            error.textContent = 'The server response could not be read. Reload to check the inventory before retrying.';
        } finally {
            submit.disabled = false;
            cancel.disabled = false;
            fields.forEach((field) => { field.readOnly = false; });
            if (submit.textContent === 'Saving…') submit.textContent = idleLabel;
            nextFocus.focus();
        }
    });
})();
