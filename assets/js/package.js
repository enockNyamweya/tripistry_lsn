function calcDuration() {

    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const durationInput = document.getElementById('duration');

    if (!startInput || !endInput || !durationInput) return;

    const startValue = startInput.value;
    const endValue = endInput.value;

    if (!startValue || !endValue) {
        durationInput.value = '';
        return;
    }

    const start = new Date(startValue);
    const end = new Date(endValue);

    if (end < start) {
        durationInput.value = '';
        return;
    }

    const diffMs = end - start;

    const days = Math.floor(
        diffMs / (1000 * 60 * 60 * 24)
    ) + 1;

    durationInput.value = days;
}

document.addEventListener('DOMContentLoaded', () => {

    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    if (startInput) {
        startInput.addEventListener('input', calcDuration);
        startInput.addEventListener('change', calcDuration);
    }

    if (endInput) {
        endInput.addEventListener('input', calcDuration);
        endInput.addEventListener('change', calcDuration);
    }

    calcDuration();
});