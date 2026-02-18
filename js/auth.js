const API_URL = "/api/users.php";

/* LOGIN */
if (window.location.pathname.includes("login.html")) {
	
	// Show registration success message
const params = new URLSearchParams(window.location.search);
if (params.get("registered") === "1") {
    const msg = document.createElement("div");
    msg.textContent = "Account created successfully. Please log in.";
    msg.style.background = "#d4edda";
    msg.style.color = "#155724";
    msg.style.padding = "10px";
    msg.style.marginBottom = "15px";
    msg.style.borderRadius = "6px";
    msg.style.textAlign = "center";

    document.querySelector(".login-card").prepend(msg);

    // Clean the URL after showing message
    window.history.replaceState({}, document.title, "login.html");
}

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
                localStorage.setItem("first_name", result.first_name);
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
                localStorage.setItem("first_name", first_name);
                
                window.location.href = "login.html?registered=1";
            } else {
                alert(result.message);
            }

        } catch (err) {
            alert("Server error.");
        }
    });
}
