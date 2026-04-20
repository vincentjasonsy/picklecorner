<div>
    @include('partials.legal-page-hero', [
        'title' => 'Booking & cancellation policy',
        'subtitle' =>
            'How bookings, changes, and cancellations work on ' .
            config('app.name') .
            '. Venues may set stricter or additional rules at the time you book.',
        'meta' => 'Version ' .
            config('legal.booking_cancellation.version') .
            ', effective ' .
            \Illuminate\Support\Carbon::parse(config('legal.booking_cancellation.effective_date'))->format('F j, Y') .
            '.',
    ])

    <x-legal-document>
            <h2>Confirmations</h2>
            <p>
                A booking is confirmed when the Service shows it as confirmed (or the venue confirms it through the
                platform). You are responsible for arriving on time with any required equipment or membership status the
                venue expects.
            </p>

            <h2>Cancellations by you</h2>
            <p>
                You may cancel eligible bookings through the Service where the venue allows self-service cancellation.
                Cancellation cutoffs, fees, and refund eligibility depend on the venue’s rules shown at booking and on
                your booking screen. Late cancellations may be treated as partial or non-refundable.
            </p>

            <h2>Cancellations by the venue</h2>
            <p>
                Venues may cancel due to maintenance, weather, staffing, or safety. If your booking is cancelled by the
                venue, we will use reasonable efforts to reflect that in the app and, where applicable, follow the
                <a href="{{ route('refund-policy') }}" wire:navigate>Refund policy</a>.
            </p>

            <h2>Rescheduling</h2>
            <p>
                Moving a booking to another time or court is subject to availability and venue rules. Some slots or
                promotional rates may not be changeable; that will be indicated where the venue configures it.
            </p>

            <h2>No-shows</h2>
            <p>
                If you do not arrive for a confirmed booking and do not cancel in line with the rules, the venue may
                mark the booking as a no-show. No-shows are typically not eligible for a refund and may affect future
                bookings with that venue.
            </p>

            <h2>Weather &amp; force majeure</h2>
            <p>
                Outdoor or exposed courts may be affected by weather. Venues decide whether to close or postpone. We are
                not responsible for events outside our reasonable control, but we will support communications shown in
                the Service when venues update bookings.
            </p>

            <h2>Manual &amp; desk bookings</h2>
            <p>
                Bookings created by front-desk staff or administrators follow the same venue rules unless the venue
                specifies different terms when the booking is made.
            </p>

            <h2>Coach &amp; lesson bookings</h2>
            <p>
                Coach-related bookings may have additional cancellation or rescheduling rules set by the coach or venue.
                Those rules apply when displayed at booking or in your account.
            </p>

            <h2>Disputes between you and a venue</h2>
            <p>
                Operational questions (access codes, lights, equipment) should be directed to the venue. If you cannot
                resolve an issue, contact us at
                <a href="mailto:{{ config('data_privacy.contact_email') }}">{{ config('data_privacy.contact_email') }}</a>
                with your booking reference and we will try to help coordinate a fair outcome within platform limits.
            </p>

            <h2>Related policies</h2>
            <p>
                Payments and refunds are covered in our
                <a href="{{ route('refund-policy') }}" wire:navigate>Refund policy</a>. Use of the Service is governed by our
                <a href="{{ route('terms') }}" wire:navigate>Terms &amp; conditions</a>.
            </p>

            <h2>Changes</h2>
            <p>
                We may update this policy periodically. The version and effective date at the top of this page will
                change when we do.
            </p>
    </x-legal-document>
</div>
