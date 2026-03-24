let notifications;

const toastDetails = {
  timer: 2000,
  success: {
    icon: "fa fa-check-circle",
    defaultText: "This is a success toast.",
  },
  error: {
    icon: "fa fa-times-circle",
    defaultText: "This is an error toast.",
  },
  warning: {
    icon: "fa fa-warning",
    defaultText: "This is a warning toast.",
  },
  info: {
    icon: "	fa fa-info-circle",
    defaultText: "This is an information toast.",
  },
  random: {
    icon: "	fa fa-question-circle",
    defaultText: "This is a random toast.",
  },
};

const removeToast = (toast) => {
  toast.classList.add("hide");
  if (toast.timeoutId) clearTimeout(toast.timeoutId);
  setTimeout(() => toast.remove(), 500);
};

const createToast = (type, message) => {
  if (!notifications) {
    initNotifications();
  }

  if (!notifications) {
    console.error("Toast container not found.");
    return;
  }

  const { icon, defaultText } = toastDetails[type];
  const text = message || defaultText;
  const toast = document.createElement("li");
  toast.className = `toast ${type}`;

  const columnDiv = document.createElement("div");
  columnDiv.className = "column";

  const iconElement = document.createElement("i");
  iconElement.className = `fa-solid ${icon}`;

  const textSpan = document.createElement("span");
  textSpan.textContent = text;

  const closeIcon = document.createElement("i");
  closeIcon.className = "fa-solid fa-xmark";
  closeIcon.addEventListener('click', () => removeToast(toast));

  columnDiv.appendChild(iconElement);
  columnDiv.appendChild(textSpan);
  toast.appendChild(columnDiv);
  toast.appendChild(closeIcon);

  notifications.appendChild(toast);
  toast.timeoutId = setTimeout(() => removeToast(toast), toastDetails.timer);
};

const showToast = (type, message) => {
  if (toastDetails[type]) {
    createToast(type, message);
  } else {
    console.error(`Toast type "${type}" is not defined.`);
  }
};

function initNotifications() {
  notifications = document.querySelector(".notifications");
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initNotifications);
} else {
  initNotifications();
}
