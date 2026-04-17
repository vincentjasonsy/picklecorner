<div>
    @include('partials.legal-page-hero', [
        'title' => 'Privacy policy',
        'subtitle' => 'This notice describes how we handle personal data when you use ' . config('app.name') . ', in line with the Data Privacy Act of 2012 (Republic Act No. 10173) of the Philippines and issuances of the National Privacy Commission (NPC).',
        'meta' => 'Policy version ' .
            config('data_privacy.policy_version') .
            ', effective ' .
            \Illuminate\Support\Carbon::parse(config('data_privacy.policy_effective_date'))->format('F j, Y') .
            '.',
    ])

    <article class="mx-auto max-w-3xl px-4 py-12 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300 sm:px-6 lg:px-8">
        <div class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-display prose-h2:mt-10 prose-h2:text-lg prose-h2:font-bold prose-h2:uppercase prose-h2:tracking-wide">
            <h2>Who we are</h2>
            <p>
                {{ config('app.name') }} (“we”, “us”) operates this website and related services for court booking,
                venue tools, and related features. For questions about this policy or your personal data, contact us at
                <a
                    href="mailto:{{ config('data_privacy.contact_email') }}"
                    class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                >
                    {{ config('data_privacy.contact_email') }}
                </a>.
            </p>

            <h2>Personal data we collect</h2>
            <p>Depending on how you use the service, we may process categories such as:</p>
            <ul>
                <li>
                    <strong>Account &amp; identity:</strong> name, email address, password (stored securely), account type,
                    and similar registration details.
                </li>
                <li>
                    <strong>Bookings &amp; activity:</strong> reservations, courts, venues, session times, and related
                    operational records needed to run the platform.
                </li>
                <li>
                    <strong>Communications:</strong> messages you send us (for example through contact forms) and
                    service-related emails (confirmations, reminders, security notices).
                </li>
                <li>
                    <strong>Optional marketing:</strong> where you separately agree, we may send newsletters, product
                    updates, or promotional emails. You can withdraw that consent at any time.
                </li>
                <li>
                    <strong>Technical data:</strong> IP address, device/browser type, and similar logs used for security,
                    abuse prevention, and service improvement.
                </li>
            </ul>

            <h2>Why we process your data (purposes)</h2>
            <p>We process personal data for purposes that include:</p>
            <ul>
                <li>Creating and managing your account;</li>
                <li>Providing booking, venue, desk, coach, and member features you request;</li>
                <li>Communicating about your bookings and account (including transactional messages);</li>
                <li>Complying with law and responding to lawful requests;</li>
                <li>Protecting the security and integrity of the service;</li>
                <li>
                    Sending optional marketing or promotional communications <strong>only if you have given consent</strong>
                    for that specific purpose.
                </li>
            </ul>
            <p>
                We aim to collect only what is necessary and proportionate for these purposes (data minimization), consistent
                with NPC guidance.
            </p>

            <h2>Legal bases (Philippines)</h2>
            <p>
                Under the DPA, processing must be justified. Depending on the activity, we rely on appropriate grounds such
                as your <strong>consent</strong> (for example registration and optional marketing), performance of a
                <strong>contract</strong> or steps prior to it, <strong>legitimate interests</strong> (for example fraud
                prevention and service security, balanced against your rights), or <strong>legal obligation</strong> where
                applicable.
            </p>

            <h2>How long we keep data</h2>
            <p>
                We retain personal data only as long as needed for the purposes above, including to resolve disputes,
                enforce agreements, and meet legal, accounting, or reporting requirements. Retention periods vary by data
                type; demo or trial accounts may be deleted automatically as described at registration.
            </p>

            <h2>Sharing and disclosure</h2>
            <p>
                We may share data with venue operators and service providers who help us run the platform (for example
                hosting or email delivery), under appropriate confidentiality and security arrangements. We may disclose
                information if required by law or to protect rights, safety, and security.
            </p>
            <p>
                If personal data is transferred outside the Philippines, we take steps to ensure protection consistent with
                the DPA and applicable NPC circulars.
            </p>

            <h2>Your rights as a data subject</h2>
            <p>Subject to applicable law, you may have the right to:</p>
            <ul>
                <li>Be informed about processing (this policy is part of that transparency);</li>
                <li>Access the personal data we hold about you;</li>
                <li>Have inaccurate or incomplete data corrected;</li>
                <li>Object to processing in certain cases, or withdraw consent where processing is consent-based;</li>
                <li>Request blocking, removal, or destruction where warranted;</li>
                <li>Data portability in appropriate circumstances;</li>
                <li>Lodge a complaint with the National Privacy Commission of the Philippines.</li>
            </ul>
            <p>
                To exercise these rights, email
                <a
                    href="mailto:{{ config('data_privacy.contact_email') }}"
                    class="font-semibold text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400"
                >
                    {{ config('data_privacy.contact_email') }}
                </a>
                . We may need to verify your identity before responding.
            </p>

            <h2>Security</h2>
            <p>
                We implement reasonable organizational, physical, and technical safeguards to protect personal data against
                unauthorized access, alteration, disclosure, or destruction. No online service can guarantee absolute
                security.
            </p>

            <h2>Cookies and similar technologies</h2>
            <p>
                We may use cookies or similar technologies needed for session management, preferences (such as theme),
                security, and analytics. You can control cookies through your browser settings.
            </p>

            <h2>Children</h2>
            <p>
                Our services are not directed at children without appropriate guardian involvement. If you believe we have
                collected data from a minor inappropriately, contact us and we will take appropriate steps.
            </p>

            <h2>Changes</h2>
            <p>
                We may update this policy from time to time. Material changes will be reflected here with an updated date
                and, where required, additional notice. Continued use after changes may constitute acceptance where
                permitted by law.
            </p>
        </div>

        @include('partials.legal-pages-nav')
    </article>
</div>
