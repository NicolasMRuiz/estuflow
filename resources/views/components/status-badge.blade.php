@props(['status'])
<span {{ $attributes->class(['state-tag', $status]) }}>{{ \App\Academic\AcademicProgress::STATUSES[$status] }}</span>
