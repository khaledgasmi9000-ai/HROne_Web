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

        // =========================
        // VALIDATION
        // =========================
        function showError(msg) {
            const errorDiv = document.getElementById("toolError");
            errorDiv.textContent = msg;
            errorDiv.classList.remove("hidden");
        }

        function clearError() {
            const errorDiv = document.getElementById("toolError");
            if (errorDiv) {
                errorDiv.classList.add("hidden");
            }
        }
        
        clearError();
        // Name validation
        if (!name) {
            return showError("Le nom est requis.");
        }

        if (name.length < 3) {
            return showError("Le nom doit contenir au moins 3 caractères.");
        }

        // EXE validation
        if (!exe) {
            return showError("L'identifiant (exe) est requis.");
        }

        if (!exe.toLowerCase().endsWith(".exe")) {
            return showError("L'identifiant doit être un fichier .exe.");
        }

        // Hash validation (basic hex check)
        const hashRegex = /^[a-fA-F0-9]{16,}$/;

        if (!hash) {
            return showError("Le hash est requis.");
        }

        if (!hashRegex.test(hash)) {
            return showError("Le hash doit contenir uniquement des caractères hexadécimaux.");
        }

        // =========================
        // SUBMIT
        // =========================
        const data = { name, exe, hash };

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

            console.log("Response:", text);

            if (!res.ok) {
                throw new Error(text);
            }

            return text;
        })
        .then(() => window.location.reload())
        .catch(err => {
            console.error("Tool submit error:", err);
            alert("Erreur: " + err.message);
        });
    });

});


function showError(msg) {
    const errorDiv = document.getElementById("toolError");
    errorDiv.textContent = msg;
    errorDiv.classList.remove("hidden");
}