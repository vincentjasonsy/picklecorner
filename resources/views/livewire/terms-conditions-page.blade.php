<div>
    @include('partials.legal-page-hero', [
        'title' => 'Terms & conditions',
        'subtitle' => 'These terms govern your use of ' . config('app.name') . ' and related services. Please read them carefully before using the platform.',
        'meta' => 'Version ' .
            config('legal.terms.version') .
            ', effective ' .
            \Illuminate\Support\Carbon::parse(config('legal.terms.effective_date'))->format('F j, Y') .
            '.',
    ])

    <article class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 sm:px-6 lg:px-8">
        <div
            class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-display prose-h2:mt-10 prose-h2:text-lg prose-h2:font-bold prose-h2:uppercase prose-h2:tracking-wide"
        >
            <h2>Agreement</h2>
            <p>
                By accessing or using {{ config('app.name') }} (the “Service”), you agree to these Terms &amp; Conditions
                (“Terms”). If you do not agree, do not use the Service. We may update these Terms from time to time; the
                “effective” date at the top of this page will change, and continued use after changes may constitute
                acceptance as allowed under applicable law.
            </p>

            <h2>Who we are</h2>
            <p>
                The Service is operated by the team behind {{ config('app.name') }} (“we”, “us”). Certain features may be
                offered together with venues (clubs, courts, and their operators). Some obligations in a booking may be
                between you and the venue; we explain that relationship below.
            </p>

            <h2>Eligibility &amp; accounts</h2>
            <p>
                You must be able to form a binding contract under applicable law to use the Service. You are responsible
                for the accuracy of information you provide and for safeguarding your login credentials. Notify us
                promptly if you suspect unauthorized access.
            </p>

            <h2>What the Service does</h2>
            <p>
                {{ config('app.name') }} provides tools to discover venues, view availability, create and manage bookings,
                and use related features (for example member tools, desk workflows, or coaching features where
                applicable). Individual venues set their own schedules, prices, rules, and house policies. Unless we state
                otherwise, we are a technology platform—not the operator of every court or facility.
            </p>

            <h2>Bookings, payments &amp; venue rules</h2>
            <p>
                When you book through the Service, you agree to the venue’s stated rules, slot times, and pricing shown at
                the time of booking. Payment, invoicing, taxes, and any on-site charges may be handled according to the
                venue’s configuration and these Terms. Specific cancellation, refund, and no-show rules are summarized in
                our
                <a href="{{ route('booking-cancellation-policy') }}" wire:navigate class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">
                    Booking &amp; cancellation policy
                </a>
                and
                <a href="{{ route('refund-policy') }}" wire:navigate class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">
                    Refund policy
                </a>.
            </p>

            <h2 id="convenience-fee">Convenience fee</h2>
            <p>
                When you pay through the Service, a separate
                <strong>convenience fee</strong> may be added to your booking. This fee is charged by {{ config('app.name') }}
                as compensation for operating and maintaining the marketplace; it is our sole fee-based revenue from those
                transactions and is shown before you confirm payment.
            </p>
            <p>
                Unless applicable law requires otherwise, <strong>the convenience fee is non-refundable</strong>.
                If you receive a refund of court rental or other venue charges (for example after a qualifying cancellation),
                those amounts may be refunded according to venue rules and our policies; the convenience fee is not refundable
                as a separate item and will not be credited back except where mandatory law applies.
            </p>

            <h2>Acceptable use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>misuse the Service, attempt unauthorized access, or interfere with security or performance;</li>
                <li>use the Service for unlawful, harassing, or fraudulent purposes;</li>
                <li>scrape, overload, or automate the Service in a way that harms us or venues without permission;</li>
                <li>impersonate another person or misrepresent your affiliation.</li>
            </ul>
            <p>We may suspend or terminate access if we reasonably believe these rules are violated.</p>

            <h2>Intellectual property</h2>
            <p>
                The Service, including branding, software, and content we provide, is protected by intellectual property
                laws. You receive a limited, revocable license to use the Service for its intended purpose. You may not
                copy, modify, or reverse engineer the Service except as permitted by law.
            </p>

            <h2>Disclaimers</h2>
            <p>
                The Service is provided on an “as is” and “as available” basis to the extent permitted by law. We do not
                guarantee uninterrupted or error-free operation. Sporting activities involve inherent risk; you are
                responsible for your own safety and compliance with venue rules.
            </p>

            <h2>Limitation of liability</h2>
            <p>
                To the maximum extent permitted by applicable law (including the laws of the Philippines where they
                apply), we are not liable for indirect, incidental, special, consequential, or punitive damages, or for
                loss of profits, data, or goodwill, arising from your use of the Service. Our aggregate liability for
                claims relating to the Service is limited to the greater of (a) the amounts you paid to us for the Service
                in the three (3) months before the claim or (b) a nominal amount where no fees were paid to us, except
                where such a cap is not allowed by mandatory law.
            </p>

            <h2>Indemnity</h2>
            <p>
                You agree to indemnify and hold harmless {{ config('app.name') }} and its team against claims arising from
                your use of the Service, your violation of these Terms, or your violation of third-party rights, to the
                extent permitted by law.
            </p>

            <h2>Privacy</h2>
            <p>
                Our collection and use of personal data is described in the
                <a href="{{ route('privacy-policy') }}" wire:navigate class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">
                    Privacy policy
                </a>
                (including practices aligned with the Data Privacy Act of 2012 of the Philippines, where relevant).
            </p>

            <h2>Governing law &amp; disputes</h2>
            <p>
                These Terms are governed by the laws of the Republic of the Philippines, without regard to conflict-of-law
                principles, subject to any mandatory rights you may have as a consumer. Courts in the Philippines shall
                have jurisdiction, unless mandatory law requires otherwise.
            </p>

            <h2>Contact</h2>
            <p>
                Questions about these Terms? Email us at
                <a
                    href="mailto:{{ config('data_privacy.contact_email') }}"
                    class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                >
                    {{ config('data_privacy.contact_email') }}
                </a>.
            </p>
        </div>

        @include('partials.legal-pages-nav')
    </article>
</div>
