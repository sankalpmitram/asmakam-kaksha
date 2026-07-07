<?php
/**
 * classes.php
 * Class management: list, search, add, edit, delete.
 */
?>
<div class="page-container" id="classesPage">

    <div class="search-bar">
        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/></svg>
        <input type="text" id="classSearchInput" placeholder="कक्षा खोजें...">
    </div>

    <div id="classesList" class="list-container">
        <div class="empty-state" id="classesEmptyState" style="display:none;">
            <p>कोई कक्षा नहीं मिली।</p>
        </div>
    </div>
</div>

<button class="fab" id="addClassFab" title="नई कक्षा जोड़ें" aria-label="नई कक्षा जोड़ें">
    <svg viewBox="0 0 24 24" width="26" height="26"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/></svg>
</button>

<!-- Add / Edit Class Modal Template -->
<template id="classFormTemplate">
    <div class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modalTitleText">कक्षा जोड़ें</h3>
                <button class="icon-btn close-modal-btn" aria-label="बंद करें">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <form id="classForm" class="modal-form">
                <input type="hidden" name="id">
                <div class="form-group">
                    <label>कक्षा नाम *</label>
                    <input type="text" name="name" required placeholder="उदा. कक्षा ५">
                </div>
                <div class="form-group">
                    <label>वर्गः (Section)</label>
                    <input type="text" name="section" placeholder="उदा. अ">
                </div>
                <div class="form-group">
                    <label>शैक्षणिक सत्र</label>
                    <input type="text" name="session" placeholder="उदा. 2026-2027">
                </div>
                <div class="form-group">
                    <label>कक्षा शिक्षक</label>
                    <input type="text" name="teacher" placeholder="शिक्षक नाम">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary close-modal-btn">रद्द करें</button>
                    <button type="submit" class="btn btn-primary">सञ्चयतु</button>
                </div>
            </form>
        </div>
    </div>
</template>
