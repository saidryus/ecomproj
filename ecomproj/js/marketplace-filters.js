document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-tag-chip]').forEach(chip => {
        const input = chip.querySelector('input[type="checkbox"]');
        if (!input) return;

        // Initial sync, in case PHP pre-selected
        if (input.checked) {
            chip.classList.add('tag-chip--selected');
        }

        chip.addEventListener('click', (e) => {
            // Let the checkbox toggle first
            if (e.target.tagName.toLowerCase() === 'input') {
                // use setTimeout to run after default toggle
                setTimeout(() => {
                    chip.classList.toggle('tag-chip--selected', input.checked);
                }, 0);
            } else {
                // If clicking label or span, label will toggle input automatically
                setTimeout(() => {
                    chip.classList.toggle('tag-chip--selected', input.checked);
                }, 0);
            }
        });
    });
});
