(function () {
    const playMode = sessionStorage.getItem("playMode");

    if (playMode !== "tournament") {
        return;
    }

    let resultSent = false;
    let abandoning = false;

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll(".safe-actions").forEach((element) => {
            element.classList.add("hidden");
        });
    });

    function currentTournamentPayload() {
        return {
            elapsedSeconds: readElapsedSeconds(),
            attemptsCount: readAttemptsCount(),
            randomNumber: sessionStorage.getItem("originalNumber") || "",
            level: sessionStorage.getItem("selectedLevel") || "",
            isRandomMode: true
        };
    }

    function readAttemptsCount() {
        const value = Number.parseInt(document.getElementById("attempts-count")?.textContent || "1", 10);
        return Number.isFinite(value) && value > 0 ? value : 1;
    }

    function readElapsedSeconds() {
        const text = document.getElementById("timer-display")?.textContent || "";
        const values = text.match(/\d+/g)?.map(Number) || [];

        if (text.includes("دقيقة")) {
            return (values[0] || 0) * 60 + (values[1] || 0);
        }

        return values[0] || 0;
    }

    async function abandonTournament(shouldRedirect = true) {
        if (resultSent || abandoning) {
            return;
        }

        abandoning = true;
        const payload = currentTournamentPayload();

        try {
            const response = await fetch("/api/abandon_tournament.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(() => ({}));
            sessionStorage.removeItem("playMode");

            if (shouldRedirect) {
                window.location.href = data.redirect || "/participant/login.php";
            }
        } catch (error) {
            if (shouldRedirect) {
                window.location.href = "/participant/login.php";
            }
        }
    }

    window.addEventListener("beforeunload", (event) => {
        if (resultSent || abandoning) {
            return;
        }

        event.preventDefault();
        event.returnValue = "";
    });

    window.addEventListener("pagehide", () => {
        if (resultSent || abandoning) {
            return;
        }

        const payload = JSON.stringify(currentTournamentPayload());
        navigator.sendBeacon?.("/api/abandon_tournament.php", new Blob([payload], { type: "application/json" }));
    });

    document.addEventListener("click", (event) => {
        const link = event.target.closest?.("a[href]");
        if (!link || resultSent || abandoning) {
            return;
        }

        event.preventDefault();
        const confirmed = confirm("إذا غادرت الآن، سيتم إقصاؤك من هذه البطولة.");
        if (confirmed) {
            abandonTournament(true);
        }
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
            sessionStorage.removeItem("originalNumber");
            sessionStorage.removeItem("selectedLevel");

            if (data.finishedAll) {
                alert("تم حفظ نتائج البطولات الثلاث. سيتم تسجيل خروجك الآن.");
            } else {
                alert("تم حفظ نتيجة هذه البطولة. يمكنك متابعة البطولات المتبقية.");
            }

            window.location.href = data.redirect || "/participant/mode.php";
        } catch (error) {
            resultSent = false;
            alert(error.message || "تعذر حفظ نتيجة البطولة. الرجاء إخبار المسؤول.");
        }
    });
})();
