@props(['campaign'])

@auth
@php
    $volunteer = auth()->user()->volunteer ?? null;
    $joined = false;
    if ($volunteer) {
        $joined = $campaign->volunteers()->where('volunteer_id', $volunteer->id)->exists();
    }
@endphp

@if(!$volunteer)
    <p>يرجى إكمال ملف المتطوع قبل التطوع للحملة.</p>
@elseif($joined)
    <button class="btn btn-secondary" disabled>تم الطلب</button>
@else
    <form method="POST" action="{{ route('campaigns.volunteer', $campaign) }}">
        @csrf
        <button type="submit" class="btn btn-primary">تطوع للحملة</button>
    </form>
@endif
@endauth

@guest
    <a href="{{ route('login') }}" class="btn btn-primary">تسجيل الدخول للتطوع</a>
@endguest
