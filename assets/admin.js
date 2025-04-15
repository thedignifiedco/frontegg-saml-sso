document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".nav-tab");
    const contents = document.querySelectorAll(".tab-content");

    tabs.forEach((tab) => {
        tab.addEventListener("click", function (e) {
            e.preventDefault();
            tabs.forEach((t) => t.classList.remove("nav-tab-active"));
            contents.forEach((c) => (c.style.display = "none"));

            this.classList.add("nav-tab-active");
            const target = document.querySelector(
                this.getAttribute("href") + "-content"
            );
            if (target) target.style.display = "block";
        });
    });
});

function copyToClipboard(id) {
    const text = document.getElementById(id).textContent;
    const button = document.querySelector(`#${id} + button`);
    let feedback = button.nextElementSibling;

    // Create feedback span if not exists
    if (!feedback || !feedback.classList.contains("copy-feedback")) {
        feedback = document.createElement("span");
        feedback.className = "copy-feedback";
        button.parentNode.insertBefore(feedback, button.nextSibling);
    }

    navigator.clipboard.writeText(text).then(() => {
        feedback.textContent = " Copied!";
        feedback.style.display = "inline";

        setTimeout(() => {
            feedback.style.display = "none";
        }, 2000);
    });
}
