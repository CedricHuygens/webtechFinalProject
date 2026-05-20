const searchBar = document.getElementById('form_teamMate');
const teamNameField = document.getElementById('form_teamName');
const applyButton = document.getElementById('applyButton');
const options = [...document.querySelectorAll('#friendsList option')]; // Converts it from a NodeList to an Array

// JavaScript check if the userName that is filled in in the searchBar is indeed in your friendList. Only then the applyBtn gets enabled
searchBar.addEventListener('change', () => {
    const match = options.find(option => option.value === searchBar.value);

    if (match) {
        teamNameField.disabled = false;
        if(teamNameField.value !== '') {
            applyButton.disabled = false;
        }
    } else {
        teamNameField.disabled = true;
        applyButton.disabled = true;
    }
});
// On top of the required tag front-end it also disables the apply btn if no teamName is filled in
teamNameField.addEventListener('input', () => {
    applyButton.disabled = teamNameField.value.trim() === '';
});
