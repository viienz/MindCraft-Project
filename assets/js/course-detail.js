// course-detail.js
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tabs
  const tabLinks = document.querySelectorAll(".course-tab-link");
  const tabContents = document.querySelectorAll(".course-tab-content");

  tabLinks.forEach((link) => {
    link.addEventListener("click", function () {
      const targetTab = this.getAttribute("data-tab");

      // Remove active class from all tabs and contents
      tabLinks.forEach((l) => l.classList.remove("active"));
      tabContents.forEach((c) => c.classList.remove("active"));

      // Add active class to clicked tab and corresponding content
      this.classList.add("active");
      document.getElementById(targetTab + "-content").classList.add("active");
    });
  });

  // Toggle module content
  const moduleHeaders = document.querySelectorAll(".module-header");
  moduleHeaders.forEach((header) => {
    header.addEventListener("click", function () {
      const module = this.closest(".module");
      module.classList.toggle("open");

      // Toggle chevron icon
      const chevron = this.querySelector(".fa-chevron-down");
      if (chevron) {
        chevron.classList.toggle("rotate");
      }
    });
  });

  // Initialize rating distribution
  function initRatingDistribution() {
    const ratingBars = document.querySelectorAll(".rating-bar .bar");
    const ratingPercentages = document.querySelectorAll(
      ".rating-bar .percentage"
    );

    // Get data from the page (already calculated in PHP)
    const ratingData = {
      5: parseInt(
        document.querySelector('.rating-bar[data-rating="5"] .percentage')
          .textContent
      ),
      4: parseInt(
        document.querySelector('.rating-bar[data-rating="4"] .percentage')
          .textContent
      ),
      3: parseInt(
        document.querySelector('.rating-bar[data-rating="3"] .percentage')
          .textContent
      ),
      2: parseInt(
        document.querySelector('.rating-bar[data-rating="2"] .percentage')
          .textContent
      ),
      1: parseInt(
        document.querySelector('.rating-bar[data-rating="1"] .percentage')
          .textContent
      ),
    };

    ratingBars.forEach((bar, index) => {
      const rating = 5 - index;
      const percentage = ratingData[rating] || 0;

      bar.style.width = `${percentage}%`;
    });
  }

  // Only initialize if reviews tab exists
  if (document.getElementById("reviews-content")) {
    initRatingDistribution();
  }

  // Enroll button functionality
  const enrollBtn = document.getElementById("enroll-btn");
  if (enrollBtn) {
    enrollBtn.addEventListener("click", function (e) {
      // If it's a premium course, the form will handle submission
      if (this.closest("form")) return;

      e.preventDefault();
      const courseId = this.getAttribute("data-course-id");

      // Enroll in free course via AJAX
      fetch("/MindCraft-Project/api/enroll.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          course_id: courseId,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showToast("Anda telah berhasil mendaftar kursus ini!", "success");
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            showToast(data.message || "Gagal mendaftar kursus", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("Terjadi kesalahan saat mendaftar kursus", "error");
        });
    });
  }

  // Wishlist button functionality
  const wishlistBtn = document.getElementById("wishlist-btn");
  if (wishlistBtn) {
    wishlistBtn.addEventListener("click", function () {
      const courseId = this.getAttribute("data-course-id");
      const isActive = this.classList.contains("active");

      fetch("/MindCraft-Project/api/wishlist.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          course_id: courseId,
          action: isActive ? "remove" : "add",
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            if (data.added) {
              this.innerHTML = '<i class="fas fa-heart"></i> Dalam Wishlist';
              this.classList.add("active");
              showToast("Kursus ditambahkan ke wishlist", "success");
            } else {
              this.innerHTML =
                '<i class="far fa-heart"></i> Tambah ke Wishlist';
              this.classList.remove("active");
              showToast("Kursus dihapus dari wishlist", "info");
            }
          } else {
            showToast(data.message || "Gagal mengupdate wishlist", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("Terjadi kesalahan saat mengupdate wishlist", "error");
        });
    });
  }

  // Check if course is in wishlist on page load
  function checkWishlist() {
    const courseId = document
      .querySelector(".course-card")
      .getAttribute("data-course-id");
    if (!courseId) return;

    fetch(`/MindCraft-Project/api/wishlist.php?course_id=${courseId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.in_wishlist && wishlistBtn) {
          wishlistBtn.innerHTML = '<i class="fas fa-heart"></i> Dalam Wishlist';
          wishlistBtn.classList.add("active");
        }
      })
      .catch((error) => console.error("Error checking wishlist:", error));
  }

  checkWishlist();

  // User menu toggle
  const userAvatar = document.getElementById("user-avatar");
  const dropdownMenu = document.getElementById("dropdown-menu");

  if (userAvatar) {
    userAvatar.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.style.display =
        dropdownMenu.style.display === "block" ? "none" : "block";
    });
  }

  // Close dropdown when clicking outside
  document.addEventListener("click", function () {
    if (dropdownMenu) {
      dropdownMenu.style.display = "none";
    }
  });

  // Toast notification function
  function showToast(message, type = "info") {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.classList.add("show");
    }, 10);

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => {
        document.body.removeChild(toast);
      }, 300);
    }, 3000);
  }

  // Social share buttons
  document.querySelectorAll(".social-share-btn").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const url = window.location.href;
      const title = document.querySelector(".course-hero h1").textContent;

      let shareUrl;
      if (this.classList.contains("facebook")) {
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
          url
        )}`;
      } else if (this.classList.contains("twitter")) {
        shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(
          url
        )}&text=${encodeURIComponent(title)}`;
      } else if (this.classList.contains("linkedin")) {
        shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(
          url
        )}&title=${encodeURIComponent(title)}`;
      } else if (this.classList.contains("whatsapp")) {
        shareUrl = `https://wa.me/?text=${encodeURIComponent(
          title + " " + url
        )}`;
      }

      if (shareUrl) {
        window.open(shareUrl, "_blank", "width=600,height=400");
      }
    });
  });
});

// Global utility functions
window.appUtils = {
  openModal(modal) {
    modal.classList.add("open");
    document.body.style.overflow = "hidden";
  },

  closeModal(modal) {
    modal.classList.remove("open");
    document.body.style.overflow = "";
  },

  formatDate(dateString) {
    const options = { year: "numeric", month: "long", day: "numeric" };
    return new Date(dateString).toLocaleDateString("id-ID", options);
  },

  formatCurrency(amount) {
    return "Rp " + amount.toLocaleString("id-ID");
  },
};
