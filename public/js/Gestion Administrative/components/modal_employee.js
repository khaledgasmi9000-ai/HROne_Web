document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("employeeModal");
    const BtnCloseModal = document.getElementById("closeEmployeeModal");

    function openModal() {
        clearError();
        document.body.classList.add("modal-open");
        modal.classList.remove("hidden");
    }

    function closeModal() {
        document.body.classList.remove("modal-open");
        modal.classList.add("hidden");
    }

    if(BtnCloseModal){
        BtnCloseModal.addEventListener("click", closeModal);
    }

    // Expose globally (so row_actions can call it)
    window.openEmployeeEditModal = function(id) {

        const url = window.getEmployeeUrl.replace('EMP_ID', id);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                //console.log("Employee data fetched:", data);
                document.getElementById("empName").value = data.Nom_Utilisateur || "";
                document.getElementById("empEmail").value = data.Email || "";
                document.getElementById("empPhone").value = data.Num_Tel || "";
                document.getElementById("empCIN").value = data.CIN || "";
                document.getElementById("empBirth").value = data.Date_Naissance || "";
                document.getElementById("empGender").value = data.Gender || "";
                document.getElementById("empSolde").value = data.Solde_Conge || "";
                document.getElementById("empSalaire").value = data.SALAIRE || "";
                document.getElementById("empHeures").value = data.Nbr_Heure_De_Travail || "";

                window.currentEmployeeId = id;

                document.getElementById("modalTitle").innerText = "Modifier un employé";

                setReadOnly(true);

                openModal();
            });
    };

    window.openEmployeeAddModal = function() {

        document.getElementById("modalTitle").innerText = "Ajouter un employé";

        document.getElementById("empName").value = "";
        document.getElementById("empEmail").value = "";
        document.getElementById("empPhone").value = "";
        document.getElementById("empCIN").value = "";
        document.getElementById("empBirth").value = "";
        document.getElementById("empGender").value = "";
        document.getElementById("empSolde").value = "";
        document.getElementById("empSalaire").value = "";
        document.getElementById("empHeures").value = "";

        window.currentEmployeeId = null;

        setReadOnly(false);
        
        openModal();
    };


    const form = document.getElementById("employeeForm");

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const data = {
            name: document.getElementById("empName").value.trim(),
            email: document.getElementById("empEmail").value.trim(),
            phone: document.getElementById("empPhone").value.trim(),
            cin: document.getElementById("empCIN").value.trim(),
            birth: document.getElementById("empBirth").value,
            gender: document.getElementById("empGender").value,
            solde: document.getElementById("empSolde").value,
            salaire: document.getElementById("empSalaire").value,
            heures: document.getElementById("empHeures").value,
        };

        const isEditMode = !!window.currentEmployeeId;

        // =============================
        // Controle Saisie (Frontend)
        // =============================

        clearError();

        // Name
        if (!data.name) return showError("Le nom est requis.");

        // Email
        if (!data.email || !validateEmail(data.email)) {
            return showError("Email invalide.");
        }

        // CIN + Phone (only add mode)
        if (!isEditMode && !data.cin) return showError("CIN requis.");
        if (!isEditMode && !data.phone) return showError("Téléphone requis.");

        // ===== NUMERIC =====
        if (data.salaire === "" || isNaN(data.salaire) || Number(data.salaire) < 0) {
            return showError("Salaire invalide (>= 0).");
        }

        if (data.solde === "" || isNaN(data.solde) || Number(data.solde) < 0) {
            return showError("Solde congé invalide (>= 0).");
        }

        if (data.heures === "" || isNaN(data.heures) || Number(data.heures) < 0) {
            return showError("Nombre d'heures invalide (>= 0).");
        }

        // ===== DATE =====
        if (!data.birth) return showError("Date de naissance requise.");

        const birthDate = new Date(data.birth);
        const today = new Date();
        today.setHours(0,0,0,0);

        if (isNaN(birthDate.getTime())) {
            return showError("Date invalide.");
        }

        if (birthDate >= today) {
            return showError("La date doit être dans le passé.");
        }

        // =============================
        // 2. BACKEND VALIDATION (EMAIL + CIN)
        // =============================
        try {
            const checkResponse = await fetch(window.checkEmployeeUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: data.email,
                    cin: data.cin,
                    id: window.currentEmployeeId // 👈 important
                })
            });

            const checkResult = await checkResponse.json();

            // ❗ Only block if ADD mode OR field is editable
            if (!isEditMode || !document.getElementById("empEmail").readOnly) {
                if (checkResult.emailExists) {
                    return showError("Email déjà utilisé");
                }
            }

            if (!isEditMode || !document.getElementById("empCIN").readOnly) {
                if (checkResult.cinExists) {
                    return showError("CIN déjà utilisé");
                }
            }

        } catch (err) {
            console.error("Validation error:", err);
            return showError("Erreur validation serveur");
        }

        // =============================
        // 3. Submit
        // =============================
        let url = isEditMode
            ? window.updateEmployeeUrl.replace('EMP_ID', window.currentEmployeeId)
            : window.createEmployeeUrl;

        try {
            const res = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            console.log("Submit response:", result);
            if (!res.ok) {
                throw new Error(result.message || "Erreur serveur");
            }

            window.location.reload();

        } catch (err) {
            console.error("Submit error:", err);
            showError(err.message);
        }
    });

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(msg) {
        const errorDiv = document.getElementById("employeeError");
        errorDiv.textContent = msg;
        errorDiv.classList.remove("hidden");

        errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function clearError() {
        const errorDiv = document.getElementById("employeeError");
        if (errorDiv) {
            errorDiv.textContent = "";   // ✅ clear content
            errorDiv.classList.add("hidden");
        }
    }
});

function setReadOnly(isReadOnly) {

    const fields = [
        "empPhone",
        "empCIN",
        "empBirth",
        "empGender",
        "empEmail"
    ];

    fields.forEach(id => {
        const el = document.getElementById(id);

        if (!el) return;

        if (el.tagName === "SELECT") {
            el.disabled = isReadOnly;   // 🔥 for select
        } else {
            el.readOnly = isReadOnly;   // 🔥 for inputs
        }
    });
}

