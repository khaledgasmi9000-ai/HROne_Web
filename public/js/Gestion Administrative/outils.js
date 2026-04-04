document.addEventListener("DOMContentLoaded", () => {

    const usersCheckbox = document.getElementById("orderUsers");
    const timeCheckbox  = document.getElementById("orderTime");

    function updateQuery() {
        const url = new URL(window.location.href);

        if (usersCheckbox.checked) {
            url.searchParams.set("orderUsers", 1);
        } else {
            url.searchParams.delete("orderUsers");
        }

        if (timeCheckbox.checked) {
            url.searchParams.set("orderTime", 1);
        } else {
            url.searchParams.delete("orderTime");
        }

        // Reset to page 1 on ordering change
        url.searchParams.set("page", 1);

        window.location.href = url.toString();
    }

    if (usersCheckbox) usersCheckbox.addEventListener("change", updateQuery);
    if (timeCheckbox)  timeCheckbox.addEventListener("change", updateQuery);
});

document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("toolSearch");

    if (searchInput) {
        searchInput.addEventListener("input", () => {

            const value = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const nameCell = row.querySelector(".tool-name");

                if (!nameCell) return;

                const name = nameCell.textContent.toLowerCase();

                if (name.includes(value)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });

        });
    }

});


window.rowActionsHandlers = {

            edit: function(id) {
                console.log("Modifier:", id);
                // open edit modal later
            },

            delete: function(id) {
                if (confirm("Confirmer la suppression ?")) {
                    console.log("Supprimer:", id);
                }
            },

            view: function(id) {
                console.log("Voir détails Outils:", id);
                // window.location.href = '/employee/' + id;
            },

        };