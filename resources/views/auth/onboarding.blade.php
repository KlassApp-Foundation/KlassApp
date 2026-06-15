<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Welcome to KlassApp — Complete your school setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .onboarding-card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        }

        .onboarding-logo {
            display: block;
            margin: 0 auto 8px;
            height: 32px;
        }

        .onboarding-badge {
            display: inline-block;
            background: #dcfce7;
            color: #16a34a;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .onboarding-title {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            text-align: center;
        }

        .onboarding-subtitle {
            font-size: 14px;
            color: #64748b;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .onboarding-subtitle strong {
            color: #1e6fd9;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 11px 14px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
            outline: none;
        }

        .form-input:focus,
        .form-select:focus {
            border-color: #1e6fd9;
            box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.12);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M6 8L1 3h10z' fill='%2394a3b8'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }

        .form-submit {
            width: 100%;
            padding: 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;
            font-weight: 700;
            background: #22c55e;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
            margin-top: 6px;
        }

        .form-submit:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.18);
        }

        .form-submit:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
        }

        .form-skip {
            display: block;
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.18s ease;
        }

        .form-skip:hover {
            color: #64748b;
        }

        .onboarding-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .onboarding-step {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .onboarding-step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e2e8f0;
        }

        .onboarding-step-dot.active {
            background: #22c55e;
            width: 10px;
            height: 10px;
        }
    </style>
</head>
<body>
    <div class="onboarding-card">
        <img src="{{ asset('images/klassapp-logo-primary.svg') }}" alt="KlassApp" class="onboarding-logo" onerror="this.style.display='none'">

        <div style="text-align: center;">
            <span class="onboarding-badge">Google Sign-in</span>
        </div>

        <h1 class="onboarding-title">Welcome to KlassApp, {{ explode(' ', $googleName)[0] ?? $googleName }}! 👋</h1>
        <p class="onboarding-subtitle">
            Your Google account <strong>{{ $email }}</strong> is connected.<br>
            Just a few more details to set up your school.
        </p>

        <div class="onboarding-step">
            <span class="onboarding-step-dot active"></span>
            <span class="onboarding-step-dot"></span>
            <span class="onboarding-step-dot"></span>
        </div>

        @if ($errors->any())
            <div class="onboarding-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('failmessage'))
            <div class="onboarding-error">
                <p>{{ session('failmessage') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ url('/welcome') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="school_name">School Name</label>
                <input type="text" name="school_name" id="school_name" class="form-input"
                       placeholder="e.g. Green Valley Academy"
                       value="{{ old('school_name') }}"
                       required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="country">Country</label>
                <select name="country" id="country" class="form-select" required>
                    <option value="" disabled {{ old('country') ? '' : 'selected' }}>Select your country</option>
                    <option value="Uganda" {{ old('country') == 'Uganda' ? 'selected' : '' }}>Uganda</option>
                    <option value="Kenya" {{ old('country') == 'Kenya' ? 'selected' : '' }}>Kenya</option>
                    <option value="Tanzania" {{ old('country') == 'Tanzania' ? 'selected' : '' }}>Tanzania</option>
                    <option value="Rwanda" {{ old('country') == 'Rwanda' ? 'selected' : '' }}>Rwanda</option>
                    <option value="Nigeria" {{ old('country') == 'Nigeria' ? 'selected' : '' }}>Nigeria</option>
                    <option value="Ghana" {{ old('country') == 'Ghana' ? 'selected' : '' }}>Ghana</option>
                    <option value="South Africa" {{ old('country') == 'South Africa' ? 'selected' : '' }}>South Africa</option>
                    <option value="Other" {{ old('country') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="mobile_no">Mobile Number</label>
                <input type="tel" name="mobile_no" id="mobile_no" class="form-input"
                       placeholder="e.g. +256 7XX XXXXXX"
                       value="{{ old('mobile_no') }}"
                       required>
            </div>

            <div class="form-group">
                <label class="form-label" for="student_size">Approximate Number of Students</label>
                <select name="student_size" id="student_size" class="form-select" required>
                    <option value="" disabled {{ old('student_size') ? '' : 'selected' }}>Select range</option>
                    <option value="Under 100 students" {{ old('student_size') == 'Under 100 students' ? 'selected' : '' }}>Under 100 students</option>
                    <option value="100-300 students" {{ old('student_size') == '100-300 students' ? 'selected' : '' }}>100-300 students</option>
                    <option value="300-500 students" {{ old('student_size') == '300-500 students' ? 'selected' : '' }}>300-500 students</option>
                    <option value="500+ students" {{ old('student_size') == '500+ students' ? 'selected' : '' }}>500+ students</option>
                </select>
            </div>

            <button type="submit" class="form-submit">Complete Setup &amp; Enter Dashboard</button>
        </form>

        <a href="{{ url('/admin/dashboard') }}" class="form-skip">I'll do this later in School Settings</a>
    </div>
</body>
</html>
