function calcDuration() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const durationInput = document.getElementById('duration');

    if (!startInput || !endInput || !durationInput) return;

    const startValue = startInput.value;
    const endValue = endInput.value;

    if (!startValue || !endValue) {
        return;
    }

    const start = new Date(startValue + 'T00:00:00');
    const end = new Date(endValue + 'T00:00:00');

    if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
    if (end < start) return;

    const diffMs = end - start;
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
    durationInput.value = days;
}

function calcEndDate() {
    const startInput = document.getElementById('start_date');
    const durationInput = document.getElementById('duration');
    const endInput = document.getElementById('end_date');

    if (!startInput || !durationInput || !endInput) return;

    const startValue = startInput.value;
    const days = parseInt(durationInput.value);

    if (!startValue || isNaN(days) || days < 1) return;

    const start = new Date(startValue + 'T00:00:00');
    if (isNaN(start.getTime())) return;

    start.setDate(start.getDate() + days - 1);
    const yyyy = start.getFullYear();
    const mm = String(start.getMonth() + 1).padStart(2, '0');
    const dd = String(start.getDate()).padStart(2, '0');
    endInput.value = yyyy + '-' + mm + '-' + dd;
}

document.addEventListener('DOMContentLoaded', () => {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const durationInput = document.getElementById('duration');

    if (startInput) {
        startInput.addEventListener('input', () => {
            if (durationInput && durationInput.value && parseInt(durationInput.value) >= 1) {
                calcEndDate();
            }
        });
        startInput.addEventListener('change', () => {
            if (durationInput && durationInput.value && parseInt(durationInput.value) >= 1) {
                calcEndDate();
            }
        });
    }

    if (endInput) {
        endInput.addEventListener('input', calcDuration);
        endInput.addEventListener('change', calcDuration);
    }

    if (durationInput) {
        durationInput.addEventListener('input', () => {
            if (startInput && startInput.value) {
                calcEndDate();
            }
        });
        durationInput.addEventListener('change', () => {
            if (startInput && startInput.value) {
                calcEndDate();
            }
        });
    }

    // Initial auto-calc: if start and duration exist, fill end date
    if (startInput && durationInput && endInput) {
        if (startInput.value && durationInput.value && !endInput.value) {
            calcEndDate();
        } else if (startInput.value && endInput.value) {
            calcDuration();
        }
    }
});
