document.addEventListener('DOMContentLoaded', () => {

    let currentSessionId = null;

    const startBtn = document.getElementById('startSession');
    const endBtn = document.getElementById('endSession');
    const container = document.getElementById('aw-container');
    const statusDiv = document.getElementById('aw-status');
    const iframe = document.getElementById('aw-frame');
    const accordion = document.getElementById('aw-toggle');
    let isConnected = false;

    // SAFETY CHECK (important)
    if (!accordion) return;

    const arrow = accordion.querySelector('.arrow');

    /* =========================
       CHECK ACTIVITYWATCH
    ========================= */
    async function checkActivityWatch() {
        try {
            const res = await fetch('/api/activitywatch/status');
            const data = await res.json();

            if (data.status !== 'running') throw new Error();

            statusDiv.classList.remove('hidden', 'error');
            statusDiv.classList.add('success');
            statusDiv.innerText = "ActivityWatch is running";

            iframe.style.display = 'block';
            startBtn.disabled = false;
            isConnected = true;

        } catch (e) {
            statusDiv.classList.add('error');
            statusDiv.innerText = "ActivityWatch is not running. Please start it.";

            iframe.style.display = 'none';
            startBtn.disabled = true;
            endBtn.disabled = true;
            isConnected = false;
        }
    }

    /* =========================
       SESSION HANDLING
    ========================= */
    startBtn.addEventListener('click', async () => {
        const res = await fetch('/api/session/start', { method: 'POST' });
        const data = await res.json();

        currentSessionId = data.sessionId;

        startBtn.disabled = true;
        endBtn.disabled = false;
    });

    endBtn.addEventListener('click', async () => {
        if (!currentSessionId) {
            alert('No active session');
            return;
        }

        await fetch(`/api/session/end/${currentSessionId}`, { method: 'POST' });

        currentSessionId = null;

        startBtn.disabled = false;
        endBtn.disabled = true;
    });

    /* =========================
       TOGGLE IFRAME
    ========================= */
    accordion.addEventListener('click', () => {
        if(!isConnected) return;
        container.classList.toggle('collapsed');
        accordion.classList.toggle('open');

        arrow.textContent = container.classList.contains('collapsed') ? '▶' : '▼';
    });

    /* =========================
       INIT
    ========================= */
    checkActivityWatch();

});