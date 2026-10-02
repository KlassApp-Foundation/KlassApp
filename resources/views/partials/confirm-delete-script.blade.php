{{-- SPDX-License-Identifier: MIT --}}
{{--
  Confirmation gate for destructive profile actions.

  Any <form data-confirm-delete="message"> is held until the user confirms. Uses SweetAlert
  when the page loaded it, else the browser's native confirm(), so the gate can never be
  skipped just because a CDN script failed to load.
--}}
@push('scripts')
<script type="text/javascript">
    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form || !form.matches || !form.matches('form[data-confirm-delete]')) {
            return;
        }

        if (form.getAttribute('data-confirmed') === '1') {
            return;
        }

        event.preventDefault();

        var message = form.getAttribute('data-confirm-delete') || 'Delete this record? This cannot be undone.';
        var proceed = function () {
            form.setAttribute('data-confirmed', '1');
            form.submit();
        };

        if (window.swal) {
            window.swal({
                icon: 'warning',
                title: 'Delete?',
                text: message,
                buttons: { cancel: 'Cancel', confirm: { text: 'Delete', closeModal: true } },
                dangerMode: true
            }).then(function (confirmed) {
                if (confirmed) {
                    proceed();
                }
            });
        } else if (window.confirm(message)) {
            proceed();
        }
    });
</script>
@endpush
