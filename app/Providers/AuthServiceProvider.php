<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Airlock\PersonalAccessToken;
use Illuminate\Support\Facades\Gate;
use App\Policies\ApiTokenPolicy;
use App\Models\Homework;
use App\Models\Plan;
use App\Models\StudentHomework;
use App\Models\Teacherlink;
use App\Models\User;
use App\Policies\ConversationPolicy;
use Laravel\Ai\Models\Conversation;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
      'App\Models\AcademicYear'     => 'App\Policies\AcademicYearPolicy',
      'App\Models\ActivityLog'      => 'App\Policies\ActivityLogPolicy',
      'App\Models\User'             => 'App\Policies\AdminPolicy',
      'App\Models\City'             => 'App\Policies\CityPolicy',
      'App\Models\Query'            => 'App\Policies\ContactPolicy',
      'App\Models\Country'          => 'App\Policies\CountryPolicy',
      'App\Models\ParentProfile'    => 'App\Policies\ParentProfilePolicy',
      'App\Models\User'             => 'App\Policies\ParentUserPolicy',
      'App\Models\Plan'             => 'App\Policies\PlanPolicy',
      'App\Models\Qualification'    => 'App\Policies\QualificationPolicy',
      'App\Models\School'           => 'App\Policies\SchoolPolicy',
      'App\Models\Section'          => 'App\Policies\SectionPolicy',
      'App\Models\Standard'         => 'App\Policies\StandardPolicy',
      'App\Models\StandardLink'     => 'App\Policies\StandardLinkPolicy',

      'App\Models\StudentAcademic'  => 'App\Policies\StudentAcademicPolicy',
      'App\Models\Subscription'     => 'App\Policies\SubscriptionPolicy',
      'App\Models\TeacherProfile'   => 'App\Policies\TeacherProfilePolicy',
      'App\Models\Usergroup'        => 'App\Policies\UsergroupPolicy',
      'App\Models\User'             => 'App\Policies\UserPolicy',
      'App\Models\Userprofile'      => 'App\Policies\UserprofilePolicy',
      PersonalAccessToken::class    => ApiTokenPolicy::class,
      Conversation::class           => ConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
      $this->registerPolicies();
      
      Gate::define('event', function ($user, $event) {
        return $user->school_id == $event->school_id;
      });

      // Destroy is ug1 (SiteAdmin) unscoped or ug3 (SchoolAdmin) school-scoped.
      // Keep the school_id-only `event` Gate unchanged for five-role details reads.
      Gate::define('event-destroy', function ($user, $event) {
        if ($event === null) {
          return false;
        }

        if ((int) $user->usergroup_id === 1) {
          return true;
        }

        if ((int) $user->usergroup_id === 3) {
          return (int) $user->school_id === (int) $event->school_id;
        }

        return false;
      });

      Gate::define('member', function ($user, $member) {
        return $user->school_id == $member->school_id;
      });

      Gate::define('academic', function ($user, $academic) {
        return $user->school_id == $academic->school_id;
      });

      // Teacher / Teacher API destroy only (ug5): school_id + teacher_id ownership.
      // Matches CreateHomeworkTool / TeacherActionService::createHomework (teacher_id = actor).
      // Admin moderate/destroy uses homework-manage (ug1/ug3) — ug3 must not rely on this Gate.
      Gate::define('homework', function ($user, $homework) {
        if ($homework === null) {
          return false;
        }

        if ((int) $user->usergroup_id !== 5) {
          return false;
        }

        return (int) $user->school_id === (int) $homework->school_id
          && (int) $user->id === (int) $homework->teacher_id;
      });

      // Admin moderate / mutate: approve·reject·bulk, admin show/edit/update/view, admin destroy.
      // ug1 unscoped or ug3 school_id (Option A). ug5 denied — teachers use `homework` for destroy.
      Gate::define('homework-manage', function ($user, $homework) {
        if ($homework === null) {
          return false;
        }

        if ((int) $user->usergroup_id === 1) {
          return true;
        }

        if ((int) $user->usergroup_id === 3) {
          return (int) $user->school_id === (int) $homework->school_id;
        }

        return false;
      });

      Gate::define('document', function ($user, $document) {
        return $user->school_id == $document->school_id;
      });

      Gate::define('fee', function ($user, $fee) {
        return $user->school_id == $fee->school_id;
      });

      Gate::define('notice', function ($user, $notice) {
        return $user->school_id == $notice->school_id;
      });

      // Read/show: school_id match for any role that can view classwall.
      Gate::define('post', function ($user, $post) {
        if ($post === null) {
          return false;
        }

        return (int) $user->school_id === (int) $post->school_id;
      });

      // Destroy: author, or ug3 moderate-delete in same school, or ug1 unscoped.
      // ug1 evidence: MustBeSchoolAdmin lets SiteAdmin onto /admin/* (including
      // classwall post destroy); MustBeTeacher redirects ug1 away from /teacher/*.
      Gate::define('post-destroy', function ($user, $post) {
        if ($post === null) {
          return false;
        }

        if ((int) $post->created_by === (int) $user->id) {
          return true;
        }

        if ((int) $user->usergroup_id === 1) {
          return true;
        }

        if ((int) $user->usergroup_id === 3) {
          return (int) $user->school_id === (int) $post->school_id;
        }

        return false;
      });

      Gate::define('post_reply', function ($user, $post) {
        return $user->school_id == $post_reply->post->school_id;
      });

      Gate::define('page', function ($user, $page) {
        return $user->school_id == $page->school_id;
      });

      Gate::define('page_attachment', function ($user, $page_attachment) {
        return $user->school_id == $page_attachment->classRoomPage->school_id;
      });

      Gate::define('subject', function ($user, $subject) {
        return $user->school_id == $subject->school_id;
      });

      Gate::define('exam', function ($user, $exam) {
        return $user->school_id == $exam->school_id;
      });

      Gate::define('book', function ($user, $book) {
        return $user->school_id == $book->school_id;
      });

      Gate::define('category', function ($user, $category) {
        return $user->school_id == $category->school_id;
      });

      Gate::define('standardlink', function ($user, $standardlink) {
        return $user->school_id == $standardlink->school_id;
      });

      Gate::define('standard', function ($user, $standard) {
        return $user->school_id == $standard->school_id;
      });

      Gate::define('section', function ($user, $section) {
        return $user->school_id == $section->school_id;
      });

      Gate::define('bulletin', function ($user, $bulletin) {
        return $user->school_id == $bulletin->school_id;
      });

      Gate::define('video', function ($user, $video) {
        return $user->school_id == $video->school_id;
      });

      Gate::define('note', function ($user, $note) {
        return $user->school_id == $note->school_id;
      });

      Gate::define('lessonplan', function ($user, $lessonplan) {
        return $user->school_id == $lessonplan->teacherlink->school_id;
      });

      Gate::define('assignment', function ($user, $assignment) {
        return $user->school_id == $assignment->school_id;
      });

      Gate::define('studentassignment', function ($user, $studentassignment) {
        if ($studentassignment === null || $studentassignment->assignment === null) {
          return false;
        }

        return (int) $user->id === (int) $studentassignment->user_id
          && (int) $user->school_id === (int) $studentassignment->assignment->school_id;
      });

      // Student owner-only (#130) — do not widen for staff review.
      Gate::define('studentHomework', function ($user, $studentHomework) {
        if ($studentHomework === null || $studentHomework->homework === null) {
          return false;
        }

        return (int) $user->id === (int) $studentHomework->user_id
          && (int) $user->school_id === (int) $studentHomework->homework->school_id;
      });

      // Staff review of submissions (admin / teacher / teacher API). Accepts Homework (list) or StudentHomework.
      // ug5: assigned class/subject only — teacher_id, class teacher, or Teacherlink (same as CreateHomeworkTool).
      Gate::define('studentHomework-review', function ($user, $model) {
        $homework = null;

        if ($model instanceof Homework) {
          $homework = $model;
        } elseif ($model instanceof StudentHomework) {
          if ($model->homework === null) {
            $model->loadMissing('homework');
          }
          $homework = $model->homework;
        }

        if ($homework === null) {
          return false;
        }

        if ((int) $user->usergroup_id === 1) {
          return true;
        }

        if ((int) $user->usergroup_id === 3) {
          return (int) $user->school_id === (int) $homework->school_id;
        }

        if ((int) $user->usergroup_id === 5) {
          if ((int) $user->school_id !== (int) $homework->school_id) {
            return false;
          }

          if ((int) $homework->teacher_id === (int) $user->id) {
            return true;
          }

          if ($homework->relationLoaded('standardLink') === false) {
            $homework->loadMissing('standardLink');
          }

          if ($homework->standardLink !== null
            && (int) $homework->standardLink->class_teacher_id === (int) $user->id) {
            return true;
          }

          return Teacherlink::where('teacher_id', $user->id)
            ->where('standardLink_id', $homework->standardLink_id)
            ->where('subject_id', $homework->subject_id)
            ->exists();
        }

        return false;
      });

      Gate::define('subscription', function ($user, $subscription) {
        return $user->school_id == $subscription->school_id;
      });

      Gate::define('payment', function ($user, $id) {
        $plans = Plan::pluck('id')->toArray();

        foreach ($plans as $plan) 
        {
          if($plan == $id)
          return true;
        }
        
      });

        // ── Toshi SDK v2 Authorization ──
        //
        // Single source of truth for all 23 SDK v2 Tool classes.
        // Rule matches MustBeSchoolAdmin middleware (admin routes):
        //   - SchoolAdmin (ug3) → authorized within own school
        //   - Superadmin (ug1)  → authorized ONLY when impersonating a SchoolAdmin
        //   - All others        → denied
        //
        // This Gate is the enforcement layer. ToshiActionService::getRoleCapabilities()
        // is the advisory LLM hint layer — they serve different purposes.
        Gate::define('toshi-school-action', function (User $user): \Illuminate\Auth\Access\Response {
            // SchoolAdmin (ug3) — core authorized role for school-level tools
            if ($user->usergroup_id === 3) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            // Superadmin (ug1) — only when impersonating a SchoolAdmin
            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 3) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this school action.');
        });

        // Deputy Admin–scoped Toshi tools only (ug4). Keep toshi-school-action ug3-only —
        // structural isolation via DeputyAdminOperationsAgent::tools() (excludes owner-level
        // AddCoAdminTool + SetCurriculumTool), not widening this Gate.
        Gate::define('toshi-deputy-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 4 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 4 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this deputy admin action.');
        });

        // Teacher-scoped Toshi tools only (ug5). Keep toshi-school-action ug3-only —
        // structural isolation via TeacherOperationsAgent::tools(), not widening this Gate.
        Gate::define('toshi-teacher-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 5 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 5 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this teacher action.');
        });

        // Accountant-scoped Toshi tools only (ug11). Do not widen toshi-school-action
        // or toshi-teacher-action — isolation via AccountantOperationsAgent::tools().
        Gate::define('toshi-accountant-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 11 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 11 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this accountant action.');
        });

        // Librarian-scoped Toshi tools only (ug8). Do not widen school/teacher/accountant Gates.
        Gate::define('toshi-librarian-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 8 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 8 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this librarian action.');
        });

        // Receptionist-scoped Toshi tools only (ug10). Do not widen school/teacher/accountant/librarian Gates.
        Gate::define('toshi-receptionist-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 10 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 10 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this receptionist action.');
        });

        // Student-scoped Toshi tools only (ug6). Self-scope ownership is enforced in tools/service.
        Gate::define('toshi-student-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 6 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 6 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this student action.');
        });

        // Parent-scoped Toshi tools only (ug7). Children ownership is enforced in ParentActionService.
        Gate::define('toshi-parent-action', function (User $user): \Illuminate\Auth\Access\Response {
            if ($user->usergroup_id === 7 && $user->school_id) {
                return \Illuminate\Auth\Access\Response::allow();
            }

            if ($user->usergroup_id === 1 && $user->isImpersonating()) {
                $impersonated = User::find(\Session::get('impersonate'));
                if ($impersonated && $impersonated->usergroup_id === 7 && $impersonated->school_id) {
                    return \Illuminate\Auth\Access\Response::allow();
                }
            }

            return \Illuminate\Auth\Access\Response::deny('You are not authorized for this parent action.');
        });
    }
}