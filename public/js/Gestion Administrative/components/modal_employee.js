document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("employeeModal");
    const BtnCloseModal = document.getElementById("closeEmployeeModal");
    const form = document.getElementById("employeeForm");

    // =============================
    // MODAL CONTROL
    // =============================
    function openModal() {
        clearError();
        document.body.classList.add("modal-open");
        modal.classList.remove("hidden");
    }

    function closeModal() {
        document.body.classList.remove("modal-open");
        modal.classList.add("hidden");
    }

    if (BtnCloseModal) {
        BtnCloseModal.addEventListener("click", closeModal);
    }

    // =============================
    // LOAD EMPLOYEE (EDIT)
    // =============================
    window.openEmployeeEditModal = async function(id) {
        try {
            const url = window.getEmployeeUrl.replace('EMP_ID', id);
            const res = await fetch(url);

            const data = await res.json();

            document.getElementById("empName").value = data.name || "";
            document.getElementById("empEmail").value = data.email || "";
            document.getElementById("empPhone").value = data.phone || "";
            document.getElementById("empCIN").value = data.cin || "";
            document.getElementById("empBirth").value = data.birth || "";
            document.getElementById("empGender").value = data.gender || "";
            document.getElementById("empSolde").value = data.solde || "";
            document.getElementById("empSalaire").value = data.salaire || "";
            document.getElementById("empHeures").value = data.heures || "";
            document.getElementById("empDepartement").value = data.departement || "";

            window.currentEmployeeId = id;

            document.getElementById("modalTitle").innerText = "Modifier un employé";

            setReadOnly(true);

            openModal();

        } catch (err) {
            console.error("Load error:", err);
            showError("Erreur chargement employé");
        }
    };

    // =============================
    // ADD MODE
    // =============================
    window.openEmployeeAddModal = function() {

        document.getElementById("modalTitle").innerText = "Ajouter un employé";

        [
            "empName","empEmail","empPhone","empCIN",
            "empBirth","empGender","empSolde",
            "empSalaire","empHeures","empDepartement"
        ].forEach(id => {
            document.getElementById(id).value = "";
        });

        window.currentEmployeeId = null;

        setReadOnly(false);
        openModal();
    };

    // =============================
    // SUBMIT
    // =============================
    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const isEditMode = !!window.currentEmployeeId;

        const data = {
            name: document.getElementById("empName").value.trim(),
            email: document.getElementById("empEmail").value.trim(),
            phone: document.getElementById("empPhone").value.trim(),
            cin: document.getElementById("empCIN").value.trim(),
            birth: document.getElementById("empBirth").value,
            gender: document.getElementById("empGender").value,
            solde: (document.getElementById("empSolde").value),
            salaire:(document.getElementById("empSalaire").value),
            heures: (document.getElementById("empHeures").value),
            departement: document.getElementById("empDepartement").value,
        };

        clearError();

        // =============================
        // SUBMIT REQUEST
        // =============================
        const url = isEditMode
            ? window.updateEmployeeUrl.replace('EMP_ID', window.currentEmployeeId)
            : window.createEmployeeUrl;

        try {
            const res = await fetch(url, {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(data)
            });

            let result;
            const text = await res.text();

            try {
                result = JSON.parse(text);
            } catch {
                console.error("Non-JSON response:", text);
                throw new Error("Erreur serveur (non JSON)");
            }

            if (!res.ok) {
                if (result.errors) {
                    const allErrors = Object.values(result.errors);

                    // show only first 2 errors
                    const limitedErrors = allErrors.slice(0, 1);

                    const messages = limitedErrors.join("\n");

                    throw new Error(messages);
                }
                throw new Error(result.error || "Erreur serveur");
            }

            window.location.reload();

        } catch (err) {
            console.error("Submit error:", err);
            showError(err.message);
        }
    });

    // =============================
    // HELPERS
    // =============================
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(msg) {
        const el = document.getElementById("employeeError");
        el.textContent = msg;
        el.classList.remove("hidden");
        el.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function clearError() {
        const el = document.getElementById("employeeError");
        el.textContent = "";
        el.classList.add("hidden");
    }
});

// =============================
// READONLY CONTROL
// =============================
function setReadOnly(isReadOnly) {
    ["empPhone","empCIN","empBirth","empGender","empEmail"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        if (el.tagName === "SELECT") {
            el.disabled = isReadOnly;
        } else {
            el.readOnly = isReadOnly;
        }
    });
}