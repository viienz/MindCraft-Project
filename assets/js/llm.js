$(document).ready(function () {
    $("#chat-form").on("submit", function (e) {
        e.preventDefault();

        const userInput = $("#user-input").val().trim();
        if (!userInput) return;

        // Tampilkan pesan user
        $("#chat-box").append(`<div class="message user">${userInput}</div>`);
        $("#user-input").val("");
        $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);

        // Tambahkan indikator loading
        // Penting: pastikan indikator loading muncul SETELAH pesan user, bukan sebelum
        $("#chat-box").append(`<div class="message bot loading-indicator"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>`);
        $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);


        $.ajax({
            url: "/MindCraft-Project/api/llm_chat.php",
            method: "POST",
            data: { message: userInput },
            success: function (res) {
                // Hapus indikator loading
                $(".loading-indicator").remove();

                try {
                    const data = JSON.parse(res);
                    // MENGGUNAKAN marked.js untuk parsing Markdown ke HTML
                    const htmlReply = marked.parse(data.reply);
                    $("#chat-box").append(`<div class="message bot">${htmlReply}</div>`);
                } catch (error) {
                    $("#chat-box").append(
                        `<div class="message bot">Terjadi kesalahan dalam memproses jawaban. Mohon coba lagi nanti.</div>`
                    );
                }
                $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);
            },
            error: function () {
                // Hapus indikator loading
                $(".loading-indicator").remove();

                $("#chat-box").append(
                    `<div class="message bot">Maaf, koneksi ke AI gagal. Silakan periksa koneksi internet Anda atau coba lagi nanti.</div>`
                );
                $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);
            },
        });
    });

    // Selector untuk quick-option-chip
    $(document).on("click", ".quick-option-chip", function () {
        const text = $(this).text();
        $("#user-input").val(text).focus();
        $("#chat-form").submit();
    });

    // --- LOGIKA POP-UP "Bagaimana Cara Menggunakan MindBot?" ---
    const infoButton = $("#sidebar-info-button"); // Menggunakan ID dari sidebar
    const infoModal = $("#info-modal");
    const closeButton = $(".close-button");

    infoButton.on("click", function (e) {
        e.preventDefault();
        infoModal.css("display", "block");
    });

    closeButton.on("click", function () {
        infoModal.css("display", "none");
    });

    $(window).on("click", function (event) {
        if ($(event.target).is(infoModal)) {
            infoModal.css("display", "none");
        }
    });
    // --- AKHIR LOGIKA POP-UP ---

    // --- LOGIKA REFRESH CHAT ---
    // Selector untuk tombol refresh chat di sidebar
    $("#sidebar-refresh-chat").click(function (e) {
        e.preventDefault();
        // Hanya hapus pesan-pesan chat, biarkan chatbot-header tetap di tempatnya
        // Kita akan identifikasi pesan chat dengan class 'message' dan yang bukan 'chatbot-header'
        $("#chat-box .message").remove(); 
        // Tambahkan kembali pesan selamat datang awal ke chat-box
        $("#chat-box").append('<div class="message bot">Halo! Saya MindBot, asisten AI-mu. Saya bisa membantumu menemukan kursus terbaik dan menyusun roadmap belajar sesuai minatmu. Silakan ketikkan apa yang ingin kamu pelajari atau pilih salah satu opsi di atas! 😊</div>');
        $("#user-input").val('').focus(); // Bersihkan input dan fokuskan
        $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight); // Scroll ke bawah setelah reset
    });
    // --- AKHIR LOGIKA REFRESH CHAT ---
});