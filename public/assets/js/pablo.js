document.addEventListener("DOMContentLoaded", function () {
  // Sidebar elements
  const leftSidebar = document.getElementById("leftSidebar");
  const rightSidebar = document.getElementById("rightSidebar");
  const mainContent = document.getElementById("main");
  const contentSection = document.getElementById("content-section");
  const isMobile = window.innerWidth <= 768;

  // Handle left sidebar toggle
  document
    .getElementById("leftSidebarToggle")
    .addEventListener("click", function () {
      if (isMobile) {
        leftSidebar.classList.toggle("show");
        rightSidebar.classList.remove("show");
      } else {
        leftSidebar.classList.toggle("collapsed");
        if (rightSidebar.classList.contains("collapsed")) {
          mainContent.classList.toggle("expanded-both");
          mainContent.classList.toggle("expanded-right");
        } else {
          mainContent.classList.toggle("expanded-left");
        }
      }
    });

  // Handle right sidebar toggle
  document
    .getElementById("rightSidebarToggle")
    .addEventListener("click", function () {
      if (isMobile) {
        rightSidebar.classList.toggle("show");
        leftSidebar.classList.remove("show");
      } else {
        rightSidebar.classList.toggle("collapsed");
        if (leftSidebar.classList.contains("collapsed")) {
          mainContent.classList.toggle("expanded-both");
          mainContent.classList.toggle("expanded-left");
        } else {
          mainContent.classList.toggle("expanded-right");
        }
      }
    });

  // Close sidebars when clicking outside on mobile
  document.addEventListener("click", function (event) {
    if (isMobile) {
      const isClickInsideLeftSidebar = leftSidebar.contains(event.target);
      const isClickInsideRightSidebar = rightSidebar.contains(event.target);
      const isClickOnLeftToggle = event.target.closest("#leftSidebarToggle");
      const isClickOnRightToggle = event.target.closest("#rightSidebarToggle");

      if (
        !isClickInsideLeftSidebar &&
        !isClickOnLeftToggle &&
        leftSidebar.classList.contains("show")
      ) {
        leftSidebar.classList.remove("show");
      }
      if (
        !isClickInsideRightSidebar &&
        !isClickOnRightToggle &&
        rightSidebar.classList.contains("show")
      ) {
        rightSidebar.classList.remove("show");
      }
    }
  });

  // Handle window resize
  window.addEventListener("resize", function () {
    const newIsMobile = window.innerWidth <= 768;
    if (newIsMobile !== isMobile) {
      location.reload();
    }
  });

  // AJAX Functions
  function showLoading() {
    contentSection.innerHTML =
      '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
  }

  function handleError(error) {
    contentSection.innerHTML = `
            <div class="alert alert-danger" role="alert">
                Error loading content: \${error.message}
            </div>
        `;
  }

  function updateURL(url) {
    history.pushState({}, "", url);
  }

  async function loadContent(url) {
    try {
      showLoading();
      const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
      ).content;
      const response = await fetch(url, {
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": csrfToken,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: \${response.status}`);
      }

      const data = await response.text();

      // Clear existing content first
      contentSection.innerHTML = "";

      // Small delay to ensure proper DOM cleanup
      setTimeout(() => {
        contentSection.innerHTML = data;
        updateURL(url);

        // Execute any inline scripts in the new content
        Array.from(contentSection.getElementsByTagName("script")).forEach(
          (script) => {
            const newScript = document.createElement("script");
            Array.from(script.attributes).forEach((attr) => {
              newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = script.textContent;
            script.parentNode.replaceChild(newScript, script);
          }
        );

        if (window.innerWidth <= 768) {
          leftSidebar.classList.remove("show");
        }
      }, 50);
    } catch (error) {
      handleError(error);
    }
  }

  // Debug function to log click events
  //function logClickDetails(event, element) {
  //    console.log('Click event:', {
  //        target: event.target,
  //        currentTarget: event.currentTarget,
  //        element: element,
  //        href: element?.href,
  //        classList: element?.classList
  //    });
  //}

  // Intercept left sidebar link clicks
  leftSidebar.addEventListener("click", function (event) {
    const link = event.target.closest("a");

    // If no link was clicked, exit early
    if (!link) return;

    // Debug logging
    //logClickDetails(event, link);

    // If the link is a collapse toggle, let it handle naturally
    if (link.getAttribute("data-bs-toggle") === "collapse") {
      return;
    }

    // At this point, we know it's a navigation link
    event.preventDefault();
    event.stopPropagation();

    // Check if we have a valid URL
    if (link.href) {
      loadContent(link.href);

      // Close mobile sidebar if needed
      if (window.innerWidth <= 768) {
        leftSidebar.classList.remove("show");
      }

      // If this is inside a collapse menu, keep it open
      const parentCollapse = link.closest(".collapse");
      if (parentCollapse) {
        parentCollapse.classList.add("show");
      }
    }
  });

  // Handle browser back/forward buttons
  window.addEventListener("popstate", function (event) {
    loadContent(window.location.href);
  });

  // Handle doc links in main content area
  document
    .getElementById("content-section")
    .addEventListener("click", function (event) {
      const docLink = event.target.closest(".doc-link");
      if (docLink) {
        event.preventDefault();
        loadContent(docLink.href);
      }
    });
});
