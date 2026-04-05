document.querySelectorAll(".conge-actions button").forEach(btn => {
    btn.addEventListener("click", () => {

        const action = btn.dataset.action;
        const id = btn.dataset.id;

        if (action === "accept") {
            console.log("Accepting conge with ID:", id);
            const url = window.congeAcceptUrl.replace('ID_CONGE', id);
            window.location.href = url;
        }

        if (action === "reject") {
            console.log("Rejecting conge with ID:", id);
            const url = window.congeRejectUrl.replace('ID_CONGE', id);
            window.location.href = url;
        }

    });
});