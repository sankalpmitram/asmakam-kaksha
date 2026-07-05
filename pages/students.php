<?php
/**
 * students.php
 * Student management: list, search, filter by class, add, edit, delete, move.
 */
?>
<div class="page-container" id="studentsPage">

    <div class="search-bar">
        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
        <input type="text" id="studentSearchInput" placeholder="छात्र खोजें (नाम / अनुक्रमांक)...">
    </div>

    <div class="filter-row">
        <select id="studentClassFilter" class="select-input">
            <option value="">सभी कक्षाएँ</option>
        </select>
    </div>

    <div id="studentsList" class="list-container">
        <div class="empty-state" id="studentsEmptyState" style="display:none;">
            <p>कोई छात्र नहीं मिला।</p>
        </div>
    </div>
</div>

<button class="fab" id="addStudentFab" title="नया छात्र जोड़ें" aria-label="नया छात्र जोड़ें">
    <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg>
</button>

<!-- Add / Edit Student Modal Template -->
<template id="studentFormTemplate">
    <div class="modal-overlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header">
                <h3 class="modalTitleText">छात्र जोड़ें</h3>
                <button class="icon-btn close-modal-btn" aria-label="बंद करें">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <form id="studentForm" class="modal-form" enctype="multipart/form-data">
                <input type="hidden" name="id">

                <div class="photo-upload-row">
                    <img id="studentPhotoPreview" class="photo-preview" src="assets/images/student-placeholder.svg" alt="फोटो">
                    <label class="btn btn-secondary btn-sm photo-upload-btn">
                        फोटो चुनें
                        <input type="file" name="photo" id="studentPhotoInput" accept="image/png, image/jpeg, image/webp" hidden>
                    </label>
                </div>

                <div class="form-group">
                    <label>छात्र नाम *</label>
                    <input type="text" name="name" required placeholder="पूरा नाम">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>अनुक्रमांक *</label>
                        <input type="text" name="roll_number" required placeholder="उदा. 12">
                    </div>
                    <div class="form-group">
                        <label>कक्षा *</label>
                        <select name="class_id" id="studentFormClassSelect" required>
                            <option value="">कक्षा चुनें</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>पिता का नाम</label>
                        <input type="text" name="father_name">
                    </div>
                    <div class="form-group">
                        <label>माता का नाम</label>
                        <input type="text" name="mother_name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>अभिभावक का नाम</label>
                        <input type="text" name="guardian_name">
                    </div>
                    <div class="form-group">
                        <label>लिंग</label>
                        <select name="gender">
                            <option value="">चुनें</option>
                            <option value="बालक">बालक</option>
                            <option value="बालिका">बालिका</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>व्हाट्सएप संख्या</label>
                        <input type="tel" name="whatsapp_number" placeholder="10 अंकों की संख्या">
                    </div>
                    <div class="form-group">
                        <label>जन्म तिथि</label>
                        <input type="date" name="dob">
                    </div>
                </div>
                <div class="form-group">
                    <label>पता</label>
                    <textarea name="address" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>टिप्पणी</label>
                    <textarea name="notes" rows="2"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary close-modal-btn">रद्द करें</button>
                    <button type="submit" class="btn btn-primary">सञ्चयतु</button>
                </div>
            </form>
        </div>
    </div>
</template>

<!-- Move Student Modal Template -->
<template id="moveStudentTemplate">
    <div class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>छात्र को स्थानांतरित करें</h3>
                <button class="icon-btn close-modal-btn" aria-label="बंद करें">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <form id="moveStudentForm" class="modal-form">
                <input type="hidden" name="student_id">
                <div class="form-group">
                    <label>नई कक्षा चुनें</label>
                    <select name="class_id" id="moveClassSelect" required>
                        <option value="">कक्षा चुनें</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary close-modal-btn">रद्द करें</button>
                    <button type="submit" class="btn btn-primary">स्थानांतरित करें</button>
                </div>
            </form>
        </div>
    </div>
</template>
