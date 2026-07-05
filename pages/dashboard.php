<?php
/**
 * dashboard.php
 * Shows summary stats (fetched via api/dashboard.php) and quick action buttons.
 */
?>
<div class="page-container" id="dashboardPage">

    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M4 4h16v2H4V4zm0 5h16v2H4V9zm0 5h10v2H4v-2zm0 5h10v2H4v-2z"/></svg>
            </div>
            <div class="stat-value" id="statTotalClasses">—</div>
            <div class="stat-label">कुल कक्षाः</div>
        </div>
        <div class="stat-card stat-accent">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M12 2 1 7l11 5 9-4.09V17h2V7L12 2zM5 13.18v4.72L12 21l7-3.1v-4.72l-7 3.1-7-3.1z"/></svg>
            </div>
            <div class="stat-value" id="statTotalStudents">—</div>
            <div class="stat-label">कुल छात्राः</div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
            </div>
            <div class="stat-value" id="statPresent">—</div>
            <div class="stat-label">आज उपस्थितः</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
            </div>
            <div class="stat-value" id="statAbsent">—</div>
            <div class="stat-label">आज अनुपस्थितः</div>
        </div>
    </div>

    <div class="card percentage-card">
        <div class="percentage-card-text">
            <span class="percentage-label">आज की उपस्थिति प्रतिशतता</span>
            <span class="percentage-value" id="statPercentage">0%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-bar-fill" id="statPercentageBar" style="width:0%"></div>
        </div>
    </div>

    <h3 class="section-title">त्वरित कार्य</h3>
    <div class="quick-actions">
        <a href="index.php?page=attendance" class="quick-action">
            <div class="quick-action-icon qa-1">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M9 11l3 3L22 4l-1.4-1.4L12 11.2 9 8.2 2 15l1.4 1.4L9 11zM2 20h20v2H2z"/></svg>
            </div>
            <span>उपस्थिति लें</span>
        </a>
        <a href="index.php?page=students" class="quick-action">
            <div class="quick-action-icon qa-2">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 2 1 7l11 5 9-4.09V17h2V7L12 2zM5 13.18v4.72L12 21l7-3.1v-4.72l-7 3.1-7-3.1z"/></svg>
            </div>
            <span>छात्राः</span>
        </a>
        <a href="index.php?page=classes" class="quick-action">
            <div class="quick-action-icon qa-3">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M4 4h16v2H4V4zm0 5h16v2H4V9zm0 5h10v2H4v-2zm0 5h10v2H4v-2z"/></svg>
            </div>
            <span>कक्षाः</span>
        </a>
        <a href="index.php?page=reports" class="quick-action">
            <div class="quick-action-icon qa-4">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm2 12h2v4H7v-4zm4-6h2v10h-2V9zm4 3h2v7h-2v-7z"/></svg>
            </div>
            <span>प्रतिवेदनम्</span>
        </a>
        <a href="index.php?page=settings" class="quick-action">
            <div class="quick-action-icon qa-5">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M19.14 12.94a7.14 7.14 0 0 0 .06-.94 7.14 7.14 0 0 0-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.3 7.3 0 0 0-1.63-.94L14.4 2.8a.5.5 0 0 0-.5-.4h-3.8a.5.5 0 0 0-.5.4l-.36 2.46a7.3 7.3 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.62-.06.94s.02.63.06.94L2.82 14.5a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.4 1.04.72 1.63.94l.36 2.46a.5.5 0 0 0 .5.4h3.8a.5.5 0 0 0 .5-.4l.36-2.46c.59-.22 1.13-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>
            </div>
            <span>विन्यासः</span>
        </a>
    </div>
</div>
