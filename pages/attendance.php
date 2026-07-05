<?php
/**
 * attendance.php
 * Take / edit attendance for a selected class and date.
 */
?>
<div class="page-container" id="attendancePage">

    <div class="attendance-toolbar card">
        <div class="form-row">
            <div class="form-group">
                <label>कक्षा चुनें</label>
                <select id="attendanceClassSelect" class="select-input">
                    <option value="">कक्षा चुनें</option>
                </select>
            </div>
            <div class="form-group">
                <label>तिथि</label>
                <input type="date" id="attendanceDateInput" class="select-input">
            </div>
        </div>
        <a href="index.php?page=attendance_history" class="link-btn">📅 उपस्थिति इतिहास देखें</a>
    </div>

    <div id="attendanceStatusBanner" class="status-banner" style="display:none;"></div>

    <div id="attendanceControls" class="attendance-controls" style="display:none;">
        <button class="btn btn-secondary btn-sm" id="markAllPresentBtn">सभी उपस्थित</button>
        <span class="attendance-count" id="attendanceCountLabel"></span>
    </div>

    <div id="attendanceStudentList" class="attendance-list"></div>

    <div id="attendanceSaveBar" class="save-bar" style="display:none;">
        <button class="btn btn-primary btn-block" id="saveAttendanceBtn">उपस्थिति सहेजें</button>
    </div>

    <div id="whatsappSection" class="whatsapp-section" style="display:none;">
        <h3 class="section-title">अनुपस्थित छात्रों के अभिभावकों को WhatsApp संदेश भेजें</h3>
        <div id="whatsappList" class="list-container"></div>
    </div>

    <div class="empty-state" id="attendanceEmptyState">
        <p>कृपया कक्षा एवं तिथि चुनें।</p>
    </div>
</div>
