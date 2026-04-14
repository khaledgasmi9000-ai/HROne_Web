window.currentEmployeeTools = {
    employeeId: null,
    tools: []
};

// OPEN MODAL
window.openOutilAssociationModal = async function(employeeId) {

    window.currentEmployeeTools.employeeId = employeeId;

    const url = window.getEmployeeToolsUrl.replace('EMP_ID', employeeId);

    const res = await fetch(url);
    const data = await res.json();

    // Expected:
    // { tools: [{id, name}], assigned: [1,2,3] }

    window.currentEmployeeTools.tools = data.tools;

    renderToolsList();
    document.getElementById("outilAssociationModal").classList.remove("hidden");
};

// CLOSE
window.closeOutilAssociationModal = function() {
    document.getElementById("outilAssociationModal").classList.add("hidden");
};

// RENDER
function renderToolsList(filter = "") {

    const container = document.getElementById("toolsList");
    container.innerHTML = "";

    const filtered = window.currentEmployeeTools.tools.filter(t =>
        t.name.toLowerCase().includes(filter.toLowerCase())
    );

    filtered.forEach(tool => {
        const div = document.createElement("div");
        div.className = "tool-item" + (tool.checked ? " checked" : ""); 

        div.innerHTML = `
            <label>
                <input type="checkbox" ${tool.checked ? "checked" : ""} data-id="${tool.id}">
                ${tool.name}
            </label>
        `;

        const checkbox = div.querySelector("input");

        
        checkbox.addEventListener("change", () => {
            tool.checked = checkbox.checked;

            div.classList.toggle("checked", checkbox.checked);
        });

        container.appendChild(div);
    });
}

// SEARCH
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("searchTools");

    if (input) {
        input.addEventListener("input", (e) => {
            renderToolsList(e.target.value);
        });
    }
});

// SAVE
window.saveOutilAssociation = async function() {

    const selected = window.currentEmployeeTools.tools
        .filter(t => t.checked)
        .map(t => t.id);

    const url = window.saveEmployeeToolsUrl.replace(
        'EMP_ID',
        window.currentEmployeeTools.employeeId
    );

    try {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ tools: selected })
        });

        const result = await res.json();

        if (!res.ok || !result.success) {
            throw new Error(result.error || "Erreur lors de l'enregistrement");
        }

        alert("Outils mis à jour avec succès");

        closeOutilAssociationModal();

    } catch (err) {
        console.error(err);
        showOutilError(err.message);
    }
};