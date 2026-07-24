// mentee-dashboard.js
const courseDetailModal = document.getElementById("course-detail-modal");

window.appUtils = window.appUtils || {};
window.appUtils.openModal = function (modalElement) {
  if (modalElement) {
    modalElement.classList.add("open");
  }
};

// DOM Elements
const coursesContainer = document.getElementById("courses-container");
const enrolledCoursesContainer = document.getElementById(
  "enrolled-courses-container"
);
const courseFilters = {
  category: document.getElementById("category-filter"),
  level: document.getElementById("level-filter"),
  price: document.getElementById("price-filter"),
};

// mentee-dashboard.js
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tabs
  const tabLinks = document.querySelectorAll(".tab-link");
  const tabContents = document.querySelectorAll(".tab-content");

  tabLinks.forEach((link) => {
    link.addEventListener("click", function () {
      const targetTab = this.getAttribute("data-tab");

      // Remove active class from all tabs and contents
      tabLinks.forEach((l) => l.classList.remove("active"));
      tabContents.forEach((c) => c.classList.remove("active"));

      // Add active class to clicked tab and corresponding content
      this.classList.add("active");
      document.getElementById(targetTab).classList.add("active");
    });
  });

  document.querySelectorAll(".course-card").forEach((card) => {
    card.addEventListener("click", function (e) {
      // Don't handle click if it's on a button or link inside the card
      if (
        e.target.tagName === "BUTTON" ||
        e.target.tagName === "A" ||
        e.target.closest("button") ||
        e.target.closest("a")
      ) {
        return;
      }

      const courseId = this.getAttribute("data-course-id");

      // Check if we're on the course detail page already
      if (window.location.pathname.includes("course-detail.php")) {
        // Open modal if on detail page
        fetchCourseDetails(courseId);
      } else {
        // Navigate to detail page if on listing page
        window.location.href = `course-detail.php?id=${courseId}`;
      }
    });
  });

  // Close modal
  document.querySelector(".modal-close").addEventListener("click", function () {
    courseDetailModal.classList.remove("open");
  });
});

// Fetch courses from API
async function fetchCourses() {
  try {
    const response = await fetch("/MindCraft-Project/api/courses.php");
    const data = await response.json();

    if (data.success) {
      // Transform database data to match frontend structure
      const transformedCourses = data.courses.map((course) => ({
        id: course.id,
        title: course.title,
        description: course.description,
        image:
          course.cover_image ||
          "https://images.pexels.com/photos/6476260/pexels-photo-6476260.jpeg",
        category: course.category.toLowerCase(),
        level: getDifficultyLevel(course.difficulty),
        price: course.is_premium ? "premium" : "free",
        priceAmount: parseFloat(course.price),
        duration: course.duration_hours
          ? `${course.duration_hours} jam`
          : "0 jam",
        lessons: course.total_lessons || 0,
        students: course.total_enrollments || 0,
        rating: parseFloat(course.avg_rating) || 0,
        instructor: {
          name: "Instruktur", // Will be fetched from mentor data
          avatar: "https://randomuser.me/api/portraits/men/32.jpg",
          bio: "Instruktur profesional",
        },
        progress: 0,
        modules: [], // Will be fetched separately
      }));

      renderCourses(transformedCourses);

      // If user is logged in, fetch enrolled courses
      if (document.getElementById("user-avatar")) {
        fetchEnrolledCourses();
      }
    } else {
      console.error("Failed to fetch courses:", data.message);
      renderCourses([]);
    }
  } catch (error) {
    console.error("Error fetching courses:", error);
    renderCourses([]);
  }
}

// Fetch enrolled courses for the user
async function fetchEnrolledCourses() {
  try {
    const response = await fetch("/MindCraft-Project/api/enrollments.php");
    const data = await response.json();

    if (data.success) {
      renderEnrolledCourses(data.enrollments);
    } else {
      console.error("Failed to fetch enrolled courses:", data.message);
      renderEnrolledCourses([]);
    }
  } catch (error) {
    console.error("Error fetching enrolled courses:", error);
    renderEnrolledCourses([]);
  }
}

// Helper function to convert difficulty level
function getDifficultyLevel(difficulty) {
  switch (difficulty) {
    case "Pemula":
      return "beginner";
    case "Menengah":
      return "intermediate";
    case "Mahir":
      return "advanced";
    default:
      return "beginner";
  }
}

// Render courses
function renderCourses(coursesToRender) {
  if (!coursesContainer) return;

  // Clear the container
  coursesContainer.innerHTML = "";

  if (coursesToRender.length === 0) {
    coursesContainer.innerHTML = `
      <div class="empty-state">
        <img src="/MindCraft-Project/assets/img/empty-search.svg" alt="Tidak ada kursus">
        <h3>Tidak ada kursus yang ditemukan</h3>
        <p>Silakan coba filter lain atau reset filter untuk melihat semua kursus</p>
        <button class="btn btn-primary" id="reset-filters">Reset Filter</button>
      </div>
    `;

    document
      .getElementById("reset-filters")
      ?.addEventListener("click", function () {
        resetFilters();
      });

    return;
  }

  // Create course cards
  coursesToRender.forEach((course) => {
    const courseCard = createCourseCard(course);
    coursesContainer.appendChild(courseCard);
  });

  // Add event listeners to course cards
  addCourseCardListeners();
}

// Create course card
function createCourseCard(course) {
  const courseCard = document.createElement("div");
  courseCard.className = "course-card";
  courseCard.setAttribute("data-course-id", course.id);

  courseCard.innerHTML = `
    <div class="course-image">
      <img src="${course.image}" alt="${course.title}">
      <div class="course-badges">
        <span class="badge badge-level">${getLevelText(course.level)}</span>
        <span class="badge badge-price ${course.price}">${getPriceText(
    course.price
  )}</span>
      </div>
    </div>
    <div class="course-content">
      <h3 class="course-title">${course.title}</h3>
      <p class="course-description">${course.description}</p>
      <div class="course-meta">
        <div class="meta-item">
          <i class="fas fa-clock"></i>
          <span>${course.duration}</span>
        </div>
        <div class="meta-item">
          <i class="fas fa-film"></i>
          <span>${course.lessons} Pelajaran</span>
        </div>
      </div>
      <div class="course-footer">
        <div class="instructor">
          <img src="${course.instructor.avatar}" alt="${
    course.instructor.name
  }" class="instructor-avatar">
          <span class="instructor-name">${course.instructor.name}</span>
        </div>
        <div class="course-price ${course.price}">${getPriceText(
    course.price
  )}</div>
      </div>
    </div>
  `;

  return courseCard;
}

// Add event listeners to course cards
function addCourseCardListeners() {
  const courseCards = document.querySelectorAll(".course-card");

  courseCards.forEach((card) => {
    card.addEventListener("click", function () {
      const courseId = this.getAttribute("data-course-id");
      fetchCourseDetails(courseId);
    });
  });
}

// Fetch course details from API
async function fetchCourseDetails(courseId) {
  try {
    const response = await fetch(
      `/MindCraft-Project/api/courses.php?id=${courseId}`
    );
    const data = await response.json();

    if (data.success) {
      const course = data.course;
      const transformedCourse = {
        id: course.id,
        title: course.title,
        description: course.description,
        image:
          course.cover_image ||
          "https://images.pexels.com/photos/6476260/pexels-photo-6476260.jpeg",
        category: course.category.toLowerCase(),
        level: getDifficultyLevel(course.difficulty),
        price: course.is_premium ? "premium" : "free",
        priceAmount: parseFloat(course.price),
        duration: course.duration_hours
          ? `${course.duration_hours} jam`
          : "0 jam",
        lessons: course.total_lessons || 0,
        students: course.total_enrollments || 0,
        rating: parseFloat(course.avg_rating) || 0,
        instructor: {
          name: "Instruktur", // Will be fetched from mentor data
          avatar: "https://randomuser.me/api/portraits/men/32.jpg",
          bio: "Instruktur profesional",
        },
        progress: 0,
        modules: [], // Will be fetched from modules data
      };

      showCourseDetails(transformedCourse);
    } else {
      console.error("Failed to fetch course details:", data.message);
      alert("Gagal memuat detail kursus");
    }
  } catch (error) {
    console.error("Error fetching course details:", error);
    alert("Terjadi kesalahan saat memuat detail kursus");
  }
}

// Show course details in modal
function showCourseDetails(course) {
  // Update modal content
  document.getElementById("modal-course-title").textContent = course.title;
  document.getElementById("modal-course-image").src = course.image;
  document.getElementById("modal-course-level").textContent = getLevelText(
    course.level
  );
  document.getElementById("modal-course-price").textContent = getPriceText(
    course.price
  );
  document.getElementById(
    "modal-course-price"
  ).className = `badge badge-price ${course.price}`;
  document.getElementById("modal-course-duration").textContent =
    course.duration;
  document.getElementById(
    "modal-course-lessons"
  ).textContent = `${course.lessons} Pelajaran`;
  document.getElementById(
    "modal-course-students"
  ).textContent = `${course.students.toLocaleString()} Siswa`;
  document.getElementById("modal-course-rating").textContent = course.rating;
  document.getElementById("modal-instructor-avatar").src =
    course.instructor.avatar;
  document.getElementById("modal-instructor-name").textContent =
    course.instructor.name;
  document.getElementById("modal-course-description").textContent =
    course.description;

  // Update instructor support modal
  document.getElementById("support-instructor-avatar").src =
    course.instructor.avatar;
  document.getElementById("support-instructor-name").textContent =
    course.instructor.name;
  document.getElementById("support-instructor-bio").textContent =
    course.instructor.bio;

  // Update enroll button based on price
  const enrollBtn = document.getElementById("enroll-btn");
  if (course.price === "free") {
    enrollBtn.textContent = "Ikuti Kursus Gratis";
  } else {
    enrollBtn.textContent = `Beli Kursus (${window.appUtils.formatCurrency(
      course.priceAmount
    )})`;
  }

  // Render modules (would be fetched from API in a real implementation)
  renderModules(course);

  // Render comments (would be fetched from API in a real implementation)
  renderComments(course);

  // Open the modal
  window.appUtils.openModal(courseDetailModal);

  // Add event listener to enroll button
  enrollBtn.onclick = function () {
    enrollInCourse(course);
  };
}

// Render modules in course detail
function renderModules(course) {
  const modulesContainer = document.getElementById("modal-course-modules");

  if (!modulesContainer) return;

  // Clear the container
  modulesContainer.innerHTML = "";

  // In a real implementation, this would be fetched from the API
  const placeholderModules = [
    {
      title: "Pengenalan Kursus",
      lessons: [
        {
          title: "Pengenalan Materi",
          duration: "10 menit",
          type: "video",
          locked: false,
        },
        {
          title: "Persiapan Belajar",
          duration: "15 menit",
          type: "video",
          locked: false,
        },
      ],
    },
  ];

  // Create modules
  placeholderModules.forEach((module, index) => {
    const moduleEl = document.createElement("div");
    moduleEl.className = "module";
    if (index === 0) moduleEl.classList.add("open");

    // Module header
    const moduleHeader = document.createElement("div");
    moduleHeader.className = "module-header";
    moduleHeader.innerHTML = `
      <div class="module-title">
        <i class="fas fa-chevron-right"></i>
        ${module.title}
      </div>
      <div class="module-meta">
        <span>${module.lessons.length} Pelajaran</span>
      </div>
    `;

    // Module content
    const moduleContent = document.createElement("div");
    moduleContent.className = "module-content";

    // Create lessons
    module.lessons.forEach((lesson) => {
      const lessonEl = document.createElement("div");
      lessonEl.className = `lesson ${lesson.locked ? "lesson-locked" : ""}`;

      let icon;
      switch (lesson.type) {
        case "video":
          icon = "fa-play-circle";
          break;
        case "practice":
          icon = "fa-laptop-code";
          break;
        case "project":
          icon = "fa-project-diagram";
          break;
        default:
          icon = "fa-file-alt";
      }

      lessonEl.innerHTML = `
        <div class="lesson-icon">
          <i class="fas ${icon}"></i>
        </div>
        <div class="lesson-title">${lesson.title}</div>
        <div class="lesson-duration">${lesson.duration}</div>
        ${
          lesson.locked
            ? '<div class="lesson-lock"><i class="fas fa-lock"></i></div>'
            : ""
        }
      `;

      moduleContent.appendChild(lessonEl);
    });

    moduleEl.appendChild(moduleHeader);
    moduleEl.appendChild(moduleContent);
    modulesContainer.appendChild(moduleEl);

    // Add click event to module header
    moduleHeader.addEventListener("click", function () {
      moduleEl.classList.toggle("open");
    });
  });
}

// Render comments in course detail
function renderComments(course) {
  const commentsContainer = document.getElementById("modal-comments-list");

  if (!commentsContainer) return;

  // Clear the container
  commentsContainer.innerHTML = "";

  // In a real implementation, this would be fetched from the API
  const placeholderComments = [
    {
      author: "Pengguna",
      avatar: "https://randomuser.me/api/portraits/men/1.jpg",
      date: new Date().toISOString().split("T")[0],
      content: "Kursus ini sangat bermanfaat!",
      likes: 5,
      role: "student",
      replies: [],
    },
  ];

  if (placeholderComments.length === 0) {
    commentsContainer.innerHTML = `
      <div class="empty-forum">
        <h3>Belum ada diskusi</h3>
        <p>Jadilah yang pertama memulai diskusi tentang kursus ini</p>
      </div>
    `;
    return;
  }

  // Create comments
  placeholderComments.forEach((comment) => {
    const commentEl = document.createElement("div");
    commentEl.className = "comment";

    commentEl.innerHTML = `
      <img src="${comment.avatar}" alt="${
      comment.author
    }" class="comment-avatar">
      <div class="comment-body">
        <div class="comment-header">
          <div class="comment-info">
            <div class="comment-author">${
              comment.author
            } <span class="comment-role ${comment.role}">${getRoleText(
      comment.role
    )}</span></div>
            <div class="comment-meta">${window.appUtils.formatDate(
              comment.date
            )}</div>
          </div>
          <div class="comment-actions">
            <span class="comment-action">Laporkan</span>
          </div>
        </div>
        <div class="comment-content">${comment.content}</div>
        <div class="comment-footer">
          <span class="comment-reaction ${comment.liked ? "active" : ""}">
            <i class="far fa-thumbs-up"></i> ${comment.likes}
          </span>
          <span class="comment-reaction">
            <i class="far fa-comment"></i> Balas
          </span>
        </div>
        
        ${
          comment.replies && comment.replies.length > 0
            ? `
          <div class="reply-list">
            ${comment.replies
              .map(
                (reply) => `
              <div class="comment">
                <img src="${reply.avatar}" alt="${
                  reply.author
                }" class="comment-avatar">
                <div class="comment-body">
                  <div class="comment-header">
                    <div class="comment-info">
                      <div class="comment-author">${
                        reply.author
                      } <span class="comment-role ${reply.role}">${getRoleText(
                  reply.role
                )}</span></div>
                      <div class="comment-meta">${window.appUtils.formatDate(
                        reply.date
                      )}</div>
                    </div>
                  </div>
                  <div class="comment-content">${reply.content}</div>
                  <div class="comment-footer">
                    <span class="comment-reaction ${
                      reply.liked ? "active" : ""
                    }">
                      <i class="far fa-thumbs-up"></i> ${reply.likes}
                    </span>
                  </div>
                </div>
              </div>
            `
              )
              .join("")}
          </div>
        `
            : ""
        }
      </div>
    `;

    commentsContainer.appendChild(commentEl);
  });
}

// Initialize course filters
function initFilters() {
  // Add event listeners to filter dropdowns
  Object.values(courseFilters).forEach((filter) => {
    if (filter) {
      filter.addEventListener("change", function () {
        applyFilters();
      });
    }
  });
}

// Apply filters to courses
async function applyFilters() {
  const categoryFilter = courseFilters.category
    ? courseFilters.category.value
    : "all";
  const levelFilter = courseFilters.level ? courseFilters.level.value : "all";
  const priceFilter = courseFilters.price ? courseFilters.price.value : "all";

  try {
    // Fetch filtered courses from API
    const response = await fetch(
      `/MindCraft-Project/api/courses.php?category=${categoryFilter}&level=${levelFilter}&price=${priceFilter}`
    );
    const data = await response.json();

    if (data.success) {
      const transformedCourses = data.courses.map((course) => ({
        id: course.id,
        title: course.title,
        description: course.description,
        image:
          course.cover_image ||
          "https://images.pexels.com/photos/6476260/pexels-photo-6476260.jpeg",
        category: course.category.toLowerCase(),
        level: getDifficultyLevel(course.difficulty),
        price: course.is_premium ? "premium" : "free",
        priceAmount: parseFloat(course.price),
        duration: course.duration_hours
          ? `${course.duration_hours} jam`
          : "0 jam",
        lessons: course.total_lessons || 0,
        students: course.total_enrollments || 0,
        rating: parseFloat(course.avg_rating) || 0,
        instructor: {
          name: "Instruktur",
          avatar: "https://randomuser.me/api/portraits/men/32.jpg",
          bio: "Instruktur profesional",
        },
        progress: 0,
        modules: [],
      }));

      renderCourses(transformedCourses);
    } else {
      console.error("Failed to fetch filtered courses:", data.message);
      renderCourses([]);
    }
  } catch (error) {
    console.error("Error fetching filtered courses:", error);
    renderCourses([]);
  }
}

// Reset filters
function resetFilters() {
  Object.values(courseFilters).forEach((filter) => {
    if (filter) {
      filter.value = "all";
    }
  });

  fetchCourses();
}

// Initialize course detail functionality
function initCourseDetails() {
  // Tabs in course detail
  const courseTabLinks = document.querySelectorAll(".course-tab-link");
  const courseTabContents = document.querySelectorAll(".course-tab-content");

  courseTabLinks.forEach((tabLink) => {
    tabLink.addEventListener("click", function () {
      // Remove active class from all tabs
      courseTabLinks.forEach((tab) => tab.classList.remove("active"));

      // Add active class to current tab
      this.classList.add("active");

      // Hide all tab content
      courseTabContents.forEach((content) =>
        content.classList.remove("active")
      );

      // Show the corresponding tab content
      const tabId = this.getAttribute("data-tab");
      document.getElementById(tabId + "-content").classList.add("active");
    });
  });
}

// Close modal on close button click
document.querySelectorAll(".modal-close").forEach((btn) => {
  btn.addEventListener("click", function () {
    const modal = btn.closest(".modal");
    window.appUtils.closeModal(modal);
  });
});

// Enroll in a course
async function enrollInCourse(course) {
  // In a real app, this would involve API calls, payment processing, etc.
  if (course.price === "premium") {
    // Show payment modal or redirect to payment page
    alert("Redirecting to payment page for premium course...");
    return;
  }

  try {
    const response = await fetch("/MindCraft-Project/api/enroll.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        course_id: course.id,
      }),
    });

    const data = await response.json();

    if (data.success) {
      // Close the modal
      window.appUtils.closeModal(courseDetailModal);

      // Switch to My Courses tab
      document.querySelector('.tab-link[data-tab="my-courses"]').click();

      // Refresh enrolled courses
      fetchEnrolledCourses();

      // Show success message
      alert("Berhasil mendaftar kursus!");
    } else {
      alert(data.message || "Gagal mendaftar kursus");
    }
  } catch (error) {
    console.error("Error enrolling in course:", error);
    alert("Terjadi kesalahan saat mendaftar kursus");
  }
}

// Render enrolled courses
function renderEnrolledCourses(enrollments) {
  if (!enrolledCoursesContainer) return;

  // Clear the container
  enrolledCoursesContainer.innerHTML = "";

  if (enrollments.length === 0) {
    enrolledCoursesContainer.innerHTML = `
      <div class="empty-state">
        <img src="/MindCraft-Project/assets/img/empty-courses.svg" alt="Belum ada kursus">
        <h3>Anda belum mengikuti kursus apapun</h3>
        <p>Jelajahi katalog kursus kami dan mulai perjalanan belajar Anda</p>
        <button class="btn btn-primary" onclick="window.location.href='kursus.php'">Jelajahi Kursus</button>
      </div>
    `;
    return;
  }

  // Create enrolled course cards
  enrollments.forEach((enrollment) => {
    const courseCard = document.createElement("div");
    courseCard.className = "enrolled-course-card";

    courseCard.innerHTML = `
      <div class="enrolled-course-content">
        <div class="enrolled-header">
          <div class="enrolled-image">
            <img src="${
              enrollment.course.cover_image ||
              "https://images.pexels.com/photos/6476260/pexels-photo-6476260.jpeg"
            }" alt="${enrollment.course.title}">
          </div>
          <div class="enrolled-info">
            <h3 class="enrolled-title">${enrollment.course.title}</h3>
            <div class="enrolled-meta">
              <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span>${enrollment.course.duration_hours || 0} Jam</span>
              </div>
              <div class="meta-item">
                <i class="fas fa-film"></i>
                <span>${enrollment.course.total_lessons || 0} Pelajaran</span>
              </div>
            </div>
            <div class="progress-wrapper">
              <div class="progress-bar">
                <div class="progress-value" style="width: ${
                  enrollment.progress_percentage || 0
                }%"></div>
              </div>
              <div class="progress-text">
                <span>Progress: ${enrollment.progress_percentage || 0}%</span>
                <span>${
                  Math.round((enrollment.progress_percentage || 0) / 100) *
                  (enrollment.course.total_lessons || 0)
                } / ${enrollment.course.total_lessons || 0} pelajaran</span>
              </div>
            </div>
          </div>
        </div>
        <div class="enrolled-footer">
          <button class="btn btn-primary continue-btn" data-course-id="${
            enrollment.course.id
          }">Lanjutkan Belajar</button>
          <div class="last-activity">Aktivitas terakhir: ${window.appUtils.formatDate(
            enrollment.last_accessed || enrollment.enrollment_date
          )}</div>
        </div>
      </div>
    `;

    enrolledCoursesContainer.appendChild(courseCard);
  });

  // Add event listeners to continue buttons
  document.querySelectorAll(".continue-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const courseId = this.getAttribute("data-course-id");
      // In a real implementation, this would redirect to the course player
      alert(`Membuka kursus dengan ID: ${courseId}`);
    });
  });
}

// Helper functions
function getLevelText(level) {
  switch (level) {
    case "beginner":
      return "Pemula";
    case "intermediate":
      return "Menengah";
    case "advanced":
      return "Mahir";
    default:
      return level;
  }
}

function getPriceText(price) {
  switch (price) {
    case "free":
      return "Gratis";
    case "premium":
      return "Premium";
    default:
      return price;
  }
}

function getRoleText(role) {
  switch (role) {
    case "student":
      return "Siswa";
    case "mentor":
      return "Mentor";
    case "admin":
      return "Admin";
    default:
      return role;
  }
}

window.appUtils = {
  openModal(modal) {
    modal.classList.add("open");
  },
  closeModal(modal) {
    modal.classList.remove("open");
  },
  formatDate(date) {
    return new Date(date).toLocaleDateString("id-ID", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  },
  formatCurrency(amount) {
    return "Rp " + amount.toLocaleString("id-ID");
  },
};
