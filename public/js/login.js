(function() {
  console.log("SLA Login Script Loaded");

  window.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById("loginForm");

    if (loginForm) {
      console.log("Login form found, attaching listener...");
      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        console.log("Login submit intercepted!");

        const usernameInput = document.getElementById("username");
        const passwordInput = document.getElementById("password");

        if (!usernameInput || !passwordInput) {
          console.error("Input fields not found!");
          return;
        }

        const username = usernameInput.value;
        const password = passwordInput.value;

        console.log("Attempting login for:", username);

        try {
          const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username, password }),
          });

          if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.statusText);
          }

          const result = await response.json();
          console.log("Server response:", result);

          if (result && result.success) {
            console.log("Login success! Redirecting to:", result.redirect);
            window.location.href = result.redirect;
          } else {
            alert("Login Gagal: " + (result.message || "Kredensial salah"));
          }
        } catch (error) {
          console.error("Login error:", error);
          alert("Terjadi kesalahan saat login. Silakan cek konsol browser.");
        }
      });
    } else {
      console.error("Login form NOT found in DOM!");
    }
  });
})();