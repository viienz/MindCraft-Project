document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("paymentForm");
  const notification = document.getElementById("notification");
  const paymentTypeRadios = document.querySelectorAll(
    'input[name="payment_type"]'
  );
  const bankFields = document.getElementById("bankFields");
  const ewalletFields = document.getElementById("ewalletFields");
  const subscriptionType = document.getElementById("subscription_type");
  const priceInput = document.getElementById("price");
  const totalAmountInput = document.getElementById("total_amount");

  // Harga berdasarkan jenis langganan
  const subscriptionPrices = {
    premium: 100000,
    pro: 200000,
    enterprise: 500000,
  };

  // Tampilkan fields berdasarkan jenis pembayaran
  function togglePaymentFields() {
    const selectedType = document.querySelector(
      'input[name="payment_type"]:checked'
    ).value;

    if (selectedType === "bank") {
      bankFields.style.display = "block";
      ewalletFields.style.display = "none";
      document
        .getElementById("payment_method_bank")
        .setAttribute("required", "");
      document
        .getElementById("payment_method_ewallet")
        .removeAttribute("required");
    } else {
      bankFields.style.display = "none";
      ewalletFields.style.display = "block";
      document
        .getElementById("payment_method_ewallet")
        .setAttribute("required", "");
      document
        .getElementById("payment_method_bank")
        .removeAttribute("required");
    }
  }

  // Update harga saat jenis langganan berubah
  function updatePrice() {
    const selectedSubscription = subscriptionType.value;

    if (selectedSubscription && subscriptionPrices[selectedSubscription]) {
      const price = subscriptionPrices[selectedSubscription];
      priceInput.value = price;
      totalAmountInput.value = price; // Di sini bisa ditambah kalkulasi pajak dll
    } else {
      priceInput.value = "";
      totalAmountInput.value = "";
    }
  }

  // Tampilkan notifikasi
  function showNotification(isSuccess, message) {
    notification.textContent = message;
    notification.className = isSuccess ? "success" : "error";
    notification.style.display = "block";

    // Scroll ke notifikasi
    notification.scrollIntoView({ behavior: "smooth" });

    // Sembunyikan notifikasi setelah 5 detik
    setTimeout(() => {
      notification.style.display = "none";
    }, 5000);
  }

  // Validasi form sebelum submit
  function validateForm(event) {
    event.preventDefault();

    // Validasi nomor telepon
    const phoneInput = document.getElementById("customer_phone");
    const phoneRegex = /^[0-9]{10,15}$/;

    if (!phoneRegex.test(phoneInput.value)) {
      showNotification(false, "Nomor telepon harus 10-15 digit angka.");
      return;
    }

    // Validasi khusus untuk bank transfer
    const paymentType = document.querySelector(
      'input[name="payment_type"]:checked'
    ).value;

    if (paymentType === "bank") {
      const accountNumber = document.getElementById(
        "bank_account_number"
      ).value;
      if (!accountNumber || !/^[0-9]{8,16}$/.test(accountNumber)) {
        showNotification(false, "Nomor rekening harus 8-16 digit angka.");
        return;
      }
    }

    // Jika semua validasi passed, submit form via AJAX
    submitForm();
  }

  // Submit form dengan AJAX
  function submitForm() {
    const formData = new FormData(form);

    fetch(form.action, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          showNotification(true, data.message);
          form.reset();
          updatePrice();
        } else {
          showNotification(false, data.message);
        }
      })
      .catch((error) => {
        showNotification(false, "Terjadi kesalahan: " + error.message);
      });
  }

  // Event listeners
  paymentTypeRadios.forEach((radio) => {
    radio.addEventListener("change", togglePaymentFields);
  });

  subscriptionType.addEventListener("change", updatePrice);
  form.addEventListener("submit", validateForm);

  // Inisialisasi
  togglePaymentFields();
  updatePrice();
});
