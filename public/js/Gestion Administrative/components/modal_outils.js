document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("toolModal");

    function openModal() {
        document.body.classList.add("modal-open");
        modal.classList.remove("hidden");
    }

    function closeModal() {
        document.body.classList.remove("modal-open");
        modal.classList.add("hidden");
    }

    document.getElementById("closeToolModal").addEventListener("click", closeModal);

    // =========================
    // EDIT MODE
    // =========================
    window.openToolEditModal = function(id) {

        const url = window.getToolUrl.replace('TOOL_ID', id);

        fetch(url)
            .then(res => res.json())
            .then(data => {

                document.getElementById("toolName").value = data.name || "";
                document.getElementById("toolExe").value = data.exe || "";
                document.getElementById("toolHash").value = data.hash || "";
                document.getElementById("toolCost").value = data.monthly_cost || 0;

                window.currentToolId = id;

                document.getElementById("toolModalTitle").innerText = "Modifier un outil";

                openModal();
            });
    };

    // =========================
    // ADD MODE
    // =========================
    window.openToolAddModal = function() {

        document.getElementById("toolModalTitle").innerText = "Ajouter un outil";

        ["toolName", "toolExe", "toolHash"].forEach(id => {
            document.getElementById(id).value = "";
        });
        document.getElementById("toolCost").value = 0;

        window.currentToolId = null;

        openModal();
    };

    // =========================
    // SUBMIT
    // =========================
    const form = document.getElementById("toolForm");

    form.addEventListener("submit", function(e) {
        e.preventDefault();

        const name = document.getElementById("toolName").value.trim();
        const exe  = document.getElementById("toolExe").value.trim();
        const hash = document.getElementById("toolHash").value.trim();
        const cost = parseFloat(document.getElementById("toolCost").value) || 0;

        // =========================
        // SUBMIT
        // =========================
        const data = { name, exe, hash, monthly_cost: cost };

        let url;

        if (window.currentToolId) {
            url = window.updateToolUrl.replace('TOOL_ID', window.currentToolId);
        } else {
            url = window.createToolUrl;
        }

        console.log("Tool submit:", data);

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const text = await res.text();

            let result;
            try {
                result = JSON.parse(text);
            } catch {
                throw new Error("Erreur serveur");
            }

            if (!res.ok) {
                if (result.errors) {
                    const messages = Object.values(result.errors).slice(0, 2).join("\n");
                    throw new Error(messages);
                }

                throw new Error(result.error || "Erreur inconnue");
            }

            return result;
        })
        .then(() => window.location.reload())
        .catch(err => {
            console.error(err);
            showError(err.message);
        });
    });

});


function showError(msg) {
    const errorDiv = document.getElementById("toolError");
    errorDiv.textContent = msg;
    errorDiv.classList.remove("hidden");
}