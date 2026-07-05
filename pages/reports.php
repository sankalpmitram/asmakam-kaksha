<?php
/**
 * reports.php
 * Reports hub: today's report, monthly report, class report, student report.
 * Each supports Export PDF (print-friendly) and Export Excel (CSV).
 */
?>
<div class="page-container" id="reportsPage">

    <div class="tabs" id="reportTabs">
        <button class="tab-btn active" data-tab="today">आज का</button>
        <button class="tab-btn" data-tab="monthly">मासिक</button>
        <button class="tab-btn" data-tab="class">कक्षा</button>
        <button class="tab-btn" data-tab="student">छात्र</button>
    </div>

    <!-- Today's report -->
    <div class="tab-panel active" data-panel="today">
        <div class="card filter-card">
            <div class="form-group">
                <label>तिथि</label>
                <input type="date" id="todayReportDate" class="select-input">
            </div>
            <div class="report-export-btns">
                <button class="btn btn-secondary btn-sm" id="todayExportPdf">📄 PDF</button>
                <button class="btn btn-secondary btn-sm" id="todayExportExcel">📊 Excel</button>
            </div>
        </div>
        <div id="todayReportResult" class="report-result"></div>
    </div>

    <!-- Monthly report -->
    <div class="tab-panel" data-panel="monthly">
        <div class="card filter-card">
            <div class="form-row">
                <div class="form-group">
                    <label>कक्षा</label>
                    <select id="monthlyReportClass" class="select-input">
                        <option value="">सभी कक्षाएँ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>माह</label>
                    <input type="month" id="monthlyReportMonth" class="select-input">
                </div>
            </div>
            <div class="report-export-btns">
                <button class="btn btn-secondary btn-sm" id="monthlyExportPdf">📄 PDF</button>
                <button class="btn btn-secondary btn-sm" id="monthlyExportExcel">📊 Excel</button>
            </div>
        </div>
        <div id="monthlyReportResult" class="report-result"></div>
    </div>

    <!-- Class report -->
    <div class="tab-panel" data-panel="class">
        <div class="card filter-card">
            <div class="form-group">
                <label>कक्षा चुनें</label>
                <select id="classReportClass" class="select-input">
                    <option value="">कक्षा चुनें</option>
                </select>
            </div>
            <div class="report-export-btns">
                <button class="btn btn-secondary btn-sm" id="classExportPdf">📄 PDF</button>
                <button class="btn btn-secondary btn-sm" id="classExportExcel">📊 Excel</button>
            </div>
        </div>
        <div id="classReportResult" class="report-result"></div>
    </div>

    <!-- Student report -->
    <div class="tab-panel" data-panel="student">
        <div class="card filter-card">
            <div class="form-row">
                <div class="form-group">
                    <label>कक्षा</label>
                    <select id="studentReportClass" class="select-input">
                        <option value="">कक्षा चुनें</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>छात्र</label>
                    <select id="studentReportStudent" class="select-input">
                        <option value="">छात्र चुनें</option>
                    </select>
                </div>
            </div>
            <div class="report-export-btns">
                <button class="btn btn-secondary btn-sm" id="studentExportPdf">📄 PDF</button>
                <button class="btn btn-secondary btn-sm" id="studentExportExcel">📊 Excel</button>
            </div>
        </div>
        <div id="studentReportResult" class="report-result"></div>
    </div>

</div>
