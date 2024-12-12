// Handle profile picture upload
function uploadImage() {
    const fileInput = document.getElementById('file-input');
    const profilePic = document.getElementById('profile-pic');
    const removeButton = document.getElementById('remove-image');

    const file = fileInput.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            profilePic.src = e.target.result;
            saveProfilePicture(e.target.result);  // Save the picture URL to localStorage
            removeButton.style.display = 'block'; // Show remove button
        };
        reader.readAsDataURL(file);
    }
}

// Save the uploaded profile picture to localStorage
function saveProfilePicture(imageUrl) {
    localStorage.setItem('profilePic', imageUrl);
}

// Remove profile picture
function removeImage() {
    document.getElementById('profile-pic').src = 'default-avatar.png';  // Reset to default image
    document.getElementById('remove-image').style.display = 'none';  // Hide the Remove button
    localStorage.removeItem('profilePic');  // Clear from localStorage
}

// Save the username to localStorage
function saveUsername() {
    const username = document.getElementById('username').value.trim();
    if (username === "") {
        alert("Username cannot be empty!");
        return;
    }
    localStorage.setItem('username', username);
    displayUsername();
    alert("Username saved successfully!");
}

// Display the saved username on the page
function displayUsername() {
    const username = localStorage.getItem('username');
    const usernameDisplay = document.getElementById('display-username');
    if (username) {
        usernameDisplay.textContent = username;
    }
}

// Save all settings to localStorage
function saveSettings() {
    const button = document.getElementById('save-btn');
    button.disabled = true;
    button.innerHTML = 'Saving...';

    const profilePic = document.getElementById('profile-pic').src;
    const username = document.getElementById('username').value;
    const bio = document.getElementById('bio').value;

    // Save data to localStorage
    localStorage.setItem('profilePic', profilePic);
    localStorage.setItem('username', username);
    localStorage.setItem('bio', bio);

    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = 'Save All Settings';
        alert('Settings saved successfully!');
    }, 1000);
}

// Initialize page with saved settings
window.onload = function() {
    const savedUsername = localStorage.getItem('username');
    if (savedUsername) {
        document.getElementById('username').value = savedUsername;
        displayUsername();
    }

    const savedProfilePic = localStorage.getItem('profilePic');
    if (savedProfilePic) {
        document.getElementById('profile-pic').src = savedProfilePic;
        document.getElementById('remove-image').style.display = 'block';  // Show remove button
    }

    const savedBio = localStorage.getItem('bio');
    if (savedBio) {
        document.getElementById('bio').value = savedBio;
    }
};
