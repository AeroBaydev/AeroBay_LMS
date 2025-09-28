document.addEventListener("DOMContentLoaded", function () {
    // 1. Remove 'active' class from all menu items
    document.querySelectorAll(".list-group-item.active").forEach(function (el) {
        el.classList.remove("active");
    });

    // 2. Find the "Video Upload Media" link by URL and add 'active'
    var link = document.querySelector('a[href*="/local/videohub/index.php"]');
    if (link) {
        link.classList.add("active");
    }
     
});