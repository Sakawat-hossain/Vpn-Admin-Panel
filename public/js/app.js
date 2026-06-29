/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/shared.js":
/*!**********************************************!*\
  !*** ./resources/assets/global/js/shared.js ***!
  \**********************************************/
/***/ (() => {

(function ($) {
  "use strict";

  document.querySelectorAll('[data-year]').forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
  var inputNumeric = document.querySelectorAll('.input-numeric');
  if (inputNumeric) {
    inputNumeric.forEach(function (el) {
      el.oninput = function () {
        el.value = el.value.replace(/[^0-9]/g, '').substr(0, 6);
      };
    });
  }
  var clipboardBtn = document.querySelectorAll(".btn-copy");
  if (clipboardBtn) {
    clipboardBtn.forEach(function (el) {
      var clipboard = new ClipboardJS(el);
      clipboard.on("success", function () {
        toastr.success(getConfig.copiedToClipboardSuccess);
      });
    });
  }
  var actionConfirm = $('.action-confirm');
  if (actionConfirm.length) {
    actionConfirm.on('click', function (e) {
      if (!confirm(getConfig.actionConfirm)) {
        e.preventDefault();
      }
    });
  }
})(jQuery);

/***/ }),

/***/ "./resources/assets/main/js/app.js":
/*!*****************************************!*\
  !*** ./resources/assets/main/js/app.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

__webpack_require__(/*! ../../global/js/shared */ "./resources/js/shared.js");
__webpack_require__(/*! ./main */ "./resources/js/main.js");

/***/ }),

/***/ "./resources/assets/main/js/main.js":
/*!******************************************!*\
  !*** ./resources/assets/main/js/main.js ***!
  \******************************************/
/***/ (() => {

(function ($) {
  "use strict";

  // AOS
  var aosInit = function aosInit() {
    if ($('[data-aos]').length > 0) {
      AOS.init({
        once: true,
        disable: 'mobile'
      });
    }
  };
  aosInit();

  // Dropdown
  var dropdown = document.querySelectorAll('[data-dropdown]');
  if (dropdown != null) {
    dropdown.forEach(function (el) {
      var dropdownMenu = el.querySelector(".drop-down-menu");
      function dropdownOP() {
        if (el.getBoundingClientRect().top + dropdownMenu.offsetHeight > window.innerHeight - 60 && el.getAttribute("data-dropdown-position") !== "top") {
          dropdownMenu.style.top = "auto";
          dropdownMenu.style.bottom = "40px";
        } else {
          dropdownMenu.style.top = "40px";
          dropdownMenu.style.bottom = "auto";
        }
      }
      window.addEventListener("click", function (e) {
        if (el.contains(e.target)) {
          el.classList.toggle('active');
          setTimeout(function () {
            el.classList.toggle('animated');
          }, 0);
        } else {
          el.classList.remove('active');
          el.classList.remove('animated');
        }
        dropdownOP();
      });
      window.addEventListener("resize", dropdownOP);
      window.addEventListener("scroll", dropdownOP);
    });
  }

  // Navbar
  var navbar = document.querySelector(".nav-bar");
  if (navbar) {
    var navbarOp = function navbarOp() {
      if (window.scrollY > 0) {
        navbar.classList.add("scrolling");
      } else {
        navbar.classList.remove("scrolling");
      }
    };
    window.addEventListener("scroll", navbarOp);
    window.addEventListener("load", navbarOp);
  }

  // Navbar Menu
  var navbarMenu = document.querySelector(".nav-bar-menu"),
    navbarMenuBtn = document.querySelector(".nav-bar-menu-btn");
  if (navbarMenu) {
    var navbarMenuClose = navbarMenu.querySelector(".nav-bar-menu-close"),
      navbarMenuOverlay = navbarMenu.querySelector(".overlay"),
      navUploadBtn = document.querySelector(".nav-bar-menu [data-upload-btn]");
    navbarMenuBtn.onclick = function () {
      navbarMenu.classList.add("show");
      document.body.classList.add("overflow-hidden");
    };
    navbarMenuClose.onclick = navbarMenuOverlay.onclick = function () {
      navbarMenu.classList.remove("show");
      document.body.classList.remove("overflow-hidden");
    };
    if (navUploadBtn) {
      navUploadBtn.addEventListener("click", function () {
        navbarMenu.classList.remove("show");
      });
    }
  }

  // Plan Switcher
  var plans = document.querySelectorAll(".plans .plans-item"),
    planSwitcher = document.querySelector(".plan-switcher");
  if (planSwitcher) {
    planSwitcher.querySelectorAll(".plan-switcher-item").forEach(function (el, id) {
      el.onclick = function () {
        planSwitcher.querySelectorAll(".plan-switcher-item").forEach(function (ele) {
          ele.classList.remove("active");
        });
        el.classList.add("active");
        plans.forEach(function (el) {
          el.classList.remove("active");
        });
        plans[id].classList.add("active");
      };
    });
  }

  // Lazyload
  var lazyLoad = function lazyLoad() {
    var lazy = $('.lazy');
    if (lazy.length) {
      lazy.Lazy({
        afterLoad: function afterLoad(element) {
          element.addClass('loaded');
        }
      });
    }
  };
  lazyLoad();
  var avatarInput = $('#change_avatar'),
    targetedImagePreview = $('#avatar_preview');
  if (avatarInput.length) {
    avatarInput.on('change', function () {
      var file = true,
        readLogoURL;
      if (file) {
        readLogoURL = function readLogoURL(input_file) {
          if (input_file.files && input_file.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
              targetedImagePreview.attr('src', e.target.result);
            };
            reader.readAsDataURL(input_file.files[0]);
          }
        };
      }
      readLogoURL(this);
    });
  }
  var generatorOptionsBtn = $('#generator-options-btn'),
    generatorOptions = $('.generator-options');
  generatorOptionsBtn.on('click', function () {
    if (generatorOptions.hasClass('d-none')) {
      generatorOptions.removeClass('d-none');
    } else {
      generatorOptions.addClass('d-none');
    }
  });
  var generatorForm = $('#generator');
  generatorForm.on('submit', function (e) {
    var reportValidity = generatorForm[0].reportValidity();
    if (reportValidity) {
      var onAjaxStart = function onAjaxStart() {
        generatorBtn.prop('disabled', true);
        generatorForm.addClass('d-none');
        defaultImages.addClass('d-none');
        viewAllImagesButton.addClass('d-none');
        faqs.addClass('d-none');
        blogArticles.addClass('d-none');
        generatorProcessing.removeClass('d-none');
      };
      var onAjaxStop = function onAjaxStop() {
        generatorBtn.prop('disabled', false);
        generatorProcessing.addClass('d-none');
        generatorForm.removeClass('d-none');
        viewAllImagesButton.removeClass('d-none');
        faqs.removeClass('d-none');
        blogArticles.removeClass('d-none');
        aosInit();
      };
      e.preventDefault();
      var action = $(this).attr('action'),
        formData = generatorForm.serializeArray(),
        generatorPromptInput = $('#generator input'),
        generatorSamples = $('#generator select[name=samples]'),
        generatorImagesSize = $('#generator select[name=size]'),
        generatorBtn = $('#generator button'),
        generatorProcessing = $('.processing');
      var defaultImages = $('#default-images'),
        generatedImages = $('#generated-images'),
        viewAllImagesButton = $('#viewAllImagesButton'),
        faqs = $('#faqs'),
        blogArticles = $('#blogArticles');
      if (generatorPromptInput.val() === '') {
        toastr.error(getConfig.generatorPromptError);
      } else if (generatorSamples.val() === null) {
        toastr.error(getConfig.generatorSamplesError);
      } else if (generatorImagesSize.val() === null) {
        toastr.error(getConfig.generatorSizeError);
      } else {
        $.ajax({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          url: action,
          type: "POST",
          data: formData,
          dataType: 'json',
          beforeSend: function beforeSend() {
            onAjaxStart();
          },
          success: function success(response) {
            onAjaxStop();
            if ($.isEmptyObject(response.error)) {
              $.each(response.images, function (index, item) {
                generatedImages.append('<div class="col"> <div class="ai-image"> <img class="lazy" data-src="' + item.src + '" alt="' + item.prompt + '" /> <div class="spinner-border"></div> <div class="ai-image-hover"> <p class="mb-0">' + item.prompt + '</p> <div class="row g-2 alig-items-center"> <div class="col"> <a href="' + item.link + '" target="_blank" class="btn btn-primary btn-md w-100">' + getConfig.translates.viewImage + '</a> </div> <div class="col-auto"> <a href="' + item.download_link + '" class="btn btn-light btn-md px-3"><i class="fas fa-download"></i></a> </div> </div> </div> </div> </div>');
                generatedImages.removeClass('d-none');
                lazyLoad();
              });
            } else {
              onAjaxStop();
              generatedImages.addClass('d-none');
              defaultImages.removeClass('d-none');
              viewAllImagesButton.removeClass('d-none');
              toastr.error(response.error);
            }
          },
          error: function error(jqXHR, textStatus, errorThrown) {
            onAjaxStop();
            generatedImages.addClass('d-none');
            defaultImages.removeClass('d-none');
            viewAllImagesButton.removeClass('d-none');
            toastr.error(errorThrown);
          }
        });
      }
    }
  });
  var editImage = $('.edit-image'),
    editImageModal = $('#editImageModal'),
    editImageModalForm = $('#editImageModal form'),
    editImageModalImg = $('#editImageModal img'),
    editModalVisibility = document.querySelector("#editImageModal select[name=visibility]");
  editImage.on('click', function (e) {
    e.preventDefault();
    var details = $(this).data('details');
    editImageModalForm.attr('action', details.action);
    editImageModalImg.attr('src', details.image);
    var visibility = editModalVisibility.querySelector("option[value=\"".concat(details.visibility, "\"]"));
    visibility.selected = true;
    editImageModal.modal('show');
  });

  // Switch Mode
  var themeBtn = document.querySelector(".btn-theme"),
    logoDark = document.querySelector(".logo-dark"),
    logoLight = document.querySelector(".logo-light");
  if (themeBtn) {
    themeBtn.onclick = function () {
      document.body.classList.toggle("dark");
      if (document.body.classList.contains("dark")) {
        document.cookie = "Theme=dark; expires=31 Dec 2080 12:00:00 GMT; path=/";
        logoDark.classList.add("d-none");
        logoLight.classList.remove("d-none");
      } else {
        document.cookie = "Theme=light; expires=31 Dec 2080 12:00:00 GMT; path=/";
        logoLight.classList.add("d-none");
        logoDark.classList.remove("d-none");
      }
    };
  }
  if (document.cookie.indexOf("Theme=dark") != -1) {
    document.body.classList.add("dark");
    logoDark.classList.add("d-none");
    logoLight.classList.remove("d-none");
  } else if (document.cookie.indexOf("Theme=light") != -1) {
    document.body.classList.remove("dark");
    logoLight.classList.add("d-none");
    logoDark.classList.remove("d-none");
  } else {
    if (config.themeMode == "auto") {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add("dark");
        logoDark.classList.add("d-none");
        logoLight.classList.remove("d-none");
      } else {
        document.body.classList.remove("dark");
        logoLight.classList.add("d-none");
        logoDark.classList.remove("d-none");
      }
    } else if (config.themeMode == "dark") {
      document.body.classList.add("dark");
      logoDark.classList.add("d-none");
      logoLight.classList.remove("d-none");
    } else {
      document.body.classList.remove("dark");
      logoLight.classList.add("d-none");
      logoDark.classList.remove("d-none");
    }
  }
})(jQuery);

/***/ }),

/***/ "./resources/assets/main/css/app.css":
/*!*******************************************!*\
  !*** ./resources/assets/main/css/app.css ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/app": 0,
/******/ 			"css/app": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk"] = self["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/app"], () => (__webpack_require__("./resources/assets/main/js/app.js")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/app"], () => (__webpack_require__("./resources/assets/main/css/app.css")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;