
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KlassApp</title>
    <!-- shadcn-classless CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fordus/shadcn-classless@main/dist/shadcn-classless.css">
    <!-- Google Fonts: Inter & Bricolage Grotesque -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Bricolage+Grotesque:wght@600;700&display=swap" rel="stylesheet">
    <style>
    :root {
      --primary: #1E6FD9;
      --primary-foreground: #FFFFFF;
      --success: #22C55E;
      --background: #F8FAFC;
      --foreground: #0F172A;
      --muted: #94A3B8;
      --border: #E2E8F0;
      --card: #FFFFFF;
      --card-foreground: #0F172A;
    }
    body {
      background: var(--background);
      color: var(--foreground);
      font-size: 15px;
      line-height: 1.7;
      font-family: 'Inter', 'Bricolage Grotesque', system-ui, sans-serif;
    }
    .container {
      max-width: 1120px;
      margin-left: auto;
      margin-right: auto;
      padding-left: 1rem;
      padding-right: 1rem;
    }
    a, button, .btn-primary { color: var(--primary); }
    .btn-primary, button[type="submit"] { background: var(--primary); border-color: var(--primary); color: var(--primary-foreground); }
    .btn-primary:hover, button[type="submit"]:hover { background: #1558b5; }
    h1, h2, h3, h4 { font-family: 'Bricolage Grotesque', 'Inter', sans-serif; }
    .hero {
      padding: 64px 0 32px 0;
      text-align: center;
    }
    .hero-title {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    .hero-sub {
      font-size: 1.25rem;
      color: var(--muted);
      margin-bottom: 2rem;
    }
    .hero-actions {
      margin-bottom: 2.5rem;
    }
    .features, .steps, .plans, .testimonials, .faq-list {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      justify-content: center;
    }
    .feature, .step, .plan, .testimonial, .faq-item {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.5rem;
      flex: 1 1 280px;
      min-width: 260px;
      max-width: 340px;
      box-shadow: 0 2px 8px rgba(30,111,217,0.04);
    }
    .plan.highlight {
      border: 2px solid var(--primary);
      box-shadow: 0 4px 16px rgba(30,111,217,0.08);
    }
    .footer {
      background: #0D1526;
      color: #fff;
      padding: 48px 0 24px 0;
      margin-top: 48px;
    }
    .footer a { color: rgba(255,255,255,0.7); text-decoration: none; margin: 0 8px; }
    .footer .container { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 2rem; }
    .footer .brand { font-weight: 700; font-size: 1.2rem; }
    </style>
</head>
<body>
    <nav style="padding: 1.5rem 0; border-bottom: 1px solid var(--border); background: var(--card);">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between;">
            <div class="brand" style="display: flex; align-items: center; gap: 0.75rem;">
                <img src="{{ asset('images/klassapp-logo-dark.png') }}" alt="KlassApp" style="height: 38px; width:auto;">
                <span>KlassApp</span>
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <a href="#features">Features</a>
                <a href="#how">How it works</a>
                <a href="#pricing">Pricing</a>
                <a href="#testimonials">Testimonials</a>
                <a class="btn btn-primary" href="{{ url('/register') }}">Join</a>
                <a class="btn" href="{{ route('login') }}">Portal</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <h1 class="hero-title">Stop managing chaos. Start leading with clarity.</h1>
            <div class="hero-sub">One platform to run your entire school, smarter, faster, and more securely than ever before.</div>
            <div class="hero-actions">
                <a class="btn btn-primary" href="{{ url('/register') }}">Join</a>
                <a class="btn" href="{{ route('login') }}">Portal</a>
                <a class="btn" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener">Book a demo</a>
            </div>
            <p>KlassApp unifies your entire school ecosystem into one intelligent, future-ready platform that transforms how modern schools connect, operate, and thrive.</p>
        </div>
    </header>

    <section id="features" style="padding:36px 0;">
        <div class="container">
            <h2>Features</h2>
            <div class="features">
                <div class="feature"><strong>Real-time Messaging</strong><div>Secure group and individual messaging with read receipts and scheduling.</div></div>
                <div class="feature"><strong>Attendance Tracking</strong><div>Fast check-ins, reporting, and absence alerts to parents automatically.</div></div>
                <div class="feature"><strong>Parent Portal</strong><div>A simple, secure view for parents to follow progress and messages.</div></div>
                <div class="feature"><strong>Homework &amp; Assignments</strong><div>Create, grade, and publish assignments with deadlines and attachments.</div></div>
                <div class="feature"><strong>Push Notifications</strong><div>Timely alerts for urgent messages and changes.</div></div>
                <div class="feature"><strong>Analytics Dashboard</strong><div>Actionable insights on attendance, engagement and communications.</div></div>
            </div>
        </div>
    </section>

    <section id="how" style="padding:36px 0;">
        <div class="container">
            <h2>How it works</h2>
            <div class="steps">
                <div class="step"><strong>1. Sign Up</strong><div>Create your school account and set basic preferences.</div></div>
                <div class="step"><strong>2. Invite Your School</strong><div>Import student lists and invite staff and parents.</div></div>
                <div class="step"><strong>3. Start Communicating</strong><div>Send announcements, take attendance and track engagement.</div></div>
            </div>
        </div>
    </section>

    <section id="about" style="padding:36px 0;">
        <div class="container">
            <h2>Built for schools, by educators</h2>
            <p>KlassApp is made to reduce administrative overhead and let teachers focus on what matters — teaching.</p>
            <div style="display:flex; gap:2rem; justify-content:center;">
                <div style="text-align:center;"><div style="font-size:2rem;font-weight:700;">500+</div><div>Schools</div></div>
                <div style="text-align:center;"><div style="font-size:2rem;font-weight:700;">200,000+</div><div>Users</div></div>
                <div style="text-align:center;"><div style="font-size:2rem;font-weight:700;">98%</div><div>Satisfaction</div></div>
            </div>
        </div>
    </section>

    <section id="testimonials" style="padding:36px 0;">
        <div class="container">
            <h2>Testimonials</h2>
            <div class="testimonials">
                <div class="testimonial"><em>“KlassApp transformed our attendance workflow and saved our office hours every week.”</em><div><strong>Sarah</strong>, Headteacher, Lincoln Academy</div></div>
                <div class="testimonial"><em>“Parents love the quick updates and our teachers spend less time on admin.”</em><div><strong>Marcus</strong>, Principal, Greenfield School</div></div>
                <div class="testimonial"><em>“Reliable, secure and simple — our finance team now closes faster each month.”</em><div><strong>Aisha</strong>, Finance Officer, Ridgeview</div></div>
            </div>
        </div>
    </section>

    <section id="pricing" style="padding:36px 0;">
        <div class="container">
            <h2>Simple, honest pricing</h2>
            <div class="plans">
                <div class="plan">
                    <div style="font-weight:700;">Basic</div>
                    <div style="font-size:2rem;">$25 <span style="font-size:1rem;">/mo</span></div>
                    <div>Per school, billed monthly</div>
                    <ul style="margin-top:1rem;">
                        <li>Student &amp; class roster management</li>
                        <li>Attendance tracking</li>
                        <li>Teacher–parent messaging</li>
                        <li>Parent portal (read-only access)</li>
                        <li>Homework &amp; assignment posting</li>
                        <li>Basic grade entry</li>
                        <li>Class calendar &amp; scheduling</li>
                        <li>Email notifications</li>
                    </ul>
                </div>
                <div class="plan highlight">
                    <div style="font-weight:700;">Pro <span style="background:var(--primary);color:#fff;padding:2px 8px;border-radius:8px;font-size:0.9rem;vertical-align:middle;">Most popular</span></div>
                    <div style="font-size:2rem;">$35 <span style="font-size:1rem;">/mo</span></div>
                    <div>Per school, billed monthly</div>
                    <ul style="margin-top:1rem;">
                        <li>Push notifications</li>
                        <li>Advanced analytics &amp; reports</li>
                        <li>Bulk messaging &amp; announcements</li>
                        <li>Assignment submission &amp; grading workflow</li>
                        <li>Progress reports &amp; report cards</li>
                        <li>File &amp; document sharing</li>
                        <li>Message read receipts</li>
                        <li>Two-way parent portal interaction</li>
                    </ul>
                </div>
                <div class="plan">
                    <div style="font-weight:700;">Enterprise</div>
                    <div style="font-size:1.2rem;">Custom pricing</div>
                    <div>Tailored to your institution's needs</div>
                    <ul style="margin-top:1rem;">
                        <li>SAML SSO / Active Directory integration</li>
                        <li>Custom integrations (SIS, LMS, ERP)</li>
                        <li>Dedicated account manager &amp; support</li>
                        <li>Role-based admin access controls</li>
                        <li>FERPA &amp; GDPR compliance tools</li>
                        <li>Custom branding &amp; white-labeling</li>
                        <li>API access &amp; audit logs</li>
                        <li>SLA guarantee</li>
                        <li>Custom-branded parent portal</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section style="padding:36px 0; text-align:center;">
        <div class="container">
            <h2>Ready to lead your school into the future?</h2>
            <p>Set up takes minutes. No training required. Your whole school connected from day one.</p>
            <div style="margin: 2rem 0;">
                <a class="btn btn-primary" href="{{ url('/register') }}">Join</a>
                <a class="btn" href="{{ route('login') }}">Portal</a>
                <a class="btn" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener">Book a demo</a>
            </div>
            <div style="color:var(--muted);font-size:1rem;">Free 30-day trial · No credit card required · Cancel anytime</div>
        </div>
    </section>

    <section id="faq" style="padding:36px 0;">
        <div class="container">
            <h2>Frequently asked questions</h2>
            <div class="faq-list">
                <div class="faq-item"><strong>How long is the free trial?</strong><div>All new accounts receive a 30-day free trial with full Pro features.</div></div>
                <div class="faq-item"><strong>Is my data secure?</strong><div>Yes — we use encryption in transit and at rest, role-based access and audit logs.</div></div>
                <div class="faq-item"><strong>Can I import my students?</strong><div>Yes — CSV import or SIS integrations are available during onboarding.</div></div>
                <div class="faq-item"><strong>Do you provide support?</strong><div>All plans include email support; Enterprise includes SLA and phone support.</div></div>
                <div class="faq-item"><strong>Can I cancel anytime?</strong><div>Yes — monthly plans can be cancelled with immediate effect.</div></div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="brand">
                <img src="{{ asset('images/klassapp-logo-dark.png') }}" alt="KlassApp" style="height:32px;width:auto;vertical-align:middle;"> KlassApp
            </div>
            <div>
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
                <a href="#">Contact</a>
            </div>
            <div>
                <a href="#" title="Facebook">f</a>
                <a href="#" title="X">𝕏</a>
                <a href="#" title="Instagram">◉</a>
                <a href="#" title="LinkedIn">in</a>
            </div>
        </div>
        <div style="text-align:center;margin-top:24px;color:rgba(255,255,255,0.55);font-size:14px;">© {{ date('Y') }} KlassApp. All rights reserved.</div>
    </footer>
</body>
</html>