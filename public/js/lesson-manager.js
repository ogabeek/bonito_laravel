/**
 * Lesson Management JavaScript
 * Handles CRUD operations and form persistence for lessons
 */

// Session storage keys
const STORAGE_KEYS = {
    STUDENT: 'lastSelectedStudent',
    DATE: 'lastSelectedDate'
};

/**
 * Save new lesson via AJAX
 */
async function saveNewLesson(formData) {
    const response = await fetch('/teacher/lesson/create', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to save lesson');
    }
    
    return response.json();
}

/**
 * Update existing lesson via AJAX
 */
async function updateLesson(lessonId, formData) {
    const response = await fetch(`/lesson/${lessonId}/update`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Failed to update lesson');
    }
    
    return response.json();
}

/**
 * Delete lesson via AJAX
 */
async function deleteLesson(lessonId) {
    if (!confirm('Are you sure you want to delete this lesson?')) {
        return;
    }
    
    const response = await fetch(`/lesson/${lessonId}/delete`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    });
    
    if (!response.ok) {
        throw new Error('Failed to delete lesson');
    }
    
    window.location.reload();
}

/**
 * Save form state to session storage
 */
function saveFormState(studentId, date) {
    if (studentId) {
        sessionStorage.setItem(STORAGE_KEYS.STUDENT, studentId);
    }
    if (date) {
        sessionStorage.setItem(STORAGE_KEYS.DATE, date);
    }
}

/**
 * Restore form state from session storage
 */
function restoreFormState() {
    const lastStudent = sessionStorage.getItem(STORAGE_KEYS.STUDENT);
    const lastDate = sessionStorage.getItem(STORAGE_KEYS.DATE);
    
    if (lastStudent) {
        const studentSelect = document.querySelector('#newLessonForm select[name="student_id"]');
        if (studentSelect) {
            studentSelect.value = lastStudent;
        }
    }
    
    // Date restoration is handled by Alpine.js calendar component
    return { student: lastStudent, date: lastDate };
}

/**
 * Clear form state from session storage
 */
function clearFormState() {
    sessionStorage.removeItem(STORAGE_KEYS.STUDENT);
    sessionStorage.removeItem(STORAGE_KEYS.DATE);
}

/**
 * Handle new lesson form submission
 */
async function handleNewLessonSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        // Save to session storage
        saveFormState(formData.get('student_id'), formData.get('class_date'));
        
        await saveNewLesson(formData);
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

/**
 * Handle lesson update form submission
 */
async function handleLessonUpdate(lessonId, event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        await updateLesson(lessonId, formData);
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

/**
 * Initialize lesson management
 */
document.addEventListener('DOMContentLoaded', function() {
    // Restore form state on page load
    restoreFormState();
    
    // Setup new lesson form
    const newLessonForm = document.getElementById('newLessonForm');
    if (newLessonForm) {
        newLessonForm.addEventListener('submit', handleNewLessonSubmit);
    }
    
    // Clear form state when cancel button is clicked
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-clear-form-state]')) {
            clearFormState();
        }
    });
});

// Make functions globally available for inline Alpine.js usage
window.saveLesson = handleLessonUpdate;
window.deleteLesson = deleteLesson;
