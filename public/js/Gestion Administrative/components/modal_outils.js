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

        const data = {
            name: document.getElementById("toolName").value,
            exe: document.getElementById("toolExe").value,
            hash: document.getElementById("toolHash").value,
        };

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