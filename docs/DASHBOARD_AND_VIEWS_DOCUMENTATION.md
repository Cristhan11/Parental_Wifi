# DILG Dashboard and Views Documentation

## Table of Contents
1. [Dashboard Overview](#dashboard-overview)
2. [Layout Structure](#layout-structure)
3. [Design System](#design-system)
4. [User Dashboard](#user-dashboard)
5. [Admin Dashboard](#admin-dashboard)
6. [Viewer Dashboard](#viewer-dashboard)
7. [Other Views Reference](#other-views-reference)
8. [Technical Stack](#technical-stack)
9. [Frontend Code Samples](#frontend-code-samples)

---

## Dashboard Overview

The DILG (Department of the Interior and Local Government) application uses three distinct dashboard interfaces:

- **User Dashboard** - For job applicants
- **Admin Dashboard** - For system administrators
- **Viewer Dashboard** - For exam viewers/managers

All dashboards follow a consistent design language with a collapsible sidebar navigation and card-based content layout.

---

## Layout Structure

### Base Layout Files

The application uses Blade template layouts located in `resources/views/layout/`:

#### 1. `layout/app.blade.php` - User Layout
- **Purpose**: Main layout for authenticated users (applicants)
- **Features**:
  - Mobile-responsive with mobile sidebar toggle
  - Collapsible desktop sidebar
  - Chatbot integration
  - Montserrat font family
  - Light blue background (`#F3F8FF`)

#### 2. `layout/admin.blade.php` - Admin Layout
- **Purpose**: Layout for admin users
- **Features**:
  - Fixed sidebar (admin-specific navigation)
  - Similar styling to user layout
  - No mobile sidebar (desktop-focused)
  - Includes activity log and admin-specific features

#### 3. `layout/viewer.blade.php` - Viewer Layout
- **Purpose**: Layout for exam viewers
- **Features**:
  - Minimal navigation (only Exam Management)
  - Similar structure to admin layout
  - Exam-focused interface

### Sidebar Components

All layouts include sidebar partials in `resources/views/partials/`:

- **`partials/sidebar.blade.php`** - User sidebar navigation
- **`partials/sidebar_admin.blade.php`** - Admin sidebar navigation
- **`partials/sidebar_viewer.blade.php`** - Viewer sidebar navigation
- **`partials/mobile-sidebar.blade.php`** - Mobile responsive sidebar

**Sidebar Features**:
- Collapsible (toggles between 64px and 288px width)
- State persisted in localStorage
- Smooth transitions (0.3s ease)
- Logo with text overlay
- Active route highlighting
- Feather icons for navigation items

---

## Design System

### Color Palette

| Color | Hex Code | Usage |
|-------|----------|-------|
| **Primary Blue** | `#002C76` | Primary brand color, headers, active states, borders |
| **Secondary Red** | `#C9282D` | Accent color, logout buttons, important actions |
| **Background** | `#F3F8FF` | Main page background (light blue) |
| **White** | `#FFFFFF` | Card backgrounds, sidebar |
| **Dark Blue** | `#0D2B70` | Alternate headers, table headers |
| **Red Action** | `#DC2626` / `#EF4444` | Danger actions, error states |
| **Green** | `#16A34A` | Success states, progress indicators |

### Typography

- **Font Family**: Montserrat (Google Fonts)
  - Weights: 400 (normal), 600 (semibold), 700 (bold), 800 (extrabold)
- **Font Sizes**:
  - Headings: `text-2xl` to `text-4xl` (1.5rem - 2.25rem)
  - Body: `text-sm` to `text-base` (0.875rem - 1rem)
  - Small text: `text-xs` (0.75rem)

### Spacing System

- Uses Tailwind CSS spacing scale
- Common padding: `p-4`, `p-6`, `p-8` (1rem, 1.5rem, 2rem)
- Common gaps: `gap-4`, `gap-6` (1rem, 1.5rem)
- Section spacing: `space-y-10` (2.5rem vertical spacing)

### Component Patterns

#### Cards
```html
<div class="rounded-xl bg-white border-4 border-[#002C76] p-8">
  <!-- Card content -->
</div>
```

#### Buttons
- **Primary Action**: `bg-[#002C76] text-white rounded-full px-5 py-2`
- **Secondary Action**: `bg-green-600 text-white rounded-full px-5 py-2`
- **Danger Action**: `bg-red-600 text-white rounded-full px-5 py-2`

#### Grid Layout
- Common: `grid grid-cols-12 gap-6` (12-column responsive grid)
- Responsive: `col-span-12 sm:col-span-7` (full width on mobile, 7 columns on desktop)

---

## User Dashboard

**File**: `resources/views/dashboard_user/dashboard_user.blade.php`

### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│ Welcome Section (User Name)                             │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────────┐  ┌─────────────────────┐       │
│ │ My Job Applications │  │ Deadline of Apps    │       │
│ │ (7 cols)            │  │ (5 cols)            │       │
│ └─────────────────────┘  └─────────────────────┘       │
│ ┌─────────────────────┐  ┌─────────────────────┐       │
│ │ Job Vacancies       │  │ Personal Data Sheet │       │
│ │ (7 cols)            │  │ (5 cols)            │       │
│ └─────────────────────┘  └─────────────────────┘       │
└─────────────────────────────────────────────────────────┘
```

### Complete Code Sample

```blade
@extends('layout.app')
@section('title', 'DILG - DASHBOARD')

@section('content')

<style>
    .success-container {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 50;
    }
</style>

<main class="flex-1 w-full space-y-10 bg-[#F3F8FF] font-sans text-gray-900 overflow-x-hidden pl-10" style="margin-top: 0;">

    <!-- Welcome Section -->
    <section class="text-center sm:text-left">
        <div class="text-xl font-normal mb-1 font-montserrat">Welcome,</div>
        <h1 class="font-extrabold text-2xl sm:text-3xl tracking-tight font-montserrat">{{ Auth::user()->name }}</h1>
    </section>

    <section class="grid grid-cols-12 gap-6 w-full">

        <!-- My Job Applications Card -->
        <article class="col-span-12 sm:col-span-7 rounded-xl bg-white text-[#002C76] border-4 border-[#002C76] p-8 flex flex-col gap-4">
            <h2 class="text-base sm:text-2xl font-extrabold flex items-center gap-3 font-montserrat">
                <i class="w-5 h-5" data-feather="clipboard"></i> MY JOB APPLICATIONS
            </h2>
            <div class="text-sm sm:text-base font-normal leading-relaxed font-montserrat space-y-1">
                @forelse($applications->filter(fn($app) => strtolower($app->status) !== 'closed') as $application)
                    <p>{{ $application->vacancy->position_title ?? 'N/A' }}</p>
                @empty
                    <p>You have not applied to any vacancies yet.</p>
                @endforelse
            </div>
            <button onclick="window.location.href='{{ route('my_applications') }}'"
                class="use-loader mt-3 inline-flex items-center font-montserrat gap-2 rounded-full bg-green-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                <i data-feather="eye" class="w-4 h-4"></i> View Your Job Applications
            </button>
        </article>

        <!-- Deadline of Applications Card -->
        <article class="col-span-12 sm:col-span-5 bg-white border-4 border-[#002C76] rounded-xl p-6 flex flex-col gap-4">
            <h2 class="text-base sm:text-xl font-extrabold flex items-center gap-3 font-montserrat text-[#C9282D]">
                <i class="w-5 h-5" data-feather="check-square"></i> DEADLINE OF APPLICATIONS
            </h2>
            @if ($applicationsWithDeadlines->isNotEmpty())
                @foreach ($applicationsWithDeadlines as $app)
                    @php
                        $deadline = Carbon::parse($app->deadline_date . ' ' . $app->deadline_time);
                        $isPastDeadline = now()->greaterThan($deadline);
                    @endphp
                    <div>
                        <p class="text-sm sm:text-base font-bold font-montserrat">
                            {{ $deadline->format('F d, Y') }} | {{ $deadline->format('h:i A') }}
                        </p>
                        <p class="uppercase text-xs sm:text-sm tracking-wide font-montserrat">
                            {{ $app->vacancy->position_title }}
                            @if ($isPastDeadline)
                                — <span class="text-red-700 font-semibold">Past Deadline</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-700 font-montserrat">You haven't applied to any vacancies with deadlines yet.</p>
            @endif
        </article>

        <!-- Job Vacancies Card -->
        <article class="col-span-12 sm:col-span-7 rounded-xl bg-white border-4 border-[#002C76] p-8 flex flex-col text-[#002C76] min-h-[360px]">
            <h2 class="text-base sm:text-2xl font-extrabold flex items-center gap-3 font-montserrat mb-2">
                <i class="w-5 h-5" data-feather="box"></i> JOB VACANCIES
            </h2>
            <div class="flex-1 text-sm sm:text-base font-normal leading-relaxed space-y-1 font-montserrat">
                @forelse ($vacancies as $vacancy)
                    <p>{{ $vacancy->position_title }}</p>
                @empty
                    <p>No open vacancies available at the moment.</p>
                @endforelse
            </div>
            <div class="mt-5">
                <button onclick="window.location.href='{{ route('job_vacancy') }}'"
                    class="use-loader inline-flex items-center gap-2 rounded-full font-montserrat bg-red-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                    <i data-feather="search" class="w-4 h-4"></i> Browse All Job Vacancies
                </button>
            </div>
        </article>

        <!-- Personal Data Sheet Card -->
        <article class="col-span-12 sm:col-span-5 rounded-xl bg-white border-4 border-[#002C76] p-8 flex flex-col gap-4">
            <h2 class="text-base sm:text-3xl font-extrabold flex items-center gap-3 font-montserrat text-[#002C76]">
                <i class="w-5 h-5" data-feather="file"></i> PERSONAL DATA SHEET
            </h2>

            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 h-2 rounded-full">
                <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $pdsProgress }}%"></div>
            </div>
            <p class="text-sm text-gray-600 font-montserrat">{{ $pdsProgress }}% PDS Completed</p>

            <!-- Status Info -->
            <div class="text-sm font-montserrat space-y-3 bg-blue-50 p-4 rounded-lg text-[#002C76]">
                <p>
                    <strong>Status:</strong>
                    @if ($pdsProgress === 100)
                        <span class="text-green-600">Completed</span>
                    @elseif ($pdsProgress >= 50)
                        <span class="text-yellow-600">In Progress</span>
                    @else
                        <span class="text-red-600">Incomplete</span>
                    @endif
                </p>
                <p><strong>Last Updated:</strong> {{ \Carbon\Carbon::parse(Auth::user()->updated_at)->format('F j, Y') }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 mt-2">
                <button type="button" onclick="window.location.href='{{ route('display_c1') }}'"
                    class="use-loader inline-flex font-montserrat items-center gap-2 rounded-full bg-red-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                    <i data-feather="edit-2" class="w-4 h-4"></i> Edit My Personal Data Sheet
                </button>
                <a href="{{ route('export.pds') }}" target="_blank"
                    class="use-loader inline-flex font-montserrat items-center gap-2 rounded-full bg-blue-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                    <i data-feather="download" class="w-4 h-4"></i> Export PDS
                </a>
            </div>
        </article>

    </section>

    @include('partials.loader')

</main>
@endsection

@section('scripts')
<script>
    feather.replace();
</script>
@endsection
```

### Sections

1. **Welcome Section**
   - Displays user's name
   - Large, bold heading
   - Left-aligned on desktop, centered on mobile

2. **My Job Applications Card** (col-span-12 sm:col-span-7)
   - Lists active job applications
   - Border: 4px solid `#002C76`
   - Action button: "View Your Job Applications" (green)
   - Icon: Clipboard (Feather)

3. **Deadline of Applications Card** (col-span-12 sm:col-span-5)
   - Shows top 3 upcoming deadlines
   - Red accent color (`#C9282D`)
   - Displays date, time, and position title
   - Highlights past deadlines

4. **Job Vacancies Card** (col-span-12 sm:col-span-7)
   - Lists available job vacancies
   - Action button: "Browse All Job Vacancies" (red)
   - Icon: Box (Feather)

5. **Personal Data Sheet Card** (col-span-12 sm:col-span-5)
   - Progress bar showing PDS completion percentage
   - Status indicator (Completed/In Progress/Incomplete)
   - Checklist for required forms (PDS, WES)
   - Action buttons: "Edit PDS" (red), "Export PDS" (blue)

### Key Features

- **Responsive Grid**: 12-column grid, adapts to mobile (stacks vertically)
- **Card Design**: White background, 4px colored borders, rounded corners (`rounded-xl`)
- **Icons**: Feather Icons for visual cues
- **Interactive Elements**: Hover states, transitions, loader integration

---

## Admin Dashboard

**File**: `resources/views/admin/dashboard_admin.blade.php`

### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│ Welcome Back Section (Admin Name)                       │
├─────────────────────────────────────────────────────────┤
│ Stats Summary (4 Stats in Row)                          │
│ [Open Vacancies] [Reviewed Apps] [Exams] [Users]       │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────────┐  ┌─────────────────────┐       │
│ │ Job Vacancies       │  │ Exam Management     │       │
│ │ (Dark Blue Card)    │  │ (Dark Blue Card)    │       │
│ └─────────────────────┘  └─────────────────────┘       │
│ ┌─────────────────────┐  ┌─────────────────────┐       │
│ │ Monthly Applicants  │  │ Reviewed Applicants │       │
│ │ (Chart)             │  │ (List)              │       │
│ └─────────────────────┘  └─────────────────────┘       │
└─────────────────────────────────────────────────────────┘
```

### Sections

1. **Welcome Back Section**
   - Admin name display
   - Similar to user dashboard

2. **Stats Summary Bar**
   - 4 clickable stat cards in a horizontal row
   - Dividers between cards
   - Each card shows:
     - Icon in colored circle
     - Count number (large, bold)
     - Label text
   - Hover effect: light blue background
   - Links to respective management pages

3. **Job Vacancies Card** (flex-1, dark blue background)
   - Dark blue theme (`bg-blue-900`)
   - Lists open vacancies
   - Action button: "Edit Job Vacancies" (red)

4. **Exam Management Card** (flex-1, dark blue background)
   - Lists upcoming exams with:
     - Position title
     - Exam date
     - Exam time
   - Action button: "Manage Exam" (red)

5. **Monthly Applicants Chart** (col-span-1 xl:col-span-1)
   - Chart.js bar chart
   - Year selector dropdown
   - Shows monthly applicant statistics
   - Responsive canvas (height: 230px)

6. **Reviewed Applicants List** (col-span-1 xl:col-span-1)
   - Scrollable list of reviewed applicants
   - Shows applicant names
   - Action button: "View Applicants" (red)

### Key Features

- **Data Visualization**: Chart.js integration for statistics
- **Dark Cards**: Blue-900 background for prominent sections
- **Statistics Overview**: Quick stats at the top for fast access
- **Responsive Layout**: Adapts to different screen sizes

### Complete Code Sample

```blade
@extends('layout.admin')
@section('title', 'DILG - Dashboard Admin')

@section('content')
<main class="space-y-6">

    <!-- Welcome Back -->
    <section>
        <p class="text-xl font-normal text-black font-montserrat">Welcome back,</p>
        <h1 class="text-3xl font-extrabold text-black uppercase font-montserrat">
            {{ auth('admin')->user()->name ?? 'Admin' }}
        </h1>
    </section>

    <!-- Stats Summary -->
    <section class="border border-blue-700 rounded-2xl max-w-full flex divide-x divide-blue-700 bg-white select-none"
        style="box-shadow: 0 3px 6px rgb(29 78 216 / 0.24);">
        @php
            $stats = [
                ['url' => '/admin/vacancies_management', 'icon' => 'briefcase', 'label' => 'Open Vacancies', 'count' => $openVacancyCount],
                ['url' => '/admin/applications_list', 'icon' => 'folder-closed', 'label' => 'Reviewed Applications', 'count' => $reviewedApplicationsCount],
                ['url' => '/admin/exam_management', 'icon' => 'file-signature', 'label' => 'Upcoming Exams', 'count' => $upcomingExamsCount],
                ['url' => '/admin/admin_account_management', 'icon' => 'user', 'label' => 'System Users', 'count' => $systemUsersCount],
            ];
        @endphp

        @foreach ($stats as $stat)
        <a href="{{ $stat['url'] }}" class="flex-1 block use-loader">
            <div class="flex flex-col items-center p-4 space-y-1 hover:bg-blue-50">
                <div class="flex justify-center items-center rounded-full bg-blue-300 w-10 h-10">
                    <i class="fa-solid fa-{{ $stat['icon'] }} text-blue-700 text-lg"></i>
                </div>
                <span class="font-extrabold text-xl font-montserrat">{{ $stat['count'] }}</span>
                <span class="text-sm font-semibold text-gray-400 font-montserrat">{{ $stat['label'] }}</span>
            </div>
        </a>
        @endforeach
    </section>

    <!-- Job Vacancies + Exam Management Row -->
    <section class="flex flex-row gap-6 max-w-full">
        <!-- Job Vacancies Card -->
        <div class="flex-1 bg-blue-900 rounded-2xl p-6 text-white shadow-lg flex flex-col">
            <h2 class="font-extrabold text-2xl flex items-center gap-3 mb-5 font-montserrat">
                <i class="fa-solid fa-clipboard"></i> JOB VACANCIES
            </h2>
            <div class="text-lg font-light space-y-1 mb-6 max-w-md font-montserrat">
                @forelse ($openVacancies as $vacancy)
                    <p>{{ $vacancy->position_title }}</p>
                @empty
                    <p class="italic text-sm text-gray-300">No open vacancies.</p>
                @endforelse
            </div>
            <div class="mt-auto flex justify-end pt-6">
                <button onclick="window.location.href='/admin/vacancies_management'"
                    class="use-loader bg-red-700 hover:bg-red-800 transition font-montserrat font-semibold rounded-lg py-3 px-6 flex items-center space-x-2 shadow-md shadow-red-900/50">
                    <i class="fa-regular fa-eye"></i>
                    <span>Edit Job Vacancies</span>
                </button>
            </div>
        </div>

        <!-- Exam Management Card -->
        <div class="flex-1 bg-blue-900 rounded-2xl p-6 text-white shadow-lg flex flex-col">
            <h2 class="font-extrabold text-2xl flex items-center gap-3 mb-5 font-montserrat">
                <i class="fa-solid fa-file-pen"></i> EXAM MANAGEMENT
            </h2>
            <div class="flex flex-col text-white text-sm mr-4">
                <p class="font-bold uppercase mb-1 font-montserrat">Upcoming Exams</p>
                <ul class="space-y-3">
                    @forelse ($upcomingExams as $exam)
                        <li class="grid grid-cols-[minmax(300px,_auto)_160px_80px] items-center">
                            <div class="flex items-center">
                                <i class="fa-solid fa-paperclip"></i>
                                <strong class="ml-2 font-montserrat">
                                    {{ $exam->vacancy->position_title ?? 'Unknown Position' }}
                                </strong>
                            </div>
                            <div class="flex items-center">
                                <i class="fa-solid fa-calendar"></i>
                                <span class="ml-2 font-montserrat">
                                    {{ \Carbon\Carbon::parse($exam->date)->format('F j, Y') }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <i class="fa-solid fa-clock"></i>
                                <span class="ml-2 font-montserrat">
                                    {{ \Carbon\Carbon::parse($exam->time)->format('H:i') }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-white font-montserrat">No upcoming exams.</li>
                    @endforelse
                </ul>
            </div>
            <div class="mt-auto flex justify-end pt-6">
                <button onclick="window.location.href='/admin/exam_management'"
                    class="use-loader bg-red-700 hover:bg-red-800 transition font-semibold rounded-lg py-3 px-6 flex items-center space-x-2 shadow-md shadow-red-900/50">
                    <i class="fa-regular fa-file-lines"></i>
                    <span class="font-montserrat">Manage Exam</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Chart Section -->
    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 max-w-full h-[380px]">
        <!-- Monthly Applicants Chart -->
        <div class="bg-white border border-blue-700 rounded-2xl p-6 shadow-lg flex flex-col justify-between h-full">
            <div class="space-y-4">
                <h2 class="text-xl font-extrabold font-montserrat text-blue-900">Monthly Applicants</h2>
                <div class="relative h-[230px]">
                    <canvas id="applicantsChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>

        <!-- Reviewed Applicants List -->
        <div class="bg-white border border-blue-700 rounded-2xl p-6 shadow-md flex flex-col justify-between h-full">
            <div>
                <h3 class="text-lg font-extrabold font-montserrat mb-4 text-blue-900">REVIEWED APPLICANTS</h3>
                <div class="text-blue-900 space-y-2 text-sm font-montserrat overflow-y-auto max-h-[260px] pr-2">
                    @forelse ($reviewedApplications as $applicant)
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-user text-blue-600"></i>
                            <span>{{ optional($applicant->personalInformation)->first_name ?? 'N/A' }} {{ optional($applicant->personalInformation)->surname ?? '' }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">No reviewed applicants.</p>
                    @endforelse
                </div>
            </div>
            <div class="pt-6 flex justify-end">
                <a href="{{ route('applications_list') }}" class="use-loader bg-red-700 hover:bg-red-800 transition font-semibold text-white rounded-lg py-3 px-6 flex items-center space-x-2 shadow-md shadow-red-900/50">
                    <i class="fa-regular fa-file-lines text-base"></i>
                    <span class="font-montserrat text-sm">View Applicants</span>
                </a>
            </div>
        </div>
    </section>

</main>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const chartLabels = {!! $chartLabels !!};
        const chartData = {!! $chartData !!};
        const canvas = document.getElementById('applicantsChart');
        const ctx = canvas.getContext('2d');
        canvas.height = 230;

        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Applicants in {{ $selectedYear }}',
                    data: chartData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(0, 44, 118, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    });
</script>

@include('partials.loader')
@endsection
```

---

## Viewer Dashboard

**File**: `resources/views/viewer/viewer_dashboard.blade.php`

### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│ Welcome Back Section (Viewer)                           │
├─────────────────────────────────────────────────────────┤
│ Exam Management Header                                  │
├─────────────────────────────────────────────────────────┤
│ Table Header Row                                        │
│ [Vacancy ID] [Job Title] [Exam ID] [Actions]           │
├─────────────────────────────────────────────────────────┤
│ Exam List Items (Rows)                                  │
│ Each row: ID, Title, Exam ID, Copy Link, Manage buttons│
└─────────────────────────────────────────────────────────┘
```

### Sections

1. **Welcome Back Section**
   - Simple greeting for viewer

2. **Exam Management Header**
   - Dark blue header (`bg-[#0D2B70]`)
   - Icon and title "EXAM MANAGEMENT"
   - Full-width bar

3. **Table Header**
   - Grid layout: `grid-cols-[1.4fr_3.2fr_3.1fr_1.9fr_2fr_1.5fr]`
   - Dark blue background
   - Column headers: Vacancy ID, Job Title, Exam ID

4. **Exam List Rows**
   - Each row displays:
     - Vacancy ID (bold)
     - Job Title with subtitle (italic)
     - Exam ID
     - "Copy Exam Join Link" button (blue)
     - "Manage" button (dark blue)
   - Border styling: 2px solid `#0D2B70`
   - Rounded corners

### Key Features

- **Table-Based Layout**: Structured data presentation
- **Action Buttons**: Quick access to exam management
- **Simplified Navigation**: Minimal sidebar (only Exam Management)
- **Data-Focused**: Clear presentation of exam information

---

## Other Views Reference

### User Views (`resources/views/dashboard_user/`)

| View File | Description | Key Features |
|-----------|-------------|--------------|
| `dashboard_user.blade.php` | Main user dashboard | Grid layout, job applications, PDS progress |
| `my_applications.blade.php` | User's job applications list | Sortable list, application cards, status tracking |
| `job_vacancy.blade.php` | Browse job vacancies | Filters (status, type, salary, location), sortable cards |
| `application_status.blade.php` | Individual application status | Status tracking, timeline, details |
| `job_description.blade.php` | Job vacancy details | Full job description, requirements, apply button |
| `about.blade.php` | About the website | Information page |
| `work_exp.blade.php` | Work experience sheet | Form for work experience data |
| `pds_print.blade.php` | PDS print view | Printable format of Personal Data Sheet |

### Admin Views (`resources/views/admin/`)

| View File | Description | Key Features |
|-----------|-------------|--------------|
| `dashboard_admin.blade.php` | Admin dashboard | Stats, charts, overview |
| `vacancies_management.blade.php` | Manage job vacancies | CRUD operations, list view |
| `vacancy_add_cos.blade.php` | Add COS vacancy | Form for Contract of Service vacancy |
| `vacancy_add_plantilla.blade.php` | Add Plantilla vacancy | Form for Plantilla position |
| `applications_list.blade.php` | Applications list | Review applications, filter, sort |
| `applicants_profile.blade.php` | Applicant profile | Detailed applicant information |
| `all_applicants_profile.blade.php` | All applicants view | Comprehensive applicant list |
| `applicant_status.blade.php` | Update applicant status | Status management interface |
| `reviewed_applicants.blade.php` | Reviewed applications | Filter reviewed applicants |
| `exam_management.blade.php` | Exam management list | Manage exams, create/edit |
| `exam_edit.blade.php` | Edit exam | Exam editing form |
| `view_exam.blade.php` | View exam details | Exam information display |
| `manage_exam.blade.php` | Manage exam items | Question management |
| `exam_view_answers.blade.php` | View exam answers | Review applicant answers |
| `admin_account_management.blade.php` | Manage admin accounts | User management, roles |
| `admin_activity_log.blade.php` | Activity log | System activity tracking |

### Viewer Views (`resources/views/viewer/`)

| View File | Description | Key Features |
|-----------|-------------|--------------|
| `viewer_dashboard.blade.php` | Viewer dashboard | Exam management overview |
| `viewer_exam_management.blade.php` | Exam management | Manage exams interface |
| `viewer_answer_view.blade.php` | View exam answers | Review applicant responses |

### Authentication Views (`resources/views/login_register/`)

- `login.blade.php` - User login page
- `register.blade.php` - User registration
- `admin_login.blade.php` - Admin login
- `forgot_password.blade.php` - Password recovery
- `forgot_password_otp.blade.php` - OTP verification for password reset
- `otp.blade.php` - OTP verification
- `reset_password.blade.php` - Reset password form

### PDS (Personal Data Sheet) Views (`resources/views/pds/`)

- `pds.blade.php` - Main PDS form
- `c2.blade.php` - PDS Section C2
- `c3.blade.php` - PDS Section C3
- `c4.blade.php` - PDS Section C4
- `c5.blade.php` - PDS Section C5
- `submit.blade.php` - PDS submission confirmation

### PDS Update Views (`resources/views/pds_update/`)

- Similar structure to PDS views but for editing existing data
- `pds_update.blade.php`, `c2_update.blade.php`, etc.

### Exam User Views (`resources/views/exam_user/`)

- `exam_lobby.blade.php` - Pre-exam waiting room
- `exam_question_page.blade.php` - Exam interface
- `exam_thankyou.blade.php` - Post-exam confirmation

### Layout Files (`resources/views/layout/`)

- `app.blade.php` - User layout (with mobile sidebar)
- `admin.blade.php` - Admin layout
- `viewer.blade.php` - Viewer layout
- `exam_user.blade.php` - Exam interface layout
- `pds_layout.blade.php` - PDS form layout

### Partial Components (`resources/views/partials/`)

- `sidebar.blade.php` - User sidebar
- `sidebar_admin.blade.php` - Admin sidebar
- `sidebar_viewer.blade.php` - Viewer sidebar
- `mobile-sidebar.blade.php` - Mobile navigation
- `loader.blade.php` - Loading spinner
- `alerts_template.blade.php` - Alert/Modal template
- `chat_ai.blade.php` - Chatbot component
- `job_vacancy_card.blade.php` - Job card component
- `application_list.blade.php` - Application list item
- And many more reusable components...

---

## Technical Stack

### Frontend Technologies

- **CSS Framework**: Tailwind CSS (via CDN)
- **JavaScript Framework**: Alpine.js (via CDN)
- **Icons**: Feather Icons + Font Awesome 6
- **Fonts**: Google Fonts (Montserrat)
- **Charts**: Chart.js (for admin dashboard statistics)

### Backend Framework

- **Framework**: Laravel (PHP)
- **Templating**: Blade Templates
- **Livewire**: For dynamic form components (PDS forms)

### Key Libraries

```html
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Alpine.js -->
<script src="https://unpkg.com/alpinejs" defer></script>

<!-- Feather Icons -->
<script src="https://unpkg.com/feather-icons"></script>

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
```

---

## Design Patterns

### Card Pattern
- White background
- 4px border (usually `#002C76`)
- Rounded corners (`rounded-xl`)
- Padding: `p-6` to `p-8`
- Shadow: Optional `shadow-lg` or `shadow-md`

### Button Pattern
- Rounded full (`rounded-full`)
- Padding: `px-5 py-2`
- Font: Montserrat, semibold/medium
- Icon + text layout
- Hover states with opacity changes

### Grid Pattern
- 12-column responsive grid
- Mobile-first: `col-span-12` (full width)
- Desktop: Split columns (e.g., `sm:col-span-7` and `sm:col-span-5`)
- Gap: `gap-6` between grid items

### Sidebar Pattern
- Fixed positioning
- Collapsible (64px ↔ 288px)
- Smooth transitions
- State persistence (localStorage)
- Logo + navigation items
- Bottom logout button

### Header Pattern
- Dark blue background (`#002C76` or `#0D2B70`)
- White text
- Icon + title
- Rounded corners (`rounded-xl`)
- Padding: `px-8 py-4`

---

## Responsive Design

### Breakpoints (Tailwind Default)

- **sm**: 640px (Small devices)
- **md**: 768px (Medium devices)
- **lg**: 1024px (Large devices)
- **xl**: 1280px (Extra large devices)

### Mobile Considerations

- Sidebar hidden on mobile, replaced with mobile menu
- Grid layouts stack vertically on mobile
- Font sizes scale down on mobile
- Padding/margins adjust for smaller screens
- Touch-friendly button sizes

---

## Notes for Implementation

1. **Color Consistency**: Always use the defined color palette for brand consistency
2. **Typography**: Use Montserrat font family throughout
3. **Spacing**: Follow the spacing system (multiples of 4px/0.25rem)
4. **Icons**: Prefer Feather Icons, use Font Awesome when needed
5. **Responsive**: Always design mobile-first, then enhance for larger screens
6. **Accessibility**: Use semantic HTML, proper ARIA labels
7. **Performance**: Use CDN resources efficiently, consider bundling for production

---

## File Structure Reference

```
resources/views/
├── layout/              # Base layout templates
│   ├── app.blade.php
│   ├── admin.blade.php
│   ├── viewer.blade.php
│   ├── exam_user.blade.php
│   └── pds_layout.blade.php
├── partials/            # Reusable components
│   ├── sidebar*.blade.php
│   ├── loader.blade.php
│   ├── alerts_template.blade.php
│   └── ...
├── dashboard_user/      # User-facing views
│   ├── dashboard_user.blade.php
│   ├── my_applications.blade.php
│   └── ...
├── admin/               # Admin views
│   ├── dashboard_admin.blade.php
│   └── ...
├── viewer/              # Viewer views
│   └── viewer_dashboard.blade.php
├── login_register/      # Authentication views
├── pds/                 # Personal Data Sheet forms
├── exam_user/           # Exam interface
└── ...
```

---

## Frontend Code Samples

### Login Pages

#### User Login Page

**File**: `resources/views/login_register/login.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - DILG CAR Recruitment and Selection Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Montserrat', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-white flex items-center justify-center">

  <div class="w-full min-h-screen flex flex-col-reverse lg:flex-row">
    
    <!-- Left: Login Form -->
    <div class="flex-1 flex items-center justify-center p-6 bg-white">
      <div class="w-full max-w-md bg-white rounded-xl border border-blue-400 p-8 shadow-xl">
        <h2 class="text-3xl font-bold text-center text-blue-900 mb-2">WELCOME</h2>
        <p class="text-center text-blue-800 font-semibold mb-6">Please log-in to continue</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
          @csrf

          <!-- Email Input -->
          <div class="flex items-center border border-blue-400 rounded-full px-4 py-2">
            <i class="fas fa-user text-yellow-400 mr-3"></i>
            <input 
              id="email" 
              type="email" 
              name="email" 
              value="{{ old('email') }}" 
              required 
              autofocus 
              placeholder="Email"
              class="w-full bg-transparent outline-none"
            />
          </div>
          @error('email')
            <p class="text-red-600 text-sm ml-3 -mt-4">{{ $message }}</p>
          @enderror

          <!-- Password Input -->
          <div class="flex items-center border border-blue-400 rounded-full px-4 py-2">
            <i class="fas fa-lock text-yellow-400 mr-3"></i>
            <input 
              id="password" 
              type="password" 
              name="password" 
              required 
              placeholder="Password"
              class="w-full bg-transparent outline-none"
            />
          </div>
          @error('password')
            <p class="text-red-600 text-sm ml-3 -mt-4">{{ $message }}</p>
          @enderror

          <!-- Forgot Password Link -->
          <div class="text-right text-sm">
            <a href="{{ route('forgot.password.form') }}" class="text-blue-800 hover:underline">Forgot Password?</a>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-blue-900 font-bold py-3 rounded-full shadow-md transition">
            LOG-IN
          </button>

          <!-- Google Login Button -->
          <div class="flex items-center justify-center my-4">
            <a class="use-loader flex items-center justify-center gap-3 w-full bg-white border-2 border-yellow-400 text-blue-900 font-bold py-2 rounded-full hover:bg-yellow-100 shadow-md transition"
              href='{{ route('google.login') }}'>
              <img src="{{ asset('images/google-icon.png') }}" alt="Google Icon" class="w-5 h-5">
              Continue with Google
            </a>
          </div>

          <!-- Register Link -->
          @if (Route::has('register'))
            <p class="text-center text-sm text-blue-800">
              Don't have an account?
              <a href="{{ route('register') }}" class="use-loader font-bold hover:underline">SIGN-UP</a>
            </p>
          @endif
        </form>
      </div>
    </div>

    <!-- Right: Logo and Agency Info -->
    <div class="flex-1 bg-blue-800 text-white flex flex-col items-center justify-center p-8 text-center">
      <img 
        src="{{ asset('images/dilg_logo.png') }}" 
        alt="DILG Logo" 
        class="w-28 sm:w-36 md:w-40 mb-6"
      />
      <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold leading-tight">
        DEPARTMENT OF THE INTERIOR<br/>AND LOCAL GOVERNMENT
      </h1>
      <p class="text-sm sm:text-base md:text-lg mt-1 font-semibold">CORDILLERA ADMINISTRATIVE REGION</p>
      <p class="text-sm sm:text-base md:text-lg mt-1 text-blue-200">MATINO. MAHUSAY. MAAASAHAN.</p>
      <p class="text-yellow-400 font-bold mt-4 text-base sm:text-lg">
        RECRUITMENT SELECTION AND PLACEMENT PORTAL
      </p>
    </div>
  </div>

  @include('partials.loader')
</body>
</html>
```

#### Admin Login Page

**File**: `resources/views/login_register/admin_login.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Montserrat', sans-serif; }
  </style>
</head>
<body x-data="{ showForgotModal: false }">
  
  <!-- Main Content (Desktop Only) -->
  <div x-show="!isMobile" class="min-h-screen flex">
    <!-- Left: Login Form -->
    <div class="flex-1 flex items-center justify-center bg-white min-h-screen shadow-lg rounded-r-3xl">
      <form class="w-[468px] h-[538px] p-8 rounded-xl border border-blue-700 shadow-xl" 
            action="{{ route('admin.login') }}" method="POST" autocomplete="off">
        @csrf
        <h1 class="text-3xl font-extrabold text-blue-900 mb-1 drop-shadow-md">WELCOME ADMIN</h1>
        <p class="text-base font-bold text-blue-900 mb-14 drop-shadow-md">Please log-in to continue</p>
        
        <!-- Email Input -->
        <label class="relative block mb-6 mt-3">
          <span class="material-icons absolute inset-y-0 left-3 flex items-center text-yellow-400 text-lg">email</span>
          <input
            type="email"
            name="email"
            placeholder="E-mail Address"
            class="pl-10 pr-4 h-10 w-full border border-blue-700 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400"
            required
          />
        </label>

        <!-- Password Input -->
        <label class="relative block mb-2">
          <span class="material-icons absolute inset-y-0 left-3 flex items-center text-yellow-400 text-lg">lock</span>
          <input
            type="password"
            name="password"
            placeholder="Password"
            class="pl-10 pr-4 h-10 w-full border border-blue-700 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-400"
            required
          />
        </label>

        <!-- Forgot Password -->
        <div class="text-xs text-blue-700 mb-6 text-right">
          <a href="#" class="hover:underline" @click.prevent="showForgotModal = true">Forgot Password?</a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-yellow-400 text-blue-900 font-bold mt-16 py-2 rounded-full hover:bg-yellow-500 shadow-md transition">
          LOG-IN
        </button>
      </form>
    </div>

    <!-- Right: Branding -->
    <div class="flex-1 bg-blue-800 text-center p-10 flex flex-col justify-center min-h-screen">
      <img src="{{ asset('images/dilg_logo.png') }}" alt="DILG Logo" class="mx-auto mb-8 max-w-[200px]" />
      <h2 class="text-white text-2xl font-bold leading-tight max-w-md mx-auto mb-1 drop-shadow-lg">
        DEPARTMENT OF THE INTERIOR <br>AND LOCAL GOVERNMENT
      </h2>
      <p class="text-white text-sm font-semibold mb-2">CORDILLERA ADMINISTRATIVE REGION</p>
      <p class="text-blue-300 text-lg font-semibold mb-5 tracking-widest uppercase">
        MATINO. MAHUSAY. MAAASAHAN.
      </p>
      <h3 class="text-yellow-400 text-xl font-extrabold max-w-lg mx-auto leading-snug">
        ADMIN ACCESS PORTAL
      </h3>
    </div>
  </div>

  <!-- Error Messages -->
  @if ($errors->any())
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition
      class="fixed top-5 right-5 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-lg w-full max-w-sm">
      <strong class="font-bold">Whoops!</strong>
      <ul class="list-disc list-inside text-sm mt-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @include('partials.loader')
</body>
</html>
```

### Sidebar Component

**File**: `resources/views/partials/sidebar.blade.php`

```blade
<aside id="sidebar"
    class="sidebar sidebar-transition fixed ml-5 mt-5 mb-5 flex flex-col justify-between bg-white text-[#002C76] rounded-xl shadow-lg overflow-hidden w-16 relative z-60 h-[95vh]">

    <!-- Toggle Button -->
    <button id="toggleSidebar" class="p-2 focus:outline-none absolute top-3 left-3 z-20" aria-label="Toggle sidebar">
        <i data-feather="menu" class="w-5 h-5 stroke-[3]"></i>
    </button>

    <!-- Upper Section -->
    <div>
        <a class="flex items-center gap-2 pt-14 px-2">
            <img src="{{ asset('images/dilg_logo.png') }}" alt="DILG Logo"
                class="h-12 w-12 rounded-full border border-white flex-shrink-0 logo-transition" />
            <div id="sidebarText" class="sidebar-text-hidden whitespace-nowrap overflow-hidden">
                <div class="font-bold font-montserrat text-[#002C76] text-[20px] uppercase leading-tight tracking-wide">
                    DILG - CAR
                </div>
                <div class="text-[16px] leading-4 font-bold font-montserrat tracking-tighter text-[#002C76] uppercase">
                    RECRUITMENT SELECTION<br>AND PLACEMENT PORTAL
                </div>
            </div>
        </a>

        <!-- Navigation -->
        <nav class="mt-8 space-y-1 px-2 font-montserrat" aria-label="Main navigation">
            <a href="{{ route('dashboard_user') }}"
                class="group flex items-center rounded-md px-4 py-2 text-sm font-bold transition use-loader
                    {{ request()->routeIs('dashboard_user')
                        ? 'bg-[#002C76] text-white'
                        : 'text-[#002C76] hover:text-white hover:bg-[#002C76]' }}">
                <i data-feather="home" class="w-5 h-5 stroke-[3] flex-shrink-0"></i>
                <span id="textHome" class="sidebar-text-hidden ml-3">HOME</span>
            </a>

            <a href="{{ route('job_vacancy') }}"
                class="group flex items-center rounded-md px-4 py-2 text-sm font-bold transition use-loader
                    {{ request()->routeIs('job_vacancy')
                        ? 'bg-[#002C76] text-white'
                        : 'text-[#002C76] hover:text-white hover:bg-[#002C76]' }}">
                <i data-feather="archive" class="w-5 h-5 stroke-[3] flex-shrink-0"></i>
                <span id="textJobVacancies" class="sidebar-text-hidden ml-3">JOB VACANCIES</span>
            </a>

            <!-- More navigation items... -->
        </nav>
    </div>

    <!-- Bottom Section: Logout -->
    <div class="px-2 pb-6">
        <button id="logoutButton"
            class="group flex items-center rounded-md border border-[#FFFFFF] px-4 py-2 text-sm font-bold text-[#C9282D] hover:bg-[#C9282D] hover:bg-opacity-20 hover:border-red-500 transition w-full">
            <i data-feather="log-out" class="w-5 h-5 stroke-[3] flex-shrink-0"></i>
            <span id="textLogOut" class="sidebar-text-hidden ml-3">LOG-OUT</span>
        </button>
    </div>
</aside>
```

### Base Layout Structure

**File**: `resources/views/layout/app.blade.php` (Excerpt)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'DILG Dashboard')</title>
    
    <!-- Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs" defer></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Styles -->
    <style>
        .font-montserrat {
            font-family: 'Montserrat', sans-serif;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar-text-hidden {
            opacity: 0;
            pointer-events: none;
            width: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .sidebar-text-visible {
            opacity: 1;
            pointer-events: auto;
            width: auto;
            transition: all 0.3s ease;
        }

        .logo-small {
            max-width: 48px;
            max-height: 48px;
        }
    </style>

    @stack('styles')
</head>

<body x-data="{ mobileSidebarOpen: false, showLogoutModal: false }" 
      class="bg-[#F3F8FF] min-h-screen font-montserrat text-gray-900 overflow-x-hidden">

    <!-- Mobile Toggle Button -->
    <button @click="mobileSidebarOpen = true"
            class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-full shadow-md mt-4">
        <i data-feather="menu" class="w-5 h-5"></i>
    </button>

    <!-- Mobile Sidebar -->
    <div class="lg:hidden">
        @include('partials.mobile-sidebar')
    </div>

    <!-- Main Layout -->
    <div class="flex h-screen w-full overflow-hidden">
        <!-- Desktop Sidebar -->
        <div class="sidebar-desktop">
            @include('partials.sidebar')
        </div>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto ml-2 p-3 sm:p-10 pt-8 mt-0 sm:mt-1 space-y-10 md:ml-20 transition-all duration-300">
            @yield('content')
        </main>

        <!-- Chatbot -->
        @include('partials.chat_ai')
    </div>

    <!-- Feather Icons Script -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                feather.replace();
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
```

### Loader Component

**File**: `resources/views/partials/loader.blade.php`

```blade
<style>
  .loader {
    width: 150px;
    height: 120px;
    background:
      linear-gradient(#0000 calc(1 * 100% / 6), rgb(201, 40, 45) 0 calc(3 * 100% / 6), #0000 0),
      linear-gradient(#0000 calc(2 * 100% / 6), rgb(255, 222, 21) 0 calc(4 * 100% / 6), #0000 0),
      linear-gradient(#0000 calc(3 * 100% / 6), rgb(0, 44, 118) 0 calc(5 * 100% / 6), #0000 0);
    background-size: 30px 400%;
    background-repeat: no-repeat;
    animation: matrix 1s infinite linear;
  }

  @keyframes matrix {
    0% { background-position: 0% 100%, 50% 100%, 100% 100%; }
    100% { background-position: 0% 0%, 50% 0%, 100% 0%; }
  }

  .background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(255, 255, 255, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  .hidden {
    display: none !important;
  }
</style>

<div class="background" id="loader">
  <div class="loader"></div>
</div>

<script>
  window.addEventListener('load', function () {
    setTimeout(() => {
      document.getElementById('loader')?.classList.add('hidden');
    }, 500);
  });

  document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('loader');

    // Form submissions
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', (e) => {
        if (form.checkValidity()) {
          loader?.classList.remove('hidden');
        }
      });
    });

    // Anchor links with use-loader class
    document.querySelectorAll('a.use-loader').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        loader?.classList.remove('hidden');
        setTimeout(() => window.location.href = this.href, 100);
      });
    });

    // Buttons with use-loader class
    document.querySelectorAll('button.use-loader').forEach(button => {
      button.addEventListener('click', () => loader?.classList.remove('hidden'));
    });
  });
</script>
```

### Job Vacancy Card Component

**File**: `resources/views/partials/job_vacancy_card.blade.php`

```blade
<div class="bg-white border-4 border-[#002C76] rounded-xl p-6 shadow-md space-y-2 relative w-full">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#002C76] font-montserrat">
        {{ $vacancy->position_title }}
        <span class="font-semibold text-gray-500 text-lg">({{ $vacancy->vacancy_type }})</span>
    </h2>
    <p class="font-montserrat text-black font-bold text-lg">
        Monthly Salary: ₱{{ number_format($vacancy->monthly_salary, 2) }}
    </p>
    <p class="font-montserrat text-black font-semibold text-sm sm:text-base">
        Place of Assignment: {{ $vacancy->place_of_assignment }}
    </p>
    <div class="flex items-center gap-2 mt-2">
        <span class="flex items-center gap-1 {{ $vacancy->status === 'OPEN' ? 'text-green-600' : 'text-red-600' }} font-bold text-sm">
            <span class="w-3 h-3 {{ $vacancy->status === 'OPEN' ? 'bg-green-600' : 'bg-red-600' }} rounded-full inline-block"></span>
            {{ $vacancy->status }}
        </span>
        <span class="text-gray-500 text-base">
            Closes {{ \Carbon\Carbon::parse($vacancy->closing_date)->subMinute()->format('n/j/Y g:i A') }},
        </span>
        <span class="text-gray-500 text-base">
            Posted {{ date('n/j/Y g:i A', strtotime($vacancy->posted_at)) }}
        </span>
    </div>
    <a href="{{ route('job_description', $vacancy->vacancy_id) }}"
        class="use-loader mt-3 inline-flex items-center gap-2 rounded-full bg-gray-200 text-[#002C76] px-4 py-2 text-sm font-medium shadow-sm hover:bg-gray-300 transition w-fit">
        <i data-feather="eye" class="w-4 h-4"></i> View this Job Vacancy
    </a>
</div>
```

### Common Button Patterns

```html
<!-- Primary Button (Blue) -->
<button class="inline-flex items-center gap-2 rounded-full bg-[#002C76] text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition">
    <i data-feather="icon-name" class="w-4 h-4"></i> Button Text
</button>

<!-- Secondary Button (Green) -->
<button class="inline-flex items-center gap-2 rounded-full bg-green-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition">
    <i data-feather="icon-name" class="w-4 h-4"></i> Button Text
</button>

<!-- Danger Button (Red) -->
<button class="inline-flex items-center gap-2 rounded-full bg-red-600 text-white px-5 py-2 text-sm font-medium shadow-sm hover:bg-opacity-90 transition">
    <i data-feather="icon-name" class="w-4 h-4"></i> Button Text
</button>

<!-- With Loader -->
<a href="{{ route('some.route') }}" class="use-loader inline-flex items-center gap-2 rounded-full bg-[#002C76] text-white px-5 py-2 text-sm font-medium">
    <i data-feather="icon-name" class="w-4 h-4"></i> Navigate
</a>
```

### Card Pattern Examples

```html
<!-- Basic Card -->
<div class="rounded-xl bg-white border-4 border-[#002C76] p-8">
    <h2 class="text-2xl font-extrabold text-[#002C76] font-montserrat mb-4">Card Title</h2>
    <p class="text-gray-700 font-montserrat">Card content goes here</p>
</div>

<!-- Card with Icon Header -->
<div class="rounded-xl bg-white border-4 border-[#002C76] p-8">
    <h2 class="text-xl font-extrabold flex items-center gap-3 font-montserrat">
        <i data-feather="icon-name" class="w-5 h-5"></i> CARD TITLE
    </h2>
    <!-- Content -->
</div>

<!-- Dark Card (Admin) -->
<div class="bg-blue-900 rounded-2xl p-6 text-white shadow-lg flex flex-col">
    <h2 class="font-extrabold text-2xl flex items-center gap-3 mb-5 font-montserrat">
        <i class="fa-solid fa-icon"></i> TITLE
    </h2>
    <!-- Content -->
</div>
```

### Grid Layout Examples

```html
<!-- 12-Column Responsive Grid -->
<section class="grid grid-cols-12 gap-6 w-full">
    <!-- 7 columns on desktop, full width on mobile -->
    <div class="col-span-12 sm:col-span-7">
        Content 1
    </div>
    <!-- 5 columns on desktop, full width on mobile -->
    <div class="col-span-12 sm:col-span-5">
        Content 2
    </div>
</section>

<!-- Two Column Equal Split -->
<section class="grid grid-cols-1 xl:grid-cols-2 gap-6 max-w-full">
    <div>Column 1</div>
    <div>Column 2</div>
</section>

<!-- Flexible Row (Admin Stats) -->
<section class="flex divide-x divide-blue-700 bg-white">
    <div class="flex-1">Stat 1</div>
    <div class="flex-1">Stat 2</div>
    <div class="flex-1">Stat 3</div>
    <div class="flex-1">Stat 4</div>
</section>
```

### Form Input Patterns

```html
<!-- Rounded Input with Icon -->
<div class="flex items-center border border-blue-400 rounded-full px-4 py-2">
    <i class="fas fa-user text-yellow-400 mr-3"></i>
    <input 
        type="text" 
        name="field_name" 
        placeholder="Placeholder text"
        class="w-full bg-transparent outline-none"
        required
    />
</div>

<!-- Error Display -->
@error('field_name')
    <p class="text-red-600 text-sm ml-3 -mt-2">{{ $message }}</p>
@enderror

<!-- Select Dropdown -->
<select class="border-2 border-[#0D2B70] rounded-lg px-4 py-2 text-sm font-montserrat">
    <option value="">All Options</option>
    <option value="1">Option 1</option>
    <option value="2">Option 2</option>
</select>
```

---

*Last Updated: Based on current codebase structure*
*Framework: Laravel with Blade Templates*
*Styling: Tailwind CSS*

