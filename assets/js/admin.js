// Constants
const API_BASE_URL = "api.php";
const REFRESH_INTERVAL = 30000; // 30 seconds
const TOAST_DURATION = 3000; // 3 seconds

// Global variables
let currentUser = null;
let currentCourse = null;
let currentCategory = null;
let userDistributionChart,
  userGrowthChart,
  courseStatusChart,
  courseCategoryChart;

// DOM Ready
document.addEventListener("DOMContentLoaded", function () {
  initializeCharts();
  loadInitialData();
  setupEventListeners();
  startAutoRefresh();
});

// Initialization Functions
function initializeCharts() {
  // User Distribution (Doughnut)
  userDistributionChart = createChart("userDistributionChart", {
    type: "doughnut",
    data: {
      labels: ["Mentee", "Mentor"],
      datasets: [
        {
          data: [0, 0],
          backgroundColor: ["#4cc9f0", "#f72585"],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || "";
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage =
                total > 0 ? Math.round((value / total) * 100) : 0;
              return `${label}: ${value} (${percentage}%)`;
            },
          },
        },
      },
      cutout: "70%",
    },
  });

  // User Growth (Line)
  userGrowthChart = createChart("userGrowthChart", {
    type: "line",
    data: {
      labels: [],
      datasets: [
        {
          label: "Mentee",
          data: [],
          borderColor: "#4cc9f0",
          backgroundColor: "rgba(76, 201, 240, 0.1)",
          tension: 0.3,
          fill: true,
        },
        {
          label: "Mentor",
          data: [],
          borderColor: "#f72585",
          backgroundColor: "rgba(247, 37, 133, 0.1)",
          tension: 0.3,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: "bottom" } },
      scales: { y: { beginAtZero: true } },
    },
  });

  // Course Status (Pie)
  courseStatusChart = createChart("courseStatusChart", {
    type: "pie",
    data: {
      labels: ["Published", "Draft", "Archived"],
      datasets: [
        {
          data: [0, 0, 0],
          backgroundColor: ["#4cc9f0", "#f8961e", "#f72585"],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || "";
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage =
                total > 0 ? Math.round((value / total) * 100) : 0;
              return `${label}: ${value} (${percentage}%)`;
            },
          },
        },
      },
    },
  });

  // Course Category (Bar)
  courseCategoryChart = createChart("courseCategoryChart", {
    type: "bar",
    data: {
      labels: [],
      datasets: [
        {
          label: "Jumlah Kursus",
          data: [],
          backgroundColor: "rgba(67, 97, 238, 0.7)",
          borderColor: "#4361ee",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } },
    },
  });
}

function createChart(elementId, config) {
  const ctx = document.getElementById(elementId).getContext("2d");
  return new Chart(ctx, config);
}

function loadInitialData() {
  loadDashboardData();
  loadUsers();
  loadCourses();
  loadCategories();
  loadEnrollments();
}

function setupEventListeners() {
  // Setup any additional event listeners here
}

function startAutoRefresh() {
  setInterval(loadDashboardData, REFRESH_INTERVAL);
}

// Navigation Functions
function showDashboard() {
  hideAllContent();
  document.getElementById("dashboard-content").style.display = "block";
  updateActiveMenu("dashboard");
  loadDashboardData();
}

function showUsers() {
  hideAllContent();
  document.getElementById("users-content").style.display = "block";
  updateActiveMenu("users");
  loadUsers();
}

function showCourses() {
  hideAllContent();
  document.getElementById("courses-content").style.display = "block";
  updateActiveMenu("courses");
  loadCourses();
}

function showCategories() {
  hideAllContent();
  document.getElementById("categories-content").style.display = "block";
  updateActiveMenu("categories");
  loadCategories();
}

function showEnrollments() {
  hideAllContent();
  document.getElementById("enrollments-content").style.display = "block";
  updateActiveMenu("enrollments");
  loadEnrollments();
}

function showEarnings() {
  hideAllContent();
  document.getElementById("earnings-content").style.display = "block";
  updateActiveMenu("earnings");
  loadEarnings();
}

function showMentorSettings() {
  hideAllContent();
  document.getElementById("mentor-settings-content").style.display = "block";
  updateActiveMenu("mentor-settings");
  loadMentorSettings();
}

function showCourseProgress() {
  hideAllContent();
  document.getElementById("course-progress-content").style.display = "block";
  updateActiveMenu("course-progress");
  loadCourseProgress();
}

function hideAllContent() {
  const contentSections = [
    "dashboard-content",
    "users-content",
    "courses-content",
    "categories-content",
    "enrollments-content",
    "earnings-content",
    "mentor-settings-content",
    "course-progress-content",
  ];

  contentSections.forEach((id) => {
    document.getElementById(id).style.display = "none";
  });
}

function updateActiveMenu(activeItem) {
  const menuItems = document.querySelectorAll(".sidebar-menu a");
  menuItems.forEach((item) => item.classList.remove("active"));

  const menuMap = {
    dashboard: 0,
    users: 1,
    courses: 2,
    categories: 3,
    enrollments: 4,
    earnings: 5,
    "mentor-settings": 6,
    "course-progress": 7,
  };

  if (menuMap[activeItem] !== undefined) {
    menuItems[menuMap[activeItem]].classList.add("active");
  }
}

// Data Loading Functions
async function loadDashboardData() {
  try {
    showLoadingIndicators();

    const [statsResponse, chartDataResponse] = await Promise.all([
      fetchData("stats"),
      fetchData("chart-data"),
    ]);

    if (statsResponse.success) {
      updateDashboardStats(statsResponse.data);
    }

    if (chartDataResponse.success) {
      updateCharts(chartDataResponse.data);
    }
  } catch (error) {
    console.error("Error loading dashboard data:", error);
    showErrorNotification("Gagal memuat data dashboard");
  } finally {
    hideLoading();
  }
}

function showLoadingIndicators() {
  document.querySelectorAll(".value").forEach((el) => {
    if (!el.querySelector(".loading")) {
      el.innerHTML = '<span class="loading"></span>';
    }
  });
}

function updateDashboardStats(data) {
  document.getElementById("total-users").textContent = data.total_users;
  document.getElementById("total-mentees").textContent = data.total_mentees;
  document.getElementById("total-mentors").textContent = data.total_mentors;
  document.getElementById("total-courses").textContent = data.total_courses;

  const lastUpdated = new Date(data.timestamp * 1000);
  document.getElementById(
    "last-updated"
  ).textContent = `Terakhir diperbarui: ${lastUpdated.toLocaleTimeString()}`;
}

function updateCharts(data) {
  // User distribution chart
  userDistributionChart.data.datasets[0].data = [
    data.user_distribution.find((d) => d.user_type === "Mentee")?.count || 0,
    data.user_distribution.find((d) => d.user_type === "Mentor")?.count || 0,
  ];
  userDistributionChart.update();

  // User growth chart
  const growthData = data.user_growth;
  userGrowthChart.data.labels = growthData.map((d) => d.date);
  userGrowthChart.data.datasets[0].data = growthData.map((d) => d.mentees);
  userGrowthChart.data.datasets[1].data = growthData.map((d) => d.mentors);

  // Update chart type based on period
  if (data.growth_period === "daily") {
    userGrowthChart.config.type = "line";
    userGrowthChart.options.scales.x.type = "category";
  } else if (data.growth_period === "monthly") {
    userGrowthChart.config.type = "bar";
    userGrowthChart.options.scales.x.type = "category";
  } else {
    // yearly
    userGrowthChart.config.type = "bar";
    userGrowthChart.options.scales.x.type = "category";
  }

  userGrowthChart.update();

  // Course status chart
  courseStatusChart.data.datasets[0].data = [
    data.course_status.find((d) => d.status === "Published")?.count || 0,
    data.course_status.find((d) => d.status === "Draft")?.count || 0,
    data.course_status.find((d) => d.status === "Archived")?.count || 0,
  ];
  courseStatusChart.update();

  // Course category chart
  const categories = data.course_category;
  courseCategoryChart.data.labels = categories.map((d) => d.category);
  courseCategoryChart.data.datasets[0].data = categories.map((d) => d.count);
  courseCategoryChart.data.datasets[0].backgroundColor = categories.map(
    (_, i) => {
      const hue = (i * 360) / categories.length;
      return `hsl(${hue}, 70%, 60%)`;
    }
  );
  courseCategoryChart.update();
}

// Add this new function to handle period changes
function changeGrowthPeriod(period) {
  // Update active button
  document.querySelectorAll(".btn-period").forEach((btn) => {
    btn.classList.remove("active");
    if (btn.textContent.toLowerCase().includes(period)) {
      btn.classList.add("active");
    }
  });

  // Reload chart data with new period
  loadDashboardData(period);
}

// Generic Data Loading Functions
async function fetchData(
  entity,
  id = null,
  method = "GET",
  body = null,
  params = {}
) {
  try {
    let url = `${API_BASE_URL}?entity=${entity}`;
    if (id) url += `&id=${id}`;

    // Add additional query parameters
    for (const [key, value] of Object.entries(params)) {
      url += `&${key}=${value}`;
    }

    const options = {
      method,
      headers: { "Content-Type": "application/json" },
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error(`Error fetching ${entity}:`, error);
    throw error;
  }
}

async function loadTableData(entity, displayFunction, errorMessage) {
  try {
    showLoading();
    const data = await fetchData(entity);

    if (data.success) {
      displayFunction(data.data);
    } else {
      displayFunction([]);
    }
  } catch (error) {
    console.error(`Error loading ${entity}:`, error);
    showErrorNotification(errorMessage);
    displayFunction([]);
  } finally {
    hideLoading();
  }
}

// User Management
async function loadUsers() {
  await loadTableData("users", displayUsers, "Gagal memuat data user");
}

function displayUsers(users) {
  const tableBody = document.querySelector("#users-table tbody");
  renderTable(
    tableBody,
    users,
    (user, index) => `
    <td>${index + 1}</td>
    <td>${user.username}</td>
    <td>${user.email}</td>
    <td>${user.user_type}</td>
    <td>${user.gender}</td>
    <td>${new Date(user.created_at).toLocaleDateString()}</td>
    <td class="action-buttons">
      <button class="btn btn-warning btn-sm" onclick="editUser(${user.id})">
        <i class="fas fa-edit"></i> Edit
      </button>
      <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
        <i class="fas fa-trash"></i> Hapus
      </button>
    </td>
  `,
    7,
    "Tidak ada data user yang tersedia"
  );
}

// Course Management
async function loadCourses() {
  await loadTableData("courses", displayCourses, "Gagal memuat data kursus");
}

function displayCourses(courses) {
  const tableBody = document.querySelector("#courses-table tbody");
  renderTable(
    tableBody,
    courses,
    (course, index) => {
      const statusClass = getStatusBadgeClass(course.status);
      return `
      <td>${index + 1}</td>
      <td><img src="${course.cover_image || "https://via.placeholder.com/50"}" 
           alt="Cover" style="width: 50px; height: auto;"></td>
      <td>${course.title}</td>
      <td>${course.mentor_name}</td>
      <td>${course.category}</td>
      <td>${course.difficulty}</td>
      <td><span class="badge ${statusClass}">${course.status}</span></td>
      <td>${course.is_premium ? "Ya" : "Tidak"}</td>
      <td class="action-buttons">
        <button class="btn btn-warning btn-sm" onclick="editCourse(${
          course.id
        })">
          <i class="fas fa-edit"></i> Edit
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteCourse(${
          course.id
        })">
          <i class="fas fa-trash"></i> Hapus
        </button>
      </td>
    `;
    },
    9,
    "Tidak ada data kursus yang tersedia"
  );
}

// Category Management
async function loadCategories() {
  await loadTableData(
    "categories",
    displayCategories,
    "Gagal memuat data kategori"
  );
}

function displayCategories(categories) {
  const tableBody = document.querySelector("#categories-table tbody");
  renderTable(
    tableBody,
    categories,
    (category, index) => `
    <td>${index + 1}</td>
    <td>${category.name}</td>
    <td>${category.slug}</td>
    <td>${category.description || "-"}</td>
    <td>${category.icon || "-"}</td>
    <td>${category.is_active ? "Aktif" : "Tidak Aktif"}</td>
    <td class="action-buttons">
      <button class="btn btn-warning btn-sm" onclick="editCategory(${
        category.id
      })">
        <i class="fas fa-edit"></i> Edit
      </button>
      <button class="btn btn-danger btn-sm" onclick="deleteCategory(${
        category.id
      })">
        <i class="fas fa-trash"></i> Hapus
      </button>
    </td>
  `,
    7,
    "Tidak ada data kategori yang tersedia"
  );
}

// Enrollment Management
async function loadEnrollments() {
  await loadTableData(
    "enrollments",
    displayEnrollments,
    "Gagal memuat data pendaftaran"
  );
}

function displayEnrollments(enrollments) {
  const tableBody = document.querySelector("#enrollments-table tbody");
  renderTable(
    tableBody,
    enrollments,
    (enrollment, index) => {
      const statusClass = getEnrollmentStatusBadgeClass(enrollment.status);
      const paymentClass = getPaymentStatusBadgeClass(
        enrollment.payment_status
      );

      return `
      <td>${index + 1}</td>
      <td>${enrollment.student_name}</td>
      <td>${enrollment.course_title}</td>
      <td>${new Date(enrollment.enrollment_date).toLocaleDateString()}</td>
      <td><span class="badge ${statusClass}">${enrollment.status}</span></td>
      <td><span class="badge ${paymentClass}">${
        enrollment.payment_status
      }</span></td>
      <td class="action-buttons">
        <button class="btn btn-warning btn-sm" onclick="editEnrollmentStatus(${
          enrollment.id
        })">
          <i class="fas fa-edit"></i> Edit Status
        </button>
      </td>
    `;
    },
    7,
    "Tidak ada data pendaftaran yang tersedia"
  );
}

// Earnings Management
async function loadEarnings() {
  await loadTableData(
    "earnings",
    displayEarnings,
    "Gagal memuat data penghasilan"
  );
}

function displayEarnings(earnings) {
  const tableBody = document.querySelector("#earnings-table tbody");
  renderTable(
    tableBody,
    earnings,
    (earning, index) => {
      return `
      <td>${index + 1}</td>
      <td>${earning.mentor_name || "-"}</td>
      <td>${earning.course_title || "-"}</td>
      <td>${earning.student_name || "-"}</td>
      <td>${earning.transaction_type}</td>
      <td>${formatCurrency(earning.amount)}</td>
      <td>${formatCurrency(earning.platform_fee)}</td>
      <td>${formatCurrency(earning.net_amount)}</td>
      <td><span class="badge ${getStatusBadgeClass(earning.status)}">${
        earning.status
      }</span></td>
      <td><span class="badge ${getPayoutBadgeClass(earning.payout_status)}">${
        earning.payout_status || "-"
      }</span></td>
      <td>${new Date(earning.created_at).toLocaleDateString()}</td>
      <td class="action-buttons">
        <button class="btn btn-warning btn-sm" onclick="editEarning(${
          earning.id
        })">
          <i class="fas fa-edit"></i> Edit Pembayaran
        </button>
      </td>
    `;
    },
    12,
    "Tidak ada data penghasilan yang tersedia"
  );
}

// Mentor Settings Management
async function loadMentorSettings() {
  await loadTableData(
    "mentor-settings",
    displayMentorSettings,
    "Gagal memuat pengaturan mentor"
  );
}

function displayMentorSettings(settings) {
  const tableBody = document.querySelector("#mentor-settings-table tbody");
  renderTable(
    tableBody,
    settings,
    (setting, index) => {
      const notifications = [
        setting.email_notifications ? "Email" : "",
        setting.push_notifications ? "Push" : "",
        setting.course_notifications ? "Kursus" : "",
        setting.review_notifications ? "Review" : "",
        setting.payment_notifications ? "Pembayaran" : "",
      ]
        .filter(Boolean)
        .join(", ");

      return `
      <td>${index + 1}</td>
      <td>${setting.mentor_name}</td>
      <td>${notifications}</td>
      <td>${setting.profile_visibility}</td>
      <td>${setting.language_preference}</td>
      <td>${setting.currency}</td>
      <td>${setting.payout_method}</td>
      <td>${setting.payout_schedule}</td>
    `;
    },
    8,
    "Tidak ada data pengaturan mentor yang tersedia"
  );
}

// Course Progress Management
async function loadCourseProgress() {
  await loadTableData(
    "course-progress",
    displayCourseProgress,
    "Gagal memuat progres kursus"
  );
}

function displayCourseProgress(progressItems) {
  const tableBody = document.querySelector("#course-progress-table tbody");
  renderTable(
    tableBody,
    progressItems,
    (progress, index) => {
      return `
      <td>${index + 1}</td>
      <td>${progress.student_name}</td>
      <td>${progress.course_title}</td>
      <td>${progress.lesson_title || "-"}</td>
      <td>
        <div class="progress-container">
          <div class="progress-bar" style="width: ${progress.progress}%"></div>
          <span>${progress.progress}%</span>
        </div>
      </td>
      <td>${progress.completed ? "Ya" : "Tidak"}</td>
      <td>${formatWatchTime(progress.watch_time)}</td>
      <td>${
        progress.last_accessed
          ? new Date(progress.last_accessed).toLocaleString()
          : "-"
      }</td>
    `;
    },
    8,
    "Tidak ada data progres kursus yang tersedia"
  );
}

// Generic Table Rendering
function renderTable(tableBody, items, rowTemplate, colSpan, emptyMessage) {
  tableBody.innerHTML = "";

  if (items && items.length > 0) {
    items.forEach((item, index) => {
      const row = document.createElement("tr");
      row.innerHTML = rowTemplate(item, index);
      tableBody.appendChild(row);
    });
  } else {
    const row = document.createElement("tr");
    row.innerHTML = `<td colspan="${colSpan}" class="text-center">${emptyMessage}</td>`;
    tableBody.appendChild(row);
  }
}

// Helper Functions
function formatCurrency(amount) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
  }).format(amount);
}

function formatWatchTime(seconds) {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  return `${hours}h ${minutes}m`;
}

function getStatusBadgeClass(status) {
  switch (status) {
    case "completed":
    case "Published":
    case "paid":
      return "badge-success";
    case "pending":
    case "Draft":
      return "badge-warning";
    case "cancelled":
    case "Archived":
      return "badge-danger";
    default:
      return "badge-info";
  }
}

function getPayoutBadgeClass(status) {
  switch (status) {
    case "paid":
      return "badge-success";
    case "pending":
      return "badge-warning";
    case "hold":
      return "badge-danger";
    default:
      return "badge-info";
  }
}

function getEnrollmentStatusBadgeClass(status) {
  switch (status) {
    case "active":
      return "badge-success";
    case "completed":
      return "badge-primary";
    case "dropped":
      return "badge-warning";
    case "suspended":
      return "badge-danger";
    default:
      return "badge-info";
  }
}

function getPaymentStatusBadgeClass(status) {
  switch (status) {
    case "paid":
      return "badge-success";
    case "free":
      return "badge-info";
    case "pending":
      return "badge-warning";
    case "refunded":
      return "badge-danger";
    default:
      return "badge-info";
  }
}

// CRUD Operations
// User CRUD
function openUserModal(user = null) {
  const modal = document.getElementById("user-modal");
  const title = document.getElementById("user-modal-title");
  const passwordFields = document.getElementById("password-fields");

  if (user) {
    title.textContent = "Edit User";
    document.getElementById("user-id").value = user.id;
    document.getElementById("username").value = user.username;
    document.getElementById("email").value = user.email;
    document.getElementById("user-type").value = user.user_type;
    document.getElementById("gender").value = user.gender;

    // Clear password and hide confirmation field
    document.getElementById("password").value = "";
    document.getElementById("confirm-password").value = "";
    passwordFields.style.display = "none";
  } else {
    title.textContent = "Tambah User";
    document.getElementById("user-form").reset();
    document.getElementById("user-id").value = "";
    passwordFields.style.display = "block";
  }

  modal.style.display = "flex";
}

async function saveUser() {
  const id = document.getElementById("user-id").value;
  const username = document.getElementById("username").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirm-password").value;
  const userType = document.getElementById("user-type").value;
  const gender = document.getElementById("gender").value;

  // Validation
  if (!username || username.length < 3) {
    showErrorNotification("Username harus diisi dan minimal 3 karakter!");
    return;
  }

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showErrorNotification("Email harus valid!");
    return;
  }

  if (!userType || !gender) {
    showErrorNotification("Role dan gender harus diisi!");
    return;
  }

  // Password validation only for new users or when password is provided
  if ((!id || password) && password !== confirmPassword) {
    showErrorNotification("Password dan konfirmasi password tidak cocok!");
    return;
  }

  if (!id && !password) {
    showErrorNotification("Password harus diisi untuk user baru!");
    return;
  }

  if (password && password.length < 6) {
    showErrorNotification("Password minimal 6 karakter!");
    return;
  }

  const userData = {
    username,
    email,
    user_type: userType,
    gender,
  };

  if (password) {
    userData.password = password;
  }

  try {
    showLoading();
    const method = id ? "PUT" : "POST";
    const data = await fetchData("users", id, method, userData);

    showSuccessNotification(
      id ? "User berhasil diperbarui" : "User berhasil ditambahkan"
    );
    closeModal("user-modal");
    await loadUsers();
    await loadDashboardData();
  } catch (error) {
    console.error("Error saving user:", error);
    showErrorNotification(error.message || "Gagal menyimpan user");
  } finally {
    hideLoading();
  }
}

async function editUser(id) {
  try {
    showLoading();
    const data = await fetchData("users", id);

    if (data.success) {
      openUserModal(data.data);
    } else {
      throw new Error(data.message || "Failed to fetch user data");
    }
  } catch (error) {
    console.error("Error fetching user:", error);
    showErrorNotification("Gagal memuat data user: " + error.message);
  } finally {
    hideLoading();
  }
}

async function deleteUser(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus user ini?")) {
    return;
  }

  try {
    showLoading();
    const data = await fetchData("users", id, "DELETE");

    showSuccessNotification("User berhasil dihapus");
    await loadUsers();
    await loadDashboardData();
  } catch (error) {
    console.error("Error deleting user:", error);
    showErrorNotification("Gagal menghapus user");
  } finally {
    hideLoading();
  }
}

// Course CRUD
async function openCourseModal(course = null) {
  const modal = document.getElementById("course-modal");
  const title = document.getElementById("course-modal-title");

  try {
    // Load mentors and categories in parallel
    const [mentorsResponse, categoriesResponse] = await Promise.all([
      fetchData("users"),
      fetchData("categories"),
    ]);

    if (mentorsResponse.success) {
      populateDropdown(
        "mentor-id",
        mentorsResponse.data.filter((u) => u.user_type === "Mentor"),
        "id",
        "username",
        "Pilih Mentor"
      );
    }

    if (categoriesResponse.success) {
      populateDropdown(
        "course-category",
        categoriesResponse.data,
        "name",
        "name",
        "Pilih Kategori"
      );
    }
  } catch (error) {
    console.error("Error loading dropdown data:", error);
  }

  if (course) {
    title.textContent = "Edit Kursus";
    document.getElementById("course-id").value = course.id;
    document.getElementById("mentor-id").value = course.mentor_id;
    document.getElementById("course-title").value = course.title;
    document.getElementById("course-slug").value = course.slug;
    document.getElementById("course-category").value = course.category;
    document.getElementById("course-difficulty").value = course.difficulty;
    document.getElementById("course-description").value = course.description;
    document.getElementById("course-cover").value = course.cover_image;
    document.getElementById("course-price").value = course.price;
    document.getElementById("course-is-premium").value = course.is_premium
      ? "1"
      : "0";
    document.getElementById("course-status").value = course.status;
    currentCourse = course;
  } else {
    title.textContent = "Tambah Kursus";
    document.getElementById("course-form").reset();
    document.getElementById("course-id").value = "";
    document.getElementById("course-slug").value = "";
    document.getElementById("course-is-premium").value = "0";
    document.getElementById("course-status").value = "Draft";
    currentCourse = null;
  }

  modal.style.display = "flex";
}

function populateDropdown(
  elementId,
  items,
  valueField,
  textField,
  placeholder
) {
  const select = document.getElementById(elementId);
  select.innerHTML = `<option value="">${placeholder}</option>`;

  items.forEach((item) => {
    const option = document.createElement("option");
    option.value = item[valueField];
    option.textContent = item[textField];
    select.appendChild(option);
  });
}

async function saveCourse() {
  const id = document.getElementById("course-id").value;
  const mentorId = document.getElementById("mentor-id").value;
  const title = document.getElementById("course-title").value.trim();
  const slug = document.getElementById("course-slug").value.trim();
  const category = document.getElementById("course-category").value;
  const difficulty = document.getElementById("course-difficulty").value;
  const description = document
    .getElementById("course-description")
    .value.trim();
  const coverImage = document.getElementById("course-cover").value.trim();
  const price = document.getElementById("course-price").value;
  const isPremium = document.getElementById("course-is-premium").value === "1";
  const status = document.getElementById("course-status").value;

  // Validation
  if (!mentorId) {
    showErrorNotification("Mentor harus dipilih!");
    return;
  }

  if (!title || title.length < 5) {
    showErrorNotification("Judul harus diisi dan minimal 5 karakter!");
    return;
  }

  if (!category || !difficulty || !status) {
    showErrorNotification(
      "Kategori, tingkat kesulitan dan status harus diisi!"
    );
    return;
  }

  if (!description || description.length < 10) {
    showErrorNotification("Deskripsi harus diisi dan minimal 10 karakter!");
    return;
  }

  try {
    showLoading();
    const method = id ? "PUT" : "POST";
    const courseData = {
      mentor_id: mentorId,
      title,
      slug,
      category,
      difficulty,
      description,
      cover_image: coverImage,
      price,
      is_premium: isPremium,
      status,
    };

    const data = await fetchData("courses", id, method, courseData);

    showSuccessNotification(
      id ? "Kursus berhasil diperbarui" : "Kursus berhasil ditambahkan"
    );
    closeModal("course-modal");
    await loadCourses();
    await loadDashboardData();
  } catch (error) {
    console.error("Error saving course:", error);
    showErrorNotification(`Gagal menyimpan kursus: ${error.message}`);
  } finally {
    hideLoading();
  }
}

async function editCourse(id) {
  try {
    showLoading();
    const data = await fetchData("courses", id);

    if (data.success) {
      openCourseModal(data.data);
    } else {
      throw new Error(data.message || "Course not found");
    }
  } catch (error) {
    console.error("Error editing course:", error);
    showErrorNotification("Gagal memuat data kursus");
  } finally {
    hideLoading();
  }
}

async function deleteCourse(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus kursus ini?")) {
    return;
  }

  try {
    showLoading();
    const data = await fetchData("courses", id, "DELETE");

    showSuccessNotification("Kursus berhasil dihapus");
    await loadCourses();
    await loadDashboardData();
  } catch (error) {
    console.error("Error deleting course:", error);
    showErrorNotification("Gagal menghapus kursus");
  } finally {
    hideLoading();
  }
}

// Category CRUD
function openCategoryModal(category = null) {
  const modal = document.getElementById("category-modal");
  const title = document.getElementById("category-modal-title");

  if (category) {
    title.textContent = "Edit Kategori";
    document.getElementById("category-id").value = category.id;
    document.getElementById("category-name").value = category.name;
    document.getElementById("category-slug").value = category.slug;
    document.getElementById("category-description").value =
      category.description || "";
    document.getElementById("category-icon").value = category.icon || "";
    document.getElementById("category-color").value =
      category.color || "#3A59D1";
    document.getElementById("category-active").value = category.is_active
      ? "1"
      : "0";
    currentCategory = category;
  } else {
    title.textContent = "Tambah Kategori";
    document.getElementById("category-form").reset();
    document.getElementById("category-id").value = "";
    document.getElementById("category-color").value = "#3A59D1";
    document.getElementById("category-active").value = "1";
    currentCategory = null;
  }

  modal.style.display = "flex";
}

async function saveCategory() {
  const id = document.getElementById("category-id").value;
  const name = document.getElementById("category-name").value.trim();
  const slug = document.getElementById("category-slug").value.trim();
  const description = document
    .getElementById("category-description")
    .value.trim();
  const icon = document.getElementById("category-icon").value.trim();
  const color = document.getElementById("category-color").value;
  const isActive = document.getElementById("category-active").value === "1";

  // Validation
  if (!name || name.length < 3) {
    showErrorNotification("Nama kategori harus diisi dan minimal 3 karakter!");
    return;
  }

  if (!color || !/^#[0-9A-F]{6}$/i.test(color)) {
    showErrorNotification("Warna harus dalam format hex (contoh: #3A59D1)!");
    return;
  }

  try {
    showLoading();
    const method = id ? "PUT" : "POST";
    const categoryData = {
      name,
      slug,
      description,
      icon,
      color,
      is_active: isActive,
      sort_order: 0,
    };

    const data = await fetchData("categories", id, method, categoryData);

    showSuccessNotification(
      id ? "Kategori berhasil diperbarui" : "Kategori berhasil ditambahkan"
    );
    closeModal("category-modal");
    await loadCategories();
    await loadDashboardData();
  } catch (error) {
    console.error("Error saving category:", error);
    showErrorNotification(`Gagal menyimpan kategori: ${error.message}`);
  } finally {
    hideLoading();
  }
}

async function editCategory(id) {
  try {
    showLoading();
    const data = await fetchData("categories", id);

    if (data.success) {
      openCategoryModal(data.data);
    } else {
      throw new Error(data.message || "Category not found");
    }
  } catch (error) {
    console.error("Error editing category:", error);
    showErrorNotification("Gagal memuat data kategori");
  } finally {
    hideLoading();
  }
}

async function deleteCategory(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus kategori ini?")) {
    return;
  }

  try {
    showLoading();
    const data = await fetchData("categories", id, "DELETE");

    showSuccessNotification("Kategori berhasil dihapus");
    await loadCategories();
    await loadDashboardData();
  } catch (error) {
    console.error("Error deleting category:", error);
    showErrorNotification("Gagal menghapus kategori");
  } finally {
    hideLoading();
  }
}

// Enrollment functions
async function editEnrollmentStatus(id) {
  const newStatus = prompt(
    "Masukkan status baru (active/completed/dropped/suspended):"
  );

  if (
    !newStatus ||
    !["active", "completed", "dropped", "suspended"].includes(newStatus)
  ) {
    showErrorNotification("Status tidak valid!");
    return;
  }

  try {
    showLoading();
    const data = await fetchData("enrollments", id, "PUT", {
      status: newStatus,
    });

    showSuccessNotification("Status pendaftaran berhasil diperbarui");
    await loadEnrollments();
  } catch (error) {
    console.error("Error updating enrollment:", error);
    showErrorNotification("Gagal memperbarui status pendaftaran");
  } finally {
    hideLoading();
  }
}

// Earnings functions
async function editEarning(id) {
  const newStatus = prompt(
    "Masukkan status pembayaran baru (pending/completed/cancelled):"
  );
  const newPayoutStatus = prompt(
    "Masukkan status payout baru (pending/paid/hold):"
  );

  if (
    !newStatus ||
    !["pending", "completed", "cancelled"].includes(newStatus)
  ) {
    showErrorNotification("Status tidak valid!");
    return;
  }

  if (
    !newPayoutStatus ||
    !["pending", "paid", "hold"].includes(newPayoutStatus)
  ) {
    showErrorNotification("Status payout tidak valid!");
    return;
  }

  try {
    showLoading();
    const data = await fetchData("earnings", id, "PUT", {
      status: newStatus,
      payout_status: newPayoutStatus,
    });

    showSuccessNotification("Status pembayaran berhasil diperbarui");
    await loadEarnings();
  } catch (error) {
    console.error("Error updating earning:", error);
    showErrorNotification("Gagal memperbarui status pembayaran");
  } finally {
    hideLoading();
  }
}

// UI Functions
function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

window.onclick = function (event) {
  if (event.target.className === "modal") {
    event.target.style.display = "none";
  }
};

function showLoading() {
  document.getElementById("loading-overlay").style.display = "flex";
}

function hideLoading() {
  document.getElementById("loading-overlay").style.display = "none";
}

function showSuccessNotification(message) {
  showToastNotification(message, "success");
}

function showErrorNotification(message) {
  showToastNotification(message, "error");
}

function showToastNotification(message, type) {
  const toast = document.createElement("div");
  toast.className = `toast-notification ${type}`;
  toast.innerHTML = `
    <i class="fas ${
      type === "success" ? "fa-check-circle" : "fa-exclamation-circle"
    }"></i>
    <span>${message}</span>
  `;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("show");
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, TOAST_DURATION);
  }, 100);
}
