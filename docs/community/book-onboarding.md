# Book an Onboarding Session

Let our team walk you through setting up your school on KlassApp. An ambassador will reach out to confirm your slot.

<form id="onboarding-booking" action="/api/onboarding/book" method="POST" style="max-width: 560px; margin: 24px 0;">
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

    <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">School Name <span style="color: #EF4444;">*</span></label>
        <input type="text" name="school_name" required
               style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">Your Name <span style="color: #EF4444;">*</span></label>
            <input type="text" name="contact_name" required
                   style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">Phone Number <span style="color: #EF4444;">*</span></label>
            <input type="tel" name="phone" required placeholder="+256..."
                   style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
        </div>
    </div>

    <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">Email Address <span style="color: #EF4444;">*</span></label>
        <input type="email" name="email" required
               style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
    </div>

    <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">School Type</label>
        <select name="school_type"
                style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box; background: white;">
            <option value="">Select...</option>
            <option value="primary">Primary</option>
            <option value="secondary">Secondary</option>
            <option value="mixed">Primary & Secondary (Mixed)</option>
            <option value="other">Other</option>
        </select>
    </div>

    <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">Preferred Date &amp; Time</label>
        <input type="datetime-local" name="preferred_time"
               style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
    </div>

    <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px;">Anything to prepare?</label>
        <textarea name="notes" rows="3" placeholder="Number of students, classes, specific needs..."
                  style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; box-sizing: border-box; resize: vertical;"></textarea>
    </div>

    <button type="submit"
            style="width: 100%; padding: 12px; background: #22C55E; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: background 0.2s;"
            onmouseover="this.style.background='#16A34A'" onmouseout="this.style.background='#22C55E'">
        Request Onboarding Session
    </button>
</form>

<div id="booking-success" style="display: none; max-width: 560px; padding: 24px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; text-align: center;">
    <div style="font-size: 40px; margin-bottom: 8px;">🎉</div>
    <h3 style="font-family: 'Bricolage Grotesque', sans-serif; font-weight: 600; margin: 0 0 4px;">Booking Received!</h3>
    <p style="color: #166534; font-size: 14px; margin: 0;">Our team will contact you shortly to confirm your session.</p>
</div>

<script>
    document.getElementById('onboarding-booking').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = e.target;
        var data = new FormData(form);
        try {
            var res = await fetch(form.action, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                form.style.display = 'none';
                document.getElementById('booking-success').style.display = 'block';
            } else {
                var err = await res.json();
                alert('Something went wrong: ' + (err.message || 'Please try again.'));
            }
        } catch(e) {
            alert('Network error. Please check your connection and try again.');
        }
    });
</script>
