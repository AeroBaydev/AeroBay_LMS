document.addEventListener("DOMContentLoaded", function () {
    // Get the current URL
    let currentUrl = window.location.href;

    // Check if the URL contains 'create_timetable.php'
    if (currentUrl.includes("attendance_new")) {
        // Remove 'active' class from any previously active tab
        let activeTab = document.querySelector(".list-group-item.active");
        if (activeTab) {
            activeTab.classList.remove("active");
        }

        // Dynamically construct the timetable management link
        let timetableLink = document.querySelector(`a[href="${M.cfg.wwwroot}/local/attendance_new/index.php"]`);

        if (timetableLink) {
            // Add 'active' class to highlight the link
            timetableLink.classList.add("active");
        }
    }
});
