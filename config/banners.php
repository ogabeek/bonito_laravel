<?php

return [
    // Teacher dashboard: development warning (dismiss 5x to hide permanently)
    'teacher_info' => [
        'enabled' => true,
        'type' => 'warning',
        'message' => '🚧 This platform is under active development. If you notice any bugs or have suggestions, please let us know!',
    ],

    // Teacher how-to guide (dismiss 5x to hide permanently)
    'teacher_howto' => [
        'enabled' => true,
        'type' => 'tip',
        'title' => 'How to use this page',
        'message' => '<strong>Mark a lesson:</strong> Use the form below to select a student, lesson date, mark attendance (Done/C - Canceled /CT - Canceled by the Teacher /Absent (when student didn\'t appear without any notifications (we need to inform parents in this case), add topic and homework.<br><strong>Quick tip:</strong> Click on any student\'s name in the list above to jump to their personal page and add any additional info',
    ],

    // Student welcome: shown if < 5 lessons
    'student_welcome' => [
        'enabled' => true,
        'type' => 'success',
        'title' => 'Welcome! 🎉',
        'message' => 'This is your personal learning dashboard. After each lesson, your teacher will log what you covered, homework assignments, and track your progress. Check back after your first class!',
    ],

    // Student announcement: set enabled=true and message to show
    'student_info' => [
        'enabled' => false,
        'type' => 'info',
        'message' => '',
    ],
];
