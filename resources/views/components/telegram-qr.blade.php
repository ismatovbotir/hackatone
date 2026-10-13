{{--
    QR code for the bot's Start link (the same for everyone, never expires). The pages around it poll the server
    (to notice when Telegram is linked), and each poll re-renders the HTML, which would wipe a QR drawn in the
    browser; wire:ignore keeps it. Retries until the QR library has loaded.
--}}
@props(['link'])
<div wire:key="qr-{{ md5($link) }}" wire:ignore
     x-data="{ draw() { const svg = window.qrSvg ? window.qrSvg(@js($link)) : ''; svg ? ($el.innerHTML = svg) : setTimeout(() => this.draw(), 200) } }"
     x-init="draw()"
     role="img" aria-label="QR: {{ $link }}"
     {{ $attributes->class('rounded-xl border border-line bg-white [&_svg]:block [&_svg]:h-auto [&_svg]:w-full') }}></div>
