{{-- resources/views/admin/hr/attendance/monthly.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Monthly Attendance')

@section('css')
    <style>
        .monthly-grid {
            display: grid;
            grid-template-columns: 200px repeat({{ $daysInMonth }}, 1fr);
            gap: 1px;
            background: var(--gray-200);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        .monthly-grid .cell {
            background: white;
            padding: 0.25rem;
            text-align: center;
            font-size: 0.7rem;
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .monthly-grid .cell.header {
            background: var(--gray-50);
            font-weight: 700;
            font-size: 0.65rem;
            color: var(--gray-500);
        }
        .monthly-grid .cell.employee-name {
            font-weight: 600;
            font-size: 0.75rem;
            justify-content: flex-start;
            padding-left: 0.5rem;
            background: var(--gray-50);
        }
        .monthly-grid .cell.present { background: var(--success-bg); color: var(--success); }
        .monthly-grid .cell.late { background: var(--warning-bg); color: var(--warning); }
        .monthly-grid .cell.absent { background: var(--danger-bg); color: var(--danger); }
        .monthly-grid .cell.leave { background: var(--primary-bg); color: var(--primary); }
        .monthly-grid .cell.weekend { background: var(--gray-100); color: var(--gray-400); }

        .summary-box {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
        }
        .summary-box .label {
            font-size: 0.65rem;
            color: var(--gray-500);
            font-weight: 600;
        }
        .summary-box .value {
            font-size: 1rem;
            font-weight: 700;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-calendar-month me-2"></i> {{ __('ui.monthly') }} <span class="accent">{{ __('ui.attendance') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-date me-1"></i> {{ $monthName }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="month" class="form-control" id="monthPicker" value="{{ sprintf('%04d-%02d', $year, $month) }}" style="width: auto;">
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="?month={{ $month - 1 }}&year={{ $year }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-chevron-left"></i> Previous
            </a>
            <a href="?month={{ $month + 1 }}&year={{ $year }}" class="btn btn-outline-primary btn-sm">
                Next <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <!-- Legend -->
        <div class="d-flex gap-3 mb-3 flex-wrap">
            <span><span class="badge bg-success">P</span> {{ __('ui.present') }}</span>
            <span><span class="badge bg-warning">L</span> Late</span>
            <span><span class="badge bg-danger">A</span> {{ __('ui.absent') }}</span>
            <span><span class="badge bg-primary">V</span> Leave</span>
            <span><span class="badge bg-secondary">-</span> Weekend</span>
        </div>

        <!-- Monthly Grid -->
        <div class="monthly-grid">
            <!-- Header -->
            <div class="cell header employee-name">{{ __('ui.employee') }}</div>
            @for($day = 1; $day <= $daysInMonth; $day++)
                <div class="cell header">{{ $day }}</div>
            @endfor

            <!-- Data -->
            @foreach($attendanceData as $data)
                <div class="cell employee-name">
                    {{ $data['employee']->first_name }} {{ substr($data['employee']->last_name, 0, 1) }}.
                </div>
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $attendance = $data['attendances']->get($dateKey);
                        $isWeekend = \Carbon\Carbon::create($year, $month, $day)->isWeekend();
                    @endphp
                    @if($isWeekend)
                        <div class="cell weekend">-</div>
                    @elseif($attendance)
                        <div class="cell {{ $attendance->status }}">
                            {{ substr(ucfirst($attendance->status), 0, 1) }}
                        </div>
                    @else
                        <div class="cell absent">A</div>
                    @endif
                @endfor
            @endforeach
        </div>

        <!-- Summary -->
        <div class="row g-3 mt-3">
            @foreach($attendanceData as $data)
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="summary-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">{{ $data['employee']->first_name }} {{ $data['employee']->last_name }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;">{{ $data['employee']->employee_id }}</span>
                        </div>
                        <div class="d-flex gap-3 mt-1">
                            <span class="label">{{ __('ui.present') }}</span>
                            <span class="value text-success">{{ $data['summary']['present'] }}</span>
                            <span class="label">Late</span>
                            <span class="value text-warning">{{ $data['summary']['late'] }}</span>
                            <span class="label">{{ __('ui.absent') }}</span>
                            <span class="value text-danger">{{ $data['summary']['absent'] }}</span>
                            <span class="label">Leave</span>
                            <span class="value text-primary">{{ $data['summary']['leave'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#monthPicker').on('change', function() {
                const [year, month] = $(this).val().split('-');
                if (year && month) {
                    window.location.href = window.location.pathname + '?month=' + parseInt(month) + '&year=' + parseInt(year);
                }
            });
        });
    </script>
@endsection
