(function () {
    const playMode = sessionStorage.getItem("playMode");

    if (playMode !== "tournament") {
        return;
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll(".safe-actions").forEach((element) => {
            element.classList.add("hidden");
        });
    });

    let resultSent = false;

    window.addEventListener("beforeunload", (event) => {
        if (resultSent) {
            return;
        }

        event.preventDefault();
        event.returnValue = "";
    });

    window.addEventListener("fassarly:game-finished", async (event) => {
        if (resultSent) {
            return;
        }

        resultSent = true;
        const payload = event.detail || {};

        try {
            const response = await fetch("/api/save_tournament_result.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "تعذر حفظ نتيجة البطولة.");
            }

            sessionStorage.removeItem("playMode");
            alert("تم حفظ نتيجة البطولة. سيتم تسجيل خروجك الآن.");
            window.location.href = "/participant/logout.php";
        } catch (error) {
            resultSent = false;
            alert(error.message || "تعذر حفظ نتيجة البطولة. الرجاء إخبار المسؤول.");
        }
    });
})();
