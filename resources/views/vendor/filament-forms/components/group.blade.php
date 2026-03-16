{{-- SPDX-License-Identifier: MIT --}}
<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
    }}
>
    {{ $getChildComponentContainer() }}
</div>
