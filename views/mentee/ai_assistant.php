<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>MindBot - AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/MindCraft-Project/assets/css/llm_chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>

    <div class="main-layout-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="/MindCraft-Project/assets/img/llmlogo.png" alt="MindCraft Logo" class="sidebar-logo">
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="/MindCraft-Project/views/mentee/kursus.php" title="Beranda"><i class="fas fa-home"></i></a></li>
                    <li><a href="#" id="sidebar-info-button" title="Bagaimana cara menggunakan MindBot?"><i class="fas fa-info-circle"></i></a></li>
                    <li><a href="#" id="sidebar-refresh-chat" title="Mulai Chat Baru"><i class="fas fa-redo-alt"></i></a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content-area">
            <div class="top-header-bar">
                <div class="app-name">MindBot 2.0</div>
                <a href="/MindCraft-Project/views/mentee/kursus.php" class="new-chat-button" title="Jelajahi Kursus Kami">
                    <i class="fas fa-book-open"></i> Jelajahi Kursus
                </a>
            </div>

            <div class="chatbot-container">
                <div id="chat-box" class="chat-box">
                    <div class="chatbot-header">
                        <h1>👋 Hi, MindBot here!</h1>
                        <p>Bingung mulai dari mana? MindBot akan membimbingmu menemukan kursus dan menyusun roadmap belajar anda.</p>
                        <div class="quick-option-chips-container">
                            <span class="quick-option-chip">Python untuk Data Science Roadmap</span>
                            <span class="quick-option-chip">Rekomendasi kursus desain grafis pemula</span>
                            <span class="quick-option-chip">Skill tradisional unik & cara belajarnya</span>
                        </div>
                    </div>
                    <div class="message bot">Halo! Saya MindBot, asisten AI-mu. Saya bisa membantumu menemukan kursus terbaik dan menyusun roadmap belajar sesuai minatmu. Silakan ketikkan apa yang ingin kamu pelajari atau pilih salah satu opsi di atas! 😊</div>
                </div>

                <form id="chat-form" class="chat-form">
                    <input type="text" id="user-input" class="chat-input" placeholder="Ketikkan minatmu di sini, misal: Aku ingin belajar digital marketing, atau Bantu aku membuat roadmap belajar" autocomplete="off">
                    <button type="submit" class="chat-submit">Kirim</button>
                </form>

                <div class="disclaimer-text">
                    MindBot bisa salah, jadi periksa kembali responsnya.
                </div>
            </div>
        </div>
    </div>

    <div id="info-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Bagaimana Cara Menggunakan MindBot?</h2>
            <p><strong>Halo! Saya MindBot, asisten AI-mu di MindCraft.</strong> Saya di sini untuk membantumu merancang perjalanan belajarmu. Kamu bisa bertanya kepada saya tentang:</p>
            <ul>
                <li><strong>Rekomendasi kursus</strong> berdasarkan minatmu (e.g., "Kursus desain grafis", "Belajar AI untuk pemula")</li>
                <li><strong>Roadmap belajar</strong> untuk skill tertentu (e.g., "Roadmap jadi Data Scientist", "Cara belajar koding")</li>
                <li><strong>Panduan untuk skill tradisional</strong> yang unik (e.g., "Ingin belajar membatik", "Skill bertani organik")</li>
                <li><strong>Penjelasan singkat tentang suatu bidang</strong> (e.g., "Apa itu Machine Learning?")</li>
            </ul>
            <p><strong>Tips:</strong> Cobalah pertanyaan yang spesifik untuk hasil terbaik! Atau, klik salah satu kartu di atas untuk inspirasi.</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="/MindCraft-Project/assets/js/llm.js"></script>

</body>

</html>