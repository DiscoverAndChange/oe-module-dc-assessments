(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["system-overview-system-overview-module"],{

/***/ "0rMz":
/*!*****************************************************************!*\
  !*** ./src/app/admin/system-overview/models/system-overview.ts ***!
  \*****************************************************************/
/*! exports provided: SystemOverview */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemOverview", function() { return SystemOverview; });
class SystemOverview {
    constructor() {
        this._authorSummaries = [];
        // public getAuthorAgreementSummaries() {
        //     // items for each other
        //     // sum for the author
        //     let itemsByAuthor = [];
        //     this.authorAgreementItems.forEach(i => {
        //         if (!itemsByAuthor[i.authorId]) {
        //             itemsByAuthor[i.authorId] = {
        //                 items: [],
        //                 total: 0
        //             };
        //         }
        //         itemsByAuthor[i.authorId].items.push(i);
        //         itemsByAuthor[i.authorId].total += i.totalAuthorRevenue;
        //     });
        //     this._authorSummaries = itemsByAuthor;
        // }
    }
}


/***/ }),

/***/ "2LnN":
/*!**************************************************************************************!*\
  !*** ./src/app/admin/system-overview/system-dashboard/system-dashboard.component.ts ***!
  \**************************************************************************************/
/*! exports provided: SystemDashboardComponent */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemDashboardComponent", function() { return SystemDashboardComponent; });
/* harmony import */ var _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../shared/services/alert.service */ "Cmua");
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _services_system_report_service__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/system-report.service */ "3hjk");
/* harmony import */ var _models_system_overview__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/system-overview */ "0rMz");
/* harmony import */ var _shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../shared/services/host-site.service */ "Taa6");
/* harmony import */ var _services_system_tools_service__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/system-tools.service */ "pjEt");
/* harmony import */ var _tabs_tabs_component__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../tabs/tabs.component */ "a46E");
/* harmony import */ var _tab_tab_component__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../tab/tab.component */ "Evwa");
/* harmony import */ var _angular_forms__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @angular/forms */ "3Pt+");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../../shared/assessment-picker/assessment-picker.component */ "GTRT");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */
















function SystemDashboardComponent_option_7_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "option", 27);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const site_r10 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngValue", site_r10.id);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](site_r10.name);
} }
function SystemDashboardComponent_div_40_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](1, " Host Site Revenue ");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](2, "span");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r1 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx_r1.SystemOverview.hostSiteRevenue);
} }
function SystemDashboardComponent_tr_56_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipe"](7, "currency");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](8, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](9);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const item_r11 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r11.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r11.type);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipeBind1"](7, 4, item_r11.totalRevenue));
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r11.soldCount);
} }
function SystemDashboardComponent_tr_76_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "tr");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](3, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](5, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](7, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](8);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](9, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](10);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipe"](11, "currency");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](12, "td");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](13);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipe"](14, "currency");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const item_r12 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r12.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r12.type);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r12.author);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](item_r12.soldCount);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipeBind1"](11, 6, item_r12.totalRevenue));
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](_angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpipeBind1"](14, 8, item_r12.totalAuthorRevenue));
} }
function SystemDashboardComponent_option_83_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "option", 27);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const site_r13 = ctx.$implicit;
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngValue", site_r13.id);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](site_r13.name);
} }
function SystemDashboardComponent_input_88_Template(rf, ctx) { if (rf & 1) {
    const _r15 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "input", 28);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_input_88_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵrestoreView"](_r15); const ctx_r14 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"](); return ctx_r14.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} }
function SystemDashboardComponent_dac_assessment_picker_89_Template(rf, ctx) { if (rf & 1) {
    const _r17 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "dac-assessment-picker", 29);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("selected", function SystemDashboardComponent_dac_assessment_picker_89_Template_dac_assessment_picker_selected_0_listener($event) { _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵrestoreView"](_r17); const ctx_r16 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"](); return ctx_r16.updateAssessment($event); });
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} }
function SystemDashboardComponent_input_90_Template(rf, ctx) { if (rf & 1) {
    const _r19 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵgetCurrentView"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "input", 30);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_input_90_Template_input_click_0_listener() { _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵrestoreView"](_r19); const ctx_r18 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"](); return ctx_r18.toggleAssessmentPicker(); });
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} }
function SystemDashboardComponent_div_91_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "div", 15);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "div", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](2, "h2");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r8 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate1"]("Assessment: ", ctx_r8.assessment.name, "");
} }
const _c0 = function (a0) { return { "mdb-color lighten-5": a0 }; };
function SystemDashboardComponent_div_95_div_3_div_9_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "div", 34);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "div", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](3, "div", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](4);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const frequency_r23 = ctx.$implicit;
    const index_r24 = ctx.index;
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngClass", _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵpureFunction1"](3, _c0, index_r24 % 2 == 0));
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](frequency_r23.score);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](frequency_r23.frequency);
} }
function SystemDashboardComponent_div_95_div_3_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "div", 15);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "div", 8);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](2, "h2");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](4, "div", 32);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](5, "div", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](6, "Scale Score");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](7, "div", 16);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](8, "Frequency Count");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](9, SystemDashboardComponent_div_95_div_3_div_9_Template, 5, 5, "div", 33);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const scale_r21 = ctx.$implicit;
    const ctx_r20 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](scale_r21.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](6);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx_r20.getScaleFrequencies(scale_r21));
} }
function SystemDashboardComponent_div_95_Template(rf, ctx) { if (rf & 1) {
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "div");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "h1");
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](3, SystemDashboardComponent_div_95_div_3_Template, 10, 2, "div", 31);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
} if (rf & 2) {
    const ctx_r9 = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵnextContext"]();
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](2);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx_r9.AssessmentData.assessment.name);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
    _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx_r9.AssessmentScales);
} }
class SystemDashboardComponent {
    constructor(reportService, alertService, hostSiteService, systemTools) {
        this.reportService = reportService;
        this.alertService = alertService;
        this.hostSiteService = hostSiteService;
        this.systemTools = systemTools;
        this.reset();
        this.showAssessmentPicker = false;
    }
    ngOnInit() {
        this._hasLoadedData = false;
        this._data = new _models_system_overview__WEBPACK_IMPORTED_MODULE_3__["SystemOverview"];
        this.loadData();
    }
    loadData() {
        let alertId = this.alertService.info("Loading system data", 90000);
        let startDate = this.startDate && this.startDate.trim() != "" ? new Date(this.startDate + " 00:00:00") : new Date(0);
        let endDate = this.endDate && this.endDate.trim() != "" ? new Date(this.endDate + " 11:59:59") : new Date();
        let hostSiteId = this.hostSiteId != 0 ? +this.hostSiteId : null;
        Promise.all([
            this.reportService.getSystemOverviewByDate(startDate, endDate, this.hostSiteId),
            Promise.resolve([]) // this.hostSiteService.getHostSites()
        ])
            .then((results) => {
            let [data, hostSites] = results;
            this._hostSites = hostSites;
            this._data = data;
            this._hasLoadedData = true;
            this.alertService.clearAlert(alertId);
        })
            .catch((error) => {
            console.error(error);
            this.alertService.error("Failed to load data from system");
            this.alertService.clearAlert(alertId);
        });
    }
    get assessment() {
        return this._assessment;
    }
    get HostSites() {
        return this._hostSites;
    }
    filter() {
        this.loadData();
    }
    reset() {
        this.hostSiteId = null;
        this.startDate = "";
        this.endDate = "";
    }
    get SystemOverview() {
        return this._data;
    }
    get hasLoadedData() {
        return this._hasLoadedData;
    }
    get AssessmentData() {
        return this._assessmentData;
    }
    get AssessmentScales() {
        return this._assessmentData.frequencies.keys();
    }
    getScaleFrequencies(scale) {
        let frequencies = this._assessmentData.frequencies.get(scale);
        let sortedFrequencies = Object.keys(frequencies).map(i => +i).sort((a, b) => a - b);
        let total = sortedFrequencies.reduce((prev, current) => prev + frequencies[current], 0);
        let results = sortedFrequencies.map(i => {
            return { "score": i.toString(), "frequency": frequencies[i] };
        });
        results.push({ "score": "Total", "frequency": total });
        return results;
    }
    getAssessmentData() {
        if (!this._assessment) {
            this.alertService.error("Please choose an assessment for the report");
            return;
        }
        let alertId = this.alertService.info("Loading system data", 90000);
        let startDate = this.startDate && this.startDate.trim() != "" ? new Date(this.startDate + " 00:00:00") : new Date(0);
        let endDate = this.endDate && this.endDate.trim() != "" ? new Date(this.endDate + " 11:59:59") : new Date();
        let hostSiteId = this.hostSiteId != 0 ? +this.hostSiteId : null;
        this.reportService.getSystemAssessmentData(this._assessment.uid, startDate, endDate, hostSiteId).then((data) => {
            this._assessmentData = data;
            this._hasLoadedData = true;
            this.alertService.clearAlert(alertId);
        })
            .catch((error) => {
            console.error(error);
            this.alertService.clearAlert(alertId);
            this.alertService.error("Check console for error there was an error in loading your data");
        });
    }
    toggleAssessmentPicker() {
        this.showAssessmentPicker = !this.showAssessmentPicker;
    }
    updateAssessment(assessment) {
        this._assessment = assessment;
        this.toggleAssessmentPicker();
    }
    generateSearch() {
        let searchCompanyId = +this.searchGeneratorCompanyId;
        let userId = +this.searchGeneratorUserId;
        if (isNaN(searchCompanyId) || searchCompanyId < 1) {
            this.alertService.error("Company id must be a valid number");
            return;
        }
        if (isNaN(userId) || userId < 1) {
            this.alertService.error("User id must be a valid number");
            return;
        }
        let info = this.alertService.info("Generating data....", 60000);
        this.systemTools.generateSearch(searchCompanyId, userId).then((resp) => {
            this.alertService.clearAlert(info);
            this.alertService.success("Data generated");
        })
            .catch(error => {
            console.error(error);
            this.alertService.error("Failed to generate data");
        });
    }
    generateRandomSearch() {
        let searchCompanyId = +this.searchGeneratorCompanyId;
        let userId = +this.searchGeneratorUserId;
        let recordsToGenerate = +this.searchGeneratorRecordsCount;
        if (isNaN(searchCompanyId) || searchCompanyId < 1) {
            this.alertService.error("Company id must be a valid number");
            return;
        }
        if (isNaN(userId) || userId < 1) {
            this.alertService.error("User id must be a valid number");
            return;
        }
        if (isNaN(recordsToGenerate) || recordsToGenerate > 5500) {
            this.alertService.error("Records to generate is required and must be less than 5500");
            return;
        }
        recordsToGenerate = Math.max(recordsToGenerate, 0);
        let batchCount = 0;
        let batchSize = 25;
        let maxBatches = recordsToGenerate / batchSize;
        let generateBatch = () => {
            if (batchCount++ >= maxBatches) {
                return;
            }
            let info = this.alertService.info("Generating batch data " + batchCount, 60000);
            this.systemTools.generateSearch(searchCompanyId, userId, batchSize).then((resp) => {
                this.alertService.clearAlert(info);
                this.alertService.success("Data generated for batch " + batchCount);
                generateBatch();
            })
                .catch(error => {
                console.error(error);
                this.alertService.error("Failed to generate data");
            });
        };
        generateBatch();
    }
}
SystemDashboardComponent.ɵfac = function SystemDashboardComponent_Factory(t) { return new (t || SystemDashboardComponent)(_angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdirectiveInject"](_services_system_report_service__WEBPACK_IMPORTED_MODULE_2__["SystemReportService"]), _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdirectiveInject"](_shared_services_alert_service__WEBPACK_IMPORTED_MODULE_0__["AlertService"]), _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdirectiveInject"](_shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__["HostSiteService"]), _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdirectiveInject"](_services_system_tools_service__WEBPACK_IMPORTED_MODULE_5__["SystemToolsService"])); };
SystemDashboardComponent.ɵcmp = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdefineComponent"]({ type: SystemDashboardComponent, selectors: [["dac-system-dashboard"]], decls: 106, vars: 30, consts: [[1, "col-12"], ["heading", "Revenue Overview"], [1, "browser-default", 3, "ngModel", "ngModelChange"], ["value", "0"], [3, "ngValue", 4, "ngFor", "ngForOf"], ["type", "text", "required", "", 3, "placeholder", "ngModel", "ngModelChange"], ["type", "button", "value", "Filter", 3, "click"], ["type", "button", "value", "Reset", 3, "click"], [1, "card", "card-body"], [4, "ngIf"], [1, "h1-responsive"], [1, "table", "table-striped"], [1, "secondary-color", "text-light"], [4, "ngFor", "ngForOf"], ["heading", "Assessments Data Overview"], [1, "row"], [1, "col"], ["type", "button", "class", "btn btn-secondary", "value", "Close Assessment Picker", 3, "click", 4, "ngIf"], [3, "selected", 4, "ngIf"], ["type", "button", "class", "btn btn-secondary", "value", "Choose Assessment", 3, "click", 4, "ngIf"], ["class", "row", 4, "ngIf"], ["type", "button", "value", "Load Data", 1, "btn", "btn-primary", 3, "click"], ["heading", "Tools"], ["type", "text", "placeholder", "Company id...", 3, "ngModel", "ngModelChange"], ["type", "text", "placeholder", "User id...", 3, "ngModel", "ngModelChange"], ["type", "button", "value", "Generate", 3, "click"], ["type", "text", "placeholder", "Records to generate", 3, "ngModel", "ngModelChange"], [3, "ngValue"], ["type", "button", "value", "Close Assessment Picker", 1, "btn", "btn-secondary", 3, "click"], [3, "selected"], ["type", "button", "value", "Choose Assessment", 1, "btn", "btn-secondary", 3, "click"], ["class", "row", 4, "ngFor", "ngForOf"], [1, "row", "primary-color", "text-light"], ["class", "row", 3, "ngClass", 4, "ngFor", "ngForOf"], [1, "row", 3, "ngClass"]], template: function SystemDashboardComponent_Template(rf, ctx) { if (rf & 1) {
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](0, "dac-tabs", 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](1, "dac-tab", 1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](2, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](3, "Filter by host site");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](4, "select", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_select_ngModelChange_4_listener($event) { return ctx.hostSiteId = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](5, "option", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](6, "All Sites");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](7, SystemDashboardComponent_option_7_Template, 2, 2, "option", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](8, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](9, " Search by date ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](10, "p");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](11, "start date ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](12, "input", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_12_listener($event) { return ctx.startDate = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](13, "input", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_13_listener($event) { return ctx.endDate = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](14, "input", 6);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_Template_input_click_14_listener() { return ctx.filter(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](15, "input", 7);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_Template_input_click_15_listener() { return ctx.reset(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](16, "div", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](17, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](18, "Overview");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](19, " Total New Companies: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](20, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](21);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](22, " Total New Clients: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](23, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](24);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](25, " Total New Assessments: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](26, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](27);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](28, " Total New Assessments Completed: ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](29, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](30);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](31, " Total New Revenue ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](32, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](33);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](34, " Total Fees ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](35, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](36);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](37, " Total New Orders ");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](38, "span");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](39);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](40, SystemDashboardComponent_div_40_Template, 4, 1, "div", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](41, "div", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](42, "h1", 10);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](43, "Revenue by Items");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](44, "table", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](45, "thead", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](46, "tr");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](47, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](48, "Item Name");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](49, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](50, "Item Type (bundle/item)");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](51, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](52, "Total Revenue");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](53, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](54, "# sold");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](55, "tbody");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](56, SystemDashboardComponent_tr_56_Template, 10, 6, "tr", 13);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](57, "div", 8);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](58, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](59, "Assessments with agreements");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](60, "table", 11);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](61, "thead", 12);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](62, "tr");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](63, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](64, "Item Name");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](65, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](66, "Type");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](67, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](68, "Agreement Author");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](69, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](70, "Sold Count");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](71, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](72, "Total Revenue");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](73, "td");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](74, "Author(s) Revenue Share %");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](75, "tbody");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](76, SystemDashboardComponent_tr_76_Template, 15, 10, "tr", 13);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](77, "dac-tab", 14);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](78, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](79, "Filter by host site");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](80, "select", 2);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_select_ngModelChange_80_listener($event) { return ctx.hostSiteId = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](81, "option", 3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](82, "All Sites");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](83, SystemDashboardComponent_option_83_Template, 2, 2, "option", 4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](84, "input", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_84_listener($event) { return ctx.startDate = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](85, "input", 5);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_85_listener($event) { return ctx.endDate = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](86, "div", 15);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](87, "div", 16);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](88, SystemDashboardComponent_input_88_Template, 1, 0, "input", 17);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](89, SystemDashboardComponent_dac_assessment_picker_89_Template, 1, 0, "dac-assessment-picker", 18);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](90, SystemDashboardComponent_input_90_Template, 1, 0, "input", 19);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](91, SystemDashboardComponent_div_91_Template, 4, 1, "div", 20);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](92, "div", 15);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](93, "div", 16);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](94, "input", 21);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_Template_input_click_94_listener() { return ctx.getAssessmentData(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtemplate"](95, SystemDashboardComponent_div_95_Template, 4, 2, "div", 9);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](96, "dac-tab", 22);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](97, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](98, "Search Generator");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](99, "input", 23);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_99_listener($event) { return ctx.searchGeneratorCompanyId = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](100, "input", 24);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_100_listener($event) { return ctx.searchGeneratorUserId = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](101, "input", 25);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_Template_input_click_101_listener() { return ctx.generateSearch(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](102, "h1");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtext"](103, "Random Search Generator Options");
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](104, "input", 26);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("ngModelChange", function SystemDashboardComponent_Template_input_ngModelChange_104_listener($event) { return ctx.searchGeneratorRecordsCount = $event; });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementStart"](105, "input", 25);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵlistener"]("click", function SystemDashboardComponent_Template_input_click_105_listener() { return ctx.generateRandomSearch(); });
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵelementEnd"]();
    } if (rf & 2) {
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngModel", ctx.hostSiteId);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx.HostSites);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](5);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("placeholder", "Date Format YYYY-MM-DD")("ngModel", ctx.startDate);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("placeholder", "Date Format YYYY-MM-DD")("ngModel", ctx.endDate);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](8);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalCompanies);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalClients);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalAssessmentsAssigned);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalAssessmentsCompleted);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalRevenue);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalFees);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵtextInterpolate"](ctx.SystemOverview.totalOrders);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", ctx.hostSiteId != 0);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](16);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx.SystemOverview.revenueItems);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](20);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx.SystemOverview.authorAgreementItems);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngModel", ctx.hostSiteId);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngForOf", ctx.HostSites);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("placeholder", "Date Format YYYY-MM-DD")("ngModel", ctx.startDate);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("placeholder", "Date Format YYYY-MM-DD")("ngModel", ctx.endDate);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](3);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", !ctx.showAssessmentPicker);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", ctx.assessment);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngIf", ctx.AssessmentData);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngModel", ctx.searchGeneratorCompanyId);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](1);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngModel", ctx.searchGeneratorUserId);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵadvance"](4);
        _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵproperty"]("ngModel", ctx.searchGeneratorRecordsCount);
    } }, directives: [_tabs_tabs_component__WEBPACK_IMPORTED_MODULE_6__["TabsComponent"], _tab_tab_component__WEBPACK_IMPORTED_MODULE_7__["TabComponent"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["SelectControlValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["NgControlStatus"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["NgModel"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["NgSelectOption"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["ɵangular_packages_forms_forms_x"], _angular_common__WEBPACK_IMPORTED_MODULE_9__["NgForOf"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["DefaultValueAccessor"], _angular_forms__WEBPACK_IMPORTED_MODULE_8__["RequiredValidator"], _angular_common__WEBPACK_IMPORTED_MODULE_9__["NgIf"], _shared_assessment_picker_assessment_picker_component__WEBPACK_IMPORTED_MODULE_10__["AssessmentPickerComponent"], _angular_common__WEBPACK_IMPORTED_MODULE_9__["NgClass"]], pipes: [_angular_common__WEBPACK_IMPORTED_MODULE_9__["CurrencyPipe"]], encapsulation: 2 });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵsetClassMetadata"](SystemDashboardComponent, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_1__["Component"],
        args: [{
                selector: 'dac-system-dashboard',
                templateUrl: './system-dashboard.component.html'
            }]
    }], function () { return [{ type: _services_system_report_service__WEBPACK_IMPORTED_MODULE_2__["SystemReportService"] }, { type: _shared_services_alert_service__WEBPACK_IMPORTED_MODULE_0__["AlertService"] }, { type: _shared_services_host_site_service__WEBPACK_IMPORTED_MODULE_4__["HostSiteService"] }, { type: _services_system_tools_service__WEBPACK_IMPORTED_MODULE_5__["SystemToolsService"] }]; }, null); })();


/***/ }),

/***/ "3hjk":
/*!*************************************************************************!*\
  !*** ./src/app/admin/system-overview/services/system-report.service.ts ***!
  \*************************************************************************/
/*! exports provided: SystemReportService */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemReportService", function() { return SystemReportService; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../shared/services/http.service */ "Tdnt");
/* harmony import */ var _models_system_revenue_item__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../models/system-revenue-item */ "YzvI");
/* harmony import */ var _models_author_agreement_item__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/author-agreement-item */ "KgPx");
/* harmony import */ var _models_host_site__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../models/host-site */ "nSd4");
/* harmony import */ var _models_system_overview__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../models/system-overview */ "0rMz");
/* harmony import */ var _shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../shared/services/assessment.service */ "7myg");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */

// TODO: stephen only used for mocking the service right now...









class SystemReportService {
    constructor(http, assessmentService) {
        this.http = http;
        this.assessmentService = assessmentService;
    }
    getHostSites() {
        return Promise.resolve([]);
    }
    getSystemAssessmentData(uid, startDate, endDate, hostSiteId) {
        let path = "/admin/system/assessment/";
        let data = {
            host: hostSiteId,
            uid: uid,
            start: startDate.getTime(),
            end: endDate.getTime()
        };
        let getStatData = this.http.get(path, data).then((json) => {
            return json;
        });
        // we merge the data together this way.
        let getAssessment = this.assessmentService.getAssessment(uid);
        return Promise.all([getStatData, getAssessment]).then((results) => {
            let statData = results[0];
            let assessment = results[1];
            let frequencies = new Map();
            Object.keys(statData.frequencyCounts).forEach(scaleId => {
                frequencies.set(assessment.scales.find(s => s.id == scaleId), statData.frequencyCounts[scaleId]);
            });
            let counts = {
                assessment: results[1],
                frequencies: frequencies
            };
            return counts;
        });
    }
    getSystemOverview(hostSiteId) {
        return this.getSystemOverviewByDate(new Date(0), new Date(), hostSiteId);
    }
    getSystemOverviewByDate(start, end, hostSiteId) {
        let path = "/admin/system/";
        let query = "?";
        let params = [];
        if (hostSiteId) {
            params.push("host=" + +hostSiteId);
        }
        params.push("start=" + start.getTime());
        params.push("end=" + end.getTime());
        query = query + params.join("&");
        let data = new _models_system_overview__WEBPACK_IMPORTED_MODULE_5__["SystemOverview"]();
        data.authorAgreementItems = [1, 2, 3, 4, 5].map((i) => {
            return Object.assign(new _models_author_agreement_item__WEBPACK_IMPORTED_MODULE_3__["AuthorAgreementItem"](), { name: "HBI-19", bundleCount: i, soldCount: i,
                totalRevenue: i * 10, authorRevenue: i * 5, systemRevenue: i * 5 });
        });
        data.revenueItems = [1, 2, 3, 4, 5, 6, 7].map((i) => {
            return Object.assign(new _models_system_revenue_item__WEBPACK_IMPORTED_MODULE_2__["SystemRevenueItem"](), { name: "HBI-19", type: (i % 2 == 0 ? "Bundle" : "Item"),
                soldCount: i, totalRevenue: i * 100, totalFees: i * 5, hostSiteRevenue: i * 40, systemRevenue: i * 60 });
        });
        data.hostSite = Object.assign(new _models_host_site__WEBPACK_IMPORTED_MODULE_4__["HostSite"](), { id: 1, name: "Discover and Change" });
        data.totalAssessmentsAssigned = 5;
        data.totalAssessmentsCompleted = 5;
        data.totalClients = 15;
        data.totalCompanies = 30;
        data.totalRevenue = 3500;
        // return Promise.resolve(data);
        return this.http.get(path + query).then((json) => {
            return json;
        });
    }
}
SystemReportService.ɵfac = function SystemReportService_Factory(t) { return new (t || SystemReportService)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵinject"](_shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__["HTTPService"]), _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵinject"](_shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_6__["AssessmentService"])); };
SystemReportService.ɵprov = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjectable"]({ token: SystemReportService, factory: SystemReportService.ɵfac });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](SystemReportService, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Injectable"]
    }], function () { return [{ type: _shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__["HTTPService"] }, { type: _shared_services_assessment_service__WEBPACK_IMPORTED_MODULE_6__["AssessmentService"] }]; }, null); })();


/***/ }),

/***/ "KcW0":
/*!*************************************************************************!*\
  !*** ./src/app/admin/system-overview/system-overview-routing.module.ts ***!
  \*************************************************************************/
/*! exports provided: SystemOverviewRoutingModule */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemOverviewRoutingModule", function() { return SystemOverviewRoutingModule; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/router */ "tyNb");
/* harmony import */ var _system_dashboard_system_dashboard_component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./system-dashboard/system-dashboard.component */ "2LnN");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */





const routes = [
    { path: '', component: _system_dashboard_system_dashboard_component__WEBPACK_IMPORTED_MODULE_2__["SystemDashboardComponent"] }
];
class SystemOverviewRoutingModule {
}
SystemOverviewRoutingModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineNgModule"]({ type: SystemOverviewRoutingModule });
SystemOverviewRoutingModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjector"]({ factory: function SystemOverviewRoutingModule_Factory(t) { return new (t || SystemOverviewRoutingModule)(); }, imports: [[
            _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"].forChild(routes)
        ], _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]] });
(function () { (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵsetNgModuleScope"](SystemOverviewRoutingModule, { imports: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]], exports: [_angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]] }); })();
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](SystemOverviewRoutingModule, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["NgModule"],
        args: [{
                imports: [
                    _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"].forChild(routes)
                ],
                exports: [
                    _angular_router__WEBPACK_IMPORTED_MODULE_1__["RouterModule"]
                ]
            }]
    }], null, null); })();


/***/ }),

/***/ "KgPx":
/*!***********************************************************************!*\
  !*** ./src/app/admin/system-overview/models/author-agreement-item.ts ***!
  \***********************************************************************/
/*! exports provided: AuthorAgreementItem */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AuthorAgreementItem", function() { return AuthorAgreementItem; });
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */
class AuthorAgreementItem {
}


/***/ }),

/***/ "YzvI":
/*!*********************************************************************!*\
  !*** ./src/app/admin/system-overview/models/system-revenue-item.ts ***!
  \*********************************************************************/
/*! exports provided: SystemRevenueItem */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemRevenueItem", function() { return SystemRevenueItem; });
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */
class SystemRevenueItem {
}


/***/ }),

/***/ "lfx0":
/*!*****************************************************************!*\
  !*** ./src/app/admin/system-overview/system-overview.module.ts ***!
  \*****************************************************************/
/*! exports provided: SystemOverviewModule */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemOverviewModule", function() { return SystemOverviewModule; });
/* harmony import */ var _shared_shared_module__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../shared/shared.module */ "PCNd");
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _angular_common__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @angular/common */ "ofXK");
/* harmony import */ var _system_dashboard_system_dashboard_component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./system-dashboard/system-dashboard.component */ "2LnN");
/* harmony import */ var _system_overview_routing_module__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./system-overview-routing.module */ "KcW0");
/* harmony import */ var _services_system_report_service__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./services/system-report.service */ "3hjk");
/* harmony import */ var _services_system_tools_service__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./services/system-tools.service */ "pjEt");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */








class SystemOverviewModule {
}
SystemOverviewModule.ɵmod = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdefineNgModule"]({ type: SystemOverviewModule });
SystemOverviewModule.ɵinj = _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵdefineInjector"]({ factory: function SystemOverviewModule_Factory(t) { return new (t || SystemOverviewModule)(); }, providers: [_services_system_report_service__WEBPACK_IMPORTED_MODULE_5__["SystemReportService"], _services_system_tools_service__WEBPACK_IMPORTED_MODULE_6__["SystemToolsService"]], imports: [[
            _angular_common__WEBPACK_IMPORTED_MODULE_2__["CommonModule"],
            _shared_shared_module__WEBPACK_IMPORTED_MODULE_0__["SharedModule"],
            _system_overview_routing_module__WEBPACK_IMPORTED_MODULE_4__["SystemOverviewRoutingModule"]
        ]] });
(function () { (typeof ngJitMode === "undefined" || ngJitMode) && _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵɵsetNgModuleScope"](SystemOverviewModule, { declarations: [_system_dashboard_system_dashboard_component__WEBPACK_IMPORTED_MODULE_3__["SystemDashboardComponent"]], imports: [_angular_common__WEBPACK_IMPORTED_MODULE_2__["CommonModule"],
        _shared_shared_module__WEBPACK_IMPORTED_MODULE_0__["SharedModule"],
        _system_overview_routing_module__WEBPACK_IMPORTED_MODULE_4__["SystemOverviewRoutingModule"]] }); })();
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_1__["ɵsetClassMetadata"](SystemOverviewModule, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_1__["NgModule"],
        args: [{
                imports: [
                    _angular_common__WEBPACK_IMPORTED_MODULE_2__["CommonModule"],
                    _shared_shared_module__WEBPACK_IMPORTED_MODULE_0__["SharedModule"],
                    _system_overview_routing_module__WEBPACK_IMPORTED_MODULE_4__["SystemOverviewRoutingModule"]
                ],
                providers: [_services_system_report_service__WEBPACK_IMPORTED_MODULE_5__["SystemReportService"], _services_system_tools_service__WEBPACK_IMPORTED_MODULE_6__["SystemToolsService"]],
                declarations: [_system_dashboard_system_dashboard_component__WEBPACK_IMPORTED_MODULE_3__["SystemDashboardComponent"]]
            }]
    }], null, null); })();


/***/ }),

/***/ "nSd4":
/*!***********************************************************!*\
  !*** ./src/app/admin/system-overview/models/host-site.ts ***!
  \***********************************************************/
/*! exports provided: HostSite */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "HostSite", function() { return HostSite; });
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */
class HostSite {
}


/***/ }),

/***/ "pjEt":
/*!************************************************************************!*\
  !*** ./src/app/admin/system-overview/services/system-tools.service.ts ***!
  \************************************************************************/
/*! exports provided: SystemToolsService */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SystemToolsService", function() { return SystemToolsService; });
/* harmony import */ var _angular_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @angular/core */ "fXoL");
/* harmony import */ var _shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../shared/services/http.service */ "Tdnt");
/*
 *   Copyright (c) 2022 Discover and Change @author Stephen Nielson <snielson@discoverandchange.com>
 *   All rights reserved.
 *   For License information see LICENSE.md in the root folder of this project.
 */




class SystemToolsService {
    constructor(http) {
        this.http = http;
    }
    generateSearch(companyId, userId, recordsToGenerate) {
        let path = "/admin/system/tools/generateSearch";
        return this.http.post(path, { companyId: companyId, userId: userId, recordsToGenerate: recordsToGenerate });
    }
}
SystemToolsService.ɵfac = function SystemToolsService_Factory(t) { return new (t || SystemToolsService)(_angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵinject"](_shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__["HTTPService"])); };
SystemToolsService.ɵprov = _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵɵdefineInjectable"]({ token: SystemToolsService, factory: SystemToolsService.ɵfac });
/*@__PURE__*/ (function () { _angular_core__WEBPACK_IMPORTED_MODULE_0__["ɵsetClassMetadata"](SystemToolsService, [{
        type: _angular_core__WEBPACK_IMPORTED_MODULE_0__["Injectable"]
    }], function () { return [{ type: _shared_services_http_service__WEBPACK_IMPORTED_MODULE_1__["HTTPService"] }]; }, null); })();


/***/ })

}]);
//# sourceMappingURL=system-overview-system-overview-module-es2015.js.map