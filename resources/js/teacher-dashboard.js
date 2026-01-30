/**
 * * TEACHER DASHBOARD JS
 * * Handles: lesson creation (AJAX), lesson deletion, form state persistence
 */

const lessonErrorBox = document.getElementById('lessonFormErrors');

const clearLessonErrors = () => {
    if (!lessonErrorBox) return;
    lessonErrorBox.innerHTML = '';
    lessonErrorBox.classList.add('hidden');
};

const showLessonErrors = (messages) => {
    if (!lessonErrorBox) return;
    const list = Array.isArray(messages) ? messages : [messages];
    lessonErrorBox.innerHTML = '<ul class="list-disc pl-4">' + list.map(m => `<li>${m}</li>`).join('') + '</ul>';
    lessonErrorBox.classList.remove('hidden');
};

// * Prevents double-submit while request is in flight
let lessonSubmitting = false;

/**
 * * CREATE LESSON: AJAX form submission
 * ! Reloads page on success to show new lesson
 */
document.addEventListener('submit', function(e) {
    if (e.target.id === 'newLessonForm') {
        e.preventDefault();

        if (lessonSubmitting) return;
        lessonSubmitting = true;
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        clearLessonErrors();
        
        // * Save form state to restore after reload
        const selectedStudent = data.student_id;
        const selectedDate = data.class_date;

        fetch('/teacher/lesson/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // * Store in sessionStorage to restore after reload
                sessionStorage.setItem('lastSelectedStudent', selectedStudent);
                sessionStorage.setItem('lastSelectedDate', selectedDate);
                location.reload();
            } else if (data.errors) {
                showLessonErrors(Object.values(data.errors).flat());
                lessonSubmitting = false;
            } else {
                showLessonErrors(data.message || 'Please check all required fields');
                lessonSubmitting = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showLessonErrors('Error creating lesson. Please try again.');
            lessonSubmitting = false;
        });
    }
});

/**
 * * RESTORE FORM STATE: Preserves selected student/date after page reload
 */
document.addEventListener('DOMContentLoaded', function() {
    const lastStudent = sessionStorage.getItem('lastSelectedStudent');
    const lastDate = sessionStorage.getItem('lastSelectedDate');
    
    if (lastStudent) {
        const studentSelect = document.querySelector('#newLessonForm select[name="student_id"]');
        if (studentSelect) {
            studentSelect.value = lastStudent;
        }
    }
    
    // * Alpine.js calendar component - needs slight delay to initialize
    if (lastDate) {
        setTimeout(() => {
            const calendarContainer = document.querySelector('.calendar-container');
            if (calendarContainer && calendarContainer._x_dataStack) {
                const alpineData = calendarContainer._x_dataStack[0];
                if (alpineData) {
                    alpineData.selected = lastDate;
                }
            }
        }, 100);
    }
});

/**
 * * DELETE LESSON: Global function called from lesson card onclick
 * ! Confirms before deleting
 */
window.deleteLesson = function(lessonId) {
    if (!confirm('Are you sure you want to delete this lesson? This cannot be undone.')) {
        return;
    }

    clearLessonErrors();
    
    fetch('/lesson/' + lessonId, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else if (data.errors) {
            showLessonErrors(Object.values(data.errors).flat());
        } else {
            showLessonErrors(data.message || 'Error deleting lesson');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showLessonErrors('Error deleting lesson. Please try again.');
    });
};
