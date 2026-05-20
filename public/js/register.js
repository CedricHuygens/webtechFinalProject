const usernameField = document.getElementById("form_username");
usernameField.addEventListener('change', () => {
    console.log("event");
    checkUser("username", usernameField.value);
});
const emailField = document.getElementById("form_email");
emailField.addEventListener('change', () => {
    console.log("event");
    checkUser("email", emailField.value);
});
const registerForm = document.querySelector("form[name='form']");
registerForm.addEventListener("change", () => {
    checkEverythingFilled();
});
const passwordField = document.getElementById("form_password");
const passwordCheckField = document.getElementById("form_passwordCheck");
passwordField.addEventListener('change', () => checkPasswordMatch());
passwordCheckField.addEventListener('change', () => checkPasswordMatch());

function checkEverythingFilled() {
    const inputs = registerForm.querySelectorAll("input");
    const registerButton = document.getElementById("form_register");
    const statusUsernameMessage = document.getElementById("registerUsernameStatusMessage");
    const statusEmailMessage = document.getElementById("registerEmailStatusMessage");
    const password = document.getElementById("form_password");
    const passwordCheck = document.getElementById("form_passwordCheck");

    const allFilled = Array.from(inputs)
        .filter(input => input.type !== "hidden")
        .every(input => input.value.trim() !== "");
    const usernameValid = statusUsernameMessage.className === "status-message success";
    const emailValid = statusEmailMessage.className === "status-message success";
    const passwordsMatch = document.getElementById("registerPasswordStatusMessage").className === "status-message success";

    registerButton.disabled = !(allFilled && usernameValid && emailValid && passwordsMatch);
}
function checkPasswordMatch() {
    const statusPasswordMessage = document.getElementById("registerPasswordStatusMessage");
    if (passwordField.value === "" || passwordCheckField.value === "") {
        statusPasswordMessage.hidden = true;
        return;
    }
    statusPasswordMessage.hidden = false;
    if (passwordField.value !== passwordCheckField.value) {
        statusPasswordMessage.textContent = "Passwords do not match.";
        statusPasswordMessage.className = "status-message error";
    } else {
        statusPasswordMessage.textContent = "Passwords match.";
        statusPasswordMessage.className = "status-message success";
    }
    checkEverythingFilled();
}
async function checkUser(key, value) { // async want hij wacht niet op het resultaat om verder te gaan
    const statusUsernameMessage = document.getElementById("registerUsernameStatusMessage");
    const statusEmailMessage = document.getElementById("registerEmailStatusMessage");

    if (value.trim() === "") {
        if (key === "username") statusUsernameMessage.hidden = true;
        else statusEmailMessage.hidden = true;
        return;
    }

    // Base is nodig om ervoor te zorgen dat het zowel lokaal als deployed werkt aangezien deze een andere root hebben
    const base = window.location.pathname.startsWith('/public') ? '/public' : '';

    const response = await fetch(`${base}/check_user`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `field=${encodeURIComponent(key)}&value=${encodeURIComponent(value)}`
    });
    const data = await response.json();

    if (key === "username") {
        statusUsernameMessage.hidden = false;
        if (data.available === false) {
            statusUsernameMessage.textContent = "Username is already in use.";
            statusUsernameMessage.className = "status-message error";
        } else {
            statusUsernameMessage.textContent = "Username is available.";
            statusUsernameMessage.className = "status-message success";
        }
    } else {
        statusEmailMessage.hidden = false;
        if (data.available === false) {
            statusEmailMessage.textContent = "Email is already in use.";
            statusEmailMessage.className = "status-message error";
        } else {
            statusEmailMessage.textContent = "Email is available.";
            statusEmailMessage.className = "status-message success";
        }
    }
    checkEverythingFilled(); // mogelijks als de username wel beschikbaar is mag de knop klikbaar worden dus daarom hier nog eens aanhalen
}
// If the page reloads (when password is not strong enough) the fields need to be checked again
document.addEventListener('DOMContentLoaded', function () {
    const fields = [
        '#form_username',
        '#form_email',
        '#form_password',
        '#form_passwordCheck',
        '#form_birthDate',
    ];

    fields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field && field.value !== '') {
            field.dispatchEvent(new Event('change')); // Zo wordt zowel checkEverythingFilled() als checkUser() voor email en Username aangehaald
        }
    });
});
function showPassword() {
    const checkBox = document.getElementById("showPasswordCheckBox");
    const passwordField = document.getElementById("form_password");
    const passwordCheckField = document.getElementById("form_passwordCheck");
    if (checkBox.checked) {
        passwordField.type = 'text'; // type bepaalt of het al dan niet zichtbaar is. Standaard staat het op 'password' en is het dus onleesbaar gemaakt in de frontend
        passwordCheckField.type = 'text';
    } else {
        passwordField.type = 'password';
        passwordCheckField.type = 'password';
    }
}
