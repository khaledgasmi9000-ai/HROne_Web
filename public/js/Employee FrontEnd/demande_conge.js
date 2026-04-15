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
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        
        if(start == "") return;
        if(end == "") return;
        const days = calculateDays(start, end);
        
        console.log(days);
        if(isNaN(days)){
            daysSpan.textContent = 0;
        }else{
            daysSpan.textContent = days;
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
        
        const data = {
            start: startInput.value,
            end: endInput.value,
            nbrJours: parseInt(daysSpan.textContent) || 0
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
            console.log("RAW RESPONSE:", text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error("Réponse serveur invalide");
            }
            
            if (!res.ok) {
                if (result.errors) {
                    const messages = Object.values(result.errors).slice(0, 2).join("\n");
                    throw new Error(messages);
                }
                throw new Error(result.error || "Erreur serveur");
            }

            return result;
        })
        .then(() => {
            alert("Demande envoyée !");
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            showError(err.message);
        });

    });

});