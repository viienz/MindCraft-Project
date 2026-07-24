
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCraft - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/admin.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <h1>MindCraft</h1>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active" onclick="showDashboard()"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#" onclick="showUsers()"><i class="fas fa-users"></i> Data User</a></li>
                <li><a href="#" onclick="showCourses()"><i class="fas fa-book"></i> Data Kursus</a></li>
                <li><a href="#" onclick="showCategories()"><i class="fas fa-tags"></i> Kategori Kursus</a></li>
                <li><a href="#" onclick="showEnrollments()"><i class="fas fa-user-graduate"></i> Pendaftaran</a></li>
                <li><a href="#" onclick="showEarnings()"><i class="fas fa-money-bill-wave"></i> Penghasilan</a></li>
                <li><a href="#" onclick="showMentorSettings()"><i class="fas fa-cog"></i> Pengaturan Mentor</a></li>
                <li><a href="#" onclick="showCourseProgress()"><i class="fas fa-tasks"></i> Progres Kursus</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <!-- Dashboard Content -->
            <div id="dashboard-content">
                <div class="header">
                    <h2>Dashboard</h2>
                    <div class="user-info">
                        <div class="user-avatar" title="Admin">A</div>
                        <div class="user-details">
                            <span class="username">Admin</span>
                            <span class="last-updated" id="last-updated"></span>
                        </div>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total User</h3>
                            <div class="value" id="total-users"><span class="loading"></span></div>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon mentee">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Mentee</h3>
                            <div class="value" id="total-mentees"><span class="loading"></span></div>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon mentor">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Mentor</h3>
                            <div class="value" id="total-mentors"><span class="loading"></span></div>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon content">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Kursus</h3>
                            <div class="value" id="total-courses"><span class="loading"></span></div>
                        </div>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card">
                        <h2><i class="fas fa-chart-pie"></i> Distribusi User</h2>
                        <div class="chart-container">
                            <canvas id="userDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h2><i class="fas fa-chart-line"></i> Pertumbuhan User</h2>
                        <div class="chart-header">
                            <div class="period-selector">
                                <button class="btn-period active" onclick="changeGrowthPeriod('daily')">Harian</button>
                                <button class="btn-period" onclick="changeGrowthPeriod('monthly')">Bulanan</button>
                                <button class="btn-period" onclick="changeGrowthPeriod('yearly')">Tahunan</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h2><i class="fas fa-chart-pie"></i> Status Kursus</h2>
                        <div class="chart-container">
                            <canvas id="courseStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h2><i class="fas fa-chart-bar"></i> Kategori Kursus</h2>
                        <div class="chart-container">
                            <canvas id="courseCategoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System Information Card -->
                <div class="system-info-card">
                    <h3><i class="fas fa-server"></i> Sistem yang Digunakan</h3>
                    <div class="system-info-grid">
                        <div>
                            <strong>Framework:</strong> Custom PHP
                        </div>
                        <div>
                            <strong>Backend:</strong> PHP
                        </div>
                        <div>
                            <strong>Database:</strong> MySQL
                        </div>
                        <div>
                            <strong>Frontend:</strong> Html, Css(Bootstrap), JavaScript
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Content -->
            <div id="users-content" style="display: none;">
                <div class="header">
                    <h2>Data User</h2>
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="users-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Tipe User</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Tanggal Daftar</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Courses Content -->
            <div id="courses-content" style="display: none;">
                <div class="header">
                    <h2>Data Kursus</h2>
                    <button class="btn btn-primary" onclick="openCourseModal()">
                        <i class="fas fa-plus"></i> Tambah Kursus
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="courses-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="10%">Cover</th>
                                    <th>Judul Kursus</th>
                                    <th>Mentor</th>
                                    <th>Kategori</th>
                                    <th>Kesulitan</th>
                                    <th>Status</th>
                                    <th>Premium</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Categories Content -->
            <div id="categories-content" style="display: none;">
                <div class="header">
                    <h2>Kategori Kursus</h2>
                    <button class="btn btn-primary" onclick="openCategoryModal()">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="categories-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama</th>
                                    <th>Slug</th>
                                    <th>Deskripsi</th>
                                    <th>Icon</th>
                                    <th>Aktif</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Enrollments Content -->
            <div id="enrollments-content" style="display: none;">
                <div class="header">
                    <h2>Data Pendaftaran</h2>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="enrollments-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Mentee</th>
                                    <th>Kursus</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Earnings Content -->
            <div id="earnings-content" style="display: none;">
                <div class="header">
                    <h2>Data Penghasilan Mentor</h2>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="earnings-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Mentor</th>
                                    <th>Kursus</th>
                                    <th>Siswa</th>
                                    <th>Tipe</th>
                                    <th>Jumlah</th>
                                    <th>Fee Platform</th>
                                    <th>Pendapatan Bersih</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th>Tanggal</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mentor Settings Content -->
            <div id="mentor-settings-content" style="display: none;">
                <div class="header">
                    <h2>Pengaturan Mentor</h2>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="mentor-settings-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Mentor</th>
                                    <th>Notifikasi</th>
                                    <th>Visibilitas</th>
                                    <th>Bahasa</th>
                                    <th>Mata Uang</th>
                                    <th>Metode Pembayaran</th>
                                    <th>Jadwal Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Course Progress Content -->
            <div id="course-progress-content" style="display: none;">
                <div class="header">
                    <h2>Progres Kursus Siswa</h2>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table id="course-progress-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Siswa</th>
                                    <th>Kursus</th>
                                    <th>Pelajaran</th>
                                    <th>Progres</th>
                                    <th>Selesai</th>
                                    <th>Waktu Tonton</th>
                                    <th>Terakhir Diakses</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="user-modal-title">Tambah User</h3>
                <button class="close" onclick="closeModal('user-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" required>
                        <small class="form-text text-muted">Minimal 3 karakter</small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div id="password-fields">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password">
                            <small class="form-text text-muted">Minimal 6 karakter</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirm-password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user-type">Tipe User</label>
                        <select class="form-control" id="user-type" required>
                            <option value="">Pilih Tipe User</option>
                            <option value="Mentee">Mentee</option>
                            <option value="Mentor">Mentor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender">Jenis Kelamin</label>
                        <select class="form-control" id="gender" required>
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('user-modal')">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Course Modal -->
    <div id="course-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="course-modal-title">Tambah Kursus</h3>
                <button class="close" onclick="closeModal('course-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="course-form">
                    <input type="hidden" id="course-id">
                    <div class="form-group">
                        <label for="mentor-id">Mentor</label>
                        <select class="form-control" id="mentor-id" required>
                            <option value="">Pilih Mentor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course-title">Judul Kursus</label>
                        <input type="text" class="form-control" id="course-title" required>
                    </div>
                    <div class="form-group">
                        <label for="course-slug">Slug</label>
                        <input type="text" class="form-control" id="course-slug">
                        <small class="form-text text-muted">URL-friendly version of the title (auto-generated)</small>
                    </div>
                    <div class="form-group">
                        <label for="course-category">Kategori</label>
                        <select class="form-control" id="course-category" required>
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course-difficulty">Tingkat Kesulitan</label>
                        <select class="form-control" id="course-difficulty" required>
                            <option value="Pemula">Pemula</option>
                            <option value="Menengah">Menengah</option>
                            <option value="Mahir">Mahir</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course-description">Deskripsi</label>
                        <textarea class="form-control" id="course-description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="course-cover">Cover Image URL</label>
                        <input type="text" class="form-control" id="course-cover">
                    </div>
                    <div class="form-group">
                        <label for="course-price">Harga (IDR)</label>
                        <input type="number" class="form-control" id="course-price" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="course-is-premium">Premium Course</label>
                        <select class="form-control" id="course-is-premium">
                            <option value="0">Tidak</option>
                            <option value="1">Ya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course-status">Status</label>
                        <select class="form-control" id="course-status" required>
                            <option value="Draft">Draft</option>
                            <option value="Published">Published</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('course-modal')">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveCourse()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="category-modal-title">Tambah Kategori</h3>
                <button class="close" onclick="closeModal('category-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="category-form">
                    <input type="hidden" id="category-id">
                    <div class="form-group">
                        <label for="category-name">Nama Kategori</label>
                        <input type="text" class="form-control" id="category-name" required>
                    </div>
                    <div class="form-group">
                        <label for="category-slug">Slug</label>
                        <input type="text" class="form-control" id="category-slug">
                        <small class="form-text text-muted">URL-friendly version of the name (auto-generated)</small>
                    </div>
                    <div class="form-group">
                        <label for="category-description">Deskripsi</label>
                        <textarea class="form-control" id="category-description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category-icon">Icon</label>
                        <input type="text" class="form-control" id="category-icon">
                    </div>
                    <div class="form-group">
                        <label for="category-color">Warna (Hex)</label>
                        <input type="color" class="form-control" id="category-color" value="#3A59D1">
                    </div>
                    <div class="form-group">
                        <label for="category-active">Status Aktif</label>
                        <select class="form-control" id="category-active">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('category-modal')">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Simpan</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="/MindCraft-Project/assets/js/admin.js"></script>
</body>
</html>