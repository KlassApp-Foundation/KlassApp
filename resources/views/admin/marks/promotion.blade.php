{{-- 
    PROMOTION FORM PARTIAL
    Structurally NOT a marks grid — it's a promotion action button with confirmation dialog.
    The ds-grid-marks pattern does not apply to this view.
    Included only by filter.blade.php for End Of Year exam type.
--}}
<form
    id="promotionForm"
    method="GET"
    action="{{ route('admin.students.promote') }}"
    class="flex items-end justify-end gap-2"
>
    <input
        type="hidden"
        name="sectionId"
        value="{{ $class->id }}"
    >

    <button
        type="button"
        id="promoteBtn"
        class="ds-btn ds-btn-primary"
    >
        Finalize Promotion
    </button>
</form>

@push('scripts')
<script>
$(document).ready(function () {

    $('#promoteBtn').on('click', function () {

        swal({
            title: "Finalize Student Promotion?",
            text: "This action will promote eligible students to the next class. Ensure report cards have been downloaded and results have been verified before continuing.",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancel",
                    value: false,
                    visible: true
                },
                confirm: {
                    text: "Yes, Finalize",
                    value: true
                }
            },
            dangerMode: true,
        })
        .then((confirmed) => {

            if (confirmed) {

                swal({
                    title: "Processing...",
                    text: "Promoting students. Please wait.",
                    icon: "info",
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });

                $('#promotionForm').submit();
            }
        });
    });

});
</script>
@endpush