let carrito = [];
let total = 0;

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

function cargarClientes() {
  const tbody = document.getElementById("tbodyClientes");

  fetch("controllers/clientes.php?action=listar_clientes")
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        throw new Error(data.message || "Error del servidor");
      }

      if (data.data.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="5" style="text-align:center; color:#999; padding:20px;">No hay clientes registrados aún</td></tr>';
        return;
      }

      let html = "";
      data.data.forEach((cliente, i) => {
        html += `
          <tr>
          <td>${i + 1}</td>
          <td>${escapar(cliente.dni)}</td>
          <td>${escapar(cliente.nombre)}</td>
          <td>${escapar(cliente.telefono)}</td>
          <td>${escapar(cliente.modelo)}</td>
          </tr>
          `;
      });
      tbody.innerHTML = html;
    })
    .catch((err) => {
      console.error("Error cargando clientes:", err);
      tbody.innerHTML =
        '<tr><td colspan="5" style="text-align:center; color:red; padding:20px;">❌ Error al cargar clientes. Abre la página desde el servidor (ejecuta ./iniciar.sh y entra a http://localhost:8000)</td></tr>';
    });
}

let clientRating = 5;

function cargarOpinionesClientes() {
  fetch("controllers/opiniones.php?action=get_opinions")
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) return;

      // Buenas: 4-5 estrellas | Regulares: 3 | Malas: 1-2
      let buenas = 0,
        regulares = 0,
        malas = 0;

      data.data.forEach((op) => {
        if (op.rating >= 4) buenas++;
        else if (op.rating === 3) regulares++;
        else malas++;
      });

      const total = data.data.length;
      document.getElementById("totalOpiniones").textContent =
        total === 0
          ? "No hay opiniones aún. ¡Sé el primero!"
          : "Total de opiniones: " + total;

      // Altura proporcional al total: el riel completo representa el 100%
      // de las opiniones (ej: 3 buenas de 5 en total = 60% del riel)
      const altura = (n) =>
        total === 0 || n === 0 ? "0%" : Math.round((n / total) * 100) + "%";

      document.getElementById("barraBuenas").style.height = altura(buenas);
      document.getElementById("barraRegulares").style.height = altura(regulares);
      document.getElementById("barraMalas").style.height = altura(malas);

      document.getElementById("cantBuenas").textContent = buenas;
      document.getElementById("cantRegulares").textContent = regulares;
      document.getElementById("cantMalas").textContent = malas;

      // Lista de opiniones debajo del gráfico
      let html = "";
      if (total === 0) {
        html =
          '<p style="text-align:center; color:#999; padding:20px;">No hay opiniones aún. ¡Sé el primero!</p>';
      } else {
        data.data.forEach((op) => {
          html += `
            <div style="background:rgba(11,18,32,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:12px 14px; margin-bottom:10px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                <strong style="color:#00c3ff; font-size:14px;">${escapar(op.nombre)}</strong>
                <span style="color:#ffd700; font-size:13px;">${"★".repeat(op.rating)}</span>
              </div>
              <p style="color:#ddd; line-height:1.5; font-size:14px;">${escapar(op.opinion)}</p>
              <small style="color:#8fa3b8; font-size:12px;">${escapar(op.fecha)}</small>
            </div>
          `;
        });
      }
      document.getElementById("opinionsContainer").innerHTML = html;
    })
    .catch((err) => {
      console.error("Error cargando opiniones:", err);
      document.getElementById("totalOpiniones").textContent =
        "Error al cargar el resumen de opiniones";
    });
}

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
  cargarOpinionesClientes();
  cargarClientes();
  setClientRating(5);

  // Abrir una sección directa desde la URL (ej: index.html#opiniones)
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

      const nombre = document
        .getElementById("nombreCliente")
        .value.trim();
      const comentario = document
        .getElementById("comentario")
        .value.trim();

      if (!nombre) {
        alert("Por favor, ingresa tu nombre");
        return;
      }

      if (!comentario) {
        alert("Por favor, escribe una opinión");
        return;
      }

      const formData = new FormData();
      formData.append("action", "add_opinion_client");
      formData.append("nombre", nombre);
      formData.append("email", "");
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
            document.getElementById("nombreCliente").value = "";
            document.getElementById("comentario").value = "";
            setClientRating(5);
            cargarOpinionesClientes();
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
