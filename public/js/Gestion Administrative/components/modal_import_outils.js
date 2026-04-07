document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("importModal");

    const stepForm   = document.getElementById("importStepForm");
    const stepLoad   = document.getElementById("importStepLoading");
    const stepResult = document.getElementById("importStepResult");

    const resultMsg  = document.getElementById("importResultMessage");
    const errorDiv   = document.getElementById("importError");

    const btnClose   = document.getElementById("closeImportModal");
    const btnConfirm = document.getElementById("confirmImport");

    let importCompleted = false;
    // =========================
    window.openImportModal = function () {
        resetModal();
        modal.classList.remove("hidden");
        document.body.classList.add("modal-open");
    };

    function closeModal() {

        modal.classList.add("hidden");
        document.body.classList.remove("modal-open");

        if (importCompleted) {
            window.location.reload();
        }
    }

    btnClose.addEventListener("click", closeModal);

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.classList.remove("hidden");
    }

    function clearError() {
        errorDiv.textContent = "";
        errorDiv.classList.add("hidden");
    }

    function resetModal() {
        importCompleted = false; // ✅ reset state

        clearError();

        stepForm.classList.remove("hidden");
        stepLoad.classList.add("hidden");
        stepResult.classList.add("hidden");

        btnConfirm.classList.remove("hidden");

        document.getElementById("importFile").value = "";
        document.getElementById("fileName").textContent = "Aucun fichier sélectionné";

        resultMsg.textContent = "";
    }

    const fileInput = document.getElementById("importFile");
    const fileBtn   = document.getElementById("filePickerBtn");
    const fileName  = document.getElementById("fileName");

    // Open file picker
    fileBtn.addEventListener("click", () => fileInput.click());

    // Show selected file name
    fileInput.addEventListener("change", () => {
        fileName.textContent = fileInput.files[0]
            ? fileInput.files[0].name
            : "Aucun fichier sélectionné";
    });
    // =========================
    btnConfirm.addEventListener("click", async () => {

        clearError();

        const fileInput = document.getElementById("importFile");
        const file = fileInput.files[0];

        if (!file) {
            return showError("Veuillez sélectionner un fichier CSV.");
        }

        if (!file.name.endsWith(".csv")) {
            return showError("Le fichier doit être un CSV.");
        }

        // UI state
        stepForm.classList.add("hidden");
        stepLoad.classList.remove("hidden");
        btnConfirm.classList.add("hidden");

        try {
            const formData = new FormData();
            formData.append("file", file);

            const response = await fetch(window.importToolsUrl, {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || "Erreur import");
            }

            stepLoad.classList.add("hidden");
            stepResult.classList.remove("hidden");

            resultMsg.textContent =
                `${result.successCount}/${result.total} importés avec succès`;

            importCompleted = true;

        } catch (err) {
            console.error(err);

            stepLoad.classList.add("hidden");
            stepForm.classList.remove("hidden");
            btnConfirm.classList.remove("hidden");

            showError(err.message);
        }
    });

});