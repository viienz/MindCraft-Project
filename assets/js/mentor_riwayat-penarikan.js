document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  document
    .getElementById("mobileMenuToggle")
    ?.addEventListener("click", function () {
      document.getElementById("sidebar").classList.toggle("active");
    });

  // Initialize any necessary functionality
  console.log("Riwayat Penarikan page loaded");
});
