document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  document
    .getElementById("mobileMenuToggle")
    ?.addEventListener("click", function () {
      document.getElementById("sidebar").classList.toggle("active");
    });

  // Form step navigation
  document.querySelectorAll(".next-step").forEach((button) => {
    button.addEventListener("click", function () {
      const nextStep = this.getAttribute("data-next");
      document.querySelector(".form-step.active").classList.remove("active");
      document.getElementById(nextStep).classList.add("active");
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  });

  document.querySelectorAll(".prev-step").forEach((button) => {
    button.addEventListener("click", function () {
      const prevStep = this.getAttribute("data-prev");
      document.querySelector(".form-step.active").classList.remove("active");
      document.getElementById(prevStep).classList.add("active");
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  });

  // Quick amount buttons
  document.querySelectorAll(".quick-amount-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const amount = this.getAttribute("data-amount");
      document.getElementById("amount").value = amount;
      updateConfirmation();
    });
  });

  // Show account selection based on method
  document
    .querySelectorAll('input[name="withdrawal_method"]')
    .forEach((radio) => {
      radio.addEventListener("change", function () {
        const method = this.value;

        // Hide all account selections
        document.querySelectorAll(".account-selection").forEach((el) => {
          el.style.display = "none";
        });

        // Show the relevant one
        if (method === "bank_transfer") {
          document.getElementById("bank_accounts").style.display = "block";
        } else if (["gopay", "dana", "ovo", "shopeepay"].includes(method)) {
          document.getElementById("ewallet_accounts").style.display = "block";
        }

        // Update confirmation preview
        updateConfirmation();
      });
    });

  // Update account info when selected
  document.querySelectorAll('input[name="account_info"]').forEach((radio) => {
    radio.addEventListener("change", updateConfirmation);
  });

  // Update amount when changed
  document
    .getElementById("amount")
    ?.addEventListener("input", updateConfirmation);

  // Update confirmation preview
  function updateConfirmation() {
    const amount = document.getElementById("amount")?.value;
    const method = document.querySelector(
      'input[name="withdrawal_method"]:checked'
    );
    const account = document.querySelector(
      'input[name="account_info"]:checked'
    );

    if (amount && document.getElementById("confirmAmount")) {
      document.getElementById("confirmAmount").textContent =
        "Rp " + parseFloat(amount).toLocaleString("id-ID");
      document.getElementById("confirmTotal").textContent =
        "Rp " + parseFloat(amount).toLocaleString("id-ID");
    }

    if (method && document.getElementById("confirmMethod")) {
      document.getElementById("confirmMethod").textContent =
        getMethodIcon(method.value) + " " + method.value.replace("_", " ");
      document.getElementById("confirmTime").textContent = getProcessingTime(
        method.value
      );
    }

    if (account && document.getElementById("confirmAccount")) {
      document.getElementById("confirmAccount").textContent = account.value;
    }
  }

  function getMethodIcon(method) {
    const icons = {
      bank_transfer: "🏦",
      gopay: "💚",
      ovo: "💜",
      dana: "💙",
      shopeepay: "🧡",
    };
    return icons[method] || "🏦";
  }

  function getProcessingTime(method) {
    const times = {
      bank_transfer: "1-2 hari kerja",
      gopay: "Instan",
      dana: "Instan",
      ovo: "Instan",
      shopeepay: "Instan",
    };
    return times[method] || "1-2 hari kerja";
  }

  // Enable submit button when terms are agreed
  document
    .getElementById("agreeTerms")
    ?.addEventListener("change", function () {
      const confirmData = document.getElementById("confirmData");
      const submitBtn = document.querySelector(".submit-btn");

      if (this.checked && confirmData.checked) {
        submitBtn.disabled = false;
      } else {
        submitBtn.disabled = true;
      }
    });

  document
    .getElementById("confirmData")
    ?.addEventListener("change", function () {
      const agreeTerms = document.getElementById("agreeTerms");
      const submitBtn = document.querySelector(".submit-btn");

      if (this.checked && agreeTerms.checked) {
        submitBtn.disabled = false;
      } else {
        submitBtn.disabled = true;
      }
    });

  // Modal handling
  function openModal(modalId) {
    document.getElementById(modalId).style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
    document.body.style.overflow = "auto";
  }

  document
    .getElementById("addBankAccount")
    ?.addEventListener("click", function (e) {
      e.preventDefault();
      openModal("bankAccountModal");
    });

  document
    .getElementById("addEwalletAccount")
    ?.addEventListener("click", function (e) {
      e.preventDefault();
      openModal("ewalletModal");
    });

  document.querySelectorAll(".modal-close").forEach((button) => {
    button.addEventListener("click", function () {
      const modal = this.closest(".modal");
      closeModal(modal.id);
    });
  });

  // Close modal when clicking outside
  window.addEventListener("click", function (event) {
    if (event.target.classList.contains("modal")) {
      closeModal(event.target.id);
    }
  });

  // Form submission validation
  document
    .getElementById("withdrawalForm")
    ?.addEventListener("submit", function (e) {
      const amount = parseFloat(document.getElementById("amount").value);
      const availableBalance = window.withdrawalData?.availableBalance || 0;
      const minimumPayout = window.withdrawalData?.minimumPayout || 100000;

      if (amount < minimumPayout) {
        alert(
          "Minimum penarikan adalah Rp " + minimumPayout.toLocaleString("id-ID")
        );
        e.preventDefault();
        return;
      }

      if (amount > availableBalance) {
        alert("Jumlah penarikan melebihi saldo tersedia");
        e.preventDefault();
        return;
      }

      // Show loading overlay
      document.getElementById("loadingOverlay").style.display = "flex";
    });
});
