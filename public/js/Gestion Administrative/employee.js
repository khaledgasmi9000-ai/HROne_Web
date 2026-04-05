document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById("searchEmployee");
    const searchBtn   = document.getElementById("searchBtnEmployee");
    const filters     = document.querySelectorAll(".filter-select");

    function updateQuery() {
        const url = new URL(window.location.href);

        // 🔍 Search
        if (searchInput && searchInput.value.trim() !== "") {
            url.searchParams.set("search", searchInput.value.trim());
        } else {
            url.searchParams.delete("search");
        }

        // 🎛 Filters
        filters.forEach(select => {
            const key = select.name;
            const value = select.value;

            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });

        // 🔁 Reset pagination
        url.searchParams.set("page", 1);

        // 🚀 Reload page
        window.location.href = url.toString();
    }

    /* =========================
       EVENTS
    ========================= */

    // 🔘 Click on search button
    if (searchBtn) {
        searchBtn.addEventListener("click", updateQuery);
    }

    // ⌨️ Enter key also triggers search
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
                console.log("Voir détails:", id);
                // window.location.href = '/employee/' + id;
            },

            tools: function(id) {
                console.log("Gérer outils:", id);
                // custom logic for your added button
            }

        };