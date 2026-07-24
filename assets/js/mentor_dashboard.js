document.addEventListener("DOMContentLoaded", function () {
  // Initialize all components
  initMobileMenu();
  initRegistrationChart();
  initAnimations();
  initHoverEffects();
  initSidebarNavigation();
  initResponsiveBehavior();
  initBadgeAnimations();
  initChartResizeHandler();
  addLoadingEffect();
});

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const sidebar = document.getElementById("sidebar");

  if (mobileMenuToggle && sidebar) {
    // Toggle sidebar on menu button click
    mobileMenuToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      sidebar.classList.toggle("open");
    });

    // Close sidebar when clicking outside
    document.addEventListener("click", function (e) {
      if (
        window.innerWidth <= 768 &&
        sidebar.classList.contains("open") &&
        !sidebar.contains(e.target) &&
        !mobileMenuToggle.contains(e.target)
      ) {
        sidebar.classList.remove("open");
      }
    });
  }
}

/**
 * Initialize registration chart using Chart.js
 */
function initRegistrationChart() {
  const chartCanvas = document.getElementById("registrationChart");
  if (!chartCanvas || typeof Chart === "undefined") return;

  const ctx = chartCanvas.getContext("2d");
  const chartData = window.dashboardData || {
    monthlyRegistrations: [0, 0, 0, 0, 0, 0, 0],
    labels: ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"],
  };

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: chartData.labels.slice(0, 7), // Ensure only 7 days
      datasets: [
        {
          label: "Pendaftaran",
          data: chartData.monthlyRegistrations.slice(0, 7), // Ensure only 7 days
          backgroundColor: "#3A59D1",
          borderColor: "#3305BC",
          borderWidth: 0,
          borderRadius: 6,
          borderSkipped: false,
          maxBarThickness: 35,
        },
      ],
    },
    options: getChartOptions(chartData),
  });
}

/**
 * Get chart configuration options
 */
function getChartOptions(chartData) {
  const maxValue = Math.max(...chartData.monthlyRegistrations);

  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: "rgba(58, 89, 209, 0.9)",
        titleColor: "#fff",
        bodyColor: "#fff",
        borderColor: "#3305BC",
        borderWidth: 1,
        cornerRadius: 8,
        displayColors: false,
        titleFont: { family: "Inter", size: 13, weight: "500" },
        bodyFont: { family: "Inter", size: 12, weight: "400" },
        callbacks: {
          title: (context) => context[0].label,
          label: (context) => "Pendaftaran: " + context.parsed.y,
        },
      },
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: {
          color: "#718096",
          font: { family: "Inter", size: 12, weight: "400" },
          padding: 8,
        },
      },
      y: {
        beginAtZero: true,
        max: maxValue > 0 ? maxValue + maxValue * 0.2 : 10, // Add 20% padding
        grid: {
          color: "rgba(0, 0, 0, 0.06)",
          drawBorder: false,
          lineWidth: 1,
        },
        ticks: {
          color: "#718096",
          font: { family: "Inter", size: 12, weight: "400" },
          stepSize: maxValue > 20 ? 5 : 1,
          padding: 8,
          callback: (value) => (Number.isInteger(value) ? value : ""),
        },
      },
    },
    animation: {
      duration: 1200,
      easing: "easeOutQuart",
    },
    interaction: {
      intersect: false,
      mode: "index",
    },
  };
}

/**
 * Initialize all animations
 */
function initAnimations() {
  // Delay animations slightly for better perceived performance
  setTimeout(() => {
    animateFadeInElements();
    animateCounterNumbers();
  }, 300);
}

/**
 * Animate fade-in elements with stagger effect
 */
function animateFadeInElements() {
  const elements = document.querySelectorAll(".fade-in-up");

  elements.forEach((element, index) => {
    element.style.opacity = "0";
    element.style.transform = "translateY(30px)";

    setTimeout(() => {
      element.style.transition = "opacity 0.8s ease, transform 0.8s ease";
      element.style.opacity = "1";
      element.style.transform = "translateY(0)";
    }, index * 150);
  });
}

/**
 * Animate counter numbers
 */
function animateCounterNumbers() {
  const counters = document.querySelectorAll(".stat-number, .summary-value");

  counters.forEach((counter, index) => {
    setTimeout(() => {
      const originalText = counter.textContent;
      const target = parseFloat(originalText.replace(/[^\d.]/g, "")) || 0;
      const duration = 1500; // Animation duration in ms
      const startTime = performance.now();
      const isDecimal = originalText.includes(".");

      const animate = (currentTime) => {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);
        const currentValue = progress * target;

        // Format based on original content
        if (originalText.includes("%")) {
          counter.textContent = Math.floor(currentValue) + "%";
        } else if (originalText.includes("Jam")) {
          counter.textContent = Math.floor(currentValue) + " Jam";
        } else if (originalText.includes("jt")) {
          counter.textContent =
            "Rp " + (currentValue / 1000000).toFixed(1) + " jt";
        } else if (isDecimal) {
          counter.textContent = currentValue.toFixed(1);
        } else {
          counter.textContent = Math.floor(currentValue);
        }

        if (progress < 1) {
          requestAnimationFrame(animate);
        }
      };

      requestAnimationFrame(animate);
    }, index * 100);
  });
}

/**
 * Initialize hover effects for cards
 */
function initHoverEffects() {
  // Card hover effects
  document
    .querySelectorAll(".stat-card, .activity-card, .chart-card")
    .forEach((card) => {
      card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-4px)";
        card.style.boxShadow = "0 8px 25px rgba(58, 89, 209, 0.15)";
        card.style.transition = "transform 0.3s ease, box-shadow 0.3s ease";
      });

      card.addEventListener("mouseleave", () => {
        card.style.transform = "translateY(0)";
        card.style.boxShadow = "0 2px 8px rgba(0,0,0,0.1)";
      });
    });

  // Summary item hover effects
  document.querySelectorAll(".summary-item").forEach((item) => {
    item.addEventListener("mouseenter", () => {
      item.style.background = "#f8fafc";
      item.style.transform = "scale(1.02)";
      item.style.transition = "all 0.3s ease";
    });

    item.addEventListener("mouseleave", () => {
      item.style.background = "white";
      item.style.transform = "scale(1)";
    });
  });
}

/**
 * Initialize sidebar navigation
 */
function initSidebarNavigation() {
  document.querySelectorAll(".sidebar-menu a").forEach((link) => {
    link.addEventListener("click", function (e) {
      if (this.getAttribute("href") === "#") {
        e.preventDefault();
      }

      // Update active state
      document.querySelectorAll(".sidebar-menu a").forEach((el) => {
        el.classList.remove("active");
      });
      this.classList.add("active");

      // Close mobile menu if open
      if (window.innerWidth <= 768) {
        const sidebar = document.getElementById("sidebar");
        if (sidebar) sidebar.classList.remove("open");
      }
    });
  });
}

/**
 * Initialize responsive behavior
 */
function initResponsiveBehavior() {
  const sidebar = document.getElementById("sidebar");
  if (!sidebar) return;

  function handleResize() {
    if (window.innerWidth <= 768) {
      sidebar.classList.add("mobile");
    } else {
      sidebar.classList.remove("mobile", "open");
    }
  }

  // Initial check
  handleResize();

  // Debounced resize handler
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(handleResize, 250);
  });
}

/**
 * Initialize badge animations
 */
function initBadgeAnimations() {
  const observerOptions = {
    threshold: 0.5,
    rootMargin: "0px",
  };

  const badgeObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const badges = entry.target.querySelectorAll(".stat-badge");
        badges.forEach((badge, index) => {
          setTimeout(() => {
            badge.style.animation = "pulse 0.6s ease-in-out";
          }, index * 200);
        });
      }
    });
  }, observerOptions);

  const statsGrid = document.querySelector(".stats-grid");
  if (statsGrid) badgeObserver.observe(statsGrid);
}

/**
 * Initialize chart resize handler
 */
function initChartResizeHandler() {
  let resizeTimeout;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      if (typeof Chart !== "undefined" && Chart.instances) {
        Chart.instances.forEach((chart) => chart.resize());
      }
    }, 250);
  });
}

/**
 * Add loading skeleton effect
 */
function addLoadingEffect() {
  const cards = document.querySelectorAll(".stat-card, .summary-item");

  cards.forEach((card) => {
    const originalBg = card.style.background;
    card.style.background =
      "linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%)";
    card.style.backgroundSize = "200% 100%";
    card.style.animation = "loading 1.5s infinite";
    card.dataset.originalBg = originalBg;
  });

  // Remove loading effect after content loads
  setTimeout(() => {
    cards.forEach((card) => {
      card.style.background = card.dataset.originalBg || "";
      card.style.animation = "";
    });
  }, 1000);
}

/**
 * Update dashboard data dynamically
 */
function updateDashboardData(newData) {
  if (!newData) return;

  try {
    // Update stat cards
    updateStatCard(
      ".stat-card:nth-child(1) .stat-number",
      newData.totalCourses
    );
    updateStatCard(
      ".stat-card:nth-child(2) .stat-number",
      newData.totalMentees
    );
    updateStatCard(
      ".stat-card:nth-child(3) .stat-number",
      newData.averageRating,
      "rating"
    );

    // Update summary bar
    updateSummaryItem(
      ".summary-item:nth-child(1) .summary-value",
      newData.completionRate,
      "percentage"
    );
    updateSummaryItem(
      ".summary-item:nth-child(2) .summary-value",
      newData.videoHours,
      "hours"
    );
    updateSummaryItem(
      ".summary-item:nth-child(3) .summary-value",
      newData.moduleCount
    );
    updateSummaryItem(
      ".summary-item:nth-child(4) .summary-value",
      newData.totalReviews
    );
    updateSummaryItem(
      ".summary-item:nth-child(5) .summary-value",
      newData.totalEarnings,
      "currency"
    );

    // Update chart if data exists
    if (newData.monthlyRegistrations && Chart?.instances?.[0]) {
      const chart = Chart.instances[0];
      chart.data.datasets[0].data = newData.monthlyRegistrations;
      chart.update();
    }
  } catch (error) {
    console.error("Error updating dashboard:", error);
  }
}

/**
 * Helper function to update stat card values
 */
function updateStatCard(selector, value, format) {
  const element = document.querySelector(selector);
  if (element) {
    animateNumberChange(element, value, format);
  }
}

/**
 * Helper function to update summary item values
 */
function updateSummaryItem(selector, value, format) {
  const element = document.querySelector(selector);
  if (element) {
    animateNumberChange(element, value, format);
  }
}

/**
 * Animate number change with smooth transition
 */
function animateNumberChange(element, newValue, format) {
  if (!element) return;

  const currentValue =
    parseFloat(element.textContent.replace(/[^\d.]/g, "")) || 0;
  const duration = 1000; // Animation duration in ms
  const startTime = performance.now();

  const animate = (currentTime) => {
    const elapsedTime = currentTime - startTime;
    const progress = Math.min(elapsedTime / duration, 1);
    const current = currentValue + (newValue - currentValue) * progress;

    element.textContent = formatNumber(current, format);

    if (progress < 1) {
      requestAnimationFrame(animate);
    }
  };

  requestAnimationFrame(animate);
}

/**
 * Format numbers based on type
 */
function formatNumber(num, type) {
  num = typeof num === "number" ? num : parseFloat(num) || 0;

  switch (type) {
    case "currency":
      return num >= 1000000
        ? "Rp " + (num / 1000000).toFixed(1) + " jt"
        : "Rp " + Math.floor(num).toLocaleString();
    case "percentage":
      return Math.floor(num) + "%";
    case "rating":
      return num.toFixed(1);
    case "hours":
      return Math.floor(num) + " Jam";
    default:
      return num >= 1000
        ? (num / 1000).toFixed(1) + "k"
        : Math.floor(num).toString();
  }
}

// Add CSS animations dynamically
const styleElement = document.createElement("style");
styleElement.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .stat-badge {
        transition: all 0.3s ease;
    }
    
    .summary-item {
        transition: all 0.3s ease;
    }
    
    .activity-item {
        transition: all 0.2s ease;
    }
    
    .fade-in-up {
        opacity: 0;
        transform: translateY(30px);
    }
    
    .fade-in-up.animated {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.8s ease, transform 0.8s ease;
    }
`;
document.head.appendChild(styleElement);
