// app.js
document.addEventListener("DOMContentLoaded", () => {
  const desc = document.querySelector("#description");
  const counter = document.querySelector("#descCounter");
  const submitBtn = document.querySelector("#submitBtn");
  const title = document.querySelector("#title");
  const category = document.querySelector("#category");

  desc.addEventListener("input", () => {
    const len = desc.value.length;
    counter.textContent = `${len}/500`;
  });

  const validate = () => {
    submitBtn.disabled = !(
      title.value.trim().length >= 4 &&
      desc.value.trim().length >= 10 &&
      category.value
    );
  };

  [title, desc, category].forEach(el =>
    el.addEventListener("input", validate)
  );

  validate();
});
