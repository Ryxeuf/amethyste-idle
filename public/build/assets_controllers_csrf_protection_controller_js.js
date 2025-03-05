"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["assets_controllers_csrf_protection_controller_js"],{

/***/ "./assets/controllers/csrf_protection_controller.js":
/*!**********************************************************!*\
  !*** ./assets/controllers/csrf_protection_controller.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   generateCsrfHeaders: () => (/* binding */ generateCsrfHeaders),
/* harmony export */   generateCsrfToken: () => (/* binding */ generateCsrfToken),
/* harmony export */   removeCsrfToken: () => (/* binding */ removeCsrfToken)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_array_buffer_constructor_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.array-buffer.constructor.js */ "./node_modules/core-js/modules/es.array-buffer.constructor.js");
/* harmony import */ var core_js_modules_es_array_buffer_constructor_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_buffer_constructor_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_array_buffer_slice_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.array-buffer.slice.js */ "./node_modules/core-js/modules/es.array-buffer.slice.js");
/* harmony import */ var core_js_modules_es_array_buffer_slice_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_buffer_slice_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_data_view_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.data-view.js */ "./node_modules/core-js/modules/es.data-view.js");
/* harmony import */ var core_js_modules_es_data_view_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_data_view_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_typed_array_uint8_array_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.typed-array.uint8-array.js */ "./node_modules/core-js/modules/es.typed-array.uint8-array.js");
/* harmony import */ var core_js_modules_es_typed_array_uint8_array_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_uint8_array_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_typed_array_copy_within_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.typed-array.copy-within.js */ "./node_modules/core-js/modules/es.typed-array.copy-within.js");
/* harmony import */ var core_js_modules_es_typed_array_copy_within_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_copy_within_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_typed_array_every_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.typed-array.every.js */ "./node_modules/core-js/modules/es.typed-array.every.js");
/* harmony import */ var core_js_modules_es_typed_array_every_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_every_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_typed_array_fill_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.typed-array.fill.js */ "./node_modules/core-js/modules/es.typed-array.fill.js");
/* harmony import */ var core_js_modules_es_typed_array_fill_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_fill_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_typed_array_filter_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.typed-array.filter.js */ "./node_modules/core-js/modules/es.typed-array.filter.js");
/* harmony import */ var core_js_modules_es_typed_array_filter_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_filter_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_typed_array_find_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.typed-array.find.js */ "./node_modules/core-js/modules/es.typed-array.find.js");
/* harmony import */ var core_js_modules_es_typed_array_find_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_find_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_typed_array_find_index_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.typed-array.find-index.js */ "./node_modules/core-js/modules/es.typed-array.find-index.js");
/* harmony import */ var core_js_modules_es_typed_array_find_index_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_find_index_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_typed_array_for_each_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.typed-array.for-each.js */ "./node_modules/core-js/modules/es.typed-array.for-each.js");
/* harmony import */ var core_js_modules_es_typed_array_for_each_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_for_each_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_typed_array_includes_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.typed-array.includes.js */ "./node_modules/core-js/modules/es.typed-array.includes.js");
/* harmony import */ var core_js_modules_es_typed_array_includes_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_includes_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_typed_array_index_of_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.typed-array.index-of.js */ "./node_modules/core-js/modules/es.typed-array.index-of.js");
/* harmony import */ var core_js_modules_es_typed_array_index_of_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_index_of_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_typed_array_iterator_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.typed-array.iterator.js */ "./node_modules/core-js/modules/es.typed-array.iterator.js");
/* harmony import */ var core_js_modules_es_typed_array_iterator_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_iterator_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_typed_array_join_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.typed-array.join.js */ "./node_modules/core-js/modules/es.typed-array.join.js");
/* harmony import */ var core_js_modules_es_typed_array_join_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_join_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_typed_array_last_index_of_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.typed-array.last-index-of.js */ "./node_modules/core-js/modules/es.typed-array.last-index-of.js");
/* harmony import */ var core_js_modules_es_typed_array_last_index_of_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_last_index_of_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_typed_array_map_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.typed-array.map.js */ "./node_modules/core-js/modules/es.typed-array.map.js");
/* harmony import */ var core_js_modules_es_typed_array_map_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_map_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_typed_array_reduce_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.typed-array.reduce.js */ "./node_modules/core-js/modules/es.typed-array.reduce.js");
/* harmony import */ var core_js_modules_es_typed_array_reduce_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_reduce_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_typed_array_reduce_right_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.typed-array.reduce-right.js */ "./node_modules/core-js/modules/es.typed-array.reduce-right.js");
/* harmony import */ var core_js_modules_es_typed_array_reduce_right_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_reduce_right_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_typed_array_reverse_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.typed-array.reverse.js */ "./node_modules/core-js/modules/es.typed-array.reverse.js");
/* harmony import */ var core_js_modules_es_typed_array_reverse_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_reverse_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_typed_array_set_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.typed-array.set.js */ "./node_modules/core-js/modules/es.typed-array.set.js");
/* harmony import */ var core_js_modules_es_typed_array_set_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_set_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_typed_array_slice_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.typed-array.slice.js */ "./node_modules/core-js/modules/es.typed-array.slice.js");
/* harmony import */ var core_js_modules_es_typed_array_slice_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_slice_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_es_typed_array_some_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/es.typed-array.some.js */ "./node_modules/core-js/modules/es.typed-array.some.js");
/* harmony import */ var core_js_modules_es_typed_array_some_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_some_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_es_typed_array_sort_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/es.typed-array.sort.js */ "./node_modules/core-js/modules/es.typed-array.sort.js");
/* harmony import */ var core_js_modules_es_typed_array_sort_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_sort_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_es_typed_array_subarray_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/es.typed-array.subarray.js */ "./node_modules/core-js/modules/es.typed-array.subarray.js");
/* harmony import */ var core_js_modules_es_typed_array_subarray_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_subarray_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var core_js_modules_es_typed_array_to_locale_string_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! core-js/modules/es.typed-array.to-locale-string.js */ "./node_modules/core-js/modules/es.typed-array.to-locale-string.js");
/* harmony import */ var core_js_modules_es_typed_array_to_locale_string_js__WEBPACK_IMPORTED_MODULE_30___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_to_locale_string_js__WEBPACK_IMPORTED_MODULE_30__);
/* harmony import */ var core_js_modules_es_typed_array_to_string_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! core-js/modules/es.typed-array.to-string.js */ "./node_modules/core-js/modules/es.typed-array.to-string.js");
/* harmony import */ var core_js_modules_es_typed_array_to_string_js__WEBPACK_IMPORTED_MODULE_31___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_typed_array_to_string_js__WEBPACK_IMPORTED_MODULE_31__);
































var nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
var tokenCheck = /^[-_\/+a-zA-Z0-9]{24,}$/;

// Generate and double-submit a CSRF token in a form field and a cookie, as defined by Symfony's SameOriginCsrfTokenManager
document.addEventListener('submit', function (event) {
  generateCsrfToken(event.target);
}, true);

// When @hotwired/turbo handles form submissions, send the CSRF token in a header in addition to a cookie
// The `framework.csrf_protection.check_header` config option needs to be enabled for the header to be checked
document.addEventListener('turbo:submit-start', function (event) {
  var h = generateCsrfHeaders(event.detail.formSubmission.formElement);
  Object.keys(h).map(function (k) {
    event.detail.formSubmission.fetchRequest.headers[k] = h[k];
  });
});

// When @hotwired/turbo handles form submissions, remove the CSRF cookie once a form has been submitted
document.addEventListener('turbo:submit-end', function (event) {
  removeCsrfToken(event.detail.formSubmission.formElement);
});
function generateCsrfToken(formElement) {
  var csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');
  if (!csrfField) {
    return;
  }
  var csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
  var csrfToken = csrfField.value;
  if (!csrfCookie && nameCheck.test(csrfToken)) {
    csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken);
    csrfField.defaultValue = csrfToken = btoa(String.fromCharCode.apply(null, (window.crypto || window.msCrypto).getRandomValues(new Uint8Array(18))));
    csrfField.dispatchEvent(new Event('change', {
      bubbles: true
    }));
  }
  if (csrfCookie && tokenCheck.test(csrfToken)) {
    var cookie = csrfCookie + '_' + csrfToken + '=' + csrfCookie + '; path=/; samesite=strict';
    document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
  }
}
function generateCsrfHeaders(formElement) {
  var headers = {};
  var csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');
  if (!csrfField) {
    return headers;
  }
  var csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
  if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
    headers[csrfCookie] = csrfField.value;
  }
  return headers;
}
function removeCsrfToken(formElement) {
  var csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');
  if (!csrfField) {
    return;
  }
  var csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
  if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
    var cookie = csrfCookie + '_' + csrfField.value + '=0; path=/; samesite=strict; max-age=0';
    document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
  }
}

/* stimulusFetch: 'lazy' */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ('csrf-protection-controller');

/***/ })

}]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXNzZXRzX2NvbnRyb2xsZXJzX2NzcmZfcHJvdGVjdGlvbl9jb250cm9sbGVyX2pzLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7QUFBQSxJQUFNQSxTQUFTLEdBQUcsdUJBQXVCO0FBQ3pDLElBQU1DLFVBQVUsR0FBRyx5QkFBeUI7O0FBRTVDO0FBQ0FDLFFBQVEsQ0FBQ0MsZ0JBQWdCLENBQUMsUUFBUSxFQUFFLFVBQVVDLEtBQUssRUFBRTtFQUNqREMsaUJBQWlCLENBQUNELEtBQUssQ0FBQ0UsTUFBTSxDQUFDO0FBQ25DLENBQUMsRUFBRSxJQUFJLENBQUM7O0FBRVI7QUFDQTtBQUNBSixRQUFRLENBQUNDLGdCQUFnQixDQUFDLG9CQUFvQixFQUFFLFVBQVVDLEtBQUssRUFBRTtFQUM3RCxJQUFNRyxDQUFDLEdBQUdDLG1CQUFtQixDQUFDSixLQUFLLENBQUNLLE1BQU0sQ0FBQ0MsY0FBYyxDQUFDQyxXQUFXLENBQUM7RUFDdEVDLE1BQU0sQ0FBQ0MsSUFBSSxDQUFDTixDQUFDLENBQUMsQ0FBQ08sR0FBRyxDQUFDLFVBQVVDLENBQUMsRUFBRTtJQUM1QlgsS0FBSyxDQUFDSyxNQUFNLENBQUNDLGNBQWMsQ0FBQ00sWUFBWSxDQUFDQyxPQUFPLENBQUNGLENBQUMsQ0FBQyxHQUFHUixDQUFDLENBQUNRLENBQUMsQ0FBQztFQUM5RCxDQUFDLENBQUM7QUFDTixDQUFDLENBQUM7O0FBRUY7QUFDQWIsUUFBUSxDQUFDQyxnQkFBZ0IsQ0FBQyxrQkFBa0IsRUFBRSxVQUFVQyxLQUFLLEVBQUU7RUFDM0RjLGVBQWUsQ0FBQ2QsS0FBSyxDQUFDSyxNQUFNLENBQUNDLGNBQWMsQ0FBQ0MsV0FBVyxDQUFDO0FBQzVELENBQUMsQ0FBQztBQUVLLFNBQVNOLGlCQUFpQkEsQ0FBRU0sV0FB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cyI6WyJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2NvbnRyb2xsZXJzL2NzcmZfcHJvdGVjdGlvbl9jb250cm9sbGVyLmpzIl0sInNvdXJjZXNDb250ZW50IjpbImNvbnN0IG5hbWVDaGVjayA9IC9eWy1fYS16QS1aMC05XXs0LDIyfSQvO1xuY29uc3QgdG9rZW5DaGVjayA9IC9eWy1fXFwvK2EtekEtWjAtOV17MjQsfSQvO1xuXG4vLyBHZW5lcmF0ZSBhbmQgZG91YmxlLXN1Ym1pdCBhIENTUkYgdG9rZW4gaW4gYSBmb3JtIGZpZWxkIGFuZCBhIGNvb2tpZSwgYXMgZGVmaW5lZCBieSBTeW1mb255J3MgU2FtZU9yaWdpbkNzcmZUb2tlbk1hbmFnZXJcbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ3N1Ym1pdCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGdlbmVyYXRlQ3NyZlRva2VuKGV2ZW50LnRhcmdldCk7XG59LCB0cnVlKTtcblxuLy8gV2hlbiBAaG90d2lyZWQvdHVyYm8gaGFuZGxlcyBmb3JtIHN1Ym1pc3Npb25zLCBzZW5kIHRoZSBDU1JGIHRva2VuIGluIGEgaGVhZGVyIGluIGFkZGl0aW9uIHRvIGEgY29va2llXG4vLyBUaGUgYGZyYW1ld29yay5jc3JmX3Byb3RlY3Rpb24uY2hlY2tfaGVhZGVyYCBjb25maWcgb3B0aW9uIG5lZWRzIHRvIGJlIGVuYWJsZWQgZm9yIHRoZSBoZWFkZXIgdG8gYmUgY2hlY2tlZFxuZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcigndHVyYm86c3VibWl0LXN0YXJ0JywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgY29uc3QgaCA9IGdlbmVyYXRlQ3NyZkhlYWRlcnMoZXZlbnQuZGV0YWlsLmZvcm1TdWJtaXNzaW9uLmZvcm1FbGVtZW50KTtcbiAgICBPYmplY3Qua2V5cyhoKS5tYXAoZnVuY3Rpb24gKGspIHtcbiAgICAgICAgZXZlbnQuZGV0YWlsLmZvcm1TdWJtaXNzaW9uLmZldGNoUmVxdWVzdC5oZWFkZXJzW2tdID0gaFtrXTtcbiAgICB9KTtcbn0pO1xuXG4vLyBXaGVuIEBob3R3aXJlZC90dXJibyBoYW5kbGVzIGZvcm0gc3VibWlzc2lvbnMsIHJlbW92ZSB0aGUgQ1NSRiBjb29raWUgb25jZSBhIGZvcm0gaGFzIGJlZW4gc3VibWl0dGVkXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCd0dXJibzpzdWJtaXQtZW5kJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgcmVtb3ZlQ3NyZlRva2VuKGV2ZW50LmRldGFpbC5mb3JtU3VibWlzc2lvbi5mb3JtRWxlbWVudCk7XG59KTtcblxuZXhwb3J0IGZ1bmN0aW9uIGdlbmVyYXRlQ3NyZlRva2VuIChmb3JtRWxlbWVudCkge1xuICAgIGNvbnN0IGNzcmZGaWVsZCA9IGZvcm1FbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W2RhdGEtY29udHJvbGxlcj1cImNzcmYtcHJvdGVjdGlvblwiXSwgaW5wdXRbbmFtZT1cIl9jc3JmX3Rva2VuXCJdJyk7XG5cbiAgICBpZiAoIWNzcmZGaWVsZCkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuXG4gICAgbGV0IGNzcmZDb29raWUgPSBjc3JmRmllbGQuZ2V0QXR0cmlidXRlKCdkYXRhLWNzcmYtcHJvdGVjdGlvbi1jb29raWUtdmFsdWUnKTtcbiAgICBsZXQgY3NyZlRva2VuID0gY3NyZkZpZWxkLnZhbHVlO1xuXG4gICAgaWYgKCFjc3JmQ29va2llICYmIG5hbWVDaGVjay50ZXN0KGNzcmZUb2tlbikpIHtcbiAgICAgICAgY3NyZkZpZWxkLnNldEF0dHJpYnV0ZSgnZGF0YS1jc3JmLXByb3RlY3Rpb24tY29va2llLXZhbHVlJywgY3NyZkNvb2tpZSA9IGNzcmZUb2tlbik7XG4gICAgICAgIGNzcmZGaWVsZC5kZWZhdWx0VmFsdWUgPSBjc3JmVG9rZW4gPSBidG9hKFN0cmluZy5mcm9tQ2hhckNvZGUuYXBwbHkobnVsbCwgKHdpbmRvdy5jcnlwdG8gfHwgd2luZG93Lm1zQ3J5cHRvKS5nZXRSYW5kb21WYWx1ZXMobmV3IFVpbnQ4QXJyYXkoMTgpKSkpO1xuICAgICAgICBjc3JmRmllbGQuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ2NoYW5nZScsIHsgYnViYmxlczogdHJ1ZSB9KSk7XG4gICAgfVxuXG4gICAgaWYgKGNzcmZDb29raWUgJiYgdG9rZW5DaGVjay50ZXN0KGNzcmZUb2tlbikpIHtcbiAgICAgICAgY29uc3QgY29va2llID0gY3NyZkNvb2tpZSArICdfJyArIGNzcmZUb2tlbiArICc9JyArIGNzcmZDb29raWUgKyAnOyBwYXRoPS87IHNhbWVzaXRlPXN0cmljdCc7XG4gICAgICAgIGRvY3VtZW50LmNvb2tpZSA9IHdpbmRvdy5sb2NhdGlvbi5wcm90b2NvbCA9PT0gJ2h0dHBzOicgPyAnX19Ib3N0LScgKyBjb29raWUgKyAnOyBzZWN1cmUnIDogY29va2llO1xuICAgIH1cbn1cblxuZXhwb3J0IGZ1bmN0aW9uIGdlbmVyYXRlQ3NyZkhlYWRlcnMgKGZvcm1FbGVtZW50KSB7XG4gICAgY29uc3QgaGVhZGVycyA9IHt9O1xuICAgIGNvbnN0IGNzcmZGaWVsZCA9IGZvcm1FbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W2RhdGEtY29udHJvbGxlcj1cImNzcmYtcHJvdGVjdGlvblwiXSwgaW5wdXRbbmFtZT1cIl9jc3JmX3Rva2VuXCJdJyk7XG5cbiAgICBpZiAoIWNzcmZGaWVsZCkge1xuICAgICAgICByZXR1cm4gaGVhZGVycztcbiAgICB9XG5cbiAgICBjb25zdCBjc3JmQ29va2llID0gY3NyZkZpZWxkLmdldEF0dHJpYnV0ZSgnZGF0YS1jc3JmLXByb3RlY3Rpb24tY29va2llLXZhbHVlJyk7XG5cbiAgICBpZiAodG9rZW5DaGVjay50ZXN0KGNzcmZGaWVsZC52YWx1ZSkgJiYgbmFtZUNoZWNrLnRlc3QoY3NyZkNvb2tpZSkpIHtcbiAgICAgICAgaGVhZGVyc1tjc3JmQ29va2llXSA9IGNzcmZGaWVsZC52YWx1ZTtcbiAgICB9XG5cbiAgICByZXR1cm4gaGVhZGVycztcbn1cblxuZXhwb3J0IGZ1bmN0aW9uIHJlbW92ZUNzcmZUb2tlbiAoZm9ybUVsZW1lbnQpIHtcbiAgICBjb25zdCBjc3JmRmllbGQgPSBmb3JtRWxlbWVudC5xdWVyeVNlbGVjdG9yKCdpbnB1dFtkYXRhLWNvbnRyb2xsZXI9XCJjc3JmLXByb3RlY3Rpb25cIl0sIGlucHV0W25hbWU9XCJfY3NyZl90b2tlblwiXScpO1xuXG4gICAgaWYgKCFjc3JmRmllbGQpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cblxuICAgIGNvbnN0IGNzcmZDb29raWUgPSBjc3JmRmllbGQuZ2V0QXR0cmlidXRlKCdkYXRhLWNzcmYtcHJvdGVjdGlvbi1jb29raWUtdmFsdWUnKTtcblxuICAgIGlmICh0b2tlbkNoZWNrLnRlc3QoY3NyZkZpZWxkLnZhbHVlKSAmJiBuYW1lQ2hlY2sudGVzdChjc3JmQ29va2llKSkge1xuICAgICAgICBjb25zdCBjb29raWUgPSBjc3JmQ29va2llICsgJ18nICsgY3NyZkZpZWxkLnZhbHVlICsgJz0wOyBwYXRoPS87IHNhbWVzaXRlPXN0cmljdDsgbWF4LWFnZT0wJztcblxuICAgICAgICBkb2N1bWVudC5jb29raWUgPSB3aW5kb3cubG9jYXRpb24ucHJvdG9jb2wgPT09ICdodHRwczonID8gJ19fSG9zdC0nICsgY29va2llICsgJzsgc2VjdXJlJyA6IGNvb2tpZTtcbiAgICB9XG59XG5cbi8qIHN0aW11bHVzRmV0Y2g6ICdsYXp5JyAqL1xuZXhwb3J0IGRlZmF1bHQgJ2NzcmYtcHJvdGVjdGlvbi1jb250cm9sbGVyJztcbiJdLCJuYW1lcyI6WyJuYW1lQ2hlY2siLCJ0b2tlbkNoZWNrIiwiZG9jdW1lbnQiLCJhZGRFdmVudExpc3RlbmVyIiwiZXZlbnQiLCJnZW5lcmF0ZUNzcmZUb2tlbiIsInRhcmdldCIsImgiLCJnZW5lcmF0ZUNzcmZIZWFkZXJzIiwiZGV0YWlsIiwiZm9ybVN1Ym1pc3Npb24iLCJmb3JtRWxlbWVudCIsIk9iamVjdCIsImtleXMiLCJtYXAiLCJrIiwiZmV0Y2hSZXF1ZXN0IiwiaGVhZGVycyIsInJlbW92ZUNzcmZUb2tlbiIsImNzcmZGaWVsZCIsInF1ZXJ5U2VsZWN0b3IiLCJjc3JmQ29va2llIiwiZ2V0QXR0cmlidXRlIiwiY3NyZlRva2VuIiwidmFsdWUiLCJ0ZXN0Iiwic2V0QXR0cmlidXRlIiwiZGVmYXVsdFZhbHVlIiwiYnRvYSIsIlN0cmluZyIsImZyb21DaGFyQ29kZSIsImFwcGx5Iiwid2luZG93IiwiY3J5cHRvIiwibXNDcnlwdG8iLCJnZXRSYW5kb21WYWx1ZXMiLCJVaW50OEFycmF5IiwiZGlzcGF0Y2hFdmVudCIsIkV2ZW50IiwiYnViYmxlcyIsImNvb2tpZSIsImxvY2F0aW9uIiwicHJvdG9jb2wiXSwic291cmNlUm9vdCI6IiJ9