/**
 * Script ultra-moderno para la página de login
 * Incluye Three.js, GSAP, validaciones avanzadas y efectos visuales
 */
document.addEventListener("DOMContentLoaded", () => {
  // Declarar variables globales
  const gsap = window.gsap;
  const Swal = window.Swal;
  const lottie = window.lottie;
  const THREE = window.THREE;

  // Ocultar elementos inicialmente para animarlos correctamente
  const loginCard = document.querySelector(".login-card");
  const loginFooter = document.querySelector(".login-footer");

  if (loginCard) loginCard.style.opacity = "0";
  if (loginFooter) loginFooter.style.opacity = "0";

  // Inicializar Three.js para el fondo
  initThreeJsBackground();

  // Preloader más corto
  const preloader = document.querySelector(".preloader");
  if (preloader) {
    setTimeout(() => {
      gsap.to(preloader, {
        opacity: 0,
        duration: 0.5,
        ease: "power2.out",
        onComplete: () => {
          preloader.style.visibility = "hidden";
          // Animar entrada de elementos
          animateElements();
        },
      });
    }, 500); // Reducido de 1000ms a 500ms
  }

  // Mostrar/ocultar contraseña
  const togglePassword = document.querySelector(".password-toggle");
  const passwordInput = document.querySelector("#contrasena");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);

      // Cambiar el icono
      const icon = this.querySelector("i");
      icon.classList.toggle("ri-eye-line");
      icon.classList.toggle("ri-eye-off-line");

      // Animar el cambio
      gsap.from(icon, {
        scale: 0.5,
        opacity: 0,
        duration: 0.3,
        ease: "back.out(1.7)",
      });
    });
  }

  // Manejar el comportamiento de las etiquetas
  const inputs = document.querySelectorAll(".input-wrapper input");

  inputs.forEach((input) => {
    // Verificar estado inicial
    if (input.value !== "") {
      input.nextElementSibling.classList.add("active");
    }

    // Eventos de focus y blur
    input.addEventListener("focus", function () {
      this.nextElementSibling.classList.add("active");
      const wrapper = this.closest(".input-wrapper");
      gsap.to(wrapper, {
        scale: 1.02,
        duration: 0.3,
        ease: "power2.out",
      });
    });

    input.addEventListener("blur", function () {
      if (this.value === "") {
        this.nextElementSibling.classList.remove("active");
      }
      const wrapper = this.closest(".input-wrapper");
      gsap.to(wrapper, {
        scale: 1,
        duration: 0.3,
        ease: "power2.out",
      });
    });
  });

  // Cerrar alerta personalizada
  const closeAlert = document.querySelector(".close-alert");
  if (closeAlert) {
    closeAlert.addEventListener("click", function () {
      const alertContainer = this.closest(".error-alert");
      gsap.to(alertContainer, {
        opacity: 0,
        y: -20,
        duration: 0.5,
        ease: "power2.out",
        onComplete: () => {
          alertContainer.style.display = "none";
        },
      });
    });
  }

  // Efecto de seguimiento para el brillo
  const glowEffect = document.querySelector(".glow-effect");

  if (loginCard && glowEffect) {
    loginCard.addEventListener("mousemove", (e) => {
      const rect = loginCard.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      gsap.to(glowEffect, {
        left: x,
        top: y,
        duration: 0.5,
        ease: "power2.out",
      });
    });

    loginCard.addEventListener("mouseleave", () => {
      gsap.to(glowEffect, {
        left: "50%",
        top: "50%",
        duration: 0.5,
        ease: "power2.out",
      });
    });
  }

  // Validación del formulario con Fetch API
  const loginForm = document.getElementById("loginForm");
  const loginButton = document.getElementById("loginButton");

  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault();

      // Validar campos
      const username = document.getElementById("nombre_usuario").value.trim();
      const password = document.getElementById("contrasena").value.trim();

      if (!username || !password) {
        // Usar SweetAlert2 para mostrar error
        Swal.fire({
          title: "Error de validación",
          text: "Por favor, complete todos los campos",
          icon: "error",
          background: "rgba(15, 23, 42, 0.9)",
          color: "#f8fafc",
          confirmButtonColor: "#6366f1",
          confirmButtonText: "Entendido",
          showClass: {
            popup: "animate__animated animate__fadeInDown",
          },
          hideClass: {
            popup: "animate__animated animate__fadeOutUp",
          },
        });
        return;
      }

      // Animar botón durante la carga
      const buttonText = loginButton.querySelector(".button-text");
      const buttonIcon = loginButton.querySelector(".button-icon");

      gsap.to(buttonText, {
        opacity: 0,
        y: -10,
        duration: 0.3,
        ease: "power2.out",
        onComplete: () => {
          buttonText.innerHTML = "Procesando...";
          gsap.to(buttonText, {
            opacity: 1,
            y: 0,
            duration: 0.3,
            ease: "power2.out",
          });
        },
      });

      gsap.to(buttonIcon, {
        opacity: 0,
        x: 10,
        duration: 0.3,
        ease: "power2.out",
        onComplete: () => {
          buttonIcon.innerHTML = '<div class="spinner"></div>';
          gsap.to(buttonIcon, {
            opacity: 1,
            x: 0,
            duration: 0.3,
            ease: "power2.out",
          });
        },
      });

      loginButton.disabled = true;

      // Crear FormData para enviar los datos
      const formData = new FormData(loginForm);

      // Enviar datos con Fetch API
      fetch("login.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          // Si la respuesta es una redirección (login exitoso)
          if (response.redirected) {
            // Mostrar animación de éxito con LottieFiles
            gsap.to(".welcome-text", {
              opacity: 0,
              y: -20,
              duration: 0.5,
              ease: "power2.out",
              onComplete: () => {
                document.querySelector(".welcome-text").style.display = "none";
              },
            });

            gsap.to(".login-form", {
              opacity: 0,
              y: 20,
              duration: 0.5,
              ease: "power2.out",
              onComplete: () => {
                document.querySelector(".login-form").style.display = "none";

                const successAnimation =
                  document.getElementById("success-animation");
                successAnimation.classList.remove("d-none");

                gsap.from(successAnimation, {
                  scale: 0.5,
                  opacity: 0,
                  duration: 0.8,
                  ease: "elastic.out(1, 0.5)",
                });

                // Cargar animación Lottie
                const animation = lottie.loadAnimation({
                  container: document.getElementById("lottie-success"),
                  renderer: "svg",
                  loop: false,
                  autoplay: true,
                  path: "https://assets5.lottiefiles.com/packages/lf20_jAW7Sp.json", // Animación de éxito moderna
                });

                // Mostrar mensaje de éxito con SweetAlert2
                setTimeout(() => {
                  Swal.fire({
                    title: "¡Acceso Concedido!",
                    text: "Iniciando sesión...",
                    icon: "success",
                    background: "rgba(15, 23, 42, 0.9)",
                    color: "#f8fafc",
                    timer: 1000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    showClass: {
                      popup: "animate__animated animate__zoomIn",
                    },
                    hideClass: {
                      popup: "animate__animated animate__zoomOut",
                    },
                  }).then(() => {
                    // Redirigir a la URL de redirección
                    window.location.href = response.url;
                  });
                }, 300);
              },
            });
          } else {
            // Si no es una redirección, procesar la respuesta HTML
            return response.text();
          }
        })
        .then((html) => {
          if (html) {
            // Buscar mensaje de error en la respuesta HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");
            const errorElement = doc.querySelector(".error-alert span");

            // Restaurar botón
            gsap.to(buttonText, {
              opacity: 0,
              y: 10,
              duration: 0.3,
              ease: "power2.out",
              onComplete: () => {
                buttonText.innerHTML = "Iniciar Sesión";
                gsap.to(buttonText, {
                  opacity: 1,
                  y: 0,
                  duration: 0.3,
                  ease: "power2.out",
                });
              },
            });

            gsap.to(buttonIcon, {
              opacity: 0,
              x: -10,
              duration: 0.3,
              ease: "power2.out",
              onComplete: () => {
                buttonIcon.innerHTML = '<i class="ri-arrow-right-line"></i>';
                gsap.to(buttonIcon, {
                  opacity: 1,
                  x: 0,
                  duration: 0.3,
                  ease: "power2.out",
                });
              },
            });

            loginButton.disabled = false;

            if (errorElement) {
              // Mostrar error con SweetAlert2
              Swal.fire({
                title: "Error de autenticación",
                text: errorElement.textContent,
                icon: "error",
                background: "rgba(15, 23, 42, 0.9)",
                color: "#f8fafc",
                confirmButtonColor: "#6366f1",
                confirmButtonText: "Intentar de nuevo",
                showClass: {
                  popup: "animate__animated animate__shakeX",
                },
              });
            } else {
              // Error genérico
              Swal.fire({
                title: "Error",
                text: "Ocurrió un error al procesar su solicitud",
                icon: "error",
                background: "rgba(15, 23, 42, 0.9)",
                color: "#f8fafc",
                confirmButtonColor: "#6366f1",
                confirmButtonText: "Intentar de nuevo",
                showClass: {
                  popup: "animate__animated animate__shakeX",
                },
              });
            }
          }
        })
        .catch((error) => {
          console.error("Error:", error);

          // Restaurar botón
          gsap.to(buttonText, {
            opacity: 0,
            y: 10,
            duration: 0.3,
            ease: "power2.out",
            onComplete: () => {
              buttonText.innerHTML = "Iniciar Sesión";
              gsap.to(buttonText, {
                opacity: 1,
                y: 0,
                duration: 0.3,
                ease: "power2.out",
              });
            },
          });

          gsap.to(buttonIcon, {
            opacity: 0,
            x: -10,
            duration: 0.3,
            ease: "power2.out",
            onComplete: () => {
              buttonIcon.innerHTML = '<i class="ri-arrow-right-line"></i>';
              gsap.to(buttonIcon, {
                opacity: 1,
                x: 0,
                duration: 0.3,
                ease: "power2.out",
              });
            },
          });

          loginButton.disabled = false;

          // Mostrar error con SweetAlert2
          Swal.fire({
            title: "Error de conexión",
            text: "No se pudo conectar con el servidor. Verifique su conexión a internet.",
            icon: "error",
            background: "rgba(15, 23, 42, 0.9)",
            color: "#f8fafc",
            confirmButtonColor: "#6366f1",
            confirmButtonText: "Intentar de nuevo",
            showClass: {
              popup: "animate__animated animate__shakeX",
            },
          });
        });
    });
  }

  // Demo link con confirmación
  const demoLink = document.querySelector(".demo-link");

  if (demoLink) {
    demoLink.addEventListener("click", function (e) {
      e.preventDefault();

      Swal.fire({
        title: "Acceso Demo",
        text: "¿Desea ingresar con el usuario de demostración?",
        icon: "question",
        background: "rgba(15, 23, 42, 0.9)",
        color: "#f8fafc",
        showCancelButton: true,
        confirmButtonColor: "#6366f1",
        cancelButtonColor: "#475569",
        confirmButtonText: "Sí, ingresar",
        cancelButtonText: "Cancelar",
        showClass: {
          popup: "animate__animated animate__fadeInDown",
        },
        hideClass: {
          popup: "animate__animated animate__fadeOutUp",
        },
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = this.getAttribute("href");
        }
      });
    });
  }

  // Función para animar la entrada de elementos
  function animateElements() {
    // Animar la tarjeta de login
    gsap.to(".login-card", {
      y: 0,
      opacity: 1,
      duration: 0.8,
      ease: "power3.out",
    });

    // Animar el footer
    gsap.to(".login-footer", {
      opacity: 1,
      y: 0,
      duration: 0.8,
      delay: 0.3,
      ease: "power3.out",
    });

    // Animar elementos internos secuencialmente
    const elements = [
      ".brand-section",
      ".welcome-text",
      ".form-group:nth-child(1)",
      ".form-group:nth-child(2)",
      ".form-options",
      ".demo-access",
      ".company-info",
    ];

    // Asegurarse de que el botón de inicio de sesión esté visible desde el principio
    gsap.set(".login-button", {
      opacity: 1,
      visibility: "visible",
    });

    elements.forEach((selector, index) => {
      const element = document.querySelector(selector);
      if (element) {
        gsap.from(element, {
          y: 20,
          opacity: 0,
          duration: 0.5,
          delay: 0.1 + index * 0.1,
          ease: "power2.out",
        });
      }
    });
  }

  // Función para inicializar Three.js
  function initThreeJsBackground() {
    // Verificar si Three.js está disponible
    if (typeof THREE === "undefined") return;

    const canvas = document.getElementById("bg-canvas");

    // Crear escena
    const scene = new THREE.Scene();

    // Configurar cámara
    const camera = new THREE.PerspectiveCamera(
      75,
      window.innerWidth / window.innerHeight,
      0.1,
      1000
    );
    camera.position.z = 30;

    // Configurar renderer
    const renderer = new THREE.WebGLRenderer({
      canvas: canvas,
      antialias: true,
      alpha: true,
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);

    // Crear partículas
    const particlesGeometry = new THREE.BufferGeometry();
    const particlesCount = 2000;

    const posArray = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i++) {
      posArray[i] = (Math.random() - 0.5) * 100;
    }

    particlesGeometry.setAttribute(
      "position",
      new THREE.BufferAttribute(posArray, 3)
    );

    // Material para partículas
    const particlesMaterial = new THREE.PointsMaterial({
      size: 0.2,
      color: 0x6366f1,
      transparent: true,
      opacity: 0.8,
      blending: THREE.AdditiveBlending,
    });

    // Crear mesh de partículas
    const particlesMesh = new THREE.Points(
      particlesGeometry,
      particlesMaterial
    );
    scene.add(particlesMesh);

    // Añadir luz ambiental
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);

    // Añadir luz puntual
    const pointLight = new THREE.PointLight(0x6366f1, 1);
    pointLight.position.set(25, 50, 5);
    scene.add(pointLight);

    // Animación
    function animate() {
      requestAnimationFrame(animate);

      particlesMesh.rotation.x += 0.0005;
      particlesMesh.rotation.y += 0.0005;

      renderer.render(scene, camera);
    }

    animate();

    // Manejar redimensionamiento de ventana
    window.addEventListener("resize", () => {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
    });

    // Interactividad con el mouse
    let mouseX = 0;
    let mouseY = 0;

    function onDocumentMouseMove(event) {
      mouseX = (event.clientX - window.innerWidth / 2) / 100;
      mouseY = (event.clientY - window.innerHeight / 2) / 100;
    }

    document.addEventListener("mousemove", onDocumentMouseMove);

    // Actualizar la posición de la cámara basada en la posición del mouse
    function updateCamera() {
      camera.position.x += (mouseX - camera.position.x) * 0.05;
      camera.position.y += (-mouseY - camera.position.y) * 0.05;
      camera.lookAt(scene.position);

      requestAnimationFrame(updateCamera);
    }

    updateCamera();
  }
});
