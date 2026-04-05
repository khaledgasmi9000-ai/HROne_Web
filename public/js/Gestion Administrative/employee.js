window.rowActionsHandlers = window.rowActionsHandlers || {};

window.rowActionsHandlers.employee = {

    edit: function(id) {
        window.openEmployeeEditModal(id);
    },

    delete: function(id) {
        console.log("Supprimer employee:", id);
        if (confirm("Confirmer la suppression ?")) {
            const url = window.deleteEmployeeUrlTemplate.replace('EMP_ID', id);
            window.location.href = url;
        }
    },

    view: function(id) {
        console.log("Voir détails employee:", id);
    },

    tools: function(id) {
        console.log("Gérer outils:", id);
    }

};

document.addEventListener("DOMContentLoaded", () => {

    const addButton = document.getElementById("btnAddEmployee");

    if (addButton) {
        addButton.addEventListener("click", window.openEmployeeAddModal);
    }
    
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


