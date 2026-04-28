{{-- 
    Parent Dashboard: Excel Import View
    
    This view allows parents to upload an Excel file to import quizzes.
    It includes:
    - Instructions on Excel format
    - Link to download template
    - File upload form
    
    Process:
    1. Parent downloads template Excel file
    2. Parent fills in quiz data in Excel
    3. Parent uploads file via this form
    4. QuizImportService processes the file
    5. Quiz is created in database
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('quizzes.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">Question Bank Import/Export</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Error message display (if import fails) --}}
            @if(session('error'))
                <div class="mb-4 p-4 rounded-lg" style="background-color: #EF4444; color: white;">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Excel Format Instructions Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Excel Format Instructions</h3>
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <p class="text-sm text-gray-700 mb-2">Your Excel file should have the following columns:</p>
                        {{-- 
                            Excel Column Layout:
                            Row 1: Headers (column names)
                            Row 2: Quiz metadata (first row) + First question
                            Row 3+: Additional questions (metadata columns left empty)
                        --}}
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li><strong>Sheet Name:</strong> Questions</li>
                            <li><strong>Columns:</strong> Question ID, Level, Subject, Question Text, Option A-D, Correct Option, Explanation, Status</li>
                            <li><strong>Supported Level:</strong> Elementary, High School, Senior High School</li>
                            <li><strong>Supported Subject:</strong> Math, English, Science</li>
                            <li><strong>Correct Option:</strong> A, B, C, or D</li>
                            <li><strong>Status:</strong> Active or Inactive</li>
                        </ul>
                    </div>
                    {{-- Download template button - generates Excel file with correct format --}}
                    <div class="flex space-x-3">
                        <a href="{{ route('quizzes.template.download') }}" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #3B82F6;">
                            Download Quiz Question Import Template (.xlsx)
                        </a>
                        <a href="{{ route('quizzes.question-bank.export') }}" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #10B981;">
                            Export Question Bank (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            {{-- File Upload Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    {{-- 
                        Excel File Upload Form
                        - enctype="multipart/form-data" is REQUIRED for file uploads
                        - accept=".xlsx,.xls" restricts file picker to Excel files only
                        - Validation handled by ImportQuizRequest
                    --}}
                    <form action="{{ route('quizzes.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">Excel File *</label>
                            {{-- File input: accepts only .xlsx and .xls files --}}
                            <input type="file" name="excel_file" id="excel_file" accept=".xlsx" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('excel_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">Accepted format: .xlsx only (Max: 5MB)</p>
                        </div>

                        <div class="mb-6">
                            <label for="mode" class="block text-sm font-medium text-gray-700 mb-2">Import Mode *</label>
                            <select name="mode" id="mode" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="add_new" {{ old('mode', 'add_new') === 'add_new' ? 'selected' : '' }}>Add New</option>
                                <option value="update_existing" {{ old('mode') === 'update_existing' ? 'selected' : '' }}>Update Existing (uses Question ID)</option>
                            </select>
                            @error('mode')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('quizzes.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #FFDE15; color: #000000;">
                                Import Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

