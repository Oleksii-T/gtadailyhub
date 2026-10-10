@extends('layouts.app')

@section('title', $page?->meta_title ?: 'Contact Us')
@section('description', $page?->meta_description)

@section('content')
<div class="page">

    <div class="cat-header">
        <h1 class="cat-title">Contact Us</h1>
    </div>

    <div class="contact-layout">

        <div class="contact-form-wrap">
            <form class="contact-form" id="contact-form">
                @csrf
                <div class="contact-form__field">
                    <label class="contact-form__label" for="contact-name">Name</label>
                    <input class="contact-form__input" type="text" id="contact-name" name="name" placeholder="Your name" autocomplete="name" maxlength="255" required>
                </div>
                <div class="contact-form__field">
                    <label class="contact-form__label" for="contact-email">Email</label>
                    <input class="contact-form__input" type="email" id="contact-email" name="email" placeholder="name@example.com" autocomplete="email" maxlength="255" required>
                </div>
                <div class="contact-form__field">
                    <label class="contact-form__label" for="contact-subject">Subject</label>
                    <input class="contact-form__input" type="text" id="contact-subject" name="subject" placeholder="What is this about?" maxlength="255" required>
                </div>
                <div class="contact-form__field">
                    <label class="contact-form__label" for="contact-text">Message</label>
                    <textarea class="contact-form__textarea" id="contact-text" name="text" rows="6" placeholder="Share the details." maxlength="2000" required></textarea>
                </div>
                <button class="contact-form__btn" type="submit">{{ $page->show('form:cta') }}</button>
            </form>
        </div>

        <div class="contact-side-blocks">
            <div class="contact-block">
                @if($page->show('side:content') && $page->show('side:content') !== 'todo')
                    {!! $page->show('side:content') !!}
                @else
                    <span class="eyebrow">The news desk</span><h2>Got a story?</h2><p>Send a tip, ask a question, or share something worth a closer look. We'd love to hear from fellow visitors to Vice City.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="contact-blocks-grid">
        <x-content-blocks :blocks="$blocks" type="1" />
    </div>
</div>
@endsection

@section('scripts')
@if(config('services.recaptcha.public_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.public_key') }}"></script>
@endif
<script>
document.querySelector('#contact-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = this.querySelector('button');
    const label = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending…';
    try {
        const data = new FormData(this);
        const key = @json(config('services.recaptcha.public_key'));
        if (key) {
            if (!window.grecaptcha) throw new Error('Verification could not load. Please try again.');
            await new Promise(resolve => grecaptcha.ready(resolve));
            data.append('g-recaptcha-response', await grecaptcha.execute(key, {action: 'contact'}));
        }
        const response = await fetch(@json(route('feedbacks.store')), {
            method: 'POST', body: data, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Please check your details and try again.');
        await Swal.fire({icon: 'success', title: 'Message sent!', text: 'Thank you for getting in touch.', background: '#1b1b25', color: '#fff9cb', confirmButtonColor: '#f976b0'});
        this.reset();
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Unable to send', text: error.message, background: '#1b1b25', color: '#fff9cb', confirmButtonColor: '#f976b0'});
    } finally {
        button.disabled = false;
        button.textContent = label;
    }
});
</script>
@endsection
