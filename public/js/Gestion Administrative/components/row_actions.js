document.addEventListener("DOMContentLoaded", () => {

    const defaultHandlers = {
        edit: (id) => console.log("Modifier:", id),
        delete: (id) => console.log("Supprimer:", id),
        view: (id) => console.log("Voir détails:", id),
    };

    document.querySelectorAll(".row-actions").forEach(container => {

        const button = container.querySelector(".action-btn");

        // Toggle dropdown
        button.addEventListener("click", (e) => {
            e.stopPropagation();

            document.querySelectorAll(".row-actions").forEach(el => {
                if (el !== container) el.classList.remove("open");
            });

            container.classList.toggle("open");
        });

        // Handle actions
        container.querySelectorAll(".dropdown-item").forEach(item => {
            item.addEventListener("click", () => {

                const action = item.dataset.action;
                const id = container.dataset.id;
                const url = item.dataset.url;

                // Close dropdown
                container.classList.remove("open");

                if (window.rowActionsHandlers && typeof window.rowActionsHandlers[action] === "function") {
                    window.rowActionsHandlers[action](id, item);
                }
                else if (defaultHandlers[action]) {
                    defaultHandlers[action](id);
                }
                else if (url) {
                    window.location.href = url;
                }
                else {
                    console.warn("No handler for action:", action);
                }

            });
        });

    });

    // Close on outside click
    document.addEventListener("click", () => {
        document.querySelectorAll(".row-actions").forEach(el => {
            el.classList.remove("open");
        });
    });

});