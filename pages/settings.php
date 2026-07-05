<?php
/**
 * settings.php
 * App settings: school info, teacher info, WhatsApp message template,
 * theme, backup/restore, reset, and password change.
 */
?>
<div class="page-container" id="settingsPage">

    <form id="settingsForm" class="card settings-card" enctype="multipart/form-data">
        <h3 class="section-title">विद्यालय जानकारी</h3>

        <div class="photo-upload-row">
            <img id="logoPreview" class="photo-preview logo-preview" src="assets/images/school-placeholder.svg" alt="लोगो">
            <label class="btn btn-secondary btn-sm photo-upload-btn">
                लोगो चुनें
                <input type="file" name="school_logo" id="schoolLogoInput" accept="image/png, image/jpeg, image/webp" hidden>
            </label>
        </div>

        <div class="form-group">
            <label>विद्यालय का नाम</label>
            <input type="text" name="school_name" id="schoolNameInput">
        </div>
        <div class="form-group">
            <label>शिक्षक का नाम</label>
            <input type="text" name="teacher_name" id="teacherNameInput">
        </div>
        <div class="form-group">
            <label>शैक्षणिक सत्र</label>
            <input type="text" name="session" id="sessionInput">
        </div>
        <div class="form-group">
            <label>उपस्थिति समय</label>
            <input type="time" name="default_attendance_time" id="attendanceTimeInput">
        </div>

        <h3 class="section-title">WhatsApp संदेश टेम्पलेट</h3>
        <p class="hint-text">प्रयोग करें: {student_name} एवं {date}</p>
        <div class="form-group">
            <textarea name="whatsapp_template" id="whatsappTemplateInput" rows="6"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">विन्यास सहेजें</button>
    </form>

    <div class="card settings-card">
        <h3 class="section-title">थीम</h3>
        <div class="theme-toggle-row">
            <button type="button" class="theme-option-btn" data-theme="light">☀️ लाइट मोड</button>
            <button type="button" class="theme-option-btn" data-theme="dark">🌙 डार्क मोड</button>
        </div>
    </div>

    <form id="passwordForm" class="card settings-card">
        <h3 class="section-title">कूटशब्द बदलें</h3>
        <div class="form-group">
            <label>पुराना कूटशब्द</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
            <label>नया कूटशब्द</label>
            <input type="password" name="new_password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary btn-block">कूटशब्द बदलें</button>
    </form>

    <div class="card settings-card">
        <h3 class="section-title">डेटा बैकअप</h3>
        <a href="api/backup.php" class="btn btn-secondary btn-block">⬇️ बैकअप डाउनलोड करें (ZIP)</a>

        <label class="btn btn-secondary btn-block restore-label" style="margin-top:10px;">
            ⬆️ बैकअप पुनर्स्थापित करें
            <input type="file" id="restoreFileInput" accept=".zip" hidden>
        </label>
    </div>

    <div class="card settings-card danger-zone">
        <h3 class="section-title">एप्लिकेशन रीसेट</h3>
        <p class="hint-text">यह सभी कक्षाएँ, छात्र एवं उपस्थिति डेटा स्थायी रूप से हटा देगा।</p>
        <button type="button" class="btn btn-danger btn-block" id="resetAppBtn">🗑️ एप्लिकेशन रीसेट करें</button>
    </div>

</div>
