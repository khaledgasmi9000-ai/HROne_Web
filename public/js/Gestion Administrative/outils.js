document.addEventListener("DOMContentLoaded", () => {

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

    const searchInput = document.getElementById("toolSearch");
    const searchBtn   = document.getElementById("searchBtnTool");

    const usersCheckbox = document.getElementById("orderUsers");
    const timeCheckbox  = document.getElementById("orderTime");

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

    const addBtn = document.getElementById("btnAddTool");

    if (addBtn) {
        addBtn.addEventListener("click", () => {
            if (window.openToolAddModal) {
                window.openToolAddModal();
            } else {
                console.warn("openToolAddModal not defined");
            }
        });
    }
});

window.rowActionsHandlers = window.rowActionsHandlers || {};

window.rowActionsHandlers.outils = {

    edit: function(id) {
        console.log("Modifier outil:", id);

        if (window.openToolEditModal) {
            window.openToolEditModal(id);
        } else {
            console.warn("openToolEditModal not defined");
        }
    },

    delete: function(id) {
        console.log("Supprimer outil:", id);
        if (confirm("Supprimer cet outil ?")) {
            const url = window.deleteToolUrlTemplate.replace('TOOL_ID', id);
            window.location.href = url;
        }
    },

    view: function(id) {
        console.log("Voir détails outil:", id);
    }

};