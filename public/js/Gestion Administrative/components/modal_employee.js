document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("employeeModal");

    function openModal() {
        document.body.classList.add("modal-open");
        modal.classList.remove("hidden");
    }

    function closeModal() {
        document.body.classList.remove("modal-open");
        modal.classList.add("hidden");
    }

    document.getElementById("closeEmployeeModal").addEventListener("click", closeModal);

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

    form.addEventListener("submit", function(e) {
        e.preventDefault();

        const data = {
            name: document.getElementById("empName").value,
            email: document.getElementById("empEmail").value,
            phone: document.getElementById("empPhone").value,
            cin: document.getElementById("empCIN").value,
            birth: document.getElementById("empBirth").value,
            gender: document.getElementById("empGender").value,
            solde: document.getElementById("empSolde").value,
            salaire: document.getElementById("empSalaire").value,
            heures: document.getElementById("empHeures").value,
        };

        let url;

        // 🔥 MODE SWITCH
        if (window.currentEmployeeId) {
            url = window.updateEmployeeUrl.replace('EMP_ID', window.currentEmployeeId);
        } else {
            url = window.createEmployeeUrl;
        }

        console.log("Submitting data:", data);
        
        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(async res => {

            const text = await res.text(); // 🔥 get raw response

            console.log("Response status:", res.status);
            console.log("Raw response:", text);

            if (!res.ok) {
                throw new Error(`HTTP ${res.status} → ${text}`);
            }

            try {
                return JSON.parse(text); // try parse JSON
            } catch {
                return text; // fallback if not JSON
            }

        })
        .then(result => {
            console.log("Success:", result);
            window.location.reload();
        })
        .catch(err => {
            console.error("Submit error FULL:", err);

            alert("Erreur serveur:\n" + err.message); // optional UI feedback
        });
    });
});

function setReadOnly(isReadOnly) {

    const fields = [
        "empPhone",
        "empCIN",
        "empBirth",
        "empGender"
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