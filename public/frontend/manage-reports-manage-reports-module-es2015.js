(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["manage-reports-manage-reports-module"],{

/***/ "0mHC":
/*!*****************************************************************!*\
  !*** ./src/app/manage-reports/manage-reports-routing.module.ts ***!
  \*****************************************************************/
/*! exports provided: ManageReportsRoutingModule */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ManageReportsRoutingModule", function() { return ManageReportsRoutingModule; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/router */ "tyNb");
/* harmony import */ var _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./custom-report-list/custom-report-list.component */ "aTY8");
/* harmony import */ var _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./custom-report-edit/custom-report-edit.component */ "ICsG");
/* harmony import */ var _custom_report_new_custom_report_new_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./custom-report-new/custom-report-new.component */ "fSH3");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */







const manageReportRoutes = [
    { path: '', component: _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_2__["CustomReportListComponent"] },
    { path: 'new', component: _custom_report_new_custom_report_new_component__WEBPACK_IMPORTED_MODULE_4__["CustomReportNewComponent"] },
    { path: ':reportId/edit', component: _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__["CustomReportEditComponent"] }
];
class ManageReportsRoutingModule {
}
ManageReportsRoutingModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineNgModule"]({ type: ManageReportsRoutingModule });
ManageReportsRoutingModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjector"]({ factory: function ManageReportsRoutingModule_Factory(t) { return new (t || ManageReportsRoutingModule)(); }, imports: [[
            _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"].forChild(manageReportRoutes)
        ], _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]] });
(function () { (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsetNgModuleScope"](ManageReportsRoutingModule, { imports: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]], exports: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]] }); })();
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ManageReportsRoutingModule, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["NgModule"],
        args: [{
                imports: [
                    _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"].forChild(manageReportRoutes)
                ],
                exports: [
                    _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]
                ]
            }]
    }], null, null); })();


/***/ }),

/***/ "9M+P":
/*!*************************************************************************************************!*\
  !*** ./src/app/manage-reports/html-content-item-preview/html-content-item-preview.component.ts ***!
  \*************************************************************************************************/
/*! exports provided: HtmlContentItemPreviewComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "HtmlContentItemPreviewComponent", function() { return HtmlContentItemPreviewComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_platform_browser__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/platform-browser */ "jhN1");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */




class HtmlContentItemPreviewComponent {
    constructor(domSanitizer, el) {
        this.domSanitizer = domSanitizer;
        this.el = el;
        // TODO: stephen look at abstracting this or merging this with our actual HTML-Content-Item component.
        this._classNames = ["row", "col-xs-12", "report-item-component"];
    }
    ngOnInit() {
        if (this.itemData && this.itemData.content) {
            var content = this.itemData.content;
            this._content = this.domSanitizer.bypassSecurityTrustHtml(content);
        }
        if (this.itemData.classNames && this.itemData.classNames.length) {
            this.el.nativeElement.classNames = this.itemData.classNames.concat(this._classNames);
        }
    }
    get Content() {
        return this._content;
    }
}
HtmlContentItemPreviewComponent.ɵfac = function HtmlContentItemPreviewComponent_Factory(t) { return new (t || HtmlContentItemPreviewComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_platform_browser__WEBPACK_IMPORTED_MODULE_1__["DomSanitizer"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ElementRef"])); };
HtmlContentItemPreviewComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: HtmlContentItemPreviewComponent, selectors: [["dac-html-content-item-preview"]], inputs: { itemData: "itemData" }, decls: 1, vars: 1, consts: [[3, "innerHTML"]], template: function HtmlContentItemPreviewComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](0, "div", 0);
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("innerHTML", ctx.Content, _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsanitizeHtml"]);
    } }, styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IiIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2h0bWwtY29udGVudC1pdGVtLXByZXZpZXcvaHRtbC1jb250ZW50LWl0ZW0tcHJldmlldy5jb21wb25lbnQuY3NzIn0= */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](HtmlContentItemPreviewComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-html-content-item-preview',
                templateUrl: './html-content-item-preview.component.html',
                styleUrls: ['./html-content-item-preview.component.css']
            }]
    }], function () { return [{ type: _angular_platform_browser__WEBPACK_IMPORTED_MODULE_1__["DomSanitizer"] }, { type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ElementRef"] }]; }, { itemData: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"],
            args: ["itemData"]
        }] }); })();


/***/ }),

/***/ "DbIf":
/*!*******************************************************************************!*\
  !*** ./src/app/manage-reports/item-json-editor/item-json-editor.component.ts ***!
  \*******************************************************************************/
/*! exports provided: ItemJsonEditorComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ItemJsonEditorComponent", function() { return ItemJsonEditorComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @angular/common */ "ofXK");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */





const _c0 = ["itemEditor"];
class ItemJsonEditorComponent {
    constructor(alertService) {
        this.alertService = alertService;
    }
    ngOnInit() {
    }
    setEditorData(data) {
        this.data = data;
    }
    getEditorData() {
        let text = this.itemEditor.nativeElement;
        let json = text.value;
        try {
            let data = JSON.parse(json);
            return Promise.resolve(data);
        }
        catch (error) {
            console.error(error);
            this.alertService.error("There is an error in your JSON");
            return Promise.reject(error);
        }
    }
}
ItemJsonEditorComponent.ɵfac = function ItemJsonEditorComponent_Factory(t) { return new (t || ItemJsonEditorComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"])); };
ItemJsonEditorComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ItemJsonEditorComponent, selectors: [["dac-item-json-editor"]], viewQuery: function ItemJsonEditorComponent_Query(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵviewQuery"](_c0, true);
    } if (rf & 2) {
        var _t;
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵqueryRefresh"](_t = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵloadQuery"]()) && (ctx.itemEditor = _t.first);
    } }, decls: 3, vars: 3, consts: [[1, "report-editor-window", 3, "value"], ["itemEditor", ""]], template: function ItemJsonEditorComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](0, "textarea", 0, 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](2, "json");
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](2, 1, ctx.data));
    } }, pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_2__["JsonPipe"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS1qc29uLWVkaXRvci9pdGVtLWpzb24tZWRpdG9yLmNvbXBvbmVudC5zY3NzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBOzs7O0VBQUEiLCJmaWxlIjoic3JjL2FwcC9tYW5hZ2UtcmVwb3J0cy9pdGVtLWpzb24tZWRpdG9yL2l0ZW0tanNvbi1lZGl0b3IuY29tcG9uZW50LnNjc3MiLCJzb3VyY2VzQ29udGVudCI6WyIvKlxuICogICBDb3B5cmlnaHQgKGMpIDIwMjIgRGlzY292ZXIgYW5kIENoYW5nZSBAYXV0aG9yIFN0ZXBoZW4gTmllbHNvbiA8c25pZWxzb25AZGlzY292ZXJhbmRjaGFuZ2UuY29tPlxuICogICBBbGwgcmlnaHRzIHJlc2VydmVkLlxuICogICBGb3IgTGljZW5zZSBpbmZvcm1hdGlvbiBzZWUgTElDRU5TRS5tZCBpbiB0aGUgcm9vdCBmb2xkZXIgb2YgdGhpcyBwcm9qZWN0LlxuICovXG4iXX0= */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ItemJsonEditorComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-item-json-editor',
                templateUrl: './item-json-editor.component.html',
                styleUrls: ['./item-json-editor.component.scss']
            }]
    }], function () { return [{ type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"] }]; }, { itemEditor: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewChild"],
            args: ["itemEditor"]
        }] }); })();


/***/ }),

/***/ "ICsG":
/*!***********************************************************************************!*\
  !*** ./src/app/manage-reports/custom-report-edit/custom-report-edit.component.ts ***!
  \***********************************************************************************/
/*! exports provided: CustomReportEditComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CustomReportEditComponent", function() { return CustomReportEditComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/router */ "tyNb");
/* harmony import */ var _reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../reports/services/report.service */ "iW2n");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../shared/services/host-site.service */ "Taa6");
/* harmony import */ var app_shared_services_assessment_result_hydrator_service__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! app/shared/services/assessment-result-hydrator.service */ "iFyB");
/* harmony import */ var _shared_back_button_back_button_component__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../shared/back-button/back-button.component */ "zXXW");
/* harmony import */ var _tabs_tabs_component__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../tabs/tabs.component */ "a46E");
/* harmony import */ var _tab_tab_component__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../tab/tab.component */ "Evwa");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../shared/assessment-picker/assessment-picker.component */ "GTRT");
/* harmony import */ var _shared_assessment_group_list_assessment_group_list_component__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../shared/assessment-group-list/assessment-group-list.component */ "7Zen");
/* harmony import */ var _report_item_edit_report_item_edit_component__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../report-item-edit/report-item-edit.component */ "KI1T");
/* harmony import */ var _reports_report_reviewer_container_report_viewer_container_component__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../reports/report-reviewer-container/report-viewer-container.component */ "vK7k");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */





















const _c0 = ["reportContents"];
function CustomReportEditComponent_li_17_Template(rf, ctx) { if (rf & 1) {
    const _r18 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "i", 33);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_li_17_Template_i_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r18); const link_r16 = ctx.$implicit; const ctx_r17 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r17.removeLinkedAssessment(link_r16); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const link_r16 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("", link_r16, " ");
} }
function CustomReportEditComponent_input_18_Template(rf, ctx) { if (rf & 1) {
    const _r20 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 34);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_input_18_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r20); const ctx_r19 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r19.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_dac_assessment_picker_19_Template(rf, ctx) { if (rf & 1) {
    const _r22 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "dac-assessment-picker", 35);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("selected", function CustomReportEditComponent_dac_assessment_picker_19_Template_dac_assessment_picker_selected_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r22); const ctx_r21 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r21.addLinkedAssessment($event); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_input_20_Template(rf, ctx) { if (rf & 1) {
    const _r24 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 36);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_input_20_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r24); const ctx_r23 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r23.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_ul_24_Template(rf, ctx) { if (rf & 1) {
    const _r26 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "ul");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "i", 33);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_ul_24_Template_i_click_3_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r26); const ctx_r25 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r25.removeLinkedAssessmentGroup(ctx_r25.Report); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r4 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("", ctx_r4.Report.linkedGroup == null ? null : ctx_r4.Report.linkedGroup.name, " ");
} }
function CustomReportEditComponent_input_25_Template(rf, ctx) { if (rf & 1) {
    const _r28 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 37);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_input_25_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r28); const ctx_r27 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r27.toggleAssessmentGroupPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_dac_assessment_group_list_26_Template(rf, ctx) { if (rf & 1) {
    const _r30 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "dac-assessment-group-list", 35);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("selected", function CustomReportEditComponent_dac_assessment_group_list_26_Template_dac_assessment_group_list_selected_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r30); const ctx_r29 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r29.addLinkedAssessmentGroup($event); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_input_27_Template(rf, ctx) { if (rf & 1) {
    const _r32 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 38);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_input_27_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r32); const ctx_r31 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r31.toggleAssessmentGroupPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportEditComponent_tr_43_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](7, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const variable_r33 = ctx.$implicit;
    const ctx_r8 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](variable_r33.id);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](variable_r33.type);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](ctx_r8.getVariableDescription(variable_r33));
} }
function CustomReportEditComponent_tr_65_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](9, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](11);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](12, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const result_r34 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](result_r34 == null ? null : result_r34.id);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](result_r34 == null ? null : result_r34.calculation);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](result_r34 == null ? null : result_r34.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](result_r34 == null ? null : result_r34.description);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](result_r34 == null ? null : result_r34.scales == null ? null : result_r34.scales.length);
} }
function CustomReportEditComponent_option_69_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "option", 39);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const type_r35 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", type_r35);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](type_r35);
} }
function CustomReportEditComponent_dac_report_item_edit_71_Template(rf, ctx) { if (rf & 1) {
    const _r40 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "dac-report-item-edit", 40);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("delete", function CustomReportEditComponent_dac_report_item_edit_71_Template_dac_report_item_edit_delete_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r40); const ctx_r39 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r39.removeItem($event); })("moveUp", function CustomReportEditComponent_dac_report_item_edit_71_Template_dac_report_item_edit_moveUp_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r40); const ctx_r41 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r41.moveUp($event); })("moveDown", function CustomReportEditComponent_dac_report_item_edit_71_Template_dac_report_item_edit_moveDown_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r40); const ctx_r42 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r42.moveDown($event); })("update", function CustomReportEditComponent_dac_report_item_edit_71_Template_dac_report_item_edit_update_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r40); const ctx_r43 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r43.updateItem($event); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const item_r36 = ctx.$implicit;
    const first_r37 = ctx.first;
    const last_r38 = ctx.last;
    const ctx_r12 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("first", first_r37)("last", last_r38)("item", item_r36)("report", ctx_r12.Report);
} }
function CustomReportEditComponent_dac_report_viewer_container_85_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](0, "dac-report-viewer-container", 41);
} if (rf & 2) {
    const ctx_r15 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("report", ctx_r15.report)("results", ctx_r15.previewResults)("showTitle", true);
} }
class CustomReportEditComponent {
    constructor(service, route, alertService, hostSiteService, hydrator, changeDetector) {
        this.service = service;
        this.route = route;
        this.alertService = alertService;
        this.hostSiteService = hostSiteService;
        this.hydrator = hydrator;
        this.changeDetector = changeDetector;
        this.reportItemTypes = [
            'HTMLContentItem',
            'ScaleResultsSectionsList',
            'AddoScaleResults',
            'ComputedScale',
            'ScaleResultsSummary',
            'TherapistScaleResults',
            'ClientScaleResults',
            'FlaggedQuestionsList',
            'FlaggedQuestionsListSimple',
            'PclAssessmentResults',
            'AssessmentQuestionsList',
            'TextItemComponent',
            'QuestionActionList'
        ];
        this.showAssessmentPicker = false;
        this.validHosts = [];
        this.hosts = [];
        this._hasReportPreview = false;
        this.defaultPreviewValue = '{"_id":"b59ef486-c302-46dc-b3cb-0afbd278fc2d","_assignmentItemId":"997b6fe1-7774-4a54-9bdd-ecd7fd345751","_assessment":{"_scales":[{"_id":"f49774df-4b38-2fe7-b5e0-7316de2984d4","_name":"General Well Being","_instructions":"","_answer_start_value":1,"_display_order":1,"_ranges":[{"_id":"1f9faba5-f4a5-ee2f-e0c1-efd1279eda39","_rangeStart":0,"_rangeEnd":1,"_name":"Normal","_displayName":"normal","_clientContent":"You are in the normal range.","_content":"You are in the normal range.","_mediaType":"video","_mediaURL":null,"_assignmentURL":null},{"_id":"d06a400b-9fdd-c886-d487-8c07e423be9e","_rangeStart":2,"_rangeEnd":3,"_name":"High","_displayName":"high","_clientContent":"You are in the high range","_content":"You are in the high range","_mediaType":"video","_mediaURL":null,"_assignmentURL":null}],"_deleted":false,"_multiplier":1,"_negativeToPositiveRanges":false,"_type":"Sum","_gradingData":{},"_includeInReports":true,"_rollUpSubScaleQuestions":false,"_descriptionClient":"general well being","_subScaleIds":[]}],"_question_sets":[{"_display_conditions":[],"_id":"b3f11ffd-e71f-95f1-ff43-2d173bf6e2cb","_title":"General Questions","_display_order":0,"_questions":[{"_id":"f0aa732d-8047-23c1-cfb1-9ad51727bb43","_prompt":"Are you satisfied with your general well being?","_display_order":0,"_is_reversed":false,"_scale":{"_id":"f49774df-4b38-2fe7-b5e0-7316de2984d4","_name":"General Well Being","_instructions":"","_answer_start_value":1,"_display_order":1,"_ranges":[{"_id":"1f9faba5-f4a5-ee2f-e0c1-efd1279eda39","_rangeStart":0,"_rangeEnd":1,"_name":"Normal","_displayName":"normal","_clientContent":"You are in the normal range.","_content":"You are in the normal range.","_mediaType":"video","_mediaURL":null,"_assignmentURL":null},{"_id":"d06a400b-9fdd-c886-d487-8c07e423be9e","_rangeStart":2,"_rangeEnd":3,"_name":"High","_displayName":"high","_clientContent":"You are in the high range","_content":"You are in the high range","_mediaType":"video","_mediaURL":null,"_assignmentURL":null}],"_deleted":false,"_multiplier":1,"_negativeToPositiveRanges":false,"_subScales":{},"_type":"Sum","_gradingData":{},"_includeInReports":true,"_rollUpSubScaleQuestions":false,"_descriptionClient":"general well being"},"_skip_scoring":false,"_display_index":0,"_flaggedCondition":null,"_is_flagged":false,"_computedScore":null,"_customLabels":[],"_type":1}],"_labels":[{"_id":"0a0b3d57-ddf9-ac8f-49bf-8d0e3eeff9cb","_display_order":0,"_label":"No"},{"_id":"c7d1da35-3c60-f1eb-e5ee-10dc7b362e53","_display_order":0,"_label":"Yes"}],"_answer_start_value":1,"_display_type":"MultipleChoiceGrid","_instructions":"Fill these out please"}],"_id":2536,"_display_order":1,"_enabled":1,"_instructions":"These are test instructions","_display_client_results":true,"_version":2536,"_name":"My New Assessment","_uid":"new-assessment","_resourcesURL":"","_creator":"Stephen Nielson","_creatorUrl":"https://www.discoverandchange.com","_description":"This is a test description","_isPublic":false,"token":"TestRandomAPIKey"},"_scaleResults":[{"_scaleId":"f49774df-4b38-2fe7-b5e0-7316de2984d4","_score":2,"_rangeId":"d06a400b-9fdd-c886-d487-8c07e423be9e"}],"_answers":[{"_question_id":"f0aa732d-8047-23c1-cfb1-9ad51727bb43","_answer":"c7d1da35-3c60-f1eb-e5ee-10dc7b362e53","_score":2}],"_flaggedQuestions":[]}';
    }
    ngOnInit() {
        this.report = { id: "", name: "", linkedAssessments: [], linkedGroup: null, variables: [], items: [], computedResults: [] };
        this.route.params.subscribe(params => {
            let reportId = params['reportId'];
            Promise.all([this.service.get(reportId)
                // ,this.hostSiteService.getHostSites()
            ])
                .then((results) => {
                // let [report, hostSites] = results;
                let report = results[0];
                // let hostSites = results[1];
                this.report = report;
                let hostIds = report.hostSites || [];
                this.hosts = []; // hostSites;
                this.validHosts = []; // hostSites.filter(hs => hostIds.indexOf(hs.id) >= 0);
            });
        });
    }
    ;
    renderReportPreview(resultsContent) {
        let reportData = [];
        this._hasReportPreview = false;
        this.changeDetector.detectChanges(); // run change detection to destroy the component, cleanest approach to resetting the preview.
        try {
            let data = JSON.parse(resultsContent);
            if (Array.isArray(data)) {
                reportData = data;
            }
            else {
                reportData.push(data);
            }
            this.previewResults = reportData.map(rd => this.hydrator.hydrate(rd));
            this._hasReportPreview = true;
        }
        catch (error) {
            this._hasReportPreview = false;
            this.alertService.error("Result data was invalid JSON");
            console.error(error);
        }
    }
    getVariableDescription(variable) {
        if (variable.type === "computed_scale_result") {
            let description = "Result ID: " + variable.computedResultId + ", Scale: " + variable.scale + ", Value: " + variable.value;
            return description;
        }
        return "";
    }
    get hasReportPreview() {
        return this._hasReportPreview;
    }
    get reportText() {
        if (!this.report) {
            return "";
        }
        return "<pre>" + JSON.stringify(this.report) + "</pre>";
    }
    get Report() {
        return this.report;
    }
    toggleAssessmentPicker() {
        this.showAssessmentPicker = !this.showAssessmentPicker;
    }
    addLinkedAssessment(assessment) {
        let linkedAssessments = this.report.linkedAssessments || [];
        if (linkedAssessments.indexOf(assessment.uid) < 0) {
            linkedAssessments.push(assessment.uid);
        }
        this.report.linkedAssessments = linkedAssessments;
        this.toggleAssessmentPicker();
    }
    removeLinkedAssessment(uid) {
        this.report.linkedAssessments = this.report.linkedAssessments.filter(u => u !== uid);
    }
    toggleAssessmentGroupPicker() {
        this.showAssessmentGroupPicker = !this.showAssessmentGroupPicker;
    }
    addLinkedAssessmentGroup(group) {
        this.report.linkedGroup = { id: group.Id, name: group.Name };
        this.toggleAssessmentGroupPicker();
    }
    removeLinkedAssessmentGroup() {
        this.report.linkedGroup = null;
    }
    saveReport() {
        let alert = this.alertService.info("Saving report...", 120000);
        this.report.hostSites = []; // this.validHosts.map(vh => vh.id);
        this.service.save(this.report).then(() => {
            this.alertService.clearAlert(alert);
            this.alertService.success("Report saved");
        })
            .catch((error) => {
            console.error(error);
            this.alertService.clearAlert(alert);
            this.alertService.error("There was an error saving the report.  Please check your internet connection and try again.  Or contact customer support");
        });
    }
    updateReportContents() {
        let reportContents = this.reportContents.nativeElement;
        let jsonData = reportContents.value;
        let reportJSON;
        try {
            reportJSON = JSON.parse(jsonData);
            this.report = reportJSON;
            this.alertService.info("Report contents updated.  Make sure to save the report before proceeding");
        }
        catch (error) {
            this.alertService.error("The report contains invalid JSON and could not be parsed");
            console.error(error);
        }
    }
    addNewItem(itemType) {
        let itemData = {
            type: itemType,
            title: "",
            titleIconClasses: [],
            data: {},
            display: true
        };
        let items = this.report.items || [];
        switch (itemType) {
            case 'HTMLContentItem':
                {
                    itemData.data = {
                        classNames: [],
                        content: ""
                    };
                }
                break;
            case 'ScaleResultsSectionsList':
                {
                    itemData.data = {
                        assessment: "",
                        scales: []
                    };
                }
                break;
            case 'AddoScaleResults':
                {
                    itemData.data = {
                        assessment: "",
                        scales: []
                    };
                }
                break;
            case 'ComputedScale':
                {
                    itemData.data = {
                        scale: "",
                        calculation: "SUM",
                        items: [
                            {
                                assessmentUid: "",
                                scaleId: ""
                            }
                        ]
                    };
                }
                break;
            case 'ScaleResultsSummary':
                {
                    itemData.data = {
                        title: "",
                        description: ""
                    };
                }
                break;
            case 'TherapistScaleResults':
                {
                    itemData.data = {
                        assessment: "",
                        scales_per_page: 2,
                        scales: []
                    };
                }
                break;
            case 'FlaggedQuestionsList':
            case 'FlaggedQuestionsListSimple':
                {
                    itemData.data = {
                        title: "",
                        description: "",
                        assessments: this.report.linkedAssessments
                    };
                }
                break;
            case 'PclAssessmentResults':
                {
                    itemData.data = {
                        assessment: "638"
                    };
                }
                break;
            case 'AssessmentQuestionsList':
                {
                    itemData.data = {
                        title: "",
                        assessment: ""
                    };
                }
                break;
        }
        items.push(itemData);
        this.report.items = items;
    }
    removeItem(item) {
        this.report.items = this.report.items.filter(i => i != item);
    }
    updateItem(item) {
        // for now since we do binding we don't have to worry about this for now...
        this.alertService.success("Item updated");
    }
    moveUp(item) {
        let index = this.report.items.indexOf(item);
        if (index >= 1) {
            this.report.items.splice(index, 1);
            this.report.items.splice(index - 1, 0, item);
        }
    }
    moveDown(item) {
        let index = this.report.items.indexOf(item);
        if (index >= 0 && index < this.report.items.length - 1) {
            this.report.items.splice(index, 1);
            this.report.items.splice(index + 1, 0, item);
        }
    }
    toggleHostPermission(host) {
        if (this.hasPermission(host)) {
            this.validHosts = this.validHosts.filter(vh => vh.id != host.id);
        }
        else {
            this.validHosts.push(host);
        }
    }
    hasPermission(host) {
        return this.validHosts.find(vh => vh.id == host.id);
    }
    get Hosts() {
        return this.hosts;
    }
}
CustomReportEditComponent.ɵfac = function CustomReportEditComponent_Factory(t) { return new (t || CustomReportEditComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__["ReportService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_router__WEBPACK_IMPORTED_MODULE_1__["ActivatedRoute"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_3__["AlertService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__["HostSiteService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](app_shared_services_assessment_result_hydrator_service__WEBPACK_IMPORTED_MODULE_5__["AssessmentResultHydrator"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ChangeDetectorRef"])); };
CustomReportEditComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: CustomReportEditComponent, selectors: [["dac-custom-report-edit"]], viewQuery: function CustomReportEditComponent_Query(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵviewQuery"](_c0, true);
    } if (rf & 2) {
        var _t;
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵqueryRefresh"](_t = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵloadQuery"]()) && (ctx.reportContents = _t.first);
    } }, decls: 86, vars: 21, consts: [[1, "col-1"], ["type", "button", "value", "Save", 1, "btn", "btn-primary", 3, "click"], [1, "col-12"], ["heading", "Visual Editor"], [1, "row"], [1, "form-group"], ["for", "name"], ["type", "text", "id", "name", "name", "name", 1, "form-control", 3, "ngModel", "ngModelChange"], ["for", "linkedAssessments"], [4, "ngFor", "ngForOf"], ["type", "button", "class", "btn btn-primary", "value", "Close Assessment Picker", 3, "click", 4, "ngIf"], [3, "selected", 4, "ngIf"], ["type", "button", "value", "Add Linked Assessment", 3, "click", 4, "ngIf"], ["for", "linkedGroup"], [4, "ngIf"], ["type", "button", "class", "btn btn-primary", "value", "Close", 3, "click", 4, "ngIf"], ["type", "button", "value", "Add Assessment Group", 3, "click", 4, "ngIf"], ["for", "variables"], ["for", "computedResults"], [1, "browser-default"], ["reportType", ""], [3, "value", 4, "ngFor", "ngForOf"], ["type", "button", "value", "Add Item", 1, "btn", "btn-primary", 3, "click"], ["class", "card card-body d-block m-3", 3, "first", "last", "item", "report", "delete", "moveUp", "moveDown", "update", 4, "ngFor", "ngForOf"], ["heading", "Raw Edit"], [1, "report-editor-window", 3, "value"], ["reportContents", ""], ["type", "button", "value", "Update", 1, "btn", "btn-primary", 3, "click"], ["heading", "Preview", 1, "itemsContainer"], [1, "form-control", 3, "value"], ["resultsSampleContainer", ""], [1, "btn", "btn-primary", 3, "click"], [3, "report", "results", "showTitle", 4, "ngIf"], [1, "fa", "fa-trash", "btn", "btn-primary", "btn-sm", 3, "click"], ["type", "button", "value", "Close Assessment Picker", 1, "btn", "btn-primary", 3, "click"], [3, "selected"], ["type", "button", "value", "Add Linked Assessment", 3, "click"], ["type", "button", "value", "Close", 1, "btn", "btn-primary", 3, "click"], ["type", "button", "value", "Add Assessment Group", 3, "click"], [3, "value"], [1, "card", "card-body", "d-block", "m-3", 3, "first", "last", "item", "report", "delete", "moveUp", "moveDown", "update"], [3, "report", "results", "showTitle"]], template: function CustomReportEditComponent_Template(rf, ctx) { if (rf & 1) {
        const _r44 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](1, "app-back-button");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](3, "Edit Report");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](4, "input", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_Template_input_click_4_listener() { return ctx.saveReport(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "dac-tabs", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](6, "dac-tab", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "div", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "div", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](9, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "label", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](11, "Name:");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](12, "input", 7);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function CustomReportEditComponent_Template_input_ngModelChange_12_listener($event) { return ctx.Report.name = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](13, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](14, "label", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](15, "Linked Assessments:");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](16, "ul");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](17, CustomReportEditComponent_li_17_Template, 3, 1, "li", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](18, CustomReportEditComponent_input_18_Template, 1, 0, "input", 10);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](19, CustomReportEditComponent_dac_assessment_picker_19_Template, 1, 0, "dac-assessment-picker", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](20, CustomReportEditComponent_input_20_Template, 1, 0, "input", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](21, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](22, "label", 13);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](23, "Linked Assessment Group:");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](24, CustomReportEditComponent_ul_24_Template, 4, 1, "ul", 14);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](25, CustomReportEditComponent_input_25_Template, 1, 0, "input", 15);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](26, CustomReportEditComponent_dac_assessment_group_list_26_Template, 1, 0, "dac-assessment-group-list", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](27, CustomReportEditComponent_input_27_Template, 1, 0, "input", 16);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](28, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](29, "label", 17);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](30, "Variables");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](31, "table");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](32, "thead");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](33, "tr");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](34, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](35, "ID");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](36, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](37, "Type");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](38, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](39, "Description");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](40, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](41, "Actions");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](42, "tbody");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](43, CustomReportEditComponent_tr_43_Template, 8, 3, "tr", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](44, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](45, "label", 18);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](46, "Computed Results");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](47, "table");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](48, "thead");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](49, "tr");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](50, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](51, "ID");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](52, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](53, "Calculation");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](54, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](55, "Name");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](56, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](57, "Description");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](58, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](59, "# Items");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](60, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](61, "# Scales");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](62, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](63, "Actions");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](64, "tbody");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](65, CustomReportEditComponent_tr_65_Template, 13, 5, "tr", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](66, "div", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](67, "select", 19, 20);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](69, CustomReportEditComponent_option_69_Template, 2, 2, "option", 21);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](70, "input", 22);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_Template_input_click_70_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r44); const _r10 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵreference"](68); return ctx.addNewItem(_r10.value); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](71, CustomReportEditComponent_dac_report_item_edit_71_Template, 1, 4, "dac-report-item-edit", 23);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](72, "pre");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](73);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](74, "json");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](75, "dac-tab", 24);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](76, "textarea", 25, 26);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](78, "json");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](79, "input", 27);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_Template_input_click_79_listener() { return ctx.updateReportContents(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](80, "dac-tab", 28);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](81, "textarea", 29, 30);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](83, "button", 31);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportEditComponent_Template_button_click_83_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r44); const _r14 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵreference"](82); return ctx.renderReportPreview(_r14.value); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](84, "Preview Report");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](85, CustomReportEditComponent_dac_report_viewer_container_85_Template, 1, 3, "dac-report-viewer-container", 32);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](12);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.Report.name);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Report.linkedAssessments);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.Report.linkedGroup);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentGroupPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentGroupPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.showAssessmentGroupPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](16);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Report.variables);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](22);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Report.computedResults);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.reportItemTypes);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Report.items);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](74, 17, ctx.Report));
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](78, 19, ctx.Report));
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", ctx.defaultPreviewValue);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.hasReportPreview);
    } }, directives: [_shared_back_button_back_button_component__WEBPACK_IMPORTED_MODULE_6__["BackButtonComponent"], _tabs_tabs_component__WEBPACK_IMPORTED_MODULE_7__["TabsComponent"], _tab_tab_component__WEBPACK_IMPORTED_MODULE_8__["TabComponent"], _angular_forms__WEBPACK_IMPORTED_MODULE_9__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_9__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_9__["NgModel"], _angular_common__WEBPACK_IMPORTED_MODULE_10__["NgForOf"], _angular_common__WEBPACK_IMPORTED_MODULE_10__["NgIf"], _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_11__["AssessmentPickerComponent"], _shared_assessment_group_list_assessment_group_list_component__WEBPACK_IMPORTED_MODULE_12__["AssessmentGroupListComponent"], _angular_forms__WEBPACK_IMPORTED_MODULE_9__["NgSelectOption"], _angular_forms__WEBPACK_IMPORTED_MODULE_9__["ɵangular_packages_forms_forms_x"], _report_item_edit_report_item_edit_component__WEBPACK_IMPORTED_MODULE_13__["ReportItemEditComponent"], _reports_report_reviewer_container_report_viewer_container_component__WEBPACK_IMPORTED_MODULE_14__["ReportViewerContainerComponent"]], pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_10__["JsonPipe"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IiIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2N1c3RvbS1yZXBvcnQtZWRpdC9jdXN0b20tcmVwb3J0LWVkaXQuY29tcG9uZW50LmNzcyJ9 */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](CustomReportEditComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-custom-report-edit',
                templateUrl: './custom-report-edit.component.html',
                styleUrls: ['./custom-report-edit.component.css']
            }]
    }], function () { return [{ type: _reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__["ReportService"] }, { type: _angular_router__WEBPACK_IMPORTED_MODULE_1__["ActivatedRoute"] }, { type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_3__["AlertService"] }, { type: _shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__["HostSiteService"] }, { type: app_shared_services_assessment_result_hydrator_service__WEBPACK_IMPORTED_MODULE_5__["AssessmentResultHydrator"] }, { type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ChangeDetectorRef"] }]; }, { reportContents: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewChild"],
            args: ['reportContents']
        }] }); })();


/***/ }),

/***/ "JTD5":
/*!***************************************************************************************************!*\
  !*** ./src/app/manage-reports/addo-scale-results-preview/addo-scale-results-preview.component.ts ***!
  \***************************************************************************************************/
/*! exports provided: AddoScaleResultsPreviewComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AddoScaleResultsPreviewComponent", function() { return AddoScaleResultsPreviewComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/common */ "ofXK");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */



function AddoScaleResultsPreviewComponent_p_3_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "p");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r0 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("Scales per page: ", ctx_r0.Data.scales_per_page, "");
} }
function AddoScaleResultsPreviewComponent_div_4_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, "All scales included in report");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function AddoScaleResultsPreviewComponent_div_5_li_3_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const scaleId_r4 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](scaleId_r4);
} }
function AddoScaleResultsPreviewComponent_div_5_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, " Scales ");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "ul");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, AddoScaleResultsPreviewComponent_div_5_li_3_Template, 2, 1, "li", 1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r2 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx_r2.Data.scales);
} }
class AddoScaleResultsPreviewComponent {
    constructor() { }
    ngOnInit() {
        let data = this.getDefaultData();
        if (this.itemData) {
            if (this.itemData.computed_result) {
                data = this.getDataForComputedResult(this.itemData.computed_result);
            }
            else {
                data = this.itemData;
            }
        }
        this._data = data;
    }
    get Data() {
        return this._data;
    }
    getDefaultData() {
        return {
            assessment: "None Defined",
            scales_per_page: 0,
            scales: [],
            type: 'normal'
        };
    }
    getDataForComputedResult(computedResultId) {
        let data = this.getDefaultData();
        if (!this.report || !this.report.computedResults) {
            return data;
        }
        let result = this.report.computedResults.find(cr => cr.id == computedResultId);
        if (result) {
            data.type = 'computed';
            data.assessment = result.name;
            data.scales = result.items.map(i => this.getComputedScaleDescription(result.calculation, i));
        }
        return data;
    }
    getComputedScaleDescription(calculation, scaleItem) {
        let description = scaleItem.assessmentUid + " => " + calculation + "(" + scaleItem.scaleId + ")";
        return description;
    }
}
AddoScaleResultsPreviewComponent.ɵfac = function AddoScaleResultsPreviewComponent_Factory(t) { return new (t || AddoScaleResultsPreviewComponent)(); };
AddoScaleResultsPreviewComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: AddoScaleResultsPreviewComponent, selectors: [["dac-addo-scale-results-preview"]], inputs: { itemData: "itemData", report: "report" }, decls: 6, vars: 4, consts: [[4, "ngIf"], [4, "ngFor", "ngForOf"]], template: function AddoScaleResultsPreviewComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, AddoScaleResultsPreviewComponent_p_3_Template, 2, 1, "p", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, AddoScaleResultsPreviewComponent_div_4_Template, 2, 0, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](5, AddoScaleResultsPreviewComponent_div_5_Template, 4, 1, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("Assessment: ", ctx.Data.assessment, "");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.Data.scales_per_page);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.Data.scales);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.Data.scales);
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_1__["NgIf"], _angular_common__WEBPACK_IMPORTED_MODULE_1__["NgForOf"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IiIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2FkZG8tc2NhbGUtcmVzdWx0cy1wcmV2aWV3L2FkZG8tc2NhbGUtcmVzdWx0cy1wcmV2aWV3LmNvbXBvbmVudC5jc3MifQ== */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](AddoScaleResultsPreviewComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-addo-scale-results-preview',
                templateUrl: './addo-scale-results-preview.component.html',
                styleUrls: ['./addo-scale-results-preview.component.css']
            }]
    }], function () { return []; }, { itemData: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"],
            args: ["itemData"]
        }], report: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"],
            args: ["report"]
        }] }); })();


/***/ }),

/***/ "KI1T":
/*!*******************************************************************************!*\
  !*** ./src/app/manage-reports/report-item-edit/report-item-edit.component.ts ***!
  \*******************************************************************************/
/*! exports provided: ReportItemEditComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ReportItemEditComponent", function() { return ReportItemEditComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _item_json_editor_item_json_editor_component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../item-json-editor/item-json-editor.component */ "DbIf");
/* harmony import */ var _item_html_content_editor_item_html_content_editor_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../item-html-content-editor/item-html-content-editor.component */ "uf23");
/* harmony import */ var _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../item-therapist-scale-results-editor/item-therapist-scale-results-editor.component */ "vz/w");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/* harmony import */ var _html_content_item_preview_html_content_item_preview_component__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../html-content-item-preview/html-content-item-preview.component */ "9M+P");
/* harmony import */ var _addo_scale_results_preview_addo_scale_results_preview_component__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../addo-scale-results-preview/addo-scale-results-preview.component */ "JTD5");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */











const _c0 = ["editorContainer"];
function ReportItemEditComponent_input_2_Template(rf, ctx) { if (rf & 1) {
    const _r12 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 15);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_input_2_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r12); const ctx_r11 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r11.toggleEdit(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_input_3_Template(rf, ctx) { if (rf & 1) {
    const _r14 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_input_3_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r14); const ctx_r13 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r13.toggleEdit(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_div_4_Template(rf, ctx) { if (rf & 1) {
    const _r16 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 17);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_div_4_Template_input_click_1_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r16); const ctx_r15 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r15.notifyUpdate(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_input_6_Template(rf, ctx) { if (rf & 1) {
    const _r18 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 18);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_input_6_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r18); const ctx_r17 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r17.notifyMoveUp(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_input_7_Template(rf, ctx) { if (rf & 1) {
    const _r20 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 19);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_input_7_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r20); const ctx_r19 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r19.notifyMoveDown(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_i_9_Template(rf, ctx) { if (rf & 1) {
    const _r22 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "i", 20);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_i_9_Template_i_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r22); const ctx_r21 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r21.toggleDisplay(ctx_r21.item); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_i_10_Template(rf, ctx) { if (rf & 1) {
    const _r24 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "i", 21);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_i_10_Template_i_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r24); const ctx_r23 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r23.toggleDisplay(ctx_r23.item); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ReportItemEditComponent_div_12_Template(rf, ctx) { if (rf & 1) {
    const _r26 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 12);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "label");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2, "Item Title");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "input", 22);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ReportItemEditComponent_div_12_Template_input_ngModelChange_3_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r26); const ctx_r25 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r25.item.title = $event; });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r7 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx_r7.item.title);
} }
function ReportItemEditComponent_div_13_Template(rf, ctx) { if (rf & 1) {
    const _r28 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 12);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 23);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_div_13_Template_input_click_1_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r28); const ctx_r27 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r27.toggleDisplay(ctx_r27.item); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "label", 24);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_div_13_Template_label_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r28); const ctx_r29 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r29.toggleDisplay(ctx_r29.item); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](3, "Display Item");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r8 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx_r8.item.display);
} }
function ReportItemEditComponent_div_17_div_2_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "pre");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](3, "json");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r30 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](3, 1, ctx_r30.item));
} }
function ReportItemEditComponent_div_17_dac_html_content_item_preview_3_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](0, "dac-html-content-item-preview", 28);
} if (rf & 2) {
    const ctx_r31 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("itemData", ctx_r31.item.data);
} }
function ReportItemEditComponent_div_17_dac_addo_scale_results_preview_4_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](0, "dac-addo-scale-results-preview", 29);
} if (rf & 2) {
    const ctx_r32 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("report", ctx_r32.Report)("itemData", ctx_r32.item.data);
} }
function ReportItemEditComponent_div_17_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 0);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "div", 12);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](2, ReportItemEditComponent_div_17_div_2_Template, 4, 3, "div", 25);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, ReportItemEditComponent_div_17_dac_html_content_item_preview_3_Template, 1, 1, "dac-html-content-item-preview", 26);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, ReportItemEditComponent_div_17_dac_addo_scale_results_preview_4_Template, 1, 2, "dac-addo-scale-results-preview", 27);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r10 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx_r10.item.type !== "HTMLContentItem" && ctx_r10.item.type !== "AddoScaleResults");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx_r10.item.type == "HTMLContentItem");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx_r10.item.type == "AddoScaleResults");
} }
class ReportItemEditComponent {
    constructor(alertService, resolver) {
        this.alertService = alertService;
        this.resolver = resolver;
        this.editing = false;
        this.update = new _angular_core__WEBPACK_IMPORTED_MODULE_0__["EventEmitter"]();
        this.delete = new _angular_core__WEBPACK_IMPORTED_MODULE_0__["EventEmitter"]();
        this.moveUp = new _angular_core__WEBPACK_IMPORTED_MODULE_0__["EventEmitter"]();
        this.moveDown = new _angular_core__WEBPACK_IMPORTED_MODULE_0__["EventEmitter"]();
    }
    ngOnInit() {
        // we init this as some reports don't have this value
        // TODO: stephen at some point we want to remove this as every report item will have this saved.
        if (this.item.display !== false) {
            this.item.display = true;
        }
    }
    createComponent(type, data) {
        let component = this.getComponentFromType(type);
        let factory = this.resolver.resolveComponentFactory(component);
        let componentRef = this.editorContainer.createComponent(factory);
        let reportItemComponent = componentRef.instance;
        reportItemComponent.setEditorData(data);
        return componentRef;
    }
    getComponentFromType(type) {
        var component = null;
        switch (type) {
            case 'TherapistScaleResults':
                {
                    component = _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_4__["ItemTherapistScaleResultsEditorComponent"];
                }
                break;
            case 'ClientScaleResults':
                {
                    component = _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_4__["ItemTherapistScaleResultsEditorComponent"];
                }
                break;
            case 'AddoScaleResults':
                {
                    component = _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_4__["ItemTherapistScaleResultsEditorComponent"];
                }
                break;
            case 'HTMLContentItem':
                {
                    component = _item_html_content_editor_item_html_content_editor_component__WEBPACK_IMPORTED_MODULE_3__["ItemHtmlContentEditorComponent"];
                }
                break;
            // case 'ScaleResultsSummary': {
            //   component = ItemScaleResultsSummaryEditorComponent;
            // }
            // break;
            default:
                {
                    component = _item_json_editor_item_json_editor_component__WEBPACK_IMPORTED_MODULE_2__["ItemJsonEditorComponent"];
                }
                break;
        }
        return component;
    }
    toggleEdit() {
        if (!this.editing) {
            this.itemEditor = this.createComponent(this.item.type, this.item.data);
        }
        else {
            this.itemEditor.destroy();
        }
        this.editing = !this.editing;
    }
    notifyDelete() {
        this.delete.emit(this.item);
    }
    notifyMoveUp() {
        this.moveUp.emit(this.item);
    }
    notifyMoveDown() {
        this.moveDown.emit(this.item);
    }
    toggleDisplay(item) {
        if (item.display === false) {
            item.display = true;
        }
        else {
            item.display = false;
        }
    }
    get Report() {
        return this.report || {};
    }
    notifyUpdate() {
        let data = this.itemEditor.instance.getEditorData().then((data) => {
            this.item.data = data;
            this.update.emit(this.item);
        })
            .catch((error) => {
            console.error("Save error occurred.  Detailed error from editor item: ", error);
        });
    }
}
ReportItemEditComponent.ɵfac = function ReportItemEditComponent_Factory(t) { return new (t || ReportItemEditComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ComponentFactoryResolver"])); };
ReportItemEditComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ReportItemEditComponent, selectors: [["dac-report-item-edit"]], viewQuery: function ReportItemEditComponent_Query(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵviewQuery"](_c0, true, _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewContainerRef"]);
    } if (rf & 2) {
        var _t;
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵqueryRefresh"](_t = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵloadQuery"]()) && (ctx.editorContainer = _t.first);
    } }, inputs: { item: "item", report: "report", first: "first", last: "last" }, outputs: { update: "update", delete: "delete", moveUp: "moveUp", moveDown: "moveDown" }, decls: 18, vars: 10, consts: [[1, "row"], [1, "col-1"], ["type", "button", "class", "btn btn-primary btn-sm", "value", "Edit", 3, "click", 4, "ngIf"], ["type", "button", "class", "btn btn-primary btn-sm", "value", "Close", 3, "click", 4, "ngIf"], ["class", "col-1", 4, "ngIf"], [1, "col-3", "ml-auto"], ["type", "button", "class", "btn btn-primary btn-sm", "value", "Up", 3, "click", 4, "ngIf"], ["type", "button", "class", "btn btn-primary btn-sm", "value", "Down", 3, "click", 4, "ngIf"], ["type", "button", "value", "Delete", 1, "btn", "btn-primary", "btn-sm", 3, "click"], ["class", "fa fa-eye btn btn-sm btn-primary", 3, "click", 4, "ngIf"], ["class", "fa fa-eye-slash btn btn-sm btn-primary", 3, "click", 4, "ngIf"], ["class", "col-12", 4, "ngIf"], [1, "col-12"], ["editorContainer", ""], ["class", "row", 4, "ngIf"], ["type", "button", "value", "Edit", 1, "btn", "btn-primary", "btn-sm", 3, "click"], ["type", "button", "value", "Close", 1, "btn", "btn-primary", "btn-sm", 3, "click"], ["type", "button", "value", "Update", 1, "btn", "btn-primary", "btn-sm", 3, "click"], ["type", "button", "value", "Up", 1, "btn", "btn-primary", "btn-sm", 3, "click"], ["type", "button", "value", "Down", 1, "btn", "btn-primary", "btn-sm", 3, "click"], [1, "fa", "fa-eye", "btn", "btn-sm", "btn-primary", 3, "click"], [1, "fa", "fa-eye-slash", "btn", "btn-sm", "btn-primary", 3, "click"], ["type", "text", "placeholder", "Enter Title", 3, "ngModel", "ngModelChange"], ["type", "checkbox", 1, "input-lg", 3, "checked", "click"], [3, "click"], [4, "ngIf"], [3, "itemData", 4, "ngIf"], [3, "report", "itemData", 4, "ngIf"], [3, "itemData"], [3, "report", "itemData"]], template: function ReportItemEditComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "div", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](2, ReportItemEditComponent_input_2_Template, 1, 0, "input", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, ReportItemEditComponent_input_3_Template, 1, 0, "input", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, ReportItemEditComponent_div_4_Template, 2, 0, "div", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](6, ReportItemEditComponent_input_6_Template, 1, 0, "input", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](7, ReportItemEditComponent_input_7_Template, 1, 0, "input", 7);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "input", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ReportItemEditComponent_Template_input_click_8_listener() { return ctx.notifyDelete(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](9, ReportItemEditComponent_i_9_Template, 1, 0, "i", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](10, ReportItemEditComponent_i_10_Template, 1, 0, "i", 10);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](11, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](12, ReportItemEditComponent_div_12_Template, 4, 1, "div", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](13, ReportItemEditComponent_div_13_Template, 4, 1, "div", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](14, "div", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](15, "div", null, 13);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](17, ReportItemEditComponent_div_17_Template, 5, 3, "div", 14);
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.editing);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.editing);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.editing);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.first);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.last);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.item.display);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.item.display);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.editing);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.editing);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.editing);
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_5__["NgIf"], _angular_forms__WEBPACK_IMPORTED_MODULE_6__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_6__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_6__["NgModel"], _html_content_item_preview_html_content_item_preview_component__WEBPACK_IMPORTED_MODULE_7__["HtmlContentItemPreviewComponent"], _addo_scale_results_preview_addo_scale_results_preview_component__WEBPACK_IMPORTED_MODULE_8__["AddoScaleResultsPreviewComponent"]], pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_5__["JsonPipe"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvcmVwb3J0LWl0ZW0tZWRpdC9yZXBvcnQtaXRlbS1lZGl0LmNvbXBvbmVudC5zY3NzIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBOzs7O0VBQUEiLCJmaWxlIjoic3JjL2FwcC9tYW5hZ2UtcmVwb3J0cy9yZXBvcnQtaXRlbS1lZGl0L3JlcG9ydC1pdGVtLWVkaXQuY29tcG9uZW50LnNjc3MiLCJzb3VyY2VzQ29udGVudCI6WyIvKlxuICogICBDb3B5cmlnaHQgKGMpIDIwMjIgRGlzY292ZXIgYW5kIENoYW5nZSBAYXV0aG9yIFN0ZXBoZW4gTmllbHNvbiA8c25pZWxzb25AZGlzY292ZXJhbmRjaGFuZ2UuY29tPlxuICogICBBbGwgcmlnaHRzIHJlc2VydmVkLlxuICogICBGb3IgTGljZW5zZSBpbmZvcm1hdGlvbiBzZWUgTElDRU5TRS5tZCBpbiB0aGUgcm9vdCBmb2xkZXIgb2YgdGhpcyBwcm9qZWN0LlxuICovXG4iXX0= */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ReportItemEditComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-report-item-edit',
                templateUrl: './report-item-edit.component.html',
                styleUrls: ['./report-item-edit.component.scss']
            }]
    }], function () { return [{ type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"] }, { type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ComponentFactoryResolver"] }]; }, { editorContainer: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewChild"],
            args: ['editorContainer', { read: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewContainerRef"] }]
        }], item: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }], report: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }], first: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }], last: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }], update: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Output"]
        }], delete: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Output"]
        }], moveUp: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Output"]
        }], moveDown: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Output"]
        }] }); })();


/***/ }),

/***/ "aTY8":
/*!***********************************************************************************!*\
  !*** ./src/app/manage-reports/custom-report-list/custom-report-list.component.ts ***!
  \***********************************************************************************/
/*! exports provided: CustomReportListComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CustomReportListComponent", function() { return CustomReportListComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _reports_services_report_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../reports/services/report.service */ "iW2n");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var app_login_has_permission_has_permission__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! app/login/has-permission/has-permission */ "mlUz");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @angular/router */ "tyNb");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */







function CustomReportListComponent_p_2_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "p");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, "There are no reports available right now");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function CustomReportListComponent_input_3_Template(rf, ctx) { if (rf & 1) {
    const _r5 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 7);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportListComponent_input_3_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r5); const ctx_r4 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r4.toggleShowAllHosts(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r1 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx_r1.showAllReports);
} }
function CustomReportListComponent_label_4_Template(rf, ctx) { if (rf & 1) {
    const _r7 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "label", 8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportListComponent_label_4_Template_label_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r7); const ctx_r6 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r6.toggleShowAllReports(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, "Show All Reports");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
const _c0 = function (a0) { return [a0, "edit"]; };
function CustomReportListComponent_tr_19_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "a", 9);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](9, "Edit");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const report_r8 = ctx.$implicit;
    const ctx_r3 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](report_r8.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](report_r8.linkedGroup == null ? null : report_r8.linkedGroup.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](ctx_r3.getSingleReportAssessment(report_r8));
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("routerLink", _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpureFunction1"](4, _c0, report_r8.id));
} }
const _c1 = function () { return ["new"]; };
class CustomReportListComponent {
    constructor(service) {
        this.service = service;
        this.showAllReports = false;
    }
    ngOnInit() {
        this.loadReports();
    }
    toggleShowAllReports() {
        this.showAllReports = !this.showAllReports;
        this.loadReports();
    }
    loadReports() {
        this.service.list(this.showAllReports).then((reports) => {
            this.reports = reports;
        });
    }
    getSingleReportAssessment(report) {
        if (report.linkedAssessments && report.linkedAssessments.length == 1) {
            return report.linkedAssessments[0];
        }
        return "";
    }
    get Reports() {
        return this.reports || [];
    }
}
CustomReportListComponent.ɵfac = function CustomReportListComponent_Factory(t) { return new (t || CustomReportListComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_reports_services_report_service__WEBPACK_IMPORTED_MODULE_1__["ReportService"])); };
CustomReportListComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: CustomReportListComponent, selectors: [["app-custom-report-list"]], decls: 20, vars: 6, consts: [[4, "ngIf"], ["class", "input-lg", "type", "checkbox", 3, "checked", "click", 4, "hasPermission"], [3, "click", 4, "hasPermission"], [1, "btn", "btn-primary", 3, "routerLink"], [1, "table", "table-striped"], [1, "table-head"], [4, "ngFor", "ngForOf"], ["type", "checkbox", 1, "input-lg", 3, "checked", "click"], [3, "click"], [1, "link-btn", "btn", "btn-primary", 3, "routerLink"]], template: function CustomReportListComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, "Reports");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](2, CustomReportListComponent_p_2_Template, 2, 0, "p", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, CustomReportListComponent_input_3_Template, 1, 1, "input", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, CustomReportListComponent_label_4_Template, 2, 0, "label", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "a", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6, "Add Report");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "table", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "thead", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](9, "tr");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "th");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](11, "Report");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](12, "th");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](13, "Assessment Group");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](14, "th");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](15, "Assessment");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](16, "th");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](17, "Action");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](18, "tbody");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](19, CustomReportListComponent_tr_19_Template, 10, 6, "tr", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.Reports.length < 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("hasPermission", "SuperUser");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("hasPermission", "SuperUser");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("routerLink", _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpureFunction0"](5, _c1));
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](14);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Reports);
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_2__["NgIf"], app_login_has_permission_has_permission__WEBPACK_IMPORTED_MODULE_3__["HasPermissionDirective"], _angular_router__WEBPACK_IMPORTED_MODULE_4__["RouterLinkWithHref"], _angular_common__WEBPACK_IMPORTED_MODULE_2__["NgForOf"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IiIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2N1c3RvbS1yZXBvcnQtbGlzdC9jdXN0b20tcmVwb3J0LWxpc3QuY29tcG9uZW50LmNzcyJ9 */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](CustomReportListComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'app-custom-report-list',
                templateUrl: './custom-report-list.component.html',
                styleUrls: ['./custom-report-list.component.css']
            }]
    }], function () { return [{ type: _reports_services_report_service__WEBPACK_IMPORTED_MODULE_1__["ReportService"] }]; }, null); })();
2;


/***/ }),

/***/ "bXne":
/*!*********************************************************!*\
  !*** ./src/app/manage-reports/manage-reports.module.ts ***!
  \*********************************************************/
/*! exports provided: ManageReportsModule */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ManageReportsModule", function() { return ManageReportsModule; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/router */ "tyNb");
/* harmony import */ var _shared_shared_module__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../shared/shared.module */ "PCNd");
/* harmony import */ var _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./custom-report-edit/custom-report-edit.component */ "ICsG");
/* harmony import */ var _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./custom-report-list/custom-report-list.component */ "aTY8");
/* harmony import */ var _manage_reports_routing_module__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./manage-reports-routing.module */ "0mHC");
/* harmony import */ var _html_content_item_preview_html_content_item_preview_component__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./html-content-item-preview/html-content-item-preview.component */ "9M+P");
/* harmony import */ var _addo_scale_results_preview_addo_scale_results_preview_component__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./addo-scale-results-preview/addo-scale-results-preview.component */ "JTD5");
/* harmony import */ var _custom_report_new_custom_report_new_component__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./custom-report-new/custom-report-new.component */ "fSH3");
/* harmony import */ var _report_item_edit_report_item_edit_component__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./report-item-edit/report-item-edit.component */ "KI1T");
/* harmony import */ var _item_json_editor_item_json_editor_component__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./item-json-editor/item-json-editor.component */ "DbIf");
/* harmony import */ var _item_html_content_editor_item_html_content_editor_component__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./item-html-content-editor/item-html-content-editor.component */ "uf23");
/* harmony import */ var _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./item-therapist-scale-results-editor/item-therapist-scale-results-editor.component */ "vz/w");
/* harmony import */ var _item_scale_results_summary_editor_item_scale_results_summary_editor_component__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./item-scale-results-summary-editor/item-scale-results-summary-editor.component */ "x5Yr");
/* harmony import */ var _login_login_module__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../login/login.module */ "X3zk");
/* harmony import */ var _scale_questions_action_editor_scale_questions_action_editor_component__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./scale-questions-action-editor/scale-questions-action-editor.component */ "wagj");
/* harmony import */ var app_reports_reports_module__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! app/reports/reports.module */ "uHdG");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */


















class ManageReportsModule {
}
ManageReportsModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineNgModule"]({ type: ManageReportsModule });
ManageReportsModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjector"]({ factory: function ManageReportsModule_Factory(t) { return new (t || ManageReportsModule)(); }, imports: [[
            _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"],
            _shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"],
            _login_login_module__WEBPACK_IMPORTED_MODULE_14__["LoginModule"],
            app_reports_reports_module__WEBPACK_IMPORTED_MODULE_16__["ReportsModule"],
            _manage_reports_routing_module__WEBPACK_IMPORTED_MODULE_5__["ManageReportsRoutingModule"]
        ]] });
(function () { (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsetNgModuleScope"](ManageReportsModule, { declarations: [_custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_4__["CustomReportListComponent"],
        _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__["CustomReportEditComponent"],
        _html_content_item_preview_html_content_item_preview_component__WEBPACK_IMPORTED_MODULE_6__["HtmlContentItemPreviewComponent"],
        _addo_scale_results_preview_addo_scale_results_preview_component__WEBPACK_IMPORTED_MODULE_7__["AddoScaleResultsPreviewComponent"],
        _custom_report_new_custom_report_new_component__WEBPACK_IMPORTED_MODULE_8__["CustomReportNewComponent"],
        _report_item_edit_report_item_edit_component__WEBPACK_IMPORTED_MODULE_9__["ReportItemEditComponent"],
        _item_json_editor_item_json_editor_component__WEBPACK_IMPORTED_MODULE_10__["ItemJsonEditorComponent"],
        _item_html_content_editor_item_html_content_editor_component__WEBPACK_IMPORTED_MODULE_11__["ItemHtmlContentEditorComponent"],
        _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_12__["ItemTherapistScaleResultsEditorComponent"],
        _item_scale_results_summary_editor_item_scale_results_summary_editor_component__WEBPACK_IMPORTED_MODULE_13__["ItemScaleResultsSummaryEditorComponent"],
        _scale_questions_action_editor_scale_questions_action_editor_component__WEBPACK_IMPORTED_MODULE_15__["ScaleQuestionsActionEditorComponent"]], imports: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"],
        _shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"],
        _login_login_module__WEBPACK_IMPORTED_MODULE_14__["LoginModule"],
        app_reports_reports_module__WEBPACK_IMPORTED_MODULE_16__["ReportsModule"],
        _manage_reports_routing_module__WEBPACK_IMPORTED_MODULE_5__["ManageReportsRoutingModule"]], exports: [_custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__["CustomReportEditComponent"], _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_4__["CustomReportListComponent"]] }); })();
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ManageReportsModule, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["NgModule"],
        args: [{
                imports: [
                    _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"],
                    _shared_shared_module__WEBPACK_IMPORTED_MODULE_2__["SharedModule"],
                    _login_login_module__WEBPACK_IMPORTED_MODULE_14__["LoginModule"],
                    app_reports_reports_module__WEBPACK_IMPORTED_MODULE_16__["ReportsModule"],
                    _manage_reports_routing_module__WEBPACK_IMPORTED_MODULE_5__["ManageReportsRoutingModule"]
                ],
                declarations: [
                    _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_4__["CustomReportListComponent"],
                    _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__["CustomReportEditComponent"],
                    _html_content_item_preview_html_content_item_preview_component__WEBPACK_IMPORTED_MODULE_6__["HtmlContentItemPreviewComponent"],
                    _addo_scale_results_preview_addo_scale_results_preview_component__WEBPACK_IMPORTED_MODULE_7__["AddoScaleResultsPreviewComponent"],
                    _custom_report_new_custom_report_new_component__WEBPACK_IMPORTED_MODULE_8__["CustomReportNewComponent"],
                    _report_item_edit_report_item_edit_component__WEBPACK_IMPORTED_MODULE_9__["ReportItemEditComponent"],
                    _item_json_editor_item_json_editor_component__WEBPACK_IMPORTED_MODULE_10__["ItemJsonEditorComponent"],
                    _item_html_content_editor_item_html_content_editor_component__WEBPACK_IMPORTED_MODULE_11__["ItemHtmlContentEditorComponent"],
                    _item_therapist_scale_results_editor_item_therapist_scale_results_editor_component__WEBPACK_IMPORTED_MODULE_12__["ItemTherapistScaleResultsEditorComponent"],
                    _item_scale_results_summary_editor_item_scale_results_summary_editor_component__WEBPACK_IMPORTED_MODULE_13__["ItemScaleResultsSummaryEditorComponent"],
                    _scale_questions_action_editor_scale_questions_action_editor_component__WEBPACK_IMPORTED_MODULE_15__["ScaleQuestionsActionEditorComponent"]
                ],
                exports: [
                    _custom_report_edit_custom_report_edit_component__WEBPACK_IMPORTED_MODULE_3__["CustomReportEditComponent"], _custom_report_list_custom_report_list_component__WEBPACK_IMPORTED_MODULE_4__["CustomReportListComponent"]
                ],
                entryComponents: []
            }]
    }], null, null); })();


/***/ }),

/***/ "fSH3":
/*!*********************************************************************************!*\
  !*** ./src/app/manage-reports/custom-report-new/custom-report-new.component.ts ***!
  \*********************************************************************************/
/*! exports provided: CustomReportNewComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CustomReportNewComponent", function() { return CustomReportNewComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../reports/services/report.service */ "iW2n");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @angular/router */ "tyNb");
/* harmony import */ var _shared_back_button_back_button_component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../shared/back-button/back-button.component */ "zXXW");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */










class CustomReportNewComponent {
    constructor(alertService, service, router, route) {
        this.alertService = alertService;
        this.service = service;
        this.router = router;
        this.route = route;
        this._report = {
            id: null,
            name: "Untitled"
        };
    }
    ngOnInit() {
    }
    get report() {
        return this._report;
    }
    set report(v) {
        this._report = v;
    }
    save() {
        let id = this.report.id || "";
        let name = this.report.name || "";
        if (id.trim() == "" || name.trim() == "") {
            this.alertService.error("report unique id and name must be filled in");
            return;
        }
        let alert = this.alertService.info("Saving report...", 120000);
        this.service.create(this.report).then(() => {
            this.alertService.clearAlert(alert);
            this.alertService.success("Report saved");
            this.router.navigate(["../", this.report.id, "edit"], { replaceUrl: true, relativeTo: this.route });
        })
            .catch((error) => {
            console.error(error);
            this.alertService.clearAlert(alert);
            this.alertService.error("There was an error saving the report.  Please check your internet connection and try again.  Or contact customer support");
        });
    }
}
CustomReportNewComponent.ɵfac = function CustomReportNewComponent_Factory(t) { return new (t || CustomReportNewComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__["ReportService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_router__WEBPACK_IMPORTED_MODULE_3__["Router"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_angular_router__WEBPACK_IMPORTED_MODULE_3__["ActivatedRoute"])); };
CustomReportNewComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: CustomReportNewComponent, selectors: [["dac-custom-report-new"]], decls: 23, vars: 3, consts: [[1, "row"], [1, "col-1"], [1, "col"], [1, "h2-responsive", "text-center"], [1, "card", "card-cascade", "narrower"], [1, "view", "gradient-card-header", "primary-gradient", "text-light"], [1, "h3-responsive", "text-center"], [1, "card-body"], [1, "form-group"], ["for", "name"], ["type", "text", "id", "uid", "name", "uid", 1, "form-control", 3, "ngModel", "ngModelChange"], ["type", "text", "id", "name", "name", "name", 1, "form-control", 3, "ngModel", "ngModelChange"], ["type", "submit", "value", "Save", 1, "btn", "btn-primary", 3, "click"]], template: function CustomReportNewComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "div", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](2, "app-back-button");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "div", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](4, "h2", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](5, "New Report");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](6, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "div", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "div", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](9, "div", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "h3", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](12, "div", 7);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](13, "form");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](14, "div", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](15, "label", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](16, "Unique Identifier:");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](17, "input", 10);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function CustomReportNewComponent_Template_input_ngModelChange_17_listener($event) { return ctx.report.id = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](18, "div", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](19, "label", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](20, "Name:");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](21, "input", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function CustomReportNewComponent_Template_input_ngModelChange_21_listener($event) { return ctx.report.name = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](22, "input", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function CustomReportNewComponent_Template_input_click_22_listener() { return ctx.save(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](ctx.report.name);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.report.id);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.report.name);
    } }, directives: [_shared_back_button_back_button_component__WEBPACK_IMPORTED_MODULE_4__["BackButtonComponent"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["ɵangular_packages_forms_forms_y"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["NgControlStatusGroup"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["NgForm"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_5__["NgModel"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvY3VzdG9tLXJlcG9ydC1uZXcvY3VzdG9tLXJlcG9ydC1uZXcuY29tcG9uZW50LnNjc3MiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7Ozs7RUFBQSIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2N1c3RvbS1yZXBvcnQtbmV3L2N1c3RvbS1yZXBvcnQtbmV3LmNvbXBvbmVudC5zY3NzIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqICAgQ29weXJpZ2h0IChjKSAyMDIyIERpc2NvdmVyIGFuZCBDaGFuZ2UgQGF1dGhvciBTdGVwaGVuIE5pZWxzb24gPHNuaWVsc29uQGRpc2NvdmVyYW5kY2hhbmdlLmNvbT5cbiAqICAgQWxsIHJpZ2h0cyByZXNlcnZlZC5cbiAqICAgRm9yIExpY2Vuc2UgaW5mb3JtYXRpb24gc2VlIExJQ0VOU0UubWQgaW4gdGhlIHJvb3QgZm9sZGVyIG9mIHRoaXMgcHJvamVjdC5cbiAqL1xuIl19 */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](CustomReportNewComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-custom-report-new',
                templateUrl: './custom-report-new.component.html',
                styleUrls: ['./custom-report-new.component.scss']
            }]
    }], function () { return [{ type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"] }, { type: _reports_services_report_service__WEBPACK_IMPORTED_MODULE_2__["ReportService"] }, { type: _angular_router__WEBPACK_IMPORTED_MODULE_3__["Router"] }, { type: _angular_router__WEBPACK_IMPORTED_MODULE_3__["ActivatedRoute"] }]; }, null); })();


/***/ }),

/***/ "uf23":
/*!***********************************************************************************************!*\
  !*** ./src/app/manage-reports/item-html-content-editor/item-html-content-editor.component.ts ***!
  \***********************************************************************************************/
/*! exports provided: ItemHtmlContentEditorComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ItemHtmlContentEditorComponent", function() { return ItemHtmlContentEditorComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */






const _c0 = ["contentEditor"];
const _c1 = ["newClass"];
function ItemHtmlContentEditorComponent_li_1_Template(rf, ctx) { if (rf & 1) {
    const _r4 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "i", 5);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemHtmlContentEditorComponent_li_1_Template_i_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r4); const clazz_r2 = ctx.$implicit; const ctx_r3 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r3.removeClass(clazz_r2); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const clazz_r2 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("", clazz_r2, " ");
} }
class ItemHtmlContentEditorComponent {
    constructor(alertService) {
        this.alertService = alertService;
    }
    ngOnInit() {
        this.newClassName = "";
    }
    addClassName() {
        let className = this.newClassName.trim();
        if (className == "") {
            this.alertService.error("Please enter a class name to continue");
            return;
        }
        this.classNames.push(className);
        this.newClassName = "";
    }
    removeClass(name) {
        this.classNames = this.classNames.filter(cn => cn != name);
    }
    setEditorData(editorData) {
        let data = editorData || {};
        this.classNames = data.classNames || [];
        this.content = data.content || "";
    }
    getEditorData() {
        let editor = this.contentEditor.nativeElement;
        let content = editor.value.trim();
        let data = {
            classNames: this.classNames,
            content: content
        };
        return Promise.resolve(data);
    }
}
ItemHtmlContentEditorComponent.ɵfac = function ItemHtmlContentEditorComponent_Factory(t) { return new (t || ItemHtmlContentEditorComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"])); };
ItemHtmlContentEditorComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ItemHtmlContentEditorComponent, selectors: [["dac-item-html-content-editor"]], viewQuery: function ItemHtmlContentEditorComponent_Query(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵviewQuery"](_c0, true);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵviewQuery"](_c1, true);
    } if (rf & 2) {
        var _t;
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵqueryRefresh"](_t = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵloadQuery"]()) && (ctx.contentEditor = _t.first);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵqueryRefresh"](_t = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵloadQuery"]()) && (ctx.newClass = _t.first);
    } }, decls: 7, vars: 3, consts: [[4, "ngFor", "ngForOf"], ["type", "text", "value", "", "placeholder", "Enter Classname", 3, "ngModel", "ngModelChange"], ["type", "button", "value", "Add", 1, "btn", "btn-primary", 3, "click"], [1, "report-editor-window", 3, "value"], ["contentEditor", ""], [1, "btn", "btn-sm", "btn-primary", "fa", "fa-trash", 3, "click"]], template: function ItemHtmlContentEditorComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "ul");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](1, ItemHtmlContentEditorComponent_li_1_Template, 3, 1, "li", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "input", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ItemHtmlContentEditorComponent_Template_input_ngModelChange_3_listener($event) { return ctx.newClassName = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](4, "input", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemHtmlContentEditorComponent_Template_input_click_4_listener() { return ctx.addClassName(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](5, "textarea", 3, 4);
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.classNames);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.newClassName);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", ctx.content);
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_2__["NgForOf"], _angular_forms__WEBPACK_IMPORTED_MODULE_3__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_3__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_3__["NgModel"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS1odG1sLWNvbnRlbnQtZWRpdG9yL2l0ZW0taHRtbC1jb250ZW50LWVkaXRvci5jb21wb25lbnQuc2NzcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7OztFQUFBIiwiZmlsZSI6InNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS1odG1sLWNvbnRlbnQtZWRpdG9yL2l0ZW0taHRtbC1jb250ZW50LWVkaXRvci5jb21wb25lbnQuc2NzcyIsInNvdXJjZXNDb250ZW50IjpbIi8qXG4gKiAgIENvcHlyaWdodCAoYykgMjAyMiBEaXNjb3ZlciBhbmQgQ2hhbmdlIEBhdXRob3IgU3RlcGhlbiBOaWVsc29uIDxzbmllbHNvbkBkaXNjb3ZlcmFuZGNoYW5nZS5jb20+XG4gKiAgIEFsbCByaWdodHMgcmVzZXJ2ZWQuXG4gKiAgIEZvciBMaWNlbnNlIGluZm9ybWF0aW9uIHNlZSBMSUNFTlNFLm1kIGluIHRoZSByb290IGZvbGRlciBvZiB0aGlzIHByb2plY3QuXG4gKi9cbiJdfQ== */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ItemHtmlContentEditorComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-item-html-content-editor',
                templateUrl: './item-html-content-editor.component.html',
                styleUrls: ['./item-html-content-editor.component.scss']
            }]
    }], function () { return [{ type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"] }]; }, { contentEditor: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewChild"],
            args: ["contentEditor"]
        }], newClass: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["ViewChild"],
            args: ["newClass"]
        }] }); })();


/***/ }),

/***/ "vz/w":
/*!*********************************************************************************************************************!*\
  !*** ./src/app/manage-reports/item-therapist-scale-results-editor/item-therapist-scale-results-editor.component.ts ***!
  \*********************************************************************************************************************/
/*! exports provided: ItemTherapistScaleResultsEditorComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ItemTherapistScaleResultsEditorComponent", function() { return ItemTherapistScaleResultsEditorComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../shared/services/assessment.service */ "7myg");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/* harmony import */ var _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../shared/assessment-picker/assessment-picker.component */ "GTRT");
/* harmony import */ var _scale_questions_action_editor_scale_questions_action_editor_component__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../scale-questions-action-editor/scale-questions-action-editor.component */ "wagj");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */










function ItemTherapistScaleResultsEditorComponent_div_0_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "label");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2, "Assessment:");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r0 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate2"]("", ctx_r0.assessment.name, " (", ctx_r0.assessment.uid, ")");
} }
function ItemTherapistScaleResultsEditorComponent_input_1_Template(rf, ctx) { if (rf & 1) {
    const _r10 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 17);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_input_1_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r10); const ctx_r9 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r9.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ItemTherapistScaleResultsEditorComponent_dac_assessment_picker_2_Template(rf, ctx) { if (rf & 1) {
    const _r12 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "dac-assessment-picker", 18);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("selected", function ItemTherapistScaleResultsEditorComponent_dac_assessment_picker_2_Template_dac_assessment_picker_selected_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r12); const ctx_r11 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r11.updateAssessment($event); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ItemTherapistScaleResultsEditorComponent_div_3_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r3 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"]("Computed Result: ", ctx_r3.computed_result, "");
} }
function ItemTherapistScaleResultsEditorComponent_input_4_Template(rf, ctx) { if (rf & 1) {
    const _r14 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "input", 19);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_input_4_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r14); const ctx_r13 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r13.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} }
function ItemTherapistScaleResultsEditorComponent_ul_7_li_6_Template(rf, ctx) { if (rf & 1) {
    const _r18 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 23);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_ul_7_li_6_Template_input_click_1_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r18); const scale_r16 = ctx.$implicit; const ctx_r17 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2); return ctx_r17.toggleScale(scale_r16.id); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "label", 21);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_ul_7_li_6_Template_label_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r18); const scale_r16 = ctx.$implicit; const ctx_r19 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2); return ctx_r19.toggleScale(scale_r16.id); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "span");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const scale_r16 = ctx.$implicit;
    const ctx_r15 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx_r15.isChecked(scale_r16.id));
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](scale_r16.name);
} }
function ItemTherapistScaleResultsEditorComponent_ul_7_Template(rf, ctx) { if (rf & 1) {
    const _r21 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "ul");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "input", 20);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_ul_7_Template_input_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r21); const ctx_r20 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r20.includeAllScales(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "label", 21);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_ul_7_Template_label_click_3_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r21); const ctx_r22 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r22.includeAllScales(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](4, "span");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](5, "Show all scales");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](6, ItemTherapistScaleResultsEditorComponent_ul_7_li_6_Template, 5, 2, "li", 22);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r5 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx_r5.scales.length == 0);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx_r5.assessment.scales);
} }
function ItemTherapistScaleResultsEditorComponent_div_28_Template(rf, ctx) { if (rf & 1) {
    const _r24 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 24);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ItemTherapistScaleResultsEditorComponent_div_28_Template_input_ngModelChange_1_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r24); const ctx_r23 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r23.tableActionLabel = $event; });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "label");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](3, "Table Action Label");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r6 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx_r6.tableActionLabel);
} }
function ItemTherapistScaleResultsEditorComponent_div_29_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelement"](1, "dac-scale-questions-action-editor", 25);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r7 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("questionActions", ctx_r7.tableActions)("assessment", ctx_r7.assessment);
} }
function ItemTherapistScaleResultsEditorComponent_li_33_Template(rf, ctx) { if (rf & 1) {
    const _r28 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "li");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 26);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("input", function ItemTherapistScaleResultsEditorComponent_li_33_Template_input_input_1_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r28); const ctx_r27 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r27.updatePageBreak($event.target.value); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "i", 27);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_li_33_Template_i_click_2_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r28); const index_r26 = ctx.index; const ctx_r29 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r29.deletePageBreak(index_r26); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const pageBreak_r25 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("value", pageBreak_r25);
} }
class ItemTherapistScaleResultsEditorComponent {
    constructor(alertService, assessmentService) {
        this.alertService = alertService;
        this.assessmentService = assessmentService;
        this.skipContent = false;
        this.skipGraph = false;
        this.skipTable = false;
        this.tableActionLabel = "";
        this.tableActions = [];
        this.hasTableAction = false;
    }
    trackByIndex(index, obj) {
        return index;
    }
    setEditorData(editorData) {
        let data = editorData || {};
        this.scalesPerPage = data.scales_per_page || "";
        if (this.scalesPerPage != "") {
            this.scalesPerPage = this.scalesPerPage + ""; // convert to a string if we have a number originally
        }
        if (data.assessment) {
            this.assessmentService.getAssessment(data.assessment).then((assessment) => {
                this.assessment = assessment;
                this.tableActions = this.createTableActions(editorData.tableActions);
            });
        }
        else if (data.computed_result) {
            this.computed_result = data.computed_result;
        }
        this.skipContent = data.skipContent == true;
        this.skipGraph = data.skipGraph == true;
        this.skipTable = data.skipTable == true;
        this.scales = editorData.scales || [];
        this.pageBreaks = editorData.pageBreaks || [];
        this.hasTableAction = data.hasTableAction == true;
        this.rollUpQuestions = data.rollUpQuestions == true;
        this.tableActionLabel = data.tableActionLabel || "";
    }
    createTableActions(tableActions) {
        let actions = (tableActions || []).map(ta => {
            let scale = this.assessment.scales.find(s => s.id == ta.scaleId);
            let question = this.assessment.getQuestionsForScale(scale).find(q => q.id == ta.questionId);
            return {
                question: question,
                scale: scale,
                action: ta.action,
                data: ta.data
            };
        });
        return actions;
    }
    getEditorData() {
        let data = {};
        let computed_result = this.computed_result || "";
        if (!this.assessment && computed_result.trim() == "") {
            this.alertService.error("You must pick an assessment or set a computed_result before saving this item");
            return Promise.reject(new Error("Invalid editor data"));
        }
        if (this.scalesPerPage && this.scalesPerPage.trim() != "") {
            let scalesPerPage = +this.scalesPerPage;
            if (isNaN(scalesPerPage) || scalesPerPage == 0) {
                this.alertService.error("The scales per page number is invalid.  Clear it out or a number greater than 0");
                return Promise.reject(new Error("Invalid editor data"));
            }
            else {
                // we are being backwards compatability here from the original data....
                data.scales_per_page = scalesPerPage;
            }
        }
        if (this.assessment) {
            data.assessment = this.assessment.uid;
        }
        else {
            data.computed_result = computed_result;
        }
        if (this.scales && this.scales.length) {
            data.scales = this.scales;
        }
        data.skipContent = this.skipContent == true;
        data.skipGraph = this.skipGraph == true;
        data.skipTable = this.skipTable == true;
        data.rollUpQuestions = this.rollUpQuestions == true;
        data.pageBreaks = this.pageBreaks.map(pb => +pb).filter(pb => !isNaN(pb)) || [];
        data.hasTableAction = this.hasTableAction == true;
        data.tableActionLabel = this.tableActionLabel;
        data.tableActions = this.tableActions.map(ta => {
            return {
                scaleId: ta.scale.id,
                questionId: ta.question.id,
                action: ta.action,
                data: ta.data
            };
        });
        return Promise.resolve(data);
    }
    updateAssessment(assessment) {
        // so we only have a preview of the assessment we need to grab the whole thing
        // so we can grab the scales
        this.assessmentService.getAssessment(assessment.uid)
            .then((assessment) => {
            this.assessment = assessment;
            this.scalesPerPage = null;
            this.scales = [];
            this.pageBreaks = [];
            this.assessment = assessment;
            this.toggleAssessmentPicker();
        })
            .catch((error) => {
            this.alertService.error("There was an error in retrieving the assessment from the server.  Check your internet connection and try again");
        });
    }
    toggleAssessmentPicker() {
        this.showAssessmentPicker = !this.showAssessmentPicker;
    }
    includeAllScales() {
        this.scales = [];
    }
    isChecked(scaleId) {
        return this.scales.indexOf(scaleId) >= 0;
    }
    addPageBreak() {
        let lastPageBreak = this.pageBreaks[this.pageBreaks.length - 1] || 0;
        lastPageBreak = lastPageBreak + 1; // go one more
        this.pageBreaks.push(lastPageBreak);
    }
    deletePageBreak(index) {
        this.pageBreaks.splice(index, 1);
    }
    updatePageBreak(value, index) {
        let validValue = +value;
        if (isNaN(validValue)) {
            this.alertService.error("Please enter a valid number for this page break");
            this.pageBreaks[index] = 0;
        }
        if (this.pageBreaks[index] !== undefined) {
            this.pageBreaks[index] = +value;
        }
    }
    toggleScale(scaleId) {
        let index = this.scales.indexOf(scaleId);
        if (index >= 0) {
            this.scales.splice(index, 1);
        }
        else {
            this.scales.push(scaleId);
        }
    }
    ngOnInit() {
    }
}
ItemTherapistScaleResultsEditorComponent.ɵfac = function ItemTherapistScaleResultsEditorComponent_Factory(t) { return new (t || ItemTherapistScaleResultsEditorComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](_shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_2__["AssessmentService"])); };
ItemTherapistScaleResultsEditorComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ItemTherapistScaleResultsEditorComponent, selectors: [["dac-item-therapist-scale-results-editor"]], decls: 38, vars: 16, consts: [[4, "ngIf"], ["type", "button", "class", "btn btn-primary", "value", "Close Assessment Picker", 3, "click", 4, "ngIf"], [3, "selected", 4, "ngIf"], ["type", "button", "value", "Change Assessment", 3, "click", 4, "ngIf"], ["name", "skipGraph", "type", "checkbox", 3, "checked", "click"], ["for", "skipGraph", 3, "click"], ["name", "skipContent", "type", "checkbox", 3, "checked", "click"], ["for", "skipContent", 3, "click"], ["name", "skipTable", "type", "checkbox", 3, "checked", "click"], ["for", "skipTable", 3, "click"], ["name", "rollUpQuestions", "type", "checkbox", 3, "checked", "click"], ["for", "rollUpQuestions", 3, "click"], ["name", "hasTableAction", "type", "checkbox", 3, "checked", "click"], ["for", "hasTableAction", 3, "click"], ["type", "button", "value", "Add Page Break", 3, "click"], [4, "ngFor", "ngForOf", "ngForTrackBy"], ["type", "text", 3, "ngModel", "ngModelChange"], ["type", "button", "value", "Close Assessment Picker", 1, "btn", "btn-primary", 3, "click"], [3, "selected"], ["type", "button", "value", "Change Assessment", 3, "click"], ["type", "checkbox", 3, "checked", "click"], [3, "click"], [4, "ngFor", "ngForOf"], ["type", "checkbox", 1, "input-lg", 3, "checked", "click"], ["name", "tableActionLabel", "type", "text", 3, "ngModel", "ngModelChange"], [3, "questionActions", "assessment"], ["type", "number", "min", "0", 3, "value", "input"], [1, "fa", "fa-trash", 3, "click"]], template: function ItemTherapistScaleResultsEditorComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](0, ItemTherapistScaleResultsEditorComponent_div_0_Template, 5, 2, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](1, ItemTherapistScaleResultsEditorComponent_input_1_Template, 1, 0, "input", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](2, ItemTherapistScaleResultsEditorComponent_dac_assessment_picker_2_Template, 1, 0, "dac-assessment-picker", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](3, ItemTherapistScaleResultsEditorComponent_div_3_Template, 2, 1, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, ItemTherapistScaleResultsEditorComponent_input_4_Template, 1, 0, "input", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "h3");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6, " Scales\n");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](7, ItemTherapistScaleResultsEditorComponent_ul_7_Template, 7, 2, "ul", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](8, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](9, "input", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_9_listener() { return ctx.skipGraph = !ctx.skipGraph; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "label", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_label_click_10_listener() { return ctx.skipGraph = !ctx.skipGraph; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](11, "Skip Graphs");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](12, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](13, "input", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_13_listener() { return ctx.skipContent = !ctx.skipContent; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](14, "label", 7);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_label_click_14_listener() { return ctx.skipContent = !ctx.skipContent; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](15, "Skip Contents");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](16, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](17, "input", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_17_listener() { return ctx.skipTable = !ctx.skipTable; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](18, "label", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_label_click_18_listener() { return ctx.skipTable = !ctx.skipTable; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](19, "Skip Tables");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](20, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](21, "input", 10);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_21_listener() { return ctx.rollUpQuestions = !ctx.rollUpQuestions; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](22, "label", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_label_click_22_listener() { return ctx.rollUpQuestions = !ctx.rollUpQuestions; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](23, "Roll Up Sub Scale Questions");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](24, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](25, "input", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_25_listener() { return ctx.hasTableAction = !ctx.hasTableAction; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](26, "label", 13);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_label_click_26_listener() { return ctx.hasTableAction = !ctx.hasTableAction; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](27, "Enable Table Action");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](28, ItemTherapistScaleResultsEditorComponent_div_28_Template, 4, 1, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](29, ItemTherapistScaleResultsEditorComponent_div_29_Template, 2, 2, "div", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](30, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](31, "ul");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](32, "input", 14);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ItemTherapistScaleResultsEditorComponent_Template_input_click_32_listener() { return ctx.addPageBreak(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](33, ItemTherapistScaleResultsEditorComponent_li_33_Template, 3, 1, "li", 15);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](34, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](35, "input", 16);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ItemTherapistScaleResultsEditorComponent_Template_input_ngModelChange_35_listener($event) { return ctx.scalesPerPage = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](36, "label");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](37, "Scales Per Page");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.assessment);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.computed_result);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", !ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.assessment);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx.skipGraph);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx.skipContent);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx.skipTable);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx.rollUpQuestions);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("checked", ctx.hasTableAction);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.hasTableAction);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.hasTableAction);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.pageBreaks)("ngForTrackBy", ctx.trackByIndex);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.scalesPerPage);
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_3__["NgIf"], _angular_common__WEBPACK_IMPORTED_MODULE_3__["NgForOf"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["NgModel"], _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_5__["AssessmentPickerComponent"], _scale_questions_action_editor_scale_questions_action_editor_component__WEBPACK_IMPORTED_MODULE_6__["ScaleQuestionsActionEditorComponent"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS10aGVyYXBpc3Qtc2NhbGUtcmVzdWx0cy1lZGl0b3IvaXRlbS10aGVyYXBpc3Qtc2NhbGUtcmVzdWx0cy1lZGl0b3IuY29tcG9uZW50LnNjc3MiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7Ozs7RUFBQSIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL2l0ZW0tdGhlcmFwaXN0LXNjYWxlLXJlc3VsdHMtZWRpdG9yL2l0ZW0tdGhlcmFwaXN0LXNjYWxlLXJlc3VsdHMtZWRpdG9yLmNvbXBvbmVudC5zY3NzIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqICAgQ29weXJpZ2h0IChjKSAyMDIyIERpc2NvdmVyIGFuZCBDaGFuZ2UgQGF1dGhvciBTdGVwaGVuIE5pZWxzb24gPHNuaWVsc29uQGRpc2NvdmVyYW5kY2hhbmdlLmNvbT5cbiAqICAgQWxsIHJpZ2h0cyByZXNlcnZlZC5cbiAqICAgRm9yIExpY2Vuc2UgaW5mb3JtYXRpb24gc2VlIExJQ0VOU0UubWQgaW4gdGhlIHJvb3QgZm9sZGVyIG9mIHRoaXMgcHJvamVjdC5cbiAqL1xuIl19 */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ItemTherapistScaleResultsEditorComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-item-therapist-scale-results-editor',
                templateUrl: './item-therapist-scale-results-editor.component.html',
                styleUrls: ['./item-therapist-scale-results-editor.component.scss']
            }]
    }], function () { return [{ type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_1__["AlertService"] }, { type: _shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_2__["AssessmentService"] }]; }, null); })();


/***/ }),

/***/ "wagj":
/*!*********************************************************************************************************!*\
  !*** ./src/app/manage-reports/scale-questions-action-editor/scale-questions-action-editor.component.ts ***!
  \*********************************************************************************************************/
/*! exports provided: ScaleQuestionsActionEditorComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ScaleQuestionsActionEditorComponent", function() { return ScaleQuestionsActionEditorComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var app_shared_models_assessment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! app/shared/models/assessment */ "WWiz");
/* harmony import */ var app_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! app/shared/services/alert.service */ "Cmua");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */







function ScaleQuestionsActionEditorComponent_table_0_tr_14_Template(rf, ctx) { if (rf & 1) {
    const _r8 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](9, "json");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](11, "input", 7);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ScaleQuestionsActionEditorComponent_table_0_tr_14_Template_input_click_11_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r8); const questionAction_r6 = ctx.$implicit; const ctx_r7 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](2); return ctx_r7.deleteAction(questionAction_r6); });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const questionAction_r6 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](questionAction_r6.scale.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](questionAction_r6.question.prompt);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](questionAction_r6.action);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](9, 4, questionAction_r6.data));
} }
function ScaleQuestionsActionEditorComponent_table_0_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "table");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "thead");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "th");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](4, "Scale");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](5, "th");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](6, "Question");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](7, "th");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](8, "Action");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](9, "th");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](10, "Data");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](11, "th");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](12, "Delete");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](13, "tbody");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](14, ScaleQuestionsActionEditorComponent_table_0_tr_14_Template, 12, 6, "tr", 6);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r0 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](14);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx_r0.questionActions);
} }
function ScaleQuestionsActionEditorComponent_option_4_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "option", 8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const scale_r9 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngValue", scale_r9);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](scale_r9.name);
} }
function ScaleQuestionsActionEditorComponent_select_6_option_1_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "option", 8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const question_r11 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngValue", question_r11);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](question_r11.prompt);
} }
function ScaleQuestionsActionEditorComponent_select_6_Template(rf, ctx) { if (rf & 1) {
    const _r13 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "select", 9);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ScaleQuestionsActionEditorComponent_select_6_Template_select_ngModelChange_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r13); const ctx_r12 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r12.newQuestionAction.question = $event; });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](1, ScaleQuestionsActionEditorComponent_select_6_option_1_Template, 2, 2, "option", 2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r2 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx_r2.newQuestionAction.question);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx_r2.Questions);
} }
function ScaleQuestionsActionEditorComponent_select_8_option_1_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "option", 8);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const action_r15 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngValue", action_r15);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](action_r15);
} }
function ScaleQuestionsActionEditorComponent_select_8_Template(rf, ctx) { if (rf & 1) {
    const _r17 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "select", 10);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ScaleQuestionsActionEditorComponent_select_8_Template_select_ngModelChange_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r17); const ctx_r16 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r16.newQuestionAction.action = $event; });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](1, ScaleQuestionsActionEditorComponent_select_8_option_1_Template, 2, 2, "option", 2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r3 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx_r3.newQuestionAction.action);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx_r3.Actions);
} }
function ScaleQuestionsActionEditorComponent_label_9_Template(rf, ctx) { if (rf & 1) {
    const _r19 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "label");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1, " Redirect URL: ");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "input", 11);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ScaleQuestionsActionEditorComponent_label_9_Template_input_ngModelChange_2_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵrestoreView"](_r19); const ctx_r18 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"](); return ctx_r18.newQuestionAction.data.url = $event; });
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r4 = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx_r4.newQuestionAction.data.url);
} }
class ScaleQuestionsActionEditorComponent {
    constructor(alertService) {
        this.alertService = alertService;
        this.questionActions = this.questionActions || [];
        this.setupNewQuestionAction();
    }
    ngOnInit() {
    }
    get Scales() {
        if (this.assessment) {
            return this.assessment.scales || [];
        }
        return [];
    }
    get Actions() {
        return ["redirect"];
    }
    get Questions() {
        if (!this.assessment) {
            return [];
        }
        return this.newQuestionAction.scale ? this.assessment.getQuestionsForScale(this.newQuestionAction.scale) : [];
    }
    deleteAction(action) {
        this.questionActions.splice(this.questionActions.indexOf(action), 1);
    }
    addAction(action) {
        let errors = [];
        if (!action.scale) {
            errors.push("Scale must be set");
        }
        if (!action.question) {
            errors.push("Question must be set");
        }
        if (!action.data || !action.data.url) {
            errors.push("URL is missing");
        }
        if (errors.length) {
            this.alertService.error(errors.join("."));
            return;
        }
        this.questionActions.push(action);
        this.setupNewQuestionAction();
    }
    setupNewQuestionAction() {
        if (this.newQuestionAction) {
            this.newQuestionAction = { scale: this.newQuestionAction.scale, question: null, action: "redirect", data: { url: "" } };
        }
        else {
            this.newQuestionAction = { scale: null, question: null, action: "redirect", data: { url: "" } };
        }
    }
}
ScaleQuestionsActionEditorComponent.ɵfac = function ScaleQuestionsActionEditorComponent_Factory(t) { return new (t || ScaleQuestionsActionEditorComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdirectiveInject"](app_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_2__["AlertService"])); };
ScaleQuestionsActionEditorComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ScaleQuestionsActionEditorComponent, selectors: [["dac-scale-questions-action-editor"]], inputs: { questionActions: "questionActions", assessment: "assessment" }, decls: 11, vars: 6, consts: [[4, "ngIf"], ["name", "scale", 1, "browser-default", "form-control", 3, "ngModel", "ngModelChange"], [3, "ngValue", 4, "ngFor", "ngForOf"], ["class", "browser-default form-control", "name", "question", 3, "ngModel", "ngModelChange", 4, "ngIf"], ["class", "browser-default form-control", "name", "action", 3, "ngModel", "ngModelChange", 4, "ngIf"], ["type", "button", "value", "Add Question Action", 3, "click"], [4, "ngFor", "ngForOf"], ["type", "button", "value", "Delete", 3, "click"], [3, "ngValue"], ["name", "question", 1, "browser-default", "form-control", 3, "ngModel", "ngModelChange"], ["name", "action", 1, "browser-default", "form-control", 3, "ngModel", "ngModelChange"], ["type", "text", "name", "url", 3, "ngModel", "ngModelChange"]], template: function ScaleQuestionsActionEditorComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](0, ScaleQuestionsActionEditorComponent_table_0_Template, 15, 1, "table", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "div");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2, " Scale: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "select", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ScaleQuestionsActionEditorComponent_Template_select_ngModelChange_3_listener($event) { return ctx.newQuestionAction.scale = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, ScaleQuestionsActionEditorComponent_option_4_Template, 2, 2, "option", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](5, " Question: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](6, ScaleQuestionsActionEditorComponent_select_6_Template, 2, 2, "select", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](7, " Action: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](8, ScaleQuestionsActionEditorComponent_select_8_Template, 2, 2, "select", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](9, ScaleQuestionsActionEditorComponent_label_9_Template, 3, 1, "label", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](10, "input", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("click", function ScaleQuestionsActionEditorComponent_Template_input_click_10_listener() { return ctx.addAction(ctx.newQuestionAction); });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.questionActions.length > 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.newQuestionAction.scale);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.Scales);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.newQuestionAction.scale);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.newQuestionAction.action);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngIf", ctx.newQuestionAction.action == "redirect");
    } }, directives: [_angular_common__WEBPACK_IMPORTED_MODULE_3__["NgIf"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["SelectControlValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["NgModel"], _angular_common__WEBPACK_IMPORTED_MODULE_3__["NgForOf"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["NgSelectOption"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["ɵangular_packages_forms_forms_x"], _angular_forms__WEBPACK_IMPORTED_MODULE_4__["DefaultValueAccessor"]], pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_3__["JsonPipe"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvc2NhbGUtcXVlc3Rpb25zLWFjdGlvbi1lZGl0b3Ivc2NhbGUtcXVlc3Rpb25zLWFjdGlvbi1lZGl0b3IuY29tcG9uZW50LnNjc3MiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7Ozs7RUFBQSIsImZpbGUiOiJzcmMvYXBwL21hbmFnZS1yZXBvcnRzL3NjYWxlLXF1ZXN0aW9ucy1hY3Rpb24tZWRpdG9yL3NjYWxlLXF1ZXN0aW9ucy1hY3Rpb24tZWRpdG9yLmNvbXBvbmVudC5zY3NzIiwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqICAgQ29weXJpZ2h0IChjKSAyMDIyIERpc2NvdmVyIGFuZCBDaGFuZ2UgQGF1dGhvciBTdGVwaGVuIE5pZWxzb24gPHNuaWVsc29uQGRpc2NvdmVyYW5kY2hhbmdlLmNvbT5cbiAqICAgQWxsIHJpZ2h0cyByZXNlcnZlZC5cbiAqICAgRm9yIExpY2Vuc2UgaW5mb3JtYXRpb24gc2VlIExJQ0VOU0UubWQgaW4gdGhlIHJvb3QgZm9sZGVyIG9mIHRoaXMgcHJvamVjdC5cbiAqL1xuIl19 */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ScaleQuestionsActionEditorComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-scale-questions-action-editor',
                templateUrl: './scale-questions-action-editor.component.html',
                styleUrls: ['./scale-questions-action-editor.component.scss']
            }]
    }], function () { return [{ type: app_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_2__["AlertService"] }]; }, { questionActions: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }], assessment: [{
            type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Input"]
        }] }); })();


/***/ }),

/***/ "x5Yr":
/*!*****************************************************************************************************************!*\
  !*** ./src/app/manage-reports/item-scale-results-summary-editor/item-scale-results-summary-editor.component.ts ***!
  \*****************************************************************************************************************/
/*! exports provided: ItemScaleResultsSummaryEditorComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ItemScaleResultsSummaryEditorComponent", function() { return ItemScaleResultsSummaryEditorComponent; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @angular/common */ "ofXK");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */




function ItemScaleResultsSummaryEditorComponent_div_4_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](2, "pre");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipe"](4, "json");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
} if (rf & 2) {
    const section_r1 = ctx.$implicit;
    const index_r2 = ctx.index;
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate1"](" Section ", index_r2, " ");
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵpipeBind1"](4, 2, section_r1));
} }
class ItemScaleResultsSummaryEditorComponent {
    constructor() { }
    ngOnInit() {
    }
    setEditorData(editorData) {
        let data = editorData || {};
        this.title = data.title || "";
        this.description = data.description || "";
        this.sections = data.sections || [];
    }
    getEditorData() {
        return Promise.resolve({
            title: this.title,
            description: this.description,
            sections: this.sections
        });
    }
}
ItemScaleResultsSummaryEditorComponent.ɵfac = function ItemScaleResultsSummaryEditorComponent_Factory(t) { return new (t || ItemScaleResultsSummaryEditorComponent)(); };
ItemScaleResultsSummaryEditorComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineComponent"]({ type: ItemScaleResultsSummaryEditorComponent, selectors: [["dac-item-scale-results-summary-editor"]], decls: 5, vars: 3, consts: [["type", "text", 3, "ngModel", "ngModelChange"], [3, "ngModel", "ngModelChange"], [4, "ngFor", "ngForOf"]], template: function ItemScaleResultsSummaryEditorComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](0, "Title: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](1, "input", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ItemScaleResultsSummaryEditorComponent_Template_input_ngModelChange_1_listener($event) { return ctx.title = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtext"](2, "\nDescription\n");
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementStart"](3, "textarea", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵlistener"]("ngModelChange", function ItemScaleResultsSummaryEditorComponent_Template_textarea_ngModelChange_3_listener($event) { return ctx.description = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵtemplate"](4, ItemScaleResultsSummaryEditorComponent_div_4_Template, 5, 4, "div", 2);
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.title);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](2);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngModel", ctx.description);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵproperty"]("ngForOf", ctx.sections);
    } }, directives: [_angular_forms__WEBPACK_IMPORTED_MODULE_1__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_1__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_1__["NgModel"], _angular_common__WEBPACK_IMPORTED_MODULE_2__["NgForOf"]], pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_2__["JsonPipe"]], styles: ["\n/*# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbInNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS1zY2FsZS1yZXN1bHRzLXN1bW1hcnktZWRpdG9yL2l0ZW0tc2NhbGUtcmVzdWx0cy1zdW1tYXJ5LWVkaXRvci5jb21wb25lbnQuc2NzcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7OztFQUFBIiwiZmlsZSI6InNyYy9hcHAvbWFuYWdlLXJlcG9ydHMvaXRlbS1zY2FsZS1yZXN1bHRzLXN1bW1hcnktZWRpdG9yL2l0ZW0tc2NhbGUtcmVzdWx0cy1zdW1tYXJ5LWVkaXRvci5jb21wb25lbnQuc2NzcyIsInNvdXJjZXNDb250ZW50IjpbIi8qXG4gKiAgIENvcHlyaWdodCAoYykgMjAyMiBEaXNjb3ZlciBhbmQgQ2hhbmdlIEBhdXRob3IgU3RlcGhlbiBOaWVsc29uIDxzbmllbHNvbkBkaXNjb3ZlcmFuZGNoYW5nZS5jb20+XG4gKiAgIEFsbCByaWdodHMgcmVzZXJ2ZWQuXG4gKiAgIEZvciBMaWNlbnNlIGluZm9ybWF0aW9uIHNlZSBMSUNFTlNFLm1kIGluIHRoZSByb290IGZvbGRlciBvZiB0aGlzIHByb2plY3QuXG4gKi9cbiJdfQ== */"] });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](ItemScaleResultsSummaryEditorComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Component"],
        args: [{
                selector: 'dac-item-scale-results-summary-editor',
                templateUrl: './item-scale-results-summary-editor.component.html',
                styleUrls: ['./item-scale-results-summary-editor.component.scss']
            }]
    }], function () { return []; }, null); })();


/***/ })

}]);
//# sourceMappingURL=manage-reports-manage-reports-module-es2015.js.map