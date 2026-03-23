{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.minimal')

@section('content')
<?php $logo = file_exists(public_path('images/klassapp-logo-dark.png')) ? asset('images/klassapp-logo-dark.png') : (file_exists(public_path('images/klassapp-logo-primary.png')) ? asset('images/klassapp-logo-primary.png') : (file_exists(public_path('favicon/klassapp-favicon.png')) ? asset('favicon/klassapp-favicon.png') : (file_exists(public_path('uploads/klassapp_assets.png')) ? asset('uploads/klassapp_assets.png') : asset('uploads/demologo.png')))); ?>

<nav class="ka-nav">
	<div class="ka-container">
		<div class="ka-brand">
			<img src="{{ $logo }}" alt="KlassApp" class="ka-brand-logo">
		</div>

		<div class="ka-links" id="kaLinks">
			<a href="#features">Features</a>
			<a href="#how">How it works</a>
			<a href="#pricing">Pricing</a>
			<a href="#testimonials">Testimonials</a>
		</div>
		<div class="ka-nav-actions">
			<a class="ka-cta" href="{{ url('/register') }}" onmouseenter="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onmouseleave="this.style.transform='';this.style.background='';this.style.boxShadow='';" onfocus="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onblur="this.style.transform='';this.style.background='';this.style.boxShadow='';">Join</a>
			<a class="ka-portal" href="{{ route('login') }}" onmouseenter="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onmouseleave="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';" onfocus="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onblur="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';">Portal</a>
			<div class="ka-hamburger" id="kaHamburger" aria-label="menu">☰</div>
		</div>
	</div>
</nav>

<header class="ka-hero ka-container">
	<video class="ka-hero-video" autoplay muted loop playsinline poster="{{ asset('images/african-school-exterior.dim_1200x600.jpg') }}" aria-hidden="true">
		<source src="{{ asset('images/KlassApp_Hero_Video.mp4') }}" type="video/mp4">
		<source src="{{ asset('images/school-environment.mp4') }}" type="video/mp4">
	</video>
	<div class="ka-hero-overlay" aria-hidden="true"></div>
	<div class="ka-hero-inner">
	<div class="ka-hero-left">
		<div class="ka-hero-panel">
			<div class="ka-audience ka-animate" role="tablist" aria-label="Audience selector">
				<button class="active" data-audience="admin">Administrators &amp; Principals</button>
				<button data-audience="teacher">Teachers</button>
				<button data-audience="parent">Parents</button>
			</div>
			<h1 class="ka-animate ka-type-target" id="heroTitle">Stop managing chaos. Start leading with clarity.</h1>
			<div class="ka-hero-sub ka-animate ka-type-target" id="heroSubtitle">One platform to run your entire school, smarter, faster, and more securely than ever before.</div>

			<p class="lead ka-animate">KlassApp unifies your entire school ecosystem into one intelligent, future-ready platform that transforms how modern schools connect, operate, and thrive.</p>

			<div class="ka-platform-note ka-animate">
				<span class="ka-platform-label">Platform capabilities</span>
				<div class="ka-platform-phrase-wrap">
					<span id="kaPlatformPhrase" class="ka-platform-phrase">AI-powered automation</span>
				</div>
			</div>

			<div class="buttons ka-animate">
				<a class="ka-cta" href="{{ url('/register') }}" onmouseenter="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onmouseleave="this.style.transform='';this.style.background='';this.style.boxShadow='';" onfocus="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onblur="this.style.transform='';this.style.background='';this.style.boxShadow='';">Join</a>
				<a class="ka-portal" href="{{ route('login') }}" onmouseenter="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onmouseleave="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';" onfocus="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onblur="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';">Portal</a>
				<a class="ka-ghost" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer">Book a demo</a>
			</div>
		</div>

	</div>

	</div>
</header>

<!-- FEATURES -->
<section id="features" class="ka-section ka-texture-shell" style="padding:36px 0;">
	<div class="ka-container">
	<h2 class="ka-animate">Features</h2>
	<p class="ka-animate ka-features-statement">Designed for schools — features that replace several siloed systems with <span>one easy-to-manage platform</span>.</p>
	<div class="ka-features-intro ka-animate">
		<div class="ka-features-media">
			<img src="{{ asset('images/african-student-tech.dim_800x800.jpg') }}" alt="Students learning in a computer lab" loading="lazy">
		</div>
		<div class="ka-features-context">
			<div class="ka-features-copy">
				<h3>Everything your school team needs to stay <span>coordinated and productive</span>.</h3>
				<p>From communication and attendance to progress tracking and analytics, these features are designed to keep day-to-day school operations simple and connected.</p>
			</div>
			<div class="ka-feature-badges">
				<span>Easy to onboard</span>
				<span>Simple daily workflows</span>
				<span>Fast for every role</span>
			</div>
			<ul class="ka-feature-highlights" aria-label="Key platform highlights">
				<li><span class="check" aria-hidden="true">✓</span><span>Real-time sync across all roles</span></li>
				<li><span class="check" aria-hidden="true">✓</span><span>Works on any device, anywhere</span></li>
				<li><span class="check" aria-hidden="true">✓</span><span>Up and running in under a day</span></li>
			</ul>
		</div>
	</div>
	<div class="ka-features" style="margin-top:18px;">
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v10H4z"/><path d="M8 19h8"/></svg></span><strong>Real-time Messaging</strong><div>Secure group and individual messaging with read receipts and scheduling.</div></div>
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span><strong>Attendance Tracking</strong><div>Fast check-ins, reporting, and absence alerts to parents automatically.</div></div>
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span><strong>Parent Portal</strong><div>A simple, secure view for parents to follow progress and messages.</div></div>
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M8 10h8M8 14h6"/></svg></span><strong>Homework &amp; Assignments</strong><div>Create, grade, and publish assignments with deadlines and attachments.</div></div>
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18"/><path d="M4 9h16"/><path d="m6 15 3-3 2 2 5-5 2 2"/></svg></span><strong>Push Notifications</strong><div>Timely alerts for urgent messages and changes.</div></div>
		<div class="ka-feature ka-animate"><span class="feat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19h16"/><path d="M7 16V9"/><path d="M12 16V5"/><path d="M17 16v-3"/></svg></span><strong>Analytics Dashboard</strong><div>Actionable insights on attendance, engagement and communications.</div></div>
	</div>
	</div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="ka-section ka-texture-shell" style="padding:36px 0;">
	<div class="ka-container">
	<h2 class="ka-animate">How it works</h2>
	<div class="ka-steps" style="margin-top:18px;">
		<div class="ka-step ka-animate"><div class="step-num">1</div><h4>Sign Up</h4><p>Create your school account and set basic preferences.</p></div>
		<div class="ka-step ka-animate"><div class="step-num">2</div><h4>Invite Your School</h4><p>Import student lists and invite staff and parents.</p></div>
		<div class="ka-step ka-animate"><div class="step-num">3</div><h4>Start Communicating</h4><p>Send announcements, take attendance and track engagement.</p></div>
	</div>
	</div>
</section>

<!-- ABOUT + STATS -->
<section id="about" class="ka-container" style="padding:36px 0;">
	<div class="ka-overline ka-animate">Impact</div>
	<h2 class="ka-animate">Built for schools, by educators</h2>
	<p class="ka-animate" style="color:rgba(15,23,42,0.7);">KlassApp is made to reduce administrative overhead and let teachers focus on what matters — teaching.</p>
	<div class="ka-stats ka-animate">
		<div class="ka-stat"><div class="num" data-target="500">0</div><div class="label">Schools</div></div>
		<div class="ka-stat"><div class="num" data-target="200000">0</div><div class="label">Users</div></div>
		<div class="ka-stat"><div class="num" data-target="98">0</div><div class="label">Satisfaction (%)</div></div>
	</div>
</section>

<!-- TESTIMONIALS -->
<section id="trusted-schools" class="ka-section ka-trusted-section" style="padding:28px 0;">
	<div class="ka-container">
		<div class="ka-overline ka-animate">Trusted by schools across Africa</div>
		<div class="ka-trusted-row ka-animate">
			<div class="badge">Lincoln Academy</div>
			<div class="badge">Greenfield School</div>
			<div class="badge">Ridgeview Collegiate</div>
			<div class="badge">Maple Grove</div>
			<div class="badge">Hillcrest Prep</div>
			<div class="badge">Seaview School</div>
		</div>
	</div>
</section>

<!-- TESTIMONIALS -->
<section id="testimonials" class="ka-section ka-texture-shell" style="padding:36px 0;">
	<div class="ka-container">
	<h2 class="ka-animate">Testimonials</h2>
	<div class="ka-testimonials" style="margin-top:18px;">
		<div class="ka-testimonial ka-animate"><div class="quote-mark">“</div><p>“KlassApp transformed our attendance workflow and saved our office hours every week.”</p><div class="t-meta"><span class="avatar">SH</span><div><strong>Sarah</strong><small>Headteacher, Lincoln Academy</small></div></div></div>
		<div class="ka-testimonial ka-animate"><div class="quote-mark">“</div><p>“Parents love the quick updates and our teachers spend less time on admin.”</p><div class="t-meta"><span class="avatar">MP</span><div><strong>Marcus</strong><small>Principal, Greenfield School</small></div></div></div>
		<div class="ka-testimonial ka-animate"><div class="quote-mark">“</div><p>“Reliable, secure and simple — our finance team now closes faster each month.”</p><div class="t-meta"><span class="avatar">AF</span><div><strong>Aisha</strong><small>Finance Officer, Ridgeview</small></div></div></div>
	</div>
	</div>
</section>

<!-- PRICING -->
<section id="pricing" class="ka-section ka-texture-shell" style="padding:36px 0;">
	<div class="ka-container">
	<h2 class="ka-animate">Simple, honest pricing</h2>
	<p class="ka-animate" style="color:rgba(15,23,42,0.7); margin-top:6px;">Everything your classroom needs — pick the plan that fits.</p>
	<div class="ka-pricing" style="margin-top:24px;">
		<div class="ka-plan ka-animate">
			<div class="plan-head">
				<div class="plan-title">Basic</div>
				<div class="plan-price"><span class="price-amount">$25</span><span class="price-unit">/mo</span></div>
				<div class="plan-sub">Per school, billed monthly</div>
			</div>
			<div class="plan-action"><a class="btn-outline" href="#">Get started ↗</a></div>
			<hr>
			<div class="plan-list">
				<div class="plan-list-title">Core tools</div>
				<ul>
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
		</div>

		<div class="ka-plan highlight ka-animate">
			<div class="plan-badge">Most popular</div>
			<div class="plan-head">
				<div class="plan-title">Pro</div>
				<div class="plan-price"><span class="price-amount">$35</span><span class="price-unit">/mo</span></div>
				<div class="plan-sub">Per school, billed monthly</div>
			</div>
			<div class="plan-action"><a class="btn-primary" href="#">Get started ↗</a></div>
			<hr>
			<div class="plan-list">
				<div class="plan-list-title">Power features</div>
				<ul>
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
		</div>

		<div class="ka-plan ka-animate ka-enterprise">
			<div class="plan-head">
				<div class="plan-title">Enterprise</div>
				<div class="plan-price"><div class="price-amount">Custom pricing</div></div>
				<div class="plan-sub">Tailored to your institution's needs</div>
			</div>
			<div class="plan-action"><a class="btn-outline" href="#">Contact sales ↗</a></div>
			<hr>
			<div class="plan-list">
				<div class="plan-list-title">Everything in Pro, plus:</div>
				<ul>
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
	</div>
</section>

<!-- PRICING CTA -->
<section class="ka-pricing-cta">
	<div class="ka-container">
		<h2 class="ka-animate">Ready to lead your school into the future?</h2>
		<p class="ka-animate">Set up takes minutes. No training required. Your whole school connected from day one.</p>
		<div class="ka-pricing-cta-actions ka-animate">
			<a class="ka-cta" href="{{ url('/register') }}" onmouseenter="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onmouseleave="this.style.transform='';this.style.background='';this.style.boxShadow='';" onfocus="this.style.transform='translateY(-3px) scale(1.02)';this.style.background='linear-gradient(120deg,#4ade80 0%,#22c55e 55%,#16a34a 100%)';this.style.boxShadow='0 12px 24px rgba(34,197,94,0.34)';" onblur="this.style.transform='';this.style.background='';this.style.boxShadow='';">Join</a>
			<a class="ka-portal" href="{{ route('login') }}" onmouseenter="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onmouseleave="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';" onfocus="this.style.animationPlayState='paused, paused';this.style.background='linear-gradient(120deg,#0f3fb8 0%,#1d4ed8 46%,#0ea5e9 100%)';this.style.boxShadow='0 14px 28px rgba(29,78,216,0.5)';" onblur="this.style.animationPlayState='running, running';this.style.background='';this.style.boxShadow='';">Portal</a>
			<a class="ka-ghost" href="https://calendly.com/moemucu/talk-to-mucu" target="_blank" rel="noopener noreferrer">Book a demo</a>
		</div>
		<div class="ka-pricing-cta-note ka-animate">Free 30-day trial · No credit card required · Cancel anytime</div>
	</div>
</section>

<!-- FAQ -->
<section id="faq" class="ka-section ka-texture-shell" style="padding:36px 0;">
	<div class="ka-container">
	<h2 class="ka-animate">Frequently asked questions</h2>
	<div class="ka-faq" style="margin-top:12px;">
		<div class="item"><button aria-expanded="false"><span>How long is the free trial?</span><span class="icon">+</span></button><div class="panel">All new accounts receive a 30-day free trial with full Pro features.</div></div>
		<div class="item"><button aria-expanded="false"><span>Is my data secure?</span><span class="icon">+</span></button><div class="panel">Yes — we use encryption in transit and at rest, role-based access and audit logs.</div></div>
		<div class="item"><button aria-expanded="false"><span>Can I import my students?</span><span class="icon">+</span></button><div class="panel">Yes — CSV import or SIS integrations are available during onboarding.</div></div>
		<div class="item"><button aria-expanded="false"><span>Do you provide support?</span><span class="icon">+</span></button><div class="panel">All plans include email support; Enterprise includes SLA and phone support.</div></div>
		<div class="item"><button aria-expanded="false"><span>Can I cancel anytime?</span><span class="icon">+</span></button><div class="panel">Yes — monthly plans can be cancelled with immediate effect.</div></div>
	</div>
	</div>
</section>

<!-- CTA BANNER -->
<div class="ka-cta-banner ka-animate">
	<div class="ka-cta-content">
		<h3>Stay in the loop</h3>
		<p>Get product updates, school success stories, and important announcements in your inbox.</p>
		<form action="#" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
			<input type="email" required placeholder="you@school.edu"> <button>Subscribe</button>
		</form>
	</div>
</div>

<!-- FOOTER -->
<footer style="padding:48px 24px;border-top:1px solid rgba(255,255,255,0.08);background:#0D1526;">
	<div class="ka-container" style="display:grid;grid-template-columns:1fr auto 1fr;gap:32px;align-items:start;max-width:1200px;">
		<div class="footer-brand"><img src="{{ $logo }}" alt="KlassApp" class="ka-brand-logo"><div class="footer-tagline">Smarter schools start here.</div></div>
		<div class="footer-links">
			<a href="#" style="color:rgba(255,255,255,0.55);text-decoration:none;">Terms</a>
			<a href="#" style="color:rgba(255,255,255,0.55);text-decoration:none;">Privacy</a>
			<a href="#" style="color:rgba(255,255,255,0.55);text-decoration:none;">Contact</a>
		</div>
		<div class="footer-socials">
			<a href="#" title="Facebook" style="color:rgba(255,255,255,0.5);text-decoration:none;">f</a>
			<a href="#" title="X" style="color:rgba(255,255,255,0.5);text-decoration:none;">𝕏</a>
			<a href="#" title="Instagram" style="color:rgba(255,255,255,0.5);text-decoration:none;">◉</a>
			<a href="#" title="LinkedIn" style="color:rgba(255,255,255,0.5);text-decoration:none;">in</a>
		</div>
	</div>
	<div style="text-align:center;margin-top:24px;color:rgba(255,255,255,0.55);font-size:14px;">© {{ date('Y') }} KlassApp. All rights reserved.</div>
</footer>

@push('scripts')
<script>
	// Mobile menu toggle
	var hamburger = document.getElementById('kaHamburger');
	if (hamburger) {
		hamburger.addEventListener('click', function(){
			var links = document.getElementById('kaLinks');
			if (links) links.classList.toggle('open');
		});
	}

	// Navbar scroll state
	window.addEventListener('scroll', function(){
		document.querySelector('.ka-nav').classList.toggle('scrolled', window.scrollY > 20);
	});

	// Force clear interaction state for CTA buttons so hover is always visible.
	function bindCtaInteractionState(selector) {
		document.querySelectorAll(selector).forEach(function(el){
			var isCta = el.classList.contains('ka-cta');
			var isPortal = el.classList.contains('ka-portal');
			
			var add = function(){ 
				el.classList.add('is-hovered');
				// Apply same hover effect to both Join and Portal: blue gradient, lift, glow
				el.style.background = 'linear-gradient(120deg, #0f3fb8 0%, #1d4ed8 46%, #0ea5e9 100%)';
				el.style.transform = 'translateY(-3px) scale(1.02)';
				el.style.boxShadow = '0 12px 24px rgba(34, 197, 94, 0.34)';
				
				if (isPortal) {
					el.style.animationPlayState = 'paused, paused';
				}
			};
			var remove = function(){ 
				el.classList.remove('is-hovered');
				el.style.background = '';
				el.style.transform = '';
				el.style.boxShadow = '';
				
				if (isPortal) {
					el.style.animationPlayState = 'running, running';
				}
			};
			var holdTimer = null;
			var hold = function(ms){
				add();
				if (holdTimer) window.clearTimeout(holdTimer);
				holdTimer = window.setTimeout(remove, ms || 900);
			};

			el.addEventListener('mouseenter', add);
			el.addEventListener('mouseleave', remove);
			el.addEventListener('focus', add);
			el.addEventListener('blur', remove);
			el.addEventListener('pointerenter', add);
			el.addEventListener('pointerleave', remove);
			el.addEventListener('touchstart', function(){ hold(1200); }, { passive: true });
			el.addEventListener('click', function(){ hold(900); });
		});
	}

	bindCtaInteractionState('a.ka-cta');
	bindCtaInteractionState('a.ka-portal');

	// Smooth scroll and active nav highlight
	document.querySelectorAll('nav a[href^="#"]').forEach(function(a){
		a.addEventListener('click', function(e){ e.preventDefault(); var id = this.getAttribute('href'); document.querySelector(id).scrollIntoView({behavior:'smooth',block:'start'}); });
	});

	// IntersectionObserver for fade-in-up and stat counters
	// If IntersectionObserver unsupported, reveal all immediately
	if (!('IntersectionObserver' in window)) {
		document.querySelectorAll('.ka-animate').forEach(function(el){ el.classList.add('visible'); });
	}
	var observer = new IntersectionObserver(function(entries){
		entries.forEach(function(entry){
			if(entry.isIntersecting){
				entry.target.classList.add('visible');
				if(entry.target.classList.contains('ka-stats')) startCounters();
				observer.unobserve(entry.target);
			}
		});
	}, {threshold:0.15});

	document.querySelectorAll('.ka-animate').forEach(function(el){ observer.observe(el); });

	function startCounters(){
		document.querySelectorAll('.ka-stat .num').forEach(function(el){
			var target = parseInt(el.getAttribute('data-target'),10) || 0; var duration=1500; var startTime=null;
			function step(timestamp){ if(!startTime) startTime=timestamp; var progress=timestamp-startTime; var pct=Math.min(progress/duration,1); el.textContent = Math.floor(pct*target).toLocaleString(); if(progress<duration) window.requestAnimationFrame(step); else el.textContent = target.toLocaleString(); }
			window.requestAnimationFrame(step);
		});
	}

	// FAQ accordion
	document.querySelectorAll('.ka-faq .item button').forEach(function(btn){
		btn.addEventListener('click', function(){
			var panel = this.nextElementSibling;
			var isOpen = this.getAttribute('aria-expanded') === 'true';

			document.querySelectorAll('.ka-faq .item button').forEach(function(b){
				b.setAttribute('aria-expanded', 'false');
				b.classList.remove('open');
			});
			document.querySelectorAll('.ka-faq .panel').forEach(function(p){
				p.style.maxHeight = null;
			});

			if (!isOpen) {
				this.setAttribute('aria-expanded', 'true');
				this.classList.add('open');
				panel.style.maxHeight = panel.scrollHeight + 'px';
			}
		});
	});

	// Make trusted strip pause on hover
	document.querySelectorAll('.ka-trusted').forEach(function(el){ el.addEventListener('mouseenter', ()=>{ el.querySelector('.ka-trusted-track').style.animationPlayState='paused'; }); el.addEventListener('mouseleave', ()=>{ el.querySelector('.ka-trusted-track').style.animationPlayState='running'; }); });

	// Hero audience toggle with fade-up animation and auto-rotation
	var audienceCopy = {
		admin: {
			title: 'Stop managing chaos. Start leading with clarity.',
			sub: 'One platform to run your entire school — smarter, faster, and more securely than ever before.'
		},
		teacher: {
			title: 'Spend less time on admin. More time doing what you love.',
			sub: 'KlassApp handles the noise so you can focus on what actually matters — teaching.'
		},
		parent: {
			title: 'Always in the loop. Never in the dark.',
			sub: 'Real-time updates on attendance, grades, and school communications — all in one place, built for busy parents.'
		}
	};
	var audienceButtons = Array.prototype.slice.call(document.querySelectorAll('.ka-audience button'));
	var audienceInterval = 14000;
	var audienceTimer = null;
	var heroAnimationToken = 0;

	function typeWriter(element, text, speed, token, callback) {
		element.textContent = '';
		element.classList.add('is-typing');
		var index = 0;

		function type() {
			if (token !== heroAnimationToken) return;
			if (index < text.length) {
				element.textContent += text.charAt(index);
				index++;
				window.setTimeout(type, speed);
				return;
			}

			window.setTimeout(function(){
				if (token !== heroAnimationToken) return;
				element.classList.remove('is-typing');
				if (callback) callback();
			}, 500);
		}

		type();
	}

	function animateHeroCopy(aud) {
		var title = document.getElementById('heroTitle');
		var sub = document.getElementById('heroSubtitle');
		if (!title || !sub || !audienceCopy[aud]) return;

		heroAnimationToken += 1;
		var currentToken = heroAnimationToken;

		title.classList.remove('hero-reveal', 'is-typing');
		sub.classList.remove('hero-reveal', 'is-typing');
		title.style.opacity = '0';
		title.style.transform = 'translateY(16px)';
		sub.style.opacity = '0';
		sub.style.transform = 'translateY(16px)';

		window.requestAnimationFrame(function(){
			title.classList.add('hero-reveal');
			sub.classList.add('hero-reveal');
			typeWriter(title, audienceCopy[aud].title, 56, currentToken, function(){
				typeWriter(sub, audienceCopy[aud].sub, 28, currentToken);
			});
		});
	}

	function setAudience(aud) {
		audienceButtons.forEach(function(b){
			b.classList.toggle('active', b.getAttribute('data-audience') === aud);
		});
		animateHeroCopy(aud);
	}

	function getActiveAudienceIndex() {
		return audienceButtons.findIndex(function(b){ return b.classList.contains('active'); });
	}

	function rotateAudience() {
		if (!audienceButtons.length) return;
		var currentIndex = getActiveAudienceIndex();
		if (currentIndex < 0) currentIndex = 0;
		var nextIndex = (currentIndex + 1) % audienceButtons.length;
		setAudience(audienceButtons[nextIndex].getAttribute('data-audience'));
	}

	function startAudienceAutoplay() {
		if (audienceTimer) {
			window.clearInterval(audienceTimer);
			audienceTimer = null;
		}
		if (!audienceButtons.length) return;
		audienceTimer = window.setInterval(rotateAudience, audienceInterval);
	}

	if (audienceButtons.length) {
		audienceButtons.forEach(function(btn){
			btn.addEventListener('click', function(){
				setAudience(this.getAttribute('data-audience'));
				startAudienceAutoplay();
			});
		});

		var activeButton = audienceButtons.find(function(b){ return b.classList.contains('active'); }) || audienceButtons[0];
		if (activeButton) setAudience(activeButton.getAttribute('data-audience'));
		startAudienceAutoplay();
	}

	// Rotating platform phrases
	var platformPhrases = [
		'AI-powered automation',
		'Blockchain-grade security',
		'Real-time communication',
		'Unbreakable data privacy',
		'Infinite scalability'
	];
	var phraseIndex = 0;
	var phraseEl = document.getElementById('kaPlatformPhrase');
	if (phraseEl) {
		phraseEl.classList.add('show');
		setInterval(function(){
			phraseEl.classList.remove('show');
			setTimeout(function(){
				phraseIndex = (phraseIndex + 1) % platformPhrases.length;
				phraseEl.textContent = platformPhrases[phraseIndex];
				phraseEl.classList.add('show');
			}, 220);
		}, 3000);
	}
</script>
@endpush

@endsection

 

