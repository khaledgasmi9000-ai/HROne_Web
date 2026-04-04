document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".conge-actions button").forEach(btn => {
        btn.addEventListener("click", () => {

            const action = btn.dataset.action;
            const id = btn.dataset.id;

            if (action === "accept") {
                console.log("Congé accepté:", id);
            }

            if (action === "reject") {
                console.log("Congé refusé:", id);
            }

        });
    });

});