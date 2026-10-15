{{-- SPDX-License-Identifier: MIT --}}
{{-- Platform admins only: early access requests. Plain styling on purpose. --}}
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
    <table data-testid="early-access-table" style="border-collapse:collapse;width:100%;">
      <thead>
        <tr>
          @foreach (['Received (UTC)', 'School', 'Contact', 'Role', 'Email', 'Phone', 'Country', 'Students'] as $heading)
            <th scope="col" style="text-align:left;padding:6px;border-bottom:1px solid currentColor;">{{ $heading }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($requests as $row)
          <tr>
            <td style="padding:6px;">{{ $row->created_at?->copy()->utc()->format('Y-m-d H:i') }}</td>
            <td style="padding:6px;">{{ $row->school_name }}</td>
            <td style="padding:6px;">{{ $row->contact_name }}</td>
            <td style="padding:6px;">{{ $row->contact_role }}</td>
            <td style="padding:6px;">{{ $row->email }}</td>
            <td style="padding:6px;">{{ $row->phone }}</td>
            <td style="padding:6px;">{{ $row->country }}</td>
            <td style="padding:6px;">{{ $row->approx_students }}</td>
          </tr>
        @empty
          <tr><td colspan="8" style="padding:6px;">No requests yet.</td></tr>
        @endforelse
      </tbody>
    </table>
    {{ $requests->links() }}
  </main>
</body>
</html>
