<form 
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
        type="submit"
        class="px-2 py-1 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition"
    >
        Finalize Promition
    </button>
</form>