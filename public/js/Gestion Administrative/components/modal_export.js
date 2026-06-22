document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("exportModal");

    const stepForm   = document.getElementById("exportStepForm");
    const stepLoad   = document.getElementById("exportStepLoading");
    const stepResult = document.getElementById("exportStepResult");

    const resultMsg  = document.getElementById("exportResultMessage");
    const modalTitle = document.getElementById("exportModalTitle");

    const btnClose   = document.getElementById("closeExportModal");
    const btnConfirm = document.getElementById("confirmExport");

    // 🔥 GLOBAL CONFIG (changes per usage)
    let currentConfig = {
        url: null,
        filename: "export",
        title: "Export"
    };

    // =========================
    // OPEN MODAL (GENERIC)
    // =========================
    window.openExportModal = function(config) {

        if (!config || !config.url) {
            console.error("Export config missing");
            return;
        }

        currentConfig = config;

        // Set dynamic title
        if (modalTitle) {
            modalTitle.textContent = config.title || "Export";
        }

        resetModal();

        modal.classList.remove("hidden");
        document.body.classList.add("modal-open");
    };

    function closeModal() {
        modal.classList.add("hidden");
        document.body.classList.remove("modal-open");
    }

    btnClose.addEventListener("click", closeModal);

    // =========================
    function resetModal() {
        stepForm.classList.remove("hidden");
        stepLoad.classList.add("hidden");
        stepResult.classList.add("hidden");

        btnConfirm.classList.remove("hidden");
        resultMsg.textContent = "";
    }

    // =========================
    // EXPORT
    // =========================
    btnConfirm.addEventListener("click", async () => {

        const format = document.getElementById("exportFormat").value;

        stepForm.classList.add("hidden");
        stepLoad.classList.remove("hidden");
        btnConfirm.classList.add("hidden");

        try {
            const url = currentConfig.url + "?format=" + format;

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error("Erreur export");
            }

            const blob = await response.blob();

            const link = document.createElement("a");
            link.href = window.URL.createObjectURL(blob);

            const ext = format === "excel" ? "xlsx" : format;
            link.download = (currentConfig.filename || "export") + "." + ext;

            document.body.appendChild(link);
            link.click();
            link.remove();

            stepLoad.classList.add("hidden");
            stepResult.classList.remove("hidden");

            resultMsg.textContent = "Export réussi ✔";

        } catch (err) {
            console.error(err);

            stepLoad.classList.add("hidden");
            stepResult.classList.remove("hidden");

            resultMsg.textContent = "Échec de l'export ❌";
        }
    });

});