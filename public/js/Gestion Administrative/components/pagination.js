document.querySelectorAll(".page-btn").forEach(btn => {
    btn.addEventListener("click", (e) => {
        const page = e.currentTarget.dataset.page;
        const action = e.currentTarget.dataset.action;

        // Get current URL
        const url = new URL(window.location.href);
        let currentPage = parseInt(url.searchParams.get("page")) || 1;

        if (page) {
            url.searchParams.set("page", page);
        }

        if (action === "prev") {
            if (currentPage > 1) {
                url.searchParams.set("page", currentPage - 1);
            }
        }
        
        const totalPages = parseInt(document.body.dataset.totalPages) || 1;

        if (action === "next" && currentPage < totalPages) {
            url.searchParams.set("page", currentPage + 1);
        }

        // Redirect (this triggers Symfony controller)
        window.location.href = url.toString();
    });
});