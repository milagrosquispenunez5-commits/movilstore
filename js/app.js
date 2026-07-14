let carrito = [];
let total = 0;

let sesion = { logged: false, rol: null, nombre: null };

function mostrar(id) {
  document
    .querySelectorAll(".section")
    .forEach((e) => e.classList.add("hidden"));

  document.getElementById("inicio").classList.add("hidden");

  if (id === "inicio") {
    document.getElementById("inicio").classList.remove("hidden");
  } else {
    document.getElementById(id).classList.remove("hidden");
  }

  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}

function agregar(nombre, precio) {
  carrito.push({ nombre, precio });

  actualizarCarrito();
}

function quitar(indice) {
  carrito.splice(indice, 1);

  actualizarCarrito();
}

function actualizarCarrito() {
  let lista = document.getElementById("listaCarrito");
  let html = "";

  total = 0;

  carrito.forEach((producto, i) => {
    total += producto.precio;

    html += `
<p>✔ ${producto.nombre} — S/ ${producto.precio}
<span onclick="quitar(${i})" style="cursor:pointer; color:#ff5555; margin-left:10px;" title="Quitar del carrito">✖</span></p>
`;
  });

  lista.innerHTML =
    html || "<p style='color:#999;'>El carrito está vacío</p>";

  document.getElementById("total").innerHTML = "Total: S/ " + total;
}

function abrirCarrito() {
  document.getElementById("carritoModal").style.display = "flex";
}

function cerrarCarrito() {
  document.getElementById("carritoModal").style.display = "none";
}

function escapar(texto) {
  const div = document.createElement("div");
  div.textContent = texto;
  return div.innerHTML;
}

function cargarProductos() {
  const contenedor = document.getElementById("listaProductos");

  fetch("controllers/productos.php?action=listar_productos")
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        throw new Error(data.message || "Error del servidor");
      }

      if (data.data.length === 0) {
        contenedor.innerHTML =
          "<p style='color:#999;'>No hay productos disponibles por ahora</p>";
        return;
      }

      contenedor.innerHTML = "";

      data.data.forEach((producto) => {
        const precio = Number(producto.precio);
        const card = document.createElement("div");
        card.className = "card";

        card.innerHTML = `
          ${
            producto.imagen
              ? `<img src="${escapar(producto.imagen)}">`
              : `<div style="height:230px; display:flex; align-items:center; justify-content:center; font-size:70px; background:black; border-radius:15px;">📱</div>`
          }
          <h3>${escapar(producto.nombre)}</h3>
          <p>S/ ${precio}</p>
          <button class="btn">Agregar al carrito</button>
        `;

        card
          .querySelector("button")
          .addEventListener("click", () => agregar(producto.nombre, precio));

        contenedor.appendChild(card);
      });
    })
    .catch((err) => {
      console.error("Error cargando productos:", err);
      contenedor.innerHTML =
        "<p style='color:red;'>❌ Error al cargar los productos. Abre la página desde el servidor (http://localhost:8000)</p>";
    });
}

function cargarSesion() {
  return fetch("controllers/auth.php?action=check")
    .then((response) => response.json())
    .then((data) => {
      sesion.logged = !!data.logged;
      sesion.rol = data.rol;
      sesion.nombre = data.nombre || data.username;

      const nav = document.getElementById("navSession");
      const aviso = document.getElementById("opinionLoginAviso");
      const formPanel = document.getElementById("opinionFormPanel");

      if (sesion.logged) {
        let html = `<span style="color:#00c3ff; margin-left:20px; font-size:15px;">👤 ${escapar(sesion.nombre)}</span>`;

        if (sesion.rol === "admin") {
          html += `<a href="views/admin.html">Panel Admin</a>`;
        }

        html += `<a href="controllers/auth.php?action=logout">Cerrar Sesión</a>`;
        nav.innerHTML = html;

        aviso.classList.add("hidden");
        formPanel.classList.remove("hidden");
        cargarMisOpiniones();
      } else {
        nav.innerHTML = `<a href="views/login.html">Iniciar Sesión</a>`;

        aviso.classList.remove("hidden");
        formPanel.classList.add("hidden");
      }
    })
    .catch((err) => {
      console.error("Error consultando la sesión:", err);
    });
}

function cargarMisOpiniones() {
  fetch("controllers/opiniones.php?action=mis_opiniones")
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) return;

      const box = document.getElementById("misOpinionesBox");
      const lista = document.getElementById("misOpiniones");

      if (data.data.length === 0) {
        box.classList.add("hidden");
        return;
      }

      let html = "";
      data.data.forEach((op) => {
        html += `
          <div style="background:rgba(11,18,32,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:12px 14px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
              <span style="color:#ffd700; font-size:13px;">${"★".repeat(op.rating)}</span>
              <small style="color:#8fa3b8; font-size:12px;">${escapar(op.fecha)}</small>
            </div>
            <p style="color:#ddd; line-height:1.5; font-size:14px;">${escapar(op.opinion)}</p>
          </div>
        `;
      });

      lista.innerHTML = html;
      box.classList.remove("hidden");
    })
    .catch((err) => {
      console.error("Error cargando tus opiniones:", err);
    });
}

function finalizarCompra() {
  if (carrito.length === 0) {
    alert("El carrito está vacío");
    return;
  }

  if (!sesion.logged) {
    if (
      confirm(
        "Para finalizar tu compra necesitas una cuenta de cliente. ¿Ir a iniciar sesión?"
      )
    ) {
      window.location.href = "views/login.html";
    }
    return;
  }

  const formData = new FormData();
  formData.append("action", "crear_pedido");
  formData.append("items", JSON.stringify(carrito));

  fetch("controllers/pedidos.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("✅ " + data.message);
        carrito = [];
        actualizarCarrito();
        cerrarCarrito();
      } else {
        alert("❌ " + data.message);
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      alert("❌ Error al registrar el pedido");
    });
}

let clientRating = 5;

function setClientRating(rating) {
  clientRating = rating;
  const stars = document.querySelectorAll("#opinionForm .star");
  stars.forEach((star, index) => {
    if (index < rating) {
      star.style.color = "#ffd700";
    } else {
      star.style.color = "#ccc";
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  cargarProductos();
  cargarSesion();
  setClientRating(5);

  if (location.hash) {
    const id = location.hash.substring(1);
    const sec = document.getElementById(id);
    if (sec && sec.classList.contains("section")) {
      mostrar(id);
    }
  }

  const opinionForm = document.getElementById("opinionForm");
  if (opinionForm) {
    opinionForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const comentario = document
        .getElementById("comentario")
        .value.trim();

      if (!comentario) {
        alert("Por favor, escribe una opinión");
        return;
      }

      const formData = new FormData();
      formData.append("action", "add_opinion_client");
      formData.append("opinion", comentario);
      formData.append("rating", clientRating);

      fetch("controllers/opiniones.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("✅ Gracias por tu opinión");
            document.getElementById("comentario").value = "";
            setClientRating(5);
            cargarMisOpiniones();
          } else {
            alert("❌ " + data.message);
          }
        })
        .catch((err) => {
          console.error("Error:", err);
          alert("❌ Error al guardar opinión");
        });
    });
  }
});
