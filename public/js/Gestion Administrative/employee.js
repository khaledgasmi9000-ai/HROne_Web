document.addEventListener("DOMContentLoaded", () => {

    // Tabs behavior
    const tabs = document.querySelectorAll(".tab");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            // Future: load content dynamically
            console.log("Switched to tab:", tab.dataset.tab);
        });
    });

    // Search placeholder behavior
    const searchInput = document.querySelector("input[type='text']");

    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            console.log("Search:", e.target.value);
            // Future: hook to backend filtering
        });
    }

});


// Filters
document.querySelectorAll(".filter-select").forEach(select => {
    select.addEventListener("change", (e) => {
        console.log("Filter changed:", e.target.value);
        // Future: send to backend
    });
});

// Search
const searchInput = document.querySelector(".search-box input");

if (searchInput) {
    searchInput.addEventListener("input", (e) => {
        console.log("Search:", e.target.value);
        // Future: debounce + backend call
    });
}

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