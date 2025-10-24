function animateMenuButton(el) {
    el.classList.toggle("on_change");
}

// --- Find your existing animateMenuButton function... ---
function animateMenuButton(button) {
    // This line is probably already in your function:
    button.classList.toggle("change");

    // --- ADD THESE LINES: ---
    const sideNav = document.getElementById("side-nav-menu");
    const overlay = document.getElementById("side-nav-overlay");

    if (sideNav && overlay) {
        sideNav.classList.toggle("open");
        overlay.classList.toggle("open");
    }
}

// --- ADD THIS NEW CODE (ideally inside a 'DOMContentLoaded' listener) ---
document.addEventListener("DOMContentLoaded", function() {

    // 1. Logic to close the menu when clicking the overlay
    const overlay = document.getElementById("side-nav-overlay");
    if (overlay) {
        overlay.addEventListener("click", function() {
            // Find the button and menu again to close them
            document.getElementById("menu_btn").classList.remove("change");
            document.getElementById("side-nav-menu").classList.remove("open");
            overlay.classList.remove("open");
        });
    }

    // 2. Logic for the accordion buttons inside the menu
    const accordionBtns = document.querySelectorAll(".nav-item-button");

    accordionBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            // Toggle 'active' class on the button (for the + icon)
            this.classList.toggle("active");

            // Find the next element (which is the .sub-menu)
            const subMenu = this.nextElementSibling;
            if (subMenu && subMenu.classList.contains("sub-menu")) {
                subMenu.classList.toggle("open");
            }
        });
    });

});