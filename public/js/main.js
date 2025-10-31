document.addEventListener("DOMContentLoaded", () => {
    console.log("JS loaded!");
  });
document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.createElement("div");
  toggleBtn.classList.add("sidebar-toggle");
  toggleBtn.innerHTML = "☰";
  document.body.appendChild(toggleBtn);

  const sidebar = document.querySelector(".sidebar");
  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
});
  