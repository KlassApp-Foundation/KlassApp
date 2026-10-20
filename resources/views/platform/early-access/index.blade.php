{{-- SPDX-License-Identifier: MIT --}}
{{-- Platform admins only: early access requests and school invites. Plain styling on purpose. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Early access requests</title>
  @vite(['resources/css/auth-preview.css'])
</head>
<body class="ap-body" style="padding:24px;">
  <main>
    <h1 class="ap-title">Early access requests</h1>
    <p><a class="ap-link" href="{{ route('platform.early-access.export') }}" data-testid="early-access-export">Download CSV</a></p>

    @if (session('success'))
      <p class="ap-alert ap-alert--success" role="status" data-testid="early-access-flash-success">{{ session('success') }}</p>
    @endif
    @if (session('error'))
      <p class="ap-alert ap-alert--error" role="alert" data-testid="early-access-flash-error">
        {{ session('error') }}
        @if (session('retry_invite_id'))
          <form method="POST" action="{{ route('platform.school-invites.resend', session('retry_invite_id')) }}" style="display:inline;">
            @csrf
            <button type="submit" class="ap-link" data-testid="early-access-flash-retry">Retry</button>
          </form>
        @endif
      </p>
    @endif

    <table data-testid="early-access-table" style="border-collapse:collapse;width:100%;">
      <thead>
        <tr>
          @foreach (['Received (UTC)', 'School', 'Contact', 'Role', 'Email', 'Phone', 'Country', 'Students', 'Invite'] as $heading)
            <th scope="col" style="text-align:left;padding:6px;border-bottom:1px solid currentColor;">{{ $heading }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($requests as $row)
          @php $invite = $invites->get(mb_strtolower(trim((string) $row->email))); @endphp
          <tr>
            <td style="padding:6px;">{{ $row->created_at?->copy()->utc()->format('Y-m-d H:i') }}</td>
            <td style="padding:6px;">{{ $row->school_name }}</td>
            <td style="padding:6px;">{{ $row->contact_name }}</td>
            <td style="padding:6px;">{{ $row->contact_role }}</td>
            <td style="padding:6px;">{{ $row->email }}</td>
            <td style="padding:6px;">{{ $row->phone }}</td>
            <td style="padding:6px;">{{ $row->country }}</td>
            <td style="padding:6px;">{{ $row->approx_students }}</td>
            <td style="padding:6px;white-space:nowrap;" data-testid="early-access-invite-{{ $row->id }}">
              @if (! $invite)
                <form method="POST" action="{{ route('platform.early-access.invite', $row) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link" data-testid="early-access-invite-button">Invite</button>
                </form>
              @elseif ($invite->isClaimed())
                <span data-testid="invite-status-claimed">Claimed {{ $invite->claimed_at->format('Y-m-d') }}</span>
              @elseif ($invite->isRevoked())
                <span data-testid="invite-status-revoked">Revoked</span>
                <form method="POST" action="{{ route('platform.school-invites.resend', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link">Resend</button>
                </form>
              @elseif ($invite->isExpired())
                <span data-testid="invite-status-expired">Expired</span>
                <form method="POST" action="{{ route('platform.school-invites.resend', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link">Resend</button>
                </form>
              @elseif ($invite->emailFailed())
                <span data-testid="invite-status-email-failed">Email not sent</span>
                <form method="POST" action="{{ route('platform.school-invites.resend', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link" data-testid="invite-retry-button">Retry</button>
                </form>
                <form method="POST" action="{{ route('platform.school-invites.revoke', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link">Revoke</button>
                </form>
              @else
                <span data-testid="invite-status-pending">Invited · expires {{ $invite->expires_at->format('Y-m-d') }}</span>
                <form method="POST" action="{{ route('platform.school-invites.resend', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link">Resend</button>
                </form>
                <form method="POST" action="{{ route('platform.school-invites.revoke', $invite) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="ap-link">Revoke</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="9" style="padding:6px;">No requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
    {{ $requests->links() }}
  </main>
</body>
</html>
