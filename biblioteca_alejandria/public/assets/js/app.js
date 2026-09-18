const API = "../api/index.php";

const state = {
  usuarios: [],
  libros: [],
  prestamos: [],
  inventario: [],
};

const money = new Intl.NumberFormat("es-CO", {
  style: "currency",
  currency: "COP",
  maximumFractionDigits: 0,
});

document.addEventListener("DOMContentLoaded", () => {
  setToday();
  bindForms();
  bindResetButtons();
  loadAll();
});

async function loadAll() {
  const [usuarios, libros, prestamos, inventario] = await Promise.all([
    api("usuarios"),
    api("libros"),
    api("prestamos"),
    api("inventario"),
  ]);

  state.usuarios = usuarios;
  state.libros = libros;
  state.prestamos = prestamos;
  state.inventario = inventario;

  renderUsers();
  renderBooks();
  renderLoans();
  renderInventory();
  renderSummary();
  fillSelects();
}

async function api(resource, options = {}) {
  const response = await fetch(`${API}?resource=${resource}${options.id ? `&id=${options.id}` : ""}`, {
    method: options.method || "GET",
    headers: { "Content-Type": "application/json" },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.error || "No fue posible completar la operacion.");
  }
  return data;
}

function bindForms() {
  document.querySelector("#user-form").addEventListener("submit", saveUser);
  document.querySelector("#book-form").addEventListener("submit", saveBook);
  document.querySelector("#loan-form").addEventListener("submit", saveLoan);
}

function bindResetButtons() {
  document.querySelectorAll("[data-reset]").forEach((button) => {
    button.addEventListener("click", () => {
      const form = document.getElementById(button.dataset.reset);
      form.reset();
      form.elements.id.value = "";
      setToday();
    });
  });
}

async function saveUser(event) {
  event.preventDefault();
  const data = formData(event.target);
  const id = data.id;
  delete data.id;

  await api("usuarios", {
    id,
    method: id ? "PUT" : "POST",
    body: data,
  });

  event.target.reset();
  toast("Usuario guardado");
  await loadAll();
}

async function saveBook(event) {
  event.preventDefault();
  const data = formData(event.target);
  const id = data.id;
  delete data.id;
  data.unidades = Number(data.unidades);
  data.precio = Number(data.precio);

  await api("libros", {
    id,
    method: id ? "PUT" : "POST",
    body: data,
  });

  event.target.reset();
  toast("Libro guardado");
  await loadAll();
}

async function saveLoan(event) {
  event.preventDefault();
  const data = formData(event.target);
  const id = data.id;
  delete data.id;
  data.usuario_id = Number(data.usuario_id);
  data.libro_id = Number(data.libro_id);

  await api("prestamos", {
    id,
    method: id ? "PUT" : "POST",
    body: data,
  });

  event.target.reset();
  setToday();
  toast("Prestamo guardado");
  await loadAll();
}

function renderUsers() {
  const tbody = document.querySelector("#users-table");
  tbody.innerHTML = state.usuarios.map((user) => `
    <tr>
      <td>${escapeHtml(user.nombre)}</td>
      <td>${escapeHtml(user.cedula)}</td>
      <td>${escapeHtml(user.telefono)}</td>
      <td class="actions">
        <button type="button" onclick="editUser(${user.id})">Editar</button>
        <button type="button" onclick="removeRow('usuarios', ${user.id})">Eliminar</button>
      </td>
    </tr>
  `).join("");
}

function renderBooks() {
  const tbody = document.querySelector("#books-table");
  tbody.innerHTML = state.libros.map((book) => `
    <tr>
      <td>${escapeHtml(book.codigo)}</td>
      <td>${escapeHtml(book.titulo)}</td>
      <td>${escapeHtml(book.autor)}</td>
      <td>${book.unidades_disponibles}/${book.unidades}</td>
      <td>${money.format(Number(book.precio))}</td>
      <td class="actions">
        <button type="button" onclick="editBook(${book.id})">Editar</button>
        <button type="button" onclick="removeRow('libros', ${book.id})">Eliminar</button>
      </td>
    </tr>
  `).join("");
}

function renderLoans() {
  const tbody = document.querySelector("#loans-table");
  tbody.innerHTML = state.prestamos.map((loan) => `
    <tr>
      <td>${escapeHtml(loan.usuario)}<br><small>${escapeHtml(loan.cedula)}</small></td>
      <td>${escapeHtml(loan.titulo)}<br><small>${escapeHtml(loan.codigo)}</small></td>
      <td>${escapeHtml(loan.fecha_prestamo)}</td>
      <td>${escapeHtml(loan.fecha_devolucion || "-")}</td>
      <td><span class="pill ${loan.estado === "devuelto" ? "returned" : ""}">${escapeHtml(loan.estado)}</span></td>
      <td class="actions">
        <button type="button" onclick="editLoan(${loan.id})">Editar</button>
        <button type="button" onclick="removeRow('prestamos', ${loan.id})">Eliminar</button>
      </td>
    </tr>
  `).join("");
}

function renderInventory() {
  const tbody = document.querySelector("#inventory-table");
  tbody.innerHTML = state.inventario.map((item) => `
    <tr>
      <td>${escapeHtml(item.codigo)}</td>
      <td>${escapeHtml(item.titulo)}</td>
      <td>${escapeHtml(item.autor)}</td>
      <td>${item.unidades}</td>
      <td>${item.unidades_disponibles}</td>
      <td>${item.prestadas}</td>
      <td>${money.format(Number(item.valor_inventario))}</td>
    </tr>
  `).join("");
}

function renderSummary() {
  const activeLoans = state.prestamos.filter((loan) => loan.estado === "activo").length;
  const inventoryValue = state.inventario.reduce((sum, item) => sum + Number(item.valor_inventario), 0);

  document.querySelector("#count-users").textContent = state.usuarios.length;
  document.querySelector("#count-books").textContent = state.libros.length;
  document.querySelector("#count-loans").textContent = activeLoans;
  document.querySelector("#inventory-value").textContent = money.format(inventoryValue);
}

function fillSelects() {
  const userSelect = document.querySelector("#loan-form [name='usuario_id']");
  const bookSelect = document.querySelector("#loan-form [name='libro_id']");

  userSelect.innerHTML = state.usuarios
    .map((user) => `<option value="${user.id}">${escapeHtml(user.nombre)} - ${escapeHtml(user.cedula)}</option>`)
    .join("");

  bookSelect.innerHTML = state.libros
    .map((book) => `<option value="${book.id}">${escapeHtml(book.codigo)} - ${escapeHtml(book.titulo)} (${book.unidades_disponibles} disp.)</option>`)
    .join("");
}

function editUser(id) {
  const user = state.usuarios.find((item) => Number(item.id) === Number(id));
  fillForm("user-form", user);
  location.hash = "usuarios";
}

function editBook(id) {
  const book = state.libros.find((item) => Number(item.id) === Number(id));
  fillForm("book-form", book);
  location.hash = "libros";
}

function editLoan(id) {
  const loan = state.prestamos.find((item) => Number(item.id) === Number(id));
  fillForm("loan-form", loan);
  location.hash = "prestamos";
}

async function removeRow(resource, id) {
  if (!confirm("¿Eliminar este registro?")) return;

  await api(resource, {
    id,
    method: "DELETE",
  });

  toast("Registro eliminado");
  await loadAll();
}

function fillForm(formId, data) {
  const form = document.getElementById(formId);
  Object.entries(data).forEach(([key, value]) => {
    if (form.elements[key]) {
      form.elements[key].value = value ?? "";
    }
  });
}

function formData(form) {
  return Object.fromEntries(new FormData(form).entries());
}

function setToday() {
  const field = document.querySelector("#loan-form [name='fecha_prestamo']");
  if (field && !field.value) {
    field.value = new Date().toISOString().slice(0, 10);
  }
}

function toast(message) {
  const toastBox = document.querySelector("#toast");
  toastBox.textContent = message;
  toastBox.classList.add("visible");
  setTimeout(() => toastBox.classList.remove("visible"), 2400);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}
