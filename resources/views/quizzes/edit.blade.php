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
                    Edit Quiz
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('quizzes.update', $quiz) }}" method="POST" id="quizForm">
                        @csrf
                        @method('PUT')

                        <!-- Quiz Metadata -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $quiz->title) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">{{ old('description', $quiz->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="passing_score" class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                                <input type="number" name="passing_score" id="passing_score" value="{{ old('passing_score', $quiz->passing_score) }}" min="0" max="100" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('passing_score')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_reward_minutes" class="block text-sm font-medium text-gray-700 mb-2">Time Reward (minutes) *</label>
                                <input type="number" name="time_reward_minutes" id="time_reward_minutes" value="{{ old('time_reward_minutes', $quiz->time_reward_minutes) }}" min="1" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('time_reward_minutes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                {{-- Hidden input ensures we always get a value (0 if unchecked, 1 if checked) --}}
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $quiz->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-yellow-600 shadow-sm focus:border-yellow-500 focus:ring focus:ring-yellow-500 focus:ring-offset-0">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        {{-- Device Assignment --}}
                        @if(isset($devices) && $devices->count() > 0)
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Devices</label>
                                <p class="text-sm text-gray-500 mb-3">Update which devices are allowed to take this quiz.</p>
                                <div class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                                    @foreach($devices as $device)
                                        <div class="flex items-center mb-2">
                                            @php
                                                $selectedDevices = old('devices', $assignedDeviceIds ?? []);
                                            @endphp
                                            <input type="checkbox" name="devices[]" id="device_{{ $device->id }}" value="{{ $device->id }}"
                                                {{ in_array($device->id, $selectedDevices) ? 'checked' : '' }}
                                                class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                            <label for="device_{{ $device->id }}" class="ml-2 block text-sm text-gray-700">
                                                {{ $device->name }} ({{ $device->mac_address }})
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('devices')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('devices.*')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                <p class="text-sm text-yellow-800">
                                    No devices available. Please add devices before assigning quizzes.
                                </p>
                            </div>
                        @endif

                        <!-- Questions Section -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Questions</h3>
                                <button type="button" onclick="addQuestion()" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #10B981;">
                                    + Add Question
                                </button>
                            </div>

                            <div id="questionsContainer">
                                <!-- Questions will be loaded here -->
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
                                Update Quiz
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
         * Quiz Edit Form - Dynamic Question Builder with Data Loading
         * 
         * This JavaScript handles editing existing quizzes. It's similar to create.blade.php,
         * but also loads existing quiz data and pre-fills the form.
         * 
         * Key Features:
         * - Loads existing questions from database
         * - Pre-fills form fields with current quiz data
         * - Allows adding/removing questions
         * - Handles all question types correctly
         * - Form cleanup before submission
         * 
         * Data Loading Process:
         * 1. Get quiz questions from database (stored as JSON)
         * 2. Extract questions array (handles nested structure)
         * 3. Loop through questions and add them to form
         * 4. Pre-select question types and fill in answers
         */
        (function() {
            // Prevent redeclaration: Check if script already ran
            if (window.quizQuestionIndex !== undefined) {
                return; // Script already loaded
            }
            
            // Global question counter (starts at 0, increments for each new question)
            window.quizQuestionIndex = 0;
            
            // Step 1: Get questions from database (passed from controller)
            // Questions are stored as JSON in database: {questions: [{id: 1, question: "...", ...}]}
            @php
                $questionsData = isset($quiz->questions) ? $quiz->questions : [];
            @endphp
            const quizQuestions = {!! json_encode($questionsData) !!};
            
            // Step 2: Extract questions array from JSON structure
            // Handles two possible structures:
            // - Nested: {questions: [{id: 1, ...}, {id: 2, ...}]}
            // - Direct: [{id: 1, ...}, {id: 2, ...}]
            let existingQuestions = [];
            if (quizQuestions) {
                if (quizQuestions.questions && Array.isArray(quizQuestions.questions)) {
                    // Structure: {questions: [...]} - extract inner array
                    existingQuestions = quizQuestions.questions;
                } else if (Array.isArray(quizQuestions)) {
                    // Structure: [...] - use directly
                    existingQuestions = quizQuestions;
                }
            }
            
            // Debug: Log to console to verify questions are loaded
            console.log('Quiz questions raw data:', quizQuestions);
            console.log('Quiz questions extracted:', existingQuestions);
            console.log('Number of questions:', existingQuestions.length);
            
            // Helper function to escape HTML in template strings
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            window.addQuestion = function(questionData = null) {
            try {
                const container = document.getElementById('questionsContainer');
                if (!container) {
                    console.error('questionsContainer not found! Cannot add question.');
                    alert('Error: Questions container not found. Please refresh the page.');
                    return;
                }
                
                console.log('Adding question with data:', questionData);
                
                const questionDiv = document.createElement('div');
                questionDiv.className = 'mb-6 p-4 border border-gray-300 rounded-lg';
                questionDiv.id = `question-${window.quizQuestionIndex}`;
                
                // Safely extract question data
                const type = (questionData && questionData.type) ? questionData.type : 'multiple_choice';
                const questionText = (questionData && questionData.question) ? String(questionData.question) : '';
                let options = ['', '', '', ''];
                if (questionData && questionData.options && Array.isArray(questionData.options)) {
                    options = questionData.options.map(opt => String(opt || ''));
                }
                const correctAnswer = (questionData && questionData.correct_answer) ? String(questionData.correct_answer) : '';
                
                console.log('Question data extracted:', { type, questionText, options, correctAnswer });
                
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">${escapeHtml(questionText)}</textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Question Type *</label>
                    <select name="questions[${window.quizQuestionIndex}][type]" required onchange="window.updateQuestionType(${window.quizQuestionIndex}, this.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <option value="multiple_choice" ${type === 'multiple_choice' ? 'selected' : ''}>Multiple Choice</option>
                        <option value="fill_blank" ${type === 'fill_blank' ? 'selected' : ''}>Fill in the Blank</option>
                        <option value="true_false" ${type === 'true_false' ? 'selected' : ''}>True/False</option>
                    </select>
                </div>
                
                <div id="options-${window.quizQuestionIndex}" class="mb-4" style="display: ${type === 'fill_blank' ? 'none' : 'block'};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Options *</label>
                    <div class="space-y-2">
                        ${[0, 1, 2, 3].map(i => `
                            <input type="text" name="questions[${window.quizQuestionIndex}][options][]" 
                                value="${escapeHtml(options[i] || '')}" 
                                placeholder="Option ${String.fromCharCode(65 + i)}" 
                                ${type !== 'fill_blank' && (type === 'true_false' ? i < 2 : true) ? 'required' : ''}
                                ${type === 'fill_blank' ? 'disabled' : ''}
                                ${type === 'true_false' && i >= 2 ? 'style="display: none;"' : ''}
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        `).join('')}
                    </div>
                </div>
                
                <div id="correct-answer-${window.quizQuestionIndex}" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Correct Answer *</label>
                    <div id="correct-answer-input-${window.quizQuestionIndex}">
                        ${getCorrectAnswerInput(window.quizQuestionIndex, type, correctAnswer, options)}
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
            } catch (error) {
                console.error('Error adding question:', error);
                alert('Error adding question: ' + error.message);
            }
        }

            function getCorrectAnswerInput(index, type, correctAnswer, options) {
            if (type === 'fill_blank') {
                return `
                    <input type="text" name="questions[${index}][correct_answer]" value="${escapeHtml(correctAnswer || '')}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
                        placeholder="Enter correct answer">
                `;
            } else if (type === 'true_false') {
                return `
                    <select name="questions[${index}][correct_answer]" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <option value="">Select correct answer</option>
                        <option value="True" ${correctAnswer === 'True' ? 'selected' : ''}>True</option>
                        <option value="False" ${correctAnswer === 'False' ? 'selected' : ''}>False</option>
                    </select>
                `;
            } else {
                // For multiple choice, correct answer is stored as the option value (e.g., "4")
                // We need to find which option index matches the correct answer
                let selectedIndex = -1;
                if (correctAnswer && options) {
                    selectedIndex = options.findIndex(opt => 
                        opt && opt.toString().toLowerCase() === correctAnswer.toString().toLowerCase()
                    );
                }
                
                return `
                    <select id="correct-answer-select-${index}" required onchange="updateCorrectAnswerFromSelect(${index})"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <option value="">Select correct answer</option>
                        ${['a', 'b', 'c', 'd'].map((letter, i) => `
                            <option value="${i}" ${selectedIndex === i ? 'selected' : ''}>
                                ${String.fromCharCode(65 + i)}${options[i] ? ': ' + options[i] : ''}
                            </option>
                        `).join('')}
                    </select>
                    <input type="hidden" id="correct-answer-value-${index}" name="questions[${index}][correct_answer]" value="${escapeHtml(correctAnswer || '')}" required>
                `;
            }
        }

            window.removeQuestion = function(index) {
                const questionDiv = document.getElementById(`question-${index}`);
                if (questionDiv) {
                    questionDiv.remove();
                    window.updateQuestionNumbers();
                }
            };

            window.updateQuestionType = function(index, type) {
                const optionsDiv = document.getElementById(`options-${index}`);
                const correctAnswerDiv = document.getElementById(`correct-answer-input-${index}`);
                
                if (!optionsDiv || !correctAnswerDiv) {
                    console.error('Could not find options or correct answer div for question', index);
                    return;
                }
                
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
                            input.style.display = 'block';
                            input.value = i === 0 ? 'True' : 'False';
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
                            ${['a', 'b', 'c', 'd'].map((letter, i) => `
                                <option value="${i}">${String.fromCharCode(65 + i)}</option>
                            `).join('')}
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

            // Clean up form data before submission for fill_blank questions
            const quizForm = document.getElementById('quizForm');
            if (quizForm) {
                quizForm.addEventListener('submit', function(e) {
                    // Remove options for fill_blank questions before submission
                    const allQuestions = document.querySelectorAll('[id^="question-"]');
                    allQuestions.forEach(questionDiv => {
                        const typeSelect = questionDiv.querySelector('select[name*="[type]"]');
                        if (typeSelect && typeSelect.value === 'fill_blank') {
                            // Remove options inputs entirely for fill_blank questions
                            const optionsInputs = questionDiv.querySelectorAll('input[name*="[options]"]');
                            optionsInputs.forEach(input => {
                                input.remove(); // Remove from DOM so they're not submitted
                            });
                        }
                    });
                });
            }

            // Load existing questions on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM loaded, existing questions:', existingQuestions);
                    
                    if (existingQuestions && existingQuestions.length > 0) {
                        console.log('Loading', existingQuestions.length, 'existing questions');
                        existingQuestions.forEach((question, index) => {
                            console.log('Loading question', index + 1, question);
                            window.addQuestion(question);
                        });
                    } else {
                        console.log('No existing questions, adding empty question');
                        window.addQuestion();
                    }
                    
                    // Ensure questionsContainer exists
                    const container = document.getElementById('questionsContainer');
                    if (!container) {
                        console.error('questionsContainer not found!');
                    } else {
                        console.log('questionsContainer found, ready to add questions');
                    }
                });
            } else {
                // DOM already loaded
                console.log('DOM already loaded, existing questions:', existingQuestions);
                
                if (existingQuestions && existingQuestions.length > 0) {
                    console.log('Loading', existingQuestions.length, 'existing questions');
                    existingQuestions.forEach((question, index) => {
                        console.log('Loading question', index + 1, question);
                        window.addQuestion(question);
                    });
                } else {
                    console.log('No existing questions, adding empty question');
                    window.addQuestion();
                }
            }
        })();
    </script>
    @endpush
</x-app-layout>

