<?php
/**
 * attendance_history.php
 * View attendance history: monthly / calendar view for a class,
 * with ability to open and edit / delete a specific day's record.
 */
?>
<div class="page-container" id="attendanceHistoryPage">

    <div class="card">
        <div class="form-row">
            <div class="form-group">
                <label>कक्षा चुनें</label>
                <select id="historyClassSelect" class="select-input">
                    <option value="">कक्षा चुनें</option>
                </select>
            </div>
            <div class="form-group">
                <label>माह</label>
                <input type="month" id="historyMonthInput" class="select-input">
            </div>
        </div>
    </div>

    <div id="calendarView" class="calendar-grid"></div>

    <div class="empty-state" id="historyEmptyState">
        <p>कृपया कक्षा चुनें।</p>
    </div>
</div>

<template id="dayRecordModalTemplate">
    <div class="modal-overlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header">
                <h3 class="dayModalTitle">उपस्थिति विवरण</h3>
                <button class="icon-btn close-modal-btn" aria-label="बंद करें">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body" id="dayRecordBody"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" id="deleteDayRecordBtn">हटाएं</button>
                <button type="button" class="btn btn-primary" id="saveDayRecordBtn">सहेजें</button>
            </div>
        </div>
    </div>
</template>
