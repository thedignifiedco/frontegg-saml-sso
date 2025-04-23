document.addEventListener("DOMContentLoaded", function () {
    // Accordion logic
    const toggles = document.querySelectorAll(".accordion-toggle");

    toggles.forEach((toggle) => {
        toggle.addEventListener("click", function () {
            const isExpanded = this.getAttribute("aria-expanded") === "true";

            // Collapse all panels
            toggles.forEach((t) => {
                t.setAttribute("aria-expanded", "false");
                const panel = t.nextElementSibling;
                if (panel) panel.style.display = "none";
            });

            // Expand current if it was previously collapsed
            if (!isExpanded) {
                this.setAttribute("aria-expanded", "true");
                const panel = this.nextElementSibling;
                if (panel) panel.style.display = "block";
            }
        });
    });
});

// Copy to clipboard
function copyToClipboard(sourceId, button = null) {
    const el = document.getElementById(sourceId);
    const text = el?.innerText || el?.value || "";

    if (!text) {
        console.error("Nothing to copy");
        return;
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard
            .writeText(text)
            .then(() => {
                showCopyFeedback(button);
            })
            .catch((err) => {
                console.error("Clipboard write failed:", err);
            });
    } else {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.setAttribute("readonly", "");
        textarea.style.position = "absolute";
        textarea.style.left = "-9999px";
        document.body.appendChild(textarea);
        textarea.select();
        try {
            if (document.execCommand("copy")) {
                showCopyFeedback(button);
            } else {
                console.error("execCommand failed");
            }
        } catch (err) {
            console.error("execCommand error:", err);
        }
        document.body.removeChild(textarea);
    }
}

function showCopyFeedback(button) {
    if (!button) return;
    const original = button.innerHTML;
    button.innerHTML = "Copied!";
    button.disabled = true;
    setTimeout(() => {
        button.innerHTML = original;
        button.disabled = false;
    }, 1200);
}

// Form validation for Step 3 before submission
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const ssoInput = document.getElementById("sso_url");
    const sloInput = document.getElementById("slo_url");
    const certInput = document.getElementById("certificate");

    // Create error message elements if they don’t exist
    [ssoInput, sloInput, certInput].forEach((input) => {
        let errorEl = document.createElement("p");
        errorEl.className = "frontegg-error";
        errorEl.style.color = "red";
        errorEl.style.margin = "4px 0 0";
        input.parentNode.appendChild(errorEl);
    });

    form.addEventListener("submit", (e) => {
        let isValid = true;

        const showError = (input, message) => {
            const errorEl = input.parentNode.querySelector(".frontegg-error");
            if (message) {
                errorEl.textContent = message;
                isValid = false;
            } else {
                errorEl.textContent = "";
            }
        };

        showError(
            ssoInput,
            !ssoInput.value.trim() ? "SSO URL is required." : ""
        );
        showError(
            sloInput,
            !sloInput.value.trim() ? "Logout URL is required." : ""
        );
        showError(
            certInput,
            !certInput.value.trim() ? "Certificate is required." : ""
        );

        if (!isValid) {
            e.preventDefault();
        }
    });
});
