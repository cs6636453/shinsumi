let xmlHttp;

function send() {
    xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = showResult;

    // This is the PHP file from Step 1
    var url = "scripts/login_check.php";

    xmlHttp.open("POST", url);
    xmlHttp.send();
}

function showResult() {
    if (xmlHttp.readyState == 4 && xmlHttp.status == 200) {

        // 1. Inject the HTML from PHP into your <section id="login_btn">
        document.getElementById("login_btn").innerHTML = xmlHttp.responseText;

        // 2. IMPORTANT: Now that the HTML exists, find the new elements
        //    and add the click listener for the popup.
        setupPopupListeners();
    }
}

function setupPopupListeners() {
    const accountButton = document.getElementById("account-button");
    const accountPopup = document.getElementById("account-popup");

    // Only add listener if the elements (for logged-in user) actually exist
    if (accountButton && accountPopup) {

        accountButton.addEventListener("click", function(event) {
            // Stop the link from trying to go to "#"
            event.preventDefault();
            // Show or hide the menu
            accountPopup.classList.toggle("show");
        });
    }
}

window.addEventListener("click", function(event) {
    const accountButton = document.getElementById("account-button");
    const accountPopup = document.getElementById("account-popup");

    // Check if the popup elements exist before trying to read them
    if (accountButton && accountPopup) {

        // If the click was *outside* both the button AND the popup...
        if (!accountButton.contains(event.target) && !accountPopup.contains(event.target)) {
            // ...then hide the popup.
            accountPopup.classList.remove("show");
        }
    }
});

send();