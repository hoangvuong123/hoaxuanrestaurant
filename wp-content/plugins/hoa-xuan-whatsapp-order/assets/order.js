(() => {
  "use strict";

  const config = window.HXOrderConfig;
  if (!config || !config.items) return;

  const resolveWebsitePrimary = () => {
    const roots = [document.documentElement, document.body].filter(Boolean);
    const variables = [
      "--ast-global-color-0",
      "--ast-global-color-1",
      "--e-global-color-primary",
      "--wp--preset--color--primary",
      "--wp--preset--color--accent-1",
      "--primary-color",
      "--theme-color",
      "--accent-color",
    ];
    for (const root of roots) {
      const style = getComputedStyle(root);
      for (const variable of variables) {
        const value = style.getPropertyValue(variable).trim();
        if (value) return value;
      }
    }
    return "";
  };

  const applyAccentColor = () => {
    const fallback = "#c9c518";
    const accent =
      config.colorMode === "custom"
        ? String(config.customColor || fallback).trim()
        : resolveWebsitePrimary() || fallback;
    document.documentElement.style.setProperty("--hx-order-accent", accent);
  };

  applyAccentColor();

  const products = new Map(Object.entries(config.items));
  const isEnglish =
    document.documentElement.lang.toLowerCase().startsWith("en") ||
    document.querySelector(".our-menu-page")?.dataset.lang === "en";
  const text = isEnglish
    ? {
      add: "Order",
      cart: "Your order",
      empty: "Your order is empty.",
      choice: "Choose an option",
      quantity: "Quantity",
      note: "Note for this item (optional)",
      generalNote: "Note for the whole order (optional)",
      addNote: "Add note",
      editNote: "Edit note",
      save: "Save",
      addToCart: "Add to order",
      edit: "Edit",
      remove: "Remove",
      total: "Total",
      whatsapp: "Order via WhatsApp",
      unitPrice: "Unit price",
      subtotal: "Subtotal",
      close: "Close",
      missingChoice: "Please choose an option.",
      missingPhone: "Please configure the WhatsApp number first.",
      added: "Added to your order",
      updated: "Item updated",
      noImage: "No Image",
    }
    : {
      add: "Bestellen",
      cart: "Ihre Bestellung",
      empty: "Ihre Bestellung ist leer.",
      choice: "Option auswählen",
      quantity: "Anzahl",
      note: "Hinweis zu diesem Gericht (optional)",
      generalNote: "Hinweis zur gesamten Bestellung (optional)",
      addNote: "Hinweis hinzufügen",
      editNote: "Hinweis bearbeiten",
      save: "Speichern",
      addToCart: "Zur Bestellung hinzufügen",
      edit: "Bearbeiten",
      remove: "Entfernen",
      total: "Gesamt",
      whatsapp: "Über WhatsApp bestellen",
      unitPrice: "Einzelpreis",
      subtotal: "Zwischensumme",
      close: "Schließen",
      missingChoice: "Bitte wählen Sie eine Option.",
      missingPhone: "Bitte zuerst die WhatsApp-Nummer konfigurieren.",
      added: "Zur Bestellung hinzugefügt",
      updated: "Gericht aktualisiert",
      noImage: "Kein Bild",
    };

  const euro = new Intl.NumberFormat("de-DE", {
    style: "currency",
    currency: "EUR",
  });
  const escapeHtml = (value) =>
    String(value ?? "").replace(
      /[&<>'"]/g,
      (character) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          "'": "&#039;",
          '"': "&quot;",
        })[character],
    );
  const productTitle = (product) =>
    (isEnglish && product.titleEn ? product.titleEn : product.titleDe) || "";
  const choiceTitle = (choice) =>
    (isEnglish && choice.nameEn ? choice.nameEn : choice.nameDe) || choice.nameDe || "";
  const makeId = () =>
    window.crypto?.randomUUID?.() ||
    `hx-${Date.now()}-${Math.random().toString(16).slice(2)}`;

  const visitorStorageKey = `${config.storageKey}-visitor`;
  const getVisitorId = () => {
    try {
      let id = localStorage.getItem(visitorStorageKey);
      if (!id) {
        id = makeId();
        localStorage.setItem(visitorStorageKey, id);
      }
      return id;
    } catch (_error) {
      return makeId();
    }
  };

  const detectSource = () => {
    const params = new URLSearchParams(window.location.search);
    const utmSource = params.get("utm_source");
    if (utmSource) return utmSource.slice(0, 64);
    const ref = (document.referrer || "").toLowerCase();
    if (!ref) return "Direct";
    if (ref.includes("facebook.com") || ref.includes("fb.com")) return "Facebook";
    if (ref.includes("instagram.com")) return "Instagram";
    if (ref.includes("google.")) return "Google";
    try {
      return new URL(document.referrer).hostname.replace(/^www\./, "").slice(0, 64);
    } catch (_error) {
      return "Referral";
    }
  };

  const detectDevice = () => {
    const ua = navigator.userAgent || "";
    if (/ipad|tablet|playbook|silk/i.test(ua)) return "tablet";
    if (/mobile|iphone|ipod|android/i.test(ua)) return "mobile";
    return "desktop";
  };

  const trackWhatsAppClick = ({ orderNumber, total }) => {
    if (!config.ajaxUrl || !config.trackNonce) return;
    const data = new FormData();
    data.append("action", "hx_track_whatsapp");
    data.append("nonce", config.trackNonce);
    data.append("order_number", orderNumber);
    data.append("visitor_id", getVisitorId());
    data.append("item_count", String(cart.reduce((sum, line) => sum + Number(line.quantity || 0), 0)));
    data.append(
      "items",
      JSON.stringify(
        cart.map((line) => ({
          code: line.code || "",
          title: line.title || "",
          choice: [line.choiceCode, line.choiceName].filter(Boolean).join(" - "),
          quantity: Number(line.quantity || 1),
          unitPrice: Number(line.unitPrice || 0),
          note: line.note || "",
        })),
      ),
    );
    data.append("order_note", generalNote);
    data.append("total", String(total));
    data.append("currency", "EUR");
    data.append("page_url", window.location.href);
    data.append("referrer", document.referrer || "");
    data.append("source", detectSource());
    data.append("device", detectDevice());

    if (navigator.sendBeacon) {
      navigator.sendBeacon(config.ajaxUrl, data);
      return;
    }

    fetch(config.ajaxUrl, {
      method: "POST",
      body: data,
      credentials: "same-origin",
      keepalive: true,
    }).catch(() => { });
  };

  let cart = [];
  try {
    const stored = JSON.parse(localStorage.getItem(config.storageKey) || "[]");
    if (Array.isArray(stored)) {
      cart = stored.filter(
        (line) => products.has(String(line.postId)) && Number(line.quantity) > 0,
      );
    }
  } catch (_error) {
    cart = [];
  }

  const generalNoteStorageKey = `${config.storageKey}-general-note`;
  let generalNote = "";
  try {
    generalNote = (localStorage.getItem(generalNoteStorageKey) || "").slice(0, 500);
  } catch (_error) {
    generalNote = "";
  }

  document.body.insertAdjacentHTML(
    "beforeend",
    `<button class="hx-cart-trigger" type="button" title="${escapeHtml(text.cart)}" aria-label="${escapeHtml(text.cart)}" data-no-translation>
      <span class="dashicons dashicons-cart" aria-hidden="true"></span>
      <span class="hx-cart-count">0</span>
    </button>
    <div class="hx-order-toast" role="status" aria-live="polite" aria-atomic="true" data-no-translation>
      <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
      <span><strong class="hx-order-toast__title"></strong><span class="hx-order-toast__message"></span></span>
    </div>
    <div class="hx-order-overlay" data-no-translation hidden></div>
    <aside class="hx-cart-drawer" aria-hidden="true" aria-label="${escapeHtml(text.cart)}" data-no-translation>
      <header class="hx-panel-header">
        <h2>${escapeHtml(text.cart)}</h2>
        <button class="hx-icon-button hx-close-cart" type="button" title="${escapeHtml(text.close)}" aria-label="${escapeHtml(text.close)}"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
      </header>
      <div class="hx-cart-lines"></div>
      <section class="hx-order-general-note" hidden>
        <button class="hx-note-toggle hx-general-note-toggle" type="button" aria-expanded="false"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><span>${escapeHtml(text.addNote)}</span></button>
        <label class="hx-general-note-field" hidden>
          <span>${escapeHtml(text.generalNote)}</span>
          <textarea rows="2" maxlength="500"></textarea>
        </label>
        <p class="hx-general-note-preview" hidden></p>
      </section>
      <footer class="hx-cart-footer">
        <div class="hx-cart-total"><span>${escapeHtml(text.total)}</span><strong>0,00 €</strong></div>
        <button class="hx-whatsapp-button" type="button"><span class="dashicons dashicons-format-chat" aria-hidden="true"></span>${escapeHtml(text.whatsapp)}</button>
      </footer>
    </aside>
    <div class="hx-product-modal" role="dialog" aria-modal="true" aria-labelledby="hx-product-modal-title" data-no-translation hidden>
      <div class="hx-product-modal__panel">
        <header class="hx-panel-header">
          <h2 id="hx-product-modal-title" tabindex="-1"></h2>
          <button class="hx-icon-button hx-close-modal" type="button" title="${escapeHtml(text.close)}" aria-label="${escapeHtml(text.close)}"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
        </header>
        <div class="hx-product-modal__body">
          <div class="hx-modal-image-wrapper" hidden>
            <img class="hx-modal-image hx-modal-zoom-btn" src="" alt="" title="Phóng to" data-image="">
          </div>
          <form class="hx-product-form">
          <fieldset class="hx-choice-fieldset">
            <legend>${escapeHtml(text.choice)}</legend>
            <div class="hx-choice-list"></div>
          </fieldset>
          <div class="hx-quantity-field" role="group" aria-labelledby="hx-quantity-label">
            <span id="hx-quantity-label">${escapeHtml(text.quantity)}</span>
            <div class="hx-modal-stepper">
              <button type="button" data-modal-quantity="decrease" aria-label="${escapeHtml(text.quantity)} −">−</button>
              <input name="quantity" type="text" value="1" inputmode="numeric" pattern="[0-9]*" aria-labelledby="hx-quantity-label">
              <button type="button" data-modal-quantity="increase" aria-label="${escapeHtml(text.quantity)} +">+</button>
            </div>
          </div>
          <div class="hx-modal-price" aria-live="polite">
            <span>${escapeHtml(text.total)}</span>
            <span class="hx-modal-price__values"><small></small><strong>0,00 €</strong></span>
          </div>
          <button class="hx-note-toggle hx-modal-note-toggle" type="button" aria-expanded="false"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><span>${escapeHtml(text.addNote)}</span></button>
          <label class="hx-note-field" hidden><span>${escapeHtml(text.note)}</span><textarea name="note" rows="3" maxlength="300"></textarea></label>
          <p class="hx-form-error" role="alert" hidden></p>
          <div class="hx-modal-submit-wrap">
            <button class="hx-modal-submit" type="submit">${escapeHtml(text.addToCart)}</button>
          </div>
          </form>
        </div>
      </div>
    </div>`,
  );

  const trigger = document.querySelector(".hx-cart-trigger");
  const badge = document.querySelector(".hx-cart-count");
  const overlay = document.querySelector(".hx-order-overlay");
  const drawer = document.querySelector(".hx-cart-drawer");
  const cartLines = document.querySelector(".hx-cart-lines");
  const generalNoteSection = document.querySelector(".hx-order-general-note");
  const generalNoteToggle = document.querySelector(".hx-general-note-toggle");
  const generalNoteField = document.querySelector(".hx-general-note-field");
  const generalNoteTextarea = generalNoteField.querySelector("textarea");
  const generalNotePreview = document.querySelector(".hx-general-note-preview");
  const cartFooter = document.querySelector(".hx-cart-footer");
  const totalElement = document.querySelector(".hx-cart-total strong");
  const modal = document.querySelector(".hx-product-modal");
  const toast = document.querySelector(".hx-order-toast");
  const toastTitle = document.querySelector(".hx-order-toast__title");
  const toastMessage = document.querySelector(".hx-order-toast__message");
  const modalTitle = document.querySelector("#hx-product-modal-title");
  const form = document.querySelector(".hx-product-form");
  const choiceFieldset = document.querySelector(".hx-choice-fieldset");
  const choiceList = document.querySelector(".hx-choice-list");
  const formError = document.querySelector(".hx-form-error");
  const modalNoteField = document.querySelector(".hx-note-field");
  const modalNoteToggle = document.querySelector(".hx-modal-note-toggle");
  const modalPrice = document.querySelector(".hx-modal-price");
  const modalPriceCalculation = document.querySelector(".hx-modal-price small");
  const modalPriceTotal = document.querySelector(".hx-modal-price strong");
  let modalProduct = null;
  let editingLineId = null;
  let toastTimer = null;
  const openNotes = new Set();

  function showToast(title, message) {
    window.clearTimeout(toastTimer);
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.classList.add("is-visible");
    toastTimer = window.setTimeout(() => toast.classList.remove("is-visible"), 2400);
  }

  const saveCart = () => {
    localStorage.setItem(config.storageKey, JSON.stringify(cart));
    renderCart();
  };

  const linePrice = (line) => Number(line.unitPrice) * Number(line.quantity);
  const cartTotal = () => cart.reduce((sum, line) => sum + linePrice(line), 0);

  function updateModalPrice() {
    if (!modalProduct) return;
    const selectedId = choiceList.querySelector('input[name="choice"]:checked')?.value || "";
    const choice = modalProduct.choices.find((entry) => entry.id === selectedId) || null;
    const unitPrice = choice?.price ?? modalProduct.basePrice;
    const quantity = Math.max(1, Math.min(99, Number(form.elements.quantity.value) || 1));
    const pending = (modalProduct.choices.length > 0 && !choice) || unitPrice === null;
    modalPrice.classList.toggle("is-pending", pending);
    modalPriceCalculation.textContent = pending
      ? text.choice
      : `${euro.format(unitPrice)} × ${quantity}`;
    modalPriceTotal.textContent = pending ? "—" : euro.format(Number(unitPrice) * quantity);

    const imgWrapper = modal.querySelector(".hx-modal-image-wrapper");
    if (imgWrapper && modalProduct) {
      const hasAnyImage = !!modalProduct.image || (modalProduct.choices && modalProduct.choices.some(c => c.image));
      const imgEl = imgWrapper.querySelector(".hx-modal-image");
      const imgToUse = choice?.image || modalProduct.image || "";

      if (hasAnyImage) {
        imgWrapper.hidden = false;
        imgWrapper.setAttribute("data-no-image", text.noImage);
        if (imgToUse) {
          imgWrapper.classList.remove("hx-no-image-placeholder");
          imgWrapper.classList.add("hx-image-loading");
          imgEl.style.display = "block";
          imgEl.style.opacity = "0";
          imgEl.onload = () => {
            imgWrapper.classList.remove("hx-image-loading");
            imgEl.style.opacity = "1";
          };
          imgEl.onerror = () => {
            imgWrapper.classList.remove("hx-image-loading");
            imgWrapper.classList.add("hx-no-image-placeholder");
            imgEl.style.display = "none";
          };
          imgEl.src = imgToUse;
          imgEl.dataset.image = imgToUse;
        } else {
          imgEl.src = "";
          imgEl.dataset.image = "";
          imgEl.style.display = "none";
          imgWrapper.classList.add("hx-no-image-placeholder");
          imgWrapper.classList.remove("hx-image-loading");
        }
      } else {
        imgWrapper.hidden = true;
      }
    }
  }

  function renderCart() {
    const itemCount = cart.reduce((sum, line) => sum + Number(line.quantity), 0);
    badge.textContent = itemCount > 99 ? "99+" : String(itemCount);
    badge.setAttribute("aria-label", `${itemCount} ${text.quantity}`);
    trigger.classList.toggle("has-items", itemCount > 0);
    totalElement.textContent = euro.format(cartTotal());
    cartFooter.hidden = cart.length === 0;
    generalNoteSection.hidden = cart.length === 0;
    if (cart.length > 0) {
      const noteIsOpen = !generalNoteField.hidden;
      generalNoteToggle.querySelector("span:last-child").textContent = generalNote ? text.editNote : text.addNote;
      generalNoteToggle.setAttribute("aria-expanded", noteIsOpen ? "true" : "false");
      generalNoteTextarea.value = generalNote;
      generalNotePreview.textContent = generalNote;
      generalNotePreview.hidden = noteIsOpen || !generalNote;
    }

    if (cart.length === 0) {
      cartLines.innerHTML = `<p class="hx-empty-cart">${escapeHtml(text.empty)}</p>`;
      return;
    }

    cartLines.innerHTML = cart
      .map((line) => {
        const option = line.choiceName
          ? `<p class="hx-cart-line__option">${escapeHtml(
            [line.choiceCode, line.choiceName].filter(Boolean).join(" - "),
          )}</p>`
          : "";
        const noteIsOpen = openNotes.has(line.id);
        const noteControl = `<button class="hx-note-toggle hx-cart-note-toggle" type="button" data-action="toggle-note" aria-expanded="${noteIsOpen ? "true" : "false"}"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><span>${escapeHtml(line.note ? text.editNote : text.addNote)}</span></button>`;
        const noteContent = noteIsOpen
          ? `<label class="hx-cart-note"><span>${escapeHtml(text.note)}</span><textarea rows="2" maxlength="300">${escapeHtml(line.note || "")}</textarea></label>`
          : line.note
            ? `<p class="hx-cart-note-preview">${escapeHtml(line.note)}</p>`
            : "";
        return `<article class="hx-cart-line" data-line-id="${escapeHtml(line.id)}">
          <div class="hx-cart-line__heading">
            <div><h3>${escapeHtml([line.code, line.title].filter(Boolean).join(". "))}</h3>${option}</div>
            <strong>${escapeHtml(euro.format(linePrice(line)))}</strong>
          </div>
          <div class="hx-cart-line__controls">
            <div class="hx-stepper" aria-label="${escapeHtml(text.quantity)}">
              <button type="button" data-action="decrease" aria-label="-">−</button>
              <span>${escapeHtml(line.quantity)}</span>
              <button type="button" data-action="increase" aria-label="+">+</button>
            </div>
            <span class="hx-cart-line__actions">
              <button class="hx-line-action" type="button" data-action="edit" title="${escapeHtml(text.edit)}" aria-label="${escapeHtml(text.edit)}"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>
              <button class="hx-line-action hx-line-action--danger" type="button" data-action="remove" title="${escapeHtml(text.remove)}" aria-label="${escapeHtml(text.remove)}"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
            </span>
          </div>
          ${noteControl}${noteContent}
        </article>`;
      })
      .join("");
  }

  function openDrawer() {
    renderCart();
    overlay.hidden = false;
    drawer.classList.add("is-open");
    drawer.setAttribute("aria-hidden", "false");
    document.body.classList.add("hx-order-open");
  }

  function closeDrawer() {
    drawer.classList.remove("is-open");
    drawer.setAttribute("aria-hidden", "true");
    if (modal.hidden) {
      overlay.hidden = true;
      document.body.classList.remove("hx-order-open");
    }
  }

  function openProductModal(product, line = null) {
    modalProduct = product;
    editingLineId = line?.id || null;
    modalTitle.textContent = [product.code, productTitle(product)].filter(Boolean).join(". ");
    form.elements.quantity.value = line?.quantity || 1;
    form.elements.note.value = line?.note || "";
    const hasNote = Boolean(line?.note);
    modalNoteField.hidden = !hasNote;
    modalNoteToggle.setAttribute("aria-expanded", hasNote ? "true" : "false");
    modalNoteToggle.querySelector("span:last-child").textContent = hasNote ? text.editNote : text.addNote;
    formError.hidden = true;
    form.querySelector(".hx-modal-submit").textContent = line ? text.save : text.addToCart;
    choiceFieldset.hidden = product.choices.length === 0;
    choiceList.innerHTML = product.choices
      .map((choice, index) => {
        const checked = line
          ? line.choiceId === choice.id
          : index === 0;
        const name = [choice.code, choiceTitle(choice)].filter(Boolean).join(" - ");
        return `<label class="hx-choice-option">
          <input type="radio" name="choice" value="${escapeHtml(choice.id)}" ${checked ? "checked" : ""}>
          <span><strong>${escapeHtml(name)}</strong>${choice.allergens ? `<small>${escapeHtml(choice.allergens)}</small>` : ""}</span>
          <b>${escapeHtml(euro.format(choice.price))}</b>
        </label>`;
      })
      .join("");
    updateModalPrice();
    modal.classList.remove("is-pointer-ready");
    overlay.hidden = false;
    modal.hidden = false;
    document.body.classList.add("hx-order-open");
    modalTitle.focus({ preventScroll: true });
  }

  function closeModal() {
    modal.hidden = true;
    modalProduct = null;
    editingLineId = null;
    if (!drawer.classList.contains("is-open")) {
      overlay.hidden = true;
      document.body.classList.remove("hx-order-open");
    }
  }

  function decorateMenu(root = document) {
    const candidates = [
      ...(root.matches?.(".menu-item[data-post-id]") ? [root] : []),
      ...(root.querySelectorAll?.(".menu-item[data-post-id]") || []),
    ];
    candidates.forEach((element) => {
      if (element.dataset.hxOrderDecorated === "true") return;
      const product = products.get(String(element.dataset.postId));
      if (!product) return;
      const title = element.querySelector(".menu-item__name");
      const titleRow = element.querySelector(".menu-item__title-row");
      if (!title) return;
      element.dataset.hxOrderDecorated = "true";
      title.classList.add("hx-order-title");

      element.classList.add("hx-order-card");
      element.dataset.postId = String(product.postId);
      element.setAttribute("role", "button");
      element.setAttribute("tabindex", "0");
      element.setAttribute("title", text.addToCart);
      element.setAttribute("aria-label", `${text.addToCart}: ${productTitle(product)}`);
    });
  }

  document.addEventListener("click", (event) => {
    const orderCard = event.target.closest(".hx-order-card");
    if (orderCard) {
      const product = products.get(String(orderCard.dataset.postId));
      if (product) openProductModal(product);
      return;
    }

    if (event.target.closest(".hx-cart-trigger")) openDrawer();
    if (event.target.closest(".hx-close-cart")) closeDrawer();
    if (event.target.closest(".hx-close-modal")) closeModal();

    const modalQuantityButton = event.target.closest("[data-modal-quantity]");
    if (modalQuantityButton) {
      const input = form.elements.quantity;
      const delta = modalQuantityButton.dataset.modalQuantity === "increase" ? 1 : -1;
      input.value = String(Math.max(1, Math.min(99, (Number(input.value) || 1) + delta)));
      updateModalPrice();
      return;
    }

    if (event.target.closest(".hx-modal-note-toggle")) {
      const willOpen = modalNoteField.hidden;
      modalNoteField.hidden = !willOpen;
      modalNoteToggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
      if (willOpen) modalNoteField.querySelector("textarea").focus();
      return;
    }

    if (event.target.closest(".hx-general-note-toggle")) {
      const willOpen = generalNoteField.hidden;
      generalNoteField.hidden = !willOpen;
      generalNoteToggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
      generalNotePreview.hidden = willOpen || !generalNote;
      if (willOpen) {
        generalNoteTextarea.value = generalNote;
        generalNoteTextarea.focus();
      }
      return;
    }

    const actionButton = event.target.closest("[data-action]");
    const lineElement = actionButton?.closest(".hx-cart-line");
    if (!actionButton || !lineElement) return;
    const line = cart.find((entry) => entry.id === lineElement.dataset.lineId);
    if (!line) return;
    const action = actionButton.dataset.action;
    if (action === "increase") line.quantity = Math.min(99, Number(line.quantity) + 1);
    if (action === "decrease") line.quantity = Math.max(1, Number(line.quantity) - 1);
    if (action === "remove") cart = cart.filter((entry) => entry.id !== line.id);
    if (action === "toggle-note") {
      if (openNotes.has(line.id)) openNotes.delete(line.id);
      else openNotes.add(line.id);
      renderCart();
      if (openNotes.has(line.id)) {
        cartLines.querySelector(`[data-line-id="${CSS.escape(line.id)}"] textarea`)?.focus();
      }
      return;
    }
    if (action === "edit") {
      const product = products.get(String(line.postId));
      if (product) openProductModal(product, line);
      return;
    }
    saveCart();
  });

  cartLines.addEventListener("input", (event) => {
    if (!event.target.matches(".hx-cart-note textarea")) return;
    const lineElement = event.target.closest(".hx-cart-line");
    const line = cart.find((entry) => entry.id === lineElement?.dataset.lineId);
    if (!line) return;
    line.note = event.target.value.slice(0, 300);
    localStorage.setItem(config.storageKey, JSON.stringify(cart));
  });

  generalNoteTextarea.addEventListener("input", (event) => {
    generalNote = event.target.value.slice(0, 500);
    try {
      localStorage.setItem(generalNoteStorageKey, generalNote);
    } catch (_error) { }
    generalNoteToggle.querySelector("span:last-child").textContent = generalNote ? text.editNote : text.addNote;
  });

  generalNoteTextarea.addEventListener("blur", () => {
    generalNotePreview.textContent = generalNote;
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!modalProduct) return;
    const selectedId = new FormData(form).get("choice") || "";
    const choice = modalProduct.choices.find((entry) => entry.id === selectedId) || null;
    if (modalProduct.choices.length > 0 && !choice) {
      formError.textContent = text.missingChoice;
      formError.hidden = false;
      return;
    }
    const quantity = Math.max(1, Math.min(99, Number(form.elements.quantity.value) || 1));
    const note = form.elements.note.value.trim().slice(0, 300);
    const submittedTitle = productTitle(modalProduct);
    const payload = {
      id: editingLineId || makeId(),
      postId: modalProduct.postId,
      code: modalProduct.code,
      title: productTitle(modalProduct),
      choiceId: choice?.id || "",
      choiceCode: choice?.code || "",
      choiceName: choice ? choiceTitle(choice) : "",
      unitPrice: choice?.price ?? modalProduct.basePrice,
      quantity,
      note,
    };
    const wasEditing = Boolean(editingLineId);
    if (wasEditing) {
      cart = cart.map((line) => (line.id === editingLineId ? payload : line));
    } else {
      cart.push(payload);
    }
    saveCart();
    closeModal();
    if (wasEditing) {
      openDrawer();
      showToast(submittedTitle, text.updated);
    } else {
      showToast(submittedTitle, text.added);
    }
  });

  form.elements.quantity.addEventListener("input", (event) => {
    event.target.value = event.target.value.replace(/\D/g, "").slice(0, 2);
    updateModalPrice();
  });
  form.elements.quantity.addEventListener("blur", (event) => {
    event.target.value = String(Math.max(1, Math.min(99, Number(event.target.value) || 1)));
    updateModalPrice();
  });
  choiceList.addEventListener("change", updateModalPrice);
  modal.addEventListener(
    "pointermove",
    () => modal.classList.add("is-pointer-ready"),
    { passive: true },
  );

  document.querySelector(".hx-whatsapp-button").addEventListener("click", () => {
    const phone = String(config.whatsappNumber || "").replace(/\D/g, "");
    if (!phone) {
      window.alert(text.missingPhone);
      return;
    }
    if (cart.length === 0) return;
    const now = new Date();
    const orderNumber = `HX-${now.toISOString().slice(0, 10).replace(/-/g, "")}-${String(now.getTime()).slice(-5)}`;
    const total = cartTotal();
    const message = [
      `*${config.restaurantName || "Restaurant"}*`,
      config.messageIntro || "Neue Bestellung über die Speisekarte",
      `Bestellnummer: ${orderNumber}`,
      `Zeit: ${new Intl.DateTimeFormat("de-DE", { dateStyle: "medium", timeStyle: "short" }).format(now)}`,
      "",
      ...cart.flatMap((line, index) => [
        `*${index + 1}. ${[line.code, line.title].filter(Boolean).join(". ")}*`,
        ...(line.choiceName
          ? [`Auswahl: ${[line.choiceCode, line.choiceName].filter(Boolean).join(" - ")}`]
          : []),
        `Menge: ${line.quantity}`,
        `Einzelpreis: ${euro.format(line.unitPrice)}`,
        `Zwischensumme: ${euro.format(linePrice(line))}`,
        ...(line.note ? [`Hinweis: ${line.note}`] : []),
        "",
      ]),
      ...(generalNote ? [isEnglish ? `Order note: ${generalNote}` : `Hinweis zur Bestellung: ${generalNote}`, ""] : []),
      "--------------------",
      `*GESAMT: ${euro.format(total)}*`,
    ].join("\n");
    trackWhatsAppClick({ orderNumber, total });
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, "_blank", "noopener,noreferrer");
  });

  overlay.addEventListener("click", () => {
    closeModal();
    closeDrawer();
  });
  document.addEventListener("keydown", (event) => {
    if ((event.key === "Enter" || event.key === " ") && event.target.matches(".hx-order-card")) {
      event.preventDefault();
      const product = products.get(String(event.target.dataset.postId));
      if (product) openProductModal(product);
      return;
    }
    if (event.key !== "Escape") return;
    closeModal();
    closeDrawer();
  });

  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        if (node.nodeType === Node.ELEMENT_NODE) decorateMenu(node);
      }
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
  decorateMenu();
  renderCart();
})();
