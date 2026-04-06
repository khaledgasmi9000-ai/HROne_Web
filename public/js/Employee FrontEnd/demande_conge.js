document.addEventListener("DOMContentLoaded", () => {

    const startInput = document.getElementById("startDate");
    const endInput   = document.getElementById("endDate");

    const daysSpan   = document.getElementById("daysCount");
    const soldeSpan  = document.getElementById("remainingSolde");
    const errorDiv   = document.getElementById("congeError");

    const form       = document.getElementById("congeForm");

    const initialSolde = parseInt(soldeSpan.textContent) || 0;

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.classList.remove("hidden");
    }

    function clearError() {
        errorDiv.classList.add("hidden");
    }

    function calculateDays(start, end) {
        const oneDay = 1000 * 60 * 60 * 24;
        return Math.floor((end - start) / oneDay) + 1;
    }

    function updateCalculation() {

        clearError();

        const start = new Date(startInput.value);
        const end   = new Date(endInput.value);

        if (!startInput.value || !endInput.value) {
            daysSpan.textContent = "0";
            soldeSpan.textContent = initialSolde;
            return;
        }

        if (end < start) {
            showError("La date de fin doit être après la date de début.");
            daysSpan.textContent = "0";
            return;
        }

        const days = calculateDays(start, end);

        daysSpan.textContent = days;

        const remaining = initialSolde - days;
        soldeSpan.textContent = remaining;

        if (remaining < 0) {
            showError("Solde insuffisant.");
        }
    }

    startInput.addEventListener("change", updateCalculation);
    endInput.addEventListener("change", updateCalculation);

    // =====================
    // SUBMIT
    // =====================
    form.addEventListener("submit", function(e) {
        e.preventDefault();

        clearError();

        if (!startInput.value || !endInput.value) {
            showError("Veuillez remplir les deux dates.");
            return;
        }

        const start = new Date(startInput.value);
        const end   = new Date(endInput.value);

        if (end < start) {
            showError("Dates invalides.");
            return;
        }

        const days = calculateDays(start, end);

        if ((initialSolde - days) < 0) {
            showError("Solde insuffisant.");
            return;
        }

        const data = {
            id_Employee: 14,
            start: startInput.value,
            end: endInput.value,
            nbrJours: days
        };

        console.log("Submitting demande:", data);

        fetch("/Gestion_Administrative/conges/create", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const text = await res.text();
            if (!res.ok) throw new Error(text);
            return text;
        })
        .then(() => {
            alert("Demande envoyée !");
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            showError("Erreur serveur.");
        });

    });

});