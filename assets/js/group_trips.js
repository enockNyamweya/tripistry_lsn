document.addEventListener('click', async (e) => {

    // CANCEL
    const cancelBtn = e.target.closest('.cancel-trip');

    if (cancelBtn) {

        const id = cancelBtn.dataset.id;

        if (!confirm('Cancel this group trip?')) return;

        const res = await fetch('cancel_group_trip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}`
        });

        if (!res.ok) {
            alert('Failed to cancel trip');
            return;
        }

        const row = cancelBtn.closest('tr');

        row.classList.add('trip-cancelled');

        // TURN BUTTON INTO UNDO
        cancelBtn.textContent = 'Undo Cancel';
        cancelBtn.classList.remove('btn-danger', 'cancel-trip');
        cancelBtn.classList.add('btn-secondary', 'undo-cancel');

        return;
    }

    // UNDO
    const undoBtn = e.target.closest('.undo-cancel');

    if (undoBtn) {

        const id = undoBtn.dataset.id;

        const res = await fetch('undo_group_trip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}`
        });

        if (!res.ok) {
            alert('Failed to restore trip');
            return;
        }

        const row = undoBtn.closest('tr');

        row.classList.remove('trip-cancelled');

        // TURN BUTTON BACK INTO CANCEL
        undoBtn.textContent = 'Cancel Trip';
        undoBtn.classList.remove('btn-secondary', 'undo-cancel');
        undoBtn.classList.add('btn-danger', 'cancel-trip');
    }

});