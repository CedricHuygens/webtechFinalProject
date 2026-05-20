// profile picture:

const input = document.getElementById('profilePicture');
const preview = document.getElementById('previewImage');

input.addEventListener('change', function(event) {
    const file = event.target.files[0]; // gives the selected files
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) { // if file is read
            preview.src = e.target.result;
            preview.style.display = 'block'; // show image
        }
        reader.readAsDataURL(file); // start reading file
    }
});

const deleteBtn = document.getElementById("deleteAccountButton");
const popup = document.getElementById("confirmPopup");
const yesBtn = document.getElementById("confirmYes");
const noBtn = document.getElementById("confirmNo");
const form = document.getElementById("deleteForm");

// delete account check:

deleteBtn.addEventListener("click", function() {
    popup.style.display = "block";
});

noBtn.addEventListener("click", function() {
    popup.style.display = "none";
});

yesBtn.addEventListener("click", function() {
    form.submit(); // only delete account if pressed YES
});

// password check:

const changePasswordBtn = document.getElementById("changePasswordButton");
const changePasswordPopup = document.getElementById("changePasswordPopup");
const oldPassword = document.getElementById("oldPassword")
const password1 = document.getElementById("password1")
const password2 = document.getElementById("password2")
const confirmButton = document.getElementById("confirmPasswordChange");
const cancelButton = document.getElementById("cancelPasswordChange")
const changePasswordform = document.getElementById("changePasswordForm");

changePasswordBtn.addEventListener("click", function() {
    changePasswordPopup.classList.add("active");
});

oldPassword.addEventListener("change", () => {
    checkPassword(oldPassword.value);
});

async function checkPassword(value){
    const statusOldPasswordMessage = document.getElementById("changePasswordOldPassword");

    statusOldPasswordMessage.hidden = false;
    if (value.trim() === "") {
        statusOldPasswordMessage.hidden = true;
        return;
    }
    const base = window.location.pathname.startsWith('/public') ? '/public' : '';

    const response = await fetch(`${base}/check_old_password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `value=${encodeURIComponent(value)}`
    });
    const data = await response.json();
    if (data.checkOk === false) {
        statusOldPasswordMessage.textContent = "Old password is wrong";
        statusOldPasswordMessage.className = "status-message error";
    } else {
        statusOldPasswordMessage.textContent = "Password is correct";
        statusOldPasswordMessage.className = "status-message success";
    }
}

confirmButton.addEventListener("click", function(){

    if(password1.value.trim() === "" || password2.value.trim() === ""){
        alert("Please fill in both password fields");
        return;
    }

    if(password1.value !== password2.value){
        alert("Passwords do not match");
        return;
    }
    changePasswordform.submit();
});

cancelButton.addEventListener("click", function() {
    changePasswordPopup.classList.remove("active");

    password1.value = "";
    password2.value = "";
});


// Email and Username check to see if they are still valid:

const emailField = document.querySelector("input[name='email']");
const usernameField = document.querySelector("input[name='username']");

emailField.addEventListener("change", () => {
    checkUser("email", emailField.value);
});

usernameField.addEventListener("change", () => {
    checkUser("username", usernameField.value);
});

async function checkUser(key, value) {
    const statusUsernameMessage = document.getElementById("changeUsernameStatusMessage");
    const statusEmailMessage = document.getElementById("changeEmailStatusMessage");

    if (value.trim() === "") {
        if (key === "username") statusUsernameMessage.hidden = true;
        else statusEmailMessage.hidden = true;
        return;
    }
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
}
