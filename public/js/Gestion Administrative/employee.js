window.rowActionsHandlers = window.rowActionsHandlers || {};

window.rowActionsHandlers.employee = {

    edit: function(id) {
        window.openEmployeeEditModal(id);
    },

    delete: async function(id) {
        if (!confirm("Confirmer la suppression ?")) return;

        const url = window.deleteEmployeeUrlTemplate.replace('EMP_ID', id);

        try {
            const res = await fetch(url, {
                method: "POST"
            });

            const text = await res.text();

            let result;
            try {
                result = JSON.parse(text);
            } catch {
                console.error("Non-JSON:", text);
                throw new Error("Erreur serveur");
            }

            if (!res.ok || !result.success) {
                throw new Error(result.error || "Suppression échouée");
            }

            alert(result.message || "Suppression réussie");

            // ✅ FORCE REDIRECT
            window.location.href = "/Gestion_Administrative";

        } catch (err) {
            console.error(err);
            alert(err.message);
        }
    },

    view: function(id) {
        console.log("Voir détails employee:", id);
    },

    tools: function(id) {
        window.openOutilAssociationModal(id);
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


