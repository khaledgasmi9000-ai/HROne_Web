document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("toolSearch");
    const searchBtn   = document.getElementById("searchBtnTool");

    const usersCheckbox = document.getElementById("orderUsers");
    const timeCheckbox  = document.getElementById("orderTime");

    function updateQuery() {
        const url = new URL(window.location.href);

        // 🔍 Search
        if (searchInput && searchInput.value.trim() !== "") {
            url.searchParams.set("search", searchInput.value.trim());
        } else {
            url.searchParams.delete("search");
        }

        // 🎛 Ordering
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

        // Reset pagination
        url.searchParams.set("page", 1);

        window.location.href = url.toString();
    }

    // 🔘 Button click
    if (searchBtn) {
        searchBtn.addEventListener("click", updateQuery);
    }

    // ⌨️ Enter key
    if (searchInput) {
        searchInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                updateQuery();
            }
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