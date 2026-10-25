@props([
    'imgClass' => 'h-11 w-11 rounded-2xl object-cover ring-2 ring-[#e2ede6]',
])

@if (! empty($company['logo']))
    <img src="{{ asset('storage/'.$company['logo']) }}" alt="" {{ $attributes->merge(['class' => $imgClass]) }}>
@else
    <img src="{{ asset('images/hooman/logo.jpg') }}" alt="هومن سرویس" {{ $attributes->merge(['class' => $imgClass]) }}>
@endif
