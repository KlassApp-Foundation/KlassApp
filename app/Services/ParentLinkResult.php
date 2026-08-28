<?php

namespace App\Services;

use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParentLinkResult
{
    public function __construct(
        public readonly bool $linked,
        public readonly string $outcome,
        public readonly ?User $parent = null,
        public readonly ?User $student = null,
        public readonly ?string $studentName = null,
        public readonly ?string $className = null,
        public readonly ?string $schoolName = null,
        public readonly bool $isNewParent = false,
        public readonly bool $alreadyLinkedToThisParent = false,
    ) {}

    public static function notLinked(string $outcome): self
    {
        return new self(linked: false, outcome: $outcome);
    }
}
