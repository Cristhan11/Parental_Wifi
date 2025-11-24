{{-- Parent Dashboard: Quiz Creation Form --}}
{{-- Dynamic form that allows parents to add multiple questions with different types --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('quizzes.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    Create New Quiz
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('quizzes.store') }}" method="POST" id="quizForm">
                        @csrf

                        {{-- Quiz Metadata: Title, Description, Passing Score, Time Reward --}}
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="passing_score" class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                                <input type="number" name="passing_score" id="passing_score" value="{{ old('passing_score', 70) }}" min="0" max="100" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('passing_score')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_reward_minutes" class="block text-sm font-medium text-gray-700 mb-2">Time Reward (minutes) *</label>
                                <input type="number" name="time_reward_minutes" id="time_reward_minutes" value="{{ old('time_reward_minutes', 15) }}" min="1" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('time_reward_minutes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Questions Section: Dynamic question builder --}}
                        {{-- Questions are added/removed dynamically using JavaScript --}}
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Questions</h3>
                                <button type="button" onclick="addQuestion()" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #10B981;">
                                    + Add Question
                                </button>
                            </div>

                            <div id="questionsContainer">
                                {{-- Questions will be added here dynamically via JavaScript --}}
                            </div>

                            @error('questions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('quizzes.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #FFDE15; color: #000000;">
                                Create Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        /**
         * Quiz Creation Form - Dynamic Question Builder
         * 
         * This JavaScript handles the dynamic form where parents can add/remove questions.
         * It uses an IIFE (Immediately Invoked Function Expression) to prevent variable
         * conflicts and ensure the script only runs once.
         * 
         * Key Features:
         * - Add questions dynamically (no page reload)
         * - Remove questions
         * - Change question type (shows/hides options based on type)
         * - Auto-scroll to new questions
         * - Form cleanup before submission (removes options for fill_blank questions)
         * 
         * Why IIFE? Prevents "variable already declared" errors if script loads multiple times.
         * Why window.quizQuestionIndex? Makes it globally accessible for onclick handlers.
         */
        (function() {
            // Prevent redeclaration: Check if script already ran
            // If window.quizQuestionIndex exists, script already loaded, so exit
            if (window.quizQuestionIndex !== undefined) {
                return; // Script already loaded
            }
            
            // Global question counter (starts at 0, increments for each new question)
            // Stored on window object so onclick handlers can access it
            window.quizQuestionIndex = 0;

            /**
             * Add a new question to the form dynamically.
             * 
             * This function:
             * 1. Creates a new question div with all form fields
             * 2. Adds it to the questions container
             * 3. Scrolls to the new question
             * 4. Focuses on the question text input
             * 5. Increments the question counter
             * 
             * Question structure includes:
             * - Question text (textarea)
             * - Question type (select dropdown)
             * - Options (4 inputs for multiple choice, hidden for fill_blank)
             * - Correct answer (select or text input, depends on type)
             */
            window.addQuestion = function() {
                const container = document.getElementById('questionsContainer');
                if (!container) {
                    console.error('Questions container not found');
                    return;
                }
                
                const questionDiv = document.createElement('div');
                questionDiv.className = 'mb-6 p-4 border border-gray-300 rounded-lg';
                questionDiv.id = `question-${window.quizQuestionIndex}`;
                
                questionDiv.innerHTML = `
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-gray-900">Question ${window.quizQuestionIndex + 1}</h4>
                        <button type="button" onclick="window.removeQuestion(${window.quizQuestionIndex})" class="px-3 py-1 rounded text-white text-sm hover:opacity-90" style="background-color: #EF4444;">
                            Delete
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Question Text *</label>
                        <textarea name="questions[${window.quizQuestionIndex}][question]" required rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Question Type *</label>
                        <select name="questions[${window.quizQuestionIndex}][type]" required onchange="window.updateQuestionType(${window.quizQuestionIndex}, this.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="fill_blank">Fill in the Blank</option>
                            <option value="true_false">True/False</option>
                        </select>
                    </div>
                    
                    <div id="options-${window.quizQuestionIndex}" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Options *</label>
                        <div class="space-y-2">
                            <input type="text" name="questions[${window.quizQuestionIndex}][options][]" placeholder="Option A" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <input type="text" name="questions[${window.quizQuestionIndex}][options][]" placeholder="Option B" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <input type="text" name="questions[${window.quizQuestionIndex}][options][]" placeholder="Option C" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <input type="text" name="questions[${window.quizQuestionIndex}][options][]" placeholder="Option D" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                    
                    <div id="correct-answer-${window.quizQuestionIndex}" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Correct Answer *</label>
                        <div id="correct-answer-input-${window.quizQuestionIndex}">
                            <select id="correct-answer-select-${window.quizQuestionIndex}" required onchange="window.updateCorrectAnswerFromSelect(${window.quizQuestionIndex})"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="">Select correct answer</option>
                                <option value="0">A</option>
                                <option value="1">B</option>
                                <option value="2">C</option>
                                <option value="3">D</option>
                            </select>
                            <input type="hidden" id="correct-answer-value-${window.quizQuestionIndex}" name="questions[${window.quizQuestionIndex}][correct_answer]" required>
                        </div>
                    </div>
                `;
                
                container.appendChild(questionDiv);
                
                // Scroll to the newly added question and focus on the question text input
                questionDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                // Focus on the question text textarea after a short delay to ensure it's rendered
                setTimeout(() => {
                    const textarea = questionDiv.querySelector('textarea[name*="[question]"]');
                    if (textarea) {
                        textarea.focus();
                        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
                
                window.quizQuestionIndex++;
            };

            window.removeQuestion = function(index) {
                const questionDiv = document.getElementById(`question-${index}`);
                if (questionDiv) {
                    questionDiv.remove();
                    window.updateQuestionNumbers();
                }
            };

            /**
             * Update question type and show/hide appropriate fields.
             * 
             * When parent changes question type dropdown, this function:
             * 1. Shows/hides options based on type
             * 2. Changes correct answer input (select vs text input)
             * 3. Enables/disables option inputs for form submission
             * 
             * Question Type Handling:
             * - fill_blank: Hide options, show text input for correct answer
             * - true_false: Show 2 options (True/False), show select dropdown
             * - multiple_choice: Show 4 options, show select dropdown with option values
             * 
             * Why disable options for fill_blank? Prevents empty option arrays
             * from being submitted, which would cause validation errors.
             * 
             * @param {number} index - Question index (0, 1, 2, ...)
             * @param {string} type - Question type (multiple_choice, fill_blank, true_false)
             */
            window.updateQuestionType = function(index, type) {
                // Get the divs that contain options and correct answer input
                const optionsDiv = document.getElementById(`options-${index}`);
                const correctAnswerDiv = document.getElementById(`correct-answer-input-${index}`);
                
                // Safety check: Ensure elements exist
                if (!optionsDiv || !correctAnswerDiv) {
                    console.error('Could not find options or correct answer div for question', index);
                    return;
                }
                
                // Handle fill-in-the-blank questions
                if (type === 'fill_blank') {
                    // Hide options and disable them so they're not submitted
                    optionsDiv.style.display = 'none';
                    optionsDiv.querySelectorAll('input').forEach(input => {
                        input.removeAttribute('required');
                        input.disabled = true; // Disable so they're not sent in form submission
                        input.name = ''; // Remove name so they're not submitted
                    });
                    // Replace with text input for fill-in-the-blank
                    correctAnswerDiv.innerHTML = `
                        <input type="text" name="questions[${index}][correct_answer]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
                            placeholder="Enter correct answer">
                    `;
                } else if (type === 'true_false') {
                    // Show options, set True/False values
                    optionsDiv.style.display = 'block';
                    optionsDiv.querySelectorAll('input').forEach((input, i) => {
                        if (i < 2) {
                            input.required = true;
                            input.disabled = false; // Re-enable
                            input.name = `questions[${index}][options][]`; // Restore name
                            input.value = i === 0 ? 'True' : 'False';
                            input.style.display = 'block';
                        } else {
                            input.removeAttribute('required');
                            input.disabled = true; // Disable unused options
                            input.name = ''; // Remove name
                            input.style.display = 'none';
                        }
                    });
                    // Replace with True/False select
                    correctAnswerDiv.innerHTML = `
                        <select name="questions[${index}][correct_answer]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select correct answer</option>
                            <option value="True">True</option>
                            <option value="False">False</option>
                        </select>
                    `;
                } else {
                    // Multiple choice - show all options
                    optionsDiv.style.display = 'block';
                    optionsDiv.querySelectorAll('input').forEach((input, i) => {
                        input.required = true;
                        input.disabled = false; // Re-enable
                        input.name = `questions[${index}][options][]`; // Restore name
                        input.style.display = 'block';
                    });
                    // Replace with multiple choice select
                    correctAnswerDiv.innerHTML = `
                        <select id="correct-answer-select-${index}" required onchange="window.updateCorrectAnswerFromSelect(${index})"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Select correct answer</option>
                            <option value="0">A</option>
                            <option value="1">B</option>
                            <option value="2">C</option>
                            <option value="3">D</option>
                        </select>
                        <input type="hidden" id="correct-answer-value-${index}" name="questions[${index}][correct_answer]" required>
                    `;
                }
            };

            window.updateCorrectAnswerFromSelect = function(index) {
                const select = document.getElementById(`correct-answer-select-${index}`);
                const hiddenInput = document.getElementById(`correct-answer-value-${index}`);
                
                if (!select || !hiddenInput) {
                    console.error('Could not find select or hidden input for question', index);
                    return;
                }
                
                const optionIndex = parseInt(select.value);
                
                if (optionIndex >= 0 && optionIndex < 4) {
                    const optionInputs = document.querySelectorAll(`#options-${index} input`);
                    if (optionInputs[optionIndex] && optionInputs[optionIndex].value) {
                        hiddenInput.value = optionInputs[optionIndex].value;
                    }
                }
            };

            window.updateQuestionNumbers = function() {
                const questions = document.querySelectorAll('[id^="question-"]');
                questions.forEach((question, index) => {
                    const header = question.querySelector('h4');
                    if (header) {
                        header.textContent = `Question ${index + 1}`;
                    }
                });
            };

            /**
             * Form Submission Handler - Clean up data before sending
             * 
             * Before form is submitted, this removes option inputs for fill_blank questions.
             * Why? Fill_blank questions don't need options, but the inputs exist in the DOM.
             * Removing them prevents empty option arrays from being sent, which would
             * cause validation errors.
             * 
             * Process:
             * 1. Listen for form submit event
             * 2. Find all questions
             * 3. For fill_blank questions, remove option inputs
             * 4. Form submits with clean data
             */
            const quizForm = document.getElementById('quizForm');
            if (quizForm) {
                quizForm.addEventListener('submit', function(e) {
                    // Find all question divs
                    const allQuestions = document.querySelectorAll('[id^="question-"]');
                    
                    // Loop through each question
                    allQuestions.forEach(questionDiv => {
                        // Get the question type select dropdown
                        const typeSelect = questionDiv.querySelector('select[name*="[type]"]');
                        
                        // If this is a fill_blank question, remove option inputs
                        if (typeSelect && typeSelect.value === 'fill_blank') {
                            // Find all option inputs for this question
                            const optionsInputs = questionDiv.querySelectorAll('input[name*="[options]"]');
                            
                            // Remove each option input from DOM
                            // This prevents them from being sent in form submission
                            optionsInputs.forEach(input => {
                                input.remove(); // Remove from DOM so they're not submitted
                            });
                        }
                    });
                });
            }

            // Add first question on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    window.addQuestion();
                });
            } else {
                // DOM already loaded
                window.addQuestion();
            }
        })();
    </script>
    @endpush
</x-app-layout>

