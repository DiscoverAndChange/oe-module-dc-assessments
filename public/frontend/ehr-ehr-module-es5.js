(function () {
  function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

  function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

  function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

  (window["webpackJsonp"] = window["webpackJsonp"] || []).push([["ehr-ehr-module"], {
    /***/
    "adS0":
    /*!*******************************************!*\
      !*** ./src/app/ehr/ehr-routing.module.ts ***!
      \*******************************************/

    /*! exports provided: EhrRoutingModule */

    /***/
    function adS0(module, __webpack_exports__, __webpack_require__) {
      "use strict";

      __webpack_require__.r(__webpack_exports__);
      /* harmony export (binding) */


      __webpack_require__.d(__webpack_exports__, "EhrRoutingModule", function () {
        return EhrRoutingModule;
      });
      /* harmony import */


      var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(
      /*! @angular/core */
      "fXoL");
      /* harmony import */


      var _angular_common__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(
      /*! @angular/common */
      "ofXK");
      /* harmony import */


      var _angular_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(
      /*! @angular/router */
      "tyNb");
      /* harmony import */


      var _services_auth_guard_ehr_service__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(
      /*! ./services/auth-guard-ehr.service */
      "b4iy");
      /* harmony import */


      var app_theme_page_not_found_page_not_found_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(
      /*! app/theme/page-not-found/page-not-found.component */
      "wXBi");
      /* harmony import */


      var app_login_admin_login_admin_login_component__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(
      /*! app/login/admin-login/admin-login.component */
      "Mjyu");
      /* harmony import */


      var app_login_admin_login_finalize_admin_login_finalize_component__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(
      /*! app/login/admin-login-finalize/admin-login-finalize.component */
      "LTHS");
      /* harmony import */


      var _ehr_container_ehr_container_component__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(
      /*! ./ehr-container/ehr-container.component */
      "dGZU");
      /*
       *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
       *   All rights reserved.
       *   For License information see LICENSE.md in the root folder of this project.
       */


      var routes = [{
        path: '',
        // redirectTo: '/login'
        // ,pathMatch: 'full'
        component: _ehr_container_ehr_container_component__WEBPACK_IMPORTED_MODULE_7__["EhrContainerComponent"],
        canActivate: [],
        children: [{
          path: 'adminLogin',
          component: app_login_admin_login_admin_login_component__WEBPACK_IMPORTED_MODULE_5__["AdminLoginComponent"]
        }, {
          path: 'adminLoginFinalize',
          component: app_login_admin_login_finalize_admin_login_finalize_component__WEBPACK_IMPORTED_MODULE_6__["AdminLoginFinalizeComponent"]
        }, {
          path: 'admin',
          canActivate: [_services_auth_guard_ehr_service__WEBPACK_IMPORTED_MODULE_3__["AuthEhrGuard"]],
          'loadChildren': function loadChildren() {
            return __webpack_require__.e(
            /*! import() | admin-admin-module */
            "admin-admin-module").then(__webpack_require__.bind(null,
            /*! ../admin/admin.module */
            "jkDv")).then(function (m) {
              return m.AdminModule;
            });
          }
        }, // { path: 'assessments', 'loadChildren': () => import('./assessments/assessments.module').then(m => m.AssessmentsModule)},
        {
          path: '**',
          component: app_theme_page_not_found_page_not_found_component__WEBPACK_IMPORTED_MODULE_4__["PageNotFoundComponent"]
        }]
      }];

      var EhrRoutingModule = function EhrRoutingModule() {
        _classCallCheck(this, EhrRoutingModule);
      };

      EhrRoutingModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineNgModule"]({
        type: EhrRoutingModule
      });
      EhrRoutingModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjector"]({
        factory: function EhrRoutingModule_Factory(t) {
          return new (t || EhrRoutingModule)();
        },
        imports: [[_angular_common__WEBPACK_IMPORTED_MODULE_1__["CommonModule"], _angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"].forChild(routes)], _angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"]]
      });

      (function () {
        (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsetNgModuleScope"](EhrRoutingModule, {
          imports: [_angular_common__WEBPACK_IMPORTED_MODULE_1__["CommonModule"], _angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"]],
          exports: [_angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"]]
        });
      })();
      /*@__PURE__*/


      (function () {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](EhrRoutingModule, [{
          type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["NgModule"],
          args: [{
            imports: [_angular_common__WEBPACK_IMPORTED_MODULE_1__["CommonModule"], _angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"].forChild(routes)],
            exports: [_angular_router__WEBPACK_IMPORTED_MODULE_2__["RouterModule"]],
            declarations: []
          }]
        }], null, null);
      })();
      /***/

    },

    /***/
    "b4iy":
    /*!********************************************************!*\
      !*** ./src/app/ehr/services/auth-guard-ehr.service.ts ***!
      \********************************************************/

    /*! exports provided: AuthEhrGuard */

    /***/
    function b4iy(module, __webpack_exports__, __webpack_require__) {
      "use strict";

      __webpack_require__.r(__webpack_exports__);
      /* harmony export (binding) */


      __webpack_require__.d(__webpack_exports__, "AuthEhrGuard", function () {
        return AuthEhrGuard;
      });
      /* harmony import */


      var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(
      /*! @angular/core */
      "fXoL");
      /* harmony import */


      var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(
      /*! @angular/router */
      "tyNb");
      /* harmony import */


      var app_login_services_auth_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(
      /*! app/login/services/auth.service */
      "6Hrc");
      /* harmony import */


      var app_shared_models_role__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(
      /*! app/shared/models/role */
      "Y5fV");
      /*
       *   Copyright (c) 2023 Discover and Change
       *   @author Stephen Nielson <snielson@discoverandchange.com>
       *   All rights reserved.
       *   For License information see LICENSE.md in the root folder of this project.
       */


      var AuthEhrGuard = /*#__PURE__*/function () {
        function AuthEhrGuard(authService, router) {
          _classCallCheck(this, AuthEhrGuard);

          this.authService = authService;
          this.router = router;
        }

        _createClass(AuthEhrGuard, [{
          key: "canActivate",
          value: function canActivate(route, state) {
            var _this = this;

            return this.authService.getCurrentUser().then(function (user) {
              if (user && user.role < app_shared_models_role__WEBPACK_IMPORTED_MODULE_3__["Role"].Client) {
                return true;
              } else {
                _this.router.navigate(['/login'], {
                  queryParams: {
                    "return": state.url,
                    loginType: 1
                  }
                });

                return false;
              }
            })["catch"](function (error) {
              _this.router.navigate(['/login'], {
                queryParams: {
                  "return": state.url,
                  loginType: 1
                }
              });

              return false;
            });
          }
        }, {
          key: "canActivateChild",
          value: function canActivateChild(route, state) {
            return this.canActivate(route, state);
          }
        }]);

        return AuthEhrGuard;
      }();

      AuthEhrGuard.ɵfac = function AuthEhrGuard_Factory(t) {
        return new (t || AuthEhrGuard)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵinject"](app_login_services_auth_service__WEBPACK_IMPORTED_MODULE_2__["AuthService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵinject"](_angular_router__WEBPACK_IMPORTED_MODULE_1__["Router"]));
      };

      AuthEhrGuard.ɵprov = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjectable"]({
        token: AuthEhrGuard,
        factory: AuthEhrGuard.ɵfac
      });
      /*@__PURE__*/

      (function () {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](AuthEhrGuard, [{
          type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Injectable"]
        }], function () {
          return [{
            type: app_login_services_auth_service__WEBPACK_IMPORTED_MODULE_2__["AuthService"]
          }, {
            type: _angular_router__WEBPACK_IMPORTED_MODULE_1__["Router"]
          }];
        }, null);
      })();
      /***/

    },

    /***/
    "dGZU":
    /*!**************************************************************!*\
      !*** ./src/app/ehr/ehr-container/ehr-container.component.ts ***!
      \**************************************************************/

    /*! exports provided: EhrContainerComponent */

    /***/
    function dGZU(module, __webpack_exports__, __webpack_require__) {
      "use strict";

      __webpack_require__.r(__webpack_exports__);
      /* harmony export (binding) */


      __webpack_require__.d(__webpack_exports__, "EhrContainerComponent", function () {
        return EhrContainerComponent;
      });
      /* harmony import */


      var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(
      /*! @angular/core */
      "fXoL");
      /* harmony import */


      var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(
      /*! @angular/router */
      "tyNb");
      /* harmony import */


      var _theme_footer_footer_component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(
      /*! ../../theme/footer/footer.component */
      "NYhC");
      /*
       *   Copyright (c) 2023 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
       *   All rights reserved.
       *   For License information see LICENSE.md in the root folder of this project.
       */


      var EhrContainerComponent = /*#__PURE__*/function () {
        function EhrContainerComponent() {
          _classCallCheck(this, EhrContainerComponent);
        }

        _createClass(EhrContainerComponent, [{
          key: "ngOnDestroy",
          value: function ngOnDestroy() {
            throw new Error("Method not implemented.");
          }
        }, {
          key: "ngOnInit",
          value: function ngOnInit() {// throw new Error("Method not implemented.");
          }
        }]);

        return EhrContainerComponent;
      }();

      EhrContainerComponent.ɵfac = function EhrContainerComponent_Factory(t) {
        return new (t || EhrContainerComponent)();
      };

      EhrContainerComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({
        type: EhrContainerComponent,
        selectors: [["dac-ehr-container"]],
        hostAttrs: [1, "d-flex", "px-0", "col", "my-3"],
        decls: 6,
        vars: 0,
        consts: [[1, "container-fluid"], [1, "row", "no-gutters"], [1, "col-12", "col-lg-10", "mx-auto", "px-3", "card", "card-body"], [1, "col-12", "d-print-none"]],
        template: function EhrContainerComponent_Template(rf, ctx) {
          if (rf & 1) {
            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 0);

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "div", 1);

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "div", 2);

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](3, "router-outlet");

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](4, "div", 1);

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](5, "dac-footer", 3);

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();

            _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
          }
        },
        directives: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterOutlet"], _theme_footer_footer_component__WEBPACK_IMPORTED_MODULE_2__["FooterComponent"]],
        encapsulation: 2
      });
      /*@__PURE__*/

      (function () {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](EhrContainerComponent, [{
          type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
          args: [{
            selector: 'dac-ehr-container',
            templateUrl: './ehr-container.component.html',
            host: {
              'class': 'd-flex px-0 col my-3'
            }
          }]
        }], null, null);
      })();
      /***/

    },

    /***/
    "hVix":
    /*!***********************************!*\
      !*** ./src/app/ehr/ehr.module.ts ***!
      \***********************************/

    /*! exports provided: EhrModule */

    /***/
    function hVix(module, __webpack_exports__, __webpack_require__) {
      "use strict";

      __webpack_require__.r(__webpack_exports__);
      /* harmony export (binding) */


      __webpack_require__.d(__webpack_exports__, "EhrModule", function () {
        return EhrModule;
      });
      /* harmony import */


      var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(
      /*! @angular/core */
      "fXoL");
      /* harmony import */


      var app_login_login_module__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(
      /*! app/login/login.module */
      "X3zk");
      /* harmony import */


      var app_shared_shared_module__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(
      /*! app/shared/shared.module */
      "PCNd");
      /* harmony import */


      var app_theme_theme_module__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(
      /*! app/theme/theme.module */
      "BLWB");
      /* harmony import */


      var _ehr_container_ehr_container_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(
      /*! ./ehr-container/ehr-container.component */
      "dGZU");
      /* harmony import */


      var _ehr_routing_module__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(
      /*! ./ehr-routing.module */
      "adS0");
      /* harmony import */


      var _services_auth_guard_ehr_service__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(
      /*! ./services/auth-guard-ehr.service */
      "b4iy");
      /*
       *   Copyright (c) 2023 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
       *   All rights reserved.
       *   For License information see LICENSE.md in the root folder of this project.
       */


      var EhrModule = function EhrModule() {
        _classCallCheck(this, EhrModule);
      };

      EhrModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineNgModule"]({
        type: EhrModule
      });
      EhrModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjector"]({
        factory: function EhrModule_Factory(t) {
          return new (t || EhrModule)();
        },
        providers: [_services_auth_guard_ehr_service__WEBPACK_IMPORTED_MODULE_6__["AuthEhrGuard"]],
        imports: [[app_shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"].forRoot(), app_login_login_module__WEBPACK_IMPORTED_MODULE_1__["LoginModule"], app_theme_theme_module__WEBPACK_IMPORTED_MODULE_3__["ThemeModule"], _ehr_routing_module__WEBPACK_IMPORTED_MODULE_5__["EhrRoutingModule"]]]
      });

      (function () {
        (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsetNgModuleScope"](EhrModule, {
          declarations: [_ehr_container_ehr_container_component__WEBPACK_IMPORTED_MODULE_4__["EhrContainerComponent"]],
          imports: [app_shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"], app_login_login_module__WEBPACK_IMPORTED_MODULE_1__["LoginModule"], app_theme_theme_module__WEBPACK_IMPORTED_MODULE_3__["ThemeModule"], _ehr_routing_module__WEBPACK_IMPORTED_MODULE_5__["EhrRoutingModule"]]
        });
      })();
      /*@__PURE__*/


      (function () {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](EhrModule, [{
          type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["NgModule"],
          args: [{
            declarations: [_ehr_container_ehr_container_component__WEBPACK_IMPORTED_MODULE_4__["EhrContainerComponent"]],
            imports: [app_shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"].forRoot(), app_login_login_module__WEBPACK_IMPORTED_MODULE_1__["LoginModule"], app_theme_theme_module__WEBPACK_IMPORTED_MODULE_3__["ThemeModule"], _ehr_routing_module__WEBPACK_IMPORTED_MODULE_5__["EhrRoutingModule"]],
            schemas: [_angular_core__WEBPACK_IMPORTED_MODULE_0__["NO_ERRORS_SCHEMA"]],
            providers: [_services_auth_guard_ehr_service__WEBPACK_IMPORTED_MODULE_6__["AuthEhrGuard"]],
            bootstrap: []
          }]
        }], null, null);
      })();
      /***/

    }
  }]);
})();
//# sourceMappingURL=ehr-ehr-module-es5.js.map