(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/app.js":
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _bootstrap_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./bootstrap.js */ "./assets/bootstrap.js");
/* harmony import */ var _styles_app_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./styles/app.css */ "./assets/styles/app.css");



/***/ }),

/***/ "./assets/bootstrap.js":
/*!*****************************!*\
  !*** ./assets/bootstrap.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   app: () => (/* binding */ app)
/* harmony export */ });
/* harmony import */ var _symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/stimulus-bridge */ "./node_modules/@symfony/stimulus-bridge/dist/index.js");


// Registers Stimulus controllers from controllers.json and in the controllers/ directory
var app = (0,_symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__.startStimulusApp)(__webpack_require__("./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$"));
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

/***/ }),

/***/ "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$":
/*!****************************************************************************************************************!*\
  !*** ./assets/controllers/ sync ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \.[jt]sx?$ ***!
  \****************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./csrf_protection_controller.js": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js",
	"./hello_controller.js": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$";

/***/ }),

/***/ "./assets/styles/app.css":
/*!*******************************!*\
  !*** ./assets/styles/app.css ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json":
/*!************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _symfony_ux_live_component_dist_live_controller_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/ux-live-component/dist/live_controller.js */ "./vendor/symfony/ux-live-component/assets/dist/live_controller.js");
/* harmony import */ var _symfony_ux_live_component_dist_live_min_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @symfony/ux-live-component/dist/live.min.css */ "./vendor/symfony/ux-live-component/assets/dist/live.min.css");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'live': _symfony_ux_live_component_dist_live_controller_js__WEBPACK_IMPORTED_MODULE_0__["default"],
});

/***/ }),

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js":
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js ***!
  \****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ controller)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");

const controller = class extends _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller {
    constructor(context) {
        super(context);
        this.__stimulusLazyController = true;
    }
    initialize() {
        if (this.application.controllers.find((controller) => {
            return controller.identifier === this.identifier && controller.__stimulusLazyController;
        })) {
            return;
        }
        Promise.all(/*! import() */[__webpack_require__.e("vendors-node_modules_core-js_modules_es_array-buffer_constructor_js-node_modules_core-js_modu-fa376c"), __webpack_require__.e("assets_controllers_csrf_protection_controller_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ./assets/controllers/csrf_protection_controller.js */ "./assets/controllers/csrf_protection_controller.js")).then((controller) => {
            this.application.register(this.identifier, controller.default);
        });
    }
};


/***/ }),

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js":
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
















function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }


/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_16__.Controller);


/***/ }),

/***/ "./vendor/symfony/ux-live-component/assets/dist/live.min.css":
/*!*******************************************************************!*\
  !*** ./vendor/symfony/ux-live-component/assets/dist/live.min.css ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./vendor/symfony/ux-live-component/assets/dist/live_controller.js":
/*!*************************************************************************!*\
  !*** ./vendor/symfony/ux-live-component/assets/dist/live_controller.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Component: () => (/* binding */ Component),
/* harmony export */   "default": () => (/* binding */ LiveControllerDefault),
/* harmony export */   getComponent: () => (/* binding */ getComponent)
/* harmony export */ });
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! core-js/modules/es.symbol.js */ "./node_modules/core-js/modules/es.symbol.js");
/* harmony import */ var core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! core-js/modules/es.symbol.description.js */ "./node_modules/core-js/modules/es.symbol.description.js");
/* harmony import */ var core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_description_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! core-js/modules/es.symbol.async-iterator.js */ "./node_modules/core-js/modules/es.symbol.async-iterator.js");
/* harmony import */ var core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_async_iterator_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! core-js/modules/es.symbol.iterator.js */ "./node_modules/core-js/modules/es.symbol.iterator.js");
/* harmony import */ var core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_iterator_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! core-js/modules/es.symbol.to-primitive.js */ "./node_modules/core-js/modules/es.symbol.to-primitive.js");
/* harmony import */ var core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_primitive_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! core-js/modules/es.symbol.to-string-tag.js */ "./node_modules/core-js/modules/es.symbol.to-string-tag.js");
/* harmony import */ var core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_symbol_to_string_tag_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! core-js/modules/es.array.concat.js */ "./node_modules/core-js/modules/es.array.concat.js");
/* harmony import */ var core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_concat_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! core-js/modules/es.array.filter.js */ "./node_modules/core-js/modules/es.array.filter.js");
/* harmony import */ var core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_filter_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! core-js/modules/es.array.for-each.js */ "./node_modules/core-js/modules/es.array.for-each.js");
/* harmony import */ var core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_for_each_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! core-js/modules/es.array.from.js */ "./node_modules/core-js/modules/es.array.from.js");
/* harmony import */ var core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_from_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! core-js/modules/es.array.includes.js */ "./node_modules/core-js/modules/es.array.includes.js");
/* harmony import */ var core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_includes_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! core-js/modules/es.array.index-of.js */ "./node_modules/core-js/modules/es.array.index-of.js");
/* harmony import */ var core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_index_of_js__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! core-js/modules/es.array.is-array.js */ "./node_modules/core-js/modules/es.array.is-array.js");
/* harmony import */ var core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_is_array_js__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! core-js/modules/es.array.iterator.js */ "./node_modules/core-js/modules/es.array.iterator.js");
/* harmony import */ var core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_iterator_js__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! core-js/modules/es.array.join.js */ "./node_modules/core-js/modules/es.array.join.js");
/* harmony import */ var core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_join_js__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! core-js/modules/es.array.map.js */ "./node_modules/core-js/modules/es.array.map.js");
/* harmony import */ var core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_map_js__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var core_js_modules_es_array_reduce_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! core-js/modules/es.array.reduce.js */ "./node_modules/core-js/modules/es.array.reduce.js");
/* harmony import */ var core_js_modules_es_array_reduce_js__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_reduce_js__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var core_js_modules_es_array_reverse_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! core-js/modules/es.array.reverse.js */ "./node_modules/core-js/modules/es.array.reverse.js");
/* harmony import */ var core_js_modules_es_array_reverse_js__WEBPACK_IMPORTED_MODULE_17___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_reverse_js__WEBPACK_IMPORTED_MODULE_17__);
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! core-js/modules/es.array.slice.js */ "./node_modules/core-js/modules/es.array.slice.js");
/* harmony import */ var core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_18___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_slice_js__WEBPACK_IMPORTED_MODULE_18__);
/* harmony import */ var core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! core-js/modules/es.array.some.js */ "./node_modules/core-js/modules/es.array.some.js");
/* harmony import */ var core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_19___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_some_js__WEBPACK_IMPORTED_MODULE_19__);
/* harmony import */ var core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! core-js/modules/es.array.splice.js */ "./node_modules/core-js/modules/es.array.splice.js");
/* harmony import */ var core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_20___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_array_splice_js__WEBPACK_IMPORTED_MODULE_20__);
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! core-js/modules/es.date.to-json.js */ "./node_modules/core-js/modules/es.date.to-json.js");
/* harmony import */ var core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_json_js__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! core-js/modules/es.date.to-primitive.js */ "./node_modules/core-js/modules/es.date.to-primitive.js");
/* harmony import */ var core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_22___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_primitive_js__WEBPACK_IMPORTED_MODULE_22__);
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! core-js/modules/es.date.to-string.js */ "./node_modules/core-js/modules/es.date.to-string.js");
/* harmony import */ var core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_23___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_date_to_string_js__WEBPACK_IMPORTED_MODULE_23__);
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! core-js/modules/es.function.bind.js */ "./node_modules/core-js/modules/es.function.bind.js");
/* harmony import */ var core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_24___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_bind_js__WEBPACK_IMPORTED_MODULE_24__);
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! core-js/modules/es.function.name.js */ "./node_modules/core-js/modules/es.function.name.js");
/* harmony import */ var core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_25___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_function_name_js__WEBPACK_IMPORTED_MODULE_25__);
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! core-js/modules/es.json.to-string-tag.js */ "./node_modules/core-js/modules/es.json.to-string-tag.js");
/* harmony import */ var core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_26___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_json_to_string_tag_js__WEBPACK_IMPORTED_MODULE_26__);
/* harmony import */ var core_js_modules_es_map_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! core-js/modules/es.map.js */ "./node_modules/core-js/modules/es.map.js");
/* harmony import */ var core_js_modules_es_map_js__WEBPACK_IMPORTED_MODULE_27___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_map_js__WEBPACK_IMPORTED_MODULE_27__);
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! core-js/modules/es.math.to-string-tag.js */ "./node_modules/core-js/modules/es.math.to-string-tag.js");
/* harmony import */ var core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_math_to_string_tag_js__WEBPACK_IMPORTED_MODULE_28__);
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! core-js/modules/es.number.constructor.js */ "./node_modules/core-js/modules/es.number.constructor.js");
/* harmony import */ var core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_29___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_constructor_js__WEBPACK_IMPORTED_MODULE_29__);
/* harmony import */ var core_js_modules_es_number_is_nan_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! core-js/modules/es.number.is-nan.js */ "./node_modules/core-js/modules/es.number.is-nan.js");
/* harmony import */ var core_js_modules_es_number_is_nan_js__WEBPACK_IMPORTED_MODULE_30___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_is_nan_js__WEBPACK_IMPORTED_MODULE_30__);
/* harmony import */ var core_js_modules_es_number_parse_int_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! core-js/modules/es.number.parse-int.js */ "./node_modules/core-js/modules/es.number.parse-int.js");
/* harmony import */ var core_js_modules_es_number_parse_int_js__WEBPACK_IMPORTED_MODULE_31___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_number_parse_int_js__WEBPACK_IMPORTED_MODULE_31__);
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! core-js/modules/es.object.assign.js */ "./node_modules/core-js/modules/es.object.assign.js");
/* harmony import */ var core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_32___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_assign_js__WEBPACK_IMPORTED_MODULE_32__);
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! core-js/modules/es.object.create.js */ "./node_modules/core-js/modules/es.object.create.js");
/* harmony import */ var core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_33___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_create_js__WEBPACK_IMPORTED_MODULE_33__);
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! core-js/modules/es.object.define-properties.js */ "./node_modules/core-js/modules/es.object.define-properties.js");
/* harmony import */ var core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_34___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_properties_js__WEBPACK_IMPORTED_MODULE_34__);
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! core-js/modules/es.object.define-property.js */ "./node_modules/core-js/modules/es.object.define-property.js");
/* harmony import */ var core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_35___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_define_property_js__WEBPACK_IMPORTED_MODULE_35__);
/* harmony import */ var core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! core-js/modules/es.object.entries.js */ "./node_modules/core-js/modules/es.object.entries.js");
/* harmony import */ var core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_36___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_entries_js__WEBPACK_IMPORTED_MODULE_36__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptor.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptor.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_37___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptor_js__WEBPACK_IMPORTED_MODULE_37__);
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! core-js/modules/es.object.get-own-property-descriptors.js */ "./node_modules/core-js/modules/es.object.get-own-property-descriptors.js");
/* harmony import */ var core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_38___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_own_property_descriptors_js__WEBPACK_IMPORTED_MODULE_38__);
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! core-js/modules/es.object.get-prototype-of.js */ "./node_modules/core-js/modules/es.object.get-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_39___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_get_prototype_of_js__WEBPACK_IMPORTED_MODULE_39__);
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! core-js/modules/es.object.keys.js */ "./node_modules/core-js/modules/es.object.keys.js");
/* harmony import */ var core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_40___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_keys_js__WEBPACK_IMPORTED_MODULE_40__);
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! core-js/modules/es.object.set-prototype-of.js */ "./node_modules/core-js/modules/es.object.set-prototype-of.js");
/* harmony import */ var core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_41___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_set_prototype_of_js__WEBPACK_IMPORTED_MODULE_41__);
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
/* harmony import */ var core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_42___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_to_string_js__WEBPACK_IMPORTED_MODULE_42__);
/* harmony import */ var core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! core-js/modules/es.object.values.js */ "./node_modules/core-js/modules/es.object.values.js");
/* harmony import */ var core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_43___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_object_values_js__WEBPACK_IMPORTED_MODULE_43__);
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! core-js/modules/es.promise.js */ "./node_modules/core-js/modules/es.promise.js");
/* harmony import */ var core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_44___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_promise_js__WEBPACK_IMPORTED_MODULE_44__);
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! core-js/modules/es.reflect.construct.js */ "./node_modules/core-js/modules/es.reflect.construct.js");
/* harmony import */ var core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_45___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_construct_js__WEBPACK_IMPORTED_MODULE_45__);
/* harmony import */ var core_js_modules_es_reflect_get_js__WEBPACK_IMPORTED_MODULE_46__ = __webpack_require__(/*! core-js/modules/es.reflect.get.js */ "./node_modules/core-js/modules/es.reflect.get.js");
/* harmony import */ var core_js_modules_es_reflect_get_js__WEBPACK_IMPORTED_MODULE_46___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_reflect_get_js__WEBPACK_IMPORTED_MODULE_46__);
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_47__ = __webpack_require__(/*! core-js/modules/es.regexp.exec.js */ "./node_modules/core-js/modules/es.regexp.exec.js");
/* harmony import */ var core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_47___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_exec_js__WEBPACK_IMPORTED_MODULE_47__);
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_48__ = __webpack_require__(/*! core-js/modules/es.regexp.to-string.js */ "./node_modules/core-js/modules/es.regexp.to-string.js");
/* harmony import */ var core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_48___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_regexp_to_string_js__WEBPACK_IMPORTED_MODULE_48__);
/* harmony import */ var core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_49__ = __webpack_require__(/*! core-js/modules/es.set.js */ "./node_modules/core-js/modules/es.set.js");
/* harmony import */ var core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_49___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_set_js__WEBPACK_IMPORTED_MODULE_49__);
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_50__ = __webpack_require__(/*! core-js/modules/es.string.includes.js */ "./node_modules/core-js/modules/es.string.includes.js");
/* harmony import */ var core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_50___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_includes_js__WEBPACK_IMPORTED_MODULE_50__);
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_51__ = __webpack_require__(/*! core-js/modules/es.string.iterator.js */ "./node_modules/core-js/modules/es.string.iterator.js");
/* harmony import */ var core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_51___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_iterator_js__WEBPACK_IMPORTED_MODULE_51__);
/* harmony import */ var core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_52__ = __webpack_require__(/*! core-js/modules/es.string.match.js */ "./node_modules/core-js/modules/es.string.match.js");
/* harmony import */ var core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_52___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_match_js__WEBPACK_IMPORTED_MODULE_52__);
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_53__ = __webpack_require__(/*! core-js/modules/es.string.replace.js */ "./node_modules/core-js/modules/es.string.replace.js");
/* harmony import */ var core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_53___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_replace_js__WEBPACK_IMPORTED_MODULE_53__);
/* harmony import */ var core_js_modules_es_string_search_js__WEBPACK_IMPORTED_MODULE_54__ = __webpack_require__(/*! core-js/modules/es.string.search.js */ "./node_modules/core-js/modules/es.string.search.js");
/* harmony import */ var core_js_modules_es_string_search_js__WEBPACK_IMPORTED_MODULE_54___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_search_js__WEBPACK_IMPORTED_MODULE_54__);
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_55__ = __webpack_require__(/*! core-js/modules/es.string.trim.js */ "./node_modules/core-js/modules/es.string.trim.js");
/* harmony import */ var core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_55___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_string_trim_js__WEBPACK_IMPORTED_MODULE_55__);
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_56__ = __webpack_require__(/*! core-js/modules/es.weak-map.js */ "./node_modules/core-js/modules/es.weak-map.js");
/* harmony import */ var core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_56___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_es_weak_map_js__WEBPACK_IMPORTED_MODULE_56__);
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_57__ = __webpack_require__(/*! core-js/modules/web.dom-collections.for-each.js */ "./node_modules/core-js/modules/web.dom-collections.for-each.js");
/* harmony import */ var core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_57___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_for_each_js__WEBPACK_IMPORTED_MODULE_57__);
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_58__ = __webpack_require__(/*! core-js/modules/web.dom-collections.iterator.js */ "./node_modules/core-js/modules/web.dom-collections.iterator.js");
/* harmony import */ var core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_58___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_dom_collections_iterator_js__WEBPACK_IMPORTED_MODULE_58__);
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_59__ = __webpack_require__(/*! core-js/modules/web.timers.js */ "./node_modules/core-js/modules/web.timers.js");
/* harmony import */ var core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_59___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_timers_js__WEBPACK_IMPORTED_MODULE_59__);
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_60__ = __webpack_require__(/*! core-js/modules/web.url.js */ "./node_modules/core-js/modules/web.url.js");
/* harmony import */ var core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_60___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_js__WEBPACK_IMPORTED_MODULE_60__);
/* harmony import */ var core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_61__ = __webpack_require__(/*! core-js/modules/web.url.to-json.js */ "./node_modules/core-js/modules/web.url.to-json.js");
/* harmony import */ var core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_61___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_to_json_js__WEBPACK_IMPORTED_MODULE_61__);
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_62__ = __webpack_require__(/*! core-js/modules/web.url-search-params.js */ "./node_modules/core-js/modules/web.url-search-params.js");
/* harmony import */ var core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_62___default = /*#__PURE__*/__webpack_require__.n(core_js_modules_web_url_search_params_js__WEBPACK_IMPORTED_MODULE_62__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_63__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _wrapNativeSuper(t) { var r = "function" == typeof Map ? new Map() : void 0; return _wrapNativeSuper = function _wrapNativeSuper(t) { if (null === t || !_isNativeFunction(t)) return t; if ("function" != typeof t) throw new TypeError("Super expression must either be null or a function"); if (void 0 !== r) { if (r.has(t)) return r.get(t); r.set(t, Wrapper); } function Wrapper() { return _construct(t, arguments, _getPrototypeOf(this).constructor); } return Wrapper.prototype = Object.create(t.prototype, { constructor: { value: Wrapper, enumerable: !1, writable: !0, configurable: !0 } }), _setPrototypeOf(Wrapper, t); }, _wrapNativeSuper(t); }
function _construct(t, e, r) { if (_isNativeReflectConstruct()) return Reflect.construct.apply(null, arguments); var o = [null]; o.push.apply(o, e); var p = new (t.bind.apply(t, o))(); return r && _setPrototypeOf(p, r.prototype), p; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _isNativeFunction(t) { try { return -1 !== Function.toString.call(t).indexOf("[native code]"); } catch (n) { return "function" == typeof t; } }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _toArray(r) { return _arrayWithHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableRest(); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator["return"] && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, "catch": function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }































































function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var BackendRequest = /*#__PURE__*/function () {
  function BackendRequest(promise, actions, updateModels) {
    var _this = this;
    _classCallCheck(this, BackendRequest);
    this.isResolved = false;
    this.promise = promise;
    this.promise.then(function (response) {
      _this.isResolved = true;
      return response;
    });
    this.actions = actions;
    this.updatedModels = updateModels;
  }
  return _createClass(BackendRequest, [{
    key: "containsOneOfActions",
    value: function containsOneOfActions(targetedActions) {
      return this.actions.filter(function (action) {
        return targetedActions.includes(action);
      }).length > 0;
    }
  }, {
    key: "areAnyModelsUpdated",
    value: function areAnyModelsUpdated(targetedModels) {
      return this.updatedModels.filter(function (model) {
        return targetedModels.includes(model);
      }).length > 0;
    }
  }]);
}();
var RequestBuilder = /*#__PURE__*/function () {
  function RequestBuilder(url) {
    var method = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'post';
    _classCallCheck(this, RequestBuilder);
    this.url = url;
    this.method = method;
  }
  return _createClass(RequestBuilder, [{
    key: "buildRequest",
    value: function buildRequest(props, actions, updated, children, updatedPropsFromParent, files) {
      var splitUrl = this.url.split('?');
      var _splitUrl = _slicedToArray(splitUrl, 1),
        url = _splitUrl[0];
      var _splitUrl2 = _slicedToArray(splitUrl, 2),
        queryString = _splitUrl2[1];
      var params = new URLSearchParams(queryString || '');
      var fetchOptions = {};
      fetchOptions.headers = {
        Accept: 'application/vnd.live-component+html',
        'X-Requested-With': 'XMLHttpRequest'
      };
      var totalFiles = Object.entries(files).reduce(function (total, current) {
        return total + current.length;
      }, 0);
      var hasFingerprints = Object.keys(children).length > 0;
      if (actions.length === 0 && totalFiles === 0 && this.method === 'get' && this.willDataFitInUrl(JSON.stringify(props), JSON.stringify(updated), params, JSON.stringify(children), JSON.stringify(updatedPropsFromParent))) {
        params.set('props', JSON.stringify(props));
        params.set('updated', JSON.stringify(updated));
        if (Object.keys(updatedPropsFromParent).length > 0) {
          params.set('propsFromParent', JSON.stringify(updatedPropsFromParent));
        }
        if (hasFingerprints) {
          params.set('children', JSON.stringify(children));
        }
        fetchOptions.method = 'GET';
      } else {
        fetchOptions.method = 'POST';
        var requestData = {
          props: props,
          updated: updated
        };
        if (Object.keys(updatedPropsFromParent).length > 0) {
          requestData.propsFromParent = updatedPropsFromParent;
        }
        if (hasFingerprints) {
          requestData.children = children;
        }
        if (actions.length > 0) {
          if (actions.length === 1) {
            requestData.args = actions[0].args;
            url += "/".concat(encodeURIComponent(actions[0].name));
          } else {
            url += '/_batch';
            requestData.actions = actions;
          }
        }
        var formData = new FormData();
        formData.append('data', JSON.stringify(requestData));
        for (var _i = 0, _Object$entries = Object.entries(files); _i < _Object$entries.length; _i++) {
          var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
            key = _Object$entries$_i[0],
            value = _Object$entries$_i[1];
          var length = value.length;
          for (var i = 0; i < length; ++i) {
            formData.append(key, value[i]);
          }
        }
        fetchOptions.body = formData;
      }
      var paramsString = params.toString();
      return {
        url: "".concat(url).concat(paramsString.length > 0 ? "?".concat(paramsString) : ''),
        fetchOptions: fetchOptions
      };
    }
  }, {
    key: "willDataFitInUrl",
    value: function willDataFitInUrl(propsJson, updatedJson, params, childrenJson, propsFromParentJson) {
      var urlEncodedJsonData = new URLSearchParams(propsJson + updatedJson + childrenJson + propsFromParentJson).toString();
      return (urlEncodedJsonData + params.toString()).length < 1500;
    }
  }]);
}();
var Backend = /*#__PURE__*/function () {
  function Backend(url) {
    var method = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'post';
    _classCallCheck(this, Backend);
    this.requestBuilder = new RequestBuilder(url, method);
  }
  return _createClass(Backend, [{
    key: "makeRequest",
    value: function makeRequest(props, actions, updated, children, updatedPropsFromParent, files) {
      var _this$requestBuilder$ = this.requestBuilder.buildRequest(props, actions, updated, children, updatedPropsFromParent, files),
        url = _this$requestBuilder$.url,
        fetchOptions = _this$requestBuilder$.fetchOptions;
      return new BackendRequest(fetch(url, fetchOptions), actions.map(function (backendAction) {
        return backendAction.name;
      }), Object.keys(updated));
    }
  }]);
}();
var BackendResponse = /*#__PURE__*/function () {
  function BackendResponse(response) {
    _classCallCheck(this, BackendResponse);
    this.response = response;
  }
  return _createClass(BackendResponse, [{
    key: "getBody",
    value: function () {
      var _getBody = _asyncToGenerator(/*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (this.body) {
                _context.next = 4;
                break;
              }
              _context.next = 3;
              return this.response.text();
            case 3:
              this.body = _context.sent;
            case 4:
              return _context.abrupt("return", this.body);
            case 5:
            case "end":
              return _context.stop();
          }
        }, _callee, this);
      }));
      function getBody() {
        return _getBody.apply(this, arguments);
      }
      return getBody;
    }()
  }]);
}();
function getElementAsTagText(element) {
  return element.innerHTML ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML)) : element.outerHTML;
}
var componentMapByElement = new WeakMap();
var componentMapByComponent = new Map();
var registerComponent = function registerComponent(component) {
  componentMapByElement.set(component.element, component);
  componentMapByComponent.set(component, component.name);
};
var unregisterComponent = function unregisterComponent(component) {
  componentMapByElement["delete"](component.element);
  componentMapByComponent["delete"](component);
};
var getComponent = function getComponent(element) {
  return new Promise(function (resolve, reject) {
    var count = 0;
    var maxCount = 10;
    var interval = setInterval(function () {
      var component = componentMapByElement.get(element);
      if (component) {
        clearInterval(interval);
        resolve(component);
      }
      count++;
      if (count > maxCount) {
        clearInterval(interval);
        reject(new Error("Component not found for element ".concat(getElementAsTagText(element))));
      }
    }, 5);
  });
};
var findComponents = function findComponents(currentComponent, onlyParents, onlyMatchName) {
  var components = [];
  componentMapByComponent.forEach(function (componentName, component) {
    if (onlyParents && (currentComponent === component || !component.element.contains(currentComponent.element))) {
      return;
    }
    if (onlyMatchName && componentName !== onlyMatchName) {
      return;
    }
    components.push(component);
  });
  return components;
};
var findChildren = function findChildren(currentComponent) {
  var children = [];
  componentMapByComponent.forEach(function (componentName, component) {
    if (currentComponent === component) {
      return;
    }
    if (!currentComponent.element.contains(component.element)) {
      return;
    }
    var foundChildComponent = false;
    componentMapByComponent.forEach(function (childComponentName, childComponent) {
      if (foundChildComponent) {
        return;
      }
      if (childComponent === component) {
        return;
      }
      if (childComponent.element.contains(component.element)) {
        foundChildComponent = true;
      }
    });
    children.push(component);
  });
  return children;
};
var findParent = function findParent(currentComponent) {
  var parentElement = currentComponent.element.parentElement;
  while (parentElement) {
    var component = componentMapByElement.get(parentElement);
    if (component) {
      return component;
    }
    parentElement = parentElement.parentElement;
  }
  return null;
};
var HookManager = /*#__PURE__*/function () {
  function HookManager() {
    _classCallCheck(this, HookManager);
    this.hooks = new Map();
  }
  return _createClass(HookManager, [{
    key: "register",
    value: function register(hookName, callback) {
      var hooks = this.hooks.get(hookName) || [];
      hooks.push(callback);
      this.hooks.set(hookName, hooks);
    }
  }, {
    key: "unregister",
    value: function unregister(hookName, callback) {
      var hooks = this.hooks.get(hookName) || [];
      var index = hooks.indexOf(callback);
      if (index === -1) {
        return;
      }
      hooks.splice(index, 1);
      this.hooks.set(hookName, hooks);
    }
  }, {
    key: "triggerHook",
    value: function triggerHook(hookName) {
      for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }
      var hooks = this.hooks.get(hookName) || [];
      hooks.forEach(function (callback) {
        return callback.apply(void 0, args);
      });
    }
  }]);
}();
var ChangingItemsTracker = /*#__PURE__*/function () {
  function ChangingItemsTracker() {
    _classCallCheck(this, ChangingItemsTracker);
    this.changedItems = new Map();
    this.removedItems = new Map();
  }
  return _createClass(ChangingItemsTracker, [{
    key: "setItem",
    value: function setItem(itemName, newValue, previousValue) {
      if (this.removedItems.has(itemName)) {
        var removedRecord = this.removedItems.get(itemName);
        this.removedItems["delete"](itemName);
        if (removedRecord.original === newValue) {
          return;
        }
      }
      if (this.changedItems.has(itemName)) {
        var originalRecord = this.changedItems.get(itemName);
        if (originalRecord.original === newValue) {
          this.changedItems["delete"](itemName);
          return;
        }
        this.changedItems.set(itemName, {
          original: originalRecord.original,
          "new": newValue
        });
        return;
      }
      this.changedItems.set(itemName, {
        original: previousValue,
        "new": newValue
      });
    }
  }, {
    key: "removeItem",
    value: function removeItem(itemName, currentValue) {
      var trueOriginalValue = currentValue;
      if (this.changedItems.has(itemName)) {
        var originalRecord = this.changedItems.get(itemName);
        trueOriginalValue = originalRecord.original;
        this.changedItems["delete"](itemName);
        if (trueOriginalValue === null) {
          return;
        }
      }
      if (!this.removedItems.has(itemName)) {
        this.removedItems.set(itemName, {
          original: trueOriginalValue
        });
      }
    }
  }, {
    key: "getChangedItems",
    value: function getChangedItems() {
      return Array.from(this.changedItems, function (_ref) {
        var _ref2 = _slicedToArray(_ref, 2),
          name = _ref2[0],
          value = _ref2[1]["new"];
        return {
          name: name,
          value: value
        };
      });
    }
  }, {
    key: "getRemovedItems",
    value: function getRemovedItems() {
      return Array.from(this.removedItems.keys());
    }
  }, {
    key: "isEmpty",
    value: function isEmpty() {
      return this.changedItems.size === 0 && this.removedItems.size === 0;
    }
  }]);
}();
var ElementChanges = /*#__PURE__*/function () {
  function ElementChanges() {
    _classCallCheck(this, ElementChanges);
    this.addedClasses = new Set();
    this.removedClasses = new Set();
    this.styleChanges = new ChangingItemsTracker();
    this.attributeChanges = new ChangingItemsTracker();
  }
  return _createClass(ElementChanges, [{
    key: "addClass",
    value: function addClass(className) {
      if (!this.removedClasses["delete"](className)) {
        this.addedClasses.add(className);
      }
    }
  }, {
    key: "removeClass",
    value: function removeClass(className) {
      if (!this.addedClasses["delete"](className)) {
        this.removedClasses.add(className);
      }
    }
  }, {
    key: "addStyle",
    value: function addStyle(styleName, newValue, originalValue) {
      this.styleChanges.setItem(styleName, newValue, originalValue);
    }
  }, {
    key: "removeStyle",
    value: function removeStyle(styleName, originalValue) {
      this.styleChanges.removeItem(styleName, originalValue);
    }
  }, {
    key: "addAttribute",
    value: function addAttribute(attributeName, newValue, originalValue) {
      this.attributeChanges.setItem(attributeName, newValue, originalValue);
    }
  }, {
    key: "removeAttribute",
    value: function removeAttribute(attributeName, originalValue) {
      this.attributeChanges.removeItem(attributeName, originalValue);
    }
  }, {
    key: "getAddedClasses",
    value: function getAddedClasses() {
      return _toConsumableArray(this.addedClasses);
    }
  }, {
    key: "getRemovedClasses",
    value: function getRemovedClasses() {
      return _toConsumableArray(this.removedClasses);
    }
  }, {
    key: "getChangedStyles",
    value: function getChangedStyles() {
      return this.styleChanges.getChangedItems();
    }
  }, {
    key: "getRemovedStyles",
    value: function getRemovedStyles() {
      return this.styleChanges.getRemovedItems();
    }
  }, {
    key: "getChangedAttributes",
    value: function getChangedAttributes() {
      return this.attributeChanges.getChangedItems();
    }
  }, {
    key: "getRemovedAttributes",
    value: function getRemovedAttributes() {
      return this.attributeChanges.getRemovedItems();
    }
  }, {
    key: "applyToElement",
    value: function applyToElement(element) {
      var _element$classList, _element$classList2;
      (_element$classList = element.classList).add.apply(_element$classList, _toConsumableArray(this.addedClasses));
      (_element$classList2 = element.classList).remove.apply(_element$classList2, _toConsumableArray(this.removedClasses));
      this.styleChanges.getChangedItems().forEach(function (change) {
        element.style.setProperty(change.name, change.value);
        return;
      });
      this.styleChanges.getRemovedItems().forEach(function (styleName) {
        element.style.removeProperty(styleName);
      });
      this.attributeChanges.getChangedItems().forEach(function (change) {
        element.setAttribute(change.name, change.value);
      });
      this.attributeChanges.getRemovedItems().forEach(function (attributeName) {
        element.removeAttribute(attributeName);
      });
    }
  }, {
    key: "isEmpty",
    value: function isEmpty() {
      return this.addedClasses.size === 0 && this.removedClasses.size === 0 && this.styleChanges.isEmpty() && this.attributeChanges.isEmpty();
    }
  }]);
}();
var ExternalMutationTracker = /*#__PURE__*/function () {
  function ExternalMutationTracker(element, shouldTrackChangeCallback) {
    _classCallCheck(this, ExternalMutationTracker);
    this.changedElements = new WeakMap();
    this.changedElementsCount = 0;
    this.addedElements = [];
    this.removedElements = [];
    this.isStarted = false;
    this.element = element;
    this.shouldTrackChangeCallback = shouldTrackChangeCallback;
    this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
  }
  return _createClass(ExternalMutationTracker, [{
    key: "start",
    value: function start() {
      if (this.isStarted) {
        return;
      }
      this.mutationObserver.observe(this.element, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeOldValue: true
      });
      this.isStarted = true;
    }
  }, {
    key: "stop",
    value: function stop() {
      if (this.isStarted) {
        this.mutationObserver.disconnect();
        this.isStarted = false;
      }
    }
  }, {
    key: "getChangedElement",
    value: function getChangedElement(element) {
      return this.changedElements.has(element) ? this.changedElements.get(element) : null;
    }
  }, {
    key: "getAddedElements",
    value: function getAddedElements() {
      return this.addedElements;
    }
  }, {
    key: "wasElementAdded",
    value: function wasElementAdded(element) {
      return this.addedElements.includes(element);
    }
  }, {
    key: "handlePendingChanges",
    value: function handlePendingChanges() {
      this.onMutations(this.mutationObserver.takeRecords());
    }
  }, {
    key: "onMutations",
    value: function onMutations(mutations) {
      var handledAttributeMutations = new WeakMap();
      var _iterator = _createForOfIteratorHelper(mutations),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var mutation = _step.value;
          var element = mutation.target;
          if (!this.shouldTrackChangeCallback(element)) {
            continue;
          }
          if (this.isElementAddedByTranslation(element)) {
            continue;
          }
          var isChangeInAddedElement = false;
          var _iterator2 = _createForOfIteratorHelper(this.addedElements),
            _step2;
          try {
            for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
              var addedElement = _step2.value;
              if (addedElement.contains(element)) {
                isChangeInAddedElement = true;
                break;
              }
            }
          } catch (err) {
            _iterator2.e(err);
          } finally {
            _iterator2.f();
          }
          if (isChangeInAddedElement) {
            continue;
          }
          switch (mutation.type) {
            case 'childList':
              this.handleChildListMutation(mutation);
              break;
            case 'attributes':
              if (!handledAttributeMutations.has(element)) {
                handledAttributeMutations.set(element, []);
              }
              if (!handledAttributeMutations.get(element).includes(mutation.attributeName)) {
                this.handleAttributeMutation(mutation);
                handledAttributeMutations.set(element, [].concat(_toConsumableArray(handledAttributeMutations.get(element)), [mutation.attributeName]));
              }
              break;
          }
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
    }
  }, {
    key: "handleChildListMutation",
    value: function handleChildListMutation(mutation) {
      var _this2 = this;
      mutation.addedNodes.forEach(function (node) {
        if (!(node instanceof Element)) {
          return;
        }
        if (_this2.removedElements.includes(node)) {
          _this2.removedElements.splice(_this2.removedElements.indexOf(node), 1);
          return;
        }
        if (_this2.isElementAddedByTranslation(node)) {
          return;
        }
        _this2.addedElements.push(node);
      });
      mutation.removedNodes.forEach(function (node) {
        if (!(node instanceof Element)) {
          return;
        }
        if (_this2.addedElements.includes(node)) {
          _this2.addedElements.splice(_this2.addedElements.indexOf(node), 1);
          return;
        }
        _this2.removedElements.push(node);
      });
    }
  }, {
    key: "handleAttributeMutation",
    value: function handleAttributeMutation(mutation) {
      var element = mutation.target;
      if (!this.changedElements.has(element)) {
        this.changedElements.set(element, new ElementChanges());
        this.changedElementsCount++;
      }
      var changedElement = this.changedElements.get(element);
      switch (mutation.attributeName) {
        case 'class':
          this.handleClassAttributeMutation(mutation, changedElement);
          break;
        case 'style':
          this.handleStyleAttributeMutation(mutation, changedElement);
          break;
        default:
          this.handleGenericAttributeMutation(mutation, changedElement);
      }
      if (changedElement.isEmpty()) {
        this.changedElements["delete"](element);
        this.changedElementsCount--;
      }
    }
  }, {
    key: "handleClassAttributeMutation",
    value: function handleClassAttributeMutation(mutation, elementChanges) {
      var element = mutation.target;
      var previousValue = mutation.oldValue || '';
      var previousValues = previousValue.match(/((?:[\0-\x08\x0E-\x1F!-\x9F\xA1-\u167F\u1681-\u1FFF\u200B-\u2027\u202A-\u202E\u2030-\u205E\u2060-\u2FFF\u3001-\uD7FF\uE000-\uFEFE\uFF00-\uFFFF]|[\uD800-\uDBFF][\uDC00-\uDFFF]|[\uD800-\uDBFF](?![\uDC00-\uDFFF])|(?:[^\uD800-\uDBFF]|^)[\uDC00-\uDFFF])+)/g) || [];
      var newValues = [].slice.call(element.classList);
      var addedValues = newValues.filter(function (value) {
        return !previousValues.includes(value);
      });
      var removedValues = previousValues.filter(function (value) {
        return !newValues.includes(value);
      });
      addedValues.forEach(function (value) {
        elementChanges.addClass(value);
      });
      removedValues.forEach(function (value) {
        elementChanges.removeClass(value);
      });
    }
  }, {
    key: "handleStyleAttributeMutation",
    value: function handleStyleAttributeMutation(mutation, elementChanges) {
      var element = mutation.target;
      var previousValue = mutation.oldValue || '';
      var previousStyles = this.extractStyles(previousValue);
      var newValue = element.getAttribute('style') || '';
      var newStyles = this.extractStyles(newValue);
      var addedOrChangedStyles = Object.keys(newStyles).filter(function (key) {
        return previousStyles[key] === undefined || previousStyles[key] !== newStyles[key];
      });
      var removedStyles = Object.keys(previousStyles).filter(function (key) {
        return !newStyles[key];
      });
      addedOrChangedStyles.forEach(function (style) {
        elementChanges.addStyle(style, newStyles[style], previousStyles[style] === undefined ? null : previousStyles[style]);
      });
      removedStyles.forEach(function (style) {
        elementChanges.removeStyle(style, previousStyles[style]);
      });
    }
  }, {
    key: "handleGenericAttributeMutation",
    value: function handleGenericAttributeMutation(mutation, elementChanges) {
      var attributeName = mutation.attributeName;
      var element = mutation.target;
      var oldValue = mutation.oldValue;
      var newValue = element.getAttribute(attributeName);
      if (oldValue === attributeName) {
        oldValue = '';
      }
      if (newValue === attributeName) {
        newValue = '';
      }
      if (!element.hasAttribute(attributeName)) {
        if (oldValue === null) {
          return;
        }
        elementChanges.removeAttribute(attributeName, mutation.oldValue);
        return;
      }
      if (newValue === oldValue) {
        return;
      }
      elementChanges.addAttribute(attributeName, element.getAttribute(attributeName), mutation.oldValue);
    }
  }, {
    key: "extractStyles",
    value: function extractStyles(styles) {
      var styleObject = {};
      styles.split(';').forEach(function (style) {
        var parts = style.split(':');
        if (parts.length === 1) {
          return;
        }
        var property = parts[0].trim();
        styleObject[property] = parts.slice(1).join(':').trim();
      });
      return styleObject;
    }
  }, {
    key: "isElementAddedByTranslation",
    value: function isElementAddedByTranslation(element) {
      return element.tagName === 'FONT' && element.getAttribute('style') === 'vertical-align: inherit;';
    }
  }]);
}();
function parseDirectives(content) {
  var directives = [];
  if (!content) {
    return directives;
  }
  var currentActionName = '';
  var currentArgumentValue = '';
  var currentArguments = [];
  var currentModifiers = [];
  var state = 'action';
  var getLastActionName = function getLastActionName() {
    if (currentActionName) {
      return currentActionName;
    }
    if (directives.length === 0) {
      throw new Error('Could not find any directives');
    }
    return directives[directives.length - 1].action;
  };
  var pushInstruction = function pushInstruction() {
    directives.push({
      action: currentActionName,
      args: currentArguments,
      modifiers: currentModifiers,
      getString: function getString() {
        return content;
      }
    });
    currentActionName = '';
    currentArgumentValue = '';
    currentArguments = [];
    currentModifiers = [];
    state = 'action';
  };
  var pushArgument = function pushArgument() {
    currentArguments.push(currentArgumentValue.trim());
    currentArgumentValue = '';
  };
  var pushModifier = function pushModifier() {
    if (currentArguments.length > 1) {
      throw new Error("The modifier \"".concat(currentActionName, "()\" does not support multiple arguments."));
    }
    currentModifiers.push({
      name: currentActionName,
      value: currentArguments.length > 0 ? currentArguments[0] : null
    });
    currentActionName = '';
    currentArguments = [];
    state = 'action';
  };
  for (var i = 0; i < content.length; i++) {
    var _char = content[i];
    switch (state) {
      case 'action':
        if (_char === '(') {
          state = 'arguments';
          break;
        }
        if (_char === ' ') {
          if (currentActionName) {
            pushInstruction();
          }
          break;
        }
        if (_char === '|') {
          pushModifier();
          break;
        }
        currentActionName += _char;
        break;
      case 'arguments':
        if (_char === ')') {
          pushArgument();
          state = 'after_arguments';
          break;
        }
        if (_char === ',') {
          pushArgument();
          break;
        }
        currentArgumentValue += _char;
        break;
      case 'after_arguments':
        if (_char === '|') {
          pushModifier();
          break;
        }
        if (_char !== ' ') {
          throw new Error("Missing space after ".concat(getLastActionName(), "()"));
        }
        pushInstruction();
        break;
    }
  }
  switch (state) {
    case 'action':
    case 'after_arguments':
      if (currentActionName) {
        pushInstruction();
      }
      break;
    default:
      throw new Error("Did you forget to add a closing \")\" after \"".concat(currentActionName, "\"?"));
  }
  return directives;
}
function combineSpacedArray(parts) {
  var finalParts = [];
  parts.forEach(function (part) {
    finalParts.push.apply(finalParts, _toConsumableArray(trimAll(part).split(' ')));
  });
  return finalParts;
}
function trimAll(str) {
  return str.replace(/[\s]+/g, ' ').trim();
}
function normalizeModelName(model) {
  return model.replace(/\[]$/, '').split('[').map(function (s) {
    return s.replace(']', '');
  }).join('.');
}
function getValueFromElement(element, valueStore) {
  if (element instanceof HTMLInputElement) {
    if (element.type === 'checkbox') {
      var modelNameData = getModelDirectiveFromElement(element, false);
      if (modelNameData !== null) {
        var modelValue = valueStore.get(modelNameData.action);
        if (Array.isArray(modelValue)) {
          return getMultipleCheckboxValue(element, modelValue);
        }
        if (Object(modelValue) === modelValue) {
          return getMultipleCheckboxValue(element, Object.values(modelValue));
        }
      }
      if (element.hasAttribute('value')) {
        return element.checked ? element.getAttribute('value') : null;
      }
      return element.checked;
    }
    return inputValue(element);
  }
  if (element instanceof HTMLSelectElement) {
    if (element.multiple) {
      return Array.from(element.selectedOptions).map(function (el) {
        return el.value;
      });
    }
    return element.value;
  }
  if (element.dataset.value) {
    return element.dataset.value;
  }
  if ('value' in element) {
    return element.value;
  }
  if (element.hasAttribute('value')) {
    return element.getAttribute('value');
  }
  return null;
}
function setValueOnElement(element, value) {
  if (element instanceof HTMLInputElement) {
    if (element.type === 'file') {
      return;
    }
    if (element.type === 'radio') {
      element.checked = element.value == value;
      return;
    }
    if (element.type === 'checkbox') {
      if (Array.isArray(value)) {
        element.checked = value.some(function (val) {
          return val == element.value;
        });
      } else if (element.hasAttribute('value')) {
        element.checked = element.value == value;
      } else {
        element.checked = value;
      }
      return;
    }
  }
  if (element instanceof HTMLSelectElement) {
    var arrayWrappedValue = [].concat(value).map(function (value) {
      return "".concat(value);
    });
    Array.from(element.options).forEach(function (option) {
      option.selected = arrayWrappedValue.includes(option.value);
    });
    return;
  }
  value = value === undefined ? '' : value;
  element.value = value;
}
function getAllModelDirectiveFromElements(element) {
  if (!element.dataset.model) {
    return [];
  }
  var directives = parseDirectives(element.dataset.model);
  directives.forEach(function (directive) {
    if (directive.args.length > 0) {
      throw new Error("The data-model=\"".concat(element.dataset.model, "\" format is invalid: it does not support passing arguments to the model."));
    }
    directive.action = normalizeModelName(directive.action);
  });
  return directives;
}
function getModelDirectiveFromElement(element) {
  var throwOnMissing = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  var dataModelDirectives = getAllModelDirectiveFromElements(element);
  if (dataModelDirectives.length > 0) {
    return dataModelDirectives[0];
  }
  if (element.getAttribute('name')) {
    var formElement = element.closest('form');
    if (formElement && 'model' in formElement.dataset) {
      var directives = parseDirectives(formElement.dataset.model || '*');
      var directive = directives[0];
      if (directive.args.length > 0) {
        throw new Error("The data-model=\"".concat(formElement.dataset.model, "\" format is invalid: it does not support passing arguments to the model."));
      }
      directive.action = normalizeModelName(element.getAttribute('name'));
      return directive;
    }
  }
  if (!throwOnMissing) {
    return null;
  }
  throw new Error("Cannot determine the model name for \"".concat(getElementAsTagText(element), "\": the element must either have a \"data-model\" (or \"name\" attribute living inside a <form data-model=\"*\">)."));
}
function elementBelongsToThisComponent(element, component) {
  if (component.element === element) {
    return true;
  }
  if (!component.element.contains(element)) {
    return false;
  }
  var closestLiveComponent = element.closest('[data-controller~="live"]');
  return closestLiveComponent === component.element;
}
function cloneHTMLElement(element) {
  var newElement = element.cloneNode(true);
  if (!(newElement instanceof HTMLElement)) {
    throw new Error('Could not clone element');
  }
  return newElement;
}
function htmlToElement(html) {
  var template = document.createElement('template');
  html = html.trim();
  template.innerHTML = html;
  if (template.content.childElementCount > 1) {
    throw new Error("Component HTML contains ".concat(template.content.childElementCount, " elements, but only 1 root element is allowed."));
  }
  var child = template.content.firstElementChild;
  if (!child) {
    throw new Error('Child not found');
  }
  if (!(child instanceof HTMLElement)) {
    throw new Error("Created element is not an HTMLElement: ".concat(html.trim()));
  }
  return child;
}
var getMultipleCheckboxValue = function getMultipleCheckboxValue(element, currentValues) {
  var finalValues = _toConsumableArray(currentValues);
  var value = inputValue(element);
  var index = currentValues.indexOf(value);
  if (element.checked) {
    if (index === -1) {
      finalValues.push(value);
    }
    return finalValues;
  }
  if (index > -1) {
    finalValues.splice(index, 1);
  }
  return finalValues;
};
var inputValue = function inputValue(element) {
  return element.dataset.value ? element.dataset.value : element.value;
};

// base IIFE to define idiomorph
var Idiomorph = function () {
  //=============================================================================
  // AND NOW IT BEGINS...
  //=============================================================================
  var EMPTY_SET = new Set();

  // default configuration values, updatable by users now
  var defaults = {
    morphStyle: "outerHTML",
    callbacks: {
      beforeNodeAdded: noOp,
      afterNodeAdded: noOp,
      beforeNodeMorphed: noOp,
      afterNodeMorphed: noOp,
      beforeNodeRemoved: noOp,
      afterNodeRemoved: noOp,
      beforeAttributeUpdated: noOp
    },
    head: {
      style: 'merge',
      shouldPreserve: function shouldPreserve(elt) {
        return elt.getAttribute("im-preserve") === "true";
      },
      shouldReAppend: function shouldReAppend(elt) {
        return elt.getAttribute("im-re-append") === "true";
      },
      shouldRemove: noOp,
      afterHeadMorphed: noOp
    }
  };

  //=============================================================================
  // Core Morphing Algorithm - morph, morphNormalizedContent, morphOldNodeTo, morphChildren
  //=============================================================================
  function morph(oldNode, newContent) {
    var config = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    if (oldNode instanceof Document) {
      oldNode = oldNode.documentElement;
    }
    if (typeof newContent === 'string') {
      newContent = parseContent(newContent);
    }
    var normalizedContent = normalizeContent(newContent);
    var ctx = createMorphContext(oldNode, normalizedContent, config);
    return morphNormalizedContent(oldNode, normalizedContent, ctx);
  }
  function morphNormalizedContent(oldNode, normalizedNewContent, ctx) {
    if (ctx.head.block) {
      var oldHead = oldNode.querySelector('head');
      var newHead = normalizedNewContent.querySelector('head');
      if (oldHead && newHead) {
        var promises = handleHeadElement(newHead, oldHead, ctx);
        // when head promises resolve, call morph again, ignoring the head tag
        Promise.all(promises).then(function () {
          morphNormalizedContent(oldNode, normalizedNewContent, Object.assign(ctx, {
            head: {
              block: false,
              ignore: true
            }
          }));
        });
        return;
      }
    }
    if (ctx.morphStyle === "innerHTML") {
      // innerHTML, so we are only updating the children
      morphChildren(normalizedNewContent, oldNode, ctx);
      return oldNode.children;
    } else if (ctx.morphStyle === "outerHTML" || ctx.morphStyle == null) {
      // otherwise find the best element match in the new content, morph that, and merge its siblings
      // into either side of the best match
      var bestMatch = findBestNodeMatch(normalizedNewContent, oldNode, ctx);

      // stash the siblings that will need to be inserted on either side of the best match
      var previousSibling = bestMatch === null || bestMatch === void 0 ? void 0 : bestMatch.previousSibling;
      var nextSibling = bestMatch === null || bestMatch === void 0 ? void 0 : bestMatch.nextSibling;

      // morph it
      var morphedNode = morphOldNodeTo(oldNode, bestMatch, ctx);
      if (bestMatch) {
        // if there was a best match, merge the siblings in too and return the
        // whole bunch
        return insertSiblings(previousSibling, morphedNode, nextSibling);
      } else {
        // otherwise nothing was added to the DOM
        return [];
      }
    } else {
      throw "Do not understand how to morph style " + ctx.morphStyle;
    }
  }

  /**
   * @param possibleActiveElement
   * @param ctx
   * @returns {boolean}
   */
  function ignoreValueOfActiveElement(possibleActiveElement, ctx) {
    return ctx.ignoreActiveValue && possibleActiveElement === document.activeElement;
  }

  /**
   * @param oldNode root node to merge content into
   * @param newContent new content to merge
   * @param ctx the merge context
   * @returns {Element} the element that ended up in the DOM
   */
  function morphOldNodeTo(oldNode, newContent, ctx) {
    if (ctx.ignoreActive && oldNode === document.activeElement) ;else if (newContent == null) {
      if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;
      oldNode.remove();
      ctx.callbacks.afterNodeRemoved(oldNode);
      return null;
    } else if (!isSoftMatch(oldNode, newContent)) {
      if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;
      if (ctx.callbacks.beforeNodeAdded(newContent) === false) return oldNode;
      oldNode.parentElement.replaceChild(newContent, oldNode);
      ctx.callbacks.afterNodeAdded(newContent);
      ctx.callbacks.afterNodeRemoved(oldNode);
      return newContent;
    } else {
      if (ctx.callbacks.beforeNodeMorphed(oldNode, newContent) === false) return oldNode;
      if (oldNode instanceof HTMLHeadElement && ctx.head.ignore) ;else if (oldNode instanceof HTMLHeadElement && ctx.head.style !== "morph") {
        handleHeadElement(newContent, oldNode, ctx);
      } else {
        syncNodeFrom(newContent, oldNode, ctx);
        if (!ignoreValueOfActiveElement(oldNode, ctx)) {
          morphChildren(newContent, oldNode, ctx);
        }
      }
      ctx.callbacks.afterNodeMorphed(oldNode, newContent);
      return oldNode;
    }
  }

  /**
   * This is the core algorithm for matching up children.  The idea is to use id sets to try to match up
   * nodes as faithfully as possible.  We greedily match, which allows us to keep the algorithm fast, but
   * by using id sets, we are able to better match up with content deeper in the DOM.
   *
   * Basic algorithm is, for each node in the new content:
   *
   * - if we have reached the end of the old parent, append the new content
   * - if the new content has an id set match with the current insertion point, morph
   * - search for an id set match
   * - if id set match found, morph
   * - otherwise search for a "soft" match
   * - if a soft match is found, morph
   * - otherwise, prepend the new node before the current insertion point
   *
   * The two search algorithms terminate if competing node matches appear to outweigh what can be achieved
   * with the current node.  See findIdSetMatch() and findSoftMatch() for details.
   *
   * @param {Element} newParent the parent element of the new content
   * @param {Element } oldParent the old content that we are merging the new content into
   * @param ctx the merge context
   */
  function morphChildren(newParent, oldParent, ctx) {
    var nextNewChild = newParent.firstChild;
    var insertionPoint = oldParent.firstChild;
    var newChild;

    // run through all the new content
    while (nextNewChild) {
      newChild = nextNewChild;
      nextNewChild = newChild.nextSibling;

      // if we are at the end of the exiting parent's children, just append
      if (insertionPoint == null) {
        if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
        oldParent.appendChild(newChild);
        ctx.callbacks.afterNodeAdded(newChild);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }

      // if the current node has an id set match then morph
      if (isIdSetMatch(newChild, insertionPoint, ctx)) {
        morphOldNodeTo(insertionPoint, newChild, ctx);
        insertionPoint = insertionPoint.nextSibling;
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }

      // otherwise search forward in the existing old children for an id set match
      var idSetMatch = findIdSetMatch(newParent, oldParent, newChild, insertionPoint, ctx);

      // if we found a potential match, remove the nodes until that point and morph
      if (idSetMatch) {
        insertionPoint = removeNodesBetween(insertionPoint, idSetMatch, ctx);
        morphOldNodeTo(idSetMatch, newChild, ctx);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }

      // no id set match found, so scan forward for a soft match for the current node
      var softMatch = findSoftMatch(newParent, oldParent, newChild, insertionPoint, ctx);

      // if we found a soft match for the current node, morph
      if (softMatch) {
        insertionPoint = removeNodesBetween(insertionPoint, softMatch, ctx);
        morphOldNodeTo(softMatch, newChild, ctx);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }

      // abandon all hope of morphing, just insert the new child before the insertion point
      // and move on
      if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
      oldParent.insertBefore(newChild, insertionPoint);
      ctx.callbacks.afterNodeAdded(newChild);
      removeIdsFromConsideration(ctx, newChild);
    }

    // remove any remaining old nodes that didn't match up with new content
    while (insertionPoint !== null) {
      var tempNode = insertionPoint;
      insertionPoint = insertionPoint.nextSibling;
      removeNode(tempNode, ctx);
    }
  }

  //=============================================================================
  // Attribute Syncing Code
  //=============================================================================

  /**
   * @param attr {String} the attribute to be mutated
   * @param to {Element} the element that is going to be updated
   * @param updateType {("update"|"remove")}
   * @param ctx the merge context
   * @returns {boolean} true if the attribute should be ignored, false otherwise
   */
  function ignoreAttribute(attr, to, updateType, ctx) {
    if (attr === 'value' && ctx.ignoreActiveValue && to === document.activeElement) {
      return true;
    }
    return ctx.callbacks.beforeAttributeUpdated(attr, to, updateType) === false;
  }

  /**
   * syncs a given node with another node, copying over all attributes and
   * inner element state from the 'from' node to the 'to' node
   *
   * @param {Element} from the element to copy attributes & state from
   * @param {Element} to the element to copy attributes & state to
   * @param ctx the merge context
   */
  function syncNodeFrom(from, to, ctx) {
    var type = from.nodeType;

    // if is an element type, sync the attributes from the
    // new node into the new node
    if (type === 1 /* element type */) {
      var fromAttributes = from.attributes;
      var toAttributes = to.attributes;
      var _iterator3 = _createForOfIteratorHelper(fromAttributes),
        _step3;
      try {
        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
          var fromAttribute = _step3.value;
          if (ignoreAttribute(fromAttribute.name, to, 'update', ctx)) {
            continue;
          }
          if (to.getAttribute(fromAttribute.name) !== fromAttribute.value) {
            to.setAttribute(fromAttribute.name, fromAttribute.value);
          }
        }
        // iterate backwards to avoid skipping over items when a delete occurs
      } catch (err) {
        _iterator3.e(err);
      } finally {
        _iterator3.f();
      }
      for (var i = toAttributes.length - 1; 0 <= i; i--) {
        var toAttribute = toAttributes[i];
        if (ignoreAttribute(toAttribute.name, to, 'remove', ctx)) {
          continue;
        }
        if (!from.hasAttribute(toAttribute.name)) {
          to.removeAttribute(toAttribute.name);
        }
      }
    }

    // sync text nodes
    if (type === 8 /* comment */ || type === 3 /* text */) {
      if (to.nodeValue !== from.nodeValue) {
        to.nodeValue = from.nodeValue;
      }
    }
    if (!ignoreValueOfActiveElement(to, ctx)) {
      // sync input values
      syncInputValue(from, to, ctx);
    }
  }

  /**
   * @param from {Element} element to sync the value from
   * @param to {Element} element to sync the value to
   * @param attributeName {String} the attribute name
   * @param ctx the merge context
   */
  function syncBooleanAttribute(from, to, attributeName, ctx) {
    if (from[attributeName] !== to[attributeName]) {
      var ignoreUpdate = ignoreAttribute(attributeName, to, 'update', ctx);
      if (!ignoreUpdate) {
        to[attributeName] = from[attributeName];
      }
      if (from[attributeName]) {
        if (!ignoreUpdate) {
          to.setAttribute(attributeName, from[attributeName]);
        }
      } else {
        if (!ignoreAttribute(attributeName, to, 'remove', ctx)) {
          to.removeAttribute(attributeName);
        }
      }
    }
  }

  /**
   * NB: many bothans died to bring us information:
   *
   *  https://github.com/patrick-steele-idem/morphdom/blob/master/src/specialElHandlers.js
   *  https://github.com/choojs/nanomorph/blob/master/lib/morph.jsL113
   *
   * @param from {Element} the element to sync the input value from
   * @param to {Element} the element to sync the input value to
   * @param ctx the merge context
   */
  function syncInputValue(from, to, ctx) {
    if (from instanceof HTMLInputElement && to instanceof HTMLInputElement && from.type !== 'file') {
      var fromValue = from.value;
      var toValue = to.value;

      // sync boolean attributes
      syncBooleanAttribute(from, to, 'checked', ctx);
      syncBooleanAttribute(from, to, 'disabled', ctx);
      if (!from.hasAttribute('value')) {
        if (!ignoreAttribute('value', to, 'remove', ctx)) {
          to.value = '';
          to.removeAttribute('value');
        }
      } else if (fromValue !== toValue) {
        if (!ignoreAttribute('value', to, 'update', ctx)) {
          to.setAttribute('value', fromValue);
          to.value = fromValue;
        }
      }
    } else if (from instanceof HTMLOptionElement) {
      syncBooleanAttribute(from, to, 'selected', ctx);
    } else if (from instanceof HTMLTextAreaElement && to instanceof HTMLTextAreaElement) {
      var _fromValue = from.value;
      var _toValue = to.value;
      if (ignoreAttribute('value', to, 'update', ctx)) {
        return;
      }
      if (_fromValue !== _toValue) {
        to.value = _fromValue;
      }
      if (to.firstChild && to.firstChild.nodeValue !== _fromValue) {
        to.firstChild.nodeValue = _fromValue;
      }
    }
  }

  //=============================================================================
  // the HEAD tag can be handled specially, either w/ a 'merge' or 'append' style
  //=============================================================================
  function handleHeadElement(newHeadTag, currentHead, ctx) {
    var added = [];
    var removed = [];
    var preserved = [];
    var nodesToAppend = [];
    var headMergeStyle = ctx.head.style;

    // put all new head elements into a Map, by their outerHTML
    var srcToNewHeadNodes = new Map();
    var _iterator4 = _createForOfIteratorHelper(newHeadTag.children),
      _step4;
    try {
      for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
        var newHeadChild = _step4.value;
        srcToNewHeadNodes.set(newHeadChild.outerHTML, newHeadChild);
      }

      // for each elt in the current head
    } catch (err) {
      _iterator4.e(err);
    } finally {
      _iterator4.f();
    }
    var _iterator5 = _createForOfIteratorHelper(currentHead.children),
      _step5;
    try {
      for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
        var currentHeadElt = _step5.value;
        // If the current head element is in the map
        var inNewContent = srcToNewHeadNodes.has(currentHeadElt.outerHTML);
        var isReAppended = ctx.head.shouldReAppend(currentHeadElt);
        var isPreserved = ctx.head.shouldPreserve(currentHeadElt);
        if (inNewContent || isPreserved) {
          if (isReAppended) {
            // remove the current version and let the new version replace it and re-execute
            removed.push(currentHeadElt);
          } else {
            // this element already exists and should not be re-appended, so remove it from
            // the new content map, preserving it in the DOM
            srcToNewHeadNodes["delete"](currentHeadElt.outerHTML);
            preserved.push(currentHeadElt);
          }
        } else {
          if (headMergeStyle === "append") {
            // we are appending and this existing element is not new content
            // so if and only if it is marked for re-append do we do anything
            if (isReAppended) {
              removed.push(currentHeadElt);
              nodesToAppend.push(currentHeadElt);
            }
          } else {
            // if this is a merge, we remove this content since it is not in the new head
            if (ctx.head.shouldRemove(currentHeadElt) !== false) {
              removed.push(currentHeadElt);
            }
          }
        }
      }

      // Push the remaining new head elements in the Map into the
      // nodes to append to the head tag
    } catch (err) {
      _iterator5.e(err);
    } finally {
      _iterator5.f();
    }
    nodesToAppend.push.apply(nodesToAppend, _toConsumableArray(srcToNewHeadNodes.values()));
    var promises = [];
    var _loop = function _loop() {
      var newNode = _nodesToAppend[_i2];
      var newElt = document.createRange().createContextualFragment(newNode.outerHTML).firstChild;
      if (ctx.callbacks.beforeNodeAdded(newElt) !== false) {
        if (newElt.href || newElt.src) {
          var resolve = null;
          var promise = new Promise(function (_resolve) {
            resolve = _resolve;
          });
          newElt.addEventListener('load', function () {
            resolve();
          });
          promises.push(promise);
        }
        currentHead.appendChild(newElt);
        ctx.callbacks.afterNodeAdded(newElt);
        added.push(newElt);
      }
    };
    for (var _i2 = 0, _nodesToAppend = nodesToAppend; _i2 < _nodesToAppend.length; _i2++) {
      _loop();
    }

    // remove all removed elements, after we have appended the new elements to avoid
    // additional network requests for things like style sheets
    for (var _i3 = 0, _removed = removed; _i3 < _removed.length; _i3++) {
      var removedElement = _removed[_i3];
      if (ctx.callbacks.beforeNodeRemoved(removedElement) !== false) {
        currentHead.removeChild(removedElement);
        ctx.callbacks.afterNodeRemoved(removedElement);
      }
    }
    ctx.head.afterHeadMorphed(currentHead, {
      added: added,
      kept: preserved,
      removed: removed
    });
    return promises;
  }
  function noOp() {}

  /*
    Deep merges the config object and the Idiomoroph.defaults object to
    produce a final configuration object
   */
  function mergeDefaults(config) {
    var finalConfig = {};
    // copy top level stuff into final config
    Object.assign(finalConfig, defaults);
    Object.assign(finalConfig, config);

    // copy callbacks into final config (do this to deep merge the callbacks)
    finalConfig.callbacks = {};
    Object.assign(finalConfig.callbacks, defaults.callbacks);
    Object.assign(finalConfig.callbacks, config.callbacks);

    // copy head config into final config  (do this to deep merge the head)
    finalConfig.head = {};
    Object.assign(finalConfig.head, defaults.head);
    Object.assign(finalConfig.head, config.head);
    return finalConfig;
  }
  function createMorphContext(oldNode, newContent, config) {
    config = mergeDefaults(config);
    return {
      target: oldNode,
      newContent: newContent,
      config: config,
      morphStyle: config.morphStyle,
      ignoreActive: config.ignoreActive,
      ignoreActiveValue: config.ignoreActiveValue,
      idMap: createIdMap(oldNode, newContent),
      deadIds: new Set(),
      callbacks: config.callbacks,
      head: config.head
    };
  }
  function isIdSetMatch(node1, node2, ctx) {
    if (node1 == null || node2 == null) {
      return false;
    }
    if (node1.nodeType === node2.nodeType && node1.tagName === node2.tagName) {
      if (node1.id !== "" && node1.id === node2.id) {
        return true;
      } else {
        return getIdIntersectionCount(ctx, node1, node2) > 0;
      }
    }
    return false;
  }
  function isSoftMatch(node1, node2) {
    if (node1 == null || node2 == null) {
      return false;
    }
    return node1.nodeType === node2.nodeType && node1.tagName === node2.tagName;
  }
  function removeNodesBetween(startInclusive, endExclusive, ctx) {
    while (startInclusive !== endExclusive) {
      var tempNode = startInclusive;
      startInclusive = startInclusive.nextSibling;
      removeNode(tempNode, ctx);
    }
    removeIdsFromConsideration(ctx, endExclusive);
    return endExclusive.nextSibling;
  }

  //=============================================================================
  // Scans forward from the insertionPoint in the old parent looking for a potential id match
  // for the newChild.  We stop if we find a potential id match for the new child OR
  // if the number of potential id matches we are discarding is greater than the
  // potential id matches for the new child
  //=============================================================================
  function findIdSetMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
    // max id matches we are willing to discard in our search
    var newChildPotentialIdCount = getIdIntersectionCount(ctx, newChild, oldParent);
    var potentialMatch = null;

    // only search forward if there is a possibility of an id match
    if (newChildPotentialIdCount > 0) {
      var _potentialMatch = insertionPoint;
      // if there is a possibility of an id match, scan forward
      // keep track of the potential id match count we are discarding (the
      // newChildPotentialIdCount must be greater than this to make it likely
      // worth it)
      var otherMatchCount = 0;
      while (_potentialMatch != null) {
        // If we have an id match, return the current potential match
        if (isIdSetMatch(newChild, _potentialMatch, ctx)) {
          return _potentialMatch;
        }

        // computer the other potential matches of this new content
        otherMatchCount += getIdIntersectionCount(ctx, _potentialMatch, newContent);
        if (otherMatchCount > newChildPotentialIdCount) {
          // if we have more potential id matches in _other_ content, we
          // do not have a good candidate for an id match, so return null
          return null;
        }

        // advanced to the next old content child
        _potentialMatch = _potentialMatch.nextSibling;
      }
    }
    return potentialMatch;
  }

  //=============================================================================
  // Scans forward from the insertionPoint in the old parent looking for a potential soft match
  // for the newChild.  We stop if we find a potential soft match for the new child OR
  // if we find a potential id match in the old parents children OR if we find two
  // potential soft matches for the next two pieces of new content
  //=============================================================================
  function findSoftMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
    var potentialSoftMatch = insertionPoint;
    var nextSibling = newChild.nextSibling;
    var siblingSoftMatchCount = 0;
    while (potentialSoftMatch != null) {
      if (getIdIntersectionCount(ctx, potentialSoftMatch, newContent) > 0) {
        // the current potential soft match has a potential id set match with the remaining new
        // content so bail out of looking
        return null;
      }

      // if we have a soft match with the current node, return it
      if (isSoftMatch(newChild, potentialSoftMatch)) {
        return potentialSoftMatch;
      }
      if (isSoftMatch(nextSibling, potentialSoftMatch)) {
        // the next new node has a soft match with this node, so
        // increment the count of future soft matches
        siblingSoftMatchCount++;
        nextSibling = nextSibling.nextSibling;

        // If there are two future soft matches, bail to allow the siblings to soft match
        // so that we don't consume future soft matches for the sake of the current node
        if (siblingSoftMatchCount >= 2) {
          return null;
        }
      }

      // advanced to the next old content child
      potentialSoftMatch = potentialSoftMatch.nextSibling;
    }
    return potentialSoftMatch;
  }
  function parseContent(newContent) {
    var parser = new DOMParser();

    // remove svgs to avoid false-positive matches on head, etc.
    var contentWithSvgsRemoved = newContent.replace(/<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim, '');

    // if the newContent contains a html, head or body tag, we can simply parse it w/o wrapping
    if (contentWithSvgsRemoved.match(/<\/html>/) || contentWithSvgsRemoved.match(/<\/head>/) || contentWithSvgsRemoved.match(/<\/body>/)) {
      var content = parser.parseFromString(newContent, "text/html");
      // if it is a full HTML document, return the document itself as the parent container
      if (contentWithSvgsRemoved.match(/<\/html>/)) {
        content.generatedByIdiomorph = true;
        return content;
      } else {
        // otherwise return the html element as the parent container
        var htmlElement = content.firstChild;
        if (htmlElement) {
          htmlElement.generatedByIdiomorph = true;
          return htmlElement;
        } else {
          return null;
        }
      }
    } else {
      // if it is partial HTML, wrap it in a template tag to provide a parent element and also to help
      // deal with touchy tags like tr, tbody, etc.
      var responseDoc = parser.parseFromString("<body><template>" + newContent + "</template></body>", "text/html");
      var _content = responseDoc.body.querySelector('template').content;
      _content.generatedByIdiomorph = true;
      return _content;
    }
  }
  function normalizeContent(newContent) {
    if (newContent == null) {
      // noinspection UnnecessaryLocalVariableJS
      var dummyParent = document.createElement('div');
      return dummyParent;
    } else if (newContent.generatedByIdiomorph) {
      // the template tag created by idiomorph parsing can serve as a dummy parent
      return newContent;
    } else if (newContent instanceof Node) {
      // a single node is added as a child to a dummy parent
      var _dummyParent = document.createElement('div');
      _dummyParent.append(newContent);
      return _dummyParent;
    } else {
      // all nodes in the array or HTMLElement collection are consolidated under
      // a single dummy parent element
      var _dummyParent2 = document.createElement('div');
      for (var _i4 = 0, _arr = _toConsumableArray(newContent); _i4 < _arr.length; _i4++) {
        var elt = _arr[_i4];
        _dummyParent2.append(elt);
      }
      return _dummyParent2;
    }
  }
  function insertSiblings(previousSibling, morphedNode, nextSibling) {
    var stack = [];
    var added = [];
    while (previousSibling != null) {
      stack.push(previousSibling);
      previousSibling = previousSibling.previousSibling;
    }
    while (stack.length > 0) {
      var node = stack.pop();
      added.push(node); // push added preceding siblings on in order and insert
      morphedNode.parentElement.insertBefore(node, morphedNode);
    }
    added.push(morphedNode);
    while (nextSibling != null) {
      stack.push(nextSibling);
      added.push(nextSibling); // here we are going in order, so push on as we scan, rather than add
      nextSibling = nextSibling.nextSibling;
    }
    while (stack.length > 0) {
      morphedNode.parentElement.insertBefore(stack.pop(), morphedNode.nextSibling);
    }
    return added;
  }
  function findBestNodeMatch(newContent, oldNode, ctx) {
    var currentElement;
    currentElement = newContent.firstChild;
    var bestElement = currentElement;
    var score = 0;
    while (currentElement) {
      var newScore = scoreElement(currentElement, oldNode, ctx);
      if (newScore > score) {
        bestElement = currentElement;
        score = newScore;
      }
      currentElement = currentElement.nextSibling;
    }
    return bestElement;
  }
  function scoreElement(node1, node2, ctx) {
    if (isSoftMatch(node1, node2)) {
      return .5 + getIdIntersectionCount(ctx, node1, node2);
    }
    return 0;
  }
  function removeNode(tempNode, ctx) {
    removeIdsFromConsideration(ctx, tempNode);
    if (ctx.callbacks.beforeNodeRemoved(tempNode) === false) return;
    tempNode.remove();
    ctx.callbacks.afterNodeRemoved(tempNode);
  }

  //=============================================================================
  // ID Set Functions
  //=============================================================================

  function isIdInConsideration(ctx, id) {
    return !ctx.deadIds.has(id);
  }
  function idIsWithinNode(ctx, id, targetNode) {
    var idSet = ctx.idMap.get(targetNode) || EMPTY_SET;
    return idSet.has(id);
  }
  function removeIdsFromConsideration(ctx, node) {
    var idSet = ctx.idMap.get(node) || EMPTY_SET;
    var _iterator6 = _createForOfIteratorHelper(idSet),
      _step6;
    try {
      for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
        var id = _step6.value;
        ctx.deadIds.add(id);
      }
    } catch (err) {
      _iterator6.e(err);
    } finally {
      _iterator6.f();
    }
  }
  function getIdIntersectionCount(ctx, node1, node2) {
    var sourceSet = ctx.idMap.get(node1) || EMPTY_SET;
    var matchCount = 0;
    var _iterator7 = _createForOfIteratorHelper(sourceSet),
      _step7;
    try {
      for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
        var id = _step7.value;
        // a potential match is an id in the source and potentialIdsSet, but
        // that has not already been merged into the DOM
        if (isIdInConsideration(ctx, id) && idIsWithinNode(ctx, id, node2)) {
          ++matchCount;
        }
      }
    } catch (err) {
      _iterator7.e(err);
    } finally {
      _iterator7.f();
    }
    return matchCount;
  }

  /**
   * A bottom up algorithm that finds all elements with ids inside of the node
   * argument and populates id sets for those nodes and all their parents, generating
   * a set of ids contained within all nodes for the entire hierarchy in the DOM
   *
   * @param node {Element}
   * @param {Map<Node, Set<String>>} idMap
   */
  function populateIdMapForNode(node, idMap) {
    var nodeParent = node.parentElement;
    // find all elements with an id property
    var idElements = node.querySelectorAll('[id]');
    var _iterator8 = _createForOfIteratorHelper(idElements),
      _step8;
    try {
      for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
        var elt = _step8.value;
        var current = elt;
        // walk up the parent hierarchy of that element, adding the id
        // of element to the parent's id set
        while (current !== nodeParent && current != null) {
          var idSet = idMap.get(current);
          // if the id set doesn't exist, create it and insert it in the  map
          if (idSet == null) {
            idSet = new Set();
            idMap.set(current, idSet);
          }
          idSet.add(elt.id);
          current = current.parentElement;
        }
      }
    } catch (err) {
      _iterator8.e(err);
    } finally {
      _iterator8.f();
    }
  }

  /**
   * This function computes a map of nodes to all ids contained within that node (inclusive of the
   * node).  This map can be used to ask if two nodes have intersecting sets of ids, which allows
   * for a looser definition of "matching" than tradition id matching, and allows child nodes
   * to contribute to a parent nodes matching.
   *
   * @param {Element} oldContent  the old content that will be morphed
   * @param {Element} newContent  the new content to morph to
   * @returns {Map<Node, Set<String>>} a map of nodes to id sets for the
   */
  function createIdMap(oldContent, newContent) {
    var idMap = new Map();
    populateIdMapForNode(oldContent, idMap);
    populateIdMapForNode(newContent, idMap);
    return idMap;
  }

  //=============================================================================
  // This is what ends up becoming the Idiomorph global object
  //=============================================================================
  return {
    morph: morph,
    defaults: defaults
  };
}();
function normalizeAttributesForComparison(element) {
  var isFileInput = element instanceof HTMLInputElement && element.type === 'file';
  if (!isFileInput) {
    if ('value' in element) {
      element.setAttribute('value', element.value);
    } else if (element.hasAttribute('value')) {
      element.setAttribute('value', '');
    }
  }
  Array.from(element.children).forEach(function (child) {
    normalizeAttributesForComparison(child);
  });
}
var syncAttributes = function syncAttributes(fromEl, toEl) {
  for (var i = 0; i < fromEl.attributes.length; i++) {
    var attr = fromEl.attributes[i];
    toEl.setAttribute(attr.name, attr.value);
  }
};
function executeMorphdom(rootFromElement, rootToElement, modifiedFieldElements, getElementValue, externalMutationTracker) {
  var originalElementIdsToSwapAfter = [];
  var originalElementsToPreserve = new Map();
  var markElementAsNeedingPostMorphSwap = function markElementAsNeedingPostMorphSwap(id, replaceWithClone) {
    var oldElement = originalElementsToPreserve.get(id);
    if (!(oldElement instanceof HTMLElement)) {
      throw new Error("Original element with id ".concat(id, " not found"));
    }
    originalElementIdsToSwapAfter.push(id);
    if (!replaceWithClone) {
      return null;
    }
    var clonedOldElement = cloneHTMLElement(oldElement);
    oldElement.replaceWith(clonedOldElement);
    return clonedOldElement;
  };
  rootToElement.querySelectorAll('[data-live-preserve]').forEach(function (newElement) {
    var id = newElement.id;
    if (!id) {
      throw new Error('The data-live-preserve attribute requires an id attribute to be set on the element');
    }
    var oldElement = rootFromElement.querySelector("#".concat(id));
    if (!(oldElement instanceof HTMLElement)) {
      throw new Error("The element with id \"".concat(id, "\" was not found in the original HTML"));
    }
    newElement.removeAttribute('data-live-preserve');
    originalElementsToPreserve.set(id, oldElement);
    syncAttributes(newElement, oldElement);
  });
  Idiomorph.morph(rootFromElement, rootToElement, {
    callbacks: {
      beforeNodeMorphed: function beforeNodeMorphed(fromEl, toEl) {
        var _fromEl$parentElement;
        if (!(fromEl instanceof Element) || !(toEl instanceof Element)) {
          return true;
        }
        if (fromEl === rootFromElement) {
          return true;
        }
        if (fromEl.id && originalElementsToPreserve.has(fromEl.id)) {
          if (fromEl.id === toEl.id) {
            return false;
          }
          var clonedFromEl = markElementAsNeedingPostMorphSwap(fromEl.id, true);
          if (!clonedFromEl) {
            throw new Error('missing clone');
          }
          Idiomorph.morph(clonedFromEl, toEl);
          return false;
        }
        if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
          if (typeof fromEl.__x !== 'undefined') {
            if (!window.Alpine) {
              throw new Error('Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent.');
            }
            if (typeof window.Alpine.morph !== 'function') {
              throw new Error('Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information.');
            }
            window.Alpine.morph(fromEl.__x, toEl);
          }
          if (externalMutationTracker.wasElementAdded(fromEl)) {
            fromEl.insertAdjacentElement('afterend', toEl);
            return false;
          }
          if (modifiedFieldElements.includes(fromEl)) {
            setValueOnElement(toEl, getElementValue(fromEl));
          }
          if (fromEl === document.activeElement && fromEl !== document.body && null !== getModelDirectiveFromElement(fromEl, false)) {
            setValueOnElement(toEl, getElementValue(fromEl));
          }
          var elementChanges = externalMutationTracker.getChangedElement(fromEl);
          if (elementChanges) {
            elementChanges.applyToElement(toEl);
          }
          if (fromEl.nodeName.toUpperCase() !== 'OPTION' && fromEl.isEqualNode(toEl)) {
            var normalizedFromEl = cloneHTMLElement(fromEl);
            normalizeAttributesForComparison(normalizedFromEl);
            var normalizedToEl = cloneHTMLElement(toEl);
            normalizeAttributesForComparison(normalizedToEl);
            if (normalizedFromEl.isEqualNode(normalizedToEl)) {
              return false;
            }
          }
        }
        if (fromEl.hasAttribute('data-skip-morph') || fromEl.id && fromEl.id !== toEl.id) {
          fromEl.innerHTML = toEl.innerHTML;
          return true;
        }
        if ((_fromEl$parentElement = fromEl.parentElement) !== null && _fromEl$parentElement !== void 0 && _fromEl$parentElement.hasAttribute('data-skip-morph')) {
          return false;
        }
        return !fromEl.hasAttribute('data-live-ignore');
      },
      beforeNodeRemoved: function beforeNodeRemoved(node) {
        if (!(node instanceof HTMLElement)) {
          return true;
        }
        if (node.id && originalElementsToPreserve.has(node.id)) {
          markElementAsNeedingPostMorphSwap(node.id, false);
          return true;
        }
        if (externalMutationTracker.wasElementAdded(node)) {
          return false;
        }
        return !node.hasAttribute('data-live-ignore');
      }
    }
  });
  originalElementIdsToSwapAfter.forEach(function (id) {
    var newElement = rootFromElement.querySelector("#".concat(id));
    var originalElement = originalElementsToPreserve.get(id);
    if (!(newElement instanceof HTMLElement) || !(originalElement instanceof HTMLElement)) {
      throw new Error('Missing elements.');
    }
    newElement.replaceWith(originalElement);
  });
}
var UnsyncedInputsTracker = /*#__PURE__*/function () {
  function UnsyncedInputsTracker(component, modelElementResolver) {
    var _this3 = this;
    _classCallCheck(this, UnsyncedInputsTracker);
    this.elementEventListeners = [{
      event: 'input',
      callback: function callback(event) {
        return _this3.handleInputEvent(event);
      }
    }];
    this.component = component;
    this.modelElementResolver = modelElementResolver;
    this.unsyncedInputs = new UnsyncedInputContainer();
  }
  return _createClass(UnsyncedInputsTracker, [{
    key: "activate",
    value: function activate() {
      var _this4 = this;
      this.elementEventListeners.forEach(function (_ref3) {
        var event = _ref3.event,
          callback = _ref3.callback;
        _this4.component.element.addEventListener(event, callback);
      });
    }
  }, {
    key: "deactivate",
    value: function deactivate() {
      var _this5 = this;
      this.elementEventListeners.forEach(function (_ref4) {
        var event = _ref4.event,
          callback = _ref4.callback;
        _this5.component.element.removeEventListener(event, callback);
      });
    }
  }, {
    key: "markModelAsSynced",
    value: function markModelAsSynced(modelName) {
      this.unsyncedInputs.markModelAsSynced(modelName);
    }
  }, {
    key: "handleInputEvent",
    value: function handleInputEvent(event) {
      var target = event.target;
      if (!target) {
        return;
      }
      this.updateModelFromElement(target);
    }
  }, {
    key: "updateModelFromElement",
    value: function updateModelFromElement(element) {
      if (!elementBelongsToThisComponent(element, this.component)) {
        return;
      }
      if (!(element instanceof HTMLElement)) {
        throw new Error('Could not update model for non HTMLElement');
      }
      var modelName = this.modelElementResolver.getModelName(element);
      this.unsyncedInputs.add(element, modelName);
    }
  }, {
    key: "getUnsyncedInputs",
    value: function getUnsyncedInputs() {
      return this.unsyncedInputs.allUnsyncedInputs();
    }
  }, {
    key: "getUnsyncedModels",
    value: function getUnsyncedModels() {
      return Array.from(this.unsyncedInputs.getUnsyncedModelNames());
    }
  }, {
    key: "resetUnsyncedFields",
    value: function resetUnsyncedFields() {
      this.unsyncedInputs.resetUnsyncedFields();
    }
  }]);
}();
var UnsyncedInputContainer = /*#__PURE__*/function () {
  function UnsyncedInputContainer() {
    _classCallCheck(this, UnsyncedInputContainer);
    this.unsyncedNonModelFields = [];
    this.unsyncedModelNames = [];
    this.unsyncedModelFields = new Map();
  }
  return _createClass(UnsyncedInputContainer, [{
    key: "add",
    value: function add(element) {
      var modelName = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      if (modelName) {
        this.unsyncedModelFields.set(modelName, element);
        if (!this.unsyncedModelNames.includes(modelName)) {
          this.unsyncedModelNames.push(modelName);
        }
        return;
      }
      this.unsyncedNonModelFields.push(element);
    }
  }, {
    key: "resetUnsyncedFields",
    value: function resetUnsyncedFields() {
      var _this6 = this;
      this.unsyncedModelFields.forEach(function (value, key) {
        if (!_this6.unsyncedModelNames.includes(key)) {
          _this6.unsyncedModelFields["delete"](key);
        }
      });
    }
  }, {
    key: "allUnsyncedInputs",
    value: function allUnsyncedInputs() {
      return [].concat(_toConsumableArray(this.unsyncedNonModelFields), _toConsumableArray(this.unsyncedModelFields.values()));
    }
  }, {
    key: "markModelAsSynced",
    value: function markModelAsSynced(modelName) {
      var index = this.unsyncedModelNames.indexOf(modelName);
      if (index !== -1) {
        this.unsyncedModelNames.splice(index, 1);
      }
    }
  }, {
    key: "getUnsyncedModelNames",
    value: function getUnsyncedModelNames() {
      return this.unsyncedModelNames;
    }
  }]);
}();
function getDeepData(data, propertyPath) {
  var _parseDeepData = parseDeepData(data, propertyPath),
    currentLevelData = _parseDeepData.currentLevelData,
    finalKey = _parseDeepData.finalKey;
  if (currentLevelData === undefined) {
    return undefined;
  }
  return currentLevelData[finalKey];
}
var parseDeepData = function parseDeepData(data, propertyPath) {
  var finalData = JSON.parse(JSON.stringify(data));
  var currentLevelData = finalData;
  var parts = propertyPath.split('.');
  for (var i = 0; i < parts.length - 1; i++) {
    currentLevelData = currentLevelData[parts[i]];
  }
  var finalKey = parts[parts.length - 1];
  return {
    currentLevelData: currentLevelData,
    finalData: finalData,
    finalKey: finalKey,
    parts: parts
  };
};
var ValueStore = /*#__PURE__*/function () {
  function ValueStore(props) {
    _classCallCheck(this, ValueStore);
    this.props = {};
    this.dirtyProps = {};
    this.pendingProps = {};
    this.updatedPropsFromParent = {};
    this.props = props;
  }
  return _createClass(ValueStore, [{
    key: "get",
    value: function get(name) {
      var normalizedName = normalizeModelName(name);
      if (this.dirtyProps[normalizedName] !== undefined) {
        return this.dirtyProps[normalizedName];
      }
      if (this.pendingProps[normalizedName] !== undefined) {
        return this.pendingProps[normalizedName];
      }
      if (this.props[normalizedName] !== undefined) {
        return this.props[normalizedName];
      }
      return getDeepData(this.props, normalizedName);
    }
  }, {
    key: "has",
    value: function has(name) {
      return this.get(name) !== undefined;
    }
  }, {
    key: "set",
    value: function set(name, value) {
      var normalizedName = normalizeModelName(name);
      if (this.get(normalizedName) === value) {
        return false;
      }
      this.dirtyProps[normalizedName] = value;
      return true;
    }
  }, {
    key: "getOriginalProps",
    value: function getOriginalProps() {
      return _objectSpread({}, this.props);
    }
  }, {
    key: "getDirtyProps",
    value: function getDirtyProps() {
      return _objectSpread({}, this.dirtyProps);
    }
  }, {
    key: "getUpdatedPropsFromParent",
    value: function getUpdatedPropsFromParent() {
      return _objectSpread({}, this.updatedPropsFromParent);
    }
  }, {
    key: "flushDirtyPropsToPending",
    value: function flushDirtyPropsToPending() {
      this.pendingProps = _objectSpread({}, this.dirtyProps);
      this.dirtyProps = {};
    }
  }, {
    key: "reinitializeAllProps",
    value: function reinitializeAllProps(props) {
      this.props = props;
      this.updatedPropsFromParent = {};
      this.pendingProps = {};
    }
  }, {
    key: "pushPendingPropsBackToDirty",
    value: function pushPendingPropsBackToDirty() {
      this.dirtyProps = _objectSpread(_objectSpread({}, this.pendingProps), this.dirtyProps);
      this.pendingProps = {};
    }
  }, {
    key: "storeNewPropsFromParent",
    value: function storeNewPropsFromParent(props) {
      var changed = false;
      for (var _i5 = 0, _Object$entries2 = Object.entries(props); _i5 < _Object$entries2.length; _i5++) {
        var _Object$entries2$_i = _slicedToArray(_Object$entries2[_i5], 2),
          key = _Object$entries2$_i[0],
          value = _Object$entries2$_i[1];
        var currentValue = this.get(key);
        if (currentValue !== value) {
          changed = true;
        }
      }
      if (changed) {
        this.updatedPropsFromParent = props;
      }
      return changed;
    }
  }]);
}();
var Component = /*#__PURE__*/function () {
  function Component(element, name, props, listeners, id, backend, elementDriver) {
    var _this7 = this;
    _classCallCheck(this, Component);
    this.fingerprint = '';
    this.defaultDebounce = 150;
    this.backendRequest = null;
    this.pendingActions = [];
    this.pendingFiles = {};
    this.isRequestPending = false;
    this.requestDebounceTimeout = null;
    this.element = element;
    this.name = name;
    this.backend = backend;
    this.elementDriver = elementDriver;
    this.id = id;
    this.listeners = new Map();
    listeners.forEach(function (listener) {
      var _this7$listeners$get;
      if (!_this7.listeners.has(listener.event)) {
        _this7.listeners.set(listener.event, []);
      }
      (_this7$listeners$get = _this7.listeners.get(listener.event)) === null || _this7$listeners$get === void 0 || _this7$listeners$get.push(listener.action);
    });
    this.valueStore = new ValueStore(props);
    this.unsyncedInputsTracker = new UnsyncedInputsTracker(this, elementDriver);
    this.hooks = new HookManager();
    this.resetPromise();
    this.externalMutationTracker = new ExternalMutationTracker(this.element, function (element) {
      return elementBelongsToThisComponent(element, _this7);
    });
    this.externalMutationTracker.start();
  }
  return _createClass(Component, [{
    key: "addPlugin",
    value: function addPlugin(plugin) {
      plugin.attachToComponent(this);
    }
  }, {
    key: "connect",
    value: function connect() {
      registerComponent(this);
      this.hooks.triggerHook('connect', this);
      this.unsyncedInputsTracker.activate();
      this.externalMutationTracker.start();
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      unregisterComponent(this);
      this.hooks.triggerHook('disconnect', this);
      this.clearRequestDebounceTimeout();
      this.unsyncedInputsTracker.deactivate();
      this.externalMutationTracker.stop();
    }
  }, {
    key: "on",
    value: function on(hookName, callback) {
      this.hooks.register(hookName, callback);
    }
  }, {
    key: "off",
    value: function off(hookName, callback) {
      this.hooks.unregister(hookName, callback);
    }
  }, {
    key: "set",
    value: function set(model, value) {
      var reRender = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      var debounce = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      var promise = this.nextRequestPromise;
      var modelName = normalizeModelName(model);
      if (!this.valueStore.has(modelName)) {
        throw new Error("Invalid model name \"".concat(model, "\"."));
      }
      var isChanged = this.valueStore.set(modelName, value);
      this.hooks.triggerHook('model:set', model, value, this);
      this.unsyncedInputsTracker.markModelAsSynced(modelName);
      if (reRender && isChanged) {
        this.debouncedStartRequest(debounce);
      }
      return promise;
    }
  }, {
    key: "getData",
    value: function getData(model) {
      var modelName = normalizeModelName(model);
      if (!this.valueStore.has(modelName)) {
        throw new Error("Invalid model \"".concat(model, "\"."));
      }
      return this.valueStore.get(modelName);
    }
  }, {
    key: "action",
    value: function action(name) {
      var args = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var debounce = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      var promise = this.nextRequestPromise;
      this.pendingActions.push({
        name: name,
        args: args
      });
      this.debouncedStartRequest(debounce);
      return promise;
    }
  }, {
    key: "files",
    value: function files(key, input) {
      this.pendingFiles[key] = input;
    }
  }, {
    key: "render",
    value: function render() {
      var promise = this.nextRequestPromise;
      this.tryStartingRequest();
      return promise;
    }
  }, {
    key: "getUnsyncedModels",
    value: function getUnsyncedModels() {
      return this.unsyncedInputsTracker.getUnsyncedModels();
    }
  }, {
    key: "emit",
    value: function emit(name, data) {
      var onlyMatchingComponentsNamed = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
      this.performEmit(name, data, false, onlyMatchingComponentsNamed);
    }
  }, {
    key: "emitUp",
    value: function emitUp(name, data) {
      var onlyMatchingComponentsNamed = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
      this.performEmit(name, data, true, onlyMatchingComponentsNamed);
    }
  }, {
    key: "emitSelf",
    value: function emitSelf(name, data) {
      this.doEmit(name, data);
    }
  }, {
    key: "performEmit",
    value: function performEmit(name, data, emitUp, matchingName) {
      var components = findComponents(this, emitUp, matchingName);
      components.forEach(function (component) {
        component.doEmit(name, data);
      });
    }
  }, {
    key: "doEmit",
    value: function doEmit(name, data) {
      var _this8 = this;
      if (!this.listeners.has(name)) {
        return;
      }
      var actions = this.listeners.get(name) || [];
      actions.forEach(function (action) {
        _this8.action(action, data, 1);
      });
    }
  }, {
    key: "isTurboEnabled",
    value: function isTurboEnabled() {
      return typeof Turbo !== 'undefined' && !this.element.closest('[data-turbo="false"]');
    }
  }, {
    key: "tryStartingRequest",
    value: function tryStartingRequest() {
      if (!this.backendRequest) {
        this.performRequest();
        return;
      }
      this.isRequestPending = true;
    }
  }, {
    key: "performRequest",
    value: function performRequest() {
      var _this9 = this;
      var thisPromiseResolve = this.nextRequestPromiseResolve;
      this.resetPromise();
      this.unsyncedInputsTracker.resetUnsyncedFields();
      var filesToSend = {};
      for (var _i6 = 0, _Object$entries3 = Object.entries(this.pendingFiles); _i6 < _Object$entries3.length; _i6++) {
        var _Object$entries3$_i = _slicedToArray(_Object$entries3[_i6], 2),
          key = _Object$entries3$_i[0],
          value = _Object$entries3$_i[1];
        if (value.files) {
          filesToSend[key] = value.files;
        }
      }
      var requestConfig = {
        props: this.valueStore.getOriginalProps(),
        actions: this.pendingActions,
        updated: this.valueStore.getDirtyProps(),
        children: {},
        updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
        files: filesToSend
      };
      this.hooks.triggerHook('request:started', requestConfig);
      this.backendRequest = this.backend.makeRequest(requestConfig.props, requestConfig.actions, requestConfig.updated, requestConfig.children, requestConfig.updatedPropsFromParent, requestConfig.files);
      this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);
      this.pendingActions = [];
      this.valueStore.flushDirtyPropsToPending();
      this.isRequestPending = false;
      this.backendRequest.promise.then(/*#__PURE__*/function () {
        var _ref5 = _asyncToGenerator(/*#__PURE__*/_regeneratorRuntime().mark(function _callee2(response) {
          var _headers$get;
          var backendResponse, html, _i7, _Object$values, input, headers, controls;
          return _regeneratorRuntime().wrap(function _callee2$(_context2) {
            while (1) switch (_context2.prev = _context2.next) {
              case 0:
                backendResponse = new BackendResponse(response);
                _context2.next = 3;
                return backendResponse.getBody();
              case 3:
                html = _context2.sent;
                for (_i7 = 0, _Object$values = Object.values(_this9.pendingFiles); _i7 < _Object$values.length; _i7++) {
                  input = _Object$values[_i7];
                  input.value = '';
                }
                headers = backendResponse.response.headers;
                if (!(!((_headers$get = headers.get('Content-Type')) !== null && _headers$get !== void 0 && _headers$get.includes('application/vnd.live-component+html')) && !headers.get('X-Live-Redirect'))) {
                  _context2.next = 14;
                  break;
                }
                controls = {
                  displayError: true
                };
                _this9.valueStore.pushPendingPropsBackToDirty();
                _this9.hooks.triggerHook('response:error', backendResponse, controls);
                if (controls.displayError) {
                  _this9.renderError(html);
                }
                _this9.backendRequest = null;
                thisPromiseResolve(backendResponse);
                return _context2.abrupt("return", response);
              case 14:
                _this9.processRerender(html, backendResponse);
                _this9.backendRequest = null;
                thisPromiseResolve(backendResponse);
                if (_this9.isRequestPending) {
                  _this9.isRequestPending = false;
                  _this9.performRequest();
                }
                return _context2.abrupt("return", response);
              case 19:
              case "end":
                return _context2.stop();
            }
          }, _callee2);
        }));
        return function (_x) {
          return _ref5.apply(this, arguments);
        };
      }());
    }
  }, {
    key: "processRerender",
    value: function processRerender(html, backendResponse) {
      var _this10 = this;
      var controls = {
        shouldRender: true
      };
      this.hooks.triggerHook('render:started', html, backendResponse, controls);
      if (!controls.shouldRender) {
        return;
      }
      if (backendResponse.response.headers.get('Location')) {
        if (this.isTurboEnabled()) {
          Turbo.visit(backendResponse.response.headers.get('Location'));
        } else {
          window.location.href = backendResponse.response.headers.get('Location') || '';
        }
        return;
      }
      this.hooks.triggerHook('loading.state:finished', this.element);
      var modifiedModelValues = {};
      Object.keys(this.valueStore.getDirtyProps()).forEach(function (modelName) {
        modifiedModelValues[modelName] = _this10.valueStore.get(modelName);
      });
      var newElement;
      try {
        newElement = htmlToElement(html);
        if (!newElement.matches('[data-controller~=live]')) {
          throw new Error('A live component template must contain a single root controller element.');
        }
      } catch (error) {
        console.error("There was a problem with the '".concat(this.name, "' component HTML returned:"), {
          id: this.id
        });
        throw error;
      }
      this.externalMutationTracker.handlePendingChanges();
      this.externalMutationTracker.stop();
      executeMorphdom(this.element, newElement, this.unsyncedInputsTracker.getUnsyncedInputs(), function (element) {
        return getValueFromElement(element, _this10.valueStore);
      }, this.externalMutationTracker);
      this.externalMutationTracker.start();
      var newProps = this.elementDriver.getComponentProps();
      this.valueStore.reinitializeAllProps(newProps);
      var eventsToEmit = this.elementDriver.getEventsToEmit();
      var browserEventsToDispatch = this.elementDriver.getBrowserEventsToDispatch();
      Object.keys(modifiedModelValues).forEach(function (modelName) {
        _this10.valueStore.set(modelName, modifiedModelValues[modelName]);
      });
      eventsToEmit.forEach(function (_ref6) {
        var event = _ref6.event,
          data = _ref6.data,
          target = _ref6.target,
          componentName = _ref6.componentName;
        if (target === 'up') {
          _this10.emitUp(event, data, componentName);
          return;
        }
        if (target === 'self') {
          _this10.emitSelf(event, data);
          return;
        }
        _this10.emit(event, data, componentName);
      });
      browserEventsToDispatch.forEach(function (_ref7) {
        var event = _ref7.event,
          payload = _ref7.payload;
        _this10.element.dispatchEvent(new CustomEvent(event, {
          detail: payload,
          bubbles: true
        }));
      });
      this.hooks.triggerHook('render:finished', this);
    }
  }, {
    key: "calculateDebounce",
    value: function calculateDebounce(debounce) {
      if (debounce === true) {
        return this.defaultDebounce;
      }
      if (debounce === false) {
        return 0;
      }
      return debounce;
    }
  }, {
    key: "clearRequestDebounceTimeout",
    value: function clearRequestDebounceTimeout() {
      if (this.requestDebounceTimeout) {
        clearTimeout(this.requestDebounceTimeout);
        this.requestDebounceTimeout = null;
      }
    }
  }, {
    key: "debouncedStartRequest",
    value: function debouncedStartRequest(debounce) {
      var _this11 = this;
      this.clearRequestDebounceTimeout();
      this.requestDebounceTimeout = window.setTimeout(function () {
        _this11.render();
      }, this.calculateDebounce(debounce));
    }
  }, {
    key: "renderError",
    value: function renderError(html) {
      var modal = document.getElementById('live-component-error');
      if (modal) {
        modal.innerHTML = '';
      } else {
        modal = document.createElement('div');
        modal.id = 'live-component-error';
        modal.style.padding = '50px';
        modal.style.backgroundColor = 'rgba(0, 0, 0, .5)';
        modal.style.zIndex = '100000';
        modal.style.position = 'fixed';
        modal.style.top = '0px';
        modal.style.bottom = '0px';
        modal.style.left = '0px';
        modal.style.right = '0px';
        modal.style.display = 'flex';
        modal.style.flexDirection = 'column';
      }
      var iframe = document.createElement('iframe');
      iframe.style.borderRadius = '5px';
      iframe.style.flexGrow = '1';
      modal.appendChild(iframe);
      document.body.prepend(modal);
      document.body.style.overflow = 'hidden';
      if (iframe.contentWindow) {
        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(html);
        iframe.contentWindow.document.close();
      }
      var closeModal = function closeModal(modal) {
        if (modal) {
          modal.outerHTML = '';
        }
        document.body.style.overflow = 'visible';
      };
      modal.addEventListener('click', function () {
        return closeModal(modal);
      });
      modal.setAttribute('tabindex', '0');
      modal.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeModal(modal);
        }
      });
      modal.focus();
    }
  }, {
    key: "resetPromise",
    value: function resetPromise() {
      var _this12 = this;
      this.nextRequestPromise = new Promise(function (resolve) {
        _this12.nextRequestPromiseResolve = resolve;
      });
    }
  }, {
    key: "_updateFromParentProps",
    value: function _updateFromParentProps(props) {
      var isChanged = this.valueStore.storeNewPropsFromParent(props);
      if (isChanged) {
        this.render();
      }
    }
  }]);
}();
function proxifyComponent(component) {
  return new Proxy(component, {
    get: function get(component, prop) {
      if (prop in component || typeof prop !== 'string') {
        if (typeof component[prop] === 'function') {
          var callable = component[prop];
          return function () {
            for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
              args[_key2] = arguments[_key2];
            }
            return callable.apply(component, args);
          };
        }
        return Reflect.get(component, prop);
      }
      if (component.valueStore.has(prop)) {
        return component.getData(prop);
      }
      return function (args) {
        return component.action.apply(component, [prop, args]);
      };
    },
    set: function set(target, property, value) {
      if (property in target) {
        target[property] = value;
        return true;
      }
      target.set(property, value);
      return true;
    }
  });
}
var StimulusElementDriver = /*#__PURE__*/function () {
  function StimulusElementDriver(controller) {
    _classCallCheck(this, StimulusElementDriver);
    this.controller = controller;
  }
  return _createClass(StimulusElementDriver, [{
    key: "getModelName",
    value: function getModelName(element) {
      var modelDirective = getModelDirectiveFromElement(element, false);
      if (!modelDirective) {
        return null;
      }
      return modelDirective.action;
    }
  }, {
    key: "getComponentProps",
    value: function getComponentProps() {
      return this.controller.propsValue;
    }
  }, {
    key: "getEventsToEmit",
    value: function getEventsToEmit() {
      return this.controller.eventsToEmitValue;
    }
  }, {
    key: "getBrowserEventsToDispatch",
    value: function getBrowserEventsToDispatch() {
      return this.controller.eventsToDispatchValue;
    }
  }]);
}();
function getModelBinding(modelDirective) {
  var shouldRender = true;
  var targetEventName = null;
  var debounce = false;
  modelDirective.modifiers.forEach(function (modifier) {
    switch (modifier.name) {
      case 'on':
        if (!modifier.value) {
          throw new Error("The \"on\" modifier in ".concat(modelDirective.getString(), " requires a value - e.g. on(change)."));
        }
        if (!['input', 'change'].includes(modifier.value)) {
          throw new Error("The \"on\" modifier in ".concat(modelDirective.getString(), " only accepts the arguments \"input\" or \"change\"."));
        }
        targetEventName = modifier.value;
        break;
      case 'norender':
        shouldRender = false;
        break;
      case 'debounce':
        debounce = modifier.value ? Number.parseInt(modifier.value) : true;
        break;
      default:
        throw new Error("Unknown modifier \"".concat(modifier.name, "\" in data-model=\"").concat(modelDirective.getString(), "\"."));
    }
  });
  var _modelDirective$actio = modelDirective.action.split(':'),
    _modelDirective$actio2 = _slicedToArray(_modelDirective$actio, 2),
    modelName = _modelDirective$actio2[0],
    innerModelName = _modelDirective$actio2[1];
  return {
    modelName: modelName,
    innerModelName: innerModelName || null,
    shouldRender: shouldRender,
    debounce: debounce,
    targetEventName: targetEventName
  };
}
var ChildComponentPlugin = /*#__PURE__*/function () {
  function ChildComponentPlugin(component) {
    _classCallCheck(this, ChildComponentPlugin);
    this.parentModelBindings = [];
    this.component = component;
    var modelDirectives = getAllModelDirectiveFromElements(this.component.element);
    this.parentModelBindings = modelDirectives.map(getModelBinding);
  }
  return _createClass(ChildComponentPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this13 = this;
      component.on('request:started', function (requestData) {
        requestData.children = _this13.getChildrenFingerprints();
      });
      component.on('model:set', function (model, value) {
        _this13.notifyParentModelChange(model, value);
      });
    }
  }, {
    key: "getChildrenFingerprints",
    value: function getChildrenFingerprints() {
      var fingerprints = {};
      this.getChildren().forEach(function (child) {
        if (!child.id) {
          throw new Error('missing id');
        }
        fingerprints[child.id] = {
          fingerprint: child.fingerprint,
          tag: child.element.tagName.toLowerCase()
        };
      });
      return fingerprints;
    }
  }, {
    key: "notifyParentModelChange",
    value: function notifyParentModelChange(modelName, value) {
      var parentComponent = findParent(this.component);
      if (!parentComponent) {
        return;
      }
      this.parentModelBindings.forEach(function (modelBinding) {
        var childModelName = modelBinding.innerModelName || 'value';
        if (childModelName !== modelName) {
          return;
        }
        parentComponent.set(modelBinding.modelName, value, modelBinding.shouldRender, modelBinding.debounce);
      });
    }
  }, {
    key: "getChildren",
    value: function getChildren() {
      return findChildren(this.component);
    }
  }]);
}();
var LazyPlugin = /*#__PURE__*/function () {
  function LazyPlugin() {
    _classCallCheck(this, LazyPlugin);
    this.intersectionObserver = null;
  }
  return _createClass(LazyPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _component$element$at,
        _this14 = this;
      if ('lazy' !== ((_component$element$at = component.element.attributes.getNamedItem('loading')) === null || _component$element$at === void 0 ? void 0 : _component$element$at.value)) {
        return;
      }
      component.on('connect', function () {
        _this14.getObserver().observe(component.element);
      });
      component.on('disconnect', function () {
        var _this14$intersectionO;
        (_this14$intersectionO = _this14.intersectionObserver) === null || _this14$intersectionO === void 0 || _this14$intersectionO.unobserve(component.element);
      });
    }
  }, {
    key: "getObserver",
    value: function getObserver() {
      if (!this.intersectionObserver) {
        this.intersectionObserver = new IntersectionObserver(function (entries, observer) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.dispatchEvent(new CustomEvent('live:appear'));
              observer.unobserve(entry.target);
            }
          });
        });
      }
      return this.intersectionObserver;
    }
  }]);
}();
var LoadingPlugin = /*#__PURE__*/function () {
  function LoadingPlugin() {
    _classCallCheck(this, LoadingPlugin);
  }
  return _createClass(LoadingPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this15 = this;
      component.on('loading.state:started', function (element, request) {
        _this15.startLoading(component, element, request);
      });
      component.on('loading.state:finished', function (element) {
        _this15.finishLoading(component, element);
      });
      this.finishLoading(component, component.element);
    }
  }, {
    key: "startLoading",
    value: function startLoading(component, targetElement, backendRequest) {
      this.handleLoadingToggle(component, true, targetElement, backendRequest);
    }
  }, {
    key: "finishLoading",
    value: function finishLoading(component, targetElement) {
      this.handleLoadingToggle(component, false, targetElement, null);
    }
  }, {
    key: "handleLoadingToggle",
    value: function handleLoadingToggle(component, isLoading, targetElement, backendRequest) {
      var _this16 = this;
      if (isLoading) {
        this.addAttributes(targetElement, ['busy']);
      } else {
        this.removeAttributes(targetElement, ['busy']);
      }
      this.getLoadingDirectives(component, targetElement).forEach(function (_ref8) {
        var element = _ref8.element,
          directives = _ref8.directives;
        if (isLoading) {
          _this16.addAttributes(element, ['data-live-is-loading']);
        } else {
          _this16.removeAttributes(element, ['data-live-is-loading']);
        }
        directives.forEach(function (directive) {
          _this16.handleLoadingDirective(element, isLoading, directive, backendRequest);
        });
      });
    }
  }, {
    key: "handleLoadingDirective",
    value: function handleLoadingDirective(element, isLoading, directive, backendRequest) {
      var _this17 = this;
      var finalAction = parseLoadingAction(directive.action, isLoading);
      var targetedActions = [];
      var targetedModels = [];
      var delay = 0;
      var validModifiers = new Map();
      validModifiers.set('delay', function (modifier) {
        if (!isLoading) {
          return;
        }
        delay = modifier.value ? Number.parseInt(modifier.value) : 200;
      });
      validModifiers.set('action', function (modifier) {
        if (!modifier.value) {
          throw new Error("The \"action\" in data-loading must have an action name - e.g. action(foo). It's missing for \"".concat(directive.getString(), "\""));
        }
        targetedActions.push(modifier.value);
      });
      validModifiers.set('model', function (modifier) {
        if (!modifier.value) {
          throw new Error("The \"model\" in data-loading must have an action name - e.g. model(foo). It's missing for \"".concat(directive.getString(), "\""));
        }
        targetedModels.push(modifier.value);
      });
      directive.modifiers.forEach(function (modifier) {
        if (validModifiers.has(modifier.name)) {
          var _validModifiers$get;
          var callable = (_validModifiers$get = validModifiers.get(modifier.name)) !== null && _validModifiers$get !== void 0 ? _validModifiers$get : function () {};
          callable(modifier);
          return;
        }
        throw new Error("Unknown modifier \"".concat(modifier.name, "\" used in data-loading=\"").concat(directive.getString(), "\". Available modifiers are: ").concat(Array.from(validModifiers.keys()).join(', '), "."));
      });
      if (isLoading && targetedActions.length > 0 && backendRequest && !backendRequest.containsOneOfActions(targetedActions)) {
        return;
      }
      if (isLoading && targetedModels.length > 0 && backendRequest && !backendRequest.areAnyModelsUpdated(targetedModels)) {
        return;
      }
      var loadingDirective;
      switch (finalAction) {
        case 'show':
          loadingDirective = function loadingDirective() {
            return _this17.showElement(element);
          };
          break;
        case 'hide':
          loadingDirective = function loadingDirective() {
            return _this17.hideElement(element);
          };
          break;
        case 'addClass':
          loadingDirective = function loadingDirective() {
            return _this17.addClass(element, directive.args);
          };
          break;
        case 'removeClass':
          loadingDirective = function loadingDirective() {
            return _this17.removeClass(element, directive.args);
          };
          break;
        case 'addAttribute':
          loadingDirective = function loadingDirective() {
            return _this17.addAttributes(element, directive.args);
          };
          break;
        case 'removeAttribute':
          loadingDirective = function loadingDirective() {
            return _this17.removeAttributes(element, directive.args);
          };
          break;
        default:
          throw new Error("Unknown data-loading action \"".concat(finalAction, "\""));
      }
      if (delay) {
        window.setTimeout(function () {
          if (backendRequest && !backendRequest.isResolved) {
            loadingDirective();
          }
        }, delay);
        return;
      }
      loadingDirective();
    }
  }, {
    key: "getLoadingDirectives",
    value: function getLoadingDirectives(component, element) {
      var loadingDirectives = [];
      var matchingElements = _toConsumableArray(Array.from(element.querySelectorAll('[data-loading]')));
      matchingElements = matchingElements.filter(function (elt) {
        return elementBelongsToThisComponent(elt, component);
      });
      if (element.hasAttribute('data-loading')) {
        matchingElements = [element].concat(_toConsumableArray(matchingElements));
      }
      matchingElements.forEach(function (element) {
        if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
          throw new Error('Invalid Element Type');
        }
        var directives = parseDirectives(element.dataset.loading || 'show');
        loadingDirectives.push({
          element: element,
          directives: directives
        });
      });
      return loadingDirectives;
    }
  }, {
    key: "showElement",
    value: function showElement(element) {
      element.style.display = 'revert';
    }
  }, {
    key: "hideElement",
    value: function hideElement(element) {
      element.style.display = 'none';
    }
  }, {
    key: "addClass",
    value: function addClass(element, classes) {
      var _element$classList3;
      (_element$classList3 = element.classList).add.apply(_element$classList3, _toConsumableArray(combineSpacedArray(classes)));
    }
  }, {
    key: "removeClass",
    value: function removeClass(element, classes) {
      var _element$classList4;
      (_element$classList4 = element.classList).remove.apply(_element$classList4, _toConsumableArray(combineSpacedArray(classes)));
      if (element.classList.length === 0) {
        element.removeAttribute('class');
      }
    }
  }, {
    key: "addAttributes",
    value: function addAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.setAttribute(attribute, '');
      });
    }
  }, {
    key: "removeAttributes",
    value: function removeAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.removeAttribute(attribute);
      });
    }
  }]);
}();
var parseLoadingAction = function parseLoadingAction(action, isLoading) {
  switch (action) {
    case 'show':
      return isLoading ? 'show' : 'hide';
    case 'hide':
      return isLoading ? 'hide' : 'show';
    case 'addClass':
      return isLoading ? 'addClass' : 'removeClass';
    case 'removeClass':
      return isLoading ? 'removeClass' : 'addClass';
    case 'addAttribute':
      return isLoading ? 'addAttribute' : 'removeAttribute';
    case 'removeAttribute':
      return isLoading ? 'removeAttribute' : 'addAttribute';
  }
  throw new Error("Unknown data-loading action \"".concat(action, "\""));
};
var PageUnloadingPlugin = /*#__PURE__*/function () {
  function PageUnloadingPlugin() {
    _classCallCheck(this, PageUnloadingPlugin);
    this.isConnected = false;
  }
  return _createClass(PageUnloadingPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this18 = this;
      component.on('render:started', function (html, response, controls) {
        if (!_this18.isConnected) {
          controls.shouldRender = false;
        }
      });
      component.on('connect', function () {
        _this18.isConnected = true;
      });
      component.on('disconnect', function () {
        _this18.isConnected = false;
      });
    }
  }]);
}();
var PollingDirector = /*#__PURE__*/function () {
  function PollingDirector(component) {
    _classCallCheck(this, PollingDirector);
    this.isPollingActive = true;
    this.pollingIntervals = [];
    this.component = component;
  }
  return _createClass(PollingDirector, [{
    key: "addPoll",
    value: function addPoll(actionName, duration) {
      this.polls.push({
        actionName: actionName,
        duration: duration
      });
      if (this.isPollingActive) {
        this.initiatePoll(actionName, duration);
      }
    }
  }, {
    key: "startAllPolling",
    value: function startAllPolling() {
      var _this19 = this;
      if (this.isPollingActive) {
        return;
      }
      this.isPollingActive = true;
      this.polls.forEach(function (_ref9) {
        var actionName = _ref9.actionName,
          duration = _ref9.duration;
        _this19.initiatePoll(actionName, duration);
      });
    }
  }, {
    key: "stopAllPolling",
    value: function stopAllPolling() {
      this.isPollingActive = false;
      this.pollingIntervals.forEach(function (interval) {
        clearInterval(interval);
      });
    }
  }, {
    key: "clearPolling",
    value: function clearPolling() {
      this.stopAllPolling();
      this.polls = [];
      this.startAllPolling();
    }
  }, {
    key: "initiatePoll",
    value: function initiatePoll(actionName, duration) {
      var _this20 = this;
      var callback;
      if (actionName === '$render') {
        callback = function callback() {
          _this20.component.render();
        };
      } else {
        callback = function callback() {
          _this20.component.action(actionName, {}, 0);
        };
      }
      var timer = window.setInterval(function () {
        callback();
      }, duration);
      this.pollingIntervals.push(timer);
    }
  }]);
}();
var PollingPlugin = /*#__PURE__*/function () {
  function PollingPlugin() {
    _classCallCheck(this, PollingPlugin);
  }
  return _createClass(PollingPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this21 = this;
      this.element = component.element;
      this.pollingDirector = new PollingDirector(component);
      this.initializePolling();
      component.on('connect', function () {
        _this21.pollingDirector.startAllPolling();
      });
      component.on('disconnect', function () {
        _this21.pollingDirector.stopAllPolling();
      });
      component.on('render:finished', function () {
        _this21.initializePolling();
      });
    }
  }, {
    key: "addPoll",
    value: function addPoll(actionName, duration) {
      this.pollingDirector.addPoll(actionName, duration);
    }
  }, {
    key: "clearPolling",
    value: function clearPolling() {
      this.pollingDirector.clearPolling();
    }
  }, {
    key: "initializePolling",
    value: function initializePolling() {
      var _this22 = this;
      this.clearPolling();
      if (this.element.dataset.poll === undefined) {
        return;
      }
      var rawPollConfig = this.element.dataset.poll;
      var directives = parseDirectives(rawPollConfig || '$render');
      directives.forEach(function (directive) {
        var duration = 2000;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'delay':
              if (modifier.value) {
                duration = Number.parseInt(modifier.value);
              }
              break;
            default:
              console.warn("Unknown modifier \"".concat(modifier.name, "\" in data-poll \"").concat(rawPollConfig, "\"."));
          }
        });
        _this22.addPoll(directive.action, duration);
      });
    }
  }]);
}();
function isValueEmpty(value) {
  if (null === value || value === '' || undefined === value || Array.isArray(value) && value.length === 0) {
    return true;
  }
  if (_typeof(value) !== 'object') {
    return false;
  }
  for (var _i8 = 0, _Object$keys = Object.keys(value); _i8 < _Object$keys.length; _i8++) {
    var key = _Object$keys[_i8];
    if (!isValueEmpty(value[key])) {
      return false;
    }
  }
  return true;
}
function toQueryString(data) {
  var _buildQueryStringEntries = function buildQueryStringEntries(data) {
    var entries = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var baseKey = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';
    Object.entries(data).forEach(function (_ref10) {
      var _ref11 = _slicedToArray(_ref10, 2),
        iKey = _ref11[0],
        iValue = _ref11[1];
      var key = baseKey === '' ? iKey : "".concat(baseKey, "[").concat(iKey, "]");
      if ('' === baseKey && isValueEmpty(iValue)) {
        entries[key] = '';
      } else if (null !== iValue) {
        if (_typeof(iValue) === 'object') {
          entries = _objectSpread(_objectSpread({}, entries), _buildQueryStringEntries(iValue, entries, key));
        } else {
          entries[key] = encodeURIComponent(iValue).replace(/%20/g, '+').replace(/%2C/g, ',');
        }
      }
    });
    return entries;
  };
  var entries = _buildQueryStringEntries(data);
  return Object.entries(entries).map(function (_ref12) {
    var _ref13 = _slicedToArray(_ref12, 2),
      key = _ref13[0],
      value = _ref13[1];
    return "".concat(key, "=").concat(value);
  }).join('&');
}
function fromQueryString(search) {
  search = search.replace('?', '');
  if (search === '') return {};
  var _insertDotNotatedValueIntoData = function insertDotNotatedValueIntoData(key, value, data) {
    var _key$split = key.split('.'),
      _key$split2 = _toArray(_key$split),
      first = _key$split2[0],
      second = _key$split2[1],
      rest = _key$split2.slice(2);
    if (!second) {
      data[key] = value;
      return value;
    }
    if (data[first] === undefined) {
      data[first] = Number.isNaN(Number.parseInt(second)) ? {} : [];
    }
    _insertDotNotatedValueIntoData([second].concat(_toConsumableArray(rest)).join('.'), value, data[first]);
  };
  var entries = search.split('&').map(function (i) {
    return i.split('=');
  });
  var data = {};
  entries.forEach(function (_ref14) {
    var _ref15 = _slicedToArray(_ref14, 2),
      key = _ref15[0],
      value = _ref15[1];
    value = decodeURIComponent(value.replace(/\+/g, '%20'));
    if (!key.includes('[')) {
      data[key] = value;
    } else {
      if ('' === value) return;
      var dotNotatedKey = key.replace(/\[/g, '.').replace(/]/g, '');
      _insertDotNotatedValueIntoData(dotNotatedKey, value, data);
    }
  });
  return data;
}
var UrlUtils = /*#__PURE__*/function (_URL) {
  function UrlUtils() {
    _classCallCheck(this, UrlUtils);
    return _callSuper(this, UrlUtils, arguments);
  }
  _inherits(UrlUtils, _URL);
  return _createClass(UrlUtils, [{
    key: "has",
    value: function has(key) {
      var data = this.getData();
      return Object.keys(data).includes(key);
    }
  }, {
    key: "set",
    value: function set(key, value) {
      var data = this.getData();
      data[key] = value;
      this.setData(data);
    }
  }, {
    key: "get",
    value: function get(key) {
      return this.getData()[key];
    }
  }, {
    key: "remove",
    value: function remove(key) {
      var data = this.getData();
      delete data[key];
      this.setData(data);
    }
  }, {
    key: "getData",
    value: function getData() {
      if (!this.search) {
        return {};
      }
      return fromQueryString(this.search);
    }
  }, {
    key: "setData",
    value: function setData(data) {
      this.search = toQueryString(data);
    }
  }]);
}(/*#__PURE__*/_wrapNativeSuper(URL));
var HistoryStrategy = /*#__PURE__*/function () {
  function HistoryStrategy() {
    _classCallCheck(this, HistoryStrategy);
  }
  return _createClass(HistoryStrategy, null, [{
    key: "replace",
    value: function replace(url) {
      history.replaceState(history.state, '', url);
    }
  }]);
}();
var QueryStringPlugin = /*#__PURE__*/function () {
  function QueryStringPlugin(mapping) {
    _classCallCheck(this, QueryStringPlugin);
    this.mapping = mapping;
  }
  return _createClass(QueryStringPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this23 = this;
      component.on('render:finished', function (component) {
        var urlUtils = new UrlUtils(window.location.href);
        var currentUrl = urlUtils.toString();
        Object.entries(_this23.mapping).forEach(function (_ref16) {
          var _ref17 = _slicedToArray(_ref16, 2),
            prop = _ref17[0],
            mapping = _ref17[1];
          var value = component.valueStore.get(prop);
          urlUtils.set(mapping.name, value);
        });
        if (currentUrl !== urlUtils.toString()) {
          HistoryStrategy.replace(urlUtils);
        }
      });
    }
  }]);
}();
var SetValueOntoModelFieldsPlugin = /*#__PURE__*/function () {
  function SetValueOntoModelFieldsPlugin() {
    _classCallCheck(this, SetValueOntoModelFieldsPlugin);
  }
  return _createClass(SetValueOntoModelFieldsPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this24 = this;
      this.synchronizeValueOfModelFields(component);
      component.on('render:finished', function () {
        _this24.synchronizeValueOfModelFields(component);
      });
    }
  }, {
    key: "synchronizeValueOfModelFields",
    value: function synchronizeValueOfModelFields(component) {
      component.element.querySelectorAll('[data-model]').forEach(function (element) {
        if (!(element instanceof HTMLElement)) {
          throw new Error('Invalid element using data-model.');
        }
        if (element instanceof HTMLFormElement) {
          return;
        }
        if (!elementBelongsToThisComponent(element, component)) {
          return;
        }
        var modelDirective = getModelDirectiveFromElement(element);
        if (!modelDirective) {
          return;
        }
        var modelName = modelDirective.action;
        if (component.getUnsyncedModels().includes(modelName)) {
          return;
        }
        if (component.valueStore.has(modelName)) {
          setValueOnElement(element, component.valueStore.get(modelName));
        }
        if (element instanceof HTMLSelectElement && !element.multiple) {
          component.valueStore.set(modelName, getValueFromElement(element, component.valueStore));
        }
      });
    }
  }]);
}();
var ValidatedFieldsPlugin = /*#__PURE__*/function () {
  function ValidatedFieldsPlugin() {
    _classCallCheck(this, ValidatedFieldsPlugin);
  }
  return _createClass(ValidatedFieldsPlugin, [{
    key: "attachToComponent",
    value: function attachToComponent(component) {
      var _this25 = this;
      component.on('model:set', function (modelName) {
        _this25.handleModelSet(modelName, component.valueStore);
      });
    }
  }, {
    key: "handleModelSet",
    value: function handleModelSet(modelName, valueStore) {
      if (valueStore.has('validatedFields')) {
        var validatedFields = _toConsumableArray(valueStore.get('validatedFields'));
        if (!validatedFields.includes(modelName)) {
          validatedFields.push(modelName);
        }
        valueStore.set('validatedFields', validatedFields);
      }
    }
  }]);
}();
var LiveControllerDefault = /*#__PURE__*/function (_Controller) {
  function LiveControllerDefault() {
    var _this26;
    _classCallCheck(this, LiveControllerDefault);
    _this26 = _callSuper(this, LiveControllerDefault, arguments);
    _this26.pendingActionTriggerModelElement = null;
    _this26.elementEventListeners = [{
      event: 'input',
      callback: function callback(event) {
        return _this26.handleInputEvent(event);
      }
    }, {
      event: 'change',
      callback: function callback(event) {
        return _this26.handleChangeEvent(event);
      }
    }];
    _this26.pendingFiles = {};
    return _this26;
  }
  _inherits(LiveControllerDefault, _Controller);
  return _createClass(LiveControllerDefault, [{
    key: "initialize",
    value: function initialize() {
      this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
      this.createComponent();
    }
  }, {
    key: "connect",
    value: function connect() {
      this.connectComponent();
      this.mutationObserver.observe(this.element, {
        attributes: true
      });
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      this.disconnectComponent();
      this.mutationObserver.disconnect();
    }
  }, {
    key: "update",
    value: function update(event) {
      if (event.type === 'input' || event.type === 'change') {
        throw new Error("Since LiveComponents 2.3, you no longer need data-action=\"live#update\" on form elements. Found on element: ".concat(getElementAsTagText(event.currentTarget)));
      }
      this.updateModelFromElementEvent(event.currentTarget, null);
    }
  }, {
    key: "action",
    value: function action(event) {
      var _this27 = this;
      var params = event.params;
      if (!params.action) {
        throw new Error("No action name provided on element: ".concat(getElementAsTagText(event.currentTarget), ". Did you forget to add the \"data-live-action-param\" attribute?"));
      }
      var rawAction = params.action;
      var actionArgs = _objectSpread({}, params);
      delete actionArgs.action;
      var directives = parseDirectives(rawAction);
      var debounce = false;
      directives.forEach(function (directive) {
        var pendingFiles = {};
        var validModifiers = new Map();
        validModifiers.set('stop', function () {
          event.stopPropagation();
        });
        validModifiers.set('self', function () {
          if (event.target !== event.currentTarget) {
            return;
          }
        });
        validModifiers.set('debounce', function (modifier) {
          debounce = modifier.value ? Number.parseInt(modifier.value) : true;
        });
        validModifiers.set('files', function (modifier) {
          if (!modifier.value) {
            pendingFiles = _this27.pendingFiles;
          } else if (_this27.pendingFiles[modifier.value]) {
            pendingFiles[modifier.value] = _this27.pendingFiles[modifier.value];
          }
        });
        directive.modifiers.forEach(function (modifier) {
          if (validModifiers.has(modifier.name)) {
            var _validModifiers$get2;
            var callable = (_validModifiers$get2 = validModifiers.get(modifier.name)) !== null && _validModifiers$get2 !== void 0 ? _validModifiers$get2 : function () {};
            callable(modifier);
            return;
          }
          console.warn("Unknown modifier ".concat(modifier.name, " in action \"").concat(rawAction, "\". Available modifiers are: ").concat(Array.from(validModifiers.keys()).join(', '), "."));
        });
        for (var _i9 = 0, _Object$entries4 = Object.entries(pendingFiles); _i9 < _Object$entries4.length; _i9++) {
          var _Object$entries4$_i = _slicedToArray(_Object$entries4[_i9], 2),
            key = _Object$entries4$_i[0],
            input = _Object$entries4$_i[1];
          if (input.files) {
            _this27.component.files(key, input);
          }
          delete _this27.pendingFiles[key];
        }
        _this27.component.action(directive.action, actionArgs, debounce);
        if (getModelDirectiveFromElement(event.currentTarget, false)) {
          _this27.pendingActionTriggerModelElement = event.currentTarget;
        }
      });
    }
  }, {
    key: "$render",
    value: function $render() {
      return this.component.render();
    }
  }, {
    key: "emit",
    value: function emit(event) {
      var _this28 = this;
      this.getEmitDirectives(event).forEach(function (_ref18) {
        var name = _ref18.name,
          data = _ref18.data,
          nameMatch = _ref18.nameMatch;
        _this28.component.emit(name, data, nameMatch);
      });
    }
  }, {
    key: "emitUp",
    value: function emitUp(event) {
      var _this29 = this;
      this.getEmitDirectives(event).forEach(function (_ref19) {
        var name = _ref19.name,
          data = _ref19.data,
          nameMatch = _ref19.nameMatch;
        _this29.component.emitUp(name, data, nameMatch);
      });
    }
  }, {
    key: "emitSelf",
    value: function emitSelf(event) {
      var _this30 = this;
      this.getEmitDirectives(event).forEach(function (_ref20) {
        var name = _ref20.name,
          data = _ref20.data;
        _this30.component.emitSelf(name, data);
      });
    }
  }, {
    key: "$updateModel",
    value: function $updateModel(model, value) {
      var shouldRender = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var debounce = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
      return this.component.set(model, value, shouldRender, debounce);
    }
  }, {
    key: "propsUpdatedFromParentValueChanged",
    value: function propsUpdatedFromParentValueChanged() {
      this.component._updateFromParentProps(this.propsUpdatedFromParentValue);
    }
  }, {
    key: "fingerprintValueChanged",
    value: function fingerprintValueChanged() {
      this.component.fingerprint = this.fingerprintValue;
    }
  }, {
    key: "getEmitDirectives",
    value: function getEmitDirectives(event) {
      var params = event.params;
      if (!params.event) {
        throw new Error("No event name provided on element: ".concat(getElementAsTagText(event.currentTarget), ". Did you forget to add the \"data-live-event-param\" attribute?"));
      }
      var eventInfo = params.event;
      var eventArgs = _objectSpread({}, params);
      delete eventArgs.event;
      var directives = parseDirectives(eventInfo);
      var emits = [];
      directives.forEach(function (directive) {
        var nameMatch = null;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'name':
              nameMatch = modifier.value;
              break;
            default:
              throw new Error("Unknown modifier ".concat(modifier.name, " in event \"").concat(eventInfo, "\"."));
          }
        });
        emits.push({
          name: directive.action,
          data: eventArgs,
          nameMatch: nameMatch
        });
      });
      return emits;
    }
  }, {
    key: "createComponent",
    value: function createComponent() {
      var _this31 = this;
      var id = this.element.id || null;
      this.component = new Component(this.element, this.nameValue, this.propsValue, this.listenersValue, id, LiveControllerDefault.backendFactory(this), new StimulusElementDriver(this));
      this.proxiedComponent = proxifyComponent(this.component);
      Object.defineProperty(this.element, '__component', {
        value: this.proxiedComponent,
        writable: true
      });
      if (this.hasDebounceValue) {
        this.component.defaultDebounce = this.debounceValue;
      }
      var plugins = [new LoadingPlugin(), new LazyPlugin(), new ValidatedFieldsPlugin(), new PageUnloadingPlugin(), new PollingPlugin(), new SetValueOntoModelFieldsPlugin(), new QueryStringPlugin(this.queryMappingValue), new ChildComponentPlugin(this.component)];
      plugins.forEach(function (plugin) {
        _this31.component.addPlugin(plugin);
      });
    }
  }, {
    key: "connectComponent",
    value: function connectComponent() {
      var _this32 = this;
      this.component.connect();
      this.mutationObserver.observe(this.element, {
        attributes: true
      });
      this.elementEventListeners.forEach(function (_ref21) {
        var event = _ref21.event,
          callback = _ref21.callback;
        _this32.component.element.addEventListener(event, callback);
      });
      this.dispatchEvent('connect');
    }
  }, {
    key: "disconnectComponent",
    value: function disconnectComponent() {
      var _this33 = this;
      this.component.disconnect();
      this.elementEventListeners.forEach(function (_ref22) {
        var event = _ref22.event,
          callback = _ref22.callback;
        _this33.component.element.removeEventListener(event, callback);
      });
      this.dispatchEvent('disconnect');
    }
  }, {
    key: "handleInputEvent",
    value: function handleInputEvent(event) {
      var target = event.target;
      if (!target) {
        return;
      }
      this.updateModelFromElementEvent(target, 'input');
    }
  }, {
    key: "handleChangeEvent",
    value: function handleChangeEvent(event) {
      var target = event.target;
      if (!target) {
        return;
      }
      this.updateModelFromElementEvent(target, 'change');
    }
  }, {
    key: "updateModelFromElementEvent",
    value: function updateModelFromElementEvent(element, eventName) {
      if (!elementBelongsToThisComponent(element, this.component)) {
        return;
      }
      if (!(element instanceof HTMLElement)) {
        throw new Error('Could not update model for non HTMLElement');
      }
      if (element instanceof HTMLInputElement && element.type === 'file') {
        var _element$files;
        var key = element.name;
        if ((_element$files = element.files) !== null && _element$files !== void 0 && _element$files.length) {
          this.pendingFiles[key] = element;
        } else if (this.pendingFiles[key]) {
          delete this.pendingFiles[key];
        }
      }
      var modelDirective = getModelDirectiveFromElement(element, false);
      if (!modelDirective) {
        return;
      }
      var modelBinding = getModelBinding(modelDirective);
      if (!modelBinding.targetEventName) {
        modelBinding.targetEventName = 'input';
      }
      if (this.pendingActionTriggerModelElement === element) {
        modelBinding.shouldRender = false;
      }
      if (eventName === 'change' && modelBinding.targetEventName === 'input') {
        modelBinding.targetEventName = 'change';
      }
      if (eventName && modelBinding.targetEventName !== eventName) {
        return;
      }
      if (false === modelBinding.debounce) {
        if (modelBinding.targetEventName === 'input') {
          modelBinding.debounce = true;
        } else {
          modelBinding.debounce = 0;
        }
      }
      var finalValue = getValueFromElement(element, this.component.valueStore);
      this.component.set(modelBinding.modelName, finalValue, modelBinding.shouldRender, modelBinding.debounce);
    }
  }, {
    key: "dispatchEvent",
    value: function dispatchEvent(name) {
      var detail = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var canBubble = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var cancelable = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      detail.controller = this;
      detail.component = this.proxiedComponent;
      this.dispatch(name, {
        detail: detail,
        prefix: 'live',
        cancelable: cancelable,
        bubbles: canBubble
      });
    }
  }, {
    key: "onMutations",
    value: function onMutations(mutations) {
      var _this34 = this;
      mutations.forEach(function (mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'id' && _this34.element.id !== _this34.component.id) {
          _this34.disconnectComponent();
          _this34.createComponent();
          _this34.connectComponent();
        }
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_63__.Controller);
LiveControllerDefault.values = {
  name: String,
  url: String,
  props: {
    type: Object,
    "default": {}
  },
  propsUpdatedFromParent: {
    type: Object,
    "default": {}
  },
  listeners: {
    type: Array,
    "default": []
  },
  eventsToEmit: {
    type: Array,
    "default": []
  },
  eventsToDispatch: {
    type: Array,
    "default": []
  },
  debounce: {
    type: Number,
    "default": 150
  },
  fingerprint: {
    type: String,
    "default": ''
  },
  requestMethod: {
    type: String,
    "default": 'post'
  },
  queryMapping: {
    type: Object,
    "default": {}
  }
};
LiveControllerDefault.backendFactory = function (controller) {
  return new Backend(controller.urlValue, controller.requestMethodValue);
};


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_symfony_stimulus-bridge_dist_index_js-node_modules_core-js_modules_es_ar-c79c1d"], () => (__webpack_exec__("./assets/app.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QixJQUFJLEVBQUV4QixNQUFNO0lBQUUsV0FBUyxDQUFDO0VBQUU7QUFDOUMsQ0FBQztBQUNEczdCLHFCQUFxQixDQUFDdUMsY0FBYyxHQUFHLFVBQUNyTCxVQUFVO0VBQUEsT0FBSyxJQUFJam5CLE9BQU8sQ0FBQ2luQixVQUFVLENBQUMyTSxRQUFRLEVBQUUzTSxVQUFVLENBQUM0TSxrQkFBa0IsQ0FBQztBQUFBIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vYXNzZXRzL2FwcC5qcyIsIndlYnBhY2s6Ly8vLi9hc3NldHMvYm9vdHN0cmFwLmpzIiwid2VicGFjazovLy8gXFwuW2p0XXN4Iiwid2VicGFjazovLy8uL2Fzc2V0cy9zdHlsZXMvYXBwLmNzcz82YmU2Iiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy5qc29uIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9jc3JmX3Byb3RlY3Rpb25fY29udHJvbGxlci5qcz81YjAzIiwid2VicGFjazovLy8uL2Fzc2V0cy9jb250cm9sbGVycy9oZWxsb19jb250cm9sbGVyLmpzIiwid2VicGFjazovLy8uL3ZlbmRvci9zeW1mb255L3V4LWxpdmUtY29tcG9uZW50L2Fzc2V0cy9kaXN0L2xpdmUubWluLmNzcz9kZTlhIiwid2VicGFjazovLy8uL3ZlbmRvci9zeW1mb255L3V4LWxpdmUtY29tcG9uZW50L2Fzc2V0cy9kaXN0L2xpdmVfY29udHJvbGxlci5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgJy4vYm9vdHN0cmFwLmpzJztcbmltcG9ydCAnLi9zdHlsZXMvYXBwLmNzcyc7ICIsImltcG9ydCB7IHN0YXJ0U3RpbXVsdXNBcHAgfSBmcm9tICdAc3ltZm9ueS9zdGltdWx1cy1icmlkZ2UnO1xuXG4vLyBSZWdpc3RlcnMgU3RpbXVsdXMgY29udHJvbGxlcnMgZnJvbSBjb250cm9sbGVycy5qc29uIGFuZCBpbiB0aGUgY29udHJvbGxlcnMvIGRpcmVjdG9yeVxuZXhwb3J0IGNvbnN0IGFwcCA9IHN0YXJ0U3RpbXVsdXNBcHAocmVxdWlyZS5jb250ZXh0KFxuICAgICdAc3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlciEuL2NvbnRyb2xsZXJzJyxcbiAgICB0cnVlLFxuICAgIC9cXC5banRdc3g/JC9cbikpO1xuLy8gcmVnaXN0ZXIgYW55IGN1c3RvbSwgM3JkIHBhcnR5IGNvbnRyb2xsZXJzIGhlcmVcbi8vIGFwcC5yZWdpc3Rlcignc29tZV9jb250cm9sbGVyX25hbWUnLCBTb21lSW1wb3J0ZWRDb250cm9sbGVyKTtcbiIsInZhciBtYXAgPSB7XG5cdFwiLi9jc3JmX3Byb3RlY3Rpb25fY29udHJvbGxlci5qc1wiOiBcIi4vbm9kZV9tb2R1bGVzL0BzeW1mb255L3N0aW11bHVzLWJyaWRnZS9sYXp5LWNvbnRyb2xsZXItbG9hZGVyLmpzIS4vYXNzZXRzL2NvbnRyb2xsZXJzL2NzcmZfcHJvdGVjdGlvbl9jb250cm9sbGVyLmpzXCIsXG5cdFwiLi9oZWxsb19jb250cm9sbGVyLmpzXCI6IFwiLi9ub2RlX21vZHVsZXMvQHN5bWZvbnkvc3RpbXVsdXMtYnJpZGdlL2xhenktY29udHJvbGxlci1sb2FkZXIuanMhLi9hc3NldHMvY29udHJvbGxlcnMvaGVsbG9fY29udHJvbGxlci5qc1wiXG59O1xuXG5cbmZ1bmN0aW9uIHdlYnBhY2tDb250ZXh0KHJlcSkge1xuXHR2YXIgaWQgPSB3ZWJwYWNrQ29udGV4dFJlc29sdmUocmVxKTtcblx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18oaWQpO1xufVxuZnVuY3Rpb24gd2VicGFja0NvbnRleHRSZXNvbHZlKHJlcSkge1xuXHRpZighX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1hcCwgcmVxKSkge1xuXHRcdHZhciBlID0gbmV3IEVycm9yKFwiQ2Fubm90IGZpbmQgbW9kdWxlICdcIiArIHJlcSArIFwiJ1wiKTtcblx0XHRlLmNvZGUgPSAnTU9EVUxFX05PVF9GT1VORCc7XG5cdFx0dGhyb3cgZTtcblx0fVxuXHRyZXR1cm4gbWFwW3JlcV07XG59XG53ZWJwYWNrQ29udGV4dC5rZXlzID0gZnVuY3Rpb24gd2VicGFja0NvbnRleHRLZXlzKCkge1xuXHRyZXR1cm4gT2JqZWN0LmtleXMobWFwKTtcbn07XG53ZWJwYWNrQ29udGV4dC5yZXNvbHZlID0gd2VicGFja0NvbnRleHRSZXNvbHZlO1xubW9kdWxlLmV4cG9ydHMgPSB3ZWJwYWNrQ29udGV4dDtcbndlYnBhY2tDb250ZXh0LmlkID0gXCIuL2Fzc2V0cy9jb250cm9sbGVycyBzeW5jIHJlY3Vyc2l2ZSAuL25vZGVfbW9kdWxlcy9Ac3ltZm9ueS9zdGltdWx1cy1icmlkZ2UvbGF6eS1jb250cm9sbGVyLWxvYWRlci5qcyEgXFxcXC5banRdc3g/JFwiOyIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsImltcG9ydCBjb250cm9sbGVyXzAgZnJvbSAnQHN5bWZvbnkvdXgtbGl2ZS1jb21wb25lbnQvZGlzdC9saXZlX2NvbnRyb2xsZXIuanMnO1xuaW1wb3J0ICdAc3ltZm9ueS91eC1saXZlLWNvbXBvbmVudC9kaXN0L2xpdmUubWluLmNzcyc7XG5leHBvcnQgZGVmYXVsdCB7XG4gICdsaXZlJzogY29udHJvbGxlcl8wLFxufTsiLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcbmNvbnN0IGNvbnRyb2xsZXIgPSBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIGNvbnN0cnVjdG9yKGNvbnRleHQpIHtcbiAgICAgICAgc3VwZXIoY29udGV4dCk7XG4gICAgICAgIHRoaXMuX19zdGltdWx1c0xhenlDb250cm9sbGVyID0gdHJ1ZTtcbiAgICB9XG4gICAgaW5pdGlhbGl6ZSgpIHtcbiAgICAgICAgaWYgKHRoaXMuYXBwbGljYXRpb24uY29udHJvbGxlcnMuZmluZCgoY29udHJvbGxlcikgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIGNvbnRyb2xsZXIuaWRlbnRpZmllciA9PT0gdGhpcy5pZGVudGlmaWVyICYmIGNvbnRyb2xsZXIuX19zdGltdWx1c0xhenlDb250cm9sbGVyO1xuICAgICAgICB9KSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGltcG9ydCgnL2FwcC9hc3NldHMvY29udHJvbGxlcnMvY3NyZl9wcm90ZWN0aW9uX2NvbnRyb2xsZXIuanMnKS50aGVuKChjb250cm9sbGVyKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLnJlZ2lzdGVyKHRoaXMuaWRlbnRpZmllciwgY29udHJvbGxlci5kZWZhdWx0KTtcbiAgICAgICAgfSk7XG4gICAgfVxufTtcbmV4cG9ydCB7IGNvbnRyb2xsZXIgYXMgZGVmYXVsdCB9OyIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG4vKlxuICogVGhpcyBpcyBhbiBleGFtcGxlIFN0aW11bHVzIGNvbnRyb2xsZXIhXG4gKlxuICogQW55IGVsZW1lbnQgd2l0aCBhIGRhdGEtY29udHJvbGxlcj1cImhlbGxvXCIgYXR0cmlidXRlIHdpbGwgY2F1c2VcbiAqIHRoaXMgY29udHJvbGxlciB0byBiZSBleGVjdXRlZC4gVGhlIG5hbWUgXCJoZWxsb1wiIGNvbWVzIGZyb20gdGhlIGZpbGVuYW1lOlxuICogaGVsbG9fY29udHJvbGxlci5qcyAtPiBcImhlbGxvXCJcbiAqXG4gKiBEZWxldGUgdGhpcyBmaWxlIG9yIGFkYXB0IGl0IGZvciB5b3VyIHVzZSFcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBjb25uZWN0KCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQudGV4dENvbnRlbnQgPSAnSGVsbG8gU3RpbXVsdXMhIEVkaXQgbWUgaW4gYXNzZXRzL2NvbnRyb2xsZXJzL2hlbGxvX2NvbnRyb2xsZXIuanMnO1xuICAgIH1cbn1cbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG5jbGFzcyBCYWNrZW5kUmVxdWVzdCB7XG4gICAgY29uc3RydWN0b3IocHJvbWlzZSwgYWN0aW9ucywgdXBkYXRlTW9kZWxzKSB7XG4gICAgICAgIHRoaXMuaXNSZXNvbHZlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLnByb21pc2UgPSBwcm9taXNlO1xuICAgICAgICB0aGlzLnByb21pc2UudGhlbigocmVzcG9uc2UpID0+IHtcbiAgICAgICAgICAgIHRoaXMuaXNSZXNvbHZlZCA9IHRydWU7XG4gICAgICAgICAgICByZXR1cm4gcmVzcG9uc2U7XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmFjdGlvbnMgPSBhY3Rpb25zO1xuICAgICAgICB0aGlzLnVwZGF0ZWRNb2RlbHMgPSB1cGRhdGVNb2RlbHM7XG4gICAgfVxuICAgIGNvbnRhaW5zT25lT2ZBY3Rpb25zKHRhcmdldGVkQWN0aW9ucykge1xuICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb25zLmZpbHRlcigoYWN0aW9uKSA9PiB0YXJnZXRlZEFjdGlvbnMuaW5jbHVkZXMoYWN0aW9uKSkubGVuZ3RoID4gMDtcbiAgICB9XG4gICAgYXJlQW55TW9kZWxzVXBkYXRlZCh0YXJnZXRlZE1vZGVscykge1xuICAgICAgICByZXR1cm4gdGhpcy51cGRhdGVkTW9kZWxzLmZpbHRlcigobW9kZWwpID0+IHRhcmdldGVkTW9kZWxzLmluY2x1ZGVzKG1vZGVsKSkubGVuZ3RoID4gMDtcbiAgICB9XG59XG5cbmNsYXNzIFJlcXVlc3RCdWlsZGVyIHtcbiAgICBjb25zdHJ1Y3Rvcih1cmwsIG1ldGhvZCA9ICdwb3N0Jykge1xuICAgICAgICB0aGlzLnVybCA9IHVybDtcbiAgICAgICAgdGhpcy5tZXRob2QgPSBtZXRob2Q7XG4gICAgfVxuICAgIGJ1aWxkUmVxdWVzdChwcm9wcywgYWN0aW9ucywgdXBkYXRlZCwgY2hpbGRyZW4sIHVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQsIGZpbGVzKSB7XG4gICAgICAgIGNvbnN0IHNwbGl0VXJsID0gdGhpcy51cmwuc3BsaXQoJz8nKTtcbiAgICAgICAgbGV0IFt1cmxdID0gc3BsaXRVcmw7XG4gICAgICAgIGNvbnN0IFssIHF1ZXJ5U3RyaW5nXSA9IHNwbGl0VXJsO1xuICAgICAgICBjb25zdCBwYXJhbXMgPSBuZXcgVVJMU2VhcmNoUGFyYW1zKHF1ZXJ5U3RyaW5nIHx8ICcnKTtcbiAgICAgICAgY29uc3QgZmV0Y2hPcHRpb25zID0ge307XG4gICAgICAgIGZldGNoT3B0aW9ucy5oZWFkZXJzID0ge1xuICAgICAgICAgICAgQWNjZXB0OiAnYXBwbGljYXRpb24vdm5kLmxpdmUtY29tcG9uZW50K2h0bWwnLFxuICAgICAgICAgICAgJ1gtUmVxdWVzdGVkLVdpdGgnOiAnWE1MSHR0cFJlcXVlc3QnLFxuICAgICAgICB9O1xuICAgICAgICBjb25zdCB0b3RhbEZpbGVzID0gT2JqZWN0LmVudHJpZXMoZmlsZXMpLnJlZHVjZSgodG90YWwsIGN1cnJlbnQpID0+IHRvdGFsICsgY3VycmVudC5sZW5ndGgsIDApO1xuICAgICAgICBjb25zdCBoYXNGaW5nZXJwcmludHMgPSBPYmplY3Qua2V5cyhjaGlsZHJlbikubGVuZ3RoID4gMDtcbiAgICAgICAgaWYgKGFjdGlvbnMubGVuZ3RoID09PSAwICYmXG4gICAgICAgICAgICB0b3RhbEZpbGVzID09PSAwICYmXG4gICAgICAgICAgICB0aGlzLm1ldGhvZCA9PT0gJ2dldCcgJiZcbiAgICAgICAgICAgIHRoaXMud2lsbERhdGFGaXRJblVybChKU09OLnN0cmluZ2lmeShwcm9wcyksIEpTT04uc3RyaW5naWZ5KHVwZGF0ZWQpLCBwYXJhbXMsIEpTT04uc3RyaW5naWZ5KGNoaWxkcmVuKSwgSlNPTi5zdHJpbmdpZnkodXBkYXRlZFByb3BzRnJvbVBhcmVudCkpKSB7XG4gICAgICAgICAgICBwYXJhbXMuc2V0KCdwcm9wcycsIEpTT04uc3RyaW5naWZ5KHByb3BzKSk7XG4gICAgICAgICAgICBwYXJhbXMuc2V0KCd1cGRhdGVkJywgSlNPTi5zdHJpbmdpZnkodXBkYXRlZCkpO1xuICAgICAgICAgICAgaWYgKE9iamVjdC5rZXlzKHVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgICAgICBwYXJhbXMuc2V0KCdwcm9wc0Zyb21QYXJlbnQnLCBKU09OLnN0cmluZ2lmeSh1cGRhdGVkUHJvcHNGcm9tUGFyZW50KSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoaGFzRmluZ2VycHJpbnRzKSB7XG4gICAgICAgICAgICAgICAgcGFyYW1zLnNldCgnY2hpbGRyZW4nLCBKU09OLnN0cmluZ2lmeShjaGlsZHJlbikpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZmV0Y2hPcHRpb25zLm1ldGhvZCA9ICdHRVQnO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgZmV0Y2hPcHRpb25zLm1ldGhvZCA9ICdQT1NUJztcbiAgICAgICAgICAgIGNvbnN0IHJlcXVlc3REYXRhID0geyBwcm9wcywgdXBkYXRlZCB9O1xuICAgICAgICAgICAgaWYgKE9iamVjdC5rZXlzKHVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgICAgICByZXF1ZXN0RGF0YS5wcm9wc0Zyb21QYXJlbnQgPSB1cGRhdGVkUHJvcHNGcm9tUGFyZW50O1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGhhc0ZpbmdlcnByaW50cykge1xuICAgICAgICAgICAgICAgIHJlcXVlc3REYXRhLmNoaWxkcmVuID0gY2hpbGRyZW47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoYWN0aW9ucy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgaWYgKGFjdGlvbnMubGVuZ3RoID09PSAxKSB7XG4gICAgICAgICAgICAgICAgICAgIHJlcXVlc3REYXRhLmFyZ3MgPSBhY3Rpb25zWzBdLmFyZ3M7XG4gICAgICAgICAgICAgICAgICAgIHVybCArPSBgLyR7ZW5jb2RlVVJJQ29tcG9uZW50KGFjdGlvbnNbMF0ubmFtZSl9YDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIHVybCArPSAnL19iYXRjaCc7XG4gICAgICAgICAgICAgICAgICAgIHJlcXVlc3REYXRhLmFjdGlvbnMgPSBhY3Rpb25zO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGNvbnN0IGZvcm1EYXRhID0gbmV3IEZvcm1EYXRhKCk7XG4gICAgICAgICAgICBmb3JtRGF0YS5hcHBlbmQoJ2RhdGEnLCBKU09OLnN0cmluZ2lmeShyZXF1ZXN0RGF0YSkpO1xuICAgICAgICAgICAgZm9yIChjb25zdCBba2V5LCB2YWx1ZV0gb2YgT2JqZWN0LmVudHJpZXMoZmlsZXMpKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgbGVuZ3RoID0gdmFsdWUubGVuZ3RoO1xuICAgICAgICAgICAgICAgIGZvciAobGV0IGkgPSAwOyBpIDwgbGVuZ3RoOyArK2kpIHtcbiAgICAgICAgICAgICAgICAgICAgZm9ybURhdGEuYXBwZW5kKGtleSwgdmFsdWVbaV0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGZldGNoT3B0aW9ucy5ib2R5ID0gZm9ybURhdGE7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgcGFyYW1zU3RyaW5nID0gcGFyYW1zLnRvU3RyaW5nKCk7XG4gICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICB1cmw6IGAke3VybH0ke3BhcmFtc1N0cmluZy5sZW5ndGggPiAwID8gYD8ke3BhcmFtc1N0cmluZ31gIDogJyd9YCxcbiAgICAgICAgICAgIGZldGNoT3B0aW9ucyxcbiAgICAgICAgfTtcbiAgICB9XG4gICAgd2lsbERhdGFGaXRJblVybChwcm9wc0pzb24sIHVwZGF0ZWRKc29uLCBwYXJhbXMsIGNoaWxkcmVuSnNvbiwgcHJvcHNGcm9tUGFyZW50SnNvbikge1xuICAgICAgICBjb25zdCB1cmxFbmNvZGVkSnNvbkRhdGEgPSBuZXcgVVJMU2VhcmNoUGFyYW1zKHByb3BzSnNvbiArIHVwZGF0ZWRKc29uICsgY2hpbGRyZW5Kc29uICsgcHJvcHNGcm9tUGFyZW50SnNvbikudG9TdHJpbmcoKTtcbiAgICAgICAgcmV0dXJuICh1cmxFbmNvZGVkSnNvbkRhdGEgKyBwYXJhbXMudG9TdHJpbmcoKSkubGVuZ3RoIDwgMTUwMDtcbiAgICB9XG59XG5cbmNsYXNzIEJhY2tlbmQge1xuICAgIGNvbnN0cnVjdG9yKHVybCwgbWV0aG9kID0gJ3Bvc3QnKSB7XG4gICAgICAgIHRoaXMucmVxdWVzdEJ1aWxkZXIgPSBuZXcgUmVxdWVzdEJ1aWxkZXIodXJsLCBtZXRob2QpO1xuICAgIH1cbiAgICBtYWtlUmVxdWVzdChwcm9wcywgYWN0aW9ucywgdXBkYXRlZCwgY2hpbGRyZW4sIHVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQsIGZpbGVzKSB7XG4gICAgICAgIGNvbnN0IHsgdXJsLCBmZXRjaE9wdGlvbnMgfSA9IHRoaXMucmVxdWVzdEJ1aWxkZXIuYnVpbGRSZXF1ZXN0KHByb3BzLCBhY3Rpb25zLCB1cGRhdGVkLCBjaGlsZHJlbiwgdXBkYXRlZFByb3BzRnJvbVBhcmVudCwgZmlsZXMpO1xuICAgICAgICByZXR1cm4gbmV3IEJhY2tlbmRSZXF1ZXN0KGZldGNoKHVybCwgZmV0Y2hPcHRpb25zKSwgYWN0aW9ucy5tYXAoKGJhY2tlbmRBY3Rpb24pID0+IGJhY2tlbmRBY3Rpb24ubmFtZSksIE9iamVjdC5rZXlzKHVwZGF0ZWQpKTtcbiAgICB9XG59XG5cbmNsYXNzIEJhY2tlbmRSZXNwb25zZSB7XG4gICAgY29uc3RydWN0b3IocmVzcG9uc2UpIHtcbiAgICAgICAgdGhpcy5yZXNwb25zZSA9IHJlc3BvbnNlO1xuICAgIH1cbiAgICBhc3luYyBnZXRCb2R5KCkge1xuICAgICAgICBpZiAoIXRoaXMuYm9keSkge1xuICAgICAgICAgICAgdGhpcy5ib2R5ID0gYXdhaXQgdGhpcy5yZXNwb25zZS50ZXh0KCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHRoaXMuYm9keTtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIGdldEVsZW1lbnRBc1RhZ1RleHQoZWxlbWVudCkge1xuICAgIHJldHVybiBlbGVtZW50LmlubmVySFRNTFxuICAgICAgICA/IGVsZW1lbnQub3V0ZXJIVE1MLnNsaWNlKDAsIGVsZW1lbnQub3V0ZXJIVE1MLmluZGV4T2YoZWxlbWVudC5pbm5lckhUTUwpKVxuICAgICAgICA6IGVsZW1lbnQub3V0ZXJIVE1MO1xufVxuXG5sZXQgY29tcG9uZW50TWFwQnlFbGVtZW50ID0gbmV3IFdlYWtNYXAoKTtcbmxldCBjb21wb25lbnRNYXBCeUNvbXBvbmVudCA9IG5ldyBNYXAoKTtcbmNvbnN0IHJlZ2lzdGVyQ29tcG9uZW50ID0gKGNvbXBvbmVudCkgPT4ge1xuICAgIGNvbXBvbmVudE1hcEJ5RWxlbWVudC5zZXQoY29tcG9uZW50LmVsZW1lbnQsIGNvbXBvbmVudCk7XG4gICAgY29tcG9uZW50TWFwQnlDb21wb25lbnQuc2V0KGNvbXBvbmVudCwgY29tcG9uZW50Lm5hbWUpO1xufTtcbmNvbnN0IHVucmVnaXN0ZXJDb21wb25lbnQgPSAoY29tcG9uZW50KSA9PiB7XG4gICAgY29tcG9uZW50TWFwQnlFbGVtZW50LmRlbGV0ZShjb21wb25lbnQuZWxlbWVudCk7XG4gICAgY29tcG9uZW50TWFwQnlDb21wb25lbnQuZGVsZXRlKGNvbXBvbmVudCk7XG59O1xuY29uc3QgZ2V0Q29tcG9uZW50ID0gKGVsZW1lbnQpID0+IG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpID0+IHtcbiAgICBsZXQgY291bnQgPSAwO1xuICAgIGNvbnN0IG1heENvdW50ID0gMTA7XG4gICAgY29uc3QgaW50ZXJ2YWwgPSBzZXRJbnRlcnZhbCgoKSA9PiB7XG4gICAgICAgIGNvbnN0IGNvbXBvbmVudCA9IGNvbXBvbmVudE1hcEJ5RWxlbWVudC5nZXQoZWxlbWVudCk7XG4gICAgICAgIGlmIChjb21wb25lbnQpIHtcbiAgICAgICAgICAgIGNsZWFySW50ZXJ2YWwoaW50ZXJ2YWwpO1xuICAgICAgICAgICAgcmVzb2x2ZShjb21wb25lbnQpO1xuICAgICAgICB9XG4gICAgICAgIGNvdW50Kys7XG4gICAgICAgIGlmIChjb3VudCA+IG1heENvdW50KSB7XG4gICAgICAgICAgICBjbGVhckludGVydmFsKGludGVydmFsKTtcbiAgICAgICAgICAgIHJlamVjdChuZXcgRXJyb3IoYENvbXBvbmVudCBub3QgZm91bmQgZm9yIGVsZW1lbnQgJHtnZXRFbGVtZW50QXNUYWdUZXh0KGVsZW1lbnQpfWApKTtcbiAgICAgICAgfVxuICAgIH0sIDUpO1xufSk7XG5jb25zdCBmaW5kQ29tcG9uZW50cyA9IChjdXJyZW50Q29tcG9uZW50LCBvbmx5UGFyZW50cywgb25seU1hdGNoTmFtZSkgPT4ge1xuICAgIGNvbnN0IGNvbXBvbmVudHMgPSBbXTtcbiAgICBjb21wb25lbnRNYXBCeUNvbXBvbmVudC5mb3JFYWNoKChjb21wb25lbnROYW1lLCBjb21wb25lbnQpID0+IHtcbiAgICAgICAgaWYgKG9ubHlQYXJlbnRzICYmIChjdXJyZW50Q29tcG9uZW50ID09PSBjb21wb25lbnQgfHwgIWNvbXBvbmVudC5lbGVtZW50LmNvbnRhaW5zKGN1cnJlbnRDb21wb25lbnQuZWxlbWVudCkpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgaWYgKG9ubHlNYXRjaE5hbWUgJiYgY29tcG9uZW50TmFtZSAhPT0gb25seU1hdGNoTmFtZSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGNvbXBvbmVudHMucHVzaChjb21wb25lbnQpO1xuICAgIH0pO1xuICAgIHJldHVybiBjb21wb25lbnRzO1xufTtcbmNvbnN0IGZpbmRDaGlsZHJlbiA9IChjdXJyZW50Q29tcG9uZW50KSA9PiB7XG4gICAgY29uc3QgY2hpbGRyZW4gPSBbXTtcbiAgICBjb21wb25lbnRNYXBCeUNvbXBvbmVudC5mb3JFYWNoKChjb21wb25lbnROYW1lLCBjb21wb25lbnQpID0+IHtcbiAgICAgICAgaWYgKGN1cnJlbnRDb21wb25lbnQgPT09IGNvbXBvbmVudCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGlmICghY3VycmVudENvbXBvbmVudC5lbGVtZW50LmNvbnRhaW5zKGNvbXBvbmVudC5lbGVtZW50KSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGxldCBmb3VuZENoaWxkQ29tcG9uZW50ID0gZmFsc2U7XG4gICAgICAgIGNvbXBvbmVudE1hcEJ5Q29tcG9uZW50LmZvckVhY2goKGNoaWxkQ29tcG9uZW50TmFtZSwgY2hpbGRDb21wb25lbnQpID0+IHtcbiAgICAgICAgICAgIGlmIChmb3VuZENoaWxkQ29tcG9uZW50KSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGNoaWxkQ29tcG9uZW50ID09PSBjb21wb25lbnQpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoY2hpbGRDb21wb25lbnQuZWxlbWVudC5jb250YWlucyhjb21wb25lbnQuZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICBmb3VuZENoaWxkQ29tcG9uZW50ID0gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIGNoaWxkcmVuLnB1c2goY29tcG9uZW50KTtcbiAgICB9KTtcbiAgICByZXR1cm4gY2hpbGRyZW47XG59O1xuY29uc3QgZmluZFBhcmVudCA9IChjdXJyZW50Q29tcG9uZW50KSA9PiB7XG4gICAgbGV0IHBhcmVudEVsZW1lbnQgPSBjdXJyZW50Q29tcG9uZW50LmVsZW1lbnQucGFyZW50RWxlbWVudDtcbiAgICB3aGlsZSAocGFyZW50RWxlbWVudCkge1xuICAgICAgICBjb25zdCBjb21wb25lbnQgPSBjb21wb25lbnRNYXBCeUVsZW1lbnQuZ2V0KHBhcmVudEVsZW1lbnQpO1xuICAgICAgICBpZiAoY29tcG9uZW50KSB7XG4gICAgICAgICAgICByZXR1cm4gY29tcG9uZW50O1xuICAgICAgICB9XG4gICAgICAgIHBhcmVudEVsZW1lbnQgPSBwYXJlbnRFbGVtZW50LnBhcmVudEVsZW1lbnQ7XG4gICAgfVxuICAgIHJldHVybiBudWxsO1xufTtcblxuY2xhc3MgSG9va01hbmFnZXIge1xuICAgIGNvbnN0cnVjdG9yKCkge1xuICAgICAgICB0aGlzLmhvb2tzID0gbmV3IE1hcCgpO1xuICAgIH1cbiAgICByZWdpc3Rlcihob29rTmFtZSwgY2FsbGJhY2spIHtcbiAgICAgICAgY29uc3QgaG9va3MgPSB0aGlzLmhvb2tzLmdldChob29rTmFtZSkgfHwgW107XG4gICAgICAgIGhvb2tzLnB1c2goY2FsbGJhY2spO1xuICAgICAgICB0aGlzLmhvb2tzLnNldChob29rTmFtZSwgaG9va3MpO1xuICAgIH1cbiAgICB1bnJlZ2lzdGVyKGhvb2tOYW1lLCBjYWxsYmFjaykge1xuICAgICAgICBjb25zdCBob29rcyA9IHRoaXMuaG9va3MuZ2V0KGhvb2tOYW1lKSB8fCBbXTtcbiAgICAgICAgY29uc3QgaW5kZXggPSBob29rcy5pbmRleE9mKGNhbGxiYWNrKTtcbiAgICAgICAgaWYgKGluZGV4ID09PSAtMSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGhvb2tzLnNwbGljZShpbmRleCwgMSk7XG4gICAgICAgIHRoaXMuaG9va3Muc2V0KGhvb2tOYW1lLCBob29rcyk7XG4gICAgfVxuICAgIHRyaWdnZXJIb29rKGhvb2tOYW1lLCAuLi5hcmdzKSB7XG4gICAgICAgIGNvbnN0IGhvb2tzID0gdGhpcy5ob29rcy5nZXQoaG9va05hbWUpIHx8IFtdO1xuICAgICAgICBob29rcy5mb3JFYWNoKChjYWxsYmFjaykgPT4gY2FsbGJhY2soLi4uYXJncykpO1xuICAgIH1cbn1cblxuY2xhc3MgQ2hhbmdpbmdJdGVtc1RyYWNrZXIge1xuICAgIGNvbnN0cnVjdG9yKCkge1xuICAgICAgICB0aGlzLmNoYW5nZWRJdGVtcyA9IG5ldyBNYXAoKTtcbiAgICAgICAgdGhpcy5yZW1vdmVkSXRlbXMgPSBuZXcgTWFwKCk7XG4gICAgfVxuICAgIHNldEl0ZW0oaXRlbU5hbWUsIG5ld1ZhbHVlLCBwcmV2aW91c1ZhbHVlKSB7XG4gICAgICAgIGlmICh0aGlzLnJlbW92ZWRJdGVtcy5oYXMoaXRlbU5hbWUpKSB7XG4gICAgICAgICAgICBjb25zdCByZW1vdmVkUmVjb3JkID0gdGhpcy5yZW1vdmVkSXRlbXMuZ2V0KGl0ZW1OYW1lKTtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlZEl0ZW1zLmRlbGV0ZShpdGVtTmFtZSk7XG4gICAgICAgICAgICBpZiAocmVtb3ZlZFJlY29yZC5vcmlnaW5hbCA9PT0gbmV3VmFsdWUpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMuY2hhbmdlZEl0ZW1zLmhhcyhpdGVtTmFtZSkpIHtcbiAgICAgICAgICAgIGNvbnN0IG9yaWdpbmFsUmVjb3JkID0gdGhpcy5jaGFuZ2VkSXRlbXMuZ2V0KGl0ZW1OYW1lKTtcbiAgICAgICAgICAgIGlmIChvcmlnaW5hbFJlY29yZC5vcmlnaW5hbCA9PT0gbmV3VmFsdWUpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmNoYW5nZWRJdGVtcy5kZWxldGUoaXRlbU5hbWUpO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMuY2hhbmdlZEl0ZW1zLnNldChpdGVtTmFtZSwgeyBvcmlnaW5hbDogb3JpZ2luYWxSZWNvcmQub3JpZ2luYWwsIG5ldzogbmV3VmFsdWUgfSk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5jaGFuZ2VkSXRlbXMuc2V0KGl0ZW1OYW1lLCB7IG9yaWdpbmFsOiBwcmV2aW91c1ZhbHVlLCBuZXc6IG5ld1ZhbHVlIH0pO1xuICAgIH1cbiAgICByZW1vdmVJdGVtKGl0ZW1OYW1lLCBjdXJyZW50VmFsdWUpIHtcbiAgICAgICAgbGV0IHRydWVPcmlnaW5hbFZhbHVlID0gY3VycmVudFZhbHVlO1xuICAgICAgICBpZiAodGhpcy5jaGFuZ2VkSXRlbXMuaGFzKGl0ZW1OYW1lKSkge1xuICAgICAgICAgICAgY29uc3Qgb3JpZ2luYWxSZWNvcmQgPSB0aGlzLmNoYW5nZWRJdGVtcy5nZXQoaXRlbU5hbWUpO1xuICAgICAgICAgICAgdHJ1ZU9yaWdpbmFsVmFsdWUgPSBvcmlnaW5hbFJlY29yZC5vcmlnaW5hbDtcbiAgICAgICAgICAgIHRoaXMuY2hhbmdlZEl0ZW1zLmRlbGV0ZShpdGVtTmFtZSk7XG4gICAgICAgICAgICBpZiAodHJ1ZU9yaWdpbmFsVmFsdWUgPT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgaWYgKCF0aGlzLnJlbW92ZWRJdGVtcy5oYXMoaXRlbU5hbWUpKSB7XG4gICAgICAgICAgICB0aGlzLnJlbW92ZWRJdGVtcy5zZXQoaXRlbU5hbWUsIHsgb3JpZ2luYWw6IHRydWVPcmlnaW5hbFZhbHVlIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldENoYW5nZWRJdGVtcygpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5jaGFuZ2VkSXRlbXMsIChbbmFtZSwgeyBuZXc6IHZhbHVlIH1dKSA9PiAoeyBuYW1lLCB2YWx1ZSB9KSk7XG4gICAgfVxuICAgIGdldFJlbW92ZWRJdGVtcygpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5yZW1vdmVkSXRlbXMua2V5cygpKTtcbiAgICB9XG4gICAgaXNFbXB0eSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY2hhbmdlZEl0ZW1zLnNpemUgPT09IDAgJiYgdGhpcy5yZW1vdmVkSXRlbXMuc2l6ZSA9PT0gMDtcbiAgICB9XG59XG5cbmNsYXNzIEVsZW1lbnRDaGFuZ2VzIHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgdGhpcy5hZGRlZENsYXNzZXMgPSBuZXcgU2V0KCk7XG4gICAgICAgIHRoaXMucmVtb3ZlZENsYXNzZXMgPSBuZXcgU2V0KCk7XG4gICAgICAgIHRoaXMuc3R5bGVDaGFuZ2VzID0gbmV3IENoYW5naW5nSXRlbXNUcmFja2VyKCk7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlQ2hhbmdlcyA9IG5ldyBDaGFuZ2luZ0l0ZW1zVHJhY2tlcigpO1xuICAgIH1cbiAgICBhZGRDbGFzcyhjbGFzc05hbWUpIHtcbiAgICAgICAgaWYgKCF0aGlzLnJlbW92ZWRDbGFzc2VzLmRlbGV0ZShjbGFzc05hbWUpKSB7XG4gICAgICAgICAgICB0aGlzLmFkZGVkQ2xhc3Nlcy5hZGQoY2xhc3NOYW1lKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZW1vdmVDbGFzcyhjbGFzc05hbWUpIHtcbiAgICAgICAgaWYgKCF0aGlzLmFkZGVkQ2xhc3Nlcy5kZWxldGUoY2xhc3NOYW1lKSkge1xuICAgICAgICAgICAgdGhpcy5yZW1vdmVkQ2xhc3Nlcy5hZGQoY2xhc3NOYW1lKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhZGRTdHlsZShzdHlsZU5hbWUsIG5ld1ZhbHVlLCBvcmlnaW5hbFZhbHVlKSB7XG4gICAgICAgIHRoaXMuc3R5bGVDaGFuZ2VzLnNldEl0ZW0oc3R5bGVOYW1lLCBuZXdWYWx1ZSwgb3JpZ2luYWxWYWx1ZSk7XG4gICAgfVxuICAgIHJlbW92ZVN0eWxlKHN0eWxlTmFtZSwgb3JpZ2luYWxWYWx1ZSkge1xuICAgICAgICB0aGlzLnN0eWxlQ2hhbmdlcy5yZW1vdmVJdGVtKHN0eWxlTmFtZSwgb3JpZ2luYWxWYWx1ZSk7XG4gICAgfVxuICAgIGFkZEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lLCBuZXdWYWx1ZSwgb3JpZ2luYWxWYWx1ZSkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZUNoYW5nZXMuc2V0SXRlbShhdHRyaWJ1dGVOYW1lLCBuZXdWYWx1ZSwgb3JpZ2luYWxWYWx1ZSk7XG4gICAgfVxuICAgIHJlbW92ZUF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lLCBvcmlnaW5hbFZhbHVlKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlQ2hhbmdlcy5yZW1vdmVJdGVtKGF0dHJpYnV0ZU5hbWUsIG9yaWdpbmFsVmFsdWUpO1xuICAgIH1cbiAgICBnZXRBZGRlZENsYXNzZXMoKSB7XG4gICAgICAgIHJldHVybiBbLi4udGhpcy5hZGRlZENsYXNzZXNdO1xuICAgIH1cbiAgICBnZXRSZW1vdmVkQ2xhc3NlcygpIHtcbiAgICAgICAgcmV0dXJuIFsuLi50aGlzLnJlbW92ZWRDbGFzc2VzXTtcbiAgICB9XG4gICAgZ2V0Q2hhbmdlZFN0eWxlcygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc3R5bGVDaGFuZ2VzLmdldENoYW5nZWRJdGVtcygpO1xuICAgIH1cbiAgICBnZXRSZW1vdmVkU3R5bGVzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zdHlsZUNoYW5nZXMuZ2V0UmVtb3ZlZEl0ZW1zKCk7XG4gICAgfVxuICAgIGdldENoYW5nZWRBdHRyaWJ1dGVzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hdHRyaWJ1dGVDaGFuZ2VzLmdldENoYW5nZWRJdGVtcygpO1xuICAgIH1cbiAgICBnZXRSZW1vdmVkQXR0cmlidXRlcygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYXR0cmlidXRlQ2hhbmdlcy5nZXRSZW1vdmVkSXRlbXMoKTtcbiAgICB9XG4gICAgYXBwbHlUb0VsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBlbGVtZW50LmNsYXNzTGlzdC5hZGQoLi4udGhpcy5hZGRlZENsYXNzZXMpO1xuICAgICAgICBlbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoLi4udGhpcy5yZW1vdmVkQ2xhc3Nlcyk7XG4gICAgICAgIHRoaXMuc3R5bGVDaGFuZ2VzLmdldENoYW5nZWRJdGVtcygpLmZvckVhY2goKGNoYW5nZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudC5zdHlsZS5zZXRQcm9wZXJ0eShjaGFuZ2UubmFtZSwgY2hhbmdlLnZhbHVlKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMuc3R5bGVDaGFuZ2VzLmdldFJlbW92ZWRJdGVtcygpLmZvckVhY2goKHN0eWxlTmFtZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudC5zdHlsZS5yZW1vdmVQcm9wZXJ0eShzdHlsZU5hbWUpO1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVDaGFuZ2VzLmdldENoYW5nZWRJdGVtcygpLmZvckVhY2goKGNoYW5nZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoY2hhbmdlLm5hbWUsIGNoYW5nZS52YWx1ZSk7XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZUNoYW5nZXMuZ2V0UmVtb3ZlZEl0ZW1zKCkuZm9yRWFjaCgoYXR0cmlidXRlTmFtZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudC5yZW1vdmVBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBpc0VtcHR5KCkge1xuICAgICAgICByZXR1cm4gKHRoaXMuYWRkZWRDbGFzc2VzLnNpemUgPT09IDAgJiZcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlZENsYXNzZXMuc2l6ZSA9PT0gMCAmJlxuICAgICAgICAgICAgdGhpcy5zdHlsZUNoYW5nZXMuaXNFbXB0eSgpICYmXG4gICAgICAgICAgICB0aGlzLmF0dHJpYnV0ZUNoYW5nZXMuaXNFbXB0eSgpKTtcbiAgICB9XG59XG5cbmNsYXNzIEV4dGVybmFsTXV0YXRpb25UcmFja2VyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBzaG91bGRUcmFja0NoYW5nZUNhbGxiYWNrKSB7XG4gICAgICAgIHRoaXMuY2hhbmdlZEVsZW1lbnRzID0gbmV3IFdlYWtNYXAoKTtcbiAgICAgICAgdGhpcy5jaGFuZ2VkRWxlbWVudHNDb3VudCA9IDA7XG4gICAgICAgIHRoaXMuYWRkZWRFbGVtZW50cyA9IFtdO1xuICAgICAgICB0aGlzLnJlbW92ZWRFbGVtZW50cyA9IFtdO1xuICAgICAgICB0aGlzLmlzU3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBlbGVtZW50O1xuICAgICAgICB0aGlzLnNob3VsZFRyYWNrQ2hhbmdlQ2FsbGJhY2sgPSBzaG91bGRUcmFja0NoYW5nZUNhbGxiYWNrO1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcih0aGlzLm9uTXV0YXRpb25zLmJpbmQodGhpcykpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKHRoaXMuaXNTdGFydGVkKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtZW50LCB7XG4gICAgICAgICAgICBjaGlsZExpc3Q6IHRydWUsXG4gICAgICAgICAgICBzdWJ0cmVlOiB0cnVlLFxuICAgICAgICAgICAgYXR0cmlidXRlczogdHJ1ZSxcbiAgICAgICAgICAgIGF0dHJpYnV0ZU9sZFZhbHVlOiB0cnVlLFxuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5pc1N0YXJ0ZWQgPSB0cnVlO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy5pc1N0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICB0aGlzLmlzU3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldENoYW5nZWRFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY2hhbmdlZEVsZW1lbnRzLmhhcyhlbGVtZW50KSA/IHRoaXMuY2hhbmdlZEVsZW1lbnRzLmdldChlbGVtZW50KSA6IG51bGw7XG4gICAgfVxuICAgIGdldEFkZGVkRWxlbWVudHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFkZGVkRWxlbWVudHM7XG4gICAgfVxuICAgIHdhc0VsZW1lbnRBZGRlZChlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFkZGVkRWxlbWVudHMuaW5jbHVkZXMoZWxlbWVudCk7XG4gICAgfVxuICAgIGhhbmRsZVBlbmRpbmdDaGFuZ2VzKCkge1xuICAgICAgICB0aGlzLm9uTXV0YXRpb25zKHRoaXMubXV0YXRpb25PYnNlcnZlci50YWtlUmVjb3JkcygpKTtcbiAgICB9XG4gICAgb25NdXRhdGlvbnMobXV0YXRpb25zKSB7XG4gICAgICAgIGNvbnN0IGhhbmRsZWRBdHRyaWJ1dGVNdXRhdGlvbnMgPSBuZXcgV2Vha01hcCgpO1xuICAgICAgICBmb3IgKGNvbnN0IG11dGF0aW9uIG9mIG11dGF0aW9ucykge1xuICAgICAgICAgICAgY29uc3QgZWxlbWVudCA9IG11dGF0aW9uLnRhcmdldDtcbiAgICAgICAgICAgIGlmICghdGhpcy5zaG91bGRUcmFja0NoYW5nZUNhbGxiYWNrKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodGhpcy5pc0VsZW1lbnRBZGRlZEJ5VHJhbnNsYXRpb24oZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGxldCBpc0NoYW5nZUluQWRkZWRFbGVtZW50ID0gZmFsc2U7XG4gICAgICAgICAgICBmb3IgKGNvbnN0IGFkZGVkRWxlbWVudCBvZiB0aGlzLmFkZGVkRWxlbWVudHMpIHtcbiAgICAgICAgICAgICAgICBpZiAoYWRkZWRFbGVtZW50LmNvbnRhaW5zKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgICAgIGlzQ2hhbmdlSW5BZGRlZEVsZW1lbnQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoaXNDaGFuZ2VJbkFkZGVkRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgc3dpdGNoIChtdXRhdGlvbi50eXBlKSB7XG4gICAgICAgICAgICAgICAgY2FzZSAnY2hpbGRMaXN0JzpcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5oYW5kbGVDaGlsZExpc3RNdXRhdGlvbihtdXRhdGlvbik7XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIGNhc2UgJ2F0dHJpYnV0ZXMnOlxuICAgICAgICAgICAgICAgICAgICBpZiAoIWhhbmRsZWRBdHRyaWJ1dGVNdXRhdGlvbnMuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBoYW5kbGVkQXR0cmlidXRlTXV0YXRpb25zLnNldChlbGVtZW50LCBbXSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKCFoYW5kbGVkQXR0cmlidXRlTXV0YXRpb25zLmdldChlbGVtZW50KS5pbmNsdWRlcyhtdXRhdGlvbi5hdHRyaWJ1dGVOYW1lKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGhpcy5oYW5kbGVBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbik7XG4gICAgICAgICAgICAgICAgICAgICAgICBoYW5kbGVkQXR0cmlidXRlTXV0YXRpb25zLnNldChlbGVtZW50LCBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgLi4uaGFuZGxlZEF0dHJpYnV0ZU11dGF0aW9ucy5nZXQoZWxlbWVudCksXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbXV0YXRpb24uYXR0cmlidXRlTmFtZSxcbiAgICAgICAgICAgICAgICAgICAgICAgIF0pO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGhhbmRsZUNoaWxkTGlzdE11dGF0aW9uKG11dGF0aW9uKSB7XG4gICAgICAgIG11dGF0aW9uLmFkZGVkTm9kZXMuZm9yRWFjaCgobm9kZSkgPT4ge1xuICAgICAgICAgICAgaWYgKCEobm9kZSBpbnN0YW5jZW9mIEVsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHRoaXMucmVtb3ZlZEVsZW1lbnRzLmluY2x1ZGVzKG5vZGUpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5yZW1vdmVkRWxlbWVudHMuc3BsaWNlKHRoaXMucmVtb3ZlZEVsZW1lbnRzLmluZGV4T2Yobm9kZSksIDEpO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh0aGlzLmlzRWxlbWVudEFkZGVkQnlUcmFuc2xhdGlvbihub2RlKSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMuYWRkZWRFbGVtZW50cy5wdXNoKG5vZGUpO1xuICAgICAgICB9KTtcbiAgICAgICAgbXV0YXRpb24ucmVtb3ZlZE5vZGVzLmZvckVhY2goKG5vZGUpID0+IHtcbiAgICAgICAgICAgIGlmICghKG5vZGUgaW5zdGFuY2VvZiBFbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh0aGlzLmFkZGVkRWxlbWVudHMuaW5jbHVkZXMobm9kZSkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmFkZGVkRWxlbWVudHMuc3BsaWNlKHRoaXMuYWRkZWRFbGVtZW50cy5pbmRleE9mKG5vZGUpLCAxKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLnJlbW92ZWRFbGVtZW50cy5wdXNoKG5vZGUpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgaGFuZGxlQXR0cmlidXRlTXV0YXRpb24obXV0YXRpb24pIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IG11dGF0aW9uLnRhcmdldDtcbiAgICAgICAgaWYgKCF0aGlzLmNoYW5nZWRFbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuY2hhbmdlZEVsZW1lbnRzLnNldChlbGVtZW50LCBuZXcgRWxlbWVudENoYW5nZXMoKSk7XG4gICAgICAgICAgICB0aGlzLmNoYW5nZWRFbGVtZW50c0NvdW50Kys7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgY2hhbmdlZEVsZW1lbnQgPSB0aGlzLmNoYW5nZWRFbGVtZW50cy5nZXQoZWxlbWVudCk7XG4gICAgICAgIHN3aXRjaCAobXV0YXRpb24uYXR0cmlidXRlTmFtZSkge1xuICAgICAgICAgICAgY2FzZSAnY2xhc3MnOlxuICAgICAgICAgICAgICAgIHRoaXMuaGFuZGxlQ2xhc3NBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbiwgY2hhbmdlZEVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAnc3R5bGUnOlxuICAgICAgICAgICAgICAgIHRoaXMuaGFuZGxlU3R5bGVBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbiwgY2hhbmdlZEVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICB0aGlzLmhhbmRsZUdlbmVyaWNBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbiwgY2hhbmdlZEVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChjaGFuZ2VkRWxlbWVudC5pc0VtcHR5KCkpIHtcbiAgICAgICAgICAgIHRoaXMuY2hhbmdlZEVsZW1lbnRzLmRlbGV0ZShlbGVtZW50KTtcbiAgICAgICAgICAgIHRoaXMuY2hhbmdlZEVsZW1lbnRzQ291bnQtLTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBoYW5kbGVDbGFzc0F0dHJpYnV0ZU11dGF0aW9uKG11dGF0aW9uLCBlbGVtZW50Q2hhbmdlcykge1xuICAgICAgICBjb25zdCBlbGVtZW50ID0gbXV0YXRpb24udGFyZ2V0O1xuICAgICAgICBjb25zdCBwcmV2aW91c1ZhbHVlID0gbXV0YXRpb24ub2xkVmFsdWUgfHwgJyc7XG4gICAgICAgIGNvbnN0IHByZXZpb3VzVmFsdWVzID0gcHJldmlvdXNWYWx1ZS5tYXRjaCgvKFxcUyspL2d1KSB8fCBbXTtcbiAgICAgICAgY29uc3QgbmV3VmFsdWVzID0gW10uc2xpY2UuY2FsbChlbGVtZW50LmNsYXNzTGlzdCk7XG4gICAgICAgIGNvbnN0IGFkZGVkVmFsdWVzID0gbmV3VmFsdWVzLmZpbHRlcigodmFsdWUpID0+ICFwcmV2aW91c1ZhbHVlcy5pbmNsdWRlcyh2YWx1ZSkpO1xuICAgICAgICBjb25zdCByZW1vdmVkVmFsdWVzID0gcHJldmlvdXNWYWx1ZXMuZmlsdGVyKCh2YWx1ZSkgPT4gIW5ld1ZhbHVlcy5pbmNsdWRlcyh2YWx1ZSkpO1xuICAgICAgICBhZGRlZFZhbHVlcy5mb3JFYWNoKCh2YWx1ZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudENoYW5nZXMuYWRkQ2xhc3ModmFsdWUpO1xuICAgICAgICB9KTtcbiAgICAgICAgcmVtb3ZlZFZhbHVlcy5mb3JFYWNoKCh2YWx1ZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudENoYW5nZXMucmVtb3ZlQ2xhc3ModmFsdWUpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgaGFuZGxlU3R5bGVBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbiwgZWxlbWVudENoYW5nZXMpIHtcbiAgICAgICAgY29uc3QgZWxlbWVudCA9IG11dGF0aW9uLnRhcmdldDtcbiAgICAgICAgY29uc3QgcHJldmlvdXNWYWx1ZSA9IG11dGF0aW9uLm9sZFZhbHVlIHx8ICcnO1xuICAgICAgICBjb25zdCBwcmV2aW91c1N0eWxlcyA9IHRoaXMuZXh0cmFjdFN0eWxlcyhwcmV2aW91c1ZhbHVlKTtcbiAgICAgICAgY29uc3QgbmV3VmFsdWUgPSBlbGVtZW50LmdldEF0dHJpYnV0ZSgnc3R5bGUnKSB8fCAnJztcbiAgICAgICAgY29uc3QgbmV3U3R5bGVzID0gdGhpcy5leHRyYWN0U3R5bGVzKG5ld1ZhbHVlKTtcbiAgICAgICAgY29uc3QgYWRkZWRPckNoYW5nZWRTdHlsZXMgPSBPYmplY3Qua2V5cyhuZXdTdHlsZXMpLmZpbHRlcigoa2V5KSA9PiBwcmV2aW91c1N0eWxlc1trZXldID09PSB1bmRlZmluZWQgfHwgcHJldmlvdXNTdHlsZXNba2V5XSAhPT0gbmV3U3R5bGVzW2tleV0pO1xuICAgICAgICBjb25zdCByZW1vdmVkU3R5bGVzID0gT2JqZWN0LmtleXMocHJldmlvdXNTdHlsZXMpLmZpbHRlcigoa2V5KSA9PiAhbmV3U3R5bGVzW2tleV0pO1xuICAgICAgICBhZGRlZE9yQ2hhbmdlZFN0eWxlcy5mb3JFYWNoKChzdHlsZSkgPT4ge1xuICAgICAgICAgICAgZWxlbWVudENoYW5nZXMuYWRkU3R5bGUoc3R5bGUsIG5ld1N0eWxlc1tzdHlsZV0sIHByZXZpb3VzU3R5bGVzW3N0eWxlXSA9PT0gdW5kZWZpbmVkID8gbnVsbCA6IHByZXZpb3VzU3R5bGVzW3N0eWxlXSk7XG4gICAgICAgIH0pO1xuICAgICAgICByZW1vdmVkU3R5bGVzLmZvckVhY2goKHN0eWxlKSA9PiB7XG4gICAgICAgICAgICBlbGVtZW50Q2hhbmdlcy5yZW1vdmVTdHlsZShzdHlsZSwgcHJldmlvdXNTdHlsZXNbc3R5bGVdKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGhhbmRsZUdlbmVyaWNBdHRyaWJ1dGVNdXRhdGlvbihtdXRhdGlvbiwgZWxlbWVudENoYW5nZXMpIHtcbiAgICAgICAgY29uc3QgYXR0cmlidXRlTmFtZSA9IG11dGF0aW9uLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIGNvbnN0IGVsZW1lbnQgPSBtdXRhdGlvbi50YXJnZXQ7XG4gICAgICAgIGxldCBvbGRWYWx1ZSA9IG11dGF0aW9uLm9sZFZhbHVlO1xuICAgICAgICBsZXQgbmV3VmFsdWUgPSBlbGVtZW50LmdldEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgaWYgKG9sZFZhbHVlID09PSBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgICAgICBvbGRWYWx1ZSA9ICcnO1xuICAgICAgICB9XG4gICAgICAgIGlmIChuZXdWYWx1ZSA9PT0gYXR0cmlidXRlTmFtZSkge1xuICAgICAgICAgICAgbmV3VmFsdWUgPSAnJztcbiAgICAgICAgfVxuICAgICAgICBpZiAoIWVsZW1lbnQuaGFzQXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpKSB7XG4gICAgICAgICAgICBpZiAob2xkVmFsdWUgPT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbGVtZW50Q2hhbmdlcy5yZW1vdmVBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSwgbXV0YXRpb24ub2xkVmFsdWUpO1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGlmIChuZXdWYWx1ZSA9PT0gb2xkVmFsdWUpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBlbGVtZW50Q2hhbmdlcy5hZGRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSwgZWxlbWVudC5nZXRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSksIG11dGF0aW9uLm9sZFZhbHVlKTtcbiAgICB9XG4gICAgZXh0cmFjdFN0eWxlcyhzdHlsZXMpIHtcbiAgICAgICAgY29uc3Qgc3R5bGVPYmplY3QgPSB7fTtcbiAgICAgICAgc3R5bGVzLnNwbGl0KCc7JykuZm9yRWFjaCgoc3R5bGUpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IHBhcnRzID0gc3R5bGUuc3BsaXQoJzonKTtcbiAgICAgICAgICAgIGlmIChwYXJ0cy5sZW5ndGggPT09IDEpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjb25zdCBwcm9wZXJ0eSA9IHBhcnRzWzBdLnRyaW0oKTtcbiAgICAgICAgICAgIHN0eWxlT2JqZWN0W3Byb3BlcnR5XSA9IHBhcnRzLnNsaWNlKDEpLmpvaW4oJzonKS50cmltKCk7XG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4gc3R5bGVPYmplY3Q7XG4gICAgfVxuICAgIGlzRWxlbWVudEFkZGVkQnlUcmFuc2xhdGlvbihlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50LnRhZ05hbWUgPT09ICdGT05UJyAmJiBlbGVtZW50LmdldEF0dHJpYnV0ZSgnc3R5bGUnKSA9PT0gJ3ZlcnRpY2FsLWFsaWduOiBpbmhlcml0Oyc7XG4gICAgfVxufVxuXG5mdW5jdGlvbiBwYXJzZURpcmVjdGl2ZXMoY29udGVudCkge1xuICAgIGNvbnN0IGRpcmVjdGl2ZXMgPSBbXTtcbiAgICBpZiAoIWNvbnRlbnQpIHtcbiAgICAgICAgcmV0dXJuIGRpcmVjdGl2ZXM7XG4gICAgfVxuICAgIGxldCBjdXJyZW50QWN0aW9uTmFtZSA9ICcnO1xuICAgIGxldCBjdXJyZW50QXJndW1lbnRWYWx1ZSA9ICcnO1xuICAgIGxldCBjdXJyZW50QXJndW1lbnRzID0gW107XG4gICAgbGV0IGN1cnJlbnRNb2RpZmllcnMgPSBbXTtcbiAgICBsZXQgc3RhdGUgPSAnYWN0aW9uJztcbiAgICBjb25zdCBnZXRMYXN0QWN0aW9uTmFtZSA9ICgpID0+IHtcbiAgICAgICAgaWYgKGN1cnJlbnRBY3Rpb25OYW1lKSB7XG4gICAgICAgICAgICByZXR1cm4gY3VycmVudEFjdGlvbk5hbWU7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGRpcmVjdGl2ZXMubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0NvdWxkIG5vdCBmaW5kIGFueSBkaXJlY3RpdmVzJyk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGRpcmVjdGl2ZXNbZGlyZWN0aXZlcy5sZW5ndGggLSAxXS5hY3Rpb247XG4gICAgfTtcbiAgICBjb25zdCBwdXNoSW5zdHJ1Y3Rpb24gPSAoKSA9PiB7XG4gICAgICAgIGRpcmVjdGl2ZXMucHVzaCh7XG4gICAgICAgICAgICBhY3Rpb246IGN1cnJlbnRBY3Rpb25OYW1lLFxuICAgICAgICAgICAgYXJnczogY3VycmVudEFyZ3VtZW50cyxcbiAgICAgICAgICAgIG1vZGlmaWVyczogY3VycmVudE1vZGlmaWVycyxcbiAgICAgICAgICAgIGdldFN0cmluZzogKCkgPT4ge1xuICAgICAgICAgICAgICAgIHJldHVybiBjb250ZW50O1xuICAgICAgICAgICAgfSxcbiAgICAgICAgfSk7XG4gICAgICAgIGN1cnJlbnRBY3Rpb25OYW1lID0gJyc7XG4gICAgICAgIGN1cnJlbnRBcmd1bWVudFZhbHVlID0gJyc7XG4gICAgICAgIGN1cnJlbnRBcmd1bWVudHMgPSBbXTtcbiAgICAgICAgY3VycmVudE1vZGlmaWVycyA9IFtdO1xuICAgICAgICBzdGF0ZSA9ICdhY3Rpb24nO1xuICAgIH07XG4gICAgY29uc3QgcHVzaEFyZ3VtZW50ID0gKCkgPT4ge1xuICAgICAgICBjdXJyZW50QXJndW1lbnRzLnB1c2goY3VycmVudEFyZ3VtZW50VmFsdWUudHJpbSgpKTtcbiAgICAgICAgY3VycmVudEFyZ3VtZW50VmFsdWUgPSAnJztcbiAgICB9O1xuICAgIGNvbnN0IHB1c2hNb2RpZmllciA9ICgpID0+IHtcbiAgICAgICAgaWYgKGN1cnJlbnRBcmd1bWVudHMubGVuZ3RoID4gMSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgbW9kaWZpZXIgXCIke2N1cnJlbnRBY3Rpb25OYW1lfSgpXCIgZG9lcyBub3Qgc3VwcG9ydCBtdWx0aXBsZSBhcmd1bWVudHMuYCk7XG4gICAgICAgIH1cbiAgICAgICAgY3VycmVudE1vZGlmaWVycy5wdXNoKHtcbiAgICAgICAgICAgIG5hbWU6IGN1cnJlbnRBY3Rpb25OYW1lLFxuICAgICAgICAgICAgdmFsdWU6IGN1cnJlbnRBcmd1bWVudHMubGVuZ3RoID4gMCA/IGN1cnJlbnRBcmd1bWVudHNbMF0gOiBudWxsLFxuICAgICAgICB9KTtcbiAgICAgICAgY3VycmVudEFjdGlvbk5hbWUgPSAnJztcbiAgICAgICAgY3VycmVudEFyZ3VtZW50cyA9IFtdO1xuICAgICAgICBzdGF0ZSA9ICdhY3Rpb24nO1xuICAgIH07XG4gICAgZm9yIChsZXQgaSA9IDA7IGkgPCBjb250ZW50Lmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIGNvbnN0IGNoYXIgPSBjb250ZW50W2ldO1xuICAgICAgICBzd2l0Y2ggKHN0YXRlKSB7XG4gICAgICAgICAgICBjYXNlICdhY3Rpb24nOlxuICAgICAgICAgICAgICAgIGlmIChjaGFyID09PSAnKCcpIHtcbiAgICAgICAgICAgICAgICAgICAgc3RhdGUgPSAnYXJndW1lbnRzJztcbiAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChjaGFyID09PSAnICcpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGN1cnJlbnRBY3Rpb25OYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBwdXNoSW5zdHJ1Y3Rpb24oKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKGNoYXIgPT09ICd8Jykge1xuICAgICAgICAgICAgICAgICAgICBwdXNoTW9kaWZpZXIoKTtcbiAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGN1cnJlbnRBY3Rpb25OYW1lICs9IGNoYXI7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdhcmd1bWVudHMnOlxuICAgICAgICAgICAgICAgIGlmIChjaGFyID09PSAnKScpIHtcbiAgICAgICAgICAgICAgICAgICAgcHVzaEFyZ3VtZW50KCk7XG4gICAgICAgICAgICAgICAgICAgIHN0YXRlID0gJ2FmdGVyX2FyZ3VtZW50cyc7XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoY2hhciA9PT0gJywnKSB7XG4gICAgICAgICAgICAgICAgICAgIHB1c2hBcmd1bWVudCgpO1xuICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY3VycmVudEFyZ3VtZW50VmFsdWUgKz0gY2hhcjtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ2FmdGVyX2FyZ3VtZW50cyc6XG4gICAgICAgICAgICAgICAgaWYgKGNoYXIgPT09ICd8Jykge1xuICAgICAgICAgICAgICAgICAgICBwdXNoTW9kaWZpZXIoKTtcbiAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChjaGFyICE9PSAnICcpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBNaXNzaW5nIHNwYWNlIGFmdGVyICR7Z2V0TGFzdEFjdGlvbk5hbWUoKX0oKWApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBwdXNoSW5zdHJ1Y3Rpb24oKTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgfVxuICAgIH1cbiAgICBzd2l0Y2ggKHN0YXRlKSB7XG4gICAgICAgIGNhc2UgJ2FjdGlvbic6XG4gICAgICAgIGNhc2UgJ2FmdGVyX2FyZ3VtZW50cyc6XG4gICAgICAgICAgICBpZiAoY3VycmVudEFjdGlvbk5hbWUpIHtcbiAgICAgICAgICAgICAgICBwdXNoSW5zdHJ1Y3Rpb24oKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBEaWQgeW91IGZvcmdldCB0byBhZGQgYSBjbG9zaW5nIFwiKVwiIGFmdGVyIFwiJHtjdXJyZW50QWN0aW9uTmFtZX1cIj9gKTtcbiAgICB9XG4gICAgcmV0dXJuIGRpcmVjdGl2ZXM7XG59XG5cbmZ1bmN0aW9uIGNvbWJpbmVTcGFjZWRBcnJheShwYXJ0cykge1xuICAgIGNvbnN0IGZpbmFsUGFydHMgPSBbXTtcbiAgICBwYXJ0cy5mb3JFYWNoKChwYXJ0KSA9PiB7XG4gICAgICAgIGZpbmFsUGFydHMucHVzaCguLi50cmltQWxsKHBhcnQpLnNwbGl0KCcgJykpO1xuICAgIH0pO1xuICAgIHJldHVybiBmaW5hbFBhcnRzO1xufVxuZnVuY3Rpb24gdHJpbUFsbChzdHIpIHtcbiAgICByZXR1cm4gc3RyLnJlcGxhY2UoL1tcXHNdKy9nLCAnICcpLnRyaW0oKTtcbn1cbmZ1bmN0aW9uIG5vcm1hbGl6ZU1vZGVsTmFtZShtb2RlbCkge1xuICAgIHJldHVybiAobW9kZWxcbiAgICAgICAgLnJlcGxhY2UoL1xcW10kLywgJycpXG4gICAgICAgIC5zcGxpdCgnWycpXG4gICAgICAgIC5tYXAoKHMpID0+IHMucmVwbGFjZSgnXScsICcnKSlcbiAgICAgICAgLmpvaW4oJy4nKSk7XG59XG5cbmZ1bmN0aW9uIGdldFZhbHVlRnJvbUVsZW1lbnQoZWxlbWVudCwgdmFsdWVTdG9yZSkge1xuICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgSFRNTElucHV0RWxlbWVudCkge1xuICAgICAgICBpZiAoZWxlbWVudC50eXBlID09PSAnY2hlY2tib3gnKSB7XG4gICAgICAgICAgICBjb25zdCBtb2RlbE5hbWVEYXRhID0gZ2V0TW9kZWxEaXJlY3RpdmVGcm9tRWxlbWVudChlbGVtZW50LCBmYWxzZSk7XG4gICAgICAgICAgICBpZiAobW9kZWxOYW1lRGF0YSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIGNvbnN0IG1vZGVsVmFsdWUgPSB2YWx1ZVN0b3JlLmdldChtb2RlbE5hbWVEYXRhLmFjdGlvbik7XG4gICAgICAgICAgICAgICAgaWYgKEFycmF5LmlzQXJyYXkobW9kZWxWYWx1ZSkpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGdldE11bHRpcGxlQ2hlY2tib3hWYWx1ZShlbGVtZW50LCBtb2RlbFZhbHVlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKE9iamVjdChtb2RlbFZhbHVlKSA9PT0gbW9kZWxWYWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZ2V0TXVsdGlwbGVDaGVja2JveFZhbHVlKGVsZW1lbnQsIE9iamVjdC52YWx1ZXMobW9kZWxWYWx1ZSkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChlbGVtZW50Lmhhc0F0dHJpYnV0ZSgndmFsdWUnKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBlbGVtZW50LmNoZWNrZWQgPyBlbGVtZW50LmdldEF0dHJpYnV0ZSgndmFsdWUnKSA6IG51bGw7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudC5jaGVja2VkO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBpbnB1dFZhbHVlKGVsZW1lbnQpO1xuICAgIH1cbiAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxTZWxlY3RFbGVtZW50KSB7XG4gICAgICAgIGlmIChlbGVtZW50Lm11bHRpcGxlKSB7XG4gICAgICAgICAgICByZXR1cm4gQXJyYXkuZnJvbShlbGVtZW50LnNlbGVjdGVkT3B0aW9ucykubWFwKChlbCkgPT4gZWwudmFsdWUpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBlbGVtZW50LnZhbHVlO1xuICAgIH1cbiAgICBpZiAoZWxlbWVudC5kYXRhc2V0LnZhbHVlKSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50LmRhdGFzZXQudmFsdWU7XG4gICAgfVxuICAgIGlmICgndmFsdWUnIGluIGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQudmFsdWU7XG4gICAgfVxuICAgIGlmIChlbGVtZW50Lmhhc0F0dHJpYnV0ZSgndmFsdWUnKSkge1xuICAgICAgICByZXR1cm4gZWxlbWVudC5nZXRBdHRyaWJ1dGUoJ3ZhbHVlJyk7XG4gICAgfVxuICAgIHJldHVybiBudWxsO1xufVxuZnVuY3Rpb24gc2V0VmFsdWVPbkVsZW1lbnQoZWxlbWVudCwgdmFsdWUpIHtcbiAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxJbnB1dEVsZW1lbnQpIHtcbiAgICAgICAgaWYgKGVsZW1lbnQudHlwZSA9PT0gJ2ZpbGUnKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGVsZW1lbnQudHlwZSA9PT0gJ3JhZGlvJykge1xuICAgICAgICAgICAgZWxlbWVudC5jaGVja2VkID0gZWxlbWVudC52YWx1ZSA9PSB2YWx1ZTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBpZiAoZWxlbWVudC50eXBlID09PSAnY2hlY2tib3gnKSB7XG4gICAgICAgICAgICBpZiAoQXJyYXkuaXNBcnJheSh2YWx1ZSkpIHtcbiAgICAgICAgICAgICAgICBlbGVtZW50LmNoZWNrZWQgPSB2YWx1ZS5zb21lKCh2YWwpID0+IHZhbCA9PSBlbGVtZW50LnZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2UgaWYgKGVsZW1lbnQuaGFzQXR0cmlidXRlKCd2YWx1ZScpKSB7XG4gICAgICAgICAgICAgICAgZWxlbWVudC5jaGVja2VkID0gZWxlbWVudC52YWx1ZSA9PSB2YWx1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgIGVsZW1lbnQuY2hlY2tlZCA9IHZhbHVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgfVxuICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgSFRNTFNlbGVjdEVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgYXJyYXlXcmFwcGVkVmFsdWUgPSBbXS5jb25jYXQodmFsdWUpLm1hcCgodmFsdWUpID0+IHtcbiAgICAgICAgICAgIHJldHVybiBgJHt2YWx1ZX1gO1xuICAgICAgICB9KTtcbiAgICAgICAgQXJyYXkuZnJvbShlbGVtZW50Lm9wdGlvbnMpLmZvckVhY2goKG9wdGlvbikgPT4ge1xuICAgICAgICAgICAgb3B0aW9uLnNlbGVjdGVkID0gYXJyYXlXcmFwcGVkVmFsdWUuaW5jbHVkZXMob3B0aW9uLnZhbHVlKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgdmFsdWUgPSB2YWx1ZSA9PT0gdW5kZWZpbmVkID8gJycgOiB2YWx1ZTtcbiAgICBlbGVtZW50LnZhbHVlID0gdmFsdWU7XG59XG5mdW5jdGlvbiBnZXRBbGxNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50cyhlbGVtZW50KSB7XG4gICAgaWYgKCFlbGVtZW50LmRhdGFzZXQubW9kZWwpIHtcbiAgICAgICAgcmV0dXJuIFtdO1xuICAgIH1cbiAgICBjb25zdCBkaXJlY3RpdmVzID0gcGFyc2VEaXJlY3RpdmVzKGVsZW1lbnQuZGF0YXNldC5tb2RlbCk7XG4gICAgZGlyZWN0aXZlcy5mb3JFYWNoKChkaXJlY3RpdmUpID0+IHtcbiAgICAgICAgaWYgKGRpcmVjdGl2ZS5hcmdzLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgVGhlIGRhdGEtbW9kZWw9XCIke2VsZW1lbnQuZGF0YXNldC5tb2RlbH1cIiBmb3JtYXQgaXMgaW52YWxpZDogaXQgZG9lcyBub3Qgc3VwcG9ydCBwYXNzaW5nIGFyZ3VtZW50cyB0byB0aGUgbW9kZWwuYCk7XG4gICAgICAgIH1cbiAgICAgICAgZGlyZWN0aXZlLmFjdGlvbiA9IG5vcm1hbGl6ZU1vZGVsTmFtZShkaXJlY3RpdmUuYWN0aW9uKTtcbiAgICB9KTtcbiAgICByZXR1cm4gZGlyZWN0aXZlcztcbn1cbmZ1bmN0aW9uIGdldE1vZGVsRGlyZWN0aXZlRnJvbUVsZW1lbnQoZWxlbWVudCwgdGhyb3dPbk1pc3NpbmcgPSB0cnVlKSB7XG4gICAgY29uc3QgZGF0YU1vZGVsRGlyZWN0aXZlcyA9IGdldEFsbE1vZGVsRGlyZWN0aXZlRnJvbUVsZW1lbnRzKGVsZW1lbnQpO1xuICAgIGlmIChkYXRhTW9kZWxEaXJlY3RpdmVzLmxlbmd0aCA+IDApIHtcbiAgICAgICAgcmV0dXJuIGRhdGFNb2RlbERpcmVjdGl2ZXNbMF07XG4gICAgfVxuICAgIGlmIChlbGVtZW50LmdldEF0dHJpYnV0ZSgnbmFtZScpKSB7XG4gICAgICAgIGNvbnN0IGZvcm1FbGVtZW50ID0gZWxlbWVudC5jbG9zZXN0KCdmb3JtJyk7XG4gICAgICAgIGlmIChmb3JtRWxlbWVudCAmJiAnbW9kZWwnIGluIGZvcm1FbGVtZW50LmRhdGFzZXQpIHtcbiAgICAgICAgICAgIGNvbnN0IGRpcmVjdGl2ZXMgPSBwYXJzZURpcmVjdGl2ZXMoZm9ybUVsZW1lbnQuZGF0YXNldC5tb2RlbCB8fCAnKicpO1xuICAgICAgICAgICAgY29uc3QgZGlyZWN0aXZlID0gZGlyZWN0aXZlc1swXTtcbiAgICAgICAgICAgIGlmIChkaXJlY3RpdmUuYXJncy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgZGF0YS1tb2RlbD1cIiR7Zm9ybUVsZW1lbnQuZGF0YXNldC5tb2RlbH1cIiBmb3JtYXQgaXMgaW52YWxpZDogaXQgZG9lcyBub3Qgc3VwcG9ydCBwYXNzaW5nIGFyZ3VtZW50cyB0byB0aGUgbW9kZWwuYCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBkaXJlY3RpdmUuYWN0aW9uID0gbm9ybWFsaXplTW9kZWxOYW1lKGVsZW1lbnQuZ2V0QXR0cmlidXRlKCduYW1lJykpO1xuICAgICAgICAgICAgcmV0dXJuIGRpcmVjdGl2ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBpZiAoIXRocm93T25NaXNzaW5nKSB7XG4gICAgICAgIHJldHVybiBudWxsO1xuICAgIH1cbiAgICB0aHJvdyBuZXcgRXJyb3IoYENhbm5vdCBkZXRlcm1pbmUgdGhlIG1vZGVsIG5hbWUgZm9yIFwiJHtnZXRFbGVtZW50QXNUYWdUZXh0KGVsZW1lbnQpfVwiOiB0aGUgZWxlbWVudCBtdXN0IGVpdGhlciBoYXZlIGEgXCJkYXRhLW1vZGVsXCIgKG9yIFwibmFtZVwiIGF0dHJpYnV0ZSBsaXZpbmcgaW5zaWRlIGEgPGZvcm0gZGF0YS1tb2RlbD1cIipcIj4pLmApO1xufVxuZnVuY3Rpb24gZWxlbWVudEJlbG9uZ3NUb1RoaXNDb21wb25lbnQoZWxlbWVudCwgY29tcG9uZW50KSB7XG4gICAgaWYgKGNvbXBvbmVudC5lbGVtZW50ID09PSBlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgICBpZiAoIWNvbXBvbmVudC5lbGVtZW50LmNvbnRhaW5zKGVsZW1lbnQpKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgY29uc3QgY2xvc2VzdExpdmVDb21wb25lbnQgPSBlbGVtZW50LmNsb3Nlc3QoJ1tkYXRhLWNvbnRyb2xsZXJ+PVwibGl2ZVwiXScpO1xuICAgIHJldHVybiBjbG9zZXN0TGl2ZUNvbXBvbmVudCA9PT0gY29tcG9uZW50LmVsZW1lbnQ7XG59XG5mdW5jdGlvbiBjbG9uZUhUTUxFbGVtZW50KGVsZW1lbnQpIHtcbiAgICBjb25zdCBuZXdFbGVtZW50ID0gZWxlbWVudC5jbG9uZU5vZGUodHJ1ZSk7XG4gICAgaWYgKCEobmV3RWxlbWVudCBpbnN0YW5jZW9mIEhUTUxFbGVtZW50KSkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0NvdWxkIG5vdCBjbG9uZSBlbGVtZW50Jyk7XG4gICAgfVxuICAgIHJldHVybiBuZXdFbGVtZW50O1xufVxuZnVuY3Rpb24gaHRtbFRvRWxlbWVudChodG1sKSB7XG4gICAgY29uc3QgdGVtcGxhdGUgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCd0ZW1wbGF0ZScpO1xuICAgIGh0bWwgPSBodG1sLnRyaW0oKTtcbiAgICB0ZW1wbGF0ZS5pbm5lckhUTUwgPSBodG1sO1xuICAgIGlmICh0ZW1wbGF0ZS5jb250ZW50LmNoaWxkRWxlbWVudENvdW50ID4gMSkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoYENvbXBvbmVudCBIVE1MIGNvbnRhaW5zICR7dGVtcGxhdGUuY29udGVudC5jaGlsZEVsZW1lbnRDb3VudH0gZWxlbWVudHMsIGJ1dCBvbmx5IDEgcm9vdCBlbGVtZW50IGlzIGFsbG93ZWQuYCk7XG4gICAgfVxuICAgIGNvbnN0IGNoaWxkID0gdGVtcGxhdGUuY29udGVudC5maXJzdEVsZW1lbnRDaGlsZDtcbiAgICBpZiAoIWNoaWxkKSB7XG4gICAgICAgIHRocm93IG5ldyBFcnJvcignQ2hpbGQgbm90IGZvdW5kJyk7XG4gICAgfVxuICAgIGlmICghKGNoaWxkIGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpKSB7XG4gICAgICAgIHRocm93IG5ldyBFcnJvcihgQ3JlYXRlZCBlbGVtZW50IGlzIG5vdCBhbiBIVE1MRWxlbWVudDogJHtodG1sLnRyaW0oKX1gKTtcbiAgICB9XG4gICAgcmV0dXJuIGNoaWxkO1xufVxuY29uc3QgZ2V0TXVsdGlwbGVDaGVja2JveFZhbHVlID0gKGVsZW1lbnQsIGN1cnJlbnRWYWx1ZXMpID0+IHtcbiAgICBjb25zdCBmaW5hbFZhbHVlcyA9IFsuLi5jdXJyZW50VmFsdWVzXTtcbiAgICBjb25zdCB2YWx1ZSA9IGlucHV0VmFsdWUoZWxlbWVudCk7XG4gICAgY29uc3QgaW5kZXggPSBjdXJyZW50VmFsdWVzLmluZGV4T2YodmFsdWUpO1xuICAgIGlmIChlbGVtZW50LmNoZWNrZWQpIHtcbiAgICAgICAgaWYgKGluZGV4ID09PSAtMSkge1xuICAgICAgICAgICAgZmluYWxWYWx1ZXMucHVzaCh2YWx1ZSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZpbmFsVmFsdWVzO1xuICAgIH1cbiAgICBpZiAoaW5kZXggPiAtMSkge1xuICAgICAgICBmaW5hbFZhbHVlcy5zcGxpY2UoaW5kZXgsIDEpO1xuICAgIH1cbiAgICByZXR1cm4gZmluYWxWYWx1ZXM7XG59O1xuY29uc3QgaW5wdXRWYWx1ZSA9IChlbGVtZW50KSA9PiBlbGVtZW50LmRhdGFzZXQudmFsdWUgPyBlbGVtZW50LmRhdGFzZXQudmFsdWUgOiBlbGVtZW50LnZhbHVlO1xuXG4vLyBiYXNlIElJRkUgdG8gZGVmaW5lIGlkaW9tb3JwaFxudmFyIElkaW9tb3JwaCA9IChmdW5jdGlvbiAoKSB7XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBBTkQgTk9XIElUIEJFR0lOUy4uLlxuICAgICAgICAvLz09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG4gICAgICAgIGxldCBFTVBUWV9TRVQgPSBuZXcgU2V0KCk7XG5cbiAgICAgICAgLy8gZGVmYXVsdCBjb25maWd1cmF0aW9uIHZhbHVlcywgdXBkYXRhYmxlIGJ5IHVzZXJzIG5vd1xuICAgICAgICBsZXQgZGVmYXVsdHMgPSB7XG4gICAgICAgICAgICBtb3JwaFN0eWxlOiBcIm91dGVySFRNTFwiLFxuICAgICAgICAgICAgY2FsbGJhY2tzIDoge1xuICAgICAgICAgICAgICAgIGJlZm9yZU5vZGVBZGRlZDogbm9PcCxcbiAgICAgICAgICAgICAgICBhZnRlck5vZGVBZGRlZDogbm9PcCxcbiAgICAgICAgICAgICAgICBiZWZvcmVOb2RlTW9ycGhlZDogbm9PcCxcbiAgICAgICAgICAgICAgICBhZnRlck5vZGVNb3JwaGVkOiBub09wLFxuICAgICAgICAgICAgICAgIGJlZm9yZU5vZGVSZW1vdmVkOiBub09wLFxuICAgICAgICAgICAgICAgIGFmdGVyTm9kZVJlbW92ZWQ6IG5vT3AsXG4gICAgICAgICAgICAgICAgYmVmb3JlQXR0cmlidXRlVXBkYXRlZDogbm9PcCxcblxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGhlYWQ6IHtcbiAgICAgICAgICAgICAgICBzdHlsZTogJ21lcmdlJyxcbiAgICAgICAgICAgICAgICBzaG91bGRQcmVzZXJ2ZTogZnVuY3Rpb24gKGVsdCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZWx0LmdldEF0dHJpYnV0ZShcImltLXByZXNlcnZlXCIpID09PSBcInRydWVcIjtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHNob3VsZFJlQXBwZW5kOiBmdW5jdGlvbiAoZWx0KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBlbHQuZ2V0QXR0cmlidXRlKFwiaW0tcmUtYXBwZW5kXCIpID09PSBcInRydWVcIjtcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHNob3VsZFJlbW92ZTogbm9PcCxcbiAgICAgICAgICAgICAgICBhZnRlckhlYWRNb3JwaGVkOiBub09wLFxuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gQ29yZSBNb3JwaGluZyBBbGdvcml0aG0gLSBtb3JwaCwgbW9ycGhOb3JtYWxpemVkQ29udGVudCwgbW9ycGhPbGROb2RlVG8sIG1vcnBoQ2hpbGRyZW5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICBmdW5jdGlvbiBtb3JwaChvbGROb2RlLCBuZXdDb250ZW50LCBjb25maWcgPSB7fSkge1xuXG4gICAgICAgICAgICBpZiAob2xkTm9kZSBpbnN0YW5jZW9mIERvY3VtZW50KSB7XG4gICAgICAgICAgICAgICAgb2xkTm9kZSA9IG9sZE5vZGUuZG9jdW1lbnRFbGVtZW50O1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBpZiAodHlwZW9mIG5ld0NvbnRlbnQgPT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgbmV3Q29udGVudCA9IHBhcnNlQ29udGVudChuZXdDb250ZW50KTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgbGV0IG5vcm1hbGl6ZWRDb250ZW50ID0gbm9ybWFsaXplQ29udGVudChuZXdDb250ZW50KTtcblxuICAgICAgICAgICAgbGV0IGN0eCA9IGNyZWF0ZU1vcnBoQ29udGV4dChvbGROb2RlLCBub3JtYWxpemVkQ29udGVudCwgY29uZmlnKTtcblxuICAgICAgICAgICAgcmV0dXJuIG1vcnBoTm9ybWFsaXplZENvbnRlbnQob2xkTm9kZSwgbm9ybWFsaXplZENvbnRlbnQsIGN0eCk7XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiBtb3JwaE5vcm1hbGl6ZWRDb250ZW50KG9sZE5vZGUsIG5vcm1hbGl6ZWROZXdDb250ZW50LCBjdHgpIHtcbiAgICAgICAgICAgIGlmIChjdHguaGVhZC5ibG9jaykge1xuICAgICAgICAgICAgICAgIGxldCBvbGRIZWFkID0gb2xkTm9kZS5xdWVyeVNlbGVjdG9yKCdoZWFkJyk7XG4gICAgICAgICAgICAgICAgbGV0IG5ld0hlYWQgPSBub3JtYWxpemVkTmV3Q29udGVudC5xdWVyeVNlbGVjdG9yKCdoZWFkJyk7XG4gICAgICAgICAgICAgICAgaWYgKG9sZEhlYWQgJiYgbmV3SGVhZCkge1xuICAgICAgICAgICAgICAgICAgICBsZXQgcHJvbWlzZXMgPSBoYW5kbGVIZWFkRWxlbWVudChuZXdIZWFkLCBvbGRIZWFkLCBjdHgpO1xuICAgICAgICAgICAgICAgICAgICAvLyB3aGVuIGhlYWQgcHJvbWlzZXMgcmVzb2x2ZSwgY2FsbCBtb3JwaCBhZ2FpbiwgaWdub3JpbmcgdGhlIGhlYWQgdGFnXG4gICAgICAgICAgICAgICAgICAgIFByb21pc2UuYWxsKHByb21pc2VzKS50aGVuKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIG1vcnBoTm9ybWFsaXplZENvbnRlbnQob2xkTm9kZSwgbm9ybWFsaXplZE5ld0NvbnRlbnQsIE9iamVjdC5hc3NpZ24oY3R4LCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaGVhZDoge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBibG9jazogZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlnbm9yZTogdHJ1ZVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0pKTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmIChjdHgubW9ycGhTdHlsZSA9PT0gXCJpbm5lckhUTUxcIikge1xuXG4gICAgICAgICAgICAgICAgLy8gaW5uZXJIVE1MLCBzbyB3ZSBhcmUgb25seSB1cGRhdGluZyB0aGUgY2hpbGRyZW5cbiAgICAgICAgICAgICAgICBtb3JwaENoaWxkcmVuKG5vcm1hbGl6ZWROZXdDb250ZW50LCBvbGROb2RlLCBjdHgpO1xuICAgICAgICAgICAgICAgIHJldHVybiBvbGROb2RlLmNoaWxkcmVuO1xuXG4gICAgICAgICAgICB9IGVsc2UgaWYgKGN0eC5tb3JwaFN0eWxlID09PSBcIm91dGVySFRNTFwiIHx8IGN0eC5tb3JwaFN0eWxlID09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAvLyBvdGhlcndpc2UgZmluZCB0aGUgYmVzdCBlbGVtZW50IG1hdGNoIGluIHRoZSBuZXcgY29udGVudCwgbW9ycGggdGhhdCwgYW5kIG1lcmdlIGl0cyBzaWJsaW5nc1xuICAgICAgICAgICAgICAgIC8vIGludG8gZWl0aGVyIHNpZGUgb2YgdGhlIGJlc3QgbWF0Y2hcbiAgICAgICAgICAgICAgICBsZXQgYmVzdE1hdGNoID0gZmluZEJlc3ROb2RlTWF0Y2gobm9ybWFsaXplZE5ld0NvbnRlbnQsIG9sZE5vZGUsIGN0eCk7XG5cbiAgICAgICAgICAgICAgICAvLyBzdGFzaCB0aGUgc2libGluZ3MgdGhhdCB3aWxsIG5lZWQgdG8gYmUgaW5zZXJ0ZWQgb24gZWl0aGVyIHNpZGUgb2YgdGhlIGJlc3QgbWF0Y2hcbiAgICAgICAgICAgICAgICBsZXQgcHJldmlvdXNTaWJsaW5nID0gYmVzdE1hdGNoPy5wcmV2aW91c1NpYmxpbmc7XG4gICAgICAgICAgICAgICAgbGV0IG5leHRTaWJsaW5nID0gYmVzdE1hdGNoPy5uZXh0U2libGluZztcblxuICAgICAgICAgICAgICAgIC8vIG1vcnBoIGl0XG4gICAgICAgICAgICAgICAgbGV0IG1vcnBoZWROb2RlID0gbW9ycGhPbGROb2RlVG8ob2xkTm9kZSwgYmVzdE1hdGNoLCBjdHgpO1xuXG4gICAgICAgICAgICAgICAgaWYgKGJlc3RNYXRjaCkge1xuICAgICAgICAgICAgICAgICAgICAvLyBpZiB0aGVyZSB3YXMgYSBiZXN0IG1hdGNoLCBtZXJnZSB0aGUgc2libGluZ3MgaW4gdG9vIGFuZCByZXR1cm4gdGhlXG4gICAgICAgICAgICAgICAgICAgIC8vIHdob2xlIGJ1bmNoXG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBpbnNlcnRTaWJsaW5ncyhwcmV2aW91c1NpYmxpbmcsIG1vcnBoZWROb2RlLCBuZXh0U2libGluZyk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgLy8gb3RoZXJ3aXNlIG5vdGhpbmcgd2FzIGFkZGVkIHRvIHRoZSBET01cbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIFtdXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBcIkRvIG5vdCB1bmRlcnN0YW5kIGhvdyB0byBtb3JwaCBzdHlsZSBcIiArIGN0eC5tb3JwaFN0eWxlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cblxuICAgICAgICAvKipcbiAgICAgICAgICogQHBhcmFtIHBvc3NpYmxlQWN0aXZlRWxlbWVudFxuICAgICAgICAgKiBAcGFyYW0gY3R4XG4gICAgICAgICAqIEByZXR1cm5zIHtib29sZWFufVxuICAgICAgICAgKi9cbiAgICAgICAgZnVuY3Rpb24gaWdub3JlVmFsdWVPZkFjdGl2ZUVsZW1lbnQocG9zc2libGVBY3RpdmVFbGVtZW50LCBjdHgpIHtcbiAgICAgICAgICAgIHJldHVybiBjdHguaWdub3JlQWN0aXZlVmFsdWUgJiYgcG9zc2libGVBY3RpdmVFbGVtZW50ID09PSBkb2N1bWVudC5hY3RpdmVFbGVtZW50O1xuICAgICAgICB9XG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEBwYXJhbSBvbGROb2RlIHJvb3Qgbm9kZSB0byBtZXJnZSBjb250ZW50IGludG9cbiAgICAgICAgICogQHBhcmFtIG5ld0NvbnRlbnQgbmV3IGNvbnRlbnQgdG8gbWVyZ2VcbiAgICAgICAgICogQHBhcmFtIGN0eCB0aGUgbWVyZ2UgY29udGV4dFxuICAgICAgICAgKiBAcmV0dXJucyB7RWxlbWVudH0gdGhlIGVsZW1lbnQgdGhhdCBlbmRlZCB1cCBpbiB0aGUgRE9NXG4gICAgICAgICAqL1xuICAgICAgICBmdW5jdGlvbiBtb3JwaE9sZE5vZGVUbyhvbGROb2RlLCBuZXdDb250ZW50LCBjdHgpIHtcbiAgICAgICAgICAgIGlmIChjdHguaWdub3JlQWN0aXZlICYmIG9sZE5vZGUgPT09IGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQpIDsgZWxzZSBpZiAobmV3Q29udGVudCA9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgaWYgKGN0eC5jYWxsYmFja3MuYmVmb3JlTm9kZVJlbW92ZWQob2xkTm9kZSkgPT09IGZhbHNlKSByZXR1cm4gb2xkTm9kZTtcblxuICAgICAgICAgICAgICAgIG9sZE5vZGUucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgY3R4LmNhbGxiYWNrcy5hZnRlck5vZGVSZW1vdmVkKG9sZE5vZGUpO1xuICAgICAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICAgICAgfSBlbHNlIGlmICghaXNTb2Z0TWF0Y2gob2xkTm9kZSwgbmV3Q29udGVudCkpIHtcbiAgICAgICAgICAgICAgICBpZiAoY3R4LmNhbGxiYWNrcy5iZWZvcmVOb2RlUmVtb3ZlZChvbGROb2RlKSA9PT0gZmFsc2UpIHJldHVybiBvbGROb2RlO1xuICAgICAgICAgICAgICAgIGlmIChjdHguY2FsbGJhY2tzLmJlZm9yZU5vZGVBZGRlZChuZXdDb250ZW50KSA9PT0gZmFsc2UpIHJldHVybiBvbGROb2RlO1xuXG4gICAgICAgICAgICAgICAgb2xkTm9kZS5wYXJlbnRFbGVtZW50LnJlcGxhY2VDaGlsZChuZXdDb250ZW50LCBvbGROb2RlKTtcbiAgICAgICAgICAgICAgICBjdHguY2FsbGJhY2tzLmFmdGVyTm9kZUFkZGVkKG5ld0NvbnRlbnQpO1xuICAgICAgICAgICAgICAgIGN0eC5jYWxsYmFja3MuYWZ0ZXJOb2RlUmVtb3ZlZChvbGROb2RlKTtcbiAgICAgICAgICAgICAgICByZXR1cm4gbmV3Q29udGVudDtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgaWYgKGN0eC5jYWxsYmFja3MuYmVmb3JlTm9kZU1vcnBoZWQob2xkTm9kZSwgbmV3Q29udGVudCkgPT09IGZhbHNlKSByZXR1cm4gb2xkTm9kZTtcblxuICAgICAgICAgICAgICAgIGlmIChvbGROb2RlIGluc3RhbmNlb2YgSFRNTEhlYWRFbGVtZW50ICYmIGN0eC5oZWFkLmlnbm9yZSkgOyBlbHNlIGlmIChvbGROb2RlIGluc3RhbmNlb2YgSFRNTEhlYWRFbGVtZW50ICYmIGN0eC5oZWFkLnN0eWxlICE9PSBcIm1vcnBoXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgaGFuZGxlSGVhZEVsZW1lbnQobmV3Q29udGVudCwgb2xkTm9kZSwgY3R4KTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBzeW5jTm9kZUZyb20obmV3Q29udGVudCwgb2xkTm9kZSwgY3R4KTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCFpZ25vcmVWYWx1ZU9mQWN0aXZlRWxlbWVudChvbGROb2RlLCBjdHgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBtb3JwaENoaWxkcmVuKG5ld0NvbnRlbnQsIG9sZE5vZGUsIGN0eCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY3R4LmNhbGxiYWNrcy5hZnRlck5vZGVNb3JwaGVkKG9sZE5vZGUsIG5ld0NvbnRlbnQpO1xuICAgICAgICAgICAgICAgIHJldHVybiBvbGROb2RlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIFRoaXMgaXMgdGhlIGNvcmUgYWxnb3JpdGhtIGZvciBtYXRjaGluZyB1cCBjaGlsZHJlbi4gIFRoZSBpZGVhIGlzIHRvIHVzZSBpZCBzZXRzIHRvIHRyeSB0byBtYXRjaCB1cFxuICAgICAgICAgKiBub2RlcyBhcyBmYWl0aGZ1bGx5IGFzIHBvc3NpYmxlLiAgV2UgZ3JlZWRpbHkgbWF0Y2gsIHdoaWNoIGFsbG93cyB1cyB0byBrZWVwIHRoZSBhbGdvcml0aG0gZmFzdCwgYnV0XG4gICAgICAgICAqIGJ5IHVzaW5nIGlkIHNldHMsIHdlIGFyZSBhYmxlIHRvIGJldHRlciBtYXRjaCB1cCB3aXRoIGNvbnRlbnQgZGVlcGVyIGluIHRoZSBET00uXG4gICAgICAgICAqXG4gICAgICAgICAqIEJhc2ljIGFsZ29yaXRobSBpcywgZm9yIGVhY2ggbm9kZSBpbiB0aGUgbmV3IGNvbnRlbnQ6XG4gICAgICAgICAqXG4gICAgICAgICAqIC0gaWYgd2UgaGF2ZSByZWFjaGVkIHRoZSBlbmQgb2YgdGhlIG9sZCBwYXJlbnQsIGFwcGVuZCB0aGUgbmV3IGNvbnRlbnRcbiAgICAgICAgICogLSBpZiB0aGUgbmV3IGNvbnRlbnQgaGFzIGFuIGlkIHNldCBtYXRjaCB3aXRoIHRoZSBjdXJyZW50IGluc2VydGlvbiBwb2ludCwgbW9ycGhcbiAgICAgICAgICogLSBzZWFyY2ggZm9yIGFuIGlkIHNldCBtYXRjaFxuICAgICAgICAgKiAtIGlmIGlkIHNldCBtYXRjaCBmb3VuZCwgbW9ycGhcbiAgICAgICAgICogLSBvdGhlcndpc2Ugc2VhcmNoIGZvciBhIFwic29mdFwiIG1hdGNoXG4gICAgICAgICAqIC0gaWYgYSBzb2Z0IG1hdGNoIGlzIGZvdW5kLCBtb3JwaFxuICAgICAgICAgKiAtIG90aGVyd2lzZSwgcHJlcGVuZCB0aGUgbmV3IG5vZGUgYmVmb3JlIHRoZSBjdXJyZW50IGluc2VydGlvbiBwb2ludFxuICAgICAgICAgKlxuICAgICAgICAgKiBUaGUgdHdvIHNlYXJjaCBhbGdvcml0aG1zIHRlcm1pbmF0ZSBpZiBjb21wZXRpbmcgbm9kZSBtYXRjaGVzIGFwcGVhciB0byBvdXR3ZWlnaCB3aGF0IGNhbiBiZSBhY2hpZXZlZFxuICAgICAgICAgKiB3aXRoIHRoZSBjdXJyZW50IG5vZGUuICBTZWUgZmluZElkU2V0TWF0Y2goKSBhbmQgZmluZFNvZnRNYXRjaCgpIGZvciBkZXRhaWxzLlxuICAgICAgICAgKlxuICAgICAgICAgKiBAcGFyYW0ge0VsZW1lbnR9IG5ld1BhcmVudCB0aGUgcGFyZW50IGVsZW1lbnQgb2YgdGhlIG5ldyBjb250ZW50XG4gICAgICAgICAqIEBwYXJhbSB7RWxlbWVudCB9IG9sZFBhcmVudCB0aGUgb2xkIGNvbnRlbnQgdGhhdCB3ZSBhcmUgbWVyZ2luZyB0aGUgbmV3IGNvbnRlbnQgaW50b1xuICAgICAgICAgKiBAcGFyYW0gY3R4IHRoZSBtZXJnZSBjb250ZXh0XG4gICAgICAgICAqL1xuICAgICAgICBmdW5jdGlvbiBtb3JwaENoaWxkcmVuKG5ld1BhcmVudCwgb2xkUGFyZW50LCBjdHgpIHtcblxuICAgICAgICAgICAgbGV0IG5leHROZXdDaGlsZCA9IG5ld1BhcmVudC5maXJzdENoaWxkO1xuICAgICAgICAgICAgbGV0IGluc2VydGlvblBvaW50ID0gb2xkUGFyZW50LmZpcnN0Q2hpbGQ7XG4gICAgICAgICAgICBsZXQgbmV3Q2hpbGQ7XG5cbiAgICAgICAgICAgIC8vIHJ1biB0aHJvdWdoIGFsbCB0aGUgbmV3IGNvbnRlbnRcbiAgICAgICAgICAgIHdoaWxlIChuZXh0TmV3Q2hpbGQpIHtcblxuICAgICAgICAgICAgICAgIG5ld0NoaWxkID0gbmV4dE5ld0NoaWxkO1xuICAgICAgICAgICAgICAgIG5leHROZXdDaGlsZCA9IG5ld0NoaWxkLm5leHRTaWJsaW5nO1xuXG4gICAgICAgICAgICAgICAgLy8gaWYgd2UgYXJlIGF0IHRoZSBlbmQgb2YgdGhlIGV4aXRpbmcgcGFyZW50J3MgY2hpbGRyZW4sIGp1c3QgYXBwZW5kXG4gICAgICAgICAgICAgICAgaWYgKGluc2VydGlvblBvaW50ID09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGN0eC5jYWxsYmFja3MuYmVmb3JlTm9kZUFkZGVkKG5ld0NoaWxkKSA9PT0gZmFsc2UpIHJldHVybjtcblxuICAgICAgICAgICAgICAgICAgICBvbGRQYXJlbnQuYXBwZW5kQ2hpbGQobmV3Q2hpbGQpO1xuICAgICAgICAgICAgICAgICAgICBjdHguY2FsbGJhY2tzLmFmdGVyTm9kZUFkZGVkKG5ld0NoaWxkKTtcbiAgICAgICAgICAgICAgICAgICAgcmVtb3ZlSWRzRnJvbUNvbnNpZGVyYXRpb24oY3R4LCBuZXdDaGlsZCk7XG4gICAgICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vIGlmIHRoZSBjdXJyZW50IG5vZGUgaGFzIGFuIGlkIHNldCBtYXRjaCB0aGVuIG1vcnBoXG4gICAgICAgICAgICAgICAgaWYgKGlzSWRTZXRNYXRjaChuZXdDaGlsZCwgaW5zZXJ0aW9uUG9pbnQsIGN0eCkpIHtcbiAgICAgICAgICAgICAgICAgICAgbW9ycGhPbGROb2RlVG8oaW5zZXJ0aW9uUG9pbnQsIG5ld0NoaWxkLCBjdHgpO1xuICAgICAgICAgICAgICAgICAgICBpbnNlcnRpb25Qb2ludCA9IGluc2VydGlvblBvaW50Lm5leHRTaWJsaW5nO1xuICAgICAgICAgICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIG5ld0NoaWxkKTtcbiAgICAgICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gb3RoZXJ3aXNlIHNlYXJjaCBmb3J3YXJkIGluIHRoZSBleGlzdGluZyBvbGQgY2hpbGRyZW4gZm9yIGFuIGlkIHNldCBtYXRjaFxuICAgICAgICAgICAgICAgIGxldCBpZFNldE1hdGNoID0gZmluZElkU2V0TWF0Y2gobmV3UGFyZW50LCBvbGRQYXJlbnQsIG5ld0NoaWxkLCBpbnNlcnRpb25Qb2ludCwgY3R4KTtcblxuICAgICAgICAgICAgICAgIC8vIGlmIHdlIGZvdW5kIGEgcG90ZW50aWFsIG1hdGNoLCByZW1vdmUgdGhlIG5vZGVzIHVudGlsIHRoYXQgcG9pbnQgYW5kIG1vcnBoXG4gICAgICAgICAgICAgICAgaWYgKGlkU2V0TWF0Y2gpIHtcbiAgICAgICAgICAgICAgICAgICAgaW5zZXJ0aW9uUG9pbnQgPSByZW1vdmVOb2Rlc0JldHdlZW4oaW5zZXJ0aW9uUG9pbnQsIGlkU2V0TWF0Y2gsIGN0eCk7XG4gICAgICAgICAgICAgICAgICAgIG1vcnBoT2xkTm9kZVRvKGlkU2V0TWF0Y2gsIG5ld0NoaWxkLCBjdHgpO1xuICAgICAgICAgICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIG5ld0NoaWxkKTtcbiAgICAgICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gbm8gaWQgc2V0IG1hdGNoIGZvdW5kLCBzbyBzY2FuIGZvcndhcmQgZm9yIGEgc29mdCBtYXRjaCBmb3IgdGhlIGN1cnJlbnQgbm9kZVxuICAgICAgICAgICAgICAgIGxldCBzb2Z0TWF0Y2ggPSBmaW5kU29mdE1hdGNoKG5ld1BhcmVudCwgb2xkUGFyZW50LCBuZXdDaGlsZCwgaW5zZXJ0aW9uUG9pbnQsIGN0eCk7XG5cbiAgICAgICAgICAgICAgICAvLyBpZiB3ZSBmb3VuZCBhIHNvZnQgbWF0Y2ggZm9yIHRoZSBjdXJyZW50IG5vZGUsIG1vcnBoXG4gICAgICAgICAgICAgICAgaWYgKHNvZnRNYXRjaCkge1xuICAgICAgICAgICAgICAgICAgICBpbnNlcnRpb25Qb2ludCA9IHJlbW92ZU5vZGVzQmV0d2VlbihpbnNlcnRpb25Qb2ludCwgc29mdE1hdGNoLCBjdHgpO1xuICAgICAgICAgICAgICAgICAgICBtb3JwaE9sZE5vZGVUbyhzb2Z0TWF0Y2gsIG5ld0NoaWxkLCBjdHgpO1xuICAgICAgICAgICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIG5ld0NoaWxkKTtcbiAgICAgICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gYWJhbmRvbiBhbGwgaG9wZSBvZiBtb3JwaGluZywganVzdCBpbnNlcnQgdGhlIG5ldyBjaGlsZCBiZWZvcmUgdGhlIGluc2VydGlvbiBwb2ludFxuICAgICAgICAgICAgICAgIC8vIGFuZCBtb3ZlIG9uXG4gICAgICAgICAgICAgICAgaWYgKGN0eC5jYWxsYmFja3MuYmVmb3JlTm9kZUFkZGVkKG5ld0NoaWxkKSA9PT0gZmFsc2UpIHJldHVybjtcblxuICAgICAgICAgICAgICAgIG9sZFBhcmVudC5pbnNlcnRCZWZvcmUobmV3Q2hpbGQsIGluc2VydGlvblBvaW50KTtcbiAgICAgICAgICAgICAgICBjdHguY2FsbGJhY2tzLmFmdGVyTm9kZUFkZGVkKG5ld0NoaWxkKTtcbiAgICAgICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIG5ld0NoaWxkKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gcmVtb3ZlIGFueSByZW1haW5pbmcgb2xkIG5vZGVzIHRoYXQgZGlkbid0IG1hdGNoIHVwIHdpdGggbmV3IGNvbnRlbnRcbiAgICAgICAgICAgIHdoaWxlIChpbnNlcnRpb25Qb2ludCAhPT0gbnVsbCkge1xuXG4gICAgICAgICAgICAgICAgbGV0IHRlbXBOb2RlID0gaW5zZXJ0aW9uUG9pbnQ7XG4gICAgICAgICAgICAgICAgaW5zZXJ0aW9uUG9pbnQgPSBpbnNlcnRpb25Qb2ludC5uZXh0U2libGluZztcbiAgICAgICAgICAgICAgICByZW1vdmVOb2RlKHRlbXBOb2RlLCBjdHgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBBdHRyaWJ1dGUgU3luY2luZyBDb2RlXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cblxuICAgICAgICAvKipcbiAgICAgICAgICogQHBhcmFtIGF0dHIge1N0cmluZ30gdGhlIGF0dHJpYnV0ZSB0byBiZSBtdXRhdGVkXG4gICAgICAgICAqIEBwYXJhbSB0byB7RWxlbWVudH0gdGhlIGVsZW1lbnQgdGhhdCBpcyBnb2luZyB0byBiZSB1cGRhdGVkXG4gICAgICAgICAqIEBwYXJhbSB1cGRhdGVUeXBlIHsoXCJ1cGRhdGVcInxcInJlbW92ZVwiKX1cbiAgICAgICAgICogQHBhcmFtIGN0eCB0aGUgbWVyZ2UgY29udGV4dFxuICAgICAgICAgKiBAcmV0dXJucyB7Ym9vbGVhbn0gdHJ1ZSBpZiB0aGUgYXR0cmlidXRlIHNob3VsZCBiZSBpZ25vcmVkLCBmYWxzZSBvdGhlcndpc2VcbiAgICAgICAgICovXG4gICAgICAgIGZ1bmN0aW9uIGlnbm9yZUF0dHJpYnV0ZShhdHRyLCB0bywgdXBkYXRlVHlwZSwgY3R4KSB7XG4gICAgICAgICAgICBpZihhdHRyID09PSAndmFsdWUnICYmIGN0eC5pZ25vcmVBY3RpdmVWYWx1ZSAmJiB0byA9PT0gZG9jdW1lbnQuYWN0aXZlRWxlbWVudCl7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gY3R4LmNhbGxiYWNrcy5iZWZvcmVBdHRyaWJ1dGVVcGRhdGVkKGF0dHIsIHRvLCB1cGRhdGVUeXBlKSA9PT0gZmFsc2U7XG4gICAgICAgIH1cblxuICAgICAgICAvKipcbiAgICAgICAgICogc3luY3MgYSBnaXZlbiBub2RlIHdpdGggYW5vdGhlciBub2RlLCBjb3B5aW5nIG92ZXIgYWxsIGF0dHJpYnV0ZXMgYW5kXG4gICAgICAgICAqIGlubmVyIGVsZW1lbnQgc3RhdGUgZnJvbSB0aGUgJ2Zyb20nIG5vZGUgdG8gdGhlICd0bycgbm9kZVxuICAgICAgICAgKlxuICAgICAgICAgKiBAcGFyYW0ge0VsZW1lbnR9IGZyb20gdGhlIGVsZW1lbnQgdG8gY29weSBhdHRyaWJ1dGVzICYgc3RhdGUgZnJvbVxuICAgICAgICAgKiBAcGFyYW0ge0VsZW1lbnR9IHRvIHRoZSBlbGVtZW50IHRvIGNvcHkgYXR0cmlidXRlcyAmIHN0YXRlIHRvXG4gICAgICAgICAqIEBwYXJhbSBjdHggdGhlIG1lcmdlIGNvbnRleHRcbiAgICAgICAgICovXG4gICAgICAgIGZ1bmN0aW9uIHN5bmNOb2RlRnJvbShmcm9tLCB0bywgY3R4KSB7XG4gICAgICAgICAgICBsZXQgdHlwZSA9IGZyb20ubm9kZVR5cGU7XG5cbiAgICAgICAgICAgIC8vIGlmIGlzIGFuIGVsZW1lbnQgdHlwZSwgc3luYyB0aGUgYXR0cmlidXRlcyBmcm9tIHRoZVxuICAgICAgICAgICAgLy8gbmV3IG5vZGUgaW50byB0aGUgbmV3IG5vZGVcbiAgICAgICAgICAgIGlmICh0eXBlID09PSAxIC8qIGVsZW1lbnQgdHlwZSAqLykge1xuICAgICAgICAgICAgICAgIGNvbnN0IGZyb21BdHRyaWJ1dGVzID0gZnJvbS5hdHRyaWJ1dGVzO1xuICAgICAgICAgICAgICAgIGNvbnN0IHRvQXR0cmlidXRlcyA9IHRvLmF0dHJpYnV0ZXM7XG4gICAgICAgICAgICAgICAgZm9yIChjb25zdCBmcm9tQXR0cmlidXRlIG9mIGZyb21BdHRyaWJ1dGVzKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChpZ25vcmVBdHRyaWJ1dGUoZnJvbUF0dHJpYnV0ZS5uYW1lLCB0bywgJ3VwZGF0ZScsIGN0eCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIGlmICh0by5nZXRBdHRyaWJ1dGUoZnJvbUF0dHJpYnV0ZS5uYW1lKSAhPT0gZnJvbUF0dHJpYnV0ZS52YWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgdG8uc2V0QXR0cmlidXRlKGZyb21BdHRyaWJ1dGUubmFtZSwgZnJvbUF0dHJpYnV0ZS52YWx1ZSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgLy8gaXRlcmF0ZSBiYWNrd2FyZHMgdG8gYXZvaWQgc2tpcHBpbmcgb3ZlciBpdGVtcyB3aGVuIGEgZGVsZXRlIG9jY3Vyc1xuICAgICAgICAgICAgICAgIGZvciAobGV0IGkgPSB0b0F0dHJpYnV0ZXMubGVuZ3RoIC0gMTsgMCA8PSBpOyBpLS0pIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgdG9BdHRyaWJ1dGUgPSB0b0F0dHJpYnV0ZXNbaV07XG4gICAgICAgICAgICAgICAgICAgIGlmIChpZ25vcmVBdHRyaWJ1dGUodG9BdHRyaWJ1dGUubmFtZSwgdG8sICdyZW1vdmUnLCBjdHgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBpZiAoIWZyb20uaGFzQXR0cmlidXRlKHRvQXR0cmlidXRlLm5hbWUpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0by5yZW1vdmVBdHRyaWJ1dGUodG9BdHRyaWJ1dGUubmFtZSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vIHN5bmMgdGV4dCBub2Rlc1xuICAgICAgICAgICAgaWYgKHR5cGUgPT09IDggLyogY29tbWVudCAqLyB8fCB0eXBlID09PSAzIC8qIHRleHQgKi8pIHtcbiAgICAgICAgICAgICAgICBpZiAodG8ubm9kZVZhbHVlICE9PSBmcm9tLm5vZGVWYWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICB0by5ub2RlVmFsdWUgPSBmcm9tLm5vZGVWYWx1ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICghaWdub3JlVmFsdWVPZkFjdGl2ZUVsZW1lbnQodG8sIGN0eCkpIHtcbiAgICAgICAgICAgICAgICAvLyBzeW5jIGlucHV0IHZhbHVlc1xuICAgICAgICAgICAgICAgIHN5bmNJbnB1dFZhbHVlKGZyb20sIHRvLCBjdHgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIEBwYXJhbSBmcm9tIHtFbGVtZW50fSBlbGVtZW50IHRvIHN5bmMgdGhlIHZhbHVlIGZyb21cbiAgICAgICAgICogQHBhcmFtIHRvIHtFbGVtZW50fSBlbGVtZW50IHRvIHN5bmMgdGhlIHZhbHVlIHRvXG4gICAgICAgICAqIEBwYXJhbSBhdHRyaWJ1dGVOYW1lIHtTdHJpbmd9IHRoZSBhdHRyaWJ1dGUgbmFtZVxuICAgICAgICAgKiBAcGFyYW0gY3R4IHRoZSBtZXJnZSBjb250ZXh0XG4gICAgICAgICAqL1xuICAgICAgICBmdW5jdGlvbiBzeW5jQm9vbGVhbkF0dHJpYnV0ZShmcm9tLCB0bywgYXR0cmlidXRlTmFtZSwgY3R4KSB7XG4gICAgICAgICAgICBpZiAoZnJvbVthdHRyaWJ1dGVOYW1lXSAhPT0gdG9bYXR0cmlidXRlTmFtZV0pIHtcbiAgICAgICAgICAgICAgICBsZXQgaWdub3JlVXBkYXRlID0gaWdub3JlQXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUsIHRvLCAndXBkYXRlJywgY3R4KTtcbiAgICAgICAgICAgICAgICBpZiAoIWlnbm9yZVVwZGF0ZSkge1xuICAgICAgICAgICAgICAgICAgICB0b1thdHRyaWJ1dGVOYW1lXSA9IGZyb21bYXR0cmlidXRlTmFtZV07XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChmcm9tW2F0dHJpYnV0ZU5hbWVdKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmICghaWdub3JlVXBkYXRlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0by5zZXRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSwgZnJvbVthdHRyaWJ1dGVOYW1lXSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBpZiAoIWlnbm9yZUF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lLCB0bywgJ3JlbW92ZScsIGN0eCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRvLnJlbW92ZUF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBOQjogbWFueSBib3RoYW5zIGRpZWQgdG8gYnJpbmcgdXMgaW5mb3JtYXRpb246XG4gICAgICAgICAqXG4gICAgICAgICAqICBodHRwczovL2dpdGh1Yi5jb20vcGF0cmljay1zdGVlbGUtaWRlbS9tb3JwaGRvbS9ibG9iL21hc3Rlci9zcmMvc3BlY2lhbEVsSGFuZGxlcnMuanNcbiAgICAgICAgICogIGh0dHBzOi8vZ2l0aHViLmNvbS9jaG9vanMvbmFub21vcnBoL2Jsb2IvbWFzdGVyL2xpYi9tb3JwaC5qc0wxMTNcbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIGZyb20ge0VsZW1lbnR9IHRoZSBlbGVtZW50IHRvIHN5bmMgdGhlIGlucHV0IHZhbHVlIGZyb21cbiAgICAgICAgICogQHBhcmFtIHRvIHtFbGVtZW50fSB0aGUgZWxlbWVudCB0byBzeW5jIHRoZSBpbnB1dCB2YWx1ZSB0b1xuICAgICAgICAgKiBAcGFyYW0gY3R4IHRoZSBtZXJnZSBjb250ZXh0XG4gICAgICAgICAqL1xuICAgICAgICBmdW5jdGlvbiBzeW5jSW5wdXRWYWx1ZShmcm9tLCB0bywgY3R4KSB7XG4gICAgICAgICAgICBpZiAoZnJvbSBpbnN0YW5jZW9mIEhUTUxJbnB1dEVsZW1lbnQgJiZcbiAgICAgICAgICAgICAgICB0byBpbnN0YW5jZW9mIEhUTUxJbnB1dEVsZW1lbnQgJiZcbiAgICAgICAgICAgICAgICBmcm9tLnR5cGUgIT09ICdmaWxlJykge1xuXG4gICAgICAgICAgICAgICAgbGV0IGZyb21WYWx1ZSA9IGZyb20udmFsdWU7XG4gICAgICAgICAgICAgICAgbGV0IHRvVmFsdWUgPSB0by52YWx1ZTtcblxuICAgICAgICAgICAgICAgIC8vIHN5bmMgYm9vbGVhbiBhdHRyaWJ1dGVzXG4gICAgICAgICAgICAgICAgc3luY0Jvb2xlYW5BdHRyaWJ1dGUoZnJvbSwgdG8sICdjaGVja2VkJywgY3R4KTtcbiAgICAgICAgICAgICAgICBzeW5jQm9vbGVhbkF0dHJpYnV0ZShmcm9tLCB0bywgJ2Rpc2FibGVkJywgY3R4KTtcblxuICAgICAgICAgICAgICAgIGlmICghZnJvbS5oYXNBdHRyaWJ1dGUoJ3ZhbHVlJykpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCFpZ25vcmVBdHRyaWJ1dGUoJ3ZhbHVlJywgdG8sICdyZW1vdmUnLCBjdHgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0by52YWx1ZSA9ICcnO1xuICAgICAgICAgICAgICAgICAgICAgICAgdG8ucmVtb3ZlQXR0cmlidXRlKCd2YWx1ZScpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmIChmcm9tVmFsdWUgIT09IHRvVmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCFpZ25vcmVBdHRyaWJ1dGUoJ3ZhbHVlJywgdG8sICd1cGRhdGUnLCBjdHgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICB0by5zZXRBdHRyaWJ1dGUoJ3ZhbHVlJywgZnJvbVZhbHVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRvLnZhbHVlID0gZnJvbVZhbHVlO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIGlmIChmcm9tIGluc3RhbmNlb2YgSFRNTE9wdGlvbkVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICBzeW5jQm9vbGVhbkF0dHJpYnV0ZShmcm9tLCB0bywgJ3NlbGVjdGVkJywgY3R4KTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAoZnJvbSBpbnN0YW5jZW9mIEhUTUxUZXh0QXJlYUVsZW1lbnQgJiYgdG8gaW5zdGFuY2VvZiBIVE1MVGV4dEFyZWFFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgbGV0IGZyb21WYWx1ZSA9IGZyb20udmFsdWU7XG4gICAgICAgICAgICAgICAgbGV0IHRvVmFsdWUgPSB0by52YWx1ZTtcbiAgICAgICAgICAgICAgICBpZiAoaWdub3JlQXR0cmlidXRlKCd2YWx1ZScsIHRvLCAndXBkYXRlJywgY3R4KSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChmcm9tVmFsdWUgIT09IHRvVmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgdG8udmFsdWUgPSBmcm9tVmFsdWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmICh0by5maXJzdENoaWxkICYmIHRvLmZpcnN0Q2hpbGQubm9kZVZhbHVlICE9PSBmcm9tVmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgdG8uZmlyc3RDaGlsZC5ub2RlVmFsdWUgPSBmcm9tVmFsdWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyB0aGUgSEVBRCB0YWcgY2FuIGJlIGhhbmRsZWQgc3BlY2lhbGx5LCBlaXRoZXIgdy8gYSAnbWVyZ2UnIG9yICdhcHBlbmQnIHN0eWxlXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgZnVuY3Rpb24gaGFuZGxlSGVhZEVsZW1lbnQobmV3SGVhZFRhZywgY3VycmVudEhlYWQsIGN0eCkge1xuXG4gICAgICAgICAgICBsZXQgYWRkZWQgPSBbXTtcbiAgICAgICAgICAgIGxldCByZW1vdmVkID0gW107XG4gICAgICAgICAgICBsZXQgcHJlc2VydmVkID0gW107XG4gICAgICAgICAgICBsZXQgbm9kZXNUb0FwcGVuZCA9IFtdO1xuXG4gICAgICAgICAgICBsZXQgaGVhZE1lcmdlU3R5bGUgPSBjdHguaGVhZC5zdHlsZTtcblxuICAgICAgICAgICAgLy8gcHV0IGFsbCBuZXcgaGVhZCBlbGVtZW50cyBpbnRvIGEgTWFwLCBieSB0aGVpciBvdXRlckhUTUxcbiAgICAgICAgICAgIGxldCBzcmNUb05ld0hlYWROb2RlcyA9IG5ldyBNYXAoKTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgbmV3SGVhZENoaWxkIG9mIG5ld0hlYWRUYWcuY2hpbGRyZW4pIHtcbiAgICAgICAgICAgICAgICBzcmNUb05ld0hlYWROb2Rlcy5zZXQobmV3SGVhZENoaWxkLm91dGVySFRNTCwgbmV3SGVhZENoaWxkKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy8gZm9yIGVhY2ggZWx0IGluIHRoZSBjdXJyZW50IGhlYWRcbiAgICAgICAgICAgIGZvciAoY29uc3QgY3VycmVudEhlYWRFbHQgb2YgY3VycmVudEhlYWQuY2hpbGRyZW4pIHtcblxuICAgICAgICAgICAgICAgIC8vIElmIHRoZSBjdXJyZW50IGhlYWQgZWxlbWVudCBpcyBpbiB0aGUgbWFwXG4gICAgICAgICAgICAgICAgbGV0IGluTmV3Q29udGVudCA9IHNyY1RvTmV3SGVhZE5vZGVzLmhhcyhjdXJyZW50SGVhZEVsdC5vdXRlckhUTUwpO1xuICAgICAgICAgICAgICAgIGxldCBpc1JlQXBwZW5kZWQgPSBjdHguaGVhZC5zaG91bGRSZUFwcGVuZChjdXJyZW50SGVhZEVsdCk7XG4gICAgICAgICAgICAgICAgbGV0IGlzUHJlc2VydmVkID0gY3R4LmhlYWQuc2hvdWxkUHJlc2VydmUoY3VycmVudEhlYWRFbHQpO1xuICAgICAgICAgICAgICAgIGlmIChpbk5ld0NvbnRlbnQgfHwgaXNQcmVzZXJ2ZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGlzUmVBcHBlbmRlZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gcmVtb3ZlIHRoZSBjdXJyZW50IHZlcnNpb24gYW5kIGxldCB0aGUgbmV3IHZlcnNpb24gcmVwbGFjZSBpdCBhbmQgcmUtZXhlY3V0ZVxuICAgICAgICAgICAgICAgICAgICAgICAgcmVtb3ZlZC5wdXNoKGN1cnJlbnRIZWFkRWx0KTtcbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHRoaXMgZWxlbWVudCBhbHJlYWR5IGV4aXN0cyBhbmQgc2hvdWxkIG5vdCBiZSByZS1hcHBlbmRlZCwgc28gcmVtb3ZlIGl0IGZyb21cbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHRoZSBuZXcgY29udGVudCBtYXAsIHByZXNlcnZpbmcgaXQgaW4gdGhlIERPTVxuICAgICAgICAgICAgICAgICAgICAgICAgc3JjVG9OZXdIZWFkTm9kZXMuZGVsZXRlKGN1cnJlbnRIZWFkRWx0Lm91dGVySFRNTCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBwcmVzZXJ2ZWQucHVzaChjdXJyZW50SGVhZEVsdCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBpZiAoaGVhZE1lcmdlU3R5bGUgPT09IFwiYXBwZW5kXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHdlIGFyZSBhcHBlbmRpbmcgYW5kIHRoaXMgZXhpc3RpbmcgZWxlbWVudCBpcyBub3QgbmV3IGNvbnRlbnRcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHNvIGlmIGFuZCBvbmx5IGlmIGl0IGlzIG1hcmtlZCBmb3IgcmUtYXBwZW5kIGRvIHdlIGRvIGFueXRoaW5nXG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoaXNSZUFwcGVuZGVkKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVtb3ZlZC5wdXNoKGN1cnJlbnRIZWFkRWx0KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBub2Rlc1RvQXBwZW5kLnB1c2goY3VycmVudEhlYWRFbHQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gaWYgdGhpcyBpcyBhIG1lcmdlLCB3ZSByZW1vdmUgdGhpcyBjb250ZW50IHNpbmNlIGl0IGlzIG5vdCBpbiB0aGUgbmV3IGhlYWRcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChjdHguaGVhZC5zaG91bGRSZW1vdmUoY3VycmVudEhlYWRFbHQpICE9PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlbW92ZWQucHVzaChjdXJyZW50SGVhZEVsdCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vIFB1c2ggdGhlIHJlbWFpbmluZyBuZXcgaGVhZCBlbGVtZW50cyBpbiB0aGUgTWFwIGludG8gdGhlXG4gICAgICAgICAgICAvLyBub2RlcyB0byBhcHBlbmQgdG8gdGhlIGhlYWQgdGFnXG4gICAgICAgICAgICBub2Rlc1RvQXBwZW5kLnB1c2goLi4uc3JjVG9OZXdIZWFkTm9kZXMudmFsdWVzKCkpO1xuXG4gICAgICAgICAgICBsZXQgcHJvbWlzZXMgPSBbXTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgbmV3Tm9kZSBvZiBub2Rlc1RvQXBwZW5kKSB7XG4gICAgICAgICAgICAgICAgbGV0IG5ld0VsdCA9IGRvY3VtZW50LmNyZWF0ZVJhbmdlKCkuY3JlYXRlQ29udGV4dHVhbEZyYWdtZW50KG5ld05vZGUub3V0ZXJIVE1MKS5maXJzdENoaWxkO1xuICAgICAgICAgICAgICAgIGlmIChjdHguY2FsbGJhY2tzLmJlZm9yZU5vZGVBZGRlZChuZXdFbHQpICE9PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgICAgICBpZiAobmV3RWx0LmhyZWYgfHwgbmV3RWx0LnNyYykge1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHJlc29sdmUgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgbGV0IHByb21pc2UgPSBuZXcgUHJvbWlzZShmdW5jdGlvbiAoX3Jlc29sdmUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXNvbHZlID0gX3Jlc29sdmU7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIG5ld0VsdC5hZGRFdmVudExpc3RlbmVyKCdsb2FkJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlc29sdmUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgcHJvbWlzZXMucHVzaChwcm9taXNlKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBjdXJyZW50SGVhZC5hcHBlbmRDaGlsZChuZXdFbHQpO1xuICAgICAgICAgICAgICAgICAgICBjdHguY2FsbGJhY2tzLmFmdGVyTm9kZUFkZGVkKG5ld0VsdCk7XG4gICAgICAgICAgICAgICAgICAgIGFkZGVkLnB1c2gobmV3RWx0KTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vIHJlbW92ZSBhbGwgcmVtb3ZlZCBlbGVtZW50cywgYWZ0ZXIgd2UgaGF2ZSBhcHBlbmRlZCB0aGUgbmV3IGVsZW1lbnRzIHRvIGF2b2lkXG4gICAgICAgICAgICAvLyBhZGRpdGlvbmFsIG5ldHdvcmsgcmVxdWVzdHMgZm9yIHRoaW5ncyBsaWtlIHN0eWxlIHNoZWV0c1xuICAgICAgICAgICAgZm9yIChjb25zdCByZW1vdmVkRWxlbWVudCBvZiByZW1vdmVkKSB7XG4gICAgICAgICAgICAgICAgaWYgKGN0eC5jYWxsYmFja3MuYmVmb3JlTm9kZVJlbW92ZWQocmVtb3ZlZEVsZW1lbnQpICE9PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgICAgICBjdXJyZW50SGVhZC5yZW1vdmVDaGlsZChyZW1vdmVkRWxlbWVudCk7XG4gICAgICAgICAgICAgICAgICAgIGN0eC5jYWxsYmFja3MuYWZ0ZXJOb2RlUmVtb3ZlZChyZW1vdmVkRWxlbWVudCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjdHguaGVhZC5hZnRlckhlYWRNb3JwaGVkKGN1cnJlbnRIZWFkLCB7YWRkZWQ6IGFkZGVkLCBrZXB0OiBwcmVzZXJ2ZWQsIHJlbW92ZWQ6IHJlbW92ZWR9KTtcbiAgICAgICAgICAgIHJldHVybiBwcm9taXNlcztcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIG5vT3AoKSB7XG4gICAgICAgIH1cblxuICAgICAgICAvKlxuICAgICAgICAgIERlZXAgbWVyZ2VzIHRoZSBjb25maWcgb2JqZWN0IGFuZCB0aGUgSWRpb21vcm9waC5kZWZhdWx0cyBvYmplY3QgdG9cbiAgICAgICAgICBwcm9kdWNlIGEgZmluYWwgY29uZmlndXJhdGlvbiBvYmplY3RcbiAgICAgICAgICovXG4gICAgICAgIGZ1bmN0aW9uIG1lcmdlRGVmYXVsdHMoY29uZmlnKSB7XG4gICAgICAgICAgICBsZXQgZmluYWxDb25maWcgPSB7fTtcbiAgICAgICAgICAgIC8vIGNvcHkgdG9wIGxldmVsIHN0dWZmIGludG8gZmluYWwgY29uZmlnXG4gICAgICAgICAgICBPYmplY3QuYXNzaWduKGZpbmFsQ29uZmlnLCBkZWZhdWx0cyk7XG4gICAgICAgICAgICBPYmplY3QuYXNzaWduKGZpbmFsQ29uZmlnLCBjb25maWcpO1xuXG4gICAgICAgICAgICAvLyBjb3B5IGNhbGxiYWNrcyBpbnRvIGZpbmFsIGNvbmZpZyAoZG8gdGhpcyB0byBkZWVwIG1lcmdlIHRoZSBjYWxsYmFja3MpXG4gICAgICAgICAgICBmaW5hbENvbmZpZy5jYWxsYmFja3MgPSB7fTtcbiAgICAgICAgICAgIE9iamVjdC5hc3NpZ24oZmluYWxDb25maWcuY2FsbGJhY2tzLCBkZWZhdWx0cy5jYWxsYmFja3MpO1xuICAgICAgICAgICAgT2JqZWN0LmFzc2lnbihmaW5hbENvbmZpZy5jYWxsYmFja3MsIGNvbmZpZy5jYWxsYmFja3MpO1xuXG4gICAgICAgICAgICAvLyBjb3B5IGhlYWQgY29uZmlnIGludG8gZmluYWwgY29uZmlnICAoZG8gdGhpcyB0byBkZWVwIG1lcmdlIHRoZSBoZWFkKVxuICAgICAgICAgICAgZmluYWxDb25maWcuaGVhZCA9IHt9O1xuICAgICAgICAgICAgT2JqZWN0LmFzc2lnbihmaW5hbENvbmZpZy5oZWFkLCBkZWZhdWx0cy5oZWFkKTtcbiAgICAgICAgICAgIE9iamVjdC5hc3NpZ24oZmluYWxDb25maWcuaGVhZCwgY29uZmlnLmhlYWQpO1xuICAgICAgICAgICAgcmV0dXJuIGZpbmFsQ29uZmlnO1xuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gY3JlYXRlTW9ycGhDb250ZXh0KG9sZE5vZGUsIG5ld0NvbnRlbnQsIGNvbmZpZykge1xuICAgICAgICAgICAgY29uZmlnID0gbWVyZ2VEZWZhdWx0cyhjb25maWcpO1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICB0YXJnZXQ6IG9sZE5vZGUsXG4gICAgICAgICAgICAgICAgbmV3Q29udGVudDogbmV3Q29udGVudCxcbiAgICAgICAgICAgICAgICBjb25maWc6IGNvbmZpZyxcbiAgICAgICAgICAgICAgICBtb3JwaFN0eWxlOiBjb25maWcubW9ycGhTdHlsZSxcbiAgICAgICAgICAgICAgICBpZ25vcmVBY3RpdmU6IGNvbmZpZy5pZ25vcmVBY3RpdmUsXG4gICAgICAgICAgICAgICAgaWdub3JlQWN0aXZlVmFsdWU6IGNvbmZpZy5pZ25vcmVBY3RpdmVWYWx1ZSxcbiAgICAgICAgICAgICAgICBpZE1hcDogY3JlYXRlSWRNYXAob2xkTm9kZSwgbmV3Q29udGVudCksXG4gICAgICAgICAgICAgICAgZGVhZElkczogbmV3IFNldCgpLFxuICAgICAgICAgICAgICAgIGNhbGxiYWNrczogY29uZmlnLmNhbGxiYWNrcyxcbiAgICAgICAgICAgICAgICBoZWFkOiBjb25maWcuaGVhZFxuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gaXNJZFNldE1hdGNoKG5vZGUxLCBub2RlMiwgY3R4KSB7XG4gICAgICAgICAgICBpZiAobm9kZTEgPT0gbnVsbCB8fCBub2RlMiA9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKG5vZGUxLm5vZGVUeXBlID09PSBub2RlMi5ub2RlVHlwZSAmJiBub2RlMS50YWdOYW1lID09PSBub2RlMi50YWdOYW1lKSB7XG4gICAgICAgICAgICAgICAgaWYgKG5vZGUxLmlkICE9PSBcIlwiICYmIG5vZGUxLmlkID09PSBub2RlMi5pZCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZ2V0SWRJbnRlcnNlY3Rpb25Db3VudChjdHgsIG5vZGUxLCBub2RlMikgPiAwO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIGlzU29mdE1hdGNoKG5vZGUxLCBub2RlMikge1xuICAgICAgICAgICAgaWYgKG5vZGUxID09IG51bGwgfHwgbm9kZTIgPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBub2RlMS5ub2RlVHlwZSA9PT0gbm9kZTIubm9kZVR5cGUgJiYgbm9kZTEudGFnTmFtZSA9PT0gbm9kZTIudGFnTmFtZVxuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gcmVtb3ZlTm9kZXNCZXR3ZWVuKHN0YXJ0SW5jbHVzaXZlLCBlbmRFeGNsdXNpdmUsIGN0eCkge1xuICAgICAgICAgICAgd2hpbGUgKHN0YXJ0SW5jbHVzaXZlICE9PSBlbmRFeGNsdXNpdmUpIHtcbiAgICAgICAgICAgICAgICBsZXQgdGVtcE5vZGUgPSBzdGFydEluY2x1c2l2ZTtcbiAgICAgICAgICAgICAgICBzdGFydEluY2x1c2l2ZSA9IHN0YXJ0SW5jbHVzaXZlLm5leHRTaWJsaW5nO1xuICAgICAgICAgICAgICAgIHJlbW92ZU5vZGUodGVtcE5vZGUsIGN0eCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIGVuZEV4Y2x1c2l2ZSk7XG4gICAgICAgICAgICByZXR1cm4gZW5kRXhjbHVzaXZlLm5leHRTaWJsaW5nO1xuICAgICAgICB9XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBTY2FucyBmb3J3YXJkIGZyb20gdGhlIGluc2VydGlvblBvaW50IGluIHRoZSBvbGQgcGFyZW50IGxvb2tpbmcgZm9yIGEgcG90ZW50aWFsIGlkIG1hdGNoXG4gICAgICAgIC8vIGZvciB0aGUgbmV3Q2hpbGQuICBXZSBzdG9wIGlmIHdlIGZpbmQgYSBwb3RlbnRpYWwgaWQgbWF0Y2ggZm9yIHRoZSBuZXcgY2hpbGQgT1JcbiAgICAgICAgLy8gaWYgdGhlIG51bWJlciBvZiBwb3RlbnRpYWwgaWQgbWF0Y2hlcyB3ZSBhcmUgZGlzY2FyZGluZyBpcyBncmVhdGVyIHRoYW4gdGhlXG4gICAgICAgIC8vIHBvdGVudGlhbCBpZCBtYXRjaGVzIGZvciB0aGUgbmV3IGNoaWxkXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgZnVuY3Rpb24gZmluZElkU2V0TWF0Y2gobmV3Q29udGVudCwgb2xkUGFyZW50LCBuZXdDaGlsZCwgaW5zZXJ0aW9uUG9pbnQsIGN0eCkge1xuXG4gICAgICAgICAgICAvLyBtYXggaWQgbWF0Y2hlcyB3ZSBhcmUgd2lsbGluZyB0byBkaXNjYXJkIGluIG91ciBzZWFyY2hcbiAgICAgICAgICAgIGxldCBuZXdDaGlsZFBvdGVudGlhbElkQ291bnQgPSBnZXRJZEludGVyc2VjdGlvbkNvdW50KGN0eCwgbmV3Q2hpbGQsIG9sZFBhcmVudCk7XG5cbiAgICAgICAgICAgIGxldCBwb3RlbnRpYWxNYXRjaCA9IG51bGw7XG5cbiAgICAgICAgICAgIC8vIG9ubHkgc2VhcmNoIGZvcndhcmQgaWYgdGhlcmUgaXMgYSBwb3NzaWJpbGl0eSBvZiBhbiBpZCBtYXRjaFxuICAgICAgICAgICAgaWYgKG5ld0NoaWxkUG90ZW50aWFsSWRDb3VudCA+IDApIHtcbiAgICAgICAgICAgICAgICBsZXQgcG90ZW50aWFsTWF0Y2ggPSBpbnNlcnRpb25Qb2ludDtcbiAgICAgICAgICAgICAgICAvLyBpZiB0aGVyZSBpcyBhIHBvc3NpYmlsaXR5IG9mIGFuIGlkIG1hdGNoLCBzY2FuIGZvcndhcmRcbiAgICAgICAgICAgICAgICAvLyBrZWVwIHRyYWNrIG9mIHRoZSBwb3RlbnRpYWwgaWQgbWF0Y2ggY291bnQgd2UgYXJlIGRpc2NhcmRpbmcgKHRoZVxuICAgICAgICAgICAgICAgIC8vIG5ld0NoaWxkUG90ZW50aWFsSWRDb3VudCBtdXN0IGJlIGdyZWF0ZXIgdGhhbiB0aGlzIHRvIG1ha2UgaXQgbGlrZWx5XG4gICAgICAgICAgICAgICAgLy8gd29ydGggaXQpXG4gICAgICAgICAgICAgICAgbGV0IG90aGVyTWF0Y2hDb3VudCA9IDA7XG4gICAgICAgICAgICAgICAgd2hpbGUgKHBvdGVudGlhbE1hdGNoICE9IG51bGwpIHtcblxuICAgICAgICAgICAgICAgICAgICAvLyBJZiB3ZSBoYXZlIGFuIGlkIG1hdGNoLCByZXR1cm4gdGhlIGN1cnJlbnQgcG90ZW50aWFsIG1hdGNoXG4gICAgICAgICAgICAgICAgICAgIGlmIChpc0lkU2V0TWF0Y2gobmV3Q2hpbGQsIHBvdGVudGlhbE1hdGNoLCBjdHgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gcG90ZW50aWFsTWF0Y2g7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAvLyBjb21wdXRlciB0aGUgb3RoZXIgcG90ZW50aWFsIG1hdGNoZXMgb2YgdGhpcyBuZXcgY29udGVudFxuICAgICAgICAgICAgICAgICAgICBvdGhlck1hdGNoQ291bnQgKz0gZ2V0SWRJbnRlcnNlY3Rpb25Db3VudChjdHgsIHBvdGVudGlhbE1hdGNoLCBuZXdDb250ZW50KTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKG90aGVyTWF0Y2hDb3VudCA+IG5ld0NoaWxkUG90ZW50aWFsSWRDb3VudCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgLy8gaWYgd2UgaGF2ZSBtb3JlIHBvdGVudGlhbCBpZCBtYXRjaGVzIGluIF9vdGhlcl8gY29udGVudCwgd2VcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIGRvIG5vdCBoYXZlIGEgZ29vZCBjYW5kaWRhdGUgZm9yIGFuIGlkIG1hdGNoLCBzbyByZXR1cm4gbnVsbFxuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAvLyBhZHZhbmNlZCB0byB0aGUgbmV4dCBvbGQgY29udGVudCBjaGlsZFxuICAgICAgICAgICAgICAgICAgICBwb3RlbnRpYWxNYXRjaCA9IHBvdGVudGlhbE1hdGNoLm5leHRTaWJsaW5nO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBwb3RlbnRpYWxNYXRjaDtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgLy8gU2NhbnMgZm9yd2FyZCBmcm9tIHRoZSBpbnNlcnRpb25Qb2ludCBpbiB0aGUgb2xkIHBhcmVudCBsb29raW5nIGZvciBhIHBvdGVudGlhbCBzb2Z0IG1hdGNoXG4gICAgICAgIC8vIGZvciB0aGUgbmV3Q2hpbGQuICBXZSBzdG9wIGlmIHdlIGZpbmQgYSBwb3RlbnRpYWwgc29mdCBtYXRjaCBmb3IgdGhlIG5ldyBjaGlsZCBPUlxuICAgICAgICAvLyBpZiB3ZSBmaW5kIGEgcG90ZW50aWFsIGlkIG1hdGNoIGluIHRoZSBvbGQgcGFyZW50cyBjaGlsZHJlbiBPUiBpZiB3ZSBmaW5kIHR3b1xuICAgICAgICAvLyBwb3RlbnRpYWwgc29mdCBtYXRjaGVzIGZvciB0aGUgbmV4dCB0d28gcGllY2VzIG9mIG5ldyBjb250ZW50XG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbiAgICAgICAgZnVuY3Rpb24gZmluZFNvZnRNYXRjaChuZXdDb250ZW50LCBvbGRQYXJlbnQsIG5ld0NoaWxkLCBpbnNlcnRpb25Qb2ludCwgY3R4KSB7XG5cbiAgICAgICAgICAgIGxldCBwb3RlbnRpYWxTb2Z0TWF0Y2ggPSBpbnNlcnRpb25Qb2ludDtcbiAgICAgICAgICAgIGxldCBuZXh0U2libGluZyA9IG5ld0NoaWxkLm5leHRTaWJsaW5nO1xuICAgICAgICAgICAgbGV0IHNpYmxpbmdTb2Z0TWF0Y2hDb3VudCA9IDA7XG5cbiAgICAgICAgICAgIHdoaWxlIChwb3RlbnRpYWxTb2Z0TWF0Y2ggIT0gbnVsbCkge1xuXG4gICAgICAgICAgICAgICAgaWYgKGdldElkSW50ZXJzZWN0aW9uQ291bnQoY3R4LCBwb3RlbnRpYWxTb2Z0TWF0Y2gsIG5ld0NvbnRlbnQpID4gMCkge1xuICAgICAgICAgICAgICAgICAgICAvLyB0aGUgY3VycmVudCBwb3RlbnRpYWwgc29mdCBtYXRjaCBoYXMgYSBwb3RlbnRpYWwgaWQgc2V0IG1hdGNoIHdpdGggdGhlIHJlbWFpbmluZyBuZXdcbiAgICAgICAgICAgICAgICAgICAgLy8gY29udGVudCBzbyBiYWlsIG91dCBvZiBsb29raW5nXG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vIGlmIHdlIGhhdmUgYSBzb2Z0IG1hdGNoIHdpdGggdGhlIGN1cnJlbnQgbm9kZSwgcmV0dXJuIGl0XG4gICAgICAgICAgICAgICAgaWYgKGlzU29mdE1hdGNoKG5ld0NoaWxkLCBwb3RlbnRpYWxTb2Z0TWF0Y2gpKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBwb3RlbnRpYWxTb2Z0TWF0Y2g7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKGlzU29mdE1hdGNoKG5leHRTaWJsaW5nLCBwb3RlbnRpYWxTb2Z0TWF0Y2gpKSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIHRoZSBuZXh0IG5ldyBub2RlIGhhcyBhIHNvZnQgbWF0Y2ggd2l0aCB0aGlzIG5vZGUsIHNvXG4gICAgICAgICAgICAgICAgICAgIC8vIGluY3JlbWVudCB0aGUgY291bnQgb2YgZnV0dXJlIHNvZnQgbWF0Y2hlc1xuICAgICAgICAgICAgICAgICAgICBzaWJsaW5nU29mdE1hdGNoQ291bnQrKztcbiAgICAgICAgICAgICAgICAgICAgbmV4dFNpYmxpbmcgPSBuZXh0U2libGluZy5uZXh0U2libGluZztcblxuICAgICAgICAgICAgICAgICAgICAvLyBJZiB0aGVyZSBhcmUgdHdvIGZ1dHVyZSBzb2Z0IG1hdGNoZXMsIGJhaWwgdG8gYWxsb3cgdGhlIHNpYmxpbmdzIHRvIHNvZnQgbWF0Y2hcbiAgICAgICAgICAgICAgICAgICAgLy8gc28gdGhhdCB3ZSBkb24ndCBjb25zdW1lIGZ1dHVyZSBzb2Z0IG1hdGNoZXMgZm9yIHRoZSBzYWtlIG9mIHRoZSBjdXJyZW50IG5vZGVcbiAgICAgICAgICAgICAgICAgICAgaWYgKHNpYmxpbmdTb2Z0TWF0Y2hDb3VudCA+PSAyKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gbnVsbDtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIC8vIGFkdmFuY2VkIHRvIHRoZSBuZXh0IG9sZCBjb250ZW50IGNoaWxkXG4gICAgICAgICAgICAgICAgcG90ZW50aWFsU29mdE1hdGNoID0gcG90ZW50aWFsU29mdE1hdGNoLm5leHRTaWJsaW5nO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZXR1cm4gcG90ZW50aWFsU29mdE1hdGNoO1xuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gcGFyc2VDb250ZW50KG5ld0NvbnRlbnQpIHtcbiAgICAgICAgICAgIGxldCBwYXJzZXIgPSBuZXcgRE9NUGFyc2VyKCk7XG5cbiAgICAgICAgICAgIC8vIHJlbW92ZSBzdmdzIHRvIGF2b2lkIGZhbHNlLXBvc2l0aXZlIG1hdGNoZXMgb24gaGVhZCwgZXRjLlxuICAgICAgICAgICAgbGV0IGNvbnRlbnRXaXRoU3Znc1JlbW92ZWQgPSBuZXdDb250ZW50LnJlcGxhY2UoLzxzdmcoXFxzW14+XSo+fD4pKFtcXHNcXFNdKj8pPFxcL3N2Zz4vZ2ltLCAnJyk7XG5cbiAgICAgICAgICAgIC8vIGlmIHRoZSBuZXdDb250ZW50IGNvbnRhaW5zIGEgaHRtbCwgaGVhZCBvciBib2R5IHRhZywgd2UgY2FuIHNpbXBseSBwYXJzZSBpdCB3L28gd3JhcHBpbmdcbiAgICAgICAgICAgIGlmIChjb250ZW50V2l0aFN2Z3NSZW1vdmVkLm1hdGNoKC88XFwvaHRtbD4vKSB8fCBjb250ZW50V2l0aFN2Z3NSZW1vdmVkLm1hdGNoKC88XFwvaGVhZD4vKSB8fCBjb250ZW50V2l0aFN2Z3NSZW1vdmVkLm1hdGNoKC88XFwvYm9keT4vKSkge1xuICAgICAgICAgICAgICAgIGxldCBjb250ZW50ID0gcGFyc2VyLnBhcnNlRnJvbVN0cmluZyhuZXdDb250ZW50LCBcInRleHQvaHRtbFwiKTtcbiAgICAgICAgICAgICAgICAvLyBpZiBpdCBpcyBhIGZ1bGwgSFRNTCBkb2N1bWVudCwgcmV0dXJuIHRoZSBkb2N1bWVudCBpdHNlbGYgYXMgdGhlIHBhcmVudCBjb250YWluZXJcbiAgICAgICAgICAgICAgICBpZiAoY29udGVudFdpdGhTdmdzUmVtb3ZlZC5tYXRjaCgvPFxcL2h0bWw+LykpIHtcbiAgICAgICAgICAgICAgICAgICAgY29udGVudC5nZW5lcmF0ZWRCeUlkaW9tb3JwaCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBjb250ZW50O1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIG90aGVyd2lzZSByZXR1cm4gdGhlIGh0bWwgZWxlbWVudCBhcyB0aGUgcGFyZW50IGNvbnRhaW5lclxuICAgICAgICAgICAgICAgICAgICBsZXQgaHRtbEVsZW1lbnQgPSBjb250ZW50LmZpcnN0Q2hpbGQ7XG4gICAgICAgICAgICAgICAgICAgIGlmIChodG1sRWxlbWVudCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaHRtbEVsZW1lbnQuZ2VuZXJhdGVkQnlJZGlvbW9ycGggPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGh0bWxFbGVtZW50O1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIC8vIGlmIGl0IGlzIHBhcnRpYWwgSFRNTCwgd3JhcCBpdCBpbiBhIHRlbXBsYXRlIHRhZyB0byBwcm92aWRlIGEgcGFyZW50IGVsZW1lbnQgYW5kIGFsc28gdG8gaGVscFxuICAgICAgICAgICAgICAgIC8vIGRlYWwgd2l0aCB0b3VjaHkgdGFncyBsaWtlIHRyLCB0Ym9keSwgZXRjLlxuICAgICAgICAgICAgICAgIGxldCByZXNwb25zZURvYyA9IHBhcnNlci5wYXJzZUZyb21TdHJpbmcoXCI8Ym9keT48dGVtcGxhdGU+XCIgKyBuZXdDb250ZW50ICsgXCI8L3RlbXBsYXRlPjwvYm9keT5cIiwgXCJ0ZXh0L2h0bWxcIik7XG4gICAgICAgICAgICAgICAgbGV0IGNvbnRlbnQgPSByZXNwb25zZURvYy5ib2R5LnF1ZXJ5U2VsZWN0b3IoJ3RlbXBsYXRlJykuY29udGVudDtcbiAgICAgICAgICAgICAgICBjb250ZW50LmdlbmVyYXRlZEJ5SWRpb21vcnBoID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICByZXR1cm4gY29udGVudFxuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gbm9ybWFsaXplQ29udGVudChuZXdDb250ZW50KSB7XG4gICAgICAgICAgICBpZiAobmV3Q29udGVudCA9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgLy8gbm9pbnNwZWN0aW9uIFVubmVjZXNzYXJ5TG9jYWxWYXJpYWJsZUpTXG4gICAgICAgICAgICAgICAgY29uc3QgZHVtbXlQYXJlbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgICAgICAgICByZXR1cm4gZHVtbXlQYXJlbnQ7XG4gICAgICAgICAgICB9IGVsc2UgaWYgKG5ld0NvbnRlbnQuZ2VuZXJhdGVkQnlJZGlvbW9ycGgpIHtcbiAgICAgICAgICAgICAgICAvLyB0aGUgdGVtcGxhdGUgdGFnIGNyZWF0ZWQgYnkgaWRpb21vcnBoIHBhcnNpbmcgY2FuIHNlcnZlIGFzIGEgZHVtbXkgcGFyZW50XG4gICAgICAgICAgICAgICAgcmV0dXJuIG5ld0NvbnRlbnQ7XG4gICAgICAgICAgICB9IGVsc2UgaWYgKG5ld0NvbnRlbnQgaW5zdGFuY2VvZiBOb2RlKSB7XG4gICAgICAgICAgICAgICAgLy8gYSBzaW5nbGUgbm9kZSBpcyBhZGRlZCBhcyBhIGNoaWxkIHRvIGEgZHVtbXkgcGFyZW50XG4gICAgICAgICAgICAgICAgY29uc3QgZHVtbXlQYXJlbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgICAgICAgICBkdW1teVBhcmVudC5hcHBlbmQobmV3Q29udGVudCk7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGR1bW15UGFyZW50O1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAvLyBhbGwgbm9kZXMgaW4gdGhlIGFycmF5IG9yIEhUTUxFbGVtZW50IGNvbGxlY3Rpb24gYXJlIGNvbnNvbGlkYXRlZCB1bmRlclxuICAgICAgICAgICAgICAgIC8vIGEgc2luZ2xlIGR1bW15IHBhcmVudCBlbGVtZW50XG4gICAgICAgICAgICAgICAgY29uc3QgZHVtbXlQYXJlbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgICAgICAgICBmb3IgKGNvbnN0IGVsdCBvZiBbLi4ubmV3Q29udGVudF0pIHtcbiAgICAgICAgICAgICAgICAgICAgZHVtbXlQYXJlbnQuYXBwZW5kKGVsdCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHJldHVybiBkdW1teVBhcmVudDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIGluc2VydFNpYmxpbmdzKHByZXZpb3VzU2libGluZywgbW9ycGhlZE5vZGUsIG5leHRTaWJsaW5nKSB7XG4gICAgICAgICAgICBsZXQgc3RhY2sgPSBbXTtcbiAgICAgICAgICAgIGxldCBhZGRlZCA9IFtdO1xuICAgICAgICAgICAgd2hpbGUgKHByZXZpb3VzU2libGluZyAhPSBudWxsKSB7XG4gICAgICAgICAgICAgICAgc3RhY2sucHVzaChwcmV2aW91c1NpYmxpbmcpO1xuICAgICAgICAgICAgICAgIHByZXZpb3VzU2libGluZyA9IHByZXZpb3VzU2libGluZy5wcmV2aW91c1NpYmxpbmc7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB3aGlsZSAoc3RhY2subGVuZ3RoID4gMCkge1xuICAgICAgICAgICAgICAgIGxldCBub2RlID0gc3RhY2sucG9wKCk7XG4gICAgICAgICAgICAgICAgYWRkZWQucHVzaChub2RlKTsgLy8gcHVzaCBhZGRlZCBwcmVjZWRpbmcgc2libGluZ3Mgb24gaW4gb3JkZXIgYW5kIGluc2VydFxuICAgICAgICAgICAgICAgIG1vcnBoZWROb2RlLnBhcmVudEVsZW1lbnQuaW5zZXJ0QmVmb3JlKG5vZGUsIG1vcnBoZWROb2RlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGFkZGVkLnB1c2gobW9ycGhlZE5vZGUpO1xuICAgICAgICAgICAgd2hpbGUgKG5leHRTaWJsaW5nICE9IG51bGwpIHtcbiAgICAgICAgICAgICAgICBzdGFjay5wdXNoKG5leHRTaWJsaW5nKTtcbiAgICAgICAgICAgICAgICBhZGRlZC5wdXNoKG5leHRTaWJsaW5nKTsgLy8gaGVyZSB3ZSBhcmUgZ29pbmcgaW4gb3JkZXIsIHNvIHB1c2ggb24gYXMgd2Ugc2NhbiwgcmF0aGVyIHRoYW4gYWRkXG4gICAgICAgICAgICAgICAgbmV4dFNpYmxpbmcgPSBuZXh0U2libGluZy5uZXh0U2libGluZztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHdoaWxlIChzdGFjay5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICAgICAgbW9ycGhlZE5vZGUucGFyZW50RWxlbWVudC5pbnNlcnRCZWZvcmUoc3RhY2sucG9wKCksIG1vcnBoZWROb2RlLm5leHRTaWJsaW5nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBhZGRlZDtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIGZpbmRCZXN0Tm9kZU1hdGNoKG5ld0NvbnRlbnQsIG9sZE5vZGUsIGN0eCkge1xuICAgICAgICAgICAgbGV0IGN1cnJlbnRFbGVtZW50O1xuICAgICAgICAgICAgY3VycmVudEVsZW1lbnQgPSBuZXdDb250ZW50LmZpcnN0Q2hpbGQ7XG4gICAgICAgICAgICBsZXQgYmVzdEVsZW1lbnQgPSBjdXJyZW50RWxlbWVudDtcbiAgICAgICAgICAgIGxldCBzY29yZSA9IDA7XG4gICAgICAgICAgICB3aGlsZSAoY3VycmVudEVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICBsZXQgbmV3U2NvcmUgPSBzY29yZUVsZW1lbnQoY3VycmVudEVsZW1lbnQsIG9sZE5vZGUsIGN0eCk7XG4gICAgICAgICAgICAgICAgaWYgKG5ld1Njb3JlID4gc2NvcmUpIHtcbiAgICAgICAgICAgICAgICAgICAgYmVzdEVsZW1lbnQgPSBjdXJyZW50RWxlbWVudDtcbiAgICAgICAgICAgICAgICAgICAgc2NvcmUgPSBuZXdTY29yZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY3VycmVudEVsZW1lbnQgPSBjdXJyZW50RWxlbWVudC5uZXh0U2libGluZztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBiZXN0RWxlbWVudDtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIHNjb3JlRWxlbWVudChub2RlMSwgbm9kZTIsIGN0eCkge1xuICAgICAgICAgICAgaWYgKGlzU29mdE1hdGNoKG5vZGUxLCBub2RlMikpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gLjUgKyBnZXRJZEludGVyc2VjdGlvbkNvdW50KGN0eCwgbm9kZTEsIG5vZGUyKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiAwO1xuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gcmVtb3ZlTm9kZSh0ZW1wTm9kZSwgY3R4KSB7XG4gICAgICAgICAgICByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIHRlbXBOb2RlKTtcbiAgICAgICAgICAgIGlmIChjdHguY2FsbGJhY2tzLmJlZm9yZU5vZGVSZW1vdmVkKHRlbXBOb2RlKSA9PT0gZmFsc2UpIHJldHVybjtcblxuICAgICAgICAgICAgdGVtcE5vZGUucmVtb3ZlKCk7XG4gICAgICAgICAgICBjdHguY2FsbGJhY2tzLmFmdGVyTm9kZVJlbW92ZWQodGVtcE5vZGUpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBJRCBTZXQgRnVuY3Rpb25zXG4gICAgICAgIC8vPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cblxuICAgICAgICBmdW5jdGlvbiBpc0lkSW5Db25zaWRlcmF0aW9uKGN0eCwgaWQpIHtcbiAgICAgICAgICAgIHJldHVybiAhY3R4LmRlYWRJZHMuaGFzKGlkKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGZ1bmN0aW9uIGlkSXNXaXRoaW5Ob2RlKGN0eCwgaWQsIHRhcmdldE5vZGUpIHtcbiAgICAgICAgICAgIGxldCBpZFNldCA9IGN0eC5pZE1hcC5nZXQodGFyZ2V0Tm9kZSkgfHwgRU1QVFlfU0VUO1xuICAgICAgICAgICAgcmV0dXJuIGlkU2V0LmhhcyhpZCk7XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiByZW1vdmVJZHNGcm9tQ29uc2lkZXJhdGlvbihjdHgsIG5vZGUpIHtcbiAgICAgICAgICAgIGxldCBpZFNldCA9IGN0eC5pZE1hcC5nZXQobm9kZSkgfHwgRU1QVFlfU0VUO1xuICAgICAgICAgICAgZm9yIChjb25zdCBpZCBvZiBpZFNldCkge1xuICAgICAgICAgICAgICAgIGN0eC5kZWFkSWRzLmFkZChpZCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBmdW5jdGlvbiBnZXRJZEludGVyc2VjdGlvbkNvdW50KGN0eCwgbm9kZTEsIG5vZGUyKSB7XG4gICAgICAgICAgICBsZXQgc291cmNlU2V0ID0gY3R4LmlkTWFwLmdldChub2RlMSkgfHwgRU1QVFlfU0VUO1xuICAgICAgICAgICAgbGV0IG1hdGNoQ291bnQgPSAwO1xuICAgICAgICAgICAgZm9yIChjb25zdCBpZCBvZiBzb3VyY2VTZXQpIHtcbiAgICAgICAgICAgICAgICAvLyBhIHBvdGVudGlhbCBtYXRjaCBpcyBhbiBpZCBpbiB0aGUgc291cmNlIGFuZCBwb3RlbnRpYWxJZHNTZXQsIGJ1dFxuICAgICAgICAgICAgICAgIC8vIHRoYXQgaGFzIG5vdCBhbHJlYWR5IGJlZW4gbWVyZ2VkIGludG8gdGhlIERPTVxuICAgICAgICAgICAgICAgIGlmIChpc0lkSW5Db25zaWRlcmF0aW9uKGN0eCwgaWQpICYmIGlkSXNXaXRoaW5Ob2RlKGN0eCwgaWQsIG5vZGUyKSkge1xuICAgICAgICAgICAgICAgICAgICArK21hdGNoQ291bnQ7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIG1hdGNoQ291bnQ7XG4gICAgICAgIH1cblxuICAgICAgICAvKipcbiAgICAgICAgICogQSBib3R0b20gdXAgYWxnb3JpdGhtIHRoYXQgZmluZHMgYWxsIGVsZW1lbnRzIHdpdGggaWRzIGluc2lkZSBvZiB0aGUgbm9kZVxuICAgICAgICAgKiBhcmd1bWVudCBhbmQgcG9wdWxhdGVzIGlkIHNldHMgZm9yIHRob3NlIG5vZGVzIGFuZCBhbGwgdGhlaXIgcGFyZW50cywgZ2VuZXJhdGluZ1xuICAgICAgICAgKiBhIHNldCBvZiBpZHMgY29udGFpbmVkIHdpdGhpbiBhbGwgbm9kZXMgZm9yIHRoZSBlbnRpcmUgaGllcmFyY2h5IGluIHRoZSBET01cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIG5vZGUge0VsZW1lbnR9XG4gICAgICAgICAqIEBwYXJhbSB7TWFwPE5vZGUsIFNldDxTdHJpbmc+Pn0gaWRNYXBcbiAgICAgICAgICovXG4gICAgICAgIGZ1bmN0aW9uIHBvcHVsYXRlSWRNYXBGb3JOb2RlKG5vZGUsIGlkTWFwKSB7XG4gICAgICAgICAgICBsZXQgbm9kZVBhcmVudCA9IG5vZGUucGFyZW50RWxlbWVudDtcbiAgICAgICAgICAgIC8vIGZpbmQgYWxsIGVsZW1lbnRzIHdpdGggYW4gaWQgcHJvcGVydHlcbiAgICAgICAgICAgIGxldCBpZEVsZW1lbnRzID0gbm9kZS5xdWVyeVNlbGVjdG9yQWxsKCdbaWRdJyk7XG4gICAgICAgICAgICBmb3IgKGNvbnN0IGVsdCBvZiBpZEVsZW1lbnRzKSB7XG4gICAgICAgICAgICAgICAgbGV0IGN1cnJlbnQgPSBlbHQ7XG4gICAgICAgICAgICAgICAgLy8gd2FsayB1cCB0aGUgcGFyZW50IGhpZXJhcmNoeSBvZiB0aGF0IGVsZW1lbnQsIGFkZGluZyB0aGUgaWRcbiAgICAgICAgICAgICAgICAvLyBvZiBlbGVtZW50IHRvIHRoZSBwYXJlbnQncyBpZCBzZXRcbiAgICAgICAgICAgICAgICB3aGlsZSAoY3VycmVudCAhPT0gbm9kZVBhcmVudCAmJiBjdXJyZW50ICE9IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgbGV0IGlkU2V0ID0gaWRNYXAuZ2V0KGN1cnJlbnQpO1xuICAgICAgICAgICAgICAgICAgICAvLyBpZiB0aGUgaWQgc2V0IGRvZXNuJ3QgZXhpc3QsIGNyZWF0ZSBpdCBhbmQgaW5zZXJ0IGl0IGluIHRoZSAgbWFwXG4gICAgICAgICAgICAgICAgICAgIGlmIChpZFNldCA9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZFNldCA9IG5ldyBTZXQoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlkTWFwLnNldChjdXJyZW50LCBpZFNldCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgaWRTZXQuYWRkKGVsdC5pZCk7XG4gICAgICAgICAgICAgICAgICAgIGN1cnJlbnQgPSBjdXJyZW50LnBhcmVudEVsZW1lbnQ7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgLyoqXG4gICAgICAgICAqIFRoaXMgZnVuY3Rpb24gY29tcHV0ZXMgYSBtYXAgb2Ygbm9kZXMgdG8gYWxsIGlkcyBjb250YWluZWQgd2l0aGluIHRoYXQgbm9kZSAoaW5jbHVzaXZlIG9mIHRoZVxuICAgICAgICAgKiBub2RlKS4gIFRoaXMgbWFwIGNhbiBiZSB1c2VkIHRvIGFzayBpZiB0d28gbm9kZXMgaGF2ZSBpbnRlcnNlY3Rpbmcgc2V0cyBvZiBpZHMsIHdoaWNoIGFsbG93c1xuICAgICAgICAgKiBmb3IgYSBsb29zZXIgZGVmaW5pdGlvbiBvZiBcIm1hdGNoaW5nXCIgdGhhbiB0cmFkaXRpb24gaWQgbWF0Y2hpbmcsIGFuZCBhbGxvd3MgY2hpbGQgbm9kZXNcbiAgICAgICAgICogdG8gY29udHJpYnV0ZSB0byBhIHBhcmVudCBub2RlcyBtYXRjaGluZy5cbiAgICAgICAgICpcbiAgICAgICAgICogQHBhcmFtIHtFbGVtZW50fSBvbGRDb250ZW50ICB0aGUgb2xkIGNvbnRlbnQgdGhhdCB3aWxsIGJlIG1vcnBoZWRcbiAgICAgICAgICogQHBhcmFtIHtFbGVtZW50fSBuZXdDb250ZW50ICB0aGUgbmV3IGNvbnRlbnQgdG8gbW9ycGggdG9cbiAgICAgICAgICogQHJldHVybnMge01hcDxOb2RlLCBTZXQ8U3RyaW5nPj59IGEgbWFwIG9mIG5vZGVzIHRvIGlkIHNldHMgZm9yIHRoZVxuICAgICAgICAgKi9cbiAgICAgICAgZnVuY3Rpb24gY3JlYXRlSWRNYXAob2xkQ29udGVudCwgbmV3Q29udGVudCkge1xuICAgICAgICAgICAgbGV0IGlkTWFwID0gbmV3IE1hcCgpO1xuICAgICAgICAgICAgcG9wdWxhdGVJZE1hcEZvck5vZGUob2xkQ29udGVudCwgaWRNYXApO1xuICAgICAgICAgICAgcG9wdWxhdGVJZE1hcEZvck5vZGUobmV3Q29udGVudCwgaWRNYXApO1xuICAgICAgICAgICAgcmV0dXJuIGlkTWFwO1xuICAgICAgICB9XG5cbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICAvLyBUaGlzIGlzIHdoYXQgZW5kcyB1cCBiZWNvbWluZyB0aGUgSWRpb21vcnBoIGdsb2JhbCBvYmplY3RcbiAgICAgICAgLy89PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PVxuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgbW9ycGgsXG4gICAgICAgICAgICBkZWZhdWx0c1xuICAgICAgICB9XG4gICAgfSkoKTtcblxuZnVuY3Rpb24gbm9ybWFsaXplQXR0cmlidXRlc0ZvckNvbXBhcmlzb24oZWxlbWVudCkge1xuICAgIGNvbnN0IGlzRmlsZUlucHV0ID0gZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxJbnB1dEVsZW1lbnQgJiYgZWxlbWVudC50eXBlID09PSAnZmlsZSc7XG4gICAgaWYgKCFpc0ZpbGVJbnB1dCkge1xuICAgICAgICBpZiAoJ3ZhbHVlJyBpbiBlbGVtZW50KSB7XG4gICAgICAgICAgICBlbGVtZW50LnNldEF0dHJpYnV0ZSgndmFsdWUnLCBlbGVtZW50LnZhbHVlKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChlbGVtZW50Lmhhc0F0dHJpYnV0ZSgndmFsdWUnKSkge1xuICAgICAgICAgICAgZWxlbWVudC5zZXRBdHRyaWJ1dGUoJ3ZhbHVlJywgJycpO1xuICAgICAgICB9XG4gICAgfVxuICAgIEFycmF5LmZyb20oZWxlbWVudC5jaGlsZHJlbikuZm9yRWFjaCgoY2hpbGQpID0+IHtcbiAgICAgICAgbm9ybWFsaXplQXR0cmlidXRlc0ZvckNvbXBhcmlzb24oY2hpbGQpO1xuICAgIH0pO1xufVxuXG5jb25zdCBzeW5jQXR0cmlidXRlcyA9IChmcm9tRWwsIHRvRWwpID0+IHtcbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IGZyb21FbC5hdHRyaWJ1dGVzLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIGNvbnN0IGF0dHIgPSBmcm9tRWwuYXR0cmlidXRlc1tpXTtcbiAgICAgICAgdG9FbC5zZXRBdHRyaWJ1dGUoYXR0ci5uYW1lLCBhdHRyLnZhbHVlKTtcbiAgICB9XG59O1xuZnVuY3Rpb24gZXhlY3V0ZU1vcnBoZG9tKHJvb3RGcm9tRWxlbWVudCwgcm9vdFRvRWxlbWVudCwgbW9kaWZpZWRGaWVsZEVsZW1lbnRzLCBnZXRFbGVtZW50VmFsdWUsIGV4dGVybmFsTXV0YXRpb25UcmFja2VyKSB7XG4gICAgY29uc3Qgb3JpZ2luYWxFbGVtZW50SWRzVG9Td2FwQWZ0ZXIgPSBbXTtcbiAgICBjb25zdCBvcmlnaW5hbEVsZW1lbnRzVG9QcmVzZXJ2ZSA9IG5ldyBNYXAoKTtcbiAgICBjb25zdCBtYXJrRWxlbWVudEFzTmVlZGluZ1Bvc3RNb3JwaFN3YXAgPSAoaWQsIHJlcGxhY2VXaXRoQ2xvbmUpID0+IHtcbiAgICAgICAgY29uc3Qgb2xkRWxlbWVudCA9IG9yaWdpbmFsRWxlbWVudHNUb1ByZXNlcnZlLmdldChpZCk7XG4gICAgICAgIGlmICghKG9sZEVsZW1lbnQgaW5zdGFuY2VvZiBIVE1MRWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgT3JpZ2luYWwgZWxlbWVudCB3aXRoIGlkICR7aWR9IG5vdCBmb3VuZGApO1xuICAgICAgICB9XG4gICAgICAgIG9yaWdpbmFsRWxlbWVudElkc1RvU3dhcEFmdGVyLnB1c2goaWQpO1xuICAgICAgICBpZiAoIXJlcGxhY2VXaXRoQ2xvbmUpIHtcbiAgICAgICAgICAgIHJldHVybiBudWxsO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGNsb25lZE9sZEVsZW1lbnQgPSBjbG9uZUhUTUxFbGVtZW50KG9sZEVsZW1lbnQpO1xuICAgICAgICBvbGRFbGVtZW50LnJlcGxhY2VXaXRoKGNsb25lZE9sZEVsZW1lbnQpO1xuICAgICAgICByZXR1cm4gY2xvbmVkT2xkRWxlbWVudDtcbiAgICB9O1xuICAgIHJvb3RUb0VsZW1lbnQucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbGl2ZS1wcmVzZXJ2ZV0nKS5mb3JFYWNoKChuZXdFbGVtZW50KSA9PiB7XG4gICAgICAgIGNvbnN0IGlkID0gbmV3RWxlbWVudC5pZDtcbiAgICAgICAgaWYgKCFpZCkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdUaGUgZGF0YS1saXZlLXByZXNlcnZlIGF0dHJpYnV0ZSByZXF1aXJlcyBhbiBpZCBhdHRyaWJ1dGUgdG8gYmUgc2V0IG9uIHRoZSBlbGVtZW50Jyk7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3Qgb2xkRWxlbWVudCA9IHJvb3RGcm9tRWxlbWVudC5xdWVyeVNlbGVjdG9yKGAjJHtpZH1gKTtcbiAgICAgICAgaWYgKCEob2xkRWxlbWVudCBpbnN0YW5jZW9mIEhUTUxFbGVtZW50KSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgZWxlbWVudCB3aXRoIGlkIFwiJHtpZH1cIiB3YXMgbm90IGZvdW5kIGluIHRoZSBvcmlnaW5hbCBIVE1MYCk7XG4gICAgICAgIH1cbiAgICAgICAgbmV3RWxlbWVudC5yZW1vdmVBdHRyaWJ1dGUoJ2RhdGEtbGl2ZS1wcmVzZXJ2ZScpO1xuICAgICAgICBvcmlnaW5hbEVsZW1lbnRzVG9QcmVzZXJ2ZS5zZXQoaWQsIG9sZEVsZW1lbnQpO1xuICAgICAgICBzeW5jQXR0cmlidXRlcyhuZXdFbGVtZW50LCBvbGRFbGVtZW50KTtcbiAgICB9KTtcbiAgICBJZGlvbW9ycGgubW9ycGgocm9vdEZyb21FbGVtZW50LCByb290VG9FbGVtZW50LCB7XG4gICAgICAgIGNhbGxiYWNrczoge1xuICAgICAgICAgICAgYmVmb3JlTm9kZU1vcnBoZWQ6IChmcm9tRWwsIHRvRWwpID0+IHtcbiAgICAgICAgICAgICAgICBpZiAoIShmcm9tRWwgaW5zdGFuY2VvZiBFbGVtZW50KSB8fCAhKHRvRWwgaW5zdGFuY2VvZiBFbGVtZW50KSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKGZyb21FbCA9PT0gcm9vdEZyb21FbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoZnJvbUVsLmlkICYmIG9yaWdpbmFsRWxlbWVudHNUb1ByZXNlcnZlLmhhcyhmcm9tRWwuaWQpKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChmcm9tRWwuaWQgPT09IHRvRWwuaWQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBjb25zdCBjbG9uZWRGcm9tRWwgPSBtYXJrRWxlbWVudEFzTmVlZGluZ1Bvc3RNb3JwaFN3YXAoZnJvbUVsLmlkLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCFjbG9uZWRGcm9tRWwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignbWlzc2luZyBjbG9uZScpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIElkaW9tb3JwaC5tb3JwaChjbG9uZWRGcm9tRWwsIHRvRWwpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChmcm9tRWwgaW5zdGFuY2VvZiBIVE1MRWxlbWVudCAmJiB0b0VsIGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBmcm9tRWwuX194ICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCF3aW5kb3cuQWxwaW5lKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdVbmFibGUgdG8gYWNjZXNzIEFscGluZS5qcyB0aG91Z2ggdGhlIGdsb2JhbCB3aW5kb3cuQWxwaW5lIHZhcmlhYmxlLiBQbGVhc2UgbWFrZSBzdXJlIEFscGluZS5qcyBpcyBsb2FkZWQgYmVmb3JlIFN5bWZvbnkgVVggTGl2ZUNvbXBvbmVudC4nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2Ygd2luZG93LkFscGluZS5tb3JwaCAhPT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignVW5hYmxlIHRvIGFjY2VzcyBBbHBpbmUuanMgbW9ycGggZnVuY3Rpb24uIFBsZWFzZSBtYWtlIHN1cmUgdGhlIEFscGluZS5qcyBNb3JwaCBwbHVnaW4gaXMgaW5zdGFsbGVkIGFuZCBsb2FkZWQsIHNlZSBodHRwczovL2FscGluZWpzLmRldi9wbHVnaW5zL21vcnBoIGZvciBtb3JlIGluZm9ybWF0aW9uLicpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LkFscGluZS5tb3JwaChmcm9tRWwuX194LCB0b0VsKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBpZiAoZXh0ZXJuYWxNdXRhdGlvblRyYWNrZXIud2FzRWxlbWVudEFkZGVkKGZyb21FbCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGZyb21FbC5pbnNlcnRBZGphY2VudEVsZW1lbnQoJ2FmdGVyZW5kJywgdG9FbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKG1vZGlmaWVkRmllbGRFbGVtZW50cy5pbmNsdWRlcyhmcm9tRWwpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZXRWYWx1ZU9uRWxlbWVudCh0b0VsLCBnZXRFbGVtZW50VmFsdWUoZnJvbUVsKSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgaWYgKGZyb21FbCA9PT0gZG9jdW1lbnQuYWN0aXZlRWxlbWVudCAmJlxuICAgICAgICAgICAgICAgICAgICAgICAgZnJvbUVsICE9PSBkb2N1bWVudC5ib2R5ICYmXG4gICAgICAgICAgICAgICAgICAgICAgICBudWxsICE9PSBnZXRNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50KGZyb21FbCwgZmFsc2UpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzZXRWYWx1ZU9uRWxlbWVudCh0b0VsLCBnZXRFbGVtZW50VmFsdWUoZnJvbUVsKSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgY29uc3QgZWxlbWVudENoYW5nZXMgPSBleHRlcm5hbE11dGF0aW9uVHJhY2tlci5nZXRDaGFuZ2VkRWxlbWVudChmcm9tRWwpO1xuICAgICAgICAgICAgICAgICAgICBpZiAoZWxlbWVudENoYW5nZXMpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGVsZW1lbnRDaGFuZ2VzLmFwcGx5VG9FbGVtZW50KHRvRWwpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIGlmIChmcm9tRWwubm9kZU5hbWUudG9VcHBlckNhc2UoKSAhPT0gJ09QVElPTicgJiYgZnJvbUVsLmlzRXF1YWxOb2RlKHRvRWwpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBub3JtYWxpemVkRnJvbUVsID0gY2xvbmVIVE1MRWxlbWVudChmcm9tRWwpO1xuICAgICAgICAgICAgICAgICAgICAgICAgbm9ybWFsaXplQXR0cmlidXRlc0ZvckNvbXBhcmlzb24obm9ybWFsaXplZEZyb21FbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBub3JtYWxpemVkVG9FbCA9IGNsb25lSFRNTEVsZW1lbnQodG9FbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBub3JtYWxpemVBdHRyaWJ1dGVzRm9yQ29tcGFyaXNvbihub3JtYWxpemVkVG9FbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAobm9ybWFsaXplZEZyb21FbC5pc0VxdWFsTm9kZShub3JtYWxpemVkVG9FbCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKGZyb21FbC5oYXNBdHRyaWJ1dGUoJ2RhdGEtc2tpcC1tb3JwaCcpIHx8IChmcm9tRWwuaWQgJiYgZnJvbUVsLmlkICE9PSB0b0VsLmlkKSkge1xuICAgICAgICAgICAgICAgICAgICBmcm9tRWwuaW5uZXJIVE1MID0gdG9FbC5pbm5lckhUTUw7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoZnJvbUVsLnBhcmVudEVsZW1lbnQ/Lmhhc0F0dHJpYnV0ZSgnZGF0YS1za2lwLW1vcnBoJykpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICByZXR1cm4gIWZyb21FbC5oYXNBdHRyaWJ1dGUoJ2RhdGEtbGl2ZS1pZ25vcmUnKTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBiZWZvcmVOb2RlUmVtb3ZlZChub2RlKSB7XG4gICAgICAgICAgICAgICAgaWYgKCEobm9kZSBpbnN0YW5jZW9mIEhUTUxFbGVtZW50KSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKG5vZGUuaWQgJiYgb3JpZ2luYWxFbGVtZW50c1RvUHJlc2VydmUuaGFzKG5vZGUuaWQpKSB7XG4gICAgICAgICAgICAgICAgICAgIG1hcmtFbGVtZW50QXNOZWVkaW5nUG9zdE1vcnBoU3dhcChub2RlLmlkLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoZXh0ZXJuYWxNdXRhdGlvblRyYWNrZXIud2FzRWxlbWVudEFkZGVkKG5vZGUpKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgcmV0dXJuICFub2RlLmhhc0F0dHJpYnV0ZSgnZGF0YS1saXZlLWlnbm9yZScpO1xuICAgICAgICAgICAgfSxcbiAgICAgICAgfSxcbiAgICB9KTtcbiAgICBvcmlnaW5hbEVsZW1lbnRJZHNUb1N3YXBBZnRlci5mb3JFYWNoKChpZCkgPT4ge1xuICAgICAgICBjb25zdCBuZXdFbGVtZW50ID0gcm9vdEZyb21FbGVtZW50LnF1ZXJ5U2VsZWN0b3IoYCMke2lkfWApO1xuICAgICAgICBjb25zdCBvcmlnaW5hbEVsZW1lbnQgPSBvcmlnaW5hbEVsZW1lbnRzVG9QcmVzZXJ2ZS5nZXQoaWQpO1xuICAgICAgICBpZiAoIShuZXdFbGVtZW50IGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpIHx8ICEob3JpZ2luYWxFbGVtZW50IGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ01pc3NpbmcgZWxlbWVudHMuJyk7XG4gICAgICAgIH1cbiAgICAgICAgbmV3RWxlbWVudC5yZXBsYWNlV2l0aChvcmlnaW5hbEVsZW1lbnQpO1xuICAgIH0pO1xufVxuXG5jbGFzcyBVbnN5bmNlZElucHV0c1RyYWNrZXIge1xuICAgIGNvbnN0cnVjdG9yKGNvbXBvbmVudCwgbW9kZWxFbGVtZW50UmVzb2x2ZXIpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50RXZlbnRMaXN0ZW5lcnMgPSBbXG4gICAgICAgICAgICB7IGV2ZW50OiAnaW5wdXQnLCBjYWxsYmFjazogKGV2ZW50KSA9PiB0aGlzLmhhbmRsZUlucHV0RXZlbnQoZXZlbnQpIH0sXG4gICAgICAgIF07XG4gICAgICAgIHRoaXMuY29tcG9uZW50ID0gY29tcG9uZW50O1xuICAgICAgICB0aGlzLm1vZGVsRWxlbWVudFJlc29sdmVyID0gbW9kZWxFbGVtZW50UmVzb2x2ZXI7XG4gICAgICAgIHRoaXMudW5zeW5jZWRJbnB1dHMgPSBuZXcgVW5zeW5jZWRJbnB1dENvbnRhaW5lcigpO1xuICAgIH1cbiAgICBhY3RpdmF0ZSgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50RXZlbnRMaXN0ZW5lcnMuZm9yRWFjaCgoeyBldmVudCwgY2FsbGJhY2sgfSkgPT4ge1xuICAgICAgICAgICAgdGhpcy5jb21wb25lbnQuZWxlbWVudC5hZGRFdmVudExpc3RlbmVyKGV2ZW50LCBjYWxsYmFjayk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBkZWFjdGl2YXRlKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRFdmVudExpc3RlbmVycy5mb3JFYWNoKCh7IGV2ZW50LCBjYWxsYmFjayB9KSA9PiB7XG4gICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5lbGVtZW50LnJlbW92ZUV2ZW50TGlzdGVuZXIoZXZlbnQsIGNhbGxiYWNrKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIG1hcmtNb2RlbEFzU3luY2VkKG1vZGVsTmFtZSkge1xuICAgICAgICB0aGlzLnVuc3luY2VkSW5wdXRzLm1hcmtNb2RlbEFzU3luY2VkKG1vZGVsTmFtZSk7XG4gICAgfVxuICAgIGhhbmRsZUlucHV0RXZlbnQoZXZlbnQpIHtcbiAgICAgICAgY29uc3QgdGFyZ2V0ID0gZXZlbnQudGFyZ2V0O1xuICAgICAgICBpZiAoIXRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMudXBkYXRlTW9kZWxGcm9tRWxlbWVudCh0YXJnZXQpO1xuICAgIH1cbiAgICB1cGRhdGVNb2RlbEZyb21FbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgaWYgKCFlbGVtZW50QmVsb25nc1RvVGhpc0NvbXBvbmVudChlbGVtZW50LCB0aGlzLmNvbXBvbmVudCkpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBpZiAoIShlbGVtZW50IGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0NvdWxkIG5vdCB1cGRhdGUgbW9kZWwgZm9yIG5vbiBIVE1MRWxlbWVudCcpO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IG1vZGVsTmFtZSA9IHRoaXMubW9kZWxFbGVtZW50UmVzb2x2ZXIuZ2V0TW9kZWxOYW1lKGVsZW1lbnQpO1xuICAgICAgICB0aGlzLnVuc3luY2VkSW5wdXRzLmFkZChlbGVtZW50LCBtb2RlbE5hbWUpO1xuICAgIH1cbiAgICBnZXRVbnN5bmNlZElucHV0cygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMudW5zeW5jZWRJbnB1dHMuYWxsVW5zeW5jZWRJbnB1dHMoKTtcbiAgICB9XG4gICAgZ2V0VW5zeW5jZWRNb2RlbHMoKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMudW5zeW5jZWRJbnB1dHMuZ2V0VW5zeW5jZWRNb2RlbE5hbWVzKCkpO1xuICAgIH1cbiAgICByZXNldFVuc3luY2VkRmllbGRzKCkge1xuICAgICAgICB0aGlzLnVuc3luY2VkSW5wdXRzLnJlc2V0VW5zeW5jZWRGaWVsZHMoKTtcbiAgICB9XG59XG5jbGFzcyBVbnN5bmNlZElucHV0Q29udGFpbmVyIHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgdGhpcy51bnN5bmNlZE5vbk1vZGVsRmllbGRzID0gW107XG4gICAgICAgIHRoaXMudW5zeW5jZWRNb2RlbE5hbWVzID0gW107XG4gICAgICAgIHRoaXMudW5zeW5jZWRNb2RlbEZpZWxkcyA9IG5ldyBNYXAoKTtcbiAgICB9XG4gICAgYWRkKGVsZW1lbnQsIG1vZGVsTmFtZSA9IG51bGwpIHtcbiAgICAgICAgaWYgKG1vZGVsTmFtZSkge1xuICAgICAgICAgICAgdGhpcy51bnN5bmNlZE1vZGVsRmllbGRzLnNldChtb2RlbE5hbWUsIGVsZW1lbnQpO1xuICAgICAgICAgICAgaWYgKCF0aGlzLnVuc3luY2VkTW9kZWxOYW1lcy5pbmNsdWRlcyhtb2RlbE5hbWUpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy51bnN5bmNlZE1vZGVsTmFtZXMucHVzaChtb2RlbE5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMudW5zeW5jZWROb25Nb2RlbEZpZWxkcy5wdXNoKGVsZW1lbnQpO1xuICAgIH1cbiAgICByZXNldFVuc3luY2VkRmllbGRzKCkge1xuICAgICAgICB0aGlzLnVuc3luY2VkTW9kZWxGaWVsZHMuZm9yRWFjaCgodmFsdWUsIGtleSkgPT4ge1xuICAgICAgICAgICAgaWYgKCF0aGlzLnVuc3luY2VkTW9kZWxOYW1lcy5pbmNsdWRlcyhrZXkpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy51bnN5bmNlZE1vZGVsRmllbGRzLmRlbGV0ZShrZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgYWxsVW5zeW5jZWRJbnB1dHMoKSB7XG4gICAgICAgIHJldHVybiBbLi4udGhpcy51bnN5bmNlZE5vbk1vZGVsRmllbGRzLCAuLi50aGlzLnVuc3luY2VkTW9kZWxGaWVsZHMudmFsdWVzKCldO1xuICAgIH1cbiAgICBtYXJrTW9kZWxBc1N5bmNlZChtb2RlbE5hbWUpIHtcbiAgICAgICAgY29uc3QgaW5kZXggPSB0aGlzLnVuc3luY2VkTW9kZWxOYW1lcy5pbmRleE9mKG1vZGVsTmFtZSk7XG4gICAgICAgIGlmIChpbmRleCAhPT0gLTEpIHtcbiAgICAgICAgICAgIHRoaXMudW5zeW5jZWRNb2RlbE5hbWVzLnNwbGljZShpbmRleCwgMSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0VW5zeW5jZWRNb2RlbE5hbWVzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy51bnN5bmNlZE1vZGVsTmFtZXM7XG4gICAgfVxufVxuXG5mdW5jdGlvbiBnZXREZWVwRGF0YShkYXRhLCBwcm9wZXJ0eVBhdGgpIHtcbiAgICBjb25zdCB7IGN1cnJlbnRMZXZlbERhdGEsIGZpbmFsS2V5IH0gPSBwYXJzZURlZXBEYXRhKGRhdGEsIHByb3BlcnR5UGF0aCk7XG4gICAgaWYgKGN1cnJlbnRMZXZlbERhdGEgPT09IHVuZGVmaW5lZCkge1xuICAgICAgICByZXR1cm4gdW5kZWZpbmVkO1xuICAgIH1cbiAgICByZXR1cm4gY3VycmVudExldmVsRGF0YVtmaW5hbEtleV07XG59XG5jb25zdCBwYXJzZURlZXBEYXRhID0gKGRhdGEsIHByb3BlcnR5UGF0aCkgPT4ge1xuICAgIGNvbnN0IGZpbmFsRGF0YSA9IEpTT04ucGFyc2UoSlNPTi5zdHJpbmdpZnkoZGF0YSkpO1xuICAgIGxldCBjdXJyZW50TGV2ZWxEYXRhID0gZmluYWxEYXRhO1xuICAgIGNvbnN0IHBhcnRzID0gcHJvcGVydHlQYXRoLnNwbGl0KCcuJyk7XG4gICAgZm9yIChsZXQgaSA9IDA7IGkgPCBwYXJ0cy5sZW5ndGggLSAxOyBpKyspIHtcbiAgICAgICAgY3VycmVudExldmVsRGF0YSA9IGN1cnJlbnRMZXZlbERhdGFbcGFydHNbaV1dO1xuICAgIH1cbiAgICBjb25zdCBmaW5hbEtleSA9IHBhcnRzW3BhcnRzLmxlbmd0aCAtIDFdO1xuICAgIHJldHVybiB7XG4gICAgICAgIGN1cnJlbnRMZXZlbERhdGEsXG4gICAgICAgIGZpbmFsRGF0YSxcbiAgICAgICAgZmluYWxLZXksXG4gICAgICAgIHBhcnRzLFxuICAgIH07XG59O1xuXG5jbGFzcyBWYWx1ZVN0b3JlIHtcbiAgICBjb25zdHJ1Y3Rvcihwcm9wcykge1xuICAgICAgICB0aGlzLnByb3BzID0ge307XG4gICAgICAgIHRoaXMuZGlydHlQcm9wcyA9IHt9O1xuICAgICAgICB0aGlzLnBlbmRpbmdQcm9wcyA9IHt9O1xuICAgICAgICB0aGlzLnVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQgPSB7fTtcbiAgICAgICAgdGhpcy5wcm9wcyA9IHByb3BzO1xuICAgIH1cbiAgICBnZXQobmFtZSkge1xuICAgICAgICBjb25zdCBub3JtYWxpemVkTmFtZSA9IG5vcm1hbGl6ZU1vZGVsTmFtZShuYW1lKTtcbiAgICAgICAgaWYgKHRoaXMuZGlydHlQcm9wc1tub3JtYWxpemVkTmFtZV0gIT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZGlydHlQcm9wc1tub3JtYWxpemVkTmFtZV07XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMucGVuZGluZ1Byb3BzW25vcm1hbGl6ZWROYW1lXSAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5wZW5kaW5nUHJvcHNbbm9ybWFsaXplZE5hbWVdO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLnByb3BzW25vcm1hbGl6ZWROYW1lXSAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5wcm9wc1tub3JtYWxpemVkTmFtZV07XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGdldERlZXBEYXRhKHRoaXMucHJvcHMsIG5vcm1hbGl6ZWROYW1lKTtcbiAgICB9XG4gICAgaGFzKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0KG5hbWUpICE9PSB1bmRlZmluZWQ7XG4gICAgfVxuICAgIHNldChuYW1lLCB2YWx1ZSkge1xuICAgICAgICBjb25zdCBub3JtYWxpemVkTmFtZSA9IG5vcm1hbGl6ZU1vZGVsTmFtZShuYW1lKTtcbiAgICAgICAgaWYgKHRoaXMuZ2V0KG5vcm1hbGl6ZWROYW1lKSA9PT0gdmFsdWUpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLmRpcnR5UHJvcHNbbm9ybWFsaXplZE5hbWVdID0gdmFsdWU7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgICBnZXRPcmlnaW5hbFByb3BzKCkge1xuICAgICAgICByZXR1cm4geyAuLi50aGlzLnByb3BzIH07XG4gICAgfVxuICAgIGdldERpcnR5UHJvcHMoKSB7XG4gICAgICAgIHJldHVybiB7IC4uLnRoaXMuZGlydHlQcm9wcyB9O1xuICAgIH1cbiAgICBnZXRVcGRhdGVkUHJvcHNGcm9tUGFyZW50KCkge1xuICAgICAgICByZXR1cm4geyAuLi50aGlzLnVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQgfTtcbiAgICB9XG4gICAgZmx1c2hEaXJ0eVByb3BzVG9QZW5kaW5nKCkge1xuICAgICAgICB0aGlzLnBlbmRpbmdQcm9wcyA9IHsgLi4udGhpcy5kaXJ0eVByb3BzIH07XG4gICAgICAgIHRoaXMuZGlydHlQcm9wcyA9IHt9O1xuICAgIH1cbiAgICByZWluaXRpYWxpemVBbGxQcm9wcyhwcm9wcykge1xuICAgICAgICB0aGlzLnByb3BzID0gcHJvcHM7XG4gICAgICAgIHRoaXMudXBkYXRlZFByb3BzRnJvbVBhcmVudCA9IHt9O1xuICAgICAgICB0aGlzLnBlbmRpbmdQcm9wcyA9IHt9O1xuICAgIH1cbiAgICBwdXNoUGVuZGluZ1Byb3BzQmFja1RvRGlydHkoKSB7XG4gICAgICAgIHRoaXMuZGlydHlQcm9wcyA9IHsgLi4udGhpcy5wZW5kaW5nUHJvcHMsIC4uLnRoaXMuZGlydHlQcm9wcyB9O1xuICAgICAgICB0aGlzLnBlbmRpbmdQcm9wcyA9IHt9O1xuICAgIH1cbiAgICBzdG9yZU5ld1Byb3BzRnJvbVBhcmVudChwcm9wcykge1xuICAgICAgICBsZXQgY2hhbmdlZCA9IGZhbHNlO1xuICAgICAgICBmb3IgKGNvbnN0IFtrZXksIHZhbHVlXSBvZiBPYmplY3QuZW50cmllcyhwcm9wcykpIHtcbiAgICAgICAgICAgIGNvbnN0IGN1cnJlbnRWYWx1ZSA9IHRoaXMuZ2V0KGtleSk7XG4gICAgICAgICAgICBpZiAoY3VycmVudFZhbHVlICE9PSB2YWx1ZSkge1xuICAgICAgICAgICAgICAgIGNoYW5nZWQgPSB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGlmIChjaGFuZ2VkKSB7XG4gICAgICAgICAgICB0aGlzLnVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQgPSBwcm9wcztcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gY2hhbmdlZDtcbiAgICB9XG59XG5cbmNsYXNzIENvbXBvbmVudCB7XG4gICAgY29uc3RydWN0b3IoZWxlbWVudCwgbmFtZSwgcHJvcHMsIGxpc3RlbmVycywgaWQsIGJhY2tlbmQsIGVsZW1lbnREcml2ZXIpIHtcbiAgICAgICAgdGhpcy5maW5nZXJwcmludCA9ICcnO1xuICAgICAgICB0aGlzLmRlZmF1bHREZWJvdW5jZSA9IDE1MDtcbiAgICAgICAgdGhpcy5iYWNrZW5kUmVxdWVzdCA9IG51bGw7XG4gICAgICAgIHRoaXMucGVuZGluZ0FjdGlvbnMgPSBbXTtcbiAgICAgICAgdGhpcy5wZW5kaW5nRmlsZXMgPSB7fTtcbiAgICAgICAgdGhpcy5pc1JlcXVlc3RQZW5kaW5nID0gZmFsc2U7XG4gICAgICAgIHRoaXMucmVxdWVzdERlYm91bmNlVGltZW91dCA9IG51bGw7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMubmFtZSA9IG5hbWU7XG4gICAgICAgIHRoaXMuYmFja2VuZCA9IGJhY2tlbmQ7XG4gICAgICAgIHRoaXMuZWxlbWVudERyaXZlciA9IGVsZW1lbnREcml2ZXI7XG4gICAgICAgIHRoaXMuaWQgPSBpZDtcbiAgICAgICAgdGhpcy5saXN0ZW5lcnMgPSBuZXcgTWFwKCk7XG4gICAgICAgIGxpc3RlbmVycy5mb3JFYWNoKChsaXN0ZW5lcikgPT4ge1xuICAgICAgICAgICAgaWYgKCF0aGlzLmxpc3RlbmVycy5oYXMobGlzdGVuZXIuZXZlbnQpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5saXN0ZW5lcnMuc2V0KGxpc3RlbmVyLmV2ZW50LCBbXSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLmxpc3RlbmVycy5nZXQobGlzdGVuZXIuZXZlbnQpPy5wdXNoKGxpc3RlbmVyLmFjdGlvbik7XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLnZhbHVlU3RvcmUgPSBuZXcgVmFsdWVTdG9yZShwcm9wcyk7XG4gICAgICAgIHRoaXMudW5zeW5jZWRJbnB1dHNUcmFja2VyID0gbmV3IFVuc3luY2VkSW5wdXRzVHJhY2tlcih0aGlzLCBlbGVtZW50RHJpdmVyKTtcbiAgICAgICAgdGhpcy5ob29rcyA9IG5ldyBIb29rTWFuYWdlcigpO1xuICAgICAgICB0aGlzLnJlc2V0UHJvbWlzZSgpO1xuICAgICAgICB0aGlzLmV4dGVybmFsTXV0YXRpb25UcmFja2VyID0gbmV3IEV4dGVybmFsTXV0YXRpb25UcmFja2VyKHRoaXMuZWxlbWVudCwgKGVsZW1lbnQpID0+IGVsZW1lbnRCZWxvbmdzVG9UaGlzQ29tcG9uZW50KGVsZW1lbnQsIHRoaXMpKTtcbiAgICAgICAgdGhpcy5leHRlcm5hbE11dGF0aW9uVHJhY2tlci5zdGFydCgpO1xuICAgIH1cbiAgICBhZGRQbHVnaW4ocGx1Z2luKSB7XG4gICAgICAgIHBsdWdpbi5hdHRhY2hUb0NvbXBvbmVudCh0aGlzKTtcbiAgICB9XG4gICAgY29ubmVjdCgpIHtcbiAgICAgICAgcmVnaXN0ZXJDb21wb25lbnQodGhpcyk7XG4gICAgICAgIHRoaXMuaG9va3MudHJpZ2dlckhvb2soJ2Nvbm5lY3QnLCB0aGlzKTtcbiAgICAgICAgdGhpcy51bnN5bmNlZElucHV0c1RyYWNrZXIuYWN0aXZhdGUoKTtcbiAgICAgICAgdGhpcy5leHRlcm5hbE11dGF0aW9uVHJhY2tlci5zdGFydCgpO1xuICAgIH1cbiAgICBkaXNjb25uZWN0KCkge1xuICAgICAgICB1bnJlZ2lzdGVyQ29tcG9uZW50KHRoaXMpO1xuICAgICAgICB0aGlzLmhvb2tzLnRyaWdnZXJIb29rKCdkaXNjb25uZWN0JywgdGhpcyk7XG4gICAgICAgIHRoaXMuY2xlYXJSZXF1ZXN0RGVib3VuY2VUaW1lb3V0KCk7XG4gICAgICAgIHRoaXMudW5zeW5jZWRJbnB1dHNUcmFja2VyLmRlYWN0aXZhdGUoKTtcbiAgICAgICAgdGhpcy5leHRlcm5hbE11dGF0aW9uVHJhY2tlci5zdG9wKCk7XG4gICAgfVxuICAgIG9uKGhvb2tOYW1lLCBjYWxsYmFjaykge1xuICAgICAgICB0aGlzLmhvb2tzLnJlZ2lzdGVyKGhvb2tOYW1lLCBjYWxsYmFjayk7XG4gICAgfVxuICAgIG9mZihob29rTmFtZSwgY2FsbGJhY2spIHtcbiAgICAgICAgdGhpcy5ob29rcy51bnJlZ2lzdGVyKGhvb2tOYW1lLCBjYWxsYmFjayk7XG4gICAgfVxuICAgIHNldChtb2RlbCwgdmFsdWUsIHJlUmVuZGVyID0gZmFsc2UsIGRlYm91bmNlID0gZmFsc2UpIHtcbiAgICAgICAgY29uc3QgcHJvbWlzZSA9IHRoaXMubmV4dFJlcXVlc3RQcm9taXNlO1xuICAgICAgICBjb25zdCBtb2RlbE5hbWUgPSBub3JtYWxpemVNb2RlbE5hbWUobW9kZWwpO1xuICAgICAgICBpZiAoIXRoaXMudmFsdWVTdG9yZS5oYXMobW9kZWxOYW1lKSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBJbnZhbGlkIG1vZGVsIG5hbWUgXCIke21vZGVsfVwiLmApO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGlzQ2hhbmdlZCA9IHRoaXMudmFsdWVTdG9yZS5zZXQobW9kZWxOYW1lLCB2YWx1ZSk7XG4gICAgICAgIHRoaXMuaG9va3MudHJpZ2dlckhvb2soJ21vZGVsOnNldCcsIG1vZGVsLCB2YWx1ZSwgdGhpcyk7XG4gICAgICAgIHRoaXMudW5zeW5jZWRJbnB1dHNUcmFja2VyLm1hcmtNb2RlbEFzU3luY2VkKG1vZGVsTmFtZSk7XG4gICAgICAgIGlmIChyZVJlbmRlciAmJiBpc0NoYW5nZWQpIHtcbiAgICAgICAgICAgIHRoaXMuZGVib3VuY2VkU3RhcnRSZXF1ZXN0KGRlYm91bmNlKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gcHJvbWlzZTtcbiAgICB9XG4gICAgZ2V0RGF0YShtb2RlbCkge1xuICAgICAgICBjb25zdCBtb2RlbE5hbWUgPSBub3JtYWxpemVNb2RlbE5hbWUobW9kZWwpO1xuICAgICAgICBpZiAoIXRoaXMudmFsdWVTdG9yZS5oYXMobW9kZWxOYW1lKSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBJbnZhbGlkIG1vZGVsIFwiJHttb2RlbH1cIi5gKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGhpcy52YWx1ZVN0b3JlLmdldChtb2RlbE5hbWUpO1xuICAgIH1cbiAgICBhY3Rpb24obmFtZSwgYXJncyA9IHt9LCBkZWJvdW5jZSA9IGZhbHNlKSB7XG4gICAgICAgIGNvbnN0IHByb21pc2UgPSB0aGlzLm5leHRSZXF1ZXN0UHJvbWlzZTtcbiAgICAgICAgdGhpcy5wZW5kaW5nQWN0aW9ucy5wdXNoKHtcbiAgICAgICAgICAgIG5hbWUsXG4gICAgICAgICAgICBhcmdzLFxuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5kZWJvdW5jZWRTdGFydFJlcXVlc3QoZGVib3VuY2UpO1xuICAgICAgICByZXR1cm4gcHJvbWlzZTtcbiAgICB9XG4gICAgZmlsZXMoa2V5LCBpbnB1dCkge1xuICAgICAgICB0aGlzLnBlbmRpbmdGaWxlc1trZXldID0gaW5wdXQ7XG4gICAgfVxuICAgIHJlbmRlcigpIHtcbiAgICAgICAgY29uc3QgcHJvbWlzZSA9IHRoaXMubmV4dFJlcXVlc3RQcm9taXNlO1xuICAgICAgICB0aGlzLnRyeVN0YXJ0aW5nUmVxdWVzdCgpO1xuICAgICAgICByZXR1cm4gcHJvbWlzZTtcbiAgICB9XG4gICAgZ2V0VW5zeW5jZWRNb2RlbHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnVuc3luY2VkSW5wdXRzVHJhY2tlci5nZXRVbnN5bmNlZE1vZGVscygpO1xuICAgIH1cbiAgICBlbWl0KG5hbWUsIGRhdGEsIG9ubHlNYXRjaGluZ0NvbXBvbmVudHNOYW1lZCA9IG51bGwpIHtcbiAgICAgICAgdGhpcy5wZXJmb3JtRW1pdChuYW1lLCBkYXRhLCBmYWxzZSwgb25seU1hdGNoaW5nQ29tcG9uZW50c05hbWVkKTtcbiAgICB9XG4gICAgZW1pdFVwKG5hbWUsIGRhdGEsIG9ubHlNYXRjaGluZ0NvbXBvbmVudHNOYW1lZCA9IG51bGwpIHtcbiAgICAgICAgdGhpcy5wZXJmb3JtRW1pdChuYW1lLCBkYXRhLCB0cnVlLCBvbmx5TWF0Y2hpbmdDb21wb25lbnRzTmFtZWQpO1xuICAgIH1cbiAgICBlbWl0U2VsZihuYW1lLCBkYXRhKSB7XG4gICAgICAgIHRoaXMuZG9FbWl0KG5hbWUsIGRhdGEpO1xuICAgIH1cbiAgICBwZXJmb3JtRW1pdChuYW1lLCBkYXRhLCBlbWl0VXAsIG1hdGNoaW5nTmFtZSkge1xuICAgICAgICBjb25zdCBjb21wb25lbnRzID0gZmluZENvbXBvbmVudHModGhpcywgZW1pdFVwLCBtYXRjaGluZ05hbWUpO1xuICAgICAgICBjb21wb25lbnRzLmZvckVhY2goKGNvbXBvbmVudCkgPT4ge1xuICAgICAgICAgICAgY29tcG9uZW50LmRvRW1pdChuYW1lLCBkYXRhKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGRvRW1pdChuYW1lLCBkYXRhKSB7XG4gICAgICAgIGlmICghdGhpcy5saXN0ZW5lcnMuaGFzKG5hbWUpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgYWN0aW9ucyA9IHRoaXMubGlzdGVuZXJzLmdldChuYW1lKSB8fCBbXTtcbiAgICAgICAgYWN0aW9ucy5mb3JFYWNoKChhY3Rpb24pID0+IHtcbiAgICAgICAgICAgIHRoaXMuYWN0aW9uKGFjdGlvbiwgZGF0YSwgMSk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBpc1R1cmJvRW5hYmxlZCgpIHtcbiAgICAgICAgcmV0dXJuIHR5cGVvZiBUdXJibyAhPT0gJ3VuZGVmaW5lZCcgJiYgIXRoaXMuZWxlbWVudC5jbG9zZXN0KCdbZGF0YS10dXJibz1cImZhbHNlXCJdJyk7XG4gICAgfVxuICAgIHRyeVN0YXJ0aW5nUmVxdWVzdCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLmJhY2tlbmRSZXF1ZXN0KSB7XG4gICAgICAgICAgICB0aGlzLnBlcmZvcm1SZXF1ZXN0KCk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5pc1JlcXVlc3RQZW5kaW5nID0gdHJ1ZTtcbiAgICB9XG4gICAgcGVyZm9ybVJlcXVlc3QoKSB7XG4gICAgICAgIGNvbnN0IHRoaXNQcm9taXNlUmVzb2x2ZSA9IHRoaXMubmV4dFJlcXVlc3RQcm9taXNlUmVzb2x2ZTtcbiAgICAgICAgdGhpcy5yZXNldFByb21pc2UoKTtcbiAgICAgICAgdGhpcy51bnN5bmNlZElucHV0c1RyYWNrZXIucmVzZXRVbnN5bmNlZEZpZWxkcygpO1xuICAgICAgICBjb25zdCBmaWxlc1RvU2VuZCA9IHt9O1xuICAgICAgICBmb3IgKGNvbnN0IFtrZXksIHZhbHVlXSBvZiBPYmplY3QuZW50cmllcyh0aGlzLnBlbmRpbmdGaWxlcykpIHtcbiAgICAgICAgICAgIGlmICh2YWx1ZS5maWxlcykge1xuICAgICAgICAgICAgICAgIGZpbGVzVG9TZW5kW2tleV0gPSB2YWx1ZS5maWxlcztcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBjb25zdCByZXF1ZXN0Q29uZmlnID0ge1xuICAgICAgICAgICAgcHJvcHM6IHRoaXMudmFsdWVTdG9yZS5nZXRPcmlnaW5hbFByb3BzKCksXG4gICAgICAgICAgICBhY3Rpb25zOiB0aGlzLnBlbmRpbmdBY3Rpb25zLFxuICAgICAgICAgICAgdXBkYXRlZDogdGhpcy52YWx1ZVN0b3JlLmdldERpcnR5UHJvcHMoKSxcbiAgICAgICAgICAgIGNoaWxkcmVuOiB7fSxcbiAgICAgICAgICAgIHVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQ6IHRoaXMudmFsdWVTdG9yZS5nZXRVcGRhdGVkUHJvcHNGcm9tUGFyZW50KCksXG4gICAgICAgICAgICBmaWxlczogZmlsZXNUb1NlbmQsXG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuaG9va3MudHJpZ2dlckhvb2soJ3JlcXVlc3Q6c3RhcnRlZCcsIHJlcXVlc3RDb25maWcpO1xuICAgICAgICB0aGlzLmJhY2tlbmRSZXF1ZXN0ID0gdGhpcy5iYWNrZW5kLm1ha2VSZXF1ZXN0KHJlcXVlc3RDb25maWcucHJvcHMsIHJlcXVlc3RDb25maWcuYWN0aW9ucywgcmVxdWVzdENvbmZpZy51cGRhdGVkLCByZXF1ZXN0Q29uZmlnLmNoaWxkcmVuLCByZXF1ZXN0Q29uZmlnLnVwZGF0ZWRQcm9wc0Zyb21QYXJlbnQsIHJlcXVlc3RDb25maWcuZmlsZXMpO1xuICAgICAgICB0aGlzLmhvb2tzLnRyaWdnZXJIb29rKCdsb2FkaW5nLnN0YXRlOnN0YXJ0ZWQnLCB0aGlzLmVsZW1lbnQsIHRoaXMuYmFja2VuZFJlcXVlc3QpO1xuICAgICAgICB0aGlzLnBlbmRpbmdBY3Rpb25zID0gW107XG4gICAgICAgIHRoaXMudmFsdWVTdG9yZS5mbHVzaERpcnR5UHJvcHNUb1BlbmRpbmcoKTtcbiAgICAgICAgdGhpcy5pc1JlcXVlc3RQZW5kaW5nID0gZmFsc2U7XG4gICAgICAgIHRoaXMuYmFja2VuZFJlcXVlc3QucHJvbWlzZS50aGVuKGFzeW5jIChyZXNwb25zZSkgPT4ge1xuICAgICAgICAgICAgY29uc3QgYmFja2VuZFJlc3BvbnNlID0gbmV3IEJhY2tlbmRSZXNwb25zZShyZXNwb25zZSk7XG4gICAgICAgICAgICBjb25zdCBodG1sID0gYXdhaXQgYmFja2VuZFJlc3BvbnNlLmdldEJvZHkoKTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgaW5wdXQgb2YgT2JqZWN0LnZhbHVlcyh0aGlzLnBlbmRpbmdGaWxlcykpIHtcbiAgICAgICAgICAgICAgICBpbnB1dC52YWx1ZSA9ICcnO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY29uc3QgaGVhZGVycyA9IGJhY2tlbmRSZXNwb25zZS5yZXNwb25zZS5oZWFkZXJzO1xuICAgICAgICAgICAgaWYgKCFoZWFkZXJzLmdldCgnQ29udGVudC1UeXBlJyk/LmluY2x1ZGVzKCdhcHBsaWNhdGlvbi92bmQubGl2ZS1jb21wb25lbnQraHRtbCcpICYmXG4gICAgICAgICAgICAgICAgIWhlYWRlcnMuZ2V0KCdYLUxpdmUtUmVkaXJlY3QnKSkge1xuICAgICAgICAgICAgICAgIGNvbnN0IGNvbnRyb2xzID0geyBkaXNwbGF5RXJyb3I6IHRydWUgfTtcbiAgICAgICAgICAgICAgICB0aGlzLnZhbHVlU3RvcmUucHVzaFBlbmRpbmdQcm9wc0JhY2tUb0RpcnR5KCk7XG4gICAgICAgICAgICAgICAgdGhpcy5ob29rcy50cmlnZ2VySG9vaygncmVzcG9uc2U6ZXJyb3InLCBiYWNrZW5kUmVzcG9uc2UsIGNvbnRyb2xzKTtcbiAgICAgICAgICAgICAgICBpZiAoY29udHJvbHMuZGlzcGxheUVycm9yKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMucmVuZGVyRXJyb3IoaHRtbCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHRoaXMuYmFja2VuZFJlcXVlc3QgPSBudWxsO1xuICAgICAgICAgICAgICAgIHRoaXNQcm9taXNlUmVzb2x2ZShiYWNrZW5kUmVzcG9uc2UpO1xuICAgICAgICAgICAgICAgIHJldHVybiByZXNwb25zZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc1JlcmVuZGVyKGh0bWwsIGJhY2tlbmRSZXNwb25zZSk7XG4gICAgICAgICAgICB0aGlzLmJhY2tlbmRSZXF1ZXN0ID0gbnVsbDtcbiAgICAgICAgICAgIHRoaXNQcm9taXNlUmVzb2x2ZShiYWNrZW5kUmVzcG9uc2UpO1xuICAgICAgICAgICAgaWYgKHRoaXMuaXNSZXF1ZXN0UGVuZGluZykge1xuICAgICAgICAgICAgICAgIHRoaXMuaXNSZXF1ZXN0UGVuZGluZyA9IGZhbHNlO1xuICAgICAgICAgICAgICAgIHRoaXMucGVyZm9ybVJlcXVlc3QoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiByZXNwb25zZTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIHByb2Nlc3NSZXJlbmRlcihodG1sLCBiYWNrZW5kUmVzcG9uc2UpIHtcbiAgICAgICAgY29uc3QgY29udHJvbHMgPSB7IHNob3VsZFJlbmRlcjogdHJ1ZSB9O1xuICAgICAgICB0aGlzLmhvb2tzLnRyaWdnZXJIb29rKCdyZW5kZXI6c3RhcnRlZCcsIGh0bWwsIGJhY2tlbmRSZXNwb25zZSwgY29udHJvbHMpO1xuICAgICAgICBpZiAoIWNvbnRyb2xzLnNob3VsZFJlbmRlcikge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGlmIChiYWNrZW5kUmVzcG9uc2UucmVzcG9uc2UuaGVhZGVycy5nZXQoJ0xvY2F0aW9uJykpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmlzVHVyYm9FbmFibGVkKCkpIHtcbiAgICAgICAgICAgICAgICBUdXJiby52aXNpdChiYWNrZW5kUmVzcG9uc2UucmVzcG9uc2UuaGVhZGVycy5nZXQoJ0xvY2F0aW9uJykpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPSBiYWNrZW5kUmVzcG9uc2UucmVzcG9uc2UuaGVhZGVycy5nZXQoJ0xvY2F0aW9uJykgfHwgJyc7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5ob29rcy50cmlnZ2VySG9vaygnbG9hZGluZy5zdGF0ZTpmaW5pc2hlZCcsIHRoaXMuZWxlbWVudCk7XG4gICAgICAgIGNvbnN0IG1vZGlmaWVkTW9kZWxWYWx1ZXMgPSB7fTtcbiAgICAgICAgT2JqZWN0LmtleXModGhpcy52YWx1ZVN0b3JlLmdldERpcnR5UHJvcHMoKSkuZm9yRWFjaCgobW9kZWxOYW1lKSA9PiB7XG4gICAgICAgICAgICBtb2RpZmllZE1vZGVsVmFsdWVzW21vZGVsTmFtZV0gPSB0aGlzLnZhbHVlU3RvcmUuZ2V0KG1vZGVsTmFtZSk7XG4gICAgICAgIH0pO1xuICAgICAgICBsZXQgbmV3RWxlbWVudDtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIG5ld0VsZW1lbnQgPSBodG1sVG9FbGVtZW50KGh0bWwpO1xuICAgICAgICAgICAgaWYgKCFuZXdFbGVtZW50Lm1hdGNoZXMoJ1tkYXRhLWNvbnRyb2xsZXJ+PWxpdmVdJykpIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0EgbGl2ZSBjb21wb25lbnQgdGVtcGxhdGUgbXVzdCBjb250YWluIGEgc2luZ2xlIHJvb3QgY29udHJvbGxlciBlbGVtZW50LicpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcihgVGhlcmUgd2FzIGEgcHJvYmxlbSB3aXRoIHRoZSAnJHt0aGlzLm5hbWV9JyBjb21wb25lbnQgSFRNTCByZXR1cm5lZDpgLCB7XG4gICAgICAgICAgICAgICAgaWQ6IHRoaXMuaWQsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIHRocm93IGVycm9yO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuZXh0ZXJuYWxNdXRhdGlvblRyYWNrZXIuaGFuZGxlUGVuZGluZ0NoYW5nZXMoKTtcbiAgICAgICAgdGhpcy5leHRlcm5hbE11dGF0aW9uVHJhY2tlci5zdG9wKCk7XG4gICAgICAgIGV4ZWN1dGVNb3JwaGRvbSh0aGlzLmVsZW1lbnQsIG5ld0VsZW1lbnQsIHRoaXMudW5zeW5jZWRJbnB1dHNUcmFja2VyLmdldFVuc3luY2VkSW5wdXRzKCksIChlbGVtZW50KSA9PiBnZXRWYWx1ZUZyb21FbGVtZW50KGVsZW1lbnQsIHRoaXMudmFsdWVTdG9yZSksIHRoaXMuZXh0ZXJuYWxNdXRhdGlvblRyYWNrZXIpO1xuICAgICAgICB0aGlzLmV4dGVybmFsTXV0YXRpb25UcmFja2VyLnN0YXJ0KCk7XG4gICAgICAgIGNvbnN0IG5ld1Byb3BzID0gdGhpcy5lbGVtZW50RHJpdmVyLmdldENvbXBvbmVudFByb3BzKCk7XG4gICAgICAgIHRoaXMudmFsdWVTdG9yZS5yZWluaXRpYWxpemVBbGxQcm9wcyhuZXdQcm9wcyk7XG4gICAgICAgIGNvbnN0IGV2ZW50c1RvRW1pdCA9IHRoaXMuZWxlbWVudERyaXZlci5nZXRFdmVudHNUb0VtaXQoKTtcbiAgICAgICAgY29uc3QgYnJvd3NlckV2ZW50c1RvRGlzcGF0Y2ggPSB0aGlzLmVsZW1lbnREcml2ZXIuZ2V0QnJvd3NlckV2ZW50c1RvRGlzcGF0Y2goKTtcbiAgICAgICAgT2JqZWN0LmtleXMobW9kaWZpZWRNb2RlbFZhbHVlcykuZm9yRWFjaCgobW9kZWxOYW1lKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnZhbHVlU3RvcmUuc2V0KG1vZGVsTmFtZSwgbW9kaWZpZWRNb2RlbFZhbHVlc1ttb2RlbE5hbWVdKTtcbiAgICAgICAgfSk7XG4gICAgICAgIGV2ZW50c1RvRW1pdC5mb3JFYWNoKCh7IGV2ZW50LCBkYXRhLCB0YXJnZXQsIGNvbXBvbmVudE5hbWUgfSkgPT4ge1xuICAgICAgICAgICAgaWYgKHRhcmdldCA9PT0gJ3VwJykge1xuICAgICAgICAgICAgICAgIHRoaXMuZW1pdFVwKGV2ZW50LCBkYXRhLCBjb21wb25lbnROYW1lKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodGFyZ2V0ID09PSAnc2VsZicpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmVtaXRTZWxmKGV2ZW50LCBkYXRhKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLmVtaXQoZXZlbnQsIGRhdGEsIGNvbXBvbmVudE5hbWUpO1xuICAgICAgICB9KTtcbiAgICAgICAgYnJvd3NlckV2ZW50c1RvRGlzcGF0Y2guZm9yRWFjaCgoeyBldmVudCwgcGF5bG9hZCB9KSA9PiB7XG4gICAgICAgICAgICB0aGlzLmVsZW1lbnQuZGlzcGF0Y2hFdmVudChuZXcgQ3VzdG9tRXZlbnQoZXZlbnQsIHtcbiAgICAgICAgICAgICAgICBkZXRhaWw6IHBheWxvYWQsXG4gICAgICAgICAgICAgICAgYnViYmxlczogdHJ1ZSxcbiAgICAgICAgICAgIH0pKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMuaG9va3MudHJpZ2dlckhvb2soJ3JlbmRlcjpmaW5pc2hlZCcsIHRoaXMpO1xuICAgIH1cbiAgICBjYWxjdWxhdGVEZWJvdW5jZShkZWJvdW5jZSkge1xuICAgICAgICBpZiAoZGVib3VuY2UgPT09IHRydWUpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmRlZmF1bHREZWJvdW5jZTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoZGVib3VuY2UgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICByZXR1cm4gMDtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZGVib3VuY2U7XG4gICAgfVxuICAgIGNsZWFyUmVxdWVzdERlYm91bmNlVGltZW91dCgpIHtcbiAgICAgICAgaWYgKHRoaXMucmVxdWVzdERlYm91bmNlVGltZW91dCkge1xuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHRoaXMucmVxdWVzdERlYm91bmNlVGltZW91dCk7XG4gICAgICAgICAgICB0aGlzLnJlcXVlc3REZWJvdW5jZVRpbWVvdXQgPSBudWxsO1xuICAgICAgICB9XG4gICAgfVxuICAgIGRlYm91bmNlZFN0YXJ0UmVxdWVzdChkZWJvdW5jZSkge1xuICAgICAgICB0aGlzLmNsZWFyUmVxdWVzdERlYm91bmNlVGltZW91dCgpO1xuICAgICAgICB0aGlzLnJlcXVlc3REZWJvdW5jZVRpbWVvdXQgPSB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnJlbmRlcigpO1xuICAgICAgICB9LCB0aGlzLmNhbGN1bGF0ZURlYm91bmNlKGRlYm91bmNlKSk7XG4gICAgfVxuICAgIHJlbmRlckVycm9yKGh0bWwpIHtcbiAgICAgICAgbGV0IG1vZGFsID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2xpdmUtY29tcG9uZW50LWVycm9yJyk7XG4gICAgICAgIGlmIChtb2RhbCkge1xuICAgICAgICAgICAgbW9kYWwuaW5uZXJIVE1MID0gJyc7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBtb2RhbCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICAgICAgbW9kYWwuaWQgPSAnbGl2ZS1jb21wb25lbnQtZXJyb3InO1xuICAgICAgICAgICAgbW9kYWwuc3R5bGUucGFkZGluZyA9ICc1MHB4JztcbiAgICAgICAgICAgIG1vZGFsLnN0eWxlLmJhY2tncm91bmRDb2xvciA9ICdyZ2JhKDAsIDAsIDAsIC41KSc7XG4gICAgICAgICAgICBtb2RhbC5zdHlsZS56SW5kZXggPSAnMTAwMDAwJztcbiAgICAgICAgICAgIG1vZGFsLnN0eWxlLnBvc2l0aW9uID0gJ2ZpeGVkJztcbiAgICAgICAgICAgIG1vZGFsLnN0eWxlLnRvcCA9ICcwcHgnO1xuICAgICAgICAgICAgbW9kYWwuc3R5bGUuYm90dG9tID0gJzBweCc7XG4gICAgICAgICAgICBtb2RhbC5zdHlsZS5sZWZ0ID0gJzBweCc7XG4gICAgICAgICAgICBtb2RhbC5zdHlsZS5yaWdodCA9ICcwcHgnO1xuICAgICAgICAgICAgbW9kYWwuc3R5bGUuZGlzcGxheSA9ICdmbGV4JztcbiAgICAgICAgICAgIG1vZGFsLnN0eWxlLmZsZXhEaXJlY3Rpb24gPSAnY29sdW1uJztcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBpZnJhbWUgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpZnJhbWUnKTtcbiAgICAgICAgaWZyYW1lLnN0eWxlLmJvcmRlclJhZGl1cyA9ICc1cHgnO1xuICAgICAgICBpZnJhbWUuc3R5bGUuZmxleEdyb3cgPSAnMSc7XG4gICAgICAgIG1vZGFsLmFwcGVuZENoaWxkKGlmcmFtZSk7XG4gICAgICAgIGRvY3VtZW50LmJvZHkucHJlcGVuZChtb2RhbCk7XG4gICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcbiAgICAgICAgaWYgKGlmcmFtZS5jb250ZW50V2luZG93KSB7XG4gICAgICAgICAgICBpZnJhbWUuY29udGVudFdpbmRvdy5kb2N1bWVudC5vcGVuKCk7XG4gICAgICAgICAgICBpZnJhbWUuY29udGVudFdpbmRvdy5kb2N1bWVudC53cml0ZShodG1sKTtcbiAgICAgICAgICAgIGlmcmFtZS5jb250ZW50V2luZG93LmRvY3VtZW50LmNsb3NlKCk7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgY2xvc2VNb2RhbCA9IChtb2RhbCkgPT4ge1xuICAgICAgICAgICAgaWYgKG1vZGFsKSB7XG4gICAgICAgICAgICAgICAgbW9kYWwub3V0ZXJIVE1MID0gJyc7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBkb2N1bWVudC5ib2R5LnN0eWxlLm92ZXJmbG93ID0gJ3Zpc2libGUnO1xuICAgICAgICB9O1xuICAgICAgICBtb2RhbC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IGNsb3NlTW9kYWwobW9kYWwpKTtcbiAgICAgICAgbW9kYWwuc2V0QXR0cmlidXRlKCd0YWJpbmRleCcsICcwJyk7XG4gICAgICAgIG1vZGFsLmFkZEV2ZW50TGlzdGVuZXIoJ2tleWRvd24nLCAoZSkgPT4ge1xuICAgICAgICAgICAgaWYgKGUua2V5ID09PSAnRXNjYXBlJykge1xuICAgICAgICAgICAgICAgIGNsb3NlTW9kYWwobW9kYWwpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgbW9kYWwuZm9jdXMoKTtcbiAgICB9XG4gICAgcmVzZXRQcm9taXNlKCkge1xuICAgICAgICB0aGlzLm5leHRSZXF1ZXN0UHJvbWlzZSA9IG5ldyBQcm9taXNlKChyZXNvbHZlKSA9PiB7XG4gICAgICAgICAgICB0aGlzLm5leHRSZXF1ZXN0UHJvbWlzZVJlc29sdmUgPSByZXNvbHZlO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgX3VwZGF0ZUZyb21QYXJlbnRQcm9wcyhwcm9wcykge1xuICAgICAgICBjb25zdCBpc0NoYW5nZWQgPSB0aGlzLnZhbHVlU3RvcmUuc3RvcmVOZXdQcm9wc0Zyb21QYXJlbnQocHJvcHMpO1xuICAgICAgICBpZiAoaXNDaGFuZ2VkKSB7XG4gICAgICAgICAgICB0aGlzLnJlbmRlcigpO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gcHJveGlmeUNvbXBvbmVudChjb21wb25lbnQpIHtcbiAgICByZXR1cm4gbmV3IFByb3h5KGNvbXBvbmVudCwge1xuICAgICAgICBnZXQoY29tcG9uZW50LCBwcm9wKSB7XG4gICAgICAgICAgICBpZiAocHJvcCBpbiBjb21wb25lbnQgfHwgdHlwZW9mIHByb3AgIT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBjb21wb25lbnRbcHJvcF0gPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgY2FsbGFibGUgPSBjb21wb25lbnRbcHJvcF07XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiAoLi4uYXJncykgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGNhbGxhYmxlLmFwcGx5KGNvbXBvbmVudCwgYXJncyk7XG4gICAgICAgICAgICAgICAgICAgIH07XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHJldHVybiBSZWZsZWN0LmdldChjb21wb25lbnQsIHByb3ApO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGNvbXBvbmVudC52YWx1ZVN0b3JlLmhhcyhwcm9wKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBjb21wb25lbnQuZ2V0RGF0YShwcm9wKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiAoYXJncykgPT4ge1xuICAgICAgICAgICAgICAgIHJldHVybiBjb21wb25lbnQuYWN0aW9uLmFwcGx5KGNvbXBvbmVudCwgW3Byb3AsIGFyZ3NdKTtcbiAgICAgICAgICAgIH07XG4gICAgICAgIH0sXG4gICAgICAgIHNldCh0YXJnZXQsIHByb3BlcnR5LCB2YWx1ZSkge1xuICAgICAgICAgICAgaWYgKHByb3BlcnR5IGluIHRhcmdldCkge1xuICAgICAgICAgICAgICAgIHRhcmdldFtwcm9wZXJ0eV0gPSB2YWx1ZTtcbiAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRhcmdldC5zZXQocHJvcGVydHksIHZhbHVlKTtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9LFxuICAgIH0pO1xufVxuXG5jbGFzcyBTdGltdWx1c0VsZW1lbnREcml2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGNvbnRyb2xsZXIpIHtcbiAgICAgICAgdGhpcy5jb250cm9sbGVyID0gY29udHJvbGxlcjtcbiAgICB9XG4gICAgZ2V0TW9kZWxOYW1lKGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgbW9kZWxEaXJlY3RpdmUgPSBnZXRNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50KGVsZW1lbnQsIGZhbHNlKTtcbiAgICAgICAgaWYgKCFtb2RlbERpcmVjdGl2ZSkge1xuICAgICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIG1vZGVsRGlyZWN0aXZlLmFjdGlvbjtcbiAgICB9XG4gICAgZ2V0Q29tcG9uZW50UHJvcHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRyb2xsZXIucHJvcHNWYWx1ZTtcbiAgICB9XG4gICAgZ2V0RXZlbnRzVG9FbWl0KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250cm9sbGVyLmV2ZW50c1RvRW1pdFZhbHVlO1xuICAgIH1cbiAgICBnZXRCcm93c2VyRXZlbnRzVG9EaXNwYXRjaCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29udHJvbGxlci5ldmVudHNUb0Rpc3BhdGNoVmFsdWU7XG4gICAgfVxufVxuXG5mdW5jdGlvbiBnZXRNb2RlbEJpbmRpbmcgKG1vZGVsRGlyZWN0aXZlKSB7XG4gICAgbGV0IHNob3VsZFJlbmRlciA9IHRydWU7XG4gICAgbGV0IHRhcmdldEV2ZW50TmFtZSA9IG51bGw7XG4gICAgbGV0IGRlYm91bmNlID0gZmFsc2U7XG4gICAgbW9kZWxEaXJlY3RpdmUubW9kaWZpZXJzLmZvckVhY2goKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgIHN3aXRjaCAobW9kaWZpZXIubmFtZSkge1xuICAgICAgICAgICAgY2FzZSAnb24nOlxuICAgICAgICAgICAgICAgIGlmICghbW9kaWZpZXIudmFsdWUpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgXCJvblwiIG1vZGlmaWVyIGluICR7bW9kZWxEaXJlY3RpdmUuZ2V0U3RyaW5nKCl9IHJlcXVpcmVzIGEgdmFsdWUgLSBlLmcuIG9uKGNoYW5nZSkuYCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmICghWydpbnB1dCcsICdjaGFuZ2UnXS5pbmNsdWRlcyhtb2RpZmllci52YWx1ZSkpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgXCJvblwiIG1vZGlmaWVyIGluICR7bW9kZWxEaXJlY3RpdmUuZ2V0U3RyaW5nKCl9IG9ubHkgYWNjZXB0cyB0aGUgYXJndW1lbnRzIFwiaW5wdXRcIiBvciBcImNoYW5nZVwiLmApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB0YXJnZXRFdmVudE5hbWUgPSBtb2RpZmllci52YWx1ZTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ25vcmVuZGVyJzpcbiAgICAgICAgICAgICAgICBzaG91bGRSZW5kZXIgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ2RlYm91bmNlJzpcbiAgICAgICAgICAgICAgICBkZWJvdW5jZSA9IG1vZGlmaWVyLnZhbHVlID8gTnVtYmVyLnBhcnNlSW50KG1vZGlmaWVyLnZhbHVlKSA6IHRydWU7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgVW5rbm93biBtb2RpZmllciBcIiR7bW9kaWZpZXIubmFtZX1cIiBpbiBkYXRhLW1vZGVsPVwiJHttb2RlbERpcmVjdGl2ZS5nZXRTdHJpbmcoKX1cIi5gKTtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgIGNvbnN0IFttb2RlbE5hbWUsIGlubmVyTW9kZWxOYW1lXSA9IG1vZGVsRGlyZWN0aXZlLmFjdGlvbi5zcGxpdCgnOicpO1xuICAgIHJldHVybiB7XG4gICAgICAgIG1vZGVsTmFtZSxcbiAgICAgICAgaW5uZXJNb2RlbE5hbWU6IGlubmVyTW9kZWxOYW1lIHx8IG51bGwsXG4gICAgICAgIHNob3VsZFJlbmRlcixcbiAgICAgICAgZGVib3VuY2UsXG4gICAgICAgIHRhcmdldEV2ZW50TmFtZSxcbiAgICB9O1xufVxuXG5jbGFzcyBDaGlsZENvbXBvbmVudFBsdWdpbiB7XG4gICAgY29uc3RydWN0b3IoY29tcG9uZW50KSB7XG4gICAgICAgIHRoaXMucGFyZW50TW9kZWxCaW5kaW5ncyA9IFtdO1xuICAgICAgICB0aGlzLmNvbXBvbmVudCA9IGNvbXBvbmVudDtcbiAgICAgICAgY29uc3QgbW9kZWxEaXJlY3RpdmVzID0gZ2V0QWxsTW9kZWxEaXJlY3RpdmVGcm9tRWxlbWVudHModGhpcy5jb21wb25lbnQuZWxlbWVudCk7XG4gICAgICAgIHRoaXMucGFyZW50TW9kZWxCaW5kaW5ncyA9IG1vZGVsRGlyZWN0aXZlcy5tYXAoZ2V0TW9kZWxCaW5kaW5nKTtcbiAgICB9XG4gICAgYXR0YWNoVG9Db21wb25lbnQoY29tcG9uZW50KSB7XG4gICAgICAgIGNvbXBvbmVudC5vbigncmVxdWVzdDpzdGFydGVkJywgKHJlcXVlc3REYXRhKSA9PiB7XG4gICAgICAgICAgICByZXF1ZXN0RGF0YS5jaGlsZHJlbiA9IHRoaXMuZ2V0Q2hpbGRyZW5GaW5nZXJwcmludHMoKTtcbiAgICAgICAgfSk7XG4gICAgICAgIGNvbXBvbmVudC5vbignbW9kZWw6c2V0JywgKG1vZGVsLCB2YWx1ZSkgPT4ge1xuICAgICAgICAgICAgdGhpcy5ub3RpZnlQYXJlbnRNb2RlbENoYW5nZShtb2RlbCwgdmFsdWUpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgZ2V0Q2hpbGRyZW5GaW5nZXJwcmludHMoKSB7XG4gICAgICAgIGNvbnN0IGZpbmdlcnByaW50cyA9IHt9O1xuICAgICAgICB0aGlzLmdldENoaWxkcmVuKCkuZm9yRWFjaCgoY2hpbGQpID0+IHtcbiAgICAgICAgICAgIGlmICghY2hpbGQuaWQpIHtcbiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ21pc3NpbmcgaWQnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGZpbmdlcnByaW50c1tjaGlsZC5pZF0gPSB7XG4gICAgICAgICAgICAgICAgZmluZ2VycHJpbnQ6IGNoaWxkLmZpbmdlcnByaW50LFxuICAgICAgICAgICAgICAgIHRhZzogY2hpbGQuZWxlbWVudC50YWdOYW1lLnRvTG93ZXJDYXNlKCksXG4gICAgICAgICAgICB9O1xuICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIGZpbmdlcnByaW50cztcbiAgICB9XG4gICAgbm90aWZ5UGFyZW50TW9kZWxDaGFuZ2UobW9kZWxOYW1lLCB2YWx1ZSkge1xuICAgICAgICBjb25zdCBwYXJlbnRDb21wb25lbnQgPSBmaW5kUGFyZW50KHRoaXMuY29tcG9uZW50KTtcbiAgICAgICAgaWYgKCFwYXJlbnRDb21wb25lbnQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLnBhcmVudE1vZGVsQmluZGluZ3MuZm9yRWFjaCgobW9kZWxCaW5kaW5nKSA9PiB7XG4gICAgICAgICAgICBjb25zdCBjaGlsZE1vZGVsTmFtZSA9IG1vZGVsQmluZGluZy5pbm5lck1vZGVsTmFtZSB8fCAndmFsdWUnO1xuICAgICAgICAgICAgaWYgKGNoaWxkTW9kZWxOYW1lICE9PSBtb2RlbE5hbWUpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBwYXJlbnRDb21wb25lbnQuc2V0KG1vZGVsQmluZGluZy5tb2RlbE5hbWUsIHZhbHVlLCBtb2RlbEJpbmRpbmcuc2hvdWxkUmVuZGVyLCBtb2RlbEJpbmRpbmcuZGVib3VuY2UpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgZ2V0Q2hpbGRyZW4oKSB7XG4gICAgICAgIHJldHVybiBmaW5kQ2hpbGRyZW4odGhpcy5jb21wb25lbnQpO1xuICAgIH1cbn1cblxuY2xhc3MgTGF6eVBsdWdpbiB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHRoaXMuaW50ZXJzZWN0aW9uT2JzZXJ2ZXIgPSBudWxsO1xuICAgIH1cbiAgICBhdHRhY2hUb0NvbXBvbmVudChjb21wb25lbnQpIHtcbiAgICAgICAgaWYgKCdsYXp5JyAhPT0gY29tcG9uZW50LmVsZW1lbnQuYXR0cmlidXRlcy5nZXROYW1lZEl0ZW0oJ2xvYWRpbmcnKT8udmFsdWUpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBjb21wb25lbnQub24oJ2Nvbm5lY3QnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmdldE9ic2VydmVyKCkub2JzZXJ2ZShjb21wb25lbnQuZWxlbWVudCk7XG4gICAgICAgIH0pO1xuICAgICAgICBjb21wb25lbnQub24oJ2Rpc2Nvbm5lY3QnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmludGVyc2VjdGlvbk9ic2VydmVyPy51bm9ic2VydmUoY29tcG9uZW50LmVsZW1lbnQpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgZ2V0T2JzZXJ2ZXIoKSB7XG4gICAgICAgIGlmICghdGhpcy5pbnRlcnNlY3Rpb25PYnNlcnZlcikge1xuICAgICAgICAgICAgdGhpcy5pbnRlcnNlY3Rpb25PYnNlcnZlciA9IG5ldyBJbnRlcnNlY3Rpb25PYnNlcnZlcigoZW50cmllcywgb2JzZXJ2ZXIpID0+IHtcbiAgICAgICAgICAgICAgICBlbnRyaWVzLmZvckVhY2goKGVudHJ5KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChlbnRyeS5pc0ludGVyc2VjdGluZykge1xuICAgICAgICAgICAgICAgICAgICAgICAgZW50cnkudGFyZ2V0LmRpc3BhdGNoRXZlbnQobmV3IEN1c3RvbUV2ZW50KCdsaXZlOmFwcGVhcicpKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIG9ic2VydmVyLnVub2JzZXJ2ZShlbnRyeS50YXJnZXQpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGhpcy5pbnRlcnNlY3Rpb25PYnNlcnZlcjtcbiAgICB9XG59XG5cbmNsYXNzIExvYWRpbmdQbHVnaW4ge1xuICAgIGF0dGFjaFRvQ29tcG9uZW50KGNvbXBvbmVudCkge1xuICAgICAgICBjb21wb25lbnQub24oJ2xvYWRpbmcuc3RhdGU6c3RhcnRlZCcsIChlbGVtZW50LCByZXF1ZXN0KSA9PiB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0TG9hZGluZyhjb21wb25lbnQsIGVsZW1lbnQsIHJlcXVlc3QpO1xuICAgICAgICB9KTtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdsb2FkaW5nLnN0YXRlOmZpbmlzaGVkJywgKGVsZW1lbnQpID0+IHtcbiAgICAgICAgICAgIHRoaXMuZmluaXNoTG9hZGluZyhjb21wb25lbnQsIGVsZW1lbnQpO1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5maW5pc2hMb2FkaW5nKGNvbXBvbmVudCwgY29tcG9uZW50LmVsZW1lbnQpO1xuICAgIH1cbiAgICBzdGFydExvYWRpbmcoY29tcG9uZW50LCB0YXJnZXRFbGVtZW50LCBiYWNrZW5kUmVxdWVzdCkge1xuICAgICAgICB0aGlzLmhhbmRsZUxvYWRpbmdUb2dnbGUoY29tcG9uZW50LCB0cnVlLCB0YXJnZXRFbGVtZW50LCBiYWNrZW5kUmVxdWVzdCk7XG4gICAgfVxuICAgIGZpbmlzaExvYWRpbmcoY29tcG9uZW50LCB0YXJnZXRFbGVtZW50KSB7XG4gICAgICAgIHRoaXMuaGFuZGxlTG9hZGluZ1RvZ2dsZShjb21wb25lbnQsIGZhbHNlLCB0YXJnZXRFbGVtZW50LCBudWxsKTtcbiAgICB9XG4gICAgaGFuZGxlTG9hZGluZ1RvZ2dsZShjb21wb25lbnQsIGlzTG9hZGluZywgdGFyZ2V0RWxlbWVudCwgYmFja2VuZFJlcXVlc3QpIHtcbiAgICAgICAgaWYgKGlzTG9hZGluZykge1xuICAgICAgICAgICAgdGhpcy5hZGRBdHRyaWJ1dGVzKHRhcmdldEVsZW1lbnQsIFsnYnVzeSddKTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMucmVtb3ZlQXR0cmlidXRlcyh0YXJnZXRFbGVtZW50LCBbJ2J1c3knXSk7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5nZXRMb2FkaW5nRGlyZWN0aXZlcyhjb21wb25lbnQsIHRhcmdldEVsZW1lbnQpLmZvckVhY2goKHsgZWxlbWVudCwgZGlyZWN0aXZlcyB9KSA9PiB7XG4gICAgICAgICAgICBpZiAoaXNMb2FkaW5nKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5hZGRBdHRyaWJ1dGVzKGVsZW1lbnQsIFsnZGF0YS1saXZlLWlzLWxvYWRpbmcnXSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aGlzLnJlbW92ZUF0dHJpYnV0ZXMoZWxlbWVudCwgWydkYXRhLWxpdmUtaXMtbG9hZGluZyddKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGRpcmVjdGl2ZXMuZm9yRWFjaCgoZGlyZWN0aXZlKSA9PiB7XG4gICAgICAgICAgICAgICAgdGhpcy5oYW5kbGVMb2FkaW5nRGlyZWN0aXZlKGVsZW1lbnQsIGlzTG9hZGluZywgZGlyZWN0aXZlLCBiYWNrZW5kUmVxdWVzdCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGhhbmRsZUxvYWRpbmdEaXJlY3RpdmUoZWxlbWVudCwgaXNMb2FkaW5nLCBkaXJlY3RpdmUsIGJhY2tlbmRSZXF1ZXN0KSB7XG4gICAgICAgIGNvbnN0IGZpbmFsQWN0aW9uID0gcGFyc2VMb2FkaW5nQWN0aW9uKGRpcmVjdGl2ZS5hY3Rpb24sIGlzTG9hZGluZyk7XG4gICAgICAgIGNvbnN0IHRhcmdldGVkQWN0aW9ucyA9IFtdO1xuICAgICAgICBjb25zdCB0YXJnZXRlZE1vZGVscyA9IFtdO1xuICAgICAgICBsZXQgZGVsYXkgPSAwO1xuICAgICAgICBjb25zdCB2YWxpZE1vZGlmaWVycyA9IG5ldyBNYXAoKTtcbiAgICAgICAgdmFsaWRNb2RpZmllcnMuc2V0KCdkZWxheScsIChtb2RpZmllcikgPT4ge1xuICAgICAgICAgICAgaWYgKCFpc0xvYWRpbmcpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBkZWxheSA9IG1vZGlmaWVyLnZhbHVlID8gTnVtYmVyLnBhcnNlSW50KG1vZGlmaWVyLnZhbHVlKSA6IDIwMDtcbiAgICAgICAgfSk7XG4gICAgICAgIHZhbGlkTW9kaWZpZXJzLnNldCgnYWN0aW9uJywgKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICBpZiAoIW1vZGlmaWVyLnZhbHVlKSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgXCJhY3Rpb25cIiBpbiBkYXRhLWxvYWRpbmcgbXVzdCBoYXZlIGFuIGFjdGlvbiBuYW1lIC0gZS5nLiBhY3Rpb24oZm9vKS4gSXQncyBtaXNzaW5nIGZvciBcIiR7ZGlyZWN0aXZlLmdldFN0cmluZygpfVwiYCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0YXJnZXRlZEFjdGlvbnMucHVzaChtb2RpZmllci52YWx1ZSk7XG4gICAgICAgIH0pO1xuICAgICAgICB2YWxpZE1vZGlmaWVycy5zZXQoJ21vZGVsJywgKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICBpZiAoIW1vZGlmaWVyLnZhbHVlKSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBUaGUgXCJtb2RlbFwiIGluIGRhdGEtbG9hZGluZyBtdXN0IGhhdmUgYW4gYWN0aW9uIG5hbWUgLSBlLmcuIG1vZGVsKGZvbykuIEl0J3MgbWlzc2luZyBmb3IgXCIke2RpcmVjdGl2ZS5nZXRTdHJpbmcoKX1cImApO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGFyZ2V0ZWRNb2RlbHMucHVzaChtb2RpZmllci52YWx1ZSk7XG4gICAgICAgIH0pO1xuICAgICAgICBkaXJlY3RpdmUubW9kaWZpZXJzLmZvckVhY2goKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICBpZiAodmFsaWRNb2RpZmllcnMuaGFzKG1vZGlmaWVyLm5hbWUpKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgY2FsbGFibGUgPSB2YWxpZE1vZGlmaWVycy5nZXQobW9kaWZpZXIubmFtZSkgPz8gKCgpID0+IHsgfSk7XG4gICAgICAgICAgICAgICAgY2FsbGFibGUobW9kaWZpZXIpO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgVW5rbm93biBtb2RpZmllciBcIiR7bW9kaWZpZXIubmFtZX1cIiB1c2VkIGluIGRhdGEtbG9hZGluZz1cIiR7ZGlyZWN0aXZlLmdldFN0cmluZygpfVwiLiBBdmFpbGFibGUgbW9kaWZpZXJzIGFyZTogJHtBcnJheS5mcm9tKHZhbGlkTW9kaWZpZXJzLmtleXMoKSkuam9pbignLCAnKX0uYCk7XG4gICAgICAgIH0pO1xuICAgICAgICBpZiAoaXNMb2FkaW5nICYmXG4gICAgICAgICAgICB0YXJnZXRlZEFjdGlvbnMubGVuZ3RoID4gMCAmJlxuICAgICAgICAgICAgYmFja2VuZFJlcXVlc3QgJiZcbiAgICAgICAgICAgICFiYWNrZW5kUmVxdWVzdC5jb250YWluc09uZU9mQWN0aW9ucyh0YXJnZXRlZEFjdGlvbnMpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGlzTG9hZGluZyAmJlxuICAgICAgICAgICAgdGFyZ2V0ZWRNb2RlbHMubGVuZ3RoID4gMCAmJlxuICAgICAgICAgICAgYmFja2VuZFJlcXVlc3QgJiZcbiAgICAgICAgICAgICFiYWNrZW5kUmVxdWVzdC5hcmVBbnlNb2RlbHNVcGRhdGVkKHRhcmdldGVkTW9kZWxzKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGxldCBsb2FkaW5nRGlyZWN0aXZlO1xuICAgICAgICBzd2l0Y2ggKGZpbmFsQWN0aW9uKSB7XG4gICAgICAgICAgICBjYXNlICdzaG93JzpcbiAgICAgICAgICAgICAgICBsb2FkaW5nRGlyZWN0aXZlID0gKCkgPT4gdGhpcy5zaG93RWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ2hpZGUnOlxuICAgICAgICAgICAgICAgIGxvYWRpbmdEaXJlY3RpdmUgPSAoKSA9PiB0aGlzLmhpZGVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAnYWRkQ2xhc3MnOlxuICAgICAgICAgICAgICAgIGxvYWRpbmdEaXJlY3RpdmUgPSAoKSA9PiB0aGlzLmFkZENsYXNzKGVsZW1lbnQsIGRpcmVjdGl2ZS5hcmdzKTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGNhc2UgJ3JlbW92ZUNsYXNzJzpcbiAgICAgICAgICAgICAgICBsb2FkaW5nRGlyZWN0aXZlID0gKCkgPT4gdGhpcy5yZW1vdmVDbGFzcyhlbGVtZW50LCBkaXJlY3RpdmUuYXJncyk7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICBjYXNlICdhZGRBdHRyaWJ1dGUnOlxuICAgICAgICAgICAgICAgIGxvYWRpbmdEaXJlY3RpdmUgPSAoKSA9PiB0aGlzLmFkZEF0dHJpYnV0ZXMoZWxlbWVudCwgZGlyZWN0aXZlLmFyZ3MpO1xuICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgY2FzZSAncmVtb3ZlQXR0cmlidXRlJzpcbiAgICAgICAgICAgICAgICBsb2FkaW5nRGlyZWN0aXZlID0gKCkgPT4gdGhpcy5yZW1vdmVBdHRyaWJ1dGVzKGVsZW1lbnQsIGRpcmVjdGl2ZS5hcmdzKTtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBVbmtub3duIGRhdGEtbG9hZGluZyBhY3Rpb24gXCIke2ZpbmFsQWN0aW9ufVwiYCk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGRlbGF5KSB7XG4gICAgICAgICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKGJhY2tlbmRSZXF1ZXN0ICYmICFiYWNrZW5kUmVxdWVzdC5pc1Jlc29sdmVkKSB7XG4gICAgICAgICAgICAgICAgICAgIGxvYWRpbmdEaXJlY3RpdmUoKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LCBkZWxheSk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgbG9hZGluZ0RpcmVjdGl2ZSgpO1xuICAgIH1cbiAgICBnZXRMb2FkaW5nRGlyZWN0aXZlcyhjb21wb25lbnQsIGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgbG9hZGluZ0RpcmVjdGl2ZXMgPSBbXTtcbiAgICAgICAgbGV0IG1hdGNoaW5nRWxlbWVudHMgPSBbLi4uQXJyYXkuZnJvbShlbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ1tkYXRhLWxvYWRpbmddJykpXTtcbiAgICAgICAgbWF0Y2hpbmdFbGVtZW50cyA9IG1hdGNoaW5nRWxlbWVudHMuZmlsdGVyKChlbHQpID0+IGVsZW1lbnRCZWxvbmdzVG9UaGlzQ29tcG9uZW50KGVsdCwgY29tcG9uZW50KSk7XG4gICAgICAgIGlmIChlbGVtZW50Lmhhc0F0dHJpYnV0ZSgnZGF0YS1sb2FkaW5nJykpIHtcbiAgICAgICAgICAgIG1hdGNoaW5nRWxlbWVudHMgPSBbZWxlbWVudCwgLi4ubWF0Y2hpbmdFbGVtZW50c107XG4gICAgICAgIH1cbiAgICAgICAgbWF0Y2hpbmdFbGVtZW50cy5mb3JFYWNoKChlbGVtZW50KSA9PiB7XG4gICAgICAgICAgICBpZiAoIShlbGVtZW50IGluc3RhbmNlb2YgSFRNTEVsZW1lbnQpICYmICEoZWxlbWVudCBpbnN0YW5jZW9mIFNWR0VsZW1lbnQpKSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdJbnZhbGlkIEVsZW1lbnQgVHlwZScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY29uc3QgZGlyZWN0aXZlcyA9IHBhcnNlRGlyZWN0aXZlcyhlbGVtZW50LmRhdGFzZXQubG9hZGluZyB8fCAnc2hvdycpO1xuICAgICAgICAgICAgbG9hZGluZ0RpcmVjdGl2ZXMucHVzaCh7XG4gICAgICAgICAgICAgICAgZWxlbWVudCxcbiAgICAgICAgICAgICAgICBkaXJlY3RpdmVzLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4gbG9hZGluZ0RpcmVjdGl2ZXM7XG4gICAgfVxuICAgIHNob3dFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5zdHlsZS5kaXNwbGF5ID0gJ3JldmVydCc7XG4gICAgfVxuICAgIGhpZGVFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgZWxlbWVudC5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnO1xuICAgIH1cbiAgICBhZGRDbGFzcyhlbGVtZW50LCBjbGFzc2VzKSB7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LmFkZCguLi5jb21iaW5lU3BhY2VkQXJyYXkoY2xhc3NlcykpO1xuICAgIH1cbiAgICByZW1vdmVDbGFzcyhlbGVtZW50LCBjbGFzc2VzKSB7XG4gICAgICAgIGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSguLi5jb21iaW5lU3BhY2VkQXJyYXkoY2xhc3NlcykpO1xuICAgICAgICBpZiAoZWxlbWVudC5jbGFzc0xpc3QubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICBlbGVtZW50LnJlbW92ZUF0dHJpYnV0ZSgnY2xhc3MnKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBhZGRBdHRyaWJ1dGVzKGVsZW1lbnQsIGF0dHJpYnV0ZXMpIHtcbiAgICAgICAgYXR0cmlidXRlcy5mb3JFYWNoKChhdHRyaWJ1dGUpID0+IHtcbiAgICAgICAgICAgIGVsZW1lbnQuc2V0QXR0cmlidXRlKGF0dHJpYnV0ZSwgJycpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgcmVtb3ZlQXR0cmlidXRlcyhlbGVtZW50LCBhdHRyaWJ1dGVzKSB7XG4gICAgICAgIGF0dHJpYnV0ZXMuZm9yRWFjaCgoYXR0cmlidXRlKSA9PiB7XG4gICAgICAgICAgICBlbGVtZW50LnJlbW92ZUF0dHJpYnV0ZShhdHRyaWJ1dGUpO1xuICAgICAgICB9KTtcbiAgICB9XG59XG5jb25zdCBwYXJzZUxvYWRpbmdBY3Rpb24gPSAoYWN0aW9uLCBpc0xvYWRpbmcpID0+IHtcbiAgICBzd2l0Y2ggKGFjdGlvbikge1xuICAgICAgICBjYXNlICdzaG93JzpcbiAgICAgICAgICAgIHJldHVybiBpc0xvYWRpbmcgPyAnc2hvdycgOiAnaGlkZSc7XG4gICAgICAgIGNhc2UgJ2hpZGUnOlxuICAgICAgICAgICAgcmV0dXJuIGlzTG9hZGluZyA/ICdoaWRlJyA6ICdzaG93JztcbiAgICAgICAgY2FzZSAnYWRkQ2xhc3MnOlxuICAgICAgICAgICAgcmV0dXJuIGlzTG9hZGluZyA/ICdhZGRDbGFzcycgOiAncmVtb3ZlQ2xhc3MnO1xuICAgICAgICBjYXNlICdyZW1vdmVDbGFzcyc6XG4gICAgICAgICAgICByZXR1cm4gaXNMb2FkaW5nID8gJ3JlbW92ZUNsYXNzJyA6ICdhZGRDbGFzcyc7XG4gICAgICAgIGNhc2UgJ2FkZEF0dHJpYnV0ZSc6XG4gICAgICAgICAgICByZXR1cm4gaXNMb2FkaW5nID8gJ2FkZEF0dHJpYnV0ZScgOiAncmVtb3ZlQXR0cmlidXRlJztcbiAgICAgICAgY2FzZSAncmVtb3ZlQXR0cmlidXRlJzpcbiAgICAgICAgICAgIHJldHVybiBpc0xvYWRpbmcgPyAncmVtb3ZlQXR0cmlidXRlJyA6ICdhZGRBdHRyaWJ1dGUnO1xuICAgIH1cbiAgICB0aHJvdyBuZXcgRXJyb3IoYFVua25vd24gZGF0YS1sb2FkaW5nIGFjdGlvbiBcIiR7YWN0aW9ufVwiYCk7XG59O1xuXG5jbGFzcyBQYWdlVW5sb2FkaW5nUGx1Z2luIHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgdGhpcy5pc0Nvbm5lY3RlZCA9IGZhbHNlO1xuICAgIH1cbiAgICBhdHRhY2hUb0NvbXBvbmVudChjb21wb25lbnQpIHtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdyZW5kZXI6c3RhcnRlZCcsIChodG1sLCByZXNwb25zZSwgY29udHJvbHMpID0+IHtcbiAgICAgICAgICAgIGlmICghdGhpcy5pc0Nvbm5lY3RlZCkge1xuICAgICAgICAgICAgICAgIGNvbnRyb2xzLnNob3VsZFJlbmRlciA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdjb25uZWN0JywgKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy5pc0Nvbm5lY3RlZCA9IHRydWU7XG4gICAgICAgIH0pO1xuICAgICAgICBjb21wb25lbnQub24oJ2Rpc2Nvbm5lY3QnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmlzQ29ubmVjdGVkID0gZmFsc2U7XG4gICAgICAgIH0pO1xuICAgIH1cbn1cblxuY2xhc3MgUG9sbGluZ0RpcmVjdG9yIHtcbiAgICBjb25zdHJ1Y3Rvcihjb21wb25lbnQpIHtcbiAgICAgICAgdGhpcy5pc1BvbGxpbmdBY3RpdmUgPSB0cnVlO1xuICAgICAgICB0aGlzLnBvbGxpbmdJbnRlcnZhbHMgPSBbXTtcbiAgICAgICAgdGhpcy5jb21wb25lbnQgPSBjb21wb25lbnQ7XG4gICAgfVxuICAgIGFkZFBvbGwoYWN0aW9uTmFtZSwgZHVyYXRpb24pIHtcbiAgICAgICAgdGhpcy5wb2xscy5wdXNoKHsgYWN0aW9uTmFtZSwgZHVyYXRpb24gfSk7XG4gICAgICAgIGlmICh0aGlzLmlzUG9sbGluZ0FjdGl2ZSkge1xuICAgICAgICAgICAgdGhpcy5pbml0aWF0ZVBvbGwoYWN0aW9uTmFtZSwgZHVyYXRpb24pO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0YXJ0QWxsUG9sbGluZygpIHtcbiAgICAgICAgaWYgKHRoaXMuaXNQb2xsaW5nQWN0aXZlKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5pc1BvbGxpbmdBY3RpdmUgPSB0cnVlO1xuICAgICAgICB0aGlzLnBvbGxzLmZvckVhY2goKHsgYWN0aW9uTmFtZSwgZHVyYXRpb24gfSkgPT4ge1xuICAgICAgICAgICAgdGhpcy5pbml0aWF0ZVBvbGwoYWN0aW9uTmFtZSwgZHVyYXRpb24pO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgc3RvcEFsbFBvbGxpbmcoKSB7XG4gICAgICAgIHRoaXMuaXNQb2xsaW5nQWN0aXZlID0gZmFsc2U7XG4gICAgICAgIHRoaXMucG9sbGluZ0ludGVydmFscy5mb3JFYWNoKChpbnRlcnZhbCkgPT4ge1xuICAgICAgICAgICAgY2xlYXJJbnRlcnZhbChpbnRlcnZhbCk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBjbGVhclBvbGxpbmcoKSB7XG4gICAgICAgIHRoaXMuc3RvcEFsbFBvbGxpbmcoKTtcbiAgICAgICAgdGhpcy5wb2xscyA9IFtdO1xuICAgICAgICB0aGlzLnN0YXJ0QWxsUG9sbGluZygpO1xuICAgIH1cbiAgICBpbml0aWF0ZVBvbGwoYWN0aW9uTmFtZSwgZHVyYXRpb24pIHtcbiAgICAgICAgbGV0IGNhbGxiYWNrO1xuICAgICAgICBpZiAoYWN0aW9uTmFtZSA9PT0gJyRyZW5kZXInKSB7XG4gICAgICAgICAgICBjYWxsYmFjayA9ICgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5yZW5kZXIoKTtcbiAgICAgICAgICAgIH07XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBjYWxsYmFjayA9ICgpID0+IHtcbiAgICAgICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5hY3Rpb24oYWN0aW9uTmFtZSwge30sIDApO1xuICAgICAgICAgICAgfTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCB0aW1lciA9IHdpbmRvdy5zZXRJbnRlcnZhbCgoKSA9PiB7XG4gICAgICAgICAgICBjYWxsYmFjaygpO1xuICAgICAgICB9LCBkdXJhdGlvbik7XG4gICAgICAgIHRoaXMucG9sbGluZ0ludGVydmFscy5wdXNoKHRpbWVyKTtcbiAgICB9XG59XG5cbmNsYXNzIFBvbGxpbmdQbHVnaW4ge1xuICAgIGF0dGFjaFRvQ29tcG9uZW50KGNvbXBvbmVudCkge1xuICAgICAgICB0aGlzLmVsZW1lbnQgPSBjb21wb25lbnQuZWxlbWVudDtcbiAgICAgICAgdGhpcy5wb2xsaW5nRGlyZWN0b3IgPSBuZXcgUG9sbGluZ0RpcmVjdG9yKGNvbXBvbmVudCk7XG4gICAgICAgIHRoaXMuaW5pdGlhbGl6ZVBvbGxpbmcoKTtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdjb25uZWN0JywgKCkgPT4ge1xuICAgICAgICAgICAgdGhpcy5wb2xsaW5nRGlyZWN0b3Iuc3RhcnRBbGxQb2xsaW5nKCk7XG4gICAgICAgIH0pO1xuICAgICAgICBjb21wb25lbnQub24oJ2Rpc2Nvbm5lY3QnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnBvbGxpbmdEaXJlY3Rvci5zdG9wQWxsUG9sbGluZygpO1xuICAgICAgICB9KTtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdyZW5kZXI6ZmluaXNoZWQnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmluaXRpYWxpemVQb2xsaW5nKCk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBhZGRQb2xsKGFjdGlvbk5hbWUsIGR1cmF0aW9uKSB7XG4gICAgICAgIHRoaXMucG9sbGluZ0RpcmVjdG9yLmFkZFBvbGwoYWN0aW9uTmFtZSwgZHVyYXRpb24pO1xuICAgIH1cbiAgICBjbGVhclBvbGxpbmcoKSB7XG4gICAgICAgIHRoaXMucG9sbGluZ0RpcmVjdG9yLmNsZWFyUG9sbGluZygpO1xuICAgIH1cbiAgICBpbml0aWFsaXplUG9sbGluZygpIHtcbiAgICAgICAgdGhpcy5jbGVhclBvbGxpbmcoKTtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudC5kYXRhc2V0LnBvbGwgPT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IHJhd1BvbGxDb25maWcgPSB0aGlzLmVsZW1lbnQuZGF0YXNldC5wb2xsO1xuICAgICAgICBjb25zdCBkaXJlY3RpdmVzID0gcGFyc2VEaXJlY3RpdmVzKHJhd1BvbGxDb25maWcgfHwgJyRyZW5kZXInKTtcbiAgICAgICAgZGlyZWN0aXZlcy5mb3JFYWNoKChkaXJlY3RpdmUpID0+IHtcbiAgICAgICAgICAgIGxldCBkdXJhdGlvbiA9IDIwMDA7XG4gICAgICAgICAgICBkaXJlY3RpdmUubW9kaWZpZXJzLmZvckVhY2goKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICAgICAgc3dpdGNoIChtb2RpZmllci5uYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgIGNhc2UgJ2RlbGF5JzpcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChtb2RpZmllci52YWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGR1cmF0aW9uID0gTnVtYmVyLnBhcnNlSW50KG1vZGlmaWVyLnZhbHVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS53YXJuKGBVbmtub3duIG1vZGlmaWVyIFwiJHttb2RpZmllci5uYW1lfVwiIGluIGRhdGEtcG9sbCBcIiR7cmF3UG9sbENvbmZpZ31cIi5gKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIHRoaXMuYWRkUG9sbChkaXJlY3RpdmUuYWN0aW9uLCBkdXJhdGlvbik7XG4gICAgICAgIH0pO1xuICAgIH1cbn1cblxuZnVuY3Rpb24gaXNWYWx1ZUVtcHR5KHZhbHVlKSB7XG4gICAgaWYgKG51bGwgPT09IHZhbHVlIHx8IHZhbHVlID09PSAnJyB8fCB1bmRlZmluZWQgPT09IHZhbHVlIHx8IChBcnJheS5pc0FycmF5KHZhbHVlKSAmJiB2YWx1ZS5sZW5ndGggPT09IDApKSB7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgICBpZiAodHlwZW9mIHZhbHVlICE9PSAnb2JqZWN0Jykge1xuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuICAgIGZvciAoY29uc3Qga2V5IG9mIE9iamVjdC5rZXlzKHZhbHVlKSkge1xuICAgICAgICBpZiAoIWlzVmFsdWVFbXB0eSh2YWx1ZVtrZXldKSkge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJldHVybiB0cnVlO1xufVxuZnVuY3Rpb24gdG9RdWVyeVN0cmluZyhkYXRhKSB7XG4gICAgY29uc3QgYnVpbGRRdWVyeVN0cmluZ0VudHJpZXMgPSAoZGF0YSwgZW50cmllcyA9IHt9LCBiYXNlS2V5ID0gJycpID0+IHtcbiAgICAgICAgT2JqZWN0LmVudHJpZXMoZGF0YSkuZm9yRWFjaCgoW2lLZXksIGlWYWx1ZV0pID0+IHtcbiAgICAgICAgICAgIGNvbnN0IGtleSA9IGJhc2VLZXkgPT09ICcnID8gaUtleSA6IGAke2Jhc2VLZXl9WyR7aUtleX1dYDtcbiAgICAgICAgICAgIGlmICgnJyA9PT0gYmFzZUtleSAmJiBpc1ZhbHVlRW1wdHkoaVZhbHVlKSkge1xuICAgICAgICAgICAgICAgIGVudHJpZXNba2V5XSA9ICcnO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSBpZiAobnVsbCAhPT0gaVZhbHVlKSB7XG4gICAgICAgICAgICAgICAgaWYgKHR5cGVvZiBpVmFsdWUgPT09ICdvYmplY3QnKSB7XG4gICAgICAgICAgICAgICAgICAgIGVudHJpZXMgPSB7IC4uLmVudHJpZXMsIC4uLmJ1aWxkUXVlcnlTdHJpbmdFbnRyaWVzKGlWYWx1ZSwgZW50cmllcywga2V5KSB9O1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgZW50cmllc1trZXldID0gZW5jb2RlVVJJQ29tcG9uZW50KGlWYWx1ZSlcbiAgICAgICAgICAgICAgICAgICAgICAgIC5yZXBsYWNlKC8lMjAvZywgJysnKVxuICAgICAgICAgICAgICAgICAgICAgICAgLnJlcGxhY2UoLyUyQy9nLCAnLCcpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiBlbnRyaWVzO1xuICAgIH07XG4gICAgY29uc3QgZW50cmllcyA9IGJ1aWxkUXVlcnlTdHJpbmdFbnRyaWVzKGRhdGEpO1xuICAgIHJldHVybiBPYmplY3QuZW50cmllcyhlbnRyaWVzKVxuICAgICAgICAubWFwKChba2V5LCB2YWx1ZV0pID0+IGAke2tleX09JHt2YWx1ZX1gKVxuICAgICAgICAuam9pbignJicpO1xufVxuZnVuY3Rpb24gZnJvbVF1ZXJ5U3RyaW5nKHNlYXJjaCkge1xuICAgIHNlYXJjaCA9IHNlYXJjaC5yZXBsYWNlKCc/JywgJycpO1xuICAgIGlmIChzZWFyY2ggPT09ICcnKVxuICAgICAgICByZXR1cm4ge307XG4gICAgY29uc3QgaW5zZXJ0RG90Tm90YXRlZFZhbHVlSW50b0RhdGEgPSAoa2V5LCB2YWx1ZSwgZGF0YSkgPT4ge1xuICAgICAgICBjb25zdCBbZmlyc3QsIHNlY29uZCwgLi4ucmVzdF0gPSBrZXkuc3BsaXQoJy4nKTtcbiAgICAgICAgaWYgKCFzZWNvbmQpIHtcbiAgICAgICAgICAgIGRhdGFba2V5XSA9IHZhbHVlO1xuICAgICAgICAgICAgcmV0dXJuIHZhbHVlO1xuICAgICAgICB9XG4gICAgICAgIGlmIChkYXRhW2ZpcnN0XSA9PT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICBkYXRhW2ZpcnN0XSA9IE51bWJlci5pc05hTihOdW1iZXIucGFyc2VJbnQoc2Vjb25kKSkgPyB7fSA6IFtdO1xuICAgICAgICB9XG4gICAgICAgIGluc2VydERvdE5vdGF0ZWRWYWx1ZUludG9EYXRhKFtzZWNvbmQsIC4uLnJlc3RdLmpvaW4oJy4nKSwgdmFsdWUsIGRhdGFbZmlyc3RdKTtcbiAgICB9O1xuICAgIGNvbnN0IGVudHJpZXMgPSBzZWFyY2guc3BsaXQoJyYnKS5tYXAoKGkpID0+IGkuc3BsaXQoJz0nKSk7XG4gICAgY29uc3QgZGF0YSA9IHt9O1xuICAgIGVudHJpZXMuZm9yRWFjaCgoW2tleSwgdmFsdWVdKSA9PiB7XG4gICAgICAgIHZhbHVlID0gZGVjb2RlVVJJQ29tcG9uZW50KHZhbHVlLnJlcGxhY2UoL1xcKy9nLCAnJTIwJykpO1xuICAgICAgICBpZiAoIWtleS5pbmNsdWRlcygnWycpKSB7XG4gICAgICAgICAgICBkYXRhW2tleV0gPSB2YWx1ZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIGlmICgnJyA9PT0gdmFsdWUpXG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgY29uc3QgZG90Tm90YXRlZEtleSA9IGtleS5yZXBsYWNlKC9cXFsvZywgJy4nKS5yZXBsYWNlKC9dL2csICcnKTtcbiAgICAgICAgICAgIGluc2VydERvdE5vdGF0ZWRWYWx1ZUludG9EYXRhKGRvdE5vdGF0ZWRLZXksIHZhbHVlLCBkYXRhKTtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgIHJldHVybiBkYXRhO1xufVxuY2xhc3MgVXJsVXRpbHMgZXh0ZW5kcyBVUkwge1xuICAgIGhhcyhrZXkpIHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHRoaXMuZ2V0RGF0YSgpO1xuICAgICAgICByZXR1cm4gT2JqZWN0LmtleXMoZGF0YSkuaW5jbHVkZXMoa2V5KTtcbiAgICB9XG4gICAgc2V0KGtleSwgdmFsdWUpIHtcbiAgICAgICAgY29uc3QgZGF0YSA9IHRoaXMuZ2V0RGF0YSgpO1xuICAgICAgICBkYXRhW2tleV0gPSB2YWx1ZTtcbiAgICAgICAgdGhpcy5zZXREYXRhKGRhdGEpO1xuICAgIH1cbiAgICBnZXQoa2V5KSB7XG4gICAgICAgIHJldHVybiB0aGlzLmdldERhdGEoKVtrZXldO1xuICAgIH1cbiAgICByZW1vdmUoa2V5KSB7XG4gICAgICAgIGNvbnN0IGRhdGEgPSB0aGlzLmdldERhdGEoKTtcbiAgICAgICAgZGVsZXRlIGRhdGFba2V5XTtcbiAgICAgICAgdGhpcy5zZXREYXRhKGRhdGEpO1xuICAgIH1cbiAgICBnZXREYXRhKCkge1xuICAgICAgICBpZiAoIXRoaXMuc2VhcmNoKSB7XG4gICAgICAgICAgICByZXR1cm4ge307XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZyb21RdWVyeVN0cmluZyh0aGlzLnNlYXJjaCk7XG4gICAgfVxuICAgIHNldERhdGEoZGF0YSkge1xuICAgICAgICB0aGlzLnNlYXJjaCA9IHRvUXVlcnlTdHJpbmcoZGF0YSk7XG4gICAgfVxufVxuY2xhc3MgSGlzdG9yeVN0cmF0ZWd5IHtcbiAgICBzdGF0aWMgcmVwbGFjZSh1cmwpIHtcbiAgICAgICAgaGlzdG9yeS5yZXBsYWNlU3RhdGUoaGlzdG9yeS5zdGF0ZSwgJycsIHVybCk7XG4gICAgfVxufVxuXG5jbGFzcyBRdWVyeVN0cmluZ1BsdWdpbiB7XG4gICAgY29uc3RydWN0b3IobWFwcGluZykge1xuICAgICAgICB0aGlzLm1hcHBpbmcgPSBtYXBwaW5nO1xuICAgIH1cbiAgICBhdHRhY2hUb0NvbXBvbmVudChjb21wb25lbnQpIHtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdyZW5kZXI6ZmluaXNoZWQnLCAoY29tcG9uZW50KSA9PiB7XG4gICAgICAgICAgICBjb25zdCB1cmxVdGlscyA9IG5ldyBVcmxVdGlscyh3aW5kb3cubG9jYXRpb24uaHJlZik7XG4gICAgICAgICAgICBjb25zdCBjdXJyZW50VXJsID0gdXJsVXRpbHMudG9TdHJpbmcoKTtcbiAgICAgICAgICAgIE9iamVjdC5lbnRyaWVzKHRoaXMubWFwcGluZykuZm9yRWFjaCgoW3Byb3AsIG1hcHBpbmddKSA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgdmFsdWUgPSBjb21wb25lbnQudmFsdWVTdG9yZS5nZXQocHJvcCk7XG4gICAgICAgICAgICAgICAgdXJsVXRpbHMuc2V0KG1hcHBpbmcubmFtZSwgdmFsdWUpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBpZiAoY3VycmVudFVybCAhPT0gdXJsVXRpbHMudG9TdHJpbmcoKSkge1xuICAgICAgICAgICAgICAgIEhpc3RvcnlTdHJhdGVneS5yZXBsYWNlKHVybFV0aWxzKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfVxufVxuXG5jbGFzcyBTZXRWYWx1ZU9udG9Nb2RlbEZpZWxkc1BsdWdpbiB7XG4gICAgYXR0YWNoVG9Db21wb25lbnQoY29tcG9uZW50KSB7XG4gICAgICAgIHRoaXMuc3luY2hyb25pemVWYWx1ZU9mTW9kZWxGaWVsZHMoY29tcG9uZW50KTtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdyZW5kZXI6ZmluaXNoZWQnLCAoKSA9PiB7XG4gICAgICAgICAgICB0aGlzLnN5bmNocm9uaXplVmFsdWVPZk1vZGVsRmllbGRzKGNvbXBvbmVudCk7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBzeW5jaHJvbml6ZVZhbHVlT2ZNb2RlbEZpZWxkcyhjb21wb25lbnQpIHtcbiAgICAgICAgY29tcG9uZW50LmVsZW1lbnQucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtbW9kZWxdJykuZm9yRWFjaCgoZWxlbWVudCkgPT4ge1xuICAgICAgICAgICAgaWYgKCEoZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxFbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignSW52YWxpZCBlbGVtZW50IHVzaW5nIGRhdGEtbW9kZWwuJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoZWxlbWVudCBpbnN0YW5jZW9mIEhUTUxGb3JtRWxlbWVudCkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICghZWxlbWVudEJlbG9uZ3NUb1RoaXNDb21wb25lbnQoZWxlbWVudCwgY29tcG9uZW50KSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGNvbnN0IG1vZGVsRGlyZWN0aXZlID0gZ2V0TW9kZWxEaXJlY3RpdmVGcm9tRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgICAgIGlmICghbW9kZWxEaXJlY3RpdmUpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjb25zdCBtb2RlbE5hbWUgPSBtb2RlbERpcmVjdGl2ZS5hY3Rpb247XG4gICAgICAgICAgICBpZiAoY29tcG9uZW50LmdldFVuc3luY2VkTW9kZWxzKCkuaW5jbHVkZXMobW9kZWxOYW1lKSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChjb21wb25lbnQudmFsdWVTdG9yZS5oYXMobW9kZWxOYW1lKSkge1xuICAgICAgICAgICAgICAgIHNldFZhbHVlT25FbGVtZW50KGVsZW1lbnQsIGNvbXBvbmVudC52YWx1ZVN0b3JlLmdldChtb2RlbE5hbWUpKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChlbGVtZW50IGluc3RhbmNlb2YgSFRNTFNlbGVjdEVsZW1lbnQgJiYgIWVsZW1lbnQubXVsdGlwbGUpIHtcbiAgICAgICAgICAgICAgICBjb21wb25lbnQudmFsdWVTdG9yZS5zZXQobW9kZWxOYW1lLCBnZXRWYWx1ZUZyb21FbGVtZW50KGVsZW1lbnQsIGNvbXBvbmVudC52YWx1ZVN0b3JlKSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cbn1cblxuY2xhc3MgVmFsaWRhdGVkRmllbGRzUGx1Z2luIHtcbiAgICBhdHRhY2hUb0NvbXBvbmVudChjb21wb25lbnQpIHtcbiAgICAgICAgY29tcG9uZW50Lm9uKCdtb2RlbDpzZXQnLCAobW9kZWxOYW1lKSA9PiB7XG4gICAgICAgICAgICB0aGlzLmhhbmRsZU1vZGVsU2V0KG1vZGVsTmFtZSwgY29tcG9uZW50LnZhbHVlU3RvcmUpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgaGFuZGxlTW9kZWxTZXQobW9kZWxOYW1lLCB2YWx1ZVN0b3JlKSB7XG4gICAgICAgIGlmICh2YWx1ZVN0b3JlLmhhcygndmFsaWRhdGVkRmllbGRzJykpIHtcbiAgICAgICAgICAgIGNvbnN0IHZhbGlkYXRlZEZpZWxkcyA9IFsuLi52YWx1ZVN0b3JlLmdldCgndmFsaWRhdGVkRmllbGRzJyldO1xuICAgICAgICAgICAgaWYgKCF2YWxpZGF0ZWRGaWVsZHMuaW5jbHVkZXMobW9kZWxOYW1lKSkge1xuICAgICAgICAgICAgICAgIHZhbGlkYXRlZEZpZWxkcy5wdXNoKG1vZGVsTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB2YWx1ZVN0b3JlLnNldCgndmFsaWRhdGVkRmllbGRzJywgdmFsaWRhdGVkRmllbGRzKTtcbiAgICAgICAgfVxuICAgIH1cbn1cblxuY2xhc3MgTGl2ZUNvbnRyb2xsZXJEZWZhdWx0IGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHN1cGVyKC4uLmFyZ3VtZW50cyk7XG4gICAgICAgIHRoaXMucGVuZGluZ0FjdGlvblRyaWdnZXJNb2RlbEVsZW1lbnQgPSBudWxsO1xuICAgICAgICB0aGlzLmVsZW1lbnRFdmVudExpc3RlbmVycyA9IFtcbiAgICAgICAgICAgIHsgZXZlbnQ6ICdpbnB1dCcsIGNhbGxiYWNrOiAoZXZlbnQpID0+IHRoaXMuaGFuZGxlSW5wdXRFdmVudChldmVudCkgfSxcbiAgICAgICAgICAgIHsgZXZlbnQ6ICdjaGFuZ2UnLCBjYWxsYmFjazogKGV2ZW50KSA9PiB0aGlzLmhhbmRsZUNoYW5nZUV2ZW50KGV2ZW50KSB9LFxuICAgICAgICBdO1xuICAgICAgICB0aGlzLnBlbmRpbmdGaWxlcyA9IHt9O1xuICAgIH1cbiAgICBpbml0aWFsaXplKCkge1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIgPSBuZXcgTXV0YXRpb25PYnNlcnZlcih0aGlzLm9uTXV0YXRpb25zLmJpbmQodGhpcykpO1xuICAgICAgICB0aGlzLmNyZWF0ZUNvbXBvbmVudCgpO1xuICAgIH1cbiAgICBjb25uZWN0KCkge1xuICAgICAgICB0aGlzLmNvbm5lY3RDb21wb25lbnQoKTtcbiAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtZW50LCB7XG4gICAgICAgICAgICBhdHRyaWJ1dGVzOiB0cnVlLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZGlzY29ubmVjdCgpIHtcbiAgICAgICAgdGhpcy5kaXNjb25uZWN0Q29tcG9uZW50KCk7XG4gICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgfVxuICAgIHVwZGF0ZShldmVudCkge1xuICAgICAgICBpZiAoZXZlbnQudHlwZSA9PT0gJ2lucHV0JyB8fCBldmVudC50eXBlID09PSAnY2hhbmdlJykge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBTaW5jZSBMaXZlQ29tcG9uZW50cyAyLjMsIHlvdSBubyBsb25nZXIgbmVlZCBkYXRhLWFjdGlvbj1cImxpdmUjdXBkYXRlXCIgb24gZm9ybSBlbGVtZW50cy4gRm91bmQgb24gZWxlbWVudDogJHtnZXRFbGVtZW50QXNUYWdUZXh0KGV2ZW50LmN1cnJlbnRUYXJnZXQpfWApO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMudXBkYXRlTW9kZWxGcm9tRWxlbWVudEV2ZW50KGV2ZW50LmN1cnJlbnRUYXJnZXQsIG51bGwpO1xuICAgIH1cbiAgICBhY3Rpb24oZXZlbnQpIHtcbiAgICAgICAgY29uc3QgcGFyYW1zID0gZXZlbnQucGFyYW1zO1xuICAgICAgICBpZiAoIXBhcmFtcy5hY3Rpb24pIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgTm8gYWN0aW9uIG5hbWUgcHJvdmlkZWQgb24gZWxlbWVudDogJHtnZXRFbGVtZW50QXNUYWdUZXh0KGV2ZW50LmN1cnJlbnRUYXJnZXQpfS4gRGlkIHlvdSBmb3JnZXQgdG8gYWRkIHRoZSBcImRhdGEtbGl2ZS1hY3Rpb24tcGFyYW1cIiBhdHRyaWJ1dGU/YCk7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgcmF3QWN0aW9uID0gcGFyYW1zLmFjdGlvbjtcbiAgICAgICAgY29uc3QgYWN0aW9uQXJncyA9IHsgLi4ucGFyYW1zIH07XG4gICAgICAgIGRlbGV0ZSBhY3Rpb25BcmdzLmFjdGlvbjtcbiAgICAgICAgY29uc3QgZGlyZWN0aXZlcyA9IHBhcnNlRGlyZWN0aXZlcyhyYXdBY3Rpb24pO1xuICAgICAgICBsZXQgZGVib3VuY2UgPSBmYWxzZTtcbiAgICAgICAgZGlyZWN0aXZlcy5mb3JFYWNoKChkaXJlY3RpdmUpID0+IHtcbiAgICAgICAgICAgIGxldCBwZW5kaW5nRmlsZXMgPSB7fTtcbiAgICAgICAgICAgIGNvbnN0IHZhbGlkTW9kaWZpZXJzID0gbmV3IE1hcCgpO1xuICAgICAgICAgICAgdmFsaWRNb2RpZmllcnMuc2V0KCdzdG9wJywgKCkgPT4ge1xuICAgICAgICAgICAgICAgIGV2ZW50LnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB2YWxpZE1vZGlmaWVycy5zZXQoJ3NlbGYnLCAoKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKGV2ZW50LnRhcmdldCAhPT0gZXZlbnQuY3VycmVudFRhcmdldCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB2YWxpZE1vZGlmaWVycy5zZXQoJ2RlYm91bmNlJywgKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICAgICAgZGVib3VuY2UgPSBtb2RpZmllci52YWx1ZSA/IE51bWJlci5wYXJzZUludChtb2RpZmllci52YWx1ZSkgOiB0cnVlO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB2YWxpZE1vZGlmaWVycy5zZXQoJ2ZpbGVzJywgKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKCFtb2RpZmllci52YWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICBwZW5kaW5nRmlsZXMgPSB0aGlzLnBlbmRpbmdGaWxlcztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSBpZiAodGhpcy5wZW5kaW5nRmlsZXNbbW9kaWZpZXIudmFsdWVdKSB7XG4gICAgICAgICAgICAgICAgICAgIHBlbmRpbmdGaWxlc1ttb2RpZmllci52YWx1ZV0gPSB0aGlzLnBlbmRpbmdGaWxlc1ttb2RpZmllci52YWx1ZV07XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBkaXJlY3RpdmUubW9kaWZpZXJzLmZvckVhY2goKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICAgICAgaWYgKHZhbGlkTW9kaWZpZXJzLmhhcyhtb2RpZmllci5uYW1lKSkge1xuICAgICAgICAgICAgICAgICAgICBjb25zdCBjYWxsYWJsZSA9IHZhbGlkTW9kaWZpZXJzLmdldChtb2RpZmllci5uYW1lKSA/PyAoKCkgPT4geyB9KTtcbiAgICAgICAgICAgICAgICAgICAgY2FsbGFibGUobW9kaWZpZXIpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGNvbnNvbGUud2FybihgVW5rbm93biBtb2RpZmllciAke21vZGlmaWVyLm5hbWV9IGluIGFjdGlvbiBcIiR7cmF3QWN0aW9ufVwiLiBBdmFpbGFibGUgbW9kaWZpZXJzIGFyZTogJHtBcnJheS5mcm9tKHZhbGlkTW9kaWZpZXJzLmtleXMoKSkuam9pbignLCAnKX0uYCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgW2tleSwgaW5wdXRdIG9mIE9iamVjdC5lbnRyaWVzKHBlbmRpbmdGaWxlcykpIHtcbiAgICAgICAgICAgICAgICBpZiAoaW5wdXQuZmlsZXMpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5jb21wb25lbnQuZmlsZXMoa2V5LCBpbnB1dCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGRlbGV0ZSB0aGlzLnBlbmRpbmdGaWxlc1trZXldO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGhpcy5jb21wb25lbnQuYWN0aW9uKGRpcmVjdGl2ZS5hY3Rpb24sIGFjdGlvbkFyZ3MsIGRlYm91bmNlKTtcbiAgICAgICAgICAgIGlmIChnZXRNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50KGV2ZW50LmN1cnJlbnRUYXJnZXQsIGZhbHNlKSkge1xuICAgICAgICAgICAgICAgIHRoaXMucGVuZGluZ0FjdGlvblRyaWdnZXJNb2RlbEVsZW1lbnQgPSBldmVudC5jdXJyZW50VGFyZ2V0O1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgJHJlbmRlcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29tcG9uZW50LnJlbmRlcigpO1xuICAgIH1cbiAgICBlbWl0KGV2ZW50KSB7XG4gICAgICAgIHRoaXMuZ2V0RW1pdERpcmVjdGl2ZXMoZXZlbnQpLmZvckVhY2goKHsgbmFtZSwgZGF0YSwgbmFtZU1hdGNoIH0pID0+IHtcbiAgICAgICAgICAgIHRoaXMuY29tcG9uZW50LmVtaXQobmFtZSwgZGF0YSwgbmFtZU1hdGNoKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGVtaXRVcChldmVudCkge1xuICAgICAgICB0aGlzLmdldEVtaXREaXJlY3RpdmVzKGV2ZW50KS5mb3JFYWNoKCh7IG5hbWUsIGRhdGEsIG5hbWVNYXRjaCB9KSA9PiB7XG4gICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5lbWl0VXAobmFtZSwgZGF0YSwgbmFtZU1hdGNoKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGVtaXRTZWxmKGV2ZW50KSB7XG4gICAgICAgIHRoaXMuZ2V0RW1pdERpcmVjdGl2ZXMoZXZlbnQpLmZvckVhY2goKHsgbmFtZSwgZGF0YSB9KSA9PiB7XG4gICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5lbWl0U2VsZihuYW1lLCBkYXRhKTtcbiAgICAgICAgfSk7XG4gICAgfVxuICAgICR1cGRhdGVNb2RlbChtb2RlbCwgdmFsdWUsIHNob3VsZFJlbmRlciA9IHRydWUsIGRlYm91bmNlID0gdHJ1ZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb21wb25lbnQuc2V0KG1vZGVsLCB2YWx1ZSwgc2hvdWxkUmVuZGVyLCBkZWJvdW5jZSk7XG4gICAgfVxuICAgIHByb3BzVXBkYXRlZEZyb21QYXJlbnRWYWx1ZUNoYW5nZWQoKSB7XG4gICAgICAgIHRoaXMuY29tcG9uZW50Ll91cGRhdGVGcm9tUGFyZW50UHJvcHModGhpcy5wcm9wc1VwZGF0ZWRGcm9tUGFyZW50VmFsdWUpO1xuICAgIH1cbiAgICBmaW5nZXJwcmludFZhbHVlQ2hhbmdlZCgpIHtcbiAgICAgICAgdGhpcy5jb21wb25lbnQuZmluZ2VycHJpbnQgPSB0aGlzLmZpbmdlcnByaW50VmFsdWU7XG4gICAgfVxuICAgIGdldEVtaXREaXJlY3RpdmVzKGV2ZW50KSB7XG4gICAgICAgIGNvbnN0IHBhcmFtcyA9IGV2ZW50LnBhcmFtcztcbiAgICAgICAgaWYgKCFwYXJhbXMuZXZlbnQpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgTm8gZXZlbnQgbmFtZSBwcm92aWRlZCBvbiBlbGVtZW50OiAke2dldEVsZW1lbnRBc1RhZ1RleHQoZXZlbnQuY3VycmVudFRhcmdldCl9LiBEaWQgeW91IGZvcmdldCB0byBhZGQgdGhlIFwiZGF0YS1saXZlLWV2ZW50LXBhcmFtXCIgYXR0cmlidXRlP2ApO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGV2ZW50SW5mbyA9IHBhcmFtcy5ldmVudDtcbiAgICAgICAgY29uc3QgZXZlbnRBcmdzID0geyAuLi5wYXJhbXMgfTtcbiAgICAgICAgZGVsZXRlIGV2ZW50QXJncy5ldmVudDtcbiAgICAgICAgY29uc3QgZGlyZWN0aXZlcyA9IHBhcnNlRGlyZWN0aXZlcyhldmVudEluZm8pO1xuICAgICAgICBjb25zdCBlbWl0cyA9IFtdO1xuICAgICAgICBkaXJlY3RpdmVzLmZvckVhY2goKGRpcmVjdGl2ZSkgPT4ge1xuICAgICAgICAgICAgbGV0IG5hbWVNYXRjaCA9IG51bGw7XG4gICAgICAgICAgICBkaXJlY3RpdmUubW9kaWZpZXJzLmZvckVhY2goKG1vZGlmaWVyKSA9PiB7XG4gICAgICAgICAgICAgICAgc3dpdGNoIChtb2RpZmllci5uYW1lKSB7XG4gICAgICAgICAgICAgICAgICAgIGNhc2UgJ25hbWUnOlxuICAgICAgICAgICAgICAgICAgICAgICAgbmFtZU1hdGNoID0gbW9kaWZpZXIudmFsdWU7XG4gICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgVW5rbm93biBtb2RpZmllciAke21vZGlmaWVyLm5hbWV9IGluIGV2ZW50IFwiJHtldmVudEluZm99XCIuYCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBlbWl0cy5wdXNoKHtcbiAgICAgICAgICAgICAgICBuYW1lOiBkaXJlY3RpdmUuYWN0aW9uLFxuICAgICAgICAgICAgICAgIGRhdGE6IGV2ZW50QXJncyxcbiAgICAgICAgICAgICAgICBuYW1lTWF0Y2gsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiBlbWl0cztcbiAgICB9XG4gICAgY3JlYXRlQ29tcG9uZW50KCkge1xuICAgICAgICBjb25zdCBpZCA9IHRoaXMuZWxlbWVudC5pZCB8fCBudWxsO1xuICAgICAgICB0aGlzLmNvbXBvbmVudCA9IG5ldyBDb21wb25lbnQodGhpcy5lbGVtZW50LCB0aGlzLm5hbWVWYWx1ZSwgdGhpcy5wcm9wc1ZhbHVlLCB0aGlzLmxpc3RlbmVyc1ZhbHVlLCBpZCwgTGl2ZUNvbnRyb2xsZXJEZWZhdWx0LmJhY2tlbmRGYWN0b3J5KHRoaXMpLCBuZXcgU3RpbXVsdXNFbGVtZW50RHJpdmVyKHRoaXMpKTtcbiAgICAgICAgdGhpcy5wcm94aWVkQ29tcG9uZW50ID0gcHJveGlmeUNvbXBvbmVudCh0aGlzLmNvbXBvbmVudCk7XG4gICAgICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eSh0aGlzLmVsZW1lbnQsICdfX2NvbXBvbmVudCcsIHtcbiAgICAgICAgICAgIHZhbHVlOiB0aGlzLnByb3hpZWRDb21wb25lbnQsXG4gICAgICAgICAgICB3cml0YWJsZTogdHJ1ZSxcbiAgICAgICAgfSk7XG4gICAgICAgIGlmICh0aGlzLmhhc0RlYm91bmNlVmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuY29tcG9uZW50LmRlZmF1bHREZWJvdW5jZSA9IHRoaXMuZGVib3VuY2VWYWx1ZTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBwbHVnaW5zID0gW1xuICAgICAgICAgICAgbmV3IExvYWRpbmdQbHVnaW4oKSxcbiAgICAgICAgICAgIG5ldyBMYXp5UGx1Z2luKCksXG4gICAgICAgICAgICBuZXcgVmFsaWRhdGVkRmllbGRzUGx1Z2luKCksXG4gICAgICAgICAgICBuZXcgUGFnZVVubG9hZGluZ1BsdWdpbigpLFxuICAgICAgICAgICAgbmV3IFBvbGxpbmdQbHVnaW4oKSxcbiAgICAgICAgICAgIG5ldyBTZXRWYWx1ZU9udG9Nb2RlbEZpZWxkc1BsdWdpbigpLFxuICAgICAgICAgICAgbmV3IFF1ZXJ5U3RyaW5nUGx1Z2luKHRoaXMucXVlcnlNYXBwaW5nVmFsdWUpLFxuICAgICAgICAgICAgbmV3IENoaWxkQ29tcG9uZW50UGx1Z2luKHRoaXMuY29tcG9uZW50KSxcbiAgICAgICAgXTtcbiAgICAgICAgcGx1Z2lucy5mb3JFYWNoKChwbHVnaW4pID0+IHtcbiAgICAgICAgICAgIHRoaXMuY29tcG9uZW50LmFkZFBsdWdpbihwbHVnaW4pO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgY29ubmVjdENvbXBvbmVudCgpIHtcbiAgICAgICAgdGhpcy5jb21wb25lbnQuY29ubmVjdCgpO1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1lbnQsIHtcbiAgICAgICAgICAgIGF0dHJpYnV0ZXM6IHRydWUsXG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLmVsZW1lbnRFdmVudExpc3RlbmVycy5mb3JFYWNoKCh7IGV2ZW50LCBjYWxsYmFjayB9KSA9PiB7XG4gICAgICAgICAgICB0aGlzLmNvbXBvbmVudC5lbGVtZW50LmFkZEV2ZW50TGlzdGVuZXIoZXZlbnQsIGNhbGxiYWNrKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMuZGlzcGF0Y2hFdmVudCgnY29ubmVjdCcpO1xuICAgIH1cbiAgICBkaXNjb25uZWN0Q29tcG9uZW50KCkge1xuICAgICAgICB0aGlzLmNvbXBvbmVudC5kaXNjb25uZWN0KCk7XG4gICAgICAgIHRoaXMuZWxlbWVudEV2ZW50TGlzdGVuZXJzLmZvckVhY2goKHsgZXZlbnQsIGNhbGxiYWNrIH0pID0+IHtcbiAgICAgICAgICAgIHRoaXMuY29tcG9uZW50LmVsZW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcihldmVudCwgY2FsbGJhY2spO1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy5kaXNwYXRjaEV2ZW50KCdkaXNjb25uZWN0Jyk7XG4gICAgfVxuICAgIGhhbmRsZUlucHV0RXZlbnQoZXZlbnQpIHtcbiAgICAgICAgY29uc3QgdGFyZ2V0ID0gZXZlbnQudGFyZ2V0O1xuICAgICAgICBpZiAoIXRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMudXBkYXRlTW9kZWxGcm9tRWxlbWVudEV2ZW50KHRhcmdldCwgJ2lucHV0Jyk7XG4gICAgfVxuICAgIGhhbmRsZUNoYW5nZUV2ZW50KGV2ZW50KSB7XG4gICAgICAgIGNvbnN0IHRhcmdldCA9IGV2ZW50LnRhcmdldDtcbiAgICAgICAgaWYgKCF0YXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLnVwZGF0ZU1vZGVsRnJvbUVsZW1lbnRFdmVudCh0YXJnZXQsICdjaGFuZ2UnKTtcbiAgICB9XG4gICAgdXBkYXRlTW9kZWxGcm9tRWxlbWVudEV2ZW50KGVsZW1lbnQsIGV2ZW50TmFtZSkge1xuICAgICAgICBpZiAoIWVsZW1lbnRCZWxvbmdzVG9UaGlzQ29tcG9uZW50KGVsZW1lbnQsIHRoaXMuY29tcG9uZW50KSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGlmICghKGVsZW1lbnQgaW5zdGFuY2VvZiBIVE1MRWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignQ291bGQgbm90IHVwZGF0ZSBtb2RlbCBmb3Igbm9uIEhUTUxFbGVtZW50Jyk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGVsZW1lbnQgaW5zdGFuY2VvZiBIVE1MSW5wdXRFbGVtZW50ICYmIGVsZW1lbnQudHlwZSA9PT0gJ2ZpbGUnKSB7XG4gICAgICAgICAgICBjb25zdCBrZXkgPSBlbGVtZW50Lm5hbWU7XG4gICAgICAgICAgICBpZiAoZWxlbWVudC5maWxlcz8ubGVuZ3RoKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wZW5kaW5nRmlsZXNba2V5XSA9IGVsZW1lbnQ7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmICh0aGlzLnBlbmRpbmdGaWxlc1trZXldKSB7XG4gICAgICAgICAgICAgICAgZGVsZXRlIHRoaXMucGVuZGluZ0ZpbGVzW2tleV07XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgbW9kZWxEaXJlY3RpdmUgPSBnZXRNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50KGVsZW1lbnQsIGZhbHNlKTtcbiAgICAgICAgaWYgKCFtb2RlbERpcmVjdGl2ZSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IG1vZGVsQmluZGluZyA9IGdldE1vZGVsQmluZGluZyhtb2RlbERpcmVjdGl2ZSk7XG4gICAgICAgIGlmICghbW9kZWxCaW5kaW5nLnRhcmdldEV2ZW50TmFtZSkge1xuICAgICAgICAgICAgbW9kZWxCaW5kaW5nLnRhcmdldEV2ZW50TmFtZSA9ICdpbnB1dCc7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMucGVuZGluZ0FjdGlvblRyaWdnZXJNb2RlbEVsZW1lbnQgPT09IGVsZW1lbnQpIHtcbiAgICAgICAgICAgIG1vZGVsQmluZGluZy5zaG91bGRSZW5kZXIgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoZXZlbnROYW1lID09PSAnY2hhbmdlJyAmJiBtb2RlbEJpbmRpbmcudGFyZ2V0RXZlbnROYW1lID09PSAnaW5wdXQnKSB7XG4gICAgICAgICAgICBtb2RlbEJpbmRpbmcudGFyZ2V0RXZlbnROYW1lID0gJ2NoYW5nZSc7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGV2ZW50TmFtZSAmJiBtb2RlbEJpbmRpbmcudGFyZ2V0RXZlbnROYW1lICE9PSBldmVudE5hbWUpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBpZiAoZmFsc2UgPT09IG1vZGVsQmluZGluZy5kZWJvdW5jZSkge1xuICAgICAgICAgICAgaWYgKG1vZGVsQmluZGluZy50YXJnZXRFdmVudE5hbWUgPT09ICdpbnB1dCcpIHtcbiAgICAgICAgICAgICAgICBtb2RlbEJpbmRpbmcuZGVib3VuY2UgPSB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgbW9kZWxCaW5kaW5nLmRlYm91bmNlID0gMDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBjb25zdCBmaW5hbFZhbHVlID0gZ2V0VmFsdWVGcm9tRWxlbWVudChlbGVtZW50LCB0aGlzLmNvbXBvbmVudC52YWx1ZVN0b3JlKTtcbiAgICAgICAgdGhpcy5jb21wb25lbnQuc2V0KG1vZGVsQmluZGluZy5tb2RlbE5hbWUsIGZpbmFsVmFsdWUsIG1vZGVsQmluZGluZy5zaG91bGRSZW5kZXIsIG1vZGVsQmluZGluZy5kZWJvdW5jZSk7XG4gICAgfVxuICAgIGRpc3BhdGNoRXZlbnQobmFtZSwgZGV0YWlsID0ge30sIGNhbkJ1YmJsZSA9IHRydWUsIGNhbmNlbGFibGUgPSBmYWxzZSkge1xuICAgICAgICBkZXRhaWwuY29udHJvbGxlciA9IHRoaXM7XG4gICAgICAgIGRldGFpbC5jb21wb25lbnQgPSB0aGlzLnByb3hpZWRDb21wb25lbnQ7XG4gICAgICAgIHRoaXMuZGlzcGF0Y2gobmFtZSwgeyBkZXRhaWwsIHByZWZpeDogJ2xpdmUnLCBjYW5jZWxhYmxlLCBidWJibGVzOiBjYW5CdWJibGUgfSk7XG4gICAgfVxuICAgIG9uTXV0YXRpb25zKG11dGF0aW9ucykge1xuICAgICAgICBtdXRhdGlvbnMuZm9yRWFjaCgobXV0YXRpb24pID0+IHtcbiAgICAgICAgICAgIGlmIChtdXRhdGlvbi50eXBlID09PSAnYXR0cmlidXRlcycgJiZcbiAgICAgICAgICAgICAgICBtdXRhdGlvbi5hdHRyaWJ1dGVOYW1lID09PSAnaWQnICYmXG4gICAgICAgICAgICAgICAgdGhpcy5lbGVtZW50LmlkICE9PSB0aGlzLmNvbXBvbmVudC5pZCkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdENvbXBvbmVudCgpO1xuICAgICAgICAgICAgICAgIHRoaXMuY3JlYXRlQ29tcG9uZW50KCk7XG4gICAgICAgICAgICAgICAgdGhpcy5jb25uZWN0Q29tcG9uZW50KCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgIH1cbn1cbkxpdmVDb250cm9sbGVyRGVmYXVsdC52YWx1ZXMgPSB7XG4gICAgbmFtZTogU3RyaW5nLFxuICAgIHVybDogU3RyaW5nLFxuICAgIHByb3BzOiB7IHR5cGU6IE9iamVjdCwgZGVmYXVsdDoge30gfSxcbiAgICBwcm9wc1VwZGF0ZWRGcm9tUGFyZW50OiB7IHR5cGU6IE9iamVjdCwgZGVmYXVsdDoge30gfSxcbiAgICBsaXN0ZW5lcnM6IHsgdHlwZTogQXJyYXksIGRlZmF1bHQ6IFtdIH0sXG4gICAgZXZlbnRzVG9FbWl0OiB7IHR5cGU6IEFycmF5LCBkZWZhdWx0OiBbXSB9LFxuICAgIGV2ZW50c1RvRGlzcGF0Y2g6IHsgdHlwZTogQXJyYXksIGRlZmF1bHQ6IFtdIH0sXG4gICAgZGVib3VuY2U6IHsgdHlwZTogTnVtYmVyLCBkZWZhdWx0OiAxNTAgfSxcbiAgICBmaW5nZXJwcmludDogeyB0eXBlOiBTdHJpbmcsIGRlZmF1bHQ6ICcnIH0sXG4gICAgcmVxdWVzdE1ldGhvZDogeyB0eXBlOiBTdHJpbmcsIGRlZmF1bHQ6ICdwb3N0JyB9LFxuICAgIHF1ZXJ5TWFwcGluZzogeyB0eXBlOiBPYmplY3QsIGRlZmF1bHQ6IHt9IH0sXG59O1xuTGl2ZUNvbnRyb2xsZXJEZWZhdWx0LmJhY2tlbmRGYWN0b3J5ID0gKGNvbnRyb2xsZXIpID0+IG5ldyBCYWNrZW5kKGNvbnRyb2xsZXIudXJsVmFsdWUsIGNvbnRyb2xsZXIucmVxdWVzdE1ldGhvZFZhbHVlKTtcblxuZXhwb3J0IHsgQ29tcG9uZW50LCBMaXZlQ29udHJvbGxlckRlZmF1bHQgYXMgZGVmYXVsdCwgZ2V0Q29tcG9uZW50IH07XG4iXSwibmFtZXMiOlsic3RhcnRTdGltdWx1c0FwcCIsImFwcCIsInJlcXVpcmUiLCJjb250ZXh0IiwiQ29udHJvbGxlciIsIl9kZWZhdWx0IiwiX0NvbnRyb2xsZXIiLCJfY2xhc3NDYWxsQ2hlY2siLCJfY2FsbFN1cGVyIiwiYXJndW1lbnRzIiwiX2luaGVyaXRzIiwiX2NyZWF0ZUNsYXNzIiwia2V5IiwidmFsdWUiLCJjb25uZWN0IiwiZWxlbWVudCIsInRleHRDb250ZW50IiwiZGVmYXVsdCIsIl9yZWdlbmVyYXRvclJ1bnRpbWUiLCJlIiwidCIsInIiLCJPYmplY3QiLCJwcm90b3R5cGUiLCJuIiwiaGFzT3duUHJvcGVydHkiLCJvIiwiZGVmaW5lUHJvcGVydHkiLCJpIiwiU3ltYm9sIiwiYSIsIml0ZXJhdG9yIiwiYyIsImFzeW5jSXRlcmF0b3IiLCJ1IiwidG9TdHJpbmdUYWciLCJkZWZpbmUiLCJlbnVtZXJhYmxlIiwiY29uZmlndXJhYmxlIiwid3JpdGFibGUiLCJ3cmFwIiwiR2VuZXJhdG9yIiwiY3JlYXRlIiwiQ29udGV4dCIsIm1ha2VJbnZva2VNZXRob2QiLCJ0cnlDYXRjaCIsInR5cGUiLCJhcmciLCJjYWxsIiwiaCIsImwiLCJmIiwicyIsInkiLCJHZW5lcmF0b3JGdW5jdGlvbiIsIkdlbmVyYXRvckZ1bmN0aW9uUHJvdG90eXBlIiwicCIsImQiLCJnZXRQcm90b3R5cGVPZiIsInYiLCJ2YWx1ZXMiLCJnIiwiZGVmaW5lSXRlcmF0b3JNZXRob2RzIiwiZm9yRWFjaCIsIl9pbnZva2UiLCJBc3luY0l0ZXJhdG9yIiwiaW52b2tlIiwiX3R5cGVvZiIsInJlc29sdmUiLCJfX2F3YWl0IiwidGhlbiIsImNhbGxJbnZva2VXaXRoTWV0aG9kQW5kQXJnIiwiRXJyb3IiLCJkb25lIiwibWV0aG9kIiwiZGVsZWdhdGUiLCJtYXliZUludm9rZURlbGVnYXRlIiwic2VudCIsIl9zZW50IiwiZGlzcGF0Y2hFeGNlcHRpb24iLCJhYnJ1cHQiLCJUeXBlRXJyb3IiLCJyZXN1bHROYW1lIiwibmV4dCIsIm5leHRMb2MiLCJwdXNoVHJ5RW50cnkiLCJ0cnlMb2MiLCJjYXRjaExvYyIsImZpbmFsbHlMb2MiLCJhZnRlckxvYyIsInRyeUVudHJpZXMiLCJwdXNoIiwicmVzZXRUcnlFbnRyeSIsImNvbXBsZXRpb24iLCJyZXNldCIsImlzTmFOIiwibGVuZ3RoIiwiZGlzcGxheU5hbWUiLCJpc0dlbmVyYXRvckZ1bmN0aW9uIiwiY29uc3RydWN0b3IiLCJuYW1lIiwibWFyayIsInNldFByb3RvdHlwZU9mIiwiX19wcm90b19fIiwiYXdyYXAiLCJhc3luYyIsIlByb21pc2UiLCJrZXlzIiwicmV2ZXJzZSIsInBvcCIsInByZXYiLCJjaGFyQXQiLCJzbGljZSIsInN0b3AiLCJydmFsIiwiaGFuZGxlIiwiY29tcGxldGUiLCJmaW5pc2giLCJfY2F0Y2giLCJkZWxlZ2F0ZVlpZWxkIiwiYXN5bmNHZW5lcmF0b3JTdGVwIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJhcHBseSIsIl9uZXh0IiwiX3Rocm93IiwiX3NsaWNlZFRvQXJyYXkiLCJfYXJyYXlXaXRoSG9sZXMiLCJfaXRlcmFibGVUb0FycmF5TGltaXQiLCJfdW5zdXBwb3J0ZWRJdGVyYWJsZVRvQXJyYXkiLCJfbm9uSXRlcmFibGVSZXN0IiwiX2FycmF5TGlrZVRvQXJyYXkiLCJ0b1N0cmluZyIsIkFycmF5IiwiZnJvbSIsInRlc3QiLCJpc0FycmF5IiwiX2RlZmluZVByb3BlcnRpZXMiLCJfdG9Qcm9wZXJ0eUtleSIsIl90b1ByaW1pdGl2ZSIsInRvUHJpbWl0aXZlIiwiU3RyaW5nIiwiTnVtYmVyIiwiQmFja2VuZFJlcXVlc3QiLCJwcm9taXNlIiwiYWN0aW9ucyIsInVwZGF0ZU1vZGVscyIsIl90aGlzIiwiaXNSZXNvbHZlZCIsInJlc3BvbnNlIiwidXBkYXRlZE1vZGVscyIsImNvbnRhaW5zT25lT2ZBY3Rpb25zIiwidGFyZ2V0ZWRBY3Rpb25zIiwiZmlsdGVyIiwiYWN0aW9uIiwiaW5jbHVkZXMiLCJhcmVBbnlNb2RlbHNVcGRhdGVkIiwidGFyZ2V0ZWRNb2RlbHMiLCJtb2RlbCIsIlJlcXVlc3RCdWlsZGVyIiwidXJsIiwidW5kZWZpbmVkIiwiYnVpbGRSZXF1ZXN0IiwicHJvcHMiLCJ1cGRhdGVkIiwiY2hpbGRyZW4iLCJ1cGRhdGVkUHJvcHNGcm9tUGFyZW50IiwiZmlsZXMiLCJzcGxpdFVybCIsInNwbGl0IiwiX3NwbGl0VXJsIiwiX3NwbGl0VXJsMiIsInF1ZXJ5U3RyaW5nIiwicGFyYW1zIiwiVVJMU2VhcmNoUGFyYW1zIiwiZmV0Y2hPcHRpb25zIiwiaGVhZGVycyIsIkFjY2VwdCIsInRvdGFsRmlsZXMiLCJlbnRyaWVzIiwicmVkdWNlIiwidG90YWwiLCJjdXJyZW50IiwiaGFzRmluZ2VycHJpbnRzIiwid2lsbERhdGFGaXRJblVybCIsIkpTT04iLCJzdHJpbmdpZnkiLCJzZXQiLCJyZXF1ZXN0RGF0YSIsInByb3BzRnJvbVBhcmVudCIsImFyZ3MiLCJjb25jYXQiLCJlbmNvZGVVUklDb21wb25lbnQiLCJmb3JtRGF0YSIsIkZvcm1EYXRhIiwiYXBwZW5kIiwiX2kiLCJfT2JqZWN0JGVudHJpZXMiLCJfT2JqZWN0JGVudHJpZXMkX2kiLCJib2R5IiwicGFyYW1zU3RyaW5nIiwicHJvcHNKc29uIiwidXBkYXRlZEpzb24iLCJjaGlsZHJlbkpzb24iLCJwcm9wc0Zyb21QYXJlbnRKc29uIiwidXJsRW5jb2RlZEpzb25EYXRhIiwiQmFja2VuZCIsInJlcXVlc3RCdWlsZGVyIiwibWFrZVJlcXVlc3QiLCJfdGhpcyRyZXF1ZXN0QnVpbGRlciQiLCJmZXRjaCIsIm1hcCIsImJhY2tlbmRBY3Rpb24iLCJCYWNrZW5kUmVzcG9uc2UiLCJfZ2V0Qm9keSIsIl9jYWxsZWUiLCJfY2FsbGVlJCIsIl9jb250ZXh0IiwidGV4dCIsImdldEJvZHkiLCJnZXRFbGVtZW50QXNUYWdUZXh0IiwiaW5uZXJIVE1MIiwib3V0ZXJIVE1MIiwiaW5kZXhPZiIsImNvbXBvbmVudE1hcEJ5RWxlbWVudCIsIldlYWtNYXAiLCJjb21wb25lbnRNYXBCeUNvbXBvbmVudCIsIk1hcCIsInJlZ2lzdGVyQ29tcG9uZW50IiwiY29tcG9uZW50IiwidW5yZWdpc3RlckNvbXBvbmVudCIsImdldENvbXBvbmVudCIsInJlamVjdCIsImNvdW50IiwibWF4Q291bnQiLCJpbnRlcnZhbCIsInNldEludGVydmFsIiwiZ2V0IiwiY2xlYXJJbnRlcnZhbCIsImZpbmRDb21wb25lbnRzIiwiY3VycmVudENvbXBvbmVudCIsIm9ubHlQYXJlbnRzIiwib25seU1hdGNoTmFtZSIsImNvbXBvbmVudHMiLCJjb21wb25lbnROYW1lIiwiY29udGFpbnMiLCJmaW5kQ2hpbGRyZW4iLCJmb3VuZENoaWxkQ29tcG9uZW50IiwiY2hpbGRDb21wb25lbnROYW1lIiwiY2hpbGRDb21wb25lbnQiLCJmaW5kUGFyZW50IiwicGFyZW50RWxlbWVudCIsIkhvb2tNYW5hZ2VyIiwiaG9va3MiLCJyZWdpc3RlciIsImhvb2tOYW1lIiwiY2FsbGJhY2siLCJ1bnJlZ2lzdGVyIiwiaW5kZXgiLCJzcGxpY2UiLCJ0cmlnZ2VySG9vayIsIl9sZW4iLCJfa2V5IiwiQ2hhbmdpbmdJdGVtc1RyYWNrZXIiLCJjaGFuZ2VkSXRlbXMiLCJyZW1vdmVkSXRlbXMiLCJzZXRJdGVtIiwiaXRlbU5hbWUiLCJuZXdWYWx1ZSIsInByZXZpb3VzVmFsdWUiLCJoYXMiLCJyZW1vdmVkUmVjb3JkIiwib3JpZ2luYWwiLCJvcmlnaW5hbFJlY29yZCIsInJlbW92ZUl0ZW0iLCJjdXJyZW50VmFsdWUiLCJ0cnVlT3JpZ2luYWxWYWx1ZSIsImdldENoYW5nZWRJdGVtcyIsIl9yZWYiLCJfcmVmMiIsImdldFJlbW92ZWRJdGVtcyIsImlzRW1wdHkiLCJzaXplIiwiRWxlbWVudENoYW5nZXMiLCJhZGRlZENsYXNzZXMiLCJTZXQiLCJyZW1vdmVkQ2xhc3NlcyIsInN0eWxlQ2hhbmdlcyIsImF0dHJpYnV0ZUNoYW5nZXMiLCJhZGRDbGFzcyIsImNsYXNzTmFtZSIsImFkZCIsInJlbW92ZUNsYXNzIiwiYWRkU3R5bGUiLCJzdHlsZU5hbWUiLCJvcmlnaW5hbFZhbHVlIiwicmVtb3ZlU3R5bGUiLCJhZGRBdHRyaWJ1dGUiLCJhdHRyaWJ1dGVOYW1lIiwicmVtb3ZlQXR0cmlidXRlIiwiZ2V0QWRkZWRDbGFzc2VzIiwiX3RvQ29uc3VtYWJsZUFycmF5IiwiZ2V0UmVtb3ZlZENsYXNzZXMiLCJnZXRDaGFuZ2VkU3R5bGVzIiwiZ2V0UmVtb3ZlZFN0eWxlcyIsImdldENoYW5nZWRBdHRyaWJ1dGVzIiwiZ2V0UmVtb3ZlZEF0dHJpYnV0ZXMiLCJhcHBseVRvRWxlbWVudCIsIl9lbGVtZW50JGNsYXNzTGlzdCIsIl9lbGVtZW50JGNsYXNzTGlzdDIiLCJjbGFzc0xpc3QiLCJyZW1vdmUiLCJjaGFuZ2UiLCJzdHlsZSIsInNldFByb3BlcnR5IiwicmVtb3ZlUHJvcGVydHkiLCJzZXRBdHRyaWJ1dGUiLCJFeHRlcm5hbE11dGF0aW9uVHJhY2tlciIsInNob3VsZFRyYWNrQ2hhbmdlQ2FsbGJhY2siLCJjaGFuZ2VkRWxlbWVudHMiLCJjaGFuZ2VkRWxlbWVudHNDb3VudCIsImFkZGVkRWxlbWVudHMiLCJyZW1vdmVkRWxlbWVudHMiLCJpc1N0YXJ0ZWQiLCJtdXRhdGlvbk9ic2VydmVyIiwiTXV0YXRpb25PYnNlcnZlciIsIm9uTXV0YXRpb25zIiwiYmluZCIsInN0YXJ0Iiwib2JzZXJ2ZSIsImNoaWxkTGlzdCIsInN1YnRyZWUiLCJhdHRyaWJ1dGVzIiwiYXR0cmlidXRlT2xkVmFsdWUiLCJkaXNjb25uZWN0IiwiZ2V0Q2hhbmdlZEVsZW1lbnQiLCJnZXRBZGRlZEVsZW1lbnRzIiwid2FzRWxlbWVudEFkZGVkIiwiaGFuZGxlUGVuZGluZ0NoYW5nZXMiLCJ0YWtlUmVjb3JkcyIsIm11dGF0aW9ucyIsImhhbmRsZWRBdHRyaWJ1dGVNdXRhdGlvbnMiLCJfaXRlcmF0b3IiLCJfY3JlYXRlRm9yT2ZJdGVyYXRvckhlbHBlciIsIl9zdGVwIiwibXV0YXRpb24iLCJ0YXJnZXQiLCJpc0VsZW1lbnRBZGRlZEJ5VHJhbnNsYXRpb24iLCJpc0NoYW5nZUluQWRkZWRFbGVtZW50IiwiX2l0ZXJhdG9yMiIsIl9zdGVwMiIsImFkZGVkRWxlbWVudCIsImVyciIsImhhbmRsZUNoaWxkTGlzdE11dGF0aW9uIiwiaGFuZGxlQXR0cmlidXRlTXV0YXRpb24iLCJfdGhpczIiLCJhZGRlZE5vZGVzIiwibm9kZSIsIkVsZW1lbnQiLCJyZW1vdmVkTm9kZXMiLCJjaGFuZ2VkRWxlbWVudCIsImhhbmRsZUNsYXNzQXR0cmlidXRlTXV0YXRpb24iLCJoYW5kbGVTdHlsZUF0dHJpYnV0ZU11dGF0aW9uIiwiaGFuZGxlR2VuZXJpY0F0dHJpYnV0ZU11dGF0aW9uIiwiZWxlbWVudENoYW5nZXMiLCJvbGRWYWx1ZSIsInByZXZpb3VzVmFsdWVzIiwibWF0Y2giLCJuZXdWYWx1ZXMiLCJhZGRlZFZhbHVlcyIsInJlbW92ZWRWYWx1ZXMiLCJwcmV2aW91c1N0eWxlcyIsImV4dHJhY3RTdHlsZXMiLCJnZXRBdHRyaWJ1dGUiLCJuZXdTdHlsZXMiLCJhZGRlZE9yQ2hhbmdlZFN0eWxlcyIsInJlbW92ZWRTdHlsZXMiLCJoYXNBdHRyaWJ1dGUiLCJzdHlsZXMiLCJzdHlsZU9iamVjdCIsInBhcnRzIiwicHJvcGVydHkiLCJ0cmltIiwiam9pbiIsInRhZ05hbWUiLCJwYXJzZURpcmVjdGl2ZXMiLCJjb250ZW50IiwiZGlyZWN0aXZlcyIsImN1cnJlbnRBY3Rpb25OYW1lIiwiY3VycmVudEFyZ3VtZW50VmFsdWUiLCJjdXJyZW50QXJndW1lbnRzIiwiY3VycmVudE1vZGlmaWVycyIsInN0YXRlIiwiZ2V0TGFzdEFjdGlvbk5hbWUiLCJwdXNoSW5zdHJ1Y3Rpb24iLCJtb2RpZmllcnMiLCJnZXRTdHJpbmciLCJwdXNoQXJndW1lbnQiLCJwdXNoTW9kaWZpZXIiLCJjaGFyIiwiY29tYmluZVNwYWNlZEFycmF5IiwiZmluYWxQYXJ0cyIsInBhcnQiLCJ0cmltQWxsIiwic3RyIiwicmVwbGFjZSIsIm5vcm1hbGl6ZU1vZGVsTmFtZSIsImdldFZhbHVlRnJvbUVsZW1lbnQiLCJ2YWx1ZVN0b3JlIiwiSFRNTElucHV0RWxlbWVudCIsIm1vZGVsTmFtZURhdGEiLCJnZXRNb2RlbERpcmVjdGl2ZUZyb21FbGVtZW50IiwibW9kZWxWYWx1ZSIsImdldE11bHRpcGxlQ2hlY2tib3hWYWx1ZSIsImNoZWNrZWQiLCJpbnB1dFZhbHVlIiwiSFRNTFNlbGVjdEVsZW1lbnQiLCJtdWx0aXBsZSIsInNlbGVjdGVkT3B0aW9ucyIsImVsIiwiZGF0YXNldCIsInNldFZhbHVlT25FbGVtZW50Iiwic29tZSIsInZhbCIsImFycmF5V3JhcHBlZFZhbHVlIiwib3B0aW9ucyIsIm9wdGlvbiIsInNlbGVjdGVkIiwiZ2V0QWxsTW9kZWxEaXJlY3RpdmVGcm9tRWxlbWVudHMiLCJkaXJlY3RpdmUiLCJ0aHJvd09uTWlzc2luZyIsImRhdGFNb2RlbERpcmVjdGl2ZXMiLCJmb3JtRWxlbWVudCIsImNsb3Nlc3QiLCJlbGVtZW50QmVsb25nc1RvVGhpc0NvbXBvbmVudCIsImNsb3Nlc3RMaXZlQ29tcG9uZW50IiwiY2xvbmVIVE1MRWxlbWVudCIsIm5ld0VsZW1lbnQiLCJjbG9uZU5vZGUiLCJIVE1MRWxlbWVudCIsImh0bWxUb0VsZW1lbnQiLCJodG1sIiwidGVtcGxhdGUiLCJkb2N1bWVudCIsImNyZWF0ZUVsZW1lbnQiLCJjaGlsZEVsZW1lbnRDb3VudCIsImNoaWxkIiwiZmlyc3RFbGVtZW50Q2hpbGQiLCJjdXJyZW50VmFsdWVzIiwiZmluYWxWYWx1ZXMiLCJJZGlvbW9ycGgiLCJFTVBUWV9TRVQiLCJkZWZhdWx0cyIsIm1vcnBoU3R5bGUiLCJjYWxsYmFja3MiLCJiZWZvcmVOb2RlQWRkZWQiLCJub09wIiwiYWZ0ZXJOb2RlQWRkZWQiLCJiZWZvcmVOb2RlTW9ycGhlZCIsImFmdGVyTm9kZU1vcnBoZWQiLCJiZWZvcmVOb2RlUmVtb3ZlZCIsImFmdGVyTm9kZVJlbW92ZWQiLCJiZWZvcmVBdHRyaWJ1dGVVcGRhdGVkIiwiaGVhZCIsInNob3VsZFByZXNlcnZlIiwiZWx0Iiwic2hvdWxkUmVBcHBlbmQiLCJzaG91bGRSZW1vdmUiLCJhZnRlckhlYWRNb3JwaGVkIiwibW9ycGgiLCJvbGROb2RlIiwibmV3Q29udGVudCIsImNvbmZpZyIsIkRvY3VtZW50IiwiZG9jdW1lbnRFbGVtZW50IiwicGFyc2VDb250ZW50Iiwibm9ybWFsaXplZENvbnRlbnQiLCJub3JtYWxpemVDb250ZW50IiwiY3R4IiwiY3JlYXRlTW9ycGhDb250ZXh0IiwibW9ycGhOb3JtYWxpemVkQ29udGVudCIsIm5vcm1hbGl6ZWROZXdDb250ZW50IiwiYmxvY2siLCJvbGRIZWFkIiwicXVlcnlTZWxlY3RvciIsIm5ld0hlYWQiLCJwcm9taXNlcyIsImhhbmRsZUhlYWRFbGVtZW50IiwiYWxsIiwiYXNzaWduIiwiaWdub3JlIiwibW9ycGhDaGlsZHJlbiIsImJlc3RNYXRjaCIsImZpbmRCZXN0Tm9kZU1hdGNoIiwicHJldmlvdXNTaWJsaW5nIiwibmV4dFNpYmxpbmciLCJtb3JwaGVkTm9kZSIsIm1vcnBoT2xkTm9kZVRvIiwiaW5zZXJ0U2libGluZ3MiLCJpZ25vcmVWYWx1ZU9mQWN0aXZlRWxlbWVudCIsInBvc3NpYmxlQWN0aXZlRWxlbWVudCIsImlnbm9yZUFjdGl2ZVZhbHVlIiwiYWN0aXZlRWxlbWVudCIsImlnbm9yZUFjdGl2ZSIsImlzU29mdE1hdGNoIiwicmVwbGFjZUNoaWxkIiwiSFRNTEhlYWRFbGVtZW50Iiwic3luY05vZGVGcm9tIiwibmV3UGFyZW50Iiwib2xkUGFyZW50IiwibmV4dE5ld0NoaWxkIiwiZmlyc3RDaGlsZCIsImluc2VydGlvblBvaW50IiwibmV3Q2hpbGQiLCJhcHBlbmRDaGlsZCIsInJlbW92ZUlkc0Zyb21Db25zaWRlcmF0aW9uIiwiaXNJZFNldE1hdGNoIiwiaWRTZXRNYXRjaCIsImZpbmRJZFNldE1hdGNoIiwicmVtb3ZlTm9kZXNCZXR3ZWVuIiwic29mdE1hdGNoIiwiZmluZFNvZnRNYXRjaCIsImluc2VydEJlZm9yZSIsInRlbXBOb2RlIiwicmVtb3ZlTm9kZSIsImlnbm9yZUF0dHJpYnV0ZSIsImF0dHIiLCJ0byIsInVwZGF0ZVR5cGUiLCJub2RlVHlwZSIsImZyb21BdHRyaWJ1dGVzIiwidG9BdHRyaWJ1dGVzIiwiX2l0ZXJhdG9yMyIsIl9zdGVwMyIsImZyb21BdHRyaWJ1dGUiLCJ0b0F0dHJpYnV0ZSIsIm5vZGVWYWx1ZSIsInN5bmNJbnB1dFZhbHVlIiwic3luY0Jvb2xlYW5BdHRyaWJ1dGUiLCJpZ25vcmVVcGRhdGUiLCJmcm9tVmFsdWUiLCJ0b1ZhbHVlIiwiSFRNTE9wdGlvbkVsZW1lbnQiLCJIVE1MVGV4dEFyZWFFbGVtZW50IiwibmV3SGVhZFRhZyIsImN1cnJlbnRIZWFkIiwiYWRkZWQiLCJyZW1vdmVkIiwicHJlc2VydmVkIiwibm9kZXNUb0FwcGVuZCIsImhlYWRNZXJnZVN0eWxlIiwic3JjVG9OZXdIZWFkTm9kZXMiLCJfaXRlcmF0b3I0IiwiX3N0ZXA0IiwibmV3SGVhZENoaWxkIiwiX2l0ZXJhdG9yNSIsIl9zdGVwNSIsImN1cnJlbnRIZWFkRWx0IiwiaW5OZXdDb250ZW50IiwiaXNSZUFwcGVuZGVkIiwiaXNQcmVzZXJ2ZWQiLCJfbG9vcCIsIm5ld05vZGUiLCJfbm9kZXNUb0FwcGVuZCIsIl9pMiIsIm5ld0VsdCIsImNyZWF0ZVJhbmdlIiwiY3JlYXRlQ29udGV4dHVhbEZyYWdtZW50IiwiaHJlZiIsInNyYyIsIl9yZXNvbHZlIiwiYWRkRXZlbnRMaXN0ZW5lciIsIl9pMyIsIl9yZW1vdmVkIiwicmVtb3ZlZEVsZW1lbnQiLCJyZW1vdmVDaGlsZCIsImtlcHQiLCJtZXJnZURlZmF1bHRzIiwiZmluYWxDb25maWciLCJpZE1hcCIsImNyZWF0ZUlkTWFwIiwiZGVhZElkcyIsIm5vZGUxIiwibm9kZTIiLCJpZCIsImdldElkSW50ZXJzZWN0aW9uQ291bnQiLCJzdGFydEluY2x1c2l2ZSIsImVuZEV4Y2x1c2l2ZSIsIm5ld0NoaWxkUG90ZW50aWFsSWRDb3VudCIsInBvdGVudGlhbE1hdGNoIiwib3RoZXJNYXRjaENvdW50IiwicG90ZW50aWFsU29mdE1hdGNoIiwic2libGluZ1NvZnRNYXRjaENvdW50IiwicGFyc2VyIiwiRE9NUGFyc2VyIiwiY29udGVudFdpdGhTdmdzUmVtb3ZlZCIsInBhcnNlRnJvbVN0cmluZyIsImdlbmVyYXRlZEJ5SWRpb21vcnBoIiwiaHRtbEVsZW1lbnQiLCJyZXNwb25zZURvYyIsImR1bW15UGFyZW50IiwiTm9kZSIsIl9pNCIsIl9hcnIiLCJzdGFjayIsImN1cnJlbnRFbGVtZW50IiwiYmVzdEVsZW1lbnQiLCJzY29yZSIsIm5ld1Njb3JlIiwic2NvcmVFbGVtZW50IiwiaXNJZEluQ29uc2lkZXJhdGlvbiIsImlkSXNXaXRoaW5Ob2RlIiwidGFyZ2V0Tm9kZSIsImlkU2V0IiwiX2l0ZXJhdG9yNiIsIl9zdGVwNiIsInNvdXJjZVNldCIsIm1hdGNoQ291bnQiLCJfaXRlcmF0b3I3IiwiX3N0ZXA3IiwicG9wdWxhdGVJZE1hcEZvck5vZGUiLCJub2RlUGFyZW50IiwiaWRFbGVtZW50cyIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJfaXRlcmF0b3I4IiwiX3N0ZXA4Iiwib2xkQ29udGVudCIsIm5vcm1hbGl6ZUF0dHJpYnV0ZXNGb3JDb21wYXJpc29uIiwiaXNGaWxlSW5wdXQiLCJzeW5jQXR0cmlidXRlcyIsImZyb21FbCIsInRvRWwiLCJleGVjdXRlTW9ycGhkb20iLCJyb290RnJvbUVsZW1lbnQiLCJyb290VG9FbGVtZW50IiwibW9kaWZpZWRGaWVsZEVsZW1lbnRzIiwiZ2V0RWxlbWVudFZhbHVlIiwiZXh0ZXJuYWxNdXRhdGlvblRyYWNrZXIiLCJvcmlnaW5hbEVsZW1lbnRJZHNUb1N3YXBBZnRlciIsIm9yaWdpbmFsRWxlbWVudHNUb1ByZXNlcnZlIiwibWFya0VsZW1lbnRBc05lZWRpbmdQb3N0TW9ycGhTd2FwIiwicmVwbGFjZVdpdGhDbG9uZSIsIm9sZEVsZW1lbnQiLCJjbG9uZWRPbGRFbGVtZW50IiwicmVwbGFjZVdpdGgiLCJfZnJvbUVsJHBhcmVudEVsZW1lbnQiLCJjbG9uZWRGcm9tRWwiLCJfX3giLCJ3aW5kb3ciLCJBbHBpbmUiLCJpbnNlcnRBZGphY2VudEVsZW1lbnQiLCJub2RlTmFtZSIsInRvVXBwZXJDYXNlIiwiaXNFcXVhbE5vZGUiLCJub3JtYWxpemVkRnJvbUVsIiwibm9ybWFsaXplZFRvRWwiLCJvcmlnaW5hbEVsZW1lbnQiLCJVbnN5bmNlZElucHV0c1RyYWNrZXIiLCJtb2RlbEVsZW1lbnRSZXNvbHZlciIsIl90aGlzMyIsImVsZW1lbnRFdmVudExpc3RlbmVycyIsImV2ZW50IiwiaGFuZGxlSW5wdXRFdmVudCIsInVuc3luY2VkSW5wdXRzIiwiVW5zeW5jZWRJbnB1dENvbnRhaW5lciIsImFjdGl2YXRlIiwiX3RoaXM0IiwiX3JlZjMiLCJkZWFjdGl2YXRlIiwiX3RoaXM1IiwiX3JlZjQiLCJyZW1vdmVFdmVudExpc3RlbmVyIiwibWFya01vZGVsQXNTeW5jZWQiLCJtb2RlbE5hbWUiLCJ1cGRhdGVNb2RlbEZyb21FbGVtZW50IiwiZ2V0TW9kZWxOYW1lIiwiZ2V0VW5zeW5jZWRJbnB1dHMiLCJhbGxVbnN5bmNlZElucHV0cyIsImdldFVuc3luY2VkTW9kZWxzIiwiZ2V0VW5zeW5jZWRNb2RlbE5hbWVzIiwicmVzZXRVbnN5bmNlZEZpZWxkcyIsInVuc3luY2VkTm9uTW9kZWxGaWVsZHMiLCJ1bnN5bmNlZE1vZGVsTmFtZXMiLCJ1bnN5bmNlZE1vZGVsRmllbGRzIiwiX3RoaXM2IiwiZ2V0RGVlcERhdGEiLCJkYXRhIiwicHJvcGVydHlQYXRoIiwiX3BhcnNlRGVlcERhdGEiLCJwYXJzZURlZXBEYXRhIiwiY3VycmVudExldmVsRGF0YSIsImZpbmFsS2V5IiwiZmluYWxEYXRhIiwicGFyc2UiLCJWYWx1ZVN0b3JlIiwiZGlydHlQcm9wcyIsInBlbmRpbmdQcm9wcyIsIm5vcm1hbGl6ZWROYW1lIiwiZ2V0T3JpZ2luYWxQcm9wcyIsIl9vYmplY3RTcHJlYWQiLCJnZXREaXJ0eVByb3BzIiwiZ2V0VXBkYXRlZFByb3BzRnJvbVBhcmVudCIsImZsdXNoRGlydHlQcm9wc1RvUGVuZGluZyIsInJlaW5pdGlhbGl6ZUFsbFByb3BzIiwicHVzaFBlbmRpbmdQcm9wc0JhY2tUb0RpcnR5Iiwic3RvcmVOZXdQcm9wc0Zyb21QYXJlbnQiLCJjaGFuZ2VkIiwiX2k1IiwiX09iamVjdCRlbnRyaWVzMiIsIl9PYmplY3QkZW50cmllczIkX2kiLCJDb21wb25lbnQiLCJsaXN0ZW5lcnMiLCJiYWNrZW5kIiwiZWxlbWVudERyaXZlciIsIl90aGlzNyIsImZpbmdlcnByaW50IiwiZGVmYXVsdERlYm91bmNlIiwiYmFja2VuZFJlcXVlc3QiLCJwZW5kaW5nQWN0aW9ucyIsInBlbmRpbmdGaWxlcyIsImlzUmVxdWVzdFBlbmRpbmciLCJyZXF1ZXN0RGVib3VuY2VUaW1lb3V0IiwibGlzdGVuZXIiLCJfdGhpczckbGlzdGVuZXJzJGdldCIsInVuc3luY2VkSW5wdXRzVHJhY2tlciIsInJlc2V0UHJvbWlzZSIsImFkZFBsdWdpbiIsInBsdWdpbiIsImF0dGFjaFRvQ29tcG9uZW50IiwiY2xlYXJSZXF1ZXN0RGVib3VuY2VUaW1lb3V0Iiwib24iLCJvZmYiLCJyZVJlbmRlciIsImRlYm91bmNlIiwibmV4dFJlcXVlc3RQcm9taXNlIiwiaXNDaGFuZ2VkIiwiZGVib3VuY2VkU3RhcnRSZXF1ZXN0IiwiZ2V0RGF0YSIsImlucHV0IiwicmVuZGVyIiwidHJ5U3RhcnRpbmdSZXF1ZXN0IiwiZW1pdCIsIm9ubHlNYXRjaGluZ0NvbXBvbmVudHNOYW1lZCIsInBlcmZvcm1FbWl0IiwiZW1pdFVwIiwiZW1pdFNlbGYiLCJkb0VtaXQiLCJtYXRjaGluZ05hbWUiLCJfdGhpczgiLCJpc1R1cmJvRW5hYmxlZCIsIlR1cmJvIiwicGVyZm9ybVJlcXVlc3QiLCJfdGhpczkiLCJ0aGlzUHJvbWlzZVJlc29sdmUiLCJuZXh0UmVxdWVzdFByb21pc2VSZXNvbHZlIiwiZmlsZXNUb1NlbmQiLCJfaTYiLCJfT2JqZWN0JGVudHJpZXMzIiwiX09iamVjdCRlbnRyaWVzMyRfaSIsInJlcXVlc3RDb25maWciLCJfcmVmNSIsIl9jYWxsZWUyIiwiX2hlYWRlcnMkZ2V0IiwiYmFja2VuZFJlc3BvbnNlIiwiX2k3IiwiX09iamVjdCR2YWx1ZXMiLCJjb250cm9scyIsIl9jYWxsZWUyJCIsIl9jb250ZXh0MiIsImRpc3BsYXlFcnJvciIsInJlbmRlckVycm9yIiwicHJvY2Vzc1JlcmVuZGVyIiwiX3giLCJfdGhpczEwIiwic2hvdWxkUmVuZGVyIiwidmlzaXQiLCJsb2NhdGlvbiIsIm1vZGlmaWVkTW9kZWxWYWx1ZXMiLCJtYXRjaGVzIiwiZXJyb3IiLCJjb25zb2xlIiwibmV3UHJvcHMiLCJnZXRDb21wb25lbnRQcm9wcyIsImV2ZW50c1RvRW1pdCIsImdldEV2ZW50c1RvRW1pdCIsImJyb3dzZXJFdmVudHNUb0Rpc3BhdGNoIiwiZ2V0QnJvd3NlckV2ZW50c1RvRGlzcGF0Y2giLCJfcmVmNiIsIl9yZWY3IiwicGF5bG9hZCIsImRpc3BhdGNoRXZlbnQiLCJDdXN0b21FdmVudCIsImRldGFpbCIsImJ1YmJsZXMiLCJjYWxjdWxhdGVEZWJvdW5jZSIsImNsZWFyVGltZW91dCIsIl90aGlzMTEiLCJzZXRUaW1lb3V0IiwibW9kYWwiLCJnZXRFbGVtZW50QnlJZCIsInBhZGRpbmciLCJiYWNrZ3JvdW5kQ29sb3IiLCJ6SW5kZXgiLCJwb3NpdGlvbiIsInRvcCIsImJvdHRvbSIsImxlZnQiLCJyaWdodCIsImRpc3BsYXkiLCJmbGV4RGlyZWN0aW9uIiwiaWZyYW1lIiwiYm9yZGVyUmFkaXVzIiwiZmxleEdyb3ciLCJwcmVwZW5kIiwib3ZlcmZsb3ciLCJjb250ZW50V2luZG93Iiwib3BlbiIsIndyaXRlIiwiY2xvc2UiLCJjbG9zZU1vZGFsIiwiZm9jdXMiLCJfdGhpczEyIiwiX3VwZGF0ZUZyb21QYXJlbnRQcm9wcyIsInByb3hpZnlDb21wb25lbnQiLCJQcm94eSIsInByb3AiLCJjYWxsYWJsZSIsIl9sZW4yIiwiX2tleTIiLCJSZWZsZWN0IiwiU3RpbXVsdXNFbGVtZW50RHJpdmVyIiwiY29udHJvbGxlciIsIm1vZGVsRGlyZWN0aXZlIiwicHJvcHNWYWx1ZSIsImV2ZW50c1RvRW1pdFZhbHVlIiwiZXZlbnRzVG9EaXNwYXRjaFZhbHVlIiwiZ2V0TW9kZWxCaW5kaW5nIiwidGFyZ2V0RXZlbnROYW1lIiwibW9kaWZpZXIiLCJwYXJzZUludCIsIl9tb2RlbERpcmVjdGl2ZSRhY3RpbyIsIl9tb2RlbERpcmVjdGl2ZSRhY3RpbzIiLCJpbm5lck1vZGVsTmFtZSIsIkNoaWxkQ29tcG9uZW50UGx1Z2luIiwicGFyZW50TW9kZWxCaW5kaW5ncyIsIm1vZGVsRGlyZWN0aXZlcyIsIl90aGlzMTMiLCJnZXRDaGlsZHJlbkZpbmdlcnByaW50cyIsIm5vdGlmeVBhcmVudE1vZGVsQ2hhbmdlIiwiZmluZ2VycHJpbnRzIiwiZ2V0Q2hpbGRyZW4iLCJ0YWciLCJ0b0xvd2VyQ2FzZSIsInBhcmVudENvbXBvbmVudCIsIm1vZGVsQmluZGluZyIsImNoaWxkTW9kZWxOYW1lIiwiTGF6eVBsdWdpbiIsImludGVyc2VjdGlvbk9ic2VydmVyIiwiX2NvbXBvbmVudCRlbGVtZW50JGF0IiwiX3RoaXMxNCIsImdldE5hbWVkSXRlbSIsImdldE9ic2VydmVyIiwiX3RoaXMxNCRpbnRlcnNlY3Rpb25PIiwidW5vYnNlcnZlIiwiSW50ZXJzZWN0aW9uT2JzZXJ2ZXIiLCJvYnNlcnZlciIsImVudHJ5IiwiaXNJbnRlcnNlY3RpbmciLCJMb2FkaW5nUGx1Z2luIiwiX3RoaXMxNSIsInJlcXVlc3QiLCJzdGFydExvYWRpbmciLCJmaW5pc2hMb2FkaW5nIiwidGFyZ2V0RWxlbWVudCIsImhhbmRsZUxvYWRpbmdUb2dnbGUiLCJpc0xvYWRpbmciLCJfdGhpczE2IiwiYWRkQXR0cmlidXRlcyIsInJlbW92ZUF0dHJpYnV0ZXMiLCJnZXRMb2FkaW5nRGlyZWN0aXZlcyIsIl9yZWY4IiwiaGFuZGxlTG9hZGluZ0RpcmVjdGl2ZSIsIl90aGlzMTciLCJmaW5hbEFjdGlvbiIsInBhcnNlTG9hZGluZ0FjdGlvbiIsImRlbGF5IiwidmFsaWRNb2RpZmllcnMiLCJfdmFsaWRNb2RpZmllcnMkZ2V0IiwibG9hZGluZ0RpcmVjdGl2ZSIsInNob3dFbGVtZW50IiwiaGlkZUVsZW1lbnQiLCJsb2FkaW5nRGlyZWN0aXZlcyIsIm1hdGNoaW5nRWxlbWVudHMiLCJTVkdFbGVtZW50IiwibG9hZGluZyIsImNsYXNzZXMiLCJfZWxlbWVudCRjbGFzc0xpc3QzIiwiX2VsZW1lbnQkY2xhc3NMaXN0NCIsImF0dHJpYnV0ZSIsIlBhZ2VVbmxvYWRpbmdQbHVnaW4iLCJpc0Nvbm5lY3RlZCIsIl90aGlzMTgiLCJQb2xsaW5nRGlyZWN0b3IiLCJpc1BvbGxpbmdBY3RpdmUiLCJwb2xsaW5nSW50ZXJ2YWxzIiwiYWRkUG9sbCIsImFjdGlvbk5hbWUiLCJkdXJhdGlvbiIsInBvbGxzIiwiaW5pdGlhdGVQb2xsIiwic3RhcnRBbGxQb2xsaW5nIiwiX3RoaXMxOSIsIl9yZWY5Iiwic3RvcEFsbFBvbGxpbmciLCJjbGVhclBvbGxpbmciLCJfdGhpczIwIiwidGltZXIiLCJQb2xsaW5nUGx1Z2luIiwiX3RoaXMyMSIsInBvbGxpbmdEaXJlY3RvciIsImluaXRpYWxpemVQb2xsaW5nIiwiX3RoaXMyMiIsInBvbGwiLCJyYXdQb2xsQ29uZmlnIiwid2FybiIsImlzVmFsdWVFbXB0eSIsIl9pOCIsIl9PYmplY3Qka2V5cyIsInRvUXVlcnlTdHJpbmciLCJidWlsZFF1ZXJ5U3RyaW5nRW50cmllcyIsImJhc2VLZXkiLCJfcmVmMTAiLCJfcmVmMTEiLCJpS2V5IiwiaVZhbHVlIiwiX3JlZjEyIiwiX3JlZjEzIiwiZnJvbVF1ZXJ5U3RyaW5nIiwic2VhcmNoIiwiaW5zZXJ0RG90Tm90YXRlZFZhbHVlSW50b0RhdGEiLCJfa2V5JHNwbGl0IiwiX2tleSRzcGxpdDIiLCJfdG9BcnJheSIsImZpcnN0Iiwic2Vjb25kIiwicmVzdCIsIl9yZWYxNCIsIl9yZWYxNSIsImRlY29kZVVSSUNvbXBvbmVudCIsImRvdE5vdGF0ZWRLZXkiLCJVcmxVdGlscyIsIl9VUkwiLCJzZXREYXRhIiwiX3dyYXBOYXRpdmVTdXBlciIsIlVSTCIsIkhpc3RvcnlTdHJhdGVneSIsImhpc3RvcnkiLCJyZXBsYWNlU3RhdGUiLCJRdWVyeVN0cmluZ1BsdWdpbiIsIm1hcHBpbmciLCJfdGhpczIzIiwidXJsVXRpbHMiLCJjdXJyZW50VXJsIiwiX3JlZjE2IiwiX3JlZjE3IiwiU2V0VmFsdWVPbnRvTW9kZWxGaWVsZHNQbHVnaW4iLCJfdGhpczI0Iiwic3luY2hyb25pemVWYWx1ZU9mTW9kZWxGaWVsZHMiLCJIVE1MRm9ybUVsZW1lbnQiLCJWYWxpZGF0ZWRGaWVsZHNQbHVnaW4iLCJfdGhpczI1IiwiaGFuZGxlTW9kZWxTZXQiLCJ2YWxpZGF0ZWRGaWVsZHMiLCJMaXZlQ29udHJvbGxlckRlZmF1bHQiLCJfdGhpczI2IiwicGVuZGluZ0FjdGlvblRyaWdnZXJNb2RlbEVsZW1lbnQiLCJoYW5kbGVDaGFuZ2VFdmVudCIsImluaXRpYWxpemUiLCJjcmVhdGVDb21wb25lbnQiLCJjb25uZWN0Q29tcG9uZW50IiwiZGlzY29ubmVjdENvbXBvbmVudCIsInVwZGF0ZSIsImN1cnJlbnRUYXJnZXQiLCJ1cGRhdGVNb2RlbEZyb21FbGVtZW50RXZlbnQiLCJfdGhpczI3IiwicmF3QWN0aW9uIiwiYWN0aW9uQXJncyIsInN0b3BQcm9wYWdhdGlvbiIsIl92YWxpZE1vZGlmaWVycyRnZXQyIiwiX2k5IiwiX09iamVjdCRlbnRyaWVzNCIsIl9PYmplY3QkZW50cmllczQkX2kiLCIkcmVuZGVyIiwiX3RoaXMyOCIsImdldEVtaXREaXJlY3RpdmVzIiwiX3JlZjE4IiwibmFtZU1hdGNoIiwiX3RoaXMyOSIsIl9yZWYxOSIsIl90aGlzMzAiLCJfcmVmMjAiLCIkdXBkYXRlTW9kZWwiLCJwcm9wc1VwZGF0ZWRGcm9tUGFyZW50VmFsdWVDaGFuZ2VkIiwicHJvcHNVcGRhdGVkRnJvbVBhcmVudFZhbHVlIiwiZmluZ2VycHJpbnRWYWx1ZUNoYW5nZWQiLCJmaW5nZXJwcmludFZhbHVlIiwiZXZlbnRJbmZvIiwiZXZlbnRBcmdzIiwiZW1pdHMiLCJfdGhpczMxIiwibmFtZVZhbHVlIiwibGlzdGVuZXJzVmFsdWUiLCJiYWNrZW5kRmFjdG9yeSIsInByb3hpZWRDb21wb25lbnQiLCJoYXNEZWJvdW5jZVZhbHVlIiwiZGVib3VuY2VWYWx1ZSIsInBsdWdpbnMiLCJxdWVyeU1hcHBpbmdWYWx1ZSIsIl90aGlzMzIiLCJfcmVmMjEiLCJfdGhpczMzIiwiX3JlZjIyIiwiZXZlbnROYW1lIiwiX2VsZW1lbnQkZmlsZXMiLCJmaW5hbFZhbHVlIiwiY2FuQnViYmxlIiwiY2FuY2VsYWJsZSIsImRpc3BhdGNoIiwicHJlZml4IiwiX3RoaXMzNCIsInByb3BzVXBkYXRlZEZyb21QYXJlbnQiLCJldmVudHNUb0Rpc3BhdGNoIiwicmVxdWVzdE1ldGhvZCIsInF1ZXJ5TWFwcGluZyIsInVybFZhbHVlIiwicmVxdWVzdE1ldGhvZFZhbHVlIl0sInNvdXJjZVJvb3QiOiIifQ==