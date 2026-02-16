const API_URL = "/api/users.php";

/* LOGIN */
if (window.location.pathname.includes("login.html")) {

    document.querySelector("form").addEventListener("submit", async (e) => {
        e.preventDefault();

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();

        try {
            const response = await fetch(API_URL, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username, password })
            });

            const result = await response.json();

            if (result.success) {
                localStorage.setItem("user_id", result.user_id);
                window.location.href = "contacts.html";
            } else {
                alert(result.message);
            }

        } catch (err) {
            alert("Server error.");
        }
    });
}

/* REGISTER */
if (window.location.pathname.includes("create-account.html")) {

    document.querySelector("form").addEventListener("submit", async (e) => {
        e.preventDefault();

        const inputs = document.querySelectorAll("input");

        const first_name = inputs[0].value.trim();
        const last_name = inputs[1].value.trim();
        const username = inputs[2].value.trim();
        const email = inputs[3].value.trim();
        const password = inputs[4].value.trim();

        try {
            const response = await fetch(API_URL, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    first_name,
                    last_name,
                    username,
                    email,
                    password
                })
            });

            const result = await response.json();

            if (result.success) {
                alert("Account created successfully!");
                window.location.href = "login.html";
            } else {
                alert(result.message);
            }

        } catch (err) {
            alert("Server error.");
        }
    });
}
