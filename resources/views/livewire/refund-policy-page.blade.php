<div>
    @include('partials.legal-page-hero', [
        'title' => 'Refund policy',
        'subtitle' =>
            'How refunds work when you pay or book through ' .
            config('app.name') .
            '. Venues may apply their own rules; where they do, those rules apply to the booking.',
        'meta' => 'Version ' .
            config('legal.refund.version') .
            ', effective ' .
            \Illuminate\Support\Carbon::parse(config('legal.refund.effective_date'))->format('F j, Y') .
            '.',
    ])

    <article class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 sm:px-6 lg:px-8">
        <div
            class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-display prose-h2:mt-10 prose-h2:text-lg prose-h2:font-bold prose-h2:uppercase prose-h2:tracking-wide"
        >
            <h2>Overview</h2>
            <p>
                {{ config('app.name') }} helps you book courts and related services with participating venues. Refunds
                depend on how the payment was taken, what the venue published at the time of booking, and whether a
                cancellation was made in line with the
                <a href="{{ route('booking-cancellation-policy') }}" wire:navigate class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">
                    Booking &amp; cancellation policy
                </a>.
            </p>

            <h2>Convenience fee</h2>
            <p>
                Certain bookings include a separate <strong>convenience fee</strong> collected by
                {{ config('app.name') }}. That fee is our compensation for providing the booking platform and related services
                and is our fee-based revenue from those payments.
            </p>
            <p>
                <strong>We do not refund the convenience fee.</strong> Where you receive a refund of amounts owed to a venue
                (such as court rental after an eligible cancellation), any refund is limited to those venue-related amounts
                and does not include repayment of the convenience fee, except where mandatory law requires otherwise. See also our
                <a href="{{ route('terms') }}" wire:navigate class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">
                    Terms &amp; conditions
                </a>.
            </p>

            <h2>Venue-specific rules</h2>
            <p>
                Many refunds are governed by the venue’s published cancellation window, pricing, and house rules shown
                during checkout or on the venue’s listing. Where a venue policy is stricter or more specific than this
                page, the venue policy applies to that booking.
            </p>

            <h2>Eligible refunds</h2>
            <p>We may process a refund when:</p>
            <ul>
                <li>a booking is cancelled within the allowed window and the venue or configuration provides for a refund;</li>
                <li>the venue or we cancel the booking (for example double-booking or closure) and a refund is due;</li>
                <li>a payment error or duplicate charge is verified and reversed;</li>
                <li>required by applicable law or payment network rules.</li>
            </ul>

            <h2>Non-refundable or partial cases</h2>
            <p>Refunds may be unavailable or only partial when:</p>
            <ul>
                <li>the <strong>convenience fee</strong> is non-refundable (as described above), even if other parts of a payment are adjusted;</li>
                <li>you cancel outside the permitted window or do not attend (no-show);</li>
                <li>the booking was sold as non-refundable or at a special rate where that was clearly disclosed;</li>
                <li>third-party fees (for example certain payment or FX fees) cannot be reclaimed;</li>
                <li>a dispute is resolved in favor of the venue under their rules.</li>
            </ul>

            <h2>How to request a refund</h2>
            <p>
                Start from your booking details in {{ config('app.name') }} where self-service options exist, or contact
                the venue directly for venue-managed payments. For platform-related billing issues, email
                <a
                    href="mailto:{{ config('data_privacy.contact_email') }}"
                    class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                >
                    {{ config('data_privacy.contact_email') }}
                </a>
                with your booking reference and a short description. We may need a few business days to coordinate with the
                venue or payment provider.
            </p>

            <h2>Timing &amp; method</h2>
            <p>
                Approved refunds are typically returned to the original payment method. Banks and card issuers may add
                their own processing time (often several business days). If that method is no longer available, we will
                work with you on a reasonable alternative where possible.
            </p>

            <h2>Chargebacks</h2>
            <p>
                If you initiate a chargeback or payment dispute, we may pause or limit account features until the matter
                is resolved. We encourage you to contact us first so we can help without unnecessary disputes.
            </p>

            <h2>Consumer rights (Philippines)</h2>
            <p>
                Nothing in this policy limits statutory rights you may have under the laws of the Philippines (including
                consumer protection rules) where they apply to your transaction. If there is a conflict, mandatory law
                prevails.
            </p>

            <h2>Changes</h2>
            <p>
                We may update this Refund policy from time to time. Material changes will be reflected on this page with
                an updated version or effective date.
            </p>
        </div>

        @include('partials.legal-pages-nav')
    </article>
</div>
