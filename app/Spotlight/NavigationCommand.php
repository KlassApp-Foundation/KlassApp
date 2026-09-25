<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Command-palette navigation (Step 4) — the destinations come from
 * `config/navigation.php`, the same source of truth the sidebars render from
 * (Step 1), so the palette can never drift from the sidebar.
 *
 * Scoping is SERVER-SIDE: `searchDestination()` only ever returns the entries
 * belonging to the authenticated user's navigation role, and `execute()` refuses
 * any destination outside that same set — so a crafted payload cannot navigate
 * somewhere the role has no sidebar access to.
 */

namespace App\Spotlight;

use App\Helpers\SiteHelper;
use App\Models\User;
use Illuminate\Support\Str;
use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;
use LivewireUI\Spotlight\SpotlightCommandDependencies;
use LivewireUI\Spotlight\SpotlightCommandDependency;
use LivewireUI\Spotlight\SpotlightSearchResult;

class NavigationCommand extends SpotlightCommand
{
    protected string $name = 'Go to…';

    protected string $description = 'Jump to a page you have access to';

    protected array $synonyms = ['navigate', 'jump', 'open', 'page', 'menu', 'sidebar'];

    /**
     * usergroup_id → navigation role key in config/navigation.php.
     *
     * Mirrors the real shells: SchoolSubadmin (4) reuses the admin UI
     * (see App\Http\Middleware\MustBeSchoolAdmin), and the dashboard side of the
     * same mapping lives in App\Helpers\AuthRedirectHelper.
     */
    public const ROLE_BY_USERGROUP = [
        1 => 'superadmin',
        3 => 'admin',
        4 => 'admin',
        5 => 'teacher',
        6 => 'student',
        7 => 'parent',
        8 => 'library',
        9 => 'alumni',
        10 => 'reception',
        11 => 'accountant',
        12 => 'stock',
    ];

    public static function roleFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return self::ROLE_BY_USERGROUP[(int) $user->usergroup_id] ?? null;
    }

    /**
     * Role-scoped destinations for a user: label keyed by resolved URL.
     *
     * @return array<string, string>
     */
    public static function destinationsFor(?User $user): array
    {
        $role = self::roleFor($user);
        $nav = $role ? config('navigation.roles.'.$role) : null;

        if (! is_array($nav)) {
            return [];
        }

        $isClassTeacher = null;
        $isCtSections = null;
        $out = [];

        $add = function (array $item) use (&$out, &$isClassTeacher, &$isCtSections, $user) {
            if (empty($item['label'])) {
                return;
            }

            // Resolver items (e.g. the parent's per-child Fees/Grades/Attendance entries)
            // need a linked child resolved against the authenticated user before they
            // have a URL; without one they would only ever produce a dead "/". The
            // sidebar renders them via the resolver — the palette skips them and offers
            // the Children page instead.
            if (! empty($item['resolver'])) {
                return;
            }

            if (($item['condition'] ?? null) === 'class_teacher') {
                if ($isClassTeacher === null) {
                    $isClassTeacher = $user && $user->school_id
                        ? SiteHelper::getClassTeacherStandardLinks((int) $user->school_id, (int) $user->id)->isNotEmpty()
                        : false;
                }
                if (! $isClassTeacher) {
                    return;
                }
            }

            // Mirrors sidebar-menu.blade.php: Class Streams is a class-teacher-of-a-section
            // item. A teacher without a school must never see it (and must not trigger a
            // DB read to find that out).
            if (($item['condition'] ?? null) === 'class_streams') {
                if ($isCtSections === null) {
                    $isCtSections = false;
                    if ($user && $user->school_id && (int) $user->usergroup_id === 5) {
                        $year = SiteHelper::getAcademicYear((int) $user->school_id);
                        if ($year) {
                            $isCtSections = ! empty(
                                app(\App\Services\ExamAuthorization::class)
                                    ->sectionIdsForClassTeacher($user, (int) $user->school_id, (int) $year->id)
                            );
                        }
                    }
                }
                if (! $isCtSections) {
                    return;
                }
            }

            $url = isset($item['route']) ? route($item['route']) : url($item['url'] ?? '/');
            if (! empty($item['hash'])) {
                $url .= '#'.$item['hash'];
            }

            $out[$url] = $item['label'];
        };

        foreach ($nav['items'] ?? [] as $item) {
            $add($item);
        }

        foreach ($nav['groups'] ?? [] as $group) {
            foreach ($group['items'] ?? [] as $item) {
                $add($item);
            }
        }

        foreach ($nav['footer'] ?? [] as $key => $value) {
            // single footer entry: ['label' => …, 'href' => …]
            if ($key === 'label' && is_string($value)) {
                $out[$nav['footer']['href']] = $value;
            }
        }

        return $out;
    }

    public function dependencies(): ?SpotlightCommandDependencies
    {
        return SpotlightCommandDependencies::collection()->add(
            SpotlightCommandDependency::make('destination')->setPlaceholder('Search your pages…')
        );
    }

    /**
     * @return array<int, SpotlightSearchResult>
     */
    public function searchDestination(string $query = ''): array
    {
        $query = trim($query);

        return collect(self::destinationsFor(auth()->user()))
            ->map(fn (string $label, string $url) => new SpotlightSearchResult(
                $url,
                $label,
                null,
                [Str::lower($label)]
            ))
            ->when($query !== '', fn ($results) => $results->filter(
                fn (SpotlightSearchResult $r) => Str::contains(Str::lower($r->getName()), Str::lower($query))
            ))
            ->values()
            ->all();
    }

    public function execute(Spotlight $spotlight, ?string $destination = null): void
    {
        $allowed = array_keys(self::destinationsFor(auth()->user()));

        // Defence in depth: only ever navigate within this role's own sidebar scope.
        if ($destination !== null && in_array($destination, $allowed, true)) {
            $spotlight->redirect($destination);
        }
    }

    public function shouldBeShown(): bool
    {
        return self::destinationsFor(auth()->user()) !== [];
    }
}
